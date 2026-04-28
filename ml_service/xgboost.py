import numpy as np


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
    def __init__(self, max_depth=3, min_samples_split=5, lambda_reg=1.0, gamma=0.0):
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

    def _gain(self, left_gradients, right_gradients):
        left_count = len(left_gradients)
        right_count = len(right_gradients)
        if left_count == 0 or right_count == 0:
            return -np.inf

        total_gradients = np.concatenate((left_gradients, right_gradients))
        left_score = (np.sum(left_gradients) ** 2) / (left_count + self.lambda_reg)
        right_score = (np.sum(right_gradients) ** 2) / (right_count + self.lambda_reg)
        parent_score = (np.sum(total_gradients) ** 2) / (len(total_gradients) + self.lambda_reg)
        return 0.5 * (left_score + right_score - parent_score) - self.gamma

    def _best_split(self, x_values, gradients):
        best_feature = None
        best_threshold = None
        best_gain = -np.inf
        num_features = x_values.shape[1]

        for feature_index in range(num_features):
            feature_values = x_values[:, feature_index]
            thresholds = np.unique(feature_values)

            for threshold in thresholds:
                left_mask = feature_values <= threshold
                right_mask = ~left_mask
                gain = self._gain(gradients[left_mask], gradients[right_mask])

                if gain > best_gain:
                    best_gain = gain
                    best_feature = feature_index
                    best_threshold = threshold

        return best_feature, best_threshold, best_gain

    def _build_tree(self, x_values, gradients, depth):
        if (
            depth >= self.max_depth
            or len(x_values) < self.min_samples_split
            or np.allclose(gradients, gradients[0])
        ):
            return TreeNode(value=self._leaf_value(gradients))

        feature_index, threshold, gain = self._best_split(x_values, gradients)
        if feature_index is None or gain <= 0:
            return TreeNode(value=self._leaf_value(gradients))

        left_mask = x_values[:, feature_index] <= threshold
        right_mask = ~left_mask

        left_node = self._build_tree(x_values[left_mask], gradients[left_mask], depth + 1)
        right_node = self._build_tree(x_values[right_mask], gradients[right_mask], depth + 1)

        return TreeNode(
            feature_index=feature_index,
            threshold=threshold,
            left=left_node,
            right=right_node,
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
    def __init__(
        self,
        n_estimators=40,
        learning_rate=0.1,
        max_depth=3,
        min_samples_split=5,
        lambda_reg=1.0,
        gamma=0.0,
    ):
        self.n_estimators = n_estimators
        self.learning_rate = learning_rate
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.lambda_reg = lambda_reg
        self.gamma = gamma
        self.base_score = 0.0
        self.trees = []
        self.training_loss_ = None

    def fit(self, x_values, y_values):
        x_values = np.asarray(x_values, dtype=float)
        y_values = np.asarray(y_values, dtype=float).reshape(-1)

        if len(x_values) == 0:
            raise ValueError("Training data is empty.")

        self.base_score = float(np.mean(y_values))
        predictions = np.full(len(y_values), self.base_score, dtype=float)
        self.trees = []

        for _ in range(self.n_estimators):
            gradients = predictions - y_values
            tree = RegressionTree(
                max_depth=self.max_depth,
                min_samples_split=self.min_samples_split,
                lambda_reg=self.lambda_reg,
                gamma=self.gamma,
            )
            tree.fit(x_values, gradients)

            update = tree.predict(x_values)
            predictions += self.learning_rate * update
            self.trees.append(tree)

        self.training_loss_ = float(np.mean((predictions - y_values) ** 2))
        return self

    def predict(self, x_values):
        x_values = np.asarray(x_values, dtype=float)
        predictions = np.full(len(x_values), self.base_score, dtype=float)

        for tree in self.trees:
            predictions += self.learning_rate * tree.predict(x_values)

        return predictions


def train_xgboost_and_forecast(day_index, close_prices, forecast_horizon=30):
    day_index = np.asarray(day_index, dtype=float).reshape(-1, 1)
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)

    if len(day_index) != len(close_prices):
        raise ValueError("Day index and close prices must have the same length.")

    if len(close_prices) < 10:
        raise ValueError("At least 10 rows are required for scratch XGBoost training.")

    model = ScratchXGBoostRegressor(
        n_estimators=50,
        learning_rate=0.08,
        max_depth=3,
        min_samples_split=4,
        lambda_reg=1.0,
        gamma=0.0,
    )
    model.fit(day_index, close_prices)

    fitted = model.predict(day_index)
    mse = float(np.mean((fitted - close_prices) ** 2))
    mae = float(np.mean(np.abs(fitted - close_prices)))
    rmse = float(np.sqrt(mse))
    mean_price = float(np.mean(close_prices)) if len(close_prices) else 1.0
    relative_error = rmse / mean_price if mean_price else 0.0
    confidence_score = max(1.0, min(99.0, 100.0 - (relative_error * 100.0)))

    future_index = np.arange(day_index[-1, 0] + 1, day_index[-1, 0] + forecast_horizon + 1, dtype=float).reshape(-1, 1)
    forecast = model.predict(future_index)

    return {
        "forecast": np.asarray(forecast, dtype=float).reshape(-1),
        "metrics": {
            "mse": round(mse, 4),
            "mae": round(mae, 4),
            "rmse": round(rmse, 4),
            "confidence_score": f"{confidence_score:.1f}%",
            "training_loss": round(float(model.training_loss_), 6),
            "trees": model.n_estimators,
        },
    }
