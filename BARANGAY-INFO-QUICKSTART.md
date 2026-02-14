# 🚀 Barangay Info - Quick Start Guide

## Step 1: Run the Setup (Choose One Method)

### Method A: Automated Setup (Recommended)
1. Open your browser
2. Navigate to: `http://localhost/bis-newsite/setup_barangay_info.php`
3. Follow the on-screen instructions
4. Click "Go to Barangay Info" when complete

### Method B: Manual Database Setup
1. Open phpMyAdmin
2. Select the `bmis` database
3. Click on "Import" tab
4. Choose file: `database/create_barangay_info_table.sql`
5. Click "Go"

## Step 2: Access the Feature
1. Log in to your system
2. Click on **Settings** in the sidebar
3. Click on **Barangay Info** (first item in submenu)

## Step 3: Update Information
1. Fill in the form fields:
   - Province Name (e.g., "Zambales")
   - Town Name (e.g., "Subic")
   - Barangay Name (e.g., "Barangay Wawandue")
   - Contact Number (e.g., "09191234567")
   - Dashboard Text (optional welcome message)

2. Upload images (optional):
   - **Municipal Logo**: Click "Choose File" under Municipality/City Logo
   - **Barangay Logo**: Click "Choose File" under Barangay Logo
   - **Dashboard Image**: Click "Choose File" under Dashboard Image

3. Preview your images before saving

4. Click **Save Changes**

## ✅ That's It!

Your barangay information is now configured and ready to use!

## 📋 File Upload Guidelines

### Logos (Municipal & Barangay)
- **Format**: JPEG, PNG, GIF, or WebP
- **Max Size**: 5MB
- **Best Size**: 200x200px to 500x500px
- **Aspect**: Square (1:1) recommended

### Dashboard Image
- **Format**: JPEG, PNG, GIF, or WebP
- **Max Size**: 10MB
- **Best Size**: 1920x1080px or higher
- **Aspect**: 16:9 recommended

## 🔍 Verify Installation

Check that these directories exist:
- ✅ `uploads/barangay/logos/`
- ✅ `uploads/barangay/dashboard/`
- ✅ `uploads/barangay/.htaccess`

## 🆘 Need Help?

See the full documentation:
- `BARANGAY-INFO-SETUP.md` - Complete setup guide
- `BARANGAY-INFO-IMPLEMENTATION.md` - Technical details

## 🎯 Quick Tips

1. **Use high-quality images** for better display
2. **Square logos** work best for the logo sections
3. **Preview before saving** to ensure images look good
4. **Old files are automatically deleted** when you upload new ones
5. **All changes are logged** in the activity logs

---

**Ready to start?** Run the setup and begin customizing your barangay information! 🎉
