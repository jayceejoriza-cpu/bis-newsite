<?php
/**
 * Save Blotter Record
 * Handles saving new blotter records to the database
 */

// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Database connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Validate required fields
    $requiredFields = ['status', 'incident_date', 'incident_type', 'incident_location', 'incident_description'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Validate at least one complainant
    if (empty($_POST['complainant_name']) || !is_array($_POST['complainant_name']) || empty($_POST['complainant_name'][0])) {
        throw new Exception('At least one complainant is required');
    }
    
    // Validate at least one victim
    if (empty($_POST['victim_name']) || !is_array($_POST['victim_name']) || empty($_POST['victim_name'][0])) {
        throw new Exception('At least one victim is required');
    }
    
    // Validate at least one respondent
    if (empty($_POST['respondent_name']) || !is_array($_POST['respondent_name']) || empty($_POST['respondent_name'][0])) {
        throw new Exception('At least one respondent is required');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Generate unique record number
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(record_number, 9) AS UNSIGNED)) as max_num FROM blotter_records WHERE record_number LIKE ?");
    $stmt->execute(["BR-{$year}-%"]);
    $result = $stmt->fetch();
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $recordNumber = sprintf("BR-%s-%06d", $year, $nextNum);
    
    // Prepare blotter record data
    $status = $_POST['status'];
    $incidentDate = $_POST['incident_date'];
    $incidentType = $_POST['incident_type'];
    $incidentLocation = $_POST['incident_location'];
    $incidentDescription = $_POST['incident_description'];
    $resolution = $_POST['resolution'] ?? null;
    $reportedBy = $_POST['reported_by'] ?? null;
    
    // Insert blotter record
    $stmt = $pdo->prepare("
        INSERT INTO blotter_records (
            record_number, incident_type, incident_description, incident_date, 
            incident_location, date_reported, reported_by, status, resolution
        ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)
    ");
    
    $stmt->execute([
        $recordNumber,
        $incidentType,
        $incidentDescription,
        $incidentDate,
        $incidentLocation,
        $reportedBy,
        $status,
        $resolution
    ]);
    
    $blotterId = $pdo->lastInsertId();
    
    // Insert complainants
    if (!empty($_POST['complainant_name']) && is_array($_POST['complainant_name'])) {
        $complainantNames = $_POST['complainant_name'];
        $complainantAddresses = $_POST['complainant_address'] ?? [];
        $complainantContacts = $_POST['complainant_contact'] ?? [];
        $complainantResidentIds = $_POST['complainant_resident_id'] ?? [];
        
        $stmt = $pdo->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($complainantNames as $index => $name) {
            if (!empty(trim($name))) {
                $residentId = !empty($complainantResidentIds[$index]) ? $complainantResidentIds[$index] : null;
                $address = $complainantAddresses[$index] ?? null;
                $contact = !empty($complainantContacts[$index]) ? '+63' . $complainantContacts[$index] : null;
                
                $stmt->execute([$blotterId, $residentId, trim($name), $address, $contact]);
            }
        }
    }
    
    // Insert victims
    if (!empty($_POST['victim_name']) && is_array($_POST['victim_name'])) {
        $victimNames = $_POST['victim_name'];
        $victimAddresses = $_POST['victim_address'] ?? [];
        $victimContacts = $_POST['victim_contact'] ?? [];
        $victimResidentIds = $_POST['victim_resident_id'] ?? [];
        
        // We'll store victims in complainants table with a flag or create a separate victims table
        // For now, let's add them to complainants with a note
        $stmt = $pdo->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
            VALUES (?, ?, ?, ?, ?, 'VICTIM')
        ");
        
        foreach ($victimNames as $index => $name) {
            if (!empty(trim($name))) {
                $residentId = !empty($victimResidentIds[$index]) ? $victimResidentIds[$index] : null;
                $address = $victimAddresses[$index] ?? null;
                $contact = !empty($victimContacts[$index]) ? '+63' . $victimContacts[$index] : null;
                
                $stmt->execute([$blotterId, $residentId, trim($name), $address, $contact]);
            }
        }
    }
    
    // Insert respondents
    if (!empty($_POST['respondent_name']) && is_array($_POST['respondent_name'])) {
        $respondentNames = $_POST['respondent_name'];
        $respondentAddresses = $_POST['respondent_address'] ?? [];
        $respondentContacts = $_POST['respondent_contact'] ?? [];
        $respondentResidentIds = $_POST['respondent_resident_id'] ?? [];
        
        $stmt = $pdo->prepare("
            INSERT INTO blotter_respondents (blotter_id, resident_id, name, address, contact_number)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($respondentNames as $index => $name) {
            if (!empty(trim($name))) {
                $residentId = !empty($respondentResidentIds[$index]) ? $respondentResidentIds[$index] : null;
                $address = $respondentAddresses[$index] ?? null;
                $contact = !empty($respondentContacts[$index]) ? '+63' . $respondentContacts[$index] : null;
                
                $stmt->execute([$blotterId, $residentId, trim($name), $address, $contact]);
            }
        }
    }
    
    // Insert witnesses (if any)
    if (!empty($_POST['witness_name']) && is_array($_POST['witness_name'])) {
        $witnessNames = $_POST['witness_name'];
        $witnessAddresses = $_POST['witness_address'] ?? [];
        $witnessContacts = $_POST['witness_contact'] ?? [];
        $witnessResidentIds = $_POST['witness_resident_id'] ?? [];
        
        // Store witnesses in complainants table with 'WITNESS' flag
        $stmt = $pdo->prepare("
            INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
            VALUES (?, ?, ?, ?, ?, 'WITNESS')
        ");
        
        foreach ($witnessNames as $index => $name) {
            if (!empty(trim($name))) {
                $residentId = !empty($witnessResidentIds[$index]) ? $witnessResidentIds[$index] : null;
                $address = $witnessAddresses[$index] ?? null;
                $contact = !empty($witnessContacts[$index]) ? '+63' . $witnessContacts[$index] : null;
                
                $stmt->execute([$blotterId, $residentId, trim($name), $address, $contact]);
            }
        }
    }
    
    // Insert actions taken (if any)
    if (!empty($_POST['action_date']) && is_array($_POST['action_date'])) {
        $actionDates = $_POST['action_date'];
        $actionOfficers = $_POST['action_officer'] ?? [];
        $actionDetails = $_POST['action_details'] ?? [];
        
        // Store actions in remarks as JSON for now
        $actions = [];
        foreach ($actionDates as $index => $date) {
            if (!empty($date)) {
                $actions[] = [
                    'date' => $date,
                    'officer' => $actionOfficers[$index] ?? '',
                    'details' => $actionDetails[$index] ?? ''
                ];
            }
        }
        
        if (!empty($actions)) {
            $stmt = $pdo->prepare("UPDATE blotter_records SET remarks = ? WHERE id = ?");
            $stmt->execute([json_encode($actions), $blotterId]);
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Blotter record saved successfully';
    $response['data'] = [
        'id' => $blotterId,
        'record_number' => $recordNumber
    ];
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in save_blotter_record.php: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in save_blotter_record.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
