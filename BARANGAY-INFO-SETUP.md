# Barangay Info Feature - Setup Guide

## Overview
The Barangay Info feature allows administrators to manage barangay information, logos, and branding through a user-friendly interface in the Settings menu.

## Features
- Update Province, Town/City, and Barangay names
- Set contact number
- Configure dashboard welcome text
- Upload and manage Municipal/City logo
- Upload and manage Barangay logo
- Upload and manage Dashboard background image
- Real-time image preview
- Activity logging for all changes

## Installation Steps

### 1. Database Setup
Run the SQL script to create the `barangay_info` table:

```bash
# Using MySQL command line
mysql -u root -p bmis < database/create_barangay_info_table.sql

# Or using phpMyAdmin
# Import the file: database/create_barangay_info_table.sql
```

### 2. Directory Permissions
Ensure the uploads directory has proper write permissions:

**Windows (XAMPP):**
- The directories are already created with proper permissions
- Location: `uploads/barangay/logos/` and `uploads/barangay/dashboard/`

**Linux/Mac:**
```bash
chmod -R 755 uploads/barangay
chown -R www-data:www-data uploads/barangay  # Adjust user/group as needed
```

### 3. Access the Feature
1. Log in to the system
2. Navigate to **Settings** → **Barangay Info**
3. Fill in the required information
4. Upload logos and images (optional)
5. Click **Save Changes**

## File Structure

```
bis-newsite/
├── barangay-info.php              # Main page for managing barangay info
├── save_barangay_info.php         # Backend handler for saving data
├── components/
│   └── sidebar.php                # Updated with new menu item
├── database/
│   └── create_barangay_info_table.sql  # Database schema
└── uploads/
    └── barangay/
        ├── .htaccess              # Security configuration
        ├── logos/                 # Municipal and barangay logos
        └── dashboard/             # Dashboard background images
```

## Database Schema

### Table: `barangay_info`
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key (always 1) |
| province_name | VARCHAR(100) | Province name |
| town_name | VARCHAR(100) | Town/City name |
| barangay_name | VARCHAR(100) | Barangay name |
| contact_number | VARCHAR(20) | Contact number |
| dashboard_text | TEXT | Dashboard welcome text |
| municipal_logo | VARCHAR(255) | Path to municipal logo |
| barangay_logo | VARCHAR(255) | Path to barangay logo |
| dashboard_image | VARCHAR(255) | Path to dashboard image |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |
| updated_by | INT(11) | User ID who updated |

## File Upload Specifications

### Logos (Municipal & Barangay)
- **Accepted formats:** JPEG, PNG, GIF, WebP
- **Maximum size:** 5MB
- **Recommended dimensions:** 200x200px to 500x500px
- **Aspect ratio:** Square (1:1) recommended

### Dashboard Image
- **Accepted formats:** JPEG, PNG, GIF, WebP
- **Maximum size:** 10MB
- **Recommended dimensions:** 1920x1080px or higher
- **Aspect ratio:** 16:9 recommended

## Security Features

1. **Authentication Required:** Only logged-in users can access the page
2. **File Type Validation:** Only image files are accepted
3. **File Size Limits:** Prevents large file uploads
4. **Directory Protection:** .htaccess prevents unauthorized access
5. **Activity Logging:** All changes are logged in activity_logs table
6. **SQL Injection Prevention:** Prepared statements used throughout

## Usage Tips

1. **Logo Quality:** Use high-resolution logos for better display quality
2. **Dashboard Image:** Choose images that don't interfere with text readability
3. **File Names:** Files are automatically renamed with timestamps to prevent conflicts
4. **Old Files:** Previous logos/images are automatically deleted when new ones are uploaded
5. **Preview:** Always check the preview before saving

## Troubleshooting

### Upload Fails
- Check directory permissions (755 for directories, 644 for files)
- Verify file size is within limits
- Ensure file format is supported
- Check PHP upload settings in php.ini:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```

### Images Not Displaying
- Verify file path in database
- Check if file exists in uploads directory
- Ensure .htaccess allows image access
- Clear browser cache

### Permission Denied
- Check web server user has write access to uploads directory
- On Linux: `sudo chown -R www-data:www-data uploads/`
- On Windows/XAMPP: Usually no action needed

## Future Enhancements

Potential features for future versions:
- Image cropping/resizing tool
- Multiple logo variants (light/dark mode)
- Logo usage throughout the system
- Barangay seal/emblem management
- Social media links
- Operating hours configuration
- Map/location integration

## Support

For issues or questions:
1. Check the activity logs for error details
2. Review PHP error logs
3. Verify database connection
4. Ensure all files are properly uploaded

## Changelog

### Version 1.0.0 (Initial Release)
- Basic information management
- Logo upload functionality
- Dashboard image management
- Activity logging
- Security features
- Dark mode support
