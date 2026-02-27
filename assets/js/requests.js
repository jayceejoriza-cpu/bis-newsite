let requestsTable;

document.addEventListener('DOMContentLoaded', function() {
    loadRequests();
    initializeSearch();
    initializeButtons();
});

// ===================================
// Load Requests Data
// ===================================
function loadRequests() {
    const tableBody = document.getElementById('requestsTableBody');
    if (!tableBody) return;

    // Show loading state
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                <p style="margin-top: 10px; color: var(--text-secondary);">Loading requests...</p>
            </td>
        </tr>
    `;

    fetch('model/get_requests.php')
        .then(response => response.json())
        .then(data => {
            tableBody.innerHTML = '';
            if (data.data && data.data.length > 0) {
                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.resident_id || 'N/A'}</td>
                        <td>${row.resident_name || 'N/A'}</td>
                        <td>${row.certificate_name || 'N/A'}</td>
                        <td>${row.purpose || 'N/A'}</td>
                        <td>${new Date(row.date_requested).toLocaleDateString()}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <i class="fas fa-file-alt" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                            <p style="color: #6b7280; font-size: 16px; margin: 0;">No requests found</p>
                        </td>
                    </tr>
                `;
            }

            // Initialize EnhancedTable after data is loaded
            requestsTable = new EnhancedTable('requestsTable', {
                sortable: true,
                searchable: true,
                paginated: true,
                pageSize: 10,
                responsive: true
            });
        })
        .catch(error => {
            console.error('Error loading requests:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
                        <p style="margin: 0;">Error loading requests</p>
                    </td>
                </tr>
            `;
        });
}

// ===================================
// Search Functionality
// ===================================
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');

    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (requestsTable) requestsTable.search(e.target.value);
            }, 300);
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = '';
            if (requestsTable) requestsTable.search('');
            if (searchInput) searchInput.focus();
        });
    }
}

// ===================================
// Button Handlers
// ===================================
function initializeButtons() {
    // Filter button
    const filterBtn = document.getElementById('filterBtn');
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            const filterPanel = document.getElementById('filterPanel');
            if (filterPanel) {
                const isVisible = filterPanel.style.display !== 'none';
                filterPanel.style.display = isVisible ? 'none' : 'block';
                filterBtn.classList.toggle('active', !isVisible);
            }
        });
    }

    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            const icon = refreshBtn.querySelector('i');
            icon.style.animation = 'spin 0.5s linear';
            setTimeout(() => {
                icon.style.animation = '';
                loadRequests();
            }, 500);
        });
    }

    // Apply Filters button
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyAdvancedFilters);
    }

    // Clear Filters button
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAdvancedFilters);
    }
}

// ===================================
// Advanced Filters
// ===================================
function applyAdvancedFilters() {
    if (!requestsTable) return;

    const residentId   = (document.getElementById('filterResidentID')?.value   || '').trim().toLowerCase();
    const residentName = (document.getElementById('filterResidentName')?.value  || '').trim().toLowerCase();
    const certificate  = (document.getElementById('filterCertificate')?.value   || '').toLowerCase();
    const dateRequest  =  document.getElementById('filterDateRequest')?.value   || '';

    requestsTable.filter(row => {
        const cells = Array.from(row.cells);
        if (residentId   && !cells[0]?.textContent.toLowerCase().includes(residentId))   return false;
        if (residentName && !cells[1]?.textContent.toLowerCase().includes(residentName)) return false;
        if (certificate  && !cells[2]?.textContent.toLowerCase().includes(certificate))  return false;
        if (dateRequest) {
            const cellDate   = cells[4]?.textContent || '';
            const filterDate = new Date(dateRequest).toLocaleDateString();
            if (cellDate !== filterDate) return false;
        }
        return true;
    });
}

function clearAdvancedFilters() {
    if (!requestsTable) return;

    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    setVal('filterResidentID',   '');
    setVal('filterResidentName', '');
    setVal('filterCertificate',  '');
    setVal('filterDateRequest',  '');

    requestsTable.reset();
}
