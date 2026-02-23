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

// Get Certificate ID or Create if not exists
$cert_id = 0;
$stmt = $conn->prepare("SELECT id FROM certificates WHERE title = ? LIMIT 1");
$stmt->bind_param("s", $cert_title);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $cert_id = $row['id'];
} else {
    // Create dummy certificate entry if not exists to maintain FK constraint
    $stmt2 = $conn->prepare("INSERT INTO certificates (title, description, status, created_at) VALUES (?, 'System Generated', 'Published', NOW())");
    $stmt2->bind_param("s", $cert_title);
    $stmt2->execute();
    $cert_id = $conn->insert_id;
    $stmt2->close();
}
$stmt->close();

// Generate Reference No
$ref_no = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);

// Insert Request
$stmt = $conn->prepare("INSERT INTO certificate_requests (reference_no, resident_id, certificate_id, purpose, status, date_requested, created_at) VALUES (?, ?, ?, ?, 'Approved', ?, NOW())");
$stmt->bind_param("siiss", $ref_no, $resident_id, $cert_id, $purpose, $date_requested);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
?>