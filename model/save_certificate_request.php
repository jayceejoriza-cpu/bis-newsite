<?php
/**
 * Save Certificate Request
 * Handles saving certificate requests to database
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
    'reference_no' => ''
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
    
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get form data
    $resident_id = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;
    $certificate_name = isset($_POST['certificate_name']) ? trim($_POST['certificate_name']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
    $field_values = isset($_POST['field_values']) ? $_POST['field_values'] : '{}';
    
    // Validate required fields
    if ($resident_id <= 0) {
        throw new Exception('Please select a resident');
    }
    
    if (empty($certificate_name)) {
        throw new Exception('Please select a certificate type');
    }
    
    // Generate unique reference number
    $reference_no = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Check if reference number already exists (very unlikely but just in case)
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM certificate_requests WHERE reference_no = ?");
    $checkStmt->execute([$reference_no]);
    
    if ($checkStmt->fetchColumn() > 0) {
        // Generate a new one if it exists
        $reference_no = 'CR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    
    // Insert certificate request
    $stmt = $pdo->prepare("
        INSERT INTO certificate_requests (
            reference_no,
            resident_id,
            certificate_name,
            purpose,
            field_values,
            status,
            date_requested,
            created_at
        ) VALUES (
            :reference_no,
            :resident_id,
            :certificate_name,
            :purpose,
            :field_values,
            'Pending',
            NOW(),
            NOW()
        )
    ");
    
    $stmt->execute([
        ':reference_no' => $reference_no,
        ':resident_id' => $resident_id,
        ':certificate_name' => $certificate_name,
        ':purpose' => $purpose,
        ':field_values' => $field_values
    ]);
    
    $request_id = $pdo->lastInsertId();
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Certificate request created successfully';
    $response['reference_no'] = $reference_no;
    $response['request_id'] = $request_id;
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Certificate request save error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Certificate request save error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit;
?>
