<?php
/**
 * Cleanup Existing Duplicates Script
 * This script identifies and helps clean up existing duplicate data
 */

require_once 'config.php';

echo "==============================================\n";
echo "DUPLICATE CLEANUP UTILITY\n";
echo "==============================================\n\n";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "Connected to database: " . DB_NAME . "\n\n";

// Find duplicate mobile numbers
echo "1. DUPLICATE MOBILE NUMBERS\n";
echo "----------------------------\n";
$sql = "SELECT mobile_number, COUNT(*) as count, GROUP_CONCAT(CONCAT(resident_id, ': ', first_name, ' ', last_name) SEPARATOR ' | ') as residents
        FROM residents 
        GROUP BY mobile_number 
        HAVING count > 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Mobile: " . $row['mobile_number'] . " (Used by " . $row['count'] . " residents)\n";
        echo "Residents: " . $row['residents'] . "\n";
        echo "---\n";
    }
    echo "\n";
} else {
    echo "✅ No duplicate mobile numbers found\n\n";
}

// Find duplicate emails
echo "2. DUPLICATE EMAIL ADDRESSES\n";
echo "-----------------------------\n";
$sql = "SELECT email, COUNT(*) as count, GROUP_CONCAT(CONCAT(resident_id, ': ', first_name, ' ', last_name) SEPARATOR ' | ') as residents
        FROM residents 
        WHERE email IS NOT NULL AND email != ''
        GROUP BY email 
        HAVING count > 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Email: " . $row['email'] . " (Used by " . $row['count'] . " residents)\n";
        echo "Residents: " . $row['residents'] . "\n";
        echo "---\n";
    }
    echo "\n";
} else {
    echo "✅ No duplicate emails found\n\n";
}

// Find duplicate Philhealth IDs
echo "3. DUPLICATE PHILHEALTH IDs\n";
echo "----------------------------\n";
$sql = "SELECT philhealth_id, COUNT(*) as count, GROUP_CONCAT(CONCAT(resident_id, ': ', first_name, ' ', last_name) SEPARATOR ' | ') as residents
        FROM residents 
        WHERE philhealth_id IS NOT NULL AND philhealth_id != ''
        GROUP BY philhealth_id 
        HAVING count > 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Philhealth ID: " . $row['philhealth_id'] . " (Used by " . $row['count'] . " residents)\n";
        echo "Residents: " . $row['residents'] . "\n";
        echo "---\n";
    }
    echo "\n";
} else {
    echo "✅ No duplicate Philhealth IDs found\n\n";
}

// Find duplicate name + DOB
echo "4. DUPLICATE NAME + DATE OF BIRTH\n";
echo "----------------------------------\n";
$sql = "SELECT first_name, last_name, date_of_birth, COUNT(*) as count, 
        GROUP_CONCAT(CONCAT(resident_id, ' (', mobile_number, ')') SEPARATOR ' | ') as residents
        FROM residents 
        GROUP BY first_name, last_name, date_of_birth 
        HAVING count > 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
        echo "DOB: " . $row['date_of_birth'] . "\n";
        echo "Count: " . $row['count'] . " residents\n";
        echo "Residents: " . $row['residents'] . "\n";
        echo "---\n";
    }
    echo "\n";
} else {
    echo "✅ No duplicate name + DOB combinations found\n\n";
}

echo "==============================================\n";
echo "CLEANUP RECOMMENDATIONS\n";
echo "==============================================\n\n";

echo "To clean up duplicates, you have these options:\n\n";

echo "OPTION 1: Manual Review (Recommended)\n";
echo "--------------------------------------\n";
echo "1. Review each duplicate case above\n";
echo "2. Determine which record is correct\n";
echo "3. Update or delete incorrect records through the UI\n";
echo "4. For duplicate mobile/email, update one of them to a different value\n\n";

echo "OPTION 2: Automated Cleanup (Use with caution)\n";
echo "-----------------------------------------------\n";
echo "For duplicate mobile numbers, you can:\n";
echo "- Keep the oldest record (by created_at)\n";
echo "- Update newer records with modified mobile numbers\n\n";

echo "OPTION 3: SQL Cleanup Queries\n";
echo "------------------------------\n";
echo "Example: To find and review duplicates:\n";
echo "SELECT * FROM residents WHERE mobile_number IN (\n";
echo "  SELECT mobile_number FROM residents GROUP BY mobile_number HAVING COUNT(*) > 1\n";
echo ") ORDER BY mobile_number, created_at;\n\n";

echo "⚠️  IMPORTANT: Backup your database before making any changes!\n";
echo "mysqldump -u root -p bmis > bmis_backup_before_cleanup.sql\n\n";

$conn->close();
echo "Cleanup analysis complete!\n";
?>
