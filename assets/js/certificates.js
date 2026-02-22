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
            residentDropdown.innerHTML = '<div class="resident-dropdown-empty">No residents found</div>';
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
            residencyDropdown.innerHTML = '<div class="resident-dropdown-empty">No residents found</div>';
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
