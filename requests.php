<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Page title
$pageTitle = 'Requests';

// ============================================
// Database Connection
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Build filter conditions
    $whereConditions = ['1=1'];
    $params = [];
    
    if (!empty($_GET['certificate'])) {
        $whereConditions[] = 'cr.certificate_name LIKE ?';
        $params[] = '%' . $_GET['certificate'] . '%';
    }
    if (!empty($_GET['purpose'])) {
        $whereConditions[] = 'cr.purpose LIKE ?';
        $params[] = '%' . $_GET['purpose'] . '%';
    }
    if (!empty($_GET['date_requested'])) {
        $whereConditions[] = 'DATE(cr.date_requested) = ?';
        $params[] = $_GET['date_requested'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get filtered total requests count
    $countSql = "SELECT COUNT(*) FROM certificate_requests cr LEFT JOIN residents r ON cr.resident_id = r.id WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRequests = $countStmt->fetchColumn();
    
    // Get filtered requests data
    $dataSql = "
        SELECT 
            cr.id,
            r.resident_id,
            r.id AS r_id,
            CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name) AS resident_name,
            cr.certificate_name,
            cr.purpose,
            cr.date_requested
        FROM certificate_requests cr
        LEFT JOIN residents r ON cr.resident_id = r.id
        WHERE $whereClause
        ORDER BY cr.date_requested DESC
    ";
    $dataStmt = $pdo->prepare($dataSql);
    $dataStmt->execute($params);
    $requestsData = $dataStmt->fetchAll();
} catch (PDOException $e) {
    $requestsError = true;
    $totalRequests = 0;
    error_log('Requests load error: ' . $e->getMessage());
    $requestsData = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/requests.css">
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
        
        <!-- Requests Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage requests records</p>
                </div>
            </div>
            
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar" style="position: relative;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="filterBtn" title="Filter" style="position: relative;">
                    <i class="fas fa-filter"></i>
                    <span class="filter-notification" id="filterNotification" style="display: none; position: absolute; top: -5px; right: -5px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px;">
                        <span class="filter-count" id="filterCount">0</span>
                    </span>
                </button>
                
                <button class="btn btn-icon" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <!-- Advanced Filter Panel -->
            <div class="filter-panel" id="filterPanel" style="display: none;">
                <div class="filter-panel-header">
                    <h3><i class="fas fa-filter"></i> Select Filters</h3>
                </div>
                <div class="filter-panel-body">
                    <div class="filter-grid-single">
                        <div class="filter-item">
                            <label for="filterCertificate">Certificate</label>
                            <select id="filterCertificate" class="filter-select">
                                <option value="">All</option>
                                <option value="Certificate of Indigency">Certificate of Indigency</option>
                                <option value="Certificate of Residency">Certificate of Residency</option>
                                <option value="Certificate of Low Income">Certificate of Low Income</option>
                                <option value="Certificate of Solo Parent">Certificate of Solo Parent</option>
                                <option value="Registration of Birth Certificate">Registration of Birth Certificate</option>
                                <option value="Barangay Clearance">Barangay Clearance</option>
                                <option value="Barangay Business Clearance">Barangay Business Clearance</option>
                                <option value="Business Permit">Business Permit</option>
                                <option value="Barangay Fishing Clearance">Barangay Fishing Clearance</option>
                                <option value="Certificate of Job Seeker Assistance">Certificate of Job Seeker Assistance</option>
                                <option value="Certificate of Good Moral Character">Certificate of Good Moral Character</option>
                                <option value="Certificate of Oath of Undertaking">Certificate of Oath of Undertaking</option>
                                <option value="Certificate for Vessel Docking">Certificate for Vessel Docking</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterPurpose">Purpose</label>
                            <input type="text" id="filterPurpose" class="filter-select" placeholder="Enter Purpose">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterDateRequest">Date Request</label>
                            <input type="date" id="filterDateRequest" class="filter-select">
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
            
            <!-- Requests Table -->
            <div class="table-container">
                <table class="data-table requests-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Resident ID</th>
                            <th>Resident Name</th>
                            <th>Certificate</th>
                            <th>Purpose</th>
                            <th>Date Request</th>
                        </tr>
                    </thead>
                    <tbody id="requestsTableBody">
                        <?php
                        ob_start();
                        // Data already fetched above with filters
                        $requestsError = false;
                        
                        if ($requestsError) { ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #ef4444;">
                                        <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 16px;"></i>
                                        <p style="margin: 0;">Error loading requests</p>
                                    </td>
                                </tr>
                        <?php } elseif (empty($requestsData)) { ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-file-alt" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                        <p style="color: #6b7280; font-size: 16px; margin: 0;">No requests found</p>
                                    </td>
                                </tr>
                        <?php } else { 
                            foreach ($requestsData as $row) { ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['resident_id'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($row['r_id'])): ?>
                                            <a href="resident_profile.php?id=<?= urlencode($row['r_id']) ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                                                <?= htmlspecialchars($row['resident_name'] ?? 'N/A') ?>
                                            </a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($row['resident_name'] ?? 'N/A') ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['certificate_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($row['purpose'] ?? 'N/A') ?></td>
                                    <td><?= ($row['date_requested'] ? date('M d, Y g:i A', strtotime($row['date_requested'])) : 'N/A') ?></td>
                                </tr>
                        <?php 
                            }
                        } ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>Showing <strong>1-10</strong> of <strong><?php echo number_format($totalRequests); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/requests.js"></script>
</body>
</html>
