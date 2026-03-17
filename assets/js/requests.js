let requestsTable;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize EnhancedTable for server-rendered data
    requestsTable = new EnhancedTable('requestsTable', {
        sortable: true,
        searchable: true,
        paginated: true,
        pageSize: 10,
        responsive: true,
        customSearch: (row, term) => {
            const residentId = row.cells[0]?.textContent.toLowerCase() || '';
            const residentName = row.cells[1]?.textContent.toLowerCase() || '';
            return residentId.includes(term) || residentName.includes(term);
        }
    });
    initializeSearch();
    initializeButtons();
    initializeFilterPanelOutsideClick();
});

// ===================================
// Close Filter Panel on Outside Click
// ===================================
function initializeFilterPanelOutsideClick() {
    const filterPanel = document.getElementById('filterPanel');
    const filterBtn = document.getElementById('filterBtn');
    
    if (!filterPanel || !filterBtn) return;
    
    document.addEventListener('click', function(e) {
        // Check if filter panel is currently visible
        const isFilterPanelVisible = filterPanel.style.display !== 'none';
        
        if (isFilterPanelVisible) {
            // Check if the click was outside the filter panel AND outside the filter button
            const clickedInsideFilterPanel = filterPanel.contains(e.target);
            const clickedOnFilterBtn = filterBtn.contains(e.target);
            
            if (!clickedInsideFilterPanel && !clickedOnFilterBtn) {
                // Close the filter panel
                filterPanel.style.display = 'none';
                filterBtn.classList.remove('active');
            }
        }
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
                location.reload(); // Reload page to refetch server data
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
