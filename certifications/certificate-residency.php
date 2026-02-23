<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

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
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// GET Parameters
// ============================================
$resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : 0;
$cert_date   = isset($_GET['date'])        ? $_GET['date']                : date('Y-m-d');
$purpose     = isset($_GET['purpose'])     ? trim($_GET['purpose'])       : '';

if ($resident_id <= 0) {
    die("Invalid resident ID.");
}

// ============================================
// Fetch Resident Data
// ============================================
$resident = null;
try {
    $stmt = $pdo->prepare("
        SELECT id, first_name, middle_name, last_name, suffix,
               date_of_birth,
               TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age
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
            'birthdate'  => $row['date_of_birth'],
            'age'        => $row['age'],
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
$city_logo  = '';
$province   = 'Province';
$town       = 'Municipality';
$brgy       = 'Barangay';

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
// Fetch Officials (Active)
// ============================================
$captain      = null;
$officials    = [];
$sk_chairman  = null;
$treasurer    = null;
$sec          = null;
$office_admin = null;

try {
    $offStmt = $pdo->query("
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
        WHERE bo.status = 'Active'
        ORDER BY bo.hierarchy_level ASC, bo.position ASC
    ");
    $allOfficials = $offStmt->fetchAll();

    foreach ($allOfficials as $off) {
        $name = trim(preg_replace('/\s+/', ' ', $off['name']));
        switch ($off['position']) {
            case 'Barangay Captain':
                if (!$captain) $captain = ['name' => $name];
                break;
            case 'Kagawad':
                $officials[] = ['name' => $name];
                break;
            case 'SK Chairman':
                if (!$sk_chairman) $sk_chairman = ['name' => $name];
                break;
            case 'Barangay Treasurer':
                if (!$treasurer) $treasurer = ['name' => $name];
                break;
            case 'Barangay Secretary':
                if (!$sec) $sec = ['name' => $name];
                break;
            case 'Office Administrator':
                if (!$office_admin) $office_admin = ['name' => $name];
                break;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching officials: " . $e->getMessage());
}

// ============================================
// Build Resident Full Name
// ============================================
$residentFullName = ucwords(trim(
    $resident['firstname'] . ' ' .
    ($resident['middlename'] ? $resident['middlename'] . ' ' : '') .
    $resident['lastname'] .
    ($resident['suffix'] ? ' ' . $resident['suffix'] : '')
));

// ============================================
// Format Date Parts
// ============================================
$certDateObj  = !empty($cert_date) ? new DateTime($cert_date) : new DateTime();
$dayOrdinal   = date('jS',  $certDateObj->getTimestamp());
$monthName    = date('F',   $certDateObj->getTimestamp());
$yearNum      = date('Y',   $certDateObj->getTimestamp());
$birthdateFmt = !empty($resident['birthdate'])
    ? date('F d, Y', strtotime($resident['birthdate']))
    : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Residency - <?= htmlspecialchars($brgy) ?></title>

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
           Content Area (sidebar + body)
        =========================== */
        .cert-content-area {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        /* ===========================
           Officials Sidebar
        =========================== */
        .officials-sidebar {
            width: 160px;
            min-width: 160px;
            border: 2px solid #000;
            padding: 8px 10px;
            font-size: 10.5px;
        }

        .officials-sidebar .council-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 11.5px;
            margin: 8px 0 14px;
            line-height: 1.4;
        }

        .officials-sidebar .official-item {
            text-align: center;
            margin-bottom: 11px;
        }

        .officials-sidebar .official-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10.5px;
            line-height: 1.3;
        }

        .officials-sidebar .official-position {
            font-style: italic;
            font-size: 9px;
            line-height: 1.2;
        }

        .officials-sidebar .kagawad-header {
            text-align: center;
            font-weight: bold;
            font-size: 10.5px;
            margin: 6px 0 8px;
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
            margin-bottom: 14px;
            line-height: 1.75;
            font-family: 'Times New Roman', Times, serif;
        }

        .cert-body-content .text-indent {
            text-indent: 40px;
        }

        .cert-body-content .underline-val {
            font-weight: bold;
            text-decoration: underline;
            font-size: 16px;
        }

        /* ===========================
           Signatures
        =========================== */
        .cert-signatures {
            display: flex;
            margin-top: 50px;
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
                font-weight: bold;
                font-size: 43.5px;
                color: #7a51c9;
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
                    <div class="fw-bold">Certificate of Residency</div>
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
                        <h2 class="cert-title">CERTIFICATE OF RESIDENCY</h2>

                        <!-- =====================
                             Content Area
                        ===================== -->
                        <div class="cert-content-area">

                            <!-- Officials Sidebar -->
                            <div class="officials-sidebar">
                                <div class="council-title">
                                    <?= strtoupper($brgy) ?><br>BARANGAY COUNCIL
                                </div>

                                <?php if (!empty($captain)): ?>
                                <div class="official-item">
                                    <div class="official-name">HON. <?= strtoupper($captain['name']) ?></div>
                                    <div class="official-position">PUNONG BARANGAY</div>
                                </div>
                                <?php endif; ?>

                                <div class="kagawad-header">BARANGAY KAGAWAD</div>

                                <?php foreach ($officials as $official): ?>
                                <div class="official-item">
                                    <div class="official-name"><?= strtoupper($official['name']) ?></div>
                                </div>
                                <?php endforeach; ?>

                                <?php if (!empty($sk_chairman)): ?>
                                <div class="official-item" style="margin-top:14px;">
                                    <div class="official-name"><?= strtoupper($sk_chairman['name']) ?></div>
                                    <div class="official-position">SK Chairman</div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($treasurer)): ?>
                                <div class="official-item">
                                    <div class="official-name"><?= strtoupper($treasurer['name']) ?></div>
                                    <div class="official-position">BARANGAY TREASURER</div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($sec)): ?>
                                <div class="official-item">
                                    <div class="official-name"><?= strtoupper($sec['name']) ?></div>
                                    <div class="official-position">BARANGAY SECRETARY</div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($office_admin)): ?>
                                <div class="official-item">
                                    <div class="official-name"><?= strtoupper($office_admin['name']) ?></div>
                                    <div class="official-position">OFFICE ADMINISTRATOR</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- /Officials Sidebar -->

                            <!-- Certificate Body -->
                            <div class="cert-body">
                                <?php if (!empty($brgy_logo)): ?>
                                <img src="<?= htmlspecialchars($brgy_logo) ?>" class="cert-watermark" alt="">
                                <?php endif; ?>

                                <div class="cert-body-content">

                                    <!-- TO WHOM IT MAY CONCERN -->
                                    <p style="margin-top: 10px; font-size:16px;">
                                        <strong>TO WHOM IT MAY CONCERN:</strong>
                                    </p>

                                    <!-- Paragraph 1: Certification body -->
                                    <p class="text-indent">
                                        This is to certify that
                                        <span class="underline-val"><?= htmlspecialchars($residentFullName) ?></span>,
                                        <span class="underline-val"><?= htmlspecialchars($resident['age']) ?></span>
                                        years old, born
                                        <span class="underline-val"><?= htmlspecialchars($birthdateFmt) ?></span>,
                                        Filipino citizen, a residence of
                                        <strong><?= ucwords($brgy) ?></strong>,
                                        <strong><?= ucwords($town) ?></strong>,
                                        <strong><?= ucwords($province) ?></strong>
                                        since birth.
                                    </p>

                                    <!-- Paragraph 2: Purpose -->
                                    <p class="text-indent">
                                        This Certification is issued upon request of the above mention name for
                                        <?php if (!empty($purpose)): ?>
                                            <span class="underline-val"><?= htmlspecialchars(strtoupper($purpose)) ?></span>.
                                        <?php else: ?>
                                            <span style="display:inline-block; min-width:180px; border-bottom:2px solid #000;">&nbsp;</span>.
                                        <?php endif; ?>
                                    </p>

                                    <!-- Paragraph 3: Given this -->
                                    <p class="text-indent">
                                        Given this
                                        <span class="underline-val"><?= $dayOrdinal ?></span>
                                        <strong>day</strong> of
                                        <span class="underline-val"><?= $monthName ?></span>,
                                        <span class="underline-val"><?= $yearNum ?></span>
                                        at the Office of the Punong Barangay of
                                        <strong><?= ucwords($brgy) ?></strong>,
                                        <strong><?= ucwords($town) ?></strong>,
                                        <strong><?= ucwords($province) ?></strong>.
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
                                    <!-- /Signatures -->

                                </div>
                                <!-- /cert-body-content -->
                            </div>
                            <!-- /Certificate Body -->

                        </div>
                        <!-- /cert-content-area -->

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
            formData.append('certificate_type', 'Certificate of Residency');
            formData.append('purpose', '<?php echo !empty($purpose) ? htmlspecialchars($purpose) : "Residency Proof"; ?>');

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
                    window.print();
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
