<?php
// Include configuration
require_once 'config.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (empty($data['householdNumber']) || empty($data['householdAddress']) || empty($data['householdHeadId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Insert household
    $sql = "INSERT INTO households (
        household_number,
        household_head_id,
        household_contact,
        address,
        water_source_type,
        toilet_facility_type,
        notes,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'sisssss',
        $data['householdNumber'],
        $data['householdHeadId'],
        $data['householdContact'],
        $data['householdAddress'],
        $data['waterSource'],
        $data['toiletFacility'],
        $data['householdNotes']
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to insert household: ' . $stmt->error);
    }
    
    $householdId = $conn->insert_id;
    $stmt->close();
    
    // Insert household members if any
    if (!empty($data['members']) && is_array($data['members'])) {
        $memberSql = "INSERT INTO household_members (
            household_id,
            resident_id,
            relationship_to_head,
            is_head
        ) VALUES (?, ?, ?, 0)";
        
        $memberStmt = $conn->prepare($memberSql);
        
        foreach ($data['members'] as $member) {
            // Only insert if resident_id exists (from database)
            if (!empty($member['residentId'])) {
                $memberStmt->bind_param(
                    'iis',
                    $householdId,
                    $member['residentId'],
                    $member['relationship']
                );
                
                if (!$memberStmt->execute()) {
                    throw new Exception('Failed to insert household member: ' . $memberStmt->error);
                }
            }
            // Note: Members without resident_id (manual entries) are not stored in household_members table
            // They would need to be registered as residents first
        }
        
        $memberStmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Household created successfully',
        'householdId' => $householdId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error saving household: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
