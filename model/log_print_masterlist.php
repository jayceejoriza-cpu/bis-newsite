<?php
/**
 * Log Print Masterlist Activity
 * Handles logging when a user prints the residents masterlist
 */

require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Print Masterlist';
        $log_desc = "Printed the residents masterlist";
        
        try {
            $stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
?>