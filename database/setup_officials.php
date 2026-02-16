<?php
/**
 * Setup script for barangay officials table
 * Run this file to create the officials table and insert sample data
 */

// Database configuration
$host = 'localhost';
$dbname = 'bmis';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "Connected to database successfully!\n\n";
    
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_officials_table.sql');
    
    if ($sql === false) {
        throw new Exception("Could not read SQL file");
    }
    
    // Execute SQL
    $pdo->exec($sql);
    
    echo "✓ Officials table created successfully!\n";
    echo "✓ Sample data inserted successfully!\n\n";
    echo "You can now access the officials page at: officials.php\n";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
