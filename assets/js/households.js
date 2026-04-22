// ===================================
// Households Page JavaScript
// Enhanced with Table.js Integration
// ===================================

let householdsTable;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Set active navigation
    setActiveNavigation();
    
    // Load households from database
    loadHouseholdsData();
    
    // Initialize all event listeners
    initializeEventListeners();
    
    // Initialize modal event listeners
    initializeModalEventListeners();
    
    // Check URL parameters for modals
    checkUrlModals();
    
    console.log('Households page loaded successfully');
});

// ===================================
// Navigation
// ===================================
function setActiveNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const householdsNavItem = document.querySelector('a[href="households.php"]');
    if (householdsNavItem) {
        householdsNavItem.parentElement.classList.add('active');
    }
}

function checkUrlModals() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('view')) {
        const id = urlParams.get('view');
        if (id) viewHousehold(id);
    } else if (urlParams.has('edit')) {
        const id = urlParams.get('edit');
        if (id) editHousehold(id);
    } else if (urlParams.has('create') && urlParams.get('create') === '1') {
        showCreateHouseholdModal();
    }
}

// ===================================
// Load Households Data
// ===================================
function loadHouseholdsData() {
    const tbody = document.getElementById('householdsTableBody');
    
    // Show loading state
    tbody.innerHTML = `
        <tr>
            <td colspan="5" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; color: var(--primary-color);"></i>
                <p style="margin-top: 10px; color: var(--text-secondary);">Loading households...</p>
            </td>
        </tr>
    `;
    
    // Fetch households from server
    fetch('model/get_households.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayHouseholds(data.data);
                initializeTable();
                applyUrlFilters();
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            <i class="fas fa-home" style="font-size: 48px; color: var(--text-secondary); opacity: 0.3;"></i>
                            <p style="margin-top: 15px; color: var(--text-secondary); font-size: 16px;">No households found</p>
                            <p style="margin-top: 5px; color: var(--text-secondary); font-size: 14px;">Click "Create Household" to add your first household</p>
                        </td>
                    </tr>
                `;
                updateTotalCount(0);
            }
        })
        .catch(error => {
            console.error('Error loading households:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #ef4444;"></i>
                        <p style="margin-top: 15px; color: var(--text-secondary);">Error loading households</p>
                    </td>
                </tr>
            `;
        });
}

function displayHouseholds(households) {
    const tbody = document.getElementById('householdsTableBody');
    tbody.innerHTML = '';
    
    // Avatar colors
    const avatarColors = ['blue', 'pink', 'teal', 'yellow', 'green', 'orange', 'lime', 'indigo', 'cyan', 'purple'];
    
    households.forEach((household, index) => {
        const row = document.createElement('tr');
        row.setAttribute('data-size', household.size);
        row.setAttribute('data-member-count', household.member_count);
        row.setAttribute('data-household-id', household.id);
        row.setAttribute('data-water-source', household.water_source_type || '');
        row.setAttribute('data-toilet-facility', household.toilet_facility_type || '');
        row.setAttribute('data-ownership-status', household.ownership_status || '');
        
        // Get initials for avatar
        const initials = household.head_first_name && household.head_last_name 
            ? (household.head_first_name.charAt(0) + household.head_last_name.charAt(0)).toUpperCase()
            : 'NA';
        
        // Get random avatar color
        const avatarColor = avatarColors[index % avatarColors.length];
        
        // Create clickable link for household head name
        // Ensure that 'household.head_name' is formatted as 'Last Name, First Name Middle Name' from the backend (model/get_households.php)
        const headNameHtml = household.household_head_id
            ? `<a href="resident_profile.php?id=${household.household_head_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${household.head_name || 'N/A'}</a>`
            : `<span>${household.head_name || 'N/A'}</span>`;
        
        row.innerHTML = `
            <td>${household.household_number}</td>
            <td>
                <div class="head-name">
                    <span class="avatar avatar-${avatarColor}">${initials}</span>
                    ${headNameHtml}
                </div>
            </td>
            <td>
                <div class="member-count">
                    <span class="member-badge">
                        <i class="fas fa-user"></i>
                        <span class="count">${household.member_count}</span>
                    </span>
                    <span class="member-indicator active"></span>
                </div>
            </td>
            <td>${household.ownership_status || 'N/A'}</td>
            <td>
                <button class="btn-action">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
    
    updateTotalCount(households.length);
}

// ===================================
// Table Initialization
// ===================================
function initializeTable() {
    // Initialize EnhancedTable with the households table
    householdsTable = new EnhancedTable('householdsTable', {
        sortable: true,
        searchable: true,
        paginated: true,
        pageSize: 10,
        responsive: true,
        customSearch: (row, term) => {
            const householdNumber = row.cells[0]?.textContent.toLowerCase() || '';
            const headName = row.cells[1]?.textContent.toLowerCase() || '';
            return householdNumber.includes(term) || headName.includes(term);
        }
    });
    
    console.log(`Total households: ${householdsTable.getTotalRows()}`);
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
    
    // Action menu handlers
    initializeActionMenus();

    // Advanced filter handlers
    initializeAdvancedFilters();
}

function initializeAdvancedFilters() {
    const filterBtn = document.getElementById('filterBtn');
    const filterPanel = document.getElementById('filterPanel');
    const applyBtn = document.getElementById('applyFiltersBtn');
    const clearBtn = document.getElementById('clearFiltersBtn');

    if (filterBtn && filterPanel) {
        filterBtn.addEventListener('click', () => {
            const isVisible = filterPanel.style.display !== 'none';
            filterPanel.style.display = isVisible ? 'none' : 'block';
            filterBtn.classList.toggle('active', !isVisible);
        });

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (filterPanel.style.display !== 'none' && !filterPanel.contains(e.target) && !filterBtn.contains(e.target)) {
                filterPanel.style.display = 'none';
                filterBtn.classList.remove('active');
            }
        });
    }

    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            applyFilters();
            if (filterPanel) filterPanel.style.display = 'none';
            if (filterBtn) filterBtn.classList.remove('active');
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            const hSize = document.getElementById('filterFamilySize');
            const hWat = document.getElementById('filterWaterSource');
            const hToi = document.getElementById('filterToiletFacility');
            const hOwn = document.getElementById('filterOwnershipStatus');
            if (hSize) hSize.value = '';
            if (hWat) hWat.value = '';
            if (hToi) hToi.value = '';
            if (hOwn) hOwn.value = '';
            applyFilters();
        });
    }
}

function updateFilterNotification(count) {
    const notification = document.getElementById('filterNotification');
    const countSpan = document.getElementById('filterCount');
    const filterBtn = document.getElementById('filterBtn');
    
    if (!notification || !countSpan || !filterBtn) return;
    
    if (count > 0) {
        countSpan.textContent = count > 9 ? '9+' : count;
        notification.style.display = 'flex';
        filterBtn.classList.add('has-active-filters');
    } else {
        notification.style.display = 'none';
        filterBtn.classList.remove('has-active-filters');
    }
}

// ===================================
// Filter Tabs
// ===================================
let currentTabFilter = 'all';

function initializeFilterTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active class from all tabs
            tabButtons.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to clicked tab
            btn.classList.add('active');
            
            currentTabFilter = btn.getAttribute('data-filter');
            applyFilters();
        });
    });
}

function applyFilter(filterType) {
    console.log('Filter applied:', filterType);
    currentTabFilter = filterType;
    applyFilters();
}

function applyFilters() {
    if (!householdsTable) return;
    
    const filterFamilySize = document.getElementById('filterFamilySize')?.value.trim() || '';
    const filterWaterSource = document.getElementById('filterWaterSource')?.value || '';
    const filterToiletFacility = document.getElementById('filterToiletFacility')?.value || '';
    const filterOwnershipStatus = document.getElementById('filterOwnershipStatus')?.value || '';

    // Update URL parameters
    const url = new URL(window.location);
    if (currentTabFilter !== 'all') {
        url.searchParams.set('tab', currentTabFilter);
    } else {
        url.searchParams.delete('tab');
    }
    
    if (filterFamilySize) url.searchParams.set('size', filterFamilySize);
    else url.searchParams.delete('size');
    
    if (filterWaterSource) url.searchParams.set('water', filterWaterSource);
    else url.searchParams.delete('water');
    
    if (filterToiletFacility) url.searchParams.set('toilet', filterToiletFacility);
    else url.searchParams.delete('toilet');
    
    if (filterOwnershipStatus) url.searchParams.set('ownership', filterOwnershipStatus);
    else url.searchParams.delete('ownership');
    
    window.history.replaceState({}, '', url);

    let activeFiltersCount = 0;
    if (filterFamilySize) activeFiltersCount++;
    if (filterWaterSource) activeFiltersCount++;
    if (filterToiletFacility) activeFiltersCount++;
    if (filterOwnershipStatus) activeFiltersCount++;
    
    updateFilterNotification(activeFiltersCount);

    householdsTable.filter(row => {
        let memberCount = 0;
        if (row.hasAttribute('data-member-count')) {
            memberCount = parseInt(row.getAttribute('data-member-count'));
        } else {
            const countEl = row.querySelector('.member-count .count');
            if (countEl) memberCount = parseInt(countEl.textContent);
        }
        
        let tabMatch = true;
        switch(currentTabFilter) {
            case 'single-person': tabMatch = memberCount === 0; break;
            case 'small': tabMatch = memberCount >= 1 && memberCount <= 4; break;
            case 'medium': tabMatch = memberCount >= 5 && memberCount <= 7; break;
            case 'large': tabMatch = memberCount >= 8 && memberCount <= 10; break;
            case 'very-large': tabMatch = memberCount >= 11; break;
        }
        
        if (!tabMatch) return false;

        if (filterFamilySize) {
            if ((memberCount + 1) !== parseInt(filterFamilySize)) return false;
        }

        if (filterWaterSource) {
            const waterSource = row.getAttribute('data-water-source') || '';
            if (waterSource !== filterWaterSource) return false;
        }

        if (filterToiletFacility) {
            const toiletFacility = row.getAttribute('data-toilet-facility') || '';
            if (toiletFacility !== filterToiletFacility) return false;
        }

        if (filterOwnershipStatus) {
            const ownershipStatus = row.getAttribute('data-ownership-status') || '';
            if (ownershipStatus !== filterOwnershipStatus) return false;
        }

        return true;
    });
    
    updateTotalCount(householdsTable.getFilteredRows());
}

function updateTotalCount(count) {
    const totalCountElement = document.getElementById('totalCount');
    if (totalCountElement) {
        if (count !== undefined) {
            totalCountElement.textContent = count;
        } else {
            const visibleRows = document.querySelectorAll('#householdsTableBody tr:not([style*="display: none"])');
            totalCountElement.textContent = visibleRows.length;
        }
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
            currentTabFilter = tab;
            hasFilters = true;
        }
    }
    
    if (urlParams.has('size')) {
        const el = document.getElementById('filterFamilySize');
        if (el) {
            el.value = urlParams.get('size');
            hasFilters = true;
        }
    }
    
    if (urlParams.has('water')) {
        const el = document.getElementById('filterWaterSource');
        if (el) {
            el.value = urlParams.get('water');
            hasFilters = true;
        }
    }
    
    if (urlParams.has('toilet')) {
        const el = document.getElementById('filterToiletFacility');
        if (el) {
            el.value = urlParams.get('toilet');
            hasFilters = true;
        }
    }
    
    if (urlParams.has('ownership')) {
        const el = document.getElementById('filterOwnershipStatus');
        if (el) {
            el.value = urlParams.get('ownership');
            hasFilters = true;
        }
    }
    
    if (hasFilters) {
        applyFilters();
    }
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
                performSearch(e.target.value);
            }, 300);
        });
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            performSearch('');
            searchInput.focus();
        });
    }
}

function performSearch(searchTerm) {
    if (householdsTable) {
        householdsTable.search(searchTerm);
        updateTotalCount(householdsTable.getFilteredRows());
    }
}

// ===================================
// Button Handlers
// ===================================
function initializeButtons() {
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
        printBtn.addEventListener('click', async () => {
            if (!householdsTable || !householdsTable.filteredRows) {
                fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
                window.print();
                return;
            }

            // Fetch barangay info
            let brgyInfo = {
                province_name: 'Province',
                town_name: 'Municipality',
                barangay_name: 'Barangay',
                barangay_logo: '',
                official_emblem: ''
            };
            
            try {
                const response = await fetch('model/get_barangay_info.php');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        brgyInfo = data.data;
                    }
                }
            } catch (error) {
                console.error('Error fetching barangay info:', error);
            }
            
            const brgyLogoHtml = brgyInfo.barangay_logo 
                ? `<img src="${brgyInfo.barangay_logo}" class="logo-img" alt="Barangay Logo">`
                : `<div class="logo-placeholder-box"></div>`;
                
            const govLogoHtml = brgyInfo.official_emblem
                ? `<img src="${brgyInfo.official_emblem}" class="logo-img" alt="Official Emblem">`
                : `<div class="logo-placeholder-box"></div>`;

            // Create a hidden iframe for printing
            let printFrame = document.getElementById('householdMasterlistPrintFrame');
            if (!printFrame) {
                printFrame = document.createElement('iframe');
                printFrame.id = 'householdMasterlistPrintFrame';
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

            // Create table header
            const tableHeaderHtml = `
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No.</th>
                        <th>Household Number</th>
                        <th>Household Head</th>
                        <th style="text-align: center; width: 100px;">Members</th>
                    </tr>
                </thead>
            `;

            // Build rows
            let rowsHtml = '';
            householdsTable.filteredRows.forEach((row, index) => {
                const no = index + 1;
                const hhNum = row.cells[0]?.textContent.trim() || '';
                
                const headNameEl = row.querySelector('.head-name span:last-child');
                const headName = headNameEl ? headNameEl.textContent.trim() : (row.cells[1]?.textContent.trim() || '');
                
                const memberCountEl = row.querySelector('.member-count .count');
                const memberCount = memberCountEl ? memberCountEl.textContent.trim() : (row.cells[2]?.textContent.trim() || '');

                rowsHtml += `
                    <tr style="display: table-row;">
                        <td style="text-align: center;">${no}</td>
                        <td>${hhNum}</td>
                        <td>${headName}</td>
                        <td style="text-align: center;">${memberCount}</td>
                    </tr>
                `;
            });

            let finalTitle = "Household Masterlist";
            const printHeader = document.querySelector('.print-header');
            if (printHeader) {
                const countBadge = printHeader.querySelector('#printTotalRecords');
                if (countBadge) countBadge.textContent = householdsTable.filteredRows.length;
                
                const printTitle = printHeader.querySelector('.print-list-title');
                if (printTitle) {
                    const activeFilters = [];
                    
                    const activeTab = document.querySelector('.tab-btn.active');
                    if (activeTab && activeTab.getAttribute('data-filter') !== 'all') {
                        activeFilters.push(activeTab.textContent.trim());
                    }

                    const filterMappings = {
                        'filterFamilySize': 'Family Size',
                        'filterWaterSource': 'Water Source',
                        'filterToiletFacility': 'Toilet Facility'
                    };

                    for (const [id, label] of Object.entries(filterMappings)) {
                        const el = document.getElementById(id);
                        if (el && el.value) {
                            activeFilters.push(`${label}: ${el.value}`);
                        }
                    }

                    const searchInput = document.getElementById('searchInput');
                    if (searchInput && searchInput.value.trim()) {
                        activeFilters.push(`Search: "${searchInput.value.trim()}"`);
                    }

                    if (activeFilters.length > 0) {
                        finalTitle += " - " + activeFilters.join(', ');
                    }
                }
            }

            const printFooter = document.querySelector('.print-footer') ? document.querySelector('.print-footer').cloneNode(true) : null;
            const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).map(s => s.outerHTML).join('\n');

            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
                    <title>Household Masterlist</title>
                    ${styles}
                    <style>
                        body { background: white !important; color: black !important; padding: 20px !important; }
                        .main-content, .dashboard-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                        .print-only { display: flex !important; }
                        .data-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
                        .data-table th, .data-table td { border: 1px solid #333 !important; padding: 10px !important; font-size: 11px !important; text-align: left; }
                        .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                        .avatar { display: none !important; }
                        .head-name { gap: 5px !important; }
                        .member-indicator { display: none !important; }
                        .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                        .header-center { flex: 1; }
                        .header-center p { margin: 2px 0; font-size: 14px; }
                        .header-center .brgy-name { font-weight: bold; font-size: 16px; margin-top: 5px; }
                        .logo-img { width: 80px; height: 80px; object-fit: contain; }
                        .logo-placeholder-box { width: 80px; height: 80px; }
                        @page { size: A4; margin: 15mm; }
                    </style>
                </head>
                <body>
                    <div class="dashboard-content">
                        <div class="cert-header">
                            ${brgyLogoHtml}
                            <div class="header-center">
                                <p>Republic of the Philippines</p>
                                <p>Province of ${brgyInfo.province_name || 'Province'}</p>
                                <p>Municipality of ${brgyInfo.town_name || 'Municipality'}</p>
                                <p class="brgy-name">${(brgyInfo.barangay_name || 'Barangay').toUpperCase()}</p>
                            </div>
                            ${govLogoHtml}
                        </div>
                        <div style="text-align: center; margin: 15px 0;">
                            <h3 style="margin: 0; text-transform: uppercase;">${finalTitle}</h3>
                            <p style="margin: 5px 0 0 0; font-size: 12px;">Total Records: ${householdsTable.filteredRows.length}</p>
                        </div>
                        <table class="data-table">
                            ${tableHeaderHtml}
                            <tbody>${rowsHtml}</tbody>
                        </table>
                        ${printFooter ? printFooter.outerHTML : ''}
                    </div>
                </body>
                </html>
            `);
            doc.close();

            // Trigger print
            setTimeout(() => {
                fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
            }, 500);
        });
    }
}

function showCreateHouseholdModal() {
    if (window.BIS_PERMS && window.BIS_PERMS.household_edit === false) {
        showNotification('Permission denied. You do not have edit privileges.', 'error');
        const url = new URL(window.location);
        url.searchParams.delete('create');
        window.history.replaceState({}, '', url);
        return;
    }

    const modal = document.getElementById('createHouseholdModal');
    if (modal) {
        modal.classList.add('show');
        
        // Reset form
        resetHouseholdForm();
        
        // Update URL parameter
        const url = new URL(window.location);
        url.searchParams.set('create', '1');
        url.searchParams.delete('view');
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url);
        
        console.log('Create Household Modal opened');
    }
}

function closeCreateHouseholdModal() {
    const modal = document.getElementById('createHouseholdModal');
    if (modal) {
        modal.classList.remove('show');
        
        // Reset form
        resetHouseholdForm();
        
        // Update URL parameter
        const url = new URL(window.location);
        url.searchParams.delete('create');
        url.searchParams.delete('view');
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url);
        
        console.log('Create Household Modal closed');
    }
}

function resetHouseholdForm() {
    const form = document.getElementById('createHouseholdForm');
    if (form) {
        form.reset();
    }
    
    // Reset household head info
    document.getElementById('headFullName').textContent = 'N/A';
    document.getElementById('headDateOfBirth').textContent = 'N/A';
    document.getElementById('headSex').textContent = 'N/A';
    document.getElementById('selectedResidentId').value = '';
    
    // Remove readonly attributes from inputs and reset styling
    const householdNumberInput = document.getElementById('householdNumber');
    householdNumberInput.removeAttribute('readonly');
    householdNumberInput.style.backgroundColor = '';
    householdNumberInput.style.cursor = '';
    
    document.getElementById('householdContact').removeAttribute('readonly');
    document.getElementById('householdAddress').removeAttribute('readonly');
    document.getElementById('ownershipStatus').removeAttribute('disabled');
    document.getElementById('ownershipStatus').value = 'Owned';
    document.getElementById('landlordName').value = '';
    document.getElementById('landlordName').removeAttribute('readonly');
    document.getElementById('landlordNameId').value = '';
    document.getElementById('landlordNameGroup').style.display = 'none';
    
    const waterSourceInput = document.getElementById('waterSource');
    if (waterSourceInput) { waterSourceInput.removeAttribute('disabled'); waterSourceInput.style.cursor = ''; }
    
    const toiletFacilityInput = document.getElementById('toiletFacility');
    if (toiletFacilityInput) { toiletFacilityInput.removeAttribute('disabled'); toiletFacilityInput.style.cursor = ''; }
    
    document.getElementById('householdNotes').removeAttribute('readonly');
    
    const addMemberBtn = document.getElementById('addMemberBtn');
    if (addMemberBtn) {
        addMemberBtn.style.display = '';
    }
    
    const saveBtn = document.getElementById('saveHouseholdBtn');
    if (saveBtn) {
        saveBtn.style.display = '';
        saveBtn.innerHTML = 'Save';
    }
    
    // Reset modal title to Create
    const modal = document.getElementById('createHouseholdModal');
    const modalTitle = modal.querySelector('.household-modal-header h3');
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="fas fa-home"></i> Community Household';
    }
    
    // Remove household ID and view mode attributes
    form.removeAttribute('data-household-id');
    form.removeAttribute('data-view-mode');
    
    // Clear members table
    const tbody = document.getElementById('membersTableBody');
    if (tbody) {
        tbody.innerHTML = '<tr class="no-members-row"><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-secondary);">No members added yet</td></tr>';
    }
    
    const actionTh = document.querySelector('.members-table th:last-child');
    if (actionTh) actionTh.style.display = '';
    
    // Regenerate household number
    if (typeof generateHouseholdNumber === 'function') {
        generateHouseholdNumber();
    }
}

function refreshData() {
    const refreshBtn = document.getElementById('refreshBtn');
    const icon = refreshBtn.querySelector('i');
    
    // Add spin animation
    icon.style.animation = 'spin 0.5s linear';
    
    setTimeout(() => {
        icon.style.animation = '';
        
        // Reset filters and search
        document.getElementById('searchInput').value = '';
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector('.tab-btn[data-filter="all"]').classList.add('active');
        
        // Show all rows
        document.querySelectorAll('#householdsTableBody tr').forEach(row => {
            row.style.display = '';
        });
        
        const hSize = document.getElementById('filterFamilySize');
        const hWat = document.getElementById('filterWaterSource');
        const hToi = document.getElementById('filterToiletFacility');
        const hOwn = document.getElementById('filterOwnershipStatus');
        if (hSize) hSize.value = '';
        if (hWat) hWat.value = '';
        if (hToi) hToi.value = '';
        if (hOwn) hOwn.value = '';
        currentTabFilter = 'all';
        applyFilters();

        updateTotalCount();
        
        // Show success message
        showNotification('Data refreshed successfully', 'success');
        console.log('Households data refreshed');
    }, 500);
}

// ===================================
// Action Menu Handlers
// ===================================
function initializeActionMenus() {
    // Use event delegation for dynamically loaded rows
    const tableBody = document.getElementById('householdsTableBody');
    
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
    const householdNumber = row.cells[0].textContent;
    const headName = row.querySelector('.head-name span:last-child')?.textContent;
    const memberCount = row.querySelector('.member-badge .count')?.textContent;
    
    // Remove any existing action menus
    document.querySelectorAll('.action-menu').forEach(menu => menu.remove());
    
    // Build menu items based on permissions
    const perms = window.BIS_PERMS || {};
    console.log('[Households] BIS_PERMS:', perms);
    let menuHtml = '';

    if (perms.household_view) {
        menuHtml += `
        <div class="action-menu-item" data-action="view">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </div>`;
    }
    if (perms.household_edit) {
        menuHtml += `
        <div class="action-menu-item" data-action="edit">
            <i class="fas fa-edit"></i>
            <span>Edit Household</span>
        </div>`;
    }
    if (perms.household_view) {
        menuHtml += `
        <div class="action-menu-item" data-action="print">
            <i class="fas fa-print"></i>
            <span>Print Household</span>
        </div>`;
    }
    if (perms.household_delete) {
        menuHtml += `
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Archive Household</span>
        </div>`;
    }

    // If no menu items, do not show the menu at all
    if (!menuHtml.trim()) {
        return;
    }

    // Create action menu
    const menu = document.createElement('div');
    menu.className = 'action-menu';
    menu.innerHTML = menuHtml;
    
    // Position menu — keep within viewport
    const rect = button.getBoundingClientRect();
    const menuLeft = Math.max(10, rect.right - 170);
    menu.style.position = 'fixed';
    menu.style.top = `${rect.bottom + 5}px`;
    menu.style.left = `${menuLeft}px`;
    
    document.body.appendChild(menu);
    
    // Add click handlers
    menu.querySelectorAll('.action-menu-item').forEach(item => {
        item.addEventListener('click', () => {
            const action = item.getAttribute('data-action');
            handleAction(action, householdNumber, headName, memberCount, row);
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

function handleAction(action, householdNumber, headName, memberCount, row) {
    console.log(`Action: ${action} for ${householdNumber}`);
    
    const householdId = row.getAttribute('data-household-id');
    
    switch(action) {
        case 'view':
            viewHousehold(householdId);
            break;
            
        case 'edit':
            editHousehold(householdId);
            break;
            
        case 'print':
            printHousehold(householdId);
            break;
            
        case 'delete':
            if (window.BIS_PERMS && window.BIS_PERMS.household_delete === false) {
                showNotification('Permission denied to archive households.', 'error');
                return;
            }
            const archiveModal = document.getElementById('archiveModal');
            if (archiveModal) {
                document.getElementById('archiveRecordId').value = householdId;
                document.getElementById('archiveRecordType').value = 'household';
                const modalTitle = document.getElementById('archiveModalTitle');
                const modalDesc = document.getElementById('archiveModalDesc');
                if (modalTitle) modalTitle.innerHTML = `Archive Household <u>${householdNumber}</u>`;
                if (modalDesc) modalDesc.textContent = `Are you sure you want to archive household ${householdNumber}? Head: ${headName}, Members: ${memberCount}.`;
                document.getElementById('archivePassword').value = '';
                document.getElementById('archiveReason').value = '';
                window.rowToArchive = row;
                archiveModal.style.display = 'block';
                document.getElementById('archiveReason').focus();
            }
            break;
    }
}

function viewHousehold(householdId) {
    if (window.BIS_PERMS && window.BIS_PERMS.household_view === false) {
        showNotification('Permission denied to view households.', 'error');
        const url = new URL(window.location);
        url.searchParams.delete('view');
        window.history.replaceState({}, '', url);
        return;
    }

    // Update URL parameter
    const url = new URL(window.location);
    url.searchParams.set('view', householdId);
    url.searchParams.delete('edit');
    url.searchParams.delete('create');
    window.history.replaceState({}, '', url);

    fetch(`model/get_household_details.php?id=${householdId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('createHouseholdModal');
                modal.classList.add('show');
                
                const modalTitle = modal.querySelector('.household-modal-header h3');
                modalTitle.innerHTML = '<i class="fas fa-eye"></i> View Household Details';
                
                document.getElementById('householdNumber').value = data.household.household_number;
                document.getElementById('householdContact').value = data.household.household_contact || '';
                document.getElementById('householdAddress').value = data.household.address;
                document.getElementById('ownershipStatus').value = data.household.ownership_status || 'Owned';
                document.getElementById('landlordName').value = data.household.landlord_name || '';
                document.getElementById('landlordNameId').value = data.household.landlord_resident_id || '';
                document.getElementById('waterSource').value = data.household.water_source_type || '';
                document.getElementById('toiletFacility').value = data.household.toilet_facility_type || '';
                document.getElementById('householdNotes').value = data.household.notes || '';
                
                const headFullNameElement = document.getElementById('headFullName');
                if (data.household.household_head_id) {
                    headFullNameElement.innerHTML = `<a href="resident_profile.php?id=${data.household.household_head_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${data.household.head_name || 'N/A'}</a>`;
                } else {
                    headFullNameElement.textContent = data.household.head_name || 'N/A';
                }
                document.getElementById('headDateOfBirth').textContent = data.household.head_dob ? formatDobAndAge(data.household.head_dob) : 'N/A';
                document.getElementById('headSex').textContent = data.household.head_sex || 'N/A';
                document.getElementById('selectedResidentId').value = data.household.household_head_id;
              
                
                document.getElementById('householdNumber').setAttribute('readonly', 'readonly');
                document.getElementById('householdContact').setAttribute('readonly', 'readonly');
                document.getElementById('householdAddress').setAttribute('readonly', 'readonly');
                document.getElementById('ownershipStatus').setAttribute('disabled', 'disabled');
                document.getElementById('landlordName').setAttribute('readonly', 'readonly');
                document.getElementById('waterSource').setAttribute('disabled', 'disabled');
                document.getElementById('toiletFacility').setAttribute('disabled', 'disabled');
                document.getElementById('householdNotes').setAttribute('readonly', 'readonly');

                document.getElementById('addMemberBtn').style.display = 'none';
                document.getElementById('saveHouseholdBtn').style.display = 'none';
                
                handleOwnershipStatusChange();
                
                const actionTh = document.querySelector('.members-table th:last-child');
                if (actionTh) actionTh.style.display = 'none';
                
                const form = document.getElementById('createHouseholdForm');
                form.setAttribute('data-household-id', householdId);
                form.setAttribute('data-view-mode', 'true');
                
                if (data.members && data.members.length > 0) {
// Sort members youngest to oldest (ascending age)
                    data.members.sort((a, b) => new Date(a.date_of_birth) - new Date(b.date_of_birth));
                    
                    const tbody = document.getElementById('membersTableBody');
                    tbody.innerHTML = '';
                    
                    data.members.forEach((member, index) => {
                        const row = document.createElement('tr');
                        if (member.resident_id) {
                            row.dataset.residentId = member.resident_id;
                        }
                        
                        const memberNameHtml = member.resident_id 
                            ? `<a href="resident_profile.php?id=${member.resident_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${member.full_name}</a>`
                            : member.full_name;
                        
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${memberNameHtml}</td>
                            <td>${member.date_of_birth ? formatDobAndAge(member.date_of_birth) : 'N/A'}</td>
                            <td>${member.sex || 'N/A'}</td>
                            <td>${member.relationship_to_head}</td>
                            <td>${member.mobile_number || 'N/A'}</td>
                            <td style="display: none;"></td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            } else {
                showNotification('Error loading household details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading household details', 'error');
        });
}

function editHousehold(householdId) {
    if (window.BIS_PERMS && window.BIS_PERMS.household_edit === false) {
        showNotification('Permission denied to edit households.', 'error');
        const url = new URL(window.location);
        url.searchParams.delete('edit');
        window.history.replaceState({}, '', url);
        return;
    }

    // Update URL parameter
    const url = new URL(window.location);
    url.searchParams.set('edit', householdId);
    url.searchParams.delete('view');
    url.searchParams.delete('create');
    window.history.replaceState({}, '', url);

    fetch(`model/get_household_details.php?id=${householdId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('createHouseholdModal');
                modal.classList.add('show');
                
                const modalTitle = modal.querySelector('.household-modal-header h3');
                modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Household';
                
                const actionTh = document.querySelector('.members-table th:last-child');
                if (actionTh) actionTh.style.display = '';
                
                // Set household number and make it readonly (cannot be edited)
                const householdNumberInput = document.getElementById('householdNumber');
                householdNumberInput.value = data.household.household_number;
                householdNumberInput.setAttribute('readonly', 'readonly');
                householdNumberInput.style.backgroundColor = '#f3f4f6';
                householdNumberInput.style.cursor = 'not-allowed';
                
                document.getElementById('householdContact').value = data.household.household_contact || '';
                document.getElementById('householdAddress').value = data.household.address;
                document.getElementById('ownershipStatus').value = data.household.ownership_status || 'Owned';
                document.getElementById('landlordName').value = data.household.landlord_name || '';
                document.getElementById('landlordNameId').value = data.household.landlord_resident_id || '';
                document.getElementById('waterSource').value = data.household.water_source_type || '';
                document.getElementById('toiletFacility').value = data.household.toilet_facility_type || '';
                document.getElementById('householdNotes').value = data.household.notes || '';
                
                const headFullNameElement = document.getElementById('headFullName');
                if (data.household.household_head_id) {
                    headFullNameElement.innerHTML = `<a href="resident_profile.php?id=${data.household.household_head_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${data.household.head_name || 'N/A'}</a>`;
                } else {
                    headFullNameElement.textContent = data.household.head_name || 'N/A';
                }
                document.getElementById('headDateOfBirth').textContent = data.household.head_dob ? formatDobAndAge(data.household.head_dob) : 'N/A';
                document.getElementById('headSex').textContent = data.household.head_sex || 'N/A';
                document.getElementById('selectedResidentId').value = data.household.household_head_id;
             
                // Ensure all fields are editable in edit mode
                document.getElementById('householdContact').removeAttribute('readonly');
                document.getElementById('householdAddress').removeAttribute('readonly');
                document.getElementById('householdNotes').removeAttribute('readonly');
                document.getElementById('ownershipStatus').removeAttribute('disabled');
                document.getElementById('landlordName').removeAttribute('readonly');
                
                handleOwnershipStatusChange();
                
                const waterSourceEl = document.getElementById('waterSource');
                if (waterSourceEl) {
                    waterSourceEl.removeAttribute('disabled');
                    waterSourceEl.style.cursor = 'auto';
                }
                
                const toiletFacilityEl = document.getElementById('toiletFacility');
                if (toiletFacilityEl) {
                    toiletFacilityEl.removeAttribute('disabled');
                    toiletFacilityEl.style.cursor = 'auto';
                }
                
                document.getElementById('createHouseholdForm').setAttribute('data-household-id', householdId);
                document.getElementById('saveHouseholdBtn').innerHTML = '<i class="fas fa-save"></i> Update';
                
                if (data.members && data.members.length > 0) {
// Sort members youngest to oldest (ascending age)
                    data.members.sort((a, b) => new Date(a.date_of_birth) - new Date(b.date_of_birth));
                    
                    const tbody = document.getElementById('membersTableBody');
                    tbody.innerHTML = '';
                    
                    data.members.forEach((member, index) => {
                        const row = document.createElement('tr');
                        if (member.resident_id) {
                            row.dataset.residentId = member.resident_id;
                        }
                        
                        const memberNameHtml = member.resident_id 
                            ? `<a href="resident_profile.php?id=${member.resident_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${member.full_name}</a>`
                            : member.full_name;
                        
                        row.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${memberNameHtml}</td>
                            <td>${member.date_of_birth ? formatDobAndAge(member.date_of_birth) : 'N/A'}</td>
                            <td>${member.sex || 'N/A'}</td>
                            <td>${member.relationship_to_head}</td>
                            <td>${member.mobile_number || 'N/A'}</td>
                            <td style="display: flex; gap: 5px;">
                                <button type="button" class="btn-transfer-head" title="Set as New Household Head" data-household-id="${householdId}" data-member-id="${member.resident_id}">
                                    <i class="fas fa-exchange-alt"></i>
                                </button>
                                <button type="button" class="btn-delete-member" title="Remove member">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                    });
                }
            } else {
                showNotification('Error loading household details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading household details', 'error');
        });
}

// ===================================
// Utility Functions
// ===================================
function formatDobAndAge(dateString) {
    if (!dateString) return 'N/A';
    const dob = new Date(dateString);
    if (isNaN(dob)) return 'N/A';
    
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const m = today.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    const formattedDate = `${monthNames[dob.getMonth()]} ${String(dob.getDate()).padStart(2, '0')}, ${dob.getFullYear()}`;
    
    return `${formattedDate} - ${age}`;
}

function showNotification(message, type = 'info') {
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10005;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===================================
// Modal Event Listeners
// ===================================
function initializeModalEventListeners() {
    const modal = document.getElementById('createHouseholdModal');
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeCreateHouseholdModal();
            }
        });
    }
    
    const saveHouseholdBtn = document.getElementById('saveHouseholdBtn');
    if (saveHouseholdBtn) {
        saveHouseholdBtn.addEventListener('click', () => {
            saveHousehold();
        });
    }

    // Initialize autocomplete once
    setupAutocomplete('landlordName', 'landlordNameDropdown');
    
    const phoneInput = document.getElementById('householdContact');
    if (phoneInput) {
        phoneInput.addEventListener('input', (e) => {
            formatPhoneNumber(e.target);
        });
    }
    
    const addMemberBtn = document.getElementById('addMemberBtn');
    if (addMemberBtn) {
        addMemberBtn.addEventListener('click', () => {
            addHouseholdMember();
        });
    }
    
    const residentSearchInput = document.getElementById('residentSearchInput');
    if (residentSearchInput) {
        let searchTimeout;
        residentSearchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadResidents(e.target.value);
            }, 300);
        });
    }
    
    const searchModal = document.getElementById('searchResidentModal');
    if (searchModal) {
        searchModal.addEventListener('click', (e) => {
            if (e.target === searchModal) {
                closeSearchResidentModal();
            }
        });
    }
    
    const addMemberModal = document.getElementById('addMemberModal');
    if (addMemberModal) {
        addMemberModal.addEventListener('click', (e) => {
            if (e.target === addMemberModal) {
                closeAddMemberModal();
            }
        });
    }
    
    const searchMemberResidentBtn = document.getElementById('searchMemberResidentBtn');
    if (searchMemberResidentBtn) {
        searchMemberResidentBtn.addEventListener('click', () => {
            searchMemberResident();
        });
    }
    
    const confirmAddMemberBtn = document.getElementById('confirmAddMemberBtn');
    if (confirmAddMemberBtn) {
        confirmAddMemberBtn.addEventListener('click', () => {
            confirmAddMember();
        });
    }
    
    const resetMemberBtn = document.getElementById('resetMemberBtn');
    if (resetMemberBtn) {
        resetMemberBtn.addEventListener('click', () => {
            resetAddMemberForm();
            showNotification('Form reset successfully', 'success');
        });
    }
    
    // Delete and Transfer member buttons (event delegation)
    const membersTableBody = document.getElementById('membersTableBody');
    if (membersTableBody) {
        membersTableBody.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.btn-delete-member');
            if (deleteBtn) {
                const row = deleteBtn.closest('tr');
                deleteMember(row);
            }
            
            const transferBtn = e.target.closest('.btn-transfer-head');
            if (transferBtn) {
                const householdId = transferBtn.getAttribute('data-household-id');
                const memberId = transferBtn.getAttribute('data-member-id');
                
                closeCreateHouseholdModal();
                openTransferHeadModal(householdId, memberId);
            }
        });
    }
}

function deleteMember(row) {
    const memberName = row.cells[1].textContent;
    const residentId = row.dataset.residentId;
    const householdId = document.getElementById('createHouseholdForm').getAttribute('data-household-id');
    
    // If it's a new member (no resident ID yet, or no household ID), just remove from table
    if (!householdId || !residentId) {
        if (confirm(`Are you sure you want to remove ${memberName} from this household?`)) {
            row.style.opacity = '0';
            row.style.transition = 'opacity 0.3s ease';
            setTimeout(() => {
                row.remove();
                renumberMembers();
                checkEmptyMembers();
                showNotification('Member removed successfully', 'success');
            }, 300);
        }
        return;
    }
    
    const archiveModal = document.getElementById('archiveModal');
    if (archiveModal) {
        document.getElementById('archiveRecordId').value = householdId;
        document.getElementById('archiveMemberId').value = residentId;
        document.getElementById('archiveRecordType').value = 'member';
        
        const modalTitle = document.getElementById('archiveModalTitle');
        const modalDesc = document.getElementById('archiveModalDesc');
        if (modalTitle) modalTitle.innerHTML = `Remove Member <u>${memberName}</u>`;
        if (modalDesc) modalDesc.textContent = `Are you sure you want to remove ${memberName} from this household? This will take effect immediately.`;
        
        document.getElementById('archivePassword').value = '';
        document.getElementById('archiveReason').value = '';
        
        window.rowToArchive = row;
        
        archiveModal.style.display = 'block';
        document.getElementById('archiveReason').focus();
    }
}

function checkEmptyMembers() {
    const tbody = document.getElementById('membersTableBody');
    if (!tbody) return;
    const remainingRows = tbody.querySelectorAll('tr:not(.no-members-row)');
    
    if (remainingRows.length === 0) {
        tbody.innerHTML = '<tr class="no-members-row"><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 20px;">No members added yet</td></tr>';
    }
}
function renumberMembers() {
    const tbody = document.getElementById('membersTableBody');
    const rows = tbody.querySelectorAll('tr:not(.no-members-row)');
    
    rows.forEach((row, index) => {
        row.cells[0].textContent = index + 1;
    });
}

function searchResident() {
    const searchModal = document.getElementById('searchResidentModal');
    if (searchModal) {
        searchModal.classList.add('show');
        loadResidents('');
        setTimeout(() => {
            const input = document.getElementById('residentSearchInput');
            if (input) input.focus();
        }, 300);
    }
}

function closeSearchResidentModal() {
    const searchModal = document.getElementById('searchResidentModal');
    if (searchModal) {
        searchModal.classList.remove('show');
        const input = document.getElementById('residentSearchInput');
        if (input) input.value = '';
    }
}

function loadResidents(searchTerm = '') {
    const container = document.getElementById('residentsListContainer');
    
    container.innerHTML = `
        <div class="loading-residents">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Loading residents...</span>
        </div>
    `;
    
    const searchModal = document.getElementById('searchResidentModal');
    const isSelectingMember = searchModal && searchModal.getAttribute('data-search-for') === 'member';
    
    let url = `model/search_residents.php?search=${encodeURIComponent(searchTerm)}&filter_households=true`;
    if (!isSelectingMember) {
        url += '&filter=adult'; // Ensure head is an adult
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                displayResidents(data.data);
            } else {
                container.innerHTML = `
                    <div class="loading-residents">
                        <i class="fas fa-user-slash"></i>
                        <span>No available residents found</span>
                        <p style="font-size: 12px; margin-top: 5px; color: var(--text-secondary);">All residents may already be assigned to households</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading residents:', error);
            container.innerHTML = `
                <div class="loading-residents">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Error loading residents</span>
                </div>
            `;
        });
}

function displayResidents(residents) {
    const container = document.getElementById('residentsListContainer');
    container.innerHTML = '';
    
    // Check if we are selecting a member (to exclude household head and existing members)
    const searchModal = document.getElementById('searchResidentModal');
    const isSelectingMember = searchModal && searchModal.getAttribute('data-search-for') === 'member';
    
    let filteredResidents = residents;
    
    if (isSelectingMember) {
        const excludeIds = new Set();
        
        // Exclude household head
        const headIdInput = document.getElementById('selectedResidentId');
        if (headIdInput && headIdInput.value) {
            excludeIds.add(String(headIdInput.value));
        }
        
        // Exclude existing members in the table
        const memberRows = document.querySelectorAll('#membersTableBody tr:not(.no-members-row)');
        memberRows.forEach(row => {
            if (row.dataset.residentId) {
                excludeIds.add(String(row.dataset.residentId));
            }
        });
        
        filteredResidents = residents.filter(r => !excludeIds.has(String(r.id)));
    }
    
    if (filteredResidents.length === 0) {
        container.innerHTML = `
            <div class="loading-residents">
                <i class="fas fa-user-slash"></i>
                <span>No available residents found</span>
                <p style="font-size: 12px; margin-top: 5px; color: var(--text-secondary);">
                    ${isSelectingMember ? 'Residents matching search are already in this household' : 'All residents may already be assigned to households'}
                </p>
            </div>
        `;
        return;
    }
    
    filteredResidents.forEach(resident => {
        const item = document.createElement('div');
        item.className = 'resident-item';
        item.innerHTML = `
            <div class="resident-item-name">${resident.full_name}</div>
            <div class="resident-item-id">${resident.resident_id || 'No ID'}</div>
        `;
        
        item.addEventListener('click', () => {
            selectResident(resident);
        });
        
        container.appendChild(item);
    });
}

function selectResident(resident) {
    const searchModal = document.getElementById('searchResidentModal');
    const searchFor = searchModal.getAttribute('data-search-for');
    
    if (searchFor === 'member') {
        setMemberResident({
            id: resident.id,
            residentId: resident.resident_id,
            fullName: resident.full_name,
            dateOfBirth: resident.date_of_birth,
            sex: resident.sex,
            mobileNumber: resident.mobile_number
        });
        searchModal.removeAttribute('data-search-for');
    } else {
        setHouseholdHead({
            id: resident.id,
            residentId: resident.resident_id,
            fullName: resident.full_name,
            dateOfBirth: resident.date_of_birth,
            sex: resident.sex,
            mobileNumber: resident.mobile_number
        });
    }
    
    closeSearchResidentModal();
}

function setHouseholdHead(resident) {
    document.getElementById('headFullName').textContent = resident.fullName;
    document.getElementById('headDateOfBirth').textContent = resident.dateOfBirth ? formatDobAndAge(resident.dateOfBirth) : 'N/A';
    document.getElementById('headSex').textContent = resident.sex;
   
    document.getElementById('selectedResidentId').value = resident.id;
    
    showNotification('Household head selected successfully', 'success');
}

function formatPhoneNumber(input) {
    let value = input.value.replace(/\D/g, '');
    value = value.substring(0, 10);
    
    if (value.length > 6) {
        value = value.substring(0, 3) + ' ' + value.substring(3, 6) + ' ' + value.substring(6);
    } else if (value.length > 3) {
        value = value.substring(0, 3) + ' ' + value.substring(3);
    }
    
    input.value = value;
}

function addHouseholdMember() {
    const modal = document.getElementById('addMemberModal');
    if (modal) {
        modal.classList.add('show');
        resetAddMemberForm();
        console.log('Add Member Modal opened');
    }
}

function closeAddMemberModal() {
    const modal = document.getElementById('addMemberModal');
    if (modal) {
        modal.classList.remove('show');
        resetAddMemberForm();
        console.log('Add Member Modal closed');
    }
}

function resetAddMemberForm() {
    const form = document.getElementById('addMemberForm');
    if (form) {
        form.reset();
    }
    
    document.getElementById('memberFullName').value = '';
    document.getElementById('memberSex').value = '';
    document.getElementById('memberDateOfBirth').value = '';
    document.getElementById('memberRelationship').value = '';
    document.getElementById('memberMobile').value = '';
    document.getElementById('selectedMemberResidentId').value = '';
    
    document.getElementById('memberSex').removeAttribute('disabled');
    document.getElementById('memberDateOfBirth').removeAttribute('readonly');
    document.getElementById('memberMobile').removeAttribute('readonly');
}

function searchMemberResident() {
    const searchModal = document.getElementById('searchResidentModal');
    if (searchModal) {
        searchModal.setAttribute('data-search-for', 'member');
        searchModal.classList.add('show');
        loadResidents('');
        setTimeout(() => {
            const input = document.getElementById('residentSearchInput');
            if (input) input.focus();
        }, 300);
    }
}

function setMemberResident(resident) {
    document.getElementById('memberFullName').value = resident.fullName;
    document.getElementById('memberSex').value = resident.sex;
    document.getElementById('memberDateOfBirth').value = resident.dateOfBirth;
    document.getElementById('memberMobile').value = resident.mobileNumber || '';
    document.getElementById('selectedMemberResidentId').value = resident.id;
    
    document.getElementById('memberSex').setAttribute('disabled', 'disabled');
    document.getElementById('memberDateOfBirth').setAttribute('readonly', 'readonly');
    document.getElementById('memberMobile').setAttribute('readonly', 'readonly');
    
    showNotification('Resident selected successfully', 'success');
}

function confirmAddMember() {
    const memberData = {
        residentId: document.getElementById('selectedMemberResidentId').value,
        name: document.getElementById('memberFullName').value.trim(),
        dateOfBirth: document.getElementById('memberDateOfBirth').value,
        sex: document.getElementById('memberSex').value,
        relationship: document.getElementById('memberRelationship').value.trim(),
        mobileNumber: document.getElementById('memberMobile').value
    };
    
    if (!memberData.name) {
        showNotification('Please enter the member\'s full name', 'error');
        document.getElementById('memberFullName').focus();
        return;
    }
    
    if (!memberData.relationship) {
        showNotification('Please enter the relationship to head', 'error');
        document.getElementById('memberRelationship').focus();
        return;
    }
    
    // Validate if resident is already added
    if (memberData.residentId) {
        const tbody = document.getElementById('membersTableBody');
        const existingRows = tbody.querySelectorAll('tr:not(.no-members-row)');
        
        // Check if already added to this household
        for (let row of existingRows) {
            if (row.dataset.residentId === memberData.residentId) {
                showNotification('This resident has already been added as a member', 'error');
                return;
            }
        }
        
        // Check if trying to add household head as member
        const householdHeadId = document.getElementById('selectedResidentId').value;
        if (memberData.residentId === householdHeadId) {
            showNotification('The household head cannot be added as a member', 'error');
            return;
        }
    }
    
    addMemberToTable(memberData);
    closeAddMemberModal();
}

document.addEventListener('DOMContentLoaded', function() {
    const archiveForm = document.getElementById('archiveForm');
    if (archiveForm) {
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const type = document.getElementById('archiveRecordType').value;
            const confirmBtn = document.getElementById('confirmArchiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            if (type === 'household') {
                const formData = new FormData(this);
                
                fetch('model/delete_household.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        document.getElementById('archiveModal').style.display = 'none';
                        if (window.rowToArchive) {
                            window.rowToArchive.style.opacity = '0';
                            setTimeout(() => {
                                window.rowToArchive.remove();
                                updateTotalCount();
                            }, 300);
                        } else {
                            setTimeout(() => location.reload(), 1500);
                        }
                    } else {
                        showNotification(data.message || 'Error archiving household', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred', 'error');
                })
                .finally(() => {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                });
            } else if (type === 'member') {
                const formData = new FormData();
                formData.append('household_id', document.getElementById('archiveRecordId').value);
                formData.append('resident_id', document.getElementById('archiveMemberId').value);
                formData.append('reason', document.getElementById('archiveReason').value);
                formData.append('password', document.getElementById('archivePassword').value);
                
                fetch('model/remove_household_member.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        document.getElementById('archiveModal').style.display = 'none';
                        
                        if (window.rowToArchive) {
                            window.rowToArchive.style.opacity = '0';
                            setTimeout(() => {
                                window.rowToArchive.remove();
                                renumberMembers();
                                checkEmptyMembers();
                            }, 300);
                        }
                    } else {
                        showNotification(data.message || 'Error removing member', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred', 'error');
                })
                .finally(() => {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                });
            }
        });
    }
    
    const cancelBtn = document.getElementById('cancelArchive');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('archiveModal').style.display = 'none';
        });
    }
    
    const togglePasswordBtn = document.getElementById('toggleArchivePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', () => {
            const input = document.getElementById('archivePassword');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            togglePasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    const archiveModal = document.getElementById('archiveModal');
    if (archiveModal) {
        archiveModal.addEventListener('click', (e) => {
            if (e.target === archiveModal) {
                archiveModal.style.display = 'none';
            }
        });
    }
});

function addMemberToTable(member) {
    const tbody = document.getElementById('membersTableBody');
    const noMembersRow = tbody.querySelector('.no-members-row');
    
    if (noMembersRow) {
        noMembersRow.remove();
    }
    
    const currentRows = tbody.querySelectorAll('tr:not(.no-members-row)').length;
    const memberNumber = currentRows + 1;
    
    const newRow = document.createElement('tr');
    
    if (member.residentId) {
        newRow.dataset.residentId = member.residentId;
    }
    
    newRow.innerHTML = `
        <td>${memberNumber}</td>
        <td>${member.name}</td>
            <td>${member.dateOfBirth ? formatDobAndAge(member.dateOfBirth) : 'N/A'}</td>
        <td>${member.sex}</td>
        <td>${member.relationship}</td>
        <td>${member.mobileNumber || 'N/A'}</td>
        <td style="display: flex; gap: 5px;">
            <button type="button" class="btn-delete-member" title="Remove member">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    
    tbody.appendChild(newRow);
    showNotification('Member added successfully', 'success');
}

function saveHousehold() {
    const form = document.getElementById('createHouseholdForm');
    const householdId = form.getAttribute('data-household-id');
    const isEditMode = householdId !== null && householdId !== '' && householdId !== undefined;
    
    // Build form data object
    const formData = {
        householdContact: document.getElementById('householdContact').value.trim(),
        householdAddress: document.getElementById('householdAddress').value.trim(),
        ownershipStatus: document.getElementById('ownershipStatus').value,
        landlordResidentId: document.getElementById('landlordNameId').value,
        landlordName: document.getElementById('landlordName').value.trim(),
        waterSource: document.getElementById('waterSource').value,
        toiletFacility: document.getElementById('toiletFacility').value,
        householdNotes: document.getElementById('householdNotes').value.trim(),
        householdHeadId: document.getElementById('selectedResidentId').value
    };
    
    // Include household number only for CREATE operations
    if (!isEditMode) {
        formData.householdNumber = document.getElementById('householdNumber').value.trim();
        
        // Validate household number for CREATE
        if (!formData.householdNumber) {
            showNotification('Please enter the household number', 'error');
            document.getElementById('householdNumber').focus();
            return;
        }
    } else {
        // Include household ID for UPDATE operations
        formData.householdId = householdId;
    }
    
    if (!formData.householdAddress) {
        showNotification('Please enter the household address', 'error');
        document.getElementById('householdAddress').focus();
        return;
    }
    
    if (!formData.householdHeadId) {
        showNotification('Please select a household head', 'error');
        return;
    }
    
    const membersTableBody = document.getElementById('membersTableBody');
    const memberRows = membersTableBody.querySelectorAll('tr:not(.no-members-row)');
    const members = [];
    
    memberRows.forEach(row => {
        const cells = row.cells;
        members.push({
            residentId: row.dataset.residentId || null,
            name: cells[1].textContent,
            dateOfBirth: cells[2].textContent,
            sex: cells[3].textContent,
            relationship: cells[4].textContent,
            mobileNumber: cells[5].textContent === 'N/A' ? '' : cells[5].textContent
        });
    });
    
    formData.members = members;
    
    const saveBtn = document.getElementById('saveHouseholdBtn');
    const originalText = saveBtn.innerHTML;
    const savingText = isEditMode ? 'Updating...' : 'Saving...';
    saveBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${savingText}`;
    saveBtn.disabled = true;
    
    fetch('model/save_household.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const successMessage = isEditMode ? 'Household updated successfully!' : 'Household created successfully!';
            showNotification(successMessage, 'success');
            
            setTimeout(() => {
                closeCreateHouseholdModal();
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message || 'Error saving household', 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving household. Please try again.', 'error');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ===================================
// Transfer Household Head Functions
// ===================================
let transferHouseholdData = null;

function openTransferHeadModal(householdId, preselectedNewHeadId = null) {
    const modal = document.getElementById('transferHeadModal');
    if (!modal) return;
    
    fetch(`model/get_household_details.php?id=${householdId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                transferHouseholdData = data;
                
                document.getElementById('transferHouseholdId').value = householdId;
                document.getElementById('transferOldHeadId').value = data.household.household_head_id;
                document.getElementById('transferCurrentHead').value = data.household.head_name;
                
                const newHeadSelect = document.getElementById('transferNewHead');
                newHeadSelect.innerHTML = '<option value="">Select a member</option>';
                
                if (data.members && data.members.length > 0) {
                    data.members.forEach(member => {
                        if (member.resident_id) {
                            let age = 0;
                            let isMinor = false;
                            if (member.date_of_birth) {
                                const dob = new Date(member.date_of_birth);
                                const today = new Date();
                                age = today.getFullYear() - dob.getFullYear();
                                const m = today.getMonth() - dob.getMonth();
                                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) { age--; }
                                isMinor = (age < 18);
                            }
                            
                            const option = document.createElement('option');
                            option.value = member.resident_id;
                            option.textContent = member.full_name + (isMinor ? ' (Minor - Not Eligible)' : '');
                            option.dataset.rel = member.relationship_to_head;
                            option.dataset.sex = member.sex;
                            if (isMinor) option.disabled = true;
                            newHeadSelect.appendChild(option);
                        }
                    });
                }
                
                document.getElementById('transferRelationshipsSection').style.display = 'none';
                modal.classList.add('show');
                
                if (preselectedNewHeadId) {
                    newHeadSelect.value = preselectedNewHeadId;
                    updateTransferRelationships();
                }
            } else {
                showNotification('Error loading household details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading household details', 'error');
        });
}

function closeTransferHeadModal() {
    const modal = document.getElementById('transferHeadModal');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('transferHeadForm').reset();
        document.getElementById('transferRelationshipsSection').style.display = 'none';
    }
}

function updateTransferRelationships() {
    const newHeadSelect = document.getElementById('transferNewHead');
    const newHeadId = newHeadSelect.value;
    const section = document.getElementById('transferRelationshipsSection');
    const tbody = document.getElementById('transferMembersBody');
    
    if (!newHeadId) {
        section.style.display = 'none';
        return;
    }
    
    const selectedOption = newHeadSelect.options[newHeadSelect.selectedIndex];
    const newHeadRelToOld = selectedOption && selectedOption.dataset.rel ? selectedOption.dataset.rel.toLowerCase() : '';
    
    tbody.innerHTML = '';
    
    const oldHeadRow = document.createElement('tr');
    const oldHeadNewRel = guessRelationship(newHeadRelToOld, 'head', transferHouseholdData.household.head_sex);
    
    oldHeadRow.innerHTML = `
        <td>${transferHouseholdData.household.head_name} <span style="font-size: 0.75rem; background: #e5e7eb; padding: 2px 6px; border-radius: 4px; margin-left: 5px;">Old Head</span></td>
        <td>Head</td>
        <td>
            <input type="hidden" name="memberId[]" value="${transferHouseholdData.household.household_head_id}">
            <input type="text" name="newRelationship[]" class="form-control" value="${oldHeadNewRel}" required>
        </td>
    `;
    tbody.appendChild(oldHeadRow);
    
    if (transferHouseholdData.members) {
        transferHouseholdData.members.forEach(member => {
            if (member.resident_id != newHeadId) {
                const memberRelToOld = member.relationship_to_head.toLowerCase();
                const newRel = guessRelationship(newHeadRelToOld, memberRelToOld, member.sex);
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${member.full_name}</td>
                    <td>${member.relationship_to_head}</td>
                    <td>
                        <input type="hidden" name="memberId[]" value="${member.resident_id}">
                        <input type="text" name="newRelationship[]" class="form-control" value="${newRel}" required>
                    </td>
                `;
                tbody.appendChild(row);
            }
        });
    }
    
    section.style.display = 'block';
}

function guessRelationship(newHeadRelToOld, memberRelToOld, memberSex) {
    newHeadRelToOld = newHeadRelToOld.toLowerCase().trim();
    memberRelToOld = memberRelToOld.toLowerCase().trim();
    const isMale = memberSex === 'Male';
    const isFemale = memberSex === 'Female';
    
    if (['son', 'daughter', 'child'].includes(newHeadRelToOld)) {
        if (memberRelToOld === 'head') return isMale ? 'Father' : (isFemale ? 'Mother' : 'Parent');
        if (['spouse', 'wife', 'husband'].includes(memberRelToOld)) return isMale ? 'Father' : (isFemale ? 'Mother' : 'Parent');
        if (['son', 'daughter', 'child'].includes(memberRelToOld)) return isMale ? 'Brother' : (isFemale ? 'Sister' : 'Sibling');
        if (['father', 'mother', 'parent'].includes(memberRelToOld)) return isMale ? 'Grandfather' : (isFemale ? 'Grandmother' : 'Grandparent');
        if (['brother', 'sister', 'sibling'].includes(memberRelToOld)) return isMale ? 'Uncle' : (isFemale ? 'Aunt' : 'Uncle/Aunt');
    }
    
    if (['spouse', 'wife', 'husband'].includes(newHeadRelToOld)) {
        if (memberRelToOld === 'head') return isMale ? 'Husband' : (isFemale ? 'Wife' : 'Spouse');
        if (['son', 'daughter', 'child'].includes(memberRelToOld)) return isMale ? 'Son' : (isFemale ? 'Daughter' : 'Child');
        if (['father', 'mother', 'parent'].includes(memberRelToOld)) return isMale ? 'Father-in-law' : (isFemale ? 'Mother-in-law' : 'Parent-in-law');
    }
    
    if (['brother', 'sister', 'sibling'].includes(newHeadRelToOld)) {
        if (memberRelToOld === 'head') return isMale ? 'Brother' : (isFemale ? 'Sister' : 'Sibling');
        if (['father', 'mother', 'parent'].includes(memberRelToOld)) return isMale ? 'Father' : (isFemale ? 'Mother' : 'Parent');
        if (['son', 'daughter', 'child'].includes(memberRelToOld)) return isMale ? 'Nephew' : (isFemale ? 'Niece' : 'Nephew/Niece');
    }

    if (!memberRelToOld || memberRelToOld === 'head') return '';
    return memberRelToOld.charAt(0).toUpperCase() + memberRelToOld.slice(1);
}

function saveTransferHead() {
    const form = document.getElementById('transferHeadForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    
    const data = {
        householdId: formData.get('householdId'),
        newHeadId: formData.get('newHeadId'),
        oldHeadId: formData.get('oldHeadId'),
        members: []
    };
    
    const memberIds = formData.getAll('memberId[]');
    const newRelationships = formData.getAll('newRelationship[]');
    
    for (let i = 0; i < memberIds.length; i++) {
        data.members.push({
            residentId: memberIds[i],
            relationship: newRelationships[i]
        });
    }
    
    const btn = document.getElementById('saveTransferHeadBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;
    
    fetch('model/transfer_household_head.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('Household head transferred successfully', 'success');
            closeTransferHeadModal();
            refreshData();
        } else {
            showNotification(result.message || 'Error transferring household head', 'error');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// ===================================
// Print Household
// ===================================
async function printHousehold(householdId) {
    try {
        showNotification('Preparing document for printing...', 'info');

        // Fetch barangay info
        let brgyInfo = {
            province_name: 'Province',
            town_name: 'Municipality',
            barangay_name: 'Barangay',
            barangay_logo: '',
            official_emblem: ''
        };
        
        try {
            const response = await fetch('model/get_barangay_info.php');
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.data) {
                    brgyInfo = data.data;
                }
            }
        } catch (error) {
            console.error('Error fetching barangay info:', error);
        }

        // Fetch household details
        const hhResponse = await fetch(`model/get_household_details.php?id=${householdId}`);
        const hhData = await hhResponse.json();

        if (!hhData.success) {
            showNotification('Failed to fetch household data for printing', 'error');
            return;
        }

        const household = hhData.household;
        const members = hhData.members || [];

        const brgyLogoHtml = brgyInfo.barangay_logo 
            ? `<img src="${brgyInfo.barangay_logo}" class="logo-img" alt="Barangay Logo">`
            : `<div class="logo-placeholder-box"></div>`;
            
        const govLogoHtml = brgyInfo.official_emblem
            ? `<img src="${brgyInfo.official_emblem}" class="logo-img" alt="Official Emblem">`
            : `<div class="logo-placeholder-box"></div>`;

        // Create a hidden iframe for printing
        let printFrame = document.getElementById('householdPrintFrame');
        if (!printFrame) {
            printFrame = document.createElement('iframe');
            printFrame.id = 'householdPrintFrame';
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

        // Build members rows
        let rowsHtml = '';
        if (members.length === 0) {
            rowsHtml = `<tr><td colspan="6" class="text-center" style="text-align: center; padding: 15px;">No members found in this household.</td></tr>`;
        } else {
            members.forEach((member, index) => {
                const isHead = (member.is_head == 1 || member.is_head === true || member.is_head === '1') ? '<strong>(Head)</strong>' : '';
                rowsHtml += `
                    <tr>
                        <td style="text-align: center;">${index + 1}</td>
                        <td>${member.full_name} ${isHead}</td>
                        <td>${member.date_of_birth ? formatDobAndAge(member.date_of_birth) : 'N/A'}</td>
                        <td>${member.sex || 'N/A'}</td>
                        <td>${member.relationship_to_head || 'N/A'}</td>
                        <td>${member.mobile_number || 'N/A'}</td>
                    </tr>
                `;
            });
        }

        doc.write(`
            <!DOCTYPE html>
            <html>
            <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
                <title>Print Household - ${household.household_number}</title>
                <style>
                    @page { size: A4; margin: 15mm; }
                    body { font-family: Arial, sans-serif; color: #000; background: #fff; margin: 0; padding: 0; }
                    .container { width: 100%; max-width: 210mm; margin: 0 auto; }
                    
                    .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                    .header-center { flex: 1; }
                    .header-center p { margin: 2px 0; font-size: 14px; }
                    .header-center .brgy-name { font-weight: bold; font-size: 16px; margin-top: 5px; }
                    .logo-img { width: 80px; height: 80px; object-fit: contain; }
                    .logo-placeholder-box { width: 80px; height: 80px; }
                    
                    .household-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; text-transform: uppercase; }
                    
                    .members-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
                    .members-table th, .members-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                    .members-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; color-adjust: exact; }
                    
                    .info-flex { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
                    .info-box { width: 48%; border: 1px solid #000; padding: 10px; }
                    .info-box h4 { margin-top: 0; margin-bottom: 8px; border-bottom: 1px solid #000; padding-bottom: 4px; font-size: 14px; text-transform: uppercase; }
                    .info-row { margin-bottom: 4px; }
                    .info-label { font-weight: bold; display: inline-block; width: 110px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="cert-header">
                        ${brgyLogoHtml}

                        <div class="header-center">
                            <p>Republic of the Philippines</p>
                            <p>Province of ${brgyInfo.province_name || 'Province'}</p>
                            <p>Municipality of ${brgyInfo.town_name || 'Municipality'}</p>
                            <p class="brgy-name">${(brgyInfo.barangay_name || 'Barangay').toUpperCase()}</p>
                        </div>

                        ${govLogoHtml}
                    </div>
                    
                    <div class="household-title">Household Information</div>
                    
                    <div class="info-flex">
                        <div class="info-box">
                            <h4>Household Details</h4>
                            <div class="info-row"><span class="info-label">Household No:</span> ${household.household_number}</div>
                            <div class="info-row"><span class="info-label">Address:</span> ${household.address || 'N/A'}</div>
                            <div class="info-row"><span class="info-label">Contact:</span> ${household.household_contact || 'N/A'}</div>
                        </div>
                        <div class="info-box">
                            <h4>Household Head</h4>
                            <div class="info-row"><span class="info-label">Name:</span> ${household.head_name || 'N/A'}</div>
                            <div class="info-row"><span class="info-label">Date of Birth:</span> ${household.head_dob ? formatDobAndAge(household.head_dob) : 'N/A'}</div>
                            <div class="info-row"><span class="info-label">Sex:</span> ${household.head_sex || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <h4 style="margin-top: 20px; margin-bottom: 10px; font-size: 14px; text-transform: uppercase;">Household Members</h4>
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th style="width: 30px; text-align: center;">#</th>
                                <th>Name</th>
                                <th>Date of Birth</th>
                                <th>Sex</th>
                                <th>Relationship to Head</th>
                                <th>Mobile Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            </body>
            </html>
        `);
        doc.close();

        // Trigger print
        setTimeout(() => {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        }, 500);

    } catch (error) {
        console.error('Print Error:', error);
        showNotification('An error occurred while printing', 'error');
    }
}

// ===================================
// Ownership & Autocomplete Logic
// ===================================
function handleOwnershipStatusChange() {
    const status = document.getElementById('ownershipStatus').value;
    const landlordGroup = document.getElementById('landlordNameGroup');
    const landlordNameInput = document.getElementById('landlordName');
    
    if (status === 'Rent') {
        landlordGroup.style.display = 'block';
    } else {
        landlordGroup.style.display = 'none';
    }
}

function setupAutocomplete(inputId, dropdownId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    let timeout = null;

    if (!input || !dropdown) return;

    input.addEventListener('input', function(e) {
        const hiddenId = document.getElementById(inputId + 'Id');
        if (hiddenId) hiddenId.value = '';

        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        timeout = setTimeout(() => {
            fetch(`model/search_residents.php?search=${encodeURIComponent(query)}&include_deceased=true`)
                .then(res => res.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.success && data.data && data.data.length > 0) {
                        data.data.forEach(resident => {
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                            let displayHtml = resident.full_name.replace(regex, '<strong>$1</strong>');
                            if (resident.activity_status === 'Deceased') displayHtml += ' <span style="font-size: 11px; color: #ef4444; font-style: italic;">(Deceased)</span>';
                            item.innerHTML = displayHtml;
                            
                            item.addEventListener('click', () => {
                                input.value = resident.full_name;
                                dropdown.style.display = 'none';
                                if (hiddenId) hiddenId.value = resident.id;
                            });
                            dropdown.appendChild(item);
                        });
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error('Error fetching residents:', err);
                    dropdown.style.display = 'none';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (e.target !== input && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });
}

// Export functions for external use
window.householdsPage = {
    refresh: refreshData,
    applyFilter: applyFilter,
    search: performSearch,
    openModal: showCreateHouseholdModal,
    closeModal: closeCreateHouseholdModal
};

// Make functions globally accessible for onclick handlers
window.closeCreateHouseholdModal = closeCreateHouseholdModal;
window.closeSearchResidentModal = closeSearchResidentModal;
window.closeAddMemberModal = closeAddMemberModal;
window.openTransferHeadModal = openTransferHeadModal;
window.closeTransferHeadModal = closeTransferHeadModal;
window.updateTransferRelationships = updateTransferRelationships;
window.saveTransferHead = saveTransferHead;
window.handleOwnershipStatusChange = handleOwnershipStatusChange;
