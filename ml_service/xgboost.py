import numpy as np
import os
import pickle
import json
import hashlib
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, Optional, Tuple
from db import register_model_in_db


# ──────────────────────────────────────────────────────────────────────────────
# MODEL REGISTRY FOR XGBOOST PERSISTENCE
# ──────────────────────────────────────────────────────────────────────────────

class XGBoostModelRegistry:
    """Persistent storage for XGBoost models with versioning and fingerprinting."""

    def __init__(self, model_dir: str = None):
        # Use absolute path based on script location if not provided
        if model_dir is None:
            script_dir = Path(__file__).resolve().parent
            model_dir = script_dir / "models"

        self.model_dir = Path(model_dir).resolve()
        self.xgb_dir = self.model_dir / "xgboost"
        self.metadata_file = self.model_dir / "metadata.json"

        # Create directories if they don't exist
        self.xgb_dir.mkdir(parents=True, exist_ok=True)
        self.metadata = self._load_metadata()

    def _load_metadata(self) -> Dict:
        """Load model metadata registry, handling empty or corrupt files gracefully."""
        if self.metadata_file.exists():
            try:
                with open(self.metadata_file, 'r') as f:
                    content = f.read().strip()
                    if content:
                        return json.loads(content)
            except (json.JSONDecodeError, ValueError):
                pass
        return {"models": {}, "last_updated": None}

    def _save_metadata(self):
        """Save model metadata registry."""
        self.metadata["last_updated"] = datetime.now().isoformat()
        with open(self.metadata_file, 'w') as f:
            json.dump(self.metadata, f, indent=2)

    def _compute_model_fingerprint(self, close_prices: np.ndarray,
                                   n_estimators: int,
                                   max_depth: int) -> str:
        """Compute a unique fingerprint for model configuration + data."""
        data_sample = close_prices[-100:] if len(close_prices) > 100 else close_prices

        fingerprint_data = {
            "data_hash": hashlib.md5(data_sample.tobytes()).hexdigest(),
            "data_length": len(close_prices),
            "n_estimators": n_estimators,
            "max_depth": max_depth,
            "last_price": float(close_prices[-1]) if len(close_prices) > 0 else None,
        }

        fingerprint_str = json.dumps(fingerprint_data, sort_keys=True)
        return hashlib.md5(fingerprint_str.encode()).hexdigest()[:16]

    def get_latest_model(self, symbol: str) -> Optional[Tuple[Any, Dict]]:
        """Retrieve the latest trained model for a symbol."""
        latest_file = self.xgb_dir / f"{symbol}_latest.pkl"

        if latest_file.exists():
            try:
                with open(latest_file, 'rb') as f:
                    model_bundle = pickle.load(f)

                model_key = f"{symbol}_xgboost"
                metadata = self.metadata["models"].get(model_key, {})
                return model_bundle, metadata
            except Exception as e:
                print(f" Error loading latest XGBoost model: {e}")

        pkl_files = list(self.xgb_dir.glob(f"{symbol}_20*.pkl"))
        pkl_files = [f for f in pkl_files if not f.name.endswith('_latest.pkl')]

        if not pkl_files:
            return None, None

        latest_file = max(pkl_files, key=lambda p: p.stat().st_mtime)

        try:
            with open(latest_file, 'rb') as f:
                model_bundle = pickle.load(f)

            model_key = f"{symbol}_xgboost"
            metadata = self.metadata["models"].get(model_key, {})
            return model_bundle, metadata
        except Exception as e:
            print(f" Error loading XGBoost model from {latest_file}: {e}")
            return None, None

    def save_model(self, symbol: str, model: Any, training_metrics: Dict,
                   close_prices: np.ndarray, config: Dict) -> str:
        """Save a trained XGBoost model to disk."""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{symbol}_{timestamp}.pkl"
        model_path = self.xgb_dir / filename

        fingerprint = self._compute_model_fingerprint(
            close_prices,
            config.get("n_estimators", 100),
            config.get("max_depth", 3)
        )

        model_bundle = {
            "model": model,
            "fingerprint": fingerprint,
            "training_metrics": training_metrics,
            "config": config,
            "training_date": datetime.now().isoformat(),
            "data_length": len(close_prices),
            "symbol": symbol
        }

        with open(model_path, 'wb') as f:
            pickle.dump(model_bundle, f)

        latest_path = self.xgb_dir / f"{symbol}_latest.pkl"
        try:
            with open(latest_path, 'wb') as f:
                pickle.dump(model_bundle, f)
            print(f" XGBoost model saved to {model_path}")
        except Exception as e:
            print(f" Could not save latest XGBoost model file: {e}")

        model_key = f"{symbol}_xgboost"
        self.metadata["models"][model_key] = {
            "path": str(model_path),
            "latest_path": str(latest_path) if latest_path.exists() else None,
            "fingerprint": fingerprint,
            "training_date": datetime.now().isoformat(),
            "metrics": training_metrics,
            "config": config,
            "data_length": len(close_prices)
        }
        self._save_metadata()

        try:
            register_model_in_db(
                symbol=symbol,
                model_type="xgboost",
                model_path=str(model_path),
                latest_path=str(latest_path) if latest_path.exists() else None,
                fingerprint=fingerprint,
                training_date=datetime.now().isoformat(),
                data_length=len(close_prices),
                config=config,
                metrics=training_metrics
            )
        except Exception as e:
            print(f"Warning: Failed to insert XGBoost model into database: {e}")

        return str(model_path)

    def needs_retraining(self, symbol: str, current_prices: np.ndarray,
                        config: Dict) -> Tuple[bool, str]:
        """Check if model needs retraining."""
        model_key = f"{symbol}_xgboost"

        if model_key not in self.metadata["models"]:
            return True, "No existing XGBoost model found"

        metadata = self.metadata["models"][model_key]

        model_path = Path(metadata["path"])
        if not model_path.exists():
            return True, f"Model file missing: {model_path}"

        current_fingerprint = self._compute_model_fingerprint(
            current_prices,
            config.get("n_estimators", 100),
            config.get("max_depth", 3)
        )

        if metadata["fingerprint"] != current_fingerprint:
            return True, "Data fingerprint changed - new data available"

        training_date = datetime.fromisoformat(metadata["training_date"])
        days_since_training = (datetime.now() - training_date).days
        if days_since_training >= 7:
            return True, f"Model is {days_since_training} days old"

        old_data_len = metadata.get("data_length", 0)
        new_data_len = len(current_prices)
        if new_data_len > old_data_len * 1.3:
            return True, f"Data grew by {new_data_len - old_data_len} rows"

        return False, "Model is valid"


# ──────────────────────────────────────────────────────────────────────────────
# ENHANCED LAG-FEATURE CONFIGURATION
# ──────────────────────────────────────────────────────────────────────────────

_LAGS           = [1, 2, 3, 5, 7, 10, 14, 20, 30]  # Extended lags
_MAX_LAG        = max(_LAGS)
_ROLLING_WINDOW = 20  # Increased window for better statistics


def _compute_features_from_history(history):
    """
    Compute enhanced feature vector from price history.
    Focuses on directional indicators and momentum.
    """
    history = np.asarray(history, dtype=float)

    feats = []

    # 1. Price lags
    for lag in _LAGS:
        if len(history) > lag:
            feats.append(float(history[-lag]))
        else:
            feats.append(float(history[-1]) if len(history) > 0 else 0.0)

    # 2. Rolling statistics
    window_size = min(_ROLLING_WINDOW, len(history))
    window = history[-window_size:] if window_size > 0 else history

    feats.append(float(np.mean(window)))
    feats.append(float(np.std(window)) if len(window) > 1 else 0.0)
    feats.append(float(np.min(window)))
    feats.append(float(np.max(window)))

    # 3. Price position in range (0-1)
    if len(window) > 1:
        price_range = np.max(window) - np.min(window)
        if price_range > 1e-8:
            position = (history[-1] - np.min(window)) / price_range
        else:
            position = 0.5
        feats.append(float(position))
    else:
        feats.append(0.5)

    # 4. Returns (simple and log)
    if len(history) >= 2:
        p1 = float(history[-1])
        p2 = float(history[-2])
        simple_return = (p1 - p2) / (abs(p2) + 1e-8)
        log_return = np.log((abs(p1) + 1e-8) / (abs(p2) + 1e-8))
        feats.append(float(simple_return))
        feats.append(float(log_return))
    else:
        feats.extend([0.0, 0.0])

    # 5. Multi-period returns (momentum indicators)
    for period in [3, 5, 10, 20]:
        if len(history) > period:
            old_price = history[-period]
            current_price = history[-1]
            period_return = (current_price - old_price) / (abs(old_price) + 1e-8)
            feats.append(float(period_return))
        else:
            feats.append(0.0)

    # 6. Moving averages and ratios
    for ma_period in [5, 10, 20]:
        if len(history) >= ma_period:
            ma = np.mean(history[-ma_period:])
            feats.append(float(ma))
            # Price to MA ratio (trend indicator)
            ma_ratio = history[-1] / (ma + 1e-8)
            feats.append(float(ma_ratio))
        else:
            feats.extend([history[-1] if len(history) > 0 else 0.0, 1.0])

    # 7. Momentum indicators (rate of change)
    if len(history) >= 3:
        # Price velocity (first derivative)
        velocity = history[-1] - history[-2]
        # Price acceleration (second derivative)
        if len(history) >= 3:
            prev_velocity = history[-2] - history[-3]
            acceleration = velocity - prev_velocity
        else:
            acceleration = 0.0
        feats.append(float(velocity / (abs(history[-1]) + 1e-8)))
        feats.append(float(acceleration / (abs(history[-1]) + 1e-8)))
    else:
        feats.extend([0.0, 0.0])

    # 8. Trend strength (linear regression slope)
    if len(window) >= 5:
        x = np.arange(len(window))
        slope = np.polyfit(x, window, 1)[0]
        normalized_slope = slope / (np.mean(window) + 1e-8)
        feats.append(float(normalized_slope))
    else:
        feats.append(0.0)

    # 9. Volatility features
    if len(history) >= 10:
        returns = np.diff(history[-10:]) / (history[-10:-1] + 1e-8)
        volatility = np.std(returns)
        feats.append(float(volatility))
    else:
        feats.append(0.0)

    return feats


def _build_lag_feature_matrix(close_prices):
    """Convert price series to supervised dataset."""
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)
    X, y = [], []

    for i in range(_MAX_LAG, len(close_prices)):
        feats = _compute_features_from_history(close_prices[:i+1])
        X.append(feats)
        y.append(close_prices[i])

    return np.array(X, dtype=float), np.array(y, dtype=float)


def _recursive_forecast(model, close_prices, steps):
    """Multi-step recursive forecast."""
    history = list(close_prices)
    forecasts = []

    for step in range(steps):
        feats = _compute_features_from_history(history)
        pred = float(model.predict(np.array(feats, dtype=float).reshape(1, -1))[0])

        if len(history) > 0:
            last_price = float(history[-1])
            pred = float(np.clip(pred, last_price * 0.85, last_price * 1.15))

        forecasts.append(pred)
        history.append(pred)

    return np.array(forecasts, dtype=float)


# ──────────────────────────────────────────────────────────────────────────────
# FIXED ACCURACY CALCULATION FUNCTIONS
# ──────────────────────────────────────────────────────────────────────────────

def calculate_directional_accuracy(y_true, y_pred):
    """
    Calculate directional accuracy correctly with shape handling.

    Directional accuracy = % of times the model correctly predicts
    whether the price will go UP or DOWN compared to consecutive predictions.

    FIXED: Compares sign(diff(actual)) with sign(diff(predicted))
    """
    # Convert to numpy arrays
    y_true = np.asarray(y_true, dtype=float).flatten()
    y_pred = np.asarray(y_pred, dtype=float).flatten()

    # Need at least 2 points to calculate direction
    if len(y_true) < 2 or len(y_pred) < 2:
        return 50.0

    # Ensure same length
    min_len = min(len(y_true), len(y_pred))
    y_true = y_true[:min_len]
    y_pred = y_pred[:min_len]

    # FIXED: Calculate directional changes with flat-price filtering
    diffs_true = np.diff(y_true)
    diffs_pred = np.diff(y_pred)

    # Filter: only count when there's meaningful movement (>0.1% change)
    threshold = 0.001 * np.mean(np.abs(y_true))
    valid_mask = (np.abs(diffs_true) > threshold) | (np.abs(diffs_pred) > threshold)

    if np.sum(valid_mask) == 0:
        # No meaningful moves detected
        return 50.0

    # Apply filter to directions
    actual_direction = np.sign(diffs_true[valid_mask])
    predicted_direction = np.sign(diffs_pred[valid_mask])

    # Count matches
    matches = (actual_direction == predicted_direction)

    # Calculate accuracy
    accuracy = np.mean(matches) * 100
    return float(accuracy)


def calculate_mape(y_true, y_pred):
    """Calculate Mean Absolute Percentage Error."""
    y_true = np.asarray(y_true, dtype=float).flatten()
    y_pred = np.asarray(y_pred, dtype=float).flatten()

    # Ensure same length
    min_len = min(len(y_true), len(y_pred))
    y_true = y_true[:min_len]
    y_pred = y_pred[:min_len]

    # Avoid division by zero
    mask = np.abs(y_true) > 1e-8
    if not np.any(mask):
        return 100.0

    mape = np.mean(np.abs((y_true[mask] - y_pred[mask]) / np.abs(y_true[mask]))) * 100
    return float(mape)


def calculate_metrics(y_true, y_pred):
    """
    Calculate all metrics with proper shape handling.
    Returns dictionary of metrics.
    """
    y_true = np.asarray(y_true, dtype=float).flatten()
    y_pred = np.asarray(y_pred, dtype=float).flatten()

    # Ensure same length
    min_len = min(len(y_true), len(y_pred))
    y_true = y_true[:min_len]
    y_pred = y_pred[:min_len]

    if len(y_true) == 0:
        return {
            "mse": 0, "mae": 0, "rmse": 0, "mape": "0.00%",
            "directional_accuracy": "50.0%", "confidence_score": "50.0%", "r2_score": 0
        }

    # Basic error metrics
    mse = float(np.mean((y_true - y_pred) ** 2))
    mae = float(np.mean(np.abs(y_true - y_pred)))
    rmse = float(np.sqrt(mse))
    mape = calculate_mape(y_true, y_pred)

    # Directional accuracy
    da = calculate_directional_accuracy(y_true, y_pred)

    # R-squared
    ss_res = np.sum((y_true - y_pred) ** 2)
    ss_tot = np.sum((y_true - np.mean(y_true)) ** 2)
    r2 = 1 - (ss_res / (ss_tot + 1e-8))

    # Confidence score (based on MAPE)
    confidence = max(1.0, min(99.0, 100 - mape))

    return {
        "mse": round(mse, 4),
        "mae": round(mae, 4),
        "rmse": round(rmse, 4),
        "mape": f"{mape:.2f}%",
        "directional_accuracy": f"{da:.1f}%",
        "confidence_score": f"{confidence:.1f}%",
        "r2_score": round(r2, 4),
    }


# ──────────────────────────────────────────────────────────────────────────────
# CORE MODEL: SCRATCH GRADIENT-BOOSTED TREES
# ──────────────────────────────────────────────────────────────────────────────

class TreeNode:
    def __init__(self, feature_index=None, threshold=None, left=None, right=None, value=None):
        self.feature_index = feature_index
        self.threshold = threshold
        self.left = left
        self.right = right
        self.value = value

    def is_leaf(self):
        return self.value is not None


class RegressionTree:
    """
    Enhanced regression tree with better splitting criteria for directional accuracy.
    """
    def __init__(self, max_depth=5, min_samples_split=5, lambda_reg=1.0, gamma=0.0):
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.lambda_reg = lambda_reg
        self.gamma = gamma
        self.root = None

    def fit(self, x_values, gradients):
        x_values = np.asarray(x_values, dtype=float)
        gradients = np.asarray(gradients, dtype=float).reshape(-1)
        self.root = self._build_tree(x_values, gradients, depth=0)
        return self

    def _leaf_value(self, gradients):
        gradients = np.asarray(gradients, dtype=float).reshape(-1)
        if len(gradients) == 0:
            return 0.0
        return float(-np.sum(gradients) / (len(gradients) + self.lambda_reg))

    def _gain(self, left_g, right_g):
        if len(left_g) == 0 or len(right_g) == 0:
            return -np.inf
        g_left = (np.sum(left_g) ** 2) / (len(left_g) + self.lambda_reg)
        g_right = (np.sum(right_g) ** 2) / (len(right_g) + self.lambda_reg)
        g_parent = (np.sum(np.concatenate((left_g, right_g))) ** 2) / (len(left_g) + len(right_g) + self.lambda_reg)
        return 0.5 * (g_left + g_right - g_parent) - self.gamma

    def _best_split(self, x_values, gradients):
        best_feat, best_thresh, best_gain = None, None, -np.inf
        n_features = x_values.shape[1]

        for feat_idx in range(n_features):
            feature_values = x_values[:, feat_idx]
            unique_values = np.unique(feature_values)

            if len(unique_values) <= 2:
                thresholds = unique_values
            else:
                # More thresholds for better splits
                thresholds = np.percentile(feature_values, np.linspace(10, 90, 9))

            for thresh in thresholds:
                left_mask = x_values[:, feat_idx] <= thresh
                right_mask = ~left_mask

                # Require minimum samples in each split
                if np.sum(left_mask) < 2 or np.sum(right_mask) < 2:
                    continue

                gain = self._gain(gradients[left_mask], gradients[right_mask])
                if gain > best_gain:
                    best_gain, best_feat, best_thresh = gain, feat_idx, thresh

        return best_feat, best_thresh, best_gain

    def _build_tree(self, x_values, gradients, depth):
        if (depth >= self.max_depth or len(x_values) < self.min_samples_split or
            np.allclose(gradients, gradients[0])):
            return TreeNode(value=self._leaf_value(gradients))

        feat, thresh, gain = self._best_split(x_values, gradients)
        if feat is None or gain <= 0:
            return TreeNode(value=self._leaf_value(gradients))

        mask = x_values[:, feat] <= thresh
        return TreeNode(
            feature_index=feat,
            threshold=thresh,
            left=self._build_tree(x_values[mask], gradients[mask], depth + 1),
            right=self._build_tree(x_values[~mask], gradients[~mask], depth + 1),
        )

    def _predict_row(self, row, node):
        if node.is_leaf():
            return node.value
        if row[node.feature_index] <= node.threshold:
            return self._predict_row(row, node.left)
        return self._predict_row(row, node.right)

    def predict(self, x_values):
        x_values = np.asarray(x_values, dtype=float)
        return np.array([self._predict_row(row, self.root) for row in x_values], dtype=float)


class ScratchXGBoostRegressor:
    """
    Enhanced XGBoost regressor optimized for directional accuracy.
    """
    def __init__(self, n_estimators=150, learning_rate=0.05, max_depth=5,
                 min_samples_split=4, lambda_reg=1.0, gamma=0.0):
        self.n_estimators = n_estimators
        self.learning_rate = learning_rate
        self.max_depth = max_depth  # Increased default depth
        self.min_samples_split = min_samples_split
        self.lambda_reg = lambda_reg
        self.gamma = gamma
        self.base_score = 0.0
        self.trees = []
        self.training_loss_ = None

    def fit(self, x_values, y_values, progress_callback=None):
        x_values = np.asarray(x_values, dtype=float)
        y_values = np.asarray(y_values, dtype=float).reshape(-1)

        if len(x_values) == 0:
            raise ValueError("Training data is empty.")

        self.base_score = float(np.mean(y_values))
        predictions = np.full(len(y_values), self.base_score, dtype=float)
        self.trees = []

        for m in range(self.n_estimators):
            # Residuals (negative gradient for MSE)
            residuals = y_values - predictions

            tree = RegressionTree(
                max_depth=self.max_depth,
                min_samples_split=self.min_samples_split,
                lambda_reg=self.lambda_reg,
                gamma=self.gamma,
            )
            tree.fit(x_values, residuals)

            predictions += self.learning_rate * tree.predict(x_values)
            self.trees.append(tree)

            if progress_callback and (m + 1) % 20 == 0:
                progress_callback(m + 1)

        self.training_loss_ = float(np.mean((predictions - y_values) ** 2))
        return self

    def predict(self, x_values):
        x_values = np.asarray(x_values, dtype=float)
        predictions = np.full(len(x_values), self.base_score, dtype=float)
        for tree in self.trees:
            predictions += self.learning_rate * tree.predict(x_values)
        return predictions


# ──────────────────────────────────────────────────────────────────────────────
# PUBLIC API WITH PERSISTENCE
# ──────────────────────────────────────────────────────────────────────────────

def train_xgboost_and_forecast(
    close_prices,
    symbol="UNKNOWN",
    forecast_horizon=30,
    n_estimators=150,
    force_retrain=False,
    use_cache=False,
    cached_only=False,
    progress_callback=None
):
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)

    min_required = _MAX_LAG + 10
    if len(close_prices) < min_required:
        raise ValueError(
            f"XGBoost requires at least {min_required} prices. Got {len(close_prices)}."
        )

    registry = XGBoostModelRegistry()
    config = {
        "n_estimators": n_estimators,
        "max_depth": 5,  # Increased for better feature interaction
        "learning_rate": 0.05
    }

    # ── CACHED PATH ───────────────────────────────────────────────────────────
    if not force_retrain:
        if cached_only or use_cache:
            model_bundle, metadata = registry.get_latest_model(symbol)
            if model_bundle and "model" in model_bundle:
                needs_retrain = False
                reason = "Using cached model"
            elif cached_only:
                raise FileNotFoundError(
                    f"No trained XGBoost model exists for {symbol}. "
                    "Please train the model from the admin panel first."
                )
            else:
                needs_retrain = True
                reason = "No cached model found"
        else:
            needs_retrain, reason = registry.needs_retraining(symbol, close_prices, config)

        if not needs_retrain:
            print(f" Using cached XGBoost model for {symbol}: {reason}")
            model_bundle, metadata = registry.get_latest_model(symbol)

            if model_bundle and "model" in model_bundle:
                model = model_bundle["model"]

                forecast = _recursive_forecast(model, close_prices, forecast_horizon)

                X, y = _build_lag_feature_matrix(close_prices)
                n = len(X)
                split_idx = max(int(n * 0.80), 1)

                if split_idx < n:
                    X_val = X[split_idx:]
                    y_val = y[split_idx:]
                    eval_pred = model.predict(X_val)
                    eval_actual = y_val

                    # Use the fixed metrics calculation
                    metrics_dict = calculate_metrics(eval_actual, eval_pred)
                    metrics_dict["training_loss"] = model_bundle.get("training_loss", 0)
                    metrics_dict["trees"] = n_estimators
                    metrics_dict["eval_set"] = "validation"
                    metrics_dict["cached"] = True

                    model_key = f"{symbol}_xgboost"
                    registry_meta = registry.metadata["models"].get(model_key, {})

                    return {
                        "forecast": forecast.reshape(-1),
                        "metrics": metrics_dict,
                        "cached": True,
                        "path": registry_meta.get("path"),
                        "latest_path": registry_meta.get("latest_path"),
                        "fingerprint": registry_meta.get("fingerprint"),
                        "training_date": registry_meta.get("training_date"),
                        "config": registry_meta.get("config"),
                    }

    # ── TRAIN PATH ────────────────────────────────────────────────────────────
    print(f" Training new XGBoost model for {symbol}...")

    X, y = _build_lag_feature_matrix(close_prices)
    n = len(X)

    split_idx = max(int(n * 0.80), 1)
    use_val = (n - split_idx) >= 5

    X_train, y_train = X[:split_idx], y[:split_idx]
    X_val, y_val = X[split_idx:], y[split_idx:]

    model = ScratchXGBoostRegressor(
        n_estimators=n_estimators,
        learning_rate=0.05,
        max_depth=5,  # Increased depth
        min_samples_split=4,
        lambda_reg=1.0,
        gamma=0.0,
    )
    model.fit(X_train, y_train, progress_callback=progress_callback)

    if use_val and len(X_val) > 0:
        eval_pred = model.predict(X_val)
        eval_actual = y_val
        eval_label = "validation"
    else:
        eval_pred = model.predict(X_train)
        eval_actual = y_train
        eval_label = "training_only"

    # Use the fixed metrics calculation
    metrics_dict = calculate_metrics(eval_actual, eval_pred)
    metrics_dict["training_loss"] = round(float(model.training_loss_), 6)
    metrics_dict["trees"] = n_estimators
    metrics_dict["eval_set"] = eval_label
    metrics_dict["cached"] = False

    forecast = _recursive_forecast(model, close_prices, forecast_horizon)

    registry.save_model(
        symbol=symbol,
        model=model,
        training_metrics=metrics_dict,
        close_prices=close_prices,
        config=config,
    )

    model_key = f"{symbol}_xgboost"
    registry_meta = registry.metadata["models"].get(model_key, {})

    return {
        "forecast": forecast.reshape(-1),
        "metrics": metrics_dict,
        "cached": False,
        "path": registry_meta.get("path"),
        "latest_path": registry_meta.get("latest_path"),
        "fingerprint": registry_meta.get("fingerprint"),
        "training_date": registry_meta.get("training_date"),
        "config": registry_meta.get("config"),
    }
