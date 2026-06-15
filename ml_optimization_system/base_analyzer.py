"""
Base Analyzer

Base class for all analyzers providing common interfaces and shared functionality.
"""

from abc import ABC, abstractmethod
from typing import List, Dict, Any, Optional
from pathlib import Path
from .finding import Finding


class BaseAnalyzer(ABC):
    """
    Base class for all analyzers in the ML optimization system.
    
    Provides common interfaces and shared functionality for code analysis,
    finding generation, and report integration.
    """
    
    def __init__(self, root_path: str = None):
        """
        Initialize analyzer with root path.
        
        Args:
            root_path: Root directory path for the project
        """
        if root_path is None:
            # Default to ArthaPredict project root
            self.root_path = Path(__file__).resolve().parent.parent
        else:
            self.root_path = Path(root_path).resolve()
            
        self.findings: List[Finding] = []
        self.analysis_metadata: Dict[str, Any] = {}
        
    @abstractmethod
    def analyze(self, **kwargs) -> List[Finding]:
        """
        Perform analysis and return findings.
        
        This method must be implemented by all analyzer subclasses.
        
        Returns:
            List of findings identified during analysis
        """
        pass
    
    def get_findings(self) -> List[Finding]:
        """
        Get all findings from the most recent analysis.
        
        Returns:
            List of findings
        """
        return self.findings.copy()
    
    def get_critical_findings(self) -> List[Finding]:
        """
        Get only critical findings.
        
        Returns:
            List of critical findings
        """
        from .finding import Severity
        return [f for f in self.findings if f.severity == Severity.CRITICAL]
    
    def get_findings_by_type(self, finding_type: str) -> List[Finding]:
        """
        Get findings filtered by type.
        
        Args:
            finding_type: Type of findings to retrieve
            
        Returns:
            List of findings matching the type
        """
        return [f for f in self.findings if f.finding_type == finding_type]
    
    def clear_findings(self):
        """Clear all findings and reset analysis state."""
        self.findings.clear()
        self.analysis_metadata.clear()
    
    def add_finding(self, finding: Finding):
        """
        Add a finding to the collection.
        
        Args:
            finding: Finding object to add
        """
        self.findings.append(finding)
    
    def get_analysis_summary(self) -> Dict[str, Any]:
        """
        Get summary of analysis results.
        
        Returns:
            Dictionary containing analysis summary
        """
        from .finding import Severity, FindingType
        
        summary = {
            'total_findings': len(self.findings),
            'critical_count': len([f for f in self.findings if f.severity == Severity.CRITICAL]),
            'major_count': len([f for f in self.findings if f.severity == Severity.MAJOR]),
            'minor_count': len([f for f in self.findings if f.severity == Severity.MINOR]),
            'finding_types': {}
        }
        
        # Count findings by type
        for finding in self.findings:
            finding_type = finding.finding_type
            if finding_type not in summary['finding_types']:
                summary['finding_types'][finding_type] = 0
            summary['finding_types'][finding_type] += 1
        
        return summary
    
    def validate_file_exists(self, file_path: Path) -> bool:
        """
        Validate that a file exists and is readable.
        
        Args:
            file_path: Path to validate
            
        Returns:
            True if file exists and is readable
        """
        return file_path.exists() and file_path.is_file()
    
    def get_relative_path(self, file_path: Path) -> str:
        """
        Get relative path from project root.
        
        Args:
            file_path: Absolute file path
            
        Returns:
            Relative path as string
        """
        try:
            return str(file_path.relative_to(self.root_path))
        except ValueError:
            return str(file_path)