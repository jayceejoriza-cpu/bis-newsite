<?php
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

try {
    if (!hasPermission('perm_blotter_status')) {
        throw new Exception('Permission denied.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $mediation_schedule = isset($_POST['mediation_schedule']) ? trim($_POST['mediation_schedule']) : null;
    $remarks = isset($_POST['update_remarks']) ? trim($_POST['update_remarks']) : 'No remarks provided.';

    if ($id <= 0 || empty($status)) {
        throw new Exception('Invalid input parameters. Record ID and Status are required.');
    }

    $allowedStatuses = ['Pending', 'Under Investigation', 'Dismissed', 'Scheduled for Mediation', 'Settled', 'Endorsed to Police'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception("Invalid status: '$status'.");
    }

    // Validation: Schedule is mandatory for mediation
    if ($status === 'Scheduled for Mediation' && empty($mediation_schedule)) {
        throw new Exception('Mediation schedule is mandatory when setting status to "Scheduled for Mediation".');
    }

    // 1. Pre-fetch current data
    $fetch_stmt = $conn->prepare("SELECT status, mediation_schedule FROM blotter_records WHERE id = ?");
    $fetch_stmt->bind_param("i", $id);
    $fetch_stmt->execute();
    $old_data = $fetch_stmt->get_result()->fetch_assoc();
    $fetch_stmt->close();

    if (!$old_data) {
        throw new Exception("Record not found.");
    }

    // 2. Smart Date Comparison using Unix Timestamps
    // Normalize DB value (remove seconds) before comparing to form input (which has no seconds)
    $old_sched_ts = $old_data['mediation_schedule'] ? strtotime(date('Y-m-d H:i', strtotime($old_data['mediation_schedule']))) : 0;
    $new_sched_ts = $mediation_schedule ? strtotime($mediation_schedule) : 0;

    $status_changed = ($old_data['status'] !== $status);
    $schedule_changed = ($old_sched_ts !== $new_sched_ts);

    // Check if any changes occurred
    if (!$status_changed && !$schedule_changed) {
        throw new Exception("Walang pagbabagong nakita sa Status o Schedule.");
    }

    $conn->begin_transaction();

    // 3. Update main table
    if ($status === 'Scheduled for Mediation') {
        $stmt = $conn->prepare("UPDATE blotter_records SET status = ?, mediation_schedule = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $status, $mediation_schedule, $id);
    } else {
        $stmt = $conn->prepare("UPDATE blotter_records SET status = ?, mediation_schedule = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
    }

    if (!$stmt->execute()) {
        throw new Exception('Update failed: ' . $stmt->error);
    }
    $stmt->close();

    // 4. LOG TO HISTORY
    if ($status_changed && $schedule_changed) {
        $action_type = "Status & Schedule Updated";
    } elseif ($status_changed) {
        $action_type = "Status Updated";
    } else {
        $action_type = "Rescheduled";
    }
    
    // Format values for audit readability (e.g., April 20, 2026 11:31 AM)
    $old_val = "Status: " . $old_data['status'] . ($old_data['mediation_schedule'] ? " | Sched: " . date('F j, Y h:i A', strtotime($old_data['mediation_schedule'])) : "");
    $new_val = "Status: " . $status . ($mediation_schedule ? " | Sched: " . date('F j, Y h:i A', strtotime($mediation_schedule)) : "");

    $user_id = $_SESSION['user_id'] ?? 0;
    $history_stmt = $conn->prepare("INSERT INTO blotter_history (blotter_id, action_type, old_value, new_value, remarks, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
    $history_stmt->bind_param("issssi", $id, $action_type, $old_val, $new_val, $remarks, $user_id);
    
    if (!$history_stmt->execute()) {
        $conn->rollback();
        throw new Exception('History logging failed.');
    }
    $history_stmt->close();

    $conn->commit();
    $response['success'] = true;
    $response['message'] = "Record successfully updated and logged.";

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno == 0) { $conn->rollback(); }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);