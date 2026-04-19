<?php
require_once '../config.php';
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

// Get event ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$password = trim($_POST['password'] ?? '');
$reason = trim($_POST['reason'] ?? 'No reason provided');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required for verification']);
    exit;
}

// 1. Verify user password for security
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password. Archiving cancelled.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User session not found']);
    exit;
}
$stmt->close();

// 2. Ensure archive table exists
$conn->query("CREATE TABLE IF NOT EXISTS `archive` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `archive_type` varchar(50) DEFAULT NULL,
    `record_id` varchar(50) DEFAULT NULL,
    `record_data` longtext DEFAULT NULL,
    `deleted_by` varchar(100) DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    // Start transaction
    $conn->begin_transaction();

    // 3. Get event details for archiving
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Event not found");
    }
    
    $event = $result->fetch_assoc();
    $eventTitle = $event['title'];
    $stmt->close();

    // Append the archival reason to the record data
    $event['archive_reason'] = $reason;

    // 4. Prepare data for archive
    $recordData = json_encode($event, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $archiveType = 'event';
    $deletedBy = $_SESSION['username'] ?? 'Unknown';

    // 5. Insert into archive table
    $stmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $archiveType, $id, $recordData, $deletedBy);
    if (!$stmt->execute()) {
        throw new Exception("Failed to save to archive: " . $stmt->error);
    }
    $stmt->close();

    // 6. Delete the original record
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $conn->commit();
        
        // 7. Log Activity
        $log_desc = "Archived event: $eventTitle. Reason: $reason";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, 'Archive Event', ?)");
        $log_stmt->bind_param("ss", $deletedBy, $log_desc);
        $log_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Event archived successfully']);
    } else {
        throw new Exception("Failed to remove event from active list");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>