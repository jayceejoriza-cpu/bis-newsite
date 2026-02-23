<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

// Disable display errors to prevent JSON corruption
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid household ID']);
    exit;
}

// Check if archive table exists (create if not - Outside transaction)
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
    $conn->begin_transaction();

    // Fetch household details
    $stmt = $conn->prepare("SELECT h.*, 
                            (SELECT CONCAT(first_name, ' ', last_name) FROM residents WHERE id = h.household_head_id) as head_name 
                            FROM households h WHERE h.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Household not found");
    }
    
    $household = $result->fetch_assoc();
    $householdNumber = $household['household_number'];
    $stmt->close();

    // Fetch members
    $members = [];
    $stmt = $conn->prepare("SELECT hm.*, 
                            (SELECT CONCAT(first_name, ' ', last_name) FROM residents WHERE id = hm.resident_id) as member_name 
                            FROM household_members hm WHERE hm.household_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resMembers = $stmt->get_result();
    while ($row = $resMembers->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();

    $household['members'] = $members;

    // Archive
    $recordData = json_encode($household, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $archiveType = 'household';
    $deletedBy = $_SESSION['username'] ?? 'Unknown';

    $stmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $archiveType, $householdNumber, $recordData, $deletedBy);
    if (!$stmt->execute()) {
        throw new Exception("Failed to archive household: " . $stmt->error);
    }
    $stmt->close();

    // Delete members
    $stmt = $conn->prepare("DELETE FROM household_members WHERE household_id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete household members: " . $stmt->error);
    }
    $stmt->close();

    // Delete household
    $stmt = $conn->prepare("DELETE FROM households WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete household: " . $stmt->error);
    }
    $stmt->close();

    // Log Activity
    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Archive Household';
        $log_desc = "Moved household to archive: $householdNumber";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Household moved to archive successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>