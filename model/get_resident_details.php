<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON header
header('Content-Type: application/json');

// Check if resident ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Resident ID is required'
    ]);
    exit;
}

$residentId = intval($_GET['id']);

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
    
    // Fetch resident details with household information
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            GROUP_CONCAT(
                DISTINCT CONCAT_WS('|', 
                    ec.contact_name, 
                    ec.relationship, 
                    ec.contact_number, 
                    ec.address
                ) SEPARATOR '||'
            ) as emergency_contacts,
            hm.household_id       AS hm_household_id,
            hm.relationship_to_head,
            hm.is_head,
            h.id                  AS household_id,
            h.household_number,
            h.household_contact   AS hh_contact,
            h.address             AS hh_address,
            h.water_source_type   AS hh_water_source_type,
            h.toilet_facility_type AS hh_toilet_facility_type,
            TRIM(CONCAT(
                head_r.first_name, ' ',
                IFNULL(CONCAT(head_r.middle_name, ' '), ''),
                head_r.last_name,
                IFNULL(CONCAT(' ', head_r.suffix), '')
            )) AS hh_head_name
        FROM residents r
        LEFT JOIN emergency_contacts ec ON r.id = ec.resident_id
        LEFT JOIN household_members hm ON hm.resident_id = r.id
        LEFT JOIN households h ON hm.household_id = h.id
        LEFT JOIN residents head_r ON h.household_head_id = head_r.id
        WHERE r.id = :id
        GROUP BY r.id
    ");
    
    $stmt->execute(['id' => $residentId]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        echo json_encode([
            'success' => false,
            'message' => 'Resident not found'
        ]);
        exit;
    }
    
    // Parse emergency contacts
    $emergencyContacts = [];
    if (!empty($resident['emergency_contacts'])) {
        $contacts = explode('||', $resident['emergency_contacts']);
        foreach ($contacts as $contact) {
            $parts = explode('|', $contact);
            if (count($parts) >= 3) {
                $emergencyContacts[] = [
                    'name' => $parts[0] ?? '',
                    'relationship' => $parts[1] ?? '',
                    'contact_number' => $parts[2] ?? '',
                    'address' => $parts[3] ?? ''
                ];
            }
        }
    }
    
    // Remove the concatenated emergency contacts field
    unset($resident['emergency_contacts']);
    
    // Add parsed emergency contacts
    $resident['emergency_contacts_list'] = $emergencyContacts;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $resident
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching resident details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>
