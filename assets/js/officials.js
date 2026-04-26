/**
 * Barangay Officials Management
 * Handles CRUD operations, filtering, search, resident picker, and view modal
 */

// ── State ────────────────────────────────────────────────────────────────────
let officialsTable;
let currentStatusFilter = 'all';
let currentSearchTerm   = '';

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    console.log('Officials page loaded');
    initOfficials();
});

function initOfficials() {
    // Apply Permissions (Hide Actions)
    if (window.BIS_PERMS) {
        if (!window.BIS_PERMS.officials_create) {
            const createBtn = document.getElementById('createOfficialBtn');
            if (createBtn) createBtn.style.display = 'none';
        }
    }

    // Initialize EnhancedTable for pagination
    officialsTable = new EnhancedTable('officialsTable', {
        sortable: false,
        searchable: false,
        paginated: true,
        pageSize: 10,
        responsive: true
    });

    // Create Official Button
    const createBtn = document.getElementById('createOfficialBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateOfficialModal);
    }

    // Print Officials Button
    const printOfficialsBtn = document.getElementById('printOfficialsBtn');
    if (printOfficialsBtn) {
        printOfficialsBtn.addEventListener('click', handlePrintOfficials);
    }

    // Export CSV Button
    const exportCsvBtn = document.getElementById('exportCsvBtn');
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function() {
            // Copy the same method as Print Masterlist to fetch only filtered data from the table
            let rowsToExport = (officialsTable && officialsTable.filteredRows) 
                ? officialsTable.filteredRows 
                : Array.from(document.querySelectorAll('#officialsTableBody tr:not([style*="display: none"])'));

            const csvHeaders = ["Official Name", "Position", "Committee", "Term Period", "Status", "Type", "Contact"];
            let csvContent = csvHeaders.join(",") + "\n";

            rowsToExport.forEach(row => {
                if (row.id === 'officialsEmptyRow' || row.cells.length < 7) return;
                
                // Manually extract name and position from nested spans just like print function
                const name = row.querySelector('.official-info-name')?.textContent.trim() || '';
                const position = row.querySelector('.official-info-position')?.textContent.trim() || '';
                const committee = row.cells[1]?.textContent.trim() || 'N/A';
                const term = row.cells[2]?.textContent.trim() || '';
                const status = row.cells[3]?.querySelector('.badge')?.textContent.trim() || '';
                const type = row.cells[4]?.querySelector('.badge')?.textContent.trim() || '';
                const contact = row.cells[5]?.textContent.trim() || '';

                const rowData = [name, position, committee, term, status, type, contact].map(val => 
                    `"${val.replace(/"/g, '""')}"`
                );
                csvContent += rowData.join(",") + "\n";
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `Barangay_Officials_Masterlist_${new Date().toISOString().slice(0, 10)}.csv`;
            link.click();

            const logData = new FormData();
            logData.append('action', 'Export Masterlist');
            logData.append('description', 'Exported the barangay officials masterlist to CSV');
            fetch('model/log_print_masterlist.php', { method: 'POST', body: logData }).catch(e => console.error(e));
        });
    }

    // Create Official Submit
    const submitBtn = document.getElementById('createOfficialSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitCreateOfficial);
    }


    // Toggle "Other" input fields
    const chairmanshipSelect = document.getElementById('chairmanship');
    const positionSelect = document.getElementById('position');
    if (chairmanshipSelect) chairmanshipSelect.addEventListener('change', () => toggleOtherInput('chairmanship', 'otherChairmanshipGroup'));
    if (positionSelect) positionSelect.addEventListener('change', () => toggleOtherInput('position', 'otherPositionGroup'));

    // Auto-calculate Term End for Create Official (3 years jump)
    const termStartInput = document.getElementById('termStart');
    if (termStartInput) {
        termStartInput.addEventListener('change', function() {
            if (this.value) {
                const startDate = new Date(this.value);
                startDate.setFullYear(startDate.getFullYear() + 3);
                const termEndInput = document.getElementById('termEnd');
                if (termEndInput) termEndInput.value = startDate.toISOString().split('T')[0];
            }
        });
    }

    // Re-apply limits when status is manually changed
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.addEventListener('change', () => applySelectionLimits());
    }

    // Initialize action menus for the table
    initializeActionMenus();

    // Reset create form when modal closes
    const createModal = document.getElementById('createOfficialModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', resetCreateForm);
    }

    // Load residents when picker modal opens
    const pickerModal = document.getElementById('residentPickerModal');
    if (pickerModal) {
        pickerModal.addEventListener('shown.bs.modal', function () {
            const input = document.getElementById('pickerSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            searchResidentsForPicker('');
        });
    }

    // Organizational Chart double-click to view details
    document.querySelectorAll('.official-card').forEach(card => {
        card.addEventListener('dblclick', function() {
            const id = this.getAttribute('data-official-id');
            if (id) viewOfficialDetails(id);
        });
    });

    // Modal close listeners for URL cleanup
    const viewModal = document.getElementById('viewOfficialModal');
    if (viewModal) {
        viewModal.addEventListener('hidden.bs.modal', function () {
            const url = new URL(window.location);
            url.searchParams.delete('view');
            window.history.replaceState({}, '', url);
        });
    }

    const editModal = document.getElementById('editOfficialModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function () {
            const url = new URL(window.location);
            url.searchParams.delete('edit');
            window.history.replaceState({}, '', url);
        });
    }

    // Archive Modal handlers
    const archiveModal = document.getElementById('archiveModal');
    const cancelArchive = document.getElementById('cancelArchive');
    const archiveForm = document.getElementById('archiveForm');
    const toggleArchivePassword = document.getElementById('toggleArchivePassword');
    const archivePassword = document.getElementById('archivePassword');

    if (cancelArchive) {
        cancelArchive.addEventListener('click', () => {
            archiveModal.style.display = 'none';
        });
    }

    if (toggleArchivePassword && archivePassword) {
        toggleArchivePassword.addEventListener('click', () => {
            const type = archivePassword.getAttribute('type') === 'password' ? 'text' : 'password';
            archivePassword.setAttribute('type', type);
            toggleArchivePassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    if (archiveForm) {
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmArchiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
            
            fetch('model/delete_official.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Official moved to archive successfully', 'success');
                    archiveModal.style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Failed to archive official', 'danger');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while archiving the official', 'danger');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        });
    }

    // Inactive Modal handlers
    const inactiveModal = document.getElementById('inactiveModal');
    const cancelInactive = document.getElementById('cancelInactive');
    const inactiveForm = document.getElementById('inactiveForm');
    const toggleInactivePassword = document.getElementById('toggleInactivePassword');
    const inactivePassword = document.getElementById('inactivePassword');

    if (cancelInactive) {
        cancelInactive.addEventListener('click', () => {
            inactiveModal.style.display = 'none';
        });
    }

    if (toggleInactivePassword && inactivePassword) {
        toggleInactivePassword.addEventListener('click', () => {
            const type = inactivePassword.getAttribute('type') === 'password' ? 'text' : 'password';
            inactivePassword.setAttribute('type', type);
            toggleInactivePassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    if (inactiveForm) {
        inactiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmInactiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('model/update_official_status.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Status updated to Inactive successfully', 'success');
                    inactiveModal.style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Failed to update status', 'danger');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating the official status', 'danger');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        });
    }

    // Define the global function called by the action menu
    window.openInactiveModal = function(officialId, row, currentStatus, officialName) {
        if (inactiveModal) {
            document.getElementById('inactiveOfficialId').value = officialId;
            document.getElementById('inactivePassword').value = '';
            document.getElementById('inactiveReason').value = '';
            
            const modalTitle = document.getElementById('inactiveModalTitle');
            if (modalTitle) {
                modalTitle.innerHTML = `Set Official <u>${escapeHtml(officialName)}</u> to Inactive`;
            }
            
            inactiveModal.style.display = 'block';
            document.getElementById('inactiveReason').focus();
        }
    };

    checkUrlParams();
}

// ── Check URL Params ──────────────────────────────────────────────────────────
function checkUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('search')) {
        const term = urlParams.get('search');
        const input = document.getElementById('officialsSearch');
        if (input) input.value = term;
        searchOfficials(term);
    }

    if (urlParams.has('status')) {
        filterByStatus(urlParams.get('status'), null);
    }
    
    if (urlParams.has('view')) {
        const id = urlParams.get('view');
        if (id && id !== 'all') viewOfficialDetails(id);
    } else if (urlParams.has('edit')) {
        const id = urlParams.get('edit');
        if (id) editOfficial(id);
    }
}

// ── Term Period Dropdown Toggle ───────────────────────────────────────────────
function toggleTermPeriodDropdown() {
    const panel  = document.getElementById('termPeriodDropdownPanel');
    const btn    = document.getElementById('calendarBtn');
    if (!panel) return;

    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    if (btn) btn.classList.toggle('active', !isOpen);
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    const wrap = document.getElementById('termPeriodDropdownWrap');
    if (wrap && !wrap.contains(e.target)) {
        const panel = document.getElementById('termPeriodDropdownPanel');
        const btn   = document.getElementById('calendarBtn');
        if (panel) panel.style.display = 'none';
        if (btn)   btn.classList.remove('active');
    }
});

// ── Term Period Filter (server-side) ─────────────────────────────────────────
function filterByTermPeriod(value) {
    if (!value) {
        window.location.href = 'officials.php?view=all';
        return;
    }
    const parts = value.split('|');
    if (parts.length === 2) {
        window.location.href =
            'officials.php?term_start=' + encodeURIComponent(parts[0]) +
            '&term_end='   + encodeURIComponent(parts[1]);
    }
}

// ── Status Filter Tabs (client-side) ─────────────────────────────────────────
function filterByStatus(status, btn) {
    currentStatusFilter = status;
    document.querySelectorAll('.officials-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');

    // Update URL parameter so the Export CSV script can detect the active tab
    const url = new URL(window.location);
    if (status !== 'all') {
        url.searchParams.set('tab', status);
    } else {
        url.searchParams.delete('tab');
    }
    window.history.replaceState({}, '', url);

    applyFilters();
}

// ── Search (client-side) ─────────────────────────────────────────────────────
let searchTimeout;
function searchOfficials(term) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentSearchTerm = term.toLowerCase().trim();
        
        // Show/hide clear button
        const clearBtn = document.getElementById('searchClearBtn');
        if (clearBtn) {
            clearBtn.style.display = term.length > 0 ? 'inline-flex' : 'none';
        }
        
        const url = new URL(window.location);
        if (term) {
            url.searchParams.set('search', term);
        } else {
            url.searchParams.delete('search');
        }
        window.history.replaceState({}, '', url);
        applyFilters();
    }, 300);
}

function clearSearch() {
    const input = document.getElementById('officialsSearch');
    const clearBtn = document.getElementById('searchClearBtn');
    if (input) input.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    currentSearchTerm = '';
    const url = new URL(window.location);
    url.searchParams.delete('search');
    window.history.replaceState({}, '', url);
    applyFilters();
}

// ── Apply both filters ────────────────────────────────────────────────────────
function applyFilters() {
    if (!officialsTable) return;

    officialsTable.filter(row => {
        // Exclude the empty-state row from pagination
        if (row.id === 'officialsEmptyRow' || !row.getAttribute('data-status')) return false;

        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

        const statusOk = currentStatusFilter === 'all' ||
                         rowStatus === currentStatusFilter.toLowerCase();
        const searchOk = !currentSearchTerm || rowSearch.includes(currentSearchTerm);

        return statusOk && searchOk;
    });
}

// ── Refresh ───────────────────────────────────────────────────────────────────
function refreshOfficials() {
    location.reload();
}

function toggleOtherInput(selectId, groupId) {
    const select = document.getElementById(selectId);
    const group = document.getElementById(groupId);
    if (select && group) group.style.display = (select.value === 'Other') ? 'block' : 'none';
}

// ── Selection Limiting Logic ──────────────────────────────────────────────────
function applySelectionLimits(officialId = null) {
    const activeOfficials = window.ACTIVE_OFFICIALS || [];
    
    const isEdit = officialId !== null;
    const statusSelect = document.getElementById(isEdit ? 'editStatus' : 'status');
    const positionSelect = document.getElementById(isEdit ? 'editPosition' : 'position');
    const chairmanshipSelect = document.getElementById(isEdit ? 'editChairmanship' : 'chairmanship');

    if (!positionSelect && !chairmanshipSelect) return;

    // If status is not Active, enable all options (as requested)
    const currentStatus = statusSelect ? statusSelect.value : 'Active';
    const isActuallyActive = currentStatus === 'Active';

    // Calculate current counts (excluding the official being edited)
    const posCounts = {};
    const chairCounts = {};

    activeOfficials.forEach(off => {
        if (off.id == officialId) return; // Skip current record in edit mode
        
        if (off.position) {
            posCounts[off.position] = (posCounts[off.position] || 0) + 1;
        }
        if (off.committee) {
            chairCounts[off.committee] = (chairCounts[off.committee] || 0) + 1;
        }
    });

    const posLimits = {
        'Barangay Captain': 1,
        'Barangay Kagawad': 7,
        'Kagawad': 7,
        'SK Chairman': 1,
        'SK Kagawad': 7,
        'Barangay Secretary': 1,
        'Barangay Treasurer': 1,
        'Barangay Administrator': 1,
        'Bookkeeper': 1
    };

    if (positionSelect) {
        Array.from(positionSelect.options).forEach(opt => {
            const val = opt.value;
            if (!val || val === 'Other') {
                opt.disabled = false;
                return;
            }

            if (!isActuallyActive) {
                opt.disabled = false;
                return;
            }

            const limit = posLimits[val] || 1;
            const count = posCounts[val] || 0;
            opt.disabled = count >= limit;
        });
    }

    if (chairmanshipSelect) {
        Array.from(chairmanshipSelect.options).forEach(opt => {
            const val = opt.value;
            if (!val || val === 'Other') {
                opt.disabled = false;
                return;
            }

            if (!isActuallyActive) {
                opt.disabled = false;
                return;
            }

            const count = chairCounts[val] || 0;
            opt.disabled = count >= 1;
        });
    }
}

// ── Create Official Modal ─────────────────────────────────────────────────────
function openCreateOfficialModal() {
    if (window.BIS_PERMS && !window.BIS_PERMS.officials_create) {
        showNotification('Permission denied to create officials.', 'error');
        return;
    }

    resetCreateForm();
    applySelectionLimits();
    const modal = new bootstrap.Modal(document.getElementById('createOfficialModal'));
    modal.show();
}

function resetCreateForm() {
    const form = document.getElementById('createOfficialForm');
    if (form) form.reset();
    clearSelectedResident();
}

// ── Resident Picker ───────────────────────────────────────────────────────────
function openResidentPicker() {
    const modal = new bootstrap.Modal(document.getElementById('residentPickerModal'));
    modal.show();
}

function searchResidentsForPicker(term) {
    const resultsEl = document.getElementById('residentPickerResults');
    if (!resultsEl) return;

    resultsEl.innerHTML = '<div class="picker-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

    fetch('model/search_residents_official.php?search=' + encodeURIComponent(term))
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.residents || data.residents.length === 0) {
                resultsEl.innerHTML = '<div class="picker-empty"><i class="fas fa-user-slash"></i><span>No residents found</span></div>';
                return;
            }

            resultsEl.innerHTML = data.residents.map(r => {
                const mInitial = r.middle_name ? r.middle_name.trim().charAt(0).toUpperCase() + '.' : '';
                const fullNameWithInitial = [r.first_name, mInitial, r.last_name, r.suffix].filter(Boolean).join(' ');

                const initials = ((r.first_name || '')[0] || '') + ((r.last_name || '')[0] || '');
                const photoHtml = r.photo
                    ? `<img src="${escapeHtml(r.photo)}" alt="${escapeHtml(fullNameWithInitial)}">`
                    : `<span class="picker-initials">${escapeHtml(initials.toUpperCase())}</span>`;

                const residentId = r.resident_id || 'N/A';

                return `
                <div class="picker-resident-item"
                     onclick="selectResident(
                         ${parseInt(r.id)},
                         '${escapeJs(fullNameWithInitial)}',
                         '${escapeJs(r.photo || '')}',
                         '${escapeJs(r.mobile_number || '')}'
                     )">
                    <div class="picker-resident-photo">${photoHtml}</div>
                    <div class="picker-resident-info">
                        <div class="picker-resident-name">${escapeHtml(fullNameWithInitial)}</div>
                        <div class="picker-resident-contact">ID: ${escapeHtml(residentId)}</div>
                    </div>
                    <button type="button" class="picker-select-btn">Select</button>
                </div>`;
            }).join('');
        })
        .catch(() => {
            resultsEl.innerHTML = '<div class="picker-empty"><i class="fas fa-exclamation-circle"></i><span>Error loading residents</span></div>';
        });
}

function selectResident(id, name, photo, contact) {
    // Populate hidden fields
    document.getElementById('selectedResidentId').value      = id;
    document.getElementById('selectedResidentFullname').value = name;
    document.getElementById('selectedResidentContact').value  = contact;
    document.getElementById('selectedResidentPhoto').value    = photo;

    // Build photo display
    const photoEl = document.getElementById('residentSelectedPhoto');
    if (photoEl) {
        if (photo) {
            photoEl.innerHTML = `<img src="${escapeHtml(photo)}" alt="${escapeHtml(name)}">`;
            photoEl.classList.add('has-photo');
        } else {
            const initials = name.split(' ')
                .filter(Boolean)
                .map(n => n[0])
                .join('')
                .substring(0, 2)
                .toUpperCase();
            photoEl.innerHTML = `<span>${escapeHtml(initials)}</span>`;
            photoEl.classList.remove('has-photo');
        }
    }

    // Update display fields
    const nameEl    = document.getElementById('residentSelectedName');
    const contactEl = document.getElementById('residentSelectedContact');
    if (nameEl)    nameEl.textContent    = name;
    if (contactEl) contactEl.textContent = contact || 'No contact number';

    // Show selected, hide placeholder
    document.getElementById('residentPlaceholder').style.display = 'none';
    document.getElementById('residentSelected').style.display    = 'flex';

    // Close picker modal
    const pickerModal = bootstrap.Modal.getInstance(document.getElementById('residentPickerModal'));
    if (pickerModal) pickerModal.hide();
}

function clearSelectedResident() {
    document.getElementById('selectedResidentId').value       = '';
    document.getElementById('selectedResidentFullname').value  = '';
    document.getElementById('selectedResidentContact').value   = '';
    document.getElementById('selectedResidentPhoto').value     = '';

    document.getElementById('residentPlaceholder').style.display = 'flex';
    document.getElementById('residentSelected').style.display    = 'none';
}

// ── Submit Create Official ────────────────────────────────────────────────────
async function submitCreateOfficial() {
    const form      = document.getElementById('createOfficialForm');
    const submitBtn = document.getElementById('createOfficialSubmitBtn');

    // Validate resident selection
    const residentId = document.getElementById('selectedResidentId')?.value;
    if (!residentId) {
        alert('Please select a resident first.');
        return;
    }

    const chairmanshipVal = document.getElementById('chairmanship')?.value || '';
    const positionVal = document.getElementById('position')?.value || '';
    const finalChairmanship = (chairmanshipVal === 'Other') ? (document.getElementById('otherChairmanship')?.value?.trim() || '') : chairmanshipVal;
    const finalPosition = (positionVal === 'Other') ? (document.getElementById('otherPosition')?.value?.trim() || '') : positionVal;
    const status = 'Active';

    if (positionVal === 'Other' && !finalPosition) {
        alert('Please enter a custom position name.');
        return;
    }

    // Validation for "Other" limit (1)
    if (status === 'Active') {
        const activeOfficials = window.ACTIVE_OFFICIALS || [];
        if (positionVal === 'Other') {
            const isTaken = activeOfficials.some(off => off.position && off.position.toLowerCase() === finalPosition.toLowerCase());
            if (isTaken) {
                alert(`The custom position "${finalPosition}" is already taken by another active official.`);
                return;
            }
        }
        if (chairmanshipVal === 'Other' && finalChairmanship) {
            const isTaken = activeOfficials.some(off => off.committee && off.committee.toLowerCase() === finalChairmanship.toLowerCase());
            if (isTaken) {
                alert(`The custom chairmanship "${finalChairmanship}" is already taken by another active official.`);
                return;
            }
        }
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    try {
        const formData = new FormData();
        formData.append('resident_id',         document.getElementById('selectedResidentId')?.value      || '');
        formData.append('fullname',             document.getElementById('selectedResidentFullname')?.value || '');
        formData.append('contact_number',       document.getElementById('selectedResidentContact')?.value  || '');
        formData.append('resident_photo_path',  document.getElementById('selectedResidentPhoto')?.value    || '');
        formData.append('chairmanship',         finalChairmanship || '');
        formData.append('position',             finalPosition);
        formData.append('term_start',           document.getElementById('termStart')?.value                || '');
        formData.append('term_end',             document.getElementById('termEnd')?.value                  || '');
        formData.append('status',               status);

        const response = await fetch('model/save_official.php', { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            showNotification('Official created successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('createOfficialModal'));
            if (modal) modal.hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.message || 'Failed to create official', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Create';
        }
    } catch (error) {
        console.error('Error creating official:', error);
        showNotification('An error occurred while creating the official.', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Create';
    }
}

// ── View Official Details ─────────────────────────────────────────────────────
function viewOfficialDetails(officialId) {
    if (window.BIS_PERMS && !window.BIS_PERMS.officials_view) {
        showNotification('Permission denied to view officials.', 'error');
        const url = new URL(window.location);
        url.searchParams.delete('view');
        window.history.replaceState({}, '', url);
        return;
    }

    console.log('Viewing official:', officialId);

    // Update URL parameter
    const url = new URL(window.location);
    url.searchParams.set('view', officialId);
    url.searchParams.delete('edit');
    window.history.replaceState({}, '', url);

    const modalEl = document.getElementById('viewOfficialModal');
    if (!modalEl) return;

    const loadingEl = document.getElementById('viewOfficialLoading');
    const contentEl = document.getElementById('viewOfficialContent');
    if (loadingEl) loadingEl.style.display = 'flex';
    if (contentEl) contentEl.style.display = 'none';

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    fetch('model/get_official_details.php?id=' + encodeURIComponent(officialId))
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed to load details');
            populateViewModal(data.official, data.history);
        })
        .catch(error => {
            console.error('Error:', error);
            if (loadingEl) {
                loadingEl.innerHTML =
                    `<i class="fas fa-exclamation-circle" style="color:#ef4444;"></i>
                     <span style="color:#ef4444;">Error: ${escapeHtml(error.message)}</span>`;
            }
        });
}

function populateViewModal(official, history) {
    // Photo box
    const photoBox = document.getElementById('viewOfficialPhotoBox');
    if (photoBox) {
        if (official.photo) {
            photoBox.innerHTML = `<img src="${escapeHtml(official.photo)}" alt="${escapeHtml(official.name)}">`;
            photoBox.classList.add('has-photo');
            photoBox.classList.remove('no-photo');
        } else {
            photoBox.innerHTML = `<span class="view-official-initials">${escapeHtml(official.initials)}</span>`;
            photoBox.classList.add('no-photo');
            photoBox.classList.remove('has-photo');
        }
    }

    const nameEl      = document.getElementById('viewOfficialName');
    const posEl       = document.getElementById('viewOfficialPosition');
    const committeeEl = document.getElementById('viewOfficialCommittee');
    const contactEl   = document.getElementById('viewOfficialContact');

    if (nameEl)      nameEl.textContent      = official.name     || '—';
    if (posEl)       posEl.textContent       = official.position || '—';
    if (committeeEl) {
        committeeEl.textContent    = official.committee || '—';
        committeeEl.style.display  = official.committee ? 'inline-block' : 'none';
    }
    if (contactEl) {
        contactEl.textContent = (official.contact_number && official.contact_number !== 'N/A')
            ? official.contact_number
            : 'No contact number';
    }

    // History table
    const historyBody = document.getElementById('viewOfficialHistoryBody');
    if (historyBody) {
        if (!history || history.length === 0) {
            historyBody.innerHTML = `
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:20px;">
                    No history records found.
                </td></tr>`;
        } else {
            historyBody.innerHTML = history.map(row => {
                const statusClass = 'hist-badge hist-badge-' + (row.status || '').toLowerCase();
                const typeClass   = 'hist-badge hist-badge-' + (row.appointment_type || '').toLowerCase();
                const currentMark = row.is_current
                    ? ' <span class="hist-current-mark">● Current</span>' : '';
                return `
                <tr class="${row.is_current ? 'hist-row-current' : ''}">
                    <td>${escapeHtml(row.position)}${currentMark}</td>
                    <td>${escapeHtml(row.committee)}</td>
                    <td>${escapeHtml(row.term_period)}</td>
                    <td><span class="${statusClass}">${escapeHtml(row.status)}</span></td>
                    <td><span class="${typeClass}">${escapeHtml(row.appointment_type)}</span></td>
                </tr>`;
            }).join('');
        }
    }

    const loadingEl = document.getElementById('viewOfficialLoading');
    const contentEl = document.getElementById('viewOfficialContent');
    if (loadingEl) loadingEl.style.display = 'none';
    if (contentEl) contentEl.style.display = 'block';
}

// ── Edit Official ─────────────────────────────────────────────────────────────
function editOfficial(officialId) {
    if (window.BIS_PERMS && !window.BIS_PERMS.officials_edit) {
        showNotification('Permission denied to edit officials.', 'error');
        const url = new URL(window.location);
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url);
        return;
    }

    console.log('Editing official:', officialId);

    // Update URL parameter
    const url = new URL(window.location);
    url.searchParams.set('edit', officialId);
    url.searchParams.delete('view');
    window.history.replaceState({}, '', url);

    const modalEl = document.getElementById('editOfficialModal');
    if (!modalEl) return;

    // Reset form first
    resetEditForm();

    // Open modal
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    applySelectionLimits(officialId);

    // Show loading state on submit button
    const submitBtn = document.getElementById('editOfficialSubmitBtn');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    }

    // Fetch official data
    fetch('model/get_official_for_edit.php?id=' + encodeURIComponent(officialId))
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed to load official');
            populateEditModal(data.official);
        })
        .catch(error => {
            console.error('Error loading official for edit:', error);
            alert('Error loading official: ' + error.message);
            const m = bootstrap.Modal.getInstance(modalEl);
            if (m) m.hide();
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
            }
        });
}

function populateEditModal(official) {
    // Set hidden ID
    const idEl = document.getElementById('editOfficialId');
    if (idEl) idEl.value = official.id;

    // Resident selector
    if (official.fullname) {
        // Show selected resident
        const photoEl = document.getElementById('editResidentSelectedPhoto');
        if (photoEl) {
            if (official.photo) {
                photoEl.innerHTML = `<img src="${escapeHtml(official.photo)}" alt="${escapeHtml(official.fullname)}">`;
                photoEl.classList.add('has-photo');
            } else {
                const initials = official.fullname.split(' ')
                    .filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
                photoEl.innerHTML = `<span>${escapeHtml(initials)}</span>`;
                photoEl.classList.remove('has-photo');
            }
        }
        const nameEl    = document.getElementById('editResidentSelectedName');
        const contactEl = document.getElementById('editResidentSelectedContact');
        if (nameEl)    nameEl.textContent    = official.fullname;
        if (contactEl) contactEl.textContent = official.contact || 'No contact number';

        document.getElementById('editResidentPlaceholder').style.display = 'none';
        document.getElementById('editResidentSelected').style.display    = 'flex';

        // Populate hidden fields
        document.getElementById('editSelectedResidentId').value       = official.resident_id || '';
        document.getElementById('editSelectedResidentFullname').value  = official.fullname;
        document.getElementById('editSelectedResidentContact').value   = official.contact || '';
        document.getElementById('editSelectedResidentPhoto').value     = official.photo || '';
    } else {
        // No resident — show placeholder
        document.getElementById('editResidentPlaceholder').style.display = 'flex';
        document.getElementById('editResidentSelected').style.display    = 'none';
    }

    // Chairmanship (committee)
    const chairEl = document.getElementById('editChairmanship');
    if (chairEl) chairEl.value = official.committee || '';

    // Position
    const posEl = document.getElementById('editPosition');
    if (posEl) posEl.value = official.position || '';

    // Check if current values are "Other" (not in predefined list)
    // Note: If you want specific logic to detect "Other" on populate, 
    // you'd compare against the dropdown options.
    toggleOtherInput('editChairmanship', 'editOtherChairmanshipGroup');
    toggleOtherInput('editPosition', 'editOtherPositionGroup');

    // Term dates
    const termStartEl = document.getElementById('editTermStart');
    const termEndEl   = document.getElementById('editTermEnd');
    if (termStartEl) termStartEl.value = official.term_start || '';
    if (termEndEl)   termEndEl.value   = official.term_end   || '';

    // Status
    const statusEl = document.getElementById('editStatus');
    if (statusEl) statusEl.value = official.status || 'Active';
}

function resetEditForm() {
    const form = document.getElementById('editOfficialForm');
    if (form) form.reset();

    document.getElementById('editOfficialId').value              = '';
    document.getElementById('editSelectedResidentId').value      = '';
    document.getElementById('editSelectedResidentFullname').value = '';
    document.getElementById('editSelectedResidentContact').value  = '';
    document.getElementById('editSelectedResidentPhoto').value    = '';

    const placeholder = document.getElementById('editResidentPlaceholder');
    const selected    = document.getElementById('editResidentSelected');
    if (placeholder) placeholder.style.display = 'flex';
    if (selected)    selected.style.display    = 'none';
}

// ── Edit Resident Picker ──────────────────────────────────────────────────────
function openEditResidentPicker() {
    const modal = new bootstrap.Modal(document.getElementById('editResidentPickerModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const editPickerModal = document.getElementById('editResidentPickerModal');
    if (editPickerModal) {
        editPickerModal.addEventListener('shown.bs.modal', function () {
            const input = document.getElementById('editPickerSearchInput');
            if (input) { input.value = ''; input.focus(); }
            searchResidentsForEditPicker('');
        });
    }

    // Edit submit button
    const editSubmitBtn = document.getElementById('editOfficialSubmitBtn');
    if (editSubmitBtn) {
        editSubmitBtn.addEventListener('click', submitEditOfficial);
    }

    // Auto-status on edit term date change
    const editTermStart = document.getElementById('editTermStart');
    const editTermEnd   = document.getElementById('editTermEnd');
    if (editTermStart) {
        editTermStart.addEventListener('change', function() {
            if (this.value) {
                const startDate = new Date(this.value);
                startDate.setFullYear(startDate.getFullYear() + 3);
                if (editTermEnd) editTermEnd.value = startDate.toISOString().split('T')[0];
            }
            updateEditStatusBasedOnDates();
        });
    }
    if (editTermEnd)   editTermEnd.addEventListener('change',   updateEditStatusBasedOnDates);

    // Toggle "Other" input fields for Edit
    const editChairSelect = document.getElementById('editChairmanship');
    const editPosSelect = document.getElementById('editPosition');
    if (editChairSelect) editChairSelect.addEventListener('change', () => toggleOtherInput('editChairmanship', 'editOtherChairmanshipGroup'));
    if (editPosSelect) editPosSelect.addEventListener('change', () => toggleOtherInput('editPosition', 'editOtherPositionGroup'));

    // Re-apply limits when edit status is manually changed
    const editStatusSelect = document.getElementById('editStatus');
    if (editStatusSelect) {
        editStatusSelect.addEventListener('change', () => {
            const id = document.getElementById('editOfficialId')?.value;
            applySelectionLimits(id);
        });
    }
});

function searchResidentsForEditPicker(term) {
    const resultsEl = document.getElementById('editResidentPickerResults');
    if (!resultsEl) return;

    resultsEl.innerHTML = '<div class="picker-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

    const officialId = document.getElementById('editOfficialId')?.value || '';

    fetch('model/search_residents_official.php?search=' + encodeURIComponent(term) + '&exclude_official_id=' + encodeURIComponent(officialId))
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.residents || data.residents.length === 0) {
                resultsEl.innerHTML = '<div class="picker-empty"><i class="fas fa-user-slash"></i><span>No residents found</span></div>';
                return;
            }
            resultsEl.innerHTML = data.residents.map(r => {
                const mInitial = r.middle_name ? r.middle_name.trim().charAt(0).toUpperCase() + '.' : '';
                const fullNameWithInitial = [r.first_name, mInitial, r.last_name, r.suffix].filter(Boolean).join(' ');

                const initials  = ((r.first_name || '')[0] || '') + ((r.last_name || '')[0] || '');
                const photoHtml = r.photo
                    ? `<img src="${escapeHtml(r.photo)}" alt="${escapeHtml(fullNameWithInitial)}">`
                    : `<span class="picker-initials">${escapeHtml(initials.toUpperCase())}</span>`;
                const residentId = r.resident_id || 'N/A';
                return `
                <div class="picker-resident-item"
                     onclick="selectEditResident(
                         ${parseInt(r.id)},
                         '${escapeJs(fullNameWithInitial)}',
                         '${escapeJs(r.photo || '')}',
                         '${escapeJs(r.mobile_number || '')}'
                     )">
                    <div class="picker-resident-photo">${photoHtml}</div>
                    <div class="picker-resident-info">
                        <div class="picker-resident-name">${escapeHtml(fullNameWithInitial)}</div>
                        <div class="picker-resident-contact">ID: ${escapeHtml(residentId)}</div>
                    </div>
                    <button type="button" class="picker-select-btn">Select</button>
                </div>`;
            }).join('');
        })
        .catch(() => {
            resultsEl.innerHTML = '<div class="picker-empty"><i class="fas fa-exclamation-circle"></i><span>Error loading residents</span></div>';
        });
}

function selectEditResident(id, name, photo, contact) {
    document.getElementById('editSelectedResidentId').value       = id;
    document.getElementById('editSelectedResidentFullname').value  = name;
    document.getElementById('editSelectedResidentContact').value   = contact;
    document.getElementById('editSelectedResidentPhoto').value     = photo;

    const photoEl = document.getElementById('editResidentSelectedPhoto');
    if (photoEl) {
        if (photo) {
            photoEl.innerHTML = `<img src="${escapeHtml(photo)}" alt="${escapeHtml(name)}">`;
            photoEl.classList.add('has-photo');
        } else {
            const initials = name.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
            photoEl.innerHTML = `<span>${escapeHtml(initials)}</span>`;
            photoEl.classList.remove('has-photo');
        }
    }

    const nameEl    = document.getElementById('editResidentSelectedName');
    const contactEl = document.getElementById('editResidentSelectedContact');
    if (nameEl)    nameEl.textContent    = name;
    if (contactEl) contactEl.textContent = contact || 'No contact number';

    document.getElementById('editResidentPlaceholder').style.display = 'none';
    document.getElementById('editResidentSelected').style.display    = 'flex';

    const pickerModal = bootstrap.Modal.getInstance(document.getElementById('editResidentPickerModal'));
    if (pickerModal) pickerModal.hide();
}

function clearEditSelectedResident() {
    document.getElementById('editSelectedResidentId').value       = '';
    document.getElementById('editSelectedResidentFullname').value  = '';
    document.getElementById('editSelectedResidentContact').value   = '';
    document.getElementById('editSelectedResidentPhoto').value     = '';

    document.getElementById('editResidentPlaceholder').style.display = 'flex';
    document.getElementById('editResidentSelected').style.display    = 'none';
}

function updateEditStatusBasedOnDates() {
    const termStart    = document.getElementById('editTermStart')?.value;
    const termEnd      = document.getElementById('editTermEnd')?.value;
    const statusSelect = document.getElementById('editStatus');
    if (!termStart || !termEnd || !statusSelect) return;

    const today = new Date();
    const start = new Date(termStart);
    const end   = new Date(termEnd);

    if (today >= start && today <= end) {
        statusSelect.value = 'Active';
    } else if (today > end) {
        statusSelect.value = 'Completed';
    } else {
        statusSelect.value = 'Inactive';
    }
    applySelectionLimits();
}

async function submitEditOfficial() {
    const form      = document.getElementById('editOfficialForm');
    const submitBtn = document.getElementById('editOfficialSubmitBtn');

    const chairmanshipVal = document.getElementById('editChairmanship')?.value || '';
    const positionVal = document.getElementById('editPosition')?.value || '';
    const finalChairmanship = (chairmanshipVal === 'Other') ? (document.getElementById('editOtherChairmanship')?.value?.trim() || '') : chairmanshipVal;
    const finalPosition = (positionVal === 'Other') ? (document.getElementById('editOtherPosition')?.value?.trim() || '') : positionVal;
    const officialId = document.getElementById('editOfficialId')?.value;

    if (positionVal === 'Other' && !finalPosition) {
        alert('Please enter a custom position name.');
        return;
    }

    // Validation for "Other" limit (1)
    if (document.getElementById('editStatus')?.value === 'Active') {
        const activeOfficials = window.ACTIVE_OFFICIALS || [];
        if (positionVal === 'Other') {
            const isTaken = activeOfficials.some(off => off.id != officialId && off.position.toLowerCase() === finalPosition.toLowerCase());
            if (isTaken) {
                alert(`The position "${finalPosition}" is already taken by another active official.`);
                return;
            }
        }
        if (chairmanshipVal === 'Other' && finalChairmanship) {
            const isTaken = activeOfficials.some(off => off.id != officialId && off.committee && off.committee.toLowerCase() === finalChairmanship.toLowerCase());
            if (isTaken) {
                alert(`The chairmanship "${finalChairmanship}" is already taken by another active official.`);
                return;
            }
        }
    }

    if (!officialId) {
        alert('Official ID is missing. Please close and try again.');
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    try {
        const formData = new FormData();
        formData.append('official_id',          officialId);
        formData.append('resident_id',           document.getElementById('editSelectedResidentId')?.value      || '');
        formData.append('fullname',              document.getElementById('editSelectedResidentFullname')?.value || '');
        formData.append('contact_number',        document.getElementById('editSelectedResidentContact')?.value  || '');
        formData.append('resident_photo_path',   document.getElementById('editSelectedResidentPhoto')?.value    || '');
        formData.append('chairmanship',          finalChairmanship);
        formData.append('position',              finalPosition);
        formData.append('term_start',            document.getElementById('editTermStart')?.value                || '');
        formData.append('term_end',              document.getElementById('editTermEnd')?.value                  || '');
        formData.append('status',                document.getElementById('editStatus')?.value                   || '');

        const response = await fetch('model/update_official.php', { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            showNotification('Official updated successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editOfficialModal'));
            if (modal) modal.hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(result.message || 'Failed to update official', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
        }
    } catch (error) {
        console.error('Error updating official:', error);
        showNotification('An error occurred while updating the official.', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
    }
}

// ── Delete Official ───────────────────────────────────────────────────────────
function deleteOfficial(officialId, officialName = 'Official') {
    if (window.BIS_PERMS && !window.BIS_PERMS.officials_archive) {
        showNotification('Permission denied to archive officials.', 'error');
        return;
    }

    const archiveModal = document.getElementById('archiveModal');
    if (archiveModal) {
        document.getElementById('archiveOfficialId').value = officialId;
        document.getElementById('archivePassword').value = '';
        document.getElementById('archiveReason').value = '';
        
        const modalTitle = document.getElementById('archiveModalTitle');
        if (modalTitle) {
            modalTitle.innerHTML = `Archive Official <u>${escapeHtml(officialName)}</u>`;
        }
        
        archiveModal.style.display = 'block';
    }
}

// ── Print Officials List ──────────────────────────────────────────────────────
async function handlePrintOfficials() {
    // Fetch Barangay Info for header
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
    
    // Prepare Print Iframe
    let printFrame = document.getElementById('officialsPrintFrame');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'officialsPrintFrame';
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

    let rowsHtml = '';
    let rowsToPrint = (officialsTable && officialsTable.filteredRows) 
        ? officialsTable.filteredRows 
        : Array.from(document.querySelectorAll('#officialsTableBody tr:not([style*="display: none"])'));
    
    rowsToPrint.forEach((row, index) => {
        if (row.id === 'officialsEmptyRow' || row.cells.length < 7) return;
        
        const no = index + 1;
        const name = row.querySelector('.official-info-name')?.textContent.trim() || '';
        const position = row.querySelector('.official-info-position')?.textContent.trim() || '';
        const chairmanship = row.cells[1]?.textContent.trim() || 'N/A';
        const termPeriod = row.cells[2]?.textContent.trim() || '';
        const contact = row.cells[5]?.textContent.trim() || '';

        rowsHtml += `
            <tr>
                <td style="text-align: center;">${no}</td>
                <td><strong>${name}</strong></td>
                <td>${chairmanship}</td>
                <td>${position}</td>
                <td>${termPeriod}</td>
                <td>${contact}</td>
            </tr>`;
    });

    const brgyLogoHtml = brgyInfo.barangay_logo 
        ? `<img src="${brgyInfo.barangay_logo}" style="width: 80px; height: 80px; object-fit: contain;">`
        : `<div style="width: 80px; height: 80px; border: 1px solid #ddd;"></div>`;
        
    const govLogoHtml = brgyInfo.official_emblem
        ? `<img src="${brgyInfo.official_emblem}" style="width: 80px; height: 80px; object-fit: contain;">`
        : `<div style="width: 80px; height: 80px; border: 1px solid #ddd;"></div>`;

    doc.write(`
        <html>
        <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
            <title>Barangay Officials List</title>
            <style>
                @page { size: A4 landscape; margin: 15mm; }
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                .header-center { flex: 1; text-align: center; }
                .header-center p { margin: 2px 0; font-size: 14px; }
                .brgy-name { font-weight: bold; font-size: 16px; text-transform: uppercase; }
                .report-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; text-transform: uppercase; text-decoration: underline; }
                .data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
                .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
            </style>
        </head>
        <body>
            <div class="cert-header">${brgyLogoHtml}<div class="header-center"><p>Republic of the Philippines</p><p>Province of ${brgyInfo.province_name}</p><p>Municipality of ${brgyInfo.town_name}</p><p class="brgy-name">${brgyInfo.barangay_name}</p></div>${govLogoHtml}</div>
            <div class="report-title">Barangay Officials Masterlist</div>
            <table class="data-table"><thead><tr><th>#</th><th>Official Name</th><th>Chairmanship</th><th>Position</th><th>Term Period</th><th>Contact Number</th></tr></thead><tbody>${rowsHtml}</tbody></table>
        </body></html>`);
    doc.close();

    // Add activity log
    const logData = new FormData();
    logData.append('description', 'Printed the barangay officials masterlist');
    fetch('model/log_print_masterlist.php', { method: 'POST', body: logData }).catch(e => console.error(e));

    setTimeout(() => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); }, 500);
}

// ── Utilities ─────────────────────────────────────────────────────────────────
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '<')
        .replace(/>/g, '>')
        .replace(/"/g, '"')
        .replace(/'/g, '&#039;');
}

function escapeJs(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}

function showNotification(message, type = 'info') {
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    let icon = 'info-circle';
    let bgColor = '#3b82f6';
    
    if (type === 'success') {
        icon = 'check-circle';
        bgColor = '#10b981';
    } else if (type === 'danger' || type === 'error') {
        icon = 'exclamation-circle';
        bgColor = '#ef4444';
    }
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===================================
// Action Menu Handlers
// ===================================
function initializeActionMenus() {
    // Use event delegation for dynamically loaded rows
    const tableBody = document.querySelector('#officialsTable tbody');
    
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const actionBtn = e.target.closest('.btn-action');
            if (actionBtn) {
                e.stopPropagation();
                const row = actionBtn.closest('tr');
                showActionMenu(row, actionBtn);
            }
        });
    }
}

function showActionMenu(row, button) {
    const officialId = button.getAttribute('data-official-id');
    const currentStatus = row.getAttribute('data-status') || 'Active';

    // Remove any existing action menus
    document.querySelectorAll('.action-menu').forEach(menu => menu.remove());
    
    // Build menu items based on permissions
    const perms = window.BIS_PERMS || {};
    let menuHtml = '';

    if (perms.officials_view) {
        menuHtml += `
        <div class="action-menu-item" data-action="view">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </div>`;
    }
    if (perms.officials_edit) {
        menuHtml += `
        <div class="action-menu-item" data-action="edit">
            <i class="fas fa-edit"></i>
            <span>Edit Official</span>
        </div>`;
    }
    if (perms.officials_status && currentStatus !== 'Completed' && currentStatus !== 'Deceased') {
        menuHtml += `
        <div class="action-menu-item has-submenu" data-action="change-status">
            <i class="fas fa-toggle-on"></i>
            <span>Change Status</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
            <div class="action-submenu">
                <div class="action-menu-item submenu-item ${currentStatus === 'Active' ? 'submenu-current' : ''}" data-status="Active">
                    <i class="fas fa-circle status-dot status-dot-active"></i>
                    <span>Active</span>
                    ${currentStatus === 'Active' ? '<i class="fas fa-check submenu-check"></i>' : ''}
                </div>
                <div class="action-menu-item submenu-item ${currentStatus === 'Inactive' ? 'submenu-current' : ''}" data-status="Inactive">
                    <i class="fas fa-circle status-dot status-dot-inactive"></i>
                    <span>Inactive</span>
                    ${currentStatus === 'Inactive' ? '<i class="fas fa-check submenu-check"></i>' : ''}
                </div>
            </div>
        </div>`;
    }
    if (perms.officials_archive) {
        menuHtml += `
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Archive Official</span>
        </div>`;
    }

    // Create action menu
    const menu = document.createElement('div');
    menu.className = 'action-menu';
    menu.innerHTML = menuHtml;
    
    // Append to body first to calculate height
    document.body.appendChild(menu);
    
    // Position menu
    const rect = button.getBoundingClientRect();
    const menuHeight = menu.offsetHeight;
    const windowHeight = window.innerHeight;

    menu.style.position = 'fixed';
    menu.style.left = 'auto';
    menu.style.right = `${window.innerWidth - rect.right}px`;
    
    // Check if menu exceeds bottom of screen
    if (rect.bottom + menuHeight + 5 > windowHeight) {
        // Position above the button
        menu.style.top = `${rect.top - menuHeight - 5}px`;
        menu.style.transformOrigin = 'bottom right';
    } else {
        // Position below the button
        menu.style.top = `${rect.bottom + 5}px`;
        menu.style.transformOrigin = 'top right';
    }

    // Handle submenu toggle
    menu.querySelectorAll('.has-submenu').forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();
            item.classList.toggle('show-submenu');
        });
    });

    // Add click handlers for regular menu items
    menu.querySelectorAll('.action-menu-item:not(.has-submenu):not(.submenu-item)').forEach(item => {
        item.addEventListener('click', () => {
            const action = item.getAttribute('data-action');
            const officialName = row.querySelector('.official-info-name')?.textContent || 'Official';
            handleAction(action, officialId, officialName);
            menu.remove();
        });
    });

    // Handle submenu item clicks
    menu.querySelectorAll('.submenu-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();
            const newStatus = item.getAttribute('data-status');
            
            if (newStatus === 'Inactive' && typeof window.openInactiveModal === 'function') {
                const officialName = row.querySelector('.official-info-name')?.textContent || 'Official';
                window.openInactiveModal(officialId, row, currentStatus, officialName);
            } else {
                updateOfficialStatus(officialId, newStatus, row, currentStatus);
            }
            menu.remove();
        });
    });

    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && e.target !== button) {
                menu.remove();
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 0);
}

function handleAction(action, officialId, officialName) {
    switch(action) {
        case 'view':
            if (officialId) viewOfficialDetails(officialId);
            break;
            
        case 'edit':
            if (officialId) editOfficial(officialId);
            break;
            
        case 'delete':
            if (officialId) deleteOfficial(officialId, officialName);
            break;
    }
}

function updateOfficialStatus(officialId, newStatus, row, currentStatus) {
    if (window.BIS_PERMS && !window.BIS_PERMS.officials_status) {
        showNotification('Permission denied to change official status.', 'error');
        return;
    }

    if (!officialId || newStatus === currentStatus) return;

    const formData = new FormData();
    formData.append('official_id', officialId);
    formData.append('status', newStatus);

    fetch('model/update_official_status.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            row.setAttribute('data-status', newStatus);
            const statusCell = row.querySelectorAll('td')[3];
            if (statusCell) {
                const badge = statusCell.querySelector('.badge');
                if (badge) {
                    badge.className = `badge badge-${newStatus.toLowerCase()}`;
                    badge.textContent = newStatus;
                }
            }
            showNotification('Status updated successfully', 'success');
        } else {
            showNotification(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        showNotification('An error occurred while updating the status', 'danger');
    });
}

// ============================================
// Inject CSS for Action Menus
// ============================================
(function () {
    const style = document.createElement('style');
    style.textContent = `
        .action-menu {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 8px;
            min-width: 180px;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }
        .action-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 14px;
            color: #374151;
        }
        .action-menu-item:hover {
            background-color: #f3f4f6;
        }
        .action-menu-item.danger {
            color: #ef4444;
        }
        .action-menu-item.danger:hover {
            background-color: #fee2e2;
        }
        .action-menu-item i {
            width: 16px;
            font-size: 14px;
        }
        .action-menu-divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 8px 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        /* ── Change Status Submenu ── */
        .action-menu-item.has-submenu { position: relative; }
        .action-menu-item.has-submenu .submenu-arrow {
            margin-left: auto; font-size: 10px; color: #9ca3af; width: auto !important;
        }
        .action-submenu {
            display: none; position: absolute; right: calc(100% + 6px); top: 0;
            background: white; border: 1px solid #e5e7eb; border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.12); padding: 6px;
            min-width: 160px; z-index: 10000; animation: fadeIn 0.15s ease;
        }
        .action-menu-item.has-submenu.show-submenu .action-submenu { display: block; }
        .submenu-item { gap: 10px; }
        .submenu-item.submenu-current { background-color: #f0f9ff; font-weight: 600; }
        .status-dot { font-size: 8px !important; width: 8px !important; flex-shrink: 0; }
        .status-dot-active   { color: #22c55e; }
        .status-dot-inactive { color: #f59e0b; }
        .status-dot-deceased { color: #ef4444; }
        .status-dot-completed { color: #3b82f6; }
        .submenu-check { margin-left: auto; font-size: 11px !important; width: auto !important; color: #3b82f6; }
    `;
    document.head.appendChild(style);
})();
