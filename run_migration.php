<?php
/**
 * Run Database Migration Script
 * This script executes the duplicate prevention migration
 */

require_once 'config.php';

echo "==============================================\n";
echo "DATABASE MIGRATION: DUPLICATE PREVENTION\n";
echo "==============================================\n\n";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected to database: " . DB_NAME . "\n\n";

// Read the migration file
$migrationFile = 'database/add_duplicate_prevention.sql';
if (!file_exists($migrationFile)) {
    die("❌ Migration file not found: " . $migrationFile . "\n");
}

$sql = file_get_contents($migrationFile);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt) &&
               !preg_match('/^USE/', $stmt);
    }
);

echo "Found " . count($statements) . " SQL statements to execute\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;
    
    // Extract index name for display
    if (preg_match('/ADD\s+(UNIQUE\s+)?INDEX\s+`?(\w+)`?/i', $statement, $matches)) {
        $indexName = $matches[2];
        echo "Creating index: " . $indexName . "... ";
        
        if ($conn->query($statement)) {
            echo "✅ Success\n";
            $successCount++;
        } else {
            // Check if error is because index already exists
            if (strpos($conn->error, 'Duplicate key name') !== false) {
                echo "⚠️  Already exists (skipped)\n";
                $successCount++;
            } else {
                echo "❌ Failed: " . $conn->error . "\n";
                $errorCount++;
            }
        }
    }
}

echo "\n";
echo "==============================================\n";
echo "MIGRATION SUMMARY\n";
echo "==============================================\n";
echo "Successful: " . $successCount . "\n";
echo "Errors: " . $errorCount . "\n";

if ($errorCount == 0) {
    echo "\n✅ Migration completed successfully!\n\n";
    
    // Verify indexes
    echo "Verifying indexes...\n";
    $result = $conn->query("SHOW INDEXES FROM residents WHERE Key_name IN ('idx_unique_mobile', 'idx_unique_email', 'idx_unique_philhealth', 'idx_name_dob')");
    echo "Indexes found: " . $result->num_rows . " / 4\n";
    
    if ($result->num_rows == 4) {
        echo "✅ All indexes verified!\n";
    } else {
        echo "⚠️  Some indexes may be missing\n";
    }
} else {
    echo "\n⚠️  Migration completed with errors. Please review.\n";
}

$conn->close();
echo "\nMigration script complete!\n";
?>
