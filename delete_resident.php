<?php
require_once 'config.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get resident ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident ID']);
    exit;
}

// Check if archive table exists and create it if not (Outside transaction)
$checkTable = $conn->query("SHOW TABLES LIKE 'archive'");
if ($checkTable->num_rows == 0) {
    $createTableSql = "CREATE TABLE `archive` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `archive_type` varchar(50) DEFAULT NULL,
        `record_id` varchar(50) DEFAULT NULL,
        `record_data` longtext DEFAULT NULL,
        `deleted_by` varchar(100) DEFAULT NULL,
        `deleted_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createTableSql);
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get resident details for archiving
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Resident not found");
    }
    
    $resident = $result->fetch_assoc();
    $residentName = $resident['first_name'] . ' ' . $resident['last_name'];
    $residentIdCode = $resident['resident_id'];
    $stmt->close();

    // Fetch emergency contacts to include in archive
    $stmt = $conn->prepare("SELECT * FROM emergency_contacts WHERE resident_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $contactsResult = $stmt->get_result();
    $emergencyContacts = [];
    while ($contact = $contactsResult->fetch_assoc()) {
        $emergencyContacts[] = $contact;
    }
    $resident['emergency_contacts'] = $emergencyContacts;
    $stmt->close();

    // Prepare data for archive
    // Use JSON_PARTIAL_OUTPUT_ON_ERROR and JSON_UNESCAPED_UNICODE
    $recordData = json_encode($resident, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    if ($recordData === false) {
        throw new Exception("Failed to encode resident data");
    }
    
    $archiveType = 'resident';
    $deletedBy = $_SESSION['username'] ?? 'Unknown';

    // Insert into archive table
    $stmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $archiveType, $residentIdCode, $recordData, $deletedBy);
    if (!$stmt->execute()) {
        throw new Exception("Failed to archive resident record: " . $stmt->error);
    }
    $stmt->close();

    // Delete emergency contacts associated with the resident
    $stmt = $conn->prepare("DELETE FROM emergency_contacts WHERE resident_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Delete the resident record
    $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete resident record: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        // Log Activity
        if (isset($_SESSION['username'])) {
            $log_user = $_SESSION['username'];
            $log_action = 'Archive Resident';
            $log_desc = "Moved resident to archive: $residentName ($residentIdCode)";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Resident moved to archive successfully']);
    } else {
        // If no rows affected, it might have been already deleted
        throw new Exception("Resident record could not be deleted or does not exist");
    }
    
    $stmt->close();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>