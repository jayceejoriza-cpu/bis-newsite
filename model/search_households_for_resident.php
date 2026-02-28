<?php
// Include configuration
require_once '../config.php';

// Suppress display errors so PHP warnings don't corrupt JSON output
ini_set('display_errors', 0);

// Set header for JSON response
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Build query - search by household number or head name
    $sql = "SELECT 
                h.id,
                h.household_number,
                h.household_contact,
                h.address,
                h.water_source_type,
                h.toilet_facility_type,
                h.household_head_id,
                CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, IFNULL(CONCAT(' ', r.suffix), '')) AS head_name,
                (SELECT COUNT(*) FROM household_members WHERE household_id = h.id) AS member_count
            FROM households h
            LEFT JOIN residents r ON h.household_head_id = r.id";

    if (!empty($search)) {
        $searchEscaped = $conn->real_escape_string($search);
        $sql .= " WHERE h.household_number LIKE '%{$searchEscaped}%'
                  OR r.first_name LIKE '%{$searchEscaped}%'
                  OR r.last_name LIKE '%{$searchEscaped}%'
                  OR CONCAT(r.first_name, ' ', r.last_name) LIKE '%{$searchEscaped}%'
                  OR h.address LIKE '%{$searchEscaped}%'";
    }

    $sql .= " ORDER BY h.household_number ASC LIMIT 20";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $households = [];
    while ($row = $result->fetch_assoc()) {
        $households[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $households,
        'count' => count($households)
    ]);

} catch (\Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
