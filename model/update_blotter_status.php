<?php
// Include configuration and authentication
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Check permission
    if (!hasPermission('perm_blotter_status')) {
        throw new Exception('Permission denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    if ($id <= 0 || empty($status)) {
        throw new Exception('Invalid input parameters.');
    }

    // Validate status value
    $allowedStatuses = ['Pending', 'Under Investigation', 'Resolved', 'Dismissed'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('Invalid status value.');
    }

    // Prepare statement to update status
    $stmt = $conn->prepare("UPDATE blotter_records SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database preparation error: ' . $conn->error);
    }
    
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update status: ' . $stmt->error);
    }
    $stmt->close();

    // Log the activity
    $log_user = $_SESSION['username'] ?? 'System';
    $log_action = 'Update Blotter Status';
    $log_desc = "Updated status of blotter record ID $id to '$status'.";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
    if ($log_stmt) {
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }

    $response['success'] = true;
    $response['message'] = 'Status updated successfully.';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
