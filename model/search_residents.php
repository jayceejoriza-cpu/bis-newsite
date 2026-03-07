<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get search term from request
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get optional parameter to filter available residents for households
$filterHouseholds = isset($_GET['filter_households']) ? $_GET['filter_households'] === 'true' : false;

// Get optional parameter to exclude a specific resident ID (for RBC child search)
$excludeResidentId = isset($_GET['exclude_resident_id']) ? intval($_GET['exclude_resident_id']) : 0;

try {
    // Prepare SQL query to search residents
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
            WHERE r.activity_status = 'Active'";
    
    // If filtering for households, exclude residents already assigned
    if ($filterHouseholds) {
        $sql .= " AND r.id NOT IN (
            SELECT household_head_id 
            FROM households 
            WHERE household_head_id IS NOT NULL
        )
        AND r.id NOT IN (
            SELECT resident_id 
            FROM household_members
        )";
    }
    
    // Exclude specific resident ID (for RBC child search to exclude parent)
    if ($excludeResidentId > 0) {
        $sql .= " AND r.id != " . $excludeResidentId;
    }
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $searchParam = "%{$searchTerm}%";
        $sql .= " AND (
            CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) LIKE ?
            OR r.resident_id LIKE ?
            OR r.mobile_number LIKE ?
        )";
    }
    
    $sql .= " ORDER BY r.last_name, r.first_name LIMIT 100";
    
    // Prepare statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($searchTerm)) {
        $stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $residents = $result->fetch_all(MYSQLI_ASSOC);
    
    // Return success response with both 'data' and 'residents' for compatibility
    echo json_encode([
        'success' => true,
        'data' => $residents,
        'residents' => $residents,
        'count' => count($residents),
        'filtered' => $filterHouseholds,
        'excluded' => $excludeResidentId
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
