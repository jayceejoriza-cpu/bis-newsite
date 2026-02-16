# Test Files Directory

This directory contains testing scripts for the Barangay Information System.

## Security

⚠️ **IMPORTANT**: These test files are protected and require:
- Admin authentication (must be logged in as Admin)
- Localhost access only (configured in `.htaccess`)

## Available Tests

### 1. test_duplicate_prevention.php
**Purpose**: Comprehensive system-wide duplicate prevention testing

**What it tests**:
- Database connection
- Existing duplicates in the database
- Database indexes verification
- Duplicate check API functionality
- Database statistics

**When to run**:
- After initial setup
- After database migrations
- When troubleshooting duplicate issues
- Periodic data integrity checks

**How to run**:
```
http://localhost/bis-newsite/tests/test_duplicate_prevention.php
```

### 2. test_duplicate_blocking.php
**Purpose**: Detailed duplicate blocking mechanism testing

**What it tests**:
- Name + Date of Birth duplicate detection
- Mobile number duplicate detection
- Email address duplicate detection
- Database constraint enforcement
- Case-insensitive matching
- Edit mode functionality (allows same resident to keep their data)

**When to run**:
- After implementing duplicate prevention features
- When debugging duplicate detection issues
- Before production deployment
- After code changes to duplicate prevention logic

**How to run**:
```
http://localhost/bis-newsite/tests/test_duplicate_blocking.php
```

## Test Results

Both scripts provide detailed output with:
- ✅ PASSED: Feature working correctly
- ❌ FAILED: Feature not working as expected
- ⚠️ WARNING: Potential issues detected

## Access Requirements

1. **Authentication**: Must be logged in as Admin user
2. **Network**: Localhost access only (127.0.0.1 or ::1)
3. **Session**: Valid PHP session with Admin role

## Troubleshooting

### "Unauthorized Access" Error
- Ensure you're logged in as an Admin user
- Check your session is active
- Verify `auth_check.php` is working correctly

### ".htaccess" Blocking Access
- Confirm you're accessing from localhost
- Check Apache `mod_headers` is enabled
- Verify `.htaccess` is being processed

### Database Connection Errors
- Check `config.php` settings
- Verify database credentials
- Ensure MySQL service is running

## Maintenance

These test files should be:
- ✅ Kept in version control
- ✅ Updated when features change
- ✅ Run before major deployments
- ❌ Never exposed in production without authentication
- ❌ Never deleted without team approval

## Production Deployment

When deploying to production:
1. Keep the `/tests/` directory
2. Ensure `.htaccess` is properly configured
3. Restrict access to admin IPs only
4. Consider adding additional authentication layers
5. Monitor access logs for unauthorized attempts

---

**Last Updated**: 2025-02-16
**Maintained By**: Development Team
