# Duplicate Resident Prevention Guide

## Overview
This guide explains how the system prevents duplicate resident entries in your Barangay Management Information System (BMIS).

## How It Works

### 1. **Backend Validation (Server-Side)**
The system performs comprehensive duplicate checks in `save_resident.php` before saving any resident data:

#### Duplicate Detection Criteria:
1. **Name + Date of Birth**: Checks if a resident with the same first name, last name, and date of birth already exists
2. **Mobile Number**: Ensures mobile numbers are unique across all residents
3. **Email Address**: Ensures email addresses are unique (if provided)
4. **Philhealth ID**: Ensures Philhealth IDs are unique (if provided)

#### How It Blocks Duplicates:
- When you try to create a resident, the system checks all four criteria
- If ANY match is found, the system **completely blocks** the submission
- An error message is displayed showing:
  - What field is duplicated
  - The existing resident's name and ID
  - Example: "This mobile number is already registered to another resident: Juan Dela Cruz (ID: W-00123)"

### 2. **Database Constraints**
The database has unique indexes to enforce data integrity:

```sql
-- Mobile number must be unique
ALTER TABLE residents ADD UNIQUE INDEX idx_unique_mobile (mobile_number);

-- Email must be unique (allows multiple NULLs)
ALTER TABLE residents ADD UNIQUE INDEX idx_unique_email (email);

-- Philhealth ID must be unique (allows multiple NULLs)
ALTER TABLE residents ADD UNIQUE INDEX idx_unique_philhealth (philhealth_id);

-- Composite index for faster name + DOB lookups
ALTER TABLE residents ADD INDEX idx_name_dob (first_name, last_name, date_of_birth);
```

### 3. **Real-Time Duplicate Checking (Optional - Frontend)**
For enhanced user experience, you can implement real-time checking as users type:
- Uses `check_duplicate_resident.php` API endpoint
- Shows warnings before form submission
- Provides immediate feedback to users

## Implementation Steps

### Step 1: Run Database Migration
Execute the SQL migration to add unique constraints:

```bash
# Using MySQL command line
mysql -u your_username -p bmis < database/add_duplicate_prevention.sql

# Or using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select 'bmis' database
# 3. Go to SQL tab
# 4. Copy and paste contents of database/add_duplicate_prevention.sql
# 5. Click 'Go'
```

**Important**: Before running the migration, check for existing duplicates:

```sql
-- Find duplicate mobile numbers
SELECT mobile_number, COUNT(*) as count 
FROM residents 
GROUP BY mobile_number 
HAVING count > 1;

-- Find duplicate emails
SELECT email, COUNT(*) as count 
FROM residents 
WHERE email IS NOT NULL AND email != ''
GROUP BY email 
HAVING count > 1;

-- Find duplicate Philhealth IDs
SELECT philhealth_id, COUNT(*) as count 
FROM residents 
WHERE philhealth_id IS NOT NULL AND philhealth_id != ''
GROUP BY philhealth_id 
HAVING count > 1;

-- Find duplicate name + DOB combinations
SELECT first_name, last_name, date_of_birth, COUNT(*) as count 
FROM residents 
GROUP BY first_name, last_name, date_of_birth 
HAVING count > 1;
```

If duplicates exist, clean them up before running the migration.

### Step 2: Verify Backend Implementation
The duplicate prevention is already implemented in `save_resident.php`. No additional changes needed.

### Step 3: Test the System
Test various scenarios:

1. **Test Name + DOB Duplicate**:
   - Create a resident: "Juan Dela Cruz", DOB: 1990-01-01
   - Try to create another with same name and DOB
   - Expected: Error message blocking the creation

2. **Test Mobile Number Duplicate**:
   - Create a resident with mobile: +63 912 345 6789
   - Try to create another with same mobile number
   - Expected: Error message showing who owns that number

3. **Test Email Duplicate**:
   - Create a resident with email: juan@example.com
   - Try to create another with same email
   - Expected: Error message showing who owns that email

4. **Test Philhealth ID Duplicate**:
   - Create a resident with Philhealth ID: 12-345678901-2
   - Try to create another with same Philhealth ID
   - Expected: Error message showing who owns that ID

5. **Test Edit Mode**:
   - Edit an existing resident
   - Keep the same mobile number
   - Expected: Should save successfully (excludes self from duplicate check)

## Error Messages

When a duplicate is detected, users will see clear error messages:

### Name + DOB Duplicate:
```
A resident with the same name and date of birth already exists (ID: W-00123). 
Please verify the information.
```

### Mobile Number Duplicate:
```
This mobile number is already registered to another resident: Juan Dela Cruz (ID: W-00123)
```

### Email Duplicate:
```
This email address is already registered to another resident: Juan Dela Cruz (ID: W-00123)
```

### Philhealth ID Duplicate:
```
This Philhealth ID is already registered to another resident: Juan Dela Cruz (ID: W-00123)
```

## Special Cases

### 1. Edit Mode
When editing an existing resident:
- The system excludes the current resident from duplicate checks
- You can keep the same mobile number, email, etc.
- Only checks against OTHER residents

### 2. Deceased Residents
- Duplicate checks exclude residents with `activity_status = 'Deceased'`
- This allows reusing mobile numbers and emails from deceased residents

### 3. Case Sensitivity
- Name comparisons are **case-insensitive**
- "Juan Dela Cruz" = "JUAN DELA CRUZ" = "juan dela cruz"

### 4. NULL Values
- Multiple residents can have NULL/empty email addresses
- Multiple residents can have NULL/empty Philhealth IDs
- Mobile number is always required and must be unique

## API Endpoint for Real-Time Checking

### Endpoint: `check_duplicate_resident.php`

**Method**: POST

**Parameters**:
- `firstName` (string): First name to check
- `lastName` (string): Last name to check
- `dateOfBirth` (string): Date of birth (YYYY-MM-DD)
- `mobileNumber` (string): Mobile number to check
- `email` (string, optional): Email to check
- `philhealthId` (string, optional): Philhealth ID to check
- `residentId` (int, optional): Current resident ID (for edit mode)

**Response**:
```json
{
  "success": true,
  "has_duplicates": true,
  "duplicates": [
    {
      "type": "name_dob",
      "reason": "Same name and date of birth",
      "severity": "high",
      "data": {
        "id": 123,
        "resident_id": "W-00123",
        "first_name": "Juan",
        "last_name": "Dela Cruz",
        "date_of_birth": "1990-01-01",
        "mobile_number": "+63 912 345 6789",
        "email": "juan@example.com"
      }
    }
  ],
  "message": "Potential duplicate resident(s) found"
}
```

## Troubleshooting

### Problem: Migration fails with "Duplicate entry" error
**Solution**: You have existing duplicates. Run the duplicate detection queries above and clean up the data first.

### Problem: Can't create resident even though no duplicate exists
**Solution**: 
1. Check if the resident was marked as 'Deceased' - deceased residents are excluded from checks
2. Verify the exact spelling and date format
3. Check database logs for the actual error

### Problem: Want to allow intentional duplicates
**Solution**: The current system completely blocks duplicates. To allow overrides:
1. Add an `override_duplicate` parameter to the form
2. Modify `save_resident.php` to skip checks when override is true
3. Add admin permission check for override capability

## Benefits

✅ **Data Integrity**: Prevents accidental duplicate entries
✅ **Data Quality**: Ensures unique contact information
✅ **User-Friendly**: Clear error messages guide users
✅ **Performance**: Database indexes speed up duplicate checks
✅ **Comprehensive**: Checks multiple criteria (name, mobile, email, Philhealth)
✅ **Smart**: Excludes deceased residents and handles edit mode correctly

## Maintenance

### Regular Checks
Run these queries monthly to ensure data quality:

```sql
-- Check for any duplicates that might have slipped through
SELECT 
    first_name, 
    last_name, 
    date_of_birth, 
    COUNT(*) as count,
    GROUP_CONCAT(resident_id) as resident_ids
FROM residents 
WHERE activity_status != 'Deceased'
GROUP BY first_name, last_name, date_of_birth 
HAVING count > 1;
```

### Backup Before Migration
Always backup your database before running the migration:

```bash
mysqldump -u your_username -p bmis > bmis_backup_$(date +%Y%m%d).sql
```

## Summary

The duplicate prevention system is now **ACTIVE** and will:
1. ✅ Block residents with same name + date of birth
2. ✅ Block duplicate mobile numbers
3. ✅ Block duplicate email addresses
4. ✅ Block duplicate Philhealth IDs
5. ✅ Show clear error messages
6. ✅ Work in both create and edit modes
7. ✅ Exclude deceased residents from checks

**No duplicates can be created** - the system completely blocks them at the database and application level.
