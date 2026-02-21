<?php
// Include configuration
require_once '../config.php';

// Suppress display errors so PHP warnings don't corrupt JSON output
// Must be called after config.php since config.php might enable them
ini_set('display_errors', 0);

// Set header for JSON response
header('Content-Type: application/json');

// Check authentication
// Use manual check to return JSON instead of HTML redirect on failure
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Fetch all households with head information and member count
    $sql = "SELECT 
                h.id,
                h.household_number,
                h.household_contact,
                h.address,
                h.water_source_type,
                h.toilet_facility_type,
                h.notes,
                h.created_at,
                h.household_head_id,
                CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, IFNULL(CONCAT(' ', r.suffix), '')) AS head_name,
                r.first_name AS head_first_name,
                r.last_name AS head_last_name,
                (SELECT COUNT(*) FROM household_members WHERE household_id = h.id) AS member_count
            FROM households h
            LEFT JOIN residents r ON h.household_head_id = r.id
            ORDER BY h.created_at DESC";
    
    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $households = [];
    
    while ($row = $result->fetch_assoc()) {
        // Determine household size category
        $memberCount = (int)$row['member_count'];
        if ($memberCount == 1) {
            $size = 'single-person';
        } elseif ($memberCount >= 2 && $memberCount <= 4) {
            $size = 'small';
        } elseif ($memberCount >= 5 && $memberCount <= 6) {
            $size = 'medium';
        } elseif ($memberCount >= 7 && $memberCount <= 9) {
            $size = 'large';
        } else {
            $size = 'very-large';
        }
        
        $row['size'] = $size;
        $households[] = $row;
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $households,
        'count' => count($households)
    ]);
    
} catch (\Throwable $e) {
    // Return error response (catches both Exception and PHP fatal errors)
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
