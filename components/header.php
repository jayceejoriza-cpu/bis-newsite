<!-- Header Component -->
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
                <i class="fas fa-user"></i>
            </div>
            
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <div class="user-name"><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User'; ?></div>
                    <div class="user-role"><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Role'; ?></div>
                </div>
                <div class="dropdown-divider"></div>
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user-circle"></i> Profile
                </a>
                <a href="settings.php" class="dropdown-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </a>
            </div>
        </div>
    </div>
</header>

<style>
    :root {
        --bg-primary: #ffffff;
        --bg-secondary: #f9fafb;
        --bg-surface: #ffffff;
        --bg-hover: #f3f4f6;
        --border-color: #e5e7eb;
        --text-primary: #111827;
        --text-secondary: #6b7280;
        --text-tertiary: #374151;
        --danger-text: #ef4444;
        --danger-bg-hover: #fef2f2;
        --header-bg: #ffffff;
        --header-text: #111827;
        --header-border: #e5e7eb;
        --sidebar-bg: #ffffff;
        --sidebar-text: #111827;
        --sidebar-border: #e5e7eb;
        --sidebar-hover: #f3f4f6;
        --sidebar-active: #e5e7eb;
    }

    body.dark-mode {
        --bg-primary: #111827;
        --bg-secondary: #111827;
        --bg-surface: #1f2937;
        --bg-hover: #374151;
        --border-color: #374151;
        --text-primary: #f3f4f6;
        --text-secondary: #9ca3af;
        --text-tertiary: #d1d5db;
        --danger-text: #ef4444;
        --danger-bg-hover: #7f1d1d;
        --header-bg: #1f2937;
        --header-text: #f3f4f6;
        --header-border: #374151;
        --sidebar-bg: #1f2937;
        --sidebar-text: #f3f4f6;
        --sidebar-border: #374151;
        --sidebar-hover: #374151;
        --sidebar-active: #4b5563;
    }

    /* General body styles for theme consistency */
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.2s, color 0.2s;
    }

    /* Apply theme to header */
    .header {
        background-color: var(--header-bg);
        color: var(--header-text);
        border-bottom: 1px solid var(--header-border);
    }

    /* Apply theme to sidebar */
    .sidebar {
        background-color: var(--sidebar-bg);
        color: var(--sidebar-text);
        border-right: 1px solid var(--sidebar-border);
        transition: background-color 0.2s, color 0.2s;
    }

    .sidebar a, .sidebar .nav-link {
        color: var(--sidebar-text);
    }

    .sidebar a:hover, .sidebar .nav-link:hover {
        background-color: var(--sidebar-hover);
    }

    .sidebar .active {
        background-color: var(--sidebar-active);
    }
    
    .theme-toggle,
    .mobile-menu-toggle {
        color: var(--header-text);
    }

    .dropdown-menu {
        position: absolute;
        top: 120%;
        right: 0;
        width: 200px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
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
        background-color: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
    }
    .dropdown-header .user-name {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
    }
    .dropdown-header .user-role {
        font-size: 12px;
        color: var(--text-secondary);
    }
    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        color: var(--text-tertiary);
        text-decoration: none;
        font-size: 14px;
        transition: background-color 0.2s, color 0.2s;
    }
    .dropdown-item:hover {
        background-color: var(--bg-hover);
        color: var(--text-primary);
    }
    .dropdown-item i {
        width: 20px;
        margin-right: 8px;
        text-align: center;
        color: var(--text-secondary);
    }
    .dropdown-item.text-danger {
        color: var(--danger-text);
    }
    .dropdown-item.text-danger:hover {
        background-color: var(--danger-bg-hover);
    }
    body.dark-mode .dropdown-item.text-danger:hover {
        color: var(--text-primary);
    }
    .dropdown-divider {
        height: 1px;
        background-color: var(--border-color);
        margin: 4px 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        
        // Check for saved theme preference
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                }
            }
        }

        if (themeToggle) {
            themeToggle.addEventListener('click', function() {
                body.classList.toggle('dark-mode');
                const icon = this.querySelector('i');
                
                if (body.classList.contains('dark-mode')) {
                    localStorage.setItem('theme', 'dark');
                    if (icon) {
                        icon.classList.remove('fa-sun');
                        icon.classList.add('fa-moon');
                    }
                } else {
                    localStorage.setItem('theme', 'light');
                    if (icon) {
                        icon.classList.remove('fa-moon');
                        icon.classList.add('fa-sun');
                    }
                }
            });
        }

        const userProfile = document.getElementById('userProfileDropdown');
        const dropdownMenu = userProfile.querySelector('.dropdown-menu');

        if (userProfile && dropdownMenu) {
            userProfile.addEventListener('click', function(e) {
                if (dropdownMenu.contains(e.target)) {
                    return;
                }
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        }
    });
</script>
