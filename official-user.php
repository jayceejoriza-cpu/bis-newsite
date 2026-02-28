<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';

// Enforce: must have view permission to access this page
requirePermission('perm_office_view');

// Page title
$pageTitle = 'Users';

// ============================================
// Fetch Users with their Roles
// ============================================
$users      = [];
$totalUsers = 0;

// Get total count
$countResult = $conn->query("SELECT COUNT(*) as total FROM users");
if ($countResult) {
    $totalUsers = $countResult->fetch_assoc()['total'];
}

// Fetch users with their roles via user_roles join
$usersResult = $conn->query("
    SELECT
        u.id,
        u.username,
        u.full_name,
        u.email,
        u.status,
        u.created_at,
        GROUP_CONCAT(r.id   ORDER BY r.name SEPARATOR ',') AS role_ids,
        GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ',') AS role_names,
        GROUP_CONCAT(r.color      ORDER BY r.name SEPARATOR '|') AS role_colors,
        GROUP_CONCAT(r.text_color ORDER BY r.name SEPARATOR '|') AS role_text_colors
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r       ON ur.role_id = r.id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        // Parse roles into arrays
        $row['roles'] = [];
        if (!empty($row['role_ids'])) {
            $ids        = explode(',', $row['role_ids']);
            $names      = explode(',', $row['role_names']);
            $colors     = explode('|', $row['role_colors']);
            $textColors = explode('|', $row['role_text_colors']);
            foreach ($ids as $i => $rid) {
                $row['roles'][] = [
                    'id'         => $rid,
                    'name'       => $names[$i]      ?? '',
                    'color'      => $colors[$i]     ?? '#e5e7eb',
                    'text_color' => $textColors[$i] ?? '#374151',
                ];
            }
        }
        $users[] = $row;
    }
}

// ============================================
// Fetch All Roles for Modal Checkboxes
// ============================================
$allRoles = [];
$rolesResult = $conn->query("SELECT id, name, color, text_color FROM roles ORDER BY name ASC");
if ($rolesResult) {
    while ($r = $rolesResult->fetch_assoc()) {
        $allRoles[] = $r;
    }
}

// ============================================
// Helper Functions
// ============================================
function getUserInitials($fullName) {
    $parts = explode(' ', trim($fullName));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
    }
    return strtoupper(substr($fullName, 0, 2));
}

function getUserAvatarColor($index) {
    $colors = ['blue', 'pink', 'teal', 'yellow', 'green', 'orange', 'indigo', 'purple'];
    return $colors[$index % count($colors)];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/official-user.css">
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- Users Content -->
        <div class="dashboard-content">

            <!-- Page Title -->
            <div class="page-header">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <?php if (hasPermission('perm_office_create')): ?>
                <button class="btn btn-primary" id="createUserBtn">
                    <i class="fas fa-plus"></i>
                    Create User
                </button>
                <?php endif; ?>

                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput" autocomplete="off">
                    <button class="btn-clear" id="clearSearch" title="Clear">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <button class="btn btn-icon" id="filterBtn" title="Filter">
                    <i class="fas fa-filter"></i>
                </button>

                <button class="btn btn-icon" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel" id="filterPanel" style="display:none;">
                <div class="filter-panel-header">
                    <h3><i class="fas fa-filter"></i> Filter Users</h3>
                </div>
                <div class="filter-panel-body">
                    <div class="filter-grid">
                        <div class="filter-item">
                            <label for="filterRole">Role</label>
                            <select id="filterRole" class="filter-select">
                                <option value="">All Roles</option>
                                <?php foreach ($allRoles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['name']); ?>">
                                    <?php echo htmlspecialchars($role['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-item">
                            <label for="filterStatus">Status</label>
                            <select id="filterStatus" class="filter-select">
                                <option value="">All Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="filter-panel-footer">
                    <button class="btn btn-secondary" id="clearFiltersBtn">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button class="btn btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-check"></i> Apply Now
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-container">
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>Full Name <i class="fas fa-arrows-alt-v sort-icon"></i></th>
                            <th>Username <i class="fas fa-arrows-alt-v sort-icon"></i></th>
                            <th>Roles</th>
                            <th>Status <i class="fas fa-arrows-alt-v sort-icon"></i></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>No users found</p>
                                    <span>Start by creating a new user</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $index => $user):
                                $initials    = getUserInitials($user['full_name']);
                                $avatarColor = getUserAvatarColor($index);
                                $statusClass = strtolower($user['status']) === 'active' ? 'status-active' : 'status-inactive';
                                // Collect role names for data-roles attribute (for filtering)
                                $roleNamesList = implode(',', array_column($user['roles'], 'name'));
                                $roleIdsJson   = htmlspecialchars(json_encode(array_column($user['roles'], 'id')));
                            ?>
                            <tr
                                data-roles="<?php echo htmlspecialchars(strtolower($roleNamesList)); ?>"
                                data-status="<?php echo htmlspecialchars($user['status']); ?>"
                                data-name="<?php echo htmlspecialchars(strtolower($user['full_name'])); ?>"
                                data-username="<?php echo htmlspecialchars(strtolower($user['username'])); ?>"
                            >
                                <td>
                                    <div class="user-name-cell">
                                        <span class="user-avatar-sm avatar-<?php echo $avatarColor; ?>">
                                            <?php echo htmlspecialchars($initials); ?>
                                        </span>
                                        <span class="user-full-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    </div>
                                </td>
                                <td class="username-cell"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td>
                                    <div class="role-badges">
                                        <?php if (empty($user['roles'])): ?>
                                            <span class="role-badge role-none">No Role</span>
                                        <?php else: ?>
                                            <?php foreach ($user['roles'] as $role): ?>
                                            <span class="role-badge"
                                                  style="background-color:<?php echo htmlspecialchars($role['color']); ?>;color:<?php echo htmlspecialchars($role['text_color']); ?>;">
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-dropdown" data-user-id="<?php echo $user['id']; ?>">
                                        <button class="btn-action action-trigger" title="Actions">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="action-menu">
                                            <?php if (hasPermission('perm_office_edit')): ?>
                                            <button class="action-menu-item edit-user-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-status="<?php echo htmlspecialchars($user['status']); ?>"
                                                data-role-ids="<?php echo $roleIdsJson; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('perm_office_delete')): ?>
                                            <button class="action-menu-item toggle-status-btn"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-status="<?php echo htmlspecialchars($user['status']); ?>">
                                                <i class="fas fa-<?php echo $user['status'] === 'Active' ? 'ban' : 'check-circle'; ?>"></i>
                                                <?php echo $user['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                            <div class="action-menu-divider"></div>
                                            <button class="action-menu-item delete-user-btn danger"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['full_name']); ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong id="totalCount"><?php echo number_format($totalUsers); ?></strong></span>
                </div>
            </div>

        </div><!-- end dashboard-content -->
    </main>

    <!-- ============================================
         Create / Edit User Modal
         ============================================ -->
    <div class="user-modal-overlay" id="userModal">
        <div class="user-modal">

            <div class="user-modal-header">
                <h2 class="user-modal-title" id="modalTitle">Create User</h2>
                <button class="user-modal-close" id="closeUserModal" title="Close">&#x2715;</button>
            </div>

            <div class="user-modal-body">
                <form id="userForm" novalidate>
                    <input type="hidden" id="userId" name="user_id" value="">

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="fullName">Full Name <span class="required">*</span></label>
                        <input type="text" class="form-input" id="fullName" name="full_name"
                               placeholder="Enter full name" required>
                        <span class="form-error" id="fullNameError"></span>
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label" for="username">Username <span class="required">*</span></label>
                        <input type="text" class="form-input" id="username" name="username"
                               placeholder="Enter username" required autocomplete="off">
                        <span class="form-error" id="usernameError"></span>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span class="required">*</span></label>
                        <input type="email" class="form-input" id="email" name="email"
                               placeholder="Enter email address" required>
                        <span class="form-error" id="emailError"></span>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Password <span class="required" id="passwordRequired">*</span>
                            <span class="form-hint" id="passwordHint" style="display:none;">(leave blank to keep current)</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" class="form-input" id="password" name="password"
                                   placeholder="Enter password" autocomplete="new-password">
                            <button type="button" class="toggle-password" id="togglePassword" title="Show/Hide">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <!-- Roles (checkboxes from DB) -->
                    <div class="form-group">
                        <label class="form-label">
                            Roles
                            <span class="form-hint" style="display:inline;">— click a badge to toggle</span>
                        </label>
                        <div class="roles-checkbox-list" id="rolesCheckboxList">
                            <?php foreach ($allRoles as $role): ?>
                            <label class="role-checkbox-item" title="Click to toggle">
                                <input type="checkbox"
                                       name="roles[]"
                                       value="<?php echo $role['id']; ?>"
                                       class="role-checkbox">
                                <span class="role-checkbox-badge"
                                      style="background-color:<?php echo htmlspecialchars($role['color']); ?>;color:<?php echo htmlspecialchars($role['text_color']); ?>;">
                                    <?php echo htmlspecialchars($role['name']); ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                            <?php if (empty($allRoles)): ?>
                            <span style="font-size:13px;color:var(--text-secondary);">
                                No roles available. <a href="roles.php" style="color:var(--primary-color);">Create roles first</a>.
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="form-error" id="rolesError"></span>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <label class="form-label" for="status">Status <span class="required">*</span></label>
                        <select class="form-input form-select" id="status" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                        <span class="form-error" id="statusError"></span>
                    </div>

                </form>
            </div>

            <div class="user-modal-footer">
                <button class="btn btn-cancel" id="cancelUserModal">Cancel</button>
                <button class="btn btn-primary" id="saveUserBtn">
                    <i class="fas fa-save"></i>
                    <span id="saveBtnText">Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="confirm-modal-overlay" id="confirmDeleteModal">
        <div class="confirm-modal">
            <div class="confirm-modal-icon danger">
                <i class="fas fa-trash-alt"></i>
            </div>
            <h3 class="confirm-modal-title">Delete User</h3>
            <p class="confirm-modal-message">Are you sure you want to delete <strong id="deleteUserName"></strong>? This action cannot be undone.</p>
            <div class="confirm-modal-actions">
                <button class="btn btn-cancel" id="cancelDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast">
        <i class="toast-icon fas fa-check-circle"></i>
        <span class="toast-message" id="toastMessage"></span>
    </div>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/official-user.js"></script>
</body>
</html>
