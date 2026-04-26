<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get parameters
$resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : 0;
$certificate_type = isset($_GET['certificate_type']) ? trim($_GET['certificate_type']) : '';

if ($resident_id <= 0 || empty($certificate_type)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
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
$db_certificate_name = isset($certificateTypeMap[$certificate_type]) ? $certificateTypeMap[$certificate_type] : $certificate_type;

// Determine if this is a 1-time only certificate
$isOneTime = (
    in_array($certificate_type, ['ftjobseeker', 'oath']) || 
    stripos($db_certificate_name, 'Job Seeker') !== false || 
    stripos($db_certificate_name, 'Oath') !== false || 
    $db_certificate_name === 'Barangay ID Card'
);

// Define daily limit for regular certificates
$dailyLimit = 3;

try {
    if ($isOneTime) {
        // For 1-time certificates, check total count (not daily)
        $targetGroup = "certificate_name = 'Barangay ID Card'";
        if (in_array($certificate_type, ['ftjobseeker', 'oath']) || stripos($db_certificate_name, 'Job Seeker') !== false || stripos($db_certificate_name, 'Oath') !== false) {
            $targetGroup = "(certificate_name LIKE '%Job%Seeker%' OR certificate_name LIKE '%Oath%' OR certificate_name LIKE '%RA 11261%')";
        }

        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_count 
            FROM certificate_requests 
            WHERE resident_id = ? 
            AND $targetGroup
        ");
        $stmt->bind_param("i", $resident_id);
    } else {
        // For regular certificates, check today's count
        $today = date('Y-m-d');
        $stmt = $conn->prepare("
            SELECT COUNT(*) as daily_count 
            FROM certificate_requests 
            WHERE resident_id = ? 
            AND certificate_name = ?
            AND DATE(date_requested) = ?
        ");
        $stmt->bind_param("iss", $resident_id, $db_certificate_name, $today);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $usedCount = $isOneTime ? ($row['total_count'] ?? 0) : ($row['daily_count'] ?? 0);
    
    // Default limit logic
    $maxLimit = $isOneTime ? 1 : $dailyLimit;
    // Specific override for ID Card
    if ($db_certificate_name === 'Barangay ID Card') $maxLimit = 2;
    
    $remaining = max(0, $maxLimit - $usedCount);
    
    echo json_encode([
        'success' => true,
        'used' => $usedCount,
        'remaining' => $remaining,
        'limit' => $maxLimit,
        'is_one_time' => $isOneTime,
        'certificate_type' => $certificate_type
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
