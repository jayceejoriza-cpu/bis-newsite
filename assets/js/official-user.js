/**
 * Official Users Page - JavaScript
 * Handles: search, filter, create/edit/delete user modals, AJAX, toast
 * Roles are now multi-select checkboxes from the DB
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // Element References
    // ============================================
    const searchInput        = document.getElementById('searchInput');
    const clearSearchBtn     = document.getElementById('clearSearch');
    const filterBtn          = document.getElementById('filterBtn');
    const filterPanel        = document.getElementById('filterPanel');
    const refreshBtn         = document.getElementById('refreshBtn');
    const filterRole         = document.getElementById('filterRole');
    const filterStatus       = document.getElementById('filterStatus');
    const clearFiltersBtn    = document.getElementById('clearFiltersBtn');
    const applyFiltersBtn    = document.getElementById('applyFiltersBtn');

    const createUserBtn      = document.getElementById('createUserBtn');
    const userModal          = document.getElementById('userModal');
    const closeUserModal     = document.getElementById('closeUserModal');
    const cancelUserModal    = document.getElementById('cancelUserModal');
    const saveUserBtn        = document.getElementById('saveUserBtn');
    const userForm           = document.getElementById('userForm');
    const modalTitle         = document.getElementById('modalTitle');
    const saveBtnText        = document.getElementById('saveBtnText');

    const confirmDeleteModal = document.getElementById('confirmDeleteModal');
    const cancelDelete       = document.getElementById('cancelDelete');
    const confirmDeleteBtn   = document.getElementById('confirmDelete');
    const deleteUserName     = document.getElementById('deleteUserName');

    const togglePasswordBtn  = document.getElementById('togglePassword');
    const passwordInput      = document.getElementById('password');
    const passwordRequired   = document.getElementById('passwordRequired');
    const passwordHint       = document.getElementById('passwordHint');
    const confirmPasswordInput   = document.getElementById('confirmPassword');
    const confirmPasswordRequired = document.getElementById('confirmPasswordRequired');
    const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');

    const toast              = document.getElementById('toast');
    const toastMessage       = document.getElementById('toastMessage');
    const totalCount         = document.getElementById('totalCount');
    const tableBody          = document.getElementById('usersTableBody');

    let usernameCheckTimeout = null;
    let currentDeleteId = null;
    let isEditMode      = false;
    let toastTimer      = null;

    // ============================================
    // Search
    // ============================================
    searchInput.addEventListener('input', function () {
        const val = this.value.trim();
        clearSearchBtn.classList.toggle('visible', val.length > 0);
        filterTable();
    });

    clearSearchBtn.addEventListener('click', function () {
        searchInput.value = '';
        clearSearchBtn.classList.remove('visible');
        filterTable();
        searchInput.focus();
    });

    // ============================================
    // Filter Panel
    // ============================================
    filterBtn.addEventListener('click', function () {
        const isVisible = filterPanel.style.display !== 'none';
        filterPanel.style.display = isVisible ? 'none' : 'block';
        filterBtn.classList.toggle('active', !isVisible);
    });

    applyFiltersBtn.addEventListener('click', filterTable);

    clearFiltersBtn.addEventListener('click', function () {
        filterRole.value   = '';
        filterStatus.value = '';
        filterTable();
    });

    // ============================================
    // Filter Table Rows
    // ============================================
    function filterTable() {
        const searchVal  = searchInput.value.toLowerCase().trim();
        const roleVal    = filterRole.value.toLowerCase();
        const statusVal  = filterStatus.value.toLowerCase();

        const rows = tableBody.querySelectorAll('tr[data-name]');
        let visibleCount = 0;

        rows.forEach(function (row) {
            const name     = (row.dataset.name     || '').toLowerCase();
            const username = (row.dataset.username || '').toLowerCase();
            const roles    = (row.dataset.roles    || '').toLowerCase(); // comma-separated role names
            const status   = (row.dataset.status   || '').toLowerCase();

            const matchSearch = !searchVal || name.includes(searchVal) || username.includes(searchVal);
            const matchRole   = !roleVal   || roles.split(',').some(r => r.trim() === roleVal);
            const matchStatus = !statusVal || status === statusVal;

            const visible = matchSearch && matchRole && matchStatus;
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (totalCount) totalCount.textContent = visibleCount;
        updateEmptyState(visibleCount, rows.length);
    }

    function updateEmptyState(visibleCount, totalRows) {
        const existingEmpty = tableBody.querySelector('.no-results-row');
        if (existingEmpty) existingEmpty.remove();

        if (visibleCount === 0 && totalRows > 0) {
            const tr = document.createElement('tr');
            tr.className = 'no-results-row';
            tr.innerHTML = `
                <td colspan="5" class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>No users found</p>
                    <span>Try adjusting your search or filter criteria</span>
                </td>`;
            tableBody.appendChild(tr);
        }
    }

    // ============================================
    // Refresh
    // ============================================
    refreshBtn.addEventListener('click', function () {
        const icon = this.querySelector('i');
        icon.style.transition = 'transform 0.6s ease';
        icon.style.transform  = 'rotate(360deg)';
        setTimeout(() => {
            icon.style.transition = '';
            icon.style.transform  = '';
        }, 600);
        window.location.reload();
    });

    // ============================================
    // Open Modal (Create / Edit)
    // ============================================
    createUserBtn.addEventListener('click', function () {
        openModal('create');
    });

    function openModal(mode, data = {}) {
        isEditMode = (mode === 'edit');

        userForm.reset();
        clearFormErrors();
        uncheckAllRoles();

        if (isEditMode) {
            modalTitle.textContent  = 'Edit User';
            saveBtnText.textContent = 'Update';
            document.getElementById('userId').value   = data.id       || '';
            document.getElementById('fullName').value = data.name     || '';
            document.getElementById('username').value = data.username || '';
            document.getElementById('status').value   = data.status   || 'Active';
            // Password optional in edit
            passwordRequired.style.display = 'none';
            confirmPasswordRequired.style.display = 'none';
            passwordHint.style.display     = 'inline';
            passwordInput.required         = false;
            confirmPasswordInput.required  = false;
            // Pre-check assigned roles
            if (data.roleIds && Array.isArray(data.roleIds)) {
                data.roleIds.forEach(function (rid) {
                    const cb = document.querySelector('.role-checkbox[value="' + rid + '"]');
                    if (cb) cb.checked = true;
                });
            }
        } else {
            modalTitle.textContent  = 'Create User';
            saveBtnText.textContent = 'Save';
            document.getElementById('userId').value = '';
            passwordRequired.style.display = 'inline';
            confirmPasswordRequired.style.display = 'inline';
            passwordHint.style.display     = 'none';
            passwordInput.required         = true;
            confirmPasswordInput.required  = true;
        }

        userModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('fullName').focus(), 100);
    }

    function closeModal() {
        userModal.classList.remove('active');
        document.body.style.overflow = '';
        userForm.reset();
        clearFormErrors();
        uncheckAllRoles();
    }

    function uncheckAllRoles() {
        document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
    }

    closeUserModal.addEventListener('click', closeModal);
    cancelUserModal.addEventListener('click', closeModal);

    // ============================================
    // Toggle Password Visibility
    // ============================================
    togglePasswordBtn.addEventListener('click', function () {
        const isText = passwordInput.type === 'text';
        passwordInput.type = isText ? 'password' : 'text';
        this.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    toggleConfirmPasswordBtn.addEventListener('click', function () {
        const isText = confirmPasswordInput.type === 'text';
        confirmPasswordInput.type = isText ? 'password' : 'text';
        this.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
    });

    // ============================================
    // Real-time Username Check
    // ============================================
    document.getElementById('username').addEventListener('input', function () {
        const username = this.value.trim();
        const userId = document.getElementById('userId').value;
        
        clearTimeout(usernameCheckTimeout);
        if (username.length < 3) return;

        usernameCheckTimeout = setTimeout(() => {
            const fd = new FormData();
            fd.append('action', 'check_username');
            fd.append('username', username);
            if (userId) fd.append('user_id', userId);

            fetch('model/save-user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showError('usernameError', data.message);
                    document.getElementById('username').classList.add('error');
                }
            });
        }, 500);
    });

    // ============================================
    // Save User
    // ============================================
    saveUserBtn.addEventListener('click', function () {
        if (validateForm()) submitUserForm();
    });

    userForm.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            if (validateForm()) submitUserForm();
        }
    });

    function validateForm() {
        clearFormErrors();
        let valid = true;

        const fullName = document.getElementById('fullName').value.trim();
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = confirmPasswordInput.value;
        const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{8,16}$/;

        if (!fullName) {
            showError('fullNameError', 'Full name is required.');
            document.getElementById('fullName').classList.add('error');
            valid = false;
        }
        if (!username) {
            showError('usernameError', 'Username is required.');
            document.getElementById('username').classList.add('error');
            valid = false;
        } else if (username.length < 3) {
            showError('usernameError', 'Username must be at least 3 characters.');
            document.getElementById('username').classList.add('error');
            valid = false;
        }
        if (!isEditMode && !password) {
            showError('passwordError', 'Password is required.');
            document.getElementById('password').classList.add('error');
            valid = false;
        } else if (password && !passwordRegex.test(password)) {
            showError('passwordError', 'Password must be 8-16 characters with at least one uppercase, one number, and one special character.');
            document.getElementById('password').classList.add('error');
            valid = false;
        }

        if ((!isEditMode || password) && password !== confirmPassword) {
            showError('confirmPasswordError', 'Passwords do not match.');
            confirmPasswordInput.classList.add('error');
            valid = false;
        }

        return valid;
    }

    function submitUserForm() {
        const btn = saveUserBtn;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        const formData = new FormData(userForm);
        formData.append('action', isEditMode ? 'edit' : 'create');

        fetch('model/save-user.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeModal();
                showToast(data.message || 'User saved successfully.', 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast(data.message || 'An error occurred.', 'error');
                if (data.field) {
                    showError(data.field + 'Error', data.message);
                    document.getElementById(data.field)?.classList.add('error');
                }
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'error'))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> <span id="saveBtnText">' + (isEditMode ? 'Update' : 'Save') + '</span>';
        });
    }

    // ============================================
    // Edit User
    // ============================================
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-user-btn');
        if (editBtn) {
            closeAllActionMenus();
            let roleIds = [];
            try {
                roleIds = JSON.parse(editBtn.dataset.roleIds || '[]');
            } catch (err) {
                roleIds = [];
            }
            openModal('edit', {
                id:       editBtn.dataset.id,
                name:     editBtn.dataset.name,
                username: editBtn.dataset.username,
                status:   editBtn.dataset.status,
                roleIds:  roleIds
            });
        }
    });

    // ============================================
    // Toggle Status
    // ============================================
    document.addEventListener('click', function (e) {
        const toggleBtn = e.target.closest('.toggle-status-btn');
        if (toggleBtn) {
            closeAllActionMenus();
            const userId    = toggleBtn.dataset.id;
            const curStatus = toggleBtn.dataset.status;
            const newStatus = curStatus === 'Active' ? 'Inactive' : 'Active';

            const fd = new FormData();
            fd.append('action', 'toggle_status');
            fd.append('user_id', userId);
            fd.append('status', newStatus);

            fetch('model/save-user.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Status updated.', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast(data.message || 'Failed to update status.', 'error');
                }
            })
            .catch(() => showToast('Network error.', 'error'));
        }
    });

    // ============================================
    // Delete User
    // ============================================
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.delete-user-btn');
        if (deleteBtn) {
            closeAllActionMenus();
            currentDeleteId = deleteBtn.dataset.id;
            deleteUserName.textContent = deleteBtn.dataset.name;
            confirmDeleteModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });

    cancelDelete.addEventListener('click', function () {
        confirmDeleteModal.classList.remove('active');
        document.body.style.overflow = '';
        currentDeleteId = null;
    });

    confirmDeleteModal.addEventListener('click', function (e) {
        if (e.target === confirmDeleteModal) {
            confirmDeleteModal.classList.remove('active');
            document.body.style.overflow = '';
            currentDeleteId = null;
        }
    });

    confirmDeleteBtn.addEventListener('click', function () {
        if (!currentDeleteId) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('user_id', currentDeleteId);

        fetch('model/save-user.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            confirmDeleteModal.classList.remove('active');
            document.body.style.overflow = '';
            if (data.success) {
                showToast(data.message || 'User deleted.', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to delete user.', 'error');
            }
        })
        .catch(() => showToast('Network error.', 'error'))
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-trash"></i> Delete';
            currentDeleteId = null;
        });
    });

    // ============================================
    // Action Dropdown Menus
    // ============================================
    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.action-trigger');
        if (trigger) {
            e.stopPropagation();
            
            const container = trigger.closest('.action-dropdown') || trigger.parentElement;
            let menu = container.querySelector('.action-menu');
            
            if (!menu && trigger.dataset.menuId) {
                menu = document.getElementById(trigger.dataset.menuId);
            }
            
            if (!menu) return;
            
            const isOpen = menu.classList.contains('open');
            closeAllActionMenus();
            
            if (!isOpen) {
                menu.classList.add('open');
                
                if (!menu.id) {
                    menu.id = 'userMenu_' + Math.random().toString(36).substr(2, 9);
                    trigger.dataset.menuId = menu.id;
                }
                if (!menu.dataset.originalParentSet) {
                    menu.originalParent = container;
                    menu.dataset.originalParentSet = 'true';
                }
                
                document.body.appendChild(menu);
                
                menu.style.position = 'fixed';
                menu.style.zIndex = '9999';
                
                const rect = trigger.getBoundingClientRect();
                menu.style.left = 'auto';
                menu.style.right = (window.innerWidth - rect.right) + 'px';
                
                if (rect.bottom + (menu.offsetHeight || 150) > window.innerHeight) {
                    menu.style.top    = 'auto';
                    menu.style.bottom = (window.innerHeight - rect.top + 5) + 'px';
                } else {
                    menu.style.top    = (rect.bottom + 5) + 'px';
                    menu.style.bottom = 'auto';
                }
            }
            return;
        }
        if (!e.target.closest('.action-menu')) closeAllActionMenus();
    });

    function closeAllActionMenus() {
        document.querySelectorAll('.action-menu.open').forEach(m => {
            m.classList.remove('open');
            m.style.top    = '';
            m.style.bottom = '';
            m.style.left   = '';
            m.style.right  = '';
            m.style.position = '';
            m.style.zIndex = '';
            
            if (m.originalParent && m.parentElement === document.body) {
                m.originalParent.appendChild(m);
            }
        });
    }

    // ============================================
    // Escape key closes modals
    // ============================================
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (userModal.classList.contains('active'))          closeModal();
            if (confirmDeleteModal.classList.contains('active')) {
                confirmDeleteModal.classList.remove('active');
                document.body.style.overflow = '';
                currentDeleteId = null;
            }
            closeAllActionMenus();
        }
    });

    // ============================================
    // Form Helpers
    // ============================================
    function showError(id, msg) {
        const el = document.getElementById(id);
        if (el) el.textContent = msg;
    }

    function clearFormErrors() {
        ['fullNameError','usernameError','passwordError','confirmPasswordError','rolesError','statusError']
            .forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = '';
            });
        userForm.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
    }

    // ============================================
    // Role Badge Click — explicit toggle handler
    // Prevents double-toggle from label default behavior
    // ============================================
    document.addEventListener('click', function (e) {
        const item = e.target.closest('.role-checkbox-item');
        if (item && userModal.classList.contains('active')) {
            e.preventDefault();
            const cb = item.querySelector('.role-checkbox');
            if (cb) cb.checked = !cb.checked;
        }
    });

    // Clear error on input
    ['fullName','username','password','confirmPassword','status'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function () {
                this.classList.remove('error');
                const errEl = document.getElementById(id + 'Error');
                if (errEl) errEl.textContent = '';
            });
        }
    });

    // ============================================
    // Toast Notification
    // ============================================
    function showToast(message, type = 'success') {
        const iconMap = {
            success: 'fas fa-check-circle',
            error:   'fas fa-exclamation-circle',
            info:    'fas fa-info-circle'
        };
        toast.className = 'toast ' + type;
        toast.querySelector('.toast-icon').className = 'toast-icon ' + (iconMap[type] || iconMap.success);
        toastMessage.textContent = message;
        toast.classList.add('show');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
    }

});
