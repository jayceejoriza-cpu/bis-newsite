// ===================================
// Residents Page JavaScript
// Enhanced with Table.js Integration
// ===================================

let residentsTable;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Set active navigation
    setActiveNavigation();
    
    // Initialize enhanced table
    initializeTable();
    
    // Initialize all event listeners
    initializeEventListeners();
    
    // Apply saved view preference
    applySavedView();
    
    console.log('Residents page loaded successfully');
});

// ===================================
// Navigation
// ===================================
function setActiveNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const residentsNavItem = document.querySelector('a[href="residents.php"]');
    if (residentsNavItem) {
        residentsNavItem.parentElement.classList.add('active');
    }
}

// ===================================
// Table Initialization
// ===================================
function initializeTable() {
    // Initialize EnhancedTable with the residents table
    residentsTable = new EnhancedTable('residentsTable', {
        sortable: true,
        searchable: true,
        paginated: true,
        pageSize: 10,
        responsive: true
    });
    
    console.log(`Total residents: ${residentsTable.getTotalRows()}`);
}

// ===================================
// Event Listeners
// ===================================
function initializeEventListeners() {
    // Filter tabs
    initializeFilterTabs();
    
    // Search functionality
    initializeSearch();
    
    // Action buttons
    initializeButtons();
    
    // View toggle
    initializeViewToggle();
    
    // Action menu handlers
    initializeActionMenus();
}

// ===================================
// Filter Tabs
// ===================================
function initializeFilterTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all tabs
            tabButtons.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked tab
            btn.classList.add('active');
            
            const filter = btn.getAttribute('data-filter');
            applyFilter(filter);
        });
    });
}

function applyFilter(filterType) {
    console.log('Filter applied:', filterType);
    
    const gridCards = document.querySelectorAll('.resident-card');
    
    if (filterType === 'all') {
        residentsTable.reset();
        gridCards.forEach(card => card.style.display = '');
        return;
    }
    
    residentsTable.filter(row => {
        const cells = Array.from(row.cells);
        
        switch(filterType) {
            case 'verified':
                const verificationBadge = cells[2]?.querySelector('.badge');
                return verificationBadge?.textContent.trim().toLowerCase() === 'verified';
                
            case 'voters':
                const voterBadge = cells[3]?.querySelector('.badge');
                return voterBadge?.textContent.trim().toLowerCase() === 'yes';
                
            case 'active':
                const activityBadge = cells[6]?.querySelector('.badge');
                return activityBadge?.textContent.trim().toLowerCase() === 'active';
                
            default:
                return true;
        }
    });

    // Filter grid cards
    gridCards.forEach(card => {
        const voterStatus = card.getAttribute('data-voter-status').toLowerCase();
        const activityStatus = card.getAttribute('data-activity-status').toLowerCase();
        
        let show = true;
        switch(filterType) {
            case 'voters':
                show = voterStatus === 'yes';
                break;
            case 'active':
                show = activityStatus === 'active';
                break;
        }
        card.style.display = show ? '' : 'none';
    });
}

// ===================================
// Search Functionality
// ===================================
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    if (searchInput) {
        // Debounce search for better performance
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                residentsTable.search(e.target.value);
                
                // Filter grid cards
                const query = e.target.value.toLowerCase();
                const gridCards = document.querySelectorAll('.resident-card');
                gridCards.forEach(card => {
                    const name = card.querySelector('.resident-name').textContent.toLowerCase();
                    const id = card.querySelector('.resident-id').textContent.toLowerCase();
                    card.style.display = (name.includes(query) || id.includes(query)) ? '' : 'none';
                });
            }, 300);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            residentsTable.search('');
            
            // Reset grid cards
            const gridCards = document.querySelectorAll('.resident-card');
            gridCards.forEach(card => card.style.display = '');
            searchInput.focus();
        });
    }
}

// ===================================
// Button Handlers
// ===================================
function initializeButtons() {
    // Create Resident button
    const createResidentBtn = document.getElementById('createResidentBtn');
    if (createResidentBtn) {
        createResidentBtn.addEventListener('click', () => {
            showCreateResidentModal();
        });
    }
    
    // Filter button
    const filterBtn = document.getElementById('filterBtn');
    if (filterBtn) {
        filterBtn.addEventListener('click', () => {
            showAdvancedFilters();
        });
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            refreshData();
        });
    }

    // Print button
    const printBtn = document.getElementById('printMasterlistBtn');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            if (!residentsTable || !residentsTable.filteredRows) {
                window.print();
                return;
            }

            // Create a hidden iframe for printing to bypass pagination visibility issues
            let printFrame = document.getElementById('residentPrintFrame');
            if (!printFrame) {
                printFrame = document.createElement('iframe');
                printFrame.id = 'residentPrintFrame';
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

            // Clone the table header and remove the Action column
            const tableHeader = document.querySelector('#residentsTable thead').cloneNode(true);
            const headerActionCol = tableHeader.querySelector('th:last-child');
            if (headerActionCol) headerActionCol.remove();

            // Get only the filtered rows from the EnhancedTable instance
            let rowsHtml = '';
            residentsTable.filteredRows.forEach(row => {
                const rowClone = row.cloneNode(true);
                // Remove the Action column (last cell)
                const actionCell = rowClone.querySelector('td:last-child');
                if (actionCell) actionCell.remove();
                
                // Ensure row is visible in the print document
                rowClone.style.display = 'table-row';
                rowsHtml += rowClone.outerHTML;
            });

            // Get the print header and update the count
            const printHeader = document.querySelector('.print-header').cloneNode(true);
            const countBadge = printHeader.querySelector('#printTotalRecords');
            if (countBadge) countBadge.textContent = residentsTable.filteredRows.length;

            // Update the print title with active filter information
            const printTitle = printHeader.querySelector('.print-list-title');
            if (printTitle) {
                const activeFilters = [];
                
                // Check tab filters (Voters, Active, etc.)
                const activeTab = document.querySelector('.tab-btn.active');
                if (activeTab && activeTab.getAttribute('data-filter') !== 'all') {
                    activeFilters.push(activeTab.textContent.trim());
                }

                // Check advanced filters from the filter panel
                const filterMappings = {
                    'filterAgeHealthGroup': 'Health Group',
                    'filterReligion': 'Religion',
                    'filterCivilStatus': 'Civil Status',
                    'filterDateOfBirth': 'DOB',
                    'filterEthnicity': 'Ethnicity',
                    'filterEducation': 'Education',
                    'filterEmploymentStatus': 'Employment',
                    'filter4ps': '4Ps'
                };

                for (const [id, label] of Object.entries(filterMappings)) {
                    const el = document.getElementById(id);
                    if (el && el.value) {
                        let val = el.value;
                        if (id === 'filterDateOfBirth') {
                            const d = new Date(val);
                            val = d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        }
                        activeFilters.push(`${label}: ${val}`);
                    }
                }

                // Check search
                const searchInput = document.getElementById('searchInput');
                if (searchInput && searchInput.value.trim()) {
                    activeFilters.push(`Search: "${searchInput.value.trim()}"`);
                }

                if (activeFilters.length > 0) {
                    printTitle.textContent += " - " + activeFilters.join(', ');
                }
            }

            // Get the print footer
            const printFooter = document.querySelector('.print-footer').cloneNode(true);

            // Collect all styles to maintain layout
            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style'))
                .map(s => s.outerHTML).join('\n');

            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Resident Masterlist</title>
                    ${styles}
                    <style>
                        body { background: white !important; color: black !important; padding: 20px !important; }
                        .main-content, .dashboard-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                        .print-only { display: flex !important; }
                        .residents-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
                        .residents-table th, .residents-table td { border: 1px solid #333 !important; padding: 10px !important; font-size: 11px !important; text-align: left; }
                        .residents-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                        .avatar { display: none !important; }
                        .resident-name { gap: 5px !important; }
                        @page { size: landscape; margin: 15mm; }
                    </style>
                </head>
                <body>
                    <div class="dashboard-content">
                        ${printHeader.outerHTML}
                        <table class="residents-table">
                            ${tableHeader.outerHTML}
                            <tbody>${rowsHtml}</tbody>
                        </table>
                        ${printFooter.outerHTML}
                    </div>
                </body>
                </html>
            `);
            doc.close();

            // Trigger print after a short delay to ensure styles/content are loaded
            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            }, 500);
        });
    }
    
    // Export button (if exists)
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const timestamp = new Date().toISOString().split('T')[0];
            residentsTable.exportToCSV(`residents-${timestamp}.csv`);
        });
    }
    
    // Apply Filters button
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            applyAdvancedFilters();
        });
    }
    
    // Clear Filters button
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', () => {
            clearAdvancedFilters();
        });
    }
}

function showCreateResidentModal() {
    // Redirect to create resident page
    window.location.href = 'model/create-resident.php';
}

function showAdvancedFilters() {
    const filterPanel = document.getElementById('filterPanel');
    const filterBtn = document.getElementById('filterBtn');
    
    if (filterPanel.style.display === 'none') {
        filterPanel.style.display = 'block';
        filterBtn.classList.add('active');
    } else {
        filterPanel.style.display = 'none';
        filterBtn.classList.remove('active');
    }
}

function refreshData() {
    const refreshBtn = document.getElementById('refreshBtn');
    const icon = refreshBtn.querySelector('i');
    
    // Add spin animation
    icon.style.animation = 'spin 0.5s linear';
    
    setTimeout(() => {
        icon.style.animation = '';
        residentsTable.refresh();
        
        // Show success message
        showNotification('Data refreshed successfully', 'success');
        console.log('Residents data refreshed');
    }, 500);
}

// ===================================
// View Toggle
// ===================================
function initializeViewToggle() {
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');
            switchView(view);
        });
    });
}

function switchView(view) {
    const viewButtons = document.querySelectorAll('.view-btn');
    const tableContainer = document.querySelector('.table-container');
    const gridContainer = document.getElementById('residentsGrid');
    
    if (!tableContainer || !gridContainer) return;

    // Update toggle buttons active state
    viewButtons.forEach(vBtn => {
        vBtn.classList.toggle('active', vBtn.getAttribute('data-view') === view);
    });

    // Toggle visibility of containers
    if (view === 'grid') {
        tableContainer.style.display = 'none';
        gridContainer.style.display = 'grid';
    } else {
        tableContainer.style.display = '';
        gridContainer.style.display = 'none';
    }
    
    // Save preference to localStorage
    localStorage.setItem('residentsViewPreference', view);
}

function applySavedView() {
    const savedView = localStorage.getItem('residentsViewPreference');
    if (savedView) {
        switchView(savedView);
    }
}

// ===================================
// Action Menu Handlers
// ===================================
function initializeActionMenus() {
    // Use event delegation for dynamically loaded rows
    const tableBody = document.getElementById('residentsTableBody');
    
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const actionBtn = e.target.closest('.btn-action');
            if (actionBtn) {
                const row = actionBtn.closest('tr');
                showActionMenu(row, actionBtn);
            }
        });
    }
}

function showActionMenu(row, button) {
    const residentName = row.querySelector('.resident-name span:last-child')?.textContent;
    const residentId = row.querySelectorAll('td')[1]?.textContent;

    // Get current activity status from the badge in column index 5
    const activityBadge = row.querySelectorAll('td')[5]?.querySelector('.badge');
    const currentStatus = activityBadge?.textContent.trim() || 'Active';
    
    // Remove any existing action menus
    document.querySelectorAll('.action-menu').forEach(menu => menu.remove());
    
    // Build menu items based on permissions
    const perms = window.BIS_PERMS || {};
    let menuHtml = '';

    if (perms.resident_view) {
        menuHtml += `
        <div class="action-menu-item" data-action="view">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </div>`;
    }
    if (perms.resident_edit) {
        menuHtml += `
        <div class="action-menu-item" data-action="edit">
            <i class="fas fa-edit"></i>
            <span>Edit Resident</span>
        </div>
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
                <div class="action-menu-item submenu-item ${currentStatus === 'Deceased' ? 'submenu-current' : ''}" data-status="Deceased">
                    <i class="fas fa-circle status-dot status-dot-deceased"></i>
                    <span>Deceased</span>
                    ${currentStatus === 'Deceased' ? '<i class="fas fa-check submenu-check"></i>' : ''}
                </div>
            </div>
        </div>`;
    }
    menuHtml += `
        <div class="action-menu-item" data-action="print">
            <i class="fas fa-print"></i>
            <span>Print ID</span>
        </div>`;
    if (perms.resident_delete) {
        menuHtml += `
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Delete Resident</span>
        </div>`;
    }

    // Create action menu
    const menu = document.createElement('div');
    menu.className = 'action-menu';
    menu.innerHTML = menuHtml;
    
    // Position menu
    const rect = button.getBoundingClientRect();
    menu.style.position = 'fixed';
    menu.style.top = `${rect.bottom + 5}px`;
    menu.style.left = `${rect.left - 150}px`;
    
    document.body.appendChild(menu);
    
    // Add click handlers for regular menu items (not submenu parents or submenu items)
    menu.querySelectorAll('.action-menu-item:not(.has-submenu):not(.submenu-item)').forEach(item => {
        item.addEventListener('click', () => {
            const action = item.getAttribute('data-action');
            handleAction(action, residentName, residentId, row);
            menu.remove();
        });
    });

    // Handle submenu item clicks
    menu.querySelectorAll('.submenu-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();
            const newStatus = item.getAttribute('data-status');
            const actionBtn = row.querySelector('.btn-action');
            const dbResidentId = actionBtn ? actionBtn.getAttribute('data-resident-id') : null;
            updateActivityStatus(dbResidentId, newStatus, row, currentStatus);
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

function handleAction(action, name, id, row) {
    console.log(`Action: ${action} for ${name} (${id})`);
    
    // Get the actual database ID from the row
    const actionBtn = row.querySelector('.btn-action');
    const residentId = actionBtn ? (actionBtn.getAttribute('data-resident-id') || actionBtn.getAttribute('data-id')) : null;
    
    switch(action) {
        case 'view':
            if (residentId) {
                // Navigate to resident profile page
                window.location.href = `resident_profile.php?id=${residentId}`;
            } else {
                showNotification('Unable to load resident details', 'error');
            }
            break;
            
        case 'edit':
            if (residentId) {
                // Navigate to edit resident page
                window.location.href = `model/edit-resident.php?id=${residentId}`;
            } else {
                showNotification('Unable to load resident details', 'error');
            }
            break;
            
        case 'print':
            alert(`Print ID\n\nResident: ${name}\nID: ${id}\n\nThis will generate and print a resident ID card.`);
            break;
            
        case 'delete':
            // Validate residentId before proceeding
            if (!residentId) {
                console.error('Delete failed: No resident ID found');
                showNotification('Error: Unable to identify resident to delete', 'error');
                return;
            }
            
            console.log(`Attempting to delete resident ID: ${residentId}`);
            
            if (confirm(`Are you sure you want to delete ${name}?\n\nThe record will be moved to the archive and can be restored later.`)) {
                const formData = new FormData();
                formData.append('id', residentId);
                
                console.log('Sending delete request to server...');
                
                fetch('model/delete_resident.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error('Server did not return JSON response');
                    }
                    
                    return response.json();
                })
                .then(data => {
                    console.log('Server response:', data);
                    
                    if(data.success) {
                        // Show success notification
                        showNotification(data.message, 'success');
                        
                        // Add transition for smooth fade out
                        row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        
                        // Wait for animation to complete, then remove row and update table
                        setTimeout(() => {
                            // Remove the row from DOM
                            row.remove();
                            
                            // Update the EnhancedTable instance
                            // This will refresh the internal arrays and recalculate pagination
                            residentsTable.allRows = Array.from(residentsTable.tbody.querySelectorAll('tr'));
                            residentsTable.filteredRows = [...residentsTable.allRows];
                            
                            // Check if current page is now empty
                            const totalRows = residentsTable.filteredRows.length;
                            const totalPages = Math.ceil(totalRows / residentsTable.options.pageSize);
                            
                            // If current page is beyond total pages, go to last page
                            if (residentsTable.currentPage > totalPages && totalPages > 0) {
                                residentsTable.currentPage = totalPages;
                            }
                            
                            // Update the display and pagination
                            residentsTable.updateDisplay();
                            residentsTable.updatePagination();
                            
                            console.log(`Resident deleted. Remaining residents: ${totalRows}`);
                        }, 300);
                    } else {
                        console.error('Delete failed:', data.message);
                        showNotification('Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Delete request error:', error);
                    showNotification('An error occurred while processing your request: ' + error.message, 'error');
                });
            }
            break;
    }
}


// ===================================
// Update Activity Status
// ===================================
function updateActivityStatus(residentId, newStatus, row, currentStatus) {
    if (!residentId) {
        showNotification('Error: Unable to identify resident', 'error');
        return;
    }

    if (newStatus === currentStatus) {
        showNotification(`Resident is already marked as ${newStatus}`, 'info');
        return;
    }

    const formData = new FormData();
    formData.append('id', residentId);
    formData.append('status', newStatus);

    fetch('model/update_activity_status.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server did not return JSON response');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update the badge in the table row (column index 5)
            const statusCell = row.querySelectorAll('td')[5];
            if (statusCell) {
                const badge = statusCell.querySelector('.badge');
                if (badge) {
                    // Remove old badge class
                    badge.classList.remove('badge-active', 'badge-inactive', 'badge-deceased');
                    // Add new badge class
                    badge.classList.add('badge-' + newStatus.toLowerCase());
                    // Update text
                    badge.textContent = newStatus;
                }
            }
            showNotification(data.message, 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Update status error:', error);
        showNotification('An error occurred: ' + error.message, 'error');
    });
}

// ===================================
// Advanced Filter Functions
// ===================================
function applyAdvancedFilters() {
    if (!residentsTable) return;

    const getFilterValue = (id) => {
        const el = document.getElementById(id);
        return el ? el.value : '';
    };

    const filters = {
        ageGroup: getFilterValue('filterAgeGroup'),
        dateOfBirth: getFilterValue('filterDateOfBirth'),
        religion: getFilterValue('filterReligion'),
        ethnicity: getFilterValue('filterEthnicity'),
        civilStatus: getFilterValue('filterCivilStatus'),
        education: getFilterValue('filterEducation'),
        employmentStatus: getFilterValue('filterEmploymentStatus'),
        fourPs: getFilterValue('filter4ps'),
        ageHealthGroup: getFilterValue('filterAgeHealthGroup')
    };
    
    console.log('Applying filters:', filters);
    
    // Filter the table based on selected criteria
    residentsTable.filter(row => {
        const cells = Array.from(row.cells);
        
        // Get the date of birth cell (index 4) and extract age
        const dobCell = cells[4]?.textContent || '';
        const ageMatch = dobCell.match(/- (\d+)$/);
        const age = ageMatch ? parseInt(ageMatch[1]) : 0;
        
        // Get data attributes from the row
        const rowData = {
            religion: row.getAttribute('data-religion') || '',
            ethnicity: row.getAttribute('data-ethnicity') || '',
            civilStatus: row.getAttribute('data-civil-status') || '',
            education: row.getAttribute('data-education') || '',
            employment: row.getAttribute('data-employment') || '',
            fourPs: row.getAttribute('data-fourps') || '',
            ageHealthGroup: row.getAttribute('data-age-health-group') || ''
        };
        
        // Age Group filter
        if (filters.ageGroup) {
            if (filters.ageGroup === '0-17' && (age < 0 || age > 17)) return false;
            if (filters.ageGroup === '18-35' && (age < 18 || age > 35)) return false;
            if (filters.ageGroup === '36-59' && (age < 36 || age > 59)) return false;
            if (filters.ageGroup === '60+' && age < 60) return false;
        }
        
        // Date of Birth filter (exact match)
        if (filters.dateOfBirth) {
            const [year, month, day] = filters.dateOfBirth.split('-');
            const formattedFilterDate = `${month}/${day}/${year}`;
            const dobInCell = dobCell.split(' - ')[0];
            if (dobInCell !== formattedFilterDate) return false;
        }
        
        // Religion filter
        if (filters.religion && rowData.religion.toLowerCase() !== filters.religion.toLowerCase()) {
            return false;
        }
        
        // Ethnicity filter
        if (filters.ethnicity && rowData.ethnicity.toLowerCase() !== filters.ethnicity.toLowerCase()) {
            return false;
        }
        
        // Civil Status filter
        if (filters.civilStatus && rowData.civilStatus.toLowerCase() !== filters.civilStatus.toLowerCase()) {
            return false;
        }
        
        // Educational Attainment filter
        if (filters.education && rowData.education.toLowerCase() !== filters.education.toLowerCase()) {
            return false;
        }
        
        // Employment Status filter
        if (filters.employmentStatus && rowData.employment.toLowerCase() !== filters.employmentStatus.toLowerCase()) {
            return false;
        }
        
        // 4Ps Member filter
        if (filters.fourPs && rowData.fourPs.toLowerCase() !== filters.fourPs.toLowerCase()) {
            return false;
        }
        
        // Age/Health Group filter
        if (filters.ageHealthGroup && rowData.ageHealthGroup.toLowerCase() !== filters.ageHealthGroup.toLowerCase()) {
            return false;
        }
        
        return true;
    });

    // Filter grid cards
    const gridCards = document.querySelectorAll('.resident-card');
    gridCards.forEach(card => {
        const age = parseInt(card.querySelector('.detail-item:first-child .value').textContent);
        const rowData = {
            religion: card.getAttribute('data-religion') || '',
            ethnicity: card.getAttribute('data-ethnicity') || '',
            civilStatus: card.getAttribute('data-civil-status') || '',
            education: card.getAttribute('data-education') || '',
            employment: card.getAttribute('data-employment') || '',
            fourPs: card.getAttribute('data-fourps') || '',
            ageHealthGroup: card.getAttribute('data-age-health-group') || ''
        };

        let show = true;
        
        if (filters.ageGroup) {
            if (filters.ageGroup === '0-17' && (age < 0 || age > 17)) show = false;
            if (filters.ageGroup === '18-35' && (age < 18 || age > 35)) show = false;
            if (filters.ageGroup === '36-59' && (age < 36 || age > 59)) show = false;
            if (filters.ageGroup === '60+' && age < 60) show = false;
        }
        
        if (filters.religion && rowData.religion.toLowerCase() !== filters.religion.toLowerCase()) show = false;
        if (filters.ethnicity && rowData.ethnicity.toLowerCase() !== filters.ethnicity.toLowerCase()) show = false;
        if (filters.civilStatus && rowData.civilStatus.toLowerCase() !== filters.civilStatus.toLowerCase()) show = false;
        if (filters.education && rowData.education.toLowerCase() !== filters.education.toLowerCase()) show = false;
        if (filters.employmentStatus && rowData.employment.toLowerCase() !== filters.employmentStatus.toLowerCase()) show = false;
        if (filters.fourPs && rowData.fourPs.toLowerCase() !== filters.fourPs.toLowerCase()) show = false;
        if (filters.ageHealthGroup && rowData.ageHealthGroup.toLowerCase() !== filters.ageHealthGroup.toLowerCase()) show = false;
        
        card.style.display = show ? '' : 'none';
    });
    
    // Count active filters
    const activeFiltersCount = Object.values(filters).filter(v => v !== '').length;
    
    if (activeFiltersCount > 0) {
        showNotification(`${activeFiltersCount} filter(s) applied successfully`, 'success');
    } else {
        showNotification('No filters selected', 'info');
    }

    // Hide the filter panel after applying
    const filterPanel = document.getElementById('filterPanel');
    const filterBtn = document.getElementById('filterBtn');
    if (filterPanel) {
        filterPanel.style.display = 'none';
        if (filterBtn) filterBtn.classList.remove('active');
    }
}

function clearAdvancedFilters() {
    if (!residentsTable) return;

    const setFilterValue = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value;
    };

    // Reset all filter inputs
    setFilterValue('filterAgeGroup', '');
    setFilterValue('filterDateOfBirth', '');
    setFilterValue('filterReligion', '');
    setFilterValue('filterEthnicity', '');
    setFilterValue('filterCivilStatus', '');
    setFilterValue('filterEducation', '');
    setFilterValue('filterEmploymentStatus', '');
    setFilterValue('filter4ps', '');
    setFilterValue('filterAgeHealthGroup', '');
    
    // Reset the table
    residentsTable.reset();
    
    // Reset grid cards
    const gridCards = document.querySelectorAll('.resident-card');
    gridCards.forEach(card => card.style.display = '');
    
    showNotification('Filters cleared', 'success');
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

// Add animations
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
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ── Change Status Submenu ── */
    .action-menu-item.has-submenu {
        position: relative;
    }

    .action-menu-item.has-submenu .submenu-arrow {
        margin-left: auto;
        font-size: 10px;
        color: #9ca3af;
        width: auto !important;
    }

    .action-submenu {
        display: none;
        position: absolute;
        right: calc(100% + 6px);
        top: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.12);
        padding: 6px;
        min-width: 160px;
        z-index: 10000;
        animation: fadeIn 0.15s ease;
    }

    .action-menu-item.has-submenu:hover .action-submenu {
        display: block;
    }

    .submenu-item {
        gap: 10px;
        padding: 9px 12px;
    }

    .submenu-item.submenu-current {
        background-color: #f0f9ff;
        font-weight: 600;
    }

    .status-dot {
        font-size: 8px !important;
        width: 8px !important;
        flex-shrink: 0;
    }

    .status-dot-active   { color: #22c55e; }
    .status-dot-inactive { color: #f59e0b; }
    .status-dot-deceased { color: #ef4444; }

    .submenu-check {
        margin-left: auto;
        font-size: 11px !important;
        width: auto !important;
        color: #3b82f6;
    }
`;
document.head.appendChild(style);
