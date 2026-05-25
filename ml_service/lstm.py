import numpy as np


def sigmoid(x):
    return 1.0 / (1.0 + np.exp(-np.clip(x, -50, 50)))


class MinMaxScaler1D:
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
        values = np.asarray(values, dtype=float)
        return (values - self.data_min) / self.scale

    def inverse_transform(self, values):
        values = np.asarray(values, dtype=float)
        return (values * self.scale) + self.data_min

    def fit_transform(self, values):
        self.fit(values)
        return self.transform(values)


def create_sequences(values, sequence_length):
    values = np.asarray(values, dtype=float).reshape(-1)
    x_train, y_train = [], []

    for index in range(sequence_length, len(values)):
        x_train.append(values[index - sequence_length:index].reshape(sequence_length, 1))
        y_train.append(values[index])

    if not x_train:
        return np.empty((0, sequence_length, 1)), np.empty((0, 1))

    return np.array(x_train, dtype=float), np.array(y_train, dtype=float).reshape(-1, 1)


class ScratchLSTMRegressor:
    def __init__(self, input_size=1, hidden_size=16, learning_rate=0.01, epochs=150, seed=42):
        self.input_size = input_size
        self.hidden_size = hidden_size
        self.learning_rate = learning_rate
        self.epochs = epochs
        self.rng = np.random.default_rng(seed)
        self._initialize_weights()

    def _initialize_weights(self):
        concat_size = self.input_size + self.hidden_size
        scale = 1.0 / np.sqrt(concat_size)

        self.Wf = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wi = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wc = self.rng.normal(0, scale, (self.hidden_size, concat_size))
        self.Wo = self.rng.normal(0, scale, (self.hidden_size, concat_size))

        self.bf = np.zeros((self.hidden_size, 1))
        self.bi = np.zeros((self.hidden_size, 1))
        self.bc = np.zeros((self.hidden_size, 1))
        self.bo = np.zeros((self.hidden_size, 1))

        self.Wy = self.rng.normal(0, scale, (1, self.hidden_size))
        self.by = np.zeros((1, 1))

    def _forward(self, sequence):
        """
        Forward pass through the LSTM.
        Required Gates:
        1. Forget Gate (f_t): Decides what information to discard from the cell state.
        2. Input Gate (i_t): Decides which values to update in the cell state.
        3. Cell Candidate (c_bar_t): Creates a vector of new candidate values to be added to the state.
        4. Output Gate (o_t): Decides what part of the cell state to output.
        """
        h_prev = np.zeros((self.hidden_size, 1))
        c_prev = np.zeros((self.hidden_size, 1))
        cache = []

        for x_t in sequence:
            x_t = np.asarray(x_t, dtype=float).reshape(self.input_size, 1)
            # Concatenate previous hidden state and current input
            z = np.vstack((h_prev, x_t))

            # 1. Forget Gate: f_t = sigmoid(Wf * [h_prev, x_t] + bf)
            f_t = sigmoid(self.Wf @ z + self.bf)
            
            # 2. Input Gate: i_t = sigmoid(Wi * [h_prev, x_t] + bi)
            i_t = sigmoid(self.Wi @ z + self.bi)
            
            # 3. Cell Candidate: c_bar_t = tanh(Wc * [h_prev, x_t] + bc)
            c_bar_t = np.tanh(self.Wc @ z + self.bc)
            
            # Update Cell State: c_t = f_t * c_prev + i_t * c_bar_t
            c_t = (f_t * c_prev) + (i_t * c_bar_t)
            
            # 4. Output Gate: o_t = sigmoid(Wo * [h_prev, x_t] + bo)
            o_t = sigmoid(self.Wo @ z + self.bo)
            
            # Final Hidden State: h_t = o_t * tanh(c_t)
            h_t = o_t * np.tanh(c_t)

            cache.append({
                "z": z,
                "f": f_t,
                "i": i_t,
                "c_bar": c_bar_t,
                "c": c_t,
                "o": o_t,
                "h_prev": h_prev,
                "c_prev": c_prev,
                "h": h_t,
            })

            h_prev, c_prev = h_t, c_t

        y_pred = self.Wy @ h_prev + self.by
        return y_pred, cache

    def _backward(self, y_pred, y_true, cache):
        grads = {
            "Wf": np.zeros_like(self.Wf),
            "Wi": np.zeros_like(self.Wi),
            "Wc": np.zeros_like(self.Wc),
            "Wo": np.zeros_like(self.Wo),
            "bf": np.zeros_like(self.bf),
            "bi": np.zeros_like(self.bi),
            "bc": np.zeros_like(self.bc),
            "bo": np.zeros_like(self.bo),
            "Wy": np.zeros_like(self.Wy),
            "by": np.zeros_like(self.by),
        }

        dy = y_pred - y_true
        grads["Wy"] += dy @ cache[-1]["h"].T
        grads["by"] += dy

        dh_next = self.Wy.T @ dy
        dc_next = np.zeros((self.hidden_size, 1))

        for step in reversed(cache):
            z = step["z"]
            f_t = step["f"]
            i_t = step["i"]
            c_bar_t = step["c_bar"]
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

            di = dc * c_bar_t
            di_raw = di * i_t * (1.0 - i_t)

            dc_bar = dc * i_t
            dc_bar_raw = dc_bar * (1.0 - c_bar_t ** 2)

            grads["Wf"] += df_raw @ z.T
            grads["Wi"] += di_raw @ z.T
            grads["Wc"] += dc_bar_raw @ z.T
            grads["Wo"] += do_raw @ z.T
            grads["bf"] += df_raw
            grads["bi"] += di_raw
            grads["bc"] += dc_bar_raw
            grads["bo"] += do_raw

            dz = (
                self.Wf.T @ df_raw
                + self.Wi.T @ di_raw
                + self.Wc.T @ dc_bar_raw
                + self.Wo.T @ do_raw
            )

            dh_next = dz[:self.hidden_size, :]
            dc_next = dc * f_t

        return grads, float(0.5 * np.square(dy).item())

    def _apply_gradients(self, grads):
        for name, gradient in grads.items():
            np.clip(gradient, -1.0, 1.0, out=gradient)
            setattr(self, name, getattr(self, name) - (self.learning_rate * gradient))

    def fit(self, x_train, y_train):
        x_train = np.asarray(x_train, dtype=float)
        y_train = np.asarray(y_train, dtype=float).reshape(-1, 1)

        if len(x_train) == 0:
            raise ValueError("Not enough sequence data to train the LSTM model.")

        for _ in range(self.epochs):
            epoch_loss = 0.0
            for sequence, target in zip(x_train, y_train):
                y_pred, cache = self._forward(sequence)
                grads, sample_loss = self._backward(y_pred, target.reshape(1, 1), cache)
                self._apply_gradients(grads)
                epoch_loss += sample_loss

            self.training_loss_ = epoch_loss / len(x_train)

        return self

    def predict_one(self, sequence):
        prediction, _ = self._forward(sequence)
        return float(prediction.item())

    def predict(self, x_values):
        x_values = np.asarray(x_values, dtype=float)
        outputs = [self.predict_one(sequence) for sequence in x_values]
        return np.array(outputs, dtype=float).reshape(-1, 1)

    def forecast(self, seed_sequence, steps):
        window = np.asarray(seed_sequence, dtype=float).reshape(-1, 1)
        predictions = []

        for _ in range(steps):
            next_value = self.predict_one(window)
            predictions.append(next_value)
            window = np.vstack((window[1:], [[next_value]]))

        return np.array(predictions, dtype=float)


def train_and_forecast(close_prices, sequence_length=20, hidden_size=16, epochs=150, learning_rate=0.01):
    close_prices = np.asarray(close_prices, dtype=float).reshape(-1)
    if len(close_prices) <= sequence_length:
        raise ValueError(f"At least {sequence_length + 1} closing prices are required for LSTM training.")

    scaler = MinMaxScaler1D()
    scaled_prices = scaler.fit_transform(close_prices)
    x_train, y_train = create_sequences(scaled_prices, sequence_length)

    model = ScratchLSTMRegressor(
        input_size=1,
        hidden_size=hidden_size,
        learning_rate=learning_rate,
        epochs=epochs,
    )
    model.fit(x_train, y_train)

    fitted_scaled = model.predict(x_train).reshape(-1)
    fitted = scaler.inverse_transform(fitted_scaled)
    actual = close_prices[sequence_length:]

    mse = float(np.mean((fitted - actual) ** 2))
    mae = float(np.mean(np.abs(fitted - actual)))
    rmse = float(np.sqrt(mse))
    mean_price = float(np.mean(actual)) if len(actual) else 1.0
    relative_error = rmse / mean_price if mean_price else 0.0
    confidence_score = max(1.0, min(99.0, 100.0 - (relative_error * 100.0)))

    forecast_seed = scaled_prices[-sequence_length:]
    scaled_forecast = model.forecast(forecast_seed, steps=30)
    forecast = scaler.inverse_transform(scaled_forecast)

    return {
        "sequence_length": sequence_length,
        "forecast": np.asarray(forecast, dtype=float).reshape(-1),
        "metrics": {
            "mse": round(mse, 4),
            "mae": round(mae, 4),
            "rmse": round(rmse, 4),
            "confidence_score": f"{confidence_score:.1f}%",
            "training_loss": round(float(model.training_loss_), 6),
        },
    }
