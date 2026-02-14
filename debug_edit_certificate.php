<?php
// Debug version to see what's happening
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Information</h2>";

// Check if ID is provided
echo "<p><strong>GET Parameters:</strong></p>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p style='color: red;'><strong>ERROR:</strong> No certificate ID provided in URL</p>";
    echo "<p>Expected URL format: edit-certificate.php?id=1</p>";
    exit;
}

$certificateId = intval($_GET['id']);
echo "<p><strong>Certificate ID:</strong> $certificateId</p>";

// Include configuration
require_once 'config.php';

echo "<p><strong>Database Config:</strong></p>";
echo "<ul>";
echo "<li>Host: " . DB_HOST . "</li>";
echo "<li>Database: " . DB_NAME . "</li>";
echo "<li>User: " . DB_USER . "</li>";
echo "</ul>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<p style='color: green;'><strong>✓ Database connection successful</strong></p>";
    
    // Check if certificates table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'certificates'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color: green;'><strong>✓ Certificates table exists</strong></p>";
        
        // Count total certificates
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM certificates");
        $total = $countStmt->fetch()['total'];
        echo "<p><strong>Total certificates in database:</strong> $total</p>";
        
        // List all certificate IDs
        $idsStmt = $pdo->query("SELECT id, title FROM certificates");
        $allCerts = $idsStmt->fetchAll();
        echo "<p><strong>Available certificates:</strong></p>";
        echo "<ul>";
        foreach ($allCerts as $cert) {
            echo "<li>ID: {$cert['id']} - {$cert['title']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'><strong>✗ Certificates table does not exist</strong></p>";
    }
    
    // Try to fetch the specific certificate
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            fee,
            status,
            template_content,
            pdf_path,
            fields,
            date_issued,
            date_expired,
            created_at,
            updated_at
        FROM certificates
        WHERE id = ?
    ");
    
    $stmt->execute([$certificateId]);
    $certificateData = $stmt->fetch();
    
    if ($certificateData) {
        echo "<p style='color: green;'><strong>✓ Certificate found!</strong></p>";
        echo "<p><strong>Certificate Data:</strong></p>";
        echo "<pre>";
        print_r($certificateData);
        echo "</pre>";
        echo "<p style='color: green;'><strong>The edit-certificate.php page should work with this ID</strong></p>";
        echo "<p><a href='edit-certificate.php?id=$certificateId' style='padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Go to Edit Page</a></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Certificate with ID $certificateId not found</strong></p>";
        echo "<p>This is why edit-certificate.php keeps redirecting!</p>";
        echo "<p><strong>Solution:</strong> Use one of the available certificate IDs listed above</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>✗ Database error:</strong> " . $e->getMessage() . "</p>";
}
?>
