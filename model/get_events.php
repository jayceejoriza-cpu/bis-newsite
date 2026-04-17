<?php
header('Content-Type: application/json');
require_once '../config.php';

$query = "SELECT e.*, 
          TRIM(CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, ''))) AS resident_name
          FROM events e
          LEFT JOIN residents r ON e.resident_id = r.id";
$result = $conn->query($query);

$events = [];
while ($row = $result->fetch_assoc()) {
    $start = $row['event_date'] . 'T' . $row['start_time'];
    $end = !empty($row['end_time']) ? ($row['event_date'] . 'T' . $row['end_time']) : null;

    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'start' => $start,
        'end' => $end,
        'allDay' => false,          // Forces it to show at a specific time
        'extendedProps' => [
            'location' => $row['location'],
            'description' => $row['description'],
            'event_type' => $row['event_type'],
            'resident_id' => $row['resident_id'],
            'resident_name' => $row['resident_name']
        ]
    ];
}
echo json_encode($events);