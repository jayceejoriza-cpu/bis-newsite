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
if ($resident_id <= 0) {
    die("Invalid resident ID.");
}

// ============================================
// Fetch Barangay Info
// ============================================
$brgy_logo = '';
$sk_logo = '';
$province  = 'Province';
$town      = 'Municipality';
$brgy      = 'Barangay';

try {
    $biStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $bi = $biStmt->fetch();
    if ($bi) {
        $province  = $bi['province_name']  ?? $province;
        $town      = $bi['town_name']      ?? $town;
        $brgy      = $bi['barangay_name']  ?? $brgy;
        $brgy_logo = !empty($bi['barangay_logo']) ? '../' . $bi['barangay_logo'] : '';
        $sk_logo = !empty($bi['sk_logo']) ? '../' . $bi['sk_logo'] : '';
    }
} catch (PDOException $e) {
    error_log("Error fetching barangay info: " . $e->getMessage());
}

// ============================================
// Fetch Resident Data
// ============================================
$resident = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ? LIMIT 1");
    $stmt->execute([$resident_id]);
    $resident = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Error fetching resident: " . $e->getMessage());
}

if (!$resident) {
    die("Resident not found.");
}

// Prepare variables for ID
$residentIdNo = $resident['resident_id'] ?? 'W-00000';
$precinctNo   = $resident['precinct_number'] ?? 'N/A';

$lastName     = $resident['last_name'] ?? '';
$firstName    = $resident['first_name'] ?? '';
$middleName   = $resident['middle_name'] ?? '';
$mi           = !empty($middleName) ? substr(trim($middleName), 0, 1) . '.' : '';
$fullName     = strtoupper("$lastName, $firstName $mi");

$dob          = !empty($resident['date_of_birth']) ? date('m/d/Y', strtotime($resident['date_of_birth'])) : 'N/A';
$civilStatus  = strtoupper($resident['civil_status'] ?? 'SINGLE');
$sex          = strtoupper(substr($resident['sex'] ?? 'M', 0, 1));
$address      = $resident['current_address'] ?? '';
$photo        = !empty($resident['photo']) ? '../' . $resident['photo'] : '';
$dateExpiry   = date('m/d/Y', strtotime('+1 year')); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="../uploads/favicon.png"> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay ID Card - <?= htmlspecialchars($fullName) ?></title>

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

        /* Standard ID Card Size: 3.375" x 2.125" (86mm x 54mm) */
        .id-card {
            width: 86mm;
            height: 54mm;
            background-color: #ffffff;
            border: 3px solid #9D64FF; /* Purple Border */
            box-sizing: border-box;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            transform: scale(2); /* Enlarges the preview for easier reading on screen */
            transform-origin: top center;
        }

        #printThis {
            padding-top: 40px;
            padding-bottom: 110mm; /* Creates space for the scaled ID card in the layout */
            display: flex;
            justify-content: center;
        }

        /* --- Header Section --- */
        .id-header {
            background-color: #9D64FF;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2mm 3mm;
            height: 16mm;
            box-sizing: border-box;
        }

        .logo {
            width: 12mm;
            height: 12mm;
            border-radius: 50%;
            object-fit: contain;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 3.5pt;
            color: #000;
            line-height: 1.1;
        }

        .header-text {
            text-align: center;
            flex-grow: 1;
            font-size: 5pt;
            line-height: 1.5;
        }

        .header-text .brgy-title {
            font-size: 6pt;
            text-transform: uppercase;
            margin-top: 1mm;
            letter-spacing: 0.5px;
            font-weight: bold;
        }

        /* --- Body Section --- */
        .id-title {
            text-align: center;
            font-size: 8pt;
            letter-spacing: 0.5px;
            color: #1a1a1a;
            margin: 1.5mm 0;
            font-family: "Times New Roman", Georgia, serif ;
            font-weight: bold;
        }

        .id-body {
            display: flex;
            padding: 0 2.5mm;
            gap: 2.5mm;
            flex-grow: 1;
        }

        /* Left Column: Photo & Date */
        .col-left {
            width: 22mm;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo-container {
            width: 22mm;
            height: 22mm;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-align: center;
            font-size: 6pt;
            line-height: 1.2;
            overflow: hidden;
            border-radius: 2px;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

      .date-expiry {
            margin-top: 1mm;
            text-align: center;
            font-size: 4.5pt;
            font-style: italic;
            color: #333;
        }

     .date-expiry span {
            display: block;
            font-size: 5.5pt;
            font-weight: bold;
            font-style: italic;
            color: #000;
        }

        /* Right Column: Information */
        .col-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: .5mm;
        }

        .data-row {
            display: flex;
            gap: 1.5mm;
        }

        .data-group {
            display: flex;
            flex-direction: column;
        }

        .w-50 { width: 50%; }
        .w-100 { width: 100%; }
        .w-33 { width: 33.33%; }

        .label {
            background-color: #D9D9D9;
            font-size: 4.5pt;
            font-style: italic;
            color: #1a1a1a;
            padding: 0.3mm 1mm;
        }

        .value {
            font-size: 5.5pt;
            font-weight: bold;
            color: #000;
            padding: 0.3mm 1mm;
            text-transform: uppercase;
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

            #printThis { padding: 0 !important; display: block !important; }

            .id-card {
                transform: none !important; /* Reset scale for actual printing */
                box-shadow: none;
                margin: 0 !important;
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }

            @page {
                size: 86mm 54mm;
                margin: 0;
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
                Print ID
            </button>
        </div>

        <!-- Certificate Content -->
        <div class="dashboard-content">
            <div class="card no-print">
                <div class="card-header">
                    <div class="fw-bold">Preview Barangay Residence ID</div>
                </div>
            </div>

            <div id="printThis">
                <div class="id-card">
                    <div class="id-header">
                        <?php if (!empty($brgy_logo)): ?>
                            <img src="<?= htmlspecialchars($brgy_logo) ?>" class="logo" alt="Barangay Logo">
                        <?php else: ?>
                            <div class="logo">BARANGAY<br>LOGO</div>
                        <?php endif; ?>
                  

                        <div class="header-text">
                            Republic of the Philippines<br>
                            Province of <?= htmlspecialchars($province) ?><br>
                            Municipality of <?= htmlspecialchars($town) ?><br>
                            <div class="brgy-title"> <?= htmlspecialchars($brgy) ?></div>
                        </div>

                        <?php if (!empty($sk_logo)): ?>
                            <img src="<?= htmlspecialchars($sk_logo) ?>" class="logo" alt="Municipal Logo">
                        <?php else: ?>
                            <div class="logo">SK<br>LOGO</div>
                        <?php endif; ?>
  </div>
                    <div class="id-title">BARANGAY RESIDENCE ID</div>

                    <div class="id-body">
                        
                        <div class="col-left">
                            <div class="photo-container">
                                <?php if ($photo): ?>
                                    <img src="<?= htmlspecialchars($photo) ?>" alt="Resident Photo">
                                <?php else: ?>
                                    RESIDENT<br>PHOTO
                                <?php endif; ?>
                            </div>
                            <div class="date-expiry">
                                Valid Until:
                                <span><?= htmlspecialchars($dateExpiry) ?></span>
                            </div>
                        </div>

                        <div class="col-right">
                            
                            <div class="data-row">
                                <div class="data-group w-50">
                                    <div class="label">Resident Id No.</div>
                                    <div class="value"><?= htmlspecialchars($residentIdNo) ?></div>
                                </div>
                                <div class="data-group w-50">
                                    <div class="label">Precint No.</div>
                                    <div class="value"><?= htmlspecialchars($precinctNo) ?></div>
                                </div>
                            </div>

                            <div class="data-row">
                                <div class="data-group w-100">
                                    <div class="label">Last Name, First Name, MI.</div>
                                    <div class="value" style="font-size: 6pt;"><?= htmlspecialchars($fullName) ?></div>
                                </div>
                            </div>

                            <div class="data-row">
                                <div class="data-group w-33">
                                    <div class="label">Date of Birth</div>
                                    <div class="value"><?= htmlspecialchars($dob) ?></div>
                                </div>
                                <div class="data-group w-33">
                                    <div class="label">Civil Status</div>
                                    <div class="value"><?= htmlspecialchars($civilStatus) ?></div>
                                </div>
                                <div class="data-group w-33">
                                    <div class="label">Sex</div>
                                    <div class="value"><?= htmlspecialchars($sex) ?></div>
                                </div>
                            </div>

                            <div class="data-row">
                                <div class="data-group w-100">
                                    <div class="label">Address:</div>
                                    <div class="value"><?= htmlspecialchars($address) ?></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
                      
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
            formData.append('certificate_type', 'Barangay ID Card');
            formData.append('purpose', 'Identification');

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
                window.location.href = '../residents.php';
            };
        }
            
    </script>
</body>
</html>
