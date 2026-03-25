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

    // Check position limits if setting to Active
    if ($status === 'Active') {
        $posStmt = $pdo->prepare("SELECT position FROM barangay_officials WHERE id = :id");
        $posStmt->execute([':id' => $officialId]);
        $official = $posStmt->fetch();
        
        if ($official) {
            $position = $official['position'];
            $limitMap = [
                'Barangay Captain' => 1,
                'SK Chairman' => 1,
                'Barangay Secretary' => 1,
                'Barangay Treasurer' => 1,
                'Barangay Administator' => 1,
                'Bookkeeper' => 1,
                'Kagawad' => 7
            ];

            if (isset($limitMap[$position])) {
                $limit = $limitMap[$position];
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM barangay_officials WHERE position = :position AND status = 'Active' AND id != :id");
                $countStmt->execute([':position' => $position, ':id' => $officialId]);
                $currentCount = $countStmt->fetchColumn();

                if ($currentCount >= $limit) {
                    throw new Exception("Cannot set status to Active. The limit of $limit for $position has been reached.");
                }
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE barangay_officials SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':id' => $officialId
    ]);

    $nameStmt = $pdo->prepare("SELECT fullname, position FROM barangay_officials WHERE id = :id");
    $nameStmt->execute([':id' => $officialId]);
    $official = $nameStmt->fetch();
    $officialName = $official['fullname'] ?? 'Unknown';
    $officialPosition = $official['position'] ?? 'Unknown';

    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Update Official Status';
        $log_desc = "Changed status of $officialName ($officialPosition) to $status";
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->execute([$log_user, $log_action, $log_desc]);
    }

    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} catch (PDOException $e) {
    error_log("Error updating official status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>