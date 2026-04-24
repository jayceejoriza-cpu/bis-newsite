<?php
/**
 * Search Residents for Official Picker
 * Returns residents with photo and contact for the official creation form
 */

require_once '../config.php';
header('Content-Type: application/json');

$search = trim($_GET['search'] ?? '');
$excludeOfficialId = intval($_GET['exclude_official_id'] ?? 0);

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $sql = "
        SELECT
            r.id,
            r.resident_id,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.mobile_number,
            r.photo,
            TRIM(CONCAT(
                r.first_name, ' ',
                IFNULL(CONCAT(r.middle_name, ' '), ''),
                r.last_name,
                IFNULL(CONCAT(' ', r.suffix), '')
            )) AS full_name
        FROM residents r
        WHERE r.activity_status = 'Alive'
          AND TIMESTAMPDIFF(YEAR, r.date_of_birth, CURDATE()) >= 18
    ";

    $params = [];

    if ($excludeOfficialId > 0) {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM barangay_officials bo 
            WHERE bo.resident_id = r.id AND bo.status = 'Active' AND bo.id != :exclude_official_id
        )";
        $params[':exclude_official_id'] = $excludeOfficialId;
    } else {
        $sql .= " AND NOT EXISTS (
            SELECT 1 FROM barangay_officials bo 
            WHERE bo.resident_id = r.id AND bo.status = 'Active'
        )";
    }

    if (!empty($search)) {
        $words = array_filter(explode(' ', $search));
        if (!empty($words)) {
            $wordConditions = [];
            foreach ($words as $index => $word) {
                $paramName = ":search" . $index;
                $wordConditions[] = "CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name, ' ', IFNULL(r.resident_id, '')) LIKE " . $paramName;
                $params[$paramName] = '%' . $word . '%';
            }
            $sql .= " AND (" . implode(" AND ", $wordConditions) . ")";
        }
    }

    $sql .= " ORDER BY r.last_name, r.first_name LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $residents = $stmt->fetchAll();

    echo json_encode([
        'success'   => true,
        'residents' => $residents,
        'count'     => count($residents)
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
