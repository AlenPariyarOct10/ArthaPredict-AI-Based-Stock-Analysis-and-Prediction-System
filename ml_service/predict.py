import sys
import json
import random
from datetime import datetime, timedelta

def main():
    # Simple placeholder script mimicking ML models
    # Usage: python predict.py <SYMBOL>
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Stock symbol required"}))
        sys.exit(1)
        
    symbol = sys.argv[1]
    
    # Simulate API fetch for recent data
    current_price = random.uniform(100.0, 500.0)
    
    # Generate predictions using dummy logic representing different models
    predictions = []
    tomorrow = (datetime.now() + timedelta(days=1)).strftime('%Y-%m-%d')
    next_week = (datetime.now() + timedelta(days=7)).strftime('%Y-%m-%d')
    next_month = (datetime.now() + timedelta(days=30)).strftime('%Y-%m-%d')
    
    models = ["Moving Average", "XGBoost", "LSTM"]
    
    for model in models:
        # LSTM might be more optimistic/varied, MA smooth
        volatility = 0.05 if model == "Moving Average" else 0.15
        
        predictions.append({
            "model_type": model,
            "target_date": tomorrow,
            "predicted_price": current_price * (1 + random.uniform(-volatility, volatility)),
            "additional_metrics": {
                "confidence_score": f"{random.randint(75, 95)}%",
                "mse": round(random.uniform(0.1, 2.5), 4)
            }
        })
        
        predictions.append({
            "model_type": model,
            "target_date": next_week,
            "predicted_price": current_price * (1 + random.uniform(-volatility*1.5, volatility*1.5)),
            "additional_metrics": {
                "confidence_score": f"{random.randint(65, 85)}%",
                "mse": round(random.uniform(0.5, 4.0), 4)
            }
        })

    # Return JSON to be consumed by Laravel Controller or run as shell script from Laravel
    result = {
        "symbol": symbol,
        "current_price": round(current_price, 2),
        "predictions": [
            {
                "model_type": p["model_type"],
                "target_date": p["target_date"],
                "predicted_price": round(p["predicted_price"], 2),
                "additional_metrics": p["additional_metrics"]
            } for p in predictions
        ]
    }
    
    print(json.dumps(result))

if __name__ == "__main__":
    main()
