<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Load permissions
require_once '../permissions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check permission
if (!hasPermission('perm_resident_edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

// Get and validate inputs
$residentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$newStatus  = isset($_POST['status']) ? trim($_POST['status']) : '';

// Validate resident ID
if ($residentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid resident ID']);
    exit;
}

// Validate status value
$allowedStatuses = ['Alive', 'Deceased'];
if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value. Must be Alive or Deceased.']);
    exit;
}

// Get current user ID from session
$changedBy = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );

    // Check resident exists and get current status
    $checkStmt = $pdo->prepare("SELECT id, first_name, last_name, activity_status FROM residents WHERE id = ?");
    $checkStmt->execute([$residentId]);
    $resident = $checkStmt->fetch();

    if (!$resident) {
        echo json_encode(['success' => false, 'message' => 'Resident not found']);
        exit;
    }

    // If status is already the same, no need to update
    if ($resident['activity_status'] === $newStatus) {
        echo json_encode([
            'success' => false,
            'message'  => "Resident is already marked as {$newStatus}"
        ]);
        exit;
    }

    // Update activity status
    $updateStmt = $pdo->prepare("
        UPDATE residents 
        SET 
            activity_status    = ?,
            status_changed_at  = NOW(),
            status_changed_by  = ?,
            updated_at         = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$newStatus, $changedBy, $residentId]);

    $fullName = trim($resident['first_name'] . ' ' . $resident['last_name']);

    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Update Resident Status';
        $log_desc = "Changed activity status of $fullName to $newStatus";
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->execute([$log_user, $log_action, $log_desc]);
    }

    echo json_encode([
        'success'    => true,
        'message'    => "Activity status of {$fullName} updated to {$newStatus}",
        'new_status' => $newStatus
    ]);

} catch (PDOException $e) {
    error_log("Error updating activity status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
