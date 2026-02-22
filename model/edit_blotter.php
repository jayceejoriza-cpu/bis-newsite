<?php
/**
 * Edit Blotter Record
 * Modal for editing existing blotter records
 */

// Include configuration
require_once __DIR__ . '/../config.php';

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
            'actions' => $actions
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // Check if this is an update request
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_id'])) {
        $recordId = $_POST['record_id'];
        
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
        
        // Prepare blotter record data
        $status = $_POST['status'];
        $incidentDate = $_POST['incident_date'];
        $incidentType = $_POST['incident_type'];
        $incidentLocation = $_POST['incident_location'];
        $incidentDescription = $_POST['incident_description'];
        $resolution = $_POST['resolution'] ?? null;
        $reportedBy = $_POST['reported_by'] ?? null;
        
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
            $recordId
        ]);
        
        // Delete existing complainants, victims, and witnesses
        $stmt = $pdo->prepare("DELETE FROM blotter_complainants WHERE blotter_id = ?");
        $stmt->execute([$recordId]);
        
        // Delete existing respondents
        $stmt = $pdo->prepare("DELETE FROM blotter_respondents WHERE blotter_id = ?");
        $stmt->execute([$recordId]);
        
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
                    
                    $stmt->execute([$recordId, $residentId, trim($name), $address, $contact]);
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
                if (!empty(trim($name))) {
                    $residentId = !empty($victimResidentIds[$index]) ? $victimResidentIds[$index] : null;
                    $address = $victimAddresses[$index] ?? null;
                    $contact = !empty($victimContacts[$index]) ? '+63' . $victimContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, trim($name), $address, $contact]);
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
                    
                    $stmt->execute([$recordId, $residentId, trim($name), $address, $contact]);
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
                if (!empty(trim($name))) {
                    $residentId = !empty($witnessResidentIds[$index]) ? $witnessResidentIds[$index] : null;
                    $address = $witnessAddresses[$index] ?? null;
                    $contact = !empty($witnessContacts[$index]) ? '+63' . $witnessContacts[$index] : null;
                    
                    $stmt->execute([$recordId, $residentId, trim($name), $address, $contact]);
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

<!-- Edit Blotter Record Modal -->
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
                    
                    <div class="step-indicator">
                        <div class="step-item active" data-step="0" id="edit-step-basic-info">
                            <div class="step-icon">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="step-label">Basic Info</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="1" id="edit-step-parties">
                            <div class="step-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="step-label">Parties Involved</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="2" id="edit-step-incident">
                            <div class="step-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="step-label">Incident Details</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="3" id="edit-step-actions">
                            <div class="step-icon">
                                <i class="fas fa-clipboard-check"></i>
                            </div>
                            <div class="step-label">Actions & Resolution</div>
                        </div>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="edit-basic-info">
                            <div class="mt-4">
                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-control" name="status" id="edit_status" required>
                                            <option value="Pending">Pending</option>
                                            <option value="Under Investigation">Under Investigation</option>
                                            <option value="Resolved">Resolved</option>
                                            <option value="Dismissed">Dismissed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Incident Date</label>
                                        <input type="datetime-local" class="form-control" id="edit_incident_date" name="incident_date" required>
                                    </div>
                                </div>
                                
                                <!-- Complainant Section -->
                                <div class="party-section mb-4">
                                    <div class="party-header">
                                        <h6 class="party-title"><i class="fas fa-user"></i> Complainant</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="editAddComplainantBtn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div id="editComplainantsContainer">
                                        <!-- Complainants will be loaded here -->
                                    </div>
                                </div>
                                
                                <!-- Victims Section -->
                                <div class="party-section">
                                    <div class="party-header">
                                        <h6 class="party-title"><i class="fas fa-user-injured"></i> Victims</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="editAddVictimBtn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div id="editVictimsContainer">
                                        <!-- Victims will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="edit-parties">
                            <div class="mt-4">
                                <div class="party-section mb-4">
                                    <div class="party-header">
                                        <h6 class="party-title"><i class="fas fa-user-shield"></i> Respondents</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="editAddRespondentBtn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div id="editRespondentsContainer">
                                        <!-- Respondents will be loaded here -->
                                    </div>
                                </div>
                                
                                <div class="party-section">
                                    <div class="party-header">
                                        <h6 class="party-title"><i class="fas fa-eye"></i> Witnesses</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="editAddWitnessBtn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div id="editWitnessesContainer">
                                        <!-- Witnesses will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="edit-incident">
                            <div class="mt-4">
                                <div class="mb-3">
                                    <label class="form-label">Incident Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="incident_date_details" id="edit_incident_date_details" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Incident Type <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="incident_type" id="edit_incident_type" placeholder="Incident type is required." required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Incident Location <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="incident_location" id="edit_incident_location" rows="2" placeholder="Incident location is required." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Incident Details <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="incident_description" id="edit_incident_description" rows="6" placeholder="Incident details is required." required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="tab-pane fade" id="edit-actions">
                            <div class="mt-4">
                                <div class="party-section mb-4">
                                    <div class="party-header">
                                        <h6 class="party-title">Action Taken</h6>
                                        <button type="button" class="btn btn-sm btn-primary" id="editAddActionBtn"><i class="fas fa-plus"></i></button>
                                    </div>
                                    <div id="editActionsContainer">
                                        <!-- Actions will be loaded here -->
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Resolution</label>
                                    <textarea class="form-control" name="resolution" id="edit_resolution" rows="4" placeholder="Enter final resolution details..."></textarea>
                                    <small class="text-danger">Resolution is required.</small>
                                </div>
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
    const editSteps = ['edit-basic-info', 'edit-parties', 'edit-incident', 'edit-actions'];
    let editStepItems;
    let editTabPanes;
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

    // Global function to open edit modal
    window.openEditBlotterModal = function(recordId) {
        // Ensure modal is initialized
        if (!editRecordModal) {
            editRecordModal = new bootstrap.Modal(document.getElementById('editRecordModal'));
        }
        
        // Ensure we have the form HTML saved
        if (!initialFormHTML) {
            const formEl = document.getElementById('editRecordForm');
            if (formEl) initialFormHTML = formEl.outerHTML;
        }
        
        // Show loading state
        document.querySelector('#editRecordModal .modal-body').innerHTML = '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Loading record...</p></div>';
        
        // Show modal
        editRecordModal.show();
        
        // Fetch record data
        fetch(`model/edit_blotter.php?ajax=true&id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadEditRecordData(data.data);
                } else {
                    alert('Error loading record: ' + data.message);
                    editRecordModal.hide();
                }
            })
            .catch(error => {
                console.error('Error fetching record:', error);
                alert('An error occurred while loading the record.');
                editRecordModal.hide();
            });
    };
    
    // Function to load record data into form
    function loadEditRecordData(data) {
        // Restore modal body HTML
        const modalBody = document.querySelector('#editRecordModal .modal-body');
        if (modalBody && initialFormHTML) {
            modalBody.innerHTML = initialFormHTML;
        }
        
        // Re-query elements
        editStepItems = document.querySelectorAll('#editRecordModal .step-item');
        editTabPanes = document.querySelectorAll('#editRecordModal .tab-pane');
        editCurrentStep = 0;
        
        updateEditStepIndicator();
        updateEditFooterButtons();
        
        const record = data.record;
        
        // Set basic fields
        document.getElementById('edit_record_id').value = record.id;
        document.getElementById('edit_status').value = record.status;
        document.getElementById('edit_incident_date').value = formatDateTimeLocal(record.incident_date);
        document.getElementById('edit_incident_date_details').value = formatDateTimeLocal(record.incident_date);
        document.getElementById('edit_incident_type').value = record.incident_type;
        document.getElementById('edit_incident_location').value = record.incident_location;
        document.getElementById('edit_incident_description').value = record.incident_description;
        document.getElementById('edit_resolution').value = record.resolution || '';
        
        // Load complainants
        editComplainantCount = 0;
        const complainantsContainer = document.getElementById('editComplainantsContainer');
        complainantsContainer.innerHTML = '';
        data.complainants.forEach((complainant, index) => {
            editComplainantCount++;
            addEditComplainant(complainant);
        });
        
        // Load victims
        editVictimCount = 0;
        const victimsContainer = document.getElementById('editVictimsContainer');
        victimsContainer.innerHTML = '';
        data.victims.forEach((victim, index) => {
            editVictimCount++;
            addEditVictim(victim);
        });
        
        // Load respondents
        editRespondentCount = 0;
        const respondentsContainer = document.getElementById('editRespondentsContainer');
        respondentsContainer.innerHTML = '';
        data.respondents.forEach((respondent, index) => {
            editRespondentCount++;
            addEditRespondent(respondent);
        });
        
        // Load witnesses
        editWitnessCount = 0;
        const witnessesContainer = document.getElementById('editWitnessesContainer');
        witnessesContainer.innerHTML = '';
        data.witnesses.forEach((witness, index) => {
            editWitnessCount++;
            addEditWitness(witness);
        });
        
        // Load actions
        editActionCount = 0;
        const actionsContainer = document.getElementById('editActionsContainer');
        actionsContainer.innerHTML = '';
        if (data.actions && data.actions.length > 0) {
            data.actions.forEach((action, index) => {
                editActionCount++;
                addEditAction(action);
            });
        }
        
        // Re-attach event listeners
        attachEditEventListeners();
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="complainant_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="text" class="form-control" name="complainant_contact[]" value="${mobile}" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditVictim(data = null) {
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="victim_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="text" class="form-control" name="victim_contact[]" value="${mobile}" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditRespondent(data = null) {
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="respondent_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="text" class="form-control" name="respondent_contact[]" value="${mobile}" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditWitness(data = null) {
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="witness_address[]" value="${data && data.address ? data.address : ''}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text"><img src="assets/image/contactph.png" alt="PH" style="width:20px;"> +63</span>
                                <input type="text" class="form-control" name="witness_contact[]" value="${mobile}" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    }
    
    function addEditAction(data = null) {
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
    
    // Attach event listeners
    function attachEditEventListeners() {
        // Add buttons
        document.getElementById('editAddComplainantBtn').addEventListener('click', function() {
            editComplainantCount++;
            addEditComplainant();
        });
        
        document.getElementById('editAddVictimBtn').addEventListener('click', function() {
            editVictimCount++;
            addEditVictim();
        });
        
        document.getElementById('editAddRespondentBtn').addEventListener('click', function() {
            editRespondentCount++;
            addEditRespondent();
        });
        
        document.getElementById('editAddWitnessBtn').addEventListener('click', function() {
            editWitnessCount++;
            addEditWitness();
        });
        
        document.getElementById('editAddActionBtn').addEventListener('click', function() {
            editActionCount++;
            addEditAction();
        });
        
        // Remove buttons
        document.querySelectorAll('.remove-edit-party-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const entry = this.closest('.party-entry');
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
                
                // Renumber entries
                const entries = container.querySelectorAll('.party-entry');
                entries.forEach((e, index) => {
                    const label = e.querySelector('.party-entry-header span');
                    if (label) {
                        const type = container.id.replace('edit', '').replace('Container', '').replace('s', '');
                        label.textContent = `${type} ${index + 1}`;
                    }
                });
                
                // Update counter
                if (container.id === 'editComplainantsContainer') editComplainantCount = entries.length;
                if (container.id === 'editVictimsContainer') editVictimCount = entries.length;
                if (container.id === 'editRespondentsContainer') editRespondentCount = entries.length;
                if (container.id === 'editWitnessesContainer') editWitnessCount = entries.length;
                if (container.id === 'editActionsContainer') editActionCount = entries.length;
            });
        });
        
        // Step navigation
        document.getElementById('editModalNextBtn').addEventListener('click', function() {
            if (!validateEditCurrentStep()) {
                return;
            }
            
            if (editCurrentStep < editSteps.length - 1) {
                editCurrentStep++;
                updateEditStepIndicator();
                updateEditFooterButtons();
            }
        });
        
        document.getElementById('editModalBackBtn').addEventListener('click', function() {
            if (editCurrentStep > 0) {
                editCurrentStep--;
                updateEditStepIndicator();
                updateEditFooterButtons();
            }
        });
        
        // Update button
        document.getElementById('updateRecordBtn').addEventListener('click', function() {
            const form = document.getElementById('editRecordForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            fetch('model/edit_blotter.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Blotter record updated successfully!');
                    editRecordModal.hide();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save"></i> Update Record';
                }
            })
            .catch(error => {
                console.error('Error updating record:', error);
                alert('An error occurred while updating the record.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> Update Record';
            });
        });
    }
    
    // Step indicator update
    function updateEditStepIndicator() {
        if (!editStepItems) return;
        editStepItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index < editCurrentStep) {
                item.classList.add('completed');
            } else if (index === editCurrentStep) {
                item.classList.add('active');
            }
        });
        
        if (!editTabPanes) return;
        editTabPanes.forEach((pane, index) => {
            pane.classList.remove('show', 'active');
            if (index === editCurrentStep) {
                pane.classList.add('show', 'active');
            }
        });
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
    
    // Validate current step
    function validateEditCurrentStep() {
        if (!editTabPanes || !editTabPanes[editCurrentStep]) return true;
        const currentPane = editTabPanes[editCurrentStep];
        const requiredFields = currentPane.querySelectorAll('[required]');
        
        for (let field of requiredFields) {
            if (!field.value || field.value.trim() === '') {
                field.reportValidity();
                return false;
            }
        }
        
        return true;
    }
})();
</script>
