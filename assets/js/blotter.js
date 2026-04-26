/**
 * Blotter Records Page JavaScript
 * Handles search, filtering, modals, and dynamic form elements
 */

let blotterTable;

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
    let selectedFiles = []; // Persistent list of files to be uploaded

    const uploadZone = document.getElementById('incidentProofUploadZone');
    const fileInput = document.getElementById('incidentProofInput');
    const previewContainer = document.getElementById('incidentProofPreviewContainer');

    // ============================================
    // Initialize EnhancedTable for Pagination
    // ============================================
    blotterTable = new EnhancedTable('blotterTable', {
        sortable: false,
        searchable: false,
        paginated: true,
        pageSize: 10,
        responsive: true
    });
    
    // Load search from URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('search')) {
        searchTerm = urlParams.get('search').toLowerCase();
        if (searchInput) searchInput.value = urlParams.get('search');
        filterTable();
        if (clearSearchBtn) clearSearchBtn.style.display = 'flex';
    }

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
            window.reinitActionMenus();
            
            const url = new URL(window.location);
            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }
            window.history.replaceState({}, '', url);

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
            const url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.replaceState({}, '', url);
            window.reinitActionMenus();
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
            window.reinitActionMenus();
        });
    });
    
    // ============================================
    // Filter Table Function — uses EnhancedTable
    // ============================================
    function filterTable() {
        if (!blotterTable) return;

        blotterTable.filter(row => {
            // Always exclude colspan/empty-state rows
            if (row.querySelector('td[colspan]')) return false;

            const status = row.getAttribute('data-status');
            const text   = row.textContent.toLowerCase();

            const filterMatch = currentFilter === 'all' || status === currentFilter;
            const searchMatch = !searchTerm || text.includes(searchTerm);

            return filterMatch && searchMatch;
        });
    }
    
    // ============================================
    // Print Masterlist Button
    // ============================================
    const printBtn = document.getElementById('printMasterlistBtn');
    if (printBtn) {
        printBtn.addEventListener('click', async () => {
            let brgyInfo = {
                province_name: 'Province',
                town_name: 'Municipality',
                barangay_name: 'Barangay',
                barangay_logo: '',
                official_emblem: ''
            };
            
            try {
                const response = await fetch('model/get_barangay_info.php');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        brgyInfo = data.data;
                    }
                }
            } catch (error) {
                console.error('Error fetching barangay info:', error);
            }
            
            const brgyLogoHtml = brgyInfo.barangay_logo 
                ? `<img src="${brgyInfo.barangay_logo}" class="logo-img" alt="Barangay Logo">`
                : `<div class="logo-placeholder-box"></div>`;
                
            const govLogoHtml = brgyInfo.official_emblem
                ? `<img src="${brgyInfo.official_emblem}" class="logo-img" alt="Official Emblem">`
                : `<div class="logo-placeholder-box"></div>`;

            let printFrame = document.getElementById('blotterPrintFrame');
            if (!printFrame) {
                printFrame = document.createElement('iframe');
                printFrame.id = 'blotterPrintFrame';
                printFrame.style.position = 'fixed';
                printFrame.style.bottom = '0';
                printFrame.style.right = '0';
                printFrame.style.width = '0';
                printFrame.style.height = '0';
                printFrame.style.border = 'none';
                document.body.appendChild(printFrame);
            }

            const doc = printFrame.contentWindow.document;
            doc.open();

            const tableHeaderHtml = `
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No.</th>
                        <th>Record #</th>
                        <th>Date Reported</th>
                        <th>Status</th>
                        <th>Complainants</th>
                        <th>Victims</th>
                        <th>Respondents</th>
                        <th>Witnesses</th>
                        <th>Incident Type</th>
                        <th>Incident Date</th>
                    </tr>
                </thead>
            `;

            let rowsHtml = '';
            let rowsToPrint = [];
            
            if (typeof blotterTable !== 'undefined' && blotterTable.filteredRows) {
                rowsToPrint = blotterTable.filteredRows;
            } else {
                rowsToPrint = Array.from(document.querySelectorAll('#blotterTableBody tr:not([style*="display: none"])'));
            }
            
            rowsToPrint.forEach((row, index) => {
                if (row.cells.length < 8) return;
                if (row.querySelector('td[colspan]')) return;

                const no = index + 1;
                const recordNum = row.cells[0]?.textContent.trim() || '';
                const dateRep = row.cells[1]?.textContent.trim() || '';
                const status = row.cells[2]?.textContent.trim() || '';
                
                const compEls = row.cells[3]?.querySelectorAll('.avatar-sm');
                let complainants = [];
                if (compEls) {
                    compEls.forEach(el => complainants.push(el.getAttribute('title') || el.textContent.trim()));
                }
                const compStr = complainants.join(', ');
                
                const respEls = row.cells[4]?.querySelectorAll('.avatar-sm');
                let respondents = [];
                if (respEls) {
                    respEls.forEach(el => respondents.push(el.getAttribute('title') || el.textContent.trim()));
                }
                const respStr = respondents.join(', ');

                const victStr = row.getAttribute('data-victims') || '---';
                const witnStr = row.getAttribute('data-witnesses') || '---';

                const incType = row.cells[5]?.textContent.trim() || '';
                const incDate = row.cells[6]?.textContent.trim() || '';

                rowsHtml += `
                    <tr style="display: table-row;">
                        <td style="text-align: center;">${no}</td>
                        <td>${recordNum}</td>
                        <td>${dateRep}</td>
                        <td>${status}</td>
                        <td>${compStr}</td>
                        <td>${victStr}</td>
                        <td>${respStr}</td>
                        <td>${witnStr}</td>
                        <td>${incType}</td>
                        <td>${incDate}</td>
                    </tr>
                `;
            });

            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).map(s => s.outerHTML).join('\n');
            const printFooter = document.querySelector('.print-footer') ? document.querySelector('.print-footer').cloneNode(true) : null;

            let finalTitle = "Blotter Records Masterlist";
            const printHeader = document.querySelector('.print-header');
            if (printHeader) {
                const countBadge = printHeader.querySelector('#printTotalRecords');
                if (countBadge) countBadge.textContent = rowsToPrint.length;
                
                const printTitle = printHeader.querySelector('.print-list-title');
                if (printTitle) {
                    const activeFilters = [];
                    
                    const activeTab = document.querySelector('.tab-btn.active');
                    if (activeTab && activeTab.getAttribute('data-filter') !== 'all') {
                        activeFilters.push("Status: " + activeTab.textContent.trim());
                    }

                    const searchInput = document.getElementById('searchInput');
                    if (searchInput && searchInput.value.trim()) {
                        activeFilters.push(`Search: "${searchInput.value.trim()}"`);
                    }
                    if (activeFilters.length > 0) {
                        finalTitle += " - " + activeFilters.join(', ');
                    }
                }
            }

            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
                    <title>Blotter Records Masterlist</title>
                    ${styles}
                    <style>
                        body { background: white !important; color: black !important; padding: 20px !important; }
                        .main-content, .dashboard-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                        .print-only { display: flex !important; }
                        .data-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
                        .data-table th, .data-table td { border: 1px solid #333 !important; padding: 6px !important; font-size: 9px !important; text-align: left; }
                        .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                        .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                        .header-center { flex: 1; }
                        .header-center p { margin: 2px 0; font-size: 14px; }
                        .header-center .brgy-name { font-weight: bold; font-size: 16px; margin-top: 5px; }
                        .logo-img { width: 80px; height: 80px; object-fit: contain; }
                        .logo-placeholder-box { width: 80px; height: 80px; }
                        @page { size: A4 landscape; margin: 15mm; }
                    </style>
                </head>
                <body>
                    <div class="dashboard-content">
                        <div class="cert-header">
                            ${brgyLogoHtml}
                            <div class="header-center">
                                <p>Republic of the Philippines</p>
                                <p>Province of ${brgyInfo.province_name || 'Province'}</p>
                                <p>Municipality of ${brgyInfo.town_name || 'Municipality'}</p>
                                <p class="brgy-name">${(brgyInfo.barangay_name || 'Barangay').toUpperCase()}</p>
                            </div>
                            ${govLogoHtml}
                        </div>
                        <div style="text-align: center; margin: 15px 0;">
                            <h3 style="margin: 0; text-transform: uppercase;">${finalTitle}</h3>
                            <p style="margin: 5px 0 0 0; font-size: 12px;">Total Records: ${rowsToPrint.length}</p>
                        </div>
                        <table class="data-table">
                            ${tableHeaderHtml}
                            <tbody>${rowsHtml}</tbody>
                        </table>
                        ${printFooter ? printFooter.outerHTML : ''}
                    </div>
                </body>
                </html>
            `);
            doc.close();

            setTimeout(() => {
                fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            }, 500);
        });
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
            if (window.BIS_PERMS && window.BIS_PERMS.blotter_create === false) {
                alert('Permission denied to create blotter records.');
                return;
            }
            
            // RESET MODAL ON OPEN
            const form = document.getElementById('createRecordForm');
            if (form) form.reset();
            
            // Clear all party containers
            clearPartyContainers();

            // Clear attachments
            selectedFiles = [];
            updateUI();
            
            // Reset step to 0
            currentStep = 0;
            updateStepIndicator();
            updateFooterButtons();
            
            // NEW: Initialize Case Outcome handler
            // initCaseOutcomeHandler moved to status modal
            
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
    const steps = ['step-1-basic', 'step-2-parties', 'step-3-narrative', 'step-4-final'];
    const stepItems = document.querySelectorAll('.step-item');
    const tabPanes = document.querySelectorAll('.tab-pane');
    const stepLines = document.querySelectorAll('.step-line');
    
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
        
        // Update step lines
        stepLines.forEach((line, index) => {
            if (index < currentStep) {
                line.classList.add('completed');
            } else {
                line.classList.remove('completed');
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
    
    // CLEAR PARTY CONTAINERS FUNCTION
    function clearPartyContainers() {
        if (complainantsContainer) complainantsContainer.innerHTML = '';
        if (victimsContainer) victimsContainer.innerHTML = '';
        if (respondentsContainer) respondentsContainer.innerHTML = '';
        if (witnessesContainer) witnessesContainer.innerHTML = '';
        
        // Add initial required entries
        if (typeof addComplainantEntry === 'function') addComplainantEntry();
        if (typeof addVictimEntry === 'function') addVictimEntry();
        if (typeof addRespondentEntry === 'function') addRespondentEntry();
    }

    // ============================================
    // Incident Proof Attachment Handling
    // ============================================
    if (uploadZone && fileInput) {
        uploadZone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });

        uploadZone.addEventListener('dragover', () => uploadZone.classList.add('dragover'));
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
        uploadZone.addEventListener('drop', (e) => {
            uploadZone.classList.remove('dragover');
            addFiles(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', e => addFiles(e.target.files));
    }

    function addFiles(files) {
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                selectedFiles.push(file);
            }
        });
        updateUI();
    }

    function updateUI() {
        if (!fileInput || !uploadZone) return;

        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;

        const zoneContent = uploadZone.querySelector('.upload-zone-content');
        previewContainer.innerHTML = '';

        if (selectedFiles.length > 0) {
            uploadZone.classList.add('is-compact');
            zoneContent.innerHTML = `<i class="fas fa-plus text-primary mb-1"></i><p class="mb-0 text-[10px] fw-bold">Add More</p>`;
            
            selectedFiles.forEach((file, index) => {
                const item = document.createElement('div');
                item.className = 'attachment-preview-item';
                const imgUrl = URL.createObjectURL(file);
                item.innerHTML = `<img src="${imgUrl}"><button type="button" class="remove-btn"><i class="fas fa-times"></i></button>`;
                item.querySelector('.remove-btn').onclick = (e) => {
                    e.stopPropagation();
                    selectedFiles.splice(index, 1);
                    updateUI();
                };
                previewContainer.appendChild(item);
            });
        } else {
            uploadZone.classList.remove('is-compact');
            zoneContent.innerHTML = `
                <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                <p class="mb-1"><strong>Drag and drop images</strong> here or <span class="text-primary">click to browse</span></p>
                <p class="text-muted small">Supports JPG and PNG (Max 5MB each)</p>`;
        }
    }

    // NEW: Case Outcome Dropdown Handler
    function initCaseOutcomeHandler() {
        const caseOutcomeSelect = document.getElementById('caseOutcomeSelect');
        const mediationDiv = document.getElementById('mediationScheduleDiv');
        const cfaDiv = document.getElementById('cfaReferralDiv');
        
        if (!caseOutcomeSelect || !mediationDiv || !cfaDiv) return;
        
        caseOutcomeSelect.addEventListener('change', function() {
            const value = this.value;
            
            // Hide all conditionals first
            mediationDiv.style.display = 'none';
            mediationDiv.style.opacity = '0';
            mediationDiv.style.maxHeight = '0';
            cfaDiv.style.display = 'none';
            cfaDiv.style.opacity = '0';
            cfaDiv.style.maxHeight = '0';
            
            // Clear any values
            const mediationInput = mediationDiv.querySelector('input[name="mediation_schedule"]');
            if (mediationInput) mediationInput.value = '';
            
            // Show specific conditional with smooth animation
            setTimeout(() => {
                if (value === 'Scheduled for Mediation') {
                    mediationDiv.style.display = 'block';
                    setTimeout(() => {
                        mediationDiv.style.opacity = '1';
                        mediationDiv.style.maxHeight = '500px';
                    }, 10);
                } else if (value === 'Referred to Police/Court (CFA)') {
                    cfaDiv.style.display = 'block';
                    setTimeout(() => {
                        cfaDiv.style.opacity = '1';
                        cfaDiv.style.maxHeight = '500px';
                    }, 10);
                }
            }, 100);
            
            // Trigger validation if needed
            this.reportValidity();
        });
        
        // CFA Generate button (placeholder)
        const cfaBtn = cfaDiv?.querySelector('.btn-warning');
        if (cfaBtn) {
            cfaBtn.addEventListener('click', function() {
                alert('CFA Document generation feature coming soon!');
            });
        }
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
        let victimCount = 0;
        
        window.addVictimEntry = function() {
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
                        <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="victim_address[]">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                    +63
                                </span>
                                <input type="text" class="form-control phone-input" name="victim_contact[]" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            victimsContainer.insertAdjacentHTML('beforeend', entryHtml);
        };

        addVictimBtn.addEventListener('click', addVictimEntry);
        
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
        let complainantCount = 0;
        
        window.addComplainantEntry = function() {
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
                        <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="complainant_address[]">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                    +63
                                </span>
                                <input type="text" class="form-control phone-input" name="complainant_contact[]" placeholder="9XX XXX XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            complainantsContainer.insertAdjacentHTML('beforeend', entryHtml);
        };

        addComplainantBtn.addEventListener('click', addComplainantEntry);
        
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
                    
                    clearPartyContainers();

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
    
    // Function to re-initialize dropdowns (useful if switching to Bootstrap JS)
    window.reinitActionMenus = function() {
        if (currentOpenMenu) {
            closeMenu(currentOpenMenu);
        }
        // Cleanup any orphaned menus in body
        document.querySelectorAll('body > .action-menu').forEach(m => m.remove());
    };

    // Store reference to the currently open menu
    let currentOpenMenu = null;

    document.addEventListener('click', function(e) {
        // Toggle action menu
        const actionBtn = e.target.closest('.btn-action');
        if (actionBtn) {
            e.stopPropagation();
            e.preventDefault();
            
            const container = actionBtn.closest('.action-menu-container');
            const recordId = actionBtn.getAttribute('data-record-id');
            
            // Find the menu - it might be in the container or already moved to body
            let menu = container.querySelector('.action-menu');
            if (!menu) {
                menu = document.querySelector(`body > .action-menu[data-record-id="${recordId}"]`);
            }
            
            if (!menu) return;

            // Toggle logic: if clicking the same menu, close it and stop
            if (currentOpenMenu) {
                const isSame = currentOpenMenu === menu;
                closeMenu(currentOpenMenu);
                if (isSame) return;
            }
            
            // Position the menu relative to the button
            const rect = actionBtn.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;

            // Temporarily show menu to measure dimensions
            const originalDisplay = menu.style.display;
            menu.style.display = 'block';
            
            const menuHeight = menu.offsetHeight;
            const menuWidth = menu.offsetWidth || 200;
            
            // Restore original display (hidden)
            menu.style.display = originalDisplay;
            
            // Set fixed positioning as requested
            menu.style.position = 'fixed';

            // Calculate vertical position: check if it fits below, otherwise show above
            let topPosition;
            if (rect.bottom + menuHeight + 5 > windowHeight) {
                topPosition = rect.top - menuHeight - 5;
            } else {
                topPosition = rect.bottom + 5;
            }
            
            // Ensure menu doesn't go above the viewport
            if (topPosition < 5) {
                topPosition = 5;
            }
            
            menu.style.top = topPosition + 'px';
            
            // Calculate horizontal position: align left edge of menu with left edge of button
            // but ensure it doesn't go off the right edge of the screen
            let leftPosition = rect.left;
            
            // If menu would go off the right edge, align to right edge of viewport
            if (leftPosition + menuWidth > windowWidth - 10) {
                leftPosition = windowWidth - menuWidth - 10;
            }
            
            // Ensure menu doesn't go off the left edge
            if (leftPosition < 5) {
                leftPosition = 5;
            }
            
            menu.style.left = leftPosition + 'px';
            
            menu.classList.add('show');
            
            // Move to body to escape stacking contexts (like tr transform)
            document.body.appendChild(menu);
            
            // Store reference to current open menu
            currentOpenMenu = menu;
            
            return;
        }
        
        // Handle action menu item clicks
        const menuItem = e.target.closest('.action-menu-item');
        if (menuItem) {
            const action = menuItem.getAttribute('data-action');
            const menu = menuItem.closest('.action-menu');
            const recordId = menu ? menu.getAttribute('data-record-id') : null;
            
            // Don't close menu if it's the status item (has submenu)
            if (menuItem.classList.contains('has-submenu')) {
                e.stopPropagation();
                menuItem.classList.toggle('show-submenu');
                return;
            }
            
            // Handle different actions
            if (recordId) {
                handleAction(action, recordId);
            } else {
                console.error('Action clicked but record ID is missing');
            }
            
            // Close menu
            closeMenu(menu);
            return;
        }
        
        // Close menu when clicking outside
        if (!e.target.closest('.action-menu')) {
            if (currentOpenMenu) {
                closeMenu(currentOpenMenu);
            }
        }
    });
    
    // Function to close the menu
    function closeMenu(menu) {
        if (!menu) return;
        
        menu.classList.remove('show');
        
        // Clear submenu states
        menu.querySelectorAll('.action-menu-item').forEach(item => {
            item.classList.remove('show-submenu');
        });
        
        // Reset positioning styles
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        
        // Move back to its original container if possible
        const recordId = menu.getAttribute('data-record-id');
        const actionBtn = document.querySelector(`.btn-action[data-record-id="${recordId}"]`);
        if (actionBtn) {
            const container = actionBtn.closest('.action-menu-container');
            if (container && !container.contains(menu)) {
                container.appendChild(menu);
            }
        } else {
            // If button no longer exists (table updated), just remove from body
            if (menu.parentNode === document.body) {
                menu.remove();
            }
        }
        
        // Reset current open menu reference
        if (currentOpenMenu === menu) {
            currentOpenMenu = null;
        }
    }
    
    // Handle action menu actions
function handleAction(action, recordId) {
        console.log('Action:', action, 'Record ID:', recordId);
        
        switch(action) {
            case 'view':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_view === false) {
                    alert('Permission denied to view blotter details.');
                    return;
                }
                viewBlotterDetails(recordId);
                break;
                
            case 'print-summons':
                window.open(`generate_document.php?id=${recordId}&type=summons`, '_blank');
                break;
            case 'print-notice':
                window.open(`generate_document.php?id=${recordId}&type=notice`, '_blank');
                break;
            case 'print-subpoena':
                if (typeof window.issueSubpoena === 'function') {
                    window.issueSubpoena(recordId);
                }
                break;

            case 'edit':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_edit === false) {
                    alert('Permission denied to edit blotter records.');
                    return;
                }
                if (typeof window.openEditBlotterModal === 'function') {
                    window.openEditBlotterModal(recordId);
                }
                break;

            case 'print':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_print === false) {
                    alert('Permission denied to print blotter records.');
                    return;
                }
                printBlotterDetails(recordId);
                break;

            case 'print-cfa':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_print === false) {
                    alert('Permission denied to print blotter records.');
                    return;
                }
                window.location.href = 'print_cfa.php?id=' + recordId;
                break;
                
            case 'update-status':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update case status.');
                    return;
                }
                openUpdateStatusModal(recordId);
                break;
                
            case 'status-pending':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Pending');
                break;
                
            case 'status-investigation':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Under Investigation');
                break;
                
            case 'status-mediation':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Scheduled for Mediation');
                break;

            case 'status-settled':
            case 'status-resolved': // Support legacy naming if any
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Settled');
                break;
                
            case 'status-dismissed':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Dismissed');
                break;

            case 'status-endorsed':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_status === false) {
                    alert('Permission denied to update blotter status.');
                    return;
                }
                updateBlotterStatus(recordId, 'Endorsed to Police');
                break;
                
            case 'archive':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_archive === false) {
                    alert('Permission denied to archive blotter records.');
                    return;
                }
                archiveBlotterRecord(recordId);
                break;
                
            case 'delete':
                if (window.BIS_PERMS && window.BIS_PERMS.blotter_archive === false) {
                    alert('Permission denied to archive blotter records.');
                    return;
                }
                deleteBlotterRecord(recordId);
                break;
                
            default:
                console.warn('Unknown action:', action);
        }
    }
    // Print individual blotter details
    function printBlotterDetails(recordId) {
        // This function remains in blotter.js as it's specific to printing.
        // The implementation is not provided in the prompt, so no changes here.
        console.log('Print blotter details:', recordId); // Placeholder
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
        
        // Set hidden record ID first
        const recordIdEl = document.getElementById('view_record_id');
        if (recordIdEl) recordIdEl.value = record.id || '';

        // Update Modern Header elements
        const headerRecNo = document.getElementById('view_header_record_number');
        if (headerRecNo) headerRecNo.textContent = record.record_number;

        const dateCreated = document.getElementById('view_date_created');
        if (dateCreated) {
            dateCreated.textContent = record.date_reported 
                ? new Date(record.date_reported).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) 
                : 'N/A';
        }

        // Basic Info
        const statusBadgeContainer = document.getElementById('view_status_badge_container');
        if (statusBadgeContainer) {
            const statusMap = {
                'Pending': 'bg-amber-100 text-amber-700 border-amber-200',
                'Scheduled for Mediation': 'bg-blue-100 text-blue-700 border-blue-200',
                'Settled': 'bg-green-100 text-green-700 border-green-200',
                'Resolved': 'bg-green-100 text-green-700 border-green-200',
                'Endorsed to Police': 'bg-orange-100 text-orange-700 border-orange-200',
                'Dismissed': 'bg-gray-100 text-gray-700 border-gray-200',
                'Under Investigation': 'bg-purple-100 text-purple-700 border-purple-200'
            };
            const badgeClass = statusMap[record.status] || 'bg-gray-100 text-gray-700 border-gray-200';
            statusBadgeContainer.innerHTML = `
                <span class="px-3 py-1 rounded-full text-xs font-bold border ${badgeClass}">
                    ${record.status.toUpperCase()}
                </span>`;
        }
        
        const dateEl = document.getElementById('view_incident_date');
        if (dateEl) dateEl.value = formatDateTime(record.incident_date);
        
        // Incident Details
        const typeEl = document.getElementById('view_incident_type');
        if (typeEl) typeEl.value = record.incident_type;
        
        const locEl = document.getElementById('view_incident_location');
        if (locEl) locEl.value = record.incident_location;
        
        const descEl = document.getElementById('view_incident_description');
        if (descEl) descEl.value = record.incident_description;
        
        // Modern Narrative Box
        const narrativeBox = document.getElementById('view_incident_description_text');
        if (narrativeBox) narrativeBox.textContent = record.incident_description || 'No details provided.';

        const resTextEl = document.getElementById('view_resolution');
        if (resTextEl) resTextEl.value = record.resolution || '';

        // Handle Print CFA Button visibility in View Modal
        const btnPrintCFA = document.getElementById('btnPrintCFA');
        if (btnPrintCFA) {
            btnPrintCFA.style.display = (record.status === 'Endorsed to Police') ? 'inline-flex' : 'none';
        }

        // Handle Mediation and Referral Display
        const mediationField = document.getElementById('view_mediation_field');
        const mediationDateInput = document.getElementById('view_mediation_date');
        const referralNotice = document.getElementById('view_referral_notice');

        if (mediationField) {
            if (record.status === 'Scheduled for Mediation') {
                mediationField.classList.remove('hidden');
                const mediationVal = record.mediation_schedule;
                if (mediationDateInput) mediationDateInput.textContent = mediationVal ? formatDateTime(mediationVal) : 'No schedule set';
            } else {
                mediationField.style.display = 'none';
            }
        }

        if (referralNotice) {
            referralNotice.style.display = (record.status === 'Referred to Police/Court (CFA)') ? 'block' : 'none';
        }

        // Populate Containers
        populateViewParties('viewComplainantsContainer', data.complainants);
        populateViewParties('viewVictimsContainer', data.victims);
        populateViewParties('viewRespondentsContainer', data.respondents);
        populateViewParties('viewWitnessesContainer', data.witnesses);
        
        // Fetch and Render Timeline
        loadBlotterHistory(record.id);

        // Handle Proof Galleries
        renderProofGallery('view_incident_proof_container', record.incident_proof, 'No incident evidence uploaded.');
        
        const settlementWrapper = document.getElementById('view_settlement_proof_wrapper');
        if (settlementWrapper) {
            if (record.status === 'Settled' || record.status === 'Resolved') {
                settlementWrapper.style.display = 'block';
                renderProofGallery('view_settlement_proof_container', record.settlement_proof, 'No settlement documents uploaded.');
            } else {
                settlementWrapper.style.display = 'none';
            }
        }

        populateViewActions('viewActionsContainer', data.actions);
        
        console.log('View modal populated for record:', record.id);
    }

    /**
     * Fetch and render the vertical timeline for case history
     */
    window.loadBlotterHistory = function(blotterId, containerId = 'case-history-timeline') {
        const timelineContainer = document.getElementById(containerId);
        if (!timelineContainer) return;

        console.log('Fetching history for ID:', blotterId);
        timelineContainer.innerHTML = '<div class="text-xs text-gray-400 italic">Loading history...</div>';

        const formatValue = (val, type) => {
            if (!val || val === '0000-00-00' || val === '0000-00-00 00:00:00' || val.includes('-0001')) {
                return type === 'Party Added' ? 'New Entry' : 'N/A';
            }
            // Specific check for mediation strings that might contain invalid dates
            if (val.includes('Sched: N/A') || val.includes('Sched: -0001')) return val.split('|')[0] + '| No Schedule';
            return val;
        };

        fetch(`model/get_blotter_history.php?id=${blotterId}`)
            .then(res => res.json())
            .then(data => {
                console.log('API Response:', data);

                // Clear existing text/loading state
                timelineContainer.innerHTML = '';
                timelineContainer.classList.add('timeline-container');

                // Handle empty history or failure gracefully
                if (!data.success || !data.data || data.data.length === 0) {
                    timelineContainer.innerHTML = '<div class="text-xs text-gray-400 italic">No history recorded for this case.</div>';
                    return;
                }

                // Logic: Hide the static "No actions recorded" box if we have dynamic history
                const actionsBox = document.getElementById('viewActionsContainer');
                if (actionsBox && data.data.length > 0) {
                    actionsBox.classList.add('hidden');
                }

                data.data.forEach(item => {
                    // Format main timestamp: April 26, 2026 • 06:12 PM (Requirement)
                    const dateObj = new Date(item.created_at);
                    const formattedDate = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) + 
                                          ' • ' + 
                                          dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                    
                    // Determine Dot Color (Priority: Red for Endorsement, else Blue)
                    let dotColor = 'bg-blue-500';
                    if (item.changes.some(c => c.new_value && c.new_value.includes('Endorsed to Police'))) {
                        dotColor = 'bg-red-600';
                    }

                    let changesHtml = '';
                    item.changes.forEach((change, idx) => {
                        // Action Icon Mapping (Strict Requirement)
                        let badgeClass = 'bg-secondary';
                        let actionIcon = '<i class="fas fa-info-circle me-1"></i>';

                        if (change.action_type === 'Rescheduled') {
                            badgeClass = 'bg-warning text-dark';
                            actionIcon = '<i class="fas fa-clock-rotate-left me-1"></i>';
                        } else if (change.action_type === 'Status Updated' || change.action_type === 'Status & Schedule Updated') {
                            badgeClass = 'bg-blue-600';
                            actionIcon = '<i class="fas fa-scale-balanced me-1"></i>'; // ⚖️ Status
                        } else if (change.action_type === 'Narrative Modified') {
                            badgeClass = 'bg-cyan-600';
                            actionIcon = '<i class="fas fa-file-lines me-1"></i>'; // 📝 Narrative
                        } else if (change.action_type === 'Location Updated') {
                            badgeClass = 'bg-slate-500';
                            actionIcon = '<i class="fas fa-location-dot me-1"></i>'; // 📍 Location
                        } else if (change.action_type === 'Schedule Adjusted') {
                            badgeClass = 'bg-teal-500';
                            actionIcon = '<i class="fas fa-calendar-days me-1"></i>';
                        } else if (change.action_type === 'Party Added') {
                            badgeClass = 'bg-success';
                            actionIcon = '<i class="fas fa-user-plus me-1"></i>';
                        }

                        // Red Badge for Endorsed (Requirement)
                        if (change.new_value && change.new_value.toLowerCase().includes('endorsed to police')) {
                            actionIcon = '<i class="fas fa-shield-halved me-1"></i>';
                            badgeClass = 'bg-danger';
                        }

                        // Hide schedule metadata for non-mediation statuses
                        let displayNewValue = change.new_value;
                        if (displayNewValue && !displayNewValue.includes('Scheduled for Mediation')) {
                            displayNewValue = displayNewValue.split(' | Sched:')[0];
                        }

                        const oldVal = formatValue(change.old_value, change.action_type);
                        const newVal = formatValue(displayNewValue, change.action_type);

                        // Add subtle horizontal divider between multiple changes (Requirement)
                        if (idx > 0) {
                            changesHtml += `<hr class="my-4 border-gray-200 opacity-50">`;
                        }

                        changesHtml += `
                            <div class="change-item">
                                <div class="mb-3">
                                    <span class="badge ${badgeClass} text-[10px] uppercase font-bold py-1 px-2 rounded-md">${actionIcon} ${change.action_type}</span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                                    <div class="audit-val-box bg-red-50 border-l-4 border-red-400">
                                        <strong class="text-red-700 block text-[9px] uppercase font-bold tracking-wider mb-1">From</strong>
                                        <span class="text-gray-700">${oldVal}</span>
                                    </div>
                                    <div class="audit-val-box bg-green-50 border-l-4 border-green-400">
                                        <strong class="text-green-700 block text-[9px] uppercase font-bold tracking-wider mb-1">To</strong>
                                        <span class="text-gray-700 font-medium">${newVal}</span>
                                    </div>
                                </div>

                                ${change.remarks ? `
                                <div class="remarks-ribbon mb-2">
                                    <i class="fas fa-quote-left mr-2 opacity-30"></i> ${change.remarks}
                                </div>` : ''}
                            </div>`;
                    });

                    const timelineItem = `
                        <div class="relative flex flex-col group mb-4 last:mb-0">
                            <div class="absolute -left-[24px] top-3 w-4 h-4 rounded-full border-4 border-white ${dotColor} shadow-sm z-10"></div>
                            <div class="timeline-card">
                                <!-- Card Header (Shows once per group) -->
                                <div class="flex justify-between items-center p-3 bg-gray-50/50 border-b border-gray-100 rounded-t-lg">
                                    <span class="text-[11px] font-bold text-gray-500 flex items-center gap-1 uppercase tracking-tight">
                                        <i class="far fa-clock"></i> ${formattedDate}
                                    </span>
                                    <div class="flex items-center gap-1.5 text-[10px] text-gray-400 font-medium">
                                        <i class="fas fa-user-tie"></i> 
                                        PROCESSED BY: <span class="text-gray-600 font-bold">${(item.officer_name || 'System').toUpperCase()}</span>
                                    </div>
                                </div>
                                
                                <!-- Card Body (Lists all actions in the group) -->
                                <div class="p-4">
                                    ${changesHtml}
                                </div>
                            </div>
                        </div>`;
                    
                    timelineContainer.insertAdjacentHTML('beforeend', timelineItem);
                });
            })
            .catch(err => {
                console.error('History Error:', err);
                timelineContainer.innerHTML = '<div class="text-xs text-red-500 font-medium italic">Error loading history</div>';
            });
    }
    
    function renderProofGallery(containerId, proofString, emptyMessage) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        if (!proofString || proofString.trim() === '') {
            container.innerHTML = `<p class="text-xs text-gray-400 italic col-span-full py-2">${emptyMessage}</p>`;
            return;
        }

        const paths = proofString.split(',');
        paths.forEach(path => {
            container.innerHTML += `
                <a href="${path}" target="_blank" class="block relative group aspect-square overflow-hidden rounded-lg border bg-white shadow-sm hover:shadow-lg transition-all">
                    <img src="${path}" class="w-full h-full object-cover group-hover:scale-125 transition-transform duration-500">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-[10px] font-bold">
                        <i class="fas fa-search-plus mr-1"></i> VIEW FULL
                    </div>
                </a>`;
        });
    }

    function populateViewParties(containerId, parties) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!parties || parties.length === 0) {
            container.innerHTML = '<div class="py-2 text-sm text-gray-500 italic flex items-center gap-2"><i class="fas fa-user-slash text-gray-400 w-4"></i>No records found.</div>';
            return;
        }
        
        parties.forEach((party, index) => {
            // Generate initials for avatar
            const initials = party.name ? party.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '?';
            
            // Determine role-based CSS class based on the container ID
            let roleClass = 'avatar-witness';
            if (containerId.includes('Complainants')) {
                roleClass = 'avatar-complainant';
            } else if (containerId.includes('Victims')) {
                roleClass = 'avatar-victim';
            } else if (containerId.includes('Respondents')) {
                roleClass = 'avatar-respondent';
            }

            const iconClass = containerId.includes('Complainants') ? 'fa-user text-blue-500' :
                             containerId.includes('Victims') ? 'fa-user-injured text-red-500' :
                             containerId.includes('Respondents') ? 'fa-user-shield text-orange-500' :
                             'fa-eye text-green-500';
            const html = `
                <div class="blotter-party-card">
                    <div class="blotter-party-avatar ${roleClass}">
                        ${initials}
                    </div>
                    <div class="blotter-party-details">
                        ${party.resident_id 
                            ? `<div class="blotter-party-name"><a href="resident_profile.php?id=${party.resident_id}" class="text-blue-600 hover:text-blue-800 hover:underline font-medium transition-colors">${party.name}</a></div>`
                            : `<div class="blotter-party-name"><span class="font-medium text-gray-900">${party.name}</span></div>`
                        }
                        <div class="blotter-party-contact">
                            <i class="fas fa-phone-alt"></i> <span>${party.contact_number || 'N/A'}</span>
                        </div>
                        <div class="blotter-party-address">
                            <i class="fas fa-map-marker-alt"></i> <span>${party.address || 'N/A'}</span>
                        </div>
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
            container.innerHTML = '<li class="py-3 px-4 bg-gray-50 rounded-lg text-sm text-gray-500 italic border flex items-center gap-2"><i class="fas fa-clipboard-list text-gray-400"></i>No actions recorded.</li>';
            return;
        }
        
        actions.forEach((action, index) => {
            const officer = action.officer || 'Officer';
            const html = `
                <li class="py-3 px-4 border rounded-lg hover:bg-gray-50 transition-colors group">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs text-blue-600 font-semibold bg-blue-50 px-2 py-1 rounded-full">
                            <i class="far fa-calendar-alt mr-1"></i> ${action.date}
                        </span>
                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-full font-medium">${officer}</span>
                    </div>
                    <p class="text-sm text-gray-900 leading-relaxed">${action.details}</p>
                </li>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    function formatDateTime(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00') return 'N/A';
        const dateObj = new Date(dateString);
        if (isNaN(dateObj.getTime()) || dateObj.getTime() === 0) return 'N/A';
        return dateObj.toLocaleString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric', 
            hour: '2-digit', minute: '2-digit' 
        });
    }
    
    // Edit blotter record

    
    /**
     * Update blotter status.
     * Enforces schedule requirement for 'Scheduled for Mediation'.
     */
    function updateBlotterStatus(recordId, newStatus, schedule = null) {
        // Enforce schedule for mediation
        if (newStatus === 'Scheduled for Mediation' && !schedule) {
            const input = prompt("Please enter the mediation schedule\n(Format: YYYY-MM-DD HH:MM)");
            
            if (input === null) return; // User clicked Cancel
            
            if (input.trim() === '') {
                alert("Mediation schedule is required. Status update cancelled.");
                return;
            }
            schedule = input.trim();
        }

        // NEW: Enforce remarks for endorsement to police
        let remarks = null;
        if (newStatus === 'Endorsed to Police') {
            remarks = prompt("Escalation Action: Please provide the reason why mediation failed and why this is being endorsed to the police.\n(This will be saved in the case history)");
            
            if (remarks === null) return; // User cancelled
            
            if (remarks.trim() === '') {
                alert("Remarks are required to endorse a case to the police.");
                return;
            }
            
            alert("Action Required: Case Escalated. Please ensure you prepare the Endorsement Letter / Certificate to File Action (CFA) documents.");
        }

        if (!confirm(`Change status to "${newStatus}"?`)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('id', recordId);
        formData.append('status', newStatus);
        
        if (schedule) {
            formData.append('mediation_schedule', schedule);
        }

        if (remarks) {
            formData.append('remarks', remarks.trim());
        }
        
        // Send AJAX request to update status
        fetch('model/update_blotter_status.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON from server:', text);
                throw new Error('Server returned invalid response');
            }
        })
        .then(data => {
            if (data.success) {
                showNotification('Status updated successfully!', 'success');
                location.reload();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error updating status:', error);
            alert('An error occurred while updating the status. Check console for details.\n\n' + error.message);
        });
    }
    
    /**
     * Locking Logic for Edit Modal
     * Disables form elements if the status is terminal (Settled/Endorsed)
     */
    window.applyBlotterLockingLogic = function(status, modalId = 'editRecordModal', mediationCount = 0) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const isStrikeLimitReached = (parseInt(mediationCount) >= 3);
        const isLocked = (status === 'Settled' || status === 'Resolved' || status === 'Endorsed to Police');
        const inputs = modal.querySelectorAll('input, textarea, select');
        const strikeMsg = modal.querySelector('#edit_strike_msg');
        const mediationInput = modal.querySelector('input[name="mediation_schedule"]');
        const saveBtn = modal.querySelector('#updateRecordBtn') || modal.querySelector('[data-action="save"]');
        
        // Print CFA Button Visibility Logic for Edit Modal
        const btnPrintCFA = modal.querySelector('#btnPrintCFA');
        if (btnPrintCFA) {
            btnPrintCFA.style.display = (status === 'Endorsed to Police') ? 'inline-flex' : 'none';
        }

        // Add event listener to Status dropdown if not already present
        const statusSelect = modal.querySelector('#edit_case_status') || modal.querySelector('#edit_status');
        if (statusSelect && !statusSelect.dataset.cfaListenerSet) {
            statusSelect.addEventListener('change', function() {
                const currentStatus = this.value;
                if (btnPrintCFA) {
                    btnPrintCFA.style.display = (currentStatus === 'Endorsed to Police') ? 'inline-flex' : 'none';
                }
            });
            statusSelect.dataset.cfaListenerSet = 'true';
        }

        // Check for documentation alert area
        let alertBox = modal.querySelector('.escalation-alert');
        
        if (isLocked) {
            inputs.forEach(input => {
                if (input.id !== 'edit_status') { // Allow status to be changed back if needed by authorized users
                    input.setAttribute('disabled', 'disabled');
                    input.classList.add('bg-gray-50', 'cursor-not-allowed');
                }
            });
            if (saveBtn) saveBtn.style.display = 'none';
            
            // Show specialized alert if Endorsed
            if (status === 'Endorsed to Police') {
                if (!alertBox) {
                    alertBox = document.createElement('div');
                    alertBox.className = 'escalation-alert alert alert-warning mt-3 flex items-center gap-3';
                    alertBox.innerHTML = `
                        <i class="fas fa-file-export fa-lg"></i>
                        <div>
                            <strong>Escalated Case:</strong> This record is locked. 
                            <button type="button" class="btn btn-sm btn-dark ms-2" onclick="alert('Generating Endorsement Letter...')">Prepare Endorsement Letter / CFA</button>
                        </div>`;
                    modal.querySelector('.modal-body').prepend(alertBox);
                }
            }
        } else {
            inputs.forEach(input => {
                input.removeAttribute('disabled');
                input.classList.remove('bg-gray-50', 'cursor-not-allowed', 'bg-light');
            });
            if (saveBtn) saveBtn.style.display = 'inline-block';
            if (alertBox) alertBox.remove();

            // Remove hidden status input if it exists and we are no longer limited
            const hiddenStatus = modal.querySelector('input[type="hidden"][name="status"]');
            if (hiddenStatus) hiddenStatus.remove();

            // Hide strike message
            if (strikeMsg) strikeMsg.style.display = 'none';
        }

        // Strictly disable mediation inputs when limit is reached (Requirement)
        if (isStrikeLimitReached) {
            if (statusSelect) {
                // Re-enable dropdown but disable specific mediation option
                statusSelect.removeAttribute('disabled');
                const mediationOption = statusSelect.querySelector('option[value="Scheduled for Mediation"]');
                if (mediationOption) mediationOption.disabled = true;
                
                // Ensure if current status IS mediation, we provide the hidden input
                if (status === 'Scheduled for Mediation') {
                    let hiddenStatus = modal.querySelector('input[type="hidden"][name="status"]');
                    if (!hiddenStatus) {
                        hiddenStatus = document.createElement('input');
                        hiddenStatus.type = 'hidden';
                        hiddenStatus.name = 'status';
                        statusSelect.parentNode.appendChild(hiddenStatus);
                    }
                    hiddenStatus.value = status;
                }
            }

            // Show strike message (Requirement)
            if (strikeMsg) {
                strikeMsg.textContent = 'STRICT LIMIT REACHED: 3/3 attempts used. Escalation required.';
                strikeMsg.style.display = 'block';
            }

            if (mediationInput) {
                mediationInput.setAttribute('disabled', 'disabled');
                mediationInput.setAttribute('readonly', 'readonly');
                mediationInput.classList.add('bg-light');
            }
        }
    };

    // Archive blotter record
    function archiveBlotterRecord(recordId) {
        const archiveModal = document.getElementById('archiveModal');
        const archiveRecordIdInput = document.getElementById('archiveRecordId');
        const archivePasswordInput = document.getElementById('archivePassword');
        const archiveReasonInput = document.getElementById('archiveReason');
        
        if (archiveModal && archiveRecordIdInput) {
            archiveRecordIdInput.value = recordId;
            if (archivePasswordInput) archivePasswordInput.value = '';
            if (archiveReasonInput) archiveReasonInput.value = '';
            
            archiveModal.style.display = 'block';
            if (archiveReasonInput) {
                archiveReasonInput.focus();
            }
        }
    }
    
    // Delete blotter record
    function deleteBlotterRecord(recordId) {
        archiveBlotterRecord(recordId);
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
                        <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="witness_address[]">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                    +63
                                </span>
                                <input type="text" class="form-control phone-input" name="witness_contact[]" placeholder="9XX XXX XXXX">
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
        let respondentCount = 0;
        
        // Override existing handler
        const newRespondentBtn = addRespondentBtn.cloneNode(true);
        addRespondentBtn.parentNode.replaceChild(newRespondentBtn, addRespondentBtn);
        
        window.addRespondentEntry = function() {
            respondentCount++;
            const entryHtml = `
                <div class="party-entry">
                    <div class="party-entry-header">
                        <span>Respondent ${respondentCount}</span>
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
                        <div class="mb-2">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="respondent_address[]">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Mobile Number</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <img src="assets/image/contactph.png" alt="PH" style="width: 20px;">
                                    +63
                                </span>
                                <input type="text" class="form-control phone-input" name="respondent_contact[]" placeholder="9XX XXX XXXX">
                        </div>
                    </div>
                </div>
            `;
            respondentsContainer.insertAdjacentHTML('beforeend', entryHtml);
        };

        newRespondentBtn.addEventListener('click', addRespondentEntry);
        
        // Remove respondent entry
        respondentsContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-party-btn')) {
                const entry = e.target.closest('.party-entry');
                if (respondentsContainer.querySelectorAll('.party-entry').length > 1) {
                    entry.remove();
                    // Renumber respondents
                    const entries = respondentsContainer.querySelectorAll('.party-entry');
                    entries.forEach((entry, index) => {
                        entry.querySelector('.party-entry-header span').textContent = `Respondent ${index + 1}`;
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
    
    // ============================================
    // Archive Modal Handlers
    // ============================================
    const archiveModal = document.getElementById('archiveModal');
    const archiveForm = document.getElementById('archiveForm');
    const cancelArchiveBtn = document.getElementById('cancelArchive');
    const toggleArchivePasswordBtn = document.getElementById('toggleArchivePassword');
    const archivePasswordInput = document.getElementById('archivePassword');
    
    if (cancelArchiveBtn) {
        cancelArchiveBtn.addEventListener('click', () => {
            if (archiveModal) archiveModal.style.display = 'none';
        });
    }
    
    if (toggleArchivePasswordBtn && archivePasswordInput) {
        toggleArchivePasswordBtn.addEventListener('click', () => {
            const type = archivePasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            archivePasswordInput.setAttribute('type', type);
            toggleArchivePasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    if (archiveForm) {
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmArchiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
            
            const formData = new FormData(this);
            
            fetch('model/archive_blotter_record.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Record archived successfully!', 'success');
                    archiveModal.style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message, 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        });
    }
    
    /**
     * Strictly validates mobile number inputs:
     * 1. Must start with 9
     * 2. Numbers only
     * 3. Max length of 10 digits
     */
    function formatMobileNumber(input) {
        // Remove non-numeric characters
        let value = input.value.replace(/\D/g, '');

        // Ensure it starts with 9
        if (value.length > 0 && value[0] !== '9') {
            input.classList.add('!border-red-500', 'ring-2', 'ring-red-200');
            value = ''; // Clear the input
            
            setTimeout(() => {
                input.classList.remove('!border-red-500', 'ring-2', 'ring-red-200');
            }, 600);
        }

        // Limit to 10 digits total
        input.value = value.substring(0, 10);
    }

    // Global event delegation for phone inputs (create/edit modals + dynamic)
    document.addEventListener('input', function(e) {
        if (e.target.matches('.phone-input')) {
            formatMobileNumber(e.target);
        }
    });

    // Handle paste for phone inputs
    document.addEventListener('paste', function(e) {
        if (e.target.matches('.phone-input')) {
            setTimeout(() => formatMobileNumber(e.target), 10);
        }
    });

    console.log('Blotter Records page initialized - Mobile validation active');
});

/**
 * Standardized notification helper with high z-index and role-based coloring
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : (type === 'error' || type === 'warning' ? 'exclamation-circle' : 'info-circle');
    const bgColor = type === 'success' ? '#10b981' : (type === 'error' || type === 'warning' ? '#ef4444' : '#3b82f6');

    notification.innerHTML = `<i class="fas fa-${icon}"></i><span>${message}</span>`;
    
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; background: ${bgColor};
        color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex; align-items: center; gap: 10px; z-index: 10000000; animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);

    // Inject animation if missing
    if (!document.getElementById('blotter-notification-styles')) {
        const style = document.createElement('style');
        style.id = 'blotter-notification-styles';
        style.textContent = `@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }`;
        document.head.appendChild(style);
    }
}
