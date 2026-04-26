<?php
/**
 * Document Generator for Summons, Notice of Hearing, and Subpoena
 */
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

// Ensure the user has permission to print blotter records
requirePermission('perm_blotter_print');

// Get parameters from GET request
$caseId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? strtolower($_GET['type']) : '';

if ($caseId <= 0 || !in_array($type, ['summons', 'notice', 'subpoena'])) {
    die("Invalid request. Please provide a valid case ID and document type (summons, notice, or subpoena).");
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Fetch Barangay Info for the header
    $infoStmt = $pdo->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    $brgyInfo = $infoStmt->fetch();

    // Prepare logos for embedding (Base64 encoding binary BLOB data)
    $brgyLogoSrc = 'assets/image/brgylogo.jpg'; // Default fallback
    if ($brgyInfo && !empty($brgyInfo['barangay_logo'])) {
        $logoData = $brgyInfo['barangay_logo'];
        if (@file_exists($logoData)) {
            $brgyLogoSrc = $logoData;
        } else {
            $brgyLogoSrc = 'data:image/png;base64,' . base64_encode($logoData);
        }
    }

    $muniLogoSrc = 'assets/image/citylogo.png'; // Default fallback
    if ($brgyInfo && !empty($brgyInfo['municipal_logo'])) {
        $logoData = $brgyInfo['municipal_logo'];
        if (@file_exists($logoData)) {
            $muniLogoSrc = $logoData;
        } else {
            $muniLogoSrc = 'data:image/png;base64,' . base64_encode($logoData);
        }
    }

    // Fetch case details from the 'blotter_records' table with joined party names to match view_details logic
    $stmt = $pdo->prepare("
        SELECT 
            br.*,
            br.incident_type AS case_type,
            (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM blotter_complainants WHERE blotter_id = br.id AND (statement = 'COMPLAINANT' OR statement IS NULL OR statement = '')) AS complainant_name,
            (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM blotter_respondents WHERE blotter_id = br.id) AS respondent_name,
            (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM blotter_complainants WHERE blotter_id = br.id AND statement = 'WITNESS') AS witness_names
        FROM blotter_records br 
        WHERE br.id = ?
    ");
    $stmt->execute([$caseId]);
    $case = $stmt->fetch();

    if (!$case) {
        die("Case record not found in the system.");
    }

    // Format mediation schedule using the 'mediation_schedule' column from blotter_records
    $schedule_timestamp = !empty($case['mediation_schedule']) ? strtotime($case['mediation_schedule']) : time();
    $mDate = date('F j, Y', $schedule_timestamp);
    $mTime = date('g:i A', $schedule_timestamp);
    
    $docTitle = "";
    $recipients = [];
    $bodyText = "";

    // Dynamic content logic based on document type
    switch ($type) {
        case 'summons':
            $docTitle = "SUMMONS";
            // Split by comma in case of multiple respondents
            $recipients = explode(',', $case['respondent_name']);
            $bodyText = "You are hereby summoned to appear before me in person, together with your witnesses, if any, for a mediation/conciliation of the aforesaid complaint on <strong>$mDate</strong> at <strong>$mTime</strong> at the Barangay Hall.";
            break;
        case 'notice':
            $docTitle = "NOTICE OF HEARING";
            // Split by comma in case of multiple complainants
            $recipients = explode(',', $case['complainant_name']);
            $bodyText = "You are hereby notified to appear before me on <strong>$mDate</strong> at <strong>$mTime</strong> for the hearing of your complaint against <strong>{$case['respondent_name']}</strong>.";
            break;
        case 'subpoena':
            $docTitle = "SUBPOENA";
            $rawNames = !empty($case['witness_names']) ? $case['witness_names'] : "[WITNESS NAME]";
            $recipients = explode(',', $rawNames);
            $bodyText = "You are hereby commanded to appear before me on <strong>$mDate</strong> at <strong>$mTime</strong> then and there to testify as a witness in the above-entitled case.";
            break;
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $docTitle; ?> - Case #<?php echo $caseId; ?></title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #000;
        }
        /* A4 Page Styling */
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 25mm;
            margin: 10mm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        .header-logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .header-text {
            text-align: center;
            flex: 1;
        }
        .header p { margin: 2px 0; font-size: 12pt; }
        .brgy-name { font-weight: bold; font-size: 14pt; text-transform: uppercase; }
        
        .case-header { margin-top: 30px; font-size: 12pt; }
        .case-row { display: flex; margin-bottom: 5px; }
        .label { width: 140px; font-weight: bold; }

        .doc-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 50px 0;
            text-transform: uppercase;
        }
        
        .recipient-section { margin-bottom: 30px; font-size: 12pt; }
        .to-name { font-weight: bold; text-decoration: underline; text-transform: uppercase; }
        
        .body-text {
            text-align: justify;
            text-indent: 50px;
            font-size: 13pt;
            line-height: 1.6;
        }
        
        .compliance-note {
            margin-top: 30px;
            font-style: italic;
            font-size: 12pt;
        }

        .signature-wrap {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
        }
        .sig-box { width: 45%; text-align: center; }
        .sig-line { border-top: 1px solid #000; margin-top: 40px; font-weight: bold; text-transform: uppercase; padding-top: 5px; }
        .sig-sub { font-size: 10pt; font-style: italic; }

        /* Floating Print Button */
        .toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
        }
        .btn-print {
            padding: 12px 25px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Page Break Logic */
        .page-break { display: none; }

        @media print {
            body { background: none; }
            .page { margin: 0; box-shadow: none; width: 100%; height: auto; }
            .toolbar { display: none !important; }
            @page { size: A4; margin: 0; }
            .page-break { display: block; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-print" onclick="window.print()">Print Document</button>
    </div>

    <?php foreach ($recipients as $recipient): ?>
        <div class="page">
            <div class="header">
                <img src="<?php echo $brgyLogoSrc; ?>" class="header-logo-img" alt="Barangay Logo">
                <div class="header-text">
                    <p>Republic of the Philippines</p>
                    <p>Province of <?php echo htmlspecialchars($brgyInfo['province_name'] ?? 'Zambales'); ?></p>
                    <p>Municipality of <?php echo htmlspecialchars($brgyInfo['town_name'] ?? 'Subic'); ?></p>
                    <p class="brgy-name">BARANGAY <?php echo strtoupper(htmlspecialchars($brgyInfo['barangay_name'] ?? 'Wawandue')); ?></p>
                    <p style="margin-top: 10px; font-weight: bold;">OFFICE OF THE LUPONG TAGAPAMAYAPA</p>
                </div>
                <img src="<?php echo $muniLogoSrc; ?>" class="header-logo-img" alt="Municipality Logo">
            </div>

            <div class="case-header">
                <div class="case-row"><span class="label">Complainant:</span> <span><?php echo strtoupper(htmlspecialchars($case['complainant_name'])); ?></span></div>
                <div class="case-row"><span class="label">Respondent:</span> <span><?php echo strtoupper(htmlspecialchars($case['respondent_name'])); ?></span></div>
                <div class="case-row"><span class="label">Case Type:</span> <span><?php echo strtoupper(htmlspecialchars($case['case_type'])); ?></span></div>
            </div>

            <h1 class="doc-title"><?php echo $docTitle; ?></h1>

            <div class="recipient-section">
                TO: <span class="to-name"><?php echo htmlspecialchars(trim($recipient)); ?></span>
            </div>

            <div class="body-text">
                <?php echo $bodyText; ?>
            </div>
            
            <p class="compliance-note">Fail not to appear under penalty of law / contempt of court.</p>

            <div class="signature-wrap">
                <div class="sig-box">
                    <div class="sig-line">Barangay Secretary</div>
                    <div class="sig-sub">Lupong Tagapamayapa</div>
                </div>
                <div class="sig-box">
                    <div class="sig-line">Hon. Punong Barangay</div>
                    <div class="sig-sub">Barangay Chairperson</div>
                </div>
            </div>
        </div>
        <div class="page-break"></div>
    <?php endforeach; ?>
</body>
</html>