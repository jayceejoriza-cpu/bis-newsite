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
    
    if (filterType === 'all') {
        residentsTable.reset();
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
    
    // Export button (if exists)
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', () => {
            const timestamp = new Date().toISOString().split('T')[0];
            residentsTable.exportToCSV(`residents-${timestamp}.csv`);
        });
    }
}

function showCreateResidentModal() {
    // TODO: Implement modal
    alert('Create Resident Modal\n\nThis will open a form to add a new resident with fields:\n- Full Name\n- Date of Birth\n- Sex\n- Address\n- Contact Information\n- Voter Status\n- etc.');
}

function showAdvancedFilters() {
    // TODO: Implement advanced filter modal
    alert('Advanced Filters\n\nFilter by:\n- Age Range\n- Sex\n- Verification Status\n- Voter Status\n- Activity Status\n- Date Range');
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
    const tableContainer = document.querySelector('.table-container');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            viewButtons.forEach(vBtn => vBtn.classList.remove('active'));
            btn.classList.add('active');
            
            const view = btn.getAttribute('data-view');
            
            if (view === 'grid') {
                // TODO: Implement grid view
                alert('Grid View\n\nThis will display residents in a card-based grid layout.');
            } else {
                tableContainer.style.display = '';
            }
        });
    });
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
            <span>Edit Resident</span>
        </div>
        <div class="action-menu-item" data-action="print">
            <i class="fas fa-print"></i>
            <span>Print ID</span>
        </div>
        <div class="action-menu-divider"></div>
        <div class="action-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            <span>Delete Resident</span>
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
            handleAction(action, residentName, residentId, row);
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
    
    switch(action) {
        case 'view':
            alert(`View Details\n\nResident: ${name}\nID: ${id}\n\nThis will open a detailed view of the resident's information.`);
            break;
            
        case 'edit':
            alert(`Edit Resident\n\nResident: ${name}\nID: ${id}\n\nThis will open an edit form with the resident's current information.`);
            break;
            
        case 'print':
            alert(`Print ID\n\nResident: ${name}\nID: ${id}\n\nThis will generate and print a resident ID card.`);
            break;
            
        case 'delete':
            if (confirm(`Are you sure you want to delete ${name}?\n\nThis action cannot be undone.`)) {
                // Simulate deletion
                row.style.opacity = '0';
                setTimeout(() => {
                    row.remove();
                    residentsTable.refresh();
                    showNotification('Resident deleted successfully', 'success');
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
        z-index: 1000;
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
`;
document.head.appendChild(style);
