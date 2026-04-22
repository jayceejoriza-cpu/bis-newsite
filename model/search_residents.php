<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$include_deceased = isset($_GET['include_deceased']) && $_GET['include_deceased'] === 'true';
$dob_before = isset($_GET['dob_before']) ? $_GET['dob_before'] : null;
$certificate_type = isset($_GET['certificate_type']) ? trim($_GET['certificate_type']) : '';
$filter = isset($_GET['filter']) ? trim($_GET['filter']) : '';

try {
    $query = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, sex, date_of_birth, activity_status, current_address, mobile_number 
              FROM residents 
              WHERE 1=1";
    
    if (!$include_deceased) {
        $query .= " AND activity_status = 'Alive'";
    }

    $params = [];
    $types = "";

    if ($filter === 'adult') {
        $query .= " AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 18";
    } elseif ($filter === 'minor') {
        $query .= " AND TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18";
    }

    if ($dob_before) {
        $query .= " AND date_of_birth < ?";
        $params[] = $dob_before;
        $types .= "s";
    }

    if (!empty($search)) {
        $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR resident_id LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
    } else {
        $query .= " LIMIT 10";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $residents = [];

    // Mapping for limit checks
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

    while ($row = $result->fetch_assoc()) {
        $fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'] . ' ' . $row['suffix']);
        $row['full_name'] = $fullName;

        // Calculate limits if requested
        if (!empty($certificate_type) && isset($certificateTypeMap[$certificate_type])) {
            $db_name = $certificateTypeMap[$certificate_type];
            $isOneTime = in_array($certificate_type, ['ftjobseeker', 'oath']);
            
            if ($isOneTime) {
                $limitStmt = $conn->prepare("
                    SELECT COUNT(*) as count FROM certificate_requests 
                    WHERE resident_id = ? AND (certificate_name LIKE ? OR certificate_name LIKE ? OR certificate_name LIKE ?)
                ");
                $s1 = '%First Time Jobseeker%'; $s2 = '%Jobseeker%'; $s3 = '%Oath%';
                $limitStmt->bind_param("isss", $row['id'], $s1, $s2, $s3);
            } else {
                $today = date('Y-m-d');
                $limitStmt = $conn->prepare("
                    SELECT COUNT(*) as count FROM certificate_requests 
                    WHERE resident_id = ? AND certificate_name = ? AND DATE(date_requested) = ?
                ");
                $limitStmt->bind_param("iss", $row['id'], $db_name, $today);
            }

            $limitStmt->execute();
            $usedCount = $limitStmt->get_result()->fetch_assoc()['count'] ?? 0;
            $limitStmt->close();

            // certificates.js expects: r.resident_limits[certType].used
            $row['resident_limits'] = [
                $certificate_type => ['used' => (int)$usedCount]
            ];
        }

        $residents[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $residents]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}