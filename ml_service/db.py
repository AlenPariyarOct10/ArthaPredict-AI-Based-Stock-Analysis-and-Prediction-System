import os
import mysql.connector
from mysql.connector import Error
from typing import Dict, Any, Optional
from datetime import datetime
import json

class DatabaseConnection:
    """Database connection handler for direct MySQL access from Python scripts."""
    
    def __init__(self):
        self.connection = None
        self.config = {
            'host': os.getenv('DB_HOST', '127.0.0.1'),
            'port': int(os.getenv('DB_PORT', '3306')),
            'database': os.getenv('DB_DATABASE', 'arthapredict'),
            'user': os.getenv('DB_USERNAME', 'root'),
            'password': os.getenv('DB_PASSWORD', ''),
            'charset': 'utf8mb4',
            'collation': 'utf8mb4_unicode_ci'
        }
    
    def connect(self):
        """Establish database connection."""
        try:
            self.connection = mysql.connector.connect(**self.config)
            if self.connection.is_connected():
                return True
        except Error as e:
            print(f"Database connection error: {e}")
            return False
    
    def disconnect(self):
        """Close database connection."""
        if self.connection and self.connection.is_connected():
            self.connection.close()
    
    def get_stock_id(self, symbol: str) -> Optional[int]:
        """Get stock ID by symbol."""
        if not self.connection or not self.connection.is_connected():
            if not self.connect():
                return None
        
        try:
            cursor = self.connection.cursor()
            query = "SELECT id FROM stocks WHERE symbol = %s"
            cursor.execute(query, (symbol,))
            result = cursor.fetchone()
            cursor.close()
            return result[0] if result else None
        except Error as e:
            print(f"Error fetching stock ID: {e}")
            return None
    
    def deactivate_old_models(self, stock_id: int, model_type: str):
        """Deactivate old models of the same type for the stock."""
        if not self.connection or not self.connection.is_connected():
            if not self.connect():
                return False
        
        try:
            cursor = self.connection.cursor()
            query = """
                UPDATE trained_models 
                SET is_active = FALSE 
                WHERE stock_id = %s AND model_type = %s
            """
            cursor.execute(query, (stock_id, model_type))
            self.connection.commit()
            cursor.close()
            return True
        except Error as e:
            print(f"Error deactivating old models: {e}")
            return False
    
    def insert_trained_model(self, data: Dict[str, Any]) -> Optional[int]:
        """
        Insert a trained model record into the database.
        
        Args:
            data: Dictionary containing model data with keys:
                - stock_id (int)
                - stock_symbol (str)
                - model_type (str)
                - model_path (str)
                - latest_path (str, optional)
                - fingerprint (str)
                - training_date (datetime or str)
                - data_length (int)
                - config (dict)
                - metrics (dict with mse, mae, rmse, mape, directional_accuracy, confidence_score, training_loss)
        
        Returns:
            The ID of the inserted record, or None on failure
        """
        if not self.connection or not self.connection.is_connected():
            if not self.connect():
                return None
        
        try:
            # Deactivate old models first
            stock_id = data.get('stock_id')
            model_type = data.get('model_type')
            if stock_id and model_type:
                self.deactivate_old_models(stock_id, model_type)
            
            cursor = self.connection.cursor()
            
            # Parse metrics
            metrics = data.get('metrics', {})
            
            # Handle percentage values (remove % and convert to float)
            mape = metrics.get('mape')
            if mape and isinstance(mape, str):
                mape = float(mape.replace('%', ''))
            
            directional_accuracy = metrics.get('directional_accuracy')
            if directional_accuracy and isinstance(directional_accuracy, str):
                directional_accuracy = float(directional_accuracy.replace('%', ''))
            
            confidence_score = metrics.get('confidence_score')
            if confidence_score and isinstance(confidence_score, str):
                confidence_score = float(confidence_score.replace('%', ''))
            
            # Convert config to JSON
            config_json = json.dumps(data.get('config', {}))
            
            # Handle training_date
            training_date = data.get('training_date')
            if isinstance(training_date, str):
                training_date = datetime.fromisoformat(training_date.replace('Z', '+00:00'))
            
            # Set timestamps
            now = datetime.now()
            
            query = """
                INSERT INTO trained_models (
                    stock_id, stock_symbol, model_type, model_path, latest_path,
                    fingerprint, training_date, data_length, config_json, is_active,
                    mse, mae, rmse, mape, directional_accuracy, confidence_score, training_loss,
                    created_at, updated_at
                ) VALUES (
                    %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
                    %s, %s, %s, %s, %s, %s, %s, %s, %s
                )
            """
            
            values = (
                data.get('stock_id'),
                data.get('stock_symbol'),
                data.get('model_type'),
                data.get('model_path'),
                data.get('latest_path'),
                data.get('fingerprint'),
                training_date,
                data.get('data_length'),
                config_json,
                True,  # is_active
                metrics.get('mse'),
                metrics.get('mae'),
                metrics.get('rmse'),
                mape,
                directional_accuracy,
                confidence_score,
                metrics.get('training_loss'),
                now,  # created_at
                now   # updated_at
            )
            
            cursor.execute(query, values)
            self.connection.commit()
            model_id = cursor.lastrowid
            cursor.close()
            
            print(f"Successfully inserted trained model with ID: {model_id}")
            return model_id
            
        except Error as e:
            print(f"Error inserting trained model: {e}")
            if self.connection:
                self.connection.rollback()
            return None
    
    def __enter__(self):
        """Context manager entry."""
        self.connect()
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit."""
        self.disconnect()


def register_model_in_db(symbol: str, model_type: str, model_path: str, 
                          latest_path: str, fingerprint: str, training_date: str,
                          data_length: int, config: Dict, metrics: Dict) -> Optional[int]:
    """
    Convenience function to register a model in the database.
    
    Args:
        symbol: Stock symbol
        model_type: Type of model (lstm, xgboost, etc.)
        model_path: Path to the saved model file
        latest_path: Path to the latest model file
        fingerprint: Model fingerprint
        training_date: Training date (ISO format string)
        data_length: Length of training data
        config: Model configuration dictionary
        metrics: Training metrics dictionary
    
    Returns:
        The ID of the inserted record, or None on failure
    """
    with DatabaseConnection() as db:
        # Get stock ID
        stock_id = db.get_stock_id(symbol)
        if not stock_id:
            print(f"Stock not found for symbol: {symbol}")
            return None
        
        # Prepare data for insertion
        data = {
            'stock_id': stock_id,
            'stock_symbol': symbol,
            'model_type': model_type,
            'model_path': model_path,
            'latest_path': latest_path,
            'fingerprint': fingerprint,
            'training_date': training_date,
            'data_length': data_length,
            'config': config,
            'metrics': metrics
        }
        
        return db.insert_trained_model(data)
