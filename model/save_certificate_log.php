<?php
/**
 * Save Certificate Log
 * Handles saving certificate print requests to database for tracking limits
 */

// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get form data
$resident_id = isset($_POST['resident_id']) ? intval($_POST['resident_id']) : 0;
$certificate_type = isset($_POST['certificate_type']) ? trim($_POST['certificate_type']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'For Record Purposes';

// Validate required fields
if ($resident_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing resident ID'
    ]);
    exit;
}

if (empty($certificate_type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing certificate type'
    ]);
    exit;
}

// Map JavaScript certificate types to database certificate names
$certificateTypeMap = [
    'indigency' => 'Certificate of Indigency',
    'residency' => 'Certificate of Residency',
    'fishing' => 'Barangay Fishing Clearance',
    'gmrc' => 'Certificate of Good Moral Character',
    'lowincome' => 'Certificate of Low-Income',
    'soloparent' => 'Certificate of Solo Parent',
    'rbc' => 'Registration of Birth Certificate',
    'brgyclearance' => 'Barangay Clearance',
    'brgybusinessclearance' => 'Barangay Business Clearance',
    'businesspermit' => 'Business Permit',
    'vesseldocking' => 'Certificate for Vessel Docking',
    'ftjobseeker' => 'Certificate of Job Seeker Assistance',
    'oath' => 'Certificate of Oath of Undertaking'
];

// Get the actual certificate name for database storage
$certificate_name = isset($certificateTypeMap[$certificate_type]) ? $certificateTypeMap[$certificate_type] : $certificate_type;

// Use certificate_id = 1 as default (Certificate of Residency exists in the certificates table if it exists)
$certificate_id = 1;

// Generate Reference No
$ref_no = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
$date_requested = date('Y-m-d H:i:s');
$created_by = $_SESSION['username'] ?? 'System';

try {
    // Insert Request into certificate_requests table
    // Using certificate_id = 1 as default and storing certificate_name for proper tracking
    $stmt = $conn->prepare("
        INSERT INTO certificate_requests 
        (reference_no, resident_id, certificate_id, certificate_name, purpose, created_by, date_requested, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("siissss", $ref_no, $resident_id, $certificate_id, $certificate_name, $purpose, $created_by, $date_requested);
    
    if ($stmt->execute()) {
        $resStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', IFNULL(CONCAT(middle_name, ' '), ''), last_name, IFNULL(CONCAT(' ', suffix), '')) AS full_name FROM residents WHERE id = ?");
        $resStmt->bind_param('i', $resident_id);
        $resStmt->execute();
        $resResult = $resStmt->get_result()->fetch_assoc();
        $resStmt->close();
        $residentName = $resResult['full_name'] ?? "Resident ID $resident_id";

        if (isset($_SESSION['username'])) {
            $certNameDisplay = str_ireplace('Certificate of ', '', $certificate_name);
            $log_user = $_SESSION['username'];
            $log_action = 'Generate Certificate';
            $log_desc = "Generate a certificate of $certNameDisplay to $residentName";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Certificate request logged successfully',
            'reference_no' => $ref_no
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save certificate request: ' . $conn->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
