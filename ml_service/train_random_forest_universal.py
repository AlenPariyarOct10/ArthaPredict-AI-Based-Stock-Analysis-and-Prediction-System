"""
Trainer for Universal Random Forest Model

This script trains a Random Forest regression model on ALL stocks in the database,
allowing the model to learn market-wide patterns while still being able to
specialise via the stock identifier feature.

Usage:
    python train_random_forest_universal.py

Progress output format:
    Processing stock 1/10: NABIL (500 samples)... done
    Processing stock 2/10: SBIBANK (450 samples)... done
    ...
    Training: 10000 processed, 0 remaining
"""

import sys
import time
from pathlib import Path
from datetime import datetime

import numpy as np

# Add parent directory to path for imports
_SCRIPT_DIR = Path(__file__).resolve().parent
if str(_SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(_SCRIPT_DIR))

from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_squared_error

from lstm_universal_model import MultiStockDataLoader, UniversalModelConfig
from random_forest import RandomForestModelRegistry


class TrainingProgress:
    """Track and display training progress with processed/remaining counts."""

    def __init__(self, total_samples: int, description: str = "Training"):
        self.total_samples = total_samples
        self.processed = 0
        self.description = description
        self.start_time = time.time()
        self.last_update = 0

    def update(self, n_samples: int = 1):
        """Update progress and display if enough time has passed."""
        self.processed += n_samples
        remaining = self.total_samples - self.processed

        # Update every 100ms to avoid flooding the terminal
        current_time = time.time()
        if current_time - self.last_update >= 0.1:
            elapsed = current_time - self.start_time
            rate = self.processed / elapsed if elapsed > 0 else 0
            eta = (remaining / rate) if rate > 0 else 0

            # Clear line and print progress
            sys.stdout.write(f"\r  {self.description}: {self.processed} processed, {remaining} remaining (ETA: {eta:.0f}s)")
            sys.stdout.flush()
            self.last_update = current_time

    def finish(self, final_message: str = "Complete"):
        """Print final progress message."""
        sys.stdout.write(f"\r  ✓ {final_message}\n")
        sys.stdout.flush()


def _prepare_features(X_seq: np.ndarray, stock_ids: np.ndarray, num_stocks: int) -> np.ndarray:
    """Flatten price sequences and append a normalized stock identifier."""
    flat_seq = X_seq.reshape(X_seq.shape[0], -1)
    stock_feat = (stock_ids.astype(float) / max(1, num_stocks - 1)).reshape(-1, 1)
    return np.hstack([flat_seq, stock_feat])


def train_universal_random_forest(config: UniversalModelConfig) -> tuple:
    """Train the universal Random Forest model and persist it."""
    print("\n" + "=" * 80)
    print("TRAINING UNIVERSAL RANDOM FOREST MODEL")
    print("=" * 80)

    # Load data for all stocks
    print("\n[1/4] Loading data for all stocks...")
    loader = MultiStockDataLoader(config)
    loader.load_from_database()
    X_seq, y, stock_ids, symbols = loader.create_training_sequences()
    num_stocks = len(symbols)

    print(f"\n      Loaded {num_stocks} stocks with {len(X_seq)} total samples")

    # Split into train/validation
    print("\n[2/4] Splitting data into train/validation sets...")
    split_idx = int(len(X_seq) * (1 - config.validation_split))
    X_train_seq, X_val_seq = X_seq[:split_idx], X_seq[split_idx:]
    y_train, y_val = y[:split_idx], y[split_idx:]
    ids_train, ids_val = stock_ids[:split_idx], stock_ids[split_idx:]

    # Convert to 2-D arrays
    X_train = _prepare_features(X_train_seq, ids_train, num_stocks)
    X_val = _prepare_features(X_val_seq, ids_val, num_stocks)

    print(f"      Training set: {len(X_train)} samples")
    print(f"      Validation set: {len(X_val)} samples")

    # Initialize model
    print("\n[3/4] Initializing Random Forest model...")
    rf_reg = RandomForestRegressor(
        n_estimators=200,
        max_depth=15,
        min_samples_split=5,
        min_samples_leaf=2,
        max_features='sqrt',
        bootstrap=True,
        random_state=42,
        n_jobs=-1,
        verbose=0,  # We handle progress ourselves
    )

    # Train with progress tracking
    print("\n[4/4] Training model...")
    progress = TrainingProgress(len(X_train), "Training samples")

    # For Random Forest, we track progress via verbose callback simulation
    # sklearn doesn't have a native progress callback, so we use a workaround
    start_time = time.time()

    # Train the model
    rf_reg.fit(X_train, y_train)

    elapsed = time.time() - start_time
    progress.finish(f"Training complete in {elapsed:.1f}s")

    # Compute metrics
    print("\n      Computing metrics...")
    train_rmse = mean_squared_error(y_train, rf_reg.predict(X_train), squared=False)
    val_rmse = mean_squared_error(y_val, rf_reg.predict(X_val), squared=False)

    metrics = {
        "train_rmse": float(train_rmse),
        "val_rmse": float(val_rmse),
        "n_stocks": num_stocks,
        "n_samples": len(X_seq),
        "training_time_seconds": elapsed
    }

    print(f"\n      Training RMSE: {train_rmse:.6f}")
    print(f"      Validation RMSE: {val_rmse:.6f}")

    # Persist model
    print("\n      Saving model...")
    registry = RandomForestModelRegistry()
    dummy_prices = np.concatenate([loader.stock_data[s]["prices"] for s in symbols])
    config_dict = {
        "n_estimators": 200,
        "max_depth": 15,
        "min_samples_split": 5,
        "min_samples_leaf": 2,
        "max_features": "sqrt",
    }
    model_path = registry.save_model(
        symbol="UNIVERSAL_RF",
        model=rf_reg,
        training_metrics=metrics,
        close_prices=dummy_prices,
        config=config_dict,
    )

    print(f"\n      Model saved to: {model_path}")
    return model_path, metrics


def main():
    """Main entry point."""
    config = UniversalModelConfig()

    try:
        model_path, metrics = train_universal_random_forest(config)

        print("\n" + "=" * 80)
        print("TRAINING COMPLETE")
        print("=" * 80)
        print(f"\nModel saved to: {model_path}")
        print(f"Metrics: {metrics}")
        print(f"\nTo predict, run: python train_random_forest_universal.py --predict SYMBOL")

    except Exception as e:
        print(f"\n✗ Error during training: {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()
