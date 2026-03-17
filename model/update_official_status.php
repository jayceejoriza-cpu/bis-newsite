<?php
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

header('Content-Type: application/json');

// Check if the user has permission to edit officials
if (!hasPermission('perm_officials_edit')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$officialId = $_POST['official_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$officialId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$validStatuses = ['Active', 'Inactive', 'Completed'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("UPDATE barangay_officials SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $officialId
    ]);

    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
    error_log("Error updating official status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>