<?php
/**
 * Get Official Data for Edit Modal
 */

require_once '../config.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid official ID']);
    exit;
}

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

    $stmt = $pdo->prepare("
        SELECT
            bo.id,
            bo.resident_id,
            bo.fullname,
            bo.position,
            bo.committee,
            bo.term_start,
            bo.term_end,
            bo.status,
            bo.appointment_type,
            bo.photo,
            bo.contact_number,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.mobile_number,
            r.photo AS resident_photo
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE bo.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $official = $stmt->fetch();

    if (!$official) {
        echo json_encode(['success' => false, 'message' => 'Official not found']);
        exit;
    }

    // Build display name with HON. and Middle Initial
    $mi = !empty($official['middle_name']) ? strtoupper(substr(trim($official['middle_name']), 0, 1)) . '.' : '';
    $nameParts = array_filter([trim($official['first_name'] ?? ''), $mi, trim($official['last_name'] ?? ''), trim($official['suffix'] ?? '')]);
    
    if (!empty($nameParts)) {
        $displayName = strtoupper(implode(' ', $nameParts));
        if ($official['appointment_type'] === 'Elected') {
            $displayName = 'HON. ' . $displayName;
        }
    } else {
        $displayName = !empty($official['fullname']) ? $official['fullname'] : '';
    }

    // Contact
    $contact = $official['contact_number'] ?? $official['mobile_number'] ?? '';

    // Photo
    $photo = $official['photo'] ?? $official['resident_photo'] ?? '';

    echo json_encode([
        'success'  => true,
        'official' => [
            'id'           => $official['id'],
            'resident_id'  => $official['resident_id'],
            'fullname'     => $displayName,
            'contact'      => $contact,
            'photo'        => $photo,
            'position'     => $official['position']     ?? '',
            'committee'    => $official['committee']    ?? '',
            'term_start'   => $official['term_start']   ?? '',
            'term_end'     => $official['term_end']     ?? '',
            'status'       => $official['status']       ?? 'Active',
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error fetching official for edit: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
