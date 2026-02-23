<?php
/**
 * Archive Blotter Record
 * Moves a blotter record to the archive table and removes it from blotter_records.
 * Triggered by the "Archive" action in the blotter action menu.
 */

require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

$conn->set_charset("utf8mb4");

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get blotter ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid blotter record ID']);
    exit;
}

// Ensure archive table exists
$conn->query("CREATE TABLE IF NOT EXISTS `archive` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `archive_type` varchar(50) DEFAULT NULL,
    `record_id` varchar(50) DEFAULT NULL,
    `record_data` longtext DEFAULT NULL,
    `deleted_by` varchar(100) DEFAULT NULL,
    `deleted_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $conn->begin_transaction();

    // ── 1. Fetch main blotter record ──────────────────────────────────────────
    $stmt = $conn->prepare("SELECT * FROM blotter_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Blotter record not found");
    }

    $blotter = $result->fetch_assoc();
    $recordNumber = $blotter['record_number'];
    $stmt->close();

    // ── 2. Fetch complainants ─────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT * FROM blotter_complainants
        WHERE blotter_id = ? AND (statement IS NULL OR statement = '' OR statement = 'COMPLAINANT')
        ORDER BY id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $complainants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── 3. Fetch victims ──────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT * FROM blotter_complainants
        WHERE blotter_id = ? AND statement = 'VICTIM'
        ORDER BY id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $victims = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── 4. Fetch witnesses ────────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT * FROM blotter_complainants
        WHERE blotter_id = ? AND statement = 'WITNESS'
        ORDER BY id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $witnesses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── 5. Fetch respondents ──────────────────────────────────────────────────
    $stmt = $conn->prepare("
        SELECT * FROM blotter_respondents
        WHERE blotter_id = ?
        ORDER BY id
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $respondents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ── 6. Parse actions from remarks JSON ────────────────────────────────────
    $actions = [];
    if (!empty($blotter['remarks'])) {
        $decoded = json_decode($blotter['remarks'], true);
        if (is_array($decoded)) {
            $actions = $decoded;
        }
    }

    // ── 7. Build archive data payload ─────────────────────────────────────────
    $firstComplainant = !empty($complainants) ? $complainants[0]['name'] : 'N/A';
    $firstRespondent  = !empty($respondents)  ? $respondents[0]['name']  : 'N/A';

    $archiveData = [
        // Quick-display fields (used by archive.php viewDetails)
        'complainant'   => $firstComplainant,
        'respondent'    => $firstRespondent,
        'complaint'     => $blotter['incident_type'],
        'status'        => $blotter['status'],
        'date'          => $blotter['date_reported'],

        // Full record data for restoration
        'record_number'        => $blotter['record_number'],
        'incident_type'        => $blotter['incident_type'],
        'incident_description' => $blotter['incident_description'],
        'incident_date'        => $blotter['incident_date'],
        'incident_location'    => $blotter['incident_location'],
        'date_reported'        => $blotter['date_reported'],
        'reported_by'          => $blotter['reported_by'],
        'resolution'           => $blotter['resolution'],
        'remarks'              => $blotter['remarks'],
        'created_at'           => $blotter['created_at'],

        // Related records
        'complainants' => $complainants,
        'victims'      => $victims,
        'witnesses'    => $witnesses,
        'respondents'  => $respondents,
        'actions'      => $actions,
    ];

    $recordData = json_encode($archiveData, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if ($recordData === false) {
        throw new Exception("Failed to encode blotter data for archive");
    }

    $archiveType = 'blotter';
    $deletedBy   = $_SESSION['username'] ?? 'Unknown';

    // ── 8. Insert into archive ────────────────────────────────────────────────
    $stmt = $conn->prepare("
        INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssss", $archiveType, $recordNumber, $recordData, $deletedBy);
    if (!$stmt->execute()) {
        throw new Exception("Failed to archive blotter record: " . $stmt->error);
    }
    $stmt->close();

    // ── 9. Explicitly delete related records (in case CASCADE is not active) ──
    $stmt = $conn->prepare("DELETE FROM blotter_complainants WHERE blotter_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM blotter_respondents WHERE blotter_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // ── 10. Delete from blotter_records ──────────────────────────────────────
    $stmt = $conn->prepare("DELETE FROM blotter_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete blotter record: " . $stmt->error);
    }
    if ($stmt->affected_rows === 0) {
        throw new Exception("Blotter record could not be deleted or does not exist");
    }
    $stmt->close();

    // ── 11. Commit ────────────────────────────────────────────────────────────
    $conn->commit();

    // ── 12. Activity log (outside transaction) ────────────────────────────────
    if (isset($_SESSION['username'])) {
        try {
            $log_user   = $_SESSION['username'];
            $log_action = 'Archive Blotter';
            $log_desc   = "Archived blotter record: $recordNumber";
            $log_stmt   = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            if ($log_stmt) {
                $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } catch (Exception $log_error) {
            error_log("Activity log error: " . $log_error->getMessage());
        }
    }

    echo json_encode(['success' => true, 'message' => 'Blotter record archived successfully']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("archive_blotter_record.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
