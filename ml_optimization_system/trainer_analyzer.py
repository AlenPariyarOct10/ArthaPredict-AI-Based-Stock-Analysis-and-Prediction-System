"""
Trainer Analyzer

Deep analysis of ML algorithm implementations to identify bugs, inefficiencies, 
and optimization opportunities using AST-based Python code parsing.
"""

import ast
import re
from typing import List, Dict, Any, Optional, Set, Tuple
from pathlib import Path
from .base_analyzer import BaseAnalyzer
from .finding import Finding, FindingType, Severity


class PythonCodeParser:
    """AST-based Python code parser for ML algorithm analysis."""
    
    def __init__(self, file_path: Path):
        """
        Initialize parser with Python file.
        
        Args:
            file_path: Path to Python file to parse
        """
        self.file_path = file_path
        self.source_code = None
        self.ast_tree = None
        self.source_lines = []
        
    def parse(self) -> bool:
        """
        Parse Python file into AST.
        
        Returns:
            True if parsing successful, False otherwise
        """
        try:
            with open(self.file_path, 'r', encoding='utf-8') as f:
                self.source_code = f.read()
                self.source_lines = self.source_code.splitlines()
            
            self.ast_tree = ast.parse(self.source_code)
            return True
        except Exception as e:
            print(f"Failed to parse {self.file_path}: {e}")
            return False
    
    def get_function_definitions(self) -> List[Dict[str, Any]]:
        """
        Extract all function definitions from AST.
        
        Returns:
            List of function metadata dictionaries
        """
        functions = []
        
        for node in ast.walk(self.ast_tree):
            if isinstance(node, ast.FunctionDef):
                func_info = {
                    'name': node.name,
                    'line_number': node.lineno,
                    'args': [arg.arg for arg in node.args.args],
                    'docstring': ast.get_docstring(node),
                    'is_method': False,
                    'class_name': None
                }
                functions.append(func_info)
                
            elif isinstance(node, ast.AsyncFunctionDef):
                func_info = {
                    'name': node.name,
                    'line_number': node.lineno,
                    'args': [arg.arg for arg in node.args.args],
                    'docstring': ast.get_docstring(node),
                    'is_method': False,
                    'class_name': None,
                    'is_async': True
                }
                functions.append(func_info)
        
        return functions
    
    def get_class_definitions(self) -> List[Dict[str, Any]]:
        """
        Extract all class definitions from AST.
        
        Returns:
            List of class metadata dictionaries
        """
        classes = []
        
        for node in ast.walk(self.ast_tree):
            if isinstance(node, ast.ClassDef):
                methods = []
                for item in node.body:
                    if isinstance(item, (ast.FunctionDef, ast.AsyncFunctionDef)):
                        method_info = {
                            'name': item.name,
                            'line_number': item.lineno,
                            'args': [arg.arg for arg in item.args.args],
                            'docstring': ast.get_docstring(item)
                        }
                        methods.append(method_info)
                
                class_info = {
                    'name': node.name,
                    'line_number': node.lineno,
                    'docstring': ast.get_docstring(node),
                    'methods': methods,
                    'base_classes': [base.id if isinstance(base, ast.Name) else str(base) 
                                   for base in node.bases]
                }
                classes.append(class_info)
        
        return classes
    
    def get_imports(self) -> List[Dict[str, Any]]:
        """
        Extract import statements from AST.
        
        Returns:
            List of import metadata dictionaries
        """
        imports = []
        
        for node in ast.walk(self.ast_tree):
            if isinstance(node, ast.Import):
                for alias in node.names:
                    import_info = {
                        'type': 'import',
                        'module': alias.name,
                        'alias': alias.asname,
                        'line_number': node.lineno
                    }
                    imports.append(import_info)
                    
            elif isinstance(node, ast.ImportFrom):
                for alias in node.names:
                    import_info = {
                        'type': 'from_import',
                        'module': node.module,
                        'name': alias.name,
                        'alias': alias.asname,
                        'line_number': node.lineno
                    }
                    imports.append(import_info)
        
        return imports
    
    def find_numpy_operations(self) -> List[Dict[str, Any]]:
        """
        Find numpy operations in the code.
        
        Returns:
            List of numpy operation metadata
        """
        numpy_ops = []
        
        for node in ast.walk(self.ast_tree):
            if isinstance(node, ast.Call):
                if isinstance(node.func, ast.Attribute):
                    if isinstance(node.func.value, ast.Name) and node.func.value.id == 'np':
                        op_info = {
                            'operation': node.func.attr,
                            'line_number': node.lineno,
                            'args_count': len(node.args)
                        }
                        numpy_ops.append(op_info)
        
        return numpy_ops
    
    def get_line_content(self, line_number: int, context: int = 2) -> str:
        """
        Get source code around a specific line.
        
        Args:
            line_number: Line number (1-indexed)
            context: Number of context lines before and after
            
        Returns:
            Code snippet as string
        """
        start = max(0, line_number - 1 - context)
        end = min(len(self.source_lines), line_number + context)
        
        lines = []
        for i in range(start, end):
            prefix = ">>>" if i == line_number - 1 else "   "
            lines.append(f"{prefix} {i+1:3d}: {self.source_lines[i]}")
        
        return "\n".join(lines)


class Trainer_Analyzer(BaseAnalyzer):
    """
    Analyzes ML algorithm implementations for bugs, inefficiencies, and optimization opportunities.
    
    Uses AST-based parsing to perform deep code analysis on Python ML algorithm files.
    """
    
    def __init__(self, root_path: str = None):
        """
        Initialize Trainer_Analyzer.
        
        Args:
            root_path: Root directory path for the project
        """
        super().__init__(root_path)
        self.ml_service_path = self.root_path / "ml_service"
        self.algorithm_files = {}
        self.parsed_files = {}
        
    def analyze(self, stock_symbol: str = None) -> List[Finding]:
        """
        Perform comprehensive analysis of ML algorithm implementations.
        
        Args:
            stock_symbol: Optional stock symbol for focused analysis
            
        Returns:
            List of findings from algorithm analysis
        """
        self.clear_findings()
        
        # Discover and parse algorithm files
        self._discover_algorithm_files()
        self._parse_algorithm_files()
        
        # Perform analysis on each algorithm type
        self._analyze_lstm_implementation()
        self._analyze_random_forest_implementation()
        self._analyze_xgboost_implementation() 
        self._analyze_moving_average_implementation()
        
        # General code quality analysis
        self._analyze_code_quality()
        
        return self.get_findings()
    
    def _discover_algorithm_files(self):
        """Discover ML algorithm files in the ml_service directory."""
        if not self.ml_service_path.exists():
            self.add_finding(Finding(
                finding_type=FindingType.IMPLEMENTATION_ERROR,
                severity=Severity.CRITICAL,
                title="ML Service Directory Not Found",
                description=f"Could not find ml_service directory at {self.ml_service_path}",
                file_path=str(self.ml_service_path)
            ))
            return
        
        # Define algorithm file patterns
        algorithm_patterns = {
            'LSTM': ['lstm.py', 'lstm_universal_model.py'],
            'RandomForest': ['random_forest.py', 'random_forest_universal_model.py'],
            'XGBoost': ['xgboost.py', 'xgboost_universal_model.py'],
            'MovingAverage': ['simple_moving_average.py']
        }
        
        self.algorithm_files = {}
        for algorithm, patterns in algorithm_patterns.items():
            self.algorithm_files[algorithm] = []
            for pattern in patterns:
                file_path = self.ml_service_path / pattern
                if file_path.exists():
                    self.algorithm_files[algorithm].append(file_path)
    
    def _parse_algorithm_files(self):
        """Parse all discovered algorithm files into AST."""
        self.parsed_files = {}
        
        for algorithm, files in self.algorithm_files.items():
            self.parsed_files[algorithm] = []
            for file_path in files:
                parser = PythonCodeParser(file_path)
                if parser.parse():
                    self.parsed_files[algorithm].append(parser)
                else:
                    self.add_finding(Finding(
                        finding_type=FindingType.IMPLEMENTATION_ERROR,
                        severity=Severity.MAJOR,
                        title=f"Failed to Parse {algorithm} File",
                        description=f"Could not parse {file_path} - may contain syntax errors",
                        file_path=str(file_path),
                        algorithm_type=algorithm
                    ))
    
    def _analyze_lstm_implementation(self):
        """Analyze LSTM algorithm implementation using specialized LSTMAnalyzer."""
        if 'LSTM' not in self.parsed_files:
            return
        
        # Use specialized LSTM analyzer for comprehensive analysis
        from .lstm_analyzer import LSTMAnalyzer
        
        print("🔬 Running specialized LSTM analysis...")
        lstm_analyzer = LSTMAnalyzer(str(self.root_path))
        lstm_findings = lstm_analyzer.analyze()
        
        # Add LSTM findings to main analyzer
        for finding in lstm_findings:
            self.add_finding(finding)
        
        print(f"   ✅ LSTM analysis completed: {len(lstm_findings)} findings generated")
    
    def _analyze_random_forest_implementation(self):
        """Analyze Random Forest implementation for common issues."""
        if 'RandomForest' not in self.parsed_files:
            return
            
        for parser in self.parsed_files['RandomForest']:
            self._check_bootstrap_sampling(parser)
            self._check_tree_construction(parser)
            self._check_voting_mechanism(parser)
    
    def _check_bootstrap_sampling(self, parser: PythonCodeParser):
        """Check Random Forest bootstrap sampling logic."""
        if 'bootstrap' not in parser.source_code.lower() and 'sample' not in parser.source_code.lower():
            self.add_finding(Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.MAJOR,
                title="Missing Bootstrap Sampling",
                description="No bootstrap sampling found in Random Forest - may reduce ensemble diversity",
                file_path=str(parser.file_path),
                algorithm_type="RandomForest",
                estimated_accuracy_improvement=10.0
            ))
    
    def _check_tree_construction(self, parser: PythonCodeParser):
        """Check tree construction logic."""
        functions = parser.get_function_definitions()
        
        tree_functions = [f for f in functions if any(keyword in f['name'].lower() 
                         for keyword in ['split', 'tree', 'node'])]
        
        if not tree_functions:
            self.add_finding(Finding(
                finding_type=FindingType.IMPLEMENTATION_ERROR,
                severity=Severity.CRITICAL,
                title="Missing Tree Construction Logic",
                description="No tree construction functions found in Random Forest",
                file_path=str(parser.file_path),
                algorithm_type="RandomForest"
            ))
    
    def _check_voting_mechanism(self, parser: PythonCodeParser):
        """Check ensemble voting mechanism."""
        if 'vote' not in parser.source_code.lower() and 'average' not in parser.source_code.lower():
            self.add_finding(Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.MAJOR,
                title="Missing Voting Mechanism", 
                description="No voting mechanism found - ensemble may not aggregate predictions properly",
                file_path=str(parser.file_path),
                algorithm_type="RandomForest"
            ))
    
    def _analyze_xgboost_implementation(self):
        """Analyze XGBoost implementation for common issues."""
        if 'XGBoost' not in self.parsed_files:
            return
            
        for parser in self.parsed_files['XGBoost']:
            self._check_gradient_boosting_logic(parser)
            self._check_tree_construction_xgb(parser)
            self._check_regularization(parser)
    
    def _check_gradient_boosting_logic(self, parser: PythonCodeParser):
        """Check XGBoost gradient boosting logic."""
        if 'gradient' not in parser.source_code.lower() or 'residual' not in parser.source_code.lower():
            self.add_finding(Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.CRITICAL,
                title="Missing Gradient Boosting Logic",
                description="No gradient boosting logic found in XGBoost implementation",
                file_path=str(parser.file_path),
                algorithm_type="XGBoost",
                estimated_accuracy_improvement=25.0
            ))
    
    def _check_tree_construction_xgb(self, parser: PythonCodeParser):
        """Check XGBoost tree construction."""
        # Similar to Random Forest but with boosting-specific checks
        if 'split' not in parser.source_code.lower():
            self.add_finding(Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.MAJOR,
                title="Missing Split Logic in XGBoost",
                description="No splitting logic found - trees may not be constructed properly",
                file_path=str(parser.file_path),
                algorithm_type="XGBoost"
            ))
    
    def _check_regularization(self, parser: PythonCodeParser):
        """Check XGBoost regularization implementation."""
        has_regularization = any(term in parser.source_code.lower() 
                               for term in ['regularization', 'lambda', 'alpha', 'l1', 'l2'])
        
        if not has_regularization:
            self.add_finding(Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.MINOR,
                title="Missing Regularization in XGBoost",
                description="No regularization found - may lead to overfitting",
                file_path=str(parser.file_path),
                algorithm_type="XGBoost",
                estimated_accuracy_improvement=5.0
            ))
    
    def _analyze_moving_average_implementation(self):
        """Analyze Moving Average implementation."""
        if 'MovingAverage' not in self.parsed_files:
            return
            
        for parser in self.parsed_files['MovingAverage']:
            self._check_moving_average_logic(parser)
    
    def _check_moving_average_logic(self, parser: PythonCodeParser):
        """Check Moving Average calculation logic."""
        if 'average' not in parser.source_code.lower() and 'mean' not in parser.source_code.lower():
            self.add_finding(Finding(
                finding_type=FindingType.IMPLEMENTATION_ERROR,
                severity=Severity.CRITICAL,
                title="Missing Average Calculation",
                description="No average calculation found in Moving Average implementation",
                file_path=str(parser.file_path),
                algorithm_type="MovingAverage"
            ))
    
    def _analyze_code_quality(self):
        """Analyze general code quality across all algorithm files."""
        for algorithm, parsers in self.parsed_files.items():
            for parser in parsers:
                self._check_imports(parser, algorithm)
                self._check_function_documentation(parser, algorithm)
    
    def _check_imports(self, parser: PythonCodeParser, algorithm: str):
        """Check for prohibited ML library imports."""
        imports = parser.get_imports()
        
        prohibited_libraries = ['sklearn', 'tensorflow', 'torch', 'pytorch', 'keras', 'xgboost']
        
        for import_info in imports:
            module = import_info.get('module', '')
            if any(lib in module.lower() for lib in prohibited_libraries):
                self.add_finding(Finding(
                    finding_type=FindingType.IMPLEMENTATION_ERROR,
                    severity=Severity.CRITICAL,
                    title="Prohibited ML Library Import",
                    description=f"Found import of prohibited library '{module}' - violates academic integrity",
                    file_path=str(parser.file_path),
                    line_number=import_info['line_number'],
                    algorithm_type=algorithm,
                    code_snippet=parser.get_line_content(import_info['line_number'])
                ))
    
    def _check_function_documentation(self, parser: PythonCodeParser, algorithm: str):
        """Check function documentation quality."""
        functions = parser.get_function_definitions()
        
        undocumented_functions = [f for f in functions if not f['docstring'] and not f['name'].startswith('_')]
        
        if len(undocumented_functions) > len(functions) * 0.5:  # More than 50% undocumented
            self.add_finding(Finding(
                finding_type=FindingType.DOCUMENTATION,
                severity=Severity.MINOR,
                title="Poor Function Documentation",
                description=f"Many functions lack documentation in {algorithm} implementation",
                file_path=str(parser.file_path),
                algorithm_type=algorithm,
                recommended_fix="Add docstrings to public functions explaining parameters and return values"
            ))