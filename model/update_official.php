<?php
/**
 * Update Barangay Official
 */

require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Collect inputs
$id          = isset($_POST['official_id'])      ? intval($_POST['official_id'])          : 0;
$residentId  = isset($_POST['resident_id'])      ? intval($_POST['resident_id'])           : null;
$fullname    = isset($_POST['fullname'])          ? trim($_POST['fullname'])                : '';
$contact     = isset($_POST['contact_number'])   ? trim($_POST['contact_number'])          : '';
$photoPath   = isset($_POST['resident_photo_path']) ? trim($_POST['resident_photo_path'])  : '';
$committee   = isset($_POST['chairmanship'])     ? trim($_POST['chairmanship'])            : '';
$position    = isset($_POST['position'])         ? trim($_POST['position'])                : '';
$termStart   = isset($_POST['term_start'])       ? trim($_POST['term_start'])              : '';
$termEnd     = isset($_POST['term_end'])         ? trim($_POST['term_end'])                : '';
$status      = isset($_POST['status'])           ? trim($_POST['status'])                  : 'Active';

// Validate
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid official ID']);
    exit;
}
if (empty($position)) {
    echo json_encode(['success' => false, 'message' => 'Position is required']);
    exit;
}
if (empty($termStart) || empty($termEnd)) {
    echo json_encode(['success' => false, 'message' => 'Term start and end dates are required']);
    exit;
}

// Determine hierarchy level from position
$hierarchyMap = [
    'Barangay Captain'   => 1,
    'Kagawad'            => 2,
    'SK Chairman'        => 3,
    'Barangay Secretary' => 3,
    'Barangay Treasurer' => 3,
];
$hierarchyLevel = $hierarchyMap[$position] ?? 2;

// Determine appointment type
$appointmentType = in_array($position, ['Barangay Secretary', 'Barangay Treasurer'])
    ? 'Appointed'
    : 'Elected';

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

    // Build photo value: use new path if provided, else keep existing
    $photoValue = null;
    if (!empty($photoPath)) {
        $photoValue = $photoPath;
    } else {
        // Keep existing photo
        $existingStmt = $pdo->prepare("SELECT photo FROM barangay_officials WHERE id = :id");
        $existingStmt->execute([':id' => $id]);
        $existing = $existingStmt->fetch();
        $photoValue = $existing['photo'] ?? null;
    }

    $stmt = $pdo->prepare("
        UPDATE barangay_officials SET
            resident_id      = :resident_id,
            fullname         = :fullname,
            contact_number   = :contact_number,
            photo            = :photo,
            committee        = :committee,
            position         = :position,
            hierarchy_level  = :hierarchy_level,
            term_start       = :term_start,
            term_end         = :term_end,
            status           = :status,
            appointment_type = :appointment_type,
            updated_at       = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':resident_id'      => $residentId ?: null,
        ':fullname'         => $fullname,
        ':contact_number'   => $contact ?: null,
        ':photo'            => $photoValue,
        ':committee'        => $committee ?: null,
        ':position'         => $position,
        ':hierarchy_level'  => $hierarchyLevel,
        ':term_start'       => $termStart,
        ':term_end'         => $termEnd,
        ':status'           => $status,
        ':appointment_type' => $appointmentType,
        ':id'               => $id,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Official updated successfully'
    ]);

} catch (PDOException $e) {
    error_log("Error updating official: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
