<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$include_deceased = isset($_GET['include_deceased']) && $_GET['include_deceased'] === 'true';
$dob_before = isset($_GET['dob_before']) ? $_GET['dob_before'] : null;

try {
    $query = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, sex, date_of_birth, activity_status 
              FROM residents 
              WHERE 1=1";
    
    if (!$include_deceased) {
        $query .= " AND activity_status = 'Alive'";
    }

    $params = [];
    $types = "";

    if ($dob_before) {
        $query .= " AND date_of_birth < ?";
        $params[] = $dob_before;
        $types .= "s";
    }

    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR resident_id LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    } else {
        $query .= " LIMIT 10";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
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