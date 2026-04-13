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
    $status = isset($_POST['status']) ? ucwords(strtolower(trim($_POST['status']))) : '';

    if ($id <= 0 || empty($status)) {
        throw new Exception('Invalid input parameters.');
    }

    // Validate status value AFTER normalization
    $allowedStatuses = ['Pending', 'Under Investigation', 'Dismissed', 'Scheduled for Mediation', 'Settled', 'Endorsed to Police'];
    if (!in_array($status, $allowedStatuses)) {
        error_log("Invalid status '$status' for record $id. Allowed: " . implode(', ', $allowedStatuses));
        throw new Exception("Invalid status: '$status'. Must be one of: " . implode(', ', $allowedStatuses));
    }
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
    
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($affected_rows === 0) {
        error_log("No rows affected updating status for record ID: $id, status: '$status'");
        throw new Exception("No record found with ID: $id or no changes made.");
    }

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

    // Verify actual DB status post-update
    $verify_stmt = $conn->prepare("SELECT status FROM blotter_records WHERE id = ?");
    $verify_stmt->bind_param("i", $id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $db_status = $verify_result->fetch_assoc()['status'] ?? 'NULL';
    $verify_stmt->close();
    
    error_log("Status updated: ID=$id, requested='$status', DB='$db_status', affected_rows=$affected_rows by {$_SESSION['username'] ?? 'unknown'}");
    
    $response['success'] = true;
    $response['message'] = "Status updated to '$status' successfully.";
    $response['affected_rows'] = $affected_rows;
    $response['new_status'] = $db_status;
    $response['requested_status'] = $status;

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
