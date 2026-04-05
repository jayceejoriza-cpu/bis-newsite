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
    
    // Check URL parameters for initial filters
    applyUrlFilters();
    
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
         responsive: true,
        // Hide deceased residents from default view; they remain searchable
        defaultFilter: (row) => {
            const status = row.getAttribute('data-activity-status');
            return status !== 'Deceased';
        },
        customSearch: (row, term) => {
            const id = row.cells[0]?.textContent.toLowerCase() || '';
            const nameEl = row.querySelector('.resident-name span:last-child');
            const name = nameEl ? nameEl.textContent.toLowerCase() : (row.cells[1]?.textContent.toLowerCase() || '');
            
            if (id.includes(term) || name.includes(term)) {
                return true;
            }
            
            // Allow matching "First Last" to "Last, First" by checking each word
            const searchWords = term.split(/\s+/).filter(word => word.length > 0);
            if (searchWords.length > 0) {
                return searchWords.every(word => name.includes(word));
            }
            
            return false;
        },
        onDisplayUpdate: (visibleRows) => {
            // Sync grid view with visible table rows
            const gridCards = document.querySelectorAll('.resident-card');
            const gridContainer = document.getElementById('residentsGrid');
            
            gridCards.forEach(card => card.style.display = 'none'); // Hide all first
            
            const existingEmpty = gridContainer.querySelector('.dynamic-empty-state');
            if (existingEmpty) existingEmpty.remove();
            
            if (visibleRows.length === 0 && gridCards.length > 0) {
                gridContainer.insertAdjacentHTML('beforeend', `
                    <div class="dynamic-empty-state" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #6b7280;">
                        <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                        <p style="font-size: 16px; font-weight: 500;">No results found</p>
                        <p style="font-size: 14px; margin-top: 5px;">Try adjusting your search or filter criteria</p>
                    </div>
                `);
            } else {
                visibleRows.forEach(row => {
                    const btnAction = row.querySelector('.btn-action');
                    if (btnAction) {
                        const residentId = btnAction.getAttribute('data-resident-id');
                        const card = document.querySelector(`.resident-card[data-resident-id="${residentId}"]`);
                        if (card) {
                            card.style.display = '';
                        }
                    }
                });
            }
        }
    });
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('sort_residentsTable')) {
        residentsTable.sortByColumn(1);
    }
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
    
    // Close filter panel when clicking outside
    initializeFilterPanelOutsideClick();
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
    
    // Update URL parameters
    const url = new URL(window.location);
    if (filterType !== 'all') {
        url.searchParams.set('tab', filterType);
    } else {
        url.searchParams.delete('tab');
    }
    window.history.replaceState({}, '', url);
    
    if (filterType === 'all') {
        residentsTable.filter(null);
        return;
    }
    
    residentsTable.filter(row => {
        const cells = Array.from(row.cells);
        
        switch(filterType) {
            case 'verified':
                // Verification status column is not available in the table
                return true;
                
            case 'voters':
                const voterBadge = cells[3]?.querySelector('.badge');
                return voterBadge?.textContent.trim().toLowerCase() === 'yes';
                
            case 'active':
                const activityBadge = cells[6]?.querySelector('.badge');
                return activityBadge?.textContent.trim().toLowerCase() === 'alive';
                
            default:
                return true;
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
        // Debounce search for better performance
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                residentsTable.search(e.target.value);
            }, 300);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            residentsTable.search('');
            searchInput.focus();
        });
    }
}

// ===================================
// URL Filters
// ===================================
function applyUrlFilters() {
    const urlParams = new URLSearchParams(window.location.search);
    let hasFilters = false;
    
    if (urlParams.has('tab')) {
        const tab = urlParams.get('tab');
        const tabBtn = document.querySelector(`.tab-btn[data-filter="${tab}"]`);
        if (tabBtn) {
            document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
            tabBtn.classList.add('active');
            applyFilter(tab);
        }
    }

    // Maps URL param keys to DOM element IDs
    const filterMappings = [
        'filterSex', 'filterPurok', 'filterAgeHealthGroup', 'filterPwdStatus',
        'filterReligion', 'filterCivilStatus', 'filterDateOfBirth', 'filterEthnicity',
        'filterEducation', 'filterOccupation', 'filterEmploymentStatus', 'filter4ps',
        'filterVoterStatus', 'filterMembershipType', 'filterPhilhealthCategory',
        'filterMedicalHistory', 'filterUsingFpMethod', 'filterFpMethodsUsed', 'filterFpStatus'
    ];

    if (urlParams.has('filterAgeGroup')) {
        let val = urlParams.get('filterAgeGroup');
        if (val === '60+') val = 'Senior Citizen (60+ years)';
        const el = document.getElementById('filterAgeHealthGroup');
        if (el) {
            el.value = val;
            hasFilters = true;
        }
    }

    for (const elementId of filterMappings) {
        if (urlParams.has(elementId)) {
            const el = document.getElementById(elementId);
            if (el) {
                el.value = urlParams.get(elementId);
                hasFilters = true;
            }
        }
    }
    
    if (hasFilters) {
        // Apply the advanced filters directly
        applyAdvancedFilters();
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
                fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
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
                    'filterAgeGroup': 'Age Group',
                    'filterReligion': 'Religion',
                    'filterEthnicity': 'Ethnicity',
                    'filterCivilStatus': 'Civil Status',
                    'filterEducation': 'Education',
                    'filterEmploymentStatus': 'Employment',
                    'filter4ps': '4Ps',
                    'filterAgeHealthGroup': 'Health Group',
                    'filterPwdStatus': 'Disability Status',
                    'filterDateOfBirth': 'Date of Birth',
                    'filterSex': 'Sex',
                    'filterPurok': 'Purok',
                    'filterOccupation': 'Occupation',
                    'filterVoterStatus': 'Voter Status',
                    'filterMembershipType': 'Philhealth',
                    'filterPhilhealthCategory': 'Philhealth Category'
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
                fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
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
        
        // Clear search input
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        // Clear all filters
        const setFilterValue = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.value = value;
        };

        const url = new URL(window.location);
        const filterMappings = [
            'filterSex', 'filterPurok', 'filterAgeHealthGroup', 'filterPwdStatus',
            'filterReligion', 'filterCivilStatus', 'filterDateOfBirth', 'filterEthnicity',
            'filterEducation', 'filterOccupation', 'filterEmploymentStatus', 'filter4ps',
            'filterVoterStatus', 'filterMembershipType', 'filterPhilhealthCategory',
            'filterMedicalHistory', 'filterUsingFpMethod', 'filterFpMethodsUsed', 'filterFpStatus'
        ];
        filterMappings.forEach(id => {
            setFilterValue(id, '');
            url.searchParams.delete(id);
        });
        setFilterValue('filterAgeGroup', ''); // Legacy cleanup
        url.searchParams.delete('tab');
        window.history.replaceState({}, '', url);
        
        // Reset Tabs
        document.querySelectorAll('.tab-btn').forEach(tab => tab.classList.remove('active'));
        const allTab = document.querySelector('.tab-btn[data-filter="all"]');
        if (allTab) allTab.classList.add('active');

        // Clear the filter notification badge
        updateFilterNotification(0);

        residentsTable.reset();
        residentsTable.sortByColumn(1);
        
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

    // Get current activity status
    let currentStatus = row.getAttribute('data-activity-status');
    if (!currentStatus) {
        const activityBadge = row.querySelectorAll('td')[6]?.querySelector('.badge');
        currentStatus = activityBadge?.textContent.trim() || 'Alive';
    }
    
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
        `;
    }
    if (perms.resident_status) {
        menuHtml += `
        <div class="action-menu-item has-submenu" data-action="change-status">
            <i class="fas fa-toggle-on"></i>
            <span>Change Status</span>
            <i class="fas fa-chevron-right submenu-arrow"></i>
            <div class="action-submenu">
                <div class="action-menu-item submenu-item ${currentStatus === 'Alive' ? 'submenu-current' : ''}" data-status="Alive">
                    <i class="fas fa-circle status-dot status-dot-alive"></i>
                    <span>Alive</span>
                    ${currentStatus === 'Alive' ? '<i class="fas fa-check submenu-check"></i>' : ''}
                </div>
                <div class="action-menu-item submenu-item ${currentStatus === 'Deceased' ? 'submenu-current' : ''}" data-status="Deceased">
                    <i class="fas fa-circle status-dot status-dot-deceased"></i>
                    <span>Deceased</span>
                    ${currentStatus === 'Deceased' ? '<i class="fas fa-check submenu-check"></i>' : ''}
                </div>
            </div>
        </div>`;
    }
    if (perms.resident_print_id) {
        menuHtml += `
        <div class="action-menu-item" data-action="print">
            <i class="fas fa-print"></i>
            <span>Print ID</span>
        </div>`;
    }
    if (perms.resident_archive || perms.resident_delete) {
        menuHtml += `
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Archive Resident</span>
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
    // Align right edge of menu with right edge of button
    menu.style.left = 'auto';
    menu.style.right = `${window.innerWidth - rect.right}px`;
    
    document.body.appendChild(menu);
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
                window.location.href = `resident_profile.php?id=${residentId}&edit=1`;
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
                            residentsTable.filteredRows = [...residentsTable.allRows]
                            
                            // Rebuild allRows from remaining DOM rows (not in tbody since updateDisplay manages it)
                            residentsTable.allRows = residentsTable.allRows.filter(r => r !== row);

                            // Re-apply defaultFilter to filteredRows
                            if (residentsTable.options.defaultFilter) {
                                residentsTable.filteredRows = residentsTable.allRows.filter(
                                    residentsTable.options.defaultFilter
                                );
                            } else {
                                residentsTable.filteredRows = [...residentsTable.allRows];
                            }
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
            // Update the badge in the table row (column index 6)
            const statusCell = row.querySelectorAll('td')[6];
            if (statusCell) {
                const badge = statusCell.querySelector('.badge');
                if (badge) {
                    // Remove old badge class
                    badge.classList.remove('badge-alive', 'badge-deceased');
                    // Add new badge class
                    badge.classList.add('badge-' + newStatus.toLowerCase());
                    // Update text
                    badge.textContent = newStatus;
                }
            }
              // Update data-activity-status so defaultFilter picks up the change
            row.setAttribute('data-activity-status', newStatus);

            // If the new status is Deceased, remove from filteredRows and refresh display
            if (newStatus === 'Deceased' && residentsTable.options.defaultFilter) {
                residentsTable.filteredRows = residentsTable.filteredRows.filter(r => r !== row);
                residentsTable.updateDisplay();
                residentsTable.updatePagination();
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
        sex: getFilterValue('filterSex'),
        purok: getFilterValue('filterPurok'),
        ageHealthGroup: getFilterValue('filterAgeHealthGroup'),
        pwdStatus: getFilterValue('filterPwdStatus'),
        religion: getFilterValue('filterReligion'),
        civilStatus: getFilterValue('filterCivilStatus'),
        dateOfBirth: getFilterValue('filterDateOfBirth'),
        ethnicity: getFilterValue('filterEthnicity'),
        education: getFilterValue('filterEducation'),
        occupation: getFilterValue('filterOccupation'),
        employmentStatus: getFilterValue('filterEmploymentStatus'),
        fourPs: getFilterValue('filter4ps'),
        voterStatus: getFilterValue('filterVoterStatus'),
        membershipType: getFilterValue('filterMembershipType'),
        philhealthCategory: getFilterValue('filterPhilhealthCategory'),
        medicalHistory: getFilterValue('filterMedicalHistory'),
        usingFpMethod: getFilterValue('filterUsingFpMethod'),
        fpMethodsUsed: getFilterValue('filterFpMethodsUsed'),
        fpStatus: getFilterValue('filterFpStatus')
    };
    
    console.log('Applying filters:', filters);
    
    // Update URL parameters
    const url = new URL(window.location);
    const filterMappings = {
        'filterSex': filters.sex,
        'filterPurok': filters.purok,
        'filterAgeHealthGroup': filters.ageHealthGroup,
        'filterPwdStatus': filters.pwdStatus,
        'filterReligion': filters.religion,
        'filterCivilStatus': filters.civilStatus,
        'filterDateOfBirth': filters.dateOfBirth,
        'filterEthnicity': filters.ethnicity,
        'filterEducation': filters.education,
        'filterOccupation': filters.occupation,
        'filterEmploymentStatus': filters.employmentStatus,
        'filter4ps': filters.fourPs,
        'filterVoterStatus': filters.voterStatus,
        'filterMembershipType': filters.membershipType,
        'filterPhilhealthCategory': filters.philhealthCategory,
        'filterMedicalHistory': filters.medicalHistory,
        'filterUsingFpMethod': filters.usingFpMethod,
        'filterFpMethodsUsed': filters.fpMethodsUsed,
        'filterFpStatus': filters.fpStatus
    };
    for (const [id, val] of Object.entries(filterMappings)) {
        if (val) url.searchParams.set(id, val);
        else url.searchParams.delete(id);
    }
    window.history.replaceState({}, '', url);

    // Filter the table based on selected criteria
    residentsTable.filter(row => {
        const cells = Array.from(row.cells);
        
        // Get the date of birth cell (index 4) and extract age
        const dobCell = cells[4]?.textContent || '';
        
        // Get data attributes from the row
        const rowData = {
            religion: row.getAttribute('data-religion') || '',
            ethnicity: row.getAttribute('data-ethnicity') || '',
            civilStatus: row.getAttribute('data-civil-status') || '',
            education: row.getAttribute('data-education') || '',
            employment: row.getAttribute('data-employment') || '',
            fourPs: row.getAttribute('data-fourps') || '',
            ageHealthGroup: row.getAttribute('data-age-health-group') || '',
            pwdStatus: row.getAttribute('data-pwd-status') || '',
            sex: row.getAttribute('data-sex') || '',
            purok: row.getAttribute('data-purok') || '',
            voterStatus: row.getAttribute('data-voter-status') || '',
            occupation: row.getAttribute('data-occupation') || '',
            membershipType: row.getAttribute('data-membership-type') || '',
            philhealthCategory: row.getAttribute('data-philhealth-category') || '',
            medicalHistory: row.getAttribute('data-medical-history') || '',
            usingFpMethod: row.getAttribute('data-using-fp-method') || '',
            fpMethodsUsed: row.getAttribute('data-fp-methods-used') || '',
            fpStatus: row.getAttribute('data-fp-status') || ''
        };
        
        // Date of Birth filter (exact match)
        if (filters.dateOfBirth) {
            const [year, month, day] = filters.dateOfBirth.split('-');
            const formattedFilterDate = `${month}/${day}/${year}`;
            const dobInCell = dobCell.split(' - ')[0];
            if (dobInCell !== formattedFilterDate) return false;
        }
        
        if (filters.sex && rowData.sex.toLowerCase() !== filters.sex.toLowerCase()) return false;
        if (filters.purok && rowData.purok.toLowerCase() !== filters.purok.toLowerCase()) return false;
        if (filters.ageHealthGroup && rowData.ageHealthGroup.toLowerCase() !== filters.ageHealthGroup.toLowerCase()) return false;
        if (filters.pwdStatus && rowData.pwdStatus.toLowerCase() !== filters.pwdStatus.toLowerCase()) return false;
        if (filters.religion && rowData.religion.toLowerCase() !== filters.religion.toLowerCase()) return false;
        if (filters.ethnicity && rowData.ethnicity.toLowerCase() !== filters.ethnicity.toLowerCase()) return false;
        if (filters.civilStatus && rowData.civilStatus.toLowerCase() !== filters.civilStatus.toLowerCase()) return false;
        if (filters.education && rowData.education.toLowerCase() !== filters.education.toLowerCase()) return false;
        if (filters.occupation && !rowData.occupation.toLowerCase().includes(filters.occupation.toLowerCase())) return false;
        if (filters.employmentStatus && rowData.employment.toLowerCase() !== filters.employmentStatus.toLowerCase()) return false;
        if (filters.fourPs && rowData.fourPs.toLowerCase() !== filters.fourPs.toLowerCase()) return false;
        if (filters.voterStatus && rowData.voterStatus.toLowerCase() !== filters.voterStatus.toLowerCase()) return false;
        if (filters.membershipType && rowData.membershipType.toLowerCase() !== filters.membershipType.toLowerCase()) return false;
        if (filters.philhealthCategory && rowData.philhealthCategory.toLowerCase() !== filters.philhealthCategory.toLowerCase()) return false;
        if (filters.medicalHistory && !rowData.medicalHistory.toLowerCase().includes(filters.medicalHistory.toLowerCase())) return false;
        if (filters.usingFpMethod && rowData.usingFpMethod.toLowerCase() !== filters.usingFpMethod.toLowerCase()) return false;
        if (filters.fpMethodsUsed && rowData.fpMethodsUsed.toLowerCase() !== filters.fpMethodsUsed.toLowerCase()) return false;
        if (filters.fpStatus && rowData.fpStatus.toLowerCase() !== filters.fpStatus.toLowerCase()) return false;
        
        return true;
    });

    // Count active filters
    const activeFiltersCount = Object.values(filters).filter(v => v !== '').length;
    
    // Update the filter notification badge
    updateFilterNotification(activeFiltersCount);
    
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

    const url = new URL(window.location);
    const filterMappings = [
        'filterSex', 'filterPurok', 'filterAgeHealthGroup', 'filterPwdStatus',
        'filterReligion', 'filterCivilStatus', 'filterDateOfBirth', 'filterEthnicity',
        'filterEducation', 'filterOccupation', 'filterEmploymentStatus', 'filter4ps',
        'filterVoterStatus', 'filterMembershipType', 'filterPhilhealthCategory',
        'filterMedicalHistory', 'filterUsingFpMethod', 'filterFpMethodsUsed', 'filterFpStatus'
    ];
    filterMappings.forEach(id => {
        setFilterValue(id, '');
        url.searchParams.delete(id);
    });
    setFilterValue('filterAgeGroup', ''); // Legacy cleanup
    window.history.replaceState({}, '', url);
    
    // Re-apply tab filter without resetting search
    const activeTab = document.querySelector('.tab-btn.active');
    const filterType = activeTab ? activeTab.getAttribute('data-filter') : 'all';
    if (filterType === 'all') {
        residentsTable.filter(null);
    } else {
        applyFilter(filterType);
    }
    
    // Clear the filter notification badge
    updateFilterNotification(0);
    
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

    .action-menu-item.has-submenu.show-submenu .action-submenu {
        display: block;
    }

    .submenu-item {
        gap: 10px;

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

    .status-dot-alive    { color: #22c55e; }
    .status-dot-deceased { color: #ef4444; }

    .submenu-check {
        margin-left: auto;
        font-size: 11px !important;
        width: auto !important;
        color: #3b82f6;
    }
`;
document.head.appendChild(style);
