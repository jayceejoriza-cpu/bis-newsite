<?php
/**
 * Save Certificate Handler
 * Handles certificate creation and updates
 */

// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => ''
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
    $certificateId = isset($_POST['certificateId']) ? intval($_POST['certificateId']) : null;
    $certificateName = isset($_POST['certificateName']) ? trim($_POST['certificateName']) : '';
    $certificateFee = isset($_POST['certificateFee']) ? floatval($_POST['certificateFee']) : 0.00;
    $dateIssued = isset($_POST['dateIssued']) ? $_POST['dateIssued'] : null;
    $dateExpired = isset($_POST['dateExpired']) ? $_POST['dateExpired'] : null;
    $published = isset($_POST['published']) && $_POST['published'] == '1' ? 'Published' : 'Unpublished';
    $fields = isset($_POST['fields']) ? $_POST['fields'] : '[]';
    
    // Validate required fields
    if (empty($certificateName)) {
        throw new Exception('Certificate name is required');
    }
    
    if (empty($dateIssued)) {
        throw new Exception('Date issued is required');
    }
    
    if (empty($dateExpired)) {
        throw new Exception('Date expired is required');
    }
    
    // Validate dates
    $issuedDate = new DateTime($dateIssued);
    $expiredDate = new DateTime($dateExpired);
    
    if ($expiredDate <= $issuedDate) {
        throw new Exception('Expiration date must be after issue date');
    }
    
    // Handle PDF file upload
    $pdfPath = null;
    
    if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/certificates/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['pdfFile']['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        if ($fileExtension !== 'pdf') {
            throw new Exception('Only PDF files are allowed');
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['pdfFile']['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size must not exceed 5MB');
        }
        
        // Generate unique filename
        $fileName = 'certificate_' . time() . '_' . uniqid() . '.pdf';
        $uploadPath = 'uploads/certificates/' . $fileName; // DB path
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['pdfFile']['tmp_name'], $uploadDir . $fileName)) {
            throw new Exception('Failed to upload PDF file');
        }
        
        $pdfPath = $uploadPath;
    }
    
    // Prepare certificate description
    $description = "Certificate valid from " . date('M d, Y', strtotime($dateIssued)) . 
                   " to " . date('M d, Y', strtotime($dateExpired));
    
    // Check if updating existing certificate
    if ($certificateId) {
        // UPDATE existing certificate
        
        // If no new PDF uploaded, keep the existing one
        if ($pdfPath === null) {
            // Get existing template_content
            $stmt = $pdo->prepare("SELECT template_content FROM certificates WHERE id = :id");
            $stmt->execute([':id' => $certificateId]);
            $existing = $stmt->fetch();
            if ($existing) {
                $pdfPath = $existing['template_content'];
            }
        }
        
        $stmt = $pdo->prepare("
            UPDATE certificates SET
                title = :title,
                description = :description,
                fee = :fee,
                status = :status,
                template_content = :template_content,
                fields = :fields,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':id' => $certificateId,
            ':title' => $certificateName,
            ':description' => $description,
            ':fee' => $certificateFee,
            ':status' => $published,
            ':template_content' => $pdfPath,
            ':fields' => $fields
        ]);
        
        // Success response
        $response['success'] = true;
        $response['message'] = 'Certificate updated successfully';
        $response['certificate_id'] = $certificateId;
        
    } else {
        // INSERT new certificate
        
        // PDF is required for new certificates
        if ($pdfPath === null) {
            throw new Exception('PDF template is required for new certificates');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO certificates (
                title,
                description,
                fee,
                status,
                template_content,
                fields,
                created_at,
                updated_at
            ) VALUES (
                :title,
                :description,
                :fee,
                :status,
                :template_content,
                :fields,
                NOW(),
                NOW()
            )
        ");
        
        $stmt->execute([
            ':title' => $certificateName,
            ':description' => $description,
            ':fee' => $certificateFee,
            ':status' => $published,
            ':template_content' => $pdfPath,
            ':fields' => $fields
        ]);
        
        $certificateId = $pdo->lastInsertId();
        
        // Success response
        $response['success'] = true;
        $response['message'] = 'Certificate created successfully';
        $response['certificate_id'] = $certificateId;
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Certificate save error: ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log('Certificate save error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit;
?>
