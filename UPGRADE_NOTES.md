# Upgrade Notes - Directional Accuracy Enhancement

## 🔄 Automatic Migration

### What Happens When You Upgrade?

The enhancement includes **automatic backward compatibility** handling:

#### 1. **LSTM Models** (Automatic Conversion)
When an old LSTM model is loaded:
```python
# Code automatically detects old format
is_old_model = (model.input_size == 1)

if is_old_model:
    print("⚠ Old model detected (input_size=1). Forcing retrain...")
    # Automatically retrains with new 3-feature format
```

**What you'll see:**
```
Using cached LSTM model for NABIL: Model is valid
⚠ Old model detected (input_size=1). Forcing retrain for new features...
Training new LSTM model for NABIL...
```

**Timeline:**
- First prediction: Auto-retrains (2-5 minutes)
- Subsequent predictions: Uses new cached model (fast)

#### 2. **XGBoost Models** (Seamless Upgrade)
- New feature calculation is backward compatible
- Old models continue working (with old features)
- New training uses 40+ features automatically
- No action needed

#### 3. **Random Forest Models** (Seamless Upgrade)
- Only bug fix applied (directional accuracy calculation)
- Existing models continue working
- New training uses same 60+ features
- No action needed

---

## 📊 Migration Strategy

### Option A: Automatic (Recommended)
**Do nothing!** Models auto-upgrade on first use:
- LSTM: Auto-detects and retrains
- XGBoost/RF: Continue working, retrain at next scheduled time (7 days)

**Timeline:**
- Day 1: Deploy code changes
- Day 1-7: Models auto-upgrade as they're used
- Day 7+: All models using enhanced features

### Option B: Manual (Immediate Upgrade)
Force retrain all models immediately:

```bash
# Get list of all stock symbols
python ml_service/list_stocks.py

# Retrain each stock
python ml_service/predict.py NABIL --force-retrain
python ml_service/predict.py ADBL --force-retrain
python ml_service/predict.py NICA --force-retrain
# ... etc
```

Or create a batch script:
```bash
# Windows (batch_retrain.bat)
@echo off
for %%S in (NABIL ADBL NICA GBIME SBI) do (
    echo Retraining %%S...
    python ml_service/predict.py %%S --force-retrain
)
```

---

## 🔍 Verification After Upgrade

### Check Auto-Conversion Happened

#### LSTM - Look for this message:
```
⚠ Old model detected (input_size=1). Forcing retrain for new features...
Training new LSTM model for NABIL...
```

#### All Models - Check Metrics:
```json
{
  "lstm": {
    "metrics": {
      "directional_accuracy": "65.3%",  // Should be 60%+
      "cached": false  // First run after conversion
    }
  }
}
```

#### Next Run - Should Use Cache:
```json
{
  "lstm": {
    "metrics": {
      "directional_accuracy": "65.3%",
      "cached": true  // Now using new cached model
    }
  }
}
```

---

## ⚠️ Known Issues & Solutions

### Issue 1: "cannot reshape array of size 3 into shape (1,1)"

**When it occurs:** Loading old LSTM model before auto-detection kicks in (race condition)

**Status:** ✅ FIXED in current code

**How it's fixed:**
- Code checks `model.input_size` before attempting to use cached model
- Forces retrain if input_size doesn't match

**If you still see it:**
- Update to latest code version
- Force retrain: `python ml_service/predict.py <SYMBOL> --force-retrain`

### Issue 2: Lower Directional Accuracy After Upgrade

**Why:** The calculation was fixed - you're now seeing **true** accuracy

**Before (Wrong Calculation):**
```
directional_accuracy: "75%"  // Incorrect metric
```

**After (Correct Calculation):**
```
directional_accuracy: "55%"  // True accuracy with old features
```

Then after retraining with new features:
```
directional_accuracy: "68%"  // Improved with enhancements
```

**Action:** This is expected! Retrain to see real improvements.

---

## 📈 Expected Timeline

### Immediate (After Deployment)
- ✅ All models show correct directional accuracy
- ⚠️ May appear "lower" (actually showing truth)
- ✅ XGBoost/RF continue working normally

### First Prediction Per Stock
- ⏱️ LSTM auto-retrains (2-5 minutes)
- ✅ Upgrades to 3-feature input
- ✅ Shows improved directional accuracy (60%+)

### 7 Days After Deployment
- ✅ XGBoost/RF auto-retrain (scheduled)
- ✅ Use new enhanced features
- ✅ All models fully upgraded

---

## 🎯 Success Metrics

Track these before and after upgrade:

| Metric | Before | After Upgrade | After Retrain |
|--------|--------|---------------|---------------|
| LSTM DA | 45-55% (wrong) | 50-55% (true) | 60-70% ✓ |
| XGBoost DA | 45-55% (wrong) | 50-55% (true) | 65-75% ✓ |
| RF DA | 45-55% (wrong) | 50-55% (true) | 65-75% ✓ |

**DA** = Directional Accuracy

---

## 🔧 Rollback Plan (If Needed)

If you need to rollback to old code:

### 1. Revert Code Changes
```bash
git revert <commit-hash>
```

### 2. Models Will Continue Working
- New LSTM models (3-feature) are incompatible with old code
- XGBoost/RF models work fine
- Solution: Delete LSTM `*_latest.pkl` files to force old model loading

### 3. Old Models Path
```
ml_service/models/lstm/<SYMBOL>_<timestamp>.pkl
```
Delete only `_latest.pkl` files, keep timestamped ones.

---

## 📋 Deployment Checklist

### Pre-Deployment
- [ ] Read `IMPLEMENTATION_SUMMARY.md`
- [ ] Review `QUICK_START_GUIDE.md`
- [ ] Run `python ml_service/test_directional_accuracy.py`
- [ ] Backup model files (optional)

### Deployment
- [ ] Deploy code changes
- [ ] Monitor first few predictions
- [ ] Check for auto-retrain messages

### Post-Deployment (First 24 Hours)
- [ ] Verify LSTM auto-retrains when loaded
- [ ] Check directional accuracy metrics
- [ ] Confirm no errors in logs
- [ ] Test predictions on 2-3 stocks

### Post-Deployment (First Week)
- [ ] Monitor all stocks as they upgrade
- [ ] Track directional accuracy improvements
- [ ] Verify user-facing predictions are reasonable

---

## 💡 Tips

### Speed Up Migration
If you want all stocks upgraded immediately:
```bash
# Create script to retrain all
python ml_service/batch_retrain_all.py
```

### Monitor Progress
Check how many models have upgraded:
```bash
# Count old LSTM models
find ml_service/models/lstm -name "*_latest.pkl" -exec grep -l "input_size': 1" {} \;

# After upgrade, this should return nothing
```

### Verify Enhanced Features
Check model config in metadata:
```bash
python ml_service/check_model_versions.py <SYMBOL>
```

---

## 🆘 Support

### If Auto-Conversion Fails

1. **Check Error Message**
   ```
   Error: An unexpected error occurred during prediction: ...
   ```
   - Look for specific error details
   - Check if it's reshape error (old model)

2. **Force Manual Retrain**
   ```bash
   python ml_service/predict.py <SYMBOL> --force-retrain
   ```

3. **Check Model Files**
   ```bash
   ls -la ml_service/models/lstm/<SYMBOL>*
   ```
   - Look for `_latest.pkl`
   - Check file timestamps

4. **Clear Cache (Last Resort)**
   ```bash
   # Backup first
   cp -r ml_service/models ml_service/models_backup
   
   # Remove latest files to force retrain
   rm ml_service/models/lstm/*_latest.pkl
   ```

---

## 📞 Questions?

- Technical Details: See `IMPLEMENTATION_SUMMARY.md`
- Quick Start: See `QUICK_START_GUIDE.md`
- Theory: See `DIRECTIONAL_ACCURACY_IMPROVEMENTS.md`

---

**Summary:** Upgrade is seamless with automatic backward compatibility. LSTM models auto-convert on first use. XGBoost/RF continue working immediately.

**Action Required:** None (automatic) or optionally force retrain all for immediate upgrade.

**Risk Level:** Low - automatic handling prevents breaking changes.

---

*Last Updated: 2026-06-07*
*Version: 1.0 (Initial Enhancement Release)*
