<?php
require_once 'config.php';
require_once 'auth_check.php';

// Initialize Database Connection using PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Fetch Barangay Info
$brgy_logo = '';
$municipal_logo = '';
$province  = 'Zambales';
$town      = 'Subic';
$brgy      = 'Wawandue';

try {
    $biStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $bi = $biStmt->fetch();
    if ($bi) {
        $province  = $bi['province_name']  ?? $province;
        $town      = $bi['town_name']      ?? $town;
        $brgy      = $bi['barangay_name']  ?? $brgy;
        $brgy_logo = $bi['barangay_logo']  ?? '';
        $municipal_logo = $bi['municipal_logo'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Error fetching barangay info: " . $e->getMessage());
}

// Fetch Resident Data if a resident_id is passed in the URL (e.g., id-sample.php?resident_id=1)
$resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : 0;
$resident = null;

if ($resident_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ? LIMIT 1");
        $stmt->execute([$resident_id]);
        $resident = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching resident: " . $e->getMessage());
    }
}

// Define variables to match the new ID design layout
$residentIdNo = $resident['resident_id'] ?? 'WW-00001';
$precinctNo   = $resident['precinct_no'] ?? 'WW-00001';

// Format Name: Last Name, First Name, MI.
$lastName     = $resident['last_name'] ?? 'CONSTANTINO';
$firstName    = $resident['first_name'] ?? 'ALVINO';
$middleName   = $resident['middle_name'] ?? 'C';
$mi           = !empty($middleName) ? substr(trim($middleName), 0, 1) . '.' : '';
$fullName     = strtoupper("$lastName, $firstName $mi");

$dob          = !empty($resident['date_of_birth']) ? date('m/d/Y', strtotime($resident['date_of_birth'])) : '07/24/1994';
$civilStatus  = $resident['civil_status'] ?? 'SINGLE';
$sex          = $resident['sex'] ?? 'M';
$address      = $resident['current_address'] ?? 'Purok 2, Barangay Sample';
$photo        = !empty($resident['photo']) ? $resident['photo'] : '';

// Calculate Issue/Validity Date
$dateIssued   = isset($resident['date_issued']) ? date('m/d/Y', strtotime($resident['date_issued'])) : '07/20/2027'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Barangay ID Card</title>
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Standard ID Card Size: 3.375" x 2.125" (Scaled up slightly for preview) */
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
            object-fit: cover;
       
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
            line-height: 1.2;
        }

        .header-text .brgy-title {
            font-size: 6pt;
            text-transform: uppercase;
            margin-top: 1mm;
            letter-spacing: 0.5px;
        }

        /* --- Body Section --- */
        .id-title {
            text-align: center;
            font-size: 8pt;
            letter-spacing: 0.5px;
            color: #1a1a1a;
            margin: 1.5mm 0;
            font-family: "Times New Roman", Georgia, serif ;
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
            background-color: #808080;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            font-size: 6pt;
            line-height: 1.2;
            overflow: hidden;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .date-issued {
            margin-top: 1.5mm;
            text-align: center;
            font-size: 4.5pt;
            font-style: italic;
            color: #333;
        }

        .date-issued span {
            display: block;
            font-size: 5.5pt;
            font-weight: bold;
            font-style: italic;
            margin-top: 0.5mm;
            color: #000;
        }

        /* Right Column: Information */
        .col-right {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5mm;
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

        /* --- Print Specific Styles --- */
        @media print {
            body { 
                background: none; 
                display: block; 
                padding: 0; 
                margin: 0;
            }
            .id-card { 
                box-shadow: none;
                margin: 0;
                /* Ensures colors print correctly in Chrome/Edge */
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

    <div class="id-card">
        <div class="id-header">
            <?php if (!empty($municipal_logo)): ?>
                <img src="<?= htmlspecialchars($municipal_logo) ?>" class="logo" alt="Municipal Logo">
            <?php else: ?>
                <div class="logo">MUNICIPAL<br>LOGO</div>
            <?php endif; ?>

            <div class="header-text">
                Republic of the Philippines<br>
                Province of <?= htmlspecialchars($province) ?><br>
                Municipality of <?= htmlspecialchars($town) ?><br>
                <div class="brgy-title"> <?= htmlspecialchars($brgy) ?></div>
            </div>

            <?php if (!empty($brgy_logo)): ?>
                <img src="<?= htmlspecialchars($brgy_logo) ?>" class="logo" alt="Barangay Logo">
            <?php else: ?>
                <div class="logo">BARANGAY<br>LOGO</div>
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
                <div class="date-issued">
                    Date Issued:
                    <span><?= htmlspecialchars($dateIssued) ?></span>
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

</body>
</html>