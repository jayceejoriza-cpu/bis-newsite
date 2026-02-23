/**
 * Roles & Permissions Page - JavaScript
 * - Permission badges: click × to hide, click + to add back
 * - Save permissions JSON to DB
 * - Fetch & restore permissions when editing
 */

document.addEventListener('DOMContentLoaded', function () {

    // ============================================
    // Element References
    // ============================================
    const searchInput      = document.getElementById('searchInput');
    const clearSearchBtn   = document.getElementById('clearSearch');
    const refreshBtn       = document.getElementById('refreshBtn');
    const tableBody        = document.getElementById('rolesTableBody');

    const createRoleBtn    = document.getElementById('createRoleBtn');
    const roleModal        = document.getElementById('createRoleModal');
    const closeRoleModal   = document.getElementById('closeRoleModal');
    const cancelRoleModal  = document.getElementById('cancelRoleModal');
    const saveRoleBtn      = document.getElementById('saveRoleBtn');
    const saveRoleBtnText  = document.getElementById('saveRoleBtnText');
    const roleModalTitle   = document.getElementById('roleModalTitle');
    const editRoleId       = document.getElementById('editRoleId');
    const roleNameInput    = document.getElementById('roleName');
    const roleDetailsInput = document.getElementById('roleDetails');

    const roleDeleteModal   = document.getElementById('roleDeleteModal');
    const cancelRoleDelete  = document.getElementById('cancelRoleDelete');
    const confirmRoleDelete = document.getElementById('confirmRoleDelete');
    const deleteRoleName    = document.getElementById('deleteRoleName');

    const roleToast        = document.getElementById('roleToast');
    const roleToastMessage = document.getElementById('roleToastMessage');

    let selectedColor      = '#fef3c7';
    let selectedTextColor  = '#92400e';
    let currentDeleteRoleId = null;
    let isEditMode         = false;
    let toastTimer         = null;

    // ============================================
    // Search
    // ============================================
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            clearSearchBtn.style.display = term ? 'block' : 'none';
            tableBody.querySelectorAll('tr[data-role-id]').forEach(row => {
                const name = row.querySelector('.role-name')?.textContent.toLowerCase() || '';
                const desc = row.querySelectorAll('td')[1]?.textContent.toLowerCase() || '';
                row.style.display = (name.includes(term) || desc.includes(term)) ? '' : 'none';
            });
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            this.style.display = 'none';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    }

    // ============================================
    // Refresh
    // ============================================
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function () {
            const icon = this.querySelector('i');
            icon.style.transition = 'transform 0.6s ease';
            icon.style.transform  = 'rotate(360deg)';
            setTimeout(() => { icon.style.transition = ''; icon.style.transform = ''; }, 600);
            window.location.reload();
        });
    }

    // ============================================
    // Color Picker
    // ============================================
    document.querySelectorAll('.color-dot').forEach(dot => {
        dot.addEventListener('click', function () {
            document.querySelectorAll('.color-dot').forEach(d => d.classList.remove('selected'));
            this.classList.add('selected');
            selectedColor     = this.dataset.color;
            selectedTextColor = this.dataset.text || '#374151';
        });
    });

    function selectColorDot(color) {
        let matched = false;
        document.querySelectorAll('.color-dot').forEach(dot => {
            dot.classList.remove('selected');
            if (dot.dataset.color === color) {
                dot.classList.add('selected');
                selectedColor     = dot.dataset.color;
                selectedTextColor = dot.dataset.text || '#374151';
                matched = true;
            }
        });
        if (!matched) {
            const first = document.querySelector('.color-dot');
            if (first) {
                first.classList.add('selected');
                selectedColor     = first.dataset.color;
                selectedTextColor = first.dataset.text || '#374151';
            }
        }
    }

    // ============================================
    // Permission Badges — Reset (show all, all checked)
    // ============================================
    function resetPermissions() {
        document.querySelectorAll('.perm-badge-item').forEach(badge => {
            badge.style.display = '';          // show
            badge.querySelector('.perm-cb').checked = true;
        });
        closeAllPermDropdowns();
    }

    // ============================================
    // Permission Badges — Apply from saved JSON
    // ============================================
    function applyPermissions(permissionsJson) {
        // First hide all
        document.querySelectorAll('.perm-badge-item').forEach(badge => {
            badge.style.display = 'none';
            badge.querySelector('.perm-cb').checked = false;
        });

        if (!permissionsJson) return;

        let perms = {};
        try { perms = JSON.parse(permissionsJson); } catch (e) { return; }

        Object.keys(perms).forEach(key => {
            if (perms[key]) {
                const badge = document.querySelector(`.perm-badge-item[data-perm="${key}"]`);
                if (badge) {
                    badge.style.display = '';
                    badge.querySelector('.perm-cb').checked = true;
                }
            }
        });

        closeAllPermDropdowns();
    }

    // ============================================
    // Permission Badge — Remove (× button)
    // ============================================
    document.addEventListener('click', function (e) {
        const removeBtn = e.target.closest('.perm-badge-remove');
        if (removeBtn) {
            e.preventDefault();
            e.stopPropagation();
            const badge = removeBtn.closest('.perm-badge-item');
            badge.style.display = 'none';
            badge.querySelector('.perm-cb').checked = false;
        }
    });

    // ============================================
    // Permission Add (+) Button — Toggle Dropdown
    // ============================================
    document.addEventListener('click', function (e) {
        const addBtn = e.target.closest('.perm-add-btn');
        if (addBtn) {
            e.preventDefault();
            e.stopPropagation();
            const wrapper  = addBtn.closest('.perm-add-wrapper');
            const dropdown = wrapper.querySelector('.perm-add-dropdown');
            const isOpen   = dropdown.classList.contains('open');

            closeAllPermDropdowns();

            if (!isOpen) {
                // Update which options are already active (gray them out)
                const badgesRow = wrapper.closest('.perm-subgroup, .perm-group').querySelector('.perm-badges-row');
                dropdown.querySelectorAll('.perm-add-option').forEach(opt => {
                    const permName = opt.dataset.perm;
                    const badge    = badgesRow ? badgesRow.querySelector(`[data-perm="${permName}"]`) : null;
                    const isActive = badge && badge.style.display !== 'none';
                    opt.classList.toggle('already-active', isActive);
                });
                dropdown.classList.add('open');
            }
            return;
        }

        // Close dropdowns when clicking outside
        if (!e.target.closest('.perm-add-wrapper')) {
            closeAllPermDropdowns();
        }
    });

    // ============================================
    // Permission Add — Option Click (add badge back)
    // ============================================
    document.addEventListener('click', function (e) {
        const opt = e.target.closest('.perm-add-option');
        if (opt && !opt.classList.contains('already-active')) {
            e.preventDefault();
            e.stopPropagation();
            const permName = opt.dataset.perm;
            const dropdown = opt.closest('.perm-add-dropdown');
            const wrapper  = dropdown.closest('.perm-add-wrapper');
            const badgesRow = wrapper.closest('.perm-subgroup, .perm-group').querySelector('.perm-badges-row');
            const badge    = badgesRow ? badgesRow.querySelector(`[data-perm="${permName}"]`) : null;

            if (badge) {
                badge.style.display = '';
                badge.querySelector('.perm-cb').checked = true;
            }
            closeAllPermDropdowns();
        }
    });

    function closeAllPermDropdowns() {
        document.querySelectorAll('.perm-add-dropdown.open').forEach(d => d.classList.remove('open'));
    }

    // ============================================
    // Open Modal (Create / Edit)
    // ============================================
    if (createRoleBtn) {
        createRoleBtn.addEventListener('click', () => openRoleModal('create'));
    }

    function openRoleModal(mode, data = {}) {
        isEditMode = (mode === 'edit');
        roleModalTitle.textContent  = isEditMode ? 'Edit Role' : 'Add New Role';
        saveRoleBtnText.textContent = isEditMode ? 'Update' : 'Save';

        // Reset fields
        roleNameInput.value    = '';
        roleDetailsInput.value = '';
        editRoleId.value       = '';
        roleNameInput.style.borderColor = '';
        selectColorDot('#fef3c7');

        if (isEditMode) {
            editRoleId.value       = data.id          || '';
            roleNameInput.value    = data.name        || '';
            roleDetailsInput.value = data.description || '';
            selectColorDot(data.color || '#fef3c7');
            // Restore permissions from saved JSON
            applyPermissions(data.permissions || '{}');
        } else {
            // New role: show all permissions (all checked)
            resetPermissions();
        }

        roleModal.classList.add('active');
        document.body.style.overflow = 'hidden';
        setTimeout(() => roleNameInput.focus(), 100);
    }

    function closeRoleModalFn() {
        roleModal.classList.remove('active');
        document.body.style.overflow = '';
        closeAllPermDropdowns();
    }

    if (closeRoleModal)  closeRoleModal.addEventListener('click', closeRoleModalFn);
    if (cancelRoleModal) cancelRoleModal.addEventListener('click', closeRoleModalFn);
    if (roleModal) {
        roleModal.addEventListener('click', e => { if (e.target === roleModal) closeRoleModalFn(); });
    }

    // ============================================
    // Save Role (Create / Edit) → DB
    // ============================================
    if (saveRoleBtn) {
        saveRoleBtn.addEventListener('click', function () {
            const name = roleNameInput.value.trim();
            if (!name) {
                roleNameInput.style.borderColor = '#ef4444';
                roleNameInput.focus();
                setTimeout(() => roleNameInput.style.borderColor = '', 2000);
                return;
            }

            // Collect permissions from visible (checked) badges
            const permissions = {};
            document.querySelectorAll('.perm-badge-item').forEach(badge => {
                const cb      = badge.querySelector('.perm-cb');
                const permKey = badge.dataset.perm;
                permissions[permKey] = cb.checked && badge.style.display !== 'none';
            });

            const fd = new FormData();
            fd.append('action',      isEditMode ? 'edit' : 'create');
            fd.append('name',        name);
            fd.append('description', roleDetailsInput.value.trim());
            fd.append('color',       selectedColor);
            fd.append('text_color',  selectedTextColor);
            fd.append('permissions', JSON.stringify(permissions));
            if (isEditMode) fd.append('role_id', editRoleId.value);

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            fetch('model/save-role.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    closeRoleModalFn();
                    showRoleToast(data.message || 'Role saved.', 'success');
                    setTimeout(() => window.location.reload(), 1100);
                } else {
                    showRoleToast(data.message || 'Failed to save role.', 'error');
                }
            })
            .catch(() => showRoleToast('Network error.', 'error'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save"></i> <span id="saveRoleBtnText">' + (isEditMode ? 'Update' : 'Save') + '</span>';
            });
        });
    }

    // ============================================
    // Action Menu (⋯ per row)
    // ============================================
    document.addEventListener('click', function (e) {
        const actionBtn = e.target.closest('.role-action-btn');
        if (actionBtn) {
            e.stopPropagation();
            const roleId = actionBtn.dataset.id;
            const menu   = document.getElementById('roleMenu_' + roleId);
            const isOpen = menu && menu.classList.contains('open');
            closeAllRoleMenus();
            if (menu && !isOpen) {
                menu.classList.add('open');
                const rect = menu.getBoundingClientRect();
                if (rect.bottom > window.innerHeight) {
                    menu.style.top    = 'auto';
                    menu.style.bottom = 'calc(100% + 4px)';
                }
            }
            return;
        }
        if (!e.target.closest('.role-action-menu')) closeAllRoleMenus();
    });

    function closeAllRoleMenus() {
        document.querySelectorAll('.role-action-menu.open').forEach(m => {
            m.classList.remove('open');
            m.style.top    = '';
            m.style.bottom = '';
        });
    }

    // ============================================
    // Edit Role — open modal with saved permissions
    // ============================================
    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.edit-role-btn');
        if (editBtn) {
            closeAllRoleMenus();
            openRoleModal('edit', {
                id:          editBtn.dataset.id,
                name:        editBtn.dataset.name,
                description: editBtn.dataset.description,
                color:       editBtn.dataset.color,
                permissions: editBtn.dataset.permissions || '{}'
            });
        }
    });

    // ============================================
    // Delete Role
    // ============================================
    document.addEventListener('click', function (e) {
        const deleteBtn = e.target.closest('.delete-role-btn');
        if (deleteBtn) {
            closeAllRoleMenus();
            currentDeleteRoleId = deleteBtn.dataset.id;
            deleteRoleName.textContent = deleteBtn.dataset.name;
            roleDeleteModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    });

    if (cancelRoleDelete) {
        cancelRoleDelete.addEventListener('click', function () {
            roleDeleteModal.classList.remove('active');
            document.body.style.overflow = '';
            currentDeleteRoleId = null;
        });
    }

    if (roleDeleteModal) {
        roleDeleteModal.addEventListener('click', function (e) {
            if (e.target === roleDeleteModal) {
                roleDeleteModal.classList.remove('active');
                document.body.style.overflow = '';
                currentDeleteRoleId = null;
            }
        });
    }

    if (confirmRoleDelete) {
        confirmRoleDelete.addEventListener('click', function () {
            if (!currentDeleteRoleId) return;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

            const fd = new FormData();
            fd.append('action',  'delete');
            fd.append('role_id', currentDeleteRoleId);

            fetch('model/save-role.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                roleDeleteModal.classList.remove('active');
                document.body.style.overflow = '';
                if (data.success) {
                    showRoleToast(data.message || 'Role deleted.', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showRoleToast(data.message || 'Failed to delete role.', 'error');
                }
            })
            .catch(() => showRoleToast('Network error.', 'error'))
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash"></i> Delete';
                currentDeleteRoleId = null;
            });
        });
    }

    // ============================================
    // Escape key
    // ============================================
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeRoleModalFn();
            if (roleDeleteModal) {
                roleDeleteModal.classList.remove('active');
                document.body.style.overflow = '';
                currentDeleteRoleId = null;
            }
            closeAllRoleMenus();
            closeAllPermDropdowns();
        }
    });

    // ============================================
    // Toast
    // ============================================
    function showRoleToast(message, type = 'success') {
        const iconMap = {
            success: 'fas fa-check-circle',
            error:   'fas fa-exclamation-circle'
        };
        roleToast.className = 'role-toast ' + type;
        roleToast.querySelector('.role-toast-icon').className = 'role-toast-icon ' + (iconMap[type] || iconMap.success);
        roleToastMessage.textContent = message;
        roleToast.classList.add('show');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => roleToast.classList.remove('show'), 3500);
    }

});
