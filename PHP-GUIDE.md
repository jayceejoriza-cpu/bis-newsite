# PHP Template Guide

## Overview

The Barangay Management System template is now available in **PHP version** with server-side component inclusion and dynamic configuration.

## 🎯 Why PHP Version?

### Advantages over HTML versions:
- ✅ **Server-side includes** - Reusable components without JavaScript
- ✅ **Configuration management** - Centralized settings
- ✅ **Dynamic content** - Easy to connect to databases
- ✅ **Better performance** - No client-side component loading
- ✅ **SEO friendly** - Fully rendered HTML
- ✅ **Production ready** - Scalable architecture

## 📁 File Structure

```
barangay-dashboard/
├── index.php                   # Main dashboard page (PHP)
├── config.php                  # Configuration file
├── components/                 # PHP components
│   ├── sidebar.php            # Sidebar navigation
│   ├── header.php             # Top header
│   └── dashboard.php          # Dashboard content
├── css/
│   └── style.css              # Styles (same as HTML version)
├── js/
│   └── script.js              # JavaScript (same as HTML version)
├── README.md
├── QUICKSTART.md
└── PHP-GUIDE.md               # This file
```

## 🚀 Getting Started

### Requirements
- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- XAMPP, WAMP, or MAMP (recommended for Windows/Mac)

### Installation

#### Option 1: Using XAMPP (Recommended)

1. **Install XAMPP** (if not already installed)
   - Download from: https://www.apachefriends.org/

2. **Copy files to htdocs**
   ```
   Copy all files to: C:/xampp/htdocs/bis-newsite/
   ```

3. **Start Apache**
   - Open XAMPP Control Panel
   - Click "Start" next to Apache

4. **Access the dashboard**
   ```
   Open browser: http://localhost/bis-newsite/index.php
   ```

#### Option 2: Using PHP Built-in Server

```bash
# Navigate to project directory
cd c:/xampp/htdocs/bis-newsite

# Start PHP server
php -S localhost:8000

# Access in browser
http://localhost:8000/index.php
```

## ⚙️ Configuration

### config.php

This file contains all configuration settings:

```php
<?php
// Site Configuration
define('SITE_NAME', 'Barangay Management System');
define('SITE_VERSION', '1.0.0');
define('BARANGAY_NAME', 'Your Barangay Name');

// Dashboard Statistics
$dashboard_stats = [
    'total_residents' => 16798,
    'total_households' => 1,
    'pending_requests' => 2,
];
?>
```

### Customizing Configuration

**Change Barangay Name:**
```php
define('BARANGAY_NAME', 'Barangay San Miguel');
```

**Update Statistics:**
```php
$dashboard_stats = [
    'total_residents' => 25000,
    'total_households' => 5000,
    'pending_requests' => 15,
];
```

**Set Timezone:**
```php
date_default_timezone_set('Asia/Manila');
```

## 🧩 Components

### 1. Sidebar Component (components/sidebar.php)

**Features:**
- Dynamic barangay name from config
- Menu items with links
- Active page detection
- Version display from config

**Usage:**
```php
<?php include 'components/sidebar.php'; ?>
```

**Customization:**
```php
// In sidebar.php, add new menu item:
<li class="nav-item">
    <a href="your-page.php" class="nav-link">
        <i class="fas fa-your-icon"></i>
        <span>Your Menu Item</span>
    </a>
</li>
```

### 2. Header Component (components/header.php)

**Features:**
- Mobile menu toggle
- Real-time date/time display
- Theme toggle button
- User profile section

**Usage:**
```php
<?php include 'components/header.php'; ?>
```

### 3. Dashboard Component (components/dashboard.php)

**Features:**
- Dynamic statistics from config
- Chart containers
- Formatted numbers

**Usage:**
```php
<?php include 'components/dashboard.php'; ?>
```

**Dynamic Data:**
```php
// Statistics are pulled from config.php
<h3 class="stat-value">
    <?php echo formatNumber($dashboard_stats['total_residents']); ?>
</h3>
```

## 🔧 Helper Functions

### formatNumber()
Formats numbers with thousand separators:
```php
echo formatNumber(16798); // Output: 16,798
```

### getCurrentPage()
Gets current page filename:
```php
$current = getCurrentPage(); // Returns: index.php
```

### isActivePage()
Checks if page is active:
```php
<li class="nav-item <?php echo isActivePage('index.php'); ?>">
```

### getGreeting()
Returns time-based greeting:
```php
echo getGreeting(); // Output: Good Morning/Afternoon/Evening
```

## 📄 Creating New Pages

### Step 1: Create New PHP File

Create `users.php`:
```php
<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
            <h1 class="page-title">Users Management</h1>
            <!-- Your content here -->
        </div>
    </main>
    
    <script src="js/script.js"></script>
</body>
</html>
```

### Step 2: Update Sidebar Links

Links in `sidebar.php` already point to PHP files:
```php
<a href="users.php" class="nav-link">
```

## 🗄️ Database Integration (Future)

### Connecting to Database

Update `config.php`:
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'barangay_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Create connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
```

### Fetching Data

Example in `dashboard.php`:
```php
<?php
// Fetch total residents from database
$stmt = $pdo->query("SELECT COUNT(*) as total FROM residents");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_residents = $result['total'];
?>

<h3 class="stat-value"><?php echo formatNumber($total_residents); ?></h3>
```

## 🔐 Security Best Practices

### 1. Input Validation
```php
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
```

### 2. SQL Injection Prevention
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
```

### 3. XSS Protection
```php
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### 4. CSRF Protection
```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}
```

## 📊 Working with Charts

Charts use the same JavaScript as HTML version. To make them dynamic:

### Create API Endpoint

`api/get-population-data.php`:
```php
<?php
header('Content-Type: application/json');
require_once '../config.php';

// Fetch data from database
$stmt = $pdo->query("SELECT year, population FROM population_data ORDER BY year");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
```

### Update JavaScript

In `js/script.js`:
```javascript
// Fetch data from API
fetch('api/get-population-data.php')
    .then(response => response.json())
    .then(data => {
        // Use data in chart
        const years = data.map(item => item.year);
        const population = data.map(item => item.population);
        
        // Create chart with fetched data
        // ... chart code
    });
```

## 🎨 Customization Examples

### Change Theme Colors

In `config.php`, add:
```php
define('PRIMARY_COLOR', '#3b82f6');
define('SECONDARY_COLOR', '#10b981');
```

In `index.php`:
```php
<style>
:root {
    --primary-color: <?php echo PRIMARY_COLOR; ?>;
    --secondary-color: <?php echo SECONDARY_COLOR; ?>;
}
</style>
```

### Dynamic Menu from Database

In `config.php`:
```php
// Fetch menu items from database
$menu_query = $pdo->query("SELECT * FROM menu_items ORDER BY sort_order");
$menu_items = $menu_query->fetchAll(PDO::FETCH_ASSOC);
```

In `sidebar.php`:
```php
<?php foreach ($menu_items as $item): ?>
    <li class="nav-item">
        <a href="<?php echo $item['url']; ?>" class="nav-link">
            <i class="fas fa-<?php echo $item['icon']; ?>"></i>
            <span><?php echo $item['title']; ?></span>
        </a>
    </li>
<?php endforeach; ?>
```

## 🚀 Deployment

### Production Checklist

1. **Disable Error Display**
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Update Database Credentials**
   ```php
   define('DB_HOST', 'your-production-host');
   define('DB_NAME', 'your-production-db');
   define('DB_USER', 'your-production-user');
   define('DB_PASS', 'your-secure-password');
   ```

3. **Update Base URL**
   ```php
   define('BASE_URL', 'https://yourdomain.com/');
   ```

4. **Enable HTTPS**
   - Get SSL certificate
   - Force HTTPS in .htaccess

5. **Set Proper Permissions**
   ```bash
   chmod 644 *.php
   chmod 755 components/
   ```

## 🔄 Version Comparison

| Feature | HTML | HTML+JS Modular | PHP |
|---------|------|-----------------|-----|
| Component Reuse | ❌ | ✅ | ✅ |
| Server Required | ❌ | ✅ | ✅ |
| Database Ready | ❌ | ❌ | ✅ |
| SEO Friendly | ✅ | ⚠️ | ✅ |
| Performance | ⭐⭐⭐ | ⭐⭐ | ⭐⭐⭐ |
| Scalability | ⭐ | ⭐⭐ | ⭐⭐⭐ |
| Best For | Prototypes | SPAs | Production |

## 🐛 Troubleshooting

### Issue: Blank Page

**Solution:**
```php
// Enable error display temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Issue: Components Not Loading

**Solution:**
```php
// Check file paths
echo __DIR__; // Shows current directory
echo COMPONENTS_PATH; // Shows components path
```

### Issue: Database Connection Failed

**Solution:**
```php
// Test connection
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

## 📚 Additional Resources

- **PHP Manual:** https://www.php.net/manual/
- **PDO Tutorial:** https://www.php.net/manual/en/book.pdo.php
- **Security Guide:** https://www.php.net/manual/en/security.php

## 🎓 Next Steps

1. **Add Authentication**
   - User login/logout
   - Session management
   - Role-based access

2. **Connect Database**
   - Create database schema
   - Implement CRUD operations
   - Add data validation

3. **Add More Pages**
   - Users management
   - Reports generation
   - Settings panel

4. **Enhance Features**
   - File uploads
   - PDF generation
   - Email notifications

---

**Version:** 1.0.0 (PHP)  
**Last Updated:** January 2025  
**Template Type:** PHP with Server-Side Includes
