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
    $placeOfBirth = $conn->real_escape_string(trim($_POST['placeOfBirth'] ?? ''));
    $ethnicity = $conn->real_escape_string($_POST['ethnicity'] ?? '');
    
    $religionSelect = $_POST['religion'] ?? '';
    $religion = ($religionSelect === 'Other') ? ($_POST['religion_other'] ?? '') : $religionSelect;
    $religion = $conn->real_escape_string(trim($religion));

    // Calculate age from date of birth
    $age = null;
    if (!empty($dateOfBirth)) {
        $dob = new DateTime($dateOfBirth);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }
    
    // Contact Information
    $mobileNumber = $conn->real_escape_string(trim($_POST['mobileNumber'] ?? ''));
    $purok = $conn->real_escape_string(trim($_POST['purok'] ?? ''));
    $streetName = $conn->real_escape_string(trim($_POST['streetName'] ?? ''));

    // Construct address for legacy current_address column
    $addressParts = array_filter([$purok ? "Purok $purok" : "", $streetName]);
    $currentAddress = $conn->real_escape_string(implode(', ', $addressParts));
    
    // Family Information
    $civilStatus = $conn->real_escape_string($_POST['civilStatus'] ?? '');
    $spouseName = $conn->real_escape_string(trim($_POST['spouseName'] ?? ''));
    $fatherName = $conn->real_escape_string(trim($_POST['fatherName'] ?? ''));
    $motherName = $conn->real_escape_string(trim($_POST['motherName'] ?? ''));
    $numberOfChildren = intval($_POST['numberOfChildren'] ?? 0);
    // Capture the hidden inputs we added to the JS FormData
    $fatherResidentId = !empty($_POST['fatherResidentId']) ? intval($_POST['fatherResidentId']) : null;
    $motherResidentId = !empty($_POST['motherResidentId']) ? intval($_POST['motherResidentId']) : null;
    // Guardian Information
    $guardianName = $conn->real_escape_string(trim($_POST['guardianName'] ?? ''));
    $guardianRelationship = $conn->real_escape_string(trim($_POST['guardianRelationship'] ?? ''));
    $guardianContact = $conn->real_escape_string(trim($_POST['guardianContact'] ?? ''));
    
    // Education & Employment
    $educationalAttainment = $conn->real_escape_string($_POST['educationalAttainment'] ?? '');
    $employmentStatus = $conn->real_escape_string($_POST['employmentStatus'] ?? '');
    $occupation = $conn->real_escape_string(trim($_POST['occupation'] ?? ''));
    
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
    $pwdStatus = $conn->real_escape_string($_POST['pwdStatus'] ?? 'No');
    $pwdType = $conn->real_escape_string(trim($_POST['pwdType'] ?? ''));
    $pwdIdNumber = $conn->real_escape_string(trim($_POST['pwdIdNumber'] ?? ''));
    $medicalHistory = $conn->real_escape_string(trim($_POST['medicalHistory'] ?? ''));
    
    // Women's Reproductive Health (WRA)
    $lmpDate = $conn->real_escape_string($_POST['lmpDate'] ?? '');
    $usingFpMethod = $conn->real_escape_string($_POST['usingFpMethod'] ?? '');
    $fpMethodsUsed = $conn->real_escape_string($_POST['fpMethodsUsed'] ?? '');
    $fpStatus = $conn->real_escape_string($_POST['fpStatus'] ?? '');
    
    // Additional Information
    $remarks = $conn->real_escape_string(trim($_POST['remarks'] ?? ''));
    
    // Status fields
    $activityStatus = $conn->real_escape_string($_POST['activityStatus'] ?? 'Alive');

    // If minor, default civil status to Single if not provided (since it's disabled in UI)
    if ($age !== null && $age < 18 && empty($civilStatus)) {
        $civilStatus = 'Single';
    }

    // Validation
    $isMinor = ($age !== null && $age < 18);
    if (empty($firstName) || empty($lastName) || empty($sex) || empty($dateOfBirth) ||
        (!$isMinor && empty($mobileNumber)) || empty($currentAddress) || 
        (!$isMinor && empty($civilStatus)) || empty($pwdStatus)) {
        throw new Exception('Please fill in all required fields.');
    }

    if ($isMinor && (empty($guardianName) || empty($guardianRelationship) || empty($guardianContact))) {
        throw new Exception('Guardian information is required for minors.');
    }

    // ============================================
    // DUPLICATE PREVENTION CHECKS
    // ============================================
    
    // Check 1: Exact Name Match (First Name + Last Name + Suffix)
    $duplicateCheckSql = "SELECT id, resident_id, CONCAT(first_name, ' ', IFNULL(middle_name, ''), ' ', last_name, IFNULL(CONCAT(' ', suffix), '')) as full_name, date_of_birth
                          FROM residents 
                          WHERE LOWER(first_name) = LOWER(?) 
                          AND LOWER(last_name) = LOWER(?)";
                          
    if (!empty($suffix)) {
        $duplicateCheckSql .= " AND LOWER(suffix) = LOWER(?)";
    } else {
        $duplicateCheckSql .= " AND (suffix IS NULL OR suffix = '')";
    }
    
    $duplicateCheckSql .= " AND activity_status != 'Deceased'";
    
    if ($mode === 'update' && $residentId) {
        $duplicateCheckSql .= " AND id != ?";
    }
    
    $stmt = $conn->prepare($duplicateCheckSql);
    
    if ($mode === 'update' && $residentId) {
        if (!empty($suffix)) {
            $stmt->bind_param("sssi", $firstName, $lastName, $suffix, $residentId);
        } else {
            $stmt->bind_param("ssi", $firstName, $lastName, $residentId);
        }
    } else {
        if (!empty($suffix)) {
            $stmt->bind_param("sss", $firstName, $lastName, $suffix);
        } else {
            $stmt->bind_param("ss", $firstName, $lastName);
        }
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
            place_of_birth = " . ($placeOfBirth ? "'$placeOfBirth'" : "NULL") . ",
            age = " . ($age !== null ? $age : "NULL") . ",
            religion = " . ($religion ? "'$religion'" : "NULL") . ",
            ethnicity = " . ($ethnicity ? "'$ethnicity'" : "NULL") . ",
            mobile_number = '$mobileNumber',
            purok = " . ($purok ? "'$purok'" : "NULL") . ",
            street_name = " . ($streetName ? "'$streetName'" : "NULL") . ",
            current_address = '$currentAddress',
            civil_status = '$civilStatus',
            spouse_name = " . ($spouseName ? "'$spouseName'" : "NULL") . ",
            father_name = " . ($fatherName ? "'$fatherName'" : "NULL") . ",
            father_resident_id = " . ($fatherResidentId ? $fatherResidentId : "NULL") . ",
            mother_name = " . ($motherName ? "'$motherName'" : "NULL") . ",
            mother_resident_id = " . ($motherResidentId ? $motherResidentId : "NULL") . ",
            number_of_children = $numberOfChildren,
            guardian_name = " . ($guardianName ? "'$guardianName'" : "NULL") . ",
            guardian_relationship = " . ($guardianRelationship ? "'$guardianRelationship'" : "NULL") . ",
            guardian_contact = " . ($guardianContact ? "'$guardianContact'" : "NULL") . ",
            educational_attainment = " . ($educationalAttainment ? "'$educationalAttainment'" : "NULL") . ",
            employment_status = " . ($employmentStatus ? "'$employmentStatus'" : "NULL") . ",
            occupation = " . ($occupation ? "'$occupation'" : "NULL") . ",
            fourps_member = '$fourPs',
            fourps_id = " . ($fourpsId ? "'$fourpsId'" : "NULL") . ",
            voter_status = " . ($voterStatus ? "'$voterStatus'" : "NULL") . ",
            precinct_number = " . ($precinctNumber ? "'$precinctNumber'" : "NULL") . ",
            philhealth_id = " . ($philhealthId ? "'$philhealthId'" : "NULL") . ",
            membership_type = " . ($membershipType ? "'$membershipType'" : "NULL") . ",
            philhealth_category = " . ($philhealthCategory ? "'$philhealthCategory'" : "NULL") . ",
            age_health_group = " . ($ageHealthGroup ? "'$ageHealthGroup'" : "NULL") . ",
            pwd_status = '$pwdStatus',
            pwd_type = " . ($pwdType ? "'$pwdType'" : "NULL") . ",
            pwd_id_number = " . ($pwdIdNumber ? "'$pwdIdNumber'" : "NULL") . ",
            medical_history = " . ($medicalHistory ? "'$medicalHistory'" : "NULL") . ",
            lmp_date = " . ($lmpDate ? "'$lmpDate'" : "NULL") . ",
            using_fp_method = " . ($usingFpMethod ? "'$usingFpMethod'" : "NULL") . ",
            fp_methods_used = " . ($fpMethodsUsed ? "'$fpMethodsUsed'" : "NULL") . ",
            fp_status = " . ($fpStatus ? "'$fpStatus'" : "NULL") . ",
            remarks = " . ($remarks ? "'$remarks'" : "NULL") . ",
            activity_status = '$activityStatus',
            status_changed_at = NOW()" .
            ($photoPath && $photoPath !== $conn->real_escape_string($_POST['existingPhoto'] ?? '') ? ", photo = '" . $conn->real_escape_string($photoPath) . "'" : "") . "
        WHERE id = $residentId";
        
        if (!$conn->query($updateSql)) {
            throw new Exception('Failed to update resident: ' . $conn->error);
        }
        
        // ============================================
        // Handle Pending Household Actions
        // ============================================
        $pendingAction = $_POST['pending_household_action'] ?? '';
        
        if ($pendingAction === 'add') {
            $householdHeadValue = $_POST['pending_household_head_value'] ?? '';
            
            if ($householdHeadValue === 'Yes') {
                $householdNumber   = $conn->real_escape_string(trim($_POST['pending_household_number'] ?? ''));
                $householdContact  = $conn->real_escape_string(trim($_POST['pending_household_contact'] ?? ''));
                $householdAddress  = $conn->real_escape_string(trim($_POST['pending_household_address'] ?? ''));
                $waterSourceType   = $conn->real_escape_string(trim($_POST['pending_water_source'] ?? ''));
                $toiletFacilityType = $conn->real_escape_string(trim($_POST['pending_toilet_facility'] ?? ''));
                
                if (!empty($householdNumber)) {
                    $checkHHSql = "SELECT id FROM households WHERE household_number = '$householdNumber'";
                    $checkHHResult = $conn->query($checkHHSql);
                    if ($checkHHResult && $checkHHResult->num_rows > 0) {
                        throw new Exception("Household number '$householdNumber' already exists.");
                    }
                    
                    $hhSql = "INSERT INTO households (
                        household_number, household_head_id, household_contact, address, water_source_type, toilet_facility_type, created_at
                    ) VALUES (
                        '$householdNumber', $residentId, " . ($householdContact ? "'$householdContact'" : "NULL") . ", " . ($householdAddress ? "'$householdAddress'" : "''") . ", " . ($waterSourceType ? "'$waterSourceType'" : "NULL") . ", " . ($toiletFacilityType ? "'$toiletFacilityType'" : "NULL") . ", NOW()
                    )";
                    if (!$conn->query($hhSql)) {
                        throw new Exception('Failed to create household: ' . $conn->error);
                    }
                }
            } elseif ($householdHeadValue === 'No') {
                $selectedHouseholdId = intval($_POST['pending_selected_household_id'] ?? 0);
                $householdRelationship = $conn->real_escape_string(trim($_POST['pending_household_relationship'] ?? ''));
                
                if ($selectedHouseholdId > 0) {
                    $checkMemberSql = "SELECT id FROM household_members WHERE household_id = $selectedHouseholdId AND resident_id = $residentId";
                    $checkMemberResult = $conn->query($checkMemberSql);
                    if (!$checkMemberResult || $checkMemberResult->num_rows === 0) {
                        $memberSql = "INSERT INTO household_members (household_id, resident_id, relationship_to_head, is_head)
                                      VALUES ($selectedHouseholdId, $residentId, " . ($householdRelationship ? "'$householdRelationship'" : "NULL") . ", 0)";
                        if (!$conn->query($memberSql)) {
                            throw new Exception('Failed to add resident to household: ' . $conn->error);
                        }
                        
                        if (isset($_SESSION['username'])) {
                            $resStmt = $conn->query("SELECT CONCAT(first_name, ' ', last_name) AS fname FROM residents WHERE id = $residentId");
                            $resName = $resStmt->fetch_assoc()['fname'] ?? "Resident ID $residentId";
                            $hhStmt = $conn->query("SELECT household_number FROM households WHERE id = $selectedHouseholdId");
                            $hhNum = $hhStmt->fetch_assoc()['household_number'] ?? "Household ID $selectedHouseholdId";
                            
                            $log_user = $_SESSION['username'];
                            $log_action = 'Add Household Members';
                            $log_desc = "Added $resName to household $hhNum";
                            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }
                    }
                }
            }
        } elseif ($pendingAction === 'remove') {
            $householdId = intval($_POST['pending_selected_household_id'] ?? 0);
            if ($householdId > 0) {
                // Archive the member before deleting
                $stmt = $conn->prepare("SELECT hm.relationship_to_head, hm.is_head, CONCAT(r.first_name, ' ', r.last_name) as resident_name, h.household_number FROM household_members hm JOIN residents r ON hm.resident_id = r.id JOIN households h ON hm.household_id = h.id WHERE hm.household_id = ? AND hm.resident_id = ?");
                $stmt->bind_param("ii", $householdId, $residentId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $archiveData = [
                        'household_id' => $householdId,
                        'household_number' => $row['household_number'],
                        'resident_id' => $residentId,
                        'resident_name' => $row['resident_name'],
                        'relationship_to_head' => $row['relationship_to_head'],
                        'is_head' => $row['is_head']
                    ];
                    $archiveType = 'household_member';
                    $deletedBy = $_SESSION['username'] ?? 'Unknown';
                    $recordData = json_encode($archiveData);
                    $recordId = $row['household_number'] ?? $residentId;
                    $archStmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
                    $archStmt->bind_param("ssss", $archiveType, $recordId, $recordData, $deletedBy);
                    $archStmt->execute();
                    $archStmt->close();
                }
                $stmt->close();

                $deleteSql = "DELETE FROM household_members WHERE household_id = $householdId AND resident_id = $residentId";
                if (!$conn->query($deleteSql)) {
                    throw new Exception('Failed to remove member: ' . $conn->error);
                }
                
                if (isset($_SESSION['username'])) {
                    $resStmt = $conn->query("SELECT CONCAT(first_name, ' ', last_name) AS fname FROM residents WHERE id = $residentId");
                    $resName = $resStmt->fetch_assoc()['fname'] ?? "Resident ID $residentId";
                    $hhStmt = $conn->query("SELECT household_number FROM households WHERE id = $householdId");
                    $hhNum = $hhStmt->fetch_assoc()['household_number'] ?? "Household ID $householdId";
                    
                    $log_user = $_SESSION['username'];
                    $log_action = 'Delete Household Members';
                    $log_desc = "Deleted $resName from household $hhNum";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            }
        } elseif ($pendingAction === 'delete_household') {
            $householdId = intval($_POST['pending_selected_household_id'] ?? 0);
            if ($householdId > 0) {
                $stmt = $conn->prepare("SELECT h.*, (SELECT CONCAT(first_name, ' ', last_name) FROM residents WHERE id = h.household_head_id) as head_name FROM households h WHERE h.id = ? AND h.household_head_id = ?");
                $stmt->bind_param("ii", $householdId, $residentId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res->num_rows > 0) {
                    $household = $res->fetch_assoc();
                    $household['members'] = [];
                    $conn->query("CREATE TABLE IF NOT EXISTS `archive` (`id` int(11) NOT NULL AUTO_INCREMENT, `archive_type` varchar(50) DEFAULT NULL, `record_id` varchar(50) DEFAULT NULL, `record_data` longtext DEFAULT NULL, `deleted_by` varchar(100) DEFAULT NULL, `deleted_at` datetime DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    $recordData = json_encode($household, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    $archiveType = 'household';
                    $deletedBy = $_SESSION['username'] ?? 'Unknown';
                    
                    $archStmt = $conn->prepare("INSERT INTO archive (archive_type, record_id, record_data, deleted_by, deleted_at) VALUES (?, ?, ?, ?, NOW())");
                    $archStmt->bind_param("ssss", $archiveType, $household['household_number'], $recordData, $deletedBy);
                    $archStmt->execute();
                    
                    $conn->query("DELETE FROM household_members WHERE household_id = $householdId");
                    $conn->query("DELETE FROM households WHERE id = $householdId AND household_head_id = $residentId");
                }
            }
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
            'activity_status' => $activityStatus
        ];
        
    } else {
        // ============================================
        // CREATE MODE - Insert New Resident Record
        // ============================================
    
    $sql = "INSERT INTO residents (
        photo, first_name, middle_name, last_name, suffix, sex, date_of_birth, place_of_birth, age, religion, ethnicity,
        mobile_number, purok, street_name, current_address,
        civil_status, spouse_name,  father_name, father_resident_id, 
            mother_name, mother_resident_id, number_of_children,
        guardian_name, guardian_relationship, guardian_contact,
        educational_attainment, employment_status, occupation, pwd_status,
        pwd_type, pwd_id_number,
        fourps_member, fourps_id, voter_status, precinct_number,
        philhealth_id, membership_type, philhealth_category, age_health_group, medical_history,
        lmp_date, using_fp_method, fp_methods_used, fp_status,
        remarks, activity_status
    ) VALUES (
        " . ($photoPath ? "'" . $conn->real_escape_string($photoPath) . "'" : "NULL") . ",
        '$firstName', " . ($middleName ? "'$middleName'" : "NULL") . ", '$lastName', " . ($suffix ? "'$suffix'" : "NULL") . ",
        '$sex', '$dateOfBirth', " . ($placeOfBirth ? "'$placeOfBirth'" : "NULL") . ", " . ($age !== null ? $age : "NULL") . ", " . ($religion ? "'$religion'" : "NULL") . ", " . ($ethnicity ? "'$ethnicity'" : "NULL") . ",
        '$mobileNumber', " . ($purok ? "'$purok'" : "NULL") . ", " . ($streetName ? "'$streetName'" : "NULL") . ", '$currentAddress',
        '$civilStatus', " . ($spouseName ? "'$spouseName'" : "NULL") . ", " . ($fatherName ? "'$fatherName'" : "NULL") . ", 
        " . ($fatherResidentId ? "'$fatherResidentId'" : "NULL") . ", " . ($motherName ? "'$motherName'" : "NULL") . ", " . ($motherResidentId ? "'$motherResidentId'" : "NULL") . ", $numberOfChildren,
        " . ($guardianName ? "'$guardianName'" : "NULL") . ", " . ($guardianRelationship ? "'$guardianRelationship'" : "NULL") . ", " . ($guardianContact ? "'$guardianContact'" : "NULL") . ",
        " . ($educationalAttainment ? "'$educationalAttainment'" : "NULL") . ", " . ($employmentStatus ? "'$employmentStatus'" : "NULL") . ",
        " . ($occupation ? "'$occupation'" : "NULL") . ", '$pwdStatus',
        " . ($pwdType ? "'$pwdType'" : "NULL") . ", " . ($pwdIdNumber ? "'$pwdIdNumber'" : "NULL") . ",
        '$fourPs', " . ($fourpsId ? "'$fourpsId'" : "NULL") . ", " . ($voterStatus ? "'$voterStatus'" : "NULL") . ", 
        " . ($precinctNumber ? "'$precinctNumber'" : "NULL") . ",
        " . ($philhealthId ? "'$philhealthId'" : "NULL") . ", " . ($membershipType ? "'$membershipType'" : "NULL") . ",
        " . ($philhealthCategory ? "'$philhealthCategory'" : "NULL") . ", " . ($ageHealthGroup ? "'$ageHealthGroup'" : "NULL") . ",
        " . ($medicalHistory ? "'$medicalHistory'" : "NULL") . ",
        " . ($lmpDate ? "'$lmpDate'" : "NULL") . ", " . ($usingFpMethod ? "'$usingFpMethod'" : "NULL") . ",
        " . ($fpMethodsUsed ? "'$fpMethodsUsed'" : "NULL") . ", " . ($fpStatus ? "'$fpStatus'" : "NULL") . ",
        " . ($remarks ? "'$remarks'" : "NULL") . ", 'Alive'
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
        if ($age !== null && $age < 18) {
            throw new Exception('A minor cannot be a household head.');
        }

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
                
                if (isset($_SESSION['username'])) {
                    $hhStmt = $conn->query("SELECT household_number FROM households WHERE id = $selectedHouseholdId");
                    $hhNum = $hhStmt->fetch_assoc()['household_number'] ?? "Household ID $selectedHouseholdId";
                    
                    $log_user = $_SESSION['username'];
                    $log_action = 'Add Household Members';
                    $log_desc = "Added $firstName $lastName to household $hhNum";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
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
        'generated_resident_id' => $generatedResidentId
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
