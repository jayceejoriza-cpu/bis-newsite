<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get household ID from query parameter
$householdId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($householdId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid household ID'
    ]);
    exit;
}

try {
    // Fetch household details
    $sql = "SELECT 
                h.*,
                CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, '')) AS head_name,
                r.date_of_birth AS head_dob,
                r.sex AS head_sex,
                r.mobile_number AS head_mobile
            FROM households h
            LEFT JOIN residents r ON h.household_head_id = r.id
            WHERE h.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $householdId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Household not found'
        ]);
        exit;
    }
    
    $household = $result->fetch_assoc();
    $stmt->close();
    
    // Fetch household members
    $membersSql = "SELECT 
                        hm.*,
                        CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
                        r.date_of_birth,
                        r.sex,
                        r.mobile_number
                    FROM household_members hm
                    LEFT JOIN residents r ON hm.resident_id = r.id
                    WHERE hm.household_id = ?
                    ORDER BY hm.is_head DESC, hm.id ASC";
    
    $membersStmt = $conn->prepare($membersSql);
    $membersStmt->bind_param('i', $householdId);
    $membersStmt->execute();
    $membersResult = $membersStmt->get_result();
    
    $members = [];
    while ($member = $membersResult->fetch_assoc()) {
        $members[] = $member;
    }
    $membersStmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'household' => $household,
        'members' => $members
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
