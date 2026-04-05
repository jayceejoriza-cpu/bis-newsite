<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$frequency = $_POST['frequency'] ?? 'None';
$backup_time = $_POST['backup_time'] ?? '00:00';
$target_folder = $_POST['target_folder'] ?? '';
$zip_password = $_POST['zip_password'] ?? '';

if (!empty($target_folder) && !file_exists($target_folder)) {
    if (!@mkdir($target_folder, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Could not create target folder. Check permissions or path accuracy.']);
        exit;
    }
}

$settingsFile = 'backup_settings.json';
$last_run = null;
if (file_exists($settingsFile)) {
    $existing = json_decode(file_get_contents($settingsFile), true);
    if ($existing && isset($existing['last_run'])) {
        $last_run = $existing['last_run'];
    }
}

$settings = [
    'frequency' => $frequency,
    'backup_time' => $backup_time,
    'target_folder' => $target_folder,
    'zip_password' => $zip_password,
    'last_run' => $last_run
];

if (file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT))) {
    // Log activity
    $log_user = $_SESSION['username'];
    $log_action = 'Update Backup Settings';
    $log_desc = "Updated automatic backup settings (Frequency: $frequency)";
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
    $log_stmt->execute();
    $log_stmt->close();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to write settings file.']);
}
?>