"""Shared infrastructure for scratch-built universal stock models."""

from __future__ import annotations

import json
import os
import pickle
from dataclasses import asdict, dataclass
from datetime import datetime
from pathlib import Path
from typing import Dict, Iterable, List, Optional, Tuple

import numpy as np
import pandas as pd


SCRIPT_DIR = Path(__file__).resolve().parent
MODEL_DIR = SCRIPT_DIR / "models" / "universal"
INDIVIDUAL_MODEL_DIR = SCRIPT_DIR / "models" / "individual"
ALGORITHMS = ("lstm", "xgboost", "random_forest", "moving_average")
MODEL_SCOPES = ("universal", "individual")


@dataclass
class UniversalConfig:
    sequence_length: int = 20
    train_ratio: float = 0.80
    validation_ratio: float = 0.10
    min_stock_length: int = 30
    forecast_horizon: int = 30
    random_seed: int = 42
    max_train_sequences_per_stock: int = 300
    max_validation_sequences_per_stock: int = 80
    max_test_sequences_per_stock: int = 80


@dataclass
class StockSeries:
    symbol: str
    dates: np.ndarray
    prices: np.ndarray
    train_end: int
    validation_end: int
    minimum: float
    maximum: float

    @property
    def scale(self) -> float:
        span = self.maximum - self.minimum
        return span if span > 1e-12 else 1.0

    def normalize(self, values: np.ndarray) -> np.ndarray:
        return (np.asarray(values, dtype=float) - self.minimum) / self.scale

    def inverse(self, values: np.ndarray) -> np.ndarray:
        return np.asarray(values, dtype=float) * self.scale + self.minimum


def load_environment() -> None:
    env_path = SCRIPT_DIR.parent / ".env"
    if not env_path.exists():
        return
    with env_path.open("r", encoding="utf-8") as handle:
        for raw_line in handle:
            line = raw_line.strip()
            if not line or line.startswith("#") or "=" not in line:
                continue
            key, value = line.split("=", 1)
            os.environ.setdefault(key.strip(), value.strip().strip("\"'"))


def database_engine():
    from sqlalchemy import create_engine

    load_environment()
    host = os.getenv("DB_HOST", "127.0.0.1")
    port = os.getenv("DB_PORT", "3306")
    database = os.getenv("DB_DATABASE", "arthapredict")
    username = os.getenv("DB_USERNAME", "root")
    password = os.getenv("DB_PASSWORD", "")
    return create_engine(
        f"mysql+pymysql://{username}:{password}@{host}:{port}/{database}"
    )


def load_stock_series(
    config: UniversalConfig, symbols: Optional[Iterable[str]] = None
) -> Dict[str, StockSeries]:
    from sqlalchemy import text

    engine = database_engine()
    params = {}
    symbol_filter = ""
    requested = sorted({symbol.strip().upper() for symbol in symbols or [] if symbol.strip()})
    if requested:
        placeholders = ", ".join(f":symbol_{index}" for index in range(len(requested)))
        symbol_filter = f"AND s.symbol IN ({placeholders})"
        params = {f"symbol_{index}": symbol for index, symbol in enumerate(requested)}

    query = text(
        f"""
        SELECT s.symbol, p.date, p.close
        FROM stock_prices p
        JOIN stocks s ON s.id = p.stock_id
        WHERE s.is_active = 1
          {symbol_filter}
        ORDER BY s.symbol ASC, p.date ASC
        """
    )
    frame = pd.read_sql(query, engine, params=params)
    if frame.empty:
        return {}

    frame["date"] = pd.to_datetime(frame["date"])
    frame["close"] = pd.to_numeric(frame["close"], errors="coerce")
    frame = frame.dropna(subset=["close"])

    result: Dict[str, StockSeries] = {}
    for symbol, group in frame.groupby("symbol", sort=True):
        group = group.sort_values("date").drop_duplicates("date", keep="last")
        # Imported placeholder rows commonly use exact low integers before a
        # security has real market data. Do not mix those values into a series
        # that otherwise trades at ordinary equity prices.
        if float(group["close"].max()) >= 50.0:
            group = group[group["close"] >= 10.0]
        prices = group["close"].to_numpy(dtype=float)
        if len(prices) < config.min_stock_length:
            continue

        train_end = max(config.sequence_length + 1, int(len(prices) * config.train_ratio))
        validation_end = max(
            train_end + 1,
            int(len(prices) * (config.train_ratio + config.validation_ratio)),
        )
        validation_end = min(validation_end, len(prices) - 1)
        train_prices = prices[:train_end]
        result[str(symbol)] = StockSeries(
            symbol=str(symbol),
            dates=group["date"].to_numpy(),
            prices=prices,
            train_end=train_end,
            validation_end=validation_end,
            minimum=float(np.min(train_prices)),
            maximum=float(np.max(train_prices)),
        )
    return result


def symbol_mapping(series: Dict[str, StockSeries]) -> Dict[str, int]:
    return {symbol: index for index, symbol in enumerate(sorted(series))}


def one_hot(index: int, size: int) -> np.ndarray:
    encoded = np.zeros(size, dtype=float)
    encoded[index] = 1.0
    return encoded


def _bounded_indices(indices: List[int], maximum: int) -> List[int]:
    if maximum <= 0 or len(indices) <= maximum:
        return indices
    positions = np.linspace(0, len(indices) - 1, maximum, dtype=int)
    return [indices[position] for position in positions]


def build_sequence_dataset(
    series: Dict[str, StockSeries],
    mapping: Dict[str, int],
    config: UniversalConfig,
    split: str,
    lstm: bool = False,
) -> Tuple[np.ndarray, np.ndarray]:
    features: List[np.ndarray] = []
    targets: List[float] = []
    stock_count = len(mapping)

    for symbol in sorted(series):
        item = series[symbol]
        normalized = item.normalize(item.prices)
        if split == "train":
            indices = list(range(config.sequence_length, item.train_end))
            limit = config.max_train_sequences_per_stock
        elif split == "validation":
            indices = list(range(item.train_end, item.validation_end))
            limit = config.max_validation_sequences_per_stock
        elif split == "test":
            indices = list(range(item.validation_end, len(item.prices)))
            limit = config.max_test_sequences_per_stock
        else:
            raise ValueError(f"Unsupported split: {split}")

        for target_index in _bounded_indices(indices, limit):
            start = target_index - config.sequence_length
            if start < 0:
                continue
            sequence = normalized[start:target_index]
            identity = one_hot(mapping[symbol], stock_count)
            if lstm:
                identity_steps = np.repeat(
                    identity.reshape(1, -1), config.sequence_length, axis=0
                )
                sample = np.column_stack([sequence, identity_steps])
            else:
                sample = np.concatenate([sequence, identity])
            features.append(sample)
            targets.append(float(normalized[target_index]))

    if not features:
        shape = (0, config.sequence_length, stock_count + 1) if lstm else (
            0,
            config.sequence_length + stock_count,
        )
        return np.empty(shape, dtype=float), np.empty((0,), dtype=float)
    return np.asarray(features, dtype=float), np.asarray(targets, dtype=float)


def build_lstm_dataset(
    series: Dict[str, StockSeries],
    mapping: Dict[str, int],
    config: UniversalConfig,
    split: str,
) -> Tuple[np.ndarray, np.ndarray, np.ndarray]:
    sequences: List[np.ndarray] = []
    contexts: List[np.ndarray] = []
    targets: List[float] = []
    stock_count = len(mapping)

    for symbol in sorted(series):
        item = series[symbol]
        normalized = item.normalize(item.prices)
        if split == "train":
            indices = list(range(config.sequence_length, item.train_end))
            limit = config.max_train_sequences_per_stock
        elif split == "validation":
            indices = list(range(item.train_end, item.validation_end))
            limit = config.max_validation_sequences_per_stock
        elif split == "test":
            indices = list(range(item.validation_end, len(item.prices)))
            limit = config.max_test_sequences_per_stock
        else:
            raise ValueError(f"Unsupported split: {split}")

        identity = one_hot(mapping[symbol], stock_count)
        for target_index in _bounded_indices(indices, limit):
            start = target_index - config.sequence_length
            if start < 0:
                continue
            sequences.append(
                normalized[start:target_index].reshape(config.sequence_length, 1)
            )
            contexts.append(identity)
            targets.append(float(normalized[target_index]))

    return (
        np.asarray(sequences, dtype=float),
        np.asarray(targets, dtype=float),
        np.asarray(contexts, dtype=float),
    )


def regression_metrics(
    actual: np.ndarray,
    predicted: np.ndarray,
    previous_actual: Optional[np.ndarray] = None,
) -> Dict[str, float]:
    actual = np.asarray(actual, dtype=float).reshape(-1)
    predicted = np.asarray(predicted, dtype=float).reshape(-1)
    if len(actual) == 0 or len(actual) != len(predicted):
        raise ValueError("Metrics require equal, non-empty arrays.")
    if previous_actual is not None:
        previous_actual = np.asarray(previous_actual, dtype=float).reshape(-1)
        if len(previous_actual) != len(actual):
            raise ValueError(
                "Directional accuracy requires one previous price per sample."
            )
    errors = predicted - actual
    mse = float(np.mean(errors ** 2))
    mae = float(np.mean(np.abs(errors)))
    rmse = float(np.sqrt(mse))
    mape = float(
        np.mean(np.abs(errors) / np.maximum(np.abs(actual), 1e-8)) * 100.0
    )
    total = float(np.sum((actual - np.mean(actual)) ** 2))
    residual = float(np.sum(errors ** 2))
    r2 = 1.0 - residual / total if total > 1e-12 else 0.0
    metrics = {
        "mse": round(mse, 6),
        "rmse": round(rmse, 6),
        "mae": round(mae, 6),
        "mape": round(mape, 4),
        "r2": round(float(r2), 6),
    }
    if previous_actual is not None:
        actual_direction = np.sign(np.round(actual - previous_actual, 8))
        predicted_direction = np.sign(np.round(predicted - previous_actual, 8))
        directional_accuracy = float(
            np.mean(actual_direction == predicted_direction) * 100.0
        )
        metrics["directional_accuracy"] = round(directional_accuracy, 4)
    return metrics


def aggregate_price_metrics(
    series: Dict[str, StockSeries],
    mapping: Dict[str, int],
    config: UniversalConfig,
    predictions: np.ndarray,
    split: str,
) -> Dict[str, float]:
    actual_prices: List[float] = []
    predicted_prices: List[float] = []
    previous_prices: List[float] = []
    cursor = 0
    for symbol in sorted(series):
        item = series[symbol]
        if split == "validation":
            indices = list(range(item.train_end, item.validation_end))
            limit = config.max_validation_sequences_per_stock
        else:
            indices = list(range(item.validation_end, len(item.prices)))
            limit = config.max_test_sequences_per_stock
        selected = [
            index
            for index in _bounded_indices(indices, limit)
            if index >= config.sequence_length
        ]
        count = len(selected)
        stock_predictions = predictions[cursor:cursor + count]
        cursor += count
        actual_prices.extend(item.prices[selected].tolist())
        previous_prices.extend(item.prices[np.asarray(selected) - 1].tolist())
        predicted_prices.extend(item.inverse(stock_predictions).tolist())
    if cursor != len(predictions):
        raise ValueError(
            "Prediction count does not match the selected metric samples."
        )
    return regression_metrics(
        np.asarray(actual_prices),
        np.asarray(predicted_prices),
        np.asarray(previous_prices),
    )


def save_model(
    algorithm: str,
    model,
    config: UniversalConfig,
    mapping: Dict[str, int],
    scalers: Dict[str, Tuple[float, float]],
    metrics: Dict[str, float],
    extra: Optional[Dict] = None,
    scope: str = "universal",
    symbol: Optional[str] = None,
) -> Path:
    if algorithm not in ALGORITHMS:
        raise ValueError(f"Unsupported algorithm: {algorithm}")
    model_dir = model_directory(scope, symbol)
    model_dir.mkdir(parents=True, exist_ok=True)
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    path = model_dir / f"{algorithm}_{timestamp}.pkl"
    bundle = {
        "algorithm": algorithm,
        "model_scope": scope,
        "stock_symbol": symbol if scope == "individual" else "UNIVERSAL",
        "model": model,
        "config": asdict(config),
        "symbol_to_index": mapping,
        "scalers": scalers,
        "metrics": metrics,
        "extra": extra or {},
        "training_date": datetime.now().isoformat(),
        "model_path": str(path),
    }
    with path.open("wb") as handle:
        pickle.dump(bundle, handle)
    latest_path = model_dir / f"{algorithm}_latest.pkl"
    with latest_path.open("wb") as handle:
        pickle.dump(bundle, handle)

    metadata_path = model_dir / "metadata.json"
    metadata = {}
    if metadata_path.exists():
        with metadata_path.open("r", encoding="utf-8") as handle:
            metadata = json.load(handle)
    metadata[algorithm] = {
        key: value for key, value in bundle.items() if key not in {"model", "scalers"}
    }
    metadata[algorithm]["latest_path"] = str(latest_path)
    metadata[algorithm]["stock_count"] = len(mapping)
    with metadata_path.open("w", encoding="utf-8") as handle:
        json.dump(metadata, handle, indent=2)
    return path


def model_directory(scope: str = "universal", symbol: Optional[str] = None) -> Path:
    if scope not in MODEL_SCOPES:
        raise ValueError(f"Unsupported model scope: {scope}")
    if scope == "universal":
        return MODEL_DIR
    normalized_symbol = (symbol or "").strip().upper()
    if not normalized_symbol:
        raise ValueError("Individual model scope requires a stock symbol.")
    return INDIVIDUAL_MODEL_DIR / normalized_symbol


def load_model(
    algorithm: str,
    scope: str = "universal",
    symbol: Optional[str] = None,
) -> Dict:
    path = model_directory(scope, symbol) / f"{algorithm}_latest.pkl"
    if not path.exists():
        label = "universal" if scope == "universal" else f"individual {symbol}"
        raise FileNotFoundError(
            f"No trained {label} {algorithm} model exists. Train it first."
        )
    with path.open("rb") as handle:
        return pickle.load(handle)


def latest_training_date(series: Dict[str, StockSeries], symbol: str):
    return pd.Timestamp(series[symbol].dates[-1]).to_pydatetime()
