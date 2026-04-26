<?php
/**
 * Get Official Details
 * Returns official details and history of terms for the view modal
 */

require_once '../config.php';

header('Content-Type: application/json');

// Validate input
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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Fetch the specific official's details
    $stmt = $pdo->prepare("
        SELECT 
            bo.id,
            bo.resident_id,
            bo.position,
            bo.committee,
            bo.hierarchy_level,
            bo.term_start,
            bo.term_end,
            bo.status,
            bo.appointment_type,
            bo.photo,
            bo.contact_number,
            bo.fullname,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.photo as resident_photo
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
        $displayName = !empty($official['fullname']) ? $official['fullname'] : 'Vacant';
    }

    // Determine photo
    $photo = $official['photo'] ?? $official['resident_photo'] ?? null;

    // Build initials for avatar fallback
    $initials = 'V';
    if (!empty($official['first_name']) && !empty($official['last_name'])) {
        $initials = strtoupper(substr($official['first_name'], 0, 1)) . strtoupper(substr($official['last_name'], 0, 1));
    } elseif (!empty($official['fullname'])) {
        $parts = explode(' ', trim($official['fullname']));
        if (count($parts) >= 2) {
            $initials = strtoupper(substr($parts[0], 0, 1)) . strtoupper(substr(end($parts), 0, 1));
        } elseif (count($parts) === 1) {
            $initials = strtoupper(substr($parts[0], 0, 2));
        }
    }

    // Fetch history: all terms for the same resident (or same official if no resident_id)
    $history = [];
    if (!empty($official['resident_id'])) {
        // Get all terms for this resident
        $histStmt = $pdo->prepare("
            SELECT 
                id,
                position,
                committee,
                term_start,
                term_end,
                status,
                appointment_type
            FROM barangay_officials
            WHERE resident_id = :resident_id
            ORDER BY term_start DESC
        ");
        $histStmt->execute([':resident_id' => $official['resident_id']]);
        $history = $histStmt->fetchAll();
    } else {
        // No resident linked — just show this single record as history
        $history = [[
            'id'               => $official['id'],
            'position'         => $official['position'],
            'committee'        => $official['committee'],
            'term_start'       => $official['term_start'],
            'term_end'         => $official['term_end'],
            'status'           => $official['status'],
            'appointment_type' => $official['appointment_type'],
        ]];
    }

    // Format history rows
    $formattedHistory = [];
    foreach ($history as $row) {
        $termStart = !empty($row['term_start']) ? date('M d, Y', strtotime($row['term_start'])) : 'N/A';
        $termEnd   = !empty($row['term_end'])   ? date('M d, Y', strtotime($row['term_end']))   : 'N/A';
        $formattedHistory[] = [
            'id'               => $row['id'],
            'position'         => $row['position'] ?? 'N/A',
            'committee'        => $row['committee'] ?? 'N/A',
            'term_period'      => $termStart . ' – ' . $termEnd,
            'status'           => $row['status'] ?? 'N/A',
            'appointment_type' => $row['appointment_type'] ?? 'N/A',
            'is_current'       => ($row['id'] == $id),
        ];
    }

    echo json_encode([
        'success'      => true,
        'official'     => [
            'id'             => $official['id'],
            'name'           => $displayName,
            'initials'       => $initials,
            'position'       => $official['position'] ?? 'N/A',
            'committee'      => $official['committee'] ?? '',
            'contact_number' => $official['contact_number'] ?? 'N/A',
            'photo'          => $photo,
            'status'         => $official['status'] ?? 'N/A',
            'appointment_type' => $official['appointment_type'] ?? 'N/A',
            'term_start'     => !empty($official['term_start']) ? date('M d, Y', strtotime($official['term_start'])) : 'N/A',
            'term_end'       => !empty($official['term_end'])   ? date('M d, Y', strtotime($official['term_end']))   : 'N/A',
        ],
        'history'      => $formattedHistory,
    ]);

} catch (PDOException $e) {
    error_log("Error fetching official details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
