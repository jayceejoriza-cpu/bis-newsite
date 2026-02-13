<?php
// Include configuration
require_once 'config.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get search term from request
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Prepare SQL query to search residents
    // Exclude residents who are already household heads or members
    $sql = "SELECT 
                r.id,
                r.resident_id,
                CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.suffix,
                r.date_of_birth,
                r.sex,
                r.mobile_number,
                r.current_address
            FROM residents r
            LEFT JOIN households h ON r.id = h.household_head_id
            LEFT JOIN household_members hm ON r.id = hm.resident_id
            WHERE r.activity_status = 'Active'
            AND h.id IS NULL
            AND hm.id IS NULL";
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $searchParam = "%{$searchTerm}%";
        $sql .= " AND (
            CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) LIKE ?
            OR r.resident_id LIKE ?
            OR r.mobile_number LIKE ?
        )";
    }
    
    $sql .= " ORDER BY r.last_name, r.first_name LIMIT 50";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($searchTerm)) {
        $stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $residents = $result->fetch_all(MYSQLI_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $residents,
        'count' => count($residents)
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
