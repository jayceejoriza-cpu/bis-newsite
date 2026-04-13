<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// ============================================
// Database Connection (PDO) — for Reports
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// Helper: safe JSON encode for data attributes
// ============================================
function jsonAttr($data) {
    return htmlspecialchars(json_encode(array_values($data)), ENT_QUOTES, 'UTF-8');
}

function jsonAttrKeys($data) {
    return htmlspecialchars(json_encode(array_keys($data)), ENT_QUOTES, 'UTF-8');
}

// ============================================
// Helper: bar color cycle
// ============================================
$barColors = ['blue','green','orange','purple','teal','pink','indigo','red','yellow','gray'];
function barColor($index, $colors) {
    return $colors[$index % count($colors)];
}

// ============================================
// Helper: percentage
// ============================================
function pct($part, $total) {
    if ($total == 0) return '0.0';
    return number_format(($part / $total) * 100, 1);
}

// ============================================
// SUMMARY STATS
// ============================================
$totalResidents   = 0;
$totalHouseholds  = 0;
$totalBlotter     = 0;
$totalCertReqs    = 0;
$pendingRequests  = 0;
$totalMale        = 0;
$totalFemale      = 0;

try {
    $totalResidents  = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE activity_status != 'Archived'")->fetchColumn();
    $totalHouseholds = (int)$pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();
    $totalBlotter    = (int)$pdo->query("SELECT COUNT(*) FROM blotter_records")->fetchColumn();
    $totalCertReqs   = (int)$pdo->query("SELECT COUNT(*) FROM certificate_requests")->fetchColumn();
    $pendingRequests = (int)$pdo->query("SELECT COUNT(*) FROM certificate_requests WHERE status = 'Pending'")->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats error: " . $e->getMessage());
}

// ============================================
// POPULATION: Gender
// ============================================
$genderData = [];
try {
    $rows = $pdo->query("SELECT sex, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' GROUP BY sex ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $genderData[$r['sex'] ?: 'Unknown'] = (int)$r['cnt'];
    }
    $totalMale   = $genderData['Male']   ?? 0;
    $totalFemale = $genderData['Female'] ?? 0;
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: Age Groups
// ============================================

$ageGroupData = [
    'Newborn (0-28 days)'        => 0,
    'Infant (29 days - 1 year)'  => 0,
    'Child (1-9 years)'          => 0,
    'Adolescent (10-19 years)'   => 0,
    'Adult (20-59 years)'        => 0,
    'Senior Citizen (60+ years)' => 0
];

try {
    // We select the existing column and count the occurrences
    $stmt = $pdo->query("
        SELECT age_health_group, COUNT(*) AS cnt 
        FROM residents 
        WHERE activity_status != 'Archived' 
        AND age_health_group IS NOT NULL
        GROUP BY age_health_group
    ");
    
    $rows = $stmt->fetchAll();

    foreach ($rows as $r) {
        // We check if the database value matches one of our predefined keys
        if (isset($ageGroupData[$r['age_health_group']])) {
            $ageGroupData[$r['age_health_group']] = (int)$r['cnt'];
        }
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
}
// ============================================
// POPULATION: Civil Status
// ============================================
$civilStatusData = [];
try {
    $rows = $pdo->query("SELECT civil_status, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' AND civil_status IS NOT NULL AND civil_status != '' GROUP BY civil_status ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $civilStatusData[$r['civil_status']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: Employment Status
// ============================================
$employmentData = [];
try {
    $rows = $pdo->query("SELECT employment_status, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' AND employment_status IS NOT NULL AND employment_status != '' GROUP BY employment_status ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $employmentData[$r['employment_status']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: Educational Attainment
// ============================================
$educationData = [];
try {
    $rows = $pdo->query("SELECT educational_attainment, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' AND educational_attainment IS NOT NULL AND educational_attainment != '' GROUP BY educational_attainment ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $educationData[$r['educational_attainment']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: Special Groups
// ============================================
$specialGroups = ['fourps' => 0, 'voters' => 0, 'pwd' => 0, 'seniors' => 0, 'indigent' => 0];
try {
    $row = $pdo->query("
        SELECT
            SUM(CASE WHEN fourps_member  = 'Yes' THEN 1 ELSE 0 END) AS fourps,
            SUM(CASE WHEN voter_status   = 'Yes' THEN 1 ELSE 0 END) AS voters,
            SUM(CASE WHEN pwd_status     = 'Yes' THEN 1 ELSE 0 END) AS pwd,
            SUM(CASE WHEN age_health_group = 'Senior Citizen (60+ years)' THEN 1 ELSE 0 END) AS seniors,
            SUM(CASE WHEN indigent       = 'Yes' THEN 1 ELSE 0 END) AS indigent
        FROM residents
        WHERE activity_status != 'Archived'
    ")->fetch();
    if ($row) {
        $specialGroups = [
            'fourps'  => (int)$row['fourps'],
            'voters'  => (int)$row['voters'],
            'pwd'     => (int)$row['pwd'],
            'seniors' => (int)$row['seniors'],
            'indigent'=> (int)$row['indigent'],
        ];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: By Purok
// ============================================
$purokCounts = ['p1' => 0, 'p2' => 0, 'p3' => 0, 'p4' => 0];
try {
    $row = $pdo->query("
        SELECT
            SUM(CASE WHEN purok = '1' THEN 1 ELSE 0 END) AS p1,
            SUM(CASE WHEN purok = '2' THEN 1 ELSE 0 END) AS p2,
            SUM(CASE WHEN purok = '3' THEN 1 ELSE 0 END) AS p3,
            SUM(CASE WHEN purok = '4' THEN 1 ELSE 0 END) AS p4
        FROM residents
        WHERE activity_status != 'Archived'
    ")->fetch();
    if ($row) {
        $purokCounts = [
            'p1' => (int)$row['p1'],
            'p2' => (int)$row['p2'],
            'p3' => (int)$row['p3'],
            'p4' => (int)$row['p4'],
        ];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// POPULATION: Verification & Activity Status
// ============================================
$verificationData = [];
$activityData     = [];
try {
    $rows = $pdo->query("SELECT verification_status, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' GROUP BY verification_status")->fetchAll();
    foreach ($rows as $r) { $verificationData[$r['verification_status']] = (int)$r['cnt']; }

    $rows = $pdo->query("SELECT activity_status, COUNT(*) as cnt FROM residents GROUP BY activity_status")->fetchAll();
    foreach ($rows as $r) { $activityData[$r['activity_status']] = (int)$r['cnt']; }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// BLOTTER: Status Breakdown
// ============================================
$blotterStatusData = [];
try {
    $rows = $pdo->query("SELECT status, COUNT(*) as cnt FROM blotter_records GROUP BY status ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $status = ($r['status'] === 'Resolved') ? 'Settled' : $r['status'];
        if (!isset($blotterStatusData[$status])) $blotterStatusData[$status] = 0;
        $blotterStatusData[$status] += (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// BLOTTER: Monthly Trend (current year)
// ============================================
$currentYear       = (int)date('Y');
$blotterMonthly    = array_fill(0, 12, 0);
$availableYears    = [];
try {
    $yearRows = $pdo->query("SELECT DISTINCT YEAR(date_reported) as yr FROM blotter_records ORDER BY yr DESC")->fetchAll();
    foreach ($yearRows as $r) { $availableYears[] = (int)$r['yr']; }
    if (empty($availableYears)) { $availableYears = [$currentYear]; }

    $stmt = $pdo->prepare("SELECT MONTH(date_reported) as mo, COUNT(*) as cnt FROM blotter_records WHERE YEAR(date_reported) = ? GROUP BY MONTH(date_reported)");
    $stmt->execute([$currentYear]);
    foreach ($stmt->fetchAll() as $r) {
        $blotterMonthly[(int)$r['mo'] - 1] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// BLOTTER: Incident Type Breakdown (top 10)
// ============================================
$blotterTypeData = [];
try {
    $rows = $pdo->query("SELECT incident_type, COUNT(*) as cnt FROM blotter_records GROUP BY incident_type ORDER BY cnt DESC LIMIT 10")->fetchAll();
    foreach ($rows as $r) {
        $blotterTypeData[$r['incident_type']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// CERTIFICATES: By Type
// ============================================
$certTypeData    = [];
$certTypeRevenue = [];
try {
    $certYear = isset($_GET['cert_year']) ? $_GET['cert_year'] : date('Y');
    $sql = "SELECT cr.certificate_name, COUNT(cr.id) as cnt FROM certificate_requests cr";
    if ($certYear !== 'all' && is_numeric($certYear)) {
        $sql .= " WHERE YEAR(cr.date_requested) = " . (int)$certYear;
    }
    $sql .= " GROUP BY cr.certificate_name ORDER BY cnt DESC";
    
    $rows = $pdo->query($sql)->fetchAll();
    foreach ($rows as $r) {
        $certTypeData[$r['certificate_name']]    = (int)$r['cnt'];
        $certTypeRevenue[$r['certificate_name']] = 0;
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// CERTIFICATES: By Status
// ============================================
$certStatusData = [];
try {
    $rows = $pdo->query("SELECT status, COUNT(*) as cnt FROM certificate_requests GROUP BY status ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $certStatusData[$r['status']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// CERTIFICATES: By Payment Status + Revenue
// ============================================
$certPaymentData = [];
$totalRevenue    = 0;
try {
    $rows = $pdo->query("SELECT payment_status, COUNT(*) as cnt, COALESCE(SUM(certificate_fee),0) as total FROM certificate_requests GROUP BY payment_status ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $certPaymentData[$r['payment_status']] = [
            'count'   => (int)$r['cnt'],
            'revenue' => (float)$r['total'],
        ];
    }
    $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(certificate_fee),0) FROM certificate_requests WHERE payment_status = 'Paid'")->fetchColumn();
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// HOUSEHOLDS: Water Source
// ============================================
$waterSourceData = [];
try {
    $rows = $pdo->query("SELECT water_source_type, COUNT(*) as cnt FROM households WHERE water_source_type IS NOT NULL AND water_source_type != '' GROUP BY water_source_type ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $waterSourceData[$r['water_source_type']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// HOUSEHOLDS: Toilet Facility
// ============================================
$toiletData = [];
try {
    $rows = $pdo->query("SELECT toilet_facility_type, COUNT(*) as cnt FROM households WHERE toilet_facility_type IS NOT NULL AND toilet_facility_type != '' GROUP BY toilet_facility_type ORDER BY cnt DESC")->fetchAll();
    foreach ($rows as $r) {
        $toiletData[$r['toilet_facility_type']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// OVERVIEW: Population Growth & Blotter Stacked
// ============================================
$popGrowthLabels = [];
$popGrowthData = [];
$blotterStackedData = [
    'Pending' => array_fill(0, 12, 0),
    'Under Investigation' => array_fill(0, 12, 0),
    'Dismissed' => array_fill(0, 12, 0),
    'Settled' => array_fill(0, 12, 0)
];

// Pre-populate labels for the last 12 months to ensure charts render even if queries fail
for ($i = 11; $i >= 0; $i--) {
    $popGrowthLabels[] = date('M Y', strtotime("-$i months"));
    $popGrowthData[] = 0;
}

try {
    // Population Growth (Rolling 12 Months)
    $basePop = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE activity_status != 'Archived' AND created_at < DATE_SUB(CURDATE(), INTERVAL 12 MONTH)")->fetchColumn();
    $stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt FROM residents WHERE activity_status != 'Archived' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym ORDER BY ym ASC");
    $monthlyAdds = [];
    while($r = $stmt->fetch()) { $monthlyAdds[$r['ym']] = (int)$r['cnt']; }
    
    $current = $basePop;
    $popGrowthData = []; // Reset to fill with actual cumulative data
    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $current += ($monthlyAdds[$ym] ?? 0);
        $popGrowthData[] = $current;
    }

    // Blotter Stacked (Rolling 12 Months)
    $stmt = $pdo->query("SELECT DATE_FORMAT(date_reported, '%Y-%m') as ym, status, COUNT(*) as cnt FROM blotter_records WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) GROUP BY ym, status");
    while($r = $stmt->fetch()) {
        $status = ($r['status'] === 'Resolved') ? 'Settled' : $r['status'];
        for($i=11; $i>=0; $i--) {
            if(date('Y-m', strtotime("-$i months")) == $r['ym']) {
                if(isset($blotterStackedData[$status])) {
                    $blotterStackedData[$status][11 - $i] += (int)$r['cnt'];
                }
                break;
            }
        }
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// Fetch Barangay Info and Captain for Official Header/Footer
$barangayInfo = null;
$captainName = 'BARANGAY CAPTAIN';
try {
    $infoStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $barangayInfo = $infoStmt->fetch();
    
    $capStmt = $pdo->query("SELECT fullname FROM barangay_officials WHERE position = 'Barangay Captain' AND status = 'Active' LIMIT 1");
    $cap = $capStmt->fetch();
    if ($cap) $captainName = $cap['fullname'];
} catch (PDOException $e) {
    error_log("Error fetching barangay info: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/reports.css">
    
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>

    <style>
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
            .sidebar, .header, .report-tabs-nav, .no-print, .btn-print, .year-select {
                display: none !important;
            }
            .print-only { display: block !important; }
            
            /* Force charts to be visible */
            canvas { max-width: 100% !important; height: auto !important; }
            
            .report-section { page-break-inside: avoid; margin-bottom: 30px; }
            .report-table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1px solid #000 !important;
            }
            .report-table th, .report-table td {
                border: 1px solid #000 !important;
                padding: 6px !important;
                page-break-inside: avoid;
            }
            .report-table th {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
            }

            /* Header Styles */
            .print-header { text-align: center; margin-bottom: 30px; }
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
            
            /* Only print active tab */
            .report-tab-content:not(.active) { display: none !important; }
            .report-tab-content.active { display: block !important; }
            
            /* Adjust grid for print */
            .reports-stats-grid, .report-two-col { display: block !important; }
            .report-stat-card, .report-chart-box { margin-bottom: 20px; border: 1px solid #eee; }
        }
    </style>
</head>
<body>
    <!-- Sidebar Component -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header Component -->
        <?php include 'components/header.php'; ?>
        
        <!-- Dashboard Content Component -->
        <?php include 'dashboard.php'; ?>
    </main>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/reports.js"></script>
</body>
</html>
