<?php
/**
 * Save Barangay Official
 * Handles creating new barangay officials with resident linkage
 */

header('Content-Type: application/json');
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
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

    // ── Inputs ──────────────────────────────────────────────
    $residentId        = !empty($_POST['resident_id'])        ? intval($_POST['resident_id'])        : null;
    $fullname          = trim($_POST['fullname']          ?? '');
    $chairmanship      = trim($_POST['chairmanship']      ?? '');
    $position          = trim($_POST['position']          ?? '');
    $termStart         = $_POST['term_start']             ?? '';
    $termEnd           = $_POST['term_end']               ?? '';
    $status            = $_POST['status']                 ?? 'Active';
    $contactNumber     = trim($_POST['contact_number']    ?? '');
    $residentPhotoPath = trim($_POST['resident_photo_path'] ?? '');

    // ── Validation ───────────────────────────────────────────
    if (empty($position)) {
        throw new Exception('Position is required');
    }
    if (empty($termStart)) {
        throw new Exception('Term start date is required');
    }
    if (empty($termEnd)) {
        throw new Exception('Term end date is required');
    }

    $startDate = new DateTime($termStart);
    $endDate   = new DateTime($termEnd);
    if ($endDate <= $startDate) {
        throw new Exception('Term end date must be after term start date');
    }

    // ── Check Position Limits for Active Officials ───────────
    if ($status === 'Active') {
        $posLimitMap = [
            'Barangay Captain' => 1,
            'SK Chairman' => 1,
            'Barangay Secretary' => 1,
            'Barangay Treasurer' => 1,
            'Barangay Administator' => 1,
            'Bookkeeper' => 1,
            'Barangay Kagawad' => 7,
            'Kagawad' => 7,
            'SK Kagawad' => 7
        ];

        $limit = $posLimitMap[$position] ?? 1; // Default limit 1 for "Other" positions
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM barangay_officials WHERE position = :position AND status = 'Active'");
        $countStmt->execute([':position' => $position]);
        if ($countStmt->fetchColumn() >= $limit) {
            throw new Exception("Cannot add another active $position. The limit of $limit has been reached.");
        }

        // Check Chairmanship Limits (All are limited to 1 active slot)
        if (!empty($chairmanship)) {
            $chairStmt = $pdo->prepare("SELECT COUNT(*) FROM barangay_officials WHERE committee = :committee AND status = 'Active'");
            $chairStmt->execute([':committee' => $chairmanship]);
            if ($chairStmt->fetchColumn() >= 1) {
                throw new Exception("Cannot add another active official for $chairmanship chairmanship. The limit of 1 has been reached.");
            }
        }
    }

    // ── If resident_id provided, fetch resident data ─────────
    $photoPath = null;
    if ($residentId) {
        $resStmt = $pdo->prepare("
            SELECT
                TRIM(CONCAT(
                    first_name, ' ',
                    IFNULL(CONCAT(middle_name, ' '), ''),
                    last_name,
                    IFNULL(CONCAT(' ', suffix), '')
                )) AS full_name,
                mobile_number,
                photo
            FROM residents
            WHERE id = :id
            LIMIT 1
        ");
        $resStmt->execute([':id' => $residentId]);
        $resident = $resStmt->fetch();

        if ($resident) {
            // Use resident's name if fullname not explicitly provided
            if (empty($fullname)) {
                $fullname = $resident['full_name'];
            }
            // Use resident's contact if not provided
            if (empty($contactNumber) && !empty($resident['mobile_number'])) {
                $contactNumber = $resident['mobile_number'];
            }
            // Use resident's photo path
            if (!empty($resident['photo'])) {
                $photoPath = $resident['photo'];
            }
        }
    }

    // Fallback: use resident_photo_path sent from form
    if (empty($photoPath) && !empty($residentPhotoPath)) {
        $photoPath = $residentPhotoPath;
    }

    // Require a name
    if (empty($fullname)) {
        throw new Exception('Please select a resident or provide a name');
    }

    // ── Hierarchy level ──────────────────────────────────────
    $hierarchyLevel = 2;
    if ($position === 'Barangay Captain') {
        $hierarchyLevel = 1;
    } elseif (in_array($position, ['SK Chairman','SK Kagawad',  'Barangay Secretary', 'Barangay Treasurer', 'Barangay Administator', 'Bookkeeper'])) {
        $hierarchyLevel = 3;
    }

    // ── Appointment type ─────────────────────────────────────
    $appointmentType = 'Elected';
    if (in_array($position, ['Barangay Secretary', 'Barangay Treasurer', 'Barangay Administator'])) {
        $appointmentType = 'Appointed';
    }

    // ── Insert ───────────────────────────────────────────────
    $stmt = $pdo->prepare("
        INSERT INTO barangay_officials (
            resident_id,
            fullname,
            position,
            committee,
            hierarchy_level,
            term_start,
            term_end,
            status,
            appointment_type,
            photo,
            contact_number,
            created_at,
            updated_at
        ) VALUES (
            :resident_id,
            :fullname,
            :position,
            :committee,
            :hierarchy_level,
            :term_start,
            :term_end,
            :status,
            :appointment_type,
            :photo,
            :contact_number,
            NOW(),
            NOW()
        )
    ");

    $stmt->execute([
        ':resident_id'    => $residentId,
        ':fullname'       => $fullname,
        ':position'       => $position,
        ':committee'      => !empty($chairmanship) ? $chairmanship : null,
        ':hierarchy_level'=> $hierarchyLevel,
        ':term_start'     => $termStart,
        ':term_end'       => $termEnd,
        ':status'         => $status,
        ':appointment_type' => $appointmentType,
        ':photo'          => $photoPath,
        ':contact_number' => !empty($contactNumber) ? $contactNumber : null,
    ]);

    $officialId = $pdo->lastInsertId();

    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Add Barangay Official';
        $log_desc = "Added new barangay official: $fullname as $position";
        $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->execute([$log_user, $log_action, $log_desc]);
    }

    echo json_encode([
        'success'     => true,
        'message'     => 'Official created successfully',
        'official_id' => $officialId,
        'data'        => [
            'fullname'   => $fullname,
            'position'   => $position,
            'committee'  => $chairmanship,
            'term_start' => $termStart,
            'term_end'   => $termEnd,
            'status'     => $status,
            'photo'      => $photoPath
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
