<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Disable error display so that warnings don't corrupt the JSON response
ini_set('display_errors', 0);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if this is an UPDATE or CREATE operation
$isUpdate = !empty($data['householdId']);

// Validate required fields based on operation type
if ($isUpdate) {
    // For UPDATE: householdId, address, and headId are required
    if (empty($data['householdId']) || empty($data['householdAddress']) || empty($data['householdHeadId'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields for update'
        ]);
        exit;
    }
} else {
    // For CREATE: householdNumber, address, and headId are required
    if (empty($data['householdNumber']) || empty($data['householdAddress']) || empty($data['householdHeadId'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields for creation'
        ]);
        exit;
    }
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Validate household head is not a minor
    $headId = intval($data['householdHeadId']);
    if ($headId > 0) {
        $checkAgeSql = "SELECT date_of_birth FROM residents WHERE id = ?";
        $checkAgeStmt = $conn->prepare($checkAgeSql);
        $checkAgeStmt->bind_param('i', $headId);
        $checkAgeStmt->execute();
        $ageResult = $checkAgeStmt->get_result()->fetch_assoc();
        $checkAgeStmt->close();
        if ($ageResult && !empty($ageResult['date_of_birth'])) {
            $dob = new DateTime($ageResult['date_of_birth']);
            $now = new DateTime();
            if ($now->diff($dob)->y < 18) {
                throw new Exception('A minor cannot be assigned as a household head.');
            }
        }
    }
    
    if ($isUpdate) {
        // UPDATE OPERATION
        $householdId = intval($data['householdId']);
        
        // NOTE: We don't validate household head during UPDATE because:
        // 1. The household head is immutable (cannot be changed via UI)
        // 2. It will always be the same resident, so validation would always pass for the current household
        // 3. The frontend prevents changing the household head during edit
        
        // Update household
        $updateSql = "UPDATE households SET
                      household_number = ?,
                      household_head_id = ?,
                      household_contact = ?,
                      address = ?,
                      water_source_type = ?,
                      toilet_facility_type = ?,
                      ownership_status = ?,
                      landlord_resident_id = ?,
                      landlord_name = ?,
                      notes = ?,
                      updated_at = NOW()
                      WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        $landlordId = !empty($data['landlordResidentId']) ? intval($data['landlordResidentId']) : null;
        $updateStmt->bind_param(
            'sisssssissi',
            $data['householdNumber'],
            $data['householdHeadId'],
            $data['householdContact'],
            $data['householdAddress'],
            $data['waterSource'],
            $data['toiletFacility'],
            $data['ownershipStatus'],
            $landlordId,
            $data['landlordName'],
            $data['householdNotes'],
            $householdId
        );
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update household: ' . $updateStmt->error);
        }
        $updateStmt->close();
        
        // Fetch existing members for logging
        $existingMembersSql = "SELECT hm.resident_id, hm.relationship_to_head, hm.is_head, CONCAT(r.first_name, ' ', r.last_name) as name, h.household_number FROM household_members hm JOIN residents r ON hm.resident_id = r.id JOIN households h ON hm.household_id = h.id WHERE hm.household_id = ?";
        $existingMembersStmt = $conn->prepare($existingMembersSql);
        $existingMembersStmt->bind_param('i', $householdId);
        $existingMembersStmt->execute();
        $res = $existingMembersStmt->get_result();
        $oldMembers = [];
        $oldMembersData = [];
        while($row = $res->fetch_assoc()) {
            $oldMembers[$row['resident_id']] = $row['name'];
            $oldMembersData[$row['resident_id']] = [
                'household_id' => $householdId,
                'household_number' => $row['household_number'],
                'resident_id' => $row['resident_id'],
                'resident_name' => $row['name'],
                'relationship_to_head' => $row['relationship_to_head'],
                'is_head' => $row['is_head']
            ];
        }
        $existingMembersStmt->close();

        // Delete existing members for this household
        $deleteMembersSql = "DELETE FROM household_members WHERE household_id = ?";
        $deleteMembersStmt = $conn->prepare($deleteMembersSql);
        $deleteMembersStmt->bind_param('i', $householdId);
        $deleteMembersStmt->execute();
        $deleteMembersStmt->close();
        
        // Insert updated members
        if (!empty($data['members']) && is_array($data['members'])) {
            $memberSql = "INSERT INTO household_members (
                household_id,
                resident_id,
                relationship_to_head,
                is_head
            ) VALUES (?, ?, ?, 0)";
            
            $memberStmt = $conn->prepare($memberSql);
            
            $addedMembers = [];

            foreach ($data['members'] as $member) {
                // Only insert if resident_id exists (from database)
                if (!empty($member['residentId'])) {
                    // NOTE: We don't validate if member is already in another household during UPDATE
                    // because we delete all existing members first, then re-insert them.
                    // The member might be in the same household we're editing, which is expected.
                    
                    // Only check if member is the same as household head
                    if ($member['residentId'] == $data['householdHeadId']) {
                        throw new Exception('The household head cannot be added as a member');
                    }
                    
                    $memberStmt->bind_param(
                        'iis',
                        $householdId,
                        $member['residentId'],
                        $member['relationship']
                    );
                    
                    if (!$memberStmt->execute()) {
                        throw new Exception('Failed to insert household member: ' . $memberStmt->error);
                    }
                    $addedMembers[] = $member['name'] ?? "Resident ID " . $member['residentId'];
                }
            }
            
            $memberStmt->close();
            
            if (isset($_SESSION['username'])) {
                $hhStmt = $conn->prepare("SELECT household_number FROM households WHERE id = ?");
                $hhStmt->bind_param('i', $householdId);
                $hhStmt->execute();
                $hhRes = $hhStmt->get_result()->fetch_assoc();
                $hhNum = $hhRes['household_number'] ?? "ID $householdId";
                $hhStmt->close();

                $log_user = $_SESSION['username'];
                
                $newMembers = [];
                foreach ($data['members'] as $m) {
                    if (!empty($m['residentId'])) {
                        $newMembers[$m['residentId']] = $m['name'] ?? "Resident ID " . $m['residentId'];
                    }
                }
                
                $addedIds = array_diff(array_keys($newMembers), array_keys($oldMembers));
                $removedIds = array_diff(array_keys($oldMembers), array_keys($newMembers));
                
                foreach ($addedIds as $addId) {
                    $resName = $newMembers[$addId];
                    $log_action = 'Add Household Members';
                    $log_desc = "Added $resName to household $hhNum";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
                
                foreach ($removedIds as $remId) {
                    // Archive the removed member
                    if (isset($oldMembersData[$remId])) {
                        $archiveType = 'household_member';
                        $recordId = $oldMembersData[$remId]['household_number'] ?? $remId;
                        $recordData = json_encode($oldMembersData[$remId]);
                        $deletedBy = $log_user;
                        $archiveStmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by) VALUES (?, ?, ?, ?)");
                        $archiveStmt->bind_param("ssss", $archiveType, $recordId, $recordData, $deletedBy);
                        $archiveStmt->execute();
                        $archiveStmt->close();
                    }
                    
                    $resName = $oldMembers[$remId];
                    $log_action = 'Delete Household Members';
                    $log_desc = "Deleted $resName from household $hhNum";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        if (isset($_SESSION['username'])) {
            $hhStmt = $conn->prepare("SELECT household_number FROM households WHERE id = ?");
            $hhStmt->bind_param('i', $householdId);
            $hhStmt->execute();
            $hhRes = $hhStmt->get_result()->fetch_assoc();
            $hhNum = $hhRes['household_number'] ?? "ID $householdId";
            $hhStmt->close();

            $log_user = $_SESSION['username'];
            $log_action = 'Update Household';
            $log_desc = "Updated household details for $hhNum";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Household updated successfully',
            'householdId' => $householdId
        ]);
        
    } else {
        // CREATE OPERATION
        // Validate that household head is not already assigned to another household
        $checkHeadSql = "SELECT h.id, h.household_number 
                         FROM households h 
                         WHERE h.household_head_id = ?";
        $checkHeadStmt = $conn->prepare($checkHeadSql);
        $checkHeadStmt->bind_param('i', $data['householdHeadId']);
        $checkHeadStmt->execute();
        $checkHeadResult = $checkHeadStmt->get_result();
        
        if ($checkHeadResult->num_rows > 0) {
            $existingHousehold = $checkHeadResult->fetch_assoc();
            $checkHeadStmt->close();
            throw new Exception('This resident is already assigned as household head in household ' . $existingHousehold['household_number']);
        }
        $checkHeadStmt->close();
        
        // Validate that household head is not already a member of another household
        $checkHeadMemberSql = "SELECT hm.household_id, h.household_number 
                               FROM household_members hm
                               JOIN households h ON hm.household_id = h.id
                               WHERE hm.resident_id = ?";
        $checkHeadMemberStmt = $conn->prepare($checkHeadMemberSql);
        $checkHeadMemberStmt->bind_param('i', $data['householdHeadId']);
        $checkHeadMemberStmt->execute();
        $checkHeadMemberResult = $checkHeadMemberStmt->get_result();
        
        if ($checkHeadMemberResult->num_rows > 0) {
            $existingHousehold = $checkHeadMemberResult->fetch_assoc();
            $checkHeadMemberStmt->close();
            throw new Exception('This resident is already a member of household ' . $existingHousehold['household_number']);
        }
        $checkHeadMemberStmt->close();
        
        // Validate members are not already assigned to any household
        if (!empty($data['members']) && is_array($data['members'])) {
            foreach ($data['members'] as $member) {
                if (!empty($member['residentId'])) {
                    // Check if member is already a household head
                    $checkMemberHeadSql = "SELECT h.id, h.household_number 
                                           FROM households h 
                                           WHERE h.household_head_id = ?";
                    $checkMemberHeadStmt = $conn->prepare($checkMemberHeadSql);
                    $checkMemberHeadStmt->bind_param('i', $member['residentId']);
                    $checkMemberHeadStmt->execute();
                    $checkMemberHeadResult = $checkMemberHeadStmt->get_result();
                    
                    if ($checkMemberHeadResult->num_rows > 0) {
                        $existingHousehold = $checkMemberHeadResult->fetch_assoc();
                        $checkMemberHeadStmt->close();
                        throw new Exception($member['name'] . ' is already assigned as household head in household ' . $existingHousehold['household_number']);
                    }
                    $checkMemberHeadStmt->close();
                    
                    // Check if member is already in another household
                    $checkMemberSql = "SELECT hm.household_id, h.household_number 
                                       FROM household_members hm
                                       JOIN households h ON hm.household_id = h.id
                                       WHERE hm.resident_id = ?";
                    $checkMemberStmt = $conn->prepare($checkMemberSql);
                    $checkMemberStmt->bind_param('i', $member['residentId']);
                    $checkMemberStmt->execute();
                    $checkMemberResult = $checkMemberStmt->get_result();
                    
                    if ($checkMemberResult->num_rows > 0) {
                        $existingHousehold = $checkMemberResult->fetch_assoc();
                        $checkMemberStmt->close();
                        throw new Exception($member['name'] . ' is already a member of household ' . $existingHousehold['household_number']);
                    }
                    $checkMemberStmt->close();
                    
                    // Check if member is the same as household head
                    if ($member['residentId'] == $data['householdHeadId']) {
                        throw new Exception('The household head cannot be added as a member');
                    }
                }
            }
        }
        
        // Insert household
        $sql = "INSERT INTO households (
            household_number,
            household_head_id,
            household_contact,
            address,
            water_source_type,
            toilet_facility_type,
            ownership_status,
            landlord_resident_id,
            landlord_name,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $landlordId = !empty($data['landlordResidentId']) ? intval($data['landlordResidentId']) : null;
        $stmt->bind_param(
            'sisssssiss',
            $data['householdNumber'],
            $data['householdHeadId'],
            $data['householdContact'],
            $data['householdAddress'],
            $data['waterSource'],
            $data['toiletFacility'],
            $data['ownershipStatus'],
            $landlordId,
            $data['landlordName'],
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
        
        if (isset($_SESSION['username'])) {
            $log_user = $_SESSION['username'];
            $log_action = 'Create Household';
            $log_desc = "Created new household " . $data['householdNumber'];
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Household created successfully',
            'householdId' => $householdId
        ]);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
