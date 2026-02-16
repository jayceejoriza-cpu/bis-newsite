<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Page title
$pageTitle = 'Roles & Permissions';
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/roles.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Roles Content -->
        <div class="dashboard-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            </div>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <button class="btn btn-primary" id="createRoleBtn">
                    <i class="fas fa-plus"></i>
                    Create Role
                </button>
                
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
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
                        <tr>
                            <td>
                                <span class="role-name">Example</span>
                            </td>
                            <td>Example Description</td>
                            <td class="text-right">
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="role-name role-admin">Administrator</span>
                            </td>
                            <td>Administrator</td>
                            <td class="text-right">
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="role-name">Test</span>
                            </td>
                            <td>Assistant</td>
                            <td class="text-right">
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="role-name">Staff</span>
                            </td>
                            <td>Staff</td>
                            <td class="text-right">
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="role-name role-kagawad">Kagawad</span>
                            </td>
                            <td>Kagawad</td>
                            <td class="text-right">
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/roles.js"></script>
</body>
</html>
