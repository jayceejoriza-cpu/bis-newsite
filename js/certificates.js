/**
 * Certificates Page JavaScript
 * Handles search, filtering, and certificate management
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // DOM Elements
    // ============================================
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    const refreshBtn = document.getElementById('refreshBtn');
    const createCertificateBtn = document.getElementById('createCertificateBtn');
    const filterTabs = document.querySelectorAll('.tab-btn');
    const certificatesGrid = document.getElementById('certificatesGrid');
    const certificateCards = document.querySelectorAll('.certificate-card');
    const contextMenu = document.getElementById('contextMenu');
    const menuButtons = document.querySelectorAll('.btn-menu');
    
    let currentFilter = 'all';
    let currentCertificateId = null;
    
    // ============================================
    // Search Functionality
    // ============================================
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            filterCertificates(searchTerm, currentFilter);
            
            // Show/hide clear button
            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm ? 'flex' : 'none';
            }
        });
    }
    
    // Clear search
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            filterCertificates('', currentFilter);
            searchInput.focus();
        });
    }
    
    // ============================================
    // Filter Tabs
    // ============================================
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            filterTabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get filter value
            currentFilter = this.getAttribute('data-filter');
            
            // Apply filter
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            filterCertificates(searchTerm, currentFilter);
        });
    });
    
    // ============================================
    // Filter Certificates Function
    // ============================================
    function filterCertificates(searchTerm, filter) {
        let visibleCount = 0;
        
        certificateCards.forEach(card => {
            const title = card.querySelector('.card-title').textContent.toLowerCase();
            const status = card.getAttribute('data-status');
            
            // Check search match
            const matchesSearch = !searchTerm || title.includes(searchTerm);
            
            // Check filter match
            let matchesFilter = false;
            if (filter === 'all') {
                matchesFilter = true;
            } else if (filter === 'published') {
                matchesFilter = status === 'published';
            } else if (filter === 'unpublished') {
                matchesFilter = status === 'unpublished';
            }
            
            // Show/hide card
            if (matchesSearch && matchesFilter) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });
        
        // Show empty state if no results
        showEmptyState(visibleCount === 0 && certificateCards.length > 0);
    }
    
    // ============================================
    // Empty State
    // ============================================
    function showEmptyState(show) {
        let emptyState = certificatesGrid.querySelector('.empty-state');
        
        if (show && !emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <i class="fas fa-search"></i>
                <p>No certificates found</p>
                <p class="empty-subtitle">Try adjusting your search or filter</p>
            `;
            certificatesGrid.appendChild(emptyState);
        } else if (!show && emptyState) {
            emptyState.remove();
        }
    }
    
    // ============================================
    // Refresh Button
    // ============================================
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            // Add spinning animation
            const icon = this.querySelector('i');
            icon.classList.add('fa-spin');
            
            // Reload page after short delay
            setTimeout(() => {
                location.reload();
            }, 500);
        });
    }
    
    // ============================================
    // Create Certificate Button
    // ============================================
    if (createCertificateBtn) {
        createCertificateBtn.addEventListener('click', function() {
            // Navigate to create certificate page
            window.location.href = 'create-certificate.php';
        });
    }
    
    // ============================================
    // Context Menu for Certificate Actions
    // ============================================
    menuButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            currentCertificateId = this.getAttribute('data-id');
            
            // Position context menu
            const rect = this.getBoundingClientRect();
            contextMenu.style.top = `${rect.bottom + 5}px`;
            contextMenu.style.left = `${rect.left - 150}px`;
            contextMenu.style.display = 'block';
        });
    });
    
    // Close context menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.context-menu') && !e.target.closest('.btn-menu')) {
            contextMenu.style.display = 'none';
        }
    });
    
    // ============================================
    // Context Menu Actions
    // ============================================
    const contextMenuItems = document.querySelectorAll('.context-menu-item');
    contextMenuItems.forEach(item => {
        item.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            handleCertificateAction(action, currentCertificateId);
            contextMenu.style.display = 'none';
        });
    });
    
    // ============================================
    // Handle Certificate Actions
    // ============================================
    function handleCertificateAction(action, certificateId) {
        switch(action) {
            case 'edit':
                // Navigate to edit-certificate.php with certificate ID
                window.location.href = `edit-certificate.php?id=${certificateId}`;
                break;
                
            case 'duplicate':
                // TODO: Duplicate certificate
                console.log('Duplicate certificate:', certificateId);
                if (confirm('Are you sure you want to duplicate this certificate?')) {
                    alert(`Duplicate certificate ${certificateId} - Feature coming soon`);
                }
                break;
                
            case 'toggle-status':
                // TODO: Toggle publish/unpublish status
                console.log('Toggle status:', certificateId);
                toggleCertificateStatus(certificateId);
                break;
                
            case 'delete':
                // TODO: Delete certificate
                console.log('Delete certificate:', certificateId);
                if (confirm('Are you sure you want to delete this certificate? This action cannot be undone.')) {
                    deleteCertificate(certificateId);
                }
                break;
        }
    }
    
    // ============================================
    // Toggle Certificate Status
    // ============================================
    function toggleCertificateStatus(certificateId) {
        // TODO: Implement AJAX call to toggle status
        const card = document.querySelector(`.certificate-card[data-id="${certificateId}"]`);
        if (card) {
            const currentStatus = card.getAttribute('data-status');
            const newStatus = currentStatus === 'published' ? 'unpublished' : 'published';
            
            // Update card status (temporary - should be done via AJAX)
            card.setAttribute('data-status', newStatus);
            
            const statusBadge = card.querySelector('.status-badge');
            statusBadge.className = `status-badge status-${newStatus}`;
            statusBadge.innerHTML = `
                <i class="fas ${newStatus === 'published' ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}
            `;
            
            // Show success message
            showNotification(`Certificate status changed to ${newStatus}`, 'success');
        }
    }
    
    // ============================================
    // Delete Certificate
    // ============================================
    function deleteCertificate(certificateId) {
        // TODO: Implement AJAX call to delete certificate
        const card = document.querySelector(`.certificate-card[data-id="${certificateId}"]`);
        if (card) {
            // Fade out animation
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                card.remove();
                
                // Update total count
                const totalElement = document.querySelector('.total-count strong');
                if (totalElement) {
                    const currentTotal = parseInt(totalElement.textContent.replace(/,/g, ''));
                    totalElement.textContent = (currentTotal - 1).toLocaleString();
                }
                
                // Show empty state if no cards left
                const remainingCards = document.querySelectorAll('.certificate-card:not(.hidden)');
                if (remainingCards.length === 0) {
                    showEmptyState(true);
                }
                
                showNotification('Certificate deleted successfully', 'success');
            }, 300);
        }
    }
    
    // ============================================
    // Show Notification
    // ============================================
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
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
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // ============================================
    // Action Icons (Bottom)
    // ============================================
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const title = this.getAttribute('title');
            showNotification(`${title} feature coming soon`, 'info');
        });
    });
    
    // ============================================
    // Certificate Card Click to Edit
    // ============================================
    const clickableCards = document.querySelectorAll('.certificate-card-clickable');
    clickableCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't navigate if clicking on menu button or context menu
            if (e.target.closest('.btn-menu') || e.target.closest('.context-menu')) {
                return;
            }
            
            const certificateId = this.getAttribute('data-id');
            if (certificateId) {
                console.log('Navigating to edit certificate:', certificateId);
                window.location.href = `edit-certificate.php?id=${certificateId}`;
            }
        });
    });
    
    // ============================================
    // Initialize
    // ============================================
    // Apply initial filter
    filterCertificates('', currentFilter);
    
    // Hide clear button initially
    if (clearSearchBtn) {
        clearSearchBtn.style.display = 'none';
    }
});

// ============================================
// Add CSS animations
// ============================================
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
