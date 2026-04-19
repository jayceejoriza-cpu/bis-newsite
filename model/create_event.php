<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $title = trim($_POST['title'] ?? '');
    $date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_type = $_POST['event_type'] ?? 'Barangay';
    $resident_id = !empty($_POST['resident_id']) ? intval($_POST['resident_id']) : null;
    $status = $_POST['status'] ?? 'Active';

    if (empty($title) || empty($date) || empty($start_time) || empty($location)) {
        throw new Exception('Required fields (Title, Date, Start Time, Location) are missing.');
    }

    // Ensure event is not set in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        throw new Exception('The selected event date has already passed.');
    }

    $recurrence_type = $_POST['recurrence_type'] ?? 'none';
    $recurrence_days = json_decode($_POST['recurrence_days'] ?? '[]', true);
    $recurrence_end_date = $_POST['recurrence_end_date'] ?? '';

    $dates_to_schedule = [];
    if ($recurrence_type === 'custom' && !empty($recurrence_days) && !empty($recurrence_end_date)) {
        $start = new DateTime($date);
        $end = new DateTime($recurrence_end_date);
        
        if ($end < $start) {
            throw new Exception('The recurrence "Until Date" cannot be before the event start date.');
        }

        $end->modify('+1 day');
        
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        foreach ($period as $dt) {
            if (in_array($dt->format('w'), $recurrence_days)) {
                $dates_to_schedule[] = $dt->format('Y-m-d');
            }
        }
    }

    // Ensure at least the primary date is included if no recurring dates matched or type is 'none'
    if (empty($dates_to_schedule)) {
        $dates_to_schedule[] = $date;
    }
    $dates_to_schedule = array_unique($dates_to_schedule);

    // If end_time is not provided, assume a 1-hour duration for conflict checking
    $effective_end_time = $end_time;
    if (empty($effective_end_time)) {
        $effective_end_time = date('H:i:s', strtotime($start_time . ' +1 hour'));
    }

    // ========================================================
    // CONFLICT LOGIC: Check all dates for location availability
    // ========================================================
    if ($status === 'Active') {
        foreach ($dates_to_schedule as $target_date) {
            $checkStmt = $conn->prepare("
                SELECT title FROM events 
                WHERE LOWER(location) = LOWER(?) 
                AND event_date = ? 
                AND start_time < ? 
                AND COALESCE(end_time, DATE_ADD(start_time, INTERVAL 1 HOUR)) > ?
                AND status NOT IN ('Postponed', 'Cancelled')
            ");
            
            if (!$checkStmt) {
                throw new Exception("Database prepare failed: " . $conn->error);
            }
            
            $checkStmt->bind_param("ssss", $location, $target_date, $effective_end_time, $start_time);
            $checkStmt->execute();
            $conflict = $checkStmt->get_result()->fetch_assoc();
            
            if ($conflict) {
                $display_date = date('M d, Y', strtotime($target_date));
                throw new Exception("Conflict on $display_date: The location '$location' is occupied by '{$conflict['title']}' during this time.");
            }
            $checkStmt->close();
        }
    }

    $created_by = $_SESSION['user_id'] ?? 0;
    $success_count = 0;

    $target_date = $date; // Initialize to prevent reference warning
    $stmt = $conn->prepare("INSERT INTO events (title, event_date, start_time, end_time, location, description, event_type, resident_id, created_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        throw new Exception("Insert prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssssssiis", $title, $target_date, $start_time, $end_time, $location, $description, $event_type, $resident_id, $created_by, $status);

    foreach ($dates_to_schedule as $target_date) {
        if ($stmt->execute()) $success_count++;
    }

    if ($success_count > 0) {
        // Log Activity
        $log_user = $_SESSION['username'] ?? 'System';
        $log_desc = "Scheduled $success_count instance(s) of event: $title at $location";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, 'Create Event', ?)");
        $log_stmt->bind_param("ss", $log_user, $log_desc);
        $log_stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>