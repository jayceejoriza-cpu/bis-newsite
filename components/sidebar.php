<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Gooning City</h2>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item active">
                <a href="index.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-section-title">User Management</li>
            
            <li class="nav-item has-submenu">
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
            
            <li class="nav-item">
                <a href="roles.php" class="nav-link">
                    <i class="fas fa-user-shield"></i>
                    <span>Roles</span>
                </a>
            </li>
            
            <li class="nav-divider"></li>
            
            <li class="nav-item">
                <a href="residents.php" class="nav-link">
                    <i class="fas fa-address-book"></i>
                    <span>Resident Records</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="households.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Community Households</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="certificates.php" class="nav-link">
                    <i class="fas fa-certificate"></i>
                    <span>Certificate Issuance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="requests.php" class="nav-link">
                    <i class="fas fa-file-alt"></i>
                    <span>Service Requests</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="blotter.php" class="nav-link">
                    <i class="fas fa-chart-bar"></i>
                    <span>Blotter Records</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="incidents.php" class="nav-link">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>Incident Reports</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="officials.php" class="nav-link">
                    <i class="fas fa-user-tie"></i>
                    <span>Barangay Officials</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="reports.php" class="nav-link">
                    <i class="fas fa-file-invoice"></i>
                    <span>Reports</span>
                </a>
            </li>
            
            <?php 
            $current_page = basename($_SERVER['PHP_SELF']);
            $settings_pages = ['activity-logs.php', 'archive.php', 'backup.php', 'barangay-info.php'];
            $is_settings_active = in_array($current_page, $settings_pages);
            ?>
            <li class="nav-item has-submenu <?php echo $is_settings_active ? 'open' : ''; ?>">
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
