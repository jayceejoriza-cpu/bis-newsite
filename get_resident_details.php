<?php
// Include configuration
require_once 'config.php';

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
    
    // Fetch resident details
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            GROUP_CONCAT(
                CONCAT_WS('|', 
                    ec.contact_name, 
                    ec.relationship, 
                    ec.contact_number, 
                    ec.address
                ) SEPARATOR '||'
            ) as emergency_contacts
        FROM residents r
        LEFT JOIN emergency_contacts ec ON r.id = ec.resident_id
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
