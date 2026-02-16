/**
 * Barangay Officials Management
 * Handles CRUD operations for barangay officials
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Officials page loaded');
    // Initialize officials page
    initOfficials();
});

/**
 * Initialize officials functionality
 */
function initOfficials() {
    // Create Official Button
    const createOfficialBtn = document.getElementById('createOfficialBtn');
    if (createOfficialBtn) {
        createOfficialBtn.addEventListener('click', function() {
            // TODO: Open create official modal
            alert('Create Official Modal - To be implemented');
        });
    }
    
    // Term Filter Button
    const termFilterBtn = document.getElementById('termFilterBtn');
    if (termFilterBtn) {
        termFilterBtn.addEventListener('click', function() {
            // TODO: Open term filter dropdown
            alert('Term Filter - To be implemented');
        });
    }
    
    // Official Cards Click
    const officialCards = document.querySelectorAll('.official-card');
    console.log('Found official cards:', officialCards.length);
    officialCards.forEach(card => {
        card.addEventListener('click', function() {
            const officialId = this.getAttribute('data-official-id');
            viewOfficialDetails(officialId);
        });
    });
    
    // Edit Buttons
    const editButtons = document.querySelectorAll('.btn-edit');
    console.log('Found edit buttons:', editButtons.length);
    editButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const officialId = this.getAttribute('data-official-id');
            editOfficial(officialId);
        });
    });
    
    // Delete Buttons
    const deleteButtons = document.querySelectorAll('.btn-delete');
    console.log('Found delete buttons:', deleteButtons.length);
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            e.preventDefault();
            const officialId = this.getAttribute('data-official-id');
            console.log('Delete button clicked for official ID:', officialId);
            deleteOfficial(officialId);
        });
    });
}

/**
 * View official details
 */
function viewOfficialDetails(officialId) {
    console.log('Viewing official:', officialId);
    // TODO: Implement view official details modal
    alert(`View Official Details - ID: ${officialId}\nTo be implemented`);
}

/**
 * Edit official
 */
function editOfficial(officialId) {
    console.log('Editing official:', officialId);
    // TODO: Implement edit official modal
    alert(`Edit Official - ID: ${officialId}\nTo be implemented`);
}

/**
 * Delete official (Move to archive)
 */
function deleteOfficial(officialId) {
    if (confirm('Are you sure you want to move this official to archive?\n\nThis action will remove the official from the active list but preserve their record in the archive.')) {
        // Show loading state
        const deleteBtn = document.querySelector(`[data-official-id="${officialId}"].btn-delete`);
        if (deleteBtn) {
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
        }

        // Send delete request
        fetch('delete_official.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${officialId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert(data.message || 'Official moved to archive successfully');
                // Reload page to refresh the list
                refreshOfficials();
            } else {
                // Show error message
                alert('Error: ' + (data.message || 'Failed to archive official'));
                // Re-enable button
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while archiving the official');
            // Re-enable button
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Delete';
            }
        });
    }
}

/**
 * Refresh officials list
 */
function refreshOfficials() {
    location.reload();
}
