/**
 * Barangay Officials Management
 * Handles CRUD operations for barangay officials
 */

let cameraStream = null;
let capturedPhotoData = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('Officials page loaded');
    // Initialize officials page
    initOfficials();
    initModalHandlers();
});

/**
 * Initialize officials functionality
 */
function initOfficials() {
    // Create Official Button
    const createOfficialBtn = document.getElementById('createOfficialBtn');
    if (createOfficialBtn) {
        createOfficialBtn.addEventListener('click', function() {
            openCreateOfficialModal();
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
 * Initialize modal handlers
 */
function initModalHandlers() {
    // Upload Photo Button
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    const photoInput = document.getElementById('photoInput');
    
    if (uploadPhotoBtn && photoInput) {
        uploadPhotoBtn.addEventListener('click', function() {
            photoInput.click();
        });
        
        photoInput.addEventListener('change', function(e) {
            handlePhotoUpload(e.target.files[0]);
        });
    }
    
    // Start Camera Button
    const startCameraBtn = document.getElementById('startCameraBtn');
    if (startCameraBtn) {
        startCameraBtn.addEventListener('click', function() {
            openCameraModal();
        });
    }
    
    // Reset Photo Button
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    if (resetPhotoBtn) {
        resetPhotoBtn.addEventListener('click', function() {
            resetPhoto();
        });
    }
    
    // Capture Photo Button
    const capturePhotoBtn = document.getElementById('capturePhotoBtn');
    if (capturePhotoBtn) {
        capturePhotoBtn.addEventListener('click', function() {
            capturePhoto();
        });
    }
    
    // Term Date Change - Auto-calculate status
    const termStartInput = document.getElementById('termStart');
    const termEndInput = document.getElementById('termEnd');
    const statusSelect = document.getElementById('status');
    
    if (termStartInput && termEndInput && statusSelect) {
        termStartInput.addEventListener('change', function() {
            updateStatusBasedOnDates();
        });
        
        termEndInput.addEventListener('change', function() {
            updateStatusBasedOnDates();
        });
    }
    
    // Create Official Submit Button
    const createOfficialSubmitBtn = document.getElementById('createOfficialSubmitBtn');
    if (createOfficialSubmitBtn) {
        createOfficialSubmitBtn.addEventListener('click', function() {
            submitCreateOfficial();
        });
    }
    
    // Camera Modal Close - Stop camera
    const cameraModal = document.getElementById('cameraModal');
    if (cameraModal) {
        cameraModal.addEventListener('hidden.bs.modal', function() {
            stopCamera();
        });
    }
}

/**
 * Open create official modal
 */
function openCreateOfficialModal() {
    const modal = new bootstrap.Modal(document.getElementById('createOfficialModal'));
    resetForm();
    modal.show();
}

/**
 * Handle photo upload
 */
function handlePhotoUpload(file) {
    if (!file) return;
    
    // Validate file type
    if (!file.type.match('image.*')) {
        alert('Please select an image file');
        return;
    }
    
    // Validate file size (1MB)
    if (file.size > 1024 * 1024) {
        alert('File size must be less than 1MB');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        displayPhoto(e.target.result);
    };
    reader.readAsDataURL(file);
}

/**
 * Display photo in preview
 */
function displayPhoto(photoData) {
    const photoPreview = document.getElementById('photoPreview');
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    const photoDataInput = document.getElementById('photoData');
    
    photoPreview.innerHTML = `<img src="${photoData}" alt="Official Photo">`;
    resetPhotoBtn.style.display = 'inline-flex';
    photoDataInput.value = photoData;
    capturedPhotoData = photoData;
}

/**
 * Reset photo
 */
function resetPhoto() {
    const photoPreview = document.getElementById('photoPreview');
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    const photoInput = document.getElementById('photoInput');
    const photoDataInput = document.getElementById('photoData');
    
    photoPreview.innerHTML = '<i class="fas fa-user"></i>';
    resetPhotoBtn.style.display = 'none';
    photoInput.value = '';
    photoDataInput.value = '';
    capturedPhotoData = null;
}

/**
 * Open camera modal
 */
async function openCameraModal() {
    const cameraModal = new bootstrap.Modal(document.getElementById('cameraModal'));
    cameraModal.show();
    
    try {
        const video = document.getElementById('cameraVideo');
        cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
        video.srcObject = cameraStream;
    } catch (error) {
        console.error('Error accessing camera:', error);
        alert('Could not access camera. Please check permissions.');
        cameraModal.hide();
    }
}

/**
 * Capture photo from camera
 */
function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const context = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    context.drawImage(video, 0, 0);
    
    const photoData = canvas.toDataURL('image/jpeg');
    displayPhoto(photoData);
    
    // Close camera modal
    const cameraModal = bootstrap.Modal.getInstance(document.getElementById('cameraModal'));
    cameraModal.hide();
}

/**
 * Stop camera
 */
function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
}

/**
 * Update status based on term dates
 */
function updateStatusBasedOnDates() {
    const termStart = document.getElementById('termStart').value;
    const termEnd = document.getElementById('termEnd').value;
    const statusSelect = document.getElementById('status');
    
    if (!termStart || !termEnd) return;
    
    const today = new Date();
    const startDate = new Date(termStart);
    const endDate = new Date(termEnd);
    
    let status = 'Inactive';
    
    if (today >= startDate && today <= endDate) {
        status = 'Active';
    } else if (today > endDate) {
        status = 'Completed';
    }
    
    statusSelect.value = status;
}

/**
 * Submit create official form
 */
async function submitCreateOfficial() {
    const form = document.getElementById('createOfficialForm');
    const submitBtn = document.getElementById('createOfficialSubmitBtn');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    try {
        // Prepare form data
        const formData = new FormData();
        formData.append('fullname', document.getElementById('fullname')?.value || '');
        formData.append('chairmanship', document.getElementById('chairmanship')?.value || '');
        formData.append('position', document.getElementById('position')?.value || '');
        formData.append('term_start', document.getElementById('termStart')?.value || '');
        formData.append('term_end', document.getElementById('termEnd')?.value || '');
        formData.append('status', document.getElementById('status')?.value || '');
        formData.append('contact_number', document.getElementById('contactNumber')?.value || '');
        formData.append('photo', capturedPhotoData || '');
        
        // Submit to backend
        const response = await fetch('save_official.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show success message
            alert('Official created successfully!');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createOfficialModal'));
            modal.hide();
            
            // Reload page to show new official
            location.reload();
        } else {
            throw new Error(result.message || 'Failed to create official');
        }
        
    } catch (error) {
        console.error('Error creating official:', error);
        alert('Error: ' + error.message);
        
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Create';
    }
}

/**
 * Reset form
 */
function resetForm() {
    document.getElementById('createOfficialForm').reset();
    resetPhoto();
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
