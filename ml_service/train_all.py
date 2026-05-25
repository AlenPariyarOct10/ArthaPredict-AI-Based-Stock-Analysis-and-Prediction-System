import sys
import json
import subprocess
import os
from datetime import datetime
from sqlalchemy import create_engine, text

def main():
    # Ensure we run from the project root directory
    project_root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    os.chdir(project_root)
    
    try:
        # Connect to the database
        engine = create_engine('mysql+pymysql://root:@127.0.0.1/arthapredict')
        
        with engine.connect() as conn:
            stocks = conn.execute(text("SELECT id, symbol FROM stocks WHERE is_active = 1")).fetchall()
            if hasattr(conn, 'commit'):
                conn.commit()
            
            if not stocks:
                print("No active stocks found.")
                return
                
            total_stocks = len(stocks)
            print(f"Found {total_stocks} active stocks. Starting training process...\n")
            
            for index, (stock_id, symbol) in enumerate(stocks, start=1):
                progress_pct = (index / total_stocks) * 100
                print(f"[{index}/{total_stocks}] ({progress_pct:.2f}%) Training models for {symbol}...", end="", flush=True)
                
                # Run predict.py as a subprocess
                result = subprocess.run(
                    [sys.executable, "ml_service/predict.py", symbol],
                    capture_output=True,
                    text=True
                )
                
                if result.returncode != 0:
                    # predict.py might have printed the error JSON to stdout
                    print(f" [FAILED]\nExit Code: {result.returncode}")
                    if result.stdout.strip():
                        print(f"Stdout Output: {result.stdout.strip()}")
                    if result.stderr.strip():
                        print(f"Stderr Output: {result.stderr.strip()}")
                    continue
                
                try:
                    output_data = json.loads(result.stdout.strip())
                    if "error" in output_data:
                        if "Not enough data" in output_data['error'] or "No data found" in output_data['error']:
                            print(f" [SKIPPED] - {output_data['error']}")
                        else:
                            print(f" [FAILED]\nError: {output_data['error']}")
                        continue
                        
                    predictions = output_data.get("predictions", [])
                    if not predictions:
                        print(" [FAILED]\nError: No predictions returned.")
                        continue
                        
                    # Transaction for updates
                    with conn.begin():
                        # Delete old predictions
                        conn.execute(
                            text("DELETE FROM stock_predictions WHERE stock_id = :stock_id"),
                            {"stock_id": stock_id}
                        )
                        
                        # Insert new predictions
                        now = datetime.now()
                        insert_query = text("""
                            INSERT INTO stock_predictions 
                            (stock_id, model_type, target_date, predicted_price, additional_metrics, created_at, updated_at) 
                            VALUES (:stock_id, :model_type, :target_date, :predicted_price, :additional_metrics, :created_at, :updated_at)
                        """)
                        
                        for p in predictions:
                            conn.execute(
                                insert_query,
                                {
                                    "stock_id": stock_id,
                                    "model_type": p["model_type"],
                                    "target_date": p["target_date"],
                                    "predicted_price": p["predicted_price"],
                                    "additional_metrics": json.dumps(p["additional_metrics"]),
                                    "created_at": now,
                                    "updated_at": now
                                }
                            )
                            
                    print(" [SUCCESS]")
                    
                except json.JSONDecodeError:
                    print(f" [FAILED]\nFailed to parse JSON output: {result.stdout}")
                    continue
                except Exception as e:
                    print(f" [FAILED]\nDatabase Error: {str(e)}")
                    continue
                    
            print("\nAll models processed successfully.")
            
    except Exception as e:
        print(f"An error occurred: {str(e)}")

if __name__ == "__main__":
    main()