<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $event_date = $_POST['event_date'];
    $start_time = $_POST['start_time'];
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $event_type = $_POST['event_type'];
    $resident_id = !empty($_POST['resident_id']) ? $_POST['resident_id'] : null;

    if (empty($title) || empty($event_date) || empty($start_time)) {
        echo json_encode(['success' => false, 'message' => 'Title, Date, and Start Time are required.']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO events (title, event_date, start_time, end_time, description, location, event_type, resident_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssii", $title, $event_date, $start_time, $end_time, $description, $location, $event_type, $resident_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Event created successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}