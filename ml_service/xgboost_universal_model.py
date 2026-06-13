"""Universal XGBoost Model for Stock Price Prediction

This module trains a single XGBoost regression model on *all* stocks in the
database.  The model receives a fixed‑length price history (the same sequence
length used by the LSTM universal model) together with a numeric identifier for
the stock.  By sharing parameters across stocks the model can learn market‑wide
patterns while still being able to specialise via the stock identifier.

Usage
-----
    python xgboost_universal_model.py --train            # Train universal model
    python xgboost_universal_model.py --predict SYMBOL   # Predict next price for SYMBOL

The implementation re‑uses the ``MultiStockDataLoader`` from
``lstm_universal_model.py`` for data loading and sequence creation, and the
``XGBoostModelRegistry`` from ``xgboost.py`` for persistence.
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path
from typing import Tuple

import numpy as np
import xgboost as xgb

# ---------------------------------------------------------------------------
# Local imports – paths are resolved relative to this file's directory.
# ---------------------------------------------------------------------------
_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

# Re‑use the data loader that already knows how to pull data from the DB.
from lstm_universal_model import MultiStockDataLoader, UniversalModelConfig

# Registry handles versioned persistence and optional DB registration.
from xgboost import XGBoostModelRegistry


def _prepare_features(
    X_seq: np.ndarray, stock_ids: np.ndarray, num_stocks: int
) -> np.ndarray:
    """Flatten price sequences and append a normalized stock identifier.

    Parameters
    ----------
    X_seq: ``[samples, seq_len, 1]`` price sequences (already normalised).
    stock_ids: integer IDs for each sample.
    num_stocks: total number of distinct stocks – used for normalisation.

    Returns
    -------
    ``[samples, seq_len + 1]`` feature matrix suitable for XGBoost.
    """
    # Flatten the sequence dimension (seq_len, 1) -> (seq_len,)
    flat_seq = X_seq.reshape(X_seq.shape[0], -1)
    # Normalise stock ID to [0, 1] to keep scale comparable to price values.
    stock_feat = (stock_ids.astype(float) / max(1, num_stocks - 1)).reshape(-1, 1)
    return np.hstack([flat_seq, stock_feat])


def train_universal_xgboost(config: UniversalModelConfig) -> Tuple[str, dict]:
    """Train the universal XGBoost model and persist it.

    Returns
    -------
    model_path: path to the saved ``.pkl`` file.
    metrics: dictionary with training loss (RMSE) and validation loss.
    """
    # -------------------------------------------------------------------
    # Load data for *all* stocks.
    # -------------------------------------------------------------------
    loader = MultiStockDataLoader(config)
    loader.load_from_database()  # Load every stock present in the DB.
    X_seq, y, stock_ids, symbols = loader.create_training_sequences()
    num_stocks = len(symbols)

    # Split into train/validation using the same proportion as the LSTM model.
    split_idx = int(len(X_seq) * (1 - config.validation_split))
    X_train_seq, X_val_seq = X_seq[:split_idx], X_seq[split_idx:]
    y_train, y_val = y[:split_idx], y[split_idx:]
    ids_train, ids_val = stock_ids[:split_idx], stock_ids[split_idx:]

    # Convert to XGBoost‑compatible 2‑D arrays.
    X_train = _prepare_features(X_train_seq, ids_train, num_stocks)
    X_val = _prepare_features(X_val_seq, ids_val, num_stocks)

    # -------------------------------------------------------------------
    # Model definition – we keep the hyper‑parameters simple but expose them
    # via the config object for easy tweaking.
    # -------------------------------------------------------------------
    xgb_reg = xgb.XGBRegressor(
        n_estimators=200,
        max_depth=6,
        learning_rate=0.05,
        subsample=0.8,
        colsample_bytree=0.8,
        objective="reg:squarederror",
        random_state=42,
        n_jobs=4,
    )

    # Train with early stopping on the validation set.
    xgb_reg.fit(
        X_train,
        y_train,
        eval_set=[(X_val, y_val)],
        early_stopping_rounds=20,
        verbose=False,
    )

    # Compute simple RMSE metrics for reporting.
    from sklearn.metrics import mean_squared_error

    train_rmse = mean_squared_error(y_train, xgb_reg.predict(X_train), squared=False)
    val_rmse = mean_squared_error(y_val, xgb_reg.predict(X_val), squared=False)
    metrics = {"train_rmse": float(train_rmse), "val_rmse": float(val_rmse)}

    # -------------------------------------------------------------------
    # Persist the model using the existing registry.  We store it under the
    # special symbol "UNIVERSAL" so that other code can retrieve it.
    # -------------------------------------------------------------------
    registry = XGBoostModelRegistry()
    # The registry expects a NumPy array of close prices; we provide a dummy
    # concatenation of all stocks' price series for fingerprinting purposes.
    dummy_prices = np.concatenate([loader.stock_data[s]["prices"] for s in symbols])
    config_dict = {
        "n_estimators": 200,
        "max_depth": 6,
        "learning_rate": 0.05,
    }
    model_path = registry.save_model(
        symbol="UNIVERSAL",
        model=xgb_reg,
        training_metrics=metrics,
        close_prices=dummy_prices,
        config=config_dict,
    )
    print(f"Universal XGBoost model saved to {model_path}")
    return model_path, metrics


def predict_next_price(symbol: str, config: UniversalModelConfig) -> float:
    """Predict the next closing price for *symbol* using the universal model.

    The function loads the most recent model, builds the latest feature vector
    from the stock's price history and returns the predicted price in the
    original scale (i.e., not the normalised value).
    """
    # Load model
    registry = XGBoostModelRegistry()
    model_bundle, _ = registry.get_latest_model("UNIVERSAL")
    if model_bundle is None:
        raise RuntimeError("Universal XGBoost model not found – train it first.")
    model = model_bundle["model"]

    # Load the specific stock's data.
    loader = MultiStockDataLoader(config)
    loader.load_from_database([symbol])
    if symbol not in loader.stock_data:
        raise ValueError(f"Symbol {symbol} not found in database.")

    # Build the most recent sequence.
    prices = loader.stock_data[symbol]["prices"]
    seq_len = config.sequence_length
    if len(prices) < seq_len:
        raise ValueError(
            f"Not enough data for {symbol}: need {seq_len} days, have {len(prices)}"
        )
    recent = prices[-seq_len:]
    # Normalise using the same min‑max scaling that was applied during training.
    min_p, max_p = loader.scalers[symbol]
    if max_p > min_p:
        norm = (recent - min_p) / (max_p - min_p)
    else:
        norm = recent - min_p
    # Shape to (1, seq_len, 1) then prepare features.
    X_seq = norm.reshape(1, seq_len, 1)
    # Stock ID – we need the integer mapping used during training.
    symbols_list = list(loader.stock_data.keys())
    stock_id = symbols_list.index(symbol)
    X_feat = _prepare_features(X_seq, np.array([stock_id]), len(symbols_list))

    # Predict normalised price and convert back to original scale.
    pred_norm = float(model.predict(X_feat)[0])
    pred_price = pred_norm * (max_p - min_p) + min_p if max_p > min_p else pred_norm + min_p
    return float(pred_price)


def _main() -> None:
    parser = argparse.ArgumentParser(description="Universal XGBoost trainer/predictor")
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--train", action="store_true", help="Train universal model")
    group.add_argument("--predict", metavar="SYMBOL", help="Predict next price for SYMBOL")
    args = parser.parse_args()

    cfg = UniversalModelConfig()
    if args.train:
        train_universal_xgboost(cfg)
    else:
        price = predict_next_price(args.predict, cfg)
        print(f"Predicted next close price for {args.predict}: {price:.2f}")


if __name__ == "__main__":
    _main()
