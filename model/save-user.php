<?php
/**
 * Save User - Backend Handler
 * Handles: create, edit, toggle_status, delete
 * Roles are stored in user_roles table (many-to-many)
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
    case 'create':        createUser();      break;
    case 'edit':          editUser();        break;
    case 'toggle_status': toggleStatus();    break;
    case 'delete':        deleteUser();      break;
    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
        exit;
}

// ============================================
// Helper: Sync user roles in user_roles table
// ============================================
function syncUserRoles($conn, $userId, $roleIds) {
    // Remove all existing roles for this user
    $del = $conn->prepare("DELETE FROM user_roles WHERE user_id = ?");
    $del->bind_param('i', $userId);
    $del->execute();
    $del->close();

    // Insert new roles
    if (!empty($roleIds)) {
        $ins = $conn->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
        foreach ($roleIds as $roleId) {
            $roleId = intval($roleId);
            if ($roleId > 0) {
                $ins->bind_param('ii', $userId, $roleId);
                $ins->execute();
            }
        }
        $ins->close();
    }
}

// ============================================
// Create User
// ============================================
function createUser() {
    global $conn;

    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username']  ?? '');
    $email    = trim($_POST['email']     ?? '');
    $password = $_POST['password']       ?? '';
    $status   = trim($_POST['status']    ?? 'Active');
    // roles[] is an array of role IDs from checkboxes
    $roleIds  = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];

    // Validate
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required.', 'field' => 'fullName']);
        return;
    }
    if (empty($username) || strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.', 'field' => 'username']);
        return;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'A valid email is required.', 'field' => 'email']);
        return;
    }
    if (empty($password) || strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.', 'field' => 'password']);
        return;
    }
    if (!in_array($status, ['Active', 'Inactive'])) {
        $status = 'Active';
    }

    // Check duplicate username
    $chkUser = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $chkUser->bind_param('s', $username);
    $chkUser->execute();
    $chkUser->store_result();
    if ($chkUser->num_rows > 0) {
        $chkUser->close();
        echo json_encode(['success' => false, 'message' => 'Username already exists.', 'field' => 'username']);
        return;
    }
    $chkUser->close();

    // Check duplicate email
    $chkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $chkEmail->bind_param('s', $email);
    $chkEmail->execute();
    $chkEmail->store_result();
    if ($chkEmail->num_rows > 0) {
        $chkEmail->close();
        echo json_encode(['success' => false, 'message' => 'Email already exists.', 'field' => 'email']);
        return;
    }
    $chkEmail->close();

    // Set role field from first selected role name (column is now VARCHAR)
    $legacyRole = 'Staff';
    if (!empty($roleIds)) {
        $firstRoleId = intval($roleIds[0]);
        $rStmt = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $rStmt->bind_param('i', $firstRoleId);
        $rStmt->execute();
        $rResult = $rStmt->get_result()->fetch_assoc();
        $rStmt->close();
        if ($rResult && !empty($rResult['name'])) {
            $legacyRole = $rResult['name'];
        }
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $username, $hashedPassword, $fullName, $email, $legacyRole, $status);

    if ($stmt->execute()) {
        $newUserId = $conn->insert_id;
        $stmt->close();

        // Sync roles
        syncUserRoles($conn, $newUserId, $roleIds);

        echo json_encode(['success' => true, 'message' => 'User created successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to create user. Please try again.']);
    }
}

// ============================================
// Edit User
// ============================================
function editUser() {
    global $conn;

    $userId   = intval($_POST['user_id']   ?? 0);
    $fullName = trim($_POST['full_name']   ?? '');
    $username = trim($_POST['username']    ?? '');
    $email    = trim($_POST['email']       ?? '');
    $password = $_POST['password']         ?? '';
    $status   = trim($_POST['status']      ?? 'Active');
    $roleIds  = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        return;
    }

    // Validate
    if (empty($fullName)) {
        echo json_encode(['success' => false, 'message' => 'Full name is required.', 'field' => 'fullName']);
        return;
    }
    if (empty($username) || strlen($username) < 3) {
        echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.', 'field' => 'username']);
        return;
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'A valid email is required.', 'field' => 'email']);
        return;
    }
    if (!in_array($status, ['Active', 'Inactive'])) {
        $status = 'Active';
    }

    // Check duplicate username (exclude current)
    $chkUser = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    $chkUser->bind_param('si', $username, $userId);
    $chkUser->execute();
    $chkUser->store_result();
    if ($chkUser->num_rows > 0) {
        $chkUser->close();
        echo json_encode(['success' => false, 'message' => 'Username already exists.', 'field' => 'username']);
        return;
    }
    $chkUser->close();

    // Check duplicate email (exclude current)
    $chkEmail = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $chkEmail->bind_param('si', $email, $userId);
    $chkEmail->execute();
    $chkEmail->store_result();
    if ($chkEmail->num_rows > 0) {
        $chkEmail->close();
        echo json_encode(['success' => false, 'message' => 'Email already exists.', 'field' => 'email']);
        return;
    }
    $chkEmail->close();

    // Set role field from first selected role name (column is now VARCHAR)
    $legacyRole = 'Staff';
    if (!empty($roleIds)) {
        $firstRoleId = intval($roleIds[0]);
        $rStmt = $conn->prepare("SELECT name FROM roles WHERE id = ? LIMIT 1");
        $rStmt->bind_param('i', $firstRoleId);
        $rStmt->execute();
        $rResult = $rStmt->get_result()->fetch_assoc();
        $rStmt->close();
        if ($rResult && !empty($rResult['name'])) {
            $legacyRole = $rResult['name'];
        }
    }

    // Update with or without password
    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.', 'field' => 'password']);
            return;
        }
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, full_name=?, email=?, role=?, status=? WHERE id=?");
        $stmt->bind_param('ssssssi', $username, $hashedPassword, $fullName, $email, $legacyRole, $status, $userId);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, full_name=?, email=?, role=?, status=? WHERE id=?");
        $stmt->bind_param('sssssi', $username, $fullName, $email, $legacyRole, $status, $userId);
    }

    if ($stmt->execute()) {
        $stmt->close();
        // Sync roles
        syncUserRoles($conn, $userId, $roleIds);
        echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update user. Please try again.']);
    }
}

// ============================================
// Toggle Status
// ============================================
function toggleStatus() {
    global $conn;

    $userId    = intval($_POST['user_id'] ?? 0);
    $newStatus = trim($_POST['status']    ?? '');

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        return;
    }
    if (!in_array($newStatus, ['Active', 'Inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        $msg = $newStatus === 'Active' ? 'User activated successfully.' : 'User deactivated successfully.';
        echo json_encode(['success' => true, 'message' => $msg]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
    }
}

// ============================================
// Delete User
// ============================================
function deleteUser() {
    global $conn;

    $userId = intval($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID.']);
        return;
    }

    // Prevent deleting own account
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        return;
    }

    // user_roles will cascade delete via FK
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'message' => 'Failed to delete user.']);
    }
}
?>
