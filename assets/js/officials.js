/**
 * Barangay Officials Management
 * Handles CRUD operations, filtering, search, resident picker, and view modal
 */

// ── State ────────────────────────────────────────────────────────────────────
let currentStatusFilter = 'all';
let currentSearchTerm   = '';

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    console.log('Officials page loaded');
    initOfficials();
});

function initOfficials() {
    // Create Official Button
    const createBtn = document.getElementById('createOfficialBtn');
    if (createBtn) {
        createBtn.addEventListener('click', openCreateOfficialModal);
    }

    // Create Official Submit
    const submitBtn = document.getElementById('createOfficialSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitCreateOfficial);
    }

    // Term date auto-status
    const termStart = document.getElementById('termStart');
    const termEnd   = document.getElementById('termEnd');
    if (termStart) termStart.addEventListener('change', updateStatusBasedOnDates);
    if (termEnd)   termEnd.addEventListener('change',   updateStatusBasedOnDates);

    // View buttons (table)
    document.querySelectorAll('.btn-view').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            viewOfficialDetails(this.getAttribute('data-official-id'));
        });
    });

    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            editOfficial(this.getAttribute('data-official-id'));
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            deleteOfficial(this.getAttribute('data-official-id'));
        });
    });

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
        window.location.href = 'officials.php';
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
    applyFilters();
}

// ── Search (client-side) ─────────────────────────────────────────────────────
function searchOfficials(term) {
    currentSearchTerm = term.toLowerCase().trim();
    // Show/hide clear button
    const clearBtn = document.getElementById('searchClearBtn');
    if (clearBtn) {
        clearBtn.style.display = term.length > 0 ? 'inline-flex' : 'none';
    }
    applyFilters();
}

function clearSearch() {
    const input = document.getElementById('officialsSearch');
    const clearBtn = document.getElementById('searchClearBtn');
    if (input) input.value = '';
    if (clearBtn) clearBtn.style.display = 'none';
    currentSearchTerm = '';
    applyFilters();
}

// ── Apply both filters ────────────────────────────────────────────────────────
function applyFilters() {
    const rows = document.querySelectorAll('#officialsTableBody tr[data-status]');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
        const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

        const statusOk = currentStatusFilter === 'all' ||
                         rowStatus === currentStatusFilter.toLowerCase();
        const searchOk = !currentSearchTerm || rowSearch.includes(currentSearchTerm);

        if (statusOk && searchOk) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    // Show/hide empty state row
    const emptyRow = document.getElementById('officialsEmptyRow');
    if (emptyRow) {
        emptyRow.style.display = visibleCount === 0 ? '' : 'none';
    }
}

// ── Refresh ───────────────────────────────────────────────────────────────────
function refreshOfficials() {
    location.reload();
}

// ── Create Official Modal ─────────────────────────────────────────────────────
function openCreateOfficialModal() {
    resetCreateForm();
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
                const initials = ((r.first_name || '')[0] || '') + ((r.last_name || '')[0] || '');
                const photoHtml = r.photo
                    ? `<img src="${escapeHtml(r.photo)}" alt="${escapeHtml(r.full_name)}">`
                    : `<span class="picker-initials">${escapeHtml(initials.toUpperCase())}</span>`;

                const contact = r.mobile_number || 'No contact number';

                return `
                <div class="picker-resident-item"
                     onclick="selectResident(
                         ${parseInt(r.id)},
                         '${escapeJs(r.full_name)}',
                         '${escapeJs(r.photo || '')}',
                         '${escapeJs(r.mobile_number || '')}'
                     )">
                    <div class="picker-resident-photo">${photoHtml}</div>
                    <div class="picker-resident-info">
                        <div class="picker-resident-name">${escapeHtml(r.full_name)}</div>
                        <div class="picker-resident-contact">${escapeHtml(contact)}</div>
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

// ── Auto-calculate status from term dates ─────────────────────────────────────
function updateStatusBasedOnDates() {
    const termStart   = document.getElementById('termStart')?.value;
    const termEnd     = document.getElementById('termEnd')?.value;
    const statusSelect = document.getElementById('status');
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
        formData.append('chairmanship',         document.getElementById('chairmanship')?.value             || '');
        formData.append('position',             document.getElementById('position')?.value                 || '');
        formData.append('term_start',           document.getElementById('termStart')?.value                || '');
        formData.append('term_end',             document.getElementById('termEnd')?.value                  || '');
        formData.append('status',               document.getElementById('status')?.value                   || '');

        const response = await fetch('model/save_official.php', { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            alert('Official created successfully!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('createOfficialModal'));
            if (modal) modal.hide();
            location.reload();
        } else {
            throw new Error(result.message || 'Failed to create official');
        }
    } catch (error) {
        console.error('Error creating official:', error);
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Create';
    }
}

// ── View Official Details ─────────────────────────────────────────────────────
function viewOfficialDetails(officialId) {
    console.log('Viewing official:', officialId);

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
    console.log('Editing official:', officialId);

    const modalEl = document.getElementById('editOfficialModal');
    if (!modalEl) return;

    // Reset form first
    resetEditForm();

    // Open modal
    const modal = new bootstrap.Modal(modalEl);
    modal.show();

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
    if (editTermStart) editTermStart.addEventListener('change', updateEditStatusBasedOnDates);
    if (editTermEnd)   editTermEnd.addEventListener('change',   updateEditStatusBasedOnDates);
});

function searchResidentsForEditPicker(term) {
    const resultsEl = document.getElementById('editResidentPickerResults');
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
                const initials  = ((r.first_name || '')[0] || '') + ((r.last_name || '')[0] || '');
                const photoHtml = r.photo
                    ? `<img src="${escapeHtml(r.photo)}" alt="${escapeHtml(r.full_name)}">`
                    : `<span class="picker-initials">${escapeHtml(initials.toUpperCase())}</span>`;
                const contact = r.mobile_number || 'No contact number';
                return `
                <div class="picker-resident-item"
                     onclick="selectEditResident(
                         ${parseInt(r.id)},
                         '${escapeJs(r.full_name)}',
                         '${escapeJs(r.photo || '')}',
                         '${escapeJs(r.mobile_number || '')}'
                     )">
                    <div class="picker-resident-photo">${photoHtml}</div>
                    <div class="picker-resident-info">
                        <div class="picker-resident-name">${escapeHtml(r.full_name)}</div>
                        <div class="picker-resident-contact">${escapeHtml(contact)}</div>
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
}

async function submitEditOfficial() {
    const form      = document.getElementById('editOfficialForm');
    const submitBtn = document.getElementById('editOfficialSubmitBtn');

    const officialId = document.getElementById('editOfficialId')?.value;
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
        formData.append('chairmanship',          document.getElementById('editChairmanship')?.value             || '');
        formData.append('position',              document.getElementById('editPosition')?.value                 || '');
        formData.append('term_start',            document.getElementById('editTermStart')?.value                || '');
        formData.append('term_end',              document.getElementById('editTermEnd')?.value                  || '');
        formData.append('status',                document.getElementById('editStatus')?.value                   || '');

        const response = await fetch('model/update_official.php', { method: 'POST', body: formData });
        const result   = await response.json();

        if (result.success) {
            alert('Official updated successfully!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editOfficialModal'));
            if (modal) modal.hide();
            location.reload();
        } else {
            throw new Error(result.message || 'Failed to update official');
        }
    } catch (error) {
        console.error('Error updating official:', error);
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update';
    }
}

// ── Delete Official ───────────────────────────────────────────────────────────
function deleteOfficial(officialId) {
    if (!confirm('Are you sure you want to move this official to archive?\n\nThis action will remove the official from the active list but preserve their record in the archive.')) {
        return;
    }

    const deleteBtn = document.querySelector(`.btn-delete[data-official-id="${officialId}"]`);
    if (deleteBtn) {
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
    }

    fetch('model/delete_official.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + officialId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Official moved to archive successfully');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to archive official'));
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while archiving the official');
        if (deleteBtn) {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
        }
    });
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
