<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

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
// Get GET Parameters
// ============================================
$resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : 0;
$cert_date   = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$assistance  = isset($_GET['assistance']) ? trim($_GET['assistance']) : '';

if ($resident_id <= 0) {
    die("Invalid resident ID.");
}

// ============================================
// Fetch Resident Data
// ============================================
$resident = null;
try {
    $stmt = $pdo->prepare("
        SELECT id, first_name, middle_name, last_name, suffix
        FROM residents
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$resident_id]);
    $row = $stmt->fetch();
    if ($row) {
        $resident = [
            'firstname'  => $row['first_name'],
            'middlename' => $row['middle_name'],
            'lastname'   => $row['last_name'],
            'suffix'     => $row['suffix'],
        ];
    }
} catch (PDOException $e) {
    error_log("Error fetching resident: " . $e->getMessage());
}

if (!$resident) {
    die("Resident not found.");
}

// ============================================
// Fetch Barangay Info
// ============================================
$brgy_logo = '';
$city_logo = '';
$province  = 'Province';
$town      = 'Municipality';
$brgy      = 'Barangay';

try {
    $biStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $bi = $biStmt->fetch();
    if ($bi) {
        $province  = $bi['province_name']  ?? 'Province';
        $town      = $bi['town_name']      ?? 'Municipality';
        $brgy      = $bi['barangay_name']  ?? 'Barangay';
        $brgy_logo = $bi['barangay_logo']  ?? '';
        $city_logo = $bi['municipal_logo'] ?? '';

        // Fix paths for subdirectory
        if (!empty($brgy_logo)) {
            $brgy_logo = '../' . $brgy_logo;
        }
        if (!empty($city_logo)) {
            $city_logo = '../' . $city_logo;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching barangay info: " . $e->getMessage());
}

// ============================================
// Fetch the brgy captain and admin
// ============================================
$captain = null;
$brgy_admin  = null;

try {
    // Fetching only the specific roles needed
    $offStmt = $pdo->prepare("
        SELECT 
            bo.position,
            COALESCE(bo.fullname, 
                CONCAT(
                    COALESCE(r.first_name,''), ' ', 
                    COALESCE(r.middle_name,''), ' ', 
                    COALESCE(r.last_name,'')
                )
            ) AS name
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE bo.position IN ('Barangay Captain', 'Barangay Administrator') 
          AND bo.status = 'Active'
    ");
    $offStmt->execute();
    $results = $offStmt->fetchAll();

    foreach ($results as $row) {
        $cleanName = trim(preg_replace('/\s+/', ' ', $row['name']));
        
        if ($row['position'] === 'Barangay Captain') {
            $captain = ['name' => $cleanName];
        } elseif ($row['position'] === 'Barangay Administrator') {
            $brgy_admin = ['name' => $cleanName];
        }
    }

} catch (PDOException $e) {
    error_log("Error fetching officials: " . $e->getMessage());
}

// Build full resident name
$residentFullName = ucwords(trim(
    $resident['firstname'] . ' ' .
    ($resident['middlename'] ? $resident['middlename'] . ' ' : '') .
    $resident['lastname'] .
    ($resident['suffix'] ? ' ' . $resident['suffix'] : '')
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Indigency - <?= htmlspecialchars($brgy) ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* ===========================
           Base
        =========================== */
        

        .print-action-bar {
            display: flex;
            justify-content: flex-end;
            padding: 12px 24px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        /* ===========================
           Certificate Page Wrapper
        =========================== */
        .cert-page {
            position: relative;
            padding: 20px 30px 30px 65px;
            min-height: 900px;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        /* ===========================
           Vertical Side Text
        =========================== */
        .vertical-text {
            position: absolute;
            left: 4px;
            top: 20px;
            font-style: italic;
            font-weight: bold;
            font-size: 34px;
            color: #7a51c9;
            letter-spacing: 10px;
            line-height: 1.6;
            text-align: center;
            user-select: none;
        }

        /* ===========================
           Certificate Header
        =========================== */
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #7a51c9;
            padding-bottom: 10px;
            margin-bottom: 12px;
            margin-top: 70px;
        }

        .cert-header .logo-img {
            width: 95px;
            height: 95px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .cert-header .logo-placeholder-box {
            width: 95px;
            height: 95px;
            flex-shrink: 0;
        }

        .cert-header .header-center {
            flex: 1;
            text-align: center;
            padding: 0 15px;
        }

        .cert-header .header-center p {
            margin: 0;
            font-size: 13px;
            line-height: 1.5;
        }

        .cert-header .header-center .brgy-name {
            font-size: 19px;
            font-weight: bold;
            font-family: 'Times New Roman', Times, serif;
            margin-top: 2px;
        }

        .cert-header .header-center .office-name {
            font-size: 13px;
            font-weight: bold;
        }

        /* ===========================
           Certificate Title
        =========================== */
        .cert-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            text-decoration: underline;
            font-family: 'Times New Roman', Times, serif;
            margin: 40px 0 10px;
            letter-spacing: 1px;

        }

        /* ===========================
           Date Line
        =========================== */
        .cert-date-line {
            text-align: right;
            margin-right: 60px;
            font-size: 15px;
            margin-bottom: 14px;
        }

        /* ===========================
           Content Area (sidebar + body)
        =========================== */
        .cert-content-area {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }


        /* ===========================
           Certificate Body
        =========================== */
        .cert-body {
            flex: 1;
            position: relative;
            min-height: 500px;
        }

        .cert-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            width: 280px;
            height: auto;
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

        .cert-body-content {
            position: relative;
            z-index: 1;
        }

        .cert-body-content p {
            font-size: 15px;
            text-align: justify;
            margin-bottom: 10px;
            line-height: 1.65;
        }

        .cert-body-content .text-indent {
            text-indent: 40px;
        }

        /* ===========================
           Signatures
        =========================== */
        .cert-signatures {
            display: flex;
            margin-top: 45px;
            position: relative;
            z-index: 1;
        }

        .sig-left {
            flex: 1;
            padding-left: 10px;
        }

        .sig-right {
            flex: 1;
            text-align: center;
        }

        .sig-line-wrap {
            display: inline-block;
            border-top: 2px solid #000;
            min-width: 220px;
            padding-top: 5px;
            font-size: 14px;
            text-align: center;
        }

        .sig-approved-label {
            font-size: 14px;
            margin-bottom: 40px;
            text-align: left;
            padding-left: 20px;
        }

        .sig-captain-name {
            font-weight: bold;
            font-size: 15px;
            text-transform: uppercase;
        }

        .sig-captain-title {
            font-size: 13px;
        }

        /* ===========================
           Print Styles
        =========================== */
        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            html, body {
                margin: 0;
                padding: 0;
                height: 100vh;
                overflow: hidden;
            }

            /* Hide system UI */
            .sidebar,
            .main-content > .header,
            .print-action-bar,
            .card-header,
            nav,
            header {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .dashboard-content {
                padding: 0 !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
            }

            .card-body {
                margin: 0 !important;
                padding: 0 !important;
            }

            #printThis {
                page-break-inside: avoid;
                page-break-after: avoid;
            }

            /* Adjust sizes for print */
            .cert-page {
                padding: 15px 70px 20px 70px;
            }

            .vertical-text {
            position: absolute;
            font-style: italic;
            top: 6px;
            text-orientation: mixed;
            font-weight: bold;
            font-size: 43.5px;
            color: #7a51c9;
            font-family: Arial, sans-serif;
            letter-spacing: 10px;
            user-select: none;
            }

            .cert-header .logo-img {
                width: 110px;
                height: 110px;
            }

            .cert-header .header-center .brgy-name {
                font-size: 22px;
            }

            .cert-title {
                font-size: 24px;
            }

            .cert-body-content p {
                font-size: 16px;
            }

            .cert-watermark {
                width: 380px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include '../components/header.php'; ?>

        <!-- Print Action Bar -->
        <div class="print-action-bar">
            <button class="btn btn-info btn-sm" onclick="saveAndPrint()">
                <i class="fa fa-print"></i>
                Print Certificate
            </button>
        </div>

        <!-- Certificate Content -->
        <div class="dashboard-content">
            <div class="card">
                <div class="card-header">
                    <div class="fw-bold">Certificate of Indigency</div>
                </div>

                <div class="card-body" id="printThis">
                    <div class="cert-page">

                        <!-- =====================
                             Vertical Side Text
                        ===================== -->
                        <div class="vertical-text">
                                        B<br>A<br>R<br>A<br>N<br>G<br>A<br>Y<br>W<br>A<br>W<br>A<br>N<br>D<br>U<br>E
                                    </div>

                        <!-- =====================
                             Header
                        ===================== -->
                        <div class="cert-header">
                            <?php if (!empty($brgy_logo)): ?>
                                <img src="<?= htmlspecialchars($brgy_logo) ?>" class="logo-img" alt="Barangay Logo">
                            <?php else: ?>
                                <div class="logo-placeholder-box"></div>
                            <?php endif; ?>

                            <div class="header-center">
                                <p>Republic of the Philippines</p>
                                <p>Province of <?= ucwords($province) ?></p>
                                <p>Municipality of <?= ucwords($town) ?></p>
                                <p class="brgy-name"><?= strtoupper($brgy) ?></p>
                                <p class="office-name">OFFICE OF THE PUNONG BARANGAY</p>
                            </div>

                            <?php if (!empty($city_logo)): ?>
                                <img src="<?= htmlspecialchars($city_logo) ?>" class="logo-img" alt="Municipal Logo">
                            <?php else: ?>
                                <div class="logo-placeholder-box"></div>
                            <?php endif; ?>
                        </div>

                        <!-- =====================
                             Title
                        ===================== -->
                        <h2 class="cert-title">CERTIFICATE OF INDIGENCY</h2>

                        <!-- =====================
                             Date
                        ===================== -->
                        <div class="cert-date-line">
                            Date: <strong><u><?= htmlspecialchars($cert_date) ?></u></strong>
                        </div>

                        <!-- =====================
                             Content Area
                        ===================== -->
                        <div class="cert-content-area">

                            <!-- Certificate Body -->
                            <div class="cert-body">
                                <?php if (!empty($brgy_logo)): ?>
                                <img src="<?= htmlspecialchars($brgy_logo) ?>" class="cert-watermark" alt="">
                                <?php endif; ?>

                                <div class="cert-body-content">
                                    <p class="text-indent">
                                        <b>THIS IS TO CERTIFY</b> that
                                        <strong><u><?= htmlspecialchars($residentFullName) ?></u></strong>
                                        of legal age, Filipino and presently residing at
                                        <?= ucwords($brgy) ?>, <?= ucwords($town) ?>, <?= ucwords($province) ?>
                                        has requested for a <b>BARANGAY CERTIFICATION</b> for <b>INDIGENCE</b> from office.
                                    </p>

                                    <p class="text-indent">
                                        That through actual investigation conducted and per inventory of the record
                                        Available in this office that he/she belongs to a;
                                    </p>

                                    <p class="text-indent">
                                        <b>BELONGS TO INDIGENT FAMILY IN
                                        <?= strtoupper($brgy) ?>,
                                        <?= strtoupper($town) ?>,
                                        <?= strtoupper($province) ?>.</b>
                                    </p>

                                    <p class="text-indent">
                                        This BARANGAY CERTIFICATION for INDIGENCE is hereby issued as
                                        Verification upon request for
                                        <b><u><?= !empty($assistance) ? strtoupper(htmlspecialchars($assistance)) : '________' ?></u>
                                        ASSISTANCE.</b>
                                    </p>

                                    <p class="text-indent">
                                        Further, the <b>BARANGAY CERTIFICATION</b> for <b>INDIGENCY</b> is valid
                                        from <b>ONE</b>(1) MONTH from the date of issue.
                                    </p>

                                    <!-- Signatures -->
                                    <div class="cert-signatures">
                                        <div class="sig-left">
                                            <div class="sig-line-wrap">
                                                APPLICANT SIGNATURE
                                            </div>
                                        </div>

                                        <div class="sig-right">
                                            <div class="sig-approved-label">APPROVED BY:</div>
                                            <?php if (!empty($captain)): ?>
                                            <div class="sig-captain-name">HON. <?= strtoupper($captain['name']) ?></div>
                                            <div class="sig-captain-title">Punong Barangay</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- /Certificate Body -->

                        </div>
                        <!-- /Content Area -->

                    </div>
                    <!-- /cert-page -->
                </div>
                <!-- /card-body -->
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    <script>
          // Fix sidebar links for subdirectory (handles hardcoded links in sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar a, .sidebar-wrapper a, .nav-item a');
            sidebarLinks.forEach(link => {
                const href = link.getAttribute('href');
                // Check if link is relative and doesn't start with ../ or other protocols
                if (href && 
                    !href.startsWith('http') && 
                    !href.startsWith('/') && 
                    !href.startsWith('#') && 
                    !href.startsWith('javascript') && 
                    !href.startsWith('../')) {
                    
                    link.setAttribute('href', '../' + href);
                }
            });
        });

        function saveAndPrint() {
            const formData = new FormData();
            formData.append('resident_id', '<?php echo $resident_id; ?>');
            formData.append('certificate_type', 'Certificate of Indigency');
            formData.append('purpose', '<?php echo !empty($assistance) ? htmlspecialchars($assistance) . " Assistance" : "Indigency Assistance"; ?>');

            fetch('../model/save_print_log.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.print();
                } else {
                    console.error('Failed to save print log:', data.message);
                    window.print(); // Print anyway
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.print();
            });
        }
            
    </script>
</body>
</html>
