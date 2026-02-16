# Profile Avatar Implementation Guide

## Overview
This implementation adds custom profile image/avatar functionality to the user profile system, allowing users to upload and manage their profile pictures.

## Features Implemented

### 1. Database Changes
- Added `profile_image` column to `users` table
- Stores the file path to the user's avatar image

### 2. File Upload System
- **Upload Avatar** (`upload_avatar.php`): Handles new avatar uploads
  - Validates file type (JPG, JPEG, PNG, GIF, WEBP)
  - Validates file size (max 5MB)
  - Generates unique filenames
  - Keeps up to 10 most recent avatars per user (automatically deletes older ones)
  - Logs activity

- **Select Avatar** (`select_avatar.php`): Allows users to select from previously uploaded avatars
  - Validates file ownership
  - Updates database with selected avatar

- **Get Recent Avatars** (`get_recent_avatars.php`): Retrieves the 6 most recent avatar uploads
  - Returns JSON array of avatar paths
  - Sorted by upload time (newest first)

### 3. User Interface
- **Profile Page** (`user-profile.php`):
  - Displays current avatar or default gradient icon
  - "Change Avatar" button with camera icon
  - Modal interface for avatar selection

- **Avatar Modal**:
  - Upload new image section (drag & drop or click to upload)
  - Recent avatars grid (3 columns, 6 most recent)
  - Responsive design (2 columns on mobile)
  - Dark mode compatible

### 4. JavaScript Functionality (`js/user-profile.js`)
- Modal open/close handlers
- File upload with drag & drop support
- AJAX upload to server
- Real-time preview updates
- Toast notifications for success/error messages
- Automatic page reload after successful upload

### 5. Security Features
- File type validation (server-side and client-side)
- File size limits (5MB maximum)
- User ownership verification
- Secure file naming (prevents overwrites)
- `.htaccess` protection in uploads directory

## Installation Steps

### Step 1: Run Database Migration
Execute the SQL migration to add the profile_image column:

```sql
-- Run this in your MySQL database
USE bmis;

ALTER TABLE `users` 
ADD COLUMN `profile_image` VARCHAR(255) DEFAULT NULL COMMENT 'Path to user profile image/avatar' 
AFTER `email`;

CREATE INDEX `idx_profile_image` ON `users` (`profile_image`);
```

Or run the migration file:
```bash
mysql -u your_username -p bmis < database/add_profile_image_column.sql
```

### Step 2: Verify File Structure
Ensure these files exist:
```
/uploads/avatars/              # Avatar storage directory
/uploads/avatars/.htaccess     # Security configuration
/upload_avatar.php             # Upload handler
/get_recent_avatars.php        # Recent avatars API
/select_avatar.php             # Avatar selection handler
/js/user-profile.js            # Frontend JavaScript
/user-profile.php              # Updated profile page
```

### Step 3: Set Directory Permissions
Ensure the uploads directory is writable:
```bash
chmod 755 uploads/avatars
```

### Step 4: Test the Implementation
1. Log in to the system
2. Navigate to User Profile page
3. Click the camera icon on the avatar
4. Upload a test image
5. Verify the avatar displays correctly
6. Test selecting from recent avatars

## File Structure

```
bis-newsite/
├── database/
│   └── add_profile_image_column.sql
├── uploads/
│   └── avatars/
│       ├── .htaccess
│       └── avatar_[user_id]_[timestamp].[ext]
├── js/
│   └── user-profile.js
├── upload_avatar.php
├── get_recent_avatars.php
├── select_avatar.php
└── user-profile.php
```

## Usage

### For Users
1. **Upload New Avatar**:
   - Click the camera icon on your profile picture
   - Click "Upload Image" or drag & drop an image
   - Wait for upload confirmation
   - Page will reload with new avatar

2. **Select Previous Avatar**:
   - Click the camera icon
   - Click on any of the recent avatars shown
   - Avatar will be updated immediately

### For Developers

#### Upload Avatar Endpoint
```php
POST /upload_avatar.php
Content-Type: multipart/form-data

Parameters:
- avatar: File (required)

Response:
{
  "success": true,
  "message": "Profile image updated successfully!",
  "avatar_url": "uploads/avatars/avatar_1_1234567890.jpg"
}
```

#### Get Recent Avatars Endpoint
```php
GET /get_recent_avatars.php

Response:
{
  "success": true,
  "avatars": [
    {
      "path": "uploads/avatars/avatar_1_1234567890.jpg",
      "url": "uploads/avatars/avatar_1_1234567890.jpg",
      "timestamp": 1234567890
    }
  ]
}
```

#### Select Avatar Endpoint
```php
POST /select_avatar.php
Content-Type: application/json

Body:
{
  "avatar_path": "uploads/avatars/avatar_1_1234567890.jpg"
}

Response:
{
  "success": true,
  "message": "Profile image updated successfully!",
  "avatar_url": "uploads/avatars/avatar_1_1234567890.jpg"
}
```

## Customization

### Change Maximum File Size
Edit `upload_avatar.php`:
```php
// Change from 5MB to desired size
$maxSize = 10 * 1024 * 1024; // 10MB
```

### Change Allowed File Types
Edit `upload_avatar.php`:
```php
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
```

### Change Number of Recent Avatars
Edit `get_recent_avatars.php`:
```php
// Change from 6 to desired number
$recentFiles = array_slice($files, 0, 10);
```

## Troubleshooting

### Avatar Not Uploading
1. Check directory permissions: `chmod 755 uploads/avatars`
2. Verify PHP upload settings in `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
3. Check error logs for specific issues

### Avatar Not Displaying
1. Verify file exists in `uploads/avatars/`
2. Check database for correct path
3. Verify `.htaccess` allows image access
4. Check browser console for errors

### Recent Avatars Not Loading
1. Verify `get_recent_avatars.php` is accessible
2. Check JavaScript console for errors
3. Verify user has uploaded avatars previously

## Security Considerations

1. **File Type Validation**: Only image files are allowed
2. **File Size Limits**: Prevents large file uploads
3. **User Ownership**: Users can only access their own avatars
4. **Secure Naming**: Prevents file overwrites and path traversal
5. **Directory Protection**: `.htaccess` prevents unauthorized access
6. **Activity Logging**: All avatar changes are logged

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Future Enhancements

Potential improvements:
1. Image cropping/resizing before upload
2. Avatar deletion functionality
3. Default avatar selection (predefined avatars)
4. Avatar history with restore capability
5. Image optimization (compression)
6. Webcam capture for avatar
7. Social media avatar import

## Support

For issues or questions:
1. Check this documentation
2. Review error logs
3. Test with different browsers
4. Verify file permissions
5. Check database connectivity

## Version History

- **v1.0.0** (2024): Initial implementation
  - Basic upload functionality
  - Recent avatars display
  - Modal interface
  - Dark mode support
