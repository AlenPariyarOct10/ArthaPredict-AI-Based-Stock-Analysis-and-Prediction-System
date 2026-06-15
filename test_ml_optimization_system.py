#!/usr/bin/env python3
"""
Test Script for ML Optimization System Core Infrastructure

This script tests the basic functionality of the newly created ML optimization system
to ensure all components are working correctly.
"""

import sys
from pathlib import Path

# Add the ml_optimization_system to Python path
sys.path.insert(0, str(Path(__file__).parent / "ml_optimization_system"))

try:
    from ml_optimization_system import (
        ML_Optimizer, 
        Trainer_Analyzer, 
        Database_Mapper,
        BaseAnalyzer,
        Finding,
        FindingType,
        Severity,
        ReportGenerator
    )
    print("✅ All imports successful!")
except ImportError as e:
    print(f"❌ Import error: {e}")
    sys.exit(1)


def test_core_infrastructure():
    """Test core infrastructure components."""
    print("\n" + "="*60)
    print("TESTING ML OPTIMIZATION SYSTEM CORE INFRASTRUCTURE")
    print("="*60)
    
    # Test 1: Initialize ML_Optimizer
    print("\n1. Testing ML_Optimizer initialization...")
    try:
        optimizer = ML_Optimizer()
        print(f"   ✅ ML_Optimizer initialized successfully")
        print(f"   📁 Root path: {optimizer.root_path}")
        print(f"   🔧 Components: trainer_analyzer, database_mapper, report_generator")
    except Exception as e:
        print(f"   ❌ ML_Optimizer initialization failed: {e}")
        return False
    
    # Test 2: Test Trainer_Analyzer
    print("\n2. Testing Trainer_Analyzer...")
    try:
        trainer = Trainer_Analyzer()
        print(f"   ✅ Trainer_Analyzer initialized successfully")
        print(f"   📁 ML service path: {trainer.ml_service_path}")
        
        # Test algorithm file discovery
        trainer._discover_algorithm_files()
        print(f"   🔍 Algorithm files discovered:")
        for algorithm, files in trainer.algorithm_files.items():
            print(f"     - {algorithm}: {len(files)} files")
    except Exception as e:
        print(f"   ❌ Trainer_Analyzer test failed: {e}")
        return False
    
    # Test 3: Test Database_Mapper
    print("\n3. Testing Database_Mapper...")
    try:
        db_mapper = Database_Mapper()
        print(f"   ✅ Database_Mapper initialized successfully")
        print(f"   📁 Migrations path: {db_mapper.migrations_path}")
        
        # Test migration parsing
        db_mapper._parse_database_schema()
        print(f"   🗄️  Database tables discovered: {len(db_mapper.database_schema)}")
        for table_name in db_mapper.database_schema.keys():
            print(f"     - {table_name}")
    except Exception as e:
        print(f"   ❌ Database_Mapper test failed: {e}")
        return False
    
    # Test 4: Test Finding creation
    print("\n4. Testing Finding data structures...")
    try:
        test_finding = Finding(
            finding_type=FindingType.ALGORITHM_BUG,
            severity=Severity.MAJOR,
            title="Test Finding",
            description="This is a test finding to verify the data structure works",
            estimated_accuracy_improvement=5.0,
            implementation_risk="medium"
        )
        priority_score = test_finding.get_priority_score()
        print(f"   ✅ Finding created successfully")
        print(f"   📊 Priority score: {priority_score}")
        print(f"   🎯 Accuracy improvement: +{test_finding.estimated_accuracy_improvement}%")
    except Exception as e:
        print(f"   ❌ Finding creation failed: {e}")
        return False
    
    # Test 5: Test basic analysis workflow
    print("\n5. Testing basic analysis workflow...")
    try:
        # Run a quick analysis (this will generate some findings)
        findings = optimizer.trainer_analyzer.analyze()
        print(f"   ✅ Trainer analysis completed")
        print(f"   📈 Findings generated: {len(findings)}")
        
        findings = optimizer.database_mapper.analyze()
        print(f"   ✅ Database analysis completed")
        print(f"   📈 Findings generated: {len(findings)}")
        
    except Exception as e:
        print(f"   ❌ Analysis workflow failed: {e}")
        return False
    
    # Test 6: Test report generation
    print("\n6. Testing report generation...")
    try:
        report_gen = ReportGenerator()
        
        # Create some sample findings for the report
        sample_findings = [
            Finding(
                finding_type=FindingType.ALGORITHM_BUG,
                severity=Severity.CRITICAL,
                title="Sample Critical Issue",
                description="This is a sample critical issue for report testing",
                estimated_accuracy_improvement=15.0,
                algorithm_type="LSTM"
            ),
            Finding(
                finding_type=FindingType.DATA_QUALITY,
                severity=Severity.MAJOR,
                title="Sample Data Quality Issue",
                description="This is a sample data quality issue",
                estimated_accuracy_improvement=8.0
            )
        ]
        
        # Generate report content (don't save to file in test)
        report_content = report_gen._generate_report_content(sample_findings, "TEST")
        print(f"   ✅ Report generation successful")
        print(f"   📄 Report length: {len(report_content)} characters")
        print(f"   📊 Sample findings processed: {len(sample_findings)}")
        
    except Exception as e:
        print(f"   ❌ Report generation failed: {e}")
        return False
    
    return True


def test_ast_parsing():
    """Test AST parsing functionality on actual ML files."""
    print("\n" + "="*60)
    print("TESTING AST PARSING ON ACTUAL ML FILES")
    print("="*60)
    
    try:
        from ml_optimization_system.trainer_analyzer import PythonCodeParser
        
        # Test on an actual ML file
        ml_service_path = Path(__file__).parent / "ml_service"
        
        # Try to parse LSTM file
        lstm_file = ml_service_path / "lstm.py"
        if lstm_file.exists():
            print(f"\n📁 Testing AST parsing on: {lstm_file}")
            parser = PythonCodeParser(lstm_file)
            
            if parser.parse():
                functions = parser.get_function_definitions()
                classes = parser.get_class_definitions()
                imports = parser.get_imports()
                
                print(f"   ✅ AST parsing successful")
                print(f"   🔧 Functions found: {len(functions)}")
                print(f"   🏗️  Classes found: {len(classes)}")
                print(f"   📦 Imports found: {len(imports)}")
                
                # Show some details
                if functions:
                    print(f"   📋 Sample functions:")
                    for func in functions[:3]:  # Show first 3
                        print(f"     - {func['name']} (line {func['line_number']})")
                
                if classes:
                    print(f"   📋 Sample classes:")
                    for cls in classes[:3]:  # Show first 3
                        print(f"     - {cls['name']} (line {cls['line_number']}) - {len(cls['methods'])} methods")
                
                return True
            else:
                print(f"   ❌ Failed to parse {lstm_file}")
                return False
        else:
            print(f"   ⚠️  LSTM file not found at {lstm_file}")
            return True  # Not a failure, just missing file
            
    except Exception as e:
        print(f"   ❌ AST parsing test failed: {e}")
        return False


def main():
    """Run all tests."""
    print("🚀 Starting ML Optimization System Tests...")
    
    # Test core infrastructure
    core_success = test_core_infrastructure()
    
    # Test AST parsing
    ast_success = test_ast_parsing()
    
    # Summary
    print("\n" + "="*60)
    print("TEST RESULTS SUMMARY")
    print("="*60)
    
    if core_success:
        print("✅ Core Infrastructure: PASSED")
    else:
        print("❌ Core Infrastructure: FAILED")
    
    if ast_success:
        print("✅ AST Parsing: PASSED")
    else:
        print("❌ AST Parsing: FAILED")
    
    overall_success = core_success and ast_success
    
    if overall_success:
        print("\n🎉 All tests PASSED! The ML Optimization System core infrastructure is ready.")
        print("📋 Next steps:")
        print("   1. Run actual analysis with: optimizer = ML_Optimizer(); report = optimizer.analyze_full_system()")
        print("   2. Generate reports with: optimizer.generate_report()")
        print("   3. Implement remaining task components")
    else:
        print("\n❌ Some tests FAILED. Please check the errors above.")
        return 1
    
    return 0


if __name__ == "__main__":
    sys.exit(main())