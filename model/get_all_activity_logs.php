<?php
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

header('Content-Type: application/json');

// Ensure user has permission to view activity logs
if (!hasPermission('perm_settings_logs_view')) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit();
}

$params = [];
$types = "";
$where_sql = " WHERE 1=1";

// Get filter values from GET request
$search = $_GET['search'] ?? '';
$filter_user = $_GET['filter_user'] ?? '';
$filter_action = $_GET['filter_action'] ?? '';
$filter_from_date = $_GET['filter_from_date'] ?? '';
$filter_to_date = $_GET['filter_to_date'] ?? '';

if (!empty($search)) {
    $where_sql .= " AND description LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}
if (!empty($filter_user)) {
    $where_sql .= " AND user = ?";
    $params[] = $filter_user;
    $types .= "s";
}
if (!empty($filter_action)) {
    $where_sql .= " AND action LIKE ?";
    $params[] = "%$filter_action%";
    $types .= "s";
}
if (!empty($filter_from_date)) {
    $where_sql .= " AND DATE(timestamp) >= ?";
    $params[] = $filter_from_date;
    $types .= "s";
}
if (!empty($filter_to_date)) {
    $where_sql .= " AND DATE(timestamp) <= ?";
    $params[] = $filter_to_date;
    $types .= "s";
}

$sql = "SELECT id, user, action, description, timestamp FROM activity_logs" . $where_sql . " ORDER BY timestamp DESC";

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $logs]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
