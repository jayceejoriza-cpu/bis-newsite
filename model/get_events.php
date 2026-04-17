<?php
// model/get_events.php - JSON API for FullCalendar
header('Content-Type: application/json');

try {
    require_once '../config.php';
    
    $currentYear = date('Y');
    $currentMonth = date('m');
    
    // Query for events this year (FullCalendar fetches range)
    $stmt = $conn->prepare("
        SELECT 
            id, 
            title, 
            description,
            event_date as start,
            start_time, 
            end_time,
            location
        FROM events 
        WHERE YEAR(event_date) = ? 
        ORDER BY event_date ASC
    ");
    $stmt->bind_param("i", $currentYear);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($events);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>

