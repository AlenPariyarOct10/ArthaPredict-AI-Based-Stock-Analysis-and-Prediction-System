"""
ML Optimization System

A comprehensive code review and optimization framework designed to improve prediction accuracy 
in the ArthaPredict stock prediction application while maintaining academic integrity.
"""

__version__ = "1.0.0"
__author__ = "ArthaPredict Development Team"

from .ml_optimizer import ML_Optimizer
from .trainer_analyzer import Trainer_Analyzer
from .database_mapper import Database_Mapper
from .base_analyzer import BaseAnalyzer
from .finding import Finding, FindingType, Severity
from .report_generator import ReportGenerator
from .lstm_analyzer import LSTMAnalyzer

__all__ = [
    'ML_Optimizer',
    'Trainer_Analyzer', 
    'Database_Mapper',
    'BaseAnalyzer',
    'Finding',
    'FindingType',
    'Severity',
    'ReportGenerator',
    'LSTMAnalyzer'
]