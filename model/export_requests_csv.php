<?php
/**
 * Export Certificate Requests to CSV with Filtering
 */
require_once '../config.php';
require_once '../auth_check.php';
require_once '../permissions.php';

// Enforce permission
requirePermission('perm_req_view');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Service_Requests_Export_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set CSV headers
fputcsv($output, [
    'Resident ID', 'Resident Name', 'Certificate Name', 'Purpose', 'Date Requested', 'Processed By'
]);

// ── 1. COLLECT FILTERS FROM GET ─────────────────────────────────────────────
$whereConditions = ['1=1'];
$params = [];
$types = "";

// Certificate Type Filter
if (!empty($_GET['certificate'])) {
    $whereConditions[] = 'cr.certificate_name = ?';
    $params[] = $_GET['certificate'];
    $types .= "s";
}

// Purpose Filter (Partial Match)
if (!empty($_GET['purpose'])) {
    $whereConditions[] = 'cr.purpose LIKE ?';
    $params[] = '%' . $_GET['purpose'] . '%';
    $types .= "s";
}

// User (Processed By) Filter
if (!empty($_GET['filter_user'])) {
    $whereConditions[] = 'cr.created_by = ?';
    $params[] = $_GET['filter_user'];
    $types .= "s";
}

// Date Range Filters
if (!empty($_GET['from_date'])) {
    $whereConditions[] = 'DATE(cr.date_requested) >= ?';
    $params[] = $_GET['from_date'];
    $types .= "s";
}
if (!empty($_GET['to_date'])) {
    $whereConditions[] = 'DATE(cr.date_requested) <= ?';
    $params[] = $_GET['to_date'];
    $types .= "s";
}

// Search Filter (Matches Table Search Logic for Name and ID)
if (!empty($_GET['search'])) {
    $searchVal = "%" . $_GET['search'] . "%";
    $whereConditions[] = "(r.resident_id LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR r.middle_name LIKE ? OR cr.certificate_name LIKE ? OR cr.purpose LIKE ?)";
    for ($i = 0; $i < 6; $i++) {
        $params[] = $searchVal;
        $types .= "s";
    }
}

$whereClause = implode(' AND ', $whereConditions);

// ── 2. CONSTRUCT AND EXECUTE SQL ───────────────────────────────────────────
$sql = "
    SELECT 
        r.resident_id,
        CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name) AS resident_name,
        cr.certificate_name,
        cr.purpose,
        cr.date_requested,
        cr.created_by
    FROM certificate_requests cr
    LEFT JOIN residents r ON cr.resident_id = r.id
    WHERE $whereClause
    ORDER BY cr.date_requested DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['resident_id'] ?? 'N/A',
            $row['resident_name'] ?? 'N/A',
            $row['certificate_name'],
            $row['purpose'] ?: 'N/A',
            $row['date_requested'] ? date('M d, Y g:i A', strtotime($row['date_requested'])) : 'N/A',
            $row['created_by'] ?: 'System'
        ]);
    }
}

fclose($output);
exit;
?>