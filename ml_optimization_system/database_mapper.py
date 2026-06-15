"""
Database Mapper

Maps database schema to ML features and identifies data quality issues
by parsing Laravel migration files and analyzing data flow.
"""

import re
import json
from typing import List, Dict, Any, Optional, Set, Tuple
from pathlib import Path
from .base_analyzer import BaseAnalyzer
from .finding import Finding, FindingType, Severity


class MigrationParser:
    """Parser for Laravel migration files to extract database schema."""
    
    def __init__(self, migration_file: Path):
        """
        Initialize parser with migration file.
        
        Args:
            migration_file: Path to Laravel migration file
        """
        self.migration_file = migration_file
        self.content = ""
        self.parsed_schema = {}
        
    def parse(self) -> bool:
        """
        Parse migration file and extract schema information.
        
        Returns:
            True if parsing successful, False otherwise
        """
        try:
            with open(self.migration_file, 'r', encoding='utf-8') as f:
                self.content = f.read()
            
            self._extract_table_schema()
            return True
        except Exception as e:
            print(f"Failed to parse {self.migration_file}: {e}")
            return False
    
    def _extract_table_schema(self):
        """Extract table schema from migration content."""
        # Find Schema::create calls
        create_pattern = r"Schema::create\s*\(\s*['\"]([^'\"]+)['\"]\s*,\s*function\s*\([^)]*\)\s*\{([^}]+)\}"
        
        matches = re.finditer(create_pattern, self.content, re.DOTALL)
        
        for match in matches:
            table_name = match.group(1)
            table_definition = match.group(2)
            
            self.parsed_schema[table_name] = self._parse_table_definition(table_definition)
    
    def _parse_table_definition(self, definition: str) -> Dict[str, Any]:
        """
        Parse table definition to extract columns and their properties.
        
        Args:
            definition: Table definition code block
            
        Returns:
            Dictionary containing column definitions
        """
        columns = {}
        
        # Common Laravel migration column patterns
        column_patterns = [
            (r'\$table->id\(\)', {'type': 'id', 'primary': True, 'auto_increment': True}),
            (r'\$table->string\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*(\d+))?\)', {'type': 'string'}),
            (r'\$table->integer\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'integer'}),
            (r'\$table->decimal\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*(\d+))?(?:\s*,\s*(\d+))?\)', {'type': 'decimal'}),
            (r'\$table->double\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'double'}),
            (r'\$table->timestamp\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'timestamp'}),
            (r'\$table->timestamps\s*\(\)', {'type': 'timestamps'}),
            (r'\$table->boolean\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'boolean'}),
            (r'\$table->text\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'text'}),
            (r'\$table->json\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'json'}),
            (r'\$table->foreignId\s*\(\s*[\'"]([^\'"]+)[\'"].*?\)', {'type': 'foreign_id'}),
        ]
        
        lines = definition.split('\n')
        
        for line in lines:
            line = line.strip()
            if not line or line.startswith('//') or line.startswith('*'):
                continue
                
            for pattern, base_props in column_patterns:
                match = re.search(pattern, line)
                if match:
                    if pattern == r'\$table->id\(\)':
                        columns['id'] = base_props.copy()
                    elif pattern == r'\$table->timestamps\s*\(\)':
                        columns['created_at'] = {'type': 'timestamp', 'nullable': True}
                        columns['updated_at'] = {'type': 'timestamp', 'nullable': True}
                    else:
                        column_name = match.group(1)
                        column_props = base_props.copy()
                        
                        # Check for modifiers
                        if '->nullable()' in line:
                            column_props['nullable'] = True
                        if '->unique()' in line:
                            column_props['unique'] = True
                        if '->index()' in line:
                            column_props['indexed'] = True
                        if '->default(' in line:
                            default_match = re.search(r'->default\([\'"]?([^\'"]*)[\'"]?\)', line)
                            if default_match:
                                column_props['default'] = default_match.group(1)
                        
                        columns[column_name] = column_props
                    break
        
        return columns


class Database_Mapper(BaseAnalyzer):
    """
    Maps database schema to ML features and identifies data quality issues.
    
    Analyzes Laravel migration files and ML service code to understand
    data flow from database to model inputs.
    """
    
    def __init__(self, root_path: str = None):
        """
        Initialize Database_Mapper.
        
        Args:
            root_path: Root directory path for the project
        """
        super().__init__(root_path)
        self.migrations_path = self.root_path / "database" / "migrations"
        self.ml_service_path = self.root_path / "ml_service"
        
        self.database_schema = {}
        self.feature_mappings = {}
        self.ml_data_usage = {}
        
    def analyze(self, **kwargs) -> List[Finding]:
        """
        Perform comprehensive database to ML feature mapping analysis.
        
        Returns:
            List of findings from database mapping analysis
        """
        self.clear_findings()
        
        # Parse database schema from migrations
        self._parse_database_schema()
        
        # Analyze ML service data usage
        self._analyze_ml_data_usage()
        
        # Map database fields to ML features
        self._map_database_to_features()
        
        # Identify data quality issues
        self._analyze_data_quality_issues()
        
        # Identify unused and missing fields
        self._identify_unused_fields()
        self._identify_missing_fields()
        
        return self.get_findings()
    
    def _parse_database_schema(self):
        """Parse all Laravel migration files to extract database schema."""
        if not self.migrations_path.exists():
            self.add_finding(Finding(
                finding_type=FindingType.SCHEMA_MAPPING,
                severity=Severity.CRITICAL,
                title="Migration Directory Not Found",
                description=f"Could not find migrations directory at {self.migrations_path}",
                file_path=str(self.migrations_path)
            ))
            return
        
        self.database_schema = {}
        
        # Get all migration files in chronological order
        migration_files = sorted([f for f in self.migrations_path.glob("*.php") 
                                if f.is_file()])
        
        for migration_file in migration_files:
            parser = MigrationParser(migration_file)
            if parser.parse():
                self.database_schema.update(parser.parsed_schema)
                
                # Log successful parsing
                self.analysis_metadata[f"parsed_{migration_file.name}"] = True
            else:
                self.add_finding(Finding(
                    finding_type=FindingType.SCHEMA_MAPPING,
                    severity=Severity.MAJOR,
                    title="Failed to Parse Migration File",
                    description=f"Could not parse migration file {migration_file.name}",
                    file_path=str(migration_file)
                ))
    
    def _analyze_ml_data_usage(self):
        """Analyze how ML service code uses database data."""
        if not self.ml_service_path.exists():
            return
        
        # Look for database connection and query patterns
        db_file = self.ml_service_path / "db.py"
        if db_file.exists():
            self._analyze_db_py_usage(db_file)
        
        # Analyze trainer files for data usage patterns
        trainer_files = [
            "lstm.py", "random_forest.py", "xgboost.py", 
            "lstm_universal_model.py", "random_forest_universal_model.py", 
            "xgboost_universal_model.py"
        ]
        
        for trainer_file in trainer_files:
            file_path = self.ml_service_path / trainer_file
            if file_path.exists():
                self._analyze_trainer_data_usage(file_path)
    
    def _analyze_db_py_usage(self, db_file: Path):
        """Analyze database connection patterns in db.py."""
        try:
            with open(db_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Look for SQL queries and table references
            query_patterns = [
                r"SELECT\s+([^FROM]+)\s+FROM\s+(\w+)",
                r"INSERT\s+INTO\s+(\w+)",
                r"UPDATE\s+(\w+)\s+SET",
                r"DELETE\s+FROM\s+(\w+)"
            ]
            
            for pattern in query_patterns:
                matches = re.finditer(pattern, content, re.IGNORECASE)
                for match in matches:
                    if "SELECT" in pattern:
                        columns = [col.strip() for col in match.group(1).split(',')]
                        table = match.group(2)
                        
                        if table not in self.ml_data_usage:
                            self.ml_data_usage[table] = {'columns_used': set(), 'operations': set()}
                        
                        self.ml_data_usage[table]['columns_used'].update(columns)
                        self.ml_data_usage[table]['operations'].add('SELECT')
                    
        except Exception as e:
            self.add_finding(Finding(
                finding_type=FindingType.SCHEMA_MAPPING,
                severity=Severity.MINOR,
                title="Failed to Analyze db.py Usage",
                description=f"Could not analyze database usage patterns: {e}",
                file_path=str(db_file)
            ))
    
    def _analyze_trainer_data_usage(self, trainer_file: Path):
        """Analyze data usage patterns in trainer files."""
        try:
            with open(trainer_file, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # Look for feature column references
            feature_patterns = [
                r"['\"](\w+)['\"]",  # String literals that might be column names
                r"data\[['\"]\w+['\"]\]",  # Data dictionary access
                r"\.(\w+)",  # Attribute access that might be columns
            ]
            
            # This is a simplified analysis - in practice, we'd do more sophisticated AST analysis
            
        except Exception as e:
            self.add_finding(Finding(
                finding_type=FindingType.SCHEMA_MAPPING,
                severity=Severity.MINOR,
                title="Failed to Analyze Trainer Data Usage",
                description=f"Could not analyze data usage in {trainer_file.name}: {e}",
                file_path=str(trainer_file)
            ))
    
    def _map_database_to_features(self):
        """Map database fields to ML features based on usage analysis."""
        # Identify key tables for stock prediction
        stock_tables = ['stocks', 'stock_prices', 'stock_predictions', 'trained_models']
        
        for table_name, schema in self.database_schema.items():
            if table_name in stock_tables:
                self._analyze_table_for_ml_features(table_name, schema)
    
    def _analyze_table_for_ml_features(self, table_name: str, schema: Dict[str, Any]):
        """Analyze a specific table for ML feature potential."""
        if table_name == 'stock_prices':
            # This is the primary table for ML features
            expected_columns = ['open', 'high', 'low', 'close', 'volume', 'date', 'stock_id']
            
            missing_columns = []
            for col in expected_columns:
                if col not in schema:
                    missing_columns.append(col)
            
            if missing_columns:
                self.add_finding(Finding(
                    finding_type=FindingType.MISSING_FIELD,
                    severity=Severity.MAJOR,
                    title="Missing Critical Stock Price Columns",
                    description=f"Missing columns in stock_prices table: {', '.join(missing_columns)}",
                    recommended_fix="Add missing OHLCV columns to stock_prices table",
                    affected_components=[table_name]
                ))
            
            # Check for additional useful columns
            beneficial_columns = ['adjusted_close', 'dividend_amount', 'split_coefficient']
            for col in beneficial_columns:
                if col not in schema:
                    self.add_finding(Finding(
                        finding_type=FindingType.MISSING_FIELD,
                        severity=Severity.MINOR,
                        title=f"Missing Beneficial Column: {col}",
                        description=f"Column '{col}' could improve feature engineering in stock_prices table",
                        recommended_fix=f"Consider adding {col} column for enhanced financial analysis",
                        affected_components=[table_name],
                        estimated_accuracy_improvement=2.0
                    ))
        
        elif table_name == 'stocks':
            # Check for stock metadata that could be useful for ML
            useful_metadata = ['sector', 'market_cap', 'country', 'currency', 'exchange']
            
            for col in useful_metadata:
                if col not in schema:
                    self.add_finding(Finding(
                        finding_type=FindingType.MISSING_FIELD,
                        severity=Severity.MINOR,
                        title=f"Missing Stock Metadata: {col}",
                        description=f"Stock metadata '{col}' could provide valuable context features",
                        recommended_fix=f"Add {col} to stocks table for categorical features",
                        affected_components=[table_name],
                        estimated_accuracy_improvement=3.0
                    ))
    
    def _analyze_data_quality_issues(self):
        """Identify potential data quality issues from schema analysis."""
        for table_name, schema in self.database_schema.items():
            if table_name in ['stock_prices', 'stocks']:
                self._check_nullable_constraints(table_name, schema)
                self._check_data_types(table_name, schema)
                self._check_indexing(table_name, schema)
    
    def _check_nullable_constraints(self, table_name: str, schema: Dict[str, Any]):
        """Check for appropriate nullable constraints."""
        critical_non_nullable = {
            'stock_prices': ['date', 'close', 'stock_id'],
            'stocks': ['symbol', 'name']
        }
        
        if table_name in critical_non_nullable:
            for column in critical_non_nullable[table_name]:
                if column in schema and schema[column].get('nullable', False):
                    self.add_finding(Finding(
                        finding_type=FindingType.DATA_QUALITY,
                        severity=Severity.MAJOR,
                        title=f"Critical Column Allows NULL: {column}",
                        description=f"Column {column} in {table_name} allows NULL but is critical for ML",
                        recommended_fix=f"Make {column} NOT NULL with appropriate constraints",
                        affected_components=[table_name]
                    ))
    
    def _check_data_types(self, table_name: str, schema: Dict[str, Any]):
        """Check for appropriate data types."""
        expected_types = {
            'stock_prices': {
                'open': ['decimal', 'double'],
                'high': ['decimal', 'double'], 
                'low': ['decimal', 'double'],
                'close': ['decimal', 'double'],
                'volume': ['integer', 'bigint']
            }
        }
        
        if table_name in expected_types:
            for column, expected in expected_types[table_name].items():
                if column in schema:
                    actual_type = schema[column].get('type', '')
                    if actual_type not in expected:
                        self.add_finding(Finding(
                            finding_type=FindingType.DATA_QUALITY,
                            severity=Severity.MINOR,
                            title=f"Suboptimal Data Type: {column}",
                            description=f"Column {column} has type {actual_type}, expected one of {expected}",
                            recommended_fix=f"Consider changing {column} to {expected[0]} for better precision",
                            affected_components=[table_name]
                        ))
    
    def _check_indexing(self, table_name: str, schema: Dict[str, Any]):
        """Check for appropriate database indexing."""
        important_indexes = {
            'stock_prices': ['date', 'stock_id', ['stock_id', 'date']],
            'stocks': ['symbol']
        }
        
        if table_name in important_indexes:
            # This is a simplified check - in practice we'd need to analyze actual index creation
            for column in important_indexes[table_name]:
                if isinstance(column, str) and column in schema:
                    if not schema[column].get('indexed', False):
                        self.add_finding(Finding(
                            finding_type=FindingType.PERFORMANCE_ISSUE,
                            severity=Severity.MINOR,
                            title=f"Missing Index on {column}",
                            description=f"Column {column} in {table_name} should be indexed for query performance",
                            recommended_fix=f"Add index on {column} column",
                            affected_components=[table_name]
                        ))
    
    def _identify_unused_fields(self):
        """Identify database fields that are not used in ML but could be valuable."""
        for table_name, schema in self.database_schema.items():
            if table_name in ['stocks', 'stock_prices']:
                used_columns = self.ml_data_usage.get(table_name, {}).get('columns_used', set())
                
                for column_name, column_props in schema.items():
                    # Skip system columns
                    if column_name in ['id', 'created_at', 'updated_at']:
                        continue
                    
                    if column_name not in used_columns:
                        self.add_finding(Finding(
                            finding_type=FindingType.UNUSED_FIELD,
                            severity=Severity.MINOR,
                            title=f"Unused Database Field: {column_name}",
                            description=f"Column {column_name} in {table_name} is not used in ML but might be valuable",
                            recommended_fix=f"Consider incorporating {column_name} into feature engineering",
                            affected_components=[table_name],
                            estimated_accuracy_improvement=1.0
                        ))
    
    def _identify_missing_fields(self):
        """Identify fields that would be beneficial for ML but are missing."""
        beneficial_fields = {
            'stock_prices': [
                ('adjusted_close', 'Adjusted closing price for dividend/split adjustments'),
                ('dividend_amount', 'Dividend payments for fundamental analysis'),
                ('split_coefficient', 'Stock split information'),
                ('trading_volume_usd', 'Volume in USD for better normalization')
            ],
            'stocks': [
                ('sector', 'Industry sector for categorical features'),
                ('market_cap', 'Market capitalization for size-based features'),
                ('pe_ratio', 'Price-to-earnings ratio'),
                ('beta', 'Stock volatility relative to market'),
                ('country', 'Geographic information'),
                ('currency', 'Trading currency'),
                ('exchange', 'Stock exchange information')
            ],
            # Suggest new tables
            'market_indices': [
                ('date', 'Date for time alignment'),
                ('index_name', 'Index identifier (S&P 500, NASDAQ, etc.)'),
                ('index_value', 'Index value for market correlation features'),
                ('volume', 'Index trading volume')
            ]
        }
        
        for table_name, fields in beneficial_fields.items():
            if table_name not in self.database_schema:
                if table_name == 'market_indices':
                    self.add_finding(Finding(
                        finding_type=FindingType.MISSING_FIELD,
                        severity=Severity.MINOR,
                        title="Missing Market Indices Table",
                        description="Market indices data would provide valuable correlation features",
                        recommended_fix="Create market_indices table for macro-economic features",
                        estimated_accuracy_improvement=8.0,
                        affected_components=['feature_engineering']
                    ))
                continue
            
            existing_schema = self.database_schema[table_name]
            for field_name, description in fields:
                if field_name not in existing_schema:
                    self.add_finding(Finding(
                        finding_type=FindingType.MISSING_FIELD,
                        severity=Severity.MINOR,
                        title=f"Missing Field: {field_name}",
                        description=f"Field {field_name} in {table_name}: {description}",
                        recommended_fix=f"Add {field_name} column to {table_name} table",
                        affected_components=[table_name],
                        estimated_accuracy_improvement=2.0
                    ))
    
    def get_schema_summary(self) -> Dict[str, Any]:
        """
        Get summary of database schema analysis.
        
        Returns:
            Dictionary containing schema analysis summary
        """
        return {
            'total_tables': len(self.database_schema),
            'tables_analyzed': list(self.database_schema.keys()),
            'ml_relevant_tables': ['stocks', 'stock_prices', 'stock_predictions', 'trained_models'],
            'feature_mappings_count': len(self.feature_mappings),
            'ml_data_usage': {table: len(data.get('columns_used', [])) 
                            for table, data in self.ml_data_usage.items()}
        }