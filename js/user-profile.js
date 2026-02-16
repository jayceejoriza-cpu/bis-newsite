// User Profile Management
document.addEventListener('DOMContentLoaded', function() {
    // Password Strength Checker
    const passwordInput = document.getElementById('password');
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const strengthText = document.getElementById('strengthText');

    if (passwordInput && passwordStrengthBar) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            // Remove all classes
            passwordStrengthBar.classList.remove('weak', 'medium', 'strong');
            
            if (password.length === 0) {
                passwordStrengthBar.style.width = '0%';
                strengthText.textContent = 'Not set';
                strengthText.style.color = 'var(--text-secondary)';
            } else if (strength.score <= 2) {
                passwordStrengthBar.classList.add('weak');
                strengthText.textContent = 'Weak';
                strengthText.style.color = '#ef4444';
            } else if (strength.score <= 3) {
                passwordStrengthBar.classList.add('medium');
                strengthText.textContent = 'Medium';
                strengthText.style.color = '#f59e0b';
            } else {
                passwordStrengthBar.classList.add('strong');
                strengthText.textContent = 'Strong';
                strengthText.style.color = '#10b981';
            }
        });
    }

    function checkPasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/\d/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        return { score: score };
    }

    // Avatar Modal Management
    const avatarModal = document.getElementById('avatarModal');
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const closeModalBtn = document.getElementById('closeAvatarModal');
    const uploadInput = document.getElementById('avatarUploadInput');
    const uploadArea = document.getElementById('uploadArea');
    const uploadForm = document.getElementById('avatarUploadForm');
    const recentAvatarsGrid = document.getElementById('recentAvatarsGrid');

    // Open modal
    if (changeAvatarBtn) {
        changeAvatarBtn.addEventListener('click', function() {
            avatarModal.classList.add('active');
            loadRecentAvatars();
        });
    }

    // Close modal
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            avatarModal.classList.remove('active');
        });
    }

    // Close modal when clicking outside
    avatarModal.addEventListener('click', function(e) {
        if (e.target === avatarModal) {
            avatarModal.classList.remove('active');
        }
    });

    // Trigger file input when clicking upload area
    if (uploadArea) {
        uploadArea.addEventListener('click', function() {
            uploadInput.click();
        });
    }

    // Handle drag and drop
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadInput.files = files;
                handleFileSelection(files[0]);
            }
        });
    }

    // Handle file selection
    if (uploadInput) {
        uploadInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileSelection(e.target.files[0]);
            }
        });
    }

    // Handle file selection (show crop modal)
    function handleFileSelection(file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.', 'error');
            return;
        }

        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showNotification('File size too large. Maximum size is 5MB.', 'error');
            return;
        }

        // Read file and show crop modal
        const reader = new FileReader();
        reader.onload = function(e) {
            showCropModal(e.target.result, file.name);
        };
        reader.readAsDataURL(file);
    }

    // Handle file upload (after cropping)
    function handleFileUpload(blob, filename) {
        // Show loading state
        uploadArea.classList.add('uploading');
        uploadArea.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i><p style="margin-top: 10px;">Uploading...</p>';

        // Create form data
        const formData = new FormData();
        formData.append('avatar', blob, filename);

        // Upload file
        fetch('upload_avatar.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                updateAvatarDisplay(data.avatar_url);
                loadRecentAvatars();
                setTimeout(() => {
                    avatarModal.classList.remove('active');
                    location.reload(); // Reload to update header avatar
                }, 1500);
            } else {
                showNotification(data.message, 'error');
                resetUploadArea();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while uploading the image.', 'error');
            resetUploadArea();
        });
    }

    // Reset upload area
    function resetUploadArea() {
        uploadArea.classList.remove('uploading');
        uploadArea.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Upload Image</p>
        `;
    }

    // Load recent avatars
    function loadRecentAvatars() {
        fetch('get_recent_avatars.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentAvatars(data.avatars);
            }
        })
        .catch(error => {
            console.error('Error loading recent avatars:', error);
        });
    }

    // Display recent avatars
    function displayRecentAvatars(avatars) {
        if (!recentAvatarsGrid) return;

        if (avatars.length === 0) {
            recentAvatarsGrid.innerHTML = '<p style="text-align: center; color: var(--text-secondary); grid-column: 1 / -1;">No recent avatars found</p>';
            return;
        }

        recentAvatarsGrid.innerHTML = '';
        avatars.forEach(avatar => {
            const avatarItem = document.createElement('div');
            avatarItem.className = 'recent-avatar-item';
            avatarItem.innerHTML = `<img src="${avatar.url}" alt="Avatar">`;
            avatarItem.addEventListener('click', function() {
                selectAvatar(avatar.path);
            });
            recentAvatarsGrid.appendChild(avatarItem);
        });
    }

    // Select avatar from recent
    function selectAvatar(avatarPath) {
        fetch('select_avatar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ avatar_path: avatarPath })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                updateAvatarDisplay(data.avatar_url);
                setTimeout(() => {
                    avatarModal.classList.remove('active');
                    location.reload(); // Reload to update header avatar
                }, 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while selecting the avatar.', 'error');
        });
    }

    // Update avatar display
    function updateAvatarDisplay(avatarUrl) {
        const profileAvatar = document.querySelector('.profile-avatar-large');
        if (profileAvatar) {
            profileAvatar.innerHTML = `<img src="${avatarUrl}" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">`;
        }
    }

    // Image Cropper
    let cropper = null;
    let currentFile = null;

    const cropModal = document.getElementById('cropModal');
    const closeCropModalBtn = document.getElementById('closeCropModal');
    const cropImage = document.getElementById('cropImage');
    const zoomSlider = document.getElementById('zoomSlider');
    const zoomValue = document.getElementById('zoomValue');
    const resetCropBtn = document.getElementById('resetCropBtn');
    const cancelCropBtn = document.getElementById('cancelCropBtn');
    const applyCropBtn = document.getElementById('applyCropBtn');

    // Show crop modal
    function showCropModal(imageSrc, filename) {
        currentFile = filename;
        cropImage.src = imageSrc;
        
        // Close avatar modal
        avatarModal.classList.remove('active');
        
        // Show crop modal
        cropModal.classList.add('active');

        // Initialize cropper
        if (cropper) {
            cropper.destroy();
        }

        cropper = new Cropper(cropImage, {
            aspectRatio: 1,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: false,
            center: true,
            highlight: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            toggleDragModeOnDblclick: false,
            ready: function() {
                // Set initial zoom
                const containerData = cropper.getContainerData();
                const imageData = cropper.getImageData();
                const minZoom = Math.max(
                    containerData.width / imageData.naturalWidth,
                    containerData.height / imageData.naturalHeight
                );
                
                // Store min zoom for slider
                zoomSlider.setAttribute('data-min-zoom', minZoom);
                zoomSlider.value = 0;
                zoomValue.textContent = '100%';
            }
        });
    }

    // Close crop modal
    if (closeCropModalBtn) {
        closeCropModalBtn.addEventListener('click', function() {
            closeCropModal();
        });
    }

    if (cancelCropBtn) {
        cancelCropBtn.addEventListener('click', function() {
            closeCropModal();
        });
    }

    function closeCropModal() {
        cropModal.classList.remove('active');
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        currentFile = null;
        
        // Reset upload input
        if (uploadInput) {
            uploadInput.value = '';
        }
        
        // Show avatar modal again
        avatarModal.classList.add('active');
    }

    // Zoom slider
    if (zoomSlider) {
        zoomSlider.addEventListener('input', function() {
            if (!cropper) return;
            
            const value = parseFloat(this.value);
            const minZoom = parseFloat(this.getAttribute('data-min-zoom')) || 0;
            
            // Calculate zoom ratio (0-100 slider to minZoom-2x zoom)
            const maxZoom = minZoom * 2;
            const zoomRatio = minZoom + (maxZoom - minZoom) * (value / 100);
            
            cropper.zoomTo(zoomRatio);
            
            // Update zoom percentage display
            const percentage = Math.round((zoomRatio / minZoom) * 100);
            zoomValue.textContent = percentage + '%';
        });
    }

    // Reset crop
    if (resetCropBtn) {
        resetCropBtn.addEventListener('click', function() {
            if (!cropper) return;
            
            cropper.reset();
            zoomSlider.value = 0;
            zoomValue.textContent = '100%';
        });
    }

    // Apply crop
    if (applyCropBtn) {
        applyCropBtn.addEventListener('click', function() {
            if (!cropper) return;
            
            applyCropBtn.disabled = true;
            applyCropBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Get cropped canvas
            const canvas = cropper.getCroppedCanvas({
                width: 512,
                height: 512,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            // Convert to blob
            canvas.toBlob(function(blob) {
                if (blob) {
                    // Close crop modal
                    cropModal.classList.remove('active');
                    if (cropper) {
                        cropper.destroy();
                        cropper = null;
                    }
                    
                    // Show avatar modal for upload progress
                    avatarModal.classList.add('active');
                    
                    // Upload the cropped image
                    handleFileUpload(blob, currentFile);
                } else {
                    showNotification('Failed to process image. Please try again.', 'error');
                    applyCropBtn.disabled = false;
                    applyCropBtn.innerHTML = '<i class="fas fa-check"></i> Apply';
                }
            }, 'image/jpeg', 0.95);
        });
    }

    // Close crop modal when clicking outside
    cropModal.addEventListener('click', function(e) {
        if (e.target === cropModal) {
            closeCropModal();
        }
    });

    // Show notification
    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotification = document.querySelector('.avatar-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `avatar-notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Add notification styles if not already present
        if (!document.querySelector('style[data-notification-styles]')) {
            const style = document.createElement('style');
            style.setAttribute('data-notification-styles', 'true');
            style.textContent = `
                .avatar-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                    z-index: 10000;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    opacity: 0;
                    transform: translateX(100px);
                    transition: opacity 0.3s ease, transform 0.3s ease;
                }
                .avatar-notification.show {
                    opacity: 1;
                    transform: translateX(0);
                }
                .avatar-notification.success {
                    background-color: #10b981;
                    color: white;
                }
                .avatar-notification.error {
                    background-color: #ef4444;
                    color: white;
                }
                .avatar-notification i {
                    font-size: 1.25rem;
                }
            `;
            document.head.appendChild(style);
        }
        
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
