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

// ===================================
// Load Households Data
// ===================================
function loadHouseholdsData() {
    const tbody = document.getElementById('householdsTableBody');
    
    // Show loading state
    tbody.innerHTML = `
        <tr>
            <td colspan="4" style="text-align: center; padding: 40px;">
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
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px;">
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
                    <td colspan="4" style="text-align: center; padding: 40px;">
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
    
    if (householdsTable) {
        householdsTable.filter(row => {
            let memberCount = 0;
            // Get member count from attribute
            if (row.hasAttribute('data-member-count')) {
                memberCount = parseInt(row.getAttribute('data-member-count'));
            } else {
                // Fallback: try to parse from the badge
                const countEl = row.querySelector('.member-count .count');
                if (countEl) memberCount = parseInt(countEl.textContent);
            }
            
            switch(filterType) {
                case 'all':
                    return true;
                case 'single-person':
                    return memberCount === 0;
                case 'small':
                    return memberCount >= 1 && memberCount <= 4;
                case 'medium':
                    return memberCount >= 5 && memberCount <= 7;
                case 'large':
                    return memberCount >= 8 && memberCount <= 10;
                case 'very-large':
                    return memberCount >= 11;
                default:
                    return true;
            }
        });
        
        updateTotalCount(householdsTable.getFilteredRows());
    }
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
}

function showCreateHouseholdModal() {
    const modal = document.getElementById('createHouseholdModal');
    if (modal) {
        modal.classList.add('show');
        
        // Reset form
        resetHouseholdForm();
        
        console.log('Create Household Modal opened');
    }
}

function closeCreateHouseholdModal() {
    const modal = document.getElementById('createHouseholdModal');
    if (modal) {
        modal.classList.remove('show');
        
        // Reset form
        resetHouseholdForm();
        
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
    document.getElementById('waterSource').removeAttribute('disabled');
    document.getElementById('toiletFacility').removeAttribute('disabled');
    document.getElementById('householdNotes').removeAttribute('readonly');
    
    // Show all buttons (in case they were hidden in view mode)
    const searchResidentBtn = document.getElementById('searchResidentBtn');
    if (searchResidentBtn) {
        searchResidentBtn.style.display = '';
    }
    
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
    
    // Regenerate household number
    generateHouseholdNumber();
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
    if (perms.household_delete) {
        menuHtml += `
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Delete Household</span>
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
            
        case 'delete':
            if (confirm(`Are you sure you want to delete household ${householdNumber}?\n\nHead: ${headName}\nMembers: ${memberCount}\n\nThis action cannot be undone.`)) {
                const formData = new FormData();
                formData.append('id', householdId);

                fetch('model/delete_household.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            updateTotalCount();
                            showNotification('Household moved to archive successfully', 'success');
                        }, 300);
                    } else {
                        showNotification(data.message || 'Error deleting household', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while deleting', 'error');
                });
            }
            break;
    }
}

function viewHousehold(householdId) {
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
                document.getElementById('waterSource').value = data.household.water_source_type || '';
                document.getElementById('toiletFacility').value = data.household.toilet_facility_type || '';
                document.getElementById('householdNotes').value = data.household.notes || '';
                
                const headFullNameElement = document.getElementById('headFullName');
                if (data.household.household_head_id) {
                    headFullNameElement.innerHTML = `<a href="resident_profile.php?id=${data.household.household_head_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${data.household.head_name || 'N/A'}</a>`;
                } else {
                    headFullNameElement.textContent = data.household.head_name || 'N/A';
                }
                document.getElementById('headDateOfBirth').textContent = data.household.head_dob || 'N/A';
                document.getElementById('headSex').textContent = data.household.head_sex || 'N/A';
                document.getElementById('selectedResidentId').value = data.household.household_head_id;
              
                
                document.getElementById('householdNumber').setAttribute('readonly', 'readonly');
                document.getElementById('householdContact').setAttribute('readonly', 'readonly');
                document.getElementById('householdAddress').setAttribute('readonly', 'readonly');
                document.getElementById('waterSource').setAttribute('disabled', 'disabled');
                document.getElementById('toiletFacility').setAttribute('disabled', 'disabled');
                document.getElementById('householdNotes').setAttribute('readonly', 'readonly');

                
                document.getElementById('searchResidentBtn').style.display = 'none';
                document.getElementById('addMemberBtn').style.display = 'none';
                document.getElementById('saveHouseholdBtn').style.display = 'none';
                
                const form = document.getElementById('createHouseholdForm');
                form.setAttribute('data-household-id', householdId);
                form.setAttribute('data-view-mode', 'true');
                
                if (data.members && data.members.length > 0) {
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
                            <td>${member.date_of_birth || 'N/A'}</td>
                            <td>${member.sex || 'N/A'}</td>
                            <td>${member.relationship_to_head}</td>
                            <td>${member.mobile_number || 'N/A'}</td>
                            <td></td>
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
    fetch(`model/get_household_details.php?id=${householdId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('createHouseholdModal');
                modal.classList.add('show');
                
                const modalTitle = modal.querySelector('.household-modal-header h3');
                modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Household';
                
                // Set household number and make it readonly (cannot be edited)
                const householdNumberInput = document.getElementById('householdNumber');
                householdNumberInput.value = data.household.household_number;
                householdNumberInput.setAttribute('readonly', 'readonly');
                householdNumberInput.style.backgroundColor = '#f3f4f6';
                householdNumberInput.style.cursor = 'not-allowed';
                
                document.getElementById('householdContact').value = data.household.household_contact || '';
                document.getElementById('householdAddress').value = data.household.address;
                document.getElementById('waterSource').value = data.household.water_source_type || '';
                document.getElementById('toiletFacility').value = data.household.toilet_facility_type || '';
                document.getElementById('householdNotes').value = data.household.notes || '';
                
                const headFullNameElement = document.getElementById('headFullName');
                if (data.household.household_head_id) {
                    headFullNameElement.innerHTML = `<a href="resident_profile.php?id=${data.household.household_head_id}" style="color: var(--text-primary); text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-primary)'">${data.household.head_name || 'N/A'}</a>`;
                } else {
                    headFullNameElement.textContent = data.household.head_name || 'N/A';
                }
                document.getElementById('headDateOfBirth').textContent = data.household.head_dob || 'N/A';
                document.getElementById('headSex').textContent = data.household.head_sex || 'N/A';
                document.getElementById('selectedResidentId').value = data.household.household_head_id;
             
                
                // Hide search resident button - household head cannot be changed during edit
                document.getElementById('searchResidentBtn').style.display = 'none';
                
                document.getElementById('createHouseholdForm').setAttribute('data-household-id', householdId);
                document.getElementById('saveHouseholdBtn').innerHTML = '<i class="fas fa-save"></i> Update';
                
                if (data.members && data.members.length > 0) {
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
                            <td>${member.date_of_birth || 'N/A'}</td>
                            <td>${member.sex || 'N/A'}</td>
                            <td>${member.relationship_to_head}</td>
                            <td>${member.mobile_number || 'N/A'}</td>
                            <td>
                                <button class="btn-delete-member" title="Remove member">
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
    
    const searchResidentBtn = document.getElementById('searchResidentBtn');
    if (searchResidentBtn) {
        searchResidentBtn.addEventListener('click', () => {
            searchResident();
        });
    }
    
    const saveHouseholdBtn = document.getElementById('saveHouseholdBtn');
    if (saveHouseholdBtn) {
        saveHouseholdBtn.addEventListener('click', () => {
            saveHousehold();
        });
    }
    
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
    
    // Delete member buttons (event delegation)
    const membersTableBody = document.getElementById('membersTableBody');
    if (membersTableBody) {
        membersTableBody.addEventListener('click', (e) => {
            const deleteBtn = e.target.closest('.btn-delete-member');
            if (deleteBtn) {
                const row = deleteBtn.closest('tr');
                deleteMember(row);
            }
        });
    }
}

function deleteMember(row) {
    const memberName = row.cells[1].textContent;
    
    if (confirm(`Are you sure you want to remove ${memberName} from this household?`)) {
        row.style.opacity = '0';
        row.style.transition = 'opacity 0.3s ease';
        
        setTimeout(() => {
            row.remove();
            renumberMembers();
            
            const tbody = document.getElementById('membersTableBody');
            const remainingRows = tbody.querySelectorAll('tr:not(.no-members-row)');
            
            if (remainingRows.length === 0) {
                tbody.innerHTML = '<tr class="no-members-row"><td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 20px;">No members added yet</td></tr>';
            }
            
            showNotification('Member removed successfully', 'success');
        }, 300);
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
    
    // Add filter_households parameter to exclude already assigned residents
    fetch(`model/search_residents.php?search=${encodeURIComponent(searchTerm)}&filter_households=true`)
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
    document.getElementById('headDateOfBirth').textContent = resident.dateOfBirth;
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
        <td>${member.dateOfBirth}</td>
        <td>${member.sex}</td>
        <td>${member.relationship}</td>
        <td>${member.mobileNumber || 'N/A'}</td>
        <td>
            <button class="btn-delete-member" title="Remove member">
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
