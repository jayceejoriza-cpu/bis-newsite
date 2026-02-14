<?php
/**
 * Get Certificate Preview
 * Generates a preview of the certificate with resident data and positioned fields
 */

header('Content-Type: application/json');

// Include configuration
require_once 'config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'preview_html' => '',
    'pdf_path' => '',
    'fields' => [],
    'resident_data' => []
];

try {
    // Get parameters
    $resident_id = isset($_GET['resident_id']) ? intval($_GET['resident_id']) : 0;
    $certificate_id = isset($_GET['certificate_id']) ? intval($_GET['certificate_id']) : 0;
    
    // Validate parameters
    if ($resident_id <= 0 || $certificate_id <= 0) {
        throw new Exception('Invalid resident ID or certificate ID');
    }
    
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
            CONCAT(r.first_name, ' ', IFNULL(r.middle_name, ''), ' ', r.last_name, ' ', IFNULL(r.suffix, '')) AS full_name,
            TIMESTAMPDIFF(YEAR, r.date_of_birth, CURDATE()) AS calculated_age
        FROM residents r
        WHERE r.id = ?
    ");
    $stmt->execute([$resident_id]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        throw new Exception('Resident not found');
    }
    
    // Fetch certificate template
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            description,
            fee,
            template_content,
            fields
        FROM certificates
        WHERE id = ? AND status = 'Published'
    ");
    $stmt->execute([$certificate_id]);
    $certificate = $stmt->fetch();
    
    if (!$certificate) {
        throw new Exception('Certificate not found or not published');
    }
    
    // Parse fields JSON
    $fields = [];
    if (!empty($certificate['fields'])) {
        $fields = json_decode($certificate['fields'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $fields = [];
        }
    }
    
    // Prepare resident data for field mapping
    $residentData = prepareResidentData($resident);
    
    // Get PDF path
    $pdfPath = $certificate['template_content'];
    
    // Check if PDF exists
    if (empty($pdfPath) || !file_exists($pdfPath)) {
        // Generate basic HTML preview if no PDF
        $preview_html = generateBasicTemplate($certificate['title'], $residentData);
        $response['preview_html'] = $preview_html;
        $response['use_html'] = true;
    } else {
        // Return PDF path and fields for client-side rendering
        $response['pdf_path'] = $pdfPath;
        $response['fields'] = $fields;
        $response['resident_data'] = $residentData;
        $response['use_html'] = false;
    }
    
    $response['success'] = true;
    $response['message'] = 'Preview data generated successfully';
    
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Error in get_certificate_preview.php: ' . $e->getMessage());
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Error in get_certificate_preview.php: ' . $e->getMessage());
}

// Output JSON response
echo json_encode($response);

/**
 * Prepare resident data for field mapping
 */
function prepareResidentData($resident) {
    // Build full name with proper formatting
    $fullName = trim($resident['first_name']);
    if (!empty($resident['middle_name'])) {
        $fullName .= ' ' . trim($resident['middle_name']);
    }
    $fullName .= ' ' . trim($resident['last_name']);
    if (!empty($resident['suffix'])) {
        $fullName .= ' ' . trim($resident['suffix']);
    }
    
    // Get current date for date_issued (today)
    $currentDate = new DateTime();
    
    // Get date_expired (1 year from now)
    $expiredDate = new DateTime();
    $expiredDate->modify('+1 year');
    
    return [
        'first_name' => ucwords(strtolower($resident['first_name'])),
        'middle_name' => ucwords(strtolower($resident['middle_name'] ?? '')),
        'last_name' => ucwords(strtolower($resident['last_name'])),
        'suffix' => $resident['suffix'] ?? '',
        'full_name' => ucwords(strtolower($fullName)), // Use proper case, let field settings handle transformation
        'age' => $resident['calculated_age'],
        'sex' => $resident['sex'],
        'civil_status' => $resident['civil_status'],
        'date_of_birth' => date('F d, Y', strtotime($resident['date_of_birth'])),
        'address' => $resident['current_address'],
        'current_address' => $resident['current_address'],
        'mobile_number' => $resident['mobile_number'],
        'email' => $resident['email'] ?? 'N/A',
        'resident_id' => $resident['resident_id'] ?? 'N/A',
        'purok' => $resident['purok'] ?? 'N/A',
        'household_no' => $resident['household_no'] ?? 'N/A',
        'occupation' => $resident['occupation'] ?? 'N/A',
        'place_of_birth' => $resident['place_of_birth'] ?? 'N/A',
        'religion' => $resident['religion'] ?? 'N/A',
        'current_date' => date('F d, Y'),
        'current_year' => date('Y'),
        // Add date_issued and date_expired as ISO format for JavaScript Date parsing
        'date_issued' => $currentDate->format('Y-m-d'),
        'date_expired' => $expiredDate->format('Y-m-d'),
    ];
}

/**
 * Generate a basic certificate template if no PDF exists
 */
function generateBasicTemplate($title, $residentData) {
    $html = '
    <div style="width: 100%; max-width: 800px; margin: 0 auto; padding: 40px; border: 2px solid #000; font-family: Arial, sans-serif;">
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="margin: 0; font-size: 24px; text-transform: uppercase;">Republic of the Philippines</h2>
            <h3 style="margin: 5px 0; font-size: 18px;">Province of [Province Name]</h3>
            <h3 style="margin: 5px 0; font-size: 18px;">Municipality of [Municipality]</h3>
            <h3 style="margin: 5px 0; font-size: 18px; font-weight: bold;">BARANGAY [Barangay Name]</h3>
        </div>
        
        <div style="text-align: center; margin: 40px 0;">
            <h1 style="font-size: 28px; margin: 0; text-decoration: underline;">' . strtoupper(htmlspecialchars($title)) . '</h1>
        </div>
        
        <div style="margin: 30px 0; line-height: 2; font-size: 14px;">
            <p style="text-indent: 50px; text-align: justify;">
                TO WHOM IT MAY CONCERN:
            </p>
            
            <p style="text-indent: 50px; text-align: justify;">
                This is to certify that <strong>' . htmlspecialchars($residentData['full_name']) . '</strong>, 
                ' . htmlspecialchars($residentData['age']) . ' years old, ' . htmlspecialchars($residentData['civil_status']) . ', 
                is a bonafide resident of ' . htmlspecialchars($residentData['current_address']) . '.
            </p>
            
            <p style="text-indent: 50px; text-align: justify;">
                This certification is issued upon the request of the above-named person 
                for whatever legal purpose it may serve.
            </p>
            
            <p style="text-indent: 50px; text-align: justify;">
                Issued this ' . htmlspecialchars($residentData['current_date']) . ' at Barangay [Barangay Name], 
                [Municipality], [Province], Philippines.
            </p>
        </div>
        
        <div style="margin-top: 60px; text-align: right;">
            <div style="display: inline-block; text-align: center;">
                <div style="border-bottom: 2px solid #000; padding: 0 50px; margin-bottom: 5px;">
                    <br><br>
                </div>
                <p style="margin: 0; font-weight: bold;">BARANGAY CAPTAIN</p>
            </div>
        </div>
    </div>
    ';
    
    return $html;
}
?>
