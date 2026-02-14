<?php
// Include configuration
require_once 'config.php';

// Page title
$pageTitle = 'Certificates';

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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// Fetch Certificates Data
// ============================================
$certificates = [];
$totalCertificates = 0;

try {
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM certificates");
    $totalCertificates = $countStmt->fetch()['total'];
    
    // Fetch certificates data
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            fee,
            status,
            created_at,
            updated_at
        FROM certificates
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $certificates = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching certificates: " . $e->getMessage());
    $certificates = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/certificates.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Certificates Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">Manage and create certificates with customizable fields</p>
                </div>
                <button class="btn btn-primary" id="createCertificateBtn">
                    <i class="fas fa-plus"></i>
                    Create Certificate
                </button>
            </div>
            
            <!-- Search and Actions Bar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                
                <div class="filter-tabs">
                    <button class="tab-btn active" data-filter="all">All</button>
                    <button class="tab-btn" data-filter="published">Publish</button>
                    <button class="tab-btn" data-filter="unpublished">Unpublished</button>
                </div>
            </div>
            
            <!-- Certificates Grid -->
            <div class="certificates-grid" id="certificatesGrid">
                <?php if (empty($certificates)): ?>
                    <!-- Empty state -->
                    <div class="empty-state">
                        <i class="fas fa-certificate"></i>
                        <p>No certificates found</p>
                        <p class="empty-subtitle">Start by creating a new certificate</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($certificates as $certificate): 
                        $statusClass = strtolower($certificate['status']);
                        $statusIcon = ($certificate['status'] === 'Published') ? 'fa-check-circle' : 'fa-times-circle';
                    ?>
                    <div class="certificate-card certificate-card-clickable" data-status="<?php echo htmlspecialchars($statusClass); ?>" data-id="<?php echo htmlspecialchars($certificate['id']); ?>">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo htmlspecialchars($certificate['title']); ?></h3>
                            <button class="btn-menu" data-id="<?php echo htmlspecialchars($certificate['id']); ?>">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                        
                        <div class="card-body">
                            <div class="certificate-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <span class="status-badge status-<?php echo htmlspecialchars($statusClass); ?>">
                                <i class="fas <?php echo htmlspecialchars($statusIcon); ?>"></i>
                                <?php echo htmlspecialchars($certificate['status']); ?>
                            </span>
                            <span class="fee-amount">Fee <?php echo number_format($certificate['fee'], 2); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong><?php echo number_format($totalCertificates); ?></strong></span>
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
    
    <!-- Context Menu for Certificate Actions -->
    <div class="context-menu" id="contextMenu" style="display: none;">
        <button class="context-menu-item" data-action="edit">
            <i class="fas fa-edit"></i>
            Edit
        </button>
        <button class="context-menu-item" data-action="duplicate">
            <i class="fas fa-copy"></i>
            Duplicate
        </button>
        <button class="context-menu-item" data-action="toggle-status">
            <i class="fas fa-exchange-alt"></i>
            Toggle Status
        </button>
         <div class="action-menu-divider" style="
        height: 1px;
        background-color: #e5e7eb;
        margin: 5px 0;"></div>
        <button class="context-menu-item danger" data-action="delete">
            <i class="fas fa-trash"></i>
            Delete
        </button>
    </div>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/certificates.js"></script>
</body>
</html>
