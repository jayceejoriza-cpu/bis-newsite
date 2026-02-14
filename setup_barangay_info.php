<?php
/**
 * Barangay Info Setup Script
 * Run this file once to set up the barangay_info table
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Barangay Info Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
        }
        .success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #10b981;
        }
        .error {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ef4444;
        }
        .info {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #3b82f6;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9fafb;
            border-radius: 5px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #2563eb;
        }
        code {
            background-color: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏛️ Barangay Info Setup</h1>";

try {
    // Check if table already exists
    $check_table = $conn->query("SHOW TABLES LIKE 'barangay_info'");
    
    if ($check_table->num_rows > 0) {
        echo "<div class='info'>
                <strong>ℹ️ Table Already Exists</strong><br>
                The <code>barangay_info</code> table already exists in the database.
              </div>";
        
        // Check if there's data
        $check_data = $conn->query("SELECT COUNT(*) as count FROM barangay_info");
        $data = $check_data->fetch_assoc();
        
        if ($data['count'] > 0) {
            echo "<div class='success'>
                    <strong>✅ Data Found</strong><br>
                    The table contains {$data['count']} record(s).
                  </div>";
        } else {
            echo "<div class='info'>
                    <strong>📝 No Data</strong><br>
                    The table exists but contains no data. Inserting default record...
                  </div>";
            
            // Insert default data
            $insert_sql = "INSERT INTO barangay_info 
                (id, province_name, town_name, barangay_name, contact_number, dashboard_text) 
                VALUES 
                (1, 'Zambales', 'Subic', 'Barangay Wawandue', '09191234567', 'TEST')
                ON DUPLICATE KEY UPDATE id=id";
            
            if ($conn->query($insert_sql)) {
                echo "<div class='success'>
                        <strong>✅ Default Data Inserted</strong><br>
                        Default barangay information has been added.
                      </div>";
            }
        }
    } else {
        echo "<div class='step'>
                <strong>Step 1:</strong> Creating <code>barangay_info</code> table...
              </div>";
        
        // Create table
        $create_table_sql = "CREATE TABLE IF NOT EXISTS `barangay_info` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `province_name` VARCHAR(100) NOT NULL DEFAULT 'Province Name',
          `town_name` VARCHAR(100) NOT NULL DEFAULT 'Town/City Name',
          `barangay_name` VARCHAR(100) NOT NULL DEFAULT 'Barangay Name',
          `contact_number` VARCHAR(20) DEFAULT NULL,
          `dashboard_text` TEXT DEFAULT NULL,
          `municipal_logo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to municipal/city logo',
          `barangay_logo` VARCHAR(255) DEFAULT NULL COMMENT 'Path to barangay logo',
          `dashboard_image` VARCHAR(255) DEFAULT NULL COMMENT 'Path to dashboard background image',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `updated_by` INT(11) DEFAULT NULL COMMENT 'User ID who last updated',
          PRIMARY KEY (`id`),
          CONSTRAINT `fk_barangay_info_user` 
            FOREIGN KEY (`updated_by`) 
            REFERENCES `users` (`id`) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barangay configuration and settings'";
        
        if ($conn->query($create_table_sql)) {
            echo "<div class='success'>
                    <strong>✅ Table Created</strong><br>
                    The <code>barangay_info</code> table has been created successfully.
                  </div>";
            
            echo "<div class='step'>
                    <strong>Step 2:</strong> Inserting default data...
                  </div>";
            
            // Insert default data
            $insert_sql = "INSERT INTO barangay_info 
                (id, province_name, town_name, barangay_name, contact_number, dashboard_text) 
                VALUES 
                (1, 'Zambales', 'Subic', 'Barangay Wawandue', '09191234567', 'TEST')";
            
            if ($conn->query($insert_sql)) {
                echo "<div class='success'>
                        <strong>✅ Default Data Inserted</strong><br>
                        Default barangay information has been added.
                      </div>";
            } else {
                echo "<div class='error'>
                        <strong>❌ Error Inserting Data</strong><br>
                        " . $conn->error . "
                      </div>";
            }
        } else {
            echo "<div class='error'>
                    <strong>❌ Error Creating Table</strong><br>
                    " . $conn->error . "
                  </div>";
        }
    }
    
    // Check upload directories
    echo "<div class='step'>
            <strong>Step 3:</strong> Checking upload directories...
          </div>";
    
    $dirs_to_check = [
        'uploads/barangay',
        'uploads/barangay/logos',
        'uploads/barangay/dashboard'
    ];
    
    $all_dirs_exist = true;
    foreach ($dirs_to_check as $dir) {
        if (is_dir($dir)) {
            echo "<div class='success'>
                    ✅ Directory exists: <code>$dir</code>
                  </div>";
        } else {
            echo "<div class='error'>
                    ❌ Directory missing: <code>$dir</code>
                  </div>";
            $all_dirs_exist = false;
        }
    }
    
    if ($all_dirs_exist) {
        echo "<div class='success'>
                <strong>✅ All Directories Ready</strong><br>
                Upload directories are properly configured.
              </div>";
    } else {
        echo "<div class='info'>
                <strong>ℹ️ Create Missing Directories</strong><br>
                Please create the missing directories manually or they will be created automatically when you upload files.
              </div>";
    }
    
    echo "<div class='step'>
            <h3>✅ Setup Complete!</h3>
            <p>The Barangay Info feature is now ready to use.</p>
            <p><strong>Next Steps:</strong></p>
            <ol>
                <li>Log in to the system</li>
                <li>Navigate to <strong>Settings → Barangay Info</strong></li>
                <li>Update the barangay information</li>
                <li>Upload logos and images</li>
            </ol>
            <a href='barangay-info.php' class='btn'>Go to Barangay Info</a>
            <a href='index.php' class='btn' style='background-color: #6b7280;'>Go to Dashboard</a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
            <strong>❌ Setup Failed</strong><br>
            Error: " . $e->getMessage() . "
          </div>";
}

echo "    </div>
</body>
</html>";

$conn->close();
?>
