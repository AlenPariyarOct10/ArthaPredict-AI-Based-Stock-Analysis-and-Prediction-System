import json
import os
import sys
from pathlib import Path

import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import pandas as pd
from sqlalchemy import create_engine

def fetch_stock_data(symbol):
    """Fetch stock prices from MySQL database"""
    # Load environment variables from .env if present
    env_path = Path(__file__).parent.parent / '.env'
    if env_path.exists():
        with open(env_path, 'r') as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith('#') and '=' in line:
                    key, val = line.split('=', 1)
                    if key.strip() not in os.environ:
                        os.environ[key.strip()] = val.strip().strip('"').strip("'")

    db_host = os.environ.get('DB_HOST', '127.0.0.1')
    db_port = os.environ.get('DB_PORT', '3306')
    db_database = os.environ.get('DB_DATABASE', 'arthapredict')
    db_username = os.environ.get('DB_USERNAME', 'root')
    db_password = os.environ.get('DB_PASSWORD', '')

    engine_url = f"mysql+pymysql://{db_username}:{db_password}@{db_host}:{db_port}/{db_database}"
    engine = create_engine(engine_url)
    query = """
        SELECT s.symbol, p.date, p.close
        FROM stock_prices p
        JOIN stocks s ON p.stock_id = s.id
        WHERE s.symbol = %s
        ORDER BY p.date
    """
    df = pd.read_sql(query, engine, params=(symbol,))
    df['date'] = pd.to_datetime(df['date'])
    df['close'] = pd.to_numeric(df['close'], errors='coerce')
    df = df.dropna(subset=['close'])
    return df

def calculate_moving_averages(df):
    """Calculate SMA and EMA for trend visualization"""
    df = df.sort_values('date')
    df['SMA_20'] = df['close'].rolling(window=20, min_periods=1).mean()
    df['SMA_50'] = df['close'].rolling(window=50, min_periods=1).mean()
    df['EMA_20'] = df['close'].ewm(span=20, adjust=False).mean()
    return df

def plot_trend(df, symbol, output_path=None):
    """Plot closing price with Moving Averages and save as PNG"""
    plt.figure(figsize=(12,6))

    plt.plot(df['date'], df['close'], label='Close Price', color='blue')
    plt.plot(df['date'], df['SMA_20'], label='SMA 20', color='orange')
    plt.plot(df['date'], df['SMA_50'], label='SMA 50', color='green')
    plt.plot(df['date'], df['EMA_20'], label='EMA 20', color='red')

    plt.title(f'{symbol} Price Trend with Moving Averages')
    plt.xlabel('Date')
    plt.ylabel('Price')
    plt.legend()
    plt.xticks(rotation=45)
    plt.grid(True)

    png_filename = output_path or f"{symbol}_trend.png"
    plt.savefig(png_filename, bbox_inches='tight')
    plt.close()
    return png_filename

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python moving_average_trend.py <SYMBOL> [OUTPUT_PATH]"}))
        sys.exit(1)

    symbol = sys.argv[1].upper()
    output_path = sys.argv[2] if len(sys.argv) > 2 else None
    df = fetch_stock_data(symbol)

    if df.empty:
        print(json.dumps({"error": f"No data found for symbol: {symbol}"}))
        sys.exit(1)

    df = calculate_moving_averages(df)
    saved_path = plot_trend(df, symbol, output_path)
    print(json.dumps({
        "status": "ok",
        "symbol": symbol,
        "image_path": saved_path,
        "latest_metrics": {
            "close": round(float(df['close'].iloc[-1]), 4),
            "sma_20": round(float(df['SMA_20'].iloc[-1]), 4),
            "sma_50": round(float(df['SMA_50'].iloc[-1]), 4),
            "ema_20": round(float(df['EMA_20'].iloc[-1]), 4),
        }
    }))

if __name__ == "__main__":
    main()
