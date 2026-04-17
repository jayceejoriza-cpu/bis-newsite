<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $query = "SELECT id, resident_id, first_name, middle_name, last_name, suffix 
              FROM residents 
              WHERE activity_status = 'Alive'";
    
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR resident_id LIKE ?)";
        $stmt = $conn->prepare($query);
        $searchTerm = "%$search%";
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    } else {
        $query .= " LIMIT 10";
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $residents = [];

    while ($row = $result->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'] . ' ' . $row['suffix']);
        $row['full_name'] = $fullName;
        $residents[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $residents]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}