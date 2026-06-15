"""
Finding Data Structures

Classes and enums for representing analysis findings and their metadata.
"""

from enum import Enum
from dataclasses import dataclass
from typing import Dict, Any, Optional, List
from pathlib import Path


class Severity(Enum):
    """Severity levels for findings."""
    CRITICAL = "critical"
    MAJOR = "major" 
    MINOR = "minor"


class FindingType(Enum):
    """Types of findings that can be detected."""
    # Algorithm Implementation Issues
    ALGORITHM_BUG = "algorithm_bug"
    MATHEMATICAL_ERROR = "mathematical_error"
    PERFORMANCE_ISSUE = "performance_issue"
    IMPLEMENTATION_ERROR = "implementation_error"
    
    # Data Quality Issues
    DATA_QUALITY = "data_quality"
    DATA_LEAKAGE = "data_leakage"
    MISSING_VALUES = "missing_values"
    OUTLIER_HANDLING = "outlier_handling"
    
    # Feature Engineering Issues
    FEATURE_ENGINEERING = "feature_engineering"
    MISSING_FEATURE = "missing_feature"
    REDUNDANT_FEATURE = "redundant_feature"
    FEATURE_INCONSISTENCY = "feature_inconsistency"
    
    # Training Configuration Issues
    HYPERPARAMETER = "hyperparameter"
    OVERFITTING = "overfitting"
    UNDERFITTING = "underfitting"
    TRAINING_CONFIG = "training_config"
    
    # Prediction Workflow Issues
    PREDICTION_INCONSISTENCY = "prediction_inconsistency"
    MODEL_PERSISTENCE = "model_persistence"
    PREPROCESSING_MISMATCH = "preprocessing_mismatch"
    
    # Database and Schema Issues
    SCHEMA_MAPPING = "schema_mapping"
    UNUSED_FIELD = "unused_field"
    MISSING_FIELD = "missing_field"
    
    # Code Quality Issues
    CODE_SMELL = "code_smell"
    MAINTAINABILITY = "maintainability"
    DOCUMENTATION = "documentation"


@dataclass
class Finding:
    """
    Represents a single finding from analysis.
    
    Contains all metadata needed for reporting and optimization planning.
    """
    
    # Core identification
    finding_type: FindingType
    severity: Severity
    title: str
    description: str
    
    # Location information
    file_path: Optional[str] = None
    line_number: Optional[int] = None
    function_name: Optional[str] = None
    class_name: Optional[str] = None
    
    # Code context
    code_snippet: Optional[str] = None
    
    # Impact and recommendations
    impact_description: Optional[str] = None
    recommended_fix: Optional[str] = None
    estimated_accuracy_improvement: Optional[float] = None  # Percentage improvement
    
    # Additional metadata
    algorithm_type: Optional[str] = None  # LSTM, RandomForest, XGBoost, MovingAverage
    affected_components: List[str] = None
    prerequisites: List[str] = None
    
    # Risk assessment
    implementation_risk: Optional[str] = None  # low, medium, high
    backward_compatibility_risk: Optional[str] = None
    
    # Evidence and validation
    validation_test_suggested: Optional[str] = None
    mathematical_justification: Optional[str] = None
    
    def __post_init__(self):
        """Initialize mutable default values."""
        if self.affected_components is None:
            self.affected_components = []
        if self.prerequisites is None:
            self.prerequisites = []
    
    def to_dict(self) -> Dict[str, Any]:
        """
        Convert finding to dictionary for serialization.
        
        Returns:
            Dictionary representation of the finding
        """
        return {
            'finding_type': self.finding_type.value if self.finding_type else None,
            'severity': self.severity.value if self.severity else None,
            'title': self.title,
            'description': self.description,
            'file_path': self.file_path,
            'line_number': self.line_number,
            'function_name': self.function_name,
            'class_name': self.class_name,
            'code_snippet': self.code_snippet,
            'impact_description': self.impact_description,
            'recommended_fix': self.recommended_fix,
            'estimated_accuracy_improvement': self.estimated_accuracy_improvement,
            'algorithm_type': self.algorithm_type,
            'affected_components': self.affected_components,
            'prerequisites': self.prerequisites,
            'implementation_risk': self.implementation_risk,
            'backward_compatibility_risk': self.backward_compatibility_risk,
            'validation_test_suggested': self.validation_test_suggested,
            'mathematical_justification': self.mathematical_justification
        }
    
    @classmethod
    def from_dict(cls, data: Dict[str, Any]) -> 'Finding':
        """
        Create finding from dictionary.
        
        Args:
            data: Dictionary containing finding data
            
        Returns:
            Finding object
        """
        # Convert enum values back to enums
        finding_type = FindingType(data['finding_type']) if data.get('finding_type') else None
        severity = Severity(data['severity']) if data.get('severity') else None
        
        return cls(
            finding_type=finding_type,
            severity=severity,
            title=data.get('title', ''),
            description=data.get('description', ''),
            file_path=data.get('file_path'),
            line_number=data.get('line_number'),
            function_name=data.get('function_name'),
            class_name=data.get('class_name'),
            code_snippet=data.get('code_snippet'),
            impact_description=data.get('impact_description'),
            recommended_fix=data.get('recommended_fix'),
            estimated_accuracy_improvement=data.get('estimated_accuracy_improvement'),
            algorithm_type=data.get('algorithm_type'),
            affected_components=data.get('affected_components', []),
            prerequisites=data.get('prerequisites', []),
            implementation_risk=data.get('implementation_risk'),
            backward_compatibility_risk=data.get('backward_compatibility_risk'),
            validation_test_suggested=data.get('validation_test_suggested'),
            mathematical_justification=data.get('mathematical_justification')
        )
    
    def get_priority_score(self) -> int:
        """
        Calculate priority score for optimization planning.
        
        Returns:
            Priority score (higher = more important)
        """
        severity_weight = {
            Severity.CRITICAL: 100,
            Severity.MAJOR: 50,
            Severity.MINOR: 10
        }
        
        risk_penalty = {
            'low': 0,
            'medium': -10,
            'high': -25,
            None: -5
        }
        
        base_score = severity_weight.get(self.severity, 0)
        risk_score = risk_penalty.get(self.implementation_risk, 0)
        accuracy_bonus = (self.estimated_accuracy_improvement or 0) * 2
        
        return int(base_score + risk_score + accuracy_bonus)