<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';

// Enforce: must have view permission to access this page
requirePermission('perm_roles_view');

// Page title
$pageTitle = 'Roles & Permissions';

// ============================================
// Fetch Roles from Database (include permissions)
// ============================================
$roles = [];
$rolesResult = $conn->query("SELECT id, name, description, color, text_color, permissions FROM roles ORDER BY name ASC");
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/roles.css">
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="dashboard-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <?php if (hasPermission('perm_roles_create')): ?>
                <button class="btn btn-primary" id="createRoleBtn">
                    <i class="fas fa-plus"></i> Create Role
                </button>
                <?php endif; ?>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch"><i class="fas fa-times"></i></button>
                </div>
                <button class="btn btn-icon" id="refreshBtn"><i class="fas fa-sync-alt"></i></button>
            </div>

            <!-- Roles Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rolesTableBody">
                        <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="3" style="text-align:center;padding:40px;color:var(--text-secondary);">
                                    <i class="fas fa-user-shield" style="font-size:40px;display:block;margin-bottom:12px;color:#d1d5db;"></i>
                                    No roles found. Create your first role.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                            <tr data-role-id="<?php echo $role['id']; ?>">
                                <td>
                                    <span class="role-name"
                                          style="background-color:<?php echo htmlspecialchars($role['color']); ?>;color:<?php echo htmlspecialchars($role['text_color']); ?>;">
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($role['description'] ?? '—'); ?></td>
                                <td class="text-right">
                                    <button class="btn-action role-action-btn"
                                            data-id="<?php echo $role['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                            data-description="<?php echo htmlspecialchars($role['description'] ?? ''); ?>"
                                            data-color="<?php echo htmlspecialchars($role['color']); ?>"
                                            data-text-color="<?php echo htmlspecialchars($role['text_color']); ?>">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <div class="role-action-menu" id="roleMenu_<?php echo $role['id']; ?>">
                                        <?php if (hasPermission('perm_roles_edit')): ?>
                                        <button class="role-action-menu-item edit-role-btn"
                                                data-id="<?php echo $role['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($role['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($role['description'] ?? ''); ?>"
                                                data-color="<?php echo htmlspecialchars($role['color']); ?>"
                                                data-text-color="<?php echo htmlspecialchars($role['text_color']); ?>"
                                                data-permissions="<?php echo htmlspecialchars($role['permissions'] ?? '{}'); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('perm_roles_delete')): ?>
                                        <div class="role-action-menu-divider"></div>
                                        <button class="role-action-menu-item delete-role-btn danger"
                                                data-id="<?php echo $role['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($role['name']); ?>">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="padding:12px 0;font-size:13px;color:var(--text-secondary);">
                TOTAL: <strong><?php echo count($roles); ?></strong>
            </div>
        </div>
    </main>

    <!-- ============================================
         Add / Edit Role Modal
         ============================================ -->
    <div class="role-modal-overlay" id="createRoleModal">
        <div class="role-modal">

            <div class="role-modal-header">
                <h2 class="role-modal-title" id="roleModalTitle">Add New Role</h2>
                <button class="role-modal-close" id="closeRoleModal" title="Close">&#x2715;</button>
            </div>

            <div class="role-modal-body">
                <input type="hidden" id="editRoleId" value="">

                <!-- Name & Color Card -->
                <div class="role-info-card">
                    <div class="role-info-fields">
                        <input type="text" class="role-input" id="roleName" placeholder="Name">
                        <input type="text" class="role-input" id="roleDetails" placeholder="Description">
                    </div>
                    <div class="role-color-row">
                        <span class="role-color-label">Badge color</span>
                        <div class="role-color-picker">
                            <span class="color-dot selected" data-color="#fef3c7" data-text="#92400e" style="background:#fef3c7;border:1px solid #d1d5db;"></span>
                            <span class="color-dot" data-color="#fee2e2" data-text="#991b1b" style="background:#fee2e2;"></span>
                            <span class="color-dot" data-color="#ede9fe" data-text="#6d28d9" style="background:#ede9fe;"></span>
                            <span class="color-dot" data-color="#dbeafe" data-text="#1e40af" style="background:#dbeafe;"></span>
                            <span class="color-dot" data-color="#d1fae5" data-text="#065f46" style="background:#d1fae5;"></span>
                            <span class="color-dot" data-color="#fce7f3" data-text="#9d174d" style="background:#fce7f3;"></span>
                            <span class="color-dot" data-color="#e0f2fe" data-text="#075985" style="background:#e0f2fe;"></span>
                            <span class="color-dot" data-color="#fef9c3" data-text="#854d0e" style="background:#fef9c3;"></span>
                            <span class="color-dot" data-color="#f3f4f6" data-text="#374151" style="background:#f3f4f6;border:1px solid #d1d5db;"></span>
                            <span class="color-dot" data-color="#e5e7eb" data-text="#374151" style="background:#e5e7eb;border:1px solid #d1d5db;"></span>
                        </div>
                    </div>
                </div>

                <!-- Permissions Section -->
                <div class="role-permissions-section">
                    <p class="role-permissions-title">Select which permissions to activate for access control</p>
                    <div class="role-permissions-scroll">

                        <!-- ── User Management ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">User Management</div>

                            <!-- Office Management (Users) -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Office Management</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_office_create">Create</div>
                                            <div class="perm-add-option" data-perm="perm_office_view">View</div>
                                            <div class="perm-add-option" data-perm="perm_office_edit">Edit</div>
                                            <div class="perm-add-option" data-perm="perm_office_delete">Delete</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_office_create">
                                        <input type="checkbox" name="perm_office_create" class="perm-cb" checked hidden>
                                        Create <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_office_view">
                                        <input type="checkbox" name="perm_office_view" class="perm-cb" checked hidden>
                                        View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_office_edit">
                                        <input type="checkbox" name="perm_office_edit" class="perm-cb" checked hidden>
                                        Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_office_delete">
                                        <input type="checkbox" name="perm_office_delete" class="perm-cb" checked hidden>
                                        Delete <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>

                            <!-- Roles -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Roles</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_roles_create">Create</div>
                                            <div class="perm-add-option" data-perm="perm_roles_view">View</div>
                                            <div class="perm-add-option" data-perm="perm_roles_edit">Edit</div>
                                            <div class="perm-add-option" data-perm="perm_roles_delete">Delete</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_roles_create">
                                        <input type="checkbox" name="perm_roles_create" class="perm-cb" checked hidden>
                                        Create <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_roles_view">
                                        <input type="checkbox" name="perm_roles_view" class="perm-cb" checked hidden>
                                        View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_roles_edit">
                                        <input type="checkbox" name="perm_roles_edit" class="perm-cb" checked hidden>
                                        Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_roles_delete">
                                        <input type="checkbox" name="perm_roles_delete" class="perm-cb" checked hidden>
                                        Delete <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ── Resident Records ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Resident Records</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_resident_create">Create Resident</div>
                                        <div class="perm-add-option" data-perm="perm_resident_print_list">Print Masterlist</div>
                                        <div class="perm-add-option" data-perm="perm_resident_status">Change Status</div>
                                        <div class="perm-add-option" data-perm="perm_resident_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_resident_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_resident_print_id">Print ID</div>
                                        <div class="perm-add-option" data-perm="perm_resident_print_profile">Print Profile</div>
                                        <div class="perm-add-option" data-perm="perm_resident_archive">Archive</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_resident_create">
                                    <input type="checkbox" name="perm_resident_create" class="perm-cb" checked hidden>
                                    Create Resident <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_print_list">
                                    <input type="checkbox" name="perm_resident_print_list" class="perm-cb" checked hidden>
                                    Print Masterlist <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_status">
                                    <input type="checkbox" name="perm_resident_status" class="perm-cb" checked hidden>
                                    Change Status <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_view">
                                    <input type="checkbox" name="perm_resident_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_edit">
                                    <input type="checkbox" name="perm_resident_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_print_id">
                                    <input type="checkbox" name="perm_resident_print_id" class="perm-cb" checked hidden>
                                    Print ID <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_print_profile">
                                    <input type="checkbox" name="perm_resident_print_profile" class="perm-cb" checked hidden>
                                    Print Profile <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_resident_archive">
                                    <input type="checkbox" name="perm_resident_archive" class="perm-cb" checked hidden>
                                    Archive <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Community Household ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Community Household</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_household_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_household_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_household_delete">Delete</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_household_view">
                                    <input type="checkbox" name="perm_household_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_household_edit">
                                    <input type="checkbox" name="perm_household_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_household_delete">
                                    <input type="checkbox" name="perm_household_delete" class="perm-cb" checked hidden>
                                    Delete <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Certificate Issuance ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Certificate Issuance</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_cert_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_cert_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_cert_generate">Generate Certificate</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_cert_view">
                                    <input type="checkbox" name="perm_cert_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_cert_edit">
                                    <input type="checkbox" name="perm_cert_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_cert_generate">
                                    <input type="checkbox" name="perm_cert_generate" class="perm-cb" checked hidden>
                                    Generate Certificate <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Service Request ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Service Request</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_req_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_req_print">Print</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_req_view">
                                    <input type="checkbox" name="perm_req_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_req_print">
                                    <input type="checkbox" name="perm_req_print" class="perm-cb" checked hidden>
                                    Print <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Blotter Record ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Blotter Record</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_blotter_create">Create Record</div>
                                        <div class="perm-add-option" data-perm="perm_blotter_print">Print Report</div>
                                        <div class="perm-add-option" data-perm="perm_blotter_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_blotter_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_blotter_status">Status</div>
                                        <div class="perm-add-option" data-perm="perm_blotter_archive">Archive</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_blotter_create">
                                    <input type="checkbox" name="perm_blotter_create" class="perm-cb" checked hidden>
                                    Create Record <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_blotter_print">
                                    <input type="checkbox" name="perm_blotter_print" class="perm-cb" checked hidden>
                                    Print Report <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_blotter_view">
                                    <input type="checkbox" name="perm_blotter_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_blotter_edit">
                                    <input type="checkbox" name="perm_blotter_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_blotter_status">
                                    <input type="checkbox" name="perm_blotter_status" class="perm-cb" checked hidden>
                                    Status <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_blotter_archive">
                                    <input type="checkbox" name="perm_blotter_archive" class="perm-cb" checked hidden>
                                    Archive <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Barangay Officials ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Barangay Officials</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_officials_create">Create Brgy Officials</div>
                                        <div class="perm-add-option" data-perm="perm_officials_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_officials_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_officials_status">Change Status</div>
                                        <div class="perm-add-option" data-perm="perm_officials_print">Print</div>
                                        <div class="perm-add-option" data-perm="perm_officials_archive">Archive</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_officials_create">
                                    <input type="checkbox" name="perm_officials_create" class="perm-cb" checked hidden>
                                    Create Brgy Officials <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_officials_view">
                                    <input type="checkbox" name="perm_officials_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_officials_edit">
                                    <input type="checkbox" name="perm_officials_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_officials_status">
                                    <input type="checkbox" name="perm_officials_status" class="perm-cb" checked hidden>
                                    Change Status <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_officials_print">
                                    <input type="checkbox" name="perm_officials_print" class="perm-cb" checked hidden>
                                    Print <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_officials_archive">
                                    <input type="checkbox" name="perm_officials_archive" class="perm-cb" checked hidden>
                                    Archive <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Barangay Events ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Barangay Events</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_events_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_events_create">Create</div>
                                        <div class="perm-add-option" data-perm="perm_events_edit">Edit</div>
                                        <div class="perm-add-option" data-perm="perm_events_archive">Archive</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_events_view">
                                    <input type="checkbox" name="perm_events_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_events_create">
                                    <input type="checkbox" name="perm_events_create" class="perm-cb" checked hidden>
                                    Create <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_events_edit">
                                    <input type="checkbox" name="perm_events_edit" class="perm-cb" checked hidden>
                                    Edit <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_events_archive">
                                    <input type="checkbox" name="perm_events_archive" class="perm-cb" checked hidden>
                                    Archive <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>

                        <!-- ── Statistical Reports ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">
                                <span>Statistical Reports</span>
                                <div class="perm-add-wrapper">
                                    <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                    <div class="perm-add-dropdown">
                                        <div class="perm-add-option" data-perm="perm_reports_view">View</div>
                                        <div class="perm-add-option" data-perm="perm_reports_print">Print Report</div>
                                    </div>
                                </div>
                            </div>
                            <div class="perm-badges-row perm-badges-row--indented">
                                <span class="perm-badge-item" data-perm="perm_reports_view">
                                    <input type="checkbox" name="perm_reports_view" class="perm-cb" checked hidden>
                                    View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                                <span class="perm-badge-item" data-perm="perm_reports_print">
                                    <input type="checkbox" name="perm_reports_print" class="perm-cb" checked hidden>
                                    Print Report <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                </span>
                            </div>
                        </div>
                        
                        <!-- ── Settings ── -->
                        <div class="perm-group">
                            <div class="perm-group-header">Settings</div>
                            
                            <!-- Barangay Info -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Barangay Info</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_settings_brgy_info">Modify</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_settings_brgy_info">
                                        <input type="checkbox" name="perm_settings_brgy_info" class="perm-cb" checked hidden>
                                        Modify <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Activity Logs -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Activity Logs</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_settings_logs_view">View</div>
                                            <div class="perm-add-option" data-perm="perm_settings_logs_print">Print</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_settings_logs_view">
                                        <input type="checkbox" name="perm_settings_logs_view" class="perm-cb" checked hidden>
                                        View <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                    <span class="perm-badge-item" data-perm="perm_settings_logs_print">
                                        <input type="checkbox" name="perm_settings_logs_print" class="perm-cb" checked hidden>
                                        Print <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Archive -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Archive</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_settings_archive">Archive</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_settings_archive">
                                        <input type="checkbox" name="perm_settings_archive" class="perm-cb" checked hidden>
                                        Archive <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Backup -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Backup</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_settings_backup">Backup</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_settings_backup">
                                        <input type="checkbox" name="perm_settings_backup" class="perm-cb" checked hidden>
                                        Backup <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Restore -->
                            <div class="perm-subgroup">
                                <div class="perm-subgroup-header">
                                    <span>Restore</span>
                                    <div class="perm-add-wrapper">
                                        <button class="perm-add-btn" type="button" title="Add permission"><i class="fas fa-plus"></i></button>
                                        <div class="perm-add-dropdown">
                                            <div class="perm-add-option" data-perm="perm_settings_restore">Restore</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="perm-badges-row">
                                    <span class="perm-badge-item" data-perm="perm_settings_restore">
                                        <input type="checkbox" name="perm_settings_restore" class="perm-cb" checked hidden>
                                        Restore <button class="perm-badge-remove" type="button" title="Remove">×</button>
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div><!-- end role-permissions-scroll -->
                </div><!-- end role-permissions-section -->

            </div><!-- end role-modal-body -->

            <div class="role-modal-footer">
                <button class="btn btn-cancel" id="cancelRoleModal">Cancel</button>
                <button class="btn btn-primary" id="saveRoleBtn">
                    <i class="fas fa-save"></i>
                    <span id="saveRoleBtnText">Save</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirm Modal -->
    <div class="modal" id="roleDeleteModal">
        <div class="modal-content" style="max-width: 400px; text-align: center; padding: 30px; margin: 0 auto;">
            <div style="font-size: 48px; color: var(--danger-color); margin-bottom: 16px;"><i class="fas fa-trash-alt"></i></div>
            <h3 style="margin-bottom: 10px; font-size: 20px; font-weight: 600; color: var(--text-primary);">Delete Role</h3>
            <p style="color: var(--text-secondary); margin-bottom: 24px;">Are you sure you want to delete <strong id="deleteRoleName"></strong>? This cannot be undone.</p>
            <div style="display: flex; justify-content: center; gap: 12px;">
                <button class="btn btn-secondary" id="cancelRoleDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmRoleDelete"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="role-toast" id="roleToast">
        <i class="role-toast-icon fas fa-check-circle"></i>
        <span id="roleToastMessage"></span>
    </div>

    <script src="assets/js/script.js"></script>
    <script src="assets/js/roles.js"></script>
</body>
</html>
