<?php
/**
 * Save Resident Handler
 * 
 * This script processes the create resident form submission
 * and saves the data to the database
 */

// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON response header
header('Content-Type: application/json');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'errors' => []
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Determine mode: create or update
    $mode = $_POST['mode'] ?? 'create';
    $residentId = isset($_POST['residentId']) ? intval($_POST['residentId']) : null;

    // Validate mode
    if ($mode === 'update' && !$residentId) {
        throw new Exception('Resident ID is required for update operation');
    }

    // Create database connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset('utf8mb4');

    // Start transaction
    $conn->begin_transaction();

    // ============================================
    // Handle Photo Upload
    // ============================================
    $photoPath = null;
    
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/residents/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
        }
        
        // Check file size (1MB max)
        if ($_FILES['photo']['size'] > 1048576) {
            throw new Exception('File size exceeds 1MB limit.');
        }
        
        // Generate unique filename
        $fileName = 'resident_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $photoPath = 'assets/uploads/residents/' . $fileName; // Store relative path in DB
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $fileName)) {
            throw new Exception('Failed to upload photo.');
        }
    } elseif (isset($_POST['webcam_photo']) && !empty($_POST['webcam_photo'])) {
        // Handle webcam photo (base64 encoded)
        $uploadDir = '../assets/uploads/residents/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $imageData = $_POST['webcam_photo'];
        
        // Remove data URI prefix if present
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
            $fileExtension = strtolower($matches[1]);
        } else {
            $fileExtension = 'jpg';
        }
        
        $imageData = base64_decode($imageData);
        
        if ($imageData === false) {
            throw new Exception('Failed to decode webcam photo.');
        }
        
        $fileName = 'resident_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $photoPath = 'assets/uploads/residents/' . $fileName; // Store relative path in DB
        
        if (file_put_contents($uploadDir . $fileName, $imageData) === false) {
            throw new Exception('Failed to save webcam photo.');
        }
    }

    // ============================================
    // Prepare Resident Data
    // ============================================
    
    // Personal Details
    $firstName = $conn->real_escape_string(trim($_POST['firstName'] ?? ''));
    $middleName = $conn->real_escape_string(trim($_POST['middleName'] ?? ''));
    $lastName = $conn->real_escape_string(trim($_POST['lastName'] ?? ''));
    $suffix = $conn->real_escape_string(trim($_POST['suffix'] ?? ''));
    $sex = $conn->real_escape_string($_POST['sex'] ?? '');
    $dateOfBirth = $conn->real_escape_string($_POST['dateOfBirth'] ?? '');
    $religion = $conn->real_escape_string(trim($_POST['religion'] ?? ''));
    $ethnicity = $conn->real_escape_string($_POST['ethnicity'] ?? '');
    
    // Calculate age from date of birth
    $age = null;
    if (!empty($dateOfBirth)) {
        $dob = new DateTime($dateOfBirth);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }
    
    // Contact Information
    $mobileNumber = $conn->real_escape_string(trim($_POST['mobileNumber'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    
    // Address Components
    $houseNo = trim($_POST['houseNo'] ?? '');
    $purok = trim($_POST['purok'] ?? '');
    $streetName = trim($_POST['streetName'] ?? '');

    // Construct address from individual fields if present
    if (isset($_POST['houseNo']) || isset($_POST['purok']) || isset($_POST['streetName'])) {
        $addressParts = array_filter([$houseNo ? "House No. $houseNo" : "", $purok ? "Purok $purok" : "", $streetName]);
        $currentAddress = $conn->real_escape_string(implode(', ', $addressParts));
    } else {
        $currentAddress = $conn->real_escape_string(trim($_POST['currentAddress'] ?? ''));
    }
    
    $purok = $conn->real_escape_string($purok);
    $streetName = $conn->real_escape_string($streetName);
    
    // Family Information
    $civilStatus = $conn->real_escape_string($_POST['civilStatus'] ?? '');
    $spouseName = $conn->real_escape_string(trim($_POST['spouseName'] ?? ''));
    $fatherName = $conn->real_escape_string(trim($_POST['fatherName'] ?? ''));
    $motherName = $conn->real_escape_string(trim($_POST['motherName'] ?? ''));
    $numberOfChildren = intval($_POST['numberOfChildren'] ?? 0);
    
    // Education & Employment
    $educationalAttainment = $conn->real_escape_string($_POST['educationalAttainment'] ?? '');
    $employmentStatus = $conn->real_escape_string($_POST['employmentStatus'] ?? '');
    $occupation = $conn->real_escape_string(trim($_POST['occupation'] ?? ''));
    $monthlyIncome = $conn->real_escape_string($_POST['monthlyIncome'] ?? '');
    
    // Government Programs
    $fourPs = $conn->real_escape_string($_POST['fourPs'] ?? 'No');
    $fourpsId = $conn->real_escape_string(trim($_POST['fourpsId'] ?? ''));
    $voterStatus = $conn->real_escape_string($_POST['voterStatus'] ?? '');
    $precinctNumber = $conn->real_escape_string(trim($_POST['precinctNumber'] ?? ''));
    
    // Health Information
    $philhealthId = $conn->real_escape_string(trim($_POST['philhealthId'] ?? ''));
    $membershipType = $conn->real_escape_string($_POST['membershipType'] ?? '');
    $philhealthCategory = $conn->real_escape_string($_POST['philhealthCategory'] ?? '');
    $ageHealthGroup = $conn->real_escape_string($_POST['ageHealthGroup'] ?? '');
    $medicalHistory = $conn->real_escape_string(trim($_POST['medicalHistory'] ?? ''));
    
    // Women's Reproductive Health (WRA)
    $lmpDate = $conn->real_escape_string($_POST['lmpDate'] ?? '');
    $usingFpMethod = $conn->real_escape_string($_POST['usingFpMethod'] ?? '');
    $fpMethodsUsed = $conn->real_escape_string($_POST['fpMethodsUsed'] ?? '');
    $fpStatus = $conn->real_escape_string($_POST['fpStatus'] ?? '');
    
    // Additional Information
    $remarks = $conn->real_escape_string(trim($_POST['remarks'] ?? ''));
    
    // Status fields (for edit mode)
    $verificationStatus = $conn->real_escape_string($_POST['verificationStatus'] ?? 'Pending');
    $activityStatus = $conn->real_escape_string($_POST['activityStatus'] ?? 'Active');
    $rejectionReason = $conn->real_escape_string(trim($_POST['rejectionReason'] ?? ''));
    $statusRemarks = $conn->real_escape_string(trim($_POST['statusRemarks'] ?? ''));
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($sex) || empty($dateOfBirth) || 
        empty($mobileNumber) || empty($currentAddress) || empty($civilStatus)) {
        throw new Exception('Please fill in all required fields.');
    }

    // ============================================
    // DUPLICATE PREVENTION CHECKS
    // ============================================
    
    // Check 1: Exact Name Match (First Name + Last Name)
    $duplicateCheckSql = "SELECT id, resident_id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name) as full_name, date_of_birth
                          FROM residents 
                          WHERE LOWER(first_name) = LOWER(?) 
                          AND LOWER(last_name) = LOWER(?)
                          AND activity_status != 'Deceased'";
    
    if ($mode === 'update' && $residentId) {
        $duplicateCheckSql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($duplicateCheckSql);
    
    if ($mode === 'update' && $residentId) {
        $stmt->bind_param("ssi", $firstName, $lastName, $residentId);
    } else {
        $stmt->bind_param("ss", $firstName, $lastName);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $duplicate = $result->fetch_assoc();
        $stmt->close();
        throw new Exception("A resident with the same name already exists: {$duplicate['full_name']} (ID: {$duplicate['resident_id']}, DOB: {$duplicate['date_of_birth']}). Please verify if this is a different person.");
    }
    $stmt->close();
    
    
    // Check 4: Philhealth ID (if provided)
    if (!empty($philhealthId)) {
        $duplicateCheckSql = "SELECT id, resident_id, CONCAT(first_name, ' ', last_name) as full_name
                              FROM residents 
                              WHERE philhealth_id = ?
                              AND activity_status != 'Deceased'";
        
        if ($mode === 'update' && $residentId) {
            $duplicateCheckSql .= " AND id != ?";
        }
        
        $stmt = $conn->prepare($duplicateCheckSql);
        
        if ($mode === 'update' && $residentId) {
            $stmt->bind_param("si", $philhealthId, $residentId);
        } else {
            $stmt->bind_param("s", $philhealthId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $duplicate = $result->fetch_assoc();
            $stmt->close();
            throw new Exception("This Philhealth ID is already registered to another resident: {$duplicate['full_name']} (ID: {$duplicate['resident_id']})");
        }
        $stmt->close();
    }

    // ============================================
    // UPDATE MODE - Update Existing Resident
    // ============================================
    if ($mode === 'update') {
        // Get existing photo if no new photo uploaded
        if (!$photoPath) {
            $existingPhoto = $conn->real_escape_string($_POST['existingPhoto'] ?? '');
            if ($existingPhoto) {
                $photoPath = $existingPhoto;
            }
        }
        
        // Build UPDATE query
        $updateSql = "UPDATE residents SET 
            first_name = '$firstName',
            middle_name = " . ($middleName ? "'$middleName'" : "NULL") . ",
            last_name = '$lastName',
            suffix = " . ($suffix ? "'$suffix'" : "NULL") . ",
            sex = '$sex',
            date_of_birth = '$dateOfBirth',
            age = " . ($age !== null ? $age : "NULL") . ",
            religion = " . ($religion ? "'$religion'" : "NULL") . ",
            ethnicity = " . ($ethnicity ? "'$ethnicity'" : "NULL") . ",
            mobile_number = '$mobileNumber',
            email = " . ($email ? "'$email'" : "NULL") . ",
            current_address = '$currentAddress',
            purok = " . ($purok ? "'$purok'" : "NULL") . ",
            street_name = " . ($streetName ? "'$streetName'" : "NULL") . ",
            civil_status = '$civilStatus',
            spouse_name = " . ($spouseName ? "'$spouseName'" : "NULL") . ",
            father_name = " . ($fatherName ? "'$fatherName'" : "NULL") . ",
            mother_name = " . ($motherName ? "'$motherName'" : "NULL") . ",
            number_of_children = $numberOfChildren,
            educational_attainment = " . ($educationalAttainment ? "'$educationalAttainment'" : "NULL") . ",
            employment_status = " . ($employmentStatus ? "'$employmentStatus'" : "NULL") . ",
            occupation = " . ($occupation ? "'$occupation'" : "NULL") . ",
            monthly_income = " . ($monthlyIncome ? "'$monthlyIncome'" : "NULL") . ",
            fourps_member = '$fourPs',
            fourps_id = " . ($fourpsId ? "'$fourpsId'" : "NULL") . ",
            voter_status = " . ($voterStatus ? "'$voterStatus'" : "NULL") . ",
            precinct_number = " . ($precinctNumber ? "'$precinctNumber'" : "NULL") . ",
            philhealth_id = " . ($philhealthId ? "'$philhealthId'" : "NULL") . ",
            membership_type = " . ($membershipType ? "'$membershipType'" : "NULL") . ",
            philhealth_category = " . ($philhealthCategory ? "'$philhealthCategory'" : "NULL") . ",
            age_health_group = " . ($ageHealthGroup ? "'$ageHealthGroup'" : "NULL") . ",
            medical_history = " . ($medicalHistory ? "'$medicalHistory'" : "NULL") . ",
            lmp_date = " . ($lmpDate ? "'$lmpDate'" : "NULL") . ",
            using_fp_method = " . ($usingFpMethod ? "'$usingFpMethod'" : "NULL") . ",
            fp_methods_used = " . ($fpMethodsUsed ? "'$fpMethodsUsed'" : "NULL") . ",
            fp_status = " . ($fpStatus ? "'$fpStatus'" : "NULL") . ",
            remarks = " . ($remarks ? "'$remarks'" : "NULL") . ",
            verification_status = '$verificationStatus',
            activity_status = '$activityStatus',
            rejection_reason = " . ($rejectionReason ? "'$rejectionReason'" : "NULL") . ",
            status_remarks = " . ($statusRemarks ? "'$statusRemarks'" : "NULL") . ",
            status_changed_at = NOW()" .
            ($photoPath && $photoPath !== $conn->real_escape_string($_POST['existingPhoto'] ?? '') ? ", photo = '" . $conn->real_escape_string($photoPath) . "'" : "") . "
        WHERE id = $residentId";
        
        if (!$conn->query($updateSql)) {
            throw new Exception('Failed to update resident: ' . $conn->error);
        }
        
        // Delete existing emergency contacts
        $deleteSql = "DELETE FROM emergency_contacts WHERE resident_id = $residentId";
        $conn->query($deleteSql);
        
        // Insert updated emergency contacts
        $contactIndex = 1;
        while (isset($_POST["emergencyContactName_$contactIndex"])) {
            $contactName = $conn->real_escape_string(trim($_POST["emergencyContactName_$contactIndex"]));
            $relationship = $conn->real_escape_string(trim($_POST["emergencyRelationship_$contactIndex"]));
            $contactNumber = $conn->real_escape_string(trim($_POST["emergencyContactNumber_$contactIndex"]));
            $contactAddress = $conn->real_escape_string(trim($_POST["emergencyAddress_$contactIndex"] ?? ''));
            
            if (!empty($contactName) && !empty($relationship) && !empty($contactNumber)) {
                $sql = "INSERT INTO emergency_contacts (resident_id, contact_name, relationship, contact_number, address, priority) 
                        VALUES ($residentId, '$contactName', '$relationship', '$contactNumber', " . 
                        ($contactAddress ? "'$contactAddress'" : "NULL") . ", $contactIndex)";
                
                if (!$conn->query($sql)) {
                    throw new Exception('Failed to save emergency contact: ' . $conn->error);
                }
            }
            
            $contactIndex++;
        }
        
        // Get resident_id for response
        $result = $conn->query("SELECT resident_id FROM residents WHERE id = $residentId");
        $row = $result->fetch_assoc();
        $generatedResidentId = $row['resident_id'];

        // Log Activity
        if (isset($_SESSION['username'])) {
            $log_user = $_SESSION['username'];
            $log_action = 'Update Resident';
            $log_desc = "Updated resident record: $firstName $lastName ($generatedResidentId)";
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Success response
        $response['success'] = true;
        $response['message'] = 'Resident record updated successfully!';
        $response['data'] = [
            'resident_id' => $residentId,
            'generated_resident_id' => $generatedResidentId,
            'verification_status' => $verificationStatus,
            'activity_status' => $activityStatus
        ];
        
    } else {
        // ============================================
        // CREATE MODE - Insert New Resident Record
        // ============================================
    
    $sql = "INSERT INTO residents (
        photo, first_name, middle_name, last_name, suffix, sex, date_of_birth, age, religion, ethnicity,
        mobile_number, email, current_address, purok, street_name,
        civil_status, spouse_name, father_name, mother_name, number_of_children,
        educational_attainment, employment_status, occupation, monthly_income,
        fourps_member, fourps_id, voter_status, precinct_number,
        philhealth_id, membership_type, philhealth_category, age_health_group, medical_history,
        lmp_date, using_fp_method, fp_methods_used, fp_status,
        remarks, verification_status
    ) VALUES (
        " . ($photoPath ? "'" . $conn->real_escape_string($photoPath) . "'" : "NULL") . ",
        '$firstName', " . ($middleName ? "'$middleName'" : "NULL") . ", '$lastName', " . ($suffix ? "'$suffix'" : "NULL") . ",
        '$sex', '$dateOfBirth', " . ($age !== null ? $age : "NULL") . ", " . ($religion ? "'$religion'" : "NULL") . ", " . ($ethnicity ? "'$ethnicity'" : "NULL") . ",
        '$mobileNumber', " . ($email ? "'$email'" : "NULL") . ", '$currentAddress', " . ($purok ? "'$purok'" : "NULL") . ", " . ($streetName ? "'$streetName'" : "NULL") . ",
        '$civilStatus', " . ($spouseName ? "'$spouseName'" : "NULL") . ", " . ($fatherName ? "'$fatherName'" : "NULL") . ", 
        " . ($motherName ? "'$motherName'" : "NULL") . ", $numberOfChildren,
        " . ($educationalAttainment ? "'$educationalAttainment'" : "NULL") . ", " . ($employmentStatus ? "'$employmentStatus'" : "NULL") . ",
        " . ($occupation ? "'$occupation'" : "NULL") . ", " . ($monthlyIncome ? "'$monthlyIncome'" : "NULL") . ",
        '$fourPs', " . ($fourpsId ? "'$fourpsId'" : "NULL") . ", " . ($voterStatus ? "'$voterStatus'" : "NULL") . ", 
        " . ($precinctNumber ? "'$precinctNumber'" : "NULL") . ",
        " . ($philhealthId ? "'$philhealthId'" : "NULL") . ", " . ($membershipType ? "'$membershipType'" : "NULL") . ",
        " . ($philhealthCategory ? "'$philhealthCategory'" : "NULL") . ", " . ($ageHealthGroup ? "'$ageHealthGroup'" : "NULL") . ",
        " . ($medicalHistory ? "'$medicalHistory'" : "NULL") . ",
        " . ($lmpDate ? "'$lmpDate'" : "NULL") . ", " . ($usingFpMethod ? "'$usingFpMethod'" : "NULL") . ",
        " . ($fpMethodsUsed ? "'$fpMethodsUsed'" : "NULL") . ", " . ($fpStatus ? "'$fpStatus'" : "NULL") . ",
        " . ($remarks ? "'$remarks'" : "NULL") . ", 'Pending'
    )";

    if (!$conn->query($sql)) {
        throw new Exception('Failed to save resident: ' . $conn->error);
    }

    $residentId = $conn->insert_id;

    // ============================================
    // Generate and Update Resident ID
    // ============================================
    
    // Generate resident ID in format W-XXXXX
    $fiveDigitNumber = str_pad($residentId % 100000, 5, '0', STR_PAD_LEFT);
    $generatedResidentId = "W-{$fiveDigitNumber}";
    
    // Update the resident record with the generated resident_id
    $updateSql = "UPDATE residents SET resident_id = '$generatedResidentId' WHERE id = $residentId";
    if (!$conn->query($updateSql)) {
        throw new Exception('Failed to update resident ID: ' . $conn->error);
    }

    // ============================================
    // Handle Household Information
    // ============================================

    $householdHeadValue = trim($_POST['householdHeadValue'] ?? '');

    if ($householdHeadValue === 'Yes') {
        // Resident is a household head — create a new household
        $householdNumber   = $conn->real_escape_string(trim($_POST['householdNumber'] ?? ''));
        $householdContact  = $conn->real_escape_string(trim($_POST['householdContact'] ?? ''));
        $householdAddress  = $conn->real_escape_string(trim($_POST['householdAddress'] ?? ''));
        $waterSourceType   = $conn->real_escape_string(trim($_POST['waterSourceType'] ?? ''));
        $toiletFacilityType = $conn->real_escape_string(trim($_POST['toiletFacilityType'] ?? ''));

        if (!empty($householdNumber)) {
            // Check if household number already exists
            $checkHHSql = "SELECT id FROM households WHERE household_number = '$householdNumber'";
            $checkHHResult = $conn->query($checkHHSql);
            if ($checkHHResult && $checkHHResult->num_rows > 0) {
                throw new Exception("Household number '$householdNumber' already exists. Please use a different number.");
            }

            $hhSql = "INSERT INTO households (
                household_number,
                household_head_id,
                household_contact,
                address,
                water_source_type,
                toilet_facility_type,
                created_at
            ) VALUES (
                '$householdNumber',
                $residentId,
                " . ($householdContact ? "'$householdContact'" : "NULL") . ",
                " . ($householdAddress ? "'$householdAddress'" : "''") . ",
                " . ($waterSourceType ? "'$waterSourceType'" : "NULL") . ",
                " . ($toiletFacilityType ? "'$toiletFacilityType'" : "NULL") . ",
                NOW()
            )";

            if (!$conn->query($hhSql)) {
                throw new Exception('Failed to create household: ' . $conn->error);
            }
        }

    } elseif ($householdHeadValue === 'No') {
        // Resident is a member — add to existing household
        $selectedHouseholdId = intval($_POST['selectedHouseholdId'] ?? 0);

        if ($selectedHouseholdId > 0) {
            // Verify household exists
            $checkHHSql = "SELECT id FROM households WHERE id = $selectedHouseholdId";
            $checkHHResult = $conn->query($checkHHSql);
            if (!$checkHHResult || $checkHHResult->num_rows === 0) {
                throw new Exception('Selected household does not exist.');
            }

            // Get relationship to head
            $householdRelationship = $conn->real_escape_string(trim($_POST['householdRelationship'] ?? ''));

            // Check resident is not already a member
            $checkMemberSql = "SELECT id FROM household_members WHERE household_id = $selectedHouseholdId AND resident_id = $residentId";
            $checkMemberResult = $conn->query($checkMemberSql);
            if (!$checkMemberResult || $checkMemberResult->num_rows === 0) {
                $memberSql = "INSERT INTO household_members (household_id, resident_id, relationship_to_head, is_head)
                              VALUES ($selectedHouseholdId, $residentId, " . ($householdRelationship ? "'$householdRelationship'" : "NULL") . ", 0)";
                if (!$conn->query($memberSql)) {
                    throw new Exception('Failed to add resident to household: ' . $conn->error);
                }
            }
        }
    }

    // Log Activity
    if (isset($_SESSION['username'])) {
        $log_user = $_SESSION['username'];
        $log_action = 'Add Resident';
        $log_desc = "Added new resident: $firstName $lastName ($generatedResidentId)";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Success response
    $response['success'] = true;
    $response['message'] = 'Resident record created successfully! Resident ID: ' . $generatedResidentId;
    $response['data'] = [
        'resident_id' => $residentId,
        'generated_resident_id' => $generatedResidentId,
        'verification_status' => 'Pending'
    ];
    
    } // End of CREATE mode

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    // Delete uploaded photo if exists
    if (isset($photoPath) && file_exists($photoPath)) {
        unlink($photoPath);
    }
    
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
} finally {
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}

// Output JSON response
echo json_encode($response);
?>
