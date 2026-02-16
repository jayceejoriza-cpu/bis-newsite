<!-- Header Component -->
<?php
// Fetch current user's profile image if not already loaded
if (isset($_SESSION['user_id']) && !isset($header_user_avatar)) {
    $header_user_id = $_SESSION['user_id'];
    $header_stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $header_stmt->bind_param("i", $header_user_id);
    $header_stmt->execute();
    $header_result = $header_stmt->get_result();
    if ($header_result->num_rows > 0) {
        $header_user_data = $header_result->fetch_assoc();
        $header_user_avatar = $header_user_data['profile_image'];
    } else {
        $header_user_avatar = null;
    }
    $header_stmt->close();
}
?>
<header class="header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="header-right">
        <div class="datetime">
            <i class="far fa-calendar"></i>
            <span id="currentDateTime"></span>
        </div>
        
        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-sun"></i>
        </button>
        
        <div class="user-profile" id="userProfileDropdown" style="cursor: pointer; position: relative; display: flex; align-items: center;">
            <div class="user-avatar">
                <?php if (!empty($header_user_avatar) && file_exists($header_user_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($header_user_avatar); ?>?v=<?php echo time(); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <div class="user-name"><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User'; ?></div>
                    <div class="user-role"><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Role'; ?></div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="user-profile.php" class="dropdown-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <div class="dropdown-item has-submenu" id="headerSettingsSubmenu">
                    <div class="dropdown-item-content">
                        <i class="fas fa-cog"></i> Settings
                        <i class="fas fa-chevron-right submenu-arrow"></i>
                    </div>
                    <div class="header-submenu">
                        <a href="activity-logs.php" class="dropdown-item submenu-item">
                            <i class="fas fa-history"></i> Activity Logs
                        </a>
                        <a href="archive.php" class="dropdown-item submenu-item">
                            <i class="fas fa-archive"></i> Archive
                        </a>
                        <a href="backup.php" class="dropdown-item submenu-item">
                            <i class="fas fa-database"></i> Backup
                        </a>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</header>

<style>
    /* Dropdown Menu Styles */
    .dropdown-menu {
        position: absolute;
        top: 120%;
        right: 0;
        width: 200px;
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: var(--shadow-md);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        z-index: 100;
        overflow: hidden;
    }
    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .dropdown-header {
        padding: 12px 16px;
        background-color: var(--bg-primary);
        border-bottom: 1px solid var(--border-color);
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .dropdown-header .user-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
        transition: color 0.3s ease;
    }
    .dropdown-header .user-role {
        font-size: 12px;
        color: var(--text-secondary);
        transition: color 0.3s ease;
    }
    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        color: var(--text-primary);
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    .dropdown-item:hover {
        background-color: var(--bg-primary);
        color: var(--text-primary);
    }
    .dropdown-item i {
        width: 20px;
        margin-right: 8px;
        text-align: center;
        color: var(--text-secondary);
        transition: color 0.3s ease;
    }
    .dropdown-item.text-danger {
        color: #ef4444;
    }
    .dropdown-item.text-danger:hover {
        background-color: var(--bg-primary);
    }
    .dropdown-divider {
        height: 1px;
        background-color: var(--border-color);
        margin: 4px 0;
        transition: background-color 0.3s ease;
    }
    
    /* Header Submenu Styles */
    .dropdown-item.has-submenu {
        position: relative;
        padding: 0;
        cursor: pointer;
        flex-direction: column;
        align-items: stretch;
    }
    
    .dropdown-item-content {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        width: 100%;
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    .dropdown-item.has-submenu:hover .dropdown-item-content {
        background-color: var(--bg-primary);
    }
    
    .submenu-arrow {
        margin-left: auto;
        font-size: 10px;
        transition: transform 0.3s ease, color 0.3s ease;
    }
    
    .dropdown-item.has-submenu.open .submenu-arrow {
        transform: rotate(90deg);
    }
    
    .header-submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background-color: var(--bg-primary);
    }
    
    .dropdown-item.has-submenu.open .header-submenu {
        max-height: 200px;
    }
    
    .header-submenu .dropdown-item {
        padding-left: 45px;
        font-size: 13px;
    }
    
    .header-submenu .dropdown-item i {
        width: 16px;
        font-size: 13px;
    }
    
    .header-submenu .dropdown-item:hover {
        background-color: var(--bg-secondary);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // User Profile Dropdown
        const userProfile = document.getElementById('userProfileDropdown');
        const dropdownMenu = userProfile ? userProfile.querySelector('.dropdown-menu') : null;
        const settingsSubmenu = document.getElementById('headerSettingsSubmenu');
        const settingsContent = settingsSubmenu ? settingsSubmenu.querySelector('.dropdown-item-content') : null;

        if (userProfile && dropdownMenu) {
            userProfile.addEventListener('click', function(e) {
                // Don't close dropdown if clicking inside it
                if (dropdownMenu.contains(e.target)) {
                    return;
                }
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
                
                // Close submenu when dropdown closes
                if (!dropdownMenu.classList.contains('show') && settingsSubmenu) {
                    settingsSubmenu.classList.remove('open');
                }
            });

            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                    // Close submenu when clicking outside
                    if (settingsSubmenu) {
                        settingsSubmenu.classList.remove('open');
                    }
                }
            });
        }
        
        // Settings Submenu Toggle
        if (settingsContent) {
            settingsContent.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                settingsSubmenu.classList.toggle('open');
            });
        }
        
        // Prevent submenu links from closing the dropdown
        if (settingsSubmenu) {
            const submenuLinks = settingsSubmenu.querySelectorAll('.header-submenu a');
            submenuLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Allow navigation but stop propagation
                    e.stopPropagation();
                });
            });
        }
    });
</script>
