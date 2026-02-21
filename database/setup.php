<?php
/**
 * Database Setup Script
 * 
 * This script creates the database and tables for the Barangay Management System
 * Run this file once to set up the database structure
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bmis';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// HTML Header
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Barangay Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .status-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .status-icon {
            font-size: 20px;
            margin-right: 10px;
        }
        
        .step {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .step-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .step-detail {
            color: #666;
            font-size: 14px;
            margin-left: 25px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .config-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 13px;
        }
        
        .config-info strong {
            color: #667eea;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏘️ Barangay Management System</h1>
        <p class="subtitle">Database Setup & Installation</p>

<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    echo '<div class="status-box info">';
    echo '<span class="status-icon">⚙️</span>';
    echo '<strong>Starting database setup...</strong>';
    echo '</div>';

    try {
        // Step 1: Connect to MySQL server (without database)
        echo '<div class="step">';
        echo '<div class="step-title">📡 Step 1: Connecting to MySQL Server</div>';
        
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        echo '<div class="step-detail">✅ Successfully connected to MySQL server</div>';
        echo '</div>';

        // Step 2: Create database
        echo '<div class="step">';
        echo '<div class="step-title">🗄️ Step 2: Creating Database</div>';
        
        $sql = "CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if ($conn->query($sql) === TRUE) {
            echo '<div class="step-detail">✅ Database "' . $database . '" created successfully</div>';
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
        
        $conn->select_db($database);
        echo '</div>';

        // Step 3: Read and execute schema file
        echo '<div class="step">';
        echo '<div class="step-title">📋 Step 3: Creating Tables</div>';
        
        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("Schema file not found: schema.sql");
        }
        
        $schema = file_get_contents($schemaFile);
        
        // Split SQL statements
        $statements = array_filter(
            array_map('trim', explode(';', $schema)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       !preg_match('/^\/\*/', $stmt);
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($statements as $statement) {
            if ($conn->multi_query($statement . ';')) {
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                $successCount++;
            } else {
                $errorCount++;
                echo '<div class="step-detail">⚠️ Warning: ' . $conn->error . '</div>';
            }
        }
        
        echo '<div class="step-detail">✅ Tables created successfully</div>';
        echo '<div class="step-detail">📊 Executed ' . $successCount . ' SQL statements</div>';
        echo '</div>';

        // Step 4: Verify tables
        echo '<div class="step">';
        echo '<div class="step-title">✔️ Step 4: Verifying Installation</div>';
        
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo '<div class="step-detail">✅ Found ' . count($tables) . ' tables:</div>';
        echo '<div class="step-detail" style="margin-left: 40px;">';
        foreach ($tables as $table) {
            echo '• ' . $table . '<br>';
        }
        echo '</div>';
        echo '</div>';

        // Success message
        echo '<div class="status-box success">';
        echo '<span class="status-icon">🎉</span>';
        echo '<strong>Database setup completed successfully!</strong><br>';
        echo 'Your Barangay Management System database is ready to use.';
        echo '</div>';

        echo '<div style="text-align: center; margin-top: 30px;">';
        echo '<a href="../model/create-resident.php" class="btn btn-success">Go to Create Resident</a> ';
        echo '<a href="../residents.php" class="btn">View Residents</a>';
        echo '</div>';

        $conn->close();

    } catch (Exception $e) {
        echo '<div class="status-box error">';
        echo '<span class="status-icon">❌</span>';
        echo '<strong>Setup Failed!</strong><br>';
        echo 'Error: ' . $e->getMessage();
        echo '</div>';
        
        echo '<div style="text-align: center; margin-top: 20px;">';
        echo '<button onclick="location.reload()" class="btn">Try Again</button>';
        echo '</div>';
    }

} else {
    // Show setup form
    ?>
    
    <div class="status-box info">
        <span class="status-icon">ℹ️</span>
        <strong>Welcome to the Database Setup Wizard</strong><br>
        This wizard will create the database and tables needed for the Barangay Management System.
    </div>

    <div class="config-info">
        <strong>Current Configuration:</strong><br>
        Host: <?php echo $host; ?><br>
        Database: <?php echo $database; ?><br>
        Username: <?php echo $username; ?><br>
        Password: <?php echo $password ? '********' : '(empty)'; ?>
    </div>

    <div class="status-box warning">
        <span class="status-icon">⚠️</span>
        <strong>Before you proceed:</strong><br>
        • Make sure MySQL/MariaDB is running<br>
        • Verify the database credentials in config.php<br>
        • Ensure you have proper permissions to create databases<br>
        • This will create a new database named "<?php echo $database; ?>"
    </div>

    <h3 style="margin: 30px 0 15px 0; color: #333;">What will be created:</h3>
    
    <div class="step">
        <div class="step-title">📊 Tables</div>
        <div class="step-detail">
            • <strong>residents</strong> - Main resident information<br>
            • <strong>emergency_contacts</strong> - Emergency contact details<br>
            • <strong>users</strong> - System users (optional)<br>
            • <strong>audit_logs</strong> - Activity tracking (optional)
        </div>
    </div>

    <div class="step">
        <div class="step-title">👁️ Views</div>
        <div class="step-detail">
            • <strong>vw_residents_complete</strong> - Complete resident info with contacts<br>
            • <strong>vw_pending_residents</strong> - Residents pending verification<br>
            • <strong>vw_resident_statistics</strong> - Statistical summary
        </div>
    </div>

    <div class="step">
        <div class="step-title">✨ Features</div>
        <div class="step-detail">
            • Verification status tracking (Pending/Verified/Rejected)<br>
            • Multiple emergency contacts per resident<br>
            • Comprehensive health and family information<br>
            • Government program tracking (4Ps, PhilHealth, etc.)<br>
            • Audit trail for changes<br>
            • Default admin user (username: admin, password: admin123)
        </div>
    </div>

    <form method="POST" style="text-align: center; margin-top: 30px;">
        <button type="submit" name="setup" class="btn">
            🚀 Start Database Setup
        </button>
    </form>

    <?php
}
?>

        <div class="footer">
            <strong>Barangay Management System</strong> v1.0.0<br>
            Database Setup Wizard
        </div>
    </div>
</body>
</html>
