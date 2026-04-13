<?php
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Set charset to ensure JSON encoding works correctly
$conn->set_charset("utf8mb4");

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

$password = trim($_POST['password'] ?? '');

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$reason = trim($_POST['reason'] ?? 'No reason provided');

// Verify user password for security
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();

// Check if archive table exists and create it if not (Outside transaction)
$checkTable = $conn->query("SHOW TABLES LIKE 'archive'");
if ($checkTable->num_rows == 0) {
    $createTableSql = "CREATE TABLE IF NOT EXISTS `archive` (
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

    // Append the archival reason to the record data
    $resident['archive_reason'] = $reason;

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

    // Delete the resident record
    $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete resident record: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        // Commit transaction first to ensure delete is saved
        $conn->commit();
        
        // Log Activity (outside transaction to prevent rollback if logging fails)
        if (isset($_SESSION['username'])) {
            try {
                $log_user = $_SESSION['username'];
                $log_action = 'Archive Resident';
                $log_desc = "Moved resident to archive: $residentName ($residentIdCode). Reason: $reason";
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                if ($log_stmt) {
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } catch (Exception $log_error) {
                // Log the error but don't fail the delete operation
                error_log("Activity log error: " . $log_error->getMessage());
            }
        }
        
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