<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$title = $_GET['title'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required']);
    exit;
}

try {
    // Query the database for the full range of this specific event series
    $stmt = $conn->prepare("
        SELECT 
            MIN(event_date) as first_date, 
            MAX(event_date) as last_date, 
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT DAYNAME(event_date) ORDER BY WEEKDAY(event_date)) as days
        FROM events 
        WHERE title = ? AND location = ? AND event_type = ? AND status NOT IN ('Postponed', 'Cancelled')
    ");
    $stmt->bind_param("sss", $title, $location, $type);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>