<?php
// Include configuration
require_once 'config.php';

// Page title
$pageTitle = 'Residents';
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
    <link rel="stylesheet" href="css/residents.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Residents Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage resident records</p>
                </div>
                <button class="btn btn-primary" id="createResidentBtn">
                    <i class="fas fa-plus"></i>
                    Create Resident
                </button>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn" data-filter="all">All</button>
                <button class="tab-btn" data-filter="verified">Verified</button>
                <button class="tab-btn" data-filter="voters">Voters</button>
                <button class="tab-btn active" data-filter="active">Active</button>
            </div>
            
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="filterBtn">
                    <i class="fas fa-filter"></i>
                </button>
                
                <button class="btn btn-icon" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
                
                <div class="view-toggle">
                    <button class="view-btn active" data-view="list">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" data-view="grid">
                        <i class="fas fa-th"></i>
                    </button>
                </div>
            </div>
            
            <!-- Residents Table -->
            <div class="table-container">
                <table class="data-table residents-table" id="residentsTable">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Resident ID</th>
                            <th>Verification Status</th>
                            <th>Voter Status</th>
                            <th>Date of Birth</th>
                            <th>Sex</th>
                            <th>Activity Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="residentsTableBody">
                        <!-- Sample data rows -->
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-blue">LE</span>
                                    <span>Laboriosam Enim Pos Sed Ad Magnam Aliqui</span>
                                </div>
                            </td>
                            <td>BRY-DACD6-ECF4C</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td><span class="badge badge-no">No</span></td>
                            <td>11/07/2025 - 0</td>
                            <td>Male</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-pink">LS</span>
                                    <span>Ladarius Schroeder</span>
                                </div>
                            </td>
                            <td>BRY-91F51-21BEF</td>
                            <td><span class="badge badge-verified">Verified</span></td>
                            <td><span class="badge badge-no">No</span></td>
                            <td>07/31/1982 - 43</td>
                            <td>Female</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-teal">UC</span>
                                    <span>Uriah Conn</span>
                                </div>
                            </td>
                            <td>BRY-A2309-F0D48</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td><span class="badge badge-yes">Yes</span></td>
                            <td>11/29/1954 - 71</td>
                            <td>Other</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-yellow">BG</span>
                                    <span>Braeden Grimes</span>
                                </div>
                            </td>
                            <td>BRY-8F932-33D80</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td><span class="badge badge-yes">Yes</span></td>
                            <td>05/15/2003 - 22</td>
                            <td>Female</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-green">HG</span>
                                    <span>Hallie Gleason</span>
                                </div>
                            </td>
                            <td>BRY-5B196-D4DF2</td>
                            <td><span class="badge badge-verified">Verified</span></td>
                            <td><span class="badge badge-yes">Yes</span></td>
                            <td>05/05/1962 - 63</td>
                            <td>Male</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-orange">JK</span>
                                    <span>Jules Koch</span>
                                </div>
                            </td>
                            <td>BRY-CA0E1-51713</td>
                            <td><span class="badge badge-verified">Verified</span></td>
                            <td><span class="badge badge-no">No</span></td>
                            <td>10/15/2008 - 17</td>
                            <td>Male</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-lime">MS</span>
                                    <span>Maymie Stamm</span>
                                </div>
                            </td>
                            <td>BRY-7B96B-93196</td>
                            <td><span class="badge badge-verified">Verified</span></td>
                            <td><span class="badge badge-yes">Yes</span></td>
                            <td>01/10/1969 - 56</td>
                            <td>Male</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-indigo">AG</span>
                                    <span>Annie Gibson</span>
                                </div>
                            </td>
                            <td>BRY-C5B7A-171DA</td>
                            <td><span class="badge badge-rejected">Rejected</span></td>
                            <td><span class="badge badge-no">No</span></td>
                            <td>04/27/1992 - 33</td>
                            <td>Other</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-cyan">GB</span>
                                    <span>Gerardo Boyle</span>
                                </div>
                            </td>
                            <td>BRY-4BA4F-23A09</td>
                            <td><span class="badge badge-verified">Verified</span></td>
                            <td><span class="badge badge-yes">Yes</span></td>
                            <td>10/12/2009 - 16</td>
                            <td>Male</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="resident-name">
                                    <span class="avatar avatar-purple">KL</span>
                                    <span>Kyla Leuschke</span>
                                </div>
                            </td>
                            <td>BRY-2A6F9-92EE6</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td><span class="badge badge-no">No</span></td>
                            <td>02/14/2003 - 22</td>
                            <td>Other</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong>16,722</strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <span class="page-dots">...</span>
                    <button class="page-btn">335</button>
                    <button class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/table.js"></script>
    <script src="js/residents.js"></script>
</body>
</html>
