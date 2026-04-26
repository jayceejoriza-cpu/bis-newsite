<?php
/**
 * Edit Blotter Record
 * Modal for editing existing blotter records
 */

// Include configuration first
require_once __DIR__ . '/../config.php';

// Error Suppression for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header for AJAX responses
if (isset($_GET['ajax']) && $_GET['ajax'] === 'true') {
    header('Content-Type: application/json');
}

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
    
    // Check if this is an AJAX request to fetch record data
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'true' && isset($_GET['id'])) {
        $recordId = $_GET['id'];
        
        // Fetch blotter record
        $stmt = $pdo->prepare("
            SELECT * FROM blotter_records WHERE id = ?
        ");
        $stmt->execute([$recordId]);
        $record = $stmt->fetch();
        
        if (!$record) {
            throw new Exception('Blotter record not found');
        }
        
        // Fetch complainants
        $stmt = $pdo->prepare("
            SELECT * FROM blotter_complainants 
            WHERE blotter_id = ? AND (statement IS NULL OR statement = '' OR statement = 'COMPLAINANT')
            ORDER BY id
        ");
        $stmt->execute([$recordId]);
        $complainants = $stmt->fetchAll();
        
        // Fetch victims
        $stmt = $pdo->prepare("
            SELECT * FROM blotter_complainants 
            WHERE blotter_id = ? AND statement = 'VICTIM'
            ORDER BY id
        ");
        $stmt->execute([$recordId]);
        $victims = $stmt->fetchAll();
        
        // Fetch respondents
        $stmt = $pdo->prepare("
            SELECT * FROM blotter_respondents 
            WHERE blotter_id = ?
            ORDER BY id
        ");
        $stmt->execute([$recordId]);
        $respondents = $stmt->fetchAll();
        
        // Fetch witnesses
        $stmt = $pdo->prepare("
            SELECT * FROM blotter_complainants 
            WHERE blotter_id = ? AND statement = 'WITNESS'
            ORDER BY id
        ");
        $stmt->execute([$recordId]);
        $witnesses = $stmt->fetchAll();
        
        // Fetch mediation count for strike logic
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM blotter_history 
            WHERE blotter_id = ? 
            AND action_type IN ('Rescheduled', 'Status & Schedule Updated') 
            AND new_value LIKE '%Scheduled for Mediation%'
        ");
        $stmt->execute([$recordId]);
        $mediationCount = (int)$stmt->fetchColumn();

        // Parse actions from remarks (if stored as JSON)
        $actions = [];
        if (!empty($record['remarks'])) {
            $decoded = json_decode($record['remarks'], true);
            if (is_array($decoded)) {
                $actions = $decoded;
            }
        }
        
        // Success response
        $response['success'] = true;
        $response['data'] = [
            'record' => $record,
            'complainants' => $complainants,
            'victims' => $victims,
            'respondents' => $respondents,
            'witnesses' => $witnesses,
            'actions' => $actions,
            'mediation_count' => $mediationCount
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Check if this is an update request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
        $recordId = $_POST['record_id'];
        $status = trim($_POST['status'] ?? '');

        // 0. Security Block: Verify mediation limit (The Guardrail)
        if ($status === 'Scheduled for Mediation') {
            $strikeStmt = $pdo->prepare("
                SELECT COUNT(*) FROM blotter_history 
                WHERE blotter_id = ? 
                AND action_type IN ('Rescheduled', 'Status & Schedule Updated') 
                AND new_value LIKE '%Scheduled for Mediation%'
            ");
            $strikeStmt->execute([$recordId]);
            $strikeCount = (int)$strikeStmt->fetchColumn();

            if ($strikeCount >= 3) {
                throw new Exception('Security Block: Mediation limit exceeded. This case must be endorsed.');
            }
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
        
        // 1. Audit Trail: Capture OLD values for comparison BEFORE update
        $old_stmt = $pdo->prepare("SELECT status, mediation_schedule, incident_location, incident_description, incident_date, resolution FROM blotter_records WHERE id = ?");
        $old_stmt->execute([$recordId]);
        $old_data = $old_stmt->fetch();
        
        if (!$old_data) {
            throw new Exception("Record not found for history logging.");
        }

        $user_id = $_SESSION['user_id'] ?? 0;
        $current_timestamp = date('Y-m-d H:i:s');

        // ── A. Granular Audit Logging (Requirement) ──
        $granular_map = [
            'incident_location' => [
                'action' => 'Location Updated',
                'remarks' => 'Incident location was modified'
            ],
            'incident_description' => [
                'action' => 'Narrative Modified',
                'remarks' => 'Detailed narrative was updated'
            ],
            'incident_date' => [
                'action' => 'Schedule Adjusted',
                'remarks' => 'Incident date/time was adjusted'
            ]
        ];

        foreach ($granular_map as $col => $cfg) {
            $old_val = trim($old_data[$col] ?? '');
            $new_val = trim($_POST[$col] ?? '');
            
            // Use specialized comparison for dates to ignore micro-second mismatches
            $is_changed = ($col === 'incident_date') ? (strtotime($old_val) !== strtotime($new_val)) : ($old_val !== $new_val);

            if ($is_changed) {
                error_log("Audit Log: Detected change in $col. Logging action: {$cfg['action']}");
                $hist_stmt = $pdo->prepare("INSERT INTO blotter_history (blotter_id, action_type, old_value, new_value, remarks, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $hist_stmt->execute([$recordId, $cfg['action'], $old_val, $new_val, $cfg['remarks'], $user_id, $current_timestamp]);
            }
        }

        // ── B. Status & Mediation Comparison ──
        $old_sched_ts = $old_data['mediation_schedule'] ? strtotime(date('Y-m-d H:i', strtotime($old_data['mediation_schedule']))) : 0;
        $new_sched_ts = !empty($_POST['mediation_schedule']) ? strtotime($_POST['mediation_schedule']) : 0;
        $status = trim($_POST['status'] ?? '');
        $status_changed = ($old_data['status'] !== $status);
        $schedule_changed = ($old_sched_ts !== $new_sched_ts);

        if ($status_changed || $schedule_changed) {
            $action_type = "Status Updated";
            if ($status_changed && $schedule_changed && !in_array($status, ['Endorsed to Police', 'Settled', 'Dismissed'])) {
                $action_type = "Status & Schedule Updated";
            } elseif ($schedule_changed && !$status_changed) {
                $action_type = "Rescheduled";
            }

            $old_val_str = "Status: " . $old_data['status'] . ($old_data['mediation_schedule'] ? " | Sched: " . date('F j, Y h:i A', strtotime($old_data['mediation_schedule'])) : "");
            $sched_part = ($status === 'Scheduled for Mediation' && !empty($_POST['mediation_schedule'])) ? " | Sched: " . date('F j, Y h:i A', $new_sched_ts) : "";
            $new_val_str = "Status: " . $status . $sched_part;
            
            $hist_remarks = !empty($_POST['resolution']) ? $_POST['resolution'] : 'Updated via Edit Modal';
            $history_stmt = $pdo->prepare("INSERT INTO blotter_history (blotter_id, action_type, old_value, new_value, remarks, changed_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->execute([$recordId, $action_type, $old_val_str, $new_val_str, $hist_remarks, $user_id, $current_timestamp]);
        }

        // ── C. Detecting New Parties (Pre-fetch for Loop Triggers) ──
        $existing_parties_map = [];
        $check_p_stmt = $pdo->prepare("
            SELECT name, 'Complainant' as role FROM blotter_complainants WHERE blotter_id = ? AND (statement IS NULL OR statement = '' OR statement = 'COMPLAINANT')
            UNION SELECT name, 'Victim' as role FROM blotter_complainants WHERE blotter_id = ? AND statement = 'VICTIM'
            UNION SELECT name, 'Witness' as role FROM blotter_complainants WHERE blotter_id = ? AND statement = 'WITNESS'
            UNION SELECT name, 'Respondent' as role FROM blotter_respondents WHERE blotter_id = ?
        ");
        $check_p_stmt->execute([$recordId, $recordId, $recordId, $recordId]);
        while($row = $check_p_stmt->fetch()) {
            $existing_parties_map[$row['name'] . '|' . $row['role']] = true;
        }

        // ── D. Resolution Comparison ──
        $old_resolution = trim($old_data['resolution'] ?? '');
        $new_resolution = trim($_POST['resolution'] ?? '');
        if ($old_resolution !== $new_resolution) {
            $res_hist = $pdo->prepare("INSERT INTO blotter_history (blotter_id, action_type, old_value, new_value, remarks, changed_by, created_at) VALUES (?, 'Resolution Updated', ?, ?, 'Case resolution text was modified', ?, ?)");
            $res_hist->execute([$recordId, $old_resolution, $new_resolution, $user_id, $current_timestamp]);
        }

        // Prepare blotter record data
        $incidentDate = $_POST['incident_date'];
        $incidentType = $_POST['incident_type'];
        $incidentLocation = $_POST['incident_location'];
        $incidentDescription = $_POST['incident_description'];
        $resolution = $_POST['resolution'] ?? null;
        $reportedBy = $_POST['reported_by'] ?? null;
        $caseOutcome = $_POST['case_outcome'] ?? null;
        $mediationSchedule = $_POST['mediation_schedule'] ?? null;
        
        // Update blotter record
        $stmt = $pdo->prepare("
            UPDATE blotter_records SET
                incident_type = ?,
                incident_description = ?,
                incident_date = ?,
                incident_location = ?,
                reported_by = ?,
                status = ?,
                resolution = ?,
                case_outcome = ?,
                mediation_schedule = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $incidentType,
            $incidentDescription,
            $incidentDate,
            $incidentLocation,
            $reportedBy,
            $status,
            $resolution,
            $caseOutcome,
            $mediationSchedule,
            $recordId
        ]);
        
        // ============================================
        // Handle File Uploads (Update)
        // ============================================
        $uploadDir = '../assets/uploads/blotter/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileKeys = ['incident_proof' => 'incident', 'settlement_proof' => 'settlement'];
        foreach ($fileKeys as $postKey => $dbPrefix) {
            if (!empty($_FILES[$postKey]['name'][0])) {
                $files = $_FILES[$postKey];
                $newPaths = [];
                
                foreach ($files['tmp_name'] as $key => $tmpName) {
                    if ($files['error'][$key] !== UPLOAD_ERR_OK) continue;

                    $fileExt = strtolower(pathinfo($files['name'][$key], PATHINFO_EXTENSION));
                    if (in_array($fileExt, ['jpg', 'jpeg', 'png']) && $files['size'][$key] <= 5 * 1024 * 1024) {
                        $newFileName = "{$dbPrefix}_{$recordId}_" . time() . "_{$key}.{$fileExt}";
                        if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                            $newPaths[] = 'assets/uploads/blotter/' . $newFileName;
                        }
                    }
                }

                if (!empty($newPaths)) {
                    $column = ($dbPrefix === 'incident') ? 'incident_proof' : 'settlement_proof';
                    
                    // Fetch existing to append
                    $getOld = $pdo->prepare("SELECT $column FROM blotter_records WHERE id = ?");
                    $getOld->execute([$recordId]);
                    $oldData = $getOld->fetchColumn();
                    
                    $finalPaths = $newPaths;
                    if ($oldData) {
                        $existingPaths = explode(',', $oldData);
                        $finalPaths = array_merge($existingPaths, $newPaths);
                    }

                    $updateFiles = $pdo->prepare("
                        UPDATE blotter_records 
                        SET $column = ? 
                        WHERE id = ?
                    ");
                    $updateFiles->execute([implode(',', $finalPaths), $recordId]);
                }
            }
        }

        // Delete existing complainants, victims, and witnesses
        $stmt = $pdo->prepare("DELETE FROM blotter_complainants WHERE blotter_id = ?");
        $stmt->execute([$recordId]);
        
        // Delete existing respondents
        $stmt = $pdo->prepare("DELETE FROM blotter_respondents WHERE blotter_id = ?");
        $stmt->execute([$recordId]);

        // Prepare statement for party-related history logging
        $p_hist_stmt = $pdo->prepare("INSERT INTO blotter_history (blotter_id, remarks, changed_by, created_at, action_type) VALUES (?, ?, ?, ?, 'Party Added')");
        
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
                $name = trim($name);
                if (!empty($name)) {
                    // Audit Log: Complainant Added
                    if (!isset($existing_parties_map[$name . '|Complainant'])) {
                        $p_hist_stmt->execute([$recordId, "$name added as Complainant", $user_id, $current_timestamp]);
                    }

                    $residentId = !empty($complainantResidentIds[$index]) ? $complainantResidentIds[$index] : null;
                    $address = $complainantAddresses[$index] ?? null;
                    $contact = !empty($complainantContacts[$index]) ? '+63' . $complainantContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, $name, $address, $contact]);
                }
            }
        }
        
        // Insert victims
        if (!empty($_POST['victim_name']) && is_array($_POST['victim_name'])) {
            $victimNames = $_POST['victim_name'];
            $victimAddresses = $_POST['victim_address'] ?? [];
            $victimContacts = $_POST['victim_contact'] ?? [];
            $victimResidentIds = $_POST['victim_resident_id'] ?? [];
            
            $stmt = $pdo->prepare("
                INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
                VALUES (?, ?, ?, ?, ?, 'VICTIM')
            ");
            
            foreach ($victimNames as $index => $name) {
                $name = trim($name);
                if (!empty($name)) {
                    // Audit Log: Victim Added
                    if (!isset($existing_parties_map[$name . '|Victim'])) {
                        $p_hist_stmt->execute([$recordId, "$name added as Victim", $user_id, $current_timestamp]);
                    }

                    $residentId = !empty($victimResidentIds[$index]) ? $victimResidentIds[$index] : null;
                    $address = $victimAddresses[$index] ?? null;
                    $contact = !empty($victimContacts[$index]) ? '+63' . $victimContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, $name, $address, $contact]);
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
                $name = trim($name);
                if (!empty($name)) {
                    // Audit Log: Respondent Added
                    if (!isset($existing_parties_map[$name . '|Respondent'])) {
                        $p_hist_stmt->execute([$recordId, "$name added as Respondent", $user_id, $current_timestamp]);
                    }

                    $residentId = !empty($respondentResidentIds[$index]) ? $respondentResidentIds[$index] : null;
                    $address = $respondentAddresses[$index] ?? null;
                    $contact = !empty($respondentContacts[$index]) ? '+63' . $respondentContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, $name, $address, $contact]);
                }
            }
        }
        
        // Insert witnesses (if any)
        if (!empty($_POST['witness_name']) && is_array($_POST['witness_name'])) {
            $witnessNames = $_POST['witness_name'];
            $witnessAddresses = $_POST['witness_address'] ?? [];
            $witnessContacts = $_POST['witness_contact'] ?? [];
            $witnessResidentIds = $_POST['witness_resident_id'] ?? [];
            
            $stmt = $pdo->prepare("
                INSERT INTO blotter_complainants (blotter_id, resident_id, name, address, contact_number, statement)
                VALUES (?, ?, ?, ?, ?, 'WITNESS')
            ");
            
            foreach ($witnessNames as $index => $name) {
                $name = trim($name);
                if (!empty($name)) {
                    // Audit Log: Witness Added
                    if (!isset($existing_parties_map[$name . '|Witness'])) {
                        $p_hist_stmt->execute([$recordId, "$name added as Witness", $user_id, $current_timestamp]);
                    }

                    $residentId = !empty($witnessResidentIds[$index]) ? $witnessResidentIds[$index] : null;
                    $address = $witnessAddresses[$index] ?? null;
                    $contact = !empty($witnessContacts[$index]) ? '+63' . $witnessContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, $name, $address, $contact]);
                }
            }
        }
        
        // Update actions taken (if any)
        if (!empty($_POST['action_date']) && is_array($_POST['action_date'])) {
            $actionDates = $_POST['action_date'];
            $actionOfficers = $_POST['action_officer'] ?? [];
            $actionDetails = $_POST['action_details'] ?? [];
            
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
                $stmt->execute([json_encode($actions), $recordId]);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Success response
        $response['success'] = true;
        $response['message'] = 'Blotter record updated successfully';
        $response['data'] = [
            'id' => $recordId
        ];
        
        echo json_encode($response);
        exit;
    }
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in edit_blotter.php: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
    
    if (isset($_GET['ajax']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode($response);
        exit;
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error in edit_blotter.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
    
    if (isset($_GET['ajax']) || $_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode($response);
        exit;
    }
}
?>
<div class="modal fade" id="editRecordModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Barangay Blotter Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editRecordForm">
                    <input type="hidden" name="record_id" id="edit_record_id">
                    
<div class="step-indicator transition-all duration-300">
                        <div class="step-item active" data-step="0">
                            <div class="step-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="step-label">Step 1: Basic Info</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="1">
                            <div class="step-icon"><i class="fas fa-users"></i></div>
                            <div class="step-label">Step 2: Parties Involved</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="2">
                            <div class="step-icon"><i class="fas fa-align-left"></i></div>
                            <div class="step-label">Step 3: Narrative</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="3">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Step 4: Actions & Resolution</div>
                        </div>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Step 1: Basic Info ONLY (Incident Date, Type, Location) -->
                        <div class="tab-pane fade show active" id="edit-step-1-basic">

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="col-span-1">
        <label class="form-label fw-bold">Incident Date <span class="text-danger">*</span></label>
        <input type="datetime-local" class="form-control" id="edit_incident_date" name="incident_date" required>
    </div>
    <div class="col-span-1">
        <label class="form-label fw-bold">Incident Type <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_incident_type" name="incident_type" placeholder="e.g., Verbal Dispute" required>
    </div>
    <div class="col-span-2">
        <label class="form-label fw-bold">Incident Location <span class="text-danger">*</span></label>
        <textarea class="form-control" id="edit_incident_location" name="incident_location" rows="2" placeholder="Full address where incident occurred" required></textarea>
    </div>
</div>

                        </div>

                        <!-- Step 2: ALL Parties Involved (Complainants, Victims, Respondents) -->
                        <div class="tab-pane fade" id="edit-step-2-parties">
                            <div class="mt-4">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-shield"></i> Complainants</h6>
                                        <div id="editComplainantsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="editAddComplainantBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-injured"></i> Victims</h6>
                                        <div id="editVictimsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="editAddVictimBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-shield"></i> Respondents</h6>
                                        <div id="editRespondentsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="editAddRespondentBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: ONLY Narrative -->
                        <div class="tab-pane fade" id="edit-step-3-narrative">
                            <div class="mt-4">
                                <label class="form-label fw-bold text-xl mb-4 d-block">
                                    <i class="fas fa-align-left text-primary me-2"></i>
                                    Incident Narrative / Details <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="edit_incident_description" name="incident_description" rows="8" style="resize: vertical; min-height: 300px; font-size: 1.1rem; line-height: 1.6;" placeholder="Provide COMPLETE detailed narrative of the incident:
- Timeline of events
- Exact words/actions of parties involved
- Location details
- Witnesses present
- Any evidence/documents
- Police/other authority involvement if any

This forms the OFFICIAL record." required></textarea>
                            </div>
                        </div>

                        <!-- Step 4: Status, Mediation Schedule, Resolution -->
                        <div class="tab-pane fade" id="edit-step-4-actions">

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                                <!-- Left Column: Logic & People -->
                                <div class="space-y-6">
                                    <div>
                                        <label class="form-label fw-bold">Case Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="edit_status" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Scheduled for Mediation">Scheduled for Mediation</option>
                                            <option value="Under Investigation">Under Investigation</option>
                                            <option value="Settled">Settled</option>
                                            <option value="Dismissed">Dismissed</option>
                                            <option value="Endorsed to Police">Endorsed to Police</option>
                                        </select>
                                        <span id="edit_strike_msg" class="text-danger" style="display:none; font-size: 11px; margin-top: 5px; font-weight: 600;">STRICT LIMIT REACHED: 3/3 attempts used. Escalation required.</span>
                                    </div>
                                    
                                    <div id="edit_mediation_field" class="min-h-[85px]" style="display:none;">
                                        <label class="form-label fw-bold">Mediation Schedule <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="edit_mediation_date" name="mediation_schedule">
                                    </div>

                                    <div class="border-t pt-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-eye text-success"></i> Witnesses</h6>
                                        <div id="editWitnessesContainer" class="party-section mb-2"></div>
                                        <button type="button" class="btn btn-outline-success btn-sm" id="editAddWitnessBtn"><i class="fas fa-plus"></i> Add Witness</button>
                                    </div>

                                    <!-- Case History Timeline (Added for Edit Modal) -->
                                    <div class="mt-6 border-t pt-4">
                                        <h6 class="text-sm font-semibold text-blue-600 mb-4 flex items-center gap-2">
                                            <i class="fas fa-history text-indigo-500"></i> Case History Timeline
                                        </h6>
                                        <div id="edit-case-history-timeline" class="relative pl-6 space-y-6"></div>
                                    </div>
                                </div>

                                <!-- Right Column: Narrative & Proof -->
                                <div class="space-y-6">
                                    <div>
                                        <label class="form-label fw-bold">Resolution / Actions Taken <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="edit_resolution" name="resolution" rows="4" placeholder="Final resolution, settlement terms, fines imposed, referrals made, signatures obtained, etc." required></textarea>
                                    </div>

                                    <div class="border-t pt-4">
                                        <h6 class="party-title mb-3 text-primary"><i class="fas fa-camera"></i> Incident Proof</h6>
                                        <div class="attachment-upload-wrapper">
                                            <div id="edit_incident_preview" class="attachment-preview-container"></div>
                                            <div id="editIncidentProofPreviewContainer" class="attachment-preview-container">
                                                <!-- New Previews will appear here -->
                                            </div>
                                            <div id="editIncidentProofUploadZone" class="attachment-upload-zone">
                                                <input type="file" id="editIncidentProofInput" name="incident_proof[]" multiple accept="image/png, image/jpeg" class="hidden">
                                                <div class="upload-zone-content">
                                                    <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                                                    <p class="mb-1"><strong>Drag and drop images</strong> here or <span class="text-primary">click to browse</span></p>
                                                    <p class="text-muted small">Supports JPG and PNG (Max 5MB each)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mediation/Settlement Proof Section (Hidden by Default) -->
                                    <div id="edit_settlement_proof_section" class="border-t pt-4 min-h-[100px]" style="display: none;">
                                        <h6 class="party-title mb-3 text-success"><i class="fas fa-handshake"></i> Settlement Proof</h6>
                                        <div class="attachment-upload-wrapper">
                                            <div id="edit_settlement_preview" class="attachment-preview-container"></div>
                                            <div id="editSettlementProofPreviewContainer" class="attachment-preview-container"></div>
                                            <div id="editSettlementProofUploadZone" class="attachment-upload-zone">
                                                <input type="file" id="editSettlementProofInput" name="settlement_proof[]" multiple accept="image/png, image/jpeg" class="hidden">
                                                <div class="upload-zone-content">
                                                    <i class="fas fa-handshake fa-3x mb-3 text-muted"></i>
                                                    <p class="mb-1"><strong>Upload settlement photos</strong> here</p>
                                                    <p class="text-muted small">Supports JPG and PNG</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="editActionsContainer" class="d-none"></div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <div style="margin-left: auto; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary" id="editModalBackBtn" style="display: none;">Back</button>
                    <button type="button" class="btn btn-primary" id="editModalNextBtn">Next</button>
                    <button type="button" class="btn btn-primary" id="updateRecordBtn" style="display: none;">
                        <i class="fas fa-save"></i> Update Record
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Edit Blotter Record Modal JavaScript
(function() {
    let editRecordModal;
    let editCurrentStep = 0;
    const editSteps = ['edit-step-1-basic', 'edit-step-2-parties', 'edit-step-3-narrative', 'edit-step-4-actions'];
    let editStepItems;
    let editTabPanes;
    let editStepLines;
    let initialFormHTML = '';
    
    // Counters for dynamic entries
    let editComplainantCount = 0;
    let editVictimCount = 0;
    let editRespondentCount = 0;
    let editWitnessCount = 0;
    let editActionCount = 0;
    
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('editRecordModal');
        if (modalEl) {
            editRecordModal = new bootstrap.Modal(modalEl);
            // Save initial form HTML to restore later
            const formEl = document.getElementById('editRecordForm');
            if (formEl) {
                initialFormHTML = formEl.outerHTML;
            }
        }

    });

// Global function to open edit modal - FIXED
    window.openEditBlotterModal = function(recordId) {
        console.log('openEditBlotterModal called for record:', recordId);
        
        const modalEl = document.getElementById('editRecordModal');
        if (!modalEl) {
            console.error('Edit modal element not found');
            alert('Edit modal not available. Please refresh.');
            return;
        }
        
        editRecordModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        
        // Show loading
        const modalBody = modalEl.querySelector('.modal-body');
        modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3 mb-0">Loading record details...</p></div>';
        
        editRecordModal.show();
        
        // Fetch data
        fetch(`model/edit_blotter.php?ajax=true&id=${recordId}`)
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.json();
            })
            .then(data => {
                console.log('Edit data loaded:', data);
                if (data.success) {
                    loadEditRecordData(data.data);
                } else {
                    throw new Error(data.message || 'Failed to load record');
                }
            })
            .catch(err => {
                console.error('Edit fetch error:', err);
                modalBody.innerHTML = `<div class="alert alert-danger p-4"><i class="fas fa-exclamation-triangle me-2"></i><strong>Error:</strong> ${err.message}<br><small>Please refresh and try again.</small></div>`;
            });
    };
    
// FIXED: Self-contained form population - no initialFormHTML needed
    function loadEditRecordData(data) {
        console.log('=== LOAD EDIT DATA START ===');
        console.log('Data received:', data);
        
        const modalEl = document.getElementById('editRecordModal');
        const modalBody = modalEl.querySelector('.modal-body');
        
        // 1. FIRST: Restore form HTML
        if (initialFormHTML) {
            modalBody.innerHTML = initialFormHTML;
            console.log('✓ Form HTML restored');
        }
        
        // 2. IMMEDIATELY attach listeners BEFORE any step manipulation
        attachEditEventListeners();
        console.log('✓ Listeners attached BEFORE step reset');

        const record = data.record || {};
        
        // Reset counters
        editComplainantCount = 0; editVictimCount = 0; editRespondentCount = 0; 
        editWitnessCount = 0; editActionCount = 0;

        // Handle "Resolved" to "Settled" transition or empty status
        const displayStatus = (record.status === 'Resolved') ? 'Settled' : (record.status || 'Pending');
        
        // Populate fields
        const fields = {
            'edit_record_id': record.id || '',
            'edit_status': displayStatus,
            'edit_incident_date': formatDateTimeLocal(record.incident_date || ''),
            'edit_incident_type': record.incident_type || '',
            'edit_incident_location': record.incident_location || '',
            'edit_incident_description': record.incident_description || '',
            'edit_resolution': record.resolution || ''
        };
        Object.entries(fields).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.value = value;
        });
        console.log('✓ Fields populated');
        
        // Re-query DOM elements
        // CRITICAL FIX: Force exact step activation for Step 0
        reQueryEditElements();
        console.log('Tab panes found:', editTabPanes?.length || 0, 'IDs:', Array.from(editTabPanes).map(p=>p.id));
        
        editCurrentStep = 0;
        
        // MANUALLY activate Step 0 - bypass updateEditStepIndicator index math
        editTabPanes.forEach((pane, index) => {
            pane.classList.remove('show', 'active');
            if (index === 0) {
                pane.classList.add('show', 'active');
            }
        });
        if (editStepItems && editStepItems[0]) editStepItems[0].classList.add('active');
        if (editStepItems) {
            for (let i = 1; i < editStepItems.length; i++) {
                editStepItems[i].classList.remove('active');
            }
        }
        
        updateEditFooterButtons();
        console.log('✓ Step 0 MANUALLY activated. Active pane:', document.querySelector('#editRecordModal .tab-pane.show')?.id);
        
        // Populate parties AFTER listeners
        populateEditParties('editComplainantsContainer', 'complainant', data.complainants || [], 1);
        populateEditParties('editVictimsContainer', 'victim', data.victims || [], 1);
        populateEditParties('editRespondentsContainer', 'respondent', data.respondents || [], 1);
        populateEditParties('editWitnessesContainer', 'witness', data.witnesses || [], 0);
        // Handle Existing Proofs
        handleExistingProofDisplay('Incident', record.incident_proof);
        handleExistingProofDisplay('Settlement', record.settlement_proof);
        populateEditParties('editActionsContainer', 'action', data.actions || [], 0);
        console.log('✓ Parties populated');

        // Fetch and Render Timeline for Edit Modal
        if (typeof window.loadBlotterHistory === 'function') {
            window.loadBlotterHistory(record.id, 'edit-case-history-timeline');
        }
        
        // Mediation visibility and validation
        const mediationField = document.getElementById('edit_mediation_field');
        const mediationInput = document.getElementById('edit_mediation_date');
        if (mediationField) {
            const isMediation = record.status === 'Scheduled for Mediation';
            mediationField.style.display = isMediation ? 'block' : 'none';
            if (mediationInput) {
                if (isMediation) mediationInput.setAttribute('required', 'required');
                else mediationInput.removeAttribute('required');
            }
        }

        const settlementSection = document.getElementById('edit_settlement_proof_section');
        if (settlementSection) {
            settlementSection.style.display = record.status === 'Settled' ? 'block' : 'none';
        }
        
        console.log('=== LOAD EDIT DATA COMPLETE ===');
    }
    
    function handleExistingProofDisplay(type, pathString) {
        const uploadZone = document.getElementById(`edit${type}ProofUploadZone`);
        const previewContainer = document.getElementById(`edit_${type.toLowerCase()}_preview`);

        if (!uploadZone || !previewContainer) return;

        if (pathString && pathString.trim() !== '') {
            uploadZone.classList.add('is-compact');
            const zoneContent = uploadZone.querySelector('.upload-zone-content');
            if (zoneContent) {
                zoneContent.innerHTML = `<i class="fas fa-plus text-primary mb-1"></i><p class="mb-0 text-[10px] fw-bold">Add More</p>`;
            }
            
            previewContainer.innerHTML = pathString.split(',').map(path => `
                <div class="attachment-preview-item">
                    <img src="${path.trim()}" alt="Evidence">
                </div>`).join('');
        } else {
            uploadZone.classList.remove('is-compact');
            const zoneContent = uploadZone.querySelector('.upload-zone-content');
            if (zoneContent) {
                if (type === 'Incident') {
                    zoneContent.innerHTML = `<i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i><p class="mb-1"><strong>Drag and drop images</strong> here</p>`;
                } else {
                    zoneContent.innerHTML = `<i class="fas fa-handshake fa-3x mb-3 text-muted"></i><p class="mb-1"><strong>Upload settlement photos</strong> here</p>`;
                }
            }
        }
    }

    // Populate parties with minimum guarantee
    function populateEditParties(containerId, type, items, minCount = 0) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        let count = 0;
        container.innerHTML = '';
        
        const addFunctions = {
            complainant: addEditComplainant,
            victim: addEditVictim,
            respondent: addEditRespondent,
            witness: addEditWitness,
            action: addEditAction
        };
        
        const addFn = addFunctions[type];
        if (!addFn) return;

        // Load existing
        items.forEach(item => {
            count++;
            addFn(item);
        });
        
        // Add minimum empty if needed
        while (count < minCount) {
            count++;
            addFn();
        }
    }
    
    // Helper function to format datetime for input
    function formatDateTimeLocal(datetime) {
        if (!datetime) return '';
        const date = new Date(datetime);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    // Functions to add party entries with data
    function addEditComplainant(data = null) {
        editComplainantCount++;
        const container = document.getElementById('editComplainantsContainer');
        const mobile = data && data.contact_number ? data.contact_number.replace(/^\+63/, '').replace(/\s/g, '') : '';
        
        const html = `
            <div class="party-entry">
                <div class="party-entry-header">
                    <span>Complainant ${editComplainantCount}</span>
                    ${editComplainantCount > 1 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>' : ''}
                </div>
                <div class="party-entry-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="member-name-input-group">
                            <input type="text" class="form-control" name="complainant_name[]" value="${data ? data.name : ''}" placeholder="Enter full name" required ${data && data.resident_id ? 'readonly' : ''}>
                            <button type="button" class="btn-resident-search" data-target="complainant" data-index="${editComplainantCount - 1}" ${data && data.resident_id ? 'style="display: none;"' : ''}>RESIDENT</button>
                            <button type="button" class="btn-reset-resident" ${data && data.resident_id ? '' : 'style="display: none;"'} title="Reset"><i class="fas fa-redo"></i></button>
                        </div>
                        <input type="hidden" name="complainant_resident_id[]" value="${data && data.resident_id ? data.resident_id : ''}">
                    </div>
                    <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="complainant_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                    <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
<input type="tel" class="form-control phone-input" name="complainant_contact[]" value="${mobile}" inputmode="tel" maxlength="12" placeholder="912 345 6789">
                            </div>
                        </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditVictim(data = null) {
        editVictimCount++;
        const container = document.getElementById('editVictimsContainer');
        const mobile = data && data.contact_number ? data.contact_number.replace(/^\+63/, '').replace(/\s/g, '') : '';
        
        const html = `
            <div class="party-entry">
                <div class="party-entry-header">
                    <span>Victim ${editVictimCount}</span>
                    ${editVictimCount > 1 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>' : ''}
                </div>
                <div class="party-entry-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="member-name-input-group">
                            <input type="text" class="form-control" name="victim_name[]" value="${data ? data.name : ''}" placeholder="Enter full name" required ${data && data.resident_id ? 'readonly' : ''}>
                            <button type="button" class="btn-resident-search" data-target="victim" data-index="${editVictimCount - 1}" ${data && data.resident_id ? 'style="display: none;"' : ''}>RESIDENT</button>
                            <button type="button" class="btn-reset-resident" ${data && data.resident_id ? '' : 'style="display: none;"'} title="Reset"><i class="fas fa-redo"></i></button>
                        </div>
                        <input type="hidden" name="victim_resident_id[]" value="${data && data.resident_id ? data.resident_id : ''}">
                    </div>
                    <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="victim_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                    <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
<input type="tel" class="form-control phone-input" name="victim_contact[]" value="${mobile}" inputmode="tel" maxlength="12" placeholder="912 345 6789">
                            </div>
                        </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditRespondent(data = null) {
        editRespondentCount++;
        const container = document.getElementById('editRespondentsContainer');
        const mobile = data && data.contact_number ? data.contact_number.replace(/^\+63/, '').replace(/\s/g, '') : '';
        
        const html = `
            <div class="party-entry">
                <div class="party-entry-header">
                    <span>Respondent ${editRespondentCount}</span>
                    ${editRespondentCount > 1 ? '<button type="button" class="btn btn-sm btn-danger remove-edit-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>' : ''}
                </div>
                <div class="party-entry-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="member-name-input-group">
                            <input type="text" class="form-control" name="respondent_name[]" value="${data ? data.name : ''}" placeholder="Enter full name" required ${data && data.resident_id ? 'readonly' : ''}>
                            <button type="button" class="btn-resident-search" data-target="respondent" data-index="${editRespondentCount - 1}" ${data && data.resident_id ? 'style="display: none;"' : ''}>RESIDENT</button>
                            <button type="button" class="btn-reset-resident" ${data && data.resident_id ? '' : 'style="display: none;"'} title="Reset"><i class="fas fa-redo"></i></button>
                        </div>
                        <input type="hidden" name="respondent_resident_id[]" value="${data && data.resident_id ? data.resident_id : ''}">
                    </div>
                    <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="respondent_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                    <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="tel" class="form-control phone-input" name="respondent_contact[]" value="${mobile}" inputmode="tel" maxlength="12" placeholder="912 345 6789">
                            </div>
                        </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditWitness(data = null) {
        editWitnessCount++;
        const container = document.getElementById('editWitnessesContainer');
        const mobile = data && data.contact_number ? data.contact_number.replace(/^\+63/, '').replace(/\s/g, '') : '';
        
        const html = `
            <div class="party-entry">
                <div class="party-entry-header">
                    <span>Witness ${editWitnessCount}</span>
                    <button type="button" class="btn btn-sm btn-danger remove-edit-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="party-entry-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <div class="member-name-input-group">
                            <input type="text" class="form-control" name="witness_name[]" value="${data ? data.name : ''}" placeholder="Enter full name" ${data && data.resident_id ? 'readonly' : ''}>
                            <button type="button" class="btn-resident-search" data-target="witness" data-index="${editWitnessCount - 1}" ${data && data.resident_id ? 'style="display: none;"' : ''}>RESIDENT</button>
                            <button type="button" class="btn-reset-resident" ${data && data.resident_id ? '' : 'style="display: none;"'} title="Reset"><i class="fas fa-redo"></i></button>
                        </div>
                        <input type="hidden" name="witness_resident_id[]" value="${data && data.resident_id ? data.resident_id : ''}">
                    </div>
                    <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="witness_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                    <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="tel" class="form-control phone-input" name="witness_contact[]" value="${mobile}" inputmode="tel" maxlength="12" placeholder="912 345 6789">
                            </div>
                        </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditAction(data = null) {
        editActionCount++;
        const container = document.getElementById('editActionsContainer');
        
        const html = `
            <div class="party-entry">
                <div class="party-entry-header">
                    <span>Action ${editActionCount}</span>
                    <button type="button" class="btn btn-sm btn-danger remove-edit-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;"><i class="fas fa-times"></i></button>
                </div>
                <div class="party-entry-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Action Date</label>
                            <input type="date" class="form-control" name="action_date[]" value="${data && data.date ? data.date : ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Officer In Charge</label>
                            <input type="text" class="form-control" name="action_officer[]" value="${data && data.officer ? data.officer : ''}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Action Details</label>
                        <textarea class="form-control" name="action_details[]" rows="3" placeholder="Action Details">${data && data.details ? data.details : ''}</textarea>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    // Utility to safely add listeners (moved to closure scope for wider access)
    function addSafeListener(id, event, fn) {
        const el = document.getElementById(id);
        if (el) el.addEventListener(event, fn);
    };

    // Attach event listeners
    function attachEditEventListeners() {
        // Status change listener for Step 4
        const statusSelect = document.getElementById('edit_status');
        if (statusSelect) {
            statusSelect.addEventListener('change', function() {
                const mediationField = document.getElementById('edit_mediation_field');
                const mediationInput = document.getElementById('edit_mediation_date');
                if (mediationField) {
                    const isMediation = this.value === 'Scheduled for Mediation';
                    mediationField.style.display = isMediation ? 'block' : 'none';
                    if (mediationInput) {
                        if (isMediation) {
                            mediationInput.setAttribute('required', 'required');
                        } else {
                            mediationInput.removeAttribute('required');
                            mediationInput.classList.remove('is-invalid');
                        }
                    }
                }
                        
                        const settlementSection = document.getElementById('edit_settlement_proof_section');
                        if (settlementSection) settlementSection.style.display = this.value === 'Settled' ? 'block' : 'none';
            });
        }

        // Add buttons for parties
        addSafeListener('editAddComplainantBtn', 'click', () => addEditComplainant());
        addSafeListener('editAddVictimBtn', 'click', () => addEditVictim());
        addSafeListener('editAddRespondentBtn', 'click', () => addEditRespondent());
        addSafeListener('editAddWitnessBtn', 'click', () => addEditWitness());
        
        // Reusable Preview Logic for Edit Modal
        function setupFilePreview(zoneId, inputId, containerId) {
            const zone = document.getElementById(zoneId);
            const input = document.getElementById(inputId);
            const container = document.getElementById(containerId);
            
            if (zone && input) {
                zone.addEventListener('click', () => input.click());
                input.addEventListener('change', e => {
                    const files = Array.from(e.target.files);
                    if (files.length > 0) {
                        zone.classList.add('is-compact');
                        const zoneContent = zone.querySelector('.upload-zone-content');
                        if (zoneContent) {
                            zoneContent.innerHTML = `<i class="fas fa-plus text-primary mb-1"></i><p class="mb-0 text-[10px] fw-bold">Add More</p>`;
                        }
                    }
                    files.forEach(file => {
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = ev => {
                                const item = document.createElement('div');
                                item.className = 'attachment-preview-item';
                                item.innerHTML = `<img src="${ev.target.result}"><button type="button" class="remove-btn" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
                                container.appendChild(item);
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                });
            }
        }

        setupFilePreview('editIncidentProofUploadZone', 'editIncidentProofInput', 'editIncidentProofPreviewContainer');
        setupFilePreview('editSettlementProofUploadZone', 'editSettlementProofInput', 'editSettlementProofPreviewContainer');


        // Use Event Delegation for Remove buttons to handle dynamically added rows
        const form = document.getElementById('editRecordForm');
        if (form) {
            form.addEventListener('click', function(e) {
                const btn = e.target.closest('.remove-edit-party-btn');
                if (!btn) return;

                const entry = btn.closest('.party-entry');
                const container = entry.parentElement;
                
                // Check minimum requirements
                if (container.id === 'editComplainantsContainer' && container.querySelectorAll('.party-entry').length <= 1) {
                    alert('At least one complainant is required.');
                    return;
                }
                if (container.id === 'editVictimsContainer' && container.querySelectorAll('.party-entry').length <= 1) {
                    alert('At least one victim is required.');
                    return;
                }
                if (container.id === 'editRespondentsContainer' && container.querySelectorAll('.party-entry').length <= 1) {
                    alert('At least one respondent is required.');
                    return;
                }
                
                entry.remove();
                
                // Renumber remaining entries
                const entries = container.querySelectorAll('.party-entry');
                entries.forEach((e, index) => {
                    const label = e.querySelector('.party-entry-header span');
                    if (label) {
                        // Extract base type (Complainant, Victim, etc.) from container ID
                        const type = container.id.replace('edit', '').replace('Container', '').replace('s', '');
                        label.textContent = `${type} ${index + 1}`;
                    }
                });
                
                // Sync global counters with remaining length
                if (container.id === 'editComplainantsContainer') editComplainantCount = entries.length;
                if (container.id === 'editVictimsContainer') editVictimCount = entries.length;
                if (container.id === 'editRespondentsContainer') editRespondentCount = entries.length;
                if (container.id === 'editWitnessesContainer') editWitnessCount = entries.length;
                if (container.id === 'editActionsContainer') editActionCount = entries.length;
            });
        }
    }
    
    // CRITICAL: Event delegation for ALL footer buttons - survives HTML restore
    document.addEventListener('click', function(e) {
        if (e.target.matches('#editModalNextBtn')) {
            handleEditNextClick();
        } else if (e.target.matches('#editModalBackBtn')) {
            handleEditBackClick();
        } else if (e.target.matches('#updateRecordBtn')) {
            handleEditUpdateClick(e);
        }
    });

    // FIXED: Robust element re-query + reset with logging - Moved outside to be accessible by all functions
    function reQueryEditElements() {
        editStepItems = Array.from(document.querySelectorAll('#editRecordModal .step-item'));
        editTabPanes = Array.from(document.querySelectorAll('#editRecordModal .tab-pane'));
        editStepLines = Array.from(document.querySelectorAll('#editRecordModal .step-line'));
        console.log('DEBUG: Re-queried - Steps:', editStepItems ? editStepItems.length : 0, 'Panes:', editTabPanes ? editTabPanes.length : 0, 'Lines:', editStepLines ? editStepLines.length : 0);
    }

    // FIXED: Robust step update with re-query
    function updateEditStepIndicator() {
        reQueryEditElements();
        
        console.log('DEBUG: Updating indicator for step', editCurrentStep, '- Panes:', editTabPanes?.length);
        
        if (!editTabPanes || editTabPanes.length !== 4) {
            console.error('CRITICAL: Wrong number of tab panes!', editTabPanes?.length);
            return;
        }
        
        // Steps
        editStepItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index < editCurrentStep) item.classList.add('completed');
            else if (index === editCurrentStep) item.classList.add('active');
        });

        // Lines  
        editStepLines.forEach((line, index) => {
            if (index < editCurrentStep) line.classList.add('completed');
            else line.classList.remove('completed');
        });
        
        // TABS - Exact index match
        editTabPanes.forEach((pane, index) => {
            console.log(`Tab ${index}:`, pane.id, 'Target active:', index === editCurrentStep);
            pane.classList.remove('show', 'active');
            if (index === editCurrentStep) {
                pane.classList.add('show', 'active');
            }
        });
        
        console.log('Active tab now:', document.querySelector('#editRecordModal .tab-pane.show.active')?.id);
    }
    
    // Footer buttons update
    function updateEditFooterButtons() {
        const backBtn = document.getElementById('editModalBackBtn');
        const nextBtn = document.getElementById('editModalNextBtn');
        const updateBtn = document.getElementById('updateRecordBtn');
        
        if (editCurrentStep === 0) {
            backBtn.style.display = 'none';
        } else {
            backBtn.style.display = 'inline-block';
        }
        
        if (editCurrentStep === editSteps.length - 1) {
            nextBtn.style.display = 'none';
            updateBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            updateBtn.style.display = 'none';
        }
    }
    
    // FIXED: Enhanced validation - blocks Next + visual feedback + logging
    function validateEditCurrentStep() {
        reQueryEditElements();
        
        console.log('DEBUG: Validating step', editCurrentStep);
        
        if (!editTabPanes || !editTabPanes[editCurrentStep]) {
            console.warn('DEBUG: No tab pane at step', editCurrentStep);
            return false;
        }
        
        const currentPane = editTabPanes[editCurrentStep];
        const requiredFields = currentPane.querySelectorAll('[required]');
        console.log('DEBUG: Checking', requiredFields.length, 'required fields');
        
        let isValid = true;
        requiredFields.forEach(field => {
            field.classList.remove('is-invalid');
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });
        
        if (!isValid) {
            const firstError = currentPane.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            console.log('DEBUG: VALIDATION FAILED at step', editCurrentStep);
            alert('Please fill all required fields in current step before proceeding.');
        } else {
            console.log('DEBUG: VALIDATION PASSED step', editCurrentStep);
        }
        
        return isValid;
    }

    // NEW: Next button handler (inline for immediate access)
    const handleEditNextClick = function() {
        console.log('DEBUG: Next clicked at step', editCurrentStep);
        if (!validateEditCurrentStep()) return;
        
        if (editCurrentStep < editSteps.length - 1) {
            editCurrentStep++;
            console.log('DEBUG: Moving to step', editCurrentStep);
            updateEditStepIndicator();
            updateEditFooterButtons();
        }
    }

    // NEW: Back button handler
    const handleEditBackClick = function() {
        console.log('DEBUG: Back clicked at step', editCurrentStep);
        if (editCurrentStep > 0) {
            editCurrentStep--;
            updateEditStepIndicator();
            updateEditFooterButtons();
        }
    };

    // NEW: Update button handler  
    const handleEditUpdateClick = function(e) { // Accept event object
        console.log('DEBUG: Update clicked at step', editCurrentStep);
        const form = document.getElementById('editRecordForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
const btn = e.target;
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

        fetch('model/edit_blotter.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Record updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(err => {
            console.error('Update error:', err);
            alert('Update failed');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    };
})(); // End of self-executing anonymous function
</script>
