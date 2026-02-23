<?php
/**
 * Permission Helper
 * 
 * Loads the current logged-in user's merged permissions from all their assigned roles.
 * Admin users (role = 'Admin') bypass all permission checks.
 * 
 * Usage: require_once 'permissions.php';
 * Then use: hasPermission('perm_office_view')
 *           isAdmin()
 */

if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

$_currentUserId = $_SESSION['user_id'] ?? 0;
$_userPermissions = [];
$_isAdminUser = false;

if ($_currentUserId > 0) {
    // Check if user is Admin (bypasses all permission checks)
    $adminStmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    if ($adminStmt) {
        $adminStmt->bind_param('i', $_currentUserId);
        $adminStmt->execute();
        $adminResult = $adminStmt->get_result()->fetch_assoc();
        $adminStmt->close();
        if ($adminResult && strtolower($adminResult['role']) === 'admin') {
            $_isAdminUser = true;
        }
    }

    if (!$_isAdminUser) {
        // Fetch all role permissions for this user (merge all assigned roles)
        $permStmt = $conn->prepare("
            SELECT r.permissions
            FROM user_roles ur
            JOIN roles r ON ur.role_id = r.id
            WHERE ur.user_id = ?
        ");
        if ($permStmt) {
            $permStmt->bind_param('i', $_currentUserId);
            $permStmt->execute();
            $permResult = $permStmt->get_result();
            while ($permRow = $permResult->fetch_assoc()) {
                if (!empty($permRow['permissions'])) {
                    $rolePerms = json_decode($permRow['permissions'], true);
                    if (is_array($rolePerms)) {
                        foreach ($rolePerms as $perm => $value) {
                            // A permission is granted if ANY assigned role grants it
                            if ($value === true) {
                                $_userPermissions[$perm] = true;
                            }
                        }
                    }
                }
            }
            $permStmt->close();
        }
    }
}

/**
 * Check if the current user has a specific permission.
 * Admin users always return true.
 */
function hasPermission(string $perm): bool {
    global $_isAdminUser, $_userPermissions;
    if ($_isAdminUser) return true;
    return isset($_userPermissions[$perm]) && $_userPermissions[$perm] === true;
}

/**
 * Check if the current user is an Admin (bypasses all permission checks).
 */
function isAdmin(): bool {
    global $_isAdminUser;
    return $_isAdminUser;
}

/**
 * Redirect to dashboard with an access-denied message if user lacks permission.
 */
function requirePermission(string $perm, string $redirectTo = 'index.php'): void {
    if (!hasPermission($perm)) {
        $_SESSION['access_denied'] = 'You do not have permission to access that page.';
        header("Location: {$redirectTo}");
        exit();
    }
}
?>
