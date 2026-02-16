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
                handleFileUpload(files[0]);
            }
        });
    }

    // Handle file selection
    if (uploadInput) {
        uploadInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileUpload(e.target.files[0]);
            }
        });
    }

    // Handle file upload
    function handleFileUpload(file) {
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

        // Show loading state
        uploadArea.classList.add('uploading');
        uploadArea.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #3b82f6;"></i><p style="margin-top: 10px;">Uploading...</p>';

        // Create form data
        const formData = new FormData();
        formData.append('avatar', file);

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
