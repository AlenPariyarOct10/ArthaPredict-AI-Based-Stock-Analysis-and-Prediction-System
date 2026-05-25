import sys
import json
import warnings
import pandas as pd
import numpy as np
from datetime import timedelta
from sqlalchemy import create_engine
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_squared_error, r2_score

from lstm import train_and_forecast
from xgboost import train_xgboost_and_forecast

warnings.filterwarnings('ignore')

def load_data(symbol, engine):
    query = f"""
        SELECT p.date, p.open, p.high, p.low, p.close, p.volume
        FROM stock_prices p
        JOIN stocks s ON p.stock_id = s.id
        WHERE s.symbol = '{symbol}'
        ORDER BY p.date ASC
    """
    df = pd.read_sql(query, engine)
    return df

def clean_data(df):
    # Print df.head() and df.info() as requested to stderr
    sys.stderr.write(f"--- DATASET SHAPE BEFORE PROCESSING: {df.shape} ---\n")
    sys.stderr.write("--- BEFORE CLEANING ---\n")
    sys.stderr.write(str(df.head()) + "\n")
    df.info(buf=sys.stderr)
    
    # Handle missing values: convert date to datetime, ensure numeric
    df['date'] = pd.to_datetime(df['date'])
    numeric_cols = ['open', 'high', 'low', 'close', 'volume']
    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors='coerce')
        
    # Sort data by date ascending
    df = df.sort_values('date')
    
    # Fill missing timestamps with interpolated values (don't delete rows)
    # We do ffill, bfill, and interpolate to preserve the exact same row count
    df[numeric_cols] = df[numeric_cols].ffill().bfill().interpolate(method='linear')
    
    return df

def engineer_features(df):
    # price_change = close - open
    df['price_change'] = df['close'] - df['open']
    
    # returns = close.pct_change() -> fill NaN with 0
    df['returns'] = df['close'].pct_change().fillna(0)
    
    # SMA (5, 10, 20)
    df['SMA_5'] = df['close'].rolling(window=5, min_periods=1).mean()
    df['SMA_10'] = df['close'].rolling(window=10, min_periods=1).mean()
    df['SMA_20'] = df['close'].rolling(window=20, min_periods=1).mean()
    
    # rolling std (volatility) -> fill NaN properly
    df['rolling_std'] = df['close'].rolling(window=10, min_periods=1).std().fillna(0)
    
    return df

def preprocess_data(df):
    features = ['open', 'high', 'low', 'volume', 'price_change', 'returns', 'SMA_5', 'SMA_10', 'SMA_20', 'rolling_std']
    
    # Handle NaN: replace remaining NaN with 0 or mean (NO ROW DROPPING)
    for col in features:
        if df[col].isnull().any():
            df[col] = df[col].fillna(df[col].mean()).fillna(0)
            
    # Define y = target (next_day_close)
    df['target'] = df['close'].shift(-1)
    df['target'] = df['target'].fillna(df['close']) # Fill the last NaN target with current close
    
    X = df[features].values
    y = df['target'].values
    
    # Apply scaling
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)
    
    return X_scaled, y, df, scaler, features

def train_ma_model(df, window=20):
    # Moving Average evaluation on historical data
    # Predict step t using average of previous `window` steps
    y_pred = df['close'].rolling(window=window, min_periods=1).mean().shift(1)
    
    valid_idx = y_pred.notna()
    y_true = df['close'][valid_idx]
    y_pred_valid = y_pred[valid_idx]
    
    if len(y_true) > 0:
        mse = mean_squared_error(y_true, y_pred_valid)
        r2 = r2_score(y_true, y_pred_valid)
    else:
        mse = 0.0
        r2 = 0.0
        
    sys.stderr.write(f"--- MODEL PERFORMANCE (Moving Average) ---\nMSE: {mse:.4f}, R2: {r2:.4f}\n")
    return mse, r2

def forecast_ma(df, days=30, window=20):
    history = list(df['close'].values)
    forecast = []
    
    for _ in range(days):
        window_slice = history[-window:]
        next_pred = sum(window_slice) / len(window_slice)
        forecast.append(next_pred)
        history.append(next_pred)
        
    return forecast

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Stock symbol required"}))
        sys.exit(1)

    symbol = sys.argv[1]

    try:
        engine = create_engine('mysql+pymysql://root:@127.0.0.1/arthapredict')
        
        # Pipeline execution
        df = load_data(symbol, engine)
        
        if df.empty:
            print(json.dumps({"error": f"No data found for symbol {symbol}"}))
            sys.exit(0)
            
        if len(df) < 10:
            print(json.dumps({"error": f"Not enough data points for {symbol}. At least 10 required, got {len(df)}."}))
            sys.exit(0)
            
        df_clean = clean_data(df)
        df_features = engineer_features(df_clean)
        X_scaled, y, df_processed, scaler, feature_cols = preprocess_data(df_features)
        
        sys.stderr.write(f"--- DATASET SHAPE AFTER PROCESSING: {df_processed.shape} ---\n")
        
        ma_mse, ma_r2 = train_ma_model(df_processed, window=20)
        ma_forecast_full = forecast_ma(df_processed, days=30, window=20)
        
        current_price = df_processed['close'].iloc[-1]
        last_date = df_processed['date'].max()
        
        # Also run the old models for compatibility (using clean data)
        df_processed['day_index'] = (df_processed['date'] - df_processed['date'].min()).dt.days
        X_days = df_processed[['day_index']].values
        y_close = df_processed['close'].values
        
        lstm_result = train_and_forecast(y_close, sequence_length=min(20, max(5, len(y_close) // 3)))
        lstm_forecast = lstm_result['forecast']
        lstm_metrics = lstm_result['metrics']
        
        xgb_result = train_xgboost_and_forecast(X_days, y_close)
        xgb_forecast = xgb_result['forecast']
        xgb_metrics = xgb_result['metrics']
        
        predictions = []
        targets = [
            ("1 Day", 1),
            ("1 Week", 7),
            ("1 Month", 30)
        ]
        
        for _, days_ahead in targets:
            target_date = last_date + timedelta(days=days_ahead)
            target_date_str = target_date.strftime('%Y-%m-%d')
            
            xgb_pred = float(xgb_forecast[days_ahead - 1])
            lstm_pred = float(lstm_forecast[days_ahead - 1])
            
            predictions.append({
                "model_type": "XGBoost",
                "target_date": target_date_str,
                "predicted_price": max(0.1, xgb_pred),
                "additional_metrics": xgb_metrics
            })
            
            predictions.append({
                "model_type": "LSTM",
                "target_date": target_date_str,
                "predicted_price": max(0.1, lstm_pred),
                "additional_metrics": lstm_metrics
            })
            
        result = {
            "symbol": symbol,
            "current_price": round(float(current_price), 2),
            "predictions": [
                {
                    "model_type": p["model_type"],
                    "target_date": p["target_date"],
                    "predicted_price": round(float(p["predicted_price"]), 2),
                    "additional_metrics": p["additional_metrics"]
                } for p in predictions
            ]
        }
        
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(0)

if __name__ == "__main__":
    main()
