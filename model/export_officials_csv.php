<?php
/**
 * Export Barangay Officials to CSV with Filtering
 */
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

// Enforce permission
requirePermission('perm_officials_view');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Barangay_Officials_Export_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set CSV headers
fputcsv($output, [
    'Full Name', 'Position', 'Committee', 'Term Start', 'Term End', 'Status', 'Appointment Type', 'Contact Number'
]);

// ── 1. COLLECT FILTERS FROM GET ─────────────────────────────────────────────
$where = ["1=1"];
$params = [];
$types = "";

// Search Filter
if (!empty($_GET['search'])) {
    $searchVal = "%" . $_GET['search'] . "%";
    $where[] = "(bo.fullname LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR bo.position LIKE ? OR bo.committee LIKE ?)";
    array_push($params, $searchVal, $searchVal, $searchVal, $searchVal, $searchVal);
    $types .= "sssss";
}

// Tab Status Filter (Active, Inactive, Completed)
if (!empty($_GET['tab']) && $_GET['tab'] !== 'all') {
    $where[] = "bo.status = ?";
    $params[] = $_GET['tab'];
    $types .= "s";
}

// Term Period Filter
if (!empty($_GET['term_start']) && !empty($_GET['term_end'])) {
    $where[] = "bo.term_start <= ? AND bo.term_end >= ?";
    array_push($params, $_GET['term_end'], $_GET['term_start']);
    $types .= "ss";
}

$whereClause = implode(" AND ", $where);

// ── 2. CONSTRUCT AND EXECUTE SQL ───────────────────────────────────────────
$sql = "SELECT bo.*, 
               r.first_name, r.middle_name, r.last_name, r.suffix,
               r.activity_status as resident_activity
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE $whereClause
        ORDER BY bo.hierarchy_level ASC, bo.position ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Resolve Name
        $fullName = !empty($row['fullname']) ? $row['fullname'] : trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? '') . ' ' . ($row['suffix'] ?? ''));
        
        // Handle Deceased status override
        $status = $row['status'];
        if ($row['resident_activity'] === 'Deceased') $status = 'Deceased';

        fputcsv($output, [
            strtoupper($fullName),
            $row['position'],
            $row['committee'] ?: 'N/A',
            date('M d, Y', strtotime($row['term_start'])),
            date('M d, Y', strtotime($row['term_end'])),
            $status,
            $row['appointment_type'],
            '+63 ' . ($row['contact_number'] ?: 'N/A')
        ]);
    }
}

fclose($output);
exit;
?>