<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';
requirePermission('perm_req_view');

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
    if (!empty($_GET['from_date'])) {
        $whereConditions[] = 'DATE(cr.date_requested) >= ?';
        $params[] = $_GET['from_date'];
    }
    if (!empty($_GET['to_date'])) {
        $whereConditions[] = 'DATE(cr.date_requested) <= ?';
        $params[] = $_GET['to_date'];
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
    <style>
        .print-only { display: none !important; }
    </style>
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
                <div class="page-header-actions">
                    <?php if (hasPermission('perm_req_print')): ?>
                    <button class="btn btn-outline-secondary" id="printMasterlistBtn" title="Print Masterlist">
                        <i class="fas fa-print"></i>
                        Print Masterlist
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Print-Only Header (hidden on screen, visible when printing) -->
            <div class="print-only print-header">
                <div class="print-header-logo">
                    <img src="assets/image/brgylogo.jpg" alt="Barangay Logo" class="print-logo">
                </div>
                <div class="print-header-info">
                    <h2 class="print-barangay-name"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Barangay Information System'; ?></h2>
                    <h3 class="print-list-title">Service Requests Masterlist</h3>
                    <p class="print-meta">
                        Date Printed: <strong><?php echo date('F d, Y'); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Total Records: <strong id="printTotalRecords"><?php echo number_format($totalRequests ?? 0); ?></strong>
                    </p>
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
                            <label for="filterFromDate">From Request Date</label>
                            <input type="date" id="filterFromDate" class="filter-select">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterToDate">To Request Date</label>
                            <input type="date" id="filterToDate" class="filter-select">
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
                            foreach ($requestsData as $row) { 
                                $requestYear = $row['date_requested'] ? date('Y', strtotime($row['date_requested'])) : '';
                                ?>
                                <tr data-year="<?= $requestYear ?>">
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

            <!-- Print-Only Footer (hidden on screen, visible when printing) -->
            <div class="print-only print-footer" style="margin-top: 60px; width: 100%;">
                <div style="display: flex; justify-content: space-between; padding: 0 50px; width: 100%;">
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Prepared by:</p>
                        <p style="margin: 0; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?></p>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Certified Correct:</p>
                        <p style="margin: 0; font-size: 14px;">Barangay Captain</p>
                    </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const printBtn = document.getElementById('printMasterlistBtn');
        if (printBtn) {
            printBtn.addEventListener('click', async () => {
                let brgyInfo = {
                    province_name: 'Province',
                    town_name: 'Municipality',
                    barangay_name: 'Barangay',
                    barangay_logo: '',
                    official_emblem: ''
                };
                
                try {
                    const response = await fetch('model/get_barangay_info.php');
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success && data.data) {
                            brgyInfo = data.data;
                        }
                    }
                } catch (error) {
                    console.error('Error fetching barangay info:', error);
                }
                
                const brgyLogoHtml = brgyInfo.barangay_logo 
                    ? `<img src="${brgyInfo.barangay_logo}" class="logo-img" alt="Barangay Logo">`
                    : `<div class="logo-placeholder-box"></div>`;
                    
                const govLogoHtml = brgyInfo.official_emblem
                    ? `<img src="${brgyInfo.official_emblem}" class="logo-img" alt="Official Emblem">`
                    : `<div class="logo-placeholder-box"></div>`;

                let printFrame = document.getElementById('requestsPrintFrame');
                if (!printFrame) {
                    printFrame = document.createElement('iframe');
                    printFrame.id = 'requestsPrintFrame';
                    printFrame.style.position = 'fixed';
                    printFrame.style.bottom = '0';
                    printFrame.style.right = '0';
                    printFrame.style.width = '0';
                    printFrame.style.height = '0';
                    printFrame.style.border = 'none';
                    document.body.appendChild(printFrame);
                }

                const doc = printFrame.contentWindow.document;
                doc.open();

                const tableHeaderHtml = `
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">No.</th>
                            <th>Resident ID</th>
                            <th>Resident Name</th>
                            <th>Certificate</th>
                            <th>Purpose</th>
                            <th>Date Request</th>
                        </tr>
                    </thead>
                `;

                let rowsHtml = '';
                let rowsToPrint = [];
                
                if (typeof requestsTable !== 'undefined' && requestsTable.filteredRows) {
                    rowsToPrint = requestsTable.filteredRows;
                } else {
                    rowsToPrint = Array.from(document.querySelectorAll('#requestsTableBody tr:not([style*="display: none"])'));
                }
                
                rowsToPrint.forEach((row, index) => {
                    if (row.cells.length < 5) return;
                    const no = index + 1;
                    const resId = row.cells[0]?.textContent.trim() || '';
                    const name = row.cells[1]?.textContent.trim() || '';
                    const cert = row.cells[2]?.textContent.trim() || '';
                    const purpose = row.cells[3]?.textContent.trim() || '';
                    const dateReq = row.cells[4]?.textContent.trim() || '';

                    rowsHtml += `
                        <tr style="display: table-row;">
                            <td style="text-align: center;">${no}</td>
                            <td>${resId}</td>
                            <td>${name}</td>
                            <td>${cert}</td>
                            <td>${purpose}</td>
                            <td>${dateReq}</td>
                        </tr>
                    `;
                });

                const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).map(s => s.outerHTML).join('\n');
                const printFooter = document.querySelector('.print-footer') ? document.querySelector('.print-footer').cloneNode(true) : null;

                let finalTitle = "Service Requests Masterlist";
                const printHeader = document.querySelector('.print-header');
                if (printHeader) {
                    const countBadge = printHeader.querySelector('#printTotalRecords');
                    if (countBadge) countBadge.textContent = rowsToPrint.length;
                    
                    const printTitle = printHeader.querySelector('.print-list-title');
                    if (printTitle) {
                        const activeFilters = [];
                        const filterMappings = {
                            'filterCertificate': 'Certificate',
                            'filterPurpose': 'Purpose',
                            'filterFromDate': 'From',
                            'filterToDate': 'To'
                        };
                        for (const [id, label] of Object.entries(filterMappings)) {
                            const el = document.getElementById(id);
                            if (el && el.value) {
                                activeFilters.push(`${label}: ${el.value}`);
                            }
                        }
                        const searchInput = document.getElementById('searchInput');
                        if (searchInput && searchInput.value.trim()) {
                            activeFilters.push(`Search: "${searchInput.value.trim()}"`);
                        }
                        if (activeFilters.length > 0) {
                            finalTitle += " - " + activeFilters.join(', ');
                        }
                    }
                }

                doc.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Service Requests Masterlist</title>
                        ${styles}
                        <style>
                            body { background: white !important; color: black !important; padding: 20px !important; }
                            .main-content, .dashboard-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                            .print-only { display: flex !important; }
                            .data-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
                            .data-table th, .data-table td { border: 1px solid #333 !important; padding: 6px !important; font-size: 9px !important; text-align: left; }
                            .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                            .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                            .header-center { flex: 1; }
                            .header-center p { margin: 2px 0; font-size: 14px; }
                            .header-center .brgy-name { font-weight: bold; font-size: 16px; margin-top: 5px; }
                            .logo-img { width: 80px; height: 80px; object-fit: contain; }
                            .logo-placeholder-box { width: 80px; height: 80px; }
                            @page { size: A4; margin: 15mm; }
                        </style>
                    </head>
                    <body>
                        <div class="dashboard-content">
                            <div class="cert-header">
                                ${brgyLogoHtml}
                                <div class="header-center">
                                    <p>Republic of the Philippines</p>
                                    <p>Province of ${brgyInfo.province_name || 'Province'}</p>
                                    <p>Municipality of ${brgyInfo.town_name || 'Municipality'}</p>
                                    <p class="brgy-name">${(brgyInfo.barangay_name || 'Barangay').toUpperCase()}</p>
                                </div>
                                ${govLogoHtml}
                            </div>
                            <div style="text-align: center; margin: 15px 0;">
                                <h3 style="margin: 0; text-transform: uppercase;">${finalTitle}</h3>
                                <p style="margin: 5px 0 0 0; font-size: 12px;">Total Records: ${rowsToPrint.length}</p>
                            </div>
                            <table class="data-table">
                                ${tableHeaderHtml}
                                <tbody>${rowsHtml}</tbody>
                            </table>
                            ${printFooter ? printFooter.outerHTML : ''}
                        </div>
                    </body>
                    </html>
                `);
                doc.close();

                setTimeout(() => {
                    fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();
                }, 500);
            });
        }
    });
    </script>
</body>
</html>
</body>
</html>
