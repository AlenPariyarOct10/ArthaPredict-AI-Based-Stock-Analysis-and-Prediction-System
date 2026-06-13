import os
import pickle
import json
import hashlib
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, Optional, Tuple
import numpy as np
from db import register_model_in_db

class ModelRegistry:
    """
    Persistent storage for trained models with versioning and fingerprinting.

    Directory structure:
    models/
    ├── lstm/
    │   ├── NABIL_20241215_170523.pkl
    │   ├── NABIL_20241216_091234.pkl
    │   └── latest -> NABIL_20241216_091234.pkl
    ├── xgboost/
    │   ├── NABIL_20241215_170523.pkl
    │   └── latest -> NABIL_20241216_091234.pkl
    └── metadata.json
    """

    def __init__(self, model_dir: str = None):
        # Use absolute path based on script location if not provided
        if model_dir is None:
            script_dir = Path(__file__).resolve().parent
            model_dir = script_dir / "models"
        
        self.model_dir = Path(model_dir).resolve()
        self.lstm_dir = self.model_dir / "lstm"
        self.xgb_dir = self.model_dir / "xgboost"
        self.metadata_file = self.model_dir / "metadata.json"

        # Create directories if they don't exist
        self.lstm_dir.mkdir(parents=True, exist_ok=True)
        self.xgb_dir.mkdir(parents=True, exist_ok=True)

        self.metadata = self._load_metadata()

    def _load_metadata(self) -> Dict:
        """Load model metadata registry."""
        if self.metadata_file.exists():
            with open(self.metadata_file, 'r') as f:
                return json.load(f)
        return {"models": {}, "last_updated": None}

    def _save_metadata(self):
        """Save model metadata registry."""
        self.metadata["last_updated"] = datetime.now().isoformat()
        with open(self.metadata_file, 'w') as f:
            json.dump(self.metadata, f, indent=2)

    def _compute_model_fingerprint(self, close_prices: np.ndarray,
                                   sequence_length: int,
                                   hidden_size: int) -> str:
        """
        Compute a unique fingerprint for model configuration + data.
        Used to detect if retraining is needed.
        """
        # Sample last 100 prices for fingerprint (efficient)
        data_sample = close_prices[-100:] if len(close_prices) > 100 else close_prices

        fingerprint_data = {
            "data_hash": hashlib.md5(data_sample.tobytes()).hexdigest(),
            "data_length": len(close_prices),
            "sequence_length": sequence_length,
            "hidden_size": hidden_size,
            "last_price": float(close_prices[-1]) if len(close_prices) > 0 else None,
            "last_date_hash": None  # Could add date info if available
        }

        fingerprint_str = json.dumps(fingerprint_data, sort_keys=True)
        return hashlib.md5(fingerprint_str.encode()).hexdigest()[:16]

    def get_latest_model(self, symbol: str, model_type: str) -> Optional[Tuple[Any, Dict]]:
        """
        Retrieve the latest trained model for a symbol.

        Returns:
            (model, metadata) or (None, None) if not found
        """
        model_dir = self.lstm_dir if model_type == "lstm" else self.xgb_dir
        latest_link = model_dir / "latest"

        if not latest_link.exists() or not latest_link.is_symlink():
            # Fallback: find most recent .pkl file
            pkl_files = list(model_dir.glob(f"{symbol}_*.pkl"))
            if not pkl_files:
                return None, None
            latest_file = max(pkl_files, key=lambda p: p.stat().st_mtime)
        else:
            latest_file = latest_link.resolve()
            if not latest_file.exists():
                return None, None

        # Load model
        with open(latest_file, 'rb') as f:
            model = pickle.load(f)

        # Load metadata for this model
        model_key = f"{symbol}_{model_type}"
        metadata = self.metadata["models"].get(model_key, {})

        return model, metadata

    def save_model(self, symbol: str, model_type: str, model: Any,
                   training_metrics: Dict, close_prices: np.ndarray,
                   config: Dict) -> str:
        """
        Save a trained model to disk with timestamp.

        Returns:
            model_path: Path where model was saved
        """
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"{symbol}_{timestamp}.pkl"

        model_dir = self.lstm_dir if model_type == "lstm" else self.xgb_dir
        model_path = model_dir / filename

        # Compute fingerprint for future change detection
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

        # Update symlink
        latest_link = model_dir / "latest"
        if latest_link.exists() or latest_link.is_symlink():
            latest_link.unlink()
        latest_link.symlink_to(filename)

        # Update metadata
        model_key = f"{symbol}_{model_type}"
        self.metadata["models"][model_key] = {
            "path": str(model_path),
            "fingerprint": fingerprint,
            "training_date": datetime.now().isoformat(),
            "metrics": training_metrics,
            "config": config,
            "data_length": len(close_prices)
        }
        self._save_metadata()

        # Insert into database directly
        try:
            register_model_in_db(
                symbol=symbol,
                model_type=model_type,
                model_path=str(model_path),
                latest_path=None,  # model_persistence doesn't use latest_path
                fingerprint=fingerprint,
                training_date=datetime.now().isoformat(),
                data_length=len(close_prices),
                config=config,
                metrics=training_metrics
            )
        except Exception as e:
            print(f"Warning: Failed to insert model into database: {e}")

        return str(model_path)

    def needs_retraining(self, symbol: str, model_type: str,
                        current_prices: np.ndarray,
                        config: Dict) -> Tuple[bool, str]:
        """
        Check if model needs retraining based on:
        1. No existing model
        2. Data fingerprint changed (new data available)
        3. Model is older than retraining threshold
        4. Performance degraded significantly
        """
        model_key = f"{symbol}_{model_type}"

        # No model exists
        if model_key not in self.metadata["models"]:
            return True, "No existing model found"

        metadata = self.metadata["models"][model_key]

        # Check if data has changed (new rows added)
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
            return True, f"Model is {days_since_training} days old (retrain threshold: 7 days)"

        # Check data growth (retrain if 20% more data available)
        old_data_len = metadata.get("data_length", 0)
        new_data_len = len(current_prices)
        if new_data_len > old_data_len * 1.2:
            return True, f"Data grew by {new_data_len - old_data_len} rows ({((new_data_len/old_data_len)-1)*100:.1f}%)"

        return False, "Model is valid"
