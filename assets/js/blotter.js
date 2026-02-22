/**
 * Blotter Records Page JavaScript
 * Handles search, filtering, modals, and dynamic form elements
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // DOM Elements
    // ============================================
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const refreshBtn = document.getElementById('refreshBtn');
    const filterTabs = document.querySelectorAll('.tab-btn');
    const tableBody = document.getElementById('blotterTableBody');
    const createRecordBtn = document.getElementById('createRecordBtn');
    const saveRecordBtn = document.getElementById('saveRecordBtn');
    const addComplainantBtn = document.getElementById('addComplainantBtn');
    const addRespondentBtn = document.getElementById('addRespondentBtn');
    const complainantsContainer = document.getElementById('complainantsContainer');
    const respondentsContainer = document.getElementById('respondentsContainer');
    
    let currentFilter = 'all';
    let searchTerm = '';
    
    // ============================================
    // Initialize Bootstrap Modal
    // ============================================
    const createRecordModal = new bootstrap.Modal(document.getElementById('createRecordModal'));
    
    // ============================================
    // Search Functionality
    // ============================================
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            searchTerm = e.target.value.toLowerCase();
            filterTable();
            
            // Show/hide clear button
            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm ? 'flex' : 'none';
            }
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchTerm = '';
            filterTable();
            clearSearchBtn.style.display = 'none';
        });
    }
    
    // ============================================
    // Filter Tabs
    // ============================================
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get filter value
            currentFilter = this.getAttribute('data-filter');
            
            // Filter table
            filterTable();
        });
    });
    
    // ============================================
    // Filter Table Function
    // ============================================
    function filterTable() {
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) {
                return;
            }
            
            const status = row.getAttribute('data-status');
            const text = row.textContent.toLowerCase();
            
            // Check filter match
            const filterMatch = currentFilter === 'all' || status === currentFilter;
            
            // Check search match
            const searchMatch = !searchTerm || text.includes(searchTerm);
            
            // Show/hide row
            if (filterMatch && searchMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update pagination info
        updatePaginationInfo(visibleCount);
    }
    
    // ============================================
    // Update Pagination Info
    // ============================================
    function updatePaginationInfo(count) {
        const paginationInfo = document.querySelector('.pagination-info strong');
        if (paginationInfo) {
            paginationInfo.textContent = count.toLocaleString();
        }
    }
    
    // ============================================
    // Refresh Button
    // ============================================
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // Add spinning animation
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            
            // Reload page after short delay
            setTimeout(() => {
                location.reload();
            }, 500);
        });
    }
    
    // ============================================
    // Create Record Modal
    // ============================================
    if (createRecordBtn) {
        createRecordBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('createRecordForm').reset();
            
            // Reset step to 0
            currentStep = 0;
            updateStepIndicator();
            updateFooterButtons();
            
            // Show modal
            createRecordModal.show();
        });
    }
    
    // ============================================
    // Step Navigation - Footer Buttons
    // ============================================
    const modalNextBtn = document.getElementById('modalNextBtn');
    const modalBackBtn = document.getElementById('modalBackBtn');
    let currentStep = 0;
    const steps = ['basic-info', 'parties', 'incident', 'actions'];
    const stepItems = document.querySelectorAll('.step-item');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    function updateStepIndicator() {
        // Update step items
        stepItems.forEach((item, index) => {
            item.classList.remove('active', 'completed');
            if (index < currentStep) {
                item.classList.add('completed');
            } else if (index === currentStep) {
                item.classList.add('active');
            }
        });
        
        // Update tab panes
        tabPanes.forEach((pane, index) => {
            pane.classList.remove('show', 'active');
            if (index === currentStep) {
                pane.classList.add('show', 'active');
            }
        });
    }
    
    function updateFooterButtons() {
        // Show/hide back button
        if (currentStep === 0) {
            modalBackBtn.style.display = 'none';
        } else {
            modalBackBtn.style.display = 'inline-block';
        }
        
        // Show Next or Save button
        if (currentStep === steps.length - 1) {
            modalNextBtn.style.display = 'none';
            saveRecordBtn.style.display = 'inline-block';
        } else {
            modalNextBtn.style.display = 'inline-block';
            saveRecordBtn.style.display = 'none';
        }
    }
    
    // Next button handler
    if (modalNextBtn) {
        modalNextBtn.addEventListener('click', function() {
            // Validate current step before proceeding
            if (!validateCurrentStep()) {
                return;
            }
            
            if (currentStep < steps.length - 1) {
                currentStep++;
                updateStepIndicator();
                updateFooterButtons();
            }
        });
    }
    
    // Validate current step
    function validateCurrentStep() {
        const currentPane = tabPanes[currentStep];
        const requiredFields = currentPane.querySelectorAll('[required]');
        
        for (let field of requiredFields) {
            if (!field.value || field.value.trim() === '') {
                // Show validation message
                field.reportValidity();
                return false;
            }
        }
        
        return true;
    }
    
    // Back button handler
    if (modalBackBtn) {
        modalBackBtn.addEventListener('click', function() {
            if (currentStep > 0) {
                currentStep--;
                updateStepIndicator();
                updateFooterButtons();
            }
        });
    }
    
    
    
    // ============================================
    // Add Victim Button
    // ============================================
    const addVictimBtn = document.getElementById('addVictimBtn');
    const victimsContainer = document.getElementById('victimsContainer');
    
    if (addVictimBtn && victimsContainer) {
        // Start count at 1 since Victim 1 already exists in HTML
        let victimCount = 1;
        
        addVictimBtn.addEventListener('click', function() {
            victimCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Victim ${victimCount}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="party-entry-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="member-name-input-group">
                                <input type="text" class="form-control victim-name-input" name="victim_name[]" placeholder="Enter full name">
                                <button type="button" class="btn-resident-search" data-target="victim" data-index="${victimCount - 1}">
                                    RESIDENT
                                </button>
                                <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <input type="hidden" name="victim_resident_id[]" value="">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="victim_address[]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                        +63
                                    </span>
                                    <input type="text" class="form-control" name="victim_contact[]" placeholder="9XX XXX XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            victimsContainer.insertAdjacentHTML('beforeend', entryHtml);
        });
        
        // Remove victim entry
        victimsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                if (victimsContainer.querySelectorAll('.party-entry').length > 1) {
                    entry.remove();
                    // Renumber victims
                    const entries = victimsContainer.querySelectorAll('.party-entry');
                    entries.forEach((entry, index) => {
                        entry.querySelector('.party-entry-header span').textContent = `Victim ${index + 1}`;
                    });
                    victimCount = entries.length;
                } else {
                    alert('At least one victim is required.');
                }
            }
        });
    }
    
    // ============================================
    // Add Complainant Button
    // ============================================
    if (addComplainantBtn && complainantsContainer) {
        // Start count at 1 since Complainant 1 already exists in HTML
        let complainantCount = 1;
        
        addComplainantBtn.addEventListener('click', function() {
            complainantCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Complainant ${complainantCount}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="party-entry-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="member-name-input-group">
                                <input type="text" class="form-control complainant-name-input" name="complainant_name[]" placeholder="Enter full name">
                                <button type="button" class="btn-resident-search" data-target="complainant" data-index="${complainantCount - 1}">
                                    RESIDENT
                                </button>
                                <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <input type="hidden" name="complainant_resident_id[]" value="">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="complainant_address[]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                        +63
                                    </span>
                                    <input type="text" class="form-control" name="complainant_contact[]" placeholder="9XX XXX XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            complainantsContainer.insertAdjacentHTML('beforeend', entryHtml);
        });
        
        // Remove complainant entry
        complainantsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                if (complainantsContainer.querySelectorAll('.party-entry').length > 1) {
                    entry.remove();
                    // Renumber complainants
                    const entries = complainantsContainer.querySelectorAll('.party-entry');
                    entries.forEach((entry, index) => {
                        entry.querySelector('.party-entry-header span').textContent = `Complainant ${index + 1}`;
                    });
                    complainantCount = entries.length;
                } else {
                    alert('At least one complainant is required.');
                }
            }
        });
    }
    
    // ============================================
    // Save Record
    // ============================================
    if (saveRecordBtn) {
        saveRecordBtn.addEventListener('click', function() {
            const form = document.getElementById('createRecordForm');
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Get form data
            const formData = new FormData(form);
            
            // Show loading state
            saveRecordBtn.disabled = true;
            saveRecordBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Send data to server via AJAX
            fetch('model/save_blotter_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('Blotter record saved successfully!\nRecord Number: ' + data.data.record_number);
                    
                    // Close modal
                    createRecordModal.hide();
                    
                    // Reload page to show new record
                    location.reload();
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                    
                    // Reset button
                    saveRecordBtn.disabled = false;
                    saveRecordBtn.innerHTML = '<i class="fas fa-save"></i> Save Record';
                }
            })
            .catch(error => {
                console.error('Error saving blotter record:', error);
                alert('An error occurred while saving the record. Please try again.');
                
                // Reset button
                saveRecordBtn.disabled = false;
                saveRecordBtn.innerHTML = '<i class="fas fa-save"></i> Save Record';
            });
        });
    }
    
    // ============================================
    // Action Button (Three Dots Menu)
    // ============================================
    document.addEventListener('click', function(e) {
        // Toggle action menu
        if (e.target.closest('.btn-action')) {
            e.stopPropagation();
            const btn = e.target.closest('.btn-action');
            const container = btn.closest('.action-menu-container');
            const menu = container.querySelector('.action-menu');
            
            // Close all other menus
            document.querySelectorAll('.action-menu.show').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('show');
                }
            });
            
            // Position the menu relative to the button
            const rect = btn.getBoundingClientRect();
            const menuWidth = 200; // min-width of menu
            
            // Position below the button
            menu.style.top = (rect.bottom + 8) + 'px';
            
            // Align to the right edge of the button
            menu.style.left = (rect.right - menuWidth) + 'px';
            
            // Toggle current menu
            menu.classList.toggle('show');
            return;
        }
        
        // Handle action menu item clicks
        if (e.target.closest('.action-menu-item')) {
            const item = e.target.closest('.action-menu-item');
            const action = item.getAttribute('data-action');
            const menu = item.closest('.action-menu');
            const recordId = menu.getAttribute('data-record-id');
            
            // Don't close menu if it's the status item (has submenu)
            if (item.classList.contains('has-submenu')) {
                return;
            }
            
            // Handle different actions
            handleAction(action, recordId);
            
            // Close menu
            menu.classList.remove('show');
        }
        
        // Close menu when clicking outside
        if (!e.target.closest('.action-menu-container')) {
            document.querySelectorAll('.action-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Handle action menu actions
    function handleAction(action, recordId) {
        console.log('Action:', action, 'Record ID:', recordId);
        
        switch(action) {
            case 'view':
                viewBlotterDetails(recordId);
                break;
                
            case 'edit':
                editBlotterRecord(recordId);
                break;
                
            case 'status-pending':
                updateBlotterStatus(recordId, 'Pending');
                break;
                
            case 'status-investigation':
                updateBlotterStatus(recordId, 'Under Investigation');
                break;
                
            case 'status-resolved':
                updateBlotterStatus(recordId, 'Resolved');
                break;
                
            case 'status-dismissed':
                updateBlotterStatus(recordId, 'Dismissed');
                break;
                
            case 'archive':
                archiveBlotterRecord(recordId);
                break;
                
            case 'delete':
                deleteBlotterRecord(recordId);
                break;
        }
    }
    
    // View blotter details
    function viewBlotterDetails(recordId) {
        console.log('View blotter details:', recordId);
        const viewModalEl = document.getElementById('viewRecordModal');
        if (!viewModalEl) return;
        
        // Initialize or get modal instance
        let viewRecordModal = bootstrap.Modal.getInstance(viewModalEl);
        if (!viewRecordModal) {
            viewRecordModal = new bootstrap.Modal(viewModalEl);
        }
        
        // Fetch record details
        fetch(`model/edit_blotter.php?ajax=true&id=${recordId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateViewModal(data.data);
                    viewRecordModal.show();
                } else {
                    alert('Error loading record: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching record:', error);
                alert('An error occurred while loading the record.');
            });
    }

    function populateViewModal(data) {
        const record = data.record;
        
        // Basic Info
        const statusEl = document.getElementById('view_status');
        if (statusEl) statusEl.value = record.status;
        
        const dateEl = document.getElementById('view_incident_date');
        if (dateEl) dateEl.value = formatDateTime(record.incident_date);
        
        // Incident Details
        const typeEl = document.getElementById('view_incident_type');
        if (typeEl) typeEl.value = record.incident_type;
        
        const locEl = document.getElementById('view_incident_location');
        if (locEl) locEl.value = record.incident_location;
        
        const descEl = document.getElementById('view_incident_description');
        if (descEl) descEl.value = record.incident_description;
        
        const resEl = document.getElementById('view_resolution');
        if (resEl) resEl.value = record.resolution || 'No resolution recorded.';

        // Populate Containers
        populateViewParties('viewComplainantsContainer', data.complainants);
        populateViewParties('viewVictimsContainer', data.victims);
        populateViewParties('viewRespondentsContainer', data.respondents);
        populateViewParties('viewWitnessesContainer', data.witnesses);
        populateViewActions('viewActionsContainer', data.actions);
        
        // Setup Print Button
        const printBtn = document.getElementById('printRecordBtn');
        if(printBtn) {
            printBtn.onclick = function() {
                window.open(`print_blotter.php?id=${record.id}`, '_blank');
            };
        }
    }
    
    function populateViewParties(containerId, parties) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!parties || parties.length === 0) {
            container.innerHTML = '<p class="text-muted fst-italic small">No records found.</p>';
            return;
        }
        
        parties.forEach((party) => {
            const html = `
                <div class="party-view-item">
                    <div class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">${party.name}</div>
                    <div class="small text-muted d-flex align-items-center">
                        ${party.contact_number ? `<i class="fas fa-phone-alt me-1"></i> ${party.contact_number}` : ''}
                        ${party.address ? `<span class="mx-2 text-secondary">|</span> <i class="fas fa-map-marker-alt me-1"></i> ${party.address}` : ''}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    function populateViewActions(containerId, actions) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!actions || actions.length === 0) {
            container.innerHTML = '<p class="text-muted fst-italic small">No actions recorded.</p>';
            return;
        }
        
        actions.forEach(action => {
            const html = `
                <div class="action-view-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small text-primary"><i class="far fa-calendar-alt me-1"></i> ${action.date}</span>
                        <span class="badge bg-secondary">${action.officer || 'Officer'}</span>
                    </div>
                    <div class="text-dark">${action.details}</div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    function formatDateTime(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric', 
            hour: '2-digit', minute: '2-digit' 
        });
    }
    
    // Edit blotter record
    function editBlotterRecord(recordId) {
        console.log('Edit blotter record:', recordId);
        // Call the openEditBlotterModal function from edit_blotter.php
        if (typeof openEditBlotterModal === 'function') {
            openEditBlotterModal(recordId);
        } else {
            console.error('openEditBlotterModal function not found');
            alert('Error: Edit modal not loaded properly');
        }
    }
    
    // Update blotter status
    function updateBlotterStatus(recordId, newStatus) {
        if (!confirm(`Change status to "${newStatus}"?`)) {
            return;
        }
        
        console.log('Updating status:', recordId, newStatus);
        
        // Send AJAX request to update status
        fetch('model/update_blotter_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${recordId}&status=${encodeURIComponent(newStatus)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Status updated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            alert('An error occurred while updating the status.');
        });
    }
    
    // Archive blotter record
    function archiveBlotterRecord(recordId) {
        if (!confirm('Are you sure you want to archive this record?')) {
            return;
        }
        
        console.log('Archive blotter record:', recordId);
        
        // Send AJAX request to archive
        fetch('model/archive_blotter_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${recordId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Record archived successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error archiving record:', error);
            alert('An error occurred while archiving the record.');
        });
    }
    
    // Delete blotter record
    function deleteBlotterRecord(recordId) {
        if (!confirm('Are you sure you want to permanently delete this record? This action cannot be undone.')) {
            return;
        }
        
        console.log('Delete blotter record:', recordId);
        
        // Send AJAX request to delete
        fetch('model/delete_blotter_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${recordId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Record deleted successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error deleting record:', error);
            alert('An error occurred while deleting the record.');
        });
    }
    
    // ============================================
    // Initialize Tooltips
    // ============================================
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // ============================================
    // Table Row Click (Optional - for viewing details)
    // ============================================
    const tableRows = document.querySelectorAll('.blotter-table tbody tr');
    tableRows.forEach(row => {
        // Skip empty state row
        if (row.querySelector('td[colspan]')) {
            return;
        }
        
        row.style.cursor = 'pointer';
        
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking action button
            if (e.target.closest('.btn-action')) {
                return;
            }
            
            // TODO: Show record details modal
            console.log('Row clicked');
        });
    });
    
    // ============================================
    // Set Default Incident Date to Now
    // ============================================
    const incidentDateInput = document.getElementById('incidentDate');
    if (incidentDateInput) {
        // Set to current date and time
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        
        incidentDateInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    // ============================================
    // Keyboard Shortcuts
    // ============================================
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Ctrl/Cmd + N to create new record
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            createRecordBtn.click();
        }
        
        // Escape to close modal
        if (e.key === 'Escape') {
            createRecordModal.hide();
        }
    });
    
    // ============================================
    // Add Witness Button
    // ============================================
    const addWitnessBtn = document.getElementById('addWitnessBtn');
    const witnessesContainer = document.getElementById('witnessesContainer');
    
    if (addWitnessBtn && witnessesContainer) {
        let witnessCount = 0;
        
        addWitnessBtn.addEventListener('click', function() {
            witnessCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Witness ${witnessCount}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="party-entry-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="member-name-input-group">
                                <input type="text" class="form-control witness-name-input" name="witness_name[]" placeholder="Enter full name">
                                <button type="button" class="btn-resident-search" data-target="witness" data-index="${witnessCount - 1}">
                                    RESIDENT
                                </button>
                                <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <input type="hidden" name="witness_resident_id[]" value="">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="witness_address[]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                        +63
                                    </span>
                                    <input type="text" class="form-control" name="witness_contact[]" placeholder="9XX XXX XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            witnessesContainer.insertAdjacentHTML('beforeend', entryHtml);
        });
        
        // Remove witness entry
        witnessesContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                entry.remove();
                // Renumber witnesses
                const entries = witnessesContainer.querySelectorAll('.party-entry');
                entries.forEach((entry, index) => {
                    entry.querySelector('.party-entry-header span').textContent = `Witness ${index + 1}`;
                });
                witnessCount = entries.length;
            }
        });
    }
    
    // ============================================
    // Add Action Taken Button
    // ============================================
    const addActionBtn = document.getElementById('addActionBtn');
    const actionsContainer = document.getElementById('actionsContainer');
    
    if (addActionBtn && actionsContainer) {
        let actionCount = 0;
        
        addActionBtn.addEventListener('click', function() {
            actionCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Action ${actionCount}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="party-entry-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Action Date</label>
                                <input type="date" class="form-control" name="action_date[]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Officer In Charge</label>
                                <input type="text" class="form-control" name="action_officer[]">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Action Details</label>
                            <textarea class="form-control" name="action_details[]" rows="3" placeholder="Action Details"></textarea>
                        </div>
                    </div>
                </div>
            `;
            actionsContainer.insertAdjacentHTML('beforeend', entryHtml);
        });
        
        // Remove action entry
        actionsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                entry.remove();
                // Renumber actions
                const entries = actionsContainer.querySelectorAll('.party-entry');
                entries.forEach((entry, index) => {
                    entry.querySelector('.party-entry-header span').textContent = `Action ${index + 1}`;
                });
                actionCount = entries.length;
            }
        });
    }
    
    // ============================================
    // Update Respondent Button Handler
    // ============================================
    if (addRespondentBtn && respondentsContainer) {
        let respondentCount = 1;
        
        // Override existing handler
        const newRespondentBtn = addRespondentBtn.cloneNode(true);
        addRespondentBtn.parentNode.replaceChild(newRespondentBtn, addRespondentBtn);
        
        newRespondentBtn.addEventListener('click', function() {
            respondentCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Respondents ${respondentCount}</span>
                        <button type="button" class="btn btn-sm btn-danger remove-party-btn" style="float: right; padding: 2px 8px; font-size: 12px;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="party-entry-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <div class="member-name-input-group">
                                <input type="text" class="form-control respondent-name-input" name="respondent_name[]" placeholder="Enter full name">
                                <button type="button" class="btn-resident-search" data-target="respondent" data-index="${respondentCount - 1}">
                                    RESIDENT
                                </button>
                                <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                            <input type="hidden" name="respondent_resident_id[]" value="">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control" name="respondent_address[]">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Mobile Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                        +63
                                    </span>
                                    <input type="text" class="form-control" name="respondent_contact[]" placeholder="9XX XXX XXXX">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            respondentsContainer.insertAdjacentHTML('beforeend', entryHtml);
        });
        
        // Remove respondent entry
        respondentsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                if (respondentsContainer.querySelectorAll('.party-entry').length > 1) {
                    entry.remove();
                    // Renumber respondents
                    const entries = respondentsContainer.querySelectorAll('.party-entry');
                    entries.forEach((entry, index) => {
                        entry.querySelector('.party-entry-header span').textContent = `Respondents ${index + 1}`;
                    });
                    respondentCount = entries.length;
                } else {
                    alert('At least one respondent is required.');
                }
            }
        });
    }
    
    // ============================================
    // RESIDENT Button Click Handlers
    // ============================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-resident-search')) {
            const btn = e.target.closest('.btn-resident-search');
            const target = btn.getAttribute('data-target');
            const index = btn.getAttribute('data-index');
            
            // Store context for later use
            window.currentResidentSearchContext = { target, index, button: btn };
            
            // Open search modal
            openSearchResidentModal();
        }
    });
    
    // ============================================
    // Search Resident Modal Functions
    // ============================================
    function openSearchResidentModal() {
        const modal = document.getElementById('searchResidentModal');
        if (modal) {
            modal.classList.add('show');
            loadResidents('');
            setTimeout(() => {
                document.getElementById('residentSearchInput').focus();
            }, 300);
        }
    }
    
    window.closeSearchResidentModal = function() {
        const modal = document.getElementById('searchResidentModal');
        if (modal) {
            modal.classList.remove('show');
            document.getElementById('residentSearchInput').value = '';
        }
    };
    
    // Load residents
    function loadResidents(searchTerm = '') {
        const container = document.getElementById('residentsListContainer');
        
        container.innerHTML = `
            <div class="loading-residents">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading residents...</span>
            </div>
        `;
        
        fetch(`model/search_residents.php?search=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    displayResidents(data.data);
                } else {
                    container.innerHTML = `
                        <div class="loading-residents">
                            <i class="fas fa-user-slash"></i>
                            <span>No residents found</span>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading residents:', error);
                container.innerHTML = `
                    <div class="loading-residents">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Error loading residents</span>
                    </div>
                `;
            });
    }
    
    // Display residents with filtering based on selection rules
    function displayResidents(residents) {
        const container = document.getElementById('residentsListContainer');
        container.innerHTML = '';
        
        const context = window.currentResidentSearchContext;
        if (!context) return;
        
        const { target } = context;
        
        // Get all selected resident IDs by category (convert to strings for comparison)
        const selectedComplainants = getSelectedResidentIds('complainant').map(String);
        const selectedVictims = getSelectedResidentIds('victim').map(String);
        const selectedRespondents = getSelectedResidentIds('respondent').map(String);
        const selectedWitnesses = getSelectedResidentIds('witness').map(String);
        
        console.log('Selected Complainants:', selectedComplainants);
        console.log('Selected Victims:', selectedVictims);
        console.log('Selected Respondents:', selectedRespondents);
        console.log('Current target:', target);
        
        let filteredResidents = residents.filter(resident => {
            const residentId = String(resident.id); // Convert to string for comparison
            
            // Logic 1: Cannot select same resident twice in same category
            if (target === 'complainant' && selectedComplainants.includes(residentId)) {
                console.log(`Filtering out ${resident.full_name} - already a complainant`);
                return false;
            }
            if (target === 'victim' && selectedVictims.includes(residentId)) {
                console.log(`Filtering out ${resident.full_name} - already a victim`);
                return false;
            }
            if (target === 'respondent' && selectedRespondents.includes(residentId)) {
                console.log(`Filtering out ${resident.full_name} - already a respondent`);
                return false;
            }
            if (target === 'witness' && selectedWitnesses.includes(residentId)) {
                console.log(`Filtering out ${resident.full_name} - already a witness`);
                return false;
            }
            
            // Logic 2: Complainants can be victims (allowed) - no filtering needed
            
            // Logic 3: Complainants and victims cannot be respondents
            if (target === 'respondent') {
                if (selectedComplainants.includes(residentId)) {
                    console.log(`Filtering out ${resident.full_name} - is a complainant`);
                    return false;
                }
                if (selectedVictims.includes(residentId)) {
                    console.log(`Filtering out ${resident.full_name} - is a victim`);
                    return false;
                }
            }
            
            // Logic 4: Respondents cannot be witnesses
            if (target === 'witness' && selectedRespondents.includes(residentId)) {
                console.log(`Filtering out ${resident.full_name} - is a respondent`);
                return false;
            }
            
            return true;
        });
        
        console.log(`Filtered residents: ${filteredResidents.length} of ${residents.length}`);
        
        if (filteredResidents.length === 0) {
            container.innerHTML = `
                <div class="loading-residents">
                    <i class="fas fa-user-slash"></i>
                    <span>No available residents</span>
                </div>
            `;
            return;
        }
        
        filteredResidents.forEach(resident => {
            const item = document.createElement('div');
            item.className = 'resident-item';
            item.innerHTML = `
                <div class="resident-item-name">${resident.full_name}</div>
                <div class="resident-item-id">${resident.resident_id || 'No ID'}</div>
            `;
            
            item.addEventListener('click', () => {
                selectResident(resident);
            });
            
            container.appendChild(item);
        });
    }
    
    // Select resident (validation now done in displayResidents)
    function selectResident(resident) {
        const context = window.currentResidentSearchContext;
        if (!context) return;
        
        const { target, index, button } = context;
        
        // Find the input field next to the button
        const inputGroup = button.closest('.member-name-input-group');
        const nameInput = inputGroup.querySelector('input[type="text"]');
        const hiddenInput = inputGroup.parentElement.querySelector('input[type="hidden"]');
        const resetBtn = inputGroup.querySelector('.btn-reset-resident');
        
        // Set the values
        if (nameInput) {
            nameInput.value = resident.full_name;
            nameInput.setAttribute('readonly', 'readonly');
        }
        if (hiddenInput) {
            hiddenInput.value = resident.id;
        }
        
        // Show reset button, hide RESIDENT button
        if (resetBtn) {
            resetBtn.style.display = 'flex';
        }
        const residentBtn = inputGroup.querySelector('.btn-resident-search');
        if (residentBtn) {
            residentBtn.style.display = 'none';
        }
        
        // Find and populate address and mobile fields if they exist
        const partyEntry = button.closest('.party-entry-body');
        if (partyEntry) {
            const addressInput = partyEntry.querySelector('input[name*="_address"]');
            const mobileInput = partyEntry.querySelector('input[name*="_contact"]');
            
            if (addressInput && resident.current_address) {
                addressInput.value = resident.current_address;
                addressInput.setAttribute('readonly', 'readonly');
            }
            if (mobileInput && resident.mobile_number) {
                // Remove +63 prefix if present
                const mobile = resident.mobile_number.replace(/^\+63/, '').replace(/\s/g, '');
                mobileInput.value = mobile;
                mobileInput.setAttribute('readonly', 'readonly');
            }
        }
        
        closeSearchResidentModal();
        
        // Show success message
        console.log('Resident selected:', resident.full_name);
    }
    
    // Get all selected resident IDs for a specific category
    function getSelectedResidentIds(category) {
        const ids = [];
        const hiddenInputs = document.querySelectorAll(`input[name="${category}_resident_id[]"]`);
        
        hiddenInputs.forEach(input => {
            if (input.value && input.value.trim() !== '') {
                ids.push(input.value);
            }
        });
        
        return ids;
    }
    
    // Search input handler
    const residentSearchInput = document.getElementById('residentSearchInput');
    if (residentSearchInput) {
        let searchTimeout;
        residentSearchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadResidents(e.target.value);
            }, 300);
        });
    }
    
    // Close modal when clicking outside
    const searchModal = document.getElementById('searchResidentModal');
    if (searchModal) {
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                closeSearchResidentModal();
            }
        });
    }
    
    // ============================================
    // Reset Button Handler
    // ============================================
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-reset-resident')) {
            const resetBtn = e.target.closest('.btn-reset-resident');
            const inputGroup = resetBtn.closest('.member-name-input-group');
            const nameInput = inputGroup.querySelector('input[type="text"]');
            const hiddenInput = inputGroup.parentElement.querySelector('input[type="hidden"]');
            const residentBtn = inputGroup.querySelector('.btn-resident-search');
            
            // Clear values
            if (nameInput) {
                nameInput.value = '';
                nameInput.removeAttribute('readonly');
                nameInput.focus();
            }
            if (hiddenInput) {
                hiddenInput.value = '';
            }
            
            // Clear address and mobile if they were auto-filled
            const partyEntry = resetBtn.closest('.party-entry-body');
            if (partyEntry) {
                const addressInput = partyEntry.querySelector('input[name*="_address"]');
                const mobileInput = partyEntry.querySelector('input[name*="_contact"]');
                
                if (addressInput) {
                    addressInput.value = '';
                }
                if (mobileInput) {
                    mobileInput.value = '';
                }
            }
            
            // Hide reset button, show RESIDENT button
            resetBtn.style.display = 'none';
            if (residentBtn) {
                residentBtn.style.display = 'block';
            }
            
            console.log('Resident selection reset');
        }
    });
    
    console.log('Blotter Records page initialized');
});
