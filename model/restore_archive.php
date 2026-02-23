<?php
require_once __DIR__ . '/../config.php';

// Check authentication
require_once __DIR__ . '/../auth_check.php';

// Get archive ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug logging
error_log("Restore Archive - Received ID: " . ($id ?? 'NULL'));
error_log("Restore Archive - GET params: " . print_r($_GET, true));

if ($id <= 0) {
    error_log("Restore Archive - Invalid ID: $id");
    $_SESSION['message'] = 'Invalid archive ID. Received ID: ' . ($id ?? 'NULL');
    $_SESSION['success'] = 'danger';
    header('Location: ../archive.php');
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Get archive record
    $stmt = $conn->prepare("SELECT * FROM archive WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Archive record not found");
    }
    
    $archive = $result->fetch_assoc();
    $stmt->close();
    
    // Decode the record data
    $recordData = json_decode($archive['record_data'], true);
    if ($recordData === null) {
        throw new Exception("Failed to decode archive data");
    }
    
    $archiveType = $archive['archive_type'];
    $recordId = $archive['record_id'];
    
    // Restore based on archive type
    switch ($archiveType) {
        case 'resident':
            restoreResident($conn, $recordData);
            $message = "Resident record restored successfully";
            break;
            
        case 'official':
            restoreOfficial($conn, $recordData);
            $message = "Official record restored successfully";
            break;
            
        case 'blotter':
            restoreBlotter($conn, $recordData);
            $message = "Blotter record restored successfully";
            break;
            
        case 'household':
            restoreHousehold($conn, $recordData);
            $message = "Household record restored successfully";
            break;
            
        case 'permit':
            restorePermit($conn, $recordData);
            $message = "Business permit restored successfully";
            break;
            
        case 'user':
            restoreUser($conn, $recordData);
            $message = "User account restored successfully";
            break;
            
        default:
            throw new Exception("Unknown archive type: " . $archiveType);
    }
    
    // Delete from archive after successful restoration
    $stmt = $conn->prepare("DELETE FROM archive WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Log activity
    if (isset($_SESSION['username'])) {
        try {
            $log_user = $_SESSION['username'];
            $log_action = 'Restore Archive';
            $log_desc = "Restored $archiveType record: $recordId";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            if ($log_stmt) {
                $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } catch (Exception $log_error) {
            error_log("Activity log error: " . $log_error->getMessage());
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['message'] = $message;
    $_SESSION['success'] = 'success';
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    $_SESSION['message'] = 'Error restoring record: ' . $e->getMessage();
    $_SESSION['success'] = 'danger';
    error_log("Restore error: " . $e->getMessage());
}

header('Location: ../archive.php');
exit;

// ============================================
// Restoration Functions
// ============================================

function restoreResident($conn, $data) {
    // Prepare columns and values
    $columns = [];
    $values = [];
    $types = '';
    
    // List of columns to restore (excluding id if not using original)
    $allowedColumns = [
        'resident_id', 'photo', 'first_name', 'middle_name', 'last_name', 'suffix',
        'sex', 'date_of_birth', 'age', 'place_of_birth', 'religion', 'ethnicity',
        'mobile_number', 'email', 'current_address', 'household_no', 'household_contact', 'purok',
        'civil_status', 'spouse_name', 'father_name', 'mother_name', 'number_of_children', 'household_head',
        'educational_attainment', 'employment_status', 'occupation', 'monthly_income',
        'fourps_member', 'fourps_id', 'voter_status', 'precinct_number', 'pwd_status', 'senior_citizen', 'indigent',
        'philhealth_id', 'membership_type', 'philhealth_category', 'age_health_group', 'medical_history',
        'lmp_date', 'using_fp_method', 'fp_methods_used', 'fp_status',
        'water_source_type', 'toilet_facility_type', 'remarks',
        'verification_status', 'verified_by', 'verified_at', 'rejection_reason',
        'activity_status', 'status_changed_at', 'status_changed_by', 'status_remarks'
    ];
    
    foreach ($allowedColumns as $column) {
        if (array_key_exists($column, $data)) {
            $columns[] = "`$column`";
            $values[] = $data[$column];
            $types .= 's'; // Treat all as strings for simplicity
        }
    }
    
    if (empty($columns)) {
        throw new Exception("No valid data to restore");
    }
    
    $sql = "INSERT INTO residents (" . implode(', ', $columns) . ") VALUES (" . str_repeat('?, ', count($columns) - 1) . "?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Fix for bind_param requiring references
    $bindParams = [];
    $bindParams[] = $types;
    for ($i = 0; $i < count($values); $i++) {
        $bindParams[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore resident: " . $stmt->error);
    }
    
    $residentId = $stmt->insert_id;
    $stmt->close();
    
    // Restore emergency contacts if they exist
    if (isset($data['emergency_contacts']) && is_array($data['emergency_contacts'])) {
        foreach ($data['emergency_contacts'] as $contact) {
            $stmt = $conn->prepare("INSERT INTO emergency_contacts (resident_id, contact_name, relationship, contact_number, address, priority) VALUES (?, ?, ?, ?, ?, ?)");
            
            // Assign to variables for bind_param references
            $c_name = $contact['contact_name'];
            $c_rel = $contact['relationship'];
            $c_num = $contact['contact_number'];
            $c_addr = $contact['address'] ?? null;
            $priority = $contact['priority'] ?? 1;
            
            $stmt->bind_param("issssi", 
                $residentId,
                $c_name,
                $c_rel,
                $c_num,
                $c_addr,
                $priority
            );
            $stmt->execute();
            $stmt->close();
        }
    }
}

function restoreOfficial($conn, $data) {
    // Check if barangay_officials table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'barangay_officials'");
    if ($checkTable->num_rows == 0) {
        throw new Exception("Barangay officials table does not exist");
    }
    
    // Prepare columns and values for restoration
    $columns = [];
    $values = [];
    $types = '';
    
    // List of columns to restore (excluding id to get new auto-increment)
    $allowedColumns = [
        'resident_id', 'position', 'committee', 'hierarchy_level',
        'term_start', 'term_end', 'status', 'appointment_type',
        'photo', 'contact_number', 'email', 'created_at', 'updated_at'
    ];
    
    foreach ($allowedColumns as $column) {
        if (array_key_exists($column, $data) && $data[$column] !== null) {
            $columns[] = "`$column`";
            $values[] = $data[$column];
            
            // Determine type for bind_param
            if (in_array($column, ['resident_id', 'hierarchy_level'])) {
                $types .= 'i'; // Integer
            } else {
                $types .= 's'; // String
            }
        }
    }
    
    if (empty($columns)) {
        throw new Exception("No valid data to restore for official");
    }
    
    // Build INSERT query
    $sql = "INSERT INTO barangay_officials (" . implode(', ', $columns) . ") VALUES (" . str_repeat('?, ', count($columns) - 1) . "?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Bind parameters dynamically
    $bindParams = [];
    $bindParams[] = $types;
    for ($i = 0; $i < count($values); $i++) {
        $bindParams[] = &$values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore official: " . $stmt->error);
    }
    
    $stmt->close();
}

function restoreBlotter($conn, $data) {
    // Check if blotter_records table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'blotter_records'");
    if ($checkTable->num_rows == 0) {
        throw new Exception("Blotter records table does not exist");
    }

    // Generate a new unique record number to avoid conflicts
    $year = date('Y');
    $maxResult = $conn->query("SELECT MAX(CAST(SUBSTRING(record_number, 9) AS UNSIGNED)) as max_num FROM blotter_records WHERE record_number LIKE 'BR-{$year}-%'");
    $maxRow = $maxResult ? $maxResult->fetch_assoc() : null;
    $nextNum = (($maxRow['max_num'] ?? 0)) + 1;
    $recordNumber = sprintf("BR-%s-%06d", $year, $nextNum);

    // Use original record number if it doesn't conflict, otherwise use new one
    if (!empty($data['record_number'])) {
        $checkDup = $conn->prepare("SELECT id FROM blotter_records WHERE record_number = ?");
        $checkDup->bind_param("s", $data['record_number']);
        $checkDup->execute();
        $dupResult = $checkDup->get_result();
        if ($dupResult->num_rows === 0) {
            $recordNumber = $data['record_number'];
        }
        $checkDup->close();
    }

    // Map archived data to blotter_records columns
    $incidentType        = $data['incident_type']        ?? ($data['complaint'] ?? 'Unknown');
    $incidentDescription = $data['incident_description'] ?? '';
    $incidentDate        = $data['incident_date']        ?? ($data['date'] ?? date('Y-m-d H:i:s'));
    $incidentLocation    = $data['incident_location']    ?? '';
    $dateReported        = $data['date_reported']        ?? ($data['date'] ?? date('Y-m-d H:i:s'));
    $reportedBy          = $data['reported_by']          ?? null;
    $status              = $data['status']               ?? 'Pending';
    $resolution          = $data['resolution']           ?? null;
    $remarks             = $data['remarks']              ?? null;

    // Insert main blotter record
    $stmt = $conn->prepare("
        INSERT INTO blotter_records
            (record_number, incident_type, incident_description, incident_date,
             incident_location, date_reported, reported_by, status, resolution, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssssssss",
        $recordNumber,
        $incidentType,
        $incidentDescription,
        $incidentDate,
        $incidentLocation,
        $dateReported,
        $reportedBy,
        $status,
        $resolution,
        $remarks
    );
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore blotter record: " . $stmt->error);
    }
    $blotterId = $conn->insert_id;
    $stmt->close();

    // Restore complainants
    if (!empty($data['complainants']) && is_array($data['complainants'])) {
        $cStmt = $conn->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($data['complainants'] as $c) {
            $resId     = $c['resident_id'] ?? null;
            $cName     = $c['name']           ?? '';
            $cAddr     = $c['address']        ?? null;
            $cContact  = $c['contact_number'] ?? null;
            $cStmt->bind_param("isssss", $blotterId, $resId, $cName, $cAddr, $cContact, $nullStmt);
            $nullStmt = null;
            $cStmt->execute();
        }
        $cStmt->close();
    } elseif (!empty($data['complainant'])) {
        // Fallback: single complainant stored as plain string
        $cName = $data['complainant'];
        $nullVal = null;
        $stmt = $conn->prepare("
            INSERT INTO blotter_complainants (blotter_id, name) VALUES (?, ?)
        ");
        $stmt->bind_param("is", $blotterId, $cName);
        $stmt->execute();
        $stmt->close();
    }

    // Restore victims
    if (!empty($data['victims']) && is_array($data['victims'])) {
        $vStmt = $conn->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
            VALUES (?, ?, ?, ?, ?, 'VICTIM')
        ");
        foreach ($data['victims'] as $v) {
            $resId    = $v['resident_id']    ?? null;
            $vName    = $v['name']           ?? '';
            $vAddr    = $v['address']        ?? null;
            $vContact = $v['contact_number'] ?? null;
            $vStmt->bind_param("issss", $blotterId, $resId, $vName, $vAddr, $vContact);
            $vStmt->execute();
        }
        $vStmt->close();
    }

    // Restore witnesses
    if (!empty($data['witnesses']) && is_array($data['witnesses'])) {
        $wStmt = $conn->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
            VALUES (?, ?, ?, ?, ?, 'WITNESS')
        ");
        foreach ($data['witnesses'] as $w) {
            $resId    = $w['resident_id']    ?? null;
            $wName    = $w['name']           ?? '';
            $wAddr    = $w['address']        ?? null;
            $wContact = $w['contact_number'] ?? null;
            $wStmt->bind_param("issss", $blotterId, $resId, $wName, $wAddr, $wContact);
            $wStmt->execute();
        }
        $wStmt->close();
    }

    // Restore respondents
    if (!empty($data['respondents']) && is_array($data['respondents'])) {
        $rStmt = $conn->prepare("
            INSERT INTO blotter_respondents (blotter_id, resident_id, name, address, contact_number)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($data['respondents'] as $r) {
            $resId    = $r['resident_id']    ?? null;
            $rName    = $r['name']           ?? '';
            $rAddr    = $r['address']        ?? null;
            $rContact = $r['contact_number'] ?? null;
            $rStmt->bind_param("issss", $blotterId, $resId, $rName, $rAddr, $rContact);
            $rStmt->execute();
        }
        $rStmt->close();
    } elseif (!empty($data['respondent'])) {
        // Fallback: single respondent stored as plain string
        $rName = $data['respondent'];
        $stmt = $conn->prepare("
            INSERT INTO blotter_respondents (blotter_id, name) VALUES (?, ?)
        ");
        $stmt->bind_param("is", $blotterId, $rName);
        $stmt->execute();
        $stmt->close();
    }
}

function restoreHousehold($conn, $data) {
    // Insert household
    $stmt = $conn->prepare("INSERT INTO households (household_number, household_head_id, household_contact, address, water_source_type, toilet_facility_type, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $h_num = $data['household_number'];
    $h_head = $data['household_head_id'];
    $h_contact = $data['household_contact'] ?? null;
    $h_addr = $data['address'] ?? null;
    $h_water = $data['water_source_type'] ?? null;
    $h_toilet = $data['toilet_facility_type'] ?? null;
    $h_notes = $data['notes'] ?? null;
    $h_created = $data['created_at'] ?? date('Y-m-d H:i:s');
    $h_updated = $data['updated_at'] ?? date('Y-m-d H:i:s');

    $stmt->bind_param("sisssssss", $h_num, $h_head, $h_contact, $h_addr, $h_water, $h_toilet, $h_notes, $h_created, $h_updated);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore household: " . $stmt->error);
    }
    $householdId = $stmt->insert_id;
    $stmt->close();
    
    // Insert members
    if (!empty($data['members']) && is_array($data['members'])) {
        $mStmt = $conn->prepare("INSERT INTO household_members (household_id, resident_id, relationship_to_head, is_head) VALUES (?, ?, ?, ?)");
        foreach ($data['members'] as $member) {
            $m_res_id = $member['resident_id'];
            $m_rel = $member['relationship_to_head'];
            $m_is_head = $member['is_head'] ?? 0;
            
            $mStmt->bind_param("iisi", $householdId, $m_res_id, $m_rel, $m_is_head);
            $mStmt->execute();
        }
        $mStmt->close();
    }
}

function restorePermit($conn, $data) {
    // Check if permit table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'tblpermit'");
    if ($checkTable->num_rows == 0) {
        throw new Exception("Permit table does not exist");
    }
    
    $stmt = $conn->prepare("INSERT INTO tblpermit (business_name, owner_name, business_address, type_of_business, or_no, amount, date_issued, date_expired) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Assign variables for bind_param
    $b_name = $data['business_name'];
    $o_name = $data['owner_name'];
    $b_addr = $data['business_address'] ?? null;
    $b_type = $data['type_of_business'] ?? null;
    $or_no = $data['or_no'] ?? null;
    $amount = $data['amount'] ?? 0;
    $d_issued = $data['date_issued'] ?? null;
    $d_expired = $data['date_expired'] ?? null;

    $stmt->bind_param("sssssdss",
        $b_name,
        $o_name,
        $b_addr,
        $b_type,
        $or_no,
        $amount,
        $d_issued,
        $d_expired
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore permit: " . $stmt->error);
    }
    $stmt->close();
}

function restoreUser($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $role = $data['role'] ?? $data['type'] ?? 'Staff';
    $status = $data['status'] ?? 'Active';
    $fullName = $data['full_name'] ?? $data['name'] ?? $data['username'];
    $email = $data['email'] ?? null;
    $username = $data['username'];
    $password = $data['password'];
    
    $stmt->bind_param("ssssss",
        $username,
        $password,
        $fullName,
        $email,
        $role,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore user: " . $stmt->error);
    }
    $stmt->close();
}
?>
