<?php
require_once '../config.php';
require_once '../auth_check.php';
header('Content-Type: application/json');

$sql = "
    SELECT 
        cr.id,
        r.resident_id,
        CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name) as resident_name,
        cr.certificate_name,
        cr.purpose,
        cr.date_requested
    FROM certificate_requests cr
    LEFT JOIN residents r ON cr.resident_id = r.id
    ORDER BY cr.date_requested DESC
";

$result = $conn->query($sql);
$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

echo json_encode(['data' => $data]);
?>
