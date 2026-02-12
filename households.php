<?php
// Include configuration
require_once 'config.php';

// Page title
$pageTitle = 'Households';
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
    <link rel="stylesheet" href="css/households.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Households Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View all household profiles, including heads and members. <i class="fas fa-info-circle info-icon"></i></p>
                </div>
                <button class="btn btn-primary" id="createHouseholdBtn">
                    <i class="fas fa-plus"></i>
                    Create Household
                </button>
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
                
                <button class="btn btn-icon" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="single-person">Single-person</button>
                <button class="tab-btn" data-filter="small">Small</button>
                <button class="tab-btn" data-filter="medium">Medium</button>
                <button class="tab-btn" data-filter="large">Large</button>
                <button class="tab-btn" data-filter="very-large">Very Large</button>
            </div>
            
            <!-- Households Table -->
            <div class="table-container">
                <table class="data-table households-table" id="householdsTable">
                    <thead>
                        <tr>
                            <th>Household Number <i class="fas fa-sort sort-icon"></i></th>
                            <th>Head Name <i class="fas fa-sort sort-icon"></i></th>
                            <th>Household Member <i class="fas fa-sort sort-icon"></i></th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="householdsTableBody">
                        <!-- Sample data row matching the screenshot -->
                        <tr data-size="single-person">
                            <td>HH-00030</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-teal">LH</span>
                                    <span>Lacey Hagenes</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">1</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <!-- Additional sample data -->
                        <tr data-size="small">
                            <td>HH-00029</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-blue">JD</span>
                                    <span>John Doe</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">3</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="medium">
                            <td>HH-00028</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-pink">MS</span>
                                    <span>Maria Santos</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">5</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="large">
                            <td>HH-00027</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-yellow">RC</span>
                                    <span>Robert Cruz</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">7</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="very-large">
                            <td>HH-00026</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-green">AR</span>
                                    <span>Anna Reyes</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">10</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="small">
                            <td>HH-00025</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-orange">PG</span>
                                    <span>Pedro Garcia</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">4</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="medium">
                            <td>HH-00024</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-purple">LV</span>
                                    <span>Linda Villanueva</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">6</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="single-person">
                            <td>HH-00023</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-cyan">TB</span>
                                    <span>Thomas Brown</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">1</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="large">
                            <td>HH-00022</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-indigo">EM</span>
                                    <span>Elena Martinez</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">8</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
                            <td>
                                <button class="btn-action">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </td>
                        </tr>
                        <tr data-size="small">
                            <td>HH-00021</td>
                            <td>
                                <div class="head-name">
                                    <span class="avatar avatar-lime">DR</span>
                                    <span>David Rodriguez</span>
                                </div>
                            </td>
                            <td>
                                <div class="member-count">
                                    <span class="member-badge">
                                        <i class="fas fa-user"></i>
                                        <span class="count">3</span>
                                    </span>
                                    <span class="member-indicator active"></span>
                                </div>
                            </td>
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
                    <span>TOTAL: <strong id="totalCount">10</strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" id="prevPage">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn" id="nextPage">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/table.js"></script>
    <script src="js/households.js"></script>
</body>
</html>
