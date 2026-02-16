<?php
/**
 * Save Barangay Official
 * Handles creating new barangay officials
 */

header('Content-Type: application/json');
require_once 'config.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

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
    
    // Get form data
    $fullname = trim($_POST['fullname'] ?? '');
    $chairmanship = trim($_POST['chairmanship'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $termStart = $_POST['term_start'] ?? '';
    $termEnd = $_POST['term_end'] ?? '';
    $status = $_POST['status'] ?? 'Active';
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $photoData = $_POST['photo'] ?? '';
    
    // Validate required fields
    if (empty($fullname)) {
        throw new Exception('Fullname is required');
    }
    
    if (empty($position)) {
        throw new Exception('Position is required');
    }
    
    if (empty($termStart)) {
        throw new Exception('Term start date is required');
    }
    
    if (empty($termEnd)) {
        throw new Exception('Term end date is required');
    }
    
    // Validate term dates
    $startDate = new DateTime($termStart);
    $endDate = new DateTime($termEnd);
    
    if ($endDate <= $startDate) {
        throw new Exception('Term end date must be after term start date');
    }
    
    // Determine hierarchy level based on position
    $hierarchyLevel = 2; // Default to middle level
    if ($position === 'Barangay Captain') {
        $hierarchyLevel = 1;
    } elseif (in_array($position, ['SK Chairman', 'Barangay Secretary', 'Barangay Treasurer'])) {
        $hierarchyLevel = 3;
    }
    
    // Determine appointment type based on position
    $appointmentType = 'Elected';
    if (in_array($position, ['Barangay Secretary', 'Barangay Treasurer'])) {
        $appointmentType = 'Appointed';
    }
    
    // Handle photo upload
    $photoPath = null;
    if (!empty($photoData) && strpos($photoData, 'data:image') === 0) {
        // Create uploads directory if it doesn't exist
        $uploadDir = 'uploads/officials/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Extract image data
        $imageData = explode(',', $photoData);
        if (count($imageData) === 2) {
            $imageContent = base64_decode($imageData[1]);
            
            // Determine file extension
            $extension = 'jpg';
            if (strpos($photoData, 'image/png') !== false) {
                $extension = 'png';
            } elseif (strpos($photoData, 'image/gif') !== false) {
                $extension = 'gif';
            }
            
            // Generate unique filename
            $filename = 'official_' . time() . '_' . uniqid() . '.' . $extension;
            $photoPath = $uploadDir . $filename;
            
            // Save file
            if (file_put_contents($photoPath, $imageContent) === false) {
                throw new Exception('Failed to save photo');
            }
        }
    }
    
    // Insert official into database
    $stmt = $pdo->prepare("
        INSERT INTO barangay_officials (
            fullname,
            position,
            committee,
            hierarchy_level,
            term_start,
            term_end,
            status,
            appointment_type,
            photo,
            contact_number,
            email,
            created_at,
            updated_at
        ) VALUES (
            :fullname,
            :position,
            :committee,
            :hierarchy_level,
            :term_start,
            :term_end,
            :status,
            :appointment_type,
            :photo,
            :contact_number,
            :email,
            NOW(),
            NOW()
        )
    ");
    
    $stmt->execute([
        ':fullname' => $fullname,
        ':position' => $position,
        ':committee' => $chairmanship,
        ':hierarchy_level' => $hierarchyLevel,
        ':term_start' => $termStart,
        ':term_end' => $termEnd,
        ':status' => $status,
        ':appointment_type' => $appointmentType,
        ':photo' => $photoPath,
        ':contact_number' => !empty($contactNumber) ? $contactNumber : null,
        ':email' => !empty($email) ? $email : null
    ]);
    
    $officialId = $pdo->lastInsertId();
    
    // Also store the fullname temporarily (you can link to residents table later)
    // For now, we'll update the database to add a fullname field
    
    echo json_encode([
        'success' => true,
        'message' => 'Official created successfully',
        'official_id' => $officialId,
        'data' => [
            'fullname' => $fullname,
            'position' => $position,
            'committee' => $chairmanship,
            'term_start' => $termStart,
            'term_end' => $termEnd,
            'status' => $status,
            'photo' => $photoPath
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
