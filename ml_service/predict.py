import sys
import json
import warnings
import os
import traceback
from pathlib import Path
from random_forest import train_random_forest_and_forecast

# ── Ensure local ml_service modules are importable regardless of cwd ────────────
_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

try:
    import numpy as np
    import pandas as pd
    from datetime import timedelta
    from sqlalchemy import create_engine, text
    from sklearn.preprocessing import StandardScaler
    from sklearn.metrics import mean_squared_error, r2_score
    from lstm import train_and_forecast
    from xgboost import train_xgboost_and_forecast
except Exception as _import_error:
    print(json.dumps({"error": f"Import failed: {_import_error}"}))
    sys.exit(1)

warnings.filterwarnings('ignore')


# ──────────────────────────────────────────────────────────────────────────────
# DATA LOADING
# ──────────────────────────────────────────────────────────────────────────────
def get_stock_id(symbol, engine):
    query = text("""
        SELECT id FROM stocks WHERE symbol = :symbol
    """)
    result = pd.read_sql(query, engine, params={"symbol": symbol})

    if result.empty:
        return None
    return int(result.iloc[0]['id'])





def load_data(symbol, engine):
    """
    Load OHLCV data for a stock symbol from the arthapredict database.

    SECURITY FIX: Uses SQLAlchemy text() with a named bound parameter (:symbol)
    instead of f-string interpolation, preventing SQL injection attacks.
    The WHERE clause is now parameterised — the driver escapes the symbol value
    before it reaches the database engine.
    """
    query = text("""
        SELECT p.date, p.open, p.high, p.low, p.close, p.volume
        FROM stock_prices p
        JOIN stocks s ON p.stock_id = s.id
        WHERE s.symbol = :symbol
        ORDER BY p.date ASC
    """)
    # Pass params dict; SQLAlchemy handles escaping and quoting.
    df = pd.read_sql(query, engine, params={"symbol": symbol})
    return df


# ──────────────────────────────────────────────────────────────────────────────
# DATA CLEANING
# ──────────────────────────────────────────────────────────────────────────────

def clean_data(df):
    """
    Coerce types, sort chronologically, and fill gaps without dropping rows.

    Missing-value strategy: forward-fill → backward-fill → linear interpolation.
    This preserves the exact row count, which is required for consistent
    feature-array alignment later in the pipeline.
    """
    sys.stderr.write(f"--- DATASET SHAPE BEFORE PROCESSING: {df.shape} ---\n")
    sys.stderr.write("--- SAMPLE (first 5 rows, before cleaning) ---\n")
    sys.stderr.write(str(df.head()) + "\n")
    df.info(buf=sys.stderr)

    df['date'] = pd.to_datetime(df['date'])
    numeric_cols = ['open', 'high', 'low', 'close', 'volume']

    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors='coerce')

    df = df.sort_values('date').reset_index(drop=True)

    # Fill gaps: ffill handles leading NaNs, bfill handles trailing, interpolate
    # handles interior gaps more smoothly than step-wise fill.
    df[numeric_cols] = (
        df[numeric_cols]
        .ffill()
        .bfill()
        .interpolate(method='linear')
    )

    return df


# ──────────────────────────────────────────────────────────────────────────────
# FEATURE ENGINEERING
# ──────────────────────────────────────────────────────────────────────────────

def engineer_features(df):
    """
    Add price-derived technical features to the dataframe.

    Features added:
        price_change  : close − open  (intra-day move)
        returns       : daily percentage change in close price
        SMA_5/10/20   : simple moving averages (trend proxies)
        rolling_std   : 10-day close std deviation (volatility proxy)

    min_periods=1 ensures no NaN rows are introduced for the rolling windows,
    so the dataframe row count is unchanged after this step.
    """
    df['price_change'] = df['close'] - df['open']
    df['returns']      = df['close'].pct_change().fillna(0)
    df['SMA_5']        = df['close'].rolling(window=5,  min_periods=1).mean()
    df['SMA_10']       = df['close'].rolling(window=10, min_periods=1).mean()
    df['SMA_20']       = df['close'].rolling(window=20, min_periods=1).mean()
    df['rolling_std']  = df['close'].rolling(window=10, min_periods=1).std().fillna(0)
    return df


# ──────────────────────────────────────────────────────────────────────────────
# PREPROCESSING / SCALING
# ──────────────────────────────────────────────────────────────────────────────

def preprocess_data(df):
    """
    Build and scale the feature matrix and construct the supervised target.

    NOTE: X_scaled is currently unused by the LSTM and XGBoost calls below
    (both models operate on the raw close-price series and build their own
    internal feature representations).  The feature matrix is retained here
    for forward compatibility — a future improvement would pass X_scaled into
    XGBoost for richer multivariate prediction.

    Target construction:
        target[t] = close[t+1]  (next-day close price)
        target[-1] = close[-1]   (last row has no future; filled with self)
    This is a valid supervised regression formulation.  Note that the target
    is one step ahead, so any feature derived from close[t] is safe — there
    is no leakage of future prices into the feature set.
    """
    features = [
        'open', 'high', 'low', 'volume',
        'price_change', 'returns',
        'SMA_5', 'SMA_10', 'SMA_20', 'rolling_std',
    ]

    for col in features:
        if df[col].isnull().any():
            fill = df[col].mean()
            df[col] = df[col].fillna(fill if not np.isnan(fill) else 0.0)

    # FIXED: Target leakage - drop NaN targets instead of filling with current close
    df['target'] = df['close'].shift(-1)
    df_valid = df.dropna(subset=['target']).copy()

    X      = df_valid[features].values
    y      = df_valid['target'].values
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    return X_scaled, y, df_valid, scaler, features


# ──────────────────────────────────────────────────────────────────────────────
# MOVING AVERAGE BASELINE
# ──────────────────────────────────────────────────────────────────────────────

def train_ma_model(df, window=20):
    """
    Evaluate the Moving Average as a 1-step-ahead baseline on the full history.

    Prediction at time t: mean of the previous `window` close prices.
    The shift(1) ensures no data leakage (we use past values only).
    MSE and R² are reported on all rows where a prediction is available.

    NOTE: In-sample evaluation is acceptable HERE because MA is a non-parametric
    baseline — it has no trainable parameters that could overfit to the training
    data.  The comparison against LSTM / XGBoost is still meaningful since those
    models ARE evaluated on a held-out validation set.
    """
    y_pred    = df['close'].rolling(window=window, min_periods=1).mean().shift(1)
    valid_idx = y_pred.notna()
    y_true    = df['close'][valid_idx]
    y_pred_v  = y_pred[valid_idx]

    if len(y_true) > 0:
        mse = mean_squared_error(y_true, y_pred_v)
        r2  = r2_score(y_true, y_pred_v)
    else:
        mse, r2 = 0.0, 0.0

    sys.stderr.write(
        f"--- BASELINE (Moving Average, window={window}) ---\n"
        f"MSE: {mse:.4f}, R²: {r2:.4f}\n"
    )
    return mse, r2


def forecast_ma(df, days=30, window=20):
    """
    30-day forecast using Holt's Double Exponential Smoothing.

    PREVIOUS ISSUE — FIXED:
    Old implementation: recursive simple moving average.
        history ← last `window` real prices
        for each step: pred = mean(history[-window:]); history.append(pred)
    This is MATHEMATICALLY GUARANTEED to converge to a flat line because
    each appended prediction is the mean of a window that increasingly
    consists of predictions rather than real prices.  By step ~20 the
    forecast is essentially constant — a pseudo-prediction, not a forecast.

    NEW APPROACH — Holt's Double Exponential Smoothing (Holt, 1957):
    Tracks two components:
        Level:  L_t = α · y_t + (1−α) · (L_{t-1} + T_{t-1})
        Trend:  T_t = β · (L_t − L_{t-1}) + (1−β) · T_{t-1}
    h-step forecast: F_{t+h} = L_t + h · T_t

    This is a well-established time-series baseline that:
        • Captures the most recent trend direction.
        • Does NOT collapse to a flat line.
        • Is simple, interpretable, and viva-defensible.

    Parameters for NEPSE calibration:
        α = 0.3  (moderate level adaptation — NEPSE trends are slow-moving)
        β = 0.1  (gentle trend component — avoids over-reacting to noise)

    Note: Holt's method is equivalent to ARIMA(0,2,2) with specific constraints,
    giving it a solid theoretical footing in the time-series literature.
    """
    prices = df['close'].values.astype(float)
    alpha  = 0.3
    beta   = 0.1

    # Initialise level and trend on the first two observations
    level = prices[0]
    trend = prices[1] - prices[0] if len(prices) > 1 else 0.0

    for price in prices[1:]:
        prev_level = level
        level = alpha * price + (1 - alpha) * (level + trend)
        trend = beta  * (level - prev_level) + (1 - beta) * trend

    # Multi-step forecast: level + h × trend
    forecast = [float(level + h * trend) for h in range(1, days + 1)]
    return forecast


# ──────────────────────────────────────────────────────────────────────────────
# ──────────────────────────────────────────────────────────────────────────────
# PROGRESS TRACKING HELPER
# ──────────────────────────────────────────────────────────────────────────────

def update_progress(engine, job_id, stage, processed_rows, total_rows):
    if not job_id:
        return
    try:
        with engine.connect() as conn:
            conn.execute(
                text("""
                    UPDATE model_training_jobs
                    SET current_stage = :stage,
                        processed_rows = :processed_rows,
                        total_rows = :total_rows,
                        updated_at = NOW()
                    WHERE id = :job_id
                """),
                {
                    "stage": stage,
                    "processed_rows": int(processed_rows),
                    "total_rows": int(total_rows),
                    "job_id": int(job_id)
                }
            )
            if hasattr(conn, 'commit'):
                conn.commit()
    except Exception as e:
        sys.stderr.write(f"Failed to update progress: {str(e)}\n")


# MAIN PIPELINE
# ──────────────────────────────────────────────────────────────────────────────

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Stock symbol required"}))
        sys.exit(1)

    symbol = sys.argv[1]
    job_id = None
    if len(sys.argv) > 2:
        try:
            job_id = int(sys.argv[2])
        except ValueError:
            pass

    try:
        # Load environment variables from .env if present
        env_path = Path(__file__).parent.parent / '.env'
        if env_path.exists():
            with open(env_path, 'r') as f:
                for line in f:
                    line = line.strip()
                    if line and not line.startswith('#') and '=' in line:
                        key, val = line.split('=', 1)
                        # Only set if not already in environment
                        if key.strip() not in os.environ:
                            os.environ[key.strip()] = val.strip().strip('"').strip("'")

        db_host = os.environ.get('DB_HOST', '127.0.0.1')
        db_port = os.environ.get('DB_PORT', '3306')
        db_database = os.environ.get('DB_DATABASE', 'arthapredict')
        db_username = os.environ.get('DB_USERNAME', 'root')
        db_password = os.environ.get('DB_PASSWORD', '')

        engine_url = f"mysql+pymysql://{db_username}:{db_password}@{db_host}:{db_port}/{db_database}"
        engine = create_engine(engine_url)
        stock_id = get_stock_id(symbol, engine)
        if not stock_id:
            print(json.dumps({"error": "Invalid stock symbol"}))
            sys.exit(0)
        # ── Load ──────────────────────────────────────────────────────────
        df = load_data(symbol, engine)

        if df.empty:
            print(json.dumps({"error": f"No data found for symbol {symbol}"}))
            sys.exit(0)

        if len(df) < 10:
            print(json.dumps({
                "error": (
                    f"Insufficient data for {symbol}. "
                    f"At least 10 rows required, got {len(df)}."
                )
            }))
            sys.exit(0)

        # ── Process ────────────────────────────────────────────────────────
        df_clean    = clean_data(df)
        df_features = engineer_features(df_clean)

        # preprocess_data adds the 'target' column and scales X; X_scaled is
        # retained for future use (see preprocess_data docstring).
        X_scaled, y, df_processed, scaler, feature_cols = preprocess_data(df_features)

        sys.stderr.write(f"--- DATASET SHAPE AFTER PROCESSING: {df_processed.shape} ---\n")

        # ── Moving Average baseline ─────────────────────────────────────────
        ma_mse, ma_r2 = train_ma_model(df_processed, window=20)

        # FIXED: Uses Holt's Double Exponential Smoothing (was: converging recursive MA)
        ma_forecast_full = forecast_ma(df_processed, days=30, window=20)

        # ── Prepare close-price series for time-series models ──────────────
        current_price = float(df_processed['close'].iloc[-1])
        last_date     = df_processed['date'].max()
        y_close       = df_processed['close'].values.astype(float)

        # Sequence length: more conservative formula for small NEPSE datasets.
        # FIXED: was len(y_close)//3 — too large for ~100-row datasets.
        seq_len = min(20, max(5, len(y_close) // 4))
        sys.stderr.write(f"--- LSTM sequence_length selected: {seq_len} ---\n")

        # Configured for fast execution while maintaining accuracy
        lstm_epochs = 50
        lstm_hidden = 16
        xgb_estimators = 50

        # Calculate exact training row counts
        # LSTM: len(X) = len(y_close) - seq_len. split_idx = max(int(len(X) * 0.80), 1)
        n_seq_lstm = len(y_close) - seq_len
        len_train_lstm = max(int(n_seq_lstm * 0.80), 1)

        # XGBoost: len(X) = len(y_close) - 10. split_idx = max(int(len(X) * 0.80), 1)
        n_seq_xgb = len(y_close) - 10
        len_train_xgb = max(int(n_seq_xgb * 0.80), 1)

        # Random Forest: Same as XGBoost for simplicity
        n_seq_rf = len(y_close) - 10
        len_train_rf = max(int(n_seq_rf * 0.80), 1)

        total_rows = (lstm_epochs * len_train_lstm) + (xgb_estimators * len_train_xgb) + (100 * len_train_rf)

        # Progress callbacks
        def lstm_progress(epoch):
            processed = epoch * len_train_lstm
            update_progress(engine, job_id, "Training LSTM", processed, total_rows)

        def xgb_progress(estimator):
            processed = (lstm_epochs * len_train_lstm) + (estimator * len_train_xgb)
            update_progress(engine, job_id, "Training XGBoost", processed, total_rows)

        def rf_progress(tree_num):
            processed = (lstm_epochs * len_train_lstm) + (xgb_estimators * len_train_xgb) + (tree_num * len_train_rf)
            update_progress(engine, job_id, "Training Random Forest", processed, total_rows)

        use_cache = '--use-cache' in sys.argv
        cached_only = '--cached-only' in sys.argv or '--predict-only' in sys.argv
        force_retrain = '--force-retrain' in sys.argv
        train_only = '--train-only' in sys.argv

        # ── LSTM ─────────────────────────────────────────────────────────────
        lstm_result = train_and_forecast(
            y_close,
            symbol=symbol,
            sequence_length=seq_len,
            epochs=lstm_epochs,
            hidden_size=lstm_hidden,
            force_retrain=force_retrain,
            use_cache=use_cache,
            cached_only=cached_only,
            progress_callback=lstm_progress
        )
        lstm_forecast = lstm_result['forecast']
        lstm_metrics  = lstm_result['metrics']

        # Extract LSTM registry fields (FIX: capture these!)
        lstm_registry = {
            "path": lstm_result.get("path"),
            "latest_path": lstm_result.get("latest_path"),
            "fingerprint": lstm_result.get("fingerprint"),
            "training_date": lstm_result.get("training_date"),
            "config": lstm_result.get("config"),
        }

        # ── Random Forest ──────────────────────────────────────────────────────────
        rf_result = train_random_forest_and_forecast(
            y_close,
            symbol=symbol,
            n_estimators=100,
            max_depth=10,
            force_retrain=force_retrain,
            use_cache=use_cache,
            cached_only=cached_only,
            progress_callback=rf_progress
        )
        rf_forecast = rf_result['forecast']
        rf_metrics = rf_result['metrics']

        # Extract Random Forest registry fields
        rf_registry = {
            "path": rf_result.get("path"),
            "latest_path": rf_result.get("latest_path"),
            "fingerprint": rf_result.get("fingerprint"),
            "training_date": rf_result.get("training_date"),
            "config": rf_result.get("config"),
        }

        # ── XGBoost ──────────────────────────────────────────────────────────
        xgb_result = train_xgboost_and_forecast(
            y_close,
            symbol=symbol,
            n_estimators=xgb_estimators,
            force_retrain=force_retrain,
            use_cache=use_cache,
            cached_only=cached_only,
            progress_callback=xgb_progress
        )
        xgb_forecast = xgb_result['forecast']
        xgb_metrics  = xgb_result['metrics']

        # Extract XGBoost registry fields (FIX: capture these!)
        xgb_registry = {
            "path": xgb_result.get("path"),
            "latest_path": xgb_result.get("latest_path"),
            "fingerprint": xgb_result.get("fingerprint"),
            "training_date": xgb_result.get("training_date"),
            "config": xgb_result.get("config"),
        }

        update_progress(engine, job_id, "Saving Predictions", total_rows, total_rows)

        models_info = [
            {
                "model_type": "lstm",
                "metrics": lstm_metrics,
                "data_length": len(y_close),
                # FIX: Add registry fields here
                "path": lstm_registry["path"],
                "latest_path": lstm_registry["latest_path"],
                "fingerprint": lstm_registry["fingerprint"],
                "training_date": lstm_registry["training_date"],
                "config": lstm_registry["config"]
            },
            {
                "model_type": "xgboost",
                "metrics": xgb_metrics,
                "data_length": len(y_close),
                # FIX: Add registry fields here
                "path": xgb_registry["path"],
                "latest_path": xgb_registry["latest_path"],
                "fingerprint": xgb_registry["fingerprint"],
                "training_date": xgb_registry["training_date"],
                "config": xgb_registry["config"]
            },
            {
                "model_type": "random_forest",  # Add this
                "metrics": rf_metrics,
                "data_length": len(y_close),
                "path": rf_registry["path"],
                "latest_path": rf_registry["latest_path"],
                "fingerprint": rf_registry["fingerprint"],
                "training_date": rf_registry["training_date"],
                "config": rf_registry["config"]
            },
        ]

        predictions = []
        if not train_only:
            targets = [
                ("1 Day",   1),
                ("1 Week",  7),
                ("1 Month", 30),
            ]

            for label, days_ahead in targets:
                target_date     = last_date + timedelta(days=days_ahead)
                target_date_str = target_date.strftime('%Y-%m-%d')
                idx             = days_ahead - 1

                xgb_pred  = max(0.01, float(xgb_forecast[idx]))
                lstm_pred = max(0.01, float(lstm_forecast[idx]))
                rf_pred   = max(0.01, float(rf_forecast[idx]))

                predictions.append({
                    "model_type":         "xgboost",
                    "target_date":        target_date_str,
                    "predicted_price":    round(xgb_pred,  2),
                    "additional_metrics": xgb_metrics,
                })
                predictions.append({
                    "model_type": "random_forest",
                    "target_date": target_date_str,
                    "predicted_price": round(rf_pred, 2),
                    "additional_metrics": rf_metrics,
                })
                predictions.append({
                    "model_type":         "lstm",
                    "target_date":        target_date_str,
                    "predicted_price":    round(lstm_pred, 2),
                    "additional_metrics": lstm_metrics,
                })

        result = {
            "symbol":        symbol,
            "current_price": round(current_price, 2),
            "models":        models_info,
            "predictions":   predictions,
        }

        print(json.dumps(result))

    except Exception as exc:
        print(json.dumps({"error": str(exc)}))
        sys.exit(0)


if __name__ == "__main__":
    main()
