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
        SELECT id, first_name, middle_name, last_name, suffix, civil_status, 
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
            'civil_status' => $row['civil_status'],
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
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>First Time Jobseeker Assistance Act - <?= htmlspecialchars($brgy) ?></title>

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
            border-bottom: 3px double #c40d0d;
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
            font-size: 15px;
            font-family: 'Times New Roman', Times, serif;
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
            font-weight: 800;
            font-family: arial, sans-serif;
            margin: 40px 0 20px;
            letter-spacing: 1px;
        }

        .cert-title h2 {
             font-weight: 800;
             letter-spacing: -1px;
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
            margin-left: 300px;
            margin-top: 50px;
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
                font-size: 15px;
            }

            .cert-title {
                font-size: 24px;
            }

            .cert-body-content p {
                font-size: 16px;
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
                    <div class="fw-bold">Barangay Certification (First-Time Jobseeker Assistance Act - RA 11261)</div>
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
                                <p>Municipality of <?= ucwords($town) ?></p>
                                <p style="letter-spacing: 2px;"><?= strtoupper($province) ?></p>
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
                        <div class="cert-title">
                        <h2>BARANGAY CERTIFICATION</h2>
                        <p style="font-size:13px;">(First-Time Jobseeker Assistance Act - RA 11261)</p>
                        </div>

                        <!-- =====================
                             Content Area
                        ===================== -->
                        <div class="cert-content-area">

                            <!-- Certificate Body -->
                            <div class="cert-body">
                                

                                <div class="cert-body-content">

                                    <!-- TO WHOM IT MAY CONCERN -->
                                    <p style="margin-top: 10px; margin-bottom: 30px; font-size:16px;">
                                        <span>TO WHOM IT MAY CONCERN:</span>
                                    </p>

                                    <!-- Paragraph 1: Certification body -->
                                    <p class="text-indent">
                                        THIS IS TO CERTIFY that
                                        <span class="bold"><?= strtoupper($residentFullName) ?></span>,
                                        <span><?= ucwords($resident['age']) ?></span> years old, 
                                        a resident of   
                                        <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?>.</span> 
                                        for SINCE BIRTH, is a qualified availee of RA 11261 or the <i>First Time Jobseeker Assistance Act 2019</i>;
                                    </p>

                                    <!-- Paragraph 2: Purpose -->
                                    <p class="text-indent">
                                        This further certifies that the holder/bearer was informed of her/his rights, including the duties and responsibilities
                                        accorded by RA 11261 throught the Oath of Undertaking he/she has signed and executed in presence of Barangay Official.
                                    </p>

                                    <!-- Paragraph 3: Given this -->
                                    <p class="text-indent">
                                        <i><strong>Signed this 
                                        <span class="underline-val"><?= $dayOrdinal ?></span>
                                        <span>day</span> of
                                        <span class="underline-val"><?= $monthName ?></span>,
                                        <span class="underline-val"><?= $yearNum ?></span></strong></i>
                                        at the Office of the Punong Barangay of
                                         <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?>.</span>
                                    </p>

                                    <!-- Paragraph 4: Validity -->
                                    <p class="text-indent">
                                        This Certification is valid for one (1) month from date of issuance.
                                    </p>
                  <!-- Signatures -->

                                    
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

        function saveAndPrint() {
            const formData = new FormData();
            formData.append('resident_id', '<?php echo $resident_id; ?>');
            formData.append('certificate_type', 'Certificate of Job Seeker Assistance');
            formData.append('purpose', 'First-Time Jobseeker Assistance (RA 11261)');

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
