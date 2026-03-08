/**
 * Certificates Page JavaScript
 * Handles search, card navigation, and certificate request modals
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // DOM Elements
    // ============================================
    const searchInput      = document.getElementById('searchInput');
    const clearSearchBtn   = document.getElementById('clearSearch');
    const refreshBtn       = document.getElementById('refreshBtn');
    const certificatesGrid = document.getElementById('certificatesGrid');

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
        showEmptyState(visible === 0 && cards.length > 0);
    }

    function showEmptyState(show) {
        let emptyState = certificatesGrid ? certificatesGrid.querySelector('.empty-state-search') : null;
        if (show && !emptyState && certificatesGrid) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state empty-state-search';
            emptyState.innerHTML = `
                <i class="fas fa-search"></i>
                <p>No certificates found</p>
                <p class="empty-subtitle">Try a different search term</p>
            `;
            certificatesGrid.appendChild(emptyState);
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
    // — Opens a modal if data-modal is set
    // — Navigates directly if data-link is set
    // ============================================
    document.querySelectorAll('.certificate-card-clickable').forEach(function (card) {
        card.addEventListener('click', function () {
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

    let searchTimeout = null;

    // Reset modal fields when it opens
    if (indigencyModalEl) {
        indigencyModalEl.addEventListener('show.bs.modal', function () {
            if (residentNameInput) residentNameInput.value = '';
            if (residentIdInput)   residentIdInput.value   = '';
            if (residentDropdown)  { residentDropdown.innerHTML = ''; residentDropdown.style.display = 'none'; }
            if (dateInput)         dateInput.value = getTodayDate();
            if (assistanceInput)   assistanceInput.value = '';
            clearResidentError();
        });
    }

    // Resident name input — live search
    if (residentNameInput) {
        residentNameInput.addEventListener('input', function () {
            // Clear previously selected ID when user types again
            if (residentIdInput) residentIdInput.value = '';
            clearResidentError();

            const term = this.value.trim();
            clearTimeout(searchTimeout);

            if (term.length < 1) {
                hideDropdown();
                return;
            }

            searchTimeout = setTimeout(function () {
                searchResidents(term);
            }, 250);
        });

        residentNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideDropdown();
        });
    }

    // RESIDENT button — clears selection and focuses input
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

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (residentDropdown && !e.target.closest('.resident-search-wrap')) {
            hideDropdown();
        }
    });

    // Search residents via AJAX
    function searchResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) {
                    renderDropdown(results);
                } else {
                    hideDropdown();
                }
            })
            .catch(function () { hideDropdown(); });
    }

    // Render autocomplete dropdown
    function renderDropdown(residents) {
        if (!residentDropdown) return;

        if (residents.length === 0) {
            residentDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            residentDropdown.style.display = 'block';
            return;
        }

        residentDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = `
                <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
            `;
            item.addEventListener('mousedown', function (e) {
                e.preventDefault(); // prevent blur before click
                selectResident(r);
            });
            residentDropdown.appendChild(item);
        });

        residentDropdown.style.display = 'block';
    }

    // Select a resident from dropdown
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

    // ============================================
    // Print Certificate Button
    // ============================================
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            const residentId  = residentIdInput  ? residentIdInput.value.trim()  : '';
            const date        = dateInput        ? dateInput.value.trim()        : '';
            const assistance  = assistanceInput  ? assistanceInput.value.trim()  : '';

            // Validate resident
            if (!residentId) {
                showResidentError('Please select a resident from the list.');
                if (residentNameInput) residentNameInput.focus();
                return;
            }

            // Validate date
            if (!date) {
                if (dateInput) {
                    dateInput.classList.add('is-invalid');
                    dateInput.focus();
                }
                return;
            } else {
                if (dateInput) dateInput.classList.remove('is-invalid');
            }

            // Build URL and navigate
            const params = new URLSearchParams({
                resident_id: residentId,
                date:        date,
                assistance:  assistance
            });

            window.location.href = 'certifications/certificate-indigency.php?' + params.toString();
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

    // Reset modal fields when it opens
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

    // Resident name input — live search
    if (residencyNameInput) {
        residencyNameInput.addEventListener('input', function () {
            if (residencyIdInput) residencyIdInput.value = '';
            clearResidencyError();

            const term = this.value.trim();
            clearTimeout(residencySearchTimeout);

            if (term.length < 1) {
                hideResidencyDropdown();
                return;
            }

            residencySearchTimeout = setTimeout(function () {
                searchResidencyResidents(term);
            }, 250);
        });

        residencyNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideResidencyDropdown();
        });
    }

    // RESIDENT button — clears selection and focuses input
    if (residencyResidentBtn) {
        residencyResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (residencyNameInput) {
                residencyNameInput.value = '';
                residencyNameInput.focus();
            }
            if (residencyIdInput) residencyIdInput.value = '';
            hideResidencyDropdown();
        });
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (residencyDropdown && !e.target.closest('#residencyModal .resident-search-wrap')) {
            hideResidencyDropdown();
        }
    });

    // Search residents via AJAX
    function searchResidencyResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) {
                    renderResidencyDropdown(results);
                } else {
                    hideResidencyDropdown();
                }
            })
            .catch(function () { hideResidencyDropdown(); });
    }

    // Render autocomplete dropdown
    function renderResidencyDropdown(residents) {
        if (!residencyDropdown) return;

        if (residents.length === 0) {
            residencyDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            residencyDropdown.style.display = 'block';
            return;
        }

        residencyDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = `
                <span class="resident-dropdown-name">${escapeHtml(r.full_name.trim())}</span>
                <span class="resident-dropdown-id">${escapeHtml(r.resident_id || '')}</span>
            `;
            item.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectResidencyResident(r);
            });
            residencyDropdown.appendChild(item);
        });

        residencyDropdown.style.display = 'block';
    }

    // Select a resident from dropdown
    function selectResidencyResident(resident) {
        if (residencyNameInput) residencyNameInput.value = resident.full_name.trim();
        if (residencyIdInput)   residencyIdInput.value   = resident.id;
        hideResidencyDropdown();
        clearResidencyError();
    }

    function hideResidencyDropdown() {
        if (residencyDropdown) {
            residencyDropdown.style.display = 'none';
            residencyDropdown.innerHTML = '';
        }
    }

    // Print Certificate Button
    if (residencyPrintBtn) {
        residencyPrintBtn.addEventListener('click', function () {
            const residentId = residencyIdInput      ? residencyIdInput.value.trim()      : '';
            const date       = residencyDateInput    ? residencyDateInput.value.trim()    : '';
            const purpose    = residencyPurposeInput ? residencyPurposeInput.value.trim() : '';

            // Validate resident
            if (!residentId) {
                showResidencyError('Please select a resident from the list.');
                if (residencyNameInput) residencyNameInput.focus();
                return;
            }

            // Validate date
            if (!date) {
                if (residencyDateInput) {
                    residencyDateInput.classList.add('is-invalid');
                    residencyDateInput.focus();
                }
                return;
            } else {
                if (residencyDateInput) residencyDateInput.classList.remove('is-invalid');
            }

            // Build URL and navigate
            const params = new URLSearchParams({
                resident_id: residentId,
                date:        date,
                purpose:     purpose
            });

            window.location.href = 'certifications/certificate-residency.php?' + params.toString();
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
    const fishingIdInput     = document.getElementById('fishingResidentId');
    const fishingDropdown    = document.getElementById('fishingResidentDropdown');
    const fishingResidentBtn = document.getElementById('fishingResidentBtn');
    const fishingBoatNameInput = document.getElementById('fishingBoatName');
    const fishingDateInput   = document.getElementById('fishingDate');
    const fishingPurposeInput = document.getElementById('fishingPurpose');
    const fishingPrintBtn    = document.getElementById('fishingPrintBtn');
    const fishingModalEl     = document.getElementById('fishingClearanceModal');

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
            if (term.length < 1) {
                hideFishingDropdown();
                return;
            }
            fishingSearchTimeout = setTimeout(function () {
                searchFishingResidents(term);
            }, 250);
        });
        fishingNameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideFishingDropdown();
        });
    }

    if (fishingResidentBtn) {
        fishingResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (fishingNameInput) {
                fishingNameInput.value = '';
                fishingNameInput.focus();
            }
            if (fishingIdInput) fishingIdInput.value = '';
            hideFishingDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (fishingDropdown && !e.target.closest('#fishingClearanceModal .resident-search-wrap')) {
            hideFishingDropdown();
        }
    });

    function searchFishingResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const results = data.residents || data.data;
                if (data.success && results) {
                    renderFishingDropdown(results);
                } else {
                    hideFishingDropdown();
                }
            })
            .catch(function () { hideFishingDropdown(); });
    }

    function renderFishingDropdown(residents) {
        if (!fishingDropdown) return;
        if (residents.length === 0) {
            fishingDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>';
            fishingDropdown.style.display = 'block';
            return;
        }
        fishingDropdown.innerHTML = '';
        residents.forEach(function (r) {
            const item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectFishingResident(r); });
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
            window.location.href = 'certifications/certificate-fishingclearance.php?' + params.toString();
        });
    }

    // ============================================
    // First Time Jobseeker Modal Logic
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
            ftJobseekerSearchTimeout = setTimeout(function () { searchFtJobseekerResidents(term); }, 250);
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

    function searchFtJobseekerResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderFtJobseekerDropdown(results); }
                else { hideFtJobseekerDropdown(); }
            })
            .catch(function () { hideFtJobseekerDropdown(); });
    }

    function renderFtJobseekerDropdown(residents) {
        if (!ftJobseekerDropdown) return;
        if (residents.length === 0) { ftJobseekerDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; ftJobseekerDropdown.style.display = 'block'; return; }
        ftJobseekerDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectFtJobseekerResident(r); });
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

            if (!residentId) { showFtJobseekerError('Please select a resident from the list.'); if (ftJobseekerNameInput) ftJobseekerNameInput.focus(); return; }
            if (!date) { if (ftJobseekerDateInput) { ftJobseekerDateInput.classList.add('is-invalid'); ftJobseekerDateInput.focus(); } return; }
            else { if (ftJobseekerDateInput) ftJobseekerDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-ft-jobseeker-assistance.php?' + params.toString();
        });
    }

    // ============================================
    // Good Moral Character (GMRC) Modal Logic
    // ============================================
    var gmrcNameInput    = document.getElementById('gmrcResidentName');
    var gmrcIdInput     = document.getElementById('gmrcResidentId');
    var gmrcDropdown    = document.getElementById('gmrcResidentDropdown');
    var gmrcResidentBtn = document.getElementById('gmrcResidentBtn');
    var gmrcDateInput   = document.getElementById('gmrcDate');
    var gmrcPurposeInput = document.getElementById('gmrcPurpose');
    var gmrcPrintBtn    = document.getElementById('gmrcPrintBtn');
    var gmrcModalEl     = document.getElementById('gmrcModal');

    var gmrcSearchTimeout = null;

    if (gmrcModalEl) {
        gmrcModalEl.addEventListener('show.bs.modal', function () {
            if (gmrcNameInput)    gmrcNameInput.value    = '';
            if (gmrcIdInput)      gmrcIdInput.value      = '';
            if (gmrcDropdown)     { gmrcDropdown.innerHTML = ''; gmrcDropdown.style.display = 'none'; }
            if (gmrcDateInput)    gmrcDateInput.value    = getTodayDate();
            if (gmrcPurposeInput)  gmrcPurposeInput.value = 'Good Moral Character Verification';
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
            gmrcSearchTimeout = setTimeout(function () { searchGmrcResidents(term); }, 250);
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

    function searchGmrcResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderGmrcDropdown(results); }
                else { hideGmrcDropdown(); }
            })
            .catch(function () { hideGmrcDropdown(); });
    }

    function renderGmrcDropdown(residents) {
        if (!gmrcDropdown) return;
        if (residents.length === 0) { gmrcDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; gmrcDropdown.style.display = 'block'; return; }
        gmrcDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectGmrcResident(r); });
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

            if (!residentId) { showGmrcError('Please select a resident from the list.'); if (gmrcNameInput) gmrcNameInput.focus(); return; }
            if (!date) { if (gmrcDateInput) { gmrcDateInput.classList.add('is-invalid'); gmrcDateInput.focus(); } return; }
            else { if (gmrcDateInput) gmrcDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-gmrc.php?' + params.toString();
        });
    }

    // ============================================
    // Oath of Undertaking Modal Logic
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
            oathSearchTimeout = setTimeout(function () { searchOathResidents(term); }, 250);
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

    function searchOathResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderOathDropdown(results); }
                else { hideOathDropdown(); }
            })
            .catch(function () { hideOathDropdown(); });
    }

    function renderOathDropdown(residents) {
        if (!oathDropdown) return;
        if (residents.length === 0) { oathDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; oathDropdown.style.display = 'block'; return; }
        oathDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectOathResident(r); });
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

            if (!residentId) { showOathError('Please select a resident from the list.'); if (oathNameInput) oathNameInput.focus(); return; }
            if (!date) { if (oathDateInput) { oathDateInput.classList.add('is-invalid'); oathDateInput.focus(); } return; }
            else { if (oathDateInput) oathDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-oathofundertaking.php?' + params.toString();
        });
    }

    // ============================================
    // Certificate of Low Income Modal Logic
    // ============================================
    var lowIncomeNameInput    = document.getElementById('lowIncomeResidentName');
    var lowIncomeIdInput      = document.getElementById('lowIncomeResidentId');
    var lowIncomeDropdown     = document.getElementById('lowIncomeResidentDropdown');
    var lowIncomeResidentBtn  = document.getElementById('lowIncomeResidentBtn');
    var lowIncomeDateInput    = document.getElementById('lowIncomeDate');
    var lowIncomePurposeInput = document.getElementById('lowIncomePurpose');
    var lowIncomePrintBtn     = document.getElementById('lowIncomePrintBtn');
    var lowIncomeModalEl      = document.getElementById('lowIncomeModal');

    var lowIncomeSearchTimeout = null;

    if (lowIncomeModalEl) {
        lowIncomeModalEl.addEventListener('show.bs.modal', function () {
            if (lowIncomeNameInput)    lowIncomeNameInput.value    = '';
            if (lowIncomeIdInput)      lowIncomeIdInput.value      = '';
            if (lowIncomeDropdown)    { lowIncomeDropdown.innerHTML = ''; lowIncomeDropdown.style.display = 'none'; }
            if (lowIncomeDateInput)   lowIncomeDateInput.value    = getTodayDate();
            if (lowIncomePurposeInput) lowIncomePurposeInput.value = 'Low Income Verification';
            clearLowIncomeError();
        });
    }

    if (lowIncomeNameInput) {
        lowIncomeNameInput.addEventListener('input', function () {
            if (lowIncomeIdInput) lowIncomeIdInput.value = '';
            clearLowIncomeError();
            var term = this.value.trim();
            clearTimeout(lowIncomeSearchTimeout);
            if (term.length < 1) { hideLowIncomeDropdown(); return; }
            lowIncomeSearchTimeout = setTimeout(function () { searchLowIncomeResidents(term); }, 250);
        });
        lowIncomeNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideLowIncomeDropdown(); });
    }

    if (lowIncomeResidentBtn) {
        lowIncomeResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (lowIncomeNameInput) { lowIncomeNameInput.value = ''; lowIncomeNameInput.focus(); }
            if (lowIncomeIdInput) lowIncomeIdInput.value = '';
            hideLowIncomeDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (lowIncomeDropdown && !e.target.closest('#lowIncomeModal .resident-search-wrap')) { hideLowIncomeDropdown(); }
    });

    function searchLowIncomeResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderLowIncomeDropdown(results); }
                else { hideLowIncomeDropdown(); }
            })
            .catch(function () { hideLowIncomeDropdown(); });
    }

    function renderLowIncomeDropdown(residents) {
        if (!lowIncomeDropdown) return;
        if (residents.length === 0) { lowIncomeDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; lowIncomeDropdown.style.display = 'block'; return; }
        lowIncomeDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectLowIncomeResident(r); });
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
        if (lowIncomeDropdown) { lowIncomeDropdown.style.display = 'none'; lowIncomeDropdown.innerHTML = ''; }
    }

    function clearLowIncomeError() {
        if (lowIncomeNameInput) lowIncomeNameInput.classList.remove('is-invalid');
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
        lowIncomeNameInput.closest('.resident-input-group').after(err);
    }

    if (lowIncomePrintBtn) {
        lowIncomePrintBtn.addEventListener('click', function () {
            var residentId = lowIncomeIdInput      ? lowIncomeIdInput.value.trim()      : '';
            var date       = lowIncomeDateInput    ? lowIncomeDateInput.value.trim()    : '';
            var purpose    = lowIncomePurposeInput ? lowIncomePurposeInput.value.trim() : '';

            if (!residentId) { showLowIncomeError('Please select a resident from the list.'); if (lowIncomeNameInput) lowIncomeNameInput.focus(); return; }
            if (!date) { if (lowIncomeDateInput) { lowIncomeDateInput.classList.add('is-invalid'); lowIncomeDateInput.focus(); } return; }
            else { if (lowIncomeDateInput) lowIncomeDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-lowincome.php?' + params.toString();
        });
    }

    // ============================================
    // Certificate of Solo Parent Modal Logic
    // ============================================
    var soloParentNameInput    = document.getElementById('soloParentResidentName');
    var soloParentIdInput      = document.getElementById('soloParentResidentId');
    var soloParentDropdown     = document.getElementById('soloParentResidentDropdown');
    var soloParentResidentBtn  = document.getElementById('soloParentResidentBtn');
    var soloParentDateInput    = document.getElementById('soloParentDate');
    var soloParentPurposeInput = document.getElementById('soloParentPurpose');
    var soloParentPrintBtn     = document.getElementById('soloParentPrintBtn');
    var soloParentModalEl      = document.getElementById('soloParentModal');

    var soloParentSearchTimeout = null;

    if (soloParentModalEl) {
        soloParentModalEl.addEventListener('show.bs.modal', function () {
            if (soloParentNameInput)    soloParentNameInput.value    = '';
            if (soloParentIdInput)       soloParentIdInput.value      = '';
            if (soloParentDropdown)     { soloParentDropdown.innerHTML = ''; soloParentDropdown.style.display = 'none'; }
            if (soloParentDateInput)     soloParentDateInput.value    = getTodayDate();
            if (soloParentPurposeInput)  soloParentPurposeInput.value = 'Solo Parent Verification';
            clearSoloParentError();
        });
    }

    if (soloParentNameInput) {
        soloParentNameInput.addEventListener('input', function () {
            if (soloParentIdInput) soloParentIdInput.value = '';
            clearSoloParentError();
            var term = this.value.trim();
            clearTimeout(soloParentSearchTimeout);
            if (term.length < 1) { hideSoloParentDropdown(); return; }
            soloParentSearchTimeout = setTimeout(function () { searchSoloParentResidents(term); }, 250);
        });
        soloParentNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideSoloParentDropdown(); });
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

    function searchSoloParentResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderSoloParentDropdown(results); }
                else { hideSoloParentDropdown(); }
            })
            .catch(function () { hideSoloParentDropdown(); });
    }

    function renderSoloParentDropdown(residents) {
        if (!soloParentDropdown) return;
        if (residents.length === 0) { soloParentDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; soloParentDropdown.style.display = 'block'; return; }
        soloParentDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectSoloParentResident(r); });
            soloParentDropdown.appendChild(item);
        });
        soloParentDropdown.style.display = 'block';
    }

    function selectSoloParentResident(resident) {
        if (soloParentNameInput) soloParentNameInput.value = resident.full_name.trim();
        if (soloParentIdInput)    soloParentIdInput.value   = resident.id;
        hideSoloParentDropdown();
        clearSoloParentError();
    }

    function hideSoloParentDropdown() {
        if (soloParentDropdown) { soloParentDropdown.style.display = 'none'; soloParentDropdown.innerHTML = ''; }
    }

    function clearSoloParentError() {
        if (soloParentNameInput) soloParentNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.soloparent-error-msg');
        if (existing) existing.remove();
    }

    function showSoloParentError(msg) {
        clearSoloParentError();
        if (!soloParentNameInput) return;
        soloParentNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback soloparent-error-msg';
        err.textContent = msg;
        soloParentNameInput.closest('.resident-input-group').after(err);
    }

    if (soloParentPrintBtn) {
        soloParentPrintBtn.addEventListener('click', function () {
            var residentId = soloParentIdInput      ? soloParentIdInput.value.trim()      : '';
            var date       = soloParentDateInput    ? soloParentDateInput.value.trim()    : '';
            var purpose    = soloParentPurposeInput ? soloParentPurposeInput.value.trim() : '';

            if (!residentId) { showSoloParentError('Please select a resident from the list.'); if (soloParentNameInput) soloParentNameInput.focus(); return; }
            if (!date) { if (soloParentDateInput) { soloParentDateInput.classList.add('is-invalid'); soloParentDateInput.focus(); } return; }
            else { if (soloParentDateInput) soloParentDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-soloparent.php?' + params.toString();
        });
    }

    // ============================================
    // Registration of Birth Certificate Modal Logic
    // ============================================
    var rbcNameInput    = document.getElementById('rbcResidentName');
    var rbcIdInput      = document.getElementById('rbcResidentId');
    var rbcDropdown     = document.getElementById('rbcResidentDropdown');
    var rbcResidentBtn  = document.getElementById('rbcResidentBtn');
    var rbcChildNameInput = document.getElementById('rbcChildName');
    var rbcChildIdInput   = document.getElementById('rbcChildId');
    var rbcChildDropdown  = document.getElementById('rbcChildDropdown');
    var rbcChildBtn       = document.getElementById('rbcChildBtn');
    var rbcDateInput    = document.getElementById('rbcDate');
    var rbcPurposeInput = document.getElementById('rbcPurpose');
    var rbcPrintBtn     = document.getElementById('rbcPrintBtn');
    var rbcModalEl      = document.getElementById('rbcModal');

    var rbcSearchTimeout = null;
    var rbcChildSearchTimeout = null;

    if (rbcModalEl) {
        rbcModalEl.addEventListener('show.bs.modal', function () {
            if (rbcNameInput)    rbcNameInput.value    = '';
            if (rbcIdInput)      rbcIdInput.value      = '';
            if (rbcDropdown)     { rbcDropdown.innerHTML = ''; rbcDropdown.style.display = 'none'; }
            if (rbcChildNameInput) rbcChildNameInput.value = '';
            if (rbcChildIdInput)   rbcChildIdInput.value   = '';
            if (rbcChildDropdown)  { rbcChildDropdown.innerHTML = ''; rbcChildDropdown.style.display = 'none'; }
            if (rbcDateInput)    rbcDateInput.value    = getTodayDate();
            if (rbcPurposeInput)  rbcPurposeInput.value = 'Birth Certificate Registration';
            clearRbcError();
        });
    }

    // Parent/Applicant search
    if (rbcNameInput) {
        rbcNameInput.addEventListener('input', function () {
            if (rbcIdInput) rbcIdInput.value = '';
            clearRbcError();
            var term = this.value.trim();
            clearTimeout(rbcSearchTimeout);
            if (term.length < 1) { hideRbcDropdown(); return; }
            rbcSearchTimeout = setTimeout(function () { searchRbcResidents(term); }, 250);
        });
        rbcNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideRbcDropdown(); });
    }

    // Child search
    if (rbcChildNameInput) {
        rbcChildNameInput.addEventListener('input', function () {
            if (rbcChildIdInput) rbcChildIdInput.value = '';
            clearRbcError();
            var term = this.value.trim();
            clearTimeout(rbcChildSearchTimeout);
            if (term.length < 1) { hideRbcChildDropdown(); return; }
            rbcChildSearchTimeout = setTimeout(function () { searchRbcChildResidents(term); }, 250);
        });
        rbcChildNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideRbcChildDropdown(); });
    }

    if (rbcResidentBtn) {
        rbcResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (rbcNameInput) { rbcNameInput.value = ''; rbcNameInput.focus(); }
            if (rbcIdInput) rbcIdInput.value = '';
            hideRbcDropdown();
        });
    }

    if (rbcChildBtn) {
        rbcChildBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (rbcChildNameInput) { rbcChildNameInput.value = ''; rbcChildNameInput.focus(); }
            if (rbcChildIdInput) rbcChildIdInput.value = '';
            hideRbcChildDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (rbcDropdown && !e.target.closest('#rbcModal .resident-search-wrap')) { hideRbcDropdown(); }
        if (rbcChildDropdown && !e.target.closest('#rbcModal .resident-search-wrap')) { hideRbcChildDropdown(); }
    });

    function searchRbcResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderRbcDropdown(results); }
                else { hideRbcDropdown(); }
            })
            .catch(function () { hideRbcDropdown(); });
    }

    function searchRbcChildResidents(term) {
        var residentId = rbcIdInput ? rbcIdInput.value.trim() : '';
        var url = 'model/search_residents.php?search=' + encodeURIComponent(term);
        if (residentId) {
            url += '&exclude_resident_id=' + encodeURIComponent(residentId);
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

    function renderRbcDropdown(residents) {
        if (!rbcDropdown) return;
        if (residents.length === 0) { rbcDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; rbcDropdown.style.display = 'block'; return; }
        rbcDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectRbcResident(r); });
            rbcDropdown.appendChild(item);
        });
        rbcDropdown.style.display = 'block';
    }

    function renderRbcChildDropdown(residents) {
        if (!rbcChildDropdown) return;
        if (residents.length === 0) { rbcChildDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; rbcChildDropdown.style.display = 'block'; return; }
        rbcChildDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectRbcChildResident(r); });
            rbcChildDropdown.appendChild(item);
        });
        rbcChildDropdown.style.display = 'block';
    }

    function selectRbcResident(resident) {
        if (rbcNameInput) rbcNameInput.value = resident.full_name.trim();
        if (rbcIdInput)   rbcIdInput.value   = resident.id;
        hideRbcDropdown();
        clearRbcError();
    }

    function selectRbcChildResident(resident) {
        if (rbcChildNameInput) rbcChildNameInput.value = resident.full_name.trim();
        if (rbcChildIdInput)   rbcChildIdInput.value   = resident.id;
        hideRbcChildDropdown();
        clearRbcError();
    }

    function hideRbcDropdown() {
        if (rbcDropdown) { rbcDropdown.style.display = 'none'; rbcDropdown.innerHTML = ''; }
    }

    function hideRbcChildDropdown() {
        if (rbcChildDropdown) { rbcChildDropdown.style.display = 'none'; rbcChildDropdown.innerHTML = ''; }
    }

    function clearRbcError() {
        if (rbcNameInput) rbcNameInput.classList.remove('is-invalid');
        if (rbcChildNameInput) rbcChildNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.rbc-error-msg');
        if (existing) existing.remove();
    }

    function showRbcError(msg) {
        clearRbcError();
        if (!rbcNameInput && !rbcChildNameInput) return;
        var err = document.createElement('div');
        err.className = 'invalid-feedback rbc-error-msg';
        err.textContent = msg;
        if (rbcNameInput) {
            rbcNameInput.classList.add('is-invalid');
            rbcNameInput.closest('.resident-input-group').after(err);
        } else if (rbcChildNameInput) {
            rbcChildNameInput.classList.add('is-invalid');
            rbcChildNameInput.closest('.resident-input-group').after(err);
        }
    }

    if (rbcPrintBtn) {
        rbcPrintBtn.addEventListener('click', function () {
            var residentId = rbcIdInput      ? rbcIdInput.value.trim()      : '';
            var childId    = rbcChildIdInput ? rbcChildIdInput.value.trim()  : '';
            var date       = rbcDateInput    ? rbcDateInput.value.trim()    : '';
            var purpose    = rbcPurposeInput ? rbcPurposeInput.value.trim() : '';

            if (!residentId) { showRbcError('Please select a parent/applicant from the list.'); if (rbcNameInput) rbcNameInput.focus(); return; }
            if (!childId) { showRbcError('Please select a child from the list.'); if (rbcChildNameInput) rbcChildNameInput.focus(); return; }
            if (!date) { if (rbcDateInput) { rbcDateInput.classList.add('is-invalid'); rbcDateInput.focus(); } return; }
            else { if (rbcDateInput) rbcDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, child_id: childId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-RBC.php?' + params.toString();
        });
    }

    // ============================================
    // Barangay Clearance Modal Logic
    // ============================================
    var brgyClearanceNameInput    = document.getElementById('brgyClearanceResidentName');
    var brgyClearanceIdInput      = document.getElementById('brgyClearanceResidentId');
    var brgyClearanceDropdown     = document.getElementById('brgyClearanceResidentDropdown');
    var brgyClearanceResidentBtn  = document.getElementById('brgyClearanceResidentBtn');
    var brgyClearanceDateInput    = document.getElementById('brgyClearanceDate');
    var brgyClearancePurposeInput = document.getElementById('brgyClearancePurpose');
    var brgyClearancePrintBtn     = document.getElementById('brgyClearancePrintBtn');
    var brgyClearanceModalEl      = document.getElementById('brgyClearanceModal');

    var brgyClearanceSearchTimeout = null;

    if (brgyClearanceModalEl) {
        brgyClearanceModalEl.addEventListener('show.bs.modal', function () {
            if (brgyClearanceNameInput)    brgyClearanceNameInput.value    = '';
            if (brgyClearanceIdInput)      brgyClearanceIdInput.value      = '';
            if (brgyClearanceDropdown)     { brgyClearanceDropdown.innerHTML = ''; brgyClearanceDropdown.style.display = 'none'; }
            if (brgyClearanceDateInput)    brgyClearanceDateInput.value    = getTodayDate();
            if (brgyClearancePurposeInput) brgyClearancePurposeInput.value = '';
            clearBrgyClearanceError();
        });
    }

    if (brgyClearanceNameInput) {
        brgyClearanceNameInput.addEventListener('input', function () {
            if (brgyClearanceIdInput) brgyClearanceIdInput.value = '';
            clearBrgyClearanceError();
            var term = this.value.trim();
            clearTimeout(brgyClearanceSearchTimeout);
            if (term.length < 1) { hideBrgyClearanceDropdown(); return; }
            brgyClearanceSearchTimeout = setTimeout(function () { searchBrgyClearanceResidents(term); }, 250);
        });
        brgyClearanceNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideBrgyClearanceDropdown(); });
    }

    if (brgyClearanceResidentBtn) {
        brgyClearanceResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (brgyClearanceNameInput) { brgyClearanceNameInput.value = ''; brgyClearanceNameInput.focus(); }
            if (brgyClearanceIdInput) brgyClearanceIdInput.value = '';
            hideBrgyClearanceDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (brgyClearanceDropdown && !e.target.closest('#brgyClearanceModal .resident-search-wrap')) { hideBrgyClearanceDropdown(); }
    });

    function searchBrgyClearanceResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBrgyClearanceDropdown(results); }
                else { hideBrgyClearanceDropdown(); }
            })
            .catch(function () { hideBrgyClearanceDropdown(); });
    }

    function renderBrgyClearanceDropdown(residents) {
        if (!brgyClearanceDropdown) return;
        if (residents.length === 0) { brgyClearanceDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; brgyClearanceDropdown.style.display = 'block'; return; }
        brgyClearanceDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBrgyClearanceResident(r); });
            brgyClearanceDropdown.appendChild(item);
        });
        brgyClearanceDropdown.style.display = 'block';
    }

    function selectBrgyClearanceResident(resident) {
        if (brgyClearanceNameInput) brgyClearanceNameInput.value = resident.full_name.trim();
        if (brgyClearanceIdInput)   brgyClearanceIdInput.value   = resident.id;
        hideBrgyClearanceDropdown();
        clearBrgyClearanceError();
    }

    function hideBrgyClearanceDropdown() {
        if (brgyClearanceDropdown) { brgyClearanceDropdown.style.display = 'none'; brgyClearanceDropdown.innerHTML = ''; }
    }

    function clearBrgyClearanceError() {
        if (brgyClearanceNameInput) brgyClearanceNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.brgyclearance-error-msg');
        if (existing) existing.remove();
    }

    function showBrgyClearanceError(msg) {
        clearBrgyClearanceError();
        if (!brgyClearanceNameInput) return;
        brgyClearanceNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback brgyclearance-error-msg';
        err.textContent = msg;
        brgyClearanceNameInput.closest('.resident-input-group').after(err);
    }

    if (brgyClearancePrintBtn) {
        brgyClearancePrintBtn.addEventListener('click', function () {
            var residentId = brgyClearanceIdInput      ? brgyClearanceIdInput.value.trim()      : '';
            var date       = brgyClearanceDateInput    ? brgyClearanceDateInput.value.trim()    : '';
            var purpose    = brgyClearancePurposeInput ? brgyClearancePurposeInput.value.trim() : '';

            if (!residentId) { showBrgyClearanceError('Please select a resident from the list.'); if (brgyClearanceNameInput) brgyClearanceNameInput.focus(); return; }
            if (!date) { if (brgyClearanceDateInput) { brgyClearanceDateInput.classList.add('is-invalid'); brgyClearanceDateInput.focus(); } return; }
            else { if (brgyClearanceDateInput) brgyClearanceDateInput.classList.remove('is-invalid'); }

            var params = new URLSearchParams({ resident_id: residentId, date: date, purpose: purpose });
            window.location.href = 'certifications/certificate-barangayclearance.php?' + params.toString();
        });
    }

    // ============================================
    // Barangay Business Clearance Modal Logic
    // ============================================
    var brgyBusinessClearanceNameInput    = document.getElementById('brgyBusinessClearanceResidentName');
    var brgyBusinessClearanceIdInput      = document.getElementById('brgyBusinessClearanceResidentId');
    var brgyBusinessClearanceDropdown     = document.getElementById('brgyBusinessClearanceResidentDropdown');
    var brgyBusinessClearanceResidentBtn  = document.getElementById('brgyBusinessClearanceResidentBtn');
    var brgyBusinessClearanceBusinessNameInput = document.getElementById('brgyBusinessClearanceBusinessName');
    var brgyBusinessClearanceBusinessAddressInput = document.getElementById('brgyBusinessClearanceBusinessAddress');
    var brgyBusinessClearanceNatureInput = document.getElementById('brgyBusinessClearanceNature');
    var brgyBusinessClearanceDateInput    = document.getElementById('brgyBusinessClearanceDate');
    var brgyBusinessClearancePrintBtn     = document.getElementById('brgyBusinessClearancePrintBtn');
    var brgyBusinessClearanceModalEl      = document.getElementById('brgyBusinessClearanceModal');

    var brgyBusinessClearanceSearchTimeout = null;

    if (brgyBusinessClearanceModalEl) {
        brgyBusinessClearanceModalEl.addEventListener('show.bs.modal', function () {
            if (brgyBusinessClearanceNameInput)    brgyBusinessClearanceNameInput.value    = '';
            if (brgyBusinessClearanceIdInput)      brgyBusinessClearanceIdInput.value      = '';
            if (brgyBusinessClearanceDropdown)     { brgyBusinessClearanceDropdown.innerHTML = ''; brgyBusinessClearanceDropdown.style.display = 'none'; }
            if (brgyBusinessClearanceBusinessNameInput) brgyBusinessClearanceBusinessNameInput.value = '';
            if (brgyBusinessClearanceBusinessAddressInput) brgyBusinessClearanceBusinessAddressInput.value = '';
            if (brgyBusinessClearanceNatureInput) brgyBusinessClearanceNatureInput.value = '';
            if (brgyBusinessClearanceDateInput)    brgyBusinessClearanceDateInput.value    = getTodayDate();
            clearBrgyBusinessClearanceError();
        });
    }

    if (brgyBusinessClearanceNameInput) {
        brgyBusinessClearanceNameInput.addEventListener('input', function () {
            if (brgyBusinessClearanceIdInput) brgyBusinessClearanceIdInput.value = '';
            clearBrgyBusinessClearanceError();
            var term = this.value.trim();
            clearTimeout(brgyBusinessClearanceSearchTimeout);
            if (term.length < 1) { hideBrgyBusinessClearanceDropdown(); return; }
            brgyBusinessClearanceSearchTimeout = setTimeout(function () { searchBrgyBusinessClearanceResidents(term); }, 250);
        });
        brgyBusinessClearanceNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideBrgyBusinessClearanceDropdown(); });
    }

    if (brgyBusinessClearanceResidentBtn) {
        brgyBusinessClearanceResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (brgyBusinessClearanceNameInput) { brgyBusinessClearanceNameInput.value = ''; brgyBusinessClearanceNameInput.focus(); }
            if (brgyBusinessClearanceIdInput) brgyBusinessClearanceIdInput.value = '';
            hideBrgyBusinessClearanceDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (brgyBusinessClearanceDropdown && !e.target.closest('#brgyBusinessClearanceModal .resident-search-wrap')) { hideBrgyBusinessClearanceDropdown(); }
    });

    function searchBrgyBusinessClearanceResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBrgyBusinessClearanceDropdown(results); }
                else { hideBrgyBusinessClearanceDropdown(); }
            })
            .catch(function () { hideBrgyBusinessClearanceDropdown(); });
    }

    function renderBrgyBusinessClearanceDropdown(residents) {
        if (!brgyBusinessClearanceDropdown) return;
        if (residents.length === 0) { brgyBusinessClearanceDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; brgyBusinessClearanceDropdown.style.display = 'block'; return; }
        brgyBusinessClearanceDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBrgyBusinessClearanceResident(r); });
            brgyBusinessClearanceDropdown.appendChild(item);
        });
        brgyBusinessClearanceDropdown.style.display = 'block';
    }

    function selectBrgyBusinessClearanceResident(resident) {
        if (brgyBusinessClearanceNameInput) brgyBusinessClearanceNameInput.value = resident.full_name.trim();
        if (brgyBusinessClearanceIdInput)   brgyBusinessClearanceIdInput.value   = resident.id;
        hideBrgyBusinessClearanceDropdown();
        clearBrgyBusinessClearanceError();
    }

    function hideBrgyBusinessClearanceDropdown() {
        if (brgyBusinessClearanceDropdown) { brgyBusinessClearanceDropdown.style.display = 'none'; brgyBusinessClearanceDropdown.innerHTML = ''; }
    }

    function clearBrgyBusinessClearanceError() {
        if (brgyBusinessClearanceNameInput) brgyBusinessClearanceNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.brgybusinessclearance-error-msg');
        if (existing) existing.remove();
    }

    function showBrgyBusinessClearanceError(msg) {
        clearBrgyBusinessClearanceError();
        if (!brgyBusinessClearanceNameInput) return;
        brgyBusinessClearanceNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback brgybusinessclearance-error-msg';
        err.textContent = msg;
        brgyBusinessClearanceNameInput.closest('.resident-input-group').after(err);
    }

    if (brgyBusinessClearancePrintBtn) {
        brgyBusinessClearancePrintBtn.addEventListener('click', function () {
            var residentId = brgyBusinessClearanceIdInput      ? brgyBusinessClearanceIdInput.value.trim()      : '';
            var businessName = brgyBusinessClearanceBusinessNameInput ? brgyBusinessClearanceBusinessNameInput.value.trim() : '';
            var businessAddress = brgyBusinessClearanceBusinessAddressInput ? brgyBusinessClearanceBusinessAddressInput.value.trim() : '';
            var nature = brgyBusinessClearanceNatureInput ? brgyBusinessClearanceNatureInput.value.trim() : '';
            var date       = brgyBusinessClearanceDateInput    ? brgyBusinessClearanceDateInput.value.trim()    : '';

            if (!residentId) { showBrgyBusinessClearanceError('Please select a resident from the list.'); if (brgyBusinessClearanceNameInput) brgyBusinessClearanceNameInput.focus(); return; }
            if (!businessName) { 
                if (brgyBusinessClearanceBusinessNameInput) { brgyBusinessClearanceBusinessNameInput.classList.add('is-invalid'); brgyBusinessClearanceBusinessNameInput.focus(); }
                return;
            }
            if (!date) { if (brgyBusinessClearanceDateInput) { brgyBusinessClearanceDateInput.classList.add('is-invalid'); brgyBusinessClearanceDateInput.focus(); } return; }
            
            if (brgyBusinessClearanceBusinessNameInput) brgyBusinessClearanceBusinessNameInput.classList.remove('is-invalid');
            if (brgyBusinessClearanceDateInput) brgyBusinessClearanceDateInput.classList.remove('is-invalid');

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                business_name: businessName,
                business_address: businessAddress,
                nature: nature,
                date: date
            });
            window.location.href = 'certifications/certificate-brgybusinessclearance.php?' + params.toString();
        });
    }

    // ============================================
    // Business Permit Modal Logic
    // ============================================
    var businessPermitNameInput    = document.getElementById('businessPermitResidentName');
    var businessPermitIdInput      = document.getElementById('businessPermitResidentId');
    var businessPermitDropdown     = document.getElementById('businessPermitResidentDropdown');
    var businessPermitResidentBtn  = document.getElementById('businessPermitResidentBtn');
    var businessPermitBusinessNameInput = document.getElementById('businessPermitBusinessName');
    var businessPermitBusinessAddressInput = document.getElementById('businessPermitBusinessAddress');
    var businessPermitNatureInput = document.getElementById('businessPermitNature');
    var businessPermitDateInput    = document.getElementById('businessPermitDate');
    var businessPermitPrintBtn     = document.getElementById('businessPermitPrintBtn');
    var businessPermitModalEl      = document.getElementById('businessPermitModal');

    var businessPermitSearchTimeout = null;

    if (businessPermitModalEl) {
        businessPermitModalEl.addEventListener('show.bs.modal', function () {
            if (businessPermitNameInput)    businessPermitNameInput.value    = '';
            if (businessPermitIdInput)      businessPermitIdInput.value      = '';
            if (businessPermitDropdown)     { businessPermitDropdown.innerHTML = ''; businessPermitDropdown.style.display = 'none'; }
            if (businessPermitBusinessNameInput) businessPermitBusinessNameInput.value = '';
            if (businessPermitBusinessAddressInput) businessPermitBusinessAddressInput.value = '';
            if (businessPermitNatureInput) businessPermitNatureInput.value = '';
            if (businessPermitDateInput)    businessPermitDateInput.value    = getTodayDate();
            clearBusinessPermitError();
        });
    }

    if (businessPermitNameInput) {
        businessPermitNameInput.addEventListener('input', function () {
            if (businessPermitIdInput) businessPermitIdInput.value = '';
            clearBusinessPermitError();
            var term = this.value.trim();
            clearTimeout(businessPermitSearchTimeout);
            if (term.length < 1) { hideBusinessPermitDropdown(); return; }
            businessPermitSearchTimeout = setTimeout(function () { searchBusinessPermitResidents(term); }, 250);
        });
        businessPermitNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideBusinessPermitDropdown(); });
    }

    if (businessPermitResidentBtn) {
        businessPermitResidentBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (businessPermitNameInput) { businessPermitNameInput.value = ''; businessPermitNameInput.focus(); }
            if (businessPermitIdInput) businessPermitIdInput.value = '';
            hideBusinessPermitDropdown();
        });
    }

    document.addEventListener('click', function (e) {
        if (businessPermitDropdown && !e.target.closest('#businessPermitModal .resident-search-wrap')) { hideBusinessPermitDropdown(); }
    });

    function searchBusinessPermitResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderBusinessPermitDropdown(results); }
                else { hideBusinessPermitDropdown(); }
            })
            .catch(function () { hideBusinessPermitDropdown(); });
    }

    function renderBusinessPermitDropdown(residents) {
        if (!businessPermitDropdown) return;
        if (residents.length === 0) { businessPermitDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; businessPermitDropdown.style.display = 'block'; return; }
        businessPermitDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectBusinessPermitResident(r); });
            businessPermitDropdown.appendChild(item);
        });
        businessPermitDropdown.style.display = 'block';
    }

    function selectBusinessPermitResident(resident) {
        if (businessPermitNameInput) businessPermitNameInput.value = resident.full_name.trim();
        if (businessPermitIdInput)   businessPermitIdInput.value   = resident.id;
        hideBusinessPermitDropdown();
        clearBusinessPermitError();
    }

    function hideBusinessPermitDropdown() {
        if (businessPermitDropdown) { businessPermitDropdown.style.display = 'none'; businessPermitDropdown.innerHTML = ''; }
    }

    function clearBusinessPermitError() {
        if (businessPermitNameInput) businessPermitNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.businesspermit-error-msg');
        if (existing) existing.remove();
    }

    function showBusinessPermitError(msg) {
        clearBusinessPermitError();
        if (!businessPermitNameInput) return;
        businessPermitNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback businesspermit-error-msg';
        err.textContent = msg;
        businessPermitNameInput.closest('.resident-input-group').after(err);
    }

    if (businessPermitPrintBtn) {
        businessPermitPrintBtn.addEventListener('click', function () {
            var residentId = businessPermitIdInput      ? businessPermitIdInput.value.trim()      : '';
            var businessName = businessPermitBusinessNameInput ? businessPermitBusinessNameInput.value.trim() : '';
            var businessAddress = businessPermitBusinessAddressInput ? businessPermitBusinessAddressInput.value.trim() : '';
            var nature = businessPermitNatureInput ? businessPermitNatureInput.value.trim() : '';
            var date       = businessPermitDateInput    ? businessPermitDateInput.value.trim()    : '';

            if (!residentId) { showBusinessPermitError('Please select a resident from the list.'); if (businessPermitNameInput) businessPermitNameInput.focus(); return; }
            if (!businessName) { 
                if (businessPermitBusinessNameInput) { businessPermitBusinessNameInput.classList.add('is-invalid'); businessPermitBusinessNameInput.focus(); }
                return;
            }
            if (!date) { if (businessPermitDateInput) { businessPermitDateInput.classList.add('is-invalid'); businessPermitDateInput.focus(); } return; }
            
            if (businessPermitBusinessNameInput) businessPermitBusinessNameInput.classList.remove('is-invalid');
            if (businessPermitDateInput) businessPermitDateInput.classList.remove('is-invalid');

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                business_name: businessName,
                business_address: businessAddress,
                nature: nature,
                date: date
            });
            window.location.href = 'certifications/certificate-businesspermit.php?' + params.toString();
        });
    }

    // ============================================
    // Vessel Docking Modal Logic
    // ============================================
    var vesselDockingNameInput    = document.getElementById('vesselDockingResidentName');
    var vesselDockingIdInput      = document.getElementById('vesselDockingResidentId');
    var vesselDockingDropdown     = document.getElementById('vesselDockingResidentDropdown');
    var vesselDockingResidentBtn  = document.getElementById('vesselDockingResidentBtn');
    var vesselDockingVesselNameInput = document.getElementById('vesselDockingVesselName');
    var vesselDockingFromDateInput = document.getElementById('vesselDockingFromDate');
    var vesselDockingToDateInput = document.getElementById('vesselDockingToDate');
    var vesselDockingDateInput    = document.getElementById('vesselDockingDate');
    var vesselDockingPrintBtn     = document.getElementById('vesselDockingPrintBtn');
    var vesselDockingModalEl      = document.getElementById('vesselDockingModal');

    var vesselDockingSearchTimeout = null;

    if (vesselDockingModalEl) {
        vesselDockingModalEl.addEventListener('show.bs.modal', function () {
            if (vesselDockingNameInput)    vesselDockingNameInput.value    = '';
            if (vesselDockingIdInput)      vesselDockingIdInput.value      = '';
            if (vesselDockingDropdown)     { vesselDockingDropdown.innerHTML = ''; vesselDockingDropdown.style.display = 'none'; }
            if (vesselDockingVesselNameInput) vesselDockingVesselNameInput.value = '';
            if (vesselDockingFromDateInput) vesselDockingFromDateInput.value = getTodayDate();
            if (vesselDockingToDateInput) vesselDockingToDateInput.value = getTodayDate();
            if (vesselDockingDateInput)    vesselDockingDateInput.value    = getTodayDate();
            clearVesselDockingError();
        });
    }

    if (vesselDockingNameInput) {
        vesselDockingNameInput.addEventListener('input', function () {
            if (vesselDockingIdInput) vesselDockingIdInput.value = '';
            clearVesselDockingError();
            var term = this.value.trim();
            clearTimeout(vesselDockingSearchTimeout);
            if (term.length < 1) { hideVesselDockingDropdown(); return; }
            vesselDockingSearchTimeout = setTimeout(function () { searchVesselDockingResidents(term); }, 250);
        });
        vesselDockingNameInput.addEventListener('keydown', function (e) { if (e.key === 'Escape') hideVesselDockingDropdown(); });
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
        if (vesselDockingDropdown && !e.target.closest('#vesselDockingModal .resident-search-wrap')) { hideVesselDockingDropdown(); }
    });

    function searchVesselDockingResidents(term) {
        fetch('model/search_residents.php?search=' + encodeURIComponent(term))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                var results = data.residents || data.data;
                if (data.success && results) { renderVesselDockingDropdown(results); }
                else { hideVesselDockingDropdown(); }
            })
            .catch(function () { hideVesselDockingDropdown(); });
    }

    function renderVesselDockingDropdown(residents) {
        if (!vesselDockingDropdown) return;
        if (residents.length === 0) { vesselDockingDropdown.innerHTML = '<div class="resident-dropdown-empty">No resident found. <a href="model/create-resident.php">Click here to register.</a></div>'; vesselDockingDropdown.style.display = 'block'; return; }
        vesselDockingDropdown.innerHTML = '';
        residents.forEach(function (r) {
            var item = document.createElement('div');
            item.className = 'resident-dropdown-item';
            item.innerHTML = '<span class="resident-dropdown-name">' + escapeHtml(r.full_name.trim()) + '</span><span class="resident-dropdown-id">' + escapeHtml(r.resident_id || '') + '</span>';
            item.addEventListener('mousedown', function (e) { e.preventDefault(); selectVesselDockingResident(r); });
            vesselDockingDropdown.appendChild(item);
        });
        vesselDockingDropdown.style.display = 'block';
    }

    function selectVesselDockingResident(resident) {
        if (vesselDockingNameInput) vesselDockingNameInput.value = resident.full_name.trim();
        if (vesselDockingIdInput)   vesselDockingIdInput.value   = resident.id;
        hideVesselDockingDropdown();
        clearVesselDockingError();
    }

    function hideVesselDockingDropdown() {
        if (vesselDockingDropdown) { vesselDockingDropdown.style.display = 'none'; vesselDockingDropdown.innerHTML = ''; }
    }

    function clearVesselDockingError() {
        if (vesselDockingNameInput) vesselDockingNameInput.classList.remove('is-invalid');
        var existing = document.querySelector('.vesseldocking-error-msg');
        if (existing) existing.remove();
    }

    function showVesselDockingError(msg) {
        clearVesselDockingError();
        if (!vesselDockingNameInput) return;
        vesselDockingNameInput.classList.add('is-invalid');
        var err = document.createElement('div');
        err.className = 'invalid-feedback vesseldocking-error-msg';
        err.textContent = msg;
        vesselDockingNameInput.closest('.resident-input-group').after(err);
    }

    if (vesselDockingPrintBtn) {
        vesselDockingPrintBtn.addEventListener('click', function () {
            var residentId = vesselDockingIdInput      ? vesselDockingIdInput.value.trim()      : '';
            var vesselName = vesselDockingVesselNameInput ? vesselDockingVesselNameInput.value.trim() : '';
            var fromDate   = vesselDockingFromDateInput ? vesselDockingFromDateInput.value.trim() : '';
            var toDate     = vesselDockingToDateInput   ? vesselDockingToDateInput.value.trim()   : '';
            var date       = vesselDockingDateInput    ? vesselDockingDateInput.value.trim()    : '';

            if (!residentId) { showVesselDockingError('Please select a resident from the list.'); if (vesselDockingNameInput) vesselDockingNameInput.focus(); return; }
            if (!vesselName) { 
                if (vesselDockingVesselNameInput) { vesselDockingVesselNameInput.classList.add('is-invalid'); vesselDockingVesselNameInput.focus(); }
                return;
            }
            if (!fromDate) { if (vesselDockingFromDateInput) { vesselDockingFromDateInput.classList.add('is-invalid'); vesselDockingFromDateInput.focus(); } return; }
            if (!toDate) { if (vesselDockingToDateInput) { vesselDockingToDateInput.classList.add('is-invalid'); vesselDockingToDateInput.focus(); } return; }
            if (!date) { if (vesselDockingDateInput) { vesselDockingDateInput.classList.add('is-invalid'); vesselDockingDateInput.focus(); } return; }
            
            if (vesselDockingVesselNameInput) vesselDockingVesselNameInput.classList.remove('is-invalid');
            if (vesselDockingFromDateInput) vesselDockingFromDateInput.classList.remove('is-invalid');
            if (vesselDockingToDateInput) vesselDockingToDateInput.classList.remove('is-invalid');
            if (vesselDockingDateInput) vesselDockingDateInput.classList.remove('is-invalid');

            var params = new URLSearchParams({ 
                resident_id: residentId, 
                vesselname: vesselName,
                fromdate: fromDate,
                todate: toDate,
                date: date
            });
            window.location.href = 'certifications/certificate-vesseldocking.php?' + params.toString();
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
