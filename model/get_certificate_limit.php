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

// Define certificate types that have 1-time only limit
$oneTimeCertificates = [
    'certificate-ft-jobseeker-assistance.php',
    'certificate-oathofundertaking.php',
    'First Time Jobseeker',
    'Oath of Undertaking'
];

// Determine if this is a 1-time only certificate
$isOneTime = false;
foreach ($oneTimeCertificates as $oneTimeCert) {
    if (stripos($certificate_type, $oneTimeCert) !== false) {
        $isOneTime = true;
        break;
    }
}

// Define daily limit for regular certificates
$dailyLimit = 3;

try {
    if ($isOneTime) {
        // For 1-time certificates, check total count (not daily)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as total_count 
            FROM certificate_requests 
            WHERE resident_id = ? 
            AND (
                certificate_name LIKE ? 
                OR certificate_name LIKE ?
                OR certificate_name LIKE ?
            )
        ");
        $searchTerm1 = '%First Time Jobseeker%';
        $searchTerm2 = '%Jobseeker%';
        $searchTerm3 = '%Oath%';
        $stmt->bind_param("isss", $resident_id, $searchTerm1, $searchTerm2, $searchTerm3);
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
        $stmt->bind_param("iss", $resident_id, $certificate_type, $today);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $usedCount = $isOneTime ? ($row['total_count'] ?? 0) : ($row['daily_count'] ?? 0);
    $maxLimit = $isOneTime ? 1 : $dailyLimit;
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

