# Fix Certificate Resident Fetch - TODO

## Task
Fix the fetch resident functionality for:
- certificate-barangayclearance.php
- certificate-brgybusinessclearance.php  
- certificate-businesspermit.php

## Issues Identified
1. Missing URL parameter retrieval (business_name, business_address, nature)
2. Business details not displayed in certificates
3. Duplicate catch block in certificate-businesspermit.php

## Steps to Completed
- [x] 1. Fix certificate-businesspermit.php - Added business parameter retrieval and removed duplicate catch block
- [x] 2. Fix certificate-brgybusinessclearance.php - Added business parameter retrieval
- [x] 3. certificate-barangayclearance.php - Already has correct purpose parameter (no changes needed)

