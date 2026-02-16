<?php
/**
 * Automatic Duplicate Fix Script
 * This script automatically fixes the identified duplicates
 */

require_once 'config.php';

echo "==============================================\n";
echo "AUTOMATIC DUPLICATE FIX\n";
echo "==============================================\n\n";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$conn->begin_transaction();

try {
    // Fix duplicate: Argel Tayson (W-00007)
    // We'll update the newer record's mobile and email to make them unique
    
    echo "Fixing duplicate: Argel Tayson (W-00007)\n";
    echo "----------------------------------------\n";
    
    // Get the duplicate record details
    $result = $conn->query("SELECT * FROM residents WHERE resident_id = 'W-00007'");
    if ($result->num_rows > 0) {
        $duplicate = $result->fetch_assoc();
        
        // Update mobile number (add -DUP suffix temporarily)
        $newMobile = $duplicate['mobile_number'] . '-DUP';
        $newEmail = 'duplicate_' . $duplicate['email'];
        
        $stmt = $conn->prepare("UPDATE residents SET mobile_number = ?, email = ? WHERE resident_id = ?");
        $stmt->bind_param("sss", $newMobile, $newEmail, $duplicate['resident_id']);
        
        if ($stmt->execute()) {
            echo "✅ Updated W-00007:\n";
            echo "   Old mobile: " . $duplicate['mobile_number'] . "\n";
            echo "   New mobile: " . $newMobile . "\n";
            echo "   Old email: " . $duplicate['email'] . "\n";
            echo "   New email: " . $newEmail . "\n";
        } else {
            throw new Exception("Failed to update W-00007: " . $stmt->error);
        }
        $stmt->close();
    }
    
    echo "\n";
    
    // Commit changes
    $conn->commit();
    echo "✅ All duplicates fixed successfully!\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Review the updated records in the UI\n";
    echo "2. Manually correct the mobile/email for W-00007 if needed\n";
    echo "3. Or delete W-00007 if it's truly a duplicate entry\n";
    echo "4. Run the database migration after cleanup\n\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Changes rolled back.\n\n";
}

$conn->close();
echo "Fix complete!\n";
?>
