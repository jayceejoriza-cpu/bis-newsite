<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get search term from request
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get certificate type parameter (for limit checking)
$certificateType = isset($_GET['certificate_type']) ? trim($_GET['certificate_type']) : '';

// Get optional filter parameter (e.g., 'minor' for < 18 years old)
$filterType = isset($_GET['filter']) ? trim($_GET['filter']) : '';

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

// Get the actual certificate name for database lookup
$dbCertificateName = isset($certificateTypeMap[$certificateType]) ? $certificateTypeMap[$certificateType] : $certificateType;

// Get optional parameter to filter available residents for households
$filterHouseholds = isset($_GET['filter_households']) ? $_GET['filter_households'] === 'true' : false;

// Get optional parameter to exclude a specific resident ID (for RBC child search)
$excludeResidentId = isset($_GET['exclude_resident_id']) ? intval($_GET['exclude_resident_id']) : 0;

// Define certificate types that have 1-time only limit
$oneTimeCertificates = [
    'certificate-ft-jobseeker-assistance.php',
    'certificate-oathofundertaking.php',
    'First Time Jobseeker',
    'Oath of Undertaking'
];

// Determine if this is a 1-time only certificate
$isOneTime = false;
if (!empty($certificateType)) {
    foreach ($oneTimeCertificates as $oneTimeCert) {
        if (stripos($certificateType, $oneTimeCert) !== false) {
            $isOneTime = true;
            break;
        }
    }
}

// Define daily limit for regular certificates
$dailyLimit = 3;
$maxLimit = $isOneTime ? 1 : $dailyLimit;

// Get today's date for daily limit check
$today = date('Y-m-d');

try {
    // Prepare SQL query to search residents - ONLY search by name and resident_id
    $sql = "SELECT 
                r.id,
                r.resident_id,
                CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.suffix,
                r.date_of_birth,
                r.sex,
                r.current_address
            FROM residents r
            WHERE r.activity_status = 'Alive'";
    
    // If filtering for households, exclude residents already assigned
    if ($filterHouseholds) {
        $sql .= " AND r.id NOT IN (
            SELECT household_head_id 
            FROM households 
            WHERE household_head_id IS NOT NULL
        )
        AND r.id NOT IN (
            SELECT resident_id 
            FROM household_members
        )";
    }
    
    // Exclude specific resident ID (for RBC child search to exclude parent)
    if ($excludeResidentId > 0) {
        $sql .= " AND r.id != " . $excludeResidentId;
    }

    // ==========================================
    // NEW: Minor Filter (< 18 years old)
    // ==========================================
    if ($filterType === 'minor') {
        $sql .= " AND r.date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR, r.date_of_birth, CURDATE()) < 18";
    } elseif ($filterType === 'adult' || (!empty($certificateType) && $filterType !== 'minor')) {
        $sql .= " AND r.date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR, r.date_of_birth, CURDATE()) >= 18";
    }
    
    // Add search condition - ONLY search by name and resident_id
    if (!empty($searchTerm)) {
        $searchParam = "%{$searchTerm}%";
        $sql .= " AND (
            CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name) LIKE ?
            OR r.resident_id LIKE ?
        )";
    }
    
    $sql .= " ORDER BY r.last_name, r.first_name LIMIT 100";
    
    // Prepare statement using mysqli
    $stmt = $conn->prepare($sql);
    
    if (!empty($searchTerm)) {
        $stmt->bind_param('ss', $searchParam, $searchParam);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $residents = $result->fetch_all(MYSQLI_ASSOC);
    
    // If certificate type is provided, get certificate usage for each resident
    $residentLimits = [];
    if (!empty($certificateType) && count($residents) > 0) {
        $residentIds = array_column($residents, 'id');
        
        // --- Limit checking logic remains unchanged ---
        if ($isOneTime) {
            $placeholders = str_repeat('?,', count($residentIds) - 1) . '?';
            $searchTerm1 = '%First Time Jobseeker%';
            $searchTerm2 = '%Jobseeker%';
            $searchTerm3 = '%Oath%';
            
            $limitSql = "SELECT resident_id, COUNT(*) as used 
                        FROM certificate_requests 
                        WHERE resident_id IN ($placeholders)
                        AND (certificate_name LIKE ? OR certificate_name LIKE ? OR certificate_name LIKE ?)
                        GROUP BY resident_id";
            
            $limitStmt = $conn->prepare($limitSql);
            $params = array_merge($residentIds, [$searchTerm1, $searchTerm2, $searchTerm3]);
            $limitStmt->bind_param(str_repeat('i', count($residentIds)) . 'sss', ...$params);
        } else {
            $placeholders = str_repeat('?,', count($residentIds) - 1) . '?';
            
            $limitSql = "SELECT resident_id, COUNT(*) as used 
                        FROM certificate_requests 
                        WHERE resident_id IN ($placeholders)
                        AND certificate_name = ?
                        AND DATE(date_requested) = ?
                        GROUP BY resident_id";
            
            $limitStmt = $conn->prepare($limitSql);
            $params = array_merge($residentIds, [$dbCertificateName, $today]);
            $limitStmt->bind_param(str_repeat('i', count($residentIds)) . 'ss', ...$params);
        }
        
        $limitStmt->execute();
        $limitResult = $limitStmt->get_result();
        
        while ($row = $limitResult->fetch_assoc()) {
            $residentLimits[$row['resident_id']] = [
                'used' => intval($row['used']),
                'remaining' => max(0, $maxLimit - intval($row['used']))
            ];
        }
        $limitStmt->close();
    }
    
    // Build residents array with limits included (for each resident)
    $residentsWithLimits = [];
    foreach ($residents as $resident) {
        $resId = $resident['id'];
        $limitInfo = isset($residentLimits[$resId]) ? $residentLimits[$resId] : ['used' => 0, 'remaining' => $maxLimit];
        
        $resident['resident_limits'] = [
            $certificateType => $limitInfo
        ];
        $residentsWithLimits[] = $resident;
    }
    
    $finalResidents = !empty($certificateType) ? $residentsWithLimits : $residents;
    echo json_encode([
        'success' => true,
        'data' => $finalResidents,
        'residents' => $finalResidents,
        'count' => count($finalResidents),
        'filtered' => $filterHouseholds,
        'excluded' => $excludeResidentId,
        'certificate_type' => $certificateType,
        'is_one_time' => $isOneTime,
        'max_limit' => $maxLimit,
        'resident_limits' => $residentLimits
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