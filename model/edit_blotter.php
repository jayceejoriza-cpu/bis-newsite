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
        $status = trim($_POST['status'] ?? '');
        if (empty($status)) {
            throw new Exception('Case status is required');
        }
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

<div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="col-12">
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
    </div>
    <div class="col-12" id="edit_mediation_field" style="display:none;">
        <label class="form-label fw-bold">Mediation Schedule</label>
        <input type="datetime-local" class="form-control" id="edit_mediation_date" name="mediation_schedule">
    </div>
    <div class="col-12">
        <label class="form-label fw-bold">Resolution / Actions Taken</label>
        <textarea class="form-control" id="edit_resolution" name="resolution" rows="4" placeholder="Final resolution, settlement terms, fines imposed, referrals made, signatures obtained, etc."></textarea>
    </div>

                                <div class="col-12 mt-4">
                                    <h6 class="party-title mb-3"><i class="fas fa-eye text-success"></i> Witnesses</h6>
                                    <div id="editWitnessesContainer" class="party-section mb-2"></div>
                                    <button type="button" class="btn btn-outline-success btn-sm" id="editAddWitnessBtn"><i class="fas fa-plus"></i> Add Witness</button>
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
        populateEditParties('editActionsContainer', 'action', data.actions || [], 0);
        console.log('✓ Parties populated');
        
        // Mediation visibility
        const mediationField = document.getElementById('edit_mediation_field');
        if (mediationField) {
            mediationField.style.display = record.status === 'Scheduled for Mediation' ? 'block' : 'none';
        }
        
        console.log('=== LOAD EDIT DATA COMPLETE ===');
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
                if (mediationField) mediationField.style.display = this.value === 'Scheduled for Mediation' ? 'block' : 'none';
            });
        }

        // Add buttons for parties
        addSafeListener('editAddComplainantBtn', 'click', () => addEditComplainant());
        addSafeListener('editAddVictimBtn', 'click', () => addEditVictim());
        addSafeListener('editAddRespondentBtn', 'click', () => addEditRespondent());
        addSafeListener('editAddWitnessBtn', 'click', () => addEditWitness());
        
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
