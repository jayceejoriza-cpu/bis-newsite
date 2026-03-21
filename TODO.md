# Fix Cursor Issue in Household Edit Mode

## Task: Fix disabled cursor on "Type of Water Source" and "Type of Toilet Facility" when editing in households.php

**Status**: Implemented ✅

### Steps:
- [x] 1. Plan approved by user
- [x] 2. Edit assets/js/households.js - Added explicit enable for fields in editHousehold() (removeAttribute('disabled') for waterSource/toiletFacility)
- [ ] 3. Test: Open households.php?edit=1 (or pick a household ID from table), hover/select fields - should show normal pointer cursor, no not-allowed
- [ ] 4. Update values, save - verify backend accepts changes
- [ ] 5. Complete task

**Changes**:
- assets/js/households.js: Inserted enable code in editHousehold() fetch.then()

**Next**: Test then mark complete.


