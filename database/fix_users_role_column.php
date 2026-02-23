<?php
require_once __DIR__ . '/../config.php';

$sql = "ALTER TABLE users MODIFY COLUMN role VARCHAR(100) NOT NULL DEFAULT 'Staff'";
$result = $conn->query($sql);

if ($result) {
    echo "SUCCESS: users.role column changed from ENUM to VARCHAR(100).\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}

$conn->close();
?>
