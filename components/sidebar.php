<!-- Sidebar Component -->
<?php 
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
$settings_pages = ['activity-logs.php', 'archive.php', 'backup.php', 'barangay-info.php'];
$is_settings_active = in_array($current_page, $settings_pages);

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
            if (!empty($s_row['barangay_name'])) {
                $sidebar_brgy_name = $s_row['barangay_name'];
            }
            if (!empty($s_row['barangay_logo'])) {
                $sidebar_brgy_logo = $s_row['barangay_logo'];
            }
        }
        $s_stmt->close();
    }
}

// Adjust logo path if in subdirectory
if (!file_exists($sidebar_brgy_logo) && file_exists('../' . $sidebar_brgy_logo)) {
    $sidebar_brgy_logo = '../' . $sidebar_brgy_logo;
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="sidebar-brand" style="display: flex; align-items: center; overflow: hidden;">
            <img src="<?php echo htmlspecialchars($sidebar_brgy_logo); ?>" alt="Logo" style="width: 30px; height: 30px; object-fit: contain; ">
            <h2 class="sidebar-title" style="font-size: 15px; margin: 0; white-space: nowrap;"><?php echo htmlspecialchars($sidebar_brgy_name); ?></h2>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo ($current_page == 'index.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section-title">User Management</li>
            
            <li class="nav-item has-submenu <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="submenu-item">
                        <a href="officials.php" class="submenu-link">
                            <i class="fas fa-user-tie"></i>
                            <span>Officials</span>
                        </a>
                    </li>
                </ul>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'roles.php' ? 'active' : ''; ?>">
                <a href="roles.php" class="nav-link">
                    <i class="fas fa-user-shield"></i>
                    <span>Roles</span>
                </a>
            </li>
            
            <li class="nav-divider"></li>
            
            <li class="nav-item <?php echo $current_page == 'residents.php' ? 'active' : ''; ?>">
                <a href="residents.php" class="nav-link">
                    <i class="fas fa-address-book"></i>
                    <span>Resident Records</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'households.php' ? 'active' : ''; ?>">
                <a href="households.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Community Households</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'certificates.php' ? 'active' : ''; ?>">
                <a href="certificates.php" class="nav-link">
                    <i class="fas fa-certificate"></i>
                    <span>Certificate Issuance</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'requests.php' ? 'active' : ''; ?>">
                <a href="requests.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Service Requests</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'blotter.php' ? 'active' : ''; ?>">
                <a href="blotter.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Blotter Records</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'incidents.php' ? 'active' : ''; ?>">
                <a href="incidents.php" class="nav-link">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Incident Reports</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'officials.php' ? 'active' : ''; ?>">
                <a href="officials.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Barangay Officials</span>
                </a>
            </li>
            
            <li class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-file-invoice"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <li class="nav-item has-submenu <?php echo $is_settings_active ? 'open active' : ''; ?>">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                    <i class="fas fa-chevron-right nav-arrow"></i>
                </a>
                <ul class="submenu">
                    <li class="submenu-item <?php echo $current_page == 'barangay-info.php' ? 'active' : ''; ?>">
                        <a href="barangay-info.php" class="submenu-link">
                            <i class="fas fa-info-circle"></i>
                            <span>Barangay Info</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page == 'activity-logs.php' ? 'active' : ''; ?>">
                        <a href="activity-logs.php" class="submenu-link">
                            <i class="fas fa-history"></i>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page == 'archive.php' ? 'active' : ''; ?>">
                        <a href="archive.php" class="submenu-link">
                            <i class="fas fa-archive"></i>
                            <span>Archive</span>
                        </a>
                    </li>
                    <li class="submenu-item <?php echo $current_page == 'backup.php' ? 'active' : ''; ?>">
                        <a href="backup.php" class="submenu-link">
                            <i class="fas fa-database"></i>
                            <span>Backup</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <p class="version">v<?php echo SITE_VERSION; ?></p>
        <p class="copyright">© <?php echo SITE_YEAR; ?> Barangay System</p>
    </div>
</aside>
