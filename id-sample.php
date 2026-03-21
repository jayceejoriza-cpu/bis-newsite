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
        $municipal_logo = $bi['municipal_logo'] ?? '';
    }
} catch (PDOException $e) {
    error_log("Error fetching barangay info: " . $e->getMessage());
}

// Optional: Fetch Resident Data if a resident_id is passed in the URL (e.g., id-sample.php?resident_id=1)
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

// Define variables with fallback sample data
$pcn         = $resident['resident_id'] ?? '0000-0000-0000-0000';
$lastName    = $resident['last_name'] ?? 'DELA CRUZ';
$givenNames  = trim(($resident['first_name'] ?? 'JUAN ANTONIO') . ' ' . ($resident['middle_name'] ?? ''));
$sex         = $resident['sex'] ?? 'MALE';
$civilStatus = $resident['civil_status'] ?? 'SINGLE';
$dob         = !empty($resident['date_of_birth']) ? date('F d, Y', strtotime($resident['date_of_birth'])) : 'JANUARY 1, 1990';
$address     = $resident['current_address'] ?? '123 STREET NAME, BARANGAY, CITY, PROVINCE';
$photo       = !empty($resident['photo']) ? $resident['photo'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ID Card Print Template</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    padding: 50px;
}

.id-card-container {
    width: 170mm; /* Two 85mm x 55mm cards side by side */
    height: 55mm;
    background: white;
    border: 1px solid #ccc;
    display: flex;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    box-sizing: border-box;
}

.card-section {
    width: 85mm;
    padding: 3mm;
    flex: 1;
    box-sizing: border-box;
}

.left {
    border-right: 1px dashed #ccc;
    display: flex;
    flex-direction: column;
}

.header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2mm;
}

.logo-img, .logo-placeholder-box {
    width: 10mm;
    height: 10mm;
    object-fit: contain;
    flex-shrink: 0;
}

.header-center {
    flex: 1;
    text-align: center;
    padding: 0 2mm;
}

.header-center p {
    margin: 0;
    font-size: 4pt;
    line-height: 1.3;
}

.header-center .brgy-name {
    font-size: 5pt;
    font-weight: bold;
}

.header-center .office-name {
    font-size: 6pt;
    font-weight: bold;
    margin-top: 1mm;
}

.header > span {
    width: 100%;
    text-align: center;
    display: block;
    margin-top: 1px;
    font-weight: bold;
    font-size: 7pt;
    text-transform: uppercase;
    color: #333;
    border-bottom: 1px solid #7708df;
    padding-bottom: 1mm;
}

.id-details-row {
    display: flex;
    gap: 3mm;
    align-items: flex-start;
    margin-top: 2mm;
    padding: 0 2mm 0 2mm;
}

.photo-placeholder {
    width: 25mm;
    height: 25mm;
    border: 1px solid #000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 6pt;
    flex-shrink: 0;
}

label {
    font-size: 5pt;
    color: #555;
    text-transform: uppercase;
    display: block;
    margin-top: 1.5mm;
}

.bold-text, .resident-number {
    font-weight: bold;
    font-size: 7pt;
    margin: 0;
}

.personal-info {
    flex: 1;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2mm;
}

.details-grid p, .address-section p {
    font-size: 7pt;
    margin: 0;
    font-weight: bold;
}

.address-section {
    margin-top: 2mm;
}

/* Print Specific Styles */
@media print {
    body { background: none; padding: 0; }
    .id-card-container { 
        box-shadow: none; 
        border: 1px solid #000; 
        margin: 0;
    }
}</style>
<body>
    <div class="id-card-container">
        <div class="card-section left">
            <div class="header">
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
                </div>

                <?php if (!empty($municipal_logo)): ?>
                    <img src="<?= htmlspecialchars($municipal_logo) ?>" class="logo-img" alt="Bagong Pilipinas Logo">
                <?php else: ?>
                    <div class="logo-placeholder-box"></div>
                <?php endif; ?>
                <span>Barangay Resident ID </span>
            </div>
            
            <div class="id-details-row">
               
                
                <div class="personal-info">
                    <label style="margin-top: -5px;">Resident ID:</label>
                    <p class="resident-number"><?= htmlspecialchars($resident_id) ?></p>
                    
                    <label>Last Name</label>
                    <p class="bold-text"><?= htmlspecialchars(strtoupper($lastName)) ?></p>
                    
                    <label>Given Names</label>
                    <p class="bold-text"><?= htmlspecialchars(strtoupper($givenNames)) ?></p>

                    <label>Middle Name</label>
                    <p class="bold-text"><?= htmlspecialchars(strtoupper($givenNames)) ?></p>

                    <label>Suffix</label>
                    <p class="bold-text"><?= htmlspecialchars(strtoupper($givenNames)) ?></p>
                </div> 
                <div class="photo-placeholder">
                    <?php if ($photo): ?>
                        <img src="<?= htmlspecialchars($photo) ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="Resident Photo">
                    <?php else: ?>
                        PHOTO
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-section right">
            
            <div class="details-grid">
                <div>
                    <label>Sex</label>
                    <p><?= htmlspecialchars(strtoupper($sex)) ?></p>
                </div>
                <div>
                    <label>Marital Status</label>
                    <p><?= htmlspecialchars(strtoupper($civilStatus)) ?></p>
                </div>
                <div>
                    <label>Date of Birth</label>
                    <p><?= htmlspecialchars(strtoupper($dob)) ?></p>
                </div>
            </div>
            
            <div class="address-section">
                <label>Address</label>
                <p><?= htmlspecialchars(strtoupper($address)) ?></p>
            </div>
        </div>
    </div>
</body>
</html>