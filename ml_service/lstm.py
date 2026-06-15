import numpy as np
import os
import pickle
import json
import hashlib
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, Optional, Tuple


# ──────────────────────────────────────────────────────────────────────────────
# MODEL REGISTRY FOR PERSISTENCE
# ──────────────────────────────────────────────────────────────────────────────

class ModelRegistry:
    """Persistent storage for trained models with versioning and fingerprinting."""

    def __init__(self, model_dir: str = None):
        # Use absolute path based on script location if not provided
        if model_dir is None:
            script_dir = Path(__file__).resolve().parent
            model_dir = script_dir / "models"

        self.model_dir = Path(model_dir).resolve()
        self.lstm_dir = self.model_dir / "lstm"
        self.metadata_file = self.model_dir / "metadata.json"

        # Create directories if they don't exist
        self.lstm_dir.mkdir(parents=True, exist_ok=True)
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
                pass  # Corrupt/empty file — start fresh
        return {"models": {}, "last_updated": None}

    def _save_metadata(self):
        """Save model metadata registry."""
        self.metadata["last_updated"] = datetime.now().isoformat()
        with open(self.metadata_file, 'w') as f:
            json.dump(self.metadata, f, indent=2)

    def _compute_model_fingerprint(self, close_prices: np.ndarray,
                                   sequence_length: int,
                                   hidden_size: int) -> str:
        """Compute a unique fingerprint for model configuration + data."""
        data_sample = close_prices[-100:] if len(close_prices) > 100 else close_prices

        fingerprint_data = {
            "data_hash": hashlib.md5(data_sample.tobytes()).hexdigest(),
            "data_length": len(close_prices),
            "sequence_length": sequence_length,
            "hidden_size": hidden_size,
            "last_price": float(close_prices[-1]) if len(close_prices) > 0 else None,
        }

        fingerprint_str = json.dumps(fingerprint_data, sort_keys=True)
        return hashlib.md5(fingerprint_str.encode()).hexdigest()[:16]

    def get_latest_model(self, symbol: str) -> Optional[Tuple[Any, Dict]]:
        """Retrieve the latest trained model for a symbol."""
        # Check for latest model file
        latest_file = self.lstm_dir / f"{symbol}_latest.pkl"

        if latest_file.exists():
            try:
                with open(latest_file, 'rb') as f:
                    model_bundle = pickle.load(f)

                model_key = f"{symbol}_lstm"
                metadata = self.metadata["models"].get(model_key, {})
                return model_bundle, metadata
            except Exception as e:
                print(f" Error loading latest model: {e}")

        # Fallback: find most recent timestamped .pkl file
        pkl_files = list(self.lstm_dir.glob(f"{symbol}_20*.pkl"))
        # Filter out the latest file
        pkl_files = [f for f in pkl_files if not f.name.endswith('_latest.pkl')]

        if not pkl_files:
            return None, None

        # Get the most recent file by modification time
        latest_file = max(pkl_files, key=lambda p: p.stat().st_mtime)

        try:
            with open(latest_file, 'rb') as f:
                model_bundle = pickle.load(f)

            model_key = f"{symbol}_lstm"
            metadata = self.metadata["models"].get(model_key, {})
            return model_bundle, metadata
        except Exception as e:
            print(f" Error loading model from {latest_file}: {e}")
            return None, None

    def save_model(self, symbol: str, model: Any, training_metrics: Dict,
                   close_prices: np.ndarray, config: Dict) -> str:
        """Save a trained model to disk."""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{symbol}_{timestamp}.pkl"
        model_path = self.lstm_dir / filename

        # Compute fingerprint
        fingerprint = self._compute_model_fingerprint(
            close_prices,
            config.get("sequence_length", 20),
            config.get("hidden_size", 16)
        )

        # Bundle model with metadata
        model_bundle = {
            "model": model,
            "fingerprint": fingerprint,
            "training_metrics": training_metrics,
            "config": config,
            "training_date": datetime.now().isoformat(),
            "data_length": len(close_prices),
            "symbol": symbol
        }

        # Save model
        with open(model_path, 'wb') as f:
            pickle.dump(model_bundle, f)

        # Save as latest model (overwrite instead of symlink)
        latest_path = self.lstm_dir / f"{symbol}_latest.pkl"
        try:
            # For Windows, just save a copy as latest
            with open(latest_path, 'wb') as f:
                pickle.dump(model_bundle, f)
            print(f"Model saved to {model_path}")
            print(f"Latest model updated: {latest_path}")
        except Exception as e:
            print(f"WARNING: Could not save latest model file: {e}")
            print(f"Model still saved to {model_path}")

        # Update metadata
        model_key = f"{symbol}_lstm"
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

        # Insert into database directly
        try:
            from db import register_model_in_db
            register_model_in_db(
                symbol=symbol,
                model_type="lstm",
                model_path=str(model_path),
                latest_path=str(latest_path) if latest_path.exists() else None,
                fingerprint=fingerprint,
                training_date=datetime.now().isoformat(),
                data_length=len(close_prices),
                config=config,
                metrics=training_metrics
            )
        except Exception as e:
            print(f"Warning: Failed to insert model into database: {e}")

        return str(model_path)

    def needs_retraining(self, symbol: str, current_prices: np.ndarray,
                        config: Dict) -> Tuple[bool, str]:
        """Check if model needs retraining."""
        model_key = f"{symbol}_lstm"

        # No model exists
        if model_key not in self.metadata["models"]:
            return True, "No existing model found"

        metadata = self.metadata["models"][model_key]

        # Check if model file actually exists
        model_path = Path(metadata["path"])
        if not model_path.exists():
            return True, f"Model file missing: {model_path}"

        # Check if data has changed
        current_fingerprint = self._compute_model_fingerprint(
            current_prices,
            config.get("sequence_length", 20),
            config.get("hidden_size", 16)
        )

        if metadata["fingerprint"] != current_fingerprint:
            return True, "Data fingerprint changed - new data available"

        # Check model age (retrain weekly)
        training_date = datetime.fromisoformat(metadata["training_date"])
        days_since_training = (datetime.now() - training_date).days
        if days_since_training >= 7:
            return True, f"Model is {days_since_training} days old"

        # Check data growth (retrain if 30% more data available)
        old_data_len = metadata.get("data_length", 0)
        new_data_len = len(current_prices)
        if new_data_len > old_data_len * 1.3:
            return True, f"Data grew by {new_data_len - old_data_len} rows ({((new_data_len/old_data_len)-1)*100:.1f}%)"

        return False, "Model is valid"


# ──────────────────────────────────────────────────────────────────────────────
# UTILITY: ACTIVATION
# ──────────────────────────────────────────────────────────────────────────────

def sigmoid(x):
    """Numerically stable sigmoid — clips input to [-50, 50] to prevent overflow."""
    return 1.0 / (1.0 + np.exp(-np.clip(x, -50, 50)))


# ──────────────────────────────────────────────────────────────────────────────
# UTILITY: SCALER
# ──────────────────────────────────────────────────────────────────────────────

class MinMaxScaler1D:
    """
    Min-Max normaliser for a 1-D price series → maps values into [0, 1].
    """

    def __init__(self):
        self.data_min = None
        self.data_max = None
        self.scale = None

    def fit(self, values):
        values = np.asarray(values, dtype=float).reshape(-1)
        self.data_min = float(np.min(values))
        self.data_max = float(np.max(values))
        span = self.data_max - self.data_min
        self.scale = span if span > 1e-12 else 1.0
        return self

    def transform(self, values):
        return (np.asarray(values, dtype=float) - self.data_min) / self.scale

    def inverse_transform(self, values):
        return (np.asarray(values, dtype=float) * self.scale) + self.data_min

    def fit_transform(self, values):
        self.fit(values)
        return self.transform(values)


# ──────────────────────────────────────────────────────────────────────────────
# UTILITY: SEQUENCE BUILDER
# ──────────────────────────────────────────────────────────────────────────────

def create_sequences(values, sequence_length):
    """
    Convert a 1-D price series into supervised (X, y) pairs for LSTM training.
    Enhanced with multi-feature input for better directional accuracy.
    
    Features per timestep:
    - Normalized price
    - Price change (momentum)
    - Price velocity (acceleration)
    
    FIXED: Proper normalization by std of changes, not mean of prices
    """
    values = np.asarray(values, dtype=float).reshape(-1)
    x_list, y_list = [], []

    for i in range(sequence_length, len(values)):
        # Get price sequence
        price_seq = values[i - sequence_length:i]
        
        # Feature 1: Normalized prices (position in range)
        seq_min = np.min(price_seq)
        seq_max = np.max(price_seq)
        seq_range = seq_max - seq_min
        if seq_range > 1e-8:
            normalized_prices = (price_seq - seq_min) / seq_range
        else:
            normalized_prices = np.ones_like(price_seq) * 0.5
        
        # Feature 2: Price changes (momentum) - FIXED: normalize by std of changes
        price_changes = np.diff(price_seq, prepend=price_seq[0])
        change_std = np.std(price_changes)
        if change_std > 1e-8:
            price_changes = price_changes / change_std
        else:
            price_changes = price_changes * 0  # All zeros if no variation
        
        # Feature 3: Price velocity (second derivative / acceleration)
        price_velocity = np.diff(price_changes, prepend=0.0)
        
        # Stack features: [sequence_length, 3]
        features = np.column_stack([normalized_prices, price_changes, price_velocity])
        
        x_list.append(features)
        y_list.append(values[i])

    if not x_list:
        return np.empty((0, sequence_length, 3)), np.empty((0, 1))

    return np.array(x_list, dtype=float), np.array(y_list, dtype=float).reshape(-1, 1)


# ──────────────────────────────────────────────────────────────────────────────
# CORE MODEL: SCRATCH LSTM
# ──────────────────────────────────────────────────────────────────────────────

class ScratchLSTMRegressor:
    """
    Enhanced LSTM regressor with better architecture for directional accuracy.
    Uses multi-feature input and dropout-like regularization.
    """

    def __init__(
        self,
        input_size=3,  # Changed from 1 to 3 for multi-feature input
        hidden_size=32,
        learning_rate=0.005,
        epochs=200,
        seed=42,
        dropout_rate=0.1,  # Added dropout for regularization
        early_stopping_patience=15,  # ADDED: Early stopping patience
        context_size=0,
    ):
        self.input_size = input_size
        self.hidden_size = hidden_size
        self.learning_rate = learning_rate
        self.epochs = epochs
        self.dropout_rate = dropout_rate
        self.early_stopping_patience = early_stopping_patience
        self.context_size = context_size
        self.rng = np.random.default_rng(seed)
        self.training_loss_ = None
        self.best_weights_ = None
        self._initialize_weights()

    def _initialize_weights(self):
        """
        FIXED: Proper Xavier/Glorot initialization for sigmoid/tanh activations.
        Forget gate bias initialized to 1 for better gradient flow.
        """
        concat_size = self.input_size + self.hidden_size
        # Xavier initialization: scale = sqrt(2 / (fan_in + fan_out))
        scale = np.sqrt(2.0 / (concat_size + self.hidden_size))

        self.Wf = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wi = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wc = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wo = self.rng.normal(0, scale, (self.hidden_size, concat_size))

        # FIXED: Forget gate bias = 1 (helps cell state persist, prevents vanishing gradients)
        self.bf = np.ones((self.hidden_size, 1))
        self.bi = np.zeros((self.hidden_size, 1))
        self.bc = np.zeros((self.hidden_size, 1))
        self.bo = np.zeros((self.hidden_size, 1))

        # Output layer initialization
        output_input_size = self.hidden_size + self.context_size
        self.Wy = self.rng.normal(
            0,
            np.sqrt(2.0 / output_input_size),
            (1, output_input_size),
        )
        self.by = np.zeros((1, 1))
    
    def _save_best_weights(self):
        """Save current weights as best weights."""
        self.best_weights_ = {
            'Wf': self.Wf.copy(), 'Wi': self.Wi.copy(),
            'Wc': self.Wc.copy(), 'Wo': self.Wo.copy(),
            'bf': self.bf.copy(), 'bi': self.bi.copy(),
            'bc': self.bc.copy(), 'bo': self.bo.copy(),
            'Wy': self.Wy.copy(), 'by': self.by.copy(),
        }
    
    def _restore_best_weights(self):
        """Restore best weights."""
        if self.best_weights_ is not None:
            self.Wf = self.best_weights_['Wf'].copy()
            self.Wi = self.best_weights_['Wi'].copy()
            self.Wc = self.best_weights_['Wc'].copy()
            self.Wo = self.best_weights_['Wo'].copy()
            self.bf = self.best_weights_['bf'].copy()
            self.bi = self.best_weights_['bi'].copy()
            self.bc = self.best_weights_['bc'].copy()
            self.bo = self.best_weights_['bo'].copy()
            self.Wy = self.best_weights_['Wy'].copy()
            self.by = self.best_weights_['by'].copy()

    def _forward(self, sequence, training=False, context=None):
        """
        FIXED: Proper recurrent dropout - applied to h_prev once per sequence,
        not regenerated at every timestep.
        """
        h_prev = np.zeros((self.hidden_size, 1))
        c_prev = np.zeros((self.hidden_size, 1))
        cache = []
        
        # Generate dropout mask ONCE for the entire sequence (recurrent dropout)
        if training and self.dropout_rate > 0:
            dropout_mask = (self.rng.random((self.hidden_size, 1)) > self.dropout_rate).astype(float)
            dropout_mask = dropout_mask / (1.0 - self.dropout_rate)  # Inverted dropout scaling
        else:
            dropout_mask = np.ones((self.hidden_size, 1))

        for x_t in sequence:
            x_t = np.asarray(x_t, dtype=float).reshape(self.input_size, 1)
            
            # Apply dropout to recurrent connection (h_prev)
            h_dropped = h_prev * dropout_mask
            z = np.vstack((h_dropped, x_t))

            f_t = sigmoid(self.Wf @ z + self.bf)
            i_t = sigmoid(self.Wi @ z + self.bi)
            c_bar = np.tanh(self.Wc @ z + self.bc)
            c_t = (f_t * c_prev) + (i_t * c_bar)
            o_t = sigmoid(self.Wo @ z + self.bo)
            h_t = o_t * np.tanh(c_t)

            cache.append({
                "z": z, "f": f_t, "i": i_t, "c_bar": c_bar,
                "c": c_t, "o": o_t, "h_prev": h_prev, "c_prev": c_prev, "h": h_t,
            })
            h_prev, c_prev = h_t, c_t

        context_size = getattr(self, "context_size", 0)
        if context_size:
            context = np.asarray(context, dtype=float).reshape(context_size, 1)
            output_input = np.vstack([h_prev, context])
        else:
            output_input = h_prev
        y_pred = self.Wy @ output_input + self.by
        if cache:
            cache[-1]["output_input"] = output_input
        return y_pred, cache

    def _backward(self, y_pred, y_true, cache):
        grads = {k: np.zeros_like(getattr(self, k))
                 for k in ("Wf", "Wi", "Wc", "Wo", "bf", "bi", "bc", "bo", "Wy", "by")}

        dy = y_pred - y_true
        grads["Wy"] += dy @ cache[-1]["output_input"].T
        grads["by"] += dy

        dh_next = self.Wy[:, :self.hidden_size].T @ dy
        dc_next = np.zeros((self.hidden_size, 1))

        for step in reversed(cache):
            z = step["z"]
            f_t = step["f"]
            i_t = step["i"]
            c_bar = step["c_bar"]
            c_t = step["c"]
            o_t = step["o"]
            c_prev = step["c_prev"]

            tanh_c = np.tanh(c_t)
            dh = dh_next

            do = dh * tanh_c
            do_raw = do * o_t * (1.0 - o_t)

            dc = (dh * o_t * (1.0 - tanh_c ** 2)) + dc_next
            df = dc * c_prev
            df_raw = df * f_t * (1.0 - f_t)

            di = dc * c_bar
            di_raw = di * i_t * (1.0 - i_t)

            dc_bar = dc * i_t
            dc_bar_r = dc_bar * (1.0 - c_bar ** 2)

            grads["Wf"] += df_raw @ z.T
            grads["Wi"] += di_raw @ z.T
            grads["Wc"] += dc_bar_r @ z.T
            grads["Wo"] += do_raw @ z.T
            grads["bf"] += df_raw
            grads["bi"] += di_raw
            grads["bc"] += dc_bar_r
            grads["bo"] += do_raw

            dz = (self.Wf.T @ df_raw + self.Wi.T @ di_raw +
                  self.Wc.T @ dc_bar_r + self.Wo.T @ do_raw)
            dh_next = dz[:self.hidden_size, :]
            dc_next = dc * f_t

        return grads, float(0.5 * np.square(dy).item())

    def _apply_gradients(self, grads):
        for name, gradient in grads.items():
            np.clip(gradient, -1.0, 1.0, out=gradient)
            setattr(self, name, getattr(self, name) - self.learning_rate * gradient)

    def fit(
        self,
        x_train,
        y_train,
        x_val=None,
        y_val=None,
        progress_callback=None,
        contexts=None,
        val_contexts=None,
    ):
        """
        ADDED: Early stopping support with optional validation set.
        """
        x_train = np.asarray(x_train, dtype=float)
        y_train = np.asarray(y_train, dtype=float).reshape(-1, 1)

        if len(x_train) == 0:
            raise ValueError("No training sequences available")

        # FIXED: Step-based learning rate decay (simpler and more effective)
        initial_lr = self.learning_rate
        
        # Early stopping variables
        best_val_loss = np.inf
        patience_counter = 0
        use_early_stopping = (x_val is not None and y_val is not None and len(x_val) > 0)
        
        for epoch in range(self.epochs):
            # Step decay: reduce LR at 1/3 and 2/3 of training
            if epoch < self.epochs // 3:
                self.learning_rate = initial_lr
            elif epoch < 2 * self.epochs // 3:
                self.learning_rate = initial_lr * 0.1
            else:
                self.learning_rate = initial_lr * 0.01
            
            epoch_loss = 0.0
            # Shuffle training data each epoch for better generalization
            indices = self.rng.permutation(len(x_train))
            
            for idx in indices:
                sequence = x_train[idx]
                target = y_train[idx]
                context = contexts[idx] if contexts is not None else None
                y_pred, cache = self._forward(
                    sequence,
                    training=True,
                    context=context,
                )
                grads, loss = self._backward(y_pred, target.reshape(1, 1), cache)
                self._apply_gradients(grads)
                epoch_loss += loss
            self.training_loss_ = epoch_loss / len(x_train)
            
            # Early stopping check
            if use_early_stopping:
                val_pred = self.predict(x_val, contexts=val_contexts)
                val_loss = np.mean((val_pred - y_val) ** 2)
                
                if val_loss < best_val_loss:
                    best_val_loss = val_loss
                    patience_counter = 0
                    self._save_best_weights()
                else:
                    patience_counter += 1
                
                if patience_counter >= self.early_stopping_patience:
                    # Early stopping triggered
                    self._restore_best_weights()
                    break
            
            if progress_callback:
                progress_callback(epoch + 1)

        # Restore original learning rate
        self.learning_rate = initial_lr
        return self

    def predict_one(self, sequence, context=None):
        prediction, _ = self._forward(
            sequence,
            training=False,
            context=context,
        )
        return float(prediction.item())

    def predict(self, x_values, contexts=None):
        x_values = np.asarray(x_values, dtype=float)
        return np.array(
            [
                self.predict_one(
                    seq,
                    context=contexts[index] if contexts is not None else None,
                )
                for index, seq in enumerate(x_values)
            ],
            dtype=float,
        ).reshape(-1, 1)

    def forecast(self, seed_prices, steps):
        """
        Enhanced forecast that maintains multi-feature representation.
        seed_prices: raw price values used to generate features
        
        FIXED: Proper feature generation matching create_sequences()
        """
        # Keep track of price history for feature generation
        price_history = list(seed_prices)
        predictions = []
        
        for _ in range(steps):
            # Get the last sequence_length prices
            recent_prices = np.array(price_history[-len(seed_prices):])
            
            # Generate features same way as in create_sequences - FIXED normalization
            seq_min = np.min(recent_prices)
            seq_max = np.max(recent_prices)
            seq_range = seq_max - seq_min
            if seq_range > 1e-8:
                normalized_prices = (recent_prices - seq_min) / seq_range
            else:
                normalized_prices = np.ones_like(recent_prices) * 0.5
            
            # FIXED: Normalize by std of changes, not mean of prices
            price_changes = np.diff(recent_prices, prepend=recent_prices[0])
            change_std = np.std(price_changes)
            if change_std > 1e-8:
                price_changes = price_changes / change_std
            else:
                price_changes = price_changes * 0
            
            # FIXED: Compute velocity without duplicating boundary (was: prepend=price_changes[0])
            price_velocity = np.diff(price_changes, prepend=0.0)
            
            features = np.column_stack([normalized_prices, price_changes, price_velocity])
            
            # Predict next value
            next_val = self.predict_one(features)
            predictions.append(next_val)
            price_history.append(next_val)
        
        return np.array(predictions, dtype=float)


# ──────────────────────────────────────────────────────────────────────────────
# PUBLIC API WITH PERSISTENCE
# ──────────────────────────────────────────────────────────────────────────────

def train_and_forecast(
    close_prices,
    symbol="UNKNOWN",
    sequence_length=20,
    hidden_size=32,
    epochs=200,
    learning_rate=0.005,
    force_retrain=False,
    use_cache=False,
    cached_only=False,
    progress_callback=None,
):
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)

    min_required = sequence_length + 10
    if len(close_prices) < min_required:
        raise ValueError(
            f"LSTM requires at least {min_required} prices. Got {len(close_prices)}."
        )

    registry = ModelRegistry()
    config = {
        "sequence_length": sequence_length,
        "hidden_size": hidden_size,
        "epochs": epochs,
        "learning_rate": learning_rate
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
                    f"No trained LSTM model exists for {symbol}. "
                    "Please train the model from the admin panel first."
                )
            else:
                needs_retrain = True
                reason = "No cached model found"
        else:
            needs_retrain, reason = registry.needs_retraining(symbol, close_prices, config)

        if not needs_retrain:
            print(f"Using cached LSTM model for {symbol}: {reason}")
            model_bundle, metadata = registry.get_latest_model(symbol)

            if model_bundle and "model" in model_bundle:
                model = model_bundle["model"]
                
                # Check if this is an old model (input_size=1) or new model (input_size=3)
                is_old_model = (model.input_size == 1)
                
                if is_old_model:
                    print(f"WARNING: Old model detected (input_size=1). Forcing retrain for new features...")
                    needs_retrain = True
                    reason = "Old model format - retraining with enhanced features"
                else:
                    scaler = MinMaxScaler1D()
                    scaled = scaler.fit_transform(close_prices)

                    forecast_seed = scaled[-sequence_length:]
                    forecast_scaled = model.forecast(forecast_seed, steps=30)
                    forecast_prices = scaler.inverse_transform(forecast_scaled)

                    X, y = create_sequences(scaled, sequence_length)
                    split_idx = max(int(len(X) * 0.80), 1)
                    X_val, y_val = X[split_idx:], y[split_idx:]

                    if len(X_val) > 0:
                        pred_scaled   = model.predict(X_val).reshape(-1)
                        actual_scaled = y_val.reshape(-1)
                        pred_prices   = scaler.inverse_transform(pred_scaled)
                        actual_prices = scaler.inverse_transform(actual_scaled)

                        mse  = float(np.mean((pred_prices - actual_prices) ** 2))
                        mae  = float(np.mean(np.abs(pred_prices - actual_prices)))
                        rmse = float(np.sqrt(mse))
                        mape = float(np.mean(
                            np.abs((actual_prices - pred_prices) / (np.abs(actual_prices) + 1e-8))
                        ) * 100)
                        if len(pred_prices) > 1:
                            # FIXED: Correct directional accuracy calculation
                            # Compare if both actual and predicted moved in same direction
                            # actual_direction[i] = sign(actual[i+1] - actual[i])
                            # pred_direction[i] = sign(pred[i+1] - pred[i])
                            actual_direction = np.sign(np.diff(actual_prices))
                            pred_direction = np.sign(np.diff(pred_prices))
                            directional_matches = (actual_direction == pred_direction)
                            da = float(np.mean(directional_matches) * 100)
                        else:
                            da = 50.0
                        confidence_score = max(1.0, min(99.0, 100.0 - mape))

                        metrics = {
                            "mse": round(mse, 4),
                            "mae": round(mae, 4),
                            "rmse": round(rmse, 4),
                            "mape": f"{mape:.2f}%",
                            "directional_accuracy": f"{da:.1f}%",
                            "confidence_score": f"{confidence_score:.1f}%",
                            "training_loss": model_bundle.get("training_loss", 0),
                            "eval_set": "validation",
                            "cached": True,
                        }

                        # ── Pull registry metadata so Laravel gets path/fingerprint ──
                        model_key     = f"{symbol}_lstm"
                        registry_meta = registry.metadata["models"].get(model_key, {})

                        return {
                            "sequence_length": sequence_length,
                            "forecast":        forecast_prices.reshape(-1),
                            "metrics":         metrics,
                            "cached":          True,
                            # Registry fields forwarded to Laravel
                            "path":            registry_meta.get("path"),
                            "latest_path":     registry_meta.get("latest_path"),
                            "fingerprint":     registry_meta.get("fingerprint"),
                            "training_date":   registry_meta.get("training_date"),
                            "config":          registry_meta.get("config"),
                        }

    # ── TRAIN PATH ────────────────────────────────────────────────────────────
    print(f"Training new LSTM model for {symbol}...")

    scaler = MinMaxScaler1D()
    scaled = scaler.fit_transform(close_prices)

    X, y   = create_sequences(scaled, sequence_length)
    n_seq  = len(X)
    MIN_VAL    = 4
    split_idx  = max(int(n_seq * 0.80), 1)
    use_val    = (n_seq - split_idx) >= MIN_VAL
    X_train, y_train = X[:split_idx], y[:split_idx]
    X_val,   y_val   = X[split_idx:], y[split_idx:]

    model = ScratchLSTMRegressor(
        input_size=3,  # Now using 3 features per timestep
        hidden_size=hidden_size,
        learning_rate=learning_rate,
        epochs=epochs,
        dropout_rate=0.1,  # Add regularization
        early_stopping_patience=15,  # ADDED: Early stopping
    )
    # ADDED: Pass validation data for early stopping
    model.fit(X_train, y_train, x_val=X_val if use_val else None, y_val=y_val if use_val else None, progress_callback=progress_callback)

    if use_val:
        pred_scaled   = model.predict(X_val).reshape(-1)
        actual_scaled = y_val.reshape(-1)
        eval_label    = "validation"
    else:
        pred_scaled   = model.predict(X_train).reshape(-1)
        actual_scaled = y_train.reshape(-1)
        eval_label    = "training_only"

    pred_prices   = scaler.inverse_transform(pred_scaled)
    actual_prices = scaler.inverse_transform(actual_scaled)

    mse  = float(np.mean((pred_prices - actual_prices) ** 2))
    mae  = float(np.mean(np.abs(pred_prices - actual_prices)))
    rmse = float(np.sqrt(mse))
    mape = float(np.mean(
        np.abs((actual_prices - pred_prices) / (np.abs(actual_prices) + 1e-8))
    ) * 100)
    if len(pred_prices) > 1:
        # FIXED: Correct directional accuracy calculation
        # Compare if both actual and predicted moved in same direction
        actual_direction = np.sign(np.diff(actual_prices))
        pred_direction = np.sign(np.diff(pred_prices))
        directional_matches = (actual_direction == pred_direction)
        da = float(np.mean(directional_matches) * 100)
    else:
        da = 50.0
    confidence_score = max(1.0, min(99.0, 100.0 - mape))

    # For forecasting, use the raw (scaled) prices to generate features
    forecast_seed   = scaled[-sequence_length:]
    forecast_scaled = model.forecast(forecast_seed, steps=30)
    forecast_prices = scaler.inverse_transform(forecast_scaled)

    metrics = {
        "mse": round(mse, 4),
        "mae": round(mae, 4),
        "rmse": round(rmse, 4),
        "mape": f"{mape:.2f}%",
        "directional_accuracy": f"{da:.1f}%",
        "confidence_score": f"{confidence_score:.1f}%",
        "training_loss": round(float(model.training_loss_), 6),
        "eval_set": eval_label,
        "cached": False,
    }

    # save_model updates registry.metadata in-memory before writing to disk,
    # so we can read back the stored fields immediately after without a file read.
    registry.save_model(
        symbol=symbol,
        model=model,
        training_metrics=metrics,
        close_prices=close_prices,
        config=config,
    )

    model_key     = f"{symbol}_lstm"
    registry_meta = registry.metadata["models"].get(model_key, {})

    return {
        "sequence_length": sequence_length,
        "forecast":        forecast_prices.reshape(-1),
        "metrics":         metrics,
        "cached":          False,
        # Registry fields forwarded to Laravel
        "path":            registry_meta.get("path"),
        "latest_path":     registry_meta.get("latest_path"),
        "fingerprint":     registry_meta.get("fingerprint"),
        "training_date":   registry_meta.get("training_date"),
        "config":          registry_meta.get("config"),
    }
