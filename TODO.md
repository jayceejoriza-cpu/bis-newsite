# Household Information - Step 4 Replacement

## Tasks

- [x] Plan approved
- [x] Step 1: Create `model/search_households_for_resident.php` (search endpoint for "No" case)
- [x] Step 2: Edit `model/create-resident.php`
  - [x] Update Step 4 progress step (icon + label)
  - [x] Replace Step 4 form content with Household Information
  - [x] Update Review Modal section (Emergency Contact → Household Information)
- [x] Step 3: Edit `assets/js/create-resident.js`
  - [x] Remove Emergency Contacts Management section
  - [x] Remove `initializeEmergencyContacts()` call
  - [x] Remove emergency contact phone formatting
  - [x] Add `initializeHouseholdInfo()` and related functions
  - [x] Update `populateReviewModal()` for household info
- [x] Step 4: Edit `model/save_resident.php`
  - [x] Add household saving logic (Yes = create household, No = add as member)
- [ ] Step 5: Test the full flow
