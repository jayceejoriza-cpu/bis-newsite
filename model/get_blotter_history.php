<?php
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

header('Content-Type: application/json');

try {
    if (!hasPermission('perm_blotter_view')) { 
        throw new Exception('Permission denied.'); 
    }

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) { 
        throw new Exception('Invalid record ID.'); 
    }

    $query = "SELECT h.*, u.username as officer_name 
              FROM blotter_history h 
              LEFT JOIN users u ON h.changed_by = u.id 
              WHERE h.blotter_id = ? 
              ORDER BY h.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    $stmt->close();

    echo json_encode([
        'success' => true,
        'data' => $history
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}