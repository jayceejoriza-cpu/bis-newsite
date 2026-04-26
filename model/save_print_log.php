<?php
require_once '../config.php';
require_once '../auth_check.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$resident_id = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;
$cert_title = isset($_POST['certificate_type']) ? trim($_POST['certificate_type']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'For Record Purposes';
$date_requested = date('Y-m-d H:i:s');

if ($resident_id <= 0 || empty($cert_title)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Check for 1-time limit (RA 11261) before saving log
$isOneTime = (stripos($cert_title, 'Job Seeker') !== false || stripos($cert_title, 'Oath') !== false || stripos($cert_title, 'First Time') !== false);
if ($isOneTime) {
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM certificate_requests WHERE resident_id = ? AND (certificate_name LIKE '%Job%Seeker%' OR certificate_name LIKE '%Oath%' OR certificate_name LIKE '%RA 11261%')");
    $checkStmt->bind_param("i", $resident_id);
    $checkStmt->execute();
    $res = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    
    if ($res && $res['count'] >= 1) {
        echo json_encode(['success' => false, 'message' => 'Limit reached: This resident has already availed of the one-time benefit (RA 11261).']);
        exit;
    }
}

// Generate Reference No
$ref_no = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
$created_by = $_SESSION['username'] ?? 'System';

// Insert Request - now using certificate_name directly
$stmt = $conn->prepare("INSERT INTO certificate_requests (reference_no, resident_id, certificate_name, purpose, created_by, date_requested, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("sissss", $ref_no, $resident_id, $cert_title, $purpose, $created_by, $date_requested);

if ($stmt->execute()) {
    $resStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name, IFNULL(CONCAT(' ', suffix), '')) AS full_name FROM residents WHERE id = ?");
    $resStmt->bind_param('i', $resident_id);
    $resStmt->execute();
    $resResult = $resStmt->get_result()->fetch_assoc();
    $resStmt->close();
    $residentName = $resResult['full_name'] ?? "Resident ID $resident_id";

    if (isset($_SESSION['username'])) {
        $certNameDisplay = str_ireplace('Certificate of ', '', $cert_title);
        $log_user = $_SESSION['username'];
        $log_action = 'Generate Certificate';
        $log_desc = "Generate a certificate of $certNameDisplay to $residentName";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
?>
