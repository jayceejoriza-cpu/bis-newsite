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

// Generate Reference No
$ref_no = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);

// Insert Request - now using certificate_name directly
$stmt = $conn->prepare("INSERT INTO certificate_requests (reference_no, resident_id, certificate_name, purpose, status, date_requested, created_at) VALUES (?, ?, ?, ?, 'Approved', ?, NOW())");
$stmt->bind_param("sisss", $ref_no, $resident_id, $cert_title, $purpose, $date_requested);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
?>
