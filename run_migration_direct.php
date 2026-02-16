<?php
/**
 * Direct Migration Execution
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

// Execute each ALTER TABLE statement directly
$migrations = [
    [
        'name' => 'idx_unique_mobile',
        'sql' => "ALTER TABLE `residents` ADD UNIQUE INDEX `idx_unique_mobile` (`mobile_number`)"
    ],
    [
        'name' => 'idx_unique_email',
        'sql' => "ALTER TABLE `residents` ADD UNIQUE INDEX `idx_unique_email` (`email`)"
    ],
    [
        'name' => 'idx_unique_philhealth',
        'sql' => "ALTER TABLE `residents` ADD UNIQUE INDEX `idx_unique_philhealth` (`philhealth_id`)"
    ],
    [
        'name' => 'idx_name_dob',
        'sql' => "ALTER TABLE `residents` ADD INDEX `idx_name_dob` (`first_name`, `last_name`, `date_of_birth`)"
    ],
    [
        'name' => 'idx_activity_status',
        'sql' => "ALTER TABLE `residents` ADD INDEX `idx_activity_status` (`activity_status`)"
    ]
];

$successCount = 0;
$errorCount = 0;

foreach ($migrations as $migration) {
    echo "Creating index: " . $migration['name'] . "... ";
    
    if ($conn->query($migration['sql'])) {
        echo "✅ Success\n";
        $successCount++;
    } else {
        if (strpos($conn->error, 'Duplicate key name') !== false || 
            strpos($conn->error, 'already exists') !== false) {
            echo "⚠️  Already exists (skipped)\n";
            $successCount++;
        } else {
            echo "❌ Failed: " . $conn->error . "\n";
            $errorCount++;
        }
    }
}

echo "\n";
echo "==============================================\n";
echo "MIGRATION SUMMARY\n";
echo "==============================================\n";
echo "Successful: " . $successCount . " / " . count($migrations) . "\n";
echo "Errors: " . $errorCount . "\n\n";

if ($errorCount == 0) {
    echo "✅ Migration completed successfully!\n\n";
    
    // Verify indexes
    echo "Verifying indexes...\n";
    $result = $conn->query("SHOW INDEXES FROM residents");
    $indexes = [];
    while ($row = $result->fetch_assoc()) {
        $indexes[] = $row['Key_name'];
    }
    
    echo "All indexes on residents table:\n";
    foreach (array_unique($indexes) as $index) {
        echo "  - " . $index . "\n";
    }
    echo "\n";
    
    $requiredIndexes = ['idx_unique_mobile', 'idx_unique_email', 'idx_unique_philhealth', 'idx_name_dob', 'idx_activity_status'];
    $foundCount = 0;
    foreach ($requiredIndexes as $reqIndex) {
        if (in_array($reqIndex, $indexes)) {
            $foundCount++;
        }
    }
    
    echo "Required indexes found: " . $foundCount . " / " . count($requiredIndexes) . "\n";
    
    if ($foundCount == count($requiredIndexes)) {
        echo "✅ All required indexes are in place!\n";
    }
} else {
    echo "⚠️  Migration completed with errors.\n";
}

$conn->close();
echo "\nMigration complete!\n";
?>
