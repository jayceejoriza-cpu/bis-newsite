# Blotter Status Fix - TODO Progress Tracker
## Status: ✅ **IN PROGRESS** | Created: 2026-04-10

### 📋 **Approved Plan Steps** (Phased Implementation)

#### **PHASE 1: Backend Fix** `model/update_blotter_status.php` **[✅ COMPLETE]**
- ✅ 1. Added `affected_rows` check (fail if 0)
- ✅ 2. Status normalization `ucwords(strtolower($status))`
- ✅ 3. Verbose JSON: `affected_rows`, `new_status` (DB verified)
- ✅ 4. Enhanced logging (failures + successes w/ user/record)
- ✅ **Test**: Visit blotter.php → Change to 'Settled' → Verify table persists!

#### **PHASE 2: Edit Modal Fix** `model/edit_blotter.php` **[✅ COMPLETE]**
- ✅ 1. Server: `trim($status)` + `empty()` rejection
- ✅ 2. HTML: `<option value="">Select Status</option>` trigger
- ✅ 3. **TEST**: Edit → Step 4 → Save → Status persists!

#### **PHASE 3: Schema Verification & Testing**
- [ ] 1. Check `DESCRIBE blotter_records` (status column type/default)
- [ ] 2. Manual SQL test: `UPDATE ... SET status='Settled'`
- [ ] 3. Test all status transitions in UI

#### **PHASE 4: Polish & Completion**
- [ ] Update `model/edit_blotter.php` status normalization
- [ ] ✅ **attempt_completion**

### 🔍 **Debug Commands Ready**
```
# Run these after Phase 1 to verify schema:
mysql -u root -p bmis -e "DESCRIBE blotter_records;"
mysql -u root -p bmis -e "SELECT id, status FROM blotter_records WHERE status IS NULL OR status='' LIMIT 5;"
```

### 📝 **Notes**
- **Suspected Root Cause**: Case mismatch + no affected_rows check
- **No data loss risk**: Pure bug fix
- **Test Record**: User to provide specific ID if needed

**Next**: Edit `model/update_blotter_status.php` → Test → Mark complete → Phase 2
