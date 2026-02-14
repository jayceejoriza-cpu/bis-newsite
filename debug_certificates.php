<?php
// Debug script to check certificates in database
require_once 'config.php';

header('Content-Type: text/plain');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "=== DATABASE CONNECTION SUCCESSFUL ===\n\n";
    
    // Check if certificates table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'certificates'")->fetchAll();
    if (empty($tables)) {
        echo "ERROR: 'certificates' table does not exist!\n";
        exit;
    }
    
    echo "✓ 'certificates' table exists\n\n";
    
    // Get table structure
    echo "=== TABLE STRUCTURE ===\n";
    $columns = $pdo->query("DESCRIBE certificates")->fetchAll();
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n=== CERTIFICATES IN DATABASE ===\n";
    
    // Get all certificates
    $stmt = $pdo->query("SELECT id, title, status, template_content, created_at FROM certificates ORDER BY id");
    $certificates = $stmt->fetchAll();
    
    if (empty($certificates)) {
        echo "No certificates found in database.\n";
    } else {
        echo "Total certificates: " . count($certificates) . "\n\n";
        foreach ($certificates as $cert) {
            echo "ID: {$cert['id']}\n";
            echo "Title: {$cert['title']}\n";
            echo "Status: {$cert['status']}\n";
            echo "Template: " . (empty($cert['template_content']) ? 'No template' : 'Has template') . "\n";
            echo "Created: {$cert['created_at']}\n";
            echo "---\n";
        }
    }
    
} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
}
?>
