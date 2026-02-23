<?php
/**
 * Save Role - Backend Handler
 * Handles: create, edit, delete roles
 */
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = trim($_POST['action'] ?? '');

switch ($action) {
    case 'create': createRole(); break;
    case 'edit':   editRole();   break;
    case 'delete': deleteRole(); break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}

// ============================================
// Create Role
// ============================================
function createRole() {
    global $conn;

    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $color       = trim($_POST['color']       ?? '#e5e7eb');
    $textColor   = trim($_POST['text_color']  ?? '#374151');
    $permissions = $_POST['permissions']      ?? '{}';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Role name is required.', 'field' => 'name']);
        return;
    }

    // Check duplicate
    $check = $conn->prepare("SELECT id FROM roles WHERE name = ? LIMIT 1");
    $check->bind_param('s', $name);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['success' => false, 'message' => 'Role name already exists.', 'field' => 'name']);
        return;
    }
    $check->close();

    $stmt = $conn->prepare("INSERT INTO roles (name, description, color, text_color, permissions) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $description, $color, $textColor, $permissions);

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        echo json_encode([
            'success' => true,
            'message' => 'Role created successfully.',
            'role'    => ['id' => $newId, 'name' => $name, 'color' => $color, 'text_color' => $textColor]
        ]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to create role.']);
    }
}

// ============================================
// Edit Role
// ============================================
function editRole() {
    global $conn;

    $roleId      = intval($_POST['role_id']    ?? 0);
    $name        = trim($_POST['name']         ?? '');
    $description = trim($_POST['description']  ?? '');
    $color       = trim($_POST['color']        ?? '#e5e7eb');
    $textColor   = trim($_POST['text_color']   ?? '#374151');
    $permissions = $_POST['permissions']       ?? '{}';

    if ($roleId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID.']);
        return;
    }
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Role name is required.', 'field' => 'name']);
        return;
    }

    // Check duplicate (exclude current)
    $check = $conn->prepare("SELECT id FROM roles WHERE name = ? AND id != ? LIMIT 1");
    $check->bind_param('si', $name, $roleId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close();
        echo json_encode(['success' => false, 'message' => 'Role name already exists.', 'field' => 'name']);
        return;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE roles SET name=?, description=?, color=?, text_color=?, permissions=? WHERE id=?");
    $stmt->bind_param('sssssi', $name, $description, $color, $textColor, $permissions, $roleId);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Role updated successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update role.']);
    }
}

// ============================================
// Delete Role
// ============================================
function deleteRole() {
    global $conn;

    $roleId = intval($_POST['role_id'] ?? 0);

    if ($roleId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid role ID.']);
        return;
    }

    // Check if role is assigned to any users
    $check = $conn->prepare("SELECT COUNT(*) as cnt FROM user_roles WHERE role_id = ?");
    $check->bind_param('i', $roleId);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    $check->close();

    if ($result['cnt'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete role — it is assigned to ' . $result['cnt'] . ' user(s). Remove the role from users first.'
        ]);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
    $stmt->bind_param('i', $roleId);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Role deleted successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete role.']);
    }
}
?>
