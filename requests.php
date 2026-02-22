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
    
    <!-- PDF.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/requests.css">
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
                
                <button class="btn btn-icon" id="filterBtn" title="Filter">
                    <i class="fas fa-filter"></i>
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
                            <label for="filterResidentID">Resident ID</label>
                            <input type="text" id="filterResidentID" class="filter-select" placeholder="Enter Resident ID">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterResidentName">Resident Name</label>
                            <input type="text" id="filterResidentName" class="filter-select" placeholder="Enter Resident Name">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterCertificate">Certificate</label>
                            <select id="filterCertificate" class="filter-select">
                                <option value="">All</option>
                                <?php
                                try {
                                    $certStmt = $pdo->query("SELECT DISTINCT title FROM certificates WHERE status = 'Published' ORDER BY title");
                                    while ($cert = $certStmt->fetch()) {
                                        echo '<option value="' . htmlspecialchars($cert['title']) . '">' . htmlspecialchars($cert['title']) . '</option>';
                                    }
                                } catch (PDOException $e) {
                                    // Silently fail if certificates table doesn't exist
                                }
                                ?>
                            </select>
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
                        
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong><?php echo number_format($totalRequests); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Certificate Request Modal -->
    <div class="modal fade" id="createRequestModal" tabindex="-1" aria-labelledby="createRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRequestModalLabel">
                        <i class="fas fa-file-certificate"></i>
                        Create Certificate Request
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form Only (No Preview Section) -->
                    <div class="request-form-container">
                            <form id="createRequestForm">
                                <!-- Resident Selection -->
                                <div class="form-group mb-3">
                                    <label class="form-label">RESIDENT FULL NAME <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="residentNameInput" placeholder="Select resident" readonly required>
                                        <input type="hidden" id="selectedResidentId" name="resident_id">
                                        <button type="button" class="btn btn-primary" id="selectResidentBtn">
                                            <i class="fas fa-user"></i>
                                            RESIDENT
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Certificate Type -->
                                <div class="form-group mb-3">
                                    <label class="form-label" for="certificateType">TYPE OF CERTIFICATION <span class="text-danger">*</span></label>
                                    <select class="form-control" id="certificateType" name="certificate_id" required>
                                        <option value="">Select certificate type</option>
                                    </select>
                                </div>
                                
                                <!-- Certificate Fee -->
                                <div class="form-group mb-3">
                                    <label class="form-label" for="certificateFeeInput">FEE <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control" id="certificateFeeInput" name="certificate_fee" value="0.00" step="0.01" min="0" readonly>
                                    </div>
                                </div>
                                
                                <!-- Dynamic Fields Container -->
                                <div id="dynamicFieldsContainer" style="display: none;">
                                    <div class="form-group mb-3">
                                        <label class="form-label">OTHER INPUTS FROM THE CERTIFICATION</label>
                                        <small class="d-block text-muted mb-2">(TYPE, NUMBER AND DROPDOWN)</small>
                                        <div id="dynamicFieldsContent" class="dynamic-fields-area">
                                            <!-- Dynamic fields will be inserted here -->
                                        </div>
                                    </div>
                                </div>
                                
                            </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="previewCertificateBtn">
                        <i class="fas fa-eye"></i>
                        Preview
                    </button>
                    <button type="button" class="btn btn-primary" id="printCertificateBtn">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resident Selection Modal -->
    <div class="modal fade" id="residentSelectionModal" tabindex="-1" aria-labelledby="residentSelectionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="residentSelectionModalLabel">
                        <i class="fas fa-users"></i>
                        Select Resident
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" class="form-control" id="residentSearchInput" placeholder="Search by name, ID, or contact number">
                        </div>
                    </div>
                    
                    <!-- Residents List -->
                    <div class="residents-list" id="residentsListContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading residents...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Certificate Preview Modal -->
    <div class="modal fade" id="certificatePreviewModal" tabindex="-1" aria-labelledby="certificatePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="certificatePreviewModalLabel">
                        <i class="fas fa-eye"></i>
                        Certificate Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="preview-container" style="background: #f5f5f5; min-height: 500px; display: flex; align-items: center; justify-content: center;">
                        <div id="certificatePreviewArea" style="max-width: 100%; overflow: auto;">
                            <div class="preview-placeholder text-center p-5">
                                <i class="fas fa-file-alt" style="font-size: 48px; color: #d1d5db;"></i>
                                <p class="mt-3 text-muted">Select a certificate type and resident to preview</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadPreviewBtn">
                        <i class="fas fa-download"></i>
                        Download
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/requests.js"></script>
</body>
</html>
