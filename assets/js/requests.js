/**
 * Requests Page JavaScript
 * Handles certificate request creation and management
 */

(function() {
    'use strict';

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initializeEventListeners();
    });

    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const refreshBtn = document.getElementById('refreshBtn');

        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearch, 300));
        }

        if (clearSearch) {
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                handleSearch();
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                location.reload();
            });
        }
    }

    /**
     * Handle search in requests table
     */
    function handleSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const tableRows = document.querySelectorAll('#requestsTableBody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Initialize filter functionality
     */
    function initializeFilters() {
        const filterBtn = document.getElementById('filterBtn');
        const filterPanel = document.getElementById('filterPanel');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');

        if (filterBtn) {
            filterBtn.addEventListener('click', function() {
                if (filterPanel.style.display === 'none' || !filterPanel.style.display) {
                    filterPanel.style.display = 'block';
                    filterBtn.classList.add('active');
                } else {
                    filterPanel.style.display = 'none';
                    filterBtn.classList.remove('active');
                }
            });
        }

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', applyFilters);
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
    }

    /**
     * Apply filters to requests table
     */
    function applyFilters() {
        const filters = {
            residentID: document.getElementById('filterResidentID').value.toLowerCase().trim(),
            residentName: document.getElementById('filterResidentName').value.toLowerCase().trim(),
            certificate: document.getElementById('filterCertificate').value,
            dateRequest: document.getElementById('filterDateRequest').value
        };

        console.log('Applying filters:', filters);

        const tableRows = document.querySelectorAll('#requestsTableBody tr');
        let visibleCount = 0;

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan]')) {
                return;
            }

            const rowData = {
                residentID: row.getAttribute('data-resident-id') || '',
                residentName: row.getAttribute('data-resident-name') || '',
                certificate: row.getAttribute('data-certificate') || '',
                dateRequest: row.getAttribute('data-date-request') || ''
            };

            let shouldShow = true;

            // Resident ID filter
            if (filters.residentID && !rowData.residentID.toLowerCase().includes(filters.residentID)) {
                shouldShow = false;
            }

            // Resident Name filter
            if (filters.residentName && !rowData.residentName.toLowerCase().includes(filters.residentName)) {
                shouldShow = false;
            }

            // Certificate filter
            if (filters.certificate && rowData.certificate !== filters.certificate) {
                shouldShow = false;
            }

            // Date Request filter
            if (filters.dateRequest && rowData.dateRequest) {
                const filterDate = new Date(filters.dateRequest);
                const rowDate = new Date(rowData.dateRequest);
                
                // Compare dates (ignoring time)
                if (filterDate.toDateString() !== rowDate.toDateString()) {
                    shouldShow = false;
                }
            }

            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        // Count active filters
        const activeFiltersCount = Object.values(filters).filter(v => v !== '').length;

        if (activeFiltersCount > 0) {
            showNotification(`${activeFiltersCount} filter(s) applied - ${visibleCount} request(s) found`, 'success');
        } else {
            showNotification('No filters selected', 'info');
        }
    }

    /**
     * Clear all filters
     */
    function clearFilters() {
        document.getElementById('filterResidentID').value = '';
        document.getElementById('filterResidentName').value = '';
        document.getElementById('filterCertificate').value = '';
        document.getElementById('filterDateRequest').value = '';

        // Show all rows
        const tableRows = document.querySelectorAll('#requestsTableBody tr');
        tableRows.forEach(row => {
            row.style.display = '';
        });

        showNotification('Filters cleared', 'success');
    }

    // Initialize filters when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFilters);
    } else {
        initializeFilters();
    }

})();
