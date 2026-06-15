"""Scratch-built universal model training and forecasting."""

from __future__ import annotations

import time
from dataclasses import dataclass
from typing import Dict, List, Tuple

import numpy as np

from lstm import ScratchLSTMRegressor
from random_forest import ScratchRandomForestRegressor
from universal_core import (
    ALGORITHMS,
    StockSeries,
    UniversalConfig,
    aggregate_price_metrics,
    build_lstm_dataset,
    build_sequence_dataset,
    load_model,
    load_stock_series,
    one_hot,
    regression_metrics,
    save_model,
    symbol_mapping,
)
from xgboost import ScratchXGBoostRegressor


@dataclass
class UniversalMovingAverage:
    window: int

    def predict_next(self, history: np.ndarray) -> float:
        values = np.asarray(history, dtype=float).reshape(-1)
        if len(values) == 0:
            raise ValueError("Moving Average requires price history.")
        return float(np.mean(values[-min(self.window, len(values)):]))

    def forecast(self, history: np.ndarray, horizon: int) -> np.ndarray:
        working = list(np.asarray(history, dtype=float).reshape(-1))
        predictions = []
        for _ in range(horizon):
            prediction = self.predict_next(np.asarray(working))
            predictions.append(prediction)
            working.append(prediction)
        return np.asarray(predictions, dtype=float)


def _scalers(series: Dict[str, StockSeries]) -> Dict[str, Tuple[float, float]]:
    return {
        symbol: (item.minimum, item.maximum)
        for symbol, item in series.items()
    }


def _training_config(scope: str = "universal") -> UniversalConfig:
    if scope == "individual":
        return UniversalConfig(
            max_train_sequences_per_stock=0,
            max_validation_sequences_per_stock=0,
            max_test_sequences_per_stock=0,
        )
    return UniversalConfig(
        max_train_sequences_per_stock=30,
        max_validation_sequences_per_stock=20,
        max_test_sequences_per_stock=20,
    )


def _validate_dataset(X_train, y_train, X_val, y_val) -> None:
    if len(X_train) == 0:
        raise ValueError("No universal training samples were created.")
    if len(X_val) == 0:
        raise ValueError("No universal validation samples were created.")
    if len(X_train) != len(y_train) or len(X_val) != len(y_val):
        raise ValueError("Universal feature and target arrays are misaligned.")


def train_lstm(scope: str = "universal", symbol: str | None = None) -> Dict:
    config = _training_config(scope)
    if scope == "universal":
        config.max_train_sequences_per_stock = 10
        config.max_validation_sequences_per_stock = 10
        config.max_test_sequences_per_stock = 10
    series = load_stock_series(config, [symbol] if symbol else None)
    mapping = symbol_mapping(series)
    X_train, y_train, train_contexts = build_lstm_dataset(
        series, mapping, config, "train"
    )
    X_val, y_val, validation_contexts = build_lstm_dataset(
        series, mapping, config, "validation"
    )
    X_test, _, test_contexts = build_lstm_dataset(
        series, mapping, config, "test"
    )
    _validate_dataset(X_train, y_train, X_val, y_val)

    model = ScratchLSTMRegressor(
        input_size=1,
        hidden_size=16,
        learning_rate=0.003,
        epochs=4,
        dropout_rate=0.0,
        early_stopping_patience=3,
        context_size=len(mapping),
    )
    started = time.time()
    model.fit(
        X_train,
        y_train.reshape(-1, 1),
        x_val=X_val,
        y_val=y_val.reshape(-1, 1),
        contexts=train_contexts,
        val_contexts=validation_contexts,
    )
    test_predictions = model.predict(
        X_test,
        contexts=test_contexts,
    ).reshape(-1)
    metrics = aggregate_price_metrics(
        series, mapping, config, test_predictions, "test"
    )
    metrics["training_time_seconds"] = round(time.time() - started, 3)
    path = save_model(
        "lstm", model, config, mapping, _scalers(series), metrics,
        extra={"benchmark": False, "implementation": "scratch"},
        scope=scope,
        symbol=symbol,
    )
    return {
        "algorithm": "lstm", "path": str(path), "metrics": metrics,
        "model_scope": scope, "stock_symbol": symbol,
    }


def train_random_forest(
    scope: str = "universal", symbol: str | None = None
) -> Dict:
    config = _training_config(scope)
    series = load_stock_series(config, [symbol] if symbol else None)
    mapping = symbol_mapping(series)
    X_train, y_train = build_sequence_dataset(series, mapping, config, "train")
    X_val, y_val = build_sequence_dataset(series, mapping, config, "validation")
    X_test, _ = build_sequence_dataset(series, mapping, config, "test")
    _validate_dataset(X_train, y_train, X_val, y_val)

    model = ScratchRandomForestRegressor(
        n_estimators=40,
        max_depth=10,
        min_samples_split=6,
        min_samples_leaf=3,
        max_features="sqrt",
        bootstrap=True,
        random_state=config.random_seed,
        min_impurity_decrease=1e-7,
    )
    started = time.time()
    model.fit(X_train, y_train)
    test_predictions = model.predict(X_test)
    metrics = aggregate_price_metrics(
        series, mapping, config, test_predictions, "test"
    )
    metrics["training_time_seconds"] = round(time.time() - started, 3)
    metrics["oob_score"] = (
        round(float(model.oob_score_), 6) if model.oob_score_ is not None else None
    )
    path = save_model(
        "random_forest", model, config, mapping, _scalers(series), metrics,
        extra={"benchmark": False, "implementation": "scratch"},
        scope=scope,
        symbol=symbol,
    )
    return {
        "algorithm": "random_forest", "path": str(path), "metrics": metrics,
        "model_scope": scope, "stock_symbol": symbol,
    }


def train_xgboost(scope: str = "universal", symbol: str | None = None) -> Dict:
    config = _training_config(scope)
    series = load_stock_series(config, [symbol] if symbol else None)
    mapping = symbol_mapping(series)
    X_train, y_train = build_sequence_dataset(series, mapping, config, "train")
    X_val, y_val = build_sequence_dataset(series, mapping, config, "validation")
    X_test, _ = build_sequence_dataset(series, mapping, config, "test")
    _validate_dataset(X_train, y_train, X_val, y_val)

    model = ScratchXGBoostRegressor(
        n_estimators=40,
        learning_rate=0.05,
        max_depth=4,
        min_samples_split=6,
        lambda_reg=1.0,
        gamma=0.0,
    )
    started = time.time()
    model.fit(X_train, y_train)
    test_predictions = model.predict(X_test)
    metrics = aggregate_price_metrics(
        series, mapping, config, test_predictions, "test"
    )
    metrics["training_time_seconds"] = round(time.time() - started, 3)
    path = save_model(
        "xgboost", model, config, mapping, _scalers(series), metrics,
        extra={"benchmark": False, "implementation": "scratch"},
        scope=scope,
        symbol=symbol,
    )
    return {
        "algorithm": "xgboost", "path": str(path), "metrics": metrics,
        "model_scope": scope, "stock_symbol": symbol,
    }


def _moving_average_validation(
    series: Dict[str, StockSeries], window: int
) -> Tuple[np.ndarray, np.ndarray, np.ndarray]:
    actual: List[float] = []
    predicted: List[float] = []
    previous: List[float] = []
    for item in series.values():
        for index in range(item.train_end, item.validation_end):
            start = max(0, index - window)
            if index <= start:
                continue
            actual.append(float(item.prices[index]))
            predicted.append(float(np.mean(item.prices[start:index])))
            previous.append(float(item.prices[index - 1]))
    return np.asarray(actual), np.asarray(predicted), np.asarray(previous)


def _moving_average_test(
    series: Dict[str, StockSeries], window: int
) -> Tuple[np.ndarray, np.ndarray, np.ndarray]:
    actual: List[float] = []
    predicted: List[float] = []
    previous: List[float] = []
    for item in series.values():
        for index in range(item.validation_end, len(item.prices)):
            start = max(0, index - window)
            if index <= start:
                continue
            actual.append(float(item.prices[index]))
            predicted.append(float(np.mean(item.prices[start:index])))
            previous.append(float(item.prices[index - 1]))
    return np.asarray(actual), np.asarray(predicted), np.asarray(previous)


def train_moving_average(
    scope: str = "universal", symbol: str | None = None
) -> Dict:
    config = _training_config(scope)
    series = load_stock_series(config, [symbol] if symbol else None)
    mapping = symbol_mapping(series)
    if not series:
        raise ValueError("No stocks are eligible for Moving Average training.")

    candidates = (5, 10, 15, 20, 30, 50)
    validation_scores = {}
    for window in candidates:
        actual, predicted, previous = _moving_average_validation(series, window)
        validation_scores[window] = regression_metrics(
            actual, predicted, previous
        )["rmse"]
    best_window = min(validation_scores, key=validation_scores.get)
    model = UniversalMovingAverage(best_window)
    actual, predicted, previous = _moving_average_test(series, best_window)
    metrics = regression_metrics(actual, predicted, previous)
    metrics["selected_window"] = best_window
    path = save_model(
        "moving_average",
        model,
        config,
        mapping,
        _scalers(series),
        metrics,
        extra={
            "benchmark": True,
            "implementation": "scratch",
            "validation_rmse_by_window": validation_scores,
        },
        scope=scope,
        symbol=symbol,
    )
    return {
        "algorithm": "moving_average",
        "path": str(path),
        "metrics": metrics,
        "benchmark": True,
        "model_scope": scope,
        "stock_symbol": symbol,
    }


TRAINERS = {
    "lstm": train_lstm,
    "xgboost": train_xgboost,
    "random_forest": train_random_forest,
    "moving_average": train_moving_average,
}


def train_algorithms(
    algorithm: str,
    scope: str = "universal",
    symbol: str | None = None,
) -> List[Dict]:
    if scope == "individual" and not symbol:
        raise ValueError("Individual training requires a stock symbol.")
    symbol = symbol.strip().upper() if symbol else None
    selected = ALGORITHMS if algorithm == "all" else (algorithm,)
    unknown = [name for name in selected if name not in TRAINERS]
    if unknown:
        raise ValueError(f"Unsupported algorithm: {unknown[0]}")
    return [TRAINERS[name](scope, symbol) for name in selected]


def _config_from_bundle(bundle: Dict) -> UniversalConfig:
    return UniversalConfig(**bundle["config"])


def _normalized_feature(
    bundle: Dict, symbol: str, history: np.ndarray, lstm: bool
) -> np.ndarray:
    mapping = bundle["symbol_to_index"]
    if symbol not in mapping:
        raise ValueError(
            f"{symbol} was not present during universal model training."
        )
    config = _config_from_bundle(bundle)
    minimum, maximum = bundle["scalers"][symbol]
    scale = maximum - minimum if maximum > minimum else 1.0
    normalized = (np.asarray(history, dtype=float) - minimum) / scale
    sequence = normalized[-config.sequence_length:]
    if len(sequence) < config.sequence_length:
        sequence = np.pad(
            sequence,
            (config.sequence_length - len(sequence), 0),
            mode="edge",
        )
    identity = one_hot(mapping[symbol], len(mapping))
    if lstm:
        identity_steps = np.repeat(
            identity.reshape(1, -1), config.sequence_length, axis=0
        )
        return np.column_stack([sequence, identity_steps])
    return np.concatenate([sequence, identity])


def forecast_algorithm(
    algorithm: str,
    symbol: str,
    history: np.ndarray,
    horizon: int = 30,
    scope: str = "universal",
) -> Tuple[np.ndarray, Dict]:
    bundle = load_model(algorithm, scope, symbol)
    model = bundle["model"]
    working = list(np.asarray(history, dtype=float).reshape(-1))
    predictions: List[float] = []

    if algorithm == "moving_average":
        return model.forecast(np.asarray(working), horizon), bundle

    minimum, maximum = bundle["scalers"][symbol]
    scale = maximum - minimum if maximum > minimum else 1.0
    for _ in range(horizon):
        feature = _normalized_feature(
            bundle, symbol, np.asarray(working), lstm=algorithm == "lstm"
        )
        if algorithm == "lstm":
            normalized_prediction = model.predict_one(
                feature[:, :1],
                context=feature[0, 1:],
            )
        else:
            normalized_prediction = float(model.predict(feature.reshape(1, -1))[0])
        normalized_prediction = float(np.clip(normalized_prediction, -0.5, 1.5))
        price = normalized_prediction * scale + minimum
        price = max(0.01, float(price))
        predictions.append(price)
        working.append(price)
    return np.asarray(predictions), bundle


def _predict_loaded_symbol(
    symbol: str,
    item: StockSeries,
    algorithms: str = "all",
    scope: str = "universal",
    allow_missing: bool = False,
) -> Dict:
    selected = ALGORITHMS if algorithms == "all" else (algorithms,)
    config = _training_config()
    predictions = []
    models = []
    warnings = []
    for algorithm in selected:
        try:
            forecast, bundle = forecast_algorithm(
                algorithm, symbol, item.prices, config.forecast_horizon, scope
            )
        except FileNotFoundError as exc:
            if not allow_missing:
                raise
            warnings.append(str(exc))
            continue
        metrics = dict(bundle["metrics"])
        benchmark = bool(bundle.get("extra", {}).get("benchmark", False))
        models.append(
            {
                "model_type": algorithm,
                "metrics": metrics,
                "path": bundle.get("model_path"),
                "training_date": bundle.get("training_date"),
                "benchmark": benchmark,
                "model_scope": scope,
            }
        )
        for label, days in (("1 Day", 1), ("1 Week", 7), ("1 Month", 30)):
            target_date = (
                np.datetime64(item.dates[-1], "D") + np.timedelta64(days, "D")
            )
            predictions.append(
                {
                    "model_type": algorithm,
                    "model_scope": scope,
                    "target_date": str(target_date),
                    "predicted_price": round(float(forecast[days - 1]), 2),
                    "additional_metrics": {
                        **metrics,
                        "horizon": label,
                        "benchmark": benchmark,
                        "universal": scope == "universal",
                        "model_scope": scope,
                    },
                }
            )
    if not models:
        raise FileNotFoundError(
            "; ".join(warnings) or f"No {scope} models are available for {symbol}."
        )
    return {
        "symbol": symbol,
        "current_price": round(float(item.prices[-1]), 2),
        "models": models,
        "predictions": predictions,
        "warnings": warnings,
    }


def _merge_scope_results(results: List[Dict], symbol: str) -> Dict:
    return {
        "symbol": symbol,
        "current_price": results[0]["current_price"],
        "models": [model for result in results for model in result["models"]],
        "predictions": [
            prediction
            for result in results
            for prediction in result["predictions"]
        ],
        "warnings": [
            warning
            for result in results
            for warning in result.get("warnings", [])
        ],
    }


def predict_symbol(
    symbol: str, algorithms: str = "all", scope: str = "universal"
) -> Dict:
    symbol = symbol.strip().upper()
    config = _training_config()
    series = load_stock_series(config, [symbol])
    if symbol not in series:
        raise ValueError(f"No eligible stock data found for {symbol}.")
    scopes = ("universal", "individual") if scope == "both" else (scope,)
    results = []
    warnings = []
    for model_scope in scopes:
        try:
            results.append(
                _predict_loaded_symbol(
                    symbol,
                    series[symbol],
                    algorithms,
                    model_scope,
                    allow_missing=scope == "both",
                )
            )
        except FileNotFoundError as exc:
            if scope != "both":
                raise
            warnings.append(str(exc))
    if not results:
        raise FileNotFoundError("; ".join(warnings))
    merged = _merge_scope_results(results, symbol)
    merged["warnings"].extend(warnings)
    return merged


def predict_history(
    symbol: str,
    dates: List[str],
    prices: List[float],
    algorithms: str = "all",
    scope: str = "universal",
) -> Dict:
    symbol = symbol.strip().upper()
    values = np.asarray(prices, dtype=float)
    date_values = np.asarray(dates)
    if len(values) != len(date_values):
        raise ValueError("Price and date history lengths do not match.")
    if len(values) and float(np.max(values)) >= 50.0:
        valid = values >= 10.0
        values = values[valid]
        date_values = date_values[valid]
    if len(values) < UniversalConfig().min_stock_length:
        raise ValueError(
            f"{symbol} requires at least {UniversalConfig().min_stock_length} "
            f"clean price records; found {len(values)}."
        )
    item = StockSeries(
        symbol=symbol,
        dates=date_values,
        prices=values,
        train_end=max(UniversalConfig().sequence_length + 1, int(len(values) * 0.8)),
        validation_end=max(
            UniversalConfig().sequence_length + 2,
            int(len(values) * 0.9),
        ),
        minimum=float(np.min(values)),
        maximum=float(np.max(values)),
    )
    scopes = ("universal", "individual") if scope == "both" else (scope,)
    results = []
    warnings = []
    for model_scope in scopes:
        try:
            results.append(
                _predict_loaded_symbol(
                    symbol,
                    item,
                    algorithms,
                    model_scope,
                    allow_missing=scope == "both",
                )
            )
        except FileNotFoundError as exc:
            if scope != "both":
                raise
            warnings.append(str(exc))
    if not results:
        raise FileNotFoundError("; ".join(warnings))
    merged = _merge_scope_results(results, symbol)
    merged["warnings"].extend(warnings)
    return merged


def predict_all_symbols(
    algorithms: str = "all", scope: str = "universal"
) -> List[Dict]:
    config = _training_config()
    series = load_stock_series(config)
    results = []
    for symbol in sorted(series):
        scopes = ("universal", "individual") if scope == "both" else (scope,)
        scope_results = []
        warnings = []
        for model_scope in scopes:
            try:
                scope_results.append(
                    _predict_loaded_symbol(
                        symbol,
                        series[symbol],
                        algorithms,
                        model_scope,
                        allow_missing=scope == "both",
                    )
                )
            except FileNotFoundError as exc:
                if scope != "both":
                    raise
                warnings.append(str(exc))
        if scope_results:
            merged = _merge_scope_results(scope_results, symbol)
            merged["warnings"].extend(warnings)
            results.append(merged)
    return results
