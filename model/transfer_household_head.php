<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['householdId']) || empty($data['newHeadId']) || empty($data['oldHeadId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $conn->begin_transaction();
    
    $householdId = intval($data['householdId']);
    $newHeadId = intval($data['newHeadId']);
    $oldHeadId = intval($data['oldHeadId']);
    
    // Update the household head in the households table
    $updateHouseholdSql = "UPDATE households SET household_head_id = ?, updated_at = NOW() WHERE id = ?";
    $updateHouseholdStmt = $conn->prepare($updateHouseholdSql);
    $updateHouseholdStmt->bind_param('ii', $newHeadId, $householdId);
    
    if (!$updateHouseholdStmt->execute()) {
        throw new Exception('Failed to update household head: ' . $updateHouseholdStmt->error);
    }
    $updateHouseholdStmt->close();
    
    // The new head is currently a member. We need to remove them from the members list
    // because they are now the head.
    $deleteNewHeadMemberSql = "DELETE FROM household_members WHERE household_id = ? AND resident_id = ?";
    $deleteNewHeadMemberStmt = $conn->prepare($deleteNewHeadMemberSql);
    $deleteNewHeadMemberStmt->bind_param('ii', $householdId, $newHeadId);
    $deleteNewHeadMemberStmt->execute();
    $deleteNewHeadMemberStmt->close();
    
    // Process relationships array
    if (!empty($data['members']) && is_array($data['members'])) {
        $updateMemberSql = "UPDATE household_members SET relationship_to_head = ? WHERE household_id = ? AND resident_id = ?";
        $updateMemberStmt = $conn->prepare($updateMemberSql);
        
        $insertMemberSql = "INSERT INTO household_members (household_id, resident_id, relationship_to_head, is_head) VALUES (?, ?, ?, 0)";
        $insertMemberStmt = $conn->prepare($insertMemberSql);
        
        foreach ($data['members'] as $member) {
            $memberId = intval($member['residentId']);
            $relationship = $member['relationship'];
            
            if ($memberId === $oldHeadId) {
                // Insert the old head as a new member
                $insertMemberStmt->bind_param('iis', $householdId, $memberId, $relationship);
                if (!$insertMemberStmt->execute()) {
                    throw new Exception('Failed to insert old head as member: ' . $insertMemberStmt->error);
                }
            } else {
                // Update existing member relationship
                $updateMemberStmt->bind_param('sii', $relationship, $householdId, $memberId);
                if (!$updateMemberStmt->execute()) {
                    throw new Exception('Failed to update member relationship: ' . $updateMemberStmt->error);
                }
            }
        }
        
        $updateMemberStmt->close();
        $insertMemberStmt->close();
    }
    
    // Log Activity
    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Transfer Household Head';
        $log_desc = "Transferred household head for Household ID $householdId to Resident ID $newHeadId";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Household head transferred successfully']);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>