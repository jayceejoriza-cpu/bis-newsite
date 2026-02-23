<?php
/**
 * Run Roles Tables Migration
 * Access this file once via browser: http://localhost/bis-newsite/bis-newsite/database/run_roles_migration.php
 */

// Use absolute path so it works from any working directory
$base = dirname(__DIR__);
require_once $base . '/config.php';

$sql = file_get_contents(__DIR__ . '/create_roles_tables.sql');

// Split into individual statements (skip comments and empty lines)
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn($s) => !empty($s) && !preg_match('/^--/', $s)
);

$success = 0;
$errors  = [];

foreach ($statements as $statement) {
    $statement = trim($statement);
    if (empty($statement)) continue;

    if ($conn->query($statement) === TRUE) {
        $success++;
    } else {
        // Ignore "already exists" errors
        if (strpos($conn->error, 'already exists') === false &&
            strpos($conn->error, 'Duplicate entry') === false) {
            $errors[] = htmlspecialchars($conn->error) . '<br><small>' . htmlspecialchars($statement) . '</small>';
        } else {
            $success++; // Count as success (idempotent)
        }
    }
}

echo '<style>body{font-family:sans-serif;padding:30px;max-width:800px;margin:0 auto;}
.ok{color:#065f46;background:#d1fae5;padding:12px 16px;border-radius:8px;margin:8px 0;}
.err{color:#991b1b;background:#fee2e2;padding:12px 16px;border-radius:8px;margin:8px 0;}
h2{color:#1f2937;}</style>';

echo '<h2>🗄️ Roles Migration</h2>';

if (empty($errors)) {
    echo '<div class="ok">✅ Migration completed successfully! ' . $success . ' statement(s) executed.</div>';
    echo '<div class="ok">✅ Tables <strong>roles</strong> and <strong>user_roles</strong> are ready.</div>';
    echo '<div class="ok">✅ Default roles seeded: Administrator, Staff, Kagawad, Test, Viewer.</div>';
} else {
    echo '<div class="ok">✅ ' . $success . ' statement(s) executed successfully.</div>';
    foreach ($errors as $err) {
        echo '<div class="err">❌ ' . $err . '</div>';
    }
}

echo '<br><a href="../official-user.php" style="color:#3b82f6;">→ Go to Users page</a> &nbsp;|&nbsp; ';
echo '<a href="../roles.php" style="color:#3b82f6;">→ Go to Roles page</a>';
?>
