"""
Report Generator

Generates comprehensive findings reports in markdown format with
clear structure, prioritization, and actionable recommendations.
"""

from typing import List, Dict, Any, Optional
from pathlib import Path
from datetime import datetime
from .finding import Finding, FindingType, Severity


class ReportGenerator:
    """
    Generates comprehensive analysis reports from findings.
    
    Creates structured markdown reports with executive summaries,
    detailed analysis sections, and prioritized recommendations.
    """
    
    def __init__(self, output_dir: str = None):
        """
        Initialize report generator.
        
        Args:
            output_dir: Directory to save generated reports
        """
        if output_dir is None:
            self.output_dir = Path.cwd() / "reports"
        else:
            self.output_dir = Path(output_dir)
        
        self.output_dir.mkdir(exist_ok=True)
    
    def generate_findings_report(self, findings: List[Finding], 
                               stock_symbol: str = None,
                               analysis_metadata: Dict[str, Any] = None) -> str:
        """
        Generate comprehensive findings report.
        
        Args:
            findings: List of analysis findings
            stock_symbol: Optional stock symbol for focused analysis
            analysis_metadata: Additional metadata from analysis
            
        Returns:
            Path to generated report file
        """
        # Sort findings by priority
        sorted_findings = sorted(findings, key=lambda f: f.get_priority_score(), reverse=True)
        
        # Generate report content
        report_content = self._generate_report_content(sorted_findings, stock_symbol, analysis_metadata)
        
        # Save report
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        if stock_symbol:
            filename = f"ml_optimization_report_{stock_symbol}_{timestamp}.md"
        else:
            filename = f"ml_optimization_report_{timestamp}.md"
        
        report_path = self.output_dir / filename
        
        with open(report_path, 'w', encoding='utf-8') as f:
            f.write(report_content)
        
        return str(report_path)
    
    def _generate_report_content(self, findings: List[Finding], 
                                stock_symbol: str = None,
                                analysis_metadata: Dict[str, Any] = None) -> str:
        """Generate the full report content."""
        sections = []
        
        # Header
        sections.append(self._generate_header(stock_symbol))
        
        # Executive Summary
        sections.append(self._generate_executive_summary(findings))
        
        # Analysis Overview
        sections.append(self._generate_analysis_overview(findings, analysis_metadata))
        
        # Detailed Findings by Category
        sections.append(self._generate_algorithm_findings(findings))
        sections.append(self._generate_data_quality_findings(findings))
        sections.append(self._generate_feature_engineering_findings(findings))
        sections.append(self._generate_schema_findings(findings))
        sections.append(self._generate_code_quality_findings(findings))
        
        # Priority Recommendations
        sections.append(self._generate_priority_recommendations(findings))
        
        # Optimization Plan
        sections.append(self._generate_optimization_plan(findings))
        
        # Risk Assessment
        sections.append(self._generate_risk_assessment(findings))
        
        # Appendix
        sections.append(self._generate_appendix(findings))
        
        return "\n\n".join(sections)
    
    def _generate_header(self, stock_symbol: str = None) -> str:
        """Generate report header."""
        title = "ML Optimization System - Analysis Report"
        if stock_symbol:
            title += f" ({stock_symbol})"
        
        timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        
        return f"""# {title}

**Generated:** {timestamp}
**System:** ArthaPredict ML Optimization System
**Scope:** {'Stock-specific analysis for ' + stock_symbol if stock_symbol else 'Full system analysis'}

---"""
    
    def _generate_executive_summary(self, findings: List[Finding]) -> str:
        """Generate executive summary section."""
        total_findings = len(findings)
        critical_count = len([f for f in findings if f.severity == Severity.CRITICAL])
        major_count = len([f for f in findings if f.severity == Severity.MAJOR])
        minor_count = len([f for f in findings if f.severity == Severity.MINOR])
        
        # Calculate potential accuracy improvement
        accuracy_improvements = [f.estimated_accuracy_improvement for f in findings 
                               if f.estimated_accuracy_improvement is not None]
        total_improvement = sum(accuracy_improvements) if accuracy_improvements else 0
        
        # Get top issues
        top_critical = [f for f in findings if f.severity == Severity.CRITICAL][:3]
        
        content = f"""## Executive Summary

The ML Optimization System has completed a comprehensive analysis of the ArthaPredict stock prediction system, identifying **{total_findings} findings** across algorithm implementation, data quality, feature engineering, and system architecture.

### Key Statistics

- **Critical Issues:** {critical_count} (require immediate attention)
- **Major Issues:** {major_count} (significant impact on accuracy)
- **Minor Issues:** {minor_count} (optimization opportunities)
- **Estimated Total Accuracy Improvement:** {total_improvement:.1f}% (if all optimizations applied)

### Critical Issues Requiring Immediate Attention

{self._format_finding_list(top_critical, brief=True)}

### Overall Assessment

The analysis reveals several areas where systematic improvements could significantly enhance prediction accuracy while maintaining the academic integrity of manual ML implementations. The findings are categorized by impact and implementation complexity to facilitate prioritized remediation."""
        
        return content
    
    def _generate_analysis_overview(self, findings: List[Finding], 
                                   analysis_metadata: Dict[str, Any] = None) -> str:
        """Generate analysis overview section."""
        # Group findings by type
        finding_groups = {}
        for finding in findings:
            if finding.finding_type not in finding_groups:
                finding_groups[finding.finding_type] = []
            finding_groups[finding.finding_type].append(finding)
        
        # Group findings by algorithm
        algorithm_groups = {}
        for finding in findings:
            if finding.algorithm_type:
                if finding.algorithm_type not in algorithm_groups:
                    algorithm_groups[finding.algorithm_type] = []
                algorithm_groups[finding.algorithm_type].append(finding)
        
        content = f"""## Analysis Overview

### Findings by Category

| Category | Count | Critical | Major | Minor |
|----------|-------|----------|-------|-------|"""
        
        for finding_type, group_findings in finding_groups.items():
            critical = len([f for f in group_findings if f.severity == Severity.CRITICAL])
            major = len([f for f in group_findings if f.severity == Severity.MAJOR])
            minor = len([f for f in group_findings if f.severity == Severity.MINOR])
            
            content += f"""
| {finding_type.value.replace('_', ' ').title()} | {len(group_findings)} | {critical} | {major} | {minor} |"""
        
        content += f"""

### Findings by Algorithm

| Algorithm | Total Findings | Avg Priority Score |
|-----------|----------------|-------------------|"""
        
        for algorithm, group_findings in algorithm_groups.items():
            avg_priority = sum(f.get_priority_score() for f in group_findings) / len(group_findings)
            content += f"""
| {algorithm} | {len(group_findings)} | {avg_priority:.1f} |"""
        
        return content
    
    def _generate_algorithm_findings(self, findings: List[Finding]) -> str:
        """Generate algorithm-specific findings section."""
        algorithm_findings = [f for f in findings if f.finding_type in [
            FindingType.ALGORITHM_BUG, FindingType.MATHEMATICAL_ERROR, 
            FindingType.IMPLEMENTATION_ERROR, FindingType.PERFORMANCE_ISSUE
        ]]
        
        if not algorithm_findings:
            return "## Algorithm Implementation Analysis\n\nNo significant algorithm implementation issues detected."
        
        content = """## Algorithm Implementation Analysis

The following issues were identified in ML algorithm implementations:

"""
        
        # Group by algorithm
        by_algorithm = {}
        for finding in algorithm_findings:
            algorithm = finding.algorithm_type or "General"
            if algorithm not in by_algorithm:
                by_algorithm[algorithm] = []
            by_algorithm[algorithm].append(finding)
        
        for algorithm, alg_findings in by_algorithm.items():
            content += f"### {algorithm} Implementation\n\n"
            content += self._format_finding_list(alg_findings) + "\n\n"
        
        return content
    
    def _generate_data_quality_findings(self, findings: List[Finding]) -> str:
        """Generate data quality findings section."""
        data_findings = [f for f in findings if f.finding_type in [
            FindingType.DATA_QUALITY, FindingType.DATA_LEAKAGE, 
            FindingType.MISSING_VALUES, FindingType.OUTLIER_HANDLING
        ]]
        
        if not data_findings:
            return "## Data Quality Analysis\n\nNo significant data quality issues detected."
        
        content = """## Data Quality Analysis

Data quality issues that may impact model accuracy:

"""
        content += self._format_finding_list(data_findings)
        
        return content
    
    def _generate_feature_engineering_findings(self, findings: List[Finding]) -> str:
        """Generate feature engineering findings section."""
        feature_findings = [f for f in findings if f.finding_type in [
            FindingType.FEATURE_ENGINEERING, FindingType.MISSING_FEATURE,
            FindingType.REDUNDANT_FEATURE, FindingType.FEATURE_INCONSISTENCY
        ]]
        
        if not feature_findings:
            return "## Feature Engineering Analysis\n\nNo significant feature engineering issues detected."
        
        content = """## Feature Engineering Analysis

Opportunities to improve model inputs through better feature engineering:

"""
        content += self._format_finding_list(feature_findings)
        
        return content
    
    def _generate_schema_findings(self, findings: List[Finding]) -> str:
        """Generate database schema findings section."""
        schema_findings = [f for f in findings if f.finding_type in [
            FindingType.SCHEMA_MAPPING, FindingType.UNUSED_FIELD, FindingType.MISSING_FIELD
        ]]
        
        if not schema_findings:
            return "## Database Schema Analysis\n\nNo significant schema issues detected."
        
        content = """## Database Schema Analysis

Database structure optimization opportunities:

"""
        content += self._format_finding_list(schema_findings)
        
        return content
    
    def _generate_code_quality_findings(self, findings: List[Finding]) -> str:
        """Generate code quality findings section."""
        quality_findings = [f for f in findings if f.finding_type in [
            FindingType.CODE_SMELL, FindingType.MAINTAINABILITY, FindingType.DOCUMENTATION
        ]]
        
        if not quality_findings:
            return "## Code Quality Analysis\n\nNo significant code quality issues detected."
        
        content = """## Code Quality Analysis

Code maintainability and documentation improvements:

"""
        content += self._format_finding_list(quality_findings)
        
        return content
    
    def _generate_priority_recommendations(self, findings: List[Finding]) -> str:
        """Generate priority recommendations section."""
        # Get top 10 highest priority findings
        top_findings = sorted(findings, key=lambda f: f.get_priority_score(), reverse=True)[:10]
        
        content = """## Priority Recommendations

The following recommendations are prioritized by impact, accuracy improvement potential, and implementation risk:

"""
        
        for i, finding in enumerate(top_findings, 1):
            priority_score = finding.get_priority_score()
            accuracy_gain = finding.estimated_accuracy_improvement or 0
            risk = finding.implementation_risk or "unknown"
            
            content += f"""### {i}. {finding.title}

**Priority Score:** {priority_score} | **Accuracy Gain:** +{accuracy_gain:.1f}% | **Risk:** {risk.title()}

{finding.description}

**Recommended Action:** {finding.recommended_fix or "See detailed analysis above."}

---

"""
        
        return content
    
    def _generate_optimization_plan(self, findings: List[Finding]) -> str:
        """Generate optimization plan section."""
        # Group findings by implementation phases
        immediate = [f for f in findings if f.severity == Severity.CRITICAL]
        short_term = [f for f in findings if f.severity == Severity.MAJOR]
        long_term = [f for f in findings if f.severity == Severity.MINOR]
        
        content = """## Optimization Implementation Plan

### Phase 1: Immediate Fixes (Critical Issues)

These issues should be addressed immediately as they may be causing incorrect predictions:

"""
        content += self._format_implementation_phase(immediate)
        
        content += """

### Phase 2: Short-term Improvements (Major Issues)

These improvements will provide significant accuracy gains with moderate implementation effort:

"""
        content += self._format_implementation_phase(short_term)
        
        content += """

### Phase 3: Long-term Enhancements (Minor Issues)

These optimizations provide incremental improvements and should be implemented over time:

"""
        content += self._format_implementation_phase(long_term)
        
        return content
    
    def _generate_risk_assessment(self, findings: List[Finding]) -> str:
        """Generate risk assessment section."""
        # Categorize findings by risk level
        high_risk = [f for f in findings if f.implementation_risk == "high"]
        medium_risk = [f for f in findings if f.implementation_risk == "medium"]
        low_risk = [f for f in findings if f.implementation_risk == "low"]
        unknown_risk = [f for f in findings if f.implementation_risk is None]
        
        content = f"""## Risk Assessment

### Implementation Risk Distribution

- **High Risk:** {len(high_risk)} findings (require careful testing and staged rollout)
- **Medium Risk:** {len(medium_risk)} findings (standard testing procedures)
- **Low Risk:** {len(low_risk)} findings (can be implemented with minimal risk)
- **Unknown Risk:** {len(unknown_risk)} findings (require risk analysis before implementation)

### High-Risk Changes Requiring Special Attention

"""
        
        if high_risk:
            content += self._format_finding_list(high_risk)
        else:
            content += "No high-risk changes identified.\n"
        
        content += """

### Risk Mitigation Strategies

1. **Incremental Implementation:** Apply optimizations in small batches
2. **Validation Testing:** Test each change against known good results
3. **Rollback Capability:** Maintain ability to revert changes quickly
4. **Accuracy Monitoring:** Track prediction accuracy before and after changes
5. **Academic Integrity Validation:** Ensure all changes maintain manual implementation approach"""
        
        return content
    
    def _generate_appendix(self, findings: List[Finding]) -> str:
        """Generate appendix with detailed findings."""
        content = """## Appendix: Detailed Findings

### Complete Findings List

"""
        
        for i, finding in enumerate(findings, 1):
            content += f"""#### Finding {i}: {finding.title}

**Type:** {finding.finding_type.value.replace('_', ' ').title()}
**Severity:** {finding.severity.value.title()}
**File:** {finding.file_path or 'N/A'}
{f"**Line:** {finding.line_number}" if finding.line_number else ""}
{f"**Algorithm:** {finding.algorithm_type}" if finding.algorithm_type else ""}

**Description:** {finding.description}

{f"**Impact:** {finding.impact_description}" if finding.impact_description else ""}

{f"**Recommended Fix:** {finding.recommended_fix}" if finding.recommended_fix else ""}

{f"**Mathematical Justification:** {finding.mathematical_justification}" if finding.mathematical_justification else ""}

{f"**Code Snippet:**\n```python\n{finding.code_snippet}\n```" if finding.code_snippet else ""}

---

"""
        
        return content
    
    def _format_finding_list(self, findings: List[Finding], brief: bool = False) -> str:
        """Format a list of findings for display."""
        if not findings:
            return "*No findings in this category.*"
        
        content = ""
        for finding in findings:
            severity_icon = {
                Severity.CRITICAL: "🔴",
                Severity.MAJOR: "🟡", 
                Severity.MINOR: "🟢"
            }
            
            icon = severity_icon.get(finding.severity, "⚪")
            accuracy_text = f" (+{finding.estimated_accuracy_improvement:.1f}%)" if finding.estimated_accuracy_improvement else ""
            
            if brief:
                content += f"- {icon} **{finding.title}**{accuracy_text}: {finding.description[:100]}...\n"
            else:
                content += f"- {icon} **{finding.title}**{accuracy_text}\n"
                content += f"  - {finding.description}\n"
                if finding.recommended_fix:
                    content += f"  - *Fix:* {finding.recommended_fix}\n"
                if finding.file_path:
                    location = finding.file_path
                    if finding.line_number:
                        location += f":{finding.line_number}"
                    content += f"  - *Location:* `{location}`\n"
                content += "\n"
        
        return content
    
    def _format_implementation_phase(self, findings: List[Finding]) -> str:
        """Format findings for implementation phase display."""
        if not findings:
            return "*No findings in this phase.*"
        
        total_accuracy_gain = sum(f.estimated_accuracy_improvement or 0 for f in findings)
        
        content = f"**Total Potential Accuracy Improvement:** +{total_accuracy_gain:.1f}%\n\n"
        
        for i, finding in enumerate(findings, 1):
            content += f"{i}. **{finding.title}**\n"
            content += f"   - *Impact:* {finding.description}\n"
            if finding.recommended_fix:
                content += f"   - *Action:* {finding.recommended_fix}\n"
            if finding.estimated_accuracy_improvement:
                content += f"   - *Expected Gain:* +{finding.estimated_accuracy_improvement:.1f}%\n"
            if finding.implementation_risk:
                content += f"   - *Risk:* {finding.implementation_risk.title()}\n"
            content += "\n"
        
        return content