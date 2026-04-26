<?php
/**
 * Export Residents and Household Data to CSV
 */
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

// Enforce permission
requirePermission('perm_resident_view');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Residents_Filtered_Masterlist_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set CSV headers
fputcsv($output, [
    'Resident ID', 'First Name', 'Middle Name', 'Last Name', 'Suffix', 
    'Sex', 'Date of Birth', 'Place of Birth','Mobile Number', 'Purok', 'Street Name', 'Civil Status',
    'Religion', 'Ethnicity', 'Spouse Name', "Father's Name", "Mother's Maiden Name", 'Legal Guardian Name', 'Number of Children', 'Education', 'Occupation', 'Employment Status',
    '4Ps Member', '4Ps ID Number', 'Voter Status', 'Precinct Number',  
    'Philhealth ID', 'Membership Type', 'Philhealth Category', 
    'Classification by Age/Health Group', 'PWD Status', 'Type of Disability', 'PWD ID Number','Medical History','Activity Status',
    'Household No.', 'Household Address', 'Household Head', 'Ownership Status', 'Landlord Name',
    'Water Source', 'Toilet Facility'
]);

// ── 1. COLLECT FILTERS FROM GET ─────────────────────────────────────────────
$where = ["r.activity_status != 'Archived'"];
$params = [];
$types = "";

// 1. Search (ID or Name)
if (!empty($_GET['search'])) {
    $searchVal = "%" . $_GET['search'] . "%";
    $where[] = "(r.resident_id LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ?)";
    array_push($params, $searchVal, $searchVal, $searchVal, $searchVal);
    $types .= "ssss";
}

// 2. Tab Filter (sync with residents.js logic)
if (isset($_GET['tab'])) {
    if ($_GET['tab'] === 'voters') {
        $where[] = "r.voter_status = 'Yes'";
    } elseif ($_GET['tab'] === 'active') {
        $where[] = "r.activity_status = 'Alive'";
    }
} else {
    // Default UI state hides Deceased if no search/filter is applied
    if (empty($_GET['search']) && empty($_GET['filterAgeHealthGroup'])) {
        $where[] = "r.activity_status != 'Deceased'";
    }
}

// 3. Advanced Filters mapping
$filterMapping = [
    'filterSex'                => 'r.sex',
    'filterPurok'              => 'r.purok',
    'filterAgeHealthGroup'     => 'r.age_health_group',
    'filterPwdStatus'          => 'r.pwd_status',
    'filterReligion'           => 'r.religion',
    'filterCivilStatus'        => 'r.civil_status',
    'filterDateOfBirth'        => 'r.date_of_birth',
    'filterEthnicity'          => 'r.ethnicity',
    'filterEducation'          => 'r.educational_attainment',
    'filterEmploymentStatus'   => 'r.employment_status',
    'filter4ps'                => 'r.fourps_member',
    'filterVoterStatus'        => 'r.voter_status',
    'filterMembershipType'     => 'r.membership_type',
    'filterPhilhealthCategory' => 'r.philhealth_category',
    'filterUsingFpMethod'      => 'r.using_fp_method',
    'filterFpMethodsUsed'      => 'r.fp_methods_used',
    'filterFpStatus'           => 'r.fp_status'
];

foreach ($filterMapping as $getParam => $dbColumn) {
    if (!empty($_GET[$getParam])) {
        $where[] = "$dbColumn = ?";
        $params[] = $_GET[$getParam];
        $types .= "s";
    }
}

// Partial matches
if (!empty($_GET['filterOccupation'])) {
    $where[] = "r.occupation LIKE ?";
    $params[] = "%" . $_GET['filterOccupation'] . "%";
    $types .= "s";
}
if (!empty($_GET['filterMedicalHistory'])) {
    $where[] = "r.medical_history LIKE ?";
    $params[] = "%" . $_GET['filterMedicalHistory'] . "%";
    $types .= "s";
}

$whereClause = implode(" AND ", $where);

// ── 2. CONSTRUCT AND EXECUTE SQL ───────────────────────────────────────────
$sql = "SELECT r.*, h.household_number, h.address AS hh_address, h.ownership_status AS hh_ownership, h.landlord_name,
                 h.water_source_type, h.toilet_facility_type,
                 (SELECT CONCAT(head.last_name, ', ', head.first_name) 
                  FROM residents head WHERE head.id = h.household_head_id) as hh_head_name
          FROM residents r
          LEFT JOIN household_members hm ON r.id = hm.resident_id
          LEFT JOIN households h ON hm.household_id = h.id
          WHERE $whereClause
          ORDER BY r.last_name, r.first_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['resident_id'],
            $row['first_name'],
            $row['middle_name'],
            $row['last_name'],
            $row['suffix'],
            $row['sex'],
            $row['date_of_birth'],
            $row['place_of_birth'] ?? 'N/A', 
            $row['mobile_number'],
            $row['purok'],
            $row['street_name'],
            $row['civil_status'],
            $row['religion'],
            $row['ethnicity'],
            $row['spouse_name'] ?? 'N/A',
            $row['father_name'] ?? 'N/A',
            $row['mother_name'] ?? 'N/A',
            $row['legal_guardian_name'] ?? 'N/A',
            $row['number_of_children'] ?? '0',
            $row['educational_attainment'],
            $row['occupation'],
            $row['employment_status'],
            $row['fourps_member'],
            $row['fourps_id'] ?? 'N/A',
            $row['voter_status'],
            $row['precinct_number'] ?? 'N/A',
            $row['philhealth_id'],
            $row['membership_type'] ?? 'N/A',
            $row['philhealth_category'],
            $row['age_health_group'] ?? 'N/A',
            $row['pwd_status'] ?? 'No',
            $row['pwd_type'] ?? 'N/A',
            $row['pwd_id_number'] ?? 'N/A', 
            $row['medical_history'],
            $row['activity_status'],
            // Household Info
            $row['household_number'] ?? 'N/A',
            $row['hh_address'] ?? 'N/A',
            $row['hh_head_name'] ?? 'N/A',
            $row['hh_ownership'] ?? 'N/A',
            $row['landlord_name'] ?? 'N/A',
            $row['water_source_type'] ?? 'N/A',
            $row['toilet_facility_type'] ?? 'N/A'
        ]);
    }
}

fclose($output);
exit;
?>
