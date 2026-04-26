<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';
requirePermission('perm_blotter_view');

// Page title
$pageTitle = 'Blotter Records';

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
// Helper Functions
// ============================================

function getInitials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getAvatarColor($index) {
    $colors = ['blue', 'pink', 'teal', 'yellow', 'green', 'orange', 'lime', 'indigo', 'cyan', 'purple'];
    return 'avatar-' . $colors[$index % count($colors)];
}

function formatDateShort($date) {
    if (empty($date)) return 'N/A';
    return date('F j, Y', strtotime($date));
}

function formatFullName($firstName, $middleName, $lastName, $suffix) {
    $name = trim($firstName);
    if (!empty($middleName)) {
        $name .= ' ' . trim($middleName);
    }
    $name .= ' ' . trim($lastName);
    if (!empty($suffix)) {
        $name .= ' ' . trim($suffix);
    }
    return $name;
}

// ============================================
// Fetch Blotter Records Data
// ============================================
$blotterRecords = [];
$totalRecords = 0;

try {
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM blotter_records");
    $totalRecords = $countStmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT 
            br.id, br.record_number, br.incident_type, br.incident_date,
            br.date_reported, br.status,
            COUNT(DISTINCT CASE WHEN bc.statement = 'COMPLAINANT' OR bc.statement IS NULL OR bc.statement = '' THEN bc.id END) AS complainant_count,
            COUNT(DISTINCT brd.id) AS respondent_count,
            GROUP_CONCAT(DISTINCT CASE WHEN bc.statement = 'COMPLAINANT' OR bc.statement IS NULL OR bc.statement = '' THEN bc.name END ORDER BY bc.id SEPARATOR '|||') AS complainant_names,
            GROUP_CONCAT(DISTINCT brd.name ORDER BY brd.id SEPARATOR '|||') AS respondent_names,
            GROUP_CONCAT(DISTINCT CASE WHEN bc.statement = 'VICTIM' THEN bc.name END ORDER BY bc.id SEPARATOR '|||') AS victim_names,
            GROUP_CONCAT(DISTINCT CASE WHEN bc.statement = 'WITNESS' THEN bc.name END ORDER BY bc.id SEPARATOR '|||') AS witness_names,
            (SELECT COUNT(*) FROM blotter_history 
             WHERE blotter_id = br.id 
             AND action_type IN ('Rescheduled', 'Status & Schedule Updated') 
             AND new_value LIKE '%Scheduled for Mediation%') AS mediation_count
        FROM blotter_records br
        LEFT JOIN blotter_complainants bc ON br.id = bc.blotter_id
        LEFT JOIN blotter_respondents brd ON br.id = brd.blotter_id
        GROUP BY br.id
        ORDER BY br.date_reported DESC
    ");
    
    $stmt->execute();
    $blotterRecords = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching blotter records: " . $e->getMessage());
}

// Fetch residents for dropdown
$residents = [];
try {
    $residentStmt = $pdo->query("SELECT id, first_name, middle_name, last_name, suffix FROM residents WHERE activity_status = 'Alive' ORDER BY last_name, first_name");
    $residents = $residentStmt->fetchAll();

    // Fetch Barangay Info and Captain for Official Header/Footer
    $barangayInfo = null;
    $captainName = 'BARANGAY CAPTAIN';
    
    $infoStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $barangayInfo = $infoStmt->fetch();
    
    $capStmt = $pdo->query("SELECT fullname FROM barangay_officials WHERE position = 'Barangay Captain' AND status = 'Active' LIMIT 1");
    $cap = $capStmt->fetch();
    if ($cap) $captainName = $cap['fullname'];

} catch (PDOException $e) {
    error_log("Error fetching residents: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/blotter.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>

        /* Modern Redesign Styles */
        .modal-content.modern-modal {
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        
        .modern-modal .modal-header {
            padding: 1.5rem 1.5rem 1rem;
            border-bottom: 1px solid #f3f4f6;
        }

        .narrative-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1.25rem;
            line-height: 1.6;
            color: #1e293b;
        }

        /* Tight Party List Styling */
        .blotter-party-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border: none !important;
            background: transparent !important;
        }

        .blotter-party-avatar {
            width: 40px !important;
            height: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.875rem !important; /* 14px */
            font-weight: 800 !important;
            border-radius: 9999px !important;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease;
        }

        /* Role-Based Color Coding */
        .avatar-complainant { background-color: #dbeafe !important; color: #1d4ed8 !important; border: 1.5px solid #bfdbfe !important; }
        .avatar-victim      { background-color: #dcfce7 !important; color: #15803d !important; border: 1.5px solid #bbf7d0 !important; }
        .avatar-respondent  { background-color: #ffedd5 !important; color: #b91c1c !important; border: 1.5px solid #fecaca !important; }
        .avatar-witness     { background-color: #f3f4f6 !important; color: #4b5563 !important; border: 1.5px solid #e5e7eb !important; }

        .blotter-party-details {
            gap: 1px !important;
        }

        .blotter-party-name { font-size: 13px !important; font-weight: 600 !important; }
        .blotter-party-contact, .blotter-party-address { font-size: 11px !important; opacity: 0.7; }

        .table-container {
            overflow: visible !important;
        }

        .btn-print {
            padding: 9px 18px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--color-transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        /* Action Menu Fixes */
        .action-menu {
            display: none;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            padding: 8px;
            min-width: 200px;
            animation: fadeIn 0.2s ease;
            position: fixed;
            z-index: 10000;
        }
        .action-menu.show { display: block; }
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-action:hover {
            background-color: #f3f4f6;
            color: #111827;
        }
        .action-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            background: none;
            border: none;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
            text-align: left;
        }
        .action-menu-item:hover { background-color: #f3f4f6; }
        .action-submenu { 
            display: none; 
            position: absolute; 
            right: calc(100% + 6px); 
            left: auto !important;
            top: 0; 
            margin: 0 !important;
            background: white !important; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.12); 
            padding: 6px !important; 
            min-width: 160px; 
            z-index: 10000; 
        }
        .action-menu-item.show-submenu .action-submenu { display: block; }
        .submenu-arrow { margin-left: auto; font-size: 10px; transition: transform 0.2s; }
        .action-menu-item.show-submenu .submenu-arrow { transform: rotate(90deg); }

        /* Modern Timeline Styles */
        #case-history-timeline {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }

        #case-history-timeline::-webkit-scrollbar {
            width: 6px;
        }

        #case-history-timeline::-webkit-scrollbar-track {
            background: transparent;
        }

        #case-history-timeline::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        #case-history-timeline::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .timeline-container {
            position: relative;
            padding-left: 30px;
        }

        .timeline-container::before {
            content: '';
            position: absolute;
            left: 13px;
            top: 5px;
            bottom: 5px;
            width: 2px;
            background: #e9ecef;
        }

        .timeline-card {
            background: #fff;
            border: 1px solid #f1f5f9;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .timeline-card:hover {
            transform: translateX(5px);
        }

        .audit-val-box {
            font-size: 11px;
            padding: 8px 12px;
            border-radius: 6px;
            line-height: 1.4;
        }

        .remarks-ribbon {
            border-left: 4px solid #3b82f6;
            background: #f8fafc;
            padding: 10px 15px;
            font-style: italic;
            font-size: 12px;
            color: #475569;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Official Philippine Government Print Format */
        .print-only { display: none; }

        @media print {
            @page {
                size: A4;
                margin: 1in;
            }
            body {
                background: white !important;
                color: #000 !important;
                font-family: "Times New Roman", Georgia, serif !important;
                font-size: 12pt;
            }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .sidebar, .header, .filter-tabs, .search-filter-bar, .pagination-container, .no-print, .btn-primary, .btn-icon, .btn-action, .action-menu-container {
                display: none !important;
            }
            .print-only { display: block !important; }
            .table-container { overflow: visible !important; }
            .data-table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1px solid #000 !important;
                margin-top: 20px;
            }
            .data-table th, .data-table td {
                border: 1px solid #000 !important;
                padding: 8px !important;
                text-align: left;
                page-break-inside: avoid;
            }
            .data-table th {
                background-color: #f3f4f6 !important;
                font-weight: bold;
                -webkit-print-color-adjust: exact;
            }
            
            /* Header Styles */
            .print-header { text-align: center; margin-bottom: 30px; position: relative; }
            .header-logos { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
            .logo-placeholder { width: 80px; height: 80px; object-fit: contain; }
            .header-text p { margin: 0; line-height: 1.4; }
            .office-name { font-weight: bold; font-size: 14pt; margin-top: 5px !important; }
            .report-title { font-weight: bold; text-decoration: underline; margin-top: 25px; font-size: 16pt; }

            /* Signatory Section */
            .print-footer { margin-top: 60px; }
            .signatories { display: flex; justify-content: space-between; margin-bottom: 40px; }
            .signatory-item { width: 40%; text-align: center; }
            .sig-line { border-bottom: 1px solid #000; margin: 50px auto 5px; width: 100%; }
            .sig-name { font-weight: bold; text-transform: uppercase; margin-bottom: 0; }
            .sig-title { font-size: 10pt; margin-top: 0; }

            /* Metadata */
            .print-metadata {
                position: fixed;
                bottom: 0;
                right: 0;
                font-size: 8pt;
                color: #333;
                text-align: right;
                width: 100%;
            }
            .page-number:after { content: "Page " counter(page); }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
            <!-- Print-Only Header (hidden on screen, visible when printing) -->
            <div class="print-only print-header">
                <div class="print-header-logo">
                    <img src="assets/image/brgylogo.jpg" alt="Barangay Logo" class="print-logo">
                </div>
                <div class="print-header-info">
                    <h2 class="print-barangay-name"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Barangay Management System'; ?></h2>
                    <h3 class="print-list-title">Blotter Records Masterlist</h3>
                    <p class="print-meta">
                        Date Printed: <strong><?php echo date('F d, Y'); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Total Records: <strong id="printTotalRecords">0</strong>
                    </p>
                </div>
            </div>

            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage official barangay blotter entries, including complaints, incidents, and case statuses.</p>
                </div>
                <div class="page-header-actions">
                <?php if (hasPermission('perm_blotter_print')): ?>
                <button class="btn-print no-print" id="printMasterlistBtn">
                    <i class="fas fa-print"></i>
                    Print Report
                </button>
                <?php endif; ?>
                <?php if (hasPermission('perm_blotter_create')): ?>
                <button class="btn btn-primary" id="createRecordBtn">
                    <i class="fas fa-plus"></i>
                    Create Record
                </button>
                <?php endif; ?>
            </div>
            </div>
            
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="pending">Pending</button>
                <button class="tab-btn" data-filter="settled">Settled</button>
                <button class="tab-btn" data-filter="under-investigation">Under Investigation</button>
                <button class="tab-btn" data-filter="scheduled-for-mediation">Mediation</button>
                <button class="tab-btn" data-filter="dismissed">Dismissed</button>
                <button class="tab-btn" data-filter="endorsed-to-police">Endorsed</button>
            </div>
            
            <div class="search-filter-bar">
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
            </div>
            
            <div class="table-container">
                <table class="data-table blotter-table" id="blotterTable">
                    <thead>
                        <tr>
                            <th>RECORD #</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <th>Complainants</th>
                            <th>Respondents</th>
                            <th>Incident Type</th>
                            <th>Incident Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="blotterTableBody">
                        <?php if (empty($blotterRecords)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-file-alt" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                    <p style="color: #6b7280; font-size: 16px; margin: 0;">No blotter records found</p>
                                    <p style="color: #9ca3af; font-size: 14px; margin-top: 8px;">Blotter records will appear here</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blotterRecords as $index => $record): 
                                $recordNumber = htmlspecialchars($record['record_number']);
                                $dateReported = formatDateShort($record['date_reported']);
                                $incidentDate = formatDateShort($record['incident_date']);
                                $status = ($record['status'] === 'Resolved') ? 'Settled' : $record['status'];
                                $incidentType = htmlspecialchars($record['incident_type']);
                                $statusBadge = 'badge-' . strtolower(str_replace(' ', '-', $status));
                                $complainants = !empty($record['complainant_names']) ? explode('|||', $record['complainant_names']) : [];
                                $respondents = !empty($record['respondent_names']) ? explode('|||', $record['respondent_names']) : [];
                                $victimStr = !empty($record['victim_names']) ? str_replace('|||', ', ', $record['victim_names']) : '---';
                                $witnessStr = !empty($record['witness_names']) ? str_replace('|||', ', ', $record['witness_names']) : '---';
                                $complainantCount = $record['complainant_count'];
                                $respondentCount = $record['respondent_count'];
                            ?>
<tr class="clickable-row" 
    data-id="<?php echo $record['id']; ?>" 
    data-status="<?php echo strtolower(str_replace(' ', '-', $status)); ?>"
    data-victims="<?php echo htmlspecialchars($victimStr); ?>"
    data-witnesses="<?php echo htmlspecialchars($witnessStr); ?>">
                                <td><span class="record-number"><?php echo $recordNumber; ?></span></td>
                                <td><?php echo $dateReported; ?></td>
                                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                <td>
                                    <div class="person-group">
                                        <?php 
                                        $displayCount = min(2, count($complainants));
                                        for ($i = 0; $i < $displayCount; $i++): 
                                            $initials = getInitials($complainants[$i]);
                                            $avatarColor = getAvatarColor($i);
                                        ?>
                                            <span class="avatar-sm <?php echo $avatarColor; ?>" title="<?php echo htmlspecialchars($complainants[$i]); ?>">
                                                <?php echo $initials; ?>
                                            </span>
                                        <?php endfor; ?>
                                        <?php if ($complainantCount > 2): ?>
                                            <span class="person-count">+<?php echo ($complainantCount - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="person-group">
                                        <?php 
                                        $displayCount = min(2, count($respondents));
                                        for ($i = 0; $i < $displayCount; $i++): 
                                            $initials = getInitials($respondents[$i]);
                                            $avatarColor = getAvatarColor($i + 5);
                                        ?>
                                            <span class="avatar-sm <?php echo $avatarColor; ?>" title="<?php echo htmlspecialchars($respondents[$i]); ?>">
                                                <?php echo $initials; ?>
                                            </span>
                                        <?php endfor; ?>
                                        <?php if ($respondentCount > 2): ?>
                                            <span class="person-count">+<?php echo ($respondentCount - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $incidentType; ?></td>
                                <td><?php echo $incidentDate; ?></td>
                                <td style="text-align: center; vertical-align: middle;">
                                    <?php 
                                    $hasAnyBlotterAction = hasPermission('perm_blotter_view') || hasPermission('perm_blotter_edit') || hasPermission('perm_blotter_status') || hasPermission('perm_blotter_archive');
                                    if ($hasAnyBlotterAction): 
                                    ?>
                                    <div class="action-menu-container">
                                        <button class="btn-action" data-record-id="<?php echo $record['id']; ?>" data-bs-strategy="fixed">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="action-menu" data-record-id="<?php echo $record['id']; ?>">
                                            <?php if ($record['status'] === 'Scheduled for Mediation'): ?>
                                            <?php if ($record['mediation_count'] <= 3): ?>
                                            <a href="generate_document.php?id=<?php echo $record['id']; ?>&type=summons" target="_blank" class="action-menu-item">
                                                <i class="fas fa-file-alt text-primary"></i>
                                                <span>Print Summons</span>
                                            </a>
                                            <a href="generate_document.php?id=<?php echo $record['id']; ?>&type=notice" target="_blank" class="action-menu-item">
                                                <i class="fas fa-bullhorn text-success"></i>
                                                <span>Print Notice</span>
                                            </a>
                                            <button type="button" class="action-menu-item" onclick="issueSubpoena(<?php echo $record['id']; ?>)">
                                                <i class="fas fa-user-tie text-warning"></i>
                                                <span>Print Subpoena</span>
                                            </button>
                                            <?php endif; ?>

                                            <?php if ($record['mediation_count'] >= 3): ?>
                                            <!-- Strike Limit Reached UI -->
                                            <button type="button" class="action-menu-item text-danger fw-bold" data-action="status-endorsed">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <span>Endorse Case (3-Strike Limit)</span>
                                            </button>
                                            <?php endif; ?>

                                            <div class="action-menu-divider" style="height: 1px; background-color: #e5e7eb; margin: 8px 0;"></div>
                                            <?php endif; ?>

                                            <?php if (hasPermission('perm_blotter_view')): ?>
                                            <button class="action-menu-item" data-action="view">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('perm_blotter_edit')): ?>
                                            <button class="action-menu-item" data-action="edit">
                                                <i class="fas fa-edit"></i>
                                                <span>Edit</span>
                                            </button>
                                            <?php endif; ?>
                                            <?php if (hasPermission('perm_blotter_archive')): ?>
                                             <div class="action-menu-divider" style="
                                                height: 1px;
                                                background-color: #e5e7eb;
                                                margin: 8px 0;">
                                                </div>
                                            <button class="action-menu-item danger" data-action="delete">
                                                <i class="fas fa-trash"></i>
                                                <span>Archive Blotter</span>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">-</div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Official Print Footer -->
            <div class="print-only print-footer">
                <div class="signatories">
                    <div class="signatory-item">
                        <p>Prepared by:</p>
                        <div class="sig-line"></div>
                        <p class="sig-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?></p>
                        <p class="sig-title">Barangay Secretary / Staff</p>
                    </div>
                    <div class="signatory-item">
                        <p>Attested by:</p>
                        <div class="sig-line"></div>
                        <p class="sig-name"><?php echo htmlspecialchars($captainName); ?></p>
                        <p class="sig-title">Barangay Captain</p>
                    </div>
                </div>
                <div class="print-metadata">
                    <p>Generated on: <?php echo date('F d, Y h:i A'); ?> | <span class="page-number"></span></p>
                </div>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong><?php echo number_format($totalRecords); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Blotter Record Modal -->
<div class="modal fade" id="createRecordModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Barangay Blotter Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createRecordForm" enctype="multipart/form-data">
                    <div class="step-indicator transition-all duration-300">
                        <div class="step-item active" data-step="0">
                            <div class="step-icon"><i class="fas fa-info-circle"></i></div>
                            <div class="step-label">Step 1: Basic Info</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="1">
                            <div class="step-icon"><i class="fas fa-users"></i></div>
                            <div class="step-label">Step 2: Parties Involved</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="2">
                            <div class="step-icon"><i class="fas fa-align-left"></i></div>
                            <div class="step-label">Step 3: Narrative</div>
                        </div>
                        <div class="step-line"></div>
                        <div class="step-item" data-step="3">
                            <div class="step-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="step-label">Step 4: Witnesses & Final</div>
                        </div>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Step 1: Basic Info -->
                        <div class="tab-pane fade show active" id="step-1-basic">
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-1">
                                    <label class="form-label fw-bold">Case Status <span class="text-danger">*</span></label>
                                    <select class="form-select bg-gray-100 cursor-not-allowed" disabled title="Status defaults to Pending upon creation">
                                        <option value="Pending" selected>Pending</option>
                                        <option value="Scheduled for Mediation">Scheduled for Mediation</option>
                                        <option value="Under Investigation">Under Investigation</option>
                                        <option value="Settled">Settled</option>
                                        <option value="Dismissed">Dismissed</option>
                                        <option value="Endorsed to Police">Endorsed to Police</option>
                                    </select>
                                    <input type="hidden" name="status" value="Pending">
                                    <p class="text-[11px] text-gray-500 mt-1 italic"><i class="fas fa-info-circle mr-1"></i> Status defaults to Pending upon creation.</p>
                                </div>
                                <div class="col-span-1">
                                    <label class="form-label fw-bold">Incident Date <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" id="incidentDate" name="incident_date" required>
                                </div>
                                <div class="col-span-1">
                                    <label class="form-label fw-bold">Incident Type <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="incident_type" placeholder="e.g., Verbal Dispute" required>
                                </div>
                                <div class="col-span-1">
                                    <label class="form-label fw-bold">Incident Location <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="incident_location" rows="2" placeholder="Full address where incident occurred" required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Parties Involved -->
                        <div class="tab-pane fade" id="step-2-parties">
                            <div class="mt-4">
                                <div class="row">
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-shield"></i> Complainants</h6>
                                        <div id="complainantsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="addComplainantBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-injured"></i> Victims</h6>
                                        <div id="victimsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="addVictimBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                    <div class="col-lg-4">
                                        <h6 class="party-title mb-3"><i class="fas fa-user-shield"></i> Respondents</h6>
                                        <div id="respondentsContainer" class="party-section mb-4"></div>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-2" id="addRespondentBtn"><i class="fas fa-plus"></i> Add</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Narrative -->
                        <div class="tab-pane fade" id="step-3-narrative">
                            <div class="mt-4">
                                <label class="form-label fw-bold text-xl mb-4 d-block">
                                    <i class="fas fa-align-left text-primary me-2"></i>
                                    Incident Narrative / Details <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" name="incident_description" rows="8" style="resize: vertical; min-height: 300px; font-size: 1.1rem; line-height: 1.6;" placeholder="Provide COMPLETE detailed narrative of the incident." required></textarea>
                            </div>
                        </div>

                        <!-- Step 4: Witnesses & Final -->
                        <div class="tab-pane fade" id="step-4-final">
                            <div class="mt-4">
                                <h6 class="party-title mb-3"><i class="fas fa-eye text-success"></i> Witnesses</h6>
                                <div id="witnessesContainer" class="party-section mb-2"></div>
                                <button type="button" class="btn btn-outline-success btn-sm" id="addWitnessBtn"><i class="fas fa-plus"></i> Add Witness</button>
                            </div>

                            <div class="mt-6 border-t pt-4">
                                <h6 class="party-title mb-3"><i class="fas fa-camera text-primary"></i> Initial Incident Proof (Photos/Evidence)</h6>
                                <div class="attachment-upload-wrapper">
                                    <div id="incidentProofPreviewContainer" class="attachment-preview-container">
                                        <!-- Previews will appear here -->
                                    </div>
                                    <div id="incidentProofUploadZone" class="attachment-upload-zone">
                                        <input type="file" id="incidentProofInput" name="incident_proof[]" multiple accept="image/png, image/jpeg" class="hidden">
                                        <div class="upload-zone-content">
                                            <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-muted"></i>
                                            <p class="mb-1"><strong>Drag and drop images</strong> here or <span class="text-primary">click to browse</span></p>
                                            <p class="text-muted small">Supports JPG and PNG (Max 5MB each)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <div style="margin-left: auto; display: flex; gap: 10px;">
                    <button type="button" class="btn btn-secondary" id="modalBackBtn" style="display: none;">Back</button>
                    <button type="button" class="btn btn-primary" id="modalNextBtn">Next</button>
                    <button type="button" class="btn btn-primary" id="saveRecordBtn" style="display: none;">
                        <i class="fas fa-save"></i> Save Record
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
    
    <!-- View Blotter Record Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modern-modal">
                <div class="modal-header flex flex-col items-start">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <h5 class="modal-title text-2xl font-bold text-gray-800 m-0">Record #<span id="view_header_record_number"></span></h5>
                            <div id="view_status_badge_container"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <p class="text-[11px] text-gray-500 font-medium mt-1 flex items-center gap-1">
                        <i class="far fa-clock"></i> DATE LOGGED: <span id="view_date_created">N/A</span>
                    </p>
                </div>
                <div class="modal-body p-8">
                    <form id="viewRecordForm" class="space-y-6">
                        <input type="hidden" id="view_record_id">
                        <input type="hidden" id="view_resolution">
                        
                        <!-- Two-Column Layout (60/40) -->
                        <div class="grid grid-cols-1 lg:grid-cols-10 gap-10">
                            
                            <!-- LEFT COLUMN (60%): Basic & Incident Details -->
                            <div class="lg:col-span-6 space-y-8">
                                
                                <div class="space-y-6">
                                    <!-- Top Row: Date & Type -->
                                    <div class="grid grid-cols-2 gap-6">
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Incident Date</label>
                                            <input type="text" class="text-sm font-semibold text-gray-700 bg-transparent border-none p-0 focus:ring-0" id="view_incident_date" readonly>
                                        </div>
                                        <div>
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Incident Type</label>
                                            <input type="text" class="text-sm font-semibold text-blue-600 bg-transparent border-none p-0 focus:ring-0" id="view_incident_type" readonly>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Incident Location</label>
                                        <input type="text" class="text-sm font-medium text-gray-600 bg-transparent border-none p-0 w-full focus:ring-0" id="view_incident_location" readonly>
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Incident Narrative</label>
                                        <div class="narrative-box" id="view_incident_description_text">
                                            <!-- Text populated by JS -->
                                        </div>
                                        <textarea id="view_incident_description" class="hidden"></textarea>
                                    </div>

                                    <!-- Evidence -->
                                    <div class="grid grid-cols-2 gap-4">
                                        <div id="view_incident_proof_wrapper">
                                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Evidence / Photos</label>
                                            <div id="view_incident_proof_container" class="grid grid-cols-3 gap-2"></div>
                                        </div>
                                        <div id="view_settlement_proof_wrapper" style="display:none;">
                                            <label class="text-[10px] font-bold text-green-600 uppercase tracking-widest block mb-2">Settlement Proof</label>
                                            <div id="view_settlement_proof_container" class="grid grid-cols-3 gap-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RIGHT COLUMN (40%): Parties Involved -->
                            <div class="lg:col-span-4 border-l pl-10">
                                <h6 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2">
                                    <i class="fas fa-users text-blue-500"></i> Parties Involved
                                </h6>
                                <div class="space-y-8">
                                    <!-- Complainant -->
                                    <div>
                                        <h6 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-user text-blue-500 w-4 h-4"></i>
                                            Complainant
                                        </h6>
                                        <ul id="viewComplainantsContainer" class="space-y-1 border-b pb-3 list-none p-0"></ul>
                                    </div>
                                    <!-- Victims -->
                                    <div>
                                        <h6 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-user-injured text-red-500 w-4 h-4"></i>
                                            Victims
                                        </h6>
                                        <ul id="viewVictimsContainer" class="space-y-1 border-b pb-3 list-none p-0"></ul>
                                    </div>
                                    <!-- Respondents -->
                                    <div>
                                        <h6 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-user-shield text-orange-500 w-4 h-4"></i>
                                            Respondents
                                        </h6>
                                        <ul id="viewRespondentsContainer" class="space-y-1 border-b pb-3 list-none p-0"></ul>
                                    </div>
                                    <!-- Witnesses -->
                                    <div>
                                        <h6 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                            <i class="fas fa-eye text-green-500 w-4 h-4"></i>
                                            Witnesses
                                        </h6>
                                        <ul id="viewWitnessesContainer" class="space-y-1 list-none p-0"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FULL WIDTH: Case History Timeline -->
                        <div class="mt-12 border-t pt-10">
                            <h5 class="text-xs font-bold text-gray-400 uppercase tracking-[0.2em] mb-8">Case History & Audit Trail</h5>
                            <div id="case-history-timeline" class="relative pl-10">
                                <!-- Timeline items injected here -->
                            </div>
                            <div id="viewActionsContainer" class="hidden"></div>
                            <div id="view_referral_notice" class="bg-blue-50 border border-blue-100 p-4 rounded-lg text-sm text-blue-700 mt-4 hidden">
                                <i class="fas fa-info-circle mr-2"></i> Note: This case is officially tagged for CFA / Legal Escalation.
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer flex justify-between items-center bg-gray-50 border-t px-8 py-4">
                    <div class="flex gap-3">
                        <?php if (hasPermission('perm_blotter_print')): ?>
                        <button type="button" id="viewPrintBtn" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg flex items-center gap-2 transition-all shadow-sm" data-action="print">
                            <i class="fas fa-print"></i> PRINT RECORD
                        </button>
                        <?php endif; ?>
                        <?php if (hasPermission('perm_blotter_print')): ?>
                        <button type="button" id="btnPrintCFA" class="px-6 py-2 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded-lg flex items-center gap-2 transition-all shadow-sm" style="display: none;" data-action="print-cfa">
                            <i class="fas fa-file-export"></i> 
                            PRINT CFA / ENDORSEMENT
                        </button>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-lg transition-all" data-bs-dismiss="modal">
                        Close Details
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Resident Modal -->
    <div id="searchResidentModal" class="search-resident-modal">
        <div class="search-resident-modal-content">
            <div class="search-resident-modal-header">
                <h4><i class="fas fa-search"></i> Search Resident</h4>
                <button type="button" class="btn-close-search-modal" onclick="closeSearchResidentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-resident-modal-body">
                <p class="search-subtitle">Add from Resident List</p>
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="residentSearchInput" class="search-input" placeholder="Search Full Name, Barangay ID">
                </div>
                <div class="residents-list" id="residentsListContainer">
                    <div class="loading-residents">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading residents...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'model/edit_blotter.php'; ?>
    
    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  width: 90%; max-width: 500px; margin: 10% auto; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="archiveModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Archive Blotter Record</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure you want to archive this record? This action will move it to the archives.</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm.
                </div>
                
                <form id="archiveForm">
                    <input type="hidden" id="archiveRecordId" name="id">
                    
                    <div style="margin-bottom: 1.25rem;">
                        <label for="archiveReason" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-comment-alt" style="margin-right: 5px;"></i> Reason for Archiving <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="archiveReason" name="reason" rows="2" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box; font-family: inherit;" placeholder="Please state the reason..." required></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="archivePassword" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-key" style="margin-right: 5px;"></i> Your Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="archivePassword" name="password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box;" placeholder="Enter your password" required>
                            <button type="button" id="toggleArchivePassword" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="cancelArchive" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #6b7280; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" id="confirmArchiveBtn" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #ef4444; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash"></i> Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        blotter_create: <?php echo hasPermission('perm_blotter_create') ? 'true' : 'false'; ?>,
        blotter_view:   <?php echo hasPermission('perm_blotter_view')   ? 'true' : 'false'; ?>,
        blotter_edit:   <?php echo hasPermission('perm_blotter_edit')   ? 'true' : 'false'; ?>,
        blotter_status: <?php echo hasPermission('perm_blotter_status') ? 'true' : 'false'; ?>,
        blotter_print:  <?php echo hasPermission('perm_blotter_print')  ? 'true' : 'false'; ?>,
        blotter_archive:<?php echo hasPermission('perm_blotter_archive')? 'true' : 'false'; ?>
    };
    </script>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
    /**
     * Triggers a prompt for Witness Name and opens the Subpoena generator
     */
    function issueSubpoena(caseId) {
        const url = `generate_document.php?id=${caseId}&type=subpoena`;
        window.open(url, '_blank');
    }
    </script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/blotter.js"></script>
    <script src="assets/js/edit-blotter.js"></script>
    <script>
    // Print data from PHP - fixes PHP-in-JS syntax issue
    window.blotterPrintData = {
        sessionName: '<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?>',
        captainName: '<?php echo htmlspecialchars($captainName); ?>',
        brgyInfo: {
            province_name: '<?php echo htmlspecialchars($barangayInfo["province_name"] ?? "Province"); ?>',
            town_name: '<?php echo htmlspecialchars($barangayInfo["town_name"] ?? "Municipality"); ?>',
            barangay_name: '<?php echo htmlspecialchars($barangayInfo["barangay_name"] ?? "Barangay"); ?>',
            barangay_logo: '<?php echo htmlspecialchars($barangayInfo["barangay_logo"] ?? "assets/image/brgylogo.jpg"); ?>',
            municipal_logo: '<?php echo htmlspecialchars($barangayInfo["municipal_logo"] ?? "assets/image/citylogo.png"); ?>'
        }
    };
    </script>
    <script>
    /**
     * Handle Print action from View Modal using dynamic iframe generation
     * following the Official Philippine Government Print Format.
     */
    document.addEventListener('click', async function(e) {
        const printBtn = e.target.closest('button[data-action="print"]');
        if (printBtn && printBtn.closest('#viewRecordModal')) {
            const recordIdEl = document.getElementById('view_record_id');
            const recordId = recordIdEl ? recordIdEl.value : null;
            if (!recordId) {
                console.error('Print failed: No record ID found');
                alert('Cannot print: Record not loaded properly. Please refresh.');
                return;
            }

            console.log('Printing record:', recordId);

            // Get data from modal fields with fallbacks
            const recordNo = document.getElementById('view_header_record_number')?.textContent || `BL-${recordId.padStart(4, '0')}`;
            const status = document.getElementById('view_status_badge_container')?.textContent.trim() || 'PENDING';
            const incidentDate = document.getElementById('view_incident_date')?.value || 'N/A';
            const incidentType = document.getElementById('view_incident_type')?.value || 'N/A';
            const location = document.getElementById('view_incident_location')?.value || 'N/A';
            const description = document.getElementById('view_incident_description')?.value || 'N/A';

            /**
             * NEW: Fetch Resolution/Remarks from the history trail (blotter_history)
             * instead of the static resolution field in the main record.
             */
            const historyTrail = document.getElementById('case-history-timeline');
            const latestHistoryRemark = historyTrail?.querySelector('.remarks-ribbon');
            
            const resolution = latestHistoryRemark 
                ? latestHistoryRemark.textContent.trim().replace(/^"|"$/g, '') 
                : (document.getElementById('view_resolution')?.value || 'Case Pending');
            
            const getNamesFromContainer = (containerId) => {
                return Array.from(document.querySelectorAll(`#${containerId} .blotter-party-name`))
                    .map(el => el.textContent.trim())
                    .filter(Boolean)
                    .join(', ') || '---';
            };

            const complainants = getNamesFromContainer('viewComplainantsContainer');
            const victims = getNamesFromContainer('viewVictimsContainer');
            const respondents = getNamesFromContainer('viewRespondentsContainer');
            const witnesses = getNamesFromContainer('viewWitnessesContainer');

            console.log('Print data:', {recordId, recordNo, status, complainants: complainants.slice(0,50)+'...', respondents: respondents.slice(0,50)+'...'});

            // Create hidden iframe for printing
            let printFrame = document.getElementById('blotterRecordPrintFrame');
            if (printFrame) printFrame.remove();
            printFrame = document.createElement('iframe');
            printFrame.id = 'blotterRecordPrintFrame';
            printFrame.style.cssText = 'position:fixed;bottom:0;right:0;width:0;height:0;border:none;z-index:99999;';
            document.body.appendChild(printFrame);

            const doc = printFrame.contentWindow.document;
            doc.open();
            doc.write(`
                <!DOCTYPE html>
                <html>
                <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
                    <title>Blotter Record - ${recordNo}</title>
                    <style>
                        @page { size: A4; margin: 1in; }
                        body { background: white !important; color: #000 !important; font-family: "Times New Roman", Georgia, serif !important; font-size: 12pt; line-height: 1.5; margin: 0; padding: 0; }
                        .data-table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #000 !important; margin-top: 20px; }
                        .data-table th, .data-table td { border: 1px solid #000 !important; padding: 8px !important; text-align: left; page-break-inside: avoid; vertical-align: top; }
                        .data-table th { background-color: #f3f4f6 !important; font-weight: bold; -webkit-print-color-adjust: exact; width: 25%; }
                        .print-header { text-align: center; margin-bottom: 30px; }
                        .header-logos { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
                        .logo-placeholder { width: 80px; height: 80px; object-fit: contain; }
                        .header-text p { margin: 0; line-height: 1.4; }
                        .office-name { font-weight: bold; font-size: 14pt; margin-top: 5px !important; }
                        .report-title { font-weight: bold; text-decoration: underline; margin-top: 25px; font-size: 16pt; text-align: center; }
                        .print-footer { margin-top: 60px; }
                        .signatories { display: flex; justify-content: space-between; margin-bottom: 40px; }
                        .signatory-item { width: 40%; text-align: center; }
                        .sig-line { border-bottom: 1px solid #000; margin: 50px auto 5px; width: 100%; }
                        .sig-name { font-weight: bold; text-transform: uppercase; margin-bottom: 0; }
                        .sig-title { font-size: 10pt; margin-top: 0; }
                        .print-metadata { position: fixed; bottom: 0; right: 0; font-size: 8pt; color: #333; text-align: right; width: 100%; }
                        .page-number:after { content: "Page " counter(page); }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <div class="header-logos">
                            <img src="${window.blotterPrintData.brgyInfo.barangay_logo}" class="logo-placeholder" onerror="this.style.display='none'">
                            <div class="header-text">
                                <p>Republic of the Philippines</p>
                                <p>Province of ${window.blotterPrintData.brgyInfo.province_name}</p>
                                <p>Municipality of ${window.blotterPrintData.brgyInfo.town_name}</p>
                                <p class="office-name">OFFICE OF THE SANGGUNIANG BARANGAY OF ${window.blotterPrintData.brgyInfo.barangay_name.toUpperCase()}</p>
                            </div>
                            <img src="${window.blotterPrintData.brgyInfo.municipal_logo}" class="logo-placeholder" onerror="this.style.display='none'">
                        </div>
                        <h2 class="report-title">OFFICIAL BLOTTER RECORD</h2>
                    </div>
                    <table class="data-table">
                        <tr><th>Record Number</th><td><strong>${recordNo}</strong></td></tr>
                        <tr><th>Incident Type</th><td>${incidentType}</td></tr>
                        <tr><th>Incident Date</th><td>${incidentDate}</td></tr>
                        <tr><th>Location</th><td>${location}</td></tr>
                        <tr><th>Complainant(s)</th><td>${complainants}</td></tr>
                        <tr><th>Victim(s)</th><td>${victims}</td></tr>
                        <tr><th>Respondent(s)</th><td>${respondents}</td></tr>
                        <tr><th>Witness(es)</th><td>${witnesses}</td></tr>
                        <tr><th>Status</th><td>${status}</td></tr>
                        <tr><th>Narrative</th><td>${description}</td></tr>
                        <tr><th>Resolution</th><td>${resolution}</td></tr>
                    </table>
                    <div class="print-footer">
                        <div class="signatories">
                            <div class="signatory-item">
                                <p>Prepared by:</p>
                                <div class="sig-line"></div>
                                <p class="sig-name">${window.blotterPrintData.sessionName}</p>
                                <p class="sig-title">Duty Officer / Staff</p>
                            </div>
                            <div class="signatory-item">
                                <p>Attested by:</p>
                                <div class="sig-line"></div>
                                <p class="sig-name">${window.blotterPrintData.captainName}</p>
                                <p class="sig-title">Barangay Captain</p>
                            </div>
                        </div>
                    </div>
                    <div class="print-metadata"><p>Printed: ${new Date().toLocaleString()}</p></div>
                </body>
                </html>
            `);
            doc.close();
            
            setTimeout(() => {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();
                console.log('Print dialog opened');
            }, 250);
        }

        // Handle Print CFA action
        const printCFABtn = e.target.closest('button[data-action="print-cfa"]');
        if (printCFABtn && printCFABtn.closest('#viewRecordModal')) {
            const recordIdEl = document.getElementById('view_record_id');
            const recordId = recordIdEl ? recordIdEl.value : null;
            
            if (recordId) {
                window.location.href = `print_cfa.php?id=${recordId}`;
            } else {
                console.error('Print CFA failed: No record ID found');
                alert('Cannot print: Record not loaded properly.');
            }
        }
    });
    </script>
</body>
</html>
