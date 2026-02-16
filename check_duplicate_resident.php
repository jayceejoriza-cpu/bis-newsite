<?php
/**
 * Check Duplicate Resident API
 * 
 * This endpoint checks if a resident with similar information already exists
 * Used for real-time duplicate detection in the create resident form
 */

// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Initialize response array
$response = [
    'success' => false,
    'has_duplicates' => false,
    'duplicates' => [],
    'message' => ''
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get input data
    $firstName = isset($_POST['firstName']) ? trim($_POST['firstName']) : '';
    $lastName = isset($_POST['lastName']) ? trim($_POST['lastName']) : '';
    $dateOfBirth = isset($_POST['dateOfBirth']) ? trim($_POST['dateOfBirth']) : '';
    $mobileNumber = isset($_POST['mobileNumber']) ? trim($_POST['mobileNumber']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $philhealthId = isset($_POST['philhealthId']) ? trim($_POST['philhealthId']) : '';
    $residentId = isset($_POST['residentId']) ? intval($_POST['residentId']) : null; // For edit mode

    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Set charset
    $conn->set_charset('utf8mb4');

    $duplicates = [];

    // ============================================
    // Check 1: Name + Date of Birth Combination
    // ============================================
    if (!empty($firstName) && !empty($lastName) && !empty($dateOfBirth)) {
        $sql = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, 
                       date_of_birth, mobile_number, email, current_address, 
                       verification_status, activity_status, created_at
                FROM residents 
                WHERE LOWER(first_name) = LOWER(?) 
                AND LOWER(last_name) = LOWER(?) 
                AND date_of_birth = ?
                AND activity_status != 'Deceased'";
        
        // Exclude current resident in edit mode
        if ($residentId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($residentId) {
            $stmt->bind_param("sssi", $firstName, $lastName, $dateOfBirth, $residentId);
        } else {
            $stmt->bind_param("sss", $firstName, $lastName, $dateOfBirth);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $duplicates[] = [
                    'type' => 'name_dob',
                    'reason' => 'Same name and date of birth',
                    'severity' => 'high',
                    'data' => $row
                ];
            }
        }
        
        $stmt->close();
    }

    // ============================================
    // Check 2: Mobile Number
    // ============================================
    if (!empty($mobileNumber)) {
        $sql = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, 
                       date_of_birth, mobile_number, email, current_address, 
                       verification_status, activity_status, created_at
                FROM residents 
                WHERE mobile_number = ?
                AND activity_status != 'Deceased'";
        
        // Exclude current resident in edit mode
        if ($residentId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($residentId) {
            $stmt->bind_param("si", $mobileNumber, $residentId);
        } else {
            $stmt->bind_param("s", $mobileNumber);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if this duplicate is already added (from name+DOB check)
                $alreadyAdded = false;
                foreach ($duplicates as $dup) {
                    if ($dup['data']['id'] == $row['id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                
                if (!$alreadyAdded) {
                    $duplicates[] = [
                        'type' => 'mobile',
                        'reason' => 'Mobile number already registered',
                        'severity' => 'high',
                        'data' => $row
                    ];
                }
            }
        }
        
        $stmt->close();
    }

    // ============================================
    // Check 3: Email Address
    // ============================================
    if (!empty($email)) {
        $sql = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, 
                       date_of_birth, mobile_number, email, current_address, 
                       verification_status, activity_status, created_at
                FROM residents 
                WHERE LOWER(email) = LOWER(?)
                AND activity_status != 'Deceased'";
        
        // Exclude current resident in edit mode
        if ($residentId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($residentId) {
            $stmt->bind_param("si", $email, $residentId);
        } else {
            $stmt->bind_param("s", $email);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if this duplicate is already added
                $alreadyAdded = false;
                foreach ($duplicates as $dup) {
                    if ($dup['data']['id'] == $row['id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                
                if (!$alreadyAdded) {
                    $duplicates[] = [
                        'type' => 'email',
                        'reason' => 'Email address already registered',
                        'severity' => 'medium',
                        'data' => $row
                    ];
                }
            }
        }
        
        $stmt->close();
    }

    // ============================================
    // Check 4: Philhealth ID
    // ============================================
    if (!empty($philhealthId)) {
        $sql = "SELECT id, resident_id, first_name, middle_name, last_name, suffix, 
                       date_of_birth, mobile_number, email, current_address, 
                       philhealth_id, verification_status, activity_status, created_at
                FROM residents 
                WHERE philhealth_id = ?
                AND activity_status != 'Deceased'";
        
        // Exclude current resident in edit mode
        if ($residentId) {
            $sql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($sql);
        
        if ($residentId) {
            $stmt->bind_param("si", $philhealthId, $residentId);
        } else {
            $stmt->bind_param("s", $philhealthId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check if this duplicate is already added
                $alreadyAdded = false;
                foreach ($duplicates as $dup) {
                    if ($dup['data']['id'] == $row['id']) {
                        $alreadyAdded = true;
                        break;
                    }
                }
                
                if (!$alreadyAdded) {
                    $duplicates[] = [
                        'type' => 'philhealth',
                        'reason' => 'Philhealth ID already registered',
                        'severity' => 'medium',
                        'data' => $row
                    ];
                }
            }
        }
        
        $stmt->close();
    }

    // Close connection
    $conn->close();

    // Prepare response
    if (count($duplicates) > 0) {
        $response['success'] = true;
        $response['has_duplicates'] = true;
        $response['duplicates'] = $duplicates;
        $response['message'] = 'Potential duplicate resident(s) found';
    } else {
        $response['success'] = true;
        $response['has_duplicates'] = false;
        $response['message'] = 'No duplicates found';
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
} finally {
    // Close database connection if still open
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}

// Output JSON response
echo json_encode($response);
?>
