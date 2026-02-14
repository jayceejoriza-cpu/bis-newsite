<?php
require_once 'config.php';

echo "=== Archive Table Structure ===\n";
$result = $conn->query('DESCRIBE archive');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - Key: ' . $row['Key'] . "\n";
}

echo "\n=== Sample Archive Records ===\n";
$result = $conn->query('SELECT id, archive_type, record_id, deleted_by FROM archive LIMIT 3');
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Type: " . $row['archive_type'] . " | Record ID: " . $row['record_id'] . " | Deleted By: " . $row['deleted_by'] . "\n";
}
?>
