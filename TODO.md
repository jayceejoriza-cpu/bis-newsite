# Progressive Milestone System Implementation

## Plan
1. [x] Update create-resident.php:
   - [x] Wrap Educational Attainment in educationContainer div
   - [x] Wrap Voter Status in voterStatusContainer div (remove adult-only class)
   - [x] Wrap Employment Status in employmentContainer div (remove adult-only class)
   - [x] Wrap Occupation in occupationContainer div (remove adult-only class)
   - [x] Wrap Monthly Income in incomeContainer div (remove adult-only class)
   - [x] Keep adult-only class only on Civil Status, Spouse Name in Step 3

2. [x] Update create-resident.js:
   - [x] Refactor updateMinorStatus() to evaluate milestones separately:
     - [x] Education: Always visible (all ages)
     - [x] Working & Voter: Show if age >= 15
     - [x] Guardian: Show if age < 18
     - [x] Adult (Civil Status/Spouse): Show if age >= 18

3. [x] Test the implementation

## Status: COMPLETED

## Summary of Changes

### PHP Changes (model/create-resident.php):
- Added ID `educationContainer` to Educational Attainment wrapper
- Added ID `employmentContainer` to Employment Status wrapper
- Added ID `occupationContainer` to Occupation wrapper
- Added ID `incomeContainer` to Monthly Income wrapper
- Added ID `voterStatusContainer` to Voter Status wrapper (in Step 6)
- **Removed `.adult-only` class** from Employment, Occupation, Income, and Voter Status containers
- **Kept `.adult-only` class** only on Civil Status, Spouse Name (Step 3) and other truly adult-only fields (4Ps, PhilHealth)

### JavaScript Changes (assets/js/create-resident.js):
- Completely refactored `updateMinorStatus()` function to implement Progressive Milestone System
- Now evaluates each milestone independently:
  1. **Education Milestone (All Ages):** Educational Attainment always visible
  2. **Working & Voter Milestone (Age 15+):** Shows Voter Status, Employment, Occupation, Income
  3. **Guardian Milestone (Age 0-17):** Shows Guardian Section only if age < 18
  4. **Adult Milestone (Age 18+):** Unlocks Civil Status, Spouse Name; hides Guardian section

### Key Behavior:
- **Age 0-4:** Only Education visible (all milestones below 15 hidden, Guardian shown for <18)
- **Age 5-14:** Education visible + Guardian shown (no voter/employment fields)
- **Age 15-17:** Education visible + Voter/Employment/Occupation visible + Guardian shown
- **Age 18+:** Education visible + Voter/Employment visible + Guardian hidden + Civil Status/Spouse unlocked

