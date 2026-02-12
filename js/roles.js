// ===================================
// Roles & Permissions Page JavaScript
// ===================================

// Set active navigation
document.addEventListener('DOMContentLoaded', () => {
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Add active class to Roles nav item
    const rolesNavItem = document.querySelector('a[href="roles.php"]');
    if (rolesNavItem) {
        rolesNavItem.parentElement.classList.add('active');
    }
});

// Search functionality
const searchInput = document.getElementById('searchInput');
const clearSearchBtn = document.getElementById('clearSearch');
const tableBody = document.getElementById('rolesTableBody');

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase();
        const rows = tableBody.querySelectorAll('tr');
        
        rows.forEach(row => {
            const name = row.querySelector('.role-name')?.textContent.toLowerCase() || '';
            const description = row.querySelectorAll('td')[1]?.textContent.toLowerCase() || '';
            
            if (name.includes(searchTerm) || description.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
}

// Clear search
if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
        searchInput.focus();
    });
}

// Create Role button
const createRoleBtn = document.getElementById('createRoleBtn');
if (createRoleBtn) {
    createRoleBtn.addEventListener('click', () => {
        alert('Create Role functionality - To be implemented');
        // TODO: Open modal or navigate to create role page
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
            // TODO: Reload data from server
            console.log('Refreshing roles data...');
        }, 500);
    });
}

// Action buttons
const actionButtons = document.querySelectorAll('.btn-action');
actionButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
        const row = e.target.closest('tr');
        const roleName = row.querySelector('.role-name')?.textContent;
        
        // TODO: Show action menu (Edit, Delete, Permissions, etc.)
        console.log('Action clicked for role:', roleName);
        alert(`Actions for role: ${roleName}\n\n- Edit Role\n- Delete Role\n- Manage Permissions`);
    });
});

// Add spin animation for refresh button
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);

// Table row hover effect enhancement
const tableRows = document.querySelectorAll('.data-table tbody tr');
tableRows.forEach(row => {
    row.addEventListener('mouseenter', () => {
        row.style.transform = 'scale(1.001)';
    });
    
    row.addEventListener('mouseleave', () => {
        row.style.transform = 'scale(1)';
    });
});

console.log('Roles & Permissions page loaded successfully');
