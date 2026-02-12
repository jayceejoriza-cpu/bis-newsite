// ===================================
// Households Page JavaScript
// Enhanced with Table.js Integration
// ===================================

let householdsTable;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Set active navigation
    setActiveNavigation();
    
    // Initialize enhanced table
    initializeTable();
    
    // Initialize all event listeners
    initializeEventListeners();
    
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
// Table Initialization
// ===================================
function initializeTable() {
    // Initialize EnhancedTable with the households table
    householdsTable = new EnhancedTable('householdsTable', {
        sortable: true,
        searchable: true,
        paginated: false, // We'll handle pagination manually for this page
        responsive: true
    });
    
    updateTotalCount();
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
    
    const rows = document.querySelectorAll('#householdsTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const size = row.getAttribute('data-size');
        
        if (filterType === 'all') {
            row.style.display = '';
            visibleCount++;
        } else if (size === filterType) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateTotalCount(visibleCount);
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
    const rows = document.querySelectorAll('#householdsTableBody tr');
    const term = searchTerm.toLowerCase().trim();
    let visibleCount = 0;
    
    rows.forEach(row => {
        // Skip rows already hidden by filter
        const currentDisplay = row.style.display;
        if (currentDisplay === 'none' && term === '') {
            return;
        }
        
        const householdNumber = row.cells[0].textContent.toLowerCase();
        const headName = row.cells[1].textContent.toLowerCase();
        const memberCount = row.cells[2].textContent.toLowerCase();
        
        const matches = householdNumber.includes(term) || 
                       headName.includes(term) || 
                       memberCount.includes(term);
        
        if (term === '' || matches) {
            // Check if row should be visible based on current filter
            const activeFilter = document.querySelector('.tab-btn.active');
            const filterType = activeFilter ? activeFilter.getAttribute('data-filter') : 'all';
            const size = row.getAttribute('data-size');
            
            if (filterType === 'all' || size === filterType) {
                row.style.display = '';
                visibleCount++;
            }
        } else {
            row.style.display = 'none';
        }
    });
    
    updateTotalCount(visibleCount);
}

// ===================================
// Button Handlers
// ===================================
function initializeButtons() {
    // Create Household button
    const createHouseholdBtn = document.getElementById('createHouseholdBtn');
    if (createHouseholdBtn) {
        createHouseholdBtn.addEventListener('click', () => {
            showCreateHouseholdModal();
        });
    }
    
    // Refresh button
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            refreshData();
        });
    }
}

function showCreateHouseholdModal() {
    // TODO: Implement modal
    alert('Create Household Modal\n\nThis will open a form to create a new household with fields:\n- Household Number\n- Head of Household\n- Address\n- Members\n- Contact Information\n- etc.');
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
    
    // Create action menu
    const menu = document.createElement('div');
    menu.className = 'action-menu';
    menu.innerHTML = `
        <div class="action-menu-item" data-action="view">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </div>
        <div class="action-menu-item" data-action="edit">
            <i class="fas fa-edit"></i>
            <span>Edit Household</span>
        </div>
        <div class="action-menu-item" data-action="add-member">
            <i class="fas fa-user-plus"></i>
            <span>Add Member</span>
        </div>
        <div class="action-menu-item" data-action="view-members">
            <i class="fas fa-users"></i>
            <span>View Members</span>
        </div>
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Delete Household</span>
        </div>
    `;
    
    // Position menu
    const rect = button.getBoundingClientRect();
    menu.style.position = 'fixed';
    menu.style.top = `${rect.bottom + 5}px`;
    menu.style.left = `${rect.left - 150}px`;
    
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
    
    switch(action) {
        case 'view':
            alert(`View Household Details\n\nHousehold: ${householdNumber}\nHead: ${headName}\nMembers: ${memberCount}\n\nThis will open a detailed view of the household information.`);
            break;
            
        case 'edit':
            alert(`Edit Household\n\nHousehold: ${householdNumber}\nHead: ${headName}\n\nThis will open an edit form with the household's current information.`);
            break;
            
        case 'add-member':
            alert(`Add Member\n\nHousehold: ${householdNumber}\nHead: ${headName}\n\nThis will open a form to add a new member to this household.`);
            break;
            
        case 'view-members':
            alert(`View Members\n\nHousehold: ${householdNumber}\nHead: ${headName}\nTotal Members: ${memberCount}\n\nThis will display a list of all household members.`);
            break;
            
        case 'delete':
            if (confirm(`Are you sure you want to delete household ${householdNumber}?\n\nHead: ${headName}\nMembers: ${memberCount}\n\nThis action cannot be undone.`)) {
                // Simulate deletion
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    updateTotalCount();
                    showNotification('Household deleted successfully', 'success');
                }, 300);
            }
            break;
    }
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

// Export functions for external use if needed
window.householdsPage = {
    refresh: refreshData,
    applyFilter: applyFilter,
    search: performSearch
};
