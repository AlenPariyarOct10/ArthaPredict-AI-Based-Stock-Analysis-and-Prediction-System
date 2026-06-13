# Quick Start Guide - Directional Accuracy Improvements

## ⚡ TL;DR

**Problem**: Directional accuracy was 45-55% (barely better than random)

**Solution**: 
1. Fixed calculation bug in all 3 models
2. Added momentum features to LSTM (3 features vs 1)
3. Expanded XGBoost features (40+ vs 13)

**Expected Result**: 60-75% directional accuracy

**Action Required**: Old LSTM models will auto-retrain when loaded (one-time conversion)

**Note**: First prediction after upgrade will trigger automatic retraining for LSTM models to convert to new format.

---

## 🚀 Quick Start (3 Steps)

### Step 1: Verify Changes Work
```bash
python ml_service/test_directional_accuracy.py
```
Expected output: "All Tests PASSED! ✓"

### Step 2: Retrain One Model (Test)
```bash
python ml_service/predict.py NABIL --force-retrain
```
Replace `NABIL` with any stock symbol in your database.

### Step 3: Check Results
Look for this in the output:
```json
{
  "lstm": {
    "metrics": {
      "directional_accuracy": "XX.X%"  // Should be 60%+
    }
  }
}
```

---

## 📊 Before vs After

| Metric | Before | After (Expected) |
|--------|--------|------------------|
| **LSTM Directional Accuracy** | 45-55% | 60-70% |
| **XGBoost Directional Accuracy** | 45-55% | 65-75% |
| **Random Forest Directional Accuracy** | 45-55% | 65-75% |
| **LSTM Features** | 1 (price only) | 3 (price + momentum + acceleration) |
| **XGBoost Features** | 13 | 40+ |

---

## 🔧 What Changed?

### 1. Bug Fix (All Models)
**OLD (Wrong):**
```python
# Compared predicted[t+1] with actual[t] - meaningless!
predicted_changes = y_pred[1:] - y_true[:-1]
```

**NEW (Correct):**
```python
# Compare if both moved in same direction
actual_direction = np.sign(np.diff(y_true))
pred_direction = np.sign(np.diff(y_pred))
```

### 2. LSTM Enhancement
- **Multi-feature input**: Price + Momentum + Acceleration
- **Dropout**: 10% for regularization
- **Learning rate decay**: Better convergence

### 3. XGBoost Enhancement
- **New features**: Multi-period returns, price/MA ratios, velocity, acceleration
- **Deeper trees**: max_depth 4 → 5
- **Better splits**: 5 → 9 threshold candidates

---

## 💻 Commands Reference

### Retrain Single Stock
```bash
python ml_service/predict.py <SYMBOL> --force-retrain
```

### Retrain with Cached Mode (faster testing)
```bash
python ml_service/predict.py <SYMBOL> --use-cache
```

### Predict Only (no training)
```bash
python ml_service/predict.py <SYMBOL> --predict-only
```

### Check Model Status (from Laravel)
Via admin panel or API endpoint for model training status.

---

## ✅ Verification Checklist

After retraining, verify:

- [ ] Directional accuracy ≥ 60% for at least one model
- [ ] MAPE ≤ 10%
- [ ] Confidence score ≥ 65%
- [ ] No error messages in logs
- [ ] Predictions look reasonable (not wild jumps)

---

## 🎯 Interpretation Guide

### Directional Accuracy
- **50%**: Random (coin flip) - something's wrong
- **55-60%**: Basic, needs improvement
- **60-70%**: Good - useful for decisions ✓
- **70-80%**: Excellent - very reliable ✓✓
- **>80%**: Suspicious - check for overfitting

### When to Use Each Model
- **LSTM**: Best for capturing trends and momentum
- **XGBoost**: Best for complex patterns and interactions
- **Random Forest**: Best for stability and confidence intervals

### Ensemble Strategy
Use all three and:
- **Majority vote**: Direction prediction
- **Average**: Price prediction
- **Highest confidence**: When models disagree

---

## ⚠️ Common Issues

### Issue 1: Directional Accuracy Still Low
**Causes:**
- Insufficient training data (<100 rows)
- Poor data quality (gaps, errors)
- Highly volatile/unpredictable stock

**Solutions:**
- Import more historical data
- Clean data (remove outliers, fill gaps)
- Try different stock symbol

### Issue 2: Training Takes Too Long
**Solutions:**
- Reduce `n_estimators` in XGBoost/RF
- Reduce `epochs` in LSTM
- Use smaller `sequence_length`

### Issue 3: Model Shows "Cached"
**Explanation:**
- Using existing trained model
- Not applying new enhancements

**Solution:**
- Use `--force-retrain` flag
- **Note**: LSTM models with old format (input_size=1) will automatically retrain when loaded

### Issue 4: Error "cannot reshape array"
**Explanation:**
- Old LSTM model format detected (happens once after upgrade)

**Solution:**
- Model will automatically retrain with new format
- This is a one-time conversion, subsequent predictions will use cached model

---

## 📁 File Locations

### Modified Code
- `ml_service/lstm.py` - LSTM enhancements
- `ml_service/xgboost.py` - XGBoost enhancements  
- `ml_service/random_forest.py` - Bug fix only

### Documentation
- `IMPLEMENTATION_SUMMARY.md` - Complete details
- `DIRECTIONAL_ACCURACY_IMPROVEMENTS.md` - Technical explanation
- `QUICK_START_GUIDE.md` - This file

### Testing
- `ml_service/test_directional_accuracy.py` - Validation tests

---

## 🔄 Workflow

### First Time Setup
1. Run validation test
2. Retrain 1-2 stocks
3. Verify improvements
4. Deploy to production

### Regular Maintenance
1. Models auto-retrain weekly
2. Monitor directional accuracy
3. Retrain manually if accuracy drops
4. Review metrics monthly

### Troubleshooting
1. Check data quality
2. Review training logs
3. Test with different stocks
4. Consult documentation

---

## 📞 Support

### If You Get Stuck

1. **Read full docs**: `IMPLEMENTATION_SUMMARY.md`
2. **Check theory**: `DIRECTIONAL_ACCURACY_IMPROVEMENTS.md`
3. **Run tests**: `python ml_service/test_directional_accuracy.py`
4. **Review code**: See inline comments in modified files

### Key Concepts to Understand

**Directional Accuracy**: Measures if model predicts UP when actual goes UP (not exact price)

**Momentum Features**: Rate of change and acceleration that indicate direction

**Multi-period Returns**: Capture trends at different time scales

---

## 🎓 Learning Path

### Beginner
1. Read this guide
2. Retrain one model
3. Understand directional accuracy concept

### Intermediate  
1. Read `IMPLEMENTATION_SUMMARY.md`
2. Understand why features help
3. Test on multiple stocks

### Advanced
1. Read `DIRECTIONAL_ACCURACY_IMPROVEMENTS.md`
2. Review actual code changes
3. Experiment with hyperparameters

---

## ⏱️ Time Estimates

| Task | Time |
|------|------|
| Read this guide | 5 min |
| Run validation test | 1 min |
| Retrain one stock | 2-5 min |
| Verify results | 2 min |
| **Total first setup** | **10-15 min** |

---

## 🎉 Success Criteria

You'll know it's working when:

✅ Directional accuracy improves by 10-20%  
✅ Models predict 60%+ correctly  
✅ MAPE decreases  
✅ Confidence scores increase  
✅ Predictions align with actual trends

---

## 🔮 Next Steps

1. ✅ **Immediate**: Retrain and verify (today)
2. 📊 **Short-term**: Monitor for 1 week (this week)
3. 📈 **Medium-term**: Expand to all stocks (this month)
4. 🚀 **Long-term**: Fine-tune based on performance (ongoing)

---

**Ready? Start with Step 1! 🚀**

```bash
python ml_service/test_directional_accuracy.py
```
