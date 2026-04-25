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
$witness_name = isset($_GET['witness'])    ? trim($_GET['witness'])       : '';

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
<link rel="icon" type="image/png" href="../uploads/favicon.png"> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outh of Undertaking- <?= htmlspecialchars($brgy) ?></title>

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
            font-size: 19px;
         
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
            font-family: arial, sans-serif;
            margin: 40px 0 20px;
            letter-spacing: 1px;
        }

        .cert-title h2 {
             font-weight: 700;
             font-size: 20px;
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

        .cert-body-content .numbered-items {
            padding-left: 20px;
            margin-left: 0;
        }

        .cert-body-content .numbered-items .item {
            display: block;
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
      .signatures-container {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-top: 40px; /* Adjust spacing above the section */
            font-family: Arial, sans-serif; /* Use your document's font */
        }

        .sig-left, .sig-right {
            width: 45%; /* Gives both columns equal width with a gap in the middle */
        }

        .sig-label {
            margin-bottom: 10px;
            font-size: 14px;
           font-style: italic;
        }

        /* Creates the vertical gap where they will physically sign */
        .signature-space {
            height: 10px; 
        }

        /* Creates a slightly smaller gap between the two witnesses on the right */
        .signature-space-small {
            height: 20px;
        }

        .sig-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
        }

        .sig-title {
            font-size: 14px;
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
                padding: 15px 50px 20px 50px;
            }


            .cert-header .logo-img {
                width: 110px;
                height: 110px;
            }

            .cert-header .header-center .brgy-name {
                font-size: 16px;
            }

            .cert-title {
                font-size: 18px;
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
                    <div class="fw-bold">Outh of Undertaking</div>
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

                         <?php if (!empty($government_logo)): ?>
                                <img src="<?= htmlspecialchars($government_logo) ?>" class="logo-img" alt="Bagong Pilipinas Logo">
                            <?php else: ?>
                                <div class="logo-placeholder-box"></div>
                            <?php endif; ?>
                        </div>
                            <span style="font-size: 11px; font-style: italic; float:right;">Revised as of 16 June 2021</span>

                        <!-- =====================
                             Title
                        ===================== -->
                        <div class="cert-title">
                        <h2>OUTH OF UNDERTAKING</h2>
                        <p style="font-size:13px;  font-style: italic;">Republic Act 11261 - First-Time Jobseeker Assistance Act</p>
                        </div>

                        <!-- =====================
                             Content Area
                        ===================== -->
                        <div class="cert-content-area">

                            <!-- Certificate Body -->
                            <div class="cert-body">

                                <div class="cert-body-content">

                                    <!-- Paragraph 1: Certification body -->
                                    <p class="text-indent">
                                         I, <span class="bold"><?= strtoupper($residentFullName) ?></span>,
                                        <strong><?= ucwords($resident['age']) ?></strong> years old, 
                                        a resident of   
                                        <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?></span> 
                                        <strong>SINCE BIRTH</strong> availing the benefits of the Republic Act 11261 otherwise known as the <strong>First Time Jobseeker Assistance Act Of 2019</strong>,
                                        do hereby declare agree and undertake to abide and be bound by the following:
                                    </p>

                                    <!-- Paragraph 2: Purpose -->
                                    <p class="numbered-items">
                                        <span class="item">1. That this is the first time that I will actively look for a job, and therefore requesting that a Barangay Certification be issued in my favor to avail the benefits of the law;</span>
                                        <span class="item">2. That I am aware that the benefit and privilege/s under the said law shall be valid only for one (1) year from the date that the Barangay Certification is issued;</span>
                                        <span class="item">3. That I can avail the benefits of the law only once;</span>
                                        <span class="item">4. That I understand that my personal information shall be included in the Roster/List of First Time Jobseekers and will not be used for any unlawful purpose;</span>
                                        <span class="item">5. That I will inform and/or report to the Barangay personally, through text or other means, or through my family/relatives once I get employed;</span>
                                        <span class="item">6. That I am not a beneficiary of the Jobstart Program under R.A. No. 10869 and other laws that give similar exemptions for the documents of transactions exempted under R.A. No. 11261;</span>
                                        <span class="item">7. That if issued the requested Certification, I will not use the same in any fraud, neither falsify nor help and/or assist in the fabrication of the said certification;</span>
                                        <span class="item">8. That the undertaking is made solely for the purpose of obtaining a Barangay Certification consistent with the objectives of R.A. No. 11261 and not for any other purpose; and</span>
                                        <span class="item">9. That I consent to the use of my personal information pursuant to the Data Privacy Act and other applicable laws, rules and regulations.</span>
                                    </p>

                                    <!-- Paragraph 3: Given this -->
                                    <p class="text-indent">
                                       Signed this 
                                        <span class="underline-val"><?= $dayOrdinal ?></span>
                                        <span>day</span> of
                                        <span class="underline-val"><?= $monthName ?></span>,
                                        <span class="underline-val"><?= $yearNum ?></span>
                                     in
                                         <span><?= ucwords($brgy) ?></span>,
                                        <span><?= ucwords($town) ?></span>,
                                        <span><?= ucwords($province) ?>.</span>
                                    </p>

                                   
                  <!-- Signatures -->
                                    
                                    <div class="signatures-container">
    
                                    <div class="sig-left">
                                        <div class="sig-label">Signed by:</div>
                                        
                                        <div class="signature-space"></div> 
                                        
                                        <div class="sig-name"><?= strtoupper($residentFullName) ?></div>
                                        <div class="sig-title">First Time Jobseeker</div>
                                    </div>
                                    
                                    <div class="sig-right">
                                        <div class="sig-label">Witnessed by:</div>
                                        
                                        <div class="signature-space"></div>
                                        
                                        <?php if (!empty($captain)): ?>
                                        <div class="sig-name">HON. <?= strtoupper($captain['name']) ?></div>
                                        <div class="sig-title">Punong Barangay</div>
                                        <?php endif; ?>
                                        
                                        <div class="signature-space-small"></div>
                                        
                                        <?php if (!empty($witness_name)): ?>
                                            <div class="sig-name">HON. <?= strtoupper(htmlspecialchars($witness_name)) ?></div>
                                        <div class="sig-title">Barangay Kagawad</div>
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
            formData.append('certificate_type', 'Certificate of Oath of Undertaking');
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
