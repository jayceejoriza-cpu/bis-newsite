<?php
/**
 * Get Roles - Returns all roles as JSON
 */
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$roles = [];

$result = $conn->query("SELECT id, name, description, color, text_color FROM roles ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row;
    }
}

echo json_encode(['success' => true, 'roles' => $roles]);
?>
