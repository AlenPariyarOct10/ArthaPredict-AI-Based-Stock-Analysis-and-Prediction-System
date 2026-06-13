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
# MODEL REGISTRY FOR RANDOM FOREST PERSISTENCE
# ──────────────────────────────────────────────────────────────────────────────

class RandomForestModelRegistry:
    """Persistent storage for Random Forest models with versioning and fingerprinting."""

    def __init__(self, model_dir: str = None):
        if model_dir is None:
            script_dir = Path(__file__).resolve().parent
            model_dir = script_dir / "models"

        self.model_dir = Path(model_dir).resolve()
        self.rf_dir = self.model_dir / "random_forest"
        self.metadata_file = self.model_dir / "metadata.json"

        self.rf_dir.mkdir(parents=True, exist_ok=True)
        self.metadata = self._load_metadata()

    def _load_metadata(self) -> Dict:
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
        self.metadata["last_updated"] = datetime.now().isoformat()
        with open(self.metadata_file, 'w') as f:
            json.dump(self.metadata, f, indent=2)

    def _compute_model_fingerprint(self, close_prices: np.ndarray,
                                   n_estimators: int,
                                   max_depth: int,
                                   max_features: str) -> str:
        data_sample = close_prices[-100:] if len(close_prices) > 100 else close_prices
        fingerprint_data = {
            "data_hash": hashlib.md5(data_sample.tobytes()).hexdigest(),
            "data_length": len(close_prices),
            "n_estimators": n_estimators,
            "max_depth": max_depth,
            "max_features": max_features,
            "last_price": float(close_prices[-1]) if len(close_prices) > 0 else None,
        }
        fingerprint_str = json.dumps(fingerprint_data, sort_keys=True)
        return hashlib.md5(fingerprint_str.encode()).hexdigest()[:16]

    def get_latest_model(self, symbol: str) -> Optional[Tuple[Any, Dict]]:
        latest_file = self.rf_dir / f"{symbol}_latest.pkl"
        if latest_file.exists():
            try:
                with open(latest_file, 'rb') as f:
                    model_bundle = pickle.load(f)
                model_key = f"{symbol}_random_forest"
                metadata = self.metadata["models"].get(model_key, {})
                return model_bundle, metadata
            except Exception as e:
                print(f"Error loading latest Random Forest model: {e}")

        pkl_files = list(self.rf_dir.glob(f"{symbol}_20*.pkl"))
        pkl_files = [f for f in pkl_files if not f.name.endswith('_latest.pkl')]
        if not pkl_files:
            return None, None
        latest_file = max(pkl_files, key=lambda p: p.stat().st_mtime)
        try:
            with open(latest_file, 'rb') as f:
                model_bundle = pickle.load(f)
            model_key = f"{symbol}_random_forest"
            metadata = self.metadata["models"].get(model_key, {})
            return model_bundle, metadata
        except Exception as e:
            print(f"Error loading Random Forest model from {latest_file}: {e}")
            return None, None

    def save_model(self, symbol: str, model: Any, training_metrics: Dict,
                   close_prices: np.ndarray, config: Dict) -> str:
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{symbol}_{timestamp}.pkl"
        model_path = self.rf_dir / filename

        fingerprint = self._compute_model_fingerprint(
            close_prices,
            config.get("n_estimators", 200),
            config.get("max_depth", 15),
            config.get("max_features", "sqrt")
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

        latest_path = self.rf_dir / f"{symbol}_latest.pkl"
        try:
            with open(latest_path, 'wb') as f:
                pickle.dump(model_bundle, f)
            print(f"Random Forest model saved to {model_path}")
        except Exception as e:
            print(f"Could not save latest Random Forest model file: {e}")

        model_key = f"{symbol}_random_forest"
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
                model_type="random_forest",
                model_path=str(model_path),
                latest_path=str(latest_path) if latest_path.exists() else None,
                fingerprint=fingerprint,
                training_date=datetime.now().isoformat(),
                data_length=len(close_prices),
                config=config,
                metrics=training_metrics
            )
        except Exception as e:
            print(f"Warning: Failed to insert Random Forest model into database: {e}")

        return str(model_path)

    def needs_retraining(self, symbol: str, current_prices: np.ndarray,
                        config: Dict) -> Tuple[bool, str]:
        model_key = f"{symbol}_random_forest"
        if model_key not in self.metadata["models"]:
            return True, "No existing Random Forest model found"
        metadata = self.metadata["models"][model_key]
        model_path = Path(metadata["path"])
        if not model_path.exists():
            return True, f"Model file missing: {model_path}"
        current_fingerprint = self._compute_model_fingerprint(
            current_prices,
            config.get("n_estimators", 200),
            config.get("max_depth", 15),
            config.get("max_features", "sqrt")
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
# ENHANCED FEATURE ENGINEERING FOR BETTER ACCURACY
# ──────────────────────────────────────────────────────────────────────────────

class TechnicalIndicators:
    """Comprehensive technical indicators for better predictions."""

    @staticmethod
    def calculate_rsi(prices, period=14):
        """Relative Strength Index - momentum indicator."""
        deltas = np.diff(prices)
        seed = deltas[:period+1]
        up = seed[seed >= 0].sum() / period
        down = -seed[seed < 0].sum() / period
        rs = up / down if down != 0 else 100
        rsi = np.zeros_like(prices)
        rsi[:period] = np.nan
        rsi[period] = 100 - 100 / (1 + rs)

        for i in range(period + 1, len(prices)):
            delta = deltas[i-1]
            if delta > 0:
                upval = delta
                downval = 0
            else:
                upval = 0
                downval = -delta
            up = (up * (period - 1) + upval) / period
            down = (down * (period - 1) + downval) / period
            rs = up / down if down != 0 else 100
            rsi[i] = 100 - 100 / (1 + rs)
        return rsi

    @staticmethod
    def calculate_macd(prices, fast=12, slow=26, signal=9):
        """MACD - trend following momentum indicator."""
        def ema(data, span):
            return pd.Series(data).ewm(span=span, adjust=False).mean().values

        ema_fast = ema(prices, fast)
        ema_slow = ema(prices, slow)
        macd_line = ema_fast - ema_slow
        signal_line = ema(macd_line, signal)
        histogram = macd_line - signal_line
        return macd_line, signal_line, histogram

    @staticmethod
    def calculate_bollinger_bands(prices, period=20, std_dev=2):
        """Bollinger Bands - volatility indicator."""
        sma = pd.Series(prices).rolling(window=period).mean().values
        std = pd.Series(prices).rolling(window=period).std().values
        upper_band = sma + (std * std_dev)
        lower_band = sma - (std * std_dev)
        bandwidth = (upper_band - lower_band) / sma
        return upper_band, lower_band, bandwidth

    @staticmethod
    def calculate_atr(high, low, close, period=14):
        """Average True Range - volatility indicator."""
        high_low = high - low
        high_close = np.abs(high - np.roll(close, 1))
        low_close = np.abs(low - np.roll(close, 1))
        tr = np.maximum(high_low, np.maximum(high_close, low_close))
        atr = pd.Series(tr).rolling(window=period).mean().values
        return atr


# Import pandas for technical indicators
try:
    import pandas as pd
except ImportError:
    pd = None
    print("Warning: pandas not available. Some features will be limited.")


def create_enhanced_features(close_prices, high_prices=None, low_prices=None, volume=None):
    """
    Create comprehensive feature set for better predictions.

    Features include:
    1. Price-based features (returns, log returns)
    2. Technical indicators (RSI, MACD, Bollinger Bands)
    3. Statistical features (mean, std, skewness, kurtosis)
    4. Trend features (moving averages, trend strength)
    5. Volatility features (ATR, historical volatility)
    6. Cycle features (sin/cos of position in trend)
    """
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)
    n = len(close_prices)

    # If high/low not provided, use close prices with small variation
    if high_prices is None:
        high_prices = close_prices * 1.01
    if low_prices is None:
        low_prices = close_prices * 0.99
    if volume is None:
        volume = np.ones(n) * 1000000

    features = []

    for i in range(20, n):  # Start after we have enough history
        window = close_prices[:i+1]
        hist_high = high_prices[:i+1]
        hist_low = low_prices[:i+1]
        hist_vol = volume[:i+1]

        feat = []

        # 1. Price-based features (lags)
        for lag in [1, 2, 3, 5, 10, 15, 20]:
            if len(window) > lag:
                feat.append(float(window[-lag]))
            else:
                feat.append(float(window[-1]))

        # 2. Returns (simple and log)
        returns = np.diff(window)
        if len(returns) > 0:
            feat.append(float(returns[-1]))  # last return
            feat.append(float(np.mean(returns[-5:])))  # 5-day avg return
            feat.append(float(np.std(returns[-10:])))  # 10-day volatility
            # Log returns
            log_returns = np.log(window[1:] / window[:-1])
            if len(log_returns) > 0:
                feat.append(float(log_returns[-1]))
                feat.append(float(np.mean(log_returns[-5:])))
        else:
            feat.extend([0, 0, 0, 0, 0])

        # 3. Moving averages and ratios
        for period in [5, 10, 20, 50]:
            if len(window) >= period:
                ma = np.mean(window[-period:])
                feat.append(float(ma))
                feat.append(float(window[-1] / ma))  # Price/MA ratio
            else:
                feat.append(float(window[-1]))
                feat.append(1.0)

        # 4. Statistical features of recent window
        recent = window[-20:] if len(window) >= 20 else window
        feat.append(float(np.mean(recent)))
        feat.append(float(np.std(recent)))
        feat.append(float(np.min(recent)))
        feat.append(float(np.max(recent)))
        feat.append(float((window[-1] - np.min(recent)) / (np.max(recent) - np.min(recent) + 1e-8)))  # % position

        # 5. Trend strength (using linear regression slope)
        if len(recent) >= 5:
            x = np.arange(len(recent))
            slope = np.polyfit(x, recent, 1)[0]
            feat.append(float(slope))
            feat.append(float(slope / (np.mean(recent) + 1e-8)))  # normalized slope
        else:
            feat.extend([0, 0])

        # 6. RSI
        if pd is not None:
            rsi = TechnicalIndicators.calculate_rsi(window)
            if not np.isnan(rsi[-1]):
                feat.append(float(rsi[-1]))
            else:
                feat.append(50.0)
        else:
            feat.append(50.0)

        # 7. Bollinger Band position
        if pd is not None and len(window) >= 20:
            upper, lower, bandwidth = TechnicalIndicators.calculate_bollinger_bands(window)
            if not np.isnan(upper[-1]) and not np.isnan(lower[-1]):
                bb_position = (window[-1] - lower[-1]) / (upper[-1] - lower[-1] + 1e-8)
                feat.append(float(bb_position))
                feat.append(float(bandwidth[-1] if not np.isnan(bandwidth[-1]) else 0))
            else:
                feat.extend([0.5, 0])
        else:
            feat.extend([0.5, 0])

        # 8. Volume features
        if len(hist_vol) > 0:
            vol_ma = np.mean(hist_vol[-10:]) if len(hist_vol) >= 10 else np.mean(hist_vol)
            feat.append(float(hist_vol[-1] / (vol_ma + 1e-8)))  # Volume ratio
            feat.append(float(np.std(hist_vol[-10:]) / (vol_ma + 1e-8)))  # Volume volatility
        else:
            feat.extend([1, 0])

        # 9. Volatility features (ATR approximation)
        if len(hist_high) > 1 and len(hist_low) > 1:
            typical_price = (hist_high[-1] + hist_low[-1] + window[-1]) / 3
            atr = np.mean([hist_high[-i] - hist_low[-i] for i in range(1, min(15, len(hist_high)))])
            feat.append(float(atr / (window[-1] + 1e-8)))  # Normalized ATR
            feat.append(float(typical_price))
        else:
            feat.extend([0, window[-1]])

        # 10. Cycle features (sin/cos of position in recent trend)
        if len(recent) >= 10:
            # Use FFT to find dominant cycle
            fft = np.fft.fft(recent - np.mean(recent))
            dominant_period = 20  # default
            if len(fft) > 2:
                freqs = np.fft.fftfreq(len(recent))
                magnitude = np.abs(fft)
                # Skip zero frequency
                if len(magnitude) > 1:
                    dominant_freq_idx = np.argmax(magnitude[1:]) + 1
                    if dominant_freq_idx < len(freqs):
                        dominant_period = max(5, int(1.0 / abs(freqs[dominant_freq_idx])))

            position_in_cycle = (len(recent) % max(dominant_period, 1)) / max(dominant_period, 1)
            feat.append(float(np.sin(2 * np.pi * position_in_cycle)))
            feat.append(float(np.cos(2 * np.pi * position_in_cycle)))
        else:
            feat.extend([0, 1])

        features.append(feat)

    return np.array(features, dtype=float)


class ScratchRandomForestRegressor:
    """
    Optimized Random Forest regressor with:
    - Better tree splitting criteria
    - Feature bagging
    - Out-of-bag error estimation
    - Improved prediction aggregation
    """

    def __init__(self, n_estimators=200, max_depth=15, min_samples_split=5,
                 min_samples_leaf=2, max_features="sqrt", bootstrap=True,
                 random_state=42, min_impurity_decrease=0.0):
        self.n_estimators = n_estimators
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.min_samples_leaf = min_samples_leaf
        self.max_features = max_features
        self.bootstrap = bootstrap
        self.min_impurity_decrease = min_impurity_decrease
        self.trees = []
        self.feature_indices_ = []
        self.oob_score_ = None
        self.training_loss_ = None

        if random_state is not None:
            np.random.seed(random_state)

    def _calculate_impurity(self, y):
        """Mean squared error as impurity measure."""
        if len(y) == 0:
            return 0
        return np.var(y)

    def _find_best_split(self, X, y, feature_indices):
        """Find best split using variance reduction."""
        n_samples = len(y)
        best_mse = self._calculate_impurity(y)
        best_feature = None
        best_threshold = None
        best_left_idx = None
        best_right_idx = None

        for feature in feature_indices:
            feature_values = X[:, feature]
            unique_values = np.unique(feature_values)

            if len(unique_values) < 2:
                continue

            # Try multiple potential thresholds for better splits
            thresholds = []
            if len(unique_values) <= 20:
                # For few unique values, try midpoints
                for i in range(len(unique_values) - 1):
                    thresholds.append((unique_values[i] + unique_values[i+1]) / 2)
            else:
                # For many unique values, use percentiles
                thresholds = np.percentile(feature_values, np.linspace(10, 90, 9))

            for threshold in thresholds:
                left_mask = feature_values <= threshold
                right_mask = ~left_mask

                # Check minimum samples constraint
                if np.sum(left_mask) < self.min_samples_leaf or np.sum(right_mask) < self.min_samples_leaf:
                    continue

                y_left = y[left_mask]
                y_right = y[right_mask]

                # Calculate weighted MSE
                mse_left = self._calculate_impurity(y_left)
                mse_right = self._calculate_impurity(y_right)
                weighted_mse = (len(y_left) * mse_left + len(y_right) * mse_right) / n_samples

                # Calculate improvement
                improvement = best_mse - weighted_mse

                if improvement > self.min_impurity_decrease and improvement > 0:
                    best_mse = weighted_mse
                    best_feature = feature
                    best_threshold = threshold
                    best_left_idx = left_mask
                    best_right_idx = right_mask

        return best_feature, best_threshold, best_left_idx, best_right_idx

    def _build_tree(self, X, y, depth=0):
        """Recursively build decision tree."""
        n_samples = len(y)
        n_features = X.shape[1]

        # Stopping conditions
        if (depth >= self.max_depth or
            n_samples < self.min_samples_split or
            len(np.unique(y)) == 1):
            return np.mean(y)

        # Select random subset of features
        if self.max_features == "sqrt":
            n_selected = max(1, int(np.sqrt(n_features)))
        elif self.max_features == "log2":
            n_selected = max(1, int(np.log2(n_features)))
        elif isinstance(self.max_features, int):
            n_selected = min(self.max_features, n_features)
        else:
            n_selected = n_features

        feature_indices = np.random.choice(n_features, n_selected, replace=False)

        # Find best split
        feature, threshold, left_idx, right_idx = self._find_best_split(X, y, feature_indices)

        if feature is None:
            return np.mean(y)

        # Build left and right subtrees
        left_tree = self._build_tree(X[left_idx], y[left_idx], depth + 1)
        right_tree = self._build_tree(X[right_idx], y[right_idx], depth + 1)

        return {
            'feature': feature,
            'threshold': threshold,
            'left': left_tree,
            'right': right_tree,
            'value': np.mean(y)
        }

    def _predict_tree(self, tree, x):
        """Predict using a single decision tree."""
        while isinstance(tree, dict):
            if x[tree['feature']] <= tree['threshold']:
                tree = tree['left']
            else:
                tree = tree['right']
        return tree

    def fit(self, X, y, progress_callback=None):
        """Fit the Random Forest with OOB scoring."""
        X = np.asarray(X, dtype=float)
        y = np.asarray(y, dtype=float).reshape(-1)

        if len(X) == 0:
            raise ValueError("Training data is empty.")

        n_samples = X.shape[0]
        self.trees = []
        oob_predictions = np.zeros(n_samples)
        oob_counts = np.zeros(n_samples)

        for i in range(self.n_estimators):
            # Bootstrap sampling
            if self.bootstrap:
                indices = np.random.choice(n_samples, n_samples, replace=True)
                oob_mask = ~np.isin(np.arange(n_samples), indices)
                X_sample = X[indices]
                y_sample = y[indices]
            else:
                X_sample = X
                y_sample = y
                oob_mask = np.zeros(n_samples, dtype=bool)

            # Build tree
            tree = self._build_tree(X_sample, y_sample)
            self.trees.append(tree)

            # OOB prediction
            if np.any(oob_mask):
                oob_X = X[oob_mask]
                for j, idx in enumerate(np.where(oob_mask)[0]):
                    pred = self._predict_tree(tree, oob_X[j])
                    oob_predictions[idx] += pred
                    oob_counts[idx] += 1

            if progress_callback:
                progress_callback(i + 1)

        # Calculate OOB score
        valid_oob = oob_counts > 0
        if np.any(valid_oob):
            oob_pred = oob_predictions[valid_oob] / oob_counts[valid_oob]
            oob_true = y[valid_oob]
            self.oob_score_ = 1 - np.mean((oob_pred - oob_true) ** 2) / np.var(oob_true)

        # Training loss
        predictions = self.predict(X)
        self.training_loss_ = float(np.mean((predictions - y) ** 2))

        return self

    def predict(self, X):
        """Predict using ensemble average."""
        X = np.asarray(X, dtype=float)
        if len(X) == 0:
            return np.array([])

        predictions = np.zeros((len(X), len(self.trees)))
        for i, tree in enumerate(self.trees):
            for j, x in enumerate(X):
                predictions[j, i] = self._predict_tree(tree, x)

        return np.mean(predictions, axis=1)

    def predict_with_confidence(self, X):
        """Predict with confidence interval."""
        X = np.asarray(X, dtype=float)
        if len(X) == 0:
            return np.array([]), np.array([])

        predictions = np.zeros((len(X), len(self.trees)))
        for i, tree in enumerate(self.trees):
            for j, x in enumerate(X):
                predictions[j, i] = self._predict_tree(tree, x)

        mean_pred = np.mean(predictions, axis=1)
        std_pred = np.std(predictions, axis=1)
        confidence = np.exp(-std_pred / (np.abs(mean_pred) + 1e-8)) * 100
        confidence = np.clip(confidence, 0, 100)

        return mean_pred, confidence


def train_random_forest_and_forecast(
    close_prices,
    symbol="UNKNOWN",
    forecast_horizon=30,
    n_estimators=200,
    max_depth=15,
    force_retrain=False,
    use_cache=False,
    cached_only=False,
    progress_callback=None
):
    """
    Train or load a Random Forest model with enhanced features.
    """
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)

    min_required = 50  # Need at least 50 prices for meaningful features
    if len(close_prices) < min_required:
        raise ValueError(
            f"Random Forest requires at least {min_required} prices. Got {len(close_prices)}."
        )

    registry = RandomForestModelRegistry()
    config = {
        "n_estimators": n_estimators,
        "max_depth": max_depth,
        "max_features": "sqrt",
        "min_samples_split": 5,
        "min_samples_leaf": 2,
        "bootstrap": True
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
                    f"No trained Random Forest model exists for {symbol}. "
                    "Please train the model from the admin panel first."
                )
            else:
                needs_retrain = True
                reason = "No cached model found"
        else:
            needs_retrain, reason = registry.needs_retraining(symbol, close_prices, config)

        if not needs_retrain:
            print(f"Using cached Random Forest model for {symbol}: {reason}")
            model_bundle, metadata = registry.get_latest_model(symbol)

            if model_bundle and "model" in model_bundle:
                model = model_bundle["model"]

                # Generate features for forecasting
                X_full = create_enhanced_features(close_prices)

                # Multi-step recursive forecast
                forecast = []
                current_prices = list(close_prices)

                for step in range(forecast_horizon):
                    # Create features with current history
                    temp_prices = np.array(current_prices)
                    X_step = create_enhanced_features(temp_prices)
                    if len(X_step) > 0:
                        next_pred = model.predict(X_step[-1:])[0]
                    else:
                        next_pred = current_prices[-1]

                    forecast.append(next_pred)
                    current_prices.append(next_pred)

                forecast = np.array(forecast)

                # Calculate metrics on validation set
                X_all = create_enhanced_features(close_prices)
                n = len(X_all)
                split_idx = max(int(n * 0.80), 1)

                if split_idx < n:
                    X_val = X_all[split_idx:]
                    y_val = close_prices[split_idx + 20:]  # Adjust for feature window

                    # Align lengths
                    min_len = min(len(X_val), len(y_val))
                    X_val = X_val[:min_len]
                    y_val = y_val[:min_len]

                    if len(X_val) > 0:
                        eval_pred = model.predict(X_val)
                        eval_actual = y_val

                        # Calculate metrics
                        mse = float(np.mean((eval_pred - eval_actual) ** 2))
                        mae = float(np.mean(np.abs(eval_pred - eval_actual)))
                        rmse = float(np.sqrt(mse))
                        mape = float(np.mean(
                            np.abs((eval_actual - eval_pred) / (np.abs(eval_actual) + 1e-8))
                        ) * 100)

                        # FIXED: Directional accuracy - compare direction changes
                        if len(eval_pred) > 1:
                            # Compare if both actual and predicted moved in same direction
                            actual_direction = np.sign(np.diff(eval_actual))
                            pred_direction = np.sign(np.diff(eval_pred))
                            da = float(np.mean(actual_direction == pred_direction) * 100)
                        else:
                            da = 50.0

                        # Calculate R-squared
                        ss_res = np.sum((eval_actual - eval_pred) ** 2)
                        ss_tot = np.sum((eval_actual - np.mean(eval_actual)) ** 2)
                        r2 = 1 - (ss_res / (ss_tot + 1e-8))

                        confidence_score = max(1.0, min(99.0, 100.0 - mape))

                        metrics = {
                            "mse": round(mse, 4),
                            "mae": round(mae, 4),
                            "rmse": round(rmse, 4),
                            "mape": f"{mape:.2f}%",
                            "directional_accuracy": f"{da:.1f}%",
                            "confidence_score": f"{confidence_score:.1f}%",
                            "r2_score": round(r2, 4),
                            "training_loss": model_bundle.get("training_loss", 0),
                            "oob_score": getattr(model, 'oob_score_', 0),
                            "trees": n_estimators,
                            "eval_set": "validation",
                            "cached": True,
                        }

                        model_key = f"{symbol}_random_forest"
                        registry_meta = registry.metadata["models"].get(model_key, {})

                        return {
                            "forecast": forecast.reshape(-1),
                            "metrics": metrics,
                            "cached": True,
                            "path": registry_meta.get("path"),
                            "latest_path": registry_meta.get("latest_path"),
                            "fingerprint": registry_meta.get("fingerprint"),
                            "training_date": registry_meta.get("training_date"),
                            "config": registry_meta.get("config"),
                        }

    # ── TRAIN PATH ────────────────────────────────────────────────────────────
    print(f"Training new Random Forest model for {symbol}...")

    # Create enhanced features
    X = create_enhanced_features(close_prices)
    y = close_prices[20:]  # Align with feature window

    # Ensure alignment
    min_len = min(len(X), len(y))
    X = X[:min_len]
    y = y[:min_len]

    # Train/validation split
    n = len(X)
    split_idx = max(int(n * 0.80), 20)

    X_train, X_val = X[:split_idx], X[split_idx:]
    y_train, y_val = y[:split_idx], y[split_idx:]

    # Train model
    model = ScratchRandomForestRegressor(
        n_estimators=n_estimators,
        max_depth=max_depth,
        min_samples_split=5,
        min_samples_leaf=2,
        max_features="sqrt",
        bootstrap=True,
        random_state=42,
        min_impurity_decrease=1e-7
    )

    model.fit(X_train, y_train, progress_callback=progress_callback)

    # Evaluate on validation set
    if len(X_val) > 0:
        eval_pred = model.predict(X_val)
        eval_actual = y_val

        mse = float(np.mean((eval_pred - eval_actual) ** 2))
        mae = float(np.mean(np.abs(eval_pred - eval_actual)))
        rmse = float(np.sqrt(mse))
        mape = float(np.mean(
            np.abs((eval_actual - eval_pred) / (np.abs(eval_actual) + 1e-8))
        ) * 100)

        if len(eval_pred) > 1:
            # FIXED: Directional accuracy - compare direction changes
            actual_direction = np.sign(np.diff(eval_actual))
            pred_direction = np.sign(np.diff(eval_pred))
            da = float(np.mean(actual_direction == pred_direction) * 100)
        else:
            da = 50.0

        ss_res = np.sum((eval_actual - eval_pred) ** 2)
        ss_tot = np.sum((eval_actual - np.mean(eval_actual)) ** 2)
        r2 = 1 - (ss_res / (ss_tot + 1e-8))

        confidence_score = max(1.0, min(99.0, 100.0 - mape))

        eval_label = "validation" if len(X_val) >= 10 else "training_only"
    else:
        eval_pred = model.predict(X_train)
        eval_actual = y_train
        mse = float(np.mean((eval_pred - eval_actual) ** 2))
        mae = float(np.mean(np.abs(eval_pred - eval_actual)))
        rmse = float(np.sqrt(mse))
        mape = float(np.mean(np.abs((eval_actual - eval_pred) / (np.abs(eval_actual) + 1e-8))) * 100)
        da = 50.0
        r2 = 0
        confidence_score = max(1.0, min(99.0, 100.0 - mape))
        eval_label = "training_only"

    # Generate forecast
    forecast = []
    current_prices = list(close_prices)

    for step in range(forecast_horizon):
        temp_prices = np.array(current_prices)
        X_step = create_enhanced_features(temp_prices)
        if len(X_step) > 0:
            next_pred = model.predict(X_step[-1:])[0]
        else:
            next_pred = current_prices[-1]

        forecast.append(next_pred)
        current_prices.append(next_pred)

    forecast = np.array(forecast)

    metrics = {
        "mse": round(mse, 4),
        "mae": round(mae, 4),
        "rmse": round(rmse, 4),
        "mape": f"{mape:.2f}%",
        "directional_accuracy": f"{da:.1f}%",
        "confidence_score": f"{confidence_score:.1f}%",
        "r2_score": round(r2, 4),
        "training_loss": round(float(model.training_loss_), 6),
        "oob_score": round(float(model.oob_score_ or 0), 4),
        "trees": n_estimators,
        "eval_set": eval_label,
        "cached": False,
    }

    # Save model
    registry.save_model(
        symbol=symbol,
        model=model,
        training_metrics=metrics,
        close_prices=close_prices,
        config=config,
    )

    model_key = f"{symbol}_random_forest"
    registry_meta = registry.metadata["models"].get(model_key, {})

    return {
        "forecast": forecast.reshape(-1),
        "metrics": metrics,
        "cached": False,
        "path": registry_meta.get("path"),
        "latest_path": registry_meta.get("latest_path"),
        "fingerprint": registry_meta.get("fingerprint"),
        "training_date": registry_meta.get("training_date"),
        "config": registry_meta.get("config"),
    }
