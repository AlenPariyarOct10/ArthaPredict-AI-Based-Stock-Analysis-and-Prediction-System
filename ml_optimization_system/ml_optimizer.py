"""
ML Optimizer

Main orchestrator that coordinates the analysis and optimization process.
Central controller for the ML optimization system workflow.
"""

from typing import List, Dict, Any, Optional, Tuple
from pathlib import Path
from datetime import datetime
import json

from .base_analyzer import BaseAnalyzer
from .trainer_analyzer import Trainer_Analyzer
from .database_mapper import Database_Mapper
from .report_generator import ReportGenerator
from .finding import Finding, FindingType, Severity


class OptimizationReport:
    """Container for optimization analysis results."""
    
    def __init__(self, stock_symbol: str = None):
        """
        Initialize optimization report.
        
        Args:
            stock_symbol: Optional stock symbol for focused analysis
        """
        self.stock_symbol = stock_symbol
        self.timestamp = datetime.now()
        self.findings: List[Finding] = []
        self.analysis_metadata: Dict[str, Any] = {}
        self.component_summaries: Dict[str, Dict[str, Any]] = {}
        self.accuracy_improvement_estimate: float = 0.0
        self.risk_assessment: Dict[str, Any] = {}
        self.optimization_plan: Dict[str, List[Finding]] = {
            'immediate': [],
            'short_term': [],
            'long_term': []
        }
    
    def add_findings(self, findings: List[Finding], component: str):
        """
        Add findings from a component analysis.
        
        Args:
            findings: List of findings to add
            component: Name of the component that generated the findings
        """
        self.findings.extend(findings)
        
        # Create component summary
        self.component_summaries[component] = {
            'total_findings': len(findings),
            'critical_count': len([f for f in findings if f.severity == Severity.CRITICAL]),
            'major_count': len([f for f in findings if f.severity == Severity.MAJOR]),
            'minor_count': len([f for f in findings if f.severity == Severity.MINOR]),
            'estimated_accuracy_improvement': sum(f.estimated_accuracy_improvement or 0 for f in findings)
        }
    
    def finalize_report(self):
        """Finalize the report by calculating summaries and organizing findings."""
        # Calculate total accuracy improvement estimate
        self.accuracy_improvement_estimate = sum(
            f.estimated_accuracy_improvement or 0 for f in self.findings
        )
        
        # Organize findings into optimization phases
        self._organize_optimization_phases()
        
        # Generate risk assessment
        self._generate_risk_assessment()
    
    def _organize_optimization_phases(self):
        """Organize findings into implementation phases."""
        for finding in self.findings:
            if finding.severity == Severity.CRITICAL:
                self.optimization_plan['immediate'].append(finding)
            elif finding.severity == Severity.MAJOR:
                self.optimization_plan['short_term'].append(finding)
            else:
                self.optimization_plan['long_term'].append(finding)
        
        # Sort each phase by priority score
        for phase in self.optimization_plan.values():
            phase.sort(key=lambda f: f.get_priority_score(), reverse=True)
    
    def _generate_risk_assessment(self):
        """Generate overall risk assessment."""
        risk_counts = {'high': 0, 'medium': 0, 'low': 0, 'unknown': 0}
        
        for finding in self.findings:
            risk_level = finding.implementation_risk or 'unknown'
            risk_counts[risk_level] += 1
        
        self.risk_assessment = {
            'risk_distribution': risk_counts,
            'high_risk_findings': [f for f in self.findings if f.implementation_risk == 'high'],
            'total_findings': len(self.findings),
            'overall_risk_score': self._calculate_overall_risk_score()
        }
    
    def _calculate_overall_risk_score(self) -> float:
        """Calculate overall risk score for the optimization plan."""
        risk_weights = {'high': 3, 'medium': 2, 'low': 1, 'unknown': 1.5}
        
        total_weight = 0
        total_findings = 0
        
        for finding in self.findings:
            risk_level = finding.implementation_risk or 'unknown'
            total_weight += risk_weights[risk_level]
            total_findings += 1
        
        return total_weight / total_findings if total_findings > 0 else 0


class OptimizationPlan:
    """Container for generated optimization plan."""
    
    def __init__(self, findings: List[Finding]):
        """
        Initialize optimization plan from findings.
        
        Args:
            findings: List of findings to create plan from
        """
        self.findings = findings
        self.phases = self._organize_phases()
        self.total_estimated_improvement = sum(
            f.estimated_accuracy_improvement or 0 for f in findings
        )
        self.implementation_sequence = self._create_implementation_sequence()
    
    def _organize_phases(self) -> Dict[str, List[Finding]]:
        """Organize findings into implementation phases."""
        phases = {'immediate': [], 'short_term': [], 'long_term': []}
        
        for finding in self.findings:
            if finding.severity == Severity.CRITICAL:
                phases['immediate'].append(finding)
            elif finding.severity == Severity.MAJOR:
                phases['short_term'].append(finding)
            else:
                phases['long_term'].append(finding)
        
        # Sort by priority within each phase
        for phase in phases.values():
            phase.sort(key=lambda f: f.get_priority_score(), reverse=True)
        
        return phases
    
    def _create_implementation_sequence(self) -> List[Finding]:
        """Create optimal implementation sequence considering dependencies."""
        # This is a simplified implementation - in practice would handle complex dependencies
        sequence = []
        
        # Add immediate fixes first
        sequence.extend(self.phases['immediate'])
        
        # Add short-term improvements
        sequence.extend(self.phases['short_term'])
        
        # Add long-term enhancements
        sequence.extend(self.phases['long_term'])
        
        return sequence


class CodeChange:
    """Represents a specific code change to implement an optimization."""
    
    def __init__(self, file_path: str, change_type: str, description: str):
        """
        Initialize code change.
        
        Args:
            file_path: Path to file being changed
            change_type: Type of change (fix, optimization, enhancement)
            description: Description of the change
        """
        self.file_path = file_path
        self.change_type = change_type
        self.description = description
        self.diff_patch: Optional[str] = None
        self.validation_tests: List[str] = []
        self.rollback_instructions: Optional[str] = None


class ML_Optimizer(BaseAnalyzer):
    """
    Main orchestrator for the ML optimization system.
    
    Coordinates analysis workflow, aggregates findings from all components,
    and generates comprehensive optimization reports and plans.
    """
    
    def __init__(self, root_path: str = None):
        """
        Initialize ML_Optimizer.
        
        Args:
            root_path: Root directory path for the project
        """
        super().__init__(root_path)
        
        # Initialize component analyzers
        self.trainer_analyzer = Trainer_Analyzer(root_path)
        self.database_mapper = Database_Mapper(root_path)
        self.report_generator = ReportGenerator()
        
        # Analysis state
        self.current_report: Optional[OptimizationReport] = None
        
    def analyze_stock(self, stock_symbol: str) -> OptimizationReport:
        """
        Perform comprehensive ML optimization analysis for a specific stock.
        
        Args:
            stock_symbol: Stock symbol to analyze (e.g., 'AAPL', 'GOOGL')
            
        Returns:
            OptimizationReport containing all findings and recommendations
        """
        print(f"Starting ML optimization analysis for {stock_symbol}...")
        
        # Initialize report
        self.current_report = OptimizationReport(stock_symbol)
        
        # Phase 1: Algorithm Implementation Analysis
        print("Phase 1: Analyzing ML algorithm implementations...")
        trainer_findings = self.trainer_analyzer.analyze(stock_symbol=stock_symbol)
        self.current_report.add_findings(trainer_findings, 'trainer_analyzer')
        print(f"Found {len(trainer_findings)} algorithm-related findings")
        
        # Phase 2: Database Schema and Feature Mapping
        print("Phase 2: Analyzing database schema and feature mapping...")
        database_findings = self.database_mapper.analyze(stock_symbol=stock_symbol)
        self.current_report.add_findings(database_findings, 'database_mapper')
        print(f"Found {len(database_findings)} database-related findings")
        
        # Phase 3: Cross-component Analysis
        print("Phase 3: Performing cross-component analysis...")
        cross_findings = self._perform_cross_component_analysis()
        self.current_report.add_findings(cross_findings, 'cross_component')
        print(f"Found {len(cross_findings)} cross-component findings")
        
        # Phase 4: Finalize Report
        print("Phase 4: Finalizing analysis report...")
        self.current_report.finalize_report()
        
        print(f"Analysis complete. Total findings: {len(self.current_report.findings)}")
        print(f"Estimated accuracy improvement: +{self.current_report.accuracy_improvement_estimate:.1f}%")
        
        return self.current_report
    
    def analyze_full_system(self) -> OptimizationReport:
        """
        Perform comprehensive analysis of the entire ML system.
        
        Returns:
            OptimizationReport for the full system
        """
        print("Starting comprehensive ML system analysis...")
        
        # Initialize report
        self.current_report = OptimizationReport()
        
        # Run all component analyses
        print("Analyzing ML algorithm implementations...")
        trainer_findings = self.trainer_analyzer.analyze()
        self.current_report.add_findings(trainer_findings, 'trainer_analyzer')
        
        print("Analyzing database schema and mappings...")
        database_findings = self.database_mapper.analyze()
        self.current_report.add_findings(database_findings, 'database_mapper')
        
        print("Performing system-wide analysis...")
        system_findings = self._analyze_system_architecture()
        self.current_report.add_findings(system_findings, 'system_architecture')
        
        # Finalize report
        self.current_report.finalize_report()
        
        print(f"Full system analysis complete. Total findings: {len(self.current_report.findings)}")
        
        return self.current_report
    
    def generate_optimization_plan(self, findings: List[Finding] = None) -> OptimizationPlan:
        """
        Generate optimization plan from findings.
        
        Args:
            findings: Optional list of findings. Uses current report findings if None.
            
        Returns:
            OptimizationPlan with organized implementation phases
        """
        if findings is None and self.current_report:
            findings = self.current_report.findings
        elif findings is None:
            raise ValueError("No findings available. Run analysis first.")
        
        return OptimizationPlan(findings)
    
    def apply_optimizations(self, plan: OptimizationPlan) -> List[CodeChange]:
        """
        Apply optimizations from a plan (stub for future implementation).
        
        Args:
            plan: OptimizationPlan to implement
            
        Returns:
            List of code changes made
        """
        # This would contain the actual implementation logic
        # For now, return empty list as this is infrastructure setup
        print(f"Would apply {len(plan.findings)} optimizations...")
        return []
    
    def generate_report(self, output_path: str = None) -> str:
        """
        Generate markdown report from current analysis.
        
        Args:
            output_path: Optional specific output path for report
            
        Returns:
            Path to generated report file
        """
        if not self.current_report:
            raise ValueError("No analysis results available. Run analyze_stock() or analyze_full_system() first.")
        
        if output_path:
            self.report_generator.output_dir = Path(output_path).parent
        
        return self.report_generator.generate_findings_report(
            findings=self.current_report.findings,
            stock_symbol=self.current_report.stock_symbol,
            analysis_metadata=self.current_report.analysis_metadata
        )
    
    def _perform_cross_component_analysis(self) -> List[Finding]:
        """Perform analysis across multiple components to identify integration issues."""
        findings = []
        
        # Example: Check if database schema supports all algorithm requirements
        trainer_findings = self.trainer_analyzer.get_findings()
        database_findings = self.database_mapper.get_findings()
        
        # Look for misalignment between what algorithms need and what database provides
        algorithm_requirements = set()
        for finding in trainer_findings:
            if finding.finding_type == FindingType.MISSING_FEATURE:
                algorithm_requirements.add(finding.title)
        
        database_capabilities = set()
        for finding in database_findings:
            if finding.finding_type == FindingType.UNUSED_FIELD:
                database_capabilities.add(finding.title)
        
        # Check for workflow consistency issues
        workflow_finding = self._check_training_prediction_consistency()
        if workflow_finding:
            findings.append(workflow_finding)
        
        return findings
    
    def _check_training_prediction_consistency(self) -> Optional[Finding]:
        """Check for consistency between training and prediction workflows."""
        # This is a stub - would implement actual consistency checking
        # For now, create a sample finding to demonstrate the structure
        
        return Finding(
            finding_type=FindingType.PREDICTION_INCONSISTENCY,
            severity=Severity.MAJOR,
            title="Potential Training-Prediction Workflow Inconsistency",
            description="Analysis suggests potential inconsistencies between training and prediction workflows",
            impact_description="May cause prediction accuracy to differ from training accuracy",
            recommended_fix="Implement comprehensive workflow consistency validation",
            estimated_accuracy_improvement=10.0,
            implementation_risk="medium",
            affected_components=["training_pipeline", "prediction_service"]
        )
    
    def _analyze_system_architecture(self) -> List[Finding]:
        """Analyze overall system architecture for optimization opportunities."""
        findings = []
        
        # Check for academic integrity compliance
        findings.append(self._check_academic_integrity())
        
        # Check for system scalability issues
        findings.extend(self._check_scalability_issues())
        
        return findings
    
    def _check_academic_integrity(self) -> Finding:
        """Check compliance with academic integrity requirements."""
        return Finding(
            finding_type=FindingType.IMPLEMENTATION_ERROR,
            severity=Severity.MINOR,
            title="Academic Integrity Validation Required",
            description="Systematic validation needed to ensure all optimizations maintain manual ML implementations",
            recommended_fix="Implement automated checks for prohibited ML library usage",
            implementation_risk="low",
            affected_components=["all_algorithms"]
        )
    
    def _check_scalability_issues(self) -> List[Finding]:
        """Check for system scalability issues."""
        findings = []
        
        # Example scalability concern
        findings.append(Finding(
            finding_type=FindingType.PERFORMANCE_ISSUE,
            severity=Severity.MINOR,
            title="Model Training Scalability",
            description="Current training approach may not scale well with large numbers of stocks",
            recommended_fix="Consider implementing batch training and model caching strategies",
            implementation_risk="medium",
            affected_components=["training_service", "model_persistence"]
        ))
        
        return findings
    
    def get_analysis_summary(self) -> Dict[str, Any]:
        """
        Get comprehensive summary of the most recent analysis.
        
        Returns:
            Dictionary containing analysis summary
        """
        if not self.current_report:
            return {"error": "No analysis results available"}
        
        return {
            'stock_symbol': self.current_report.stock_symbol,
            'timestamp': self.current_report.timestamp.isoformat(),
            'total_findings': len(self.current_report.findings),
            'severity_breakdown': {
                'critical': len([f for f in self.current_report.findings if f.severity == Severity.CRITICAL]),
                'major': len([f for f in self.current_report.findings if f.severity == Severity.MAJOR]),
                'minor': len([f for f in self.current_report.findings if f.severity == Severity.MINOR])
            },
            'estimated_accuracy_improvement': self.current_report.accuracy_improvement_estimate,
            'component_summaries': self.current_report.component_summaries,
            'optimization_phases': {
                'immediate': len(self.current_report.optimization_plan['immediate']),
                'short_term': len(self.current_report.optimization_plan['short_term']),
                'long_term': len(self.current_report.optimization_plan['long_term'])
            },
            'risk_assessment': self.current_report.risk_assessment
        }
    
    def save_analysis_results(self, output_file: str):
        """
        Save analysis results to JSON file.
        
        Args:
            output_file: Path to save analysis results
        """
        if not self.current_report:
            raise ValueError("No analysis results to save")
        
        results = {
            'metadata': {
                'stock_symbol': self.current_report.stock_symbol,
                'timestamp': self.current_report.timestamp.isoformat(),
                'total_findings': len(self.current_report.findings)
            },
            'findings': [finding.to_dict() for finding in self.current_report.findings],
            'summary': self.get_analysis_summary()
        }
        
        with open(output_file, 'w', encoding='utf-8') as f:
            json.dump(results, f, indent=2, ensure_ascii=False)
        
        print(f"Analysis results saved to {output_file}")
    
    def analyze(self, **kwargs) -> List[Finding]:
        """
        Base analyzer interface implementation.
        
        Returns:
            List of all findings from current analysis
        """
        stock_symbol = kwargs.get('stock_symbol')
        
        if stock_symbol:
            report = self.analyze_stock(stock_symbol)
        else:
            report = self.analyze_full_system()
        
        return report.findings