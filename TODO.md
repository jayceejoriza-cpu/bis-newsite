# Edit Resident - Make Identical to Create Resident

## Tasks - ALL COMPLETED

### model/edit-resident.php
- [x] 1. Update CSS style block to match create-resident.php phone-input-group styles
- [x] 2. Add Household Information step to progress steps (6 steps total)
- [x] 3. Step 1 - Religion: change input[text] to select dropdown with all options
- [x] 4. Step 1 - Sex: remove "Other" option
- [x] 5. Step 2 - Contact: replace currentAddress textarea with purok select + streetName input
- [x] 6. Step 3 - Civil Status: "Widowed" → "Widow/er", "Divorced" → "Cohabitation"
- [x] 7. Step 3 - Mother label: "Mother's Maiden Name"
- [x] 8. Step 4 - Insert full Household Information step
- [x] 9. Step 5 - Education: remove monthlyIncome field
- [x] 10. Add bootstrap.bundle.min.js script include
- [x] 11. Review Modal: add Household section + confirmation checkbox in footer

### assets/js/edit-resident.js
- [x] 12. totalSteps = 6
- [x] 13. Update populateForm() for new fields (purok, streetName, religion select, household)
- [x] 14. Update populateReviewModal() to full 6-section implementation
- [x] 15. Implement webcam functions properly
- [x] 16. Add household functions (initializeHouseholdInfo, searchHouseholds, selectHousehold, clearSelectedHousehold)
- [x] 17. Add confirmation checkbox handling
- [x] 18. Call initializeHouseholdInfo() in DOMContentLoaded
