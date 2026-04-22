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
$business_name = isset($_GET['business_name']) ? trim($_GET['business_name']) : '';
$business_address = isset($_GET['business_address']) ? trim($_GET['business_address']) : '';
$nature = isset($_GET['nature']) ? trim($_GET['nature']) : '';

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
$government_logo = '';
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
        $government_logo = $bi['official_emblem'] ?? '';

        // Fix paths for subdirectory
        if (!empty($brgy_logo)) {
            $brgy_logo = '../' . $brgy_logo;
        }
        if (!empty($government_logo)) {
            $government_logo = '../' . $government_logo;
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
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Business Clearance - <?= htmlspecialchars($brgy) ?></title>

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
        left: 50px;
        top: 30px;
        font-style: italic;
        font-weight: bold;
        font-size: 50px;
        color: #4292d3;
        letter-spacing: 10px;
        user-select: none;
        /* Rotation properties */
        transform: rotate(90deg);
        transform-origin: left top; /* Keeps the text anchored to your top/left coordinates */
        white-space: nowrap; /* Ensures the text stays on one line */
        }

        /* ===========================
           Certificate Header
        =========================== */
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            margin: 20px 0 30px 100px;
            font-family: 'Arial Narrow', Arial, sans-serif;
            font-weight: bold;
            font-size: 48px;
            color: #4a8ddc; /* Blue color matching the image */
            user-select: none;
            text-transform: uppercase;
        }

        /* ===========================
           Content Area (sidebar + body)
        =========================== */
        .cert-content-area {
            display: flex;
            gap: 15px;
            align-items: stretch; /* Makes sidebar and body same height */
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
            top: 38%;
            left: 57%;
            transform: translate(-50%, -50%);
            opacity: 0.1;
            width: 280px;
            height: auto;
            z-index: 0;
            pointer-events: none;
            user-select: none;
        }

       /* ===========================
           Certificate Body (Updated for Centering)
        =========================== */
        .cert-body-content {
            position: relative;
            z-index: 1;
            text-align: center; /* Center everything by default */
        }

        .cert-body-content p {
            font-size: 12px;
            text-align: center; /* Override justify */
            margin-bottom: 15px;
            line-height: 1.5;
            font-family: Arial, Helvetica, sans-serif; /* Image uses a standard sans-serif like Arial */
        }

        /* Wide Underline for Names */
        .underline-wide {
            display: inline-block;
            min-width: 350px;
            border-bottom: 1px solid #000;
            font-weight: bold;
            font-size: 14px;
            padding-bottom: 2px;
            margin-bottom: 15px;
            text-transform: uppercase;
            margin-left: 100px;
        }

        .underline-val {
            font-weight: bold;
            text-decoration: underline;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Paragraphs at the bottom */
       .cert-body-content p.terms-text {
            font-size: 12px;
          
        }

        .cert-body-content .text-indent {
            text-indent: 40px;
        }

        .sig-right {
            float: right;
            text-align: center;
            margin-right: 50px;
            margin-top: 30px;
        }

        .sig-captain-name {
            font-family: 'Times New Roman', Times, serif; /* Image uses serif for name */
            font-weight: bold;
            font-size: 18px;
            text-transform: uppercase;
        }

        .sig-captain-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 16px;
        }
        
        /* Large Year Stamp */
        .year-stamp {
            font-size: 70px;
            font-weight: 900;
            color: #4292d3;
            margin: 10px 0 -5px 40px;
            font-family: 'Arial Black', sans-serif;

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
                left: 95px;
                top: 45px;
                font-style: italic;
                font-weight: bold;
                font-size: 70px;
                font-weight: 900;
                color: #3b89e2;
                letter-spacing: 2px;
                user-select: none;
                
                /* Rotation properties */
                transform: rotate(90deg);
                transform-origin: left top; /* Keeps the text anchored to your top/left coordinates */
                white-space: nowrap; /* Ensures the text stays on one line */
            }

            .cert-header .logo-img {
                width: 110px;
                height: 110px;
            }

            .cert-header .header-center .brgy-name {
                font-size: 22px;
            }

            .cert-title {
               letter-spacing: -4.5px;
               font-size: 45px;
               font-style: italic;
               
                font-weight: 600;
            }

            .cert-body-content p {
                font-size: 14px;
                margin-left: 100px;
            }

            .cert-watermark {
                width: 500px;
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
                    <div class="fw-bold">Barangay Clearance</div>
                </div>

                <div class="card-body" id="printThis">
                    <div class="cert-page">

                        <!-- =====================
                             Vertical Side Text
                        ===================== -->
                        <div class="vertical-text">
                           BARANGAY WAWANDUE
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

                            <?php if (!empty($government_logo)): ?>
                                <img src="<?= htmlspecialchars($government_logo) ?>" class="logo-img" alt="Bagong Pilipinas Logo">
                            <?php else: ?>
                                <div class="logo-placeholder-box"></div>
                            <?php endif; ?>
                        </div>

                        <!-- =====================
                             Title
                        ===================== -->
                        <h2 class="cert-title">BARANGAY BUSINESS CLEARANCE</h2>

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
    
                                        <p>is hereby granted to</p>

                                        <div>
                                            <span class="underline-wide"><?= htmlspecialchars($residentFullName) ?></span>
                                        </div>

                                        <p>
                                            a resident of <strong><?= strtoupper($brgy) ?>, <?= strtoupper($town) ?>, <?= strtoupper($province) ?></strong> Filipino Citizen,
                                        </p>

                                        <p>
                                            single, married/widow/er to engage/operate a <span class="underline-val">"<?= strtoupper($nature) ?>"</span>
                                        </p>

                                        <p>
                                            located at <strong><?= ucwords($brgy) ?>, <?= ucwords($town) ?>, <?= ucwords($province) ?></strong> within the jurisdiction of this barangay
                                        </p>

                                        <p style="margin-top: 25px;">under the business name:</p>

                                        <div style="margin-top: 20px; ">
                                            <span class="underline-wide">"<?= strtoupper($business_name) ?>"</span>
                                        </div>

                                        <p style="font-weight: bold;">
                                            Valid until DEC 31, 2026
                                        </p>

                                        <p class="terms-text">
                                            The <strong>GRANTEE</strong> of this permit shall secure and comply with all existing General Orders, Municipal and Barangay Ordinance, Rules and Regulations Governing the operation of such activity.
                                        </p>

                                        <p class="terms-text">
                                            This <strong>CLEARANCE</strong> is not valid without corresponding receipts of license fees and is subject to revocation of non-compliance with all the existing laws mentioned above.
                                        </p>

                                        <p class="terms-text">
                                            Issued <strong><?= strtoupper($dayOrdinal) ?></strong> day of <strong><?= strtoupper($monthName) ?>, <?= $yearNum ?></strong> at Barangay <?= ucwords($brgy) ?> <?= ucwords($town) ?>, <?= ucwords($province) ?>
                                        </p>

                                        <div class="sig-right">
                                            <?php if (!empty($captain)): ?>
                                                <div class="sig-captain-name"><?= strtoupper($captain['name']) ?></div>
                                                <div class="sig-captain-title">Punong Barangay</div>
                                            <?php endif; ?>
                                        </div>
                                        <div style="clear: both;"></div>

                                    </div>
                                  
                                    <!-- /Signatures -->
                                    <div class="year-stamp"><?= $yearNum ?></div>

                                    
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
            formData.append('certificate_type', 'Barangay Business Clearance');
            formData.append('purpose', '<?php echo !empty($nature) ? htmlspecialchars($nature) : "Barangay Business Clearance"; ?>');

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
            
            // Redirect after printing
            window.onafterprint = function() {
                window.location.href = '../certificates.php';
            };
        }
            
    </script>
</body>
</html>
