<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = 'Unauthorized access';
    $_SESSION['success'] = 'danger';
    header('Location: archive.php');
    exit;
}

// Get archive ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug logging
error_log("Restore Archive - Received ID: " . ($id ?? 'NULL'));
error_log("Restore Archive - GET params: " . print_r($_GET, true));

if ($id <= 0) {
    error_log("Restore Archive - Invalid ID: $id");
    $_SESSION['message'] = 'Invalid archive ID. Received ID: ' . ($id ?? 'NULL');
    $_SESSION['success'] = 'danger';
    header('Location: archive.php');
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

header('Location: archive.php');
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
    // Check if officials table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'officials'");
    if ($checkTable->num_rows == 0) {
        throw new Exception("Officials table does not exist");
    }
    
    $stmt = $conn->prepare("INSERT INTO officials (name, chairmanship, position, termstart, termend, status) VALUES (?, ?, ?, ?, ?, ?)");
    
    // Assign variables for bind_param
    $name = $data['name'];
    $chairmanship = $data['chairmanship'] ?? null;
    $position = $data['position'];
    $termstart = $data['termstart'] ?? null;
    $termend = $data['termend'] ?? null;
    $status = $data['status'] ?? 'Active';
    
    $stmt->bind_param("ssssss",
        $name,
        $chairmanship,
        $position,
        $termstart,
        $termend,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore official: " . $stmt->error);
    }
    $stmt->close();
}

function restoreBlotter($conn, $data) {
    // Check if blotter table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'tblblotter'");
    if ($checkTable->num_rows == 0) {
        throw new Exception("Blotter table does not exist");
    }
    
    $stmt = $conn->prepare("INSERT INTO tblblotter (complainant, respondent, victim, type, location, date, time, details, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Assign variables for bind_param
    $complainant = $data['complainant'];
    $respondent = $data['respondent'] ?? null;
    $victim = $data['victim'] ?? null;
    $type = $data['type'] ?? null;
    $location = $data['location'] ?? null;
    $date = $data['date'];
    $time = $data['time'] ?? null;
    $details = $data['details'] ?? null;
    $status = $data['status'] ?? 'Pending';

    $stmt->bind_param("sssssssss",
        $complainant,
        $respondent,
        $victim,
        $type,
        $location,
        $date,
        $time,
        $details,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to restore blotter: " . $stmt->error);
    }
    $stmt->close();
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
