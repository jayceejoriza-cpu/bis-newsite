<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

requirePermission('perm_reports_view');

$pageTitle = 'Statistical Reports';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate   = $_GET['to_date']   ?? date('Y-m-t');

// 1. Current Population Snapshot (Independent of Date Range)
$totalResidents = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE activity_status = 'Alive'")->fetchColumn();
$totalHouseholds = (int)$pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();

$genderData = $pdo->query("SELECT sex, COUNT(*) as cnt FROM residents WHERE activity_status = 'Alive' GROUP BY sex")->fetchAll(PDO::FETCH_KEY_PAIR);
$purokData = $pdo->query("SELECT purok, COUNT(*) as cnt FROM residents WHERE activity_status = 'Alive' AND purok IS NOT NULL AND purok != '' GROUP BY purok ORDER BY purok ASC")->fetchAll(PDO::FETCH_KEY_PAIR);
$ageGroupData = $pdo->query("SELECT age_health_group, COUNT(*) as cnt FROM residents WHERE activity_status = 'Alive' AND age_health_group IS NOT NULL AND age_health_group != '' GROUP BY age_health_group")->fetchAll(PDO::FETCH_KEY_PAIR);
$civilStatusData = $pdo->query("SELECT civil_status, COUNT(*) as cnt FROM residents WHERE activity_status = 'Alive' AND civil_status IS NOT NULL AND civil_status != '' GROUP BY civil_status")->fetchAll(PDO::FETCH_KEY_PAIR);

$ethnicityData = $pdo->query("SELECT ethnicity, COUNT(*) as cnt FROM residents WHERE activity_status = 'Alive' AND ethnicity IS NOT NULL AND ethnicity != '' GROUP BY ethnicity")->fetchAll(PDO::FETCH_KEY_PAIR);

$waterSourceData = $pdo->query("SELECT water_source_type, COUNT(*) as cnt FROM households WHERE water_source_type IS NOT NULL AND water_source_type != '' GROUP BY water_source_type")->fetchAll(PDO::FETCH_KEY_PAIR);

$toiletData = $pdo->query("SELECT toilet_facility_type, COUNT(*) as cnt FROM households WHERE toilet_facility_type IS NOT NULL AND toilet_facility_type != '' GROUP BY toilet_facility_type")->fetchAll(PDO::FETCH_KEY_PAIR);

$householdSizeData = $pdo->query("
    SELECT
        CASE 
            WHEN (member_count + 1) = 1 THEN 'Single-person (1)'
            WHEN (member_count + 1) BETWEEN 2 AND 4 THEN 'Small (2-4)'
            WHEN (member_count + 1) BETWEEN 5 AND 7 THEN 'Medium (5-7)'
            WHEN (member_count + 1) BETWEEN 8 AND 10 THEN 'Large (8-10)'
            ELSE 'Very Large (11+)'
        END AS size_category,
        COUNT(*) as cnt
    FROM (
        SELECT h.id, (SELECT COUNT(*) FROM household_members hm WHERE hm.household_id = h.id) as member_count
        FROM households h
    ) as subquery
    GROUP BY size_category
")->fetchAll(PDO::FETCH_KEY_PAIR);

$specialGroups = $pdo->query("
    SELECT
        SUM(CASE WHEN fourps_member = 'Yes' THEN 1 ELSE 0 END) AS fourps,
        SUM(CASE WHEN pwd_status = 'Yes' THEN 1 ELSE 0 END) AS pwd,
        SUM(CASE WHEN voter_status = 'Yes' THEN 1 ELSE 0 END) AS voters
    FROM residents WHERE activity_status = 'Alive'
")->fetch();

// 2. Period Statistics (Based on Date Range)
$stmt = $pdo->prepare("SELECT certificate_name, COUNT(*) as cnt FROM certificate_requests WHERE DATE(date_requested) BETWEEN ? AND ? GROUP BY certificate_name ORDER BY cnt DESC");
$stmt->execute([$fromDate, $toDate]);
$periodCerts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalCertsPeriod = array_sum($periodCerts);

$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM blotter_records WHERE DATE(date_reported) BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$fromDate, $toDate]);
$periodBlotterStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalBlotterPeriod = array_sum($periodBlotterStatus);

$stmt = $pdo->prepare("SELECT incident_type, COUNT(*) as cnt FROM blotter_records WHERE DATE(date_reported) BETWEEN ? AND ? GROUP BY incident_type ORDER BY cnt DESC");
$stmt->execute([$fromDate, $toDate]);
$periodBlotterType = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM residents WHERE DATE(created_at) BETWEEN ? AND ? AND activity_status = 'Alive'");
$stmt->execute([$fromDate, $toDate]);
$newResidentsPeriod = $stmt->fetchColumn();

// Barangay Info for print header
$infoStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
$barangayInfo = $infoStmt->fetch();
$capStmt = $pdo->query("SELECT fullname FROM barangay_officials WHERE position = 'Barangay Captain' AND status = 'Active' LIMIT 1");
$cap = $capStmt->fetch();
$captainName = $cap ? $cap['fullname'] : 'BARANGAY CAPTAIN';

function pct($val, $total) {
    return $total > 0 ? number_format(($val / $total) * 100, 1) . '%' : '0.0%';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo defined('SITE_NAME') ? SITE_NAME : 'BIS'; ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        .filter-bar-reports {
            background: var(--bg-secondary);
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .filter-bar-reports .form-group {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .filter-bar-reports input[type="date"] {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        .section-divider {
            margin: 30px 0 20px;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Official Philippine Government Print Format */
        .print-only { display: none; }

        @media print {
            @page { size: A4; margin: 0.5in; }
            body { background: white !important; color: #000 !important; font-family: "Times New Roman", Georgia, serif !important; font-size: 9pt !important; }
            .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .sidebar, .header, .no-print { display: none !important; }
            .print-only { display: block !important; }
            
            .report-section { page-break-inside: avoid; margin-bottom: 15px !important; }
            .report-table { width: 100% !important; border-collapse: collapse !important; border: 1px solid #000 !important; font-size: 8pt !important; }
            .report-table th, .report-table td { border: 1px solid #000 !important; padding: 3px 5px !important; page-break-inside: avoid; }
            .report-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }

            .print-header { text-align: center; margin-bottom: 15px !important; }
            .header-logos { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px !important; }
            .logo-placeholder { width: 60px !important; height: 60px !important; object-fit: contain; }
            .header-text p { margin: 0; line-height: 1.2 !important; }
            .office-name { font-weight: bold; font-size: 11pt !important; margin-top: 3px !important; }
            .report-title { font-weight: bold; text-decoration: underline; margin-top: 10px !important; font-size: 12pt !important; }

            .print-footer { margin-top: 20px !important; page-break-inside: avoid; }
            .signatories { display: flex; justify-content: space-between; margin-bottom: 20px !important; }
            .signatory-item { width: 40%; text-align: center; }
            .sig-line { border-bottom: 1px solid #000; margin: 30px auto 5px !important; width: 100%; }
            .sig-name { font-weight: bold; text-transform: uppercase; margin-bottom: 0; font-size: 9pt !important; }
            .sig-title { font-size: 8pt !important; margin-top: 0; }
            
            .reports-stats-grid { display: flex !important; gap: 10px !important; margin-bottom: 15px !important; }
            .report-two-col { display: flex !important; flex-direction: row !important; gap: 15px !important; align-items: flex-start !important; }
            .report-two-col > * { flex: 1 !important; min-width: 0 !important; }
            .report-stat-card { flex: 1 !important; margin-bottom: 10px !important; padding: 10px !important; border: 1px solid #000 !important; page-break-inside: avoid; box-shadow: none !important; }
            .report-table-box { margin-bottom: 10px !important; border: 1px solid #000 !important; page-break-inside: avoid; box-shadow: none !important; }
            .section-divider { border-bottom: 1px solid #000 !important; margin: 15px 0 10px !important; font-size: 10pt !important; padding-bottom: 5px !important; }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'components/header.php'; ?>
        <div class="dashboard-content">
            
            <!-- Print Header -->
            <div class="print-only print-header">
                <div class="header-logos">
                    <img src="<?php echo !empty($barangayInfo['barangay_logo']) ? htmlspecialchars($barangayInfo['barangay_logo']) : 'assets/image/brgylogo.jpg'; ?>" class="logo-placeholder" alt="Barangay Logo">
                    <div class="header-text">
                        <p>Republic of the Philippines</p>
                        <p>Province of <?php echo htmlspecialchars($barangayInfo['province_name'] ?? '[Province Name]'); ?>, City/Municipality of <?php echo htmlspecialchars($barangayInfo['town_name'] ?? '[City Name]'); ?></p>
                        <p class="office-name">OFFICE OF THE SANGGUNIANG BARANGAY OF <?php echo strtoupper(htmlspecialchars($barangayInfo['barangay_name'] ?? '[BARANGAY NAME]')); ?></p>
                    </div>
                    <img src="<?php echo !empty($barangayInfo['municipal_logo']) ? htmlspecialchars($barangayInfo['municipal_logo']) : 'assets/image/citylogo.png'; ?>" class="logo-placeholder" alt="City Logo">
                </div>
                <h2 class="report-title">DETAILED STATISTICAL REPORT</h2>
            </div>

            <div class="reports-header no-print">
                <div class="reports-header-left">
                    <h1 class="page-title">Statistical Reports</h1>
                    <p class="page-subtitle">Detailed tabular statistics and period-based reporting</p>
                </div>
                <div class="reports-header-right">
                    <?php if (hasPermission('perm_reports_print')): ?>
                    <button class="btn-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <?php endif; ?>
                </div>
            </div>


            <!-- ===================== SNAPSHOT ===================== -->
            <div class="section-divider">
                <i class="fas fa-users"></i> Current Population Snapshot
            </div>
            
            <div class="report-two-col">
                <div class="report-table-box">
                    <div class="report-table-box-title">Basic Demographics</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Residents</td>
                                <td class="text-right"><strong><?php echo number_format($totalResidents); ?></strong></td>
                                <td class="text-right">100.0%</td>
                            </tr>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">By Sex</td></tr>
                            <tr>
                                <td>Male</td>
                                <td class="text-right"><?php echo number_format($genderData['Male'] ?? 0); ?></td>
                                <td class="text-right"><?php echo pct($genderData['Male'] ?? 0, $totalResidents); ?></td>
                            </tr>
                            <tr>
                                <td>Female</td>
                                <td class="text-right"><?php echo number_format($genderData['Female'] ?? 0); ?></td>
                                <td class="text-right"><?php echo pct($genderData['Female'] ?? 0, $totalResidents); ?></td>
                            </tr>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">By Civil Status</td></tr>
                            <?php foreach($civilStatusData as $k => $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($k); ?></td>
                                    <td class="text-right"><?php echo number_format($v); ?></td>
                                    <td class="text-right"><?php echo pct($v, $totalResidents); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">By Ethnicity</td></tr>
                            <?php foreach($ethnicityData as $k => $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($k); ?></td>
                                    <td class="text-right"><?php echo number_format($v); ?></td>
                                    <td class="text-right"><?php echo pct($v, $totalResidents); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
<div class="report-table-box">
                    <div class="report-table-box-title">Age Groups</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">Age Classification</td></tr>
                            <?php foreach($ageGroupData as $k => $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($k); ?></td>
                                    <td class="text-right"><?php echo number_format($v); ?></td>
                                    <td class="text-right"><?php echo pct($v, $totalResidents); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="report-table-box">
                    <div class="report-table-box-title">Vulnerable Sectors</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">Special Groups</td></tr>
                            <tr>
                                <td>Registered Voters</td>
                                <td class="text-right"><?php echo number_format($specialGroups['voters'] ?? 0); ?></td>
                                <td class="text-right"><?php echo pct($specialGroups['voters'] ?? 0, $totalResidents); ?></td>
                            </tr>
                            <tr>
                                <td>4Ps Members</td>
                                <td class="text-right"><?php echo number_format($specialGroups['fourps'] ?? 0); ?></td>
                                <td class="text-right"><?php echo pct($specialGroups['fourps'] ?? 0, $totalResidents); ?></td>
                            </tr>
                            <tr>
                                <td>Persons with Disability (PWD)</td>
                                <td class="text-right"><?php echo number_format($specialGroups['pwd'] ?? 0); ?></td>
                                <td class="text-right"><?php echo pct($specialGroups['pwd'] ?? 0, $totalResidents); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- ===================== HOUSEHOLD STATS ===================== -->
            <div class="section-divider" style="margin-top:40px;">
                <i class="fas fa-home"></i> Household Statistics
            </div>
            
            <div class="report-two-col">
                <div class="report-table-box">
                    <div class="report-table-box-title">Household Size & Overview</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Total Households</td>
                                <td class="text-right"><strong><?php echo number_format($totalHouseholds); ?></strong></td>
                                <td class="text-right">100.0%</td>
                            </tr>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">By Family Size</td></tr>
                            <?php 
                            $orderedSizes = ['Single-person (1)', 'Small (2-4)', 'Medium (5-7)', 'Large (8-10)', 'Very Large (11+)'];
                            foreach($orderedSizes as $sizeLabel): 
                                $v = $householdSizeData[$sizeLabel] ?? 0;
                                if ($v > 0):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sizeLabel); ?></td>
                                    <td class="text-right"><?php echo number_format($v); ?></td>
                                    <td class="text-right"><?php echo pct($v, $totalHouseholds); ?></td>
                                </tr>
                            <?php endif; endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-table-box">
                    <div class="report-table-box-title">Household Facilities</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Facility Type</th>
                                <th class="text-right">Count</th>
                                <th class="text-right">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">Water Source</td></tr>
                            <?php if (empty($waterSourceData)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach($waterSourceData as $k => $v): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($k); ?></td>
                                        <td class="text-right"><?php echo number_format($v); ?></td>
                                        <td class="text-right"><?php echo pct($v, $totalHouseholds); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <tr><td colspan="3" style="background:var(--bg-secondary); font-weight:600;">Toilet Facility</td></tr>
                            <?php if (empty($toiletData)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No data available</td></tr>
                            <?php else: ?>
                                <?php foreach($toiletData as $k => $v): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($k); ?></td>
                                        <td class="text-right"><?php echo number_format($v); ?></td>
                                        <td class="text-right"><?php echo pct($v, $totalHouseholds); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
             <div class="filter-bar-reports no-print">
                <form method="GET" action="reports.php" style="display:flex; gap:15px; align-items:center; width:100%;">
                    <div class="form-group">
                        <label style="font-weight:600; color:var(--text-secondary);">From:</label>
                        <input type="date" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" required>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600; color:var(--text-secondary);">To:</label>
                        <input type="date" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                </form>
            </div>
            <!-- ===================== PERIOD STATS ===================== -->
            <div class="section-divider" style="margin-top:40px;">
                <i class="fas fa-calendar-alt"></i> Period Statistics (<?php echo date('M d, Y', strtotime($fromDate)); ?> to <?php echo date('M d, Y', strtotime($toDate)); ?>)
            </div>

            <div class="reports-stats-grid">
                <div class="report-stat-card">
                    <div class="report-stat-icon blue"><i class="fas fa-user-plus"></i></div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($newResidentsPeriod); ?></div>
                        <div class="report-stat-label">New Registrations</div>
                    </div>
                </div>
                <div class="report-stat-card">
                    <div class="report-stat-icon purple"><i class="fas fa-certificate"></i></div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalCertsPeriod); ?></div>
                        <div class="report-stat-label">Certificates Issued</div>
                    </div>
                </div>
                <div class="report-stat-card">
                    <div class="report-stat-icon orange"><i class="fas fa-file-alt"></i></div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalBlotterPeriod); ?></div>
                        <div class="report-stat-label">Blotter Cases Logged</div>
                    </div>
                </div>
            </div>

            <div class="report-two-col" style="margin-top: 24px;">
                <div class="report-table-box">
                    <div class="report-table-box-title">Certificates Requested</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Certificate Type</th>
                                <th class="text-right">Issued</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($periodCerts)): ?>
                                <tr><td colspan="2"><div class="report-empty"><i class="fas fa-inbox"></i><p>No certificates issued in this period</p></div></td></tr>
                            <?php else: ?>
                                <?php foreach($periodCerts as $k => $v): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($k); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($v); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div>
                    <div class="report-table-box" style="margin-bottom: 24px;">
                        <div class="report-table-box-title">Blotter Records by Status</div>
                        <table class="report-table">
                            <thead><tr><th>Status</th><th class="text-right">Cases</th></tr></thead>
                            <tbody>
                                <?php if (empty($periodBlotterStatus)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No cases in this period</td></tr>
                                <?php else: ?>
                                    <?php foreach($periodBlotterStatus as $k => $v): ?>
                                        <tr><td><?php echo htmlspecialchars($k); ?></td><td class="text-right"><strong><?php echo number_format($v); ?></strong></td></tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="report-table-box">
                        <div class="report-table-box-title">Blotter Records by Incident Type</div>
                        <table class="report-table">
                            <thead><tr><th>Incident Type</th><th class="text-right">Cases</th></tr></thead>
                            <tbody>
                                <?php if (empty($periodBlotterType)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No cases in this period</td></tr>
                                <?php else: ?>
                                    <?php foreach($periodBlotterType as $k => $v): ?>
                                        <tr><td><?php echo htmlspecialchars($k); ?></td><td class="text-right"><strong><?php echo number_format($v); ?></strong></td></tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Print Footer -->
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
        </div>
    </main>
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>
