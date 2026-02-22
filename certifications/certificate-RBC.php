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
// Fetch the brgy captain
// ============================================
$captain = null;

try {
    // Optimized query to fetch only the active Captain
    $offStmt = $pdo->prepare("
        SELECT 
            COALESCE(bo.fullname, 
                CONCAT(
                    COALESCE(r.first_name,''), ' ', 
                    COALESCE(r.middle_name,''), ' ', 
                    COALESCE(r.last_name,'')
                )
            ) AS name
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE bo.position = 'Barangay Captain' 
          AND bo.status = 'Active'
        LIMIT 1
    ");
    $offStmt->execute();
    $row = $offStmt->fetch();

    if ($row) {
        // Clean up whitespace and assign to the variable
        $name = trim(preg_replace('/\s+/', ' ', $row['name']));
        $captain = ['name' => $name];
    }

} catch (PDOException $e) {
    error_log("Error fetching Captain: " . $e->getMessage());
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
    <title>Certification Of Registration of Birth Certificate - <?= htmlspecialchars($brgy) ?></title>

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
           Certificate Header
        =========================== */
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #d81010;
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
            margin-right: 140px;
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
            font-size: 16px;
            font-weight: bold;
            margin-top: 10px;
        }

        /* ===========================
           Certificate Title
        =========================== */
        .cert-title {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
           
            font-family: arial, sans-serif;
            margin: 40px 0 20px;
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
            line-height: 1.15;
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
        .cert-body-content .bold {
            font-weight: bold;
            font-size: 16px;
        }

        /* ===========================
           Signatures
        =========================== */

        .sig-right {
            flex: 1;
            text-align: center;
            margin-top: 60px;
            margin-left: 360px;
        }

        .sig-line-wrap {
            display: inline-block;
            border-top: 2px solid #000;
            min-width: 220px;
            padding-top: 5px;
            font-size: 14px;
            text-align: center;
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
            <button class="btn btn-info btn-sm" onclick="window.print()">
                <i class="fa fa-print"></i>
                Print Certificate
            </button>
        </div>

        <!-- Certificate Content -->
        <div class="dashboard-content">
            <div class="card">
                <div class="card-header">
                    <div class="fw-bold">Certification Of Registration of Birth Certificate</div>
                </div>

                <div class="card-body" id="printThis">
                    <div class="cert-page">

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

                        
                        </div>

                        <!-- =====================
                             Title
                        ===================== -->
                        <h2 class="cert-title">CERTIFICATION</h2>

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

                                    <!-- TO WHOM IT MAY CONCERN -->
                                    <p style="margin-top: 10px; font-size:16px;">
                                        <span>TO WHOM IT MAY CONCERN:</span>
                                    </p>

                                    <!-- Paragraph 1: Certification body -->
                                    <p class="text-indent">
                                       THIS IS TO CERTIFIED that
                                       *name of the child*
                                       born on *age*at *Place of Birth*, Filipino, *SON/DAUGHTER* of  <span class="bold"><?= strtoupper($residentFullName) ?></span>
                                       is a resident with postal address at *purok/address*
                                        <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?>.</span>
                                       
                                    </p>

                                    <!-- Paragraph 2: Purpose -->
                                    <p class="text-indent">
                                        This Certification is issued upon request of *MS/MR* <span class="bold"><?= strtoupper($residentFullName) ?></span>
                                        for REGISTRATION OF BIRTH CERTIFICATE of *her/his son/daughter*.
                                    </p>

                                    <!-- Paragraph 3: Given this -->
                                    <p class="text-indent">
                                       <strong> Issued this
                                        <span class="underline-val"><?= $dayOrdinal ?></span>
                                        <strong>day</strong> of
                                        <span class="underline-val"><?= $monthName ?></span>,
                                        <span class="underline-val"><?= $yearNum ?></span></strong>
                                        at the Office of the Punong Barangay of
                                         <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?>.</span>
                                    </p>

                                        <div class="sig-right">
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
            
    </script>
</body>
</html>
