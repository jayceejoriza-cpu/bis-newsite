<?php
/**
 * Search Residents without Household
 * This file is specifically used for the Search Resident Modal in the Households module.
 */
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : ''; // 'adult' for head selection

try {
    // Base query selects residents who are ALIVE and not ARCHIVED
    // and uses NOT EXISTS subqueries to ensure they are NOT a head OR a member of any household
    $query = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, sex, date_of_birth, fourps_member 
              FROM residents r
              WHERE activity_status = 'Alive'
              AND NOT EXISTS (SELECT 1 FROM households h WHERE h.household_head_id = r.id)
              AND NOT EXISTS (SELECT 1 FROM household_members hm WHERE hm.resident_id = r.id)";
    
    $params = [];
    $types = "";

    // Filter by age if selecting a household head
    if ($filter === 'adult') {
        $query .= " AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 18";
    }

    // Search term logic
    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR resident_id LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    }

    $query .= " ORDER BY last_name ASC LIMIT 20";

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $residents = [];

    while ($row = $result->fetch_assoc()) {
        // Format full name for the frontend list
        $fullName = trim($row['last_name']);
        if (!empty($row['first_name'])) $fullName .= ', ' . trim($row['first_name']);
        if (!empty($row['suffix'])) $fullName .= ' ' . trim($row['suffix']);
        if (!empty($row['middle_name'])) $fullName .= ' ' . strtoupper(substr(trim($row['middle_name']), 0, 1)) . '.';
        
        $row['full_name'] = $fullName;
        $residents[] = $row;
    }

    echo json_encode([
        'success' => true, 
        'data' => $residents,
        'count' => count($residents)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}