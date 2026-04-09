<?php
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Database Connection
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
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get official ID
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid official ID']);
    exit;
}

$password = trim($_POST['password'] ?? '');
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$reason = trim($_POST['reason'] ?? 'No reason provided');

try {
    // Start transaction
    $pdo->beginTransaction();

    // Verify user password for security
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    if (!password_verify($password, $user['password'])) {
        throw new Exception("Invalid password");
    }

    // Get official details for archiving
    $stmt = $pdo->prepare("
        SELECT 
            bo.*,
            r.resident_id as r_resident_id,
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE bo.id = ?
    ");
    $stmt->execute([$id]);
    $official = $stmt->fetch();
    
    if (!$official) {
        throw new Exception("Official not found");
    }
    
    // Format official name
    $officialName = trim($official['first_name'] . ' ' . $official['last_name']);
    $officialPosition = $official['position'];
    $recordId = $official['r_resident_id'] ?? $official['id'];

    $official['archive_reason'] = $reason;

    // Prepare data for archive
    $recordData = json_encode($official, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
    if ($recordData === false) {
        throw new Exception("Failed to encode official data");
    }
    
    $archiveType = 'official';
    $deletedBy = $_SESSION['username'] ?? 'Unknown';

    // Check if archive table exists and create it if not
    $checkTable = $pdo->query("SHOW TABLES LIKE 'archive'");
    if ($checkTable->rowCount() == 0) {
        $createTableSql = "CREATE TABLE IF NOT EXISTS `archive` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `archive_type` varchar(50) DEFAULT NULL,
            `record_id` varchar(50) DEFAULT NULL,
            `record_data` longtext DEFAULT NULL,
            `deleted_by` varchar(100) DEFAULT NULL,
            `deleted_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($createTableSql);
    }

    // Insert into archive table
    $stmt = $pdo->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$archiveType, $recordId, $recordData, $deletedBy]);

    // Delete the official record
    $stmt = $pdo->prepare("DELETE FROM barangay_officials WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        // Commit transaction
        $pdo->commit();
        
        // Log Activity (outside transaction to prevent rollback if logging fails)
        if (isset($_SESSION['username'])) {
            try {
                $log_user = $_SESSION['username'];
                $log_action = 'Archive Official';
                $log_desc = "Moved official to archive: $officialName - $officialPosition (ID: $recordId)";
                $log_stmt = $pdo->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                $log_stmt->execute([$log_user, $log_action, $log_desc]);
            } catch (Exception $log_error) {
                // Log the error but don't fail the delete operation
                error_log("Activity log error: " . $log_error->getMessage());
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Official moved to archive successfully']);
    } else {
        throw new Exception("Official record could not be deleted or does not exist");
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
