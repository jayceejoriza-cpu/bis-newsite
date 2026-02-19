<?php
/**
 * Add fullname field to barangay_officials table
 */

$host = 'localhost';
$dbname = 'bmis';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database successfully!\n\n";
    
    $sql = file_get_contents(__DIR__ . '/add_fullname_to_officials.sql');
    $pdo->exec($sql);
    
    echo "✓ Fullname field added to barangay_officials table!\n";
    echo "✓ Migration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
