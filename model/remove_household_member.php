<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Disable error display so that warnings don't corrupt the JSON response
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$householdId = isset($_POST['household_id']) ? intval($_POST['household_id']) : 0;
$residentId = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;

if ($householdId <= 0 || $residentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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

try {
    // Make sure we are not removing the household head
    $checkSql = "SELECT household_head_id FROM households WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('i', $householdId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $household = $result->fetch_assoc();
        if ($household['household_head_id'] == $residentId) {
            throw new Exception("Cannot remove the household head directly. Please transfer the head role first.");
        }
    } else {
        throw new Exception("Household not found.");
    }
    $checkStmt->close();

    // Archive the member before deleting
    $stmt = $conn->prepare("SELECT hm.relationship_to_head, hm.is_head, CONCAT(r.first_name, ' ', r.last_name) as resident_name, h.household_number FROM household_members hm JOIN residents r ON hm.resident_id = r.id JOIN households h ON hm.household_id = h.id WHERE hm.household_id = ? AND hm.resident_id = ?");
    $stmt->bind_param("ii", $householdId, $residentId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $archiveData = [
            'household_id' => $householdId,
            'household_number' => $row['household_number'],
            'resident_id' => $residentId,
            'resident_name' => $row['resident_name'],
            'relationship_to_head' => $row['relationship_to_head'],
            'is_head' => $row['is_head'],
            'archive_reason' => $reason
        ];
        $archiveType = 'household_member';
        $deletedBy = $_SESSION['username'] ?? 'Unknown';
        $recordData = json_encode($archiveData);
        $recordId = $row['household_number'];
        
        $archStmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
        $archStmt->bind_param("ssss", $archiveType, $recordId, $recordData, $deletedBy);
        $archStmt->execute();
        $archStmt->close();
    }
    $stmt->close();

    // Delete member
    $deleteSql = "DELETE FROM household_members WHERE household_id = ? AND resident_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param('ii', $householdId, $residentId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to remove member: ' . $deleteStmt->error);
    }
    $deleteStmt->close();
    
    if (isset($_SESSION['username'])) {
        $resStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS fname FROM residents WHERE id = ?");
        $resStmt->bind_param('i', $residentId);
        $resStmt->execute();
        $resName = $resStmt->get_result()->fetch_assoc()['fname'] ?? "Resident ID $residentId";
        $resStmt->close();
        
        $hhStmt = $conn->prepare("SELECT household_number FROM households WHERE id = ?");
        $hhStmt->bind_param('i', $householdId);
        $hhStmt->execute();
        $hhNum = $hhStmt->get_result()->fetch_assoc()['household_number'] ?? "Household ID $householdId";
        $hhStmt->close();
        
        $log_user = $_SESSION['username'];
        $log_action = 'Delete Household Members';
        $log_desc = "Deleted $resName from household $hhNum. Reason: $reason";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
