<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();
    
    if ($info) {
        echo json_encode(['success' => true, 'data' => $info]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No information found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
