<?php
require_once '../config.php';

// Prevent PHP errors from corrupting JSON output
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    $query = "SELECT e.*, 
              TRIM(CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, ''))) AS resident_name,
              bo.fullname AS approver_name
              FROM events e
              LEFT JOIN residents r ON e.resident_id = r.id
              LEFT JOIN barangay_officials bo ON e.approved_by = bo.id";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $start = $row['event_date'] . 'T' . $row['start_time'];
        $end = !empty($row['end_time']) ? ($row['event_date'] . 'T' . $row['end_time']) : null;

        $events[] = [
            'id' => (string)$row['id'],
            'title' => $row['title'],
            'start' => $start,
            'end' => $end,
            'allDay' => false,
            'extendedProps' => [
                'location' => $row['location'],
                'description' => $row['description'],
                'event_type' => $row['event_type'],
                'resident_id' => $row['resident_id'],
                'resident_name' => $row['resident_name'],
                'status' => $row['status'],
                'organizer' => $row['organizer'],
                'approved_by' => $row['approved_by'],
                'approved_by_name' => $row['approver_name']
            ]
        ];
    }
    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}