<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_type = $_POST['event_type'] ?? 'Barangay';
    $resident_id = !empty($_POST['resident_id']) ? intval($_POST['resident_id']) : null;
    $organizer = trim($_POST['organizer'] ?? '');
    $approved_by = !empty($_POST['approved_by']) ? intval($_POST['approved_by']) : null;
    $status = $_POST['status'] ?? 'Active';

    if (!$id || empty($title) || empty($location)) {
        throw new Exception('Required fields are missing.');
    }

    // Fetch existing record to preserve date/time if they are empty (common when postponing)
    $getStmt = $conn->prepare("SELECT event_date, start_time FROM events WHERE id = ?");
    $getStmt->bind_param("i", $id);
    $getStmt->execute();
    $existing = $getStmt->get_result()->fetch_assoc();
    $getStmt->close();

    if (!$existing) throw new Exception('Event not found.');

    if (empty($date)) $date = $existing['event_date'];
    if (empty($start_time)) $start_time = $existing['start_time'];

    // If end_time is not provided, assume a 1-hour duration for conflict checking
    $effective_end_time = $end_time;
    if (empty($effective_end_time)) {
        $effective_end_time = date('H:i:s', strtotime($start_time . ' +1 hour'));
    }

    // ============================================
    // CONFLICT LOGIC: Check if location is in use (excluding current record)
    // ============================================
    $conflict = null;
    if ($status === 'Active') {
        $checkStmt = $conn->prepare("
            SELECT title FROM events 
            WHERE LOWER(location) = LOWER(?) 
            AND event_date = ? 
            AND id != ?
            AND start_time < ? 
            AND COALESCE(end_time, DATE_ADD(start_time, INTERVAL 1 HOUR)) > ?
            AND status NOT IN ('Postponed', 'Cancelled')
        ");
        
        $checkStmt->bind_param("ssiss", $location, $date, $id, $effective_end_time, $start_time);
        $checkStmt->execute();
        $conflict = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();
    }

    if ($conflict) {
        throw new Exception("Conflict detected: The location '$location' is occupied by '{$conflict['title']}' during this time.");
    }

    $updated_by = $_SESSION['user_id'] ?? 0;
    $stmt = $conn->prepare("UPDATE events SET title=?, event_date=?, start_time=?, end_time=?, location=?, description=?, event_type=?, resident_id=?, organizer=?, approved_by=?, updated_by=?, status=?, updated_at=NOW() WHERE id=?");
    // Fixed variable names ($date) and order to match the SQL placeholders
    $stmt->bind_param('sssssssisissi', $title, $date, $start_time, $end_time, $location, $description, $event_type, $resident_id, $organizer, $approved_by, $updated_by, $status, $id);

    if ($stmt->execute()) {
        // Log Activity
        $log_user = $_SESSION['username'] ?? 'System';
        $log_desc = "Updated event ID $id: $title at $location";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, 'Update Event', ?)");
        $log_stmt->bind_param("ss", $log_user, $log_desc);
        $log_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
