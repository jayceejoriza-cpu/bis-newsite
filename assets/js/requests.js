let requestsTable;

document.addEventListener('DOMContentLoaded', function() {
    const activeFiltersCount = loadFiltersFromUrl();
    
    // Initialize EnhancedTable for server-rendered data
    requestsTable = new EnhancedTable('requestsTable', {
        sortable: true,
        searchable: true,
        paginated: true,
        pageSize: 10,
        responsive: true,
        defaultFilter: (row) => {
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = urlParams.has('certificate') || urlParams.has('purpose') || urlParams.has('filter_user') || urlParams.has('from_date') || urlParams.has('to_date');
            if (hasFilters) return true; // Show all returned records if specific URL filters are active
            
            const rowYear = parseInt(row.getAttribute('data-year'), 10);
            const currentYear = new Date().getFullYear();
            
            if (!isNaN(rowYear) && rowYear < currentYear) {
                return false; // Hide past year records
            }
            return true;
        },
        customSearch: (row, term) => {
            const residentId = row.cells[0]?.textContent.toLowerCase() || '';
            const residentName = row.cells[1]?.textContent.toLowerCase() || '';
            return residentId.includes(term) || residentName.includes(term);
        }
    });

    // Initial Search from URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialSearch = urlParams.get('search');
    if (initialSearch) {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = initialSearch;
        requestsTable.search(initialSearch);
    }

    initializeSearch();
    initializeButtons();
    initializeFilterPanelOutsideClick();
    
    // Check for alerts
    const alertMsg = sessionStorage.getItem('requests_filter_alert');
    if (alertMsg) {
        showNotification(alertMsg, alertMsg.includes('cleared') || alertMsg.includes('selected') || alertMsg.includes('refreshed') ? 'info' : 'success');
        sessionStorage.removeItem('requests_filter_alert');
    } else if (activeFiltersCount > 0) {
        showNotification(`${activeFiltersCount} filter(s) applied successfully`, 'success');
    }
});

// Load filter values from URL params
function loadFiltersFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    
    const setVal = (id, val) => { 
        const el = document.getElementById(id); 
        if (el) el.value = val; 
    };
    
    setVal('filterCertificate', urlParams.get('certificate') || '');
    setVal('filterPurpose', urlParams.get('purpose') || '');
    setVal('filterUser', urlParams.get('filter_user') || '');
    setVal('filterFromDate', urlParams.get('from_date') || '');
    setVal('filterToDate', urlParams.get('to_date') || '');
    
    let activeFiltersCount = 0;
    if (urlParams.get('certificate')) activeFiltersCount++;
    if (urlParams.get('purpose')) activeFiltersCount++;
    if (urlParams.get('filter_user')) activeFiltersCount++;
    if (urlParams.get('from_date')) activeFiltersCount++;
    if (urlParams.get('to_date')) activeFiltersCount++;
    
    updateFilterNotification(activeFiltersCount);
    
    return activeFiltersCount;
}

// ===================================
// Filter Notification Badge
// ===================================
function updateFilterNotification(count) {
    const notification = document.getElementById('filterNotification');
    const countSpan = document.getElementById('filterCount');
    const filterBtn = document.getElementById('filterBtn');
    
    if (!notification || !countSpan || !filterBtn) return;
    
    if (count > 0) {
        countSpan.textContent = count > 9 ? '9+' : count;
        notification.style.display = 'flex';
        filterBtn.classList.add('has-active-filters');
        
        // Re-trigger animation
        notification.style.animation = 'none';
        notification.offsetHeight; // Trigger reflow
        notification.style.animation = 'filterNotifPop 0.3s ease';
    } else {
        notification.style.display = 'none';
        filterBtn.classList.remove('has-active-filters');
    }
}

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
            const searchTerm = e.target.value;
            searchTimeout = setTimeout(() => {
                if (requestsTable) requestsTable.search(searchTerm);
                const url = new URL(window.location);
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.replaceState({}, '', url);
            }, 300);
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) searchInput.value = '';
            if (requestsTable) requestsTable.search('');
            const url = new URL(window.location);
            url.searchParams.delete('search');
            window.history.replaceState({}, '', url);
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
                sessionStorage.setItem('requests_filter_alert', 'Data refreshed successfully');
                window.location.search = '?'; // Reload page to refetch server data without filters
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
    // Close filter panel before reload
    const filterPanel = document.getElementById('filterPanel');
    const filterBtn = document.getElementById('filterBtn');
    if (filterPanel) filterPanel.style.display = 'none';
    if (filterBtn) filterBtn.classList.remove('active');
    
    // Sync filters to URL params for server-side filtering (reloads page)
    const certificate = document.getElementById('filterCertificate')?.value || '';
    const purpose = document.getElementById('filterPurpose')?.value || '';
    const user = document.getElementById('filterUser')?.value || '';
    const fromDate = document.getElementById('filterFromDate')?.value || '';
    const toDate = document.getElementById('filterToDate')?.value || '';

    let count = 0;
    if (certificate) count++;
    if (purpose) count++;
    if (user) count++;
    if (fromDate) count++;
    if (toDate) count++;

    if (count > 0) {
        sessionStorage.setItem('requests_filter_alert', `${count} filter(s) applied successfully`);
    } else {
        sessionStorage.setItem('requests_filter_alert', 'No filters selected');
    }

    const params = new URLSearchParams();
    if (certificate) params.set('certificate', certificate);
    if (purpose) params.set('purpose', purpose);
    if (user) params.set('filter_user', user);
    if (fromDate) params.set('from_date', fromDate);
    if (toDate) params.set('to_date', toDate);

    // Reload with params (server filters)
    const queryString = params.toString();
    window.location.search = queryString || '?'; // ? for clean reload if empty
}

function clearAdvancedFilters() {
    // Clear filters and URL params
    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val; };
    setVal('filterCertificate', '');
    setVal('filterPurpose', '');
    setVal('filterUser', '');
    setVal('filterFromDate', '');
    setVal('filterToDate', '');

    sessionStorage.setItem('requests_filter_alert', 'Filters cleared');

    // Clear URL and reload
    window.history.replaceState({}, document.title, window.location.pathname);
    window.location.search = '?';
}

// ===================================
// Utility Functions
// ===================================
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : '#3b82f6'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animations for notification
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    @keyframes filterNotifPop {
        0% { transform: scale(0.8); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
