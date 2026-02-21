<?php
/**
 * Get Certificates API
 * Returns list of published certificates for dropdown selection
 */

header('Content-Type: application/json');

// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'certificates' => []
];

try {
    // Database connection
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
    
    // Fetch published certificates
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            fee,
            fields
        FROM certificates
        WHERE status = 'Published'
        ORDER BY title ASC
    ");
    
    $stmt->execute();
    $certificates = $stmt->fetchAll();
    
    $response['success'] = true;
    $response['certificates'] = $certificates;
    $response['message'] = 'Certificates fetched successfully';
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Error in get_certificates.php: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error in get_certificates.php: ' . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
?>
