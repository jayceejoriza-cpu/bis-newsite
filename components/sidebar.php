<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$settings_pages = ['activity-logs.php', 'archive.php', 'backup.php', 'barangay-info.php', 'restore.php'];
$is_settings_active = in_array($current_page, $settings_pages);

$user_mgmt_pages = ['official-user.php', 'roles.php'];
$is_user_mgmt_active = in_array($current_page, $user_mgmt_pages);

// Load permissions if not already loaded
if (!function_exists('hasPermission')) {
    require_once __DIR__ . '/../permissions.php';
}

// Fetch Barangay Info for Sidebar
$sidebar_brgy_name = 'BIS';
$sidebar_brgy_logo = 'assets/images/logo.png';

if (isset($conn)) {
    $s_stmt = $conn->prepare("SELECT barangay_name, barangay_logo FROM barangay_info WHERE id = 1 LIMIT 1");
    if ($s_stmt) {
        $s_stmt->execute();
        $s_result = $s_stmt->get_result();
        if ($s_result->num_rows > 0) {
            $s_row = $s_result->fetch_assoc();
            if (!empty($s_row['barangay_name'])) $sidebar_brgy_name = $s_row['barangay_name'];
            if (!empty($s_row['barangay_logo']))  $sidebar_brgy_logo = $s_row['barangay_logo'];
        }
        $s_stmt->close();
    }
}

// Adjust logo path if in subdirectory
if (!file_exists($sidebar_brgy_logo) && file_exists('../' . $sidebar_brgy_logo)) {
    $sidebar_brgy_logo = '../' . $sidebar_brgy_logo;
}

// Determine which user-mgmt sub-items are visible
$can_view_users = hasPermission('perm_office_view');
$can_view_roles = hasPermission('perm_roles_view');
$show_user_mgmt = $can_view_users || $can_view_roles;
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar-brand" style="display:flex;align-items:center;overflow:hidden;">
            <img src="<?php echo htmlspecialchars($sidebar_brgy_logo); ?>" alt="Logo"
                 style="width:30px;height:30px;object-fit:contain;">
            <h2 class="sidebar-title" style="font-size:15px;margin:0;white-space:nowrap;">
                <?php echo htmlspecialchars($sidebar_brgy_name); ?>
            </h2>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">

            <!-- Dashboard (always visible) -->
            <li class="nav-item <?php echo ($current_page === 'index.php' || $current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>


            <!-- ── Resident Records ── -->
            <?php if (hasPermission('perm_resident_view')): ?>
            <li class="nav-item <?php echo ($current_page === 'residents.php' || $current_page === 'create-resident.php' || $current_page === 'resident_profile.php') ? 'active' : ''; ?>">
                <a href="residents.php" class="nav-link">
                    <i class="fas fa-address-book"></i>
                    <span>Resident Records</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- ── Community Households ── -->
            <?php if (hasPermission('perm_household_view')): ?>
            <li class="nav-item <?php echo $current_page === 'households.php' ? 'active' : ''; ?>">
                <a href="households.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Community Households</span>
                </a>
            </li>
            <?php endif; ?>

            <!-- ── Certificate Issuance (no specific perm yet — show to all) ── -->
            <li class="nav-item <?php echo ($current_page === 'certificates.php' || $current_page === 'certificate-barangayclearance.php' || $current_page === 'certificate-brgybusinessclearance.php' 
            || $current_page === 'certificate-businesspermit.php' || $current_page === 'certificate-fishingclearance.php' || $current_page === 'certificate-ft-jobseeker-assistance.php' || $current_page === 'certificate-gmrc.php' 
            || $current_page === 'certificate-indigency.php' || $current_page === 'certificate-lowincome.php' || $current_page === 'certificate-oathofundertaking.php'  || $current_page === 'certificate-RBC.php' 
            || $current_page === 'certificate-soloparent.php' || $current_page === 'certificate-vesseldocking.php') ? 'active' : ''; ?>">
                <a href="certificates.php" class="nav-link">
                    <i class="fas fa-certificate"></i>
                    <span>Certificate Issuance</span>
                </a>
            </li>

            <!-- ── Service Requests ── -->
            <li class="nav-item <?php echo $current_page === 'requests.php' ? 'active' : ''; ?>">
                <a href="requests.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Service Requests</span>
                </a>
            </li>

            <!-- ── Blotter Records ── -->
            <li class="nav-item <?php echo $current_page === 'blotter.php' ? 'active' : ''; ?>">
                <a href="blotter.php" class="nav-link">
                    <i class="fas fa-book"></i>
                    <span>Blotter Records</span>
                </a>
            </li>

            <!-- ── Barangay Officials ── -->
            <?php if (hasPermission('perm_officials_view')): ?>
            <li class="nav-item <?php echo $current_page === 'officials.php' ? 'active' : ''; ?>">
                <a href="officials.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Barangay Officials</span>
                </a>
            </li>
            <?php endif; ?>

            

            <!-- ── Settings ── -->
            <li class="nav-item has-submenu <?php echo $is_settings_active ? 'open active' : ''; ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="submenu-item <?php echo $current_page === 'barangay-info.php' ? 'active' : ''; ?>">
                        <a href="barangay-info.php" class="submenu-link">
                            <i class="fas fa-info-circle"></i>
                            <span>Barangay Info</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page === 'activity-logs.php' ? 'active' : ''; ?>">
                        <a href="activity-logs.php" class="submenu-link">
                            <i class="fas fa-history"></i>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page === 'archive.php' ? 'active' : ''; ?>">
                        <a href="archive.php" class="submenu-link">
                            <i class="fas fa-archive"></i>
                            <span>Archive</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page === 'backup.php' ? 'active' : ''; ?>">
                        <a href="backup.php" class="submenu-link">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page === 'restore.php' ? 'active' : ''; ?>">
                        <a href="restore.php" class="submenu-link">
                            <i class="fas fa-upload"></i>
                            <span>Restore</span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- ── User Management ── -->
            <?php if ($show_user_mgmt): ?>
            <li class="nav-section-title">User Management</li>

            <li class="nav-item has-submenu <?php echo $is_user_mgmt_active ? 'open active' : ''; ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </a>
                <ul class="submenu">
                    <?php if ($can_view_users): ?>
                    <li class="submenu-item <?php echo $current_page === 'official-user.php' ? 'active' : ''; ?>">
                        <a href="official-user.php" class="submenu-link">
                            <i class="fas fa-user-circle"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($can_view_roles): ?>
                    <li class="submenu-item <?php echo $current_page === 'roles.php' ? 'active' : ''; ?>">
                        <a href="roles.php" class="submenu-link">
                            <i class="fas fa-user-shield"></i>
                            <span>Roles</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

        </ul>
    </nav>

    <div class="sidebar-footer">
        <p class="version">v<?php echo SITE_VERSION; ?></p>
        <p class="copyright">© <?php echo SITE_YEAR; ?> Barangay System</p>
    </div>
</aside>
