<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

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

    // Delete member
    $deleteSql = "DELETE FROM household_members WHERE household_id = ? AND resident_id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param('ii', $householdId, $residentId);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Failed to remove member: ' . $deleteStmt->error);
    }
    $deleteStmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
