<?php
// model/get_upcoming_events.php - JSON API for upcoming events table
header('Content-Type: application/json');

try {
    require_once '../config.php';
    
    $stmt = $conn->prepare("
        SELECT 
            id, 
            title, 
            event_date, 
            start_time, 
            location
        FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC 
        LIMIT 10
    ");
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($events);
    
} catch (Exception $e) {
    error_log("Error fetching upcoming events: " . $e->getMessage());
    echo json_encode([]);
}
?>
