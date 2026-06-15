"""Command-line interface for universal scratch models."""

from __future__ import annotations

import argparse
import json
import sys

from universal_core import ALGORITHMS
from universal_models import (
    predict_all_symbols,
    predict_history,
    predict_symbol,
    train_algorithms,
)


def build_parser(fixed_algorithm: str | None = None) -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Train and use scratch-built universal stock models."
    )
    action = parser.add_mutually_exclusive_group(required=True)
    action.add_argument("--train", action="store_true", help="Train model(s)")
    action.add_argument("--predict", metavar="SYMBOL", help="Predict one stock")
    action.add_argument(
        "--predict-stdin",
        metavar="SYMBOL",
        help="Predict one stock using JSON history read from standard input",
    )
    action.add_argument(
        "--predict-all",
        action="store_true",
        help="Generate predictions for every eligible stock",
    )
    if fixed_algorithm is None:
        parser.add_argument(
            "--algorithm",
            choices=("all",) + ALGORITHMS,
            default="all",
            help="Algorithm to train or predict with",
        )
    parser.add_argument(
        "--scope",
        choices=("universal", "individual", "both"),
        default="universal",
        help="Model scope. Training supports universal or individual.",
    )
    parser.add_argument(
        "--symbol",
        help="Stock symbol required for individual training.",
    )
    return parser


def run(fixed_algorithm: str | None = None) -> int:
    parser = build_parser(fixed_algorithm)
    args = parser.parse_args()
    algorithm = fixed_algorithm or args.algorithm
    try:
        if args.train:
            if args.scope == "both":
                raise ValueError("Training scope must be universal or individual.")
            result = {
                "status": "ok",
                "action": "train",
                "algorithm": algorithm,
                "scope": args.scope,
                "symbol": args.symbol,
                "models": train_algorithms(algorithm, args.scope, args.symbol),
            }
        elif args.predict:
            result = predict_symbol(args.predict, algorithm, args.scope)
            result.update({"status": "ok", "action": "predict"})
        elif args.predict_stdin:
            payload = json.load(sys.stdin)
            result = predict_history(
                args.predict_stdin,
                payload["dates"],
                payload["prices"],
                algorithm,
                args.scope,
            )
            result.update({"status": "ok", "action": "predict"})
        else:
            result = {
                "status": "ok",
                "action": "predict_all",
                "algorithm": algorithm,
                "scope": args.scope,
                "results": predict_all_symbols(algorithm, args.scope),
            }
        print(json.dumps(result))
        return 0
    except Exception as exc:
        print(json.dumps({"status": "error", "error": str(exc)}))
        return 1


def main(fixed_algorithm: str | None = None) -> None:
    sys.exit(run(fixed_algorithm))


if __name__ == "__main__":
    main()
