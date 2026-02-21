/**
 * Edit Blotter Record Modal JavaScript
 * Handles edit modal functionality
 */

// Global variables for edit modal
let editRecordModal;
let editCurrentStep = 0;
const editSteps = ['edit-basic-info', 'edit-parties', 'edit-incident', 'edit-actions'];
let editStepItems;
let editTabPanes;

// Counters for dynamic entries
let editComplainantCount = 0;
let editVictimCount = 0;
let editRespondentCount = 0;
let editWitnessCount = 0;
let editActionCount = 0;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('editRecordModal');
    if (modalElement) {
        editRecordModal = new bootstrap.Modal(modalElement);
        editStepItems = document.querySelectorAll('#editRecordModal .step-item');
        editTabPanes = document.querySelectorAll('#editRecordModal .tab-pane');
    }
});

// Global function to open edit modal
window.openEditBlotterModal = function(recordId) {
    console.log('Opening edit modal for record:', recordId);
    
    // Ensure modal is initialized
    if (!editRecordModal) {
        const modalElement = document.getElementById('editRecordModal');
        if (modalElement) {
            editRecordModal = new bootstrap.Modal(modalElement);
            editStepItems = document.querySelectorAll('#editRecordModal .step-item');
            editTabPanes = document.querySelectorAll('#editRecordModal .tab-pane');
        } else {
            console.error('Edit modal element not found');
            alert('Error: Edit modal not loaded properly');
            return;
        }
    }
    
    // Reset form and step
    const form = document.getElementById('editRecordForm');
    if (form) {
        form.reset();
    }
    editCurrentStep = 0;
    updateEditStepIndicator();
    updateEditFooterButtons();
    
    // Show loading state
    const modalBody = document.querySelector('#editRecordModal .modal-body');
    if (modalBody) {
        modalBody.innerHTML = '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p class="mt-3">Loading record...</p></div>';
    }
    
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
    // Get the original form HTML
    const formHTML = `
        <form id="editRecordForm">
            <input type="hidden" name="record_id" id="edit_record_id">
            
            <div class="step-indicator">
                <div class="step-item active" data-step="0" id="edit-step-basic-info">
                    <div class="step-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="step-label">Basic Info</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="1" id="edit-step-parties">
                    <div class="step-icon"><i class="fas fa-users"></i></div>
                    <div class="step-label">Parties Involved</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="2" id="edit-step-incident">
                    <div class="step-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="step-label">Incident Details</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="3" id="edit-step-actions">
                    <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
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
                        
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-user"></i> Complainant</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="editAddComplainantBtn"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="editComplainantsContainer"></div>
                        </div>
                        
                        <div class="party-section">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-user-injured"></i> Victims</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="editAddVictimBtn"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="editVictimsContainer"></div>
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
                            <div id="editRespondentsContainer"></div>
                        </div>
                        
                        <div class="party-section">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-eye"></i> Witnesses</h6>
                                <button type="button" class="btn btn-sm btn-primary" id="editAddWitnessBtn"><i class="fas fa-plus"></i></button>
                            </div>
                            <div id="editWitnessesContainer"></div>
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
                            <div id="editActionsContainer"></div>
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
    `;
    
    // Restore modal body HTML
    document.querySelector('#editRecordModal .modal-body').innerHTML = formHTML;
    
    // Re-initialize step items and panes
    editStepItems = document.querySelectorAll('#editRecordModal .step-item');
    editTabPanes = document.querySelectorAll('#editRecordModal .tab-pane');
    
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
    data.complainants.forEach((complainant) => {
        editComplainantCount++;
        addEditComplainant(complainant);
    });
    
    // Load victims
    editVictimCount = 0;
    const victimsContainer = document.getElementById('editVictimsContainer');
    victimsContainer.innerHTML = '';
    data.victims.forEach((victim) => {
        editVictimCount++;
        addEditVictim(victim);
    });
    
    // Load respondents
    editRespondentCount = 0;
    const respondentsContainer = document.getElementById('editRespondentsContainer');
    respondentsContainer.innerHTML = '';
    data.respondents.forEach((respondent) => {
        editRespondentCount++;
        addEditRespondent(respondent);
    });
    
    // Load witnesses
    editWitnessCount = 0;
    const witnessesContainer = document.getElementById('editWitnessesContainer');
    witnessesContainer.innerHTML = '';
    data.witnesses.forEach((witness) => {
        editWitnessCount++;
        addEditWitness(witness);
    });
    
    // Load actions
    editActionCount = 0;
    const actionsContainer = document.getElementById('editActionsContainer');
    actionsContainer.innerHTML = '';
    if (data.actions && data.actions.length > 0) {
        data.actions.forEach((action) => {
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
                            <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
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
                            <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
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
                            <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
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
                            <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
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
    const addComplainantBtn = document.getElementById('editAddComplainantBtn');
    if (addComplainantBtn) {
        addComplainantBtn.addEventListener('click', function() {
            editComplainantCount++;
            addEditComplainant();
        });
    }
    
    const addVictimBtn = document.getElementById('editAddVictimBtn');
    if (addVictimBtn) {
        addVictimBtn.addEventListener('click', function() {
            editVictimCount++;
            addEditVictim();
        });
    }
    
    const addRespondentBtn = document.getElementById('editAddRespondentBtn');
    if (addRespondentBtn) {
        addRespondentBtn.addEventListener('click', function() {
            editRespondentCount++;
            addEditRespondent();
        });
    }
    
    const addWitnessBtn = document.getElementById('editAddWitnessBtn');
    if (addWitnessBtn) {
        addWitnessBtn.addEventListener('click', function() {
            editWitnessCount++;
            addEditWitness();
        });
    }
    
    const addActionBtn = document.getElementById('editAddActionBtn');
    if (addActionBtn) {
        addActionBtn.addEventListener('click', function() {
            editActionCount++;
            addEditAction();
        });
    }
    
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
    const nextBtn = document.getElementById('editModalNextBtn');
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            if (!validateEditCurrentStep()) {
                return;
            }
            
            if (editCurrentStep < editSteps.length - 1) {
                editCurrentStep++;
                updateEditStepIndicator();
                updateEditFooterButtons();
            }
        });
    }
    
    const backBtn = document.getElementById('editModalBackBtn');
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            if (editCurrentStep > 0) {
                editCurrentStep--;
                updateEditStepIndicator();
                updateEditFooterButtons();
            }
        });
    }
    
    // Update button
    const updateBtn = document.getElementById('updateRecordBtn');
    if (updateBtn) {
        updateBtn.addEventListener('click', function() {
            const form = document.getElementById('editRecordForm');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const formData = new FormData(form);
            
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            const thisBtn = this;
            
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
                    thisBtn.disabled = false;
                    thisBtn.innerHTML = '<i class="fas fa-save"></i> Update Record';
                }
            })
            .catch(error => {
                console.error('Error updating record:', error);
                alert('An error occurred while updating the record.');
                thisBtn.disabled = false;
                thisBtn.innerHTML = '<i class="fas fa-save"></i> Update Record';
            });
        });
    }
}

// Step indicator update
function updateEditStepIndicator() {
    if (!editStepItems || !editTabPanes) return;
    
    editStepItems.forEach((item, index) => {
        item.classList.remove('active', 'completed');
        if (index < editCurrentStep) {
            item.classList.add('completed');
        } else if (index === editCurrentStep) {
            item.classList.add('active');
        }
    });
    
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
    
    if (!backBtn || !nextBtn || !updateBtn) return;
    
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
    if (!editTabPanes || editTabPanes.length === 0) return true;
    
    const currentPane = editTabPanes[editCurrentStep];
    if (!currentPane) return true;
    
    const requiredFields = currentPane.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        if (!field.value || field.value.trim() === '') {
            field.reportValidity();
            return false;
        }
    }
    
    return true;
}
