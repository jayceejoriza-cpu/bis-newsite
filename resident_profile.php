<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';

// Enforce: redirect if user lacks view permission
requirePermission('perm_resident_view');

// Get resident ID from URL
$residentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($residentId <= 0) {
    header('Location: residents.php');
    exit;
}

// Page title
$pageTitle = 'Resident Profile';

// ============================================
// Database Connection
// ============================================
try {
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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// Fetch Resident Data
// ============================================
$resident = null;
$emergencyContacts = [];
$householdInfo = null;
$householdMembers = [];

try {
    // Fetch resident data
    $stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ?");
    $stmt->execute([$residentId]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        header('Location: residents.php');
        exit;
    }
    
    // Fetch emergency contacts
    $contactStmt = $pdo->prepare("SELECT * FROM emergency_contacts WHERE resident_id = ?");
    $contactStmt->execute([$residentId]);
    $emergencyContacts = $contactStmt->fetchAll();
    
    // Fetch household information where resident is the household head
    $householdStmt = $pdo->prepare("
        SELECT h.*, 
               CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) as head_name, -- Already in desired format
               r.date_of_birth as head_dob,
               r.sex as head_sex
        FROM households h
        LEFT JOIN residents r ON h.household_head_id = r.id
        WHERE h.household_head_id = ?
    ");
    $householdStmt->execute([$residentId]);
    $householdInfo = $householdStmt->fetch();
    
    // If resident is household head, fetch all members
    if ($householdInfo) {
        $membersStmt = $pdo->prepare("
            SELECT hm.*,
                   CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) as full_name,
                   r.date_of_birth,
                   r.sex,
                   r.mobile_number,
                   r.id as resident_id
            FROM household_members hm
            LEFT JOIN residents r ON hm.resident_id = r.id
            WHERE hm.household_id = ?
            ORDER BY hm.id
        ");
        $membersStmt->execute([$householdInfo['id']]);
        $householdMembers = $membersStmt->fetchAll();
    } else {
        // Check if resident is a member of any household
        $memberStmt = $pdo->prepare("
            SELECT h.*, 
                   CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) as head_name,
                   r.date_of_birth as head_dob,
                   r.sex as head_sex,
                   r.id as head_resident_id,
                   hm.relationship_to_head
            FROM household_members hm
            JOIN households h ON hm.household_id = h.id
            LEFT JOIN residents r ON h.household_head_id = r.id
            WHERE hm.resident_id = ?
        ");
        $memberStmt->execute([$residentId]);
        $householdInfo = $memberStmt->fetch();
        
        // If resident is a member, fetch all other members
        if ($householdInfo) {
            $membersStmt = $pdo->prepare("
                SELECT hm.*, -- This is for other members, not the current resident
                       CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) as full_name,
                       r.date_of_birth,
                       r.sex,
                       r.mobile_number,
                       r.id as resident_id
                FROM household_members hm
                LEFT JOIN residents r ON hm.resident_id = r.id
                WHERE hm.household_id = ? AND hm.resident_id != ?
                ORDER BY hm.id
            ");
            $membersStmt->execute([$householdInfo['id'], $residentId]);
            $householdMembers = $membersStmt->fetchAll();
        }
    }
    
} catch (PDOException $e) {
    error_log("Error fetching resident: " . $e->getMessage());
    header('Location: residents.php');
    exit;
}

// Helper function to format full name
// This function is for the main resident's name display, not directly for household names in the table.
// The request is specifically for "the house hold name", so this function remains as is.
function formatFullName($firstName, $middleName, $lastName, $suffix) {
    $name = trim($lastName);
    if (!empty($firstName)) { $name .= ', ' . trim($firstName); }
    if (!empty($middleName)) { $name .= ' ' . trim($middleName); }
    if (!empty($suffix)) { $name .= ' ' . trim($suffix); }
    return $name;
}

// Helper function to calculate age
function calculateAge($dateOfBirth) {
    if (empty($dateOfBirth)) return 0;
    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    return $now->diff($dob)->y;
}

$fullName = formatFullName(
    $resident['first_name'],
    $resident['middle_name'],
    $resident['last_name'],
    $resident['suffix']
);

$age = calculateAge($resident['date_of_birth']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($fullName); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/resident-profile.css">

    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        .edit-field, .edit-field-conditional {
            font-size: 15px !important;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Profile Content -->
        <div class="dashboard-content">
            <!-- Back Button -->
            <div class="back-navigation">
                <a href="residents.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back to Residents
                </a>
            </div>
            
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-header-left">
                    <div class="profile-photo-wrapper" style="display: flex; flex-direction: row; gap: 20px; align-items: center;">
                        <div class="profile-photo" style="flex-shrink: 0; position: relative;">
                            <?php if (!empty($resident['photo'])): ?>
                                <img id="photoPreview" src="<?php echo htmlspecialchars($resident['photo']); ?>" alt="<?php echo htmlspecialchars($fullName); ?>" style="width: 100%; height: 100%; object-fit: cover; ">
                                <div class="profile-photo-placeholder" style="display:none;"><i class="fas fa-user"></i></div>
                            <?php else: ?>
                                <div class="profile-photo-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                                <img id="photoPreview" src="" alt="Photo Preview" style="display:none; width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                            <div id="inlineWebcamPreview" style="display: none; width:100%; height:100%; border-radius:50%; overflow:hidden; position: absolute; top:0; left:0; z-index: 10;"></div>
                        </div>
                        <div class="edit-field" style="display:none;">
                            <div class="photo-upload-actions" style="display: flex; flex-direction: column; gap: 8px; justify-content: center;">
                                <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                <button type="button" class="btn btn-sm btn-info" onclick="document.getElementById('photoInput').click()">
                                    <i class="fas fa-upload"></i> <?php echo !empty($resident['photo']) ? 'Change Photo' : 'Upload Photo'; ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-info" id="takePhotoBtn" onclick="toggleInlineWebcam()">
                                    <i class="fas fa-camera"></i> <span id="cameraButtonText">Start Camera</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="captureInlineBtn" onclick="captureInlinePhoto()" style="display: none;">
                                    <i class="fas fa-camera"></i> Capture
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" id="resetPhotoBtn">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="profile-header-info">
                        <h1 class="profile-name"><?php echo strtoupper  ($fullName); ?></h1>
                        <p class="profile-id"><?php echo htmlspecialchars($resident['resident_id'] ?: 'N/A'); ?></p>
                        <div class="profile-badges">
                            <span class="badge badge-<?php echo strtolower($resident['activity_status']); ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo htmlspecialchars($resident['activity_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="profile-header-actions">
                    <?php if (hasPermission('perm_resident_print_profile')): ?>
                    <button class="btn btn-secondary view-action" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Print Profile
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('perm_resident_edit')): ?>
                    <button class="btn btn-primary view-action" type="button" onclick="toggleEditMode(true)">
                        <i class="fas fa-edit"></i>
                        Edit Profile
                    </button>
                    <button class="btn btn-secondary edit-action" type="button" onclick="cancelEditMode()" style="display:none;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-success edit-action" type="button" onclick="saveProfile()" style="display:none;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Profile Content Grid -->
            <div class="profile-content-grid">
                <!-- Sidebar Navigation -->
                <div class="profile-sidebar">
                    <nav class="profile-nav">
                        <a href="#personal-details" class="profile-nav-item active">
                            <i class="fas fa-user"></i>
                            <span>Personal Details</span>
                        </a>
                        <a href="#contact-info" class="profile-nav-item">
                            <i class="fas fa-phone"></i>
                            <span>Contact Information</span>
                        </a>
                        <a href="#family-info" class="profile-nav-item">
                            <i class="fas fa-users"></i>
                            <span>Family Information</span>
                        </a> 
                        <!---
                        <a href="#emergency-contact" class="profile-nav-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>Emergency Contact</span>
                        </a>--->
                        <a href="#additional-info" class="profile-nav-item">
                            <i class="fas fa-info-circle"></i>
                            <span>Additional Information</span>
                        </a>
                        <a href="#household-details" class="profile-nav-item">
                            <i class="fas fa-home"></i>
                            <span>Household Details</span>
                        </a> 
                        <a href="#service-requests" class="profile-nav-item">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Service Requests</span>
                        </a>
                        <a href="#blotter-records" class="profile-nav-item">
                            <i class="fas fa-file-alt"></i>
                            <span>Blotter Records</span>
                        </a>
                       
                        <a href="#incident-report" class="profile-nav-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Incident Report</span>
                        </a>
                    </nav>
                </div>
                
                <!-- Main Content Area -->
                <div class="profile-main-content">
                    <form id="inlineEditForm">
                        <input type="hidden" name="resident_id" value="<?php echo $residentId; ?>">
                        
                        <!-- Pending Household Actions -->
                        <input type="hidden" name="pending_household_action" id="pendingHouseholdAction" value="">
                        <input type="hidden" name="pending_household_head_value" id="pendingHouseholdHeadValue" value="">
                        <input type="hidden" name="pending_household_number" id="pendingHouseholdNumber" value="">
                        <input type="hidden" name="pending_household_contact" id="pendingHouseholdContact" value="">
                        <input type="hidden" name="pending_household_address" id="pendingHouseholdAddress" value="">
                        <input type="hidden" name="pending_water_source" id="pendingWaterSource" value="">
                        <input type="hidden" name="pending_toilet_facility" id="pendingToiletFacility" value="">
                        <input type="hidden" name="pending_selected_household_id" id="pendingSelectedHouseholdId" value="">
                        <input type="hidden" name="pending_household_relationship" id="pendingHouseholdRelationship" value="">

                    <!-- Personal Information Section -->
                    <section id="personal-details" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-user"></i> Personal Information</h2>
                            <p>Basic personal details and identification</p>
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>First Name</label>
                                    <p class="view-field"><?php echo strtoupper($resident['first_name']); ?></p>
                                    <input type="text" name="first_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['first_name']); ?>" style="display:none;" required>
                                </div>
                                <div class="info-item">
                                    <label>Middle Name</label>
                                    <p class="view-field"><?php echo strtoupper($resident['middle_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="middle_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['middle_name']); ?>" style="display:none;">
                                </div>
                                <div class="info-item">
                                    <label>Last Name</label>
                                    <p class="view-field"><?php echo strtoupper($resident['last_name']); ?></p>
                                    <input type="text" name="last_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['last_name']); ?>" style="display:none;" required>
                                </div>
                                <div class="info-item">
                                    <label>Suffix</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['suffix'] ?: 'N/A'); ?></p>
                                    <input type="text" name="suffix" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['suffix']); ?>" style="display:none;">
                                </div>
                                <div class="info-item">
                                    <label>Sex</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['sex']); ?></p>
                                    <select name="sex" class="form-control edit-field" style="display:none;" required>
                                        <option value="Male" <?php echo $resident['sex'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo $resident['sex'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    </select>
                                </div>
                                <div class="info-item">
                                    <label>Date of Birth</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['date_of_birth'] ? date('F d, Y', strtotime($resident['date_of_birth'])) : 'N/A'); ?></p>
                                    <input type="date" name="date_of_birth" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['date_of_birth']); ?>" style="display:none;" required>
                                </div>
                                <div class="info-item">
                                    <label>Age</label>
                                    <p><?php echo $age; ?> years old</p>
                                </div>
                                <div class="info-item">
                                    <label>Place of Birth</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['place_of_birth'] ?: 'N/A'); ?></p>
                                    <input type="text" name="place_of_birth" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['place_of_birth']); ?>" style="display:none;" required>
                                </div>
                                <div class="info-item">
                                    <label>Religion</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['religion'] ?: 'N/A'); ?></p>
                                    <?php 
                                    $religions = [
                                        "Roman Catholic", "Christian", "Iglesia ni Cristo", "Catholic", "Islam", 
                                        "Baptist", "Buddhism", "Born Again", "Church of God", "Jehovahs Witness", 
                                        "Protestant", "Seventh Day Adventist", "LDS-Mormons", "Evangelical", 
                                        "Pentecostal", "Unknown"
                                    ];
                                    $isOtherReligion = !in_array($resident['religion'], $religions) && !empty($resident['religion']);
                                    ?>
                                    <select name="religion_select" id="religionSelect" class="form-control edit-field" style="display:none;" onchange="toggleOtherReligion()">
                                        <option value="">Select Religion</option>
                                        <?php foreach($religions as $rel): ?>
                                            <option value="<?php echo htmlspecialchars($rel); ?>" <?php echo ($resident['religion'] === $rel) ? 'selected' : ''; ?>><?php echo htmlspecialchars($rel); ?></option>
                                        <?php endforeach; ?>
                                        <option value="Other" <?php echo $isOtherReligion ? 'selected' : ''; ?>>Other (pls. Specify)</option>
                                    </select>
                                    <input type="text" name="religion_other" id="religionOther" class="form-control mt-2 edit-field-conditional" placeholder="Specify Religion" value="<?php echo $isOtherReligion ? htmlspecialchars($resident['religion']) : ''; ?>" style="display: none;">
                                </div>
                                <div class="info-item">
                                    <label>Ethnicity</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['ethnicity'] ?: 'N/A'); ?></p>
                                    <?php 
                                    $isOtherEthnicity = !in_array($resident['ethnicity'], ['IPS', 'Non-IPS']) && !empty($resident['ethnicity']);
                                    ?>
                                    <select name="ethnicity_select" id="ethnicitySelect" class="form-control edit-field" style="display:none;" onchange="toggleOtherEthnicity()">
                                        <option value="">Select Ethnicity</option>
                                        <option value="IPS" <?php echo ($resident['ethnicity'] === 'IPS') ? 'selected' : ''; ?>>IPS (Indigenous People)</option>
                                        <option value="Non-IPS" <?php echo ($resident['ethnicity'] === 'Non-IPS') ? 'selected' : ''; ?>>Non-IPS</option>
                                    </select>
                                    <input type="text" name="ethnicity_other" id="ethnicityOther" class="form-control mt-2 edit-field-conditional" placeholder="Specify Ethnicity" value="<?php echo $isOtherEthnicity ? htmlspecialchars($resident['ethnicity']) : ''; ?>" style="display: none;">
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Contact Information -->
                    <section id="contact-info" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-phone"></i> Contact Information</h2>
                            <p>Address and communication details</p>
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Purok</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['purok'] ?: 'N/A'); ?></p>
                                    <select name="purok" id="purokInput" class="form-control edit-field" style="display:none;">
                                        <option value="">Select Purok</option>
                                        <option value="1" <?php echo $resident['purok'] == '1' ? 'selected' : ''; ?>>Purok 1</option>
                                        <option value="2" <?php echo $resident['purok'] == '2' ? 'selected' : ''; ?>>Purok 2</option>
                                        <option value="3" <?php echo $resident['purok'] == '3' ? 'selected' : ''; ?>>Purok 3</option>
                                        <option value="4" <?php echo $resident['purok'] == '4' ? 'selected' : ''; ?>>Purok 4</option>
                                    </select>
                                </div>
                                <div class="info-item">
                                    <label>Street Name</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['street_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="street_name" id="streetNameInput" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['street_name']); ?>" style="display:none;" >
                                </div>
                                <div class="info-item">
                                    <label>Mobile Number</label>
                                    <p class="view-field">+63 <?php echo htmlspecialchars($resident['mobile_number'] ?: 'N/A'); ?></p>
                                    <input type="text" name="mobile_number" class="form-control edit-field"  placeholder="XXX XXX XXXX" pattern="[0-9 ]+" maxlength="12" oninput="let v=this.value.replace(/\D/g,'').substring(0,10);if(v.length>6)this.value=v.slice(0,3)+' '+v.slice(3,6)+' '+v.slice(6);else if(v.length>3)this.value=v.slice(0,3)+' '+v.slice(3);else this.value=v;" value="<?php echo htmlspecialchars($resident['mobile_number']); ?>" style="display:none;">
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Family Information -->
                    <section id="family-info" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-users"></i> Family Information</h2>
                            <p>Family details and connections</p>
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                 <div class="info-item adult-only">
                                    <label>Civil Status</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['civil_status'] ?: 'N/A'); ?></p>
                                    <select name="civil_status" id="civilStatusSelect" class="form-control edit-field" style="display:none;" onchange="handleCivilStatusChange()">
                                        <option value="Single" <?php echo $resident['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo $resident['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                        <option value="Widow/er" <?php echo $resident['civil_status'] == 'Widow/er' ? 'selected' : ''; ?>>Widow/er</option>
                                        <option value="Separated" <?php echo $resident['civil_status'] == 'Separated' ? 'selected' : ''; ?>>Separated</option>
                                          <option value="Cohabitation" <?php echo $resident['civil_status'] == 'Cohabitation' ? 'selected' : ''; ?>>Cohabitation</option>
                                    </select>
                                </div>
                                <div class="info-item adult-only" id="spouseNameGroup">
                                    <label id="spouseNameLabel">Spouse Name</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['spouse_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="spouse_name" id="spouseNameInput" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['spouse_name']); ?>" style="display:none;">
                                </div>
                                <div class="info-item">
                                    <label>Father's Name</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['father_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="father_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['father_name']); ?>" style="display:none;">
                                </div>
                                <div class="info-item">
                                    <label>Mother's Name</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['mother_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="mother_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['mother_name']); ?>" style="display:none;">
                                </div>
                                <div class="info-item adult-only">
                                    <label>Number of Children</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['number_of_children'] ?: '0'); ?></p>
                                    <input type="number" name="number_of_children" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['number_of_children']); ?>" style="display:none;">
                                </div>
                            </div>

                            <div class="minor-only" style="grid-column: 1 / -1; width: 100%;">
                            <h3 class="subsection-title" style="margin-top: 20px;"><i class="fas fa-user-shield"></i> Guardian Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Guardian Name</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['guardian_name'] ?: 'N/A'); ?></p>
                                    <input type="text" name="guardian_name" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['guardian_name'] ?? ''); ?>" style="display:none;">
                                </div>
                                <div class="info-item">
                                    <label>Relationship to Guardian</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['guardian_relationship'] ?: 'N/A'); ?></p>
                                    <select name="guardian_relationship" class="form-control edit-field" style="display:none;">
                                        <option value="">Select Relationship</option>
                                        <option value="Father" <?php echo ($resident['guardian_relationship'] ?? '') == 'Father' ? 'selected' : ''; ?>>Father</option>
                                        <option value="Mother" <?php echo ($resident['guardian_relationship'] ?? '') == 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                        <option value="Grandparent" <?php echo ($resident['guardian_relationship'] ?? '') == 'Grandparent' ? 'selected' : ''; ?>>Grandparent</option>
                                        <option value="Legal Guardian" <?php echo ($resident['guardian_relationship'] ?? '') == 'Legal Guardian' ? 'selected' : ''; ?>>Legal Guardian</option>
                                        <option value="Other" <?php echo ($resident['guardian_relationship'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="info-item">
                                    <label>Guardian Contact Number</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['guardian_contact'] ?: 'N/A'); ?></p>
                                    <input type="text" name="guardian_contact" class="form-control edit-field" placeholder="XXX XXX XXXX" maxlength="12" value="<?php echo htmlspecialchars($resident['guardian_contact'] ?? ''); ?>" style="display:none;" oninput="let v=this.value.replace(/\D/g,'').substring(0,10);if(v.length>6)this.value=v.slice(0,3)+' '+v.slice(3,6)+' '+v.slice(6);else if(v.length>3)this.value=v.slice(0,3)+' '+v.slice(3);else this.value=v;">
                                </div>
                            </div>
                            </div>
                        </div>
                    </section>
                    
                 
                    <!-- Education & Employment Section -->
                    <section id="education-employment" class="profile-section age-10-plus" <?php echo $age < 10 ? 'style="display: none;"' : ''; ?>>
                        <div class="section-header">
                            <h2><i class="fas fa-graduation-cap"></i> Education & Employment</h2>
                            <p>Educational and employment information</p>
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                <div class="info-item age-10-plus" <?php echo $age < 10 ? 'style="display: none;"' : ''; ?>>
                                    <label>Educational Attainment</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['educational_attainment'] ?: 'N/A'); ?></p>
                                    <select name="educational_attainment" class="form-control edit-field" style="display:none;">
                                        <option value="" <?php echo empty($resident['educational_attainment']) ? 'selected' : ''; ?>>Select</option>
                                        <option value="No Formal Education" <?php echo $resident['educational_attainment'] == 'No Formal Education' ? 'selected' : ''; ?>>No Formal Education</option>
                                        <option value="Elementary Level" <?php echo $resident['educational_attainment'] == 'Elementary Level' ? 'selected' : ''; ?>>Elementary Level</option>
                                        <option value="Elementary Graduate" <?php echo $resident['educational_attainment'] == 'Elementary Graduate' ? 'selected' : ''; ?>>Elementary Graduate</option>
                                        <option value="High School Level" <?php echo $resident['educational_attainment'] == 'High School Level' ? 'selected' : ''; ?>>High School Level</option>
                                        <option value="High School Graduate" <?php echo $resident['educational_attainment'] == 'High School Graduate' ? 'selected' : ''; ?>>High School Graduate</option>
                                        <option value="College Level" <?php echo $resident['educational_attainment'] == 'College Level' ? 'selected' : ''; ?>>College Level</option>
                                        <option value="College Graduate" <?php echo $resident['educational_attainment'] == 'College Graduate' ? 'selected' : ''; ?>>College Graduate</option>
                                        <option value="Vocational" <?php echo $resident['educational_attainment'] == 'Vocational' ? 'selected' : ''; ?>>Vocational</option>
                                        <option value="Post Graduate" <?php echo $resident['educational_attainment'] == 'Post Graduate' ? 'selected' : ''; ?>>Post Graduate</option>
                                    </select>
                                </div>
                                <div class="info-item adult-only">
                                    <label>Employment Status</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['employment_status'] ?: 'N/A'); ?></p>
                                    <select name="employment_status" class="form-control edit-field" style="display:none;">
                                        <option value="" <?php echo empty($resident['employment_status']) ? 'selected' : ''; ?>>Select</option>
                                        <option value="Employed" <?php echo $resident['employment_status'] == 'Employed' ? 'selected' : ''; ?>>Employed</option>
                                        <option value="Unemployed" <?php echo $resident['employment_status'] == 'Unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                        <option value="Self-Employed" <?php echo $resident['employment_status'] == 'Self-Employed' ? 'selected' : ''; ?>>Self-Employed</option>
                                        <option value="Student" <?php echo $resident['employment_status'] == 'Student' ? 'selected' : ''; ?>>Student</option>
                                        <option value="Retired" <?php echo $resident['employment_status'] == 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                    </select>
                                </div>
                                <div class="info-item adult-only">
                                    <label>Occupation</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['occupation'] ?: 'N/A'); ?></p>
                                    <input type="text" name="occupation" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['occupation']); ?>" style="display:none;">
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Additional Information Section -->
                    <section id="additional-info" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-info-circle"></i> Additional Information</h2>
                            <p>Government programs and health information</p>
                        </div>
                        <div class="section-content">
                            <h3 class="subsection-title gov-programs-section"><i class="fas fa-landmark"></i> Government Programs</h3>
                            <div class="info-grid gov-programs-section">
                                <div class="info-item adult-only">
                                    <label>4Ps Member</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['fourps_member'] ?: 'No'); ?></p>
                                    <select name="fourps_member" class="form-control edit-field" style="display:none;">
                                        <option value="Yes" <?php echo $resident['fourps_member'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="No" <?php echo $resident['fourps_member'] == 'No' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="info-item adult-only">
                                    <label>4Ps ID Number</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['fourps_id'] ?: 'N/A'); ?></p>
                                    <input type="text" name="fourps_id" class="form-control edit-field" placeholder="XX-YYYY-ZZZZ" maxlength="12" oninput="let v=this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase().substring(0,10);if(v.length > 6) this.value = v.slice(0,2) + '-' + v.slice(2,6) + '-' + v.slice(6);else if(v.length > 2) this.value = v.slice(0,2) + '-' + v.slice(2);else this.value = v;" value="<?php echo htmlspecialchars($resident['fourps_id']); ?>" style="display:none;">
                                </div>
                                <div class="info-item voter-only">
                                    <label>Voter Status</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['voter_status'] ?: 'No'); ?></p>
                                    <select name="voter_status" class="form-control edit-field" style="display:none;">
                                        <option value="Yes" <?php echo $resident['voter_status'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                        <option value="No" <?php echo $resident['voter_status'] == 'No' ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="info-item voter-only">
                                    <label>Precinct Number</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['precinct_number'] ?: 'N/A'); ?></p>
                                    <input type="text" name="precinct_number" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['precinct_number']); ?>" style="display:none;">
                                </div>
                            </div>
                            
                            <h3 class="subsection-title"><i class="fas fa-heartbeat"></i> Health Information</h3>
                            <div class="info-grid">
                                <div class="info-item adult-only">
                                    <label>Philhealth ID</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['philhealth_id'] ?: 'N/A'); ?></p>
                                    <input type="text" name="philhealth_id" class="form-control edit-field" placeholder="1234-5678-9012" maxlength="14" oninput="let v=this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase().substring(0,12);if(v.length > 8) this.value = v.slice(0,4) + '-' + v.slice(4,8) + '-' + v.slice(8);else if(v.length > 4) this.value = v.slice(0,4) + '-' + v.slice(4);else this.value = v;" value="<?php echo htmlspecialchars($resident['philhealth_id']); ?>" style="display:none;">
                                </div>
                                <div class="info-item adult-only">
                                    <label>Membership Type</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['membership_type'] ?: 'N/A'); ?></p>
                                    <select name="membership_type" class="form-control edit-field" style="display:none;">
                                        <option value="" <?php echo empty($resident['membership_type']) ? 'selected' : ''; ?>>Select</option>
                                        <option value="Member" <?php echo $resident['membership_type'] == 'Member' ? 'selected' : ''; ?>>Member</option>
                                        <option value="Dependent" <?php echo $resident['membership_type'] == 'Dependent' ? 'selected' : ''; ?>>Dependent</option>
                                        <option value="None" <?php echo $resident['membership_type'] == 'None' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                                <div class="info-item adult-only">
                                    <label>Philhealth Category</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['philhealth_category'] ?: 'N/A'); ?></p>
                                    <select name="philhealth_category" class="form-control edit-field" style="display:none;">
                                        <option value="" <?php echo empty($resident['philhealth_category']) ? 'selected' : ''; ?>>Select</option>
                                        <option value="Direct Contributor" <?php echo $resident['philhealth_category'] == 'Direct Contributor' ? 'selected' : ''; ?>>Direct Contributor</option>
                                        <option value="Indirect Contributor" <?php echo $resident['philhealth_category'] == 'Indirect Contributor' ? 'selected' : ''; ?>>Indirect Contributor</option>
                                        <option value="Sponsored" <?php echo $resident['philhealth_category'] == 'Sponsored' ? 'selected' : ''; ?>>Sponsored</option>
                                        <option value="None" <?php echo $resident['philhealth_category'] == 'None' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                                <div class="info-item">
                                    <label>Age/Health Group</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['age_health_group'] ?: 'N/A'); ?></p>
                                    <select name="age_health_group" class="form-control edit-field" style="display:none;">
                                        <option value="" <?php echo empty($resident['age_health_group']) ? 'selected' : ''; ?>>Select</option>
                                        <option value="Newborn (0-28 days)" <?php echo $resident['age_health_group'] == 'Newborn (0-28 days)' ? 'selected' : ''; ?>>Newborn (0-28 days)</option>
                                        <option value="Infant (29 days - 1 year)" <?php echo $resident['age_health_group'] == 'Infant (29 days - 1 year)' ? 'selected' : ''; ?>>Infant (29 days - 1 year)</option>
                                        <option value="Child (1-9 years)" <?php echo $resident['age_health_group'] == 'Child (1-9 years)' ? 'selected' : ''; ?>>Child (1-9 years)</option>
                                        <option value="Adolescent (10-19 years)" <?php echo $resident['age_health_group'] == 'Adolescent (10-19 years)' ? 'selected' : ''; ?>>Adolescent (10-19 years)</option>
                                        <option value="Adult (20-59 years)" <?php echo $resident['age_health_group'] == 'Adult (20-59 years)' ? 'selected' : ''; ?>>Adult (20-59 years)</option>
                                        <option value="Senior Citizen (60+ years)" <?php echo $resident['age_health_group'] == 'Senior Citizen (60+ years)' ? 'selected' : ''; ?>>Senior Citizen (60+ years)</option>
                                    </select>
                                </div>
                                 <div class="info-item">
                                    <label>PWD Status</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['pwd_status'] ?: 'N/A'); ?></p>
                                 <select name="pwd_status" id="profilePwdStatus" class="form-control edit-field" style="display:none;">
                                    <option value="No" <?php echo $resident['pwd_status'] == 'No' ? 'selected' : ''; ?>>No</option>
                                      <option value="Yes" <?php echo $resident['pwd_status'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                    </select>
                                </div>
                                <div class="info-item" id="profilePwdTypeGroup" style="display: <?php echo ($resident['pwd_status'] ?? '') == 'Yes' ? 'block' : 'none'; ?>;">
                                    <label>Type of Disability <span class="required">*</span></label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['pwd_type'] ?? 'N/A'); ?></p>
                                    <input type="text" name="pwd_type" id="profilePwdType" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['pwd_type'] ?? ''); ?>" style="display:none;">
                                </div>
                                <div class="info-item" id="profilePwdIdGroup" style="display: <?php echo ($resident['pwd_status'] ?? '') == 'Yes' ? 'block' : 'none'; ?>;">
                                    <label>PWD ID Number <span class="text-muted">(Optional)</span></label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['pwd_id_number'] ?? 'N/A'); ?></p>
                                    <input type="text" name="pwd_id_number" id="profilePwdId" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['pwd_id_number'] ?? ''); ?>" style="display:none;">
                                </div>
                                <div class="info-item full-width">
                                    <label>Medical History</label>
                                    <p class="view-field"><?php echo htmlspecialchars($resident['medical_history'] ?: 'N/A'); ?></p>
                                   
                                </div>
                            </div>
                            
                            <?php if ($resident['sex'] === 'Female'): ?>
                                <h3 class="subsection-title"><i class="fas fa-female"></i> Women's Reproductive Health</h3>
                                <div class="info-grid">
                                        <div class="info-item">
                                            <label>Last Menstrual Period</label>
                                            <p class="view-field"><?php echo htmlspecialchars($resident['lmp_date'] ?? 'N/A'); ?></p>
                                            <input type="date" name="lmp_date" class="form-control edit-field" value="<?php echo htmlspecialchars($resident['lmp_date'] ?? ''); ?>" style="display:none;">
                                        </div>
                                        <div class="info-item">
                                            <label>Using FP Method</label>
                                            <p class="view-field"><?php echo htmlspecialchars($resident['using_fp_method'] ?? 'N/A'); ?></p>
                                            <select name="using_fp_method" class="form-control edit-field" style="display:none;">
                                                <option value="" <?php echo empty($resident['using_fp_method']) ? 'selected' : ''; ?>>Select</option>
                                                <option value="Yes" <?php echo ($resident['using_fp_method'] ?? '') == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                                <option value="No" <?php echo ($resident['using_fp_method'] ?? '') == 'No' ? 'selected' : ''; ?>>No</option>
                                            </select>
                                        </div>
                                        <div class="info-item">
                                            <label>FP Methods Used</label>
                                            <p class="view-field"><?php echo htmlspecialchars($resident['fp_methods_used'] ?? 'N/A'); ?></p>
                                            <?php 
                                            $fpMethods = ["Pills", "Injectable", "IUD", "Condom", "Implant", "Natural"];
                                            $isOtherFpMethod = !in_array($resident['fp_methods_used'], $fpMethods) && !empty($resident['fp_methods_used']);
                                            ?>
                                            <select name="fp_methods_select" id="fpMethodSelect" class="form-control edit-field" style="display:none;" onchange="toggleOtherFpMethod()">
                                                <option value="">Select</option>
                                                <?php foreach($fpMethods as $method): ?>
                                                    <option value="<?php echo htmlspecialchars($method); ?>" <?php echo ($resident['fp_methods_used'] === $method) ? 'selected' : ''; ?>><?php echo htmlspecialchars($method); ?></option>
                                                <?php endforeach; ?>
                                                <option value="Other" <?php echo $isOtherFpMethod ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <input type="text" name="fp_methods_other" id="fpMethodOther" class="form-control mt-2 edit-field-conditional" placeholder="Specify FP Method" value="<?php echo $isOtherFpMethod ? htmlspecialchars($resident['fp_methods_used']) : ''; ?>" style="display: none;">
                                        </div>
                                        <div class="info-item">
                                            <label>FP Status</label>
                                            <p class="view-field"><?php echo htmlspecialchars($resident['fp_status'] ?? 'N/A'); ?></p>
                                            <select name="fp_status" class="form-control edit-field" style="display:none;">
                                                <option value="" <?php echo empty($resident['fp_status']) ? 'selected' : ''; ?>>Select</option>
                                                <option value="Current User" <?php echo ($resident['fp_status'] ?? '') == 'Current User' ? 'selected' : ''; ?>>Current User</option>
                                                <option value="Dropout" <?php echo ($resident['fp_status'] ?? '') == 'Dropout' ? 'selected' : ''; ?>>Dropout</option>
                                                <option value="New Acceptor" <?php echo ($resident['fp_status'] ?? '') == 'New Acceptor' ? 'selected' : ''; ?>>New Acceptor</option>
                                            </select>
                                        </div>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="subsection-title"><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                            <div class="info-grid">
                                <div class="info-item full-width">
                                    <label>Remarks</label>
                                    <p class="view-field"><?php echo nl2br(htmlspecialchars($resident['remarks'] ?: 'None')); ?></p>
                                    <textarea name="remarks" class="form-control edit-field" style="display:none;"><?php echo htmlspecialchars($resident['remarks'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </section>
                    </form>
                    
                    <!-- Household Details Section -->
                    <section id="household-details" class="profile-section">
                        <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h2><i class="fas fa-home"></i> Household Details</h2>
                                <p>Household information and members</p>
                            </div>
                            <div class="household-actions edit-action" style="display: none; gap: 8px;">
                                <?php if ($householdInfo): ?>
                                    <?php if (hasPermission('perm_household_edit')): ?>
                                    <a href="households.php?edit=<?php echo $householdInfo['id']; ?>" class="btn btn-sm btn-primary" title="Edit Household">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($householdInfo['household_head_id'] == $residentId): ?>
                                        <?php if (empty($householdMembers)): ?>
                                        <button type="button" onclick="deleteHousehold(<?php echo $householdInfo['id']; ?>)" class="btn btn-sm btn-danger" title="Remove from Household">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                        <?php else: ?>
                                        <button type="button" onclick="if(confirm('To remove this resident from the household, please edit the household and transfer the head role to another member first. Go to Edit Household now?')) { window.location.href='households.php?edit=<?php echo $householdInfo['id']; ?>'; }" class="btn btn-sm btn-danger" title="Remove from Household">
                                            <i class="fas fa-times"></i> Remove
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <button type="button" onclick="removeHouseholdMember(<?php echo $householdInfo['id']; ?>, <?php echo $residentId; ?>)" class="btn btn-sm btn-danger" title="Remove from Household">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if (hasPermission('perm_household_create')): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="openAddToHouseholdModal()" title="Add to Household">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="section-content">
                            <?php if ($householdInfo): ?>
                                <div class="household-info-card">
                                    <h3 class="subsection-title"><i class="fas fa-info-circle"></i> Household Information</h3>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Household Number</label>
                                            <p><?php echo htmlspecialchars($householdInfo['household_number']); ?></p>
                                        </div>
                                        <div class="info-item">
                                            <label>Household Contact</label>
                                            <p><?php echo htmlspecialchars($householdInfo['household_contact'] ?: 'N/A'); ?></p>
                                        </div>
                                        <div class="info-item full-width">
                                            <label>Address</label>
                                            <p><?php echo htmlspecialchars($householdInfo['address']); ?></p>
                                        </div>
                                        <div class="info-item">
                                            <label>Water Source Type</label>
                                            <p><?php echo htmlspecialchars($householdInfo['water_source_type'] ?: 'N/A'); ?></p>
                                        </div>
                                        <div class="info-item">
                                            <label>Toilet Facility Type</label>
                                            <p><?php echo htmlspecialchars($householdInfo['toilet_facility_type'] ?: 'N/A'); ?></p>
                                        </div>
                                        <?php if (!empty($householdInfo['notes'])): ?>
                                            <div class="info-item full-width">
                                                <label>Notes</label>
                                                <p><?php echo htmlspecialchars($householdInfo['notes']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="household-head-card" style="margin-top: 30px;">
                                    <h3 class="subsection-title"><i class="fas fa-user-tie"></i> Household Head</h3>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <label>Full Name</label>
                                            <p>
                                                <?php if (isset($householdInfo['head_resident_id'])): ?>
                                                    <a href="resident_profile.php?id=<?php echo $householdInfo['head_resident_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                                        <?php echo htmlspecialchars($householdInfo['head_name']); ?>
                                                    </a>
                                                <?php elseif ($householdInfo['household_head_id'] == $residentId): ?>
                                                    <?php echo htmlspecialchars($householdInfo['head_name']); ?> <span style="color: var(--primary-color);">(You)</span>
                                                <?php else: ?>
                                                    <a href="resident_profile.php?id=<?php echo $householdInfo['household_head_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                                        <?php echo htmlspecialchars($householdInfo['head_name']); ?>
                                                    </a>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="info-item">
                                            <label>Date of Birth</label>
                                            <p><?php echo htmlspecialchars($householdInfo['head_dob'] ? date('F d, Y', strtotime($householdInfo['head_dob'])) : 'N/A'); ?></p>
                                        </div>
                                        <div class="info-item">
                                            <label>Sex</label>
                                            <p><?php echo htmlspecialchars($householdInfo['head_sex'] ?: 'N/A'); ?></p>
                                        </div>
                                        <?php if (isset($householdInfo['relationship_to_head'])): ?>
                                            <div class="info-item">
                                                <label>Your Relationship to Head</label>
                                                <p><strong><?php echo htmlspecialchars($householdInfo['relationship_to_head']); ?></strong></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php 
                                // Only show household members if current resident is the household head
                                if ($householdInfo['household_head_id'] == $residentId && !empty($householdMembers)): 
                                ?>
                                    <div class="household-members-card" style="margin-top: 30px;">
                                        <h3 class="subsection-title"><i class="fas fa-users"></i> Household Members (<?php echo count($householdMembers); ?>)</h3>
                                        <div class="members-table-wrapper">
                                            <table class="members-display-table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Name</th>
                                                        <th>Date of Birth</th>
                                                        <th>Sex</th>
                                                        <th>Relationship to Head</th>
                                                        <th>Mobile Number</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($householdMembers as $index => $member): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <?php if ($member['resident_id']): ?>
                                                                    <a href="resident_profile.php?id=<?php echo $member['resident_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                                                    </a>
                                                                <?php else: ?>
                                                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($member['date_of_birth'] ? date('M d, Y', strtotime($member['date_of_birth'])) : 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($member['sex'] ?: 'N/A'); ?></td>
                                                            <td><?php echo htmlspecialchars($member['relationship_to_head']); ?></td>
                                                            <td><?php echo htmlspecialchars($member['mobile_number'] ?: 'N/A'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                
                            <?php else: ?>
                                <p class="no-data">This resident is not associated with any household</p>
                            <?php endif; ?>
                        </div>
                    </section>

                     <section id="service-requests" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-clipboard-list"></i> Service Requests</h2>
                            <p>Document and certificate requests</p>
                        </div>
                        
                        <div class="section-content">
                            <?php
                            // Fetch certificate requests for this resident
                            $certRequests = [];
                            try {
                                $certStmt = $pdo->prepare("
                                    SELECT 
                                        id,
                                        reference_no,
                                        certificate_name,
                                        purpose,
                                        status,
                                        date_requested
                                    FROM certificate_requests
                                    WHERE resident_id = ?
                                    ORDER BY date_requested DESC, created_at DESC
                                ");
                                $certStmt->execute([$residentId]);
                                $certRequests = $certStmt->fetchAll();
                            } catch (PDOException $e) {
                                error_log("Error fetching certificate requests: " . $e->getMessage());
                            }
                            ?>
                            
                            <?php if (empty($certRequests)): ?>
                                <p class="no-data">No service requests found</p>
                            <?php else: ?>
                                <div class="cert-requests-table-wrapper">
                                    <table class="data-table cert-requests-table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Certificate</th>
                                                <th>Purpose</th>
                                                <th>Issued Date</th>
                                              
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $totalRequests = count($certRequests); foreach ($certRequests as $index => $req): ?>
                                                <tr>
                                                    <td><?php echo $totalRequests - $index; ?></td>
                                                    <td><?php echo htmlspecialchars($req['certificate_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($req['purpose'] ?: 'N/A'); ?></td>
                                                    <td><?php echo $req['date_requested'] ? date('M d, Y g:i A', strtotime($req['date_requested'])) : 'N/A'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section id="blotter-records" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-file-alt"></i> Blotter Records</h2>
                            <p>Incident and complaint records</p>
                        </div>
                        <div class="section-content">
                            <p class="no-data">No blotter records found</p>
                        </div>
                    </section>
                    
                   
                    
                    <section id="incident-report" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-exclamation-triangle"></i> Incident Report</h2>
                            <p>Reported incidents and violations</p>
                        </div>
                        <div class="section-content">
                            <p class="no-data">No incident reports found</p>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Add to Household Modal -->
    <div class="modal" id="addToHouseholdModal">
        <div class="modal-content" style="max-width: 700px; width: 95%;">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-home"></i> Add to Household</h3>
                <button type="button" class="btn-close-modal" onclick="closeAddToHouseholdModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body" style="padding: 24px;">
                <form id="addToHouseholdForm">
                    <input type="hidden" name="resident_id" value="<?php echo $residentId; ?>">
                    
                    <div class="form-group" style="margin-bottom: 24px;">
                        <label style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px; display: block;">Are you a Household Head? <span style="color: #ef4444;">*</span></label>
                        <div style="display: flex; gap: 24px;">
                            <label style="display: flex; align-items: center; gap: 8px; <?php echo ($age < 18) ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;'; ?> font-weight: 500; color: var(--text-primary); font-size: 14px;">
                                <input type="radio" name="householdHead" id="householdHeadYes" value="Yes" style="width: 18px; height: 18px; <?php echo ($age < 18) ? 'cursor: not-allowed;' : 'cursor: pointer;'; ?> accent-color: var(--primary-color);" <?php echo ($age < 18) ? 'disabled' : ''; ?>>
                                Yes
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500; color: var(--text-primary); font-size: 14px;">
                                <input type="radio" name="householdHead" id="householdHeadNo" value="No" style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-color);">
                                No
                            </label>
                        </div>
                        <?php if ($age < 18): ?>
                        <small style="color: #ef4444; display: block; margin-top: 8px;"><i class="fas fa-info-circle"></i> Minors cannot be registered as a Household Head.</small>
                        <?php endif; ?>
                    </div>

                    <!-- YES Panel: Create Household -->
                    <div id="householdYesPanel" style="display: none; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; margin-bottom: 15px;">
                        <h6 style="margin: 0 0 20px 0; color: var(--primary-color); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-plus-circle"></i> Create New Household
                        </h6>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="householdNumber" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Household Number <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="householdNumber" name="householdNumber" class="form-control" placeholder="e.g. HH-00001">
                            </div>
                            <div class="form-group">
                                <label for="householdContact" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Household Contact</label>
                                <div style="display: flex;">
                                    <span style="padding: 10px 12px; background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-right: 0; border-radius: 8px 0 0 8px; font-size: 14px; font-weight: 500; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                                        <img src="assets/image/contactph.png" alt="PH" style="height: 14px; border-radius: 2px;"> +63
                                    </span>
                                    <input type="tel" id="householdContact" name="householdContact" class="form-control" placeholder="XXX XXX XXXX" maxlength="12" style="border-radius: 0 8px 8px 0;" value="<?php echo htmlspecialchars($resident['mobile_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="householdAddress" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Household Address</label>
                                <input type="text" id="householdAddress" name="householdAddress" class="form-control" placeholder="Enter household address" value="<?php echo htmlspecialchars($resident['current_address'] ?? ''); ?>" style="width: 100%;">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="waterSourceType" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Water Source Type</label>
                                <select id="waterSourceType" name="waterSourceType" class="form-control">
                                    <option value="">Select Water Source</option>
                                    <option value="" disabled>Select Water Source</option>
                                    <option value="Level I (Point Spring)">Level I (Point Spring)</option>
                                    <option value="Level II (Communal Faucet system or stand post)">Level II (Communal Faucet system or stand post)</option>
                                    <option value="Level III (Waterworks system or individual house connection)">Level III (Waterworks system or individual house connection)</option>
                                    <option value="O (For doubtful sources, open dug well etc.)">O (For doubtful sources, open dug well etc.)</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label for="toiletFacilityType" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Toilet Facility Type</label>
                                <select id="toiletFacilityType" name="toiletFacilityType" class="form-control">
                                    <option value="">Select Toilet Facility</option>
                                    <option value="" disabled>----Sanitary Toilet----</option>
                                    <option value="P - Pour/Flush toilet connected to septic tank)">P - (Pour/Flush toilet connected to septic tank)</option>
                                    <option value="PF - Pour/Flush toilet connected to septic tank and sewerage system">PF - Pour/Flush toilet connected to septic tank and sewerage system</option>
                                    <option value="VIP - Ventilated impoved pit latrine (VIP) or composting">VIP - Ventilated impoved pit latrine (VIP) or composting</option>
                                    <option value="" disabled>----Unsanitary Toilet----</option>
                                    <option value="WS - Water-sealed connected to open drain">WS - Water-sealed connected to open drain</option>
                                    <option value="OH - Overhung Latrine">OH - Overhung Latrine</option>
                                    <option value="OP - Overpit Latrine">OP - Overpit Latrine</option>
                                    <option value="WO - Without Latrine">WO - Without Latrine</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- NO Panel: Find Existing Household -->
                    <div id="householdNoPanel" style="display: none; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; margin-bottom: 15px;">
                        <h6 style="margin: 0 0 20px 0; color: var(--primary-color); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-search"></i> Find Existing Household
                        </h6>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="householdSearch" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 6px; display: block;">Search Household</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="householdSearch" class="form-control" style="width: 100%;" placeholder="Search by household number, head name, or address..." >
                                <button type="button" class="btn btn-primary" id="searchHouseholdBtn" style="white-space: nowrap; padding: 10px 20px; border-radius: 8px;">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div id="householdSearchResults" style="display: none; margin-top: 15px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px;">
                            <div id="householdResultsList" style="max-height: 250px; overflow-y: auto;"></div>
                        </div>
                        <div id="selectedHouseholdCard" style="display: none; margin-top: 15px;">
                            <div style="background: var(--bg-secondary); border: 2px solid var(--primary-color); border-radius: 10px; padding: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
                                    <h6 style="margin: 0; color: var(--text-primary); font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-check-circle" style="color: #10b981; font-size: 18px;"></i> Selected Household
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-secondary" id="clearHouseholdBtn" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;">
                                        <i class="fas fa-times"></i> Clear
                                    </button>
                                </div>
                                <div id="selectedHouseholdInfo" style="margin: 0;"></div>
                            </div>
                        </div>
                        <input type="hidden" id="selectedHouseholdId" name="selectedHouseholdId" value="">
                        <input type="hidden" id="householdRelationship" name="householdRelationship" value="">
                    </div>
                    <input type="hidden" id="householdHeadValue" name="householdHeadValue" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddToHouseholdModal()" style="padding: 10px 20px; border-radius: 8px;">Cancel</button>
                <button type="button" class="btn btn-success" id="saveAddToHouseholdBtn" onclick="submitAddToHousehold()" style="padding: 10px 24px; border-radius: 8px; font-weight: 600;"><i class="fas fa-save"></i> Save</button>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <!-- WebcamJS Library -->
    <script src="assets/webcamjs/webcam.min.js"></script>
    <script>
        window.RESIDENT_AGE = <?php echo json_encode($age); ?>;
        window.RESIDENT_DATA = {
            pwdStatus: <?php echo json_encode($resident['pwd_status'] ?? 'No'); ?>,
            verificationStatus: <?php echo json_encode($resident['verification_status'] ?? 'Pending'); ?>,
            activityStatus: <?php echo json_encode($resident['activity_status'] ?? 'Active'); ?>,
            rejectionReason: <?php echo json_encode($resident['rejection_reason'] ?? ''); ?>,
            statusRemarks: <?php echo json_encode($resident['status_remarks'] ?? ''); ?>,
            existingPhoto: <?php echo json_encode($resident['photo'] ?? ''); ?>
        };
    </script>
    <script src="assets/js/edit-resident.js"></script>
</body>
</html>
