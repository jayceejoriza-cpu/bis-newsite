<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

// Enforce print permission
requirePermission('perm_blotter_print');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid Record ID.");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 1. Fetch Barangay Info for Header
    $infoStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $brgy = $infoStmt->fetch();

    // 2. Fetch Captain and Secretary for Signatures
    $officialsStmt = $pdo->query("SELECT fullname, position FROM barangay_officials WHERE position IN ('Barangay Captain', 'Barangay Secretary') AND status = 'Active'");
    $staff = $officialsStmt->fetchAll();
    $captain = 'BARANGAY CAPTAIN';
    $secretary = 'BARANGAY SECRETARY';

    foreach ($staff as $s) {
        if ($s['position'] === 'Barangay Captain') $captain = $s['fullname'];
        if ($s['position'] === 'Barangay Secretary') $secretary = $s['fullname'];
    }

    // 3. Fetch Blotter Details with GROUP_CONCAT for Parties
    $stmt = $pdo->prepare("
        SELECT 
            br.record_number, 
            br.incident_type,
            GROUP_CONCAT(DISTINCT CASE WHEN bc.statement = 'COMPLAINANT' OR bc.statement IS NULL OR bc.statement = '' THEN bc.name END SEPARATOR ', ') AS complainants,
            GROUP_CONCAT(DISTINCT brd.name SEPARATOR ', ') AS respondents
        FROM blotter_records br
        LEFT JOIN blotter_complainants bc ON br.id = bc.blotter_id
        LEFT JOIN blotter_respondents brd ON br.id = brd.blotter_id
        WHERE br.id = ?
        GROUP BY br.id
    ");
    $stmt->execute([$id]);
    $record = $stmt->fetch();

    if (!$record) {
        die("Record not found.");
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print CFA - <?php echo $record['record_number']; ?></title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* Preview Interface Styles */
        .preview-container {
            background-color: #f8f9fa; /* bg-light */
            min-height: 100vh;
            padding-bottom: 50px;
        }
        
        .print-toolbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .document-wrapper {
            display: flex;
            justify-content: center;
            padding: 0 15px;
        }

        .print-paper {
            background-color: #fff;
            width: 8.5in; /* 8.5x11 Letter Width */
            min-height: 11in;
            padding: 1in;
            box-shadow: 0 0 15px rgba(0,0,0,0.1); /* shadow-sm effect */
            font-family: "Times New Roman", Times, serif;
            line-height: 1.6;
            color: #000;
            margin: 0 auto;
        }


        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-bottom: 2px solid black;
            padding-bottom: 10px;
        }
        .logo-col { width: 100px; text-align: center; }
        .logo-img { width: 80px; height: 80px; object-fit: contain; }
        .header-text { text-align: center; }
        .header-text p { margin: 0; font-size: 12pt; }
        .office-title { font-weight: bold; font-size: 13pt; margin-top: 5px !important; }
        
        .case-info { margin-top: 30px; font-size: 12pt; }
        .case-row { display: flex; margin-bottom: 5px; }
        .case-label { width: 150px; font-weight: bold; }
        
        .document-title {
            text-align: center;
            text-decoration: underline;
            font-size: 18pt;
            font-weight: bold;
            margin: 50px 0;
            text-transform: uppercase;
        }

        .body-text {
            text-align: justify;
            text-indent: 50px;
            font-size: 13pt;
            margin-bottom: 20px;
        }

        .parties-box {
            margin: 30px 0;
            font-size: 12pt;
        }
        .vs-text { font-style: italic; margin: 10px 0; font-weight: bold; }

        .date-section { margin-top: 40px; font-size: 12pt; text-align: right; }

        .signature-section {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        .sig-block { width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid black; margin-top: 40px; font-weight: bold; text-transform: uppercase; padding-top: 5px; }
        .sig-title { font-size: 10pt; font-style: italic; }

        .footer-stamp {
            position: fixed;
            bottom: 20px;
            left: 40px;
            font-size: 8pt;
            color: #667085;
            font-style: italic;
        }

        @media print {
            /* Hide UI elements during print */
            .sidebar, .header, .print-toolbar, .no-print { 
                display: none !important; 
            }
            
            .main-content { 
                margin: 0 !important; 
                padding: 0 !important; 
                width: 100% !important; 
            }
            
            .dashboard-content {
                padding: 0 !important;
                margin: 0 !important;
            }

            .preview-container {
                background: none !important;
                padding: 0 !important;
            }

            .document-wrapper {
                padding: 0 !important;
                display: block !important;
            }

            .print-paper {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                border: none !important;
            }

            body { 
                background: white !important;
                padding: 0; 
            }
            
            @page { size: letter; margin: 20mm; }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="dashboard-content p-0">
            <div class="preview-container">
                <!-- Top Toolbar -->
                <div class="print-toolbar no-print">
                    <h4 class="m-0 font-bold text-gray-800">Certificate to File Action</h4>
                    <button onclick="window.print()" class="btn btn-primary px-4 py-2 flex items-center gap-2">
                        <i class="fas fa-print"></i> Print Certificate
                    </button>
                </div>

                <!-- Document Wrapper -->
                <div class="document-wrapper">
                    <div class="print-paper">
                        <!-- Header -->
                        <table class="header-table">
                            <tr>
                                <td class="logo-col">
                                    <img src="<?php echo $brgy['barangay_logo'] ?: 'assets/image/brgylogo.jpg'; ?>" class="logo-img">
                                </td>
                                <td class="header-text">
                                    <p>Republic of the Philippines</p>
                                    <p>Province of <?php echo htmlspecialchars($brgy['province_name']); ?></p>
                                    <p>Municipality of <?php echo htmlspecialchars($brgy['town_name']); ?></p>
                                    <p class="office-title">Office of the Sangguniang Barangay of <?php echo htmlspecialchars($brgy['barangay_name']); ?></p>
                                    <p class="office-title" style="font-size: 14pt;">OFFICE OF THE LUPONG TAGAPAMAYAPA</p>
                                </td>
                                <td class="logo-col">
                                    <img src="<?php echo $brgy['municipal_logo'] ?: 'assets/image/citylogo.png'; ?>" class="logo-img">
                                </td>
                            </tr>
                        </table>

                        <!-- Case Information -->
                        <div class="case-info">
                            <div class="case-row"><span class="case-label">CASE NO:</span> <span><?php echo htmlspecialchars($record['record_number']); ?></span></div>
                            <div class="case-row"><span class="case-label">FOR:</span> <span><?php echo strtoupper(htmlspecialchars($record['incident_type'])); ?></span></div>
                        </div>

                        <div class="parties-box">
                            <div style="font-weight: bold;"><?php echo strtoupper(htmlspecialchars($record['complainants'])); ?></div>
                            <div class="vs-text">- against -</div>
                            <div style="font-weight: bold;"><?php echo strtoupper(htmlspecialchars($record['respondents'])); ?></div>
                        </div>

                        <!-- Title -->
                        <h1 class="document-title">CERTIFICATE TO FILE ACTION</h1>

                        <!-- Body Content -->
                        <div class="body-text">
                            This is to certify that the above-captioned case was brought before the Office of the Lupong Tagapamayapa for mediation and conciliation. However, despite earnest efforts exerted by the Lupon, no voluntary settlement was reached between the parties involved.
                        </div>

                        <div class="body-text">
                            Therefore, pursuant to the provisions of the Katarungang Pambarangay Law (RA 7160), the corresponding complaint for the above-entitled case may now be filed in the <strong>Subic Police Station</strong> or the proper courts of justice.
                        </div>

                        <!-- Date -->
                        <div class="date-section">
                            Issued this <?php echo date('jS'); ?> day of <?php echo date('F, Y'); ?>.
                        </div>

                        <!-- Signatures -->
                        <div class="signature-section">
                            <div class="sig-block">
                                <div class="sig-line"><?php echo htmlspecialchars($secretary); ?></div>
                                <div class="sig-title">Barangay Secretary</div>
                            </div>
                            <div class="sig-block">
                                <div class="sig-line"><?php echo htmlspecialchars($captain); ?></div>
                                <div class="sig-title">Punong Barangay / Lupon Chairman</div>
                            </div>
                        </div>

                        <!-- Footer Stamp -->
                        <div class="footer-stamp">
                            This is a system-generated document from the Barangay Management System.<br>
                            Printed on: <?php echo date('M d, Y | h:i A'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- External JS -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
</body>
</html>