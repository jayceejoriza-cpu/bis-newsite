<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

$pageTitle = 'Reports';

// ============================================
// Database Connection (PDO)
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
// SUMMARY STATS
// ============================================
$totalResidents   = 0;
$totalHouseholds  = 0;
$totalBlotter     = 0;
$totalCertReqs    = 0;
$totalMale        = 0;
$totalFemale      = 0;

try {
    $totalResidents  = (int)$pdo->query("SELECT COUNT(*) FROM residents WHERE activity_status != 'Archived'")->fetchColumn();
    $totalHouseholds = (int)$pdo->query("SELECT COUNT(*) FROM households")->fetchColumn();
    $totalBlotter    = (int)$pdo->query("SELECT COUNT(*) FROM blotter_records")->fetchColumn();
    $totalCertReqs   = (int)$pdo->query("SELECT COUNT(*) FROM certificate_requests")->fetchColumn();
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
$ageGroupData = [];
try {
    $rows = $pdo->query("
        SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18  THEN 'Children (0-17)'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 29 THEN 'Young Adults (18-29)'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 30 AND 59 THEN 'Adults (30-59)'
                ELSE 'Seniors (60+)'
            END AS age_group,
            COUNT(*) AS cnt
        FROM residents
        WHERE activity_status != 'Archived'
        GROUP BY age_group
        ORDER BY FIELD(age_group, 'Children (0-17)', 'Young Adults (18-29)', 'Adults (30-59)', 'Seniors (60+)')
    ")->fetchAll();
    foreach ($rows as $r) {
        $ageGroupData[$r['age_group']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

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
            SUM(CASE WHEN senior_citizen = 'Yes' THEN 1 ELSE 0 END) AS seniors,
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
        $blotterStatusData[$r['status']] = (int)$r['cnt'];
    }
} catch (PDOException $e) { error_log($e->getMessage()); }

// ============================================
// BLOTTER: Monthly Trend (current year)
// ============================================
$currentYear       = (int)date('Y');
$blotterMonthly    = array_fill(0, 12, 0);
$availableYears    = [];
try {
    // Get available years
    $yearRows = $pdo->query("SELECT DISTINCT YEAR(date_reported) as yr FROM blotter_records ORDER BY yr DESC")->fetchAll();
    foreach ($yearRows as $r) { $availableYears[] = (int)$r['yr']; }
    if (empty($availableYears)) { $availableYears = [$currentYear]; }

    // Monthly data for current year
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
    $rows = $pdo->query("
        SELECT c.title, COUNT(cr.id) as cnt, COALESCE(SUM(cr.certificate_fee),0) as revenue
        FROM certificate_requests cr
        JOIN certificates c ON cr.certificate_id = c.id
        GROUP BY c.id, c.title
        ORDER BY cnt DESC
    ")->fetchAll();
    foreach ($rows as $r) {
        $certTypeData[$r['title']]    = (int)$r['cnt'];
        $certTypeRevenue[$r['title']] = (float)$r['revenue'];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/reports.css">

    <script>
        // Apply dark mode immediately to prevent flash
        (function() {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark-mode');
                document.body && document.body.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- Reports Content -->
        <div class="dashboard-content">

            <!-- Page Header -->
            <div class="reports-header">
                <div class="reports-header-left">
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">Comprehensive barangay statistics and data summaries</p>
                </div>
                <div class="reports-header-right no-print">
                    <button class="btn-print" id="printReportBtn">
                        <i class="fas fa-print"></i>
                        Print Report
                    </button>
                </div>
            </div>

            <!-- ================================
                 Summary Stats Cards
                 ================================ -->
            <div class="reports-stats-grid">
                <div class="report-stat-card">
                    <div class="report-stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalResidents); ?></div>
                        <div class="report-stat-label">Total Residents</div>
                    </div>
                </div>

                <div class="report-stat-card">
                    <div class="report-stat-icon green">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalHouseholds); ?></div>
                        <div class="report-stat-label">Total Households</div>
                    </div>
                </div>

                <div class="report-stat-card">
                    <div class="report-stat-icon orange">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalBlotter); ?></div>
                        <div class="report-stat-label">Blotter Records</div>
                    </div>
                </div>

                <div class="report-stat-card">
                    <div class="report-stat-icon purple">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="report-stat-info">
                        <div class="report-stat-value"><?php echo number_format($totalCertReqs); ?></div>
                        <div class="report-stat-label">Certificate Requests</div>
                    </div>
                </div>
            </div>

            <!-- ================================
                 Tabbed Report Sections
                 ================================ -->
            <div class="report-tabs-wrapper">

                <!-- Tab Navigation -->
                <div class="report-tabs-nav no-print">
                    <button class="report-tab-btn active" data-tab="population">
                        <i class="fas fa-users"></i> Population
                    </button>
                    <button class="report-tab-btn" data-tab="blotter">
                        <i class="fas fa-file-alt"></i> Blotter Records
                    </button>
                    <button class="report-tab-btn" data-tab="certificates">
                        <i class="fas fa-certificate"></i> Certificate Requests
                    </button>
                    <button class="report-tab-btn" data-tab="households">
                        <i class="fas fa-home"></i> Households
                    </button>
                </div>

                <!-- ============================
                     TAB 1: Population
                     ============================ -->
                <div class="report-tab-content active" id="tab-population">

                    <!-- Special Groups -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-star"></i> Special Groups
                        </h3>
                        <div class="special-groups-grid">
                            <div class="special-group-card">
                                <div class="special-group-icon" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8);">
                                    <i class="fas fa-hand-holding-heart"></i>
                                </div>
                                <div class="special-group-value"><?php echo number_format($specialGroups['fourps']); ?></div>
                                <div class="special-group-label">4Ps Members</div>
                            </div>
                            <div class="special-group-card">
                                <div class="special-group-icon" style="background: linear-gradient(135deg,#10b981,#059669);">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <div class="special-group-value"><?php echo number_format($specialGroups['voters']); ?></div>
                                <div class="special-group-label">Registered Voters</div>
                            </div>
                            <div class="special-group-card">
                                <div class="special-group-icon" style="background: linear-gradient(135deg,#8b5cf6,#7c3aed);">
                                    <i class="fas fa-wheelchair"></i>
                                </div>
                                <div class="special-group-value"><?php echo number_format($specialGroups['pwd']); ?></div>
                                <div class="special-group-label">PWD</div>
                            </div>
                            <div class="special-group-card">
                                <div class="special-group-icon" style="background: linear-gradient(135deg,#f59e0b,#d97706);">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                                <div class="special-group-value"><?php echo number_format($specialGroups['seniors']); ?></div>
                                <div class="special-group-label">Senior Citizens</div>
                            </div>
                            <div class="special-group-card">
                                <div class="special-group-icon" style="background: linear-gradient(135deg,#ef4444,#dc2626);">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <div class="special-group-value"><?php echo number_format($specialGroups['indigent']); ?></div>
                                <div class="special-group-label">Indigent</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gender + Age Group -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-venus-mars"></i> Gender & Age Distribution
                        </h3>
                        <div class="report-two-col">
                            <!-- Gender Chart -->
                            <div>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Gender Distribution</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="genderChart"
                                            data-labels="<?php echo jsonAttr(array_keys($genderData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($genderData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <!-- Gender Table -->
                                <div class="report-table-box" style="margin-top:16px;">
                                    <div class="report-table-box-title">Gender Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Gender</th>
                                                <th class="text-right">Count</th>
                                                <th class="text-right">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($genderData)): ?>
                                                <tr><td colspan="3" class="report-empty"><i class="fas fa-inbox"></i><p>No data</p></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($genderData as $label => $count): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($label); ?></td>
                                                    <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                    <td class="text-right"><?php echo pct($count, $totalResidents); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr style="font-weight:600; border-top: 2px solid var(--border-color);">
                                                    <td>Total</td>
                                                    <td class="text-right"><?php echo number_format($totalResidents); ?></td>
                                                    <td class="text-right">100%</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Age Group Chart -->
                            <div>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Age Group Distribution</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="ageGroupChart"
                                            data-labels="<?php echo jsonAttr(array_keys($ageGroupData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($ageGroupData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <!-- Age Group Table -->
                                <div class="report-table-box" style="margin-top:16px;">
                                    <div class="report-table-box-title">Age Group Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Age Group</th>
                                                <th class="text-right">Count</th>
                                                <th>Distribution</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $ageColors = ['green','blue','orange','red'];
                                            $ai = 0;
                                            foreach ($ageGroupData as $label => $count):
                                                $color = $ageColors[$ai % count($ageColors)];
                                                $ai++;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td>
                                                    <div class="report-bar-wrap">
                                                        <div class="report-bar">
                                                            <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                        </div>
                                                        <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Civil Status + Employment -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-briefcase"></i> Civil Status & Employment
                        </h3>
                        <div class="report-two-col">
                            <!-- Civil Status -->
                            <div class="report-table-box">
                                <div class="report-table-box-title">Civil Status</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-right">Count</th>
                                            <th>Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($civilStatusData)): ?>
                                            <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                                        <?php else: ?>
                                            <?php $ci = 0; foreach ($civilStatusData as $label => $count): $color = barColor($ci++, $barColors); ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td>
                                                    <div class="report-bar-wrap">
                                                        <div class="report-bar">
                                                            <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                        </div>
                                                        <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Employment Status -->
                            <div class="report-table-box">
                                <div class="report-table-box-title">Employment Status</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-right">Count</th>
                                            <th>Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($employmentData)): ?>
                                            <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                                        <?php else: ?>
                                            <?php $ei = 0; foreach ($employmentData as $label => $count): $color = barColor($ei++, $barColors); ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td>
                                                    <div class="report-bar-wrap">
                                                        <div class="report-bar">
                                                            <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                        </div>
                                                        <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Educational Attainment -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-graduation-cap"></i> Educational Attainment
                        </h3>
                        <div class="report-table-box">
                            <div class="report-table-box-title">Education Level Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Education Level</th>
                                        <th class="text-right">Count</th>
                                        <th>Distribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($educationData)): ?>
                                        <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                                    <?php else: ?>
                                        <?php $edi = 0; foreach ($educationData as $label => $count): $color = barColor($edi++, $barColors); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td>
                                                <div class="report-bar-wrap">
                                                    <div class="report-bar">
                                                        <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                    </div>
                                                    <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Verification & Activity Status -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-check-circle"></i> Verification & Activity Status
                        </h3>
                        <div class="report-two-col">
                            <div class="report-table-box">
                                <div class="report-table-box-title">Verification Status</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-right">Count</th>
                                            <th class="text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($verificationData)): ?>
                                            <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data</p></div></td></tr>
                                        <?php else: ?>
                                            <?php foreach ($verificationData as $label => $count): ?>
                                            <tr>
                                                <td>
                                                    <span class="report-badge <?php echo strtolower($label); ?>">
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </span>
                                                </td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td class="text-right"><?php echo pct($count, $totalResidents); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="report-table-box">
                                <div class="report-table-box-title">Activity Status</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-right">Count</th>
                                            <th class="text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $allResidents = array_sum($activityData);
                                        foreach ($activityData as $label => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td class="text-right"><?php echo pct($count, $allResidents); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div><!-- end tab-population -->

                <!-- ============================
                     TAB 2: Blotter Records
                     ============================ -->
                <div class="report-tab-content" id="tab-blotter">

                    <!-- Blotter Status + Monthly Trend -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-chart-pie"></i> Case Status Overview
                        </h3>
                        <div class="report-two-col">
                            <!-- Status Doughnut -->
                            <div>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Status Distribution</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="blotterStatusChart"
                                            data-labels="<?php echo jsonAttr(array_keys($blotterStatusData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($blotterStatusData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <div class="report-table-box" style="margin-top:16px;">
                                    <div class="report-table-box-title">Status Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-right">Count</th>
                                                <th class="text-right">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($blotterStatusData)): ?>
                                                <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No blotter records</p></div></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($blotterStatusData as $label => $count): ?>
                                                <tr>
                                                    <td>
                                                        <span class="report-badge <?php echo strtolower(str_replace(' ','-',$label)); ?>">
                                                            <?php echo htmlspecialchars($label); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                    <td class="text-right"><?php echo pct($count, $totalBlotter); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <tr style="font-weight:600; border-top:2px solid var(--border-color);">
                                                    <td>Total</td>
                                                    <td class="text-right"><?php echo number_format($totalBlotter); ?></td>
                                                    <td class="text-right">100%</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Monthly Trend -->
                            <div>
                                <div class="report-chart-box">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                        <div class="report-chart-box-title" style="margin-bottom:0;">Monthly Trend</div>
                                        <select id="blotterYearSelect" class="year-select" style="font-size:13px;padding:5px 10px;">
                                            <?php foreach ($availableYears as $yr): ?>
                                                <option value="<?php echo $yr; ?>" <?php echo ($yr == $currentYear) ? 'selected' : ''; ?>>
                                                    <?php echo $yr; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="report-chart-canvas-wrap tall">
                                        <canvas id="blotterMonthlyChart"
                                            data-values="<?php echo jsonAttr($blotterMonthly); ?>">
                                        </canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Incident Type Breakdown -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-exclamation-triangle"></i> Incident Type Breakdown
                        </h3>
                        <div class="report-two-col">
                            <div class="report-chart-box">
                                <div class="report-chart-box-title">Top Incident Types</div>
                                <div class="report-chart-canvas-wrap tall">
                                    <canvas id="blotterTypeChart"
                                        data-labels="<?php echo jsonAttr(array_keys($blotterTypeData)); ?>"
                                        data-values="<?php echo jsonAttr(array_values($blotterTypeData)); ?>">
                                    </canvas>
                                </div>
                            </div>
                            <div class="report-table-box">
                                <div class="report-table-box-title">Incident Type Summary</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Incident Type</th>
                                            <th class="text-right">Cases</th>
                                            <th>Share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($blotterTypeData)): ?>
                                            <tr><td colspan="4"><div class="report-empty"><i class="fas fa-inbox"></i><p>No incident data</p></div></td></tr>
                                        <?php else: ?>
                                            <?php $rank = 1; foreach ($blotterTypeData as $label => $count): $color = barColor($rank-1, $barColors); ?>
                                            <tr>
                                                <td style="color:var(--text-secondary);font-weight:600;"><?php echo $rank++; ?></td>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td>
                                                    <div class="report-bar-wrap">
                                                        <div class="report-bar">
                                                            <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalBlotter); ?>%"></div>
                                                        </div>
                                                        <span class="report-bar-pct"><?php echo pct($count, $totalBlotter); ?>%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div><!-- end tab-blotter -->

                <!-- ============================
                     TAB 3: Certificate Requests
                     ============================ -->
                <div class="report-tab-content" id="tab-certificates">

                    <!-- Revenue Highlight -->
                    <div class="revenue-highlight">
                        <div>
                            <div class="revenue-highlight-label">Total Revenue Collected (Paid)</div>
                            <div class="revenue-highlight-value">₱<?php echo number_format($totalRevenue, 2); ?></div>
                        </div>
                        <div class="revenue-highlight-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                    </div>

                    <!-- By Type + By Status -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-chart-bar"></i> Requests by Certificate Type
                        </h3>
                        <div class="report-two-col">
                            <div class="report-chart-box">
                                <div class="report-chart-box-title">Certificate Type Distribution</div>
                                <div class="report-chart-canvas-wrap tall">
                                    <canvas id="certTypeChart"
                                        data-labels="<?php echo jsonAttr(array_keys($certTypeData)); ?>"
                                        data-values="<?php echo jsonAttr(array_values($certTypeData)); ?>">
                                    </canvas>
                                </div>
                            </div>
                            <div class="report-table-box">
                                <div class="report-table-box-title">Certificate Type Summary</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Certificate</th>
                                            <th class="text-right">Requests</th>
                                            <th class="text-right">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($certTypeData)): ?>
                                            <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No certificate requests</p></div></td></tr>
                                        <?php else: ?>
                                            <?php foreach ($certTypeData as $label => $count): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($label); ?></td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td class="text-right">₱<?php echo number_format($certTypeRevenue[$label] ?? 0, 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr style="font-weight:600; border-top:2px solid var(--border-color);">
                                                <td>Total</td>
                                                <td class="text-right"><?php echo number_format($totalCertReqs); ?></td>
                                                <td class="text-right">₱<?php echo number_format(array_sum($certTypeRevenue), 2); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Status + Payment -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-tasks"></i> Request Status & Payment
                        </h3>
                        <div class="report-two-col">
                            <!-- Status Doughnut -->
                            <div>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Request Status</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="certStatusChart"
                                            data-labels="<?php echo jsonAttr(array_keys($certStatusData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($certStatusData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <div class="report-table-box" style="margin-top:16px;">
                                    <div class="report-table-box-title">Status Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-right">Count</th>
                                                <th class="text-right">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($certStatusData as $label => $count): ?>
                                            <tr>
                                                <td>
                                                    <span class="report-badge <?php echo strtolower($label); ?>">
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </span>
                                                </td>
                                                <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                <td class="text-right"><?php echo pct($count, $totalCertReqs); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Payment Status -->
                            <div class="report-table-box">
                                <div class="report-table-box-title">Payment Status & Revenue</div>
                                <table class="report-table">
                                    <thead>
                                        <tr>
                                            <th>Payment Status</th>
                                            <th class="text-right">Count</th>
                                            <th class="text-right">Amount</th>
                                            <th class="text-right">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($certPaymentData)): ?>
                                            <tr><td colspan="4"><div class="report-empty"><i class="fas fa-inbox"></i><p>No payment data</p></div></td></tr>
                                        <?php else: ?>
                                            <?php foreach ($certPaymentData as $label => $data): ?>
                                            <tr>
                                                <td>
                                                    <span class="report-badge <?php echo strtolower($label); ?>">
                                                        <?php echo htmlspecialchars($label); ?>
                                                    </span>
                                                </td>
                                                <td class="text-right"><strong><?php echo number_format($data['count']); ?></strong></td>
                                                <td class="text-right">₱<?php echo number_format($data['revenue'], 2); ?></td>
                                                <td class="text-right"><?php echo pct($data['count'], $totalCertReqs); ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div><!-- end tab-certificates -->

                <!-- ============================
                     TAB 4: Households
                     ============================ -->
                <div class="report-tab-content" id="tab-households">

                    <!-- Household Summary Card -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-home"></i> Household Overview
                        </h3>
                        <div class="reports-stats-grid" style="margin-bottom:0;">
                            <div class="report-stat-card">
                                <div class="report-stat-icon teal">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="report-stat-info">
                                    <div class="report-stat-value"><?php echo number_format($totalHouseholds); ?></div>
                                    <div class="report-stat-label">Total Households</div>
                                </div>
                            </div>
                            <div class="report-stat-card">
                                <div class="report-stat-icon blue">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="report-stat-info">
                                    <div class="report-stat-value"><?php echo number_format($totalResidents); ?></div>
                                    <div class="report-stat-label">Total Residents</div>
                                </div>
                            </div>
                            <div class="report-stat-card">
                                <div class="report-stat-icon green">
                                    <i class="fas fa-user-friends"></i>
                                </div>
                                <div class="report-stat-info">
                                    <div class="report-stat-value">
                                        <?php echo $totalHouseholds > 0 ? number_format($totalResidents / $totalHouseholds, 1) : '0'; ?>
                                    </div>
                                    <div class="report-stat-label">Avg. Residents/Household</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Water Source + Toilet Facility -->
                    <div class="report-section">
                        <h3 class="report-section-title">
                            <i class="fas fa-tint"></i> Facilities & Utilities
                        </h3>
                        <div class="report-two-col">
                            <!-- Water Source -->
                            <div>
                                <?php if (!empty($waterSourceData)): ?>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Water Source Types</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="waterSourceChart"
                                            data-labels="<?php echo jsonAttr(array_keys($waterSourceData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($waterSourceData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="report-table-box" style="margin-top:<?php echo !empty($waterSourceData) ? '16px' : '0'; ?>;">
                                    <div class="report-table-box-title">Water Source Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Water Source</th>
                                                <th class="text-right">Households</th>
                                                <th class="text-right">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($waterSourceData)): ?>
                                                <tr><td colspan="3"><div class="report-empty"><i class="fas fa-tint-slash"></i><p>No water source data recorded</p></div></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($waterSourceData as $label => $count): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($label); ?></td>
                                                    <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                    <td class="text-right"><?php echo pct($count, $totalHouseholds); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Toilet Facility -->
                            <div>
                                <?php if (!empty($toiletData)): ?>
                                <div class="report-chart-box">
                                    <div class="report-chart-box-title">Toilet Facility Types</div>
                                    <div class="report-chart-canvas-wrap">
                                        <canvas id="toiletChart"
                                            data-labels="<?php echo jsonAttr(array_keys($toiletData)); ?>"
                                            data-values="<?php echo jsonAttr(array_values($toiletData)); ?>">
                                        </canvas>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <div class="report-table-box" style="margin-top:<?php echo !empty($toiletData) ? '16px' : '0'; ?>;">
                                    <div class="report-table-box-title">Toilet Facility Breakdown</div>
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>Facility Type</th>
                                                <th class="text-right">Households</th>
                                                <th class="text-right">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($toiletData)): ?>
                                                <tr><td colspan="3"><div class="report-empty"><i class="fas fa-toilet"></i><p>No toilet facility data recorded</p></div></td></tr>
                                            <?php else: ?>
                                                <?php foreach ($toiletData as $label => $count): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($label); ?></td>
                                                    <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                                    <td class="text-right"><?php echo pct($count, $totalHouseholds); ?>%</td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- end tab-households -->

            </div><!-- end report-tabs-wrapper -->

        </div><!-- end dashboard-content -->
    </main>

    <!-- Bootstrap JS -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="js/script.js"></script>
    <script src="js/reports.js"></script>
</body>
</html>
