/**
 * Certificates Page JavaScript
 * Handles search, card navigation, and certificate request modals
 * Includes certificate request limitation logic (3x daily for most certs, 1x for jobseeker/oath)
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // DOM Elements
    // ============================================
    const searchInput      = document.getElementById('searchInput');
    const clearSearchBtn   = document.getElementById('clearSearch');
    const refreshBtn       = document.getElementById('refreshBtn');
    const certificatesGridContainer = document.getElementById('certificatesGridContainer');

    // Certificate types with their limits (daily)
    const CERTIFICATE_LIMITS = {
        'indigency': 3,
        'residency': 3,
        'fishing': 3,
        'gmrc': 3,
        'lowincome': 3,
        'soloparent': 3,
        'rbc': 3,
        'brgyclearance': 3,
        'brgybusinessclearance': 3,
        'businesspermit': 3,
        'vesseldocking': 3,
        'ftjobseeker': 1,  // 1-time only
        'oath': 1          // 1-time only
    };

    // Current certificate type for each modal
    let currentCertType = '';

    // ============================================
    // Apply Permissions (Hide/Block Actions)
    // ============================================
    if (window.BIS_PERMS) {
        if (!window.BIS_PERMS.cert_edit) {
            const editCertBtn = document.getElementById('editCertPhotoBtn');
            if (editCertBtn) editCertBtn.style.display = 'none';
        }
        if (!window.BIS_PERMS.cert_generate) {
            document.querySelectorAll('.btn-print-cert, [id$="PrintBtn"]').forEach(btn => {
                btn.style.display = 'none';
            });
        }
    }

    // Intercept clicks to prevent bypasses
    document.addEventListener('click', function(e) {
        const isPrintBtn = e.target.closest('.btn-print-cert') || e.target.closest('[id$="PrintBtn"]');
        
        if (isPrintBtn && window.BIS_PERMS && !window.BIS_PERMS.cert_generate) {
            e.preventDefault();
            e.stopPropagation();
            showNotification('Permission denied to generate certificates.', 'error');
            return;
        }
    }, true);

    // ============================================
    // Search Functionality
    // ============================================
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase().trim();
            filterCards(term);
            if (clearSearchBtn) {
                clearSearchBtn.style.display = term ? 'flex' : 'none';
            }
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            this.style.display = 'none';
            filterCards('');
            searchInput.focus();
        });
    }

    function filterCards(term) {
        const cards = document.querySelectorAll('.certificate-card');
        let visible = 0;
        cards.forEach(function (card) {
            const title = card.getAttribute('data-title') || '';
            const matches = !term || title.includes(term);
            card.classList.toggle('hidden', !matches);
            if (matches) visible++;
        });
        
        // Hide empty categories
        const categories = document.querySelectorAll('.category-section');
        categories.forEach(category => {
            const visibleCards = category.querySelectorAll('.certificate-card:not(.hidden)');
            if (visibleCards.length === 0) {
                category.style.display = 'none';
            } else {
                category.style.display = 'block';
            }
        });

        showEmptyState(visible === 0 && cards.length > 0);
    }

    function showEmptyState(show) {
        let emptyState = certificatesGridContainer ? certificatesGridContainer.querySelector('.empty-state-search') : null;
        if (show && !emptyState && certificatesGridContainer) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state empty-state-search';
            emptyState.innerHTML = `
                <i class="fas fa-search"></i>
                <p>No certificates found</p>
                <p class="empty-subtitle">Try a different search term</p>
            `;
            certificatesGridContainer.appendChild(emptyState);
        } else if (!show && emptyState) {
            emptyState.remove();
        }
    }

    // ============================================
    // Refresh Button
    // ============================================
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            setTimeout(function () { location.reload(); }, 500);
        });
    }

    // ============================================
    // Certificate Card Click
    // ============================================
    document.querySelectorAll('.certificate-card-clickable').forEach(function (card) {
        card.addEventListener('click', function () {
            if (document.body.classList.contains('edit-mode-active')) {
                return; // Prevent modal opening while in edit mode
            }

            const modalId = this.getAttribute('data-modal');
            const link    = this.getAttribute('data-link');

            if (modalId) {
                const modalEl = document.getElementById(modalId);
                if (modalEl) {
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                }
            } else if (link) {
                window.location.href = link;
            }
        });
    });

    // ============================================
    // Preview Photos
    // ============================================
    document.querySelectorAll('.preview-cert-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent triggering the card's click event
            const photoUrl = this.getAttribute('data-photo-url');
            const previewImg = document.getElementById('previewModalImage');
            if (previewImg) {
                previewImg.src = photoUrl;
                const previewModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
                previewModal.show();
            }
        });
    });

    // ============================================
    // Edit Photos Mode
    // ============================================
    const editCertPhotoBtn = document.getElementById('editCertPhotoBtn');
    if (editCertPhotoBtn) {
        editCertPhotoBtn.addEventListener('click', function () {
            if (window.BIS_PERMS && !window.BIS_PERMS.cert_edit) {
                showNotification('Permission denied to edit certificate photos.', 'error');
                return;
            }
            document.body.classList.toggle('edit-mode-active');
            this.classList.toggle('active');
        });
    }

    const uploadCertBtns = document.querySelectorAll('.upload-cert-btn');
    uploadCertBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation(); // prevent card click
            const certId = this.getAttribute('data-cert-id');
            document.getElementById('uploadCertId').value = certId;
            const uploadModal = new bootstrap.Modal(document.getElementById('uploadCertPhotoModal'));
            uploadModal.show();
        });
    });

    const saveCertPhotoBtn = document.getElementById('saveCertPhotoBtn');
    if (saveCertPhotoBtn) {
        saveCertPhotoBtn.addEventListener('click', function () {
            if (!document.getElementById('certPhotoInput').files.length) {
                alert('Please select a file.');
                return;
            }
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

            const form = document.getElementById('uploadCertPhotoForm');
            const formData = new FormData(form);
            
            fetch('model/upload_cert_photo.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Error: ' + data.message);
            })
            .finally(() => { this.disabled = false; this.innerHTML = 'Upload'; });
        });
    }

    // ============================================
    // Helper: Get certificate limit
    // ============================================
    function getCertificateLimit(certType) {
        return CERTIFICATE_LIMITS[certType] || 3;
    }

    // ============================================
    // Certificate of Indigency Modal Logic
    // ============================================
    const residentNameInput  = document.getElementById('indigencyResidentName');
    const residentIdInput    = document.getElementById('indigencyResidentId');
    const residentDropdown   = document.getElementById('indigencyResidentDropdown');
    const residentBtn        = document.getElementById('indigencyResidentBtn');
    const dateInput          = document.getElementById('indigencyDate');
    const assistanceInput    = document.getElementById('indigencyAssistance');
    const printBtn           = document.getElementById('indigencyPrintBtn');
    const indigencyModalEl   = document.getElementById('indigencyModal');

    // --- NEW: Tab & Minor Elements ---
    const tabForSelf         = document.getElementById('tabForSelf');
    const tabGuardian        = document.getElementById('tabGuardian');
    const requestTypeInput   = document.getElementById('indigencyRequestType');
    
    const primaryResidentLabel = document.getElementById('primaryResidentLabel');
    const minorResidentGroup   = document.getElementById('minorResidentGroup');
    const minorNameInput       = document.getElementById('indigencyMinorName');
    const minorIdInput         = document.getElementById('indigencyMinorId');
    const minorDropdown        = document.getElementById('indigencyMinorDropdown');
    const minorBtn             = document.getElementById('indigencyMinorBtn');

    let searchTimeout = null;
    let minorSearchTimeout = null; // NEW: Timeout for minor search
    currentCertType = 'indigency';

    // --- NEW: Tab Toggle Function ---
    function setIndigencyTab(tabType) {
        if (!tabForSelf || !tabGuardian) return;

        if (tabType === 'self') {
            tabForSelf.classList.add('active');
            tabGuardian.classList.remove('active');
            if (requestTypeInput) requestTypeInput.value = 'self';
            
            // Revert label and hide minor input
            if (primaryResidentLabel) primaryResidentLabel.innerHTML = 'RESIDENT FULL NAME <span class="required-star">*</span>';
            if (minorResidentGroup) minorResidentGroup.style.display = 'none';
            
            if (minorNameInput) { minorNameInput.value = ''; minorNameInput.classList.remove('is-invalid'); }
            if (minorIdInput) minorIdInput.value = '';
            
        } else if (tabType === 'guardian') {
            tabGuardian.classList.add('active');
            tabForSelf.classList.remove('active');
            if (requestTypeInput) requestTypeInput.value = 'guardian';
            
            // Change label to Guardian and show minor input
            if (primaryResidentLabel) primaryResidentLabel.innerHTML = 'GUARDIAN FULL NAME <span class="required-star">*</span>';
            if (minorResidentGroup) minorResidentGroup.style.display = 'flex'; 
        }
    }

    // --- NEW: Attach Tab Events ---
    if (tabForSelf && tabGuardian) {
        tabForSelf.addEventListener('click', function() { setIndigencyTab('self'); });
        tabGuardian.addEventListener('click', function() { setIndigencyTab('guardian'); });
    }

    // --- ORIGINAL: Modal Reset (Updated to reset tabs) ---
    if (indigencyModalEl) {
        indigencyModalEl.addEventListener('show.bs.modal', function () {
            if (residentNameInput) residentNameInput.value = '';
            if (residentIdInput)   residentIdInput.value   = '';
            if (residentDropdown)  { residentDropdown.innerHTML = ''; residentDropdown.style.display = 'none'; }
            if (dateInput)         dateInput.value = getTodayDate();
            if (assistanceInput)   assistanceInput.value = '';
            
            setIndigencyTab('self'); // NEW: Reset tab to default
            clearResidentError();
        });
    }

    // --- ORIGINAL: Resident Search ---
    if (residentNameInput) {
        residentNameInput.addEventListener('input', function () {
            if (residentIdInput) residentIdInput.value = '';
            clearResidentError();
            const term = this.value.trim();
            clearTimeout(searchTimeout);
            if (term.length < 1) {
                hideDropdown();
                return;
            }
            searchTimeout = setTimeout(function () {
                searchResidents(term, 'indigency');
            }, 250);
        });
        residentNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideDropdown();
        });
    }

    if (residentBtn) {
        residentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (residentNameInput) {
                residentNameInput.value = '';
                residentNameInput.focus();
            }
            if (residentIdInput) residentIdInput.value = '';
            hideDropdown();
        });
    }

    // --- ORIGINAL: Global Click (Updated for Minor Dropdown) ---
    document.addEventListener('click', function (e) {
        if (residentDropdown && !e.target.closest('.resident-search-wrap')) {
            hideDropdown();
        }
        if (minorDropdown && !e.target.closest('#minorResidentGroup')) {
            minorDropdown.style.display = 'none'; // NEW: Hide minor dropdown
        }
    });

    // --- ORIGINAL: Search API ---
    function searchResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) {
                    renderDropdown(results, certType);
                } else {
                    hideDropdown();
                }
            })
            .catch(function () { hideDropdown(); });
    }

    // --- ORIGINAL: Render Primary Dropdown ---
    function renderDropdown(residents, certType) {
        if (!residentDropdown) return;
        const limit = getCertificateLimit(certType);

        if (residents.length === 0) {
            residentDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            residentDropdown.style.display = 'block';
            return;
        }

        residentDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            const used = limitInfo ? limitInfo.used : 0;
            const remaining = limit - used;
            const isDisabled = remaining <= 0;

            const item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            let limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault();
                    selectResident(r);
                });
            }
            residentDropdown.appendChild(item);
        });
        residentDropdown.style.display = 'block';
    }

    function selectResident(resident) {
        if (residentNameInput) residentNameInput.value = resident.full_name.trim();
        if (residentIdInput)   residentIdInput.value   = resident.id;
        hideDropdown();
        clearResidentError();
    }

    function hideDropdown() {
        if (residentDropdown) {
            residentDropdown.style.display = 'none';
            residentDropdown.innerHTML = '';
        }
    }

    // --- NEW: MINOR SEARCH LOGIC ---
    if (minorNameInput) {
        minorNameInput.addEventListener('input', function () {
            if (minorIdInput) minorIdInput.value = '';
            minorNameInput.classList.remove('is-invalid');
            
            const term = this.value.trim();
            clearTimeout(minorSearchTimeout);
            if (term.length < 1) {
                if (minorDropdown) minorDropdown.style.display = 'none';
                return;
            }
            minorSearchTimeout = setTimeout(function () {
                fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=indigency&filter=minor')
                    .then(res => res.json())
                    .then(data => {
                        const results = data.residents || data.data;
                        if (data.success && results) {
                            renderMinorDropdown(results);
                        } else {
                            if (minorDropdown) minorDropdown.style.display = 'none';
                        }
                    })
                    .catch(() => { if (minorDropdown) minorDropdown.style.display = 'none'; });
            }, 250);
        });

        minorNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && minorDropdown) minorDropdown.style.display = 'none';
        });
    }

    if (minorBtn) {
        minorBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (minorNameInput) {
                minorNameInput.value = '';
                minorNameInput.focus();
            }
            if (minorIdInput) minorIdInput.value = '';
            if (minorDropdown) minorDropdown.style.display = 'none';
        });
    }

    function renderMinorDropdown(residents) {
        if (!minorDropdown) return;
        if (residents.length === 0) {
            minorDropdown.innerHTML = '<div class="resident-dropdown-empty">No minor found.</div>';
            minorDropdown.style.display = 'block';
            return;
        }

        minorDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            
            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
            `;
            
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                if (minorNameInput) minorNameInput.value = r.full_name.trim();
                if (minorIdInput)   minorIdInput.value   = r.id; 
                if (minorDropdown)  minorDropdown.style.display = 'none';
                minorNameInput.classList.remove('is-invalid');
            });
            minorDropdown.appendChild(item);
        });
        minorDropdown.style.display = 'block';
    }


    // --- ORIGINAL: Print Button (Updated with Request Type and Minor ID) ---
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            const residentId  = residentIdInput  ? residentIdInput.value.trim()  : '';
            const date        = dateInput        ? dateInput.value.trim()        : '';
            const assistance  = assistanceInput  ? assistanceInput.value.trim()  : '';
            
            // NEW: Grab tab selection and minor ID
            const requestType = requestTypeInput ? requestTypeInput.value : 'self';
            const minorId     = minorIdInput     ? minorIdInput.value.trim()  : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!assistance) { alert('Please enter the assistance type.'); return; }

            if (!residentId) {
                // Ensure this original error function is still called
                if (typeof showResidentError === "function") showResidentError('Please select a resident from the list.');
                if (residentNameInput) residentNameInput.focus();
                return;
            }

            // NEW: Validation for minor if guardian is selected
            if (requestType === 'guardian' && !minorId) {
                if (minorNameInput) {
                    minorNameInput.classList.add('is-invalid');
                    minorNameInput.focus();
                }
                return;
            }

            if (!date) {
                if (dateInput) { dateInput.classList.add('is-invalid'); dateInput.focus(); }
                return;
            } else {
                if (dateInput) dateInput.classList.remove('is-invalid');
            }

            // ORIGINAL: Updated params
            const params = new URLSearchParams({ 
                resident_id: residentId, 
                date: date, 
                assistance: assistance,
                request_type: requestType,
                minor_id: requestType === 'guardian' ? minorId : '' // Send minor ID
            });
            
            logCertificateRequest(residentId, 'indigency', assistance, function() {
                window.location.href = 'certifications/certificate-indigency.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Certificate of Residency Modal Logic
    // ============================================
    const residencyNameInput    = document.getElementById('residencyResidentName');
    const residencyIdInput      = document.getElementById('residencyResidentId');
    const residencyDropdown     = document.getElementById('residencyResidentDropdown');
    const residencyResidentBtn  = document.getElementById('residencyResidentBtn');
    const residencyDateInput    = document.getElementById('residencyDate');
    const residencyPurposeInput = document.getElementById('residencyPurpose');
    const residencyPrintBtn     = document.getElementById('residencyPrintBtn');
    const residencyModalEl      = document.getElementById('residencyModal');

    let residencySearchTimeout = null;

    if (residencyModalEl) {
        residencyModalEl.addEventListener('show.bs.modal', function () {
            if (residencyNameInput)    residencyNameInput.value    = '';
            if (residencyIdInput)      residencyIdInput.value      = '';
            if (residencyDropdown)     { residencyDropdown.innerHTML = ''; residencyDropdown.style.display = 'none'; }
            if (residencyDateInput)    residencyDateInput.value    = getTodayDate();
            if (residencyPurposeInput) residencyPurposeInput.value = '';
            clearResidencyError();
        });
    }

    if (residencyNameInput) {
        residencyNameInput.addEventListener('input', function () {
            if (residencyIdInput) residencyIdInput.value = '';
            clearResidencyError();
            const term = this.value.trim();
            clearTimeout(residencySearchTimeout);
            if (term.length < 1) { hideResidencyDropdown(); return; }
            residencySearchTimeout = setTimeout(function () { searchResidencyResidents(term, 'residency'); }, 250);
        });
        residencyNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideResidencyDropdown(); });
    }

    if (residencyResidentBtn) {
        residencyResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (residencyNameInput) { residencyNameInput.value = ''; residencyNameInput.focus(); }
            if (residencyIdInput) residencyIdInput.value = '';
            hideResidencyDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (residencyDropdown && !e.target.closest('#residencyModal .resident-search-wrap')) { hideResidencyDropdown(); }
    });

    function searchResidencyResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) { renderResidencyDropdown(results, certType); }
                else { hideResidencyDropdown(); }
            })
            .catch(function () { hideResidencyDropdown(); });
    }

    function renderResidencyDropdown(residents, certType) {
        if (!residencyDropdown) return;
        const limit = getCertificateLimit(certType);

        if (residents.length === 0) {
            residencyDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            residencyDropdown.style.display = 'block';
            return;
        }

        residencyDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            const used = limitInfo ? limitInfo.used : 0;
            const remaining = limit - used;
            const isDisabled = remaining <= 0;

            const item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            let limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectResidencyResident(r); });
            }
            residencyDropdown.appendChild(item);
        });
        residencyDropdown.style.display = 'block';
    }

    function selectResidencyResident(resident) {
        if (residencyNameInput) residencyNameInput.value = resident.full_name.trim();
        if (residencyIdInput)   residencyIdInput.value   = resident.id;
        hideResidencyDropdown();
        clearResidencyError();
    }

    function hideResidencyDropdown() {
        if (residencyDropdown) { residencyDropdown.style.display = 'none'; residencyDropdown.innerHTML = ''; }
    }

    if (residencyPrintBtn) {
        residencyPrintBtn.addEventListener('click', function () {
            const residentId = residencyIdInput      ? residencyIdInput.value.trim()      : '';
            const date       = residencyDateInput    ? residencyDateInput.value.trim()    : '';
            const purpose    = residencyPurposeInput ? residencyPurposeInput.value.trim() : '';
            
            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }

            if (!residentId) { showResidencyError('Please select a resident from the list.'); if (residencyNameInput) residencyNameInput.focus(); return; }
            if (!date) { if (residencyDateInput) { residencyDateInput.classList.add('is-invalid'); residencyDateInput.focus(); } return; }
            else { if (residencyDateInput) residencyDateInput.classList.remove('is-invalid'); }

            const params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'residency', purpose, function() {
                window.location.href = 'certifications/certificate-residency.php?' + params.toString();
            });
        });
    }

    function showResidencyError(msg) {
        clearResidencyError();
        if (!residencyNameInput) return;
        residencyNameInput.classList.add('is-invalid');
        const err = document.createElement('div');
        err.className = 'invalid-feedback residency-error-msg';
        err.textContent = msg;
        residencyNameInput.closest('.resident-input-group').after(err);
    }

    function clearResidencyError() {
        if (residencyNameInput) residencyNameInput.classList.remove('is-invalid');
        const existing = document.querySelector('.residency-error-msg');
        if (existing) existing.remove();
    }

    // ============================================
    // Helpers
    // ============================================
    function getTodayDate() {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm   = String(d.getMonth() + 1).padStart(2, '0');
        const dd   = String(d.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    function showResidentError(msg) {
        clearResidentError();
        if (!residentNameInput) return;
        residentNameInput.classList.add('is-invalid');
        const err = document.createElement('div');
        err.className = 'invalid-feedback resident-error-msg';
        err.textContent = msg;
        residentNameInput.closest('.resident-input-group').after(err);
    }

    function clearResidentError() {
        if (residentNameInput) residentNameInput.classList.remove('is-invalid');
        const existing = document.querySelector('.resident-error-msg');
        if (existing) existing.remove();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ============================================
    // Fishing Clearance Modal Logic
    // ============================================
    const fishingNameInput    = document.getElementById('fishingResidentName');
    const fishingIdInput      = document.getElementById('fishingResidentId');
    const fishingDropdown     = document.getElementById('fishingResidentDropdown');
    const fishingResidentBtn  = document.getElementById('fishingResidentBtn');
    const fishingBoatNameInput = document.getElementById('fishingBoatName');
    const fishingDateInput    = document.getElementById('fishingDate');
    const fishingPurposeInput = document.getElementById('fishingPurpose');
    const fishingPrintBtn     = document.getElementById('fishingPrintBtn');
    const fishingModalEl      = document.getElementById('fishingClearanceModal');

    let fishingSearchTimeout = null;

    if (fishingModalEl) {
        fishingModalEl.addEventListener('show.bs.modal', function () {
            if (fishingNameInput)    fishingNameInput.value    = '';
            if (fishingIdInput)      fishingIdInput.value      = '';
            if (fishingDropdown)     { fishingDropdown.innerHTML = ''; fishingDropdown.style.display = 'none'; }
            if (fishingBoatNameInput) fishingBoatNameInput.value = '';
            if (fishingDateInput)    fishingDateInput.value    = getTodayDate();
            if (fishingPurposeInput)  fishingPurposeInput.value = 'Boat Registration';
            clearFishingError();
        });
    }

    if (fishingNameInput) {
        fishingNameInput.addEventListener('input', function () {
            if (fishingIdInput) fishingIdInput.value = '';
            clearFishingError();
            const term = this.value.trim();
            clearTimeout(fishingSearchTimeout);
            if (term.length < 1) { hideFishingDropdown(); return; }
            fishingSearchTimeout = setTimeout(function () { searchFishingResidents(term, 'fishing'); }, 250);
        });
        fishingNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideFishingDropdown(); });
    }

    if (fishingResidentBtn) {
        fishingResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (fishingNameInput) { fishingNameInput.value = ''; fishingNameInput.focus(); }
            if (fishingIdInput) fishingIdInput.value = '';
            hideFishingDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (fishingDropdown && !e.target.closest('#fishingClearanceModal .resident-search-wrap')) { hideFishingDropdown(); }
    });

    function searchFishingResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) { renderFishingDropdown(results, certType); }
                else { hideFishingDropdown(); }
            })
            .catch(function () { hideFishingDropdown(); });
    }

    function renderFishingDropdown(residents, certType) {
        if (!fishingDropdown) return;
        const limit = getCertificateLimit(certType);

        if (residents.length === 0) {
            fishingDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            fishingDropdown.style.display = 'block';
            return;
        }
        fishingDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            const used = limitInfo ? limitInfo.used : 0;
            const remaining = limit - used;
            const isDisabled = remaining <= 0;

            const item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            let limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectFishingResident(r); });
            }
            fishingDropdown.appendChild(item);
        });
        fishingDropdown.style.display = 'block';
    }

    function selectFishingResident(resident) {
        if (fishingNameInput) fishingNameInput.value = resident.full_name.trim();
        if (fishingIdInput)   fishingIdInput.value   = resident.id;
        hideFishingDropdown();
        clearFishingError();
    }

    function hideFishingDropdown() {
        if (fishingDropdown) { fishingDropdown.style.display = 'none'; fishingDropdown.innerHTML = ''; }
    }

    function clearFishingError() {
        if (fishingNameInput) fishingNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.fishing-error-msg');
        if (existing) existing.remove();
    }

    function showFishingError(msg) {
        clearFishingError();
        if (!fishingNameInput) return;
        fishingNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback fishing-error-msg';
        err.textContent = msg;
        fishingNameInput.closest('.resident-input-group').after(err);
    }

    if (fishingPrintBtn) {
        fishingPrintBtn.addEventListener('click', function () {
            var residentId = fishingIdInput      ? fishingIdInput.value.trim()      : '';
            var boatName   = fishingBoatNameInput ? fishingBoatNameInput.value.trim() : '';
            var date       = fishingDateInput    ? fishingDateInput.value.trim()    : '';
            var purpose    = fishingPurposeInput ? fishingPurposeInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!boatName) { alert('Please enter the boat name.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }

            if (!residentId) {
                showFishingError('Please select a resident from the list.');
                if (fishingNameInput) fishingNameInput.focus();
                return;
            }
            if (!date) {
                if (fishingDateInput) { fishingDateInput.classList.add('is-invalid'); fishingDateInput.focus(); }
                return;
            } else {
                if (fishingDateInput) fishingDateInput.classList.remove('is-invalid');
            }

            var params = new URLSearchParams({ resident_id: residentId,  boatname: boatName, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'fishing', purpose, function() {
                window.location.href = 'certifications/certificate-fishingclearance.php?' + params.toString();
            });
        });
    }

    // ============================================
    // First Time Jobseeker Modal Logic (1-time only)
    // ============================================
    var ftJobseekerNameInput    = document.getElementById('ftJobseekerResidentName');
    var ftJobseekerIdInput      = document.getElementById('ftJobseekerResidentId');
    var ftJobseekerDropdown     = document.getElementById('ftJobseekerResidentDropdown');
    var ftJobseekerResidentBtn  = document.getElementById('ftJobseekerResidentBtn');
    var ftJobseekerDateInput    = document.getElementById('ftJobseekerDate');
    var ftJobseekerPurposeInput = document.getElementById('ftJobseekerPurpose');
    var ftJobseekerPrintBtn     = document.getElementById('ftJobseekerPrintBtn');
    var ftJobseekerModalEl      = document.getElementById('ftJobseekerModal');

    var ftJobseekerSearchTimeout = null;

    if (ftJobseekerModalEl) {
        ftJobseekerModalEl.addEventListener('show.bs.modal', function () {
            if (ftJobseekerNameInput)    ftJobseekerNameInput.value    = '';
            if (ftJobseekerIdInput)       ftJobseekerIdInput.value      = '';
            if (ftJobseekerDropdown)      { ftJobseekerDropdown.innerHTML = ''; ftJobseekerDropdown.style.display = 'none'; }
            if (ftJobseekerDateInput)     ftJobseekerDateInput.value    = getTodayDate();
            if (ftJobseekerPurposeInput)  ftJobseekerPurposeInput.value = 'First Time Jobseeker Assistance (RA 11261)';
            clearFtJobseekerError();
        });
    }

    if (ftJobseekerNameInput) {
        ftJobseekerNameInput.addEventListener('input', function () {
            if (ftJobseekerIdInput) ftJobseekerIdInput.value = '';
            clearFtJobseekerError();
            var term = this.value.trim();
            clearTimeout(ftJobseekerSearchTimeout);
            if (term.length < 1) { hideFtJobseekerDropdown(); return; }
            ftJobseekerSearchTimeout = setTimeout(function () { searchFtJobseekerResidents(term, 'ftjobseeker'); }, 250);
        });
        ftJobseekerNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideFtJobseekerDropdown(); });
    }

    if (ftJobseekerResidentBtn) {
        ftJobseekerResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (ftJobseekerNameInput) { ftJobseekerNameInput.value = ''; ftJobseekerNameInput.focus(); }
            if (ftJobseekerIdInput) ftJobseekerIdInput.value = '';
            hideFtJobseekerDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (ftJobseekerDropdown && !e.target.closest('#ftJobseekerModal .resident-search-wrap')) { hideFtJobseekerDropdown(); }
    });

    function searchFtJobseekerResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderFtJobseekerDropdown(results, certType); }
                else { hideFtJobseekerDropdown(); }
            })
            .catch(function () { hideFtJobseekerDropdown(); });
    }

    function renderFtJobseekerDropdown(residents, certType) {
        if (!ftJobseekerDropdown) return;
        var limit = getCertificateLimit(certType);

        if (residents.length === 0) { ftJobseekerDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; ftJobseekerDropdown.style.display = 'block'; return; }
        ftJobseekerDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;

            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectFtJobseekerResident(r); });
            }
            ftJobseekerDropdown.appendChild(item);
        });
        ftJobseekerDropdown.style.display = 'block';
    }

    function selectFtJobseekerResident(resident) {
        if (ftJobseekerNameInput) ftJobseekerNameInput.value = resident.full_name.trim();
        if (ftJobseekerIdInput)   ftJobseekerIdInput.value   = resident.id;
        hideFtJobseekerDropdown();
        clearFtJobseekerError();
    }

    function hideFtJobseekerDropdown() {
        if (ftJobseekerDropdown) { ftJobseekerDropdown.style.display = 'none'; ftJobseekerDropdown.innerHTML = ''; }
    }

    function clearFtJobseekerError() {
        if (ftJobseekerNameInput) ftJobseekerNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.ftjobseeker-error-msg');
        if (existing) existing.remove();
    }

    function showFtJobseekerError(msg) {
        clearFtJobseekerError();
        if (!ftJobseekerNameInput) return;
        ftJobseekerNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback ftjobseeker-error-msg';
        err.textContent = msg;
        ftJobseekerNameInput.closest('.resident-input-group').after(err);
    }

    if (ftJobseekerPrintBtn) {
        ftJobseekerPrintBtn.addEventListener('click', function () {
            var residentId = ftJobseekerIdInput      ? ftJobseekerIdInput.value.trim()      : '';
            var date       = ftJobseekerDateInput    ? ftJobseekerDateInput.value.trim()    : '';
            var purpose    = ftJobseekerPurposeInput ? ftJobseekerPurposeInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'ftjobseeker', purpose, function() {
                window.location.href = 'certifications/certificate-ft-jobseeker-assistance.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Good Moral Character (GMRC) Modal Logic
    // ============================================
    var gmrcNameInput    = document.getElementById('gmrcResidentName');
    var gmrcIdInput      = document.getElementById('gmrcResidentId');
    var gmrcDropdown     = document.getElementById('gmrcResidentDropdown');
    var gmrcResidentBtn  = document.getElementById('gmrcResidentBtn');
    var gmrcDateInput    = document.getElementById('gmrcDate');
    var gmrcPurposeInput = document.getElementById('gmrcPurpose');
    var gmrcPrintBtn     = document.getElementById('gmrcPrintBtn');
    var gmrcModalEl      = document.getElementById('gmrcModal');

    var gmrcSearchTimeout = null;

    if (gmrcModalEl) {
        gmrcModalEl.addEventListener('show.bs.modal', function () {
            if (gmrcNameInput)    gmrcNameInput.value    = '';
            if (gmrcIdInput)      gmrcIdInput.value      = '';
            if (gmrcDropdown)     { gmrcDropdown.innerHTML = ''; gmrcDropdown.style.display = 'none'; }
            if (gmrcDateInput)    gmrcDateInput.value    = getTodayDate();
            if (gmrcPurposeInput)  gmrcPurposeInput.value = '';
            clearGmrcError();
        });
    }

    if (gmrcNameInput) {
        gmrcNameInput.addEventListener('input', function () {
            if (gmrcIdInput) gmrcIdInput.value = '';
            clearGmrcError();
            var term = this.value.trim();
            clearTimeout(gmrcSearchTimeout);
            if (term.length < 1) { hideGmrcDropdown(); return; }
            gmrcSearchTimeout = setTimeout(function () { searchGmrcResidents(term, 'gmrc'); }, 250);
        });
        gmrcNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideGmrcDropdown(); });
    }

    if (gmrcResidentBtn) {
        gmrcResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (gmrcNameInput) { gmrcNameInput.value = ''; gmrcNameInput.focus(); }
            if (gmrcIdInput) gmrcIdInput.value = '';
            hideGmrcDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (gmrcDropdown && !e.target.closest('#gmrcModal .resident-search-wrap')) { hideGmrcDropdown(); }
    });

    function searchGmrcResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderGmrcDropdown(results, certType); }
                else { hideGmrcDropdown(); }
            })
            .catch(function () { hideGmrcDropdown(); });
    }

    function renderGmrcDropdown(residents, certType) {
        if (!gmrcDropdown) return;
        var limit = getCertificateLimit(certType);

        if (residents.length === 0) { gmrcDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; gmrcDropdown.style.display = 'block'; return; }
        gmrcDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;

            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectGmrcResident(r); });
            }
            gmrcDropdown.appendChild(item);
        });
        gmrcDropdown.style.display = 'block';
    }

    function selectGmrcResident(resident) {
        if (gmrcNameInput) gmrcNameInput.value = resident.full_name.trim();
        if (gmrcIdInput)   gmrcIdInput.value   = resident.id;
        hideGmrcDropdown();
        clearGmrcError();
    }

    function hideGmrcDropdown() {
        if (gmrcDropdown) { gmrcDropdown.style.display = 'none'; gmrcDropdown.innerHTML = ''; }
    }

    function clearGmrcError() {
        if (gmrcNameInput) gmrcNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.gmrc-error-msg');
        if (existing) existing.remove();
    }

    function showGmrcError(msg) {
        clearGmrcError();
        if (!gmrcNameInput) return;
        gmrcNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback gmrc-error-msg';
        err.textContent = msg;
        gmrcNameInput.closest('.resident-input-group').after(err);
    }

    if (gmrcPrintBtn) {
        gmrcPrintBtn.addEventListener('click', function () {
            var residentId = gmrcIdInput      ? gmrcIdInput.value.trim()      : '';
            var date       = gmrcDateInput    ? gmrcDateInput.value.trim()    : '';
            var purpose    = gmrcPurposeInput ? gmrcPurposeInput.value.trim() : '';

             if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'gmrc', purpose, function() {
                window.location.href = 'certifications/certificate-gmrc.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Oath of Undertaking Modal Logic (1-time only)
    // ============================================
    var oathNameInput    = document.getElementById('oathResidentName');
    var oathIdInput      = document.getElementById('oathResidentId');
    var oathDropdown     = document.getElementById('oathResidentDropdown');
    var oathResidentBtn  = document.getElementById('oathResidentBtn');
    var oathDateInput    = document.getElementById('oathDate');
    var oathPurposeInput = document.getElementById('oathPurpose');
    var oathPrintBtn     = document.getElementById('oathPrintBtn');
    var oathModalEl      = document.getElementById('oathModal');

    var oathSearchTimeout = null;

    if (oathModalEl) {
        oathModalEl.addEventListener('show.bs.modal', function () {
            if (oathNameInput)    oathNameInput.value    = '';
            if (oathIdInput)      oathIdInput.value      = '';
            if (oathDropdown)     { oathDropdown.innerHTML = ''; oathDropdown.style.display = 'none'; }
            if (oathDateInput)    oathDateInput.value    = getTodayDate();
            if (oathPurposeInput) oathPurposeInput.value = 'Oath of Undertaking (RA 11261)';
            clearOathError();
        });
    }

    if (oathNameInput) {
        oathNameInput.addEventListener('input', function () {
            if (oathIdInput) oathIdInput.value = '';
            clearOathError();
            var term = this.value.trim();
            clearTimeout(oathSearchTimeout);
            if (term.length < 1) { hideOathDropdown(); return; }
            oathSearchTimeout = setTimeout(function () { searchOathResidents(term, 'oath'); }, 250);
        });
        oathNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideOathDropdown(); });
    }

    if (oathResidentBtn) {
        oathResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (oathNameInput) { oathNameInput.value = ''; oathNameInput.focus(); }
            if (oathIdInput) oathIdInput.value = '';
            hideOathDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (oathDropdown && !e.target.closest('#oathModal .resident-search-wrap')) { hideOathDropdown(); }
    });

    function searchOathResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderOathDropdown(results, certType); }
                else { hideOathDropdown(); }
            })
            .catch(function () { hideOathDropdown(); });
    }

    function renderOathDropdown(residents, certType) {
        if (!oathDropdown) return;
        var limit = getCertificateLimit(certType);

        if (residents.length === 0) { oathDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; oathDropdown.style.display = 'block'; return; }
        oathDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;

            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectOathResident(r); });
            }
            oathDropdown.appendChild(item);
        });
        oathDropdown.style.display = 'block';
    }

    function selectOathResident(resident) {
        if (oathNameInput) oathNameInput.value = resident.full_name.trim();
        if (oathIdInput)   oathIdInput.value   = resident.id;
        hideOathDropdown();
        clearOathError();
    }

    function hideOathDropdown() {
        if (oathDropdown) { oathDropdown.style.display = 'none'; oathDropdown.innerHTML = ''; }
    }

    function clearOathError() {
        if (oathNameInput) oathNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.oath-error-msg');
        if (existing) existing.remove();
    }

    function showOathError(msg) {
        clearOathError();
        if (!oathNameInput) return;
        oathNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback oath-error-msg';
        err.textContent = msg;
        oathNameInput.closest('.resident-input-group').after(err);
    }

    if (oathPrintBtn) {
        oathPrintBtn.addEventListener('click', function () {
            var residentId = oathIdInput      ? oathIdInput.value.trim()      : '';
            var date       = oathDateInput    ? oathDateInput.value.trim()    : '';
            var purpose    = oathPurposeInput ? oathPurposeInput.value.trim() : '';
            
            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'oath', purpose, function() {
                window.location.href = 'certifications/certificate-oathofundertaking.php?' + params.toString();
            });
        });
    }


    // ============================================
// Certificate of Low Income Modal Logic
// ============================================

// 1. Map exactly to your HTML IDs
var lowIncomeNameInput     = document.getElementById('lowIncomeResidentName');
var lowIncomeIdInput       = document.getElementById('lowIncomeResidentId');
var lowIncomeDropdown      = document.getElementById('lowIncomeResidentDropdown');
var lowIncomeResidentBtn   = document.getElementById('lowIncomeResidentBtn');
var lowIncomeWorkInput     = document.getElementById('lowIncomeWork');
var lowIncomeYearInput     = document.getElementById('lowIncomeWorkYear');
var lowIncomeAmountInput   = document.getElementById('lowIncomeAmount'); // Matches your HTML
var lowIncomePurposeInput  = document.getElementById('lowIncomePurpose');
var lowIncomeDateInput     = document.getElementById('lowIncomeDate');
var lowIncomePrintBtn      = document.getElementById('lowIncomePrintBtn');
var lowIncomeModalEl       = document.getElementById('lowIncomeModal');

var lowIncomeSearchTimeout = null;

// 2. Reset Modal when it opens
if (lowIncomeModalEl) {
    lowIncomeModalEl.addEventListener('show.bs.modal', function () {
        if (lowIncomeNameInput)    lowIncomeNameInput.value    = '';
        if (lowIncomeIdInput)      lowIncomeIdInput.value      = '';
        if (lowIncomeWorkInput)    lowIncomeWorkInput.value    = '';
        if (lowIncomeAmountInput)  lowIncomeAmountInput.value  = '';
        if (lowIncomeYearInput)    lowIncomeYearInput.value    = new Date().getFullYear();
        if (lowIncomePurposeInput) lowIncomePurposeInput.value = '';
        
        if (lowIncomeDropdown) { 
            lowIncomeDropdown.innerHTML = ''; 
            lowIncomeDropdown.style.display = 'none'; 
        }
        
        if (lowIncomeDateInput) {
            lowIncomeDateInput.value = (typeof getTodayDate === 'function') ? getTodayDate() : new Date().toISOString().split('T')[0];
        }
        
        clearLowIncomeError();
    });
}

// 3. Input Search Logic
if (lowIncomeNameInput) {
    lowIncomeNameInput.addEventListener('input', function () {
        if (lowIncomeIdInput) lowIncomeIdInput.value = '';
        clearLowIncomeError();
        var term = this.value.trim();
        clearTimeout(lowIncomeSearchTimeout);
        
        if (term.length < 1) { 
            hideLowIncomeDropdown(); 
            return; 
        }
        
        lowIncomeSearchTimeout = setTimeout(function () { 
            searchLowIncomeResidents(term, 'lowincome'); 
        }, 250);
    });

    lowIncomeNameInput.addEventListener('keydown', function (e) { 
        if (e.key === 'Escape') hideLowIncomeDropdown(); 
    });
}

// 4. "Resident" Button Action (Clear search)
if (lowIncomeResidentBtn) {
    lowIncomeResidentBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        if (lowIncomeNameInput) { lowIncomeNameInput.value = ''; lowIncomeNameInput.focus(); }
        if (lowIncomeIdInput) lowIncomeIdInput.value = '';
        hideLowIncomeDropdown();
    });
}

// Hide dropdown when clicking outside
document.addEventListener('click', function (e) {
    if (lowIncomeDropdown && !e.target.closest('#lowIncomeModal .resident-search-wrap')) { 
        hideLowIncomeDropdown(); 
    }
});

// 5. Search API Call
function searchLowIncomeResidents(term, certType) {
    fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var results = data.residents || data.data;
            if (data.success && results) { 
                renderLowIncomeDropdown(results, certType); 
            } else { 
                hideLowIncomeDropdown(); 
            }
        })
        .catch(function () { hideLowIncomeDropdown(); });
}

// 6. Render Dropdown Results
function renderLowIncomeDropdown(residents, certType) {
    if (!lowIncomeDropdown) return;
    
    var limit = (typeof getCertificateLimit === 'function') ? getCertificateLimit(certType) : 5;

    if (residents.length === 0) { 
        lowIncomeDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; 
        lowIncomeDropdown.style.display = 'block'; 
        return; 
    }

    lowIncomeDropdown.innerHTML = '';
    residents.forEach(function (r) {
        var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
        var used = limitInfo ? limitInfo.used : 0;
        var remaining = limit - used;
        var isDisabled = remaining <= 0;

        var item = document.createElement('div');
        item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
        
        var limitBadge = remaining > 0 
            ? `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`
            : `<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>`;

        var safeEscape = (typeof escapeHtml === 'function') ? escapeHtml : function(str) { return str; };

        item.innerHTML = `
            <div class="resident-dropdown-info">
                <span class="resident-dropdown-id">${safeEscape(r.resident_id || '')}</span>
                <span class="resident-dropdown-name">${safeEscape(r.full_name.trim())}</span>
            </div>
            ${limitBadge}
        `;
        
        if (!isDisabled) {
            item.addEventListener('mousedown', function (e) { 
                e.preventDefault(); 
                selectLowIncomeResident(r); 
            });
        }
        lowIncomeDropdown.appendChild(item);
    });
    lowIncomeDropdown.style.display = 'block';
}

function selectLowIncomeResident(resident) {
    if (lowIncomeNameInput) lowIncomeNameInput.value = resident.full_name.trim();
    if (lowIncomeIdInput)   lowIncomeIdInput.value   = resident.id;
    hideLowIncomeDropdown();
    clearLowIncomeError();
}

function hideLowIncomeDropdown() {
    if (lowIncomeDropdown) { 
        lowIncomeDropdown.style.display = 'none'; 
        lowIncomeDropdown.innerHTML = ''; 
    }
}

// 7. Validation UI Helpers
function clearLowIncomeError() {
    if (lowIncomeNameInput)   lowIncomeNameInput.classList.remove('is-invalid');
    if (lowIncomeWorkInput)   lowIncomeWorkInput.classList.remove('is-invalid');
    if (lowIncomeAmountInput) lowIncomeAmountInput.classList.remove('is-invalid');
    if (lowIncomeDateInput)   lowIncomeDateInput.classList.remove('is-invalid');
    
    var existing = document.querySelector('.lowincome-error-msg');
    if (existing) existing.remove();
}

function showLowIncomeError(msg) {
    clearLowIncomeError();
    if (!lowIncomeNameInput) return;
    lowIncomeNameInput.classList.add('is-invalid');
    var err = document.createElement('div');
    err.className = 'invalid-feedback lowincome-error-msg';
    err.textContent = msg;
    
    var container = lowIncomeNameInput.closest('.resident-input-group') || lowIncomeNameInput.parentElement;
    container.after(err);
}

// 8. Generate Certificate / Print Button Logic
if (lowIncomePrintBtn) {
    lowIncomePrintBtn.addEventListener('click', function () {
        // Fetch all current values from inputs
        var residentId = lowIncomeIdInput      ? lowIncomeIdInput.value.trim()      : '';
        var workType   = lowIncomeWorkInput    ? lowIncomeWorkInput.value.trim()    : '';
        var workYear   = lowIncomeYearInput    ? lowIncomeYearInput.value.trim()    : '';
        var income     = lowIncomeAmountInput  ? lowIncomeAmountInput.value.trim()  : '';
        var purpose    = lowIncomePurposeInput ? lowIncomePurposeInput.value.trim() : '';
        var date       = lowIncomeDateInput    ? lowIncomeDateInput.value.trim()    : '';

         if (!residentId) { alert('Please select a resident from the list.'); return; }
         if (!workType) { alert('Please enter the work type.'); return; }
         if (!workYear) { alert('Please enter the work year.'); return; }
         if (!income) { alert('Please enter the income.'); return; }
         if (!date) { alert('Please select a date.'); return; }
         if (!purpose) { alert('Please enter the purpose type.'); return; }


        // Create URL Parameters including ALL new fields
        var params = new URLSearchParams({
            resident_id: residentId,
            work_type:   workType,
            work_year:   workYear,
            income:      income,
            purpose:     purpose,
            date:        date
        });
        
        // Log Request and Redirect to PHP file
        if (typeof logCertificateRequest === 'function') {
            logCertificateRequest(residentId, 'lowincome', purpose, function() {
                window.location.href = 'certifications/certificate-lowincome.php?' + params.toString();
            });
        } else {
            window.location.href = 'certifications/certificate-lowincome.php?' + params.toString();
        }
    });
}

    // ============================================
    // Certificate of Solo Parent Modal Logic
    // ============================================
    var soloParentNameInput = document.getElementById('soloParentResidentName');
    var soloParentIdInput = document.getElementById('soloParentResidentId');
    var soloParentDropdown = document.getElementById('soloParentResidentDropdown');
    var soloParentResidentBtn = document.getElementById('soloParentResidentBtn');
    var soloParentDateInput = document.getElementById('soloParentDate');
    var soloParentPurposeInput = document.getElementById('soloParentPurpose');
    var soloParentPrintBtn = document.getElementById('soloParentPrintBtn');
    var soloParentModalEl = document.getElementById('soloParentModal');
    var soloParentSearchTimeout = null;

    if (soloParentModalEl) {
        soloParentModalEl.addEventListener('show.bs.modal', function () {
            if (soloParentNameInput) soloParentNameInput.value = '';
            if (soloParentIdInput) soloParentIdInput.value = '';
            if (soloParentDropdown) { soloParentDropdown.innerHTML = ''; soloParentDropdown.style.display = 'none'; }
            if (soloParentDateInput) soloParentDateInput.value = getTodayDate();
            if (soloParentPurposeInput) soloParentPurposeInput.value = 'Solo Parent';
        });
    }

    if (soloParentNameInput) {
        soloParentNameInput.addEventListener('input', function () {
            if (soloParentIdInput) soloParentIdInput.value = '';
            var term = this.value.trim();
            clearTimeout(soloParentSearchTimeout);
            if (term.length < 1) { hideSoloParentDropdown(); return; }
            soloParentSearchTimeout = setTimeout(function () { searchSoloParentResidents(term, 'soloparent'); }, 250);
        });
    }

    if (soloParentResidentBtn) {
        soloParentResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (soloParentNameInput) { soloParentNameInput.value = ''; soloParentNameInput.focus(); }
            if (soloParentIdInput) soloParentIdInput.value = '';
            hideSoloParentDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (soloParentDropdown && !e.target.closest('#soloParentModal .resident-search-wrap')) { hideSoloParentDropdown(); }
    });

    function searchSoloParentResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderSoloParentDropdown(results, certType); }
                else { hideSoloParentDropdown(); }
            })
            .catch(function () { hideSoloParentDropdown(); });
    }

    function renderSoloParentDropdown(residents, certType) {
        if (!soloParentDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { soloParentDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; soloParentDropdown.style.display = 'block'; return; }
        soloParentDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectSoloParentResident(r); });
            }
            soloParentDropdown.appendChild(item);
        });
        soloParentDropdown.style.display = 'block';
    }

    function selectSoloParentResident(resident) {
        if (soloParentNameInput) soloParentNameInput.value = resident.full_name.trim();
        if (soloParentIdInput) soloParentIdInput.value = resident.id;
        hideSoloParentDropdown();
    }

    function hideSoloParentDropdown() {
        if (soloParentDropdown) { soloParentDropdown.style.display = 'none'; soloParentDropdown.innerHTML = ''; }
    }

    if (soloParentPrintBtn) {
        soloParentPrintBtn.addEventListener('click', function () {
            var residentId = soloParentIdInput ? soloParentIdInput.value.trim() : '';
            var date = soloParentDateInput ? soloParentDateInput.value.trim() : '';
            var purpose = soloParentPurposeInput ? soloParentPurposeInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }
            
            
            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            logCertificateRequest(residentId, 'soloparent', purpose, function() {
                window.location.href = 'certifications/certificate-soloparent.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Vessel Docking Modal Logic
    // ============================================
    var vesselDockingNameInput = document.getElementById('vesselDockingResidentName');
    var vesselDockingIdInput = document.getElementById('vesselDockingResidentId');
    var vesselDockingDropdown = document.getElementById('vesselDockingResidentDropdown');
    var vesselDockingResidentBtn = document.getElementById('vesselDockingResidentBtn');
    var vesselDockingVesselNameInput = document.getElementById('vesselDockingVesselName');
    var vesselDockingFromDateInput = document.getElementById('vesselDockingFromDate');
    var vesselDockingToDateInput = document.getElementById('vesselDockingToDate');
    var vesselDockingDateInput = document.getElementById('vesselDockingDate');
    var vesselDockingPrintBtn = document.getElementById('vesselDockingPrintBtn');
    var vesselDockingModalEl = document.getElementById('vesselDockingModal');

    var vesselDockingSearchTimeout = null;

    if (vesselDockingModalEl) {
        vesselDockingModalEl.addEventListener('show.bs.modal', function () {
            if (vesselDockingNameInput) vesselDockingNameInput.value = '';
            if (vesselDockingIdInput) vesselDockingIdInput.value = '';
            if (vesselDockingDropdown) { vesselDockingDropdown.innerHTML = ''; vesselDockingDropdown.style.display = 'none'; }
            if (vesselDockingVesselNameInput) vesselDockingVesselNameInput.value = '';
            if (vesselDockingFromDateInput) vesselDockingFromDateInput.value = getTodayDate();
            if (vesselDockingToDateInput) vesselDockingToDateInput.value = getTodayDate();
            if (vesselDockingDateInput) vesselDockingDateInput.value = getTodayDate();
        });
    }

    if (vesselDockingNameInput) {
        vesselDockingNameInput.addEventListener('input', function () {
            if (vesselDockingIdInput) vesselDockingIdInput.value = '';
            var term = this.value.trim();
            clearTimeout(vesselDockingSearchTimeout);
            if (term.length < 1) { hideVesselDockingDropdown(); return; }
            vesselDockingSearchTimeout = setTimeout(function () { searchVesselDockingResidents(term, 'vesseldocking'); }, 250);
        });
    }

    if (vesselDockingResidentBtn) {
        vesselDockingResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (vesselDockingNameInput) { vesselDockingNameInput.value = ''; vesselDockingNameInput.focus(); }
            if (vesselDockingIdInput) vesselDockingIdInput.value = '';
            hideVesselDockingDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (vesselDockingDropdown && vesselDockingDropdown.parentElement && !vesselDockingDropdown.parentElement.contains(e.target)) { hideVesselDockingDropdown(); }
    });

    function searchVesselDockingResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderVesselDockingDropdown(results, certType); }
                else { hideVesselDockingDropdown(); }
            })
            .catch(function () { hideVesselDockingDropdown(); });
    }

    function renderVesselDockingDropdown(residents, certType) {
        if (!vesselDockingDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { vesselDockingDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; vesselDockingDropdown.style.display = 'block'; return; }
        vesselDockingDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectVesselDockingResident(r); });
            }
            vesselDockingDropdown.appendChild(item);
        });
        vesselDockingDropdown.style.display = 'block';
    }

    function selectVesselDockingResident(resident) {
        if (vesselDockingNameInput) vesselDockingNameInput.value = resident.full_name.trim();
        if (vesselDockingIdInput) vesselDockingIdInput.value = resident.id;
        hideVesselDockingDropdown();
    }

    function hideVesselDockingDropdown() {
        if (vesselDockingDropdown) { vesselDockingDropdown.style.display = 'none'; vesselDockingDropdown.innerHTML = ''; }
    }

    if (vesselDockingPrintBtn) {
        vesselDockingPrintBtn.addEventListener('click', function () {
            var residentId = vesselDockingIdInput ? vesselDockingIdInput.value.trim() : '';
            var vesselName = vesselDockingVesselNameInput ? vesselDockingVesselNameInput.value.trim() : '';
            var fromDate = vesselDockingFromDateInput ? vesselDockingFromDateInput.value.trim() : '';
            var toDate = vesselDockingToDateInput ? vesselDockingToDateInput.value.trim() : '';
            var date = vesselDockingDateInput ? vesselDockingDateInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!vesselName) { alert('Please enter the vessel name.'); return; }
            if (!fromDate) { alert('Please select a from date.'); return; }
            if (!toDate) { alert('Please select a to date.'); return; }
            if (!date) { alert('Please select a date.'); return; }

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                vesselname: vesselName,
                fromdate: fromDate,
                todate: toDate,
                date: date 
            });
            logCertificateRequest(residentId, 'vesseldocking', 'Vessel Docking Certification', function() {
                window.location.href = 'certifications/certificate-vesseldocking.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Business Permit Modal Logic
    // ============================================
    var businessPermitNameInput = document.getElementById('businessPermitResidentName');
    var businessPermitIdInput = document.getElementById('businessPermitResidentId');
    var businessPermitDropdown = document.getElementById('businessPermitResidentDropdown');
    var businessPermitResidentBtn = document.getElementById('businessPermitResidentBtn');
    var businessPermitBusinessNameInput = document.getElementById('businessPermitBusinessName');
    var businessPermitBusinessAddressInput = document.getElementById('businessPermitBusinessAddress');
    var businessPermitNatureInput = document.getElementById('businessPermitNature');
    var businessPermitDateInput = document.getElementById('businessPermitDate');
    var businessPermitPrintBtn = document.getElementById('businessPermitPrintBtn');
    var businessPermitModalEl = document.getElementById('businessPermitModal');

    var businessPermitSearchTimeout = null;

    if (businessPermitModalEl) {
        businessPermitModalEl.addEventListener('show.bs.modal', function () {
            if (businessPermitNameInput) businessPermitNameInput.value = '';
            if (businessPermitIdInput) businessPermitIdInput.value = '';
            if (businessPermitDropdown) { businessPermitDropdown.innerHTML = ''; businessPermitDropdown.style.display = 'none'; }
            if (businessPermitBusinessNameInput) businessPermitBusinessNameInput.value = '';
            if (businessPermitBusinessAddressInput) businessPermitBusinessAddressInput.value = '';
            if (businessPermitNatureInput) businessPermitNatureInput.value = '';
            if (businessPermitDateInput) businessPermitDateInput.value = getTodayDate();
        });
    }

    if (businessPermitNameInput) {
        businessPermitNameInput.addEventListener('input', function () {
            if (businessPermitIdInput) businessPermitIdInput.value = '';
            var term = this.value.trim();
            if (term.length < 1) { hideBusinessPermitDropdown(); return; }
            setTimeout(function () { searchBusinessPermitResidents(term, 'businesspermit'); }, 250);
        });
    }

    function searchBusinessPermitResidents(term, certType) {
          fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBusinessPermitDropdown(results, certType); }
                else { hideBusinessPermitDropdown(); }
            })
            .catch(function () { hideBusinessPermitDropdown(); });
    }

    function renderBusinessPermitDropdown(residents, certType) {
        if (!businessPermitDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { businessPermitDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; businessPermitDropdown.style.display = 'block'; return; }
        businessPermitDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBusinessPermitResident(r); });
            }
            businessPermitDropdown.appendChild(item);
        });
        businessPermitDropdown.style.display = 'block';
    }

    function selectBusinessPermitResident(resident) {
        if (businessPermitNameInput) businessPermitNameInput.value = resident.full_name.trim();
        if (businessPermitIdInput) businessPermitIdInput.value = resident.id;
        hideBusinessPermitDropdown();
    }

    function hideBusinessPermitDropdown() {
        if (businessPermitDropdown) { businessPermitDropdown.style.display = 'none'; businessPermitDropdown.innerHTML = ''; }
    }

    if (businessPermitPrintBtn) {
        businessPermitPrintBtn.addEventListener('click', function () {
            var residentId = businessPermitIdInput ? businessPermitIdInput.value.trim() : '';
            var businessName = businessPermitBusinessNameInput ? businessPermitBusinessNameInput.value.trim() : '';
            var businessAddress = businessPermitBusinessAddressInput ? businessPermitBusinessAddressInput.value.trim() : '';
            var nature = businessPermitNatureInput ? businessPermitNatureInput.value.trim() : '';
            var date = businessPermitDateInput ? businessPermitDateInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!businessName) { alert('Please enter the business name.'); return; }
            if (!businessAddress) { alert('Please enter the business address.'); return; }
            if (!nature) { alert('Please enter the nature of business.'); return; }
            if (!date) { alert('Please select a date.'); return; }

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                business_name: businessName,
                business_address: businessAddress,
                nature: nature,
                date: date 
            });
            logCertificateRequest(residentId, 'businesspermit', nature || 'Business Permit', function() {
                window.location.href = 'certifications/certificate-businesspermit.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Barangay Business Clearance Modal Logic
    // ============================================
    var brgyBusinessNameInput = document.getElementById('brgyBusinessClearanceResidentName');
    var brgyBusinessIdInput = document.getElementById('brgyBusinessClearanceResidentId');
    var brgyBusinessDropdown = document.getElementById('brgyBusinessClearanceResidentDropdown');
    var brgyBusinessResidentBtn = document.getElementById('brgyBusinessClearanceResidentBtn');
    var brgyBusinessClearanceBusinessNameInput = document.getElementById('brgyBusinessClearanceBusinessName');
    var brgyBusinessClearanceBusinessAddressInput = document.getElementById('brgyBusinessClearanceBusinessAddress');
    var brgyBusinessClearanceNatureInput = document.getElementById('brgyBusinessClearanceNature');
    var brgyBusinessClearanceDateInput = document.getElementById('brgyBusinessClearanceDate');
    var brgyBusinessClearancePrintBtn = document.getElementById('brgyBusinessClearancePrintBtn');
    var brgyBusinessModalEl = document.getElementById('brgyBusinessClearanceModal');

    var brgyBusinessSearchTimeout = null;

    if (brgyBusinessModalEl) {
        brgyBusinessModalEl.addEventListener('show.bs.modal', function () {
            if (brgyBusinessNameInput) brgyBusinessNameInput.value = '';
            if (brgyBusinessIdInput) brgyBusinessIdInput.value = '';
            if (brgyBusinessDropdown) { brgyBusinessDropdown.innerHTML = ''; brgyBusinessDropdown.style.display = 'none'; }
            if (brgyBusinessClearanceBusinessNameInput) brgyBusinessClearanceBusinessNameInput.value = '';
            if (brgyBusinessClearanceBusinessAddressInput) brgyBusinessClearanceBusinessAddressInput.value = '';
            if (brgyBusinessClearanceNatureInput) brgyBusinessClearanceNatureInput.value = '';
            if (brgyBusinessClearanceDateInput) brgyBusinessClearanceDateInput.value = getTodayDate();
        });
    }

    if (brgyBusinessNameInput) {
        brgyBusinessNameInput.addEventListener('input', function () {
            if (brgyBusinessIdInput) brgyBusinessIdInput.value = '';
            var term = this.value.trim();
            if (term.length < 1) { hideBrgyBusinessDropdown(); return; }
            setTimeout(function () { searchBrgyBusinessResidents(term, 'brgybusinessclearance'); }, 250);
        });
    }

    function searchBrgyBusinessResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBrgyBusinessDropdown(results, certType); }
                else { hideBrgyBusinessDropdown(); }
            })
            .catch(function () { hideBrgyBusinessDropdown(); });
    }

    function renderBrgyBusinessDropdown(residents, certType) {
        if (!brgyBusinessDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { brgyBusinessDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; brgyBusinessDropdown.style.display = 'block'; return; }
        brgyBusinessDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBrgyBusinessResident(r); });
            }
            brgyBusinessDropdown.appendChild(item);
        });
        brgyBusinessDropdown.style.display = 'block';
    }

    function selectBrgyBusinessResident(resident) {
        if (brgyBusinessNameInput) brgyBusinessNameInput.value = resident.full_name.trim();
        if (brgyBusinessIdInput) brgyBusinessIdInput.value = resident.id;
        hideBrgyBusinessDropdown();
    }

    function hideBrgyBusinessDropdown() {
        if (brgyBusinessDropdown) { brgyBusinessDropdown.style.display = 'none'; brgyBusinessDropdown.innerHTML = ''; }
    }

    if (brgyBusinessClearancePrintBtn) {
        brgyBusinessClearancePrintBtn.addEventListener('click', function () {
            var residentId = brgyBusinessIdInput ? brgyBusinessIdInput.value.trim() : '';
            var businessName = brgyBusinessClearanceBusinessNameInput ? brgyBusinessClearanceBusinessNameInput.value.trim() : '';
            var businessAddress = brgyBusinessClearanceBusinessAddressInput ? brgyBusinessClearanceBusinessAddressInput.value.trim() : '';
            var nature = brgyBusinessClearanceNatureInput ? brgyBusinessClearanceNatureInput.value.trim() : '';
            var date = brgyBusinessClearanceDateInput ? brgyBusinessClearanceDateInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!businessName) { alert('Please enter the business name.'); return; }
            if (!businessAddress) { alert('Please enter the business address.'); return; }
            if (!nature) { alert('Please enter the nature of business.'); return; }
            if (!date) { alert('Please select a date.'); return; }

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                business_name: businessName,
                business_address: businessAddress,
                nature: nature,
                date: date 
            });
            logCertificateRequest(residentId, 'brgybusinessclearance', nature || 'Barangay Business Clearance', function() {
                window.location.href = 'certifications/certificate-brgybusinessclearance.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Barangay Clearance Modal Logic
    // ============================================
    var brgyClearanceNameInput = document.getElementById('brgyClearanceResidentName');
    var brgyClearanceIdInput = document.getElementById('brgyClearanceResidentId');
    var brgyClearanceDropdown = document.getElementById('brgyClearanceResidentDropdown');
    var brgyClearanceResidentBtn = document.getElementById('brgyClearanceResidentBtn');
    var brgyClearanceDateInput = document.getElementById('brgyClearanceDate');
    var brgyClearancePurposeInput = document.getElementById('brgyClearancePurpose');
    var brgyClearancePrintBtn = document.getElementById('brgyClearancePrintBtn');
    var brgyClearanceModalEl = document.getElementById('brgyClearanceModal');

    var brgyClearanceSearchTimeout = null;

    if (brgyClearanceModalEl) {
        brgyClearanceModalEl.addEventListener('show.bs.modal', function () {
            if (brgyClearanceNameInput) brgyClearanceNameInput.value = '';
            if (brgyClearanceIdInput) brgyClearanceIdInput.value = '';
            if (brgyClearanceDropdown) { brgyClearanceDropdown.innerHTML = ''; brgyClearanceDropdown.style.display = 'none'; }
            if (brgyClearanceDateInput) brgyClearanceDateInput.value = getTodayDate();
            if (brgyClearancePurposeInput) brgyClearancePurposeInput.value = '';
        });
    }

    if (brgyClearanceNameInput) {
        brgyClearanceNameInput.addEventListener('input', function () {
            if (brgyClearanceIdInput) brgyClearanceIdInput.value = '';
            var term = this.value.trim();
            if (term.length < 1) { hideBrgyClearanceDropdown(); return; }
            setTimeout(function () { searchBrgyClearanceResidents(term, 'brgyclearance'); }, 250);
        });
    }

    function searchBrgyClearanceResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBrgyClearanceDropdown(results, certType); }
                else { hideBrgyClearanceDropdown(); }
            })
            .catch(function () { hideBrgyClearanceDropdown(); });
    }

    function renderBrgyClearanceDropdown(residents, certType) {
        if (!brgyClearanceDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { brgyClearanceDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; brgyClearanceDropdown.style.display = 'block'; return; }
        brgyClearanceDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit badge bg-danger text-white">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBrgyClearanceResident(r); });
            }
            brgyClearanceDropdown.appendChild(item);
        });
        brgyClearanceDropdown.style.display = 'block';
    }

    function selectBrgyClearanceResident(resident) {
        if (brgyClearanceNameInput) brgyClearanceNameInput.value = resident.full_name.trim();
        if (brgyClearanceIdInput) brgyClearanceIdInput.value = resident.id;
        hideBrgyClearanceDropdown();
    }

    function hideBrgyClearanceDropdown() {
        if (brgyClearanceDropdown) { brgyClearanceDropdown.style.display = 'none'; brgyClearanceDropdown.innerHTML = ''; }
    }

    if (brgyClearancePrintBtn) {
        brgyClearancePrintBtn.addEventListener('click', function () {
            var residentId = brgyClearanceIdInput ? brgyClearanceIdInput.value.trim() : '';
            var date = brgyClearanceDateInput ? brgyClearanceDateInput.value.trim() : '';
            var purpose = brgyClearancePurposeInput ? brgyClearancePurposeInput.value.trim() : '';

            if (!residentId) { alert('Please select a resident from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }
            if (!purpose) { alert('Please enter the purpose type.'); return; }


            var params = new URLSearchParams({ 
                resident_id: residentId, 
                date: date,
                purpose: purpose 
            });
            logCertificateRequest(residentId, 'brgyclearance', purpose || 'Barangay Clearance', function() {
                window.location.href = 'certifications/certificate-barangayclearance.php?' + params.toString();
            });
        });
    }

    // ============================================
    // Registration of Birth Certificate (RBC) Modal Logic
    // ============================================
    var rbcNameInput = document.getElementById('rbcResidentName');
    var rbcIdInput = document.getElementById('rbcResidentId');
    var rbcDropdown = document.getElementById('rbcResidentDropdown');
    var rbcResidentBtn = document.getElementById('rbcResidentBtn');
    var rbcChildNameInput = document.getElementById('rbcChildName');
    var rbcChildIdInput = document.getElementById('rbcChildId');
    var rbcChildDropdown = document.getElementById('rbcChildDropdown');
    var rbcDateInput = document.getElementById('rbcDate');
    var rbcPurposeInput = document.getElementById('rbcPurpose');
    var rbcPrintBtn = document.getElementById('rbcPrintBtn');
    var rbcModalEl = document.getElementById('rbcModal');

    var rbcSearchTimeout = null;
    var rbcChildSearchTimeout = null;
    var rbcSelectedParentId = null;

    if (rbcModalEl) {
        rbcModalEl.addEventListener('show.bs.modal', function () {
            if (rbcNameInput) rbcNameInput.value = '';
            if (rbcIdInput) rbcIdInput.value = '';
            if (rbcDropdown) { rbcDropdown.innerHTML = ''; rbcDropdown.style.display = 'none'; }
            if (rbcChildNameInput) rbcChildNameInput.value = '';
            if (rbcChildIdInput) rbcChildIdInput.value = '';
            if (rbcChildDropdown) { rbcChildDropdown.innerHTML = ''; rbcChildDropdown.style.display = 'none'; }
            if (rbcDateInput) rbcDateInput.value = getTodayDate();
            if (rbcPurposeInput) rbcPurposeInput.value = 'Birth Certificate Registration';
            // Reset selected parent ID when modal opens
            rbcSelectedParentId = null;
        });
    }

    if (rbcNameInput) {
        rbcNameInput.addEventListener('input', function () {
            if (rbcIdInput) rbcIdInput.value = '';
            var term = this.value.trim();
            if (term.length < 1) { hideRbcDropdown(); return; }
            setTimeout(function () { searchRbcResidents(term, 'rbc'); }, 250);
        });
    }

    function searchRbcResidents(term, certType) {
         fetch('model/search_residents.php?search=' + encodeURIComponent(term) + '&certificate_type=' + encodeURIComponent(certType) + '&filter=adult')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderRbcDropdown(results, certType); }
                else { hideRbcDropdown(); }
            })
            .catch(function () { hideRbcDropdown(); });
    }

    function renderRbcDropdown(residents, certType) {
        if (!rbcDropdown) return;
        var limit = getCertificateLimit(certType);
        if (residents.length === 0) { rbcDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; rbcDropdown.style.display = 'block'; return; }
        rbcDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var limitInfo = r.resident_limits ? r.resident_limits[certType] : null;
            var used = limitInfo ? limitInfo.used : 0;
            var remaining = limit - used;
            var isDisabled = remaining <= 0;
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item' + (isDisabled ? ' disabled' : '');
            
            var limitBadge = '';
            if (remaining > 0) {
                limitBadge = `<span class="resident-dropdown-limit">${remaining}/${limit} available</span>`;
            } else {
                limitBadge = '<span class="resident-dropdown-limit">Limit reached</span>';
            }

            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
                ${limitBadge}
            `;
            
            if (!isDisabled) {
                item.addEventListener('mousedown', function (e) { e.preventDefault(); selectRbcResident(r); });
            }
            rbcDropdown.appendChild(item);
        });
        rbcDropdown.style.display = 'block';
    }

    function selectRbcResident(resident) {
        if (rbcNameInput) rbcNameInput.value = resident.full_name.trim();
        if (rbcIdInput) rbcIdInput.value = resident.id;
        // Store the selected parent ID to exclude from child search
        rbcSelectedParentId = resident.id;
        hideRbcDropdown();
    }

    function hideRbcDropdown() {
        if (rbcDropdown) { rbcDropdown.style.display = 'none'; rbcDropdown.innerHTML = ''; }
    }

    // Child search functionality
    if (rbcChildNameInput) {
        rbcChildNameInput.addEventListener('input', function () {
            if (rbcChildIdInput) rbcChildIdInput.value = '';
            var term = this.value.trim();
            clearTimeout(rbcChildSearchTimeout);
            if (term.length < 1) { hideRbcChildDropdown(); return; }
            rbcChildSearchTimeout = setTimeout(function () { searchRbcChildResidents(term); }, 250);
        });
    }

    var rbcChildResidentBtn = document.getElementById('rbcChildResidentBtn');
    if (rbcChildResidentBtn) {
        rbcChildResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (rbcChildNameInput) { rbcChildNameInput.value = ''; rbcChildNameInput.focus(); }
            if (rbcChildIdInput) rbcChildIdInput.value = '';
            hideRbcChildDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (rbcChildDropdown && rbcChildDropdown.parentElement && !rbcChildDropdown.parentElement.contains(e.target)) { hideRbcChildDropdown(); }
    });

    function searchRbcChildResidents(term) {
        // Build query with exclude_resident_id if parent is selected
        var url = 'model/search_residents.php?search=' + encodeURIComponent(term);
        if (rbcSelectedParentId) {
            url += '&exclude_resident_id=' + encodeURIComponent(rbcSelectedParentId);
        }
        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderRbcChildDropdown(results); }
                else { hideRbcChildDropdown(); }
            })
            .catch(function () { hideRbcChildDropdown(); });
    }

    function renderRbcChildDropdown(residents) {
        if (!rbcChildDropdown) return;
        if (residents.length === 0) { rbcChildDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; rbcChildDropdown.style.display = 'block'; return; }
        rbcChildDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = `
                <div class="resident-dropdown-info">
                    <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
                    <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                </div>
            `;
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectRbcChildResident(r); });
            rbcChildDropdown.appendChild(item);
        });
        rbcChildDropdown.style.display = 'block';
    }

    function selectRbcChildResident(resident) {
        if (rbcChildNameInput) rbcChildNameInput.value = resident.full_name.trim();
        if (rbcChildIdInput) rbcChildIdInput.value = resident.id;
        hideRbcChildDropdown();
    }

    function hideRbcChildDropdown() {
        if (rbcChildDropdown) { rbcChildDropdown.style.display = 'none'; rbcChildDropdown.innerHTML = ''; }
    }

    if (rbcPrintBtn) {
        rbcPrintBtn.addEventListener('click', function () {
            var residentId = rbcIdInput ? rbcIdInput.value.trim() : '';
            var childId = rbcChildIdInput ? rbcChildIdInput.value.trim() : '';
            var date = rbcDateInput ? rbcDateInput.value.trim() : '';
            var purpose = rbcPurposeInput ? rbcPurposeInput.value.trim() : '';

            if (!residentId) { alert('Please select a parent/applicant from the list.'); return; }
            if (!childId) { alert('Please select a child from the list.'); return; }
            if (!date) { alert('Please select a date.'); return; }

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                child_id: childId,
                date: date 
            });
            logCertificateRequest(residentId, 'rbc', purpose || 'Birth Certificate Registration', function() {
                window.location.href = 'certifications/certificate-RBC.php?' + params.toString();
            });
        });
    }

    function logCertificateRequest(residentId, certType, purpose, callback) {
        var formData = new FormData();
        formData.append('resident_id', residentId);
        formData.append('certificate_type', certType);
        formData.append('purpose', purpose || '');

        fetch('model/save_certificate_log.php', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (callback) callback();
        })
        .catch(function(err) {
            console.error('Error logging request:', err);
            if (callback) callback();
        });
    }

});

// ============================================
// Animation keyframes
// ============================================
(function () {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0);    opacity: 1; }
            to   { transform: translateX(100%); opacity: 0; }
        }
    `;
document.head.appendChild(style);

})();