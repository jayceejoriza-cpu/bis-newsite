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
               CONCAT(r.last_name, ', ', r.first_name, ' ', COALESCE(r.middle_name, '')) as head_name,
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
                   CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as full_name,
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
                   CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as head_name,
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
                SELECT hm.*,
                       CONCAT(r.first_name, ' ', COALESCE(r.middle_name, ''), ' ', r.last_name, ' ', COALESCE(r.suffix, '')) as full_name,
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
function formatFullName($firstName, $middleName, $lastName, $suffix) {
    $name = trim($lastName);
    if (!empty($firstName)) {
        $name .= ', ' . trim($firstName);
    }
    if (!empty($middleName)) {
        $name .= ' ' . trim($middleName);
    }
    if (!empty($suffix)) {
        $name .= ' ' . trim($suffix);
    }
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
                    <div class="profile-photo">
                        <?php if (!empty($resident['photo'])): ?>
                            <img src="<?php echo htmlspecialchars($resident['photo']); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-header-info">
                        <h1 class="profile-name"><?php echo strtoupper  ($fullName); ?></h1>
                        <p class="profile-id"><?php echo htmlspecialchars($resident['resident_id'] ?: 'N/A'); ?></p>
                        <div class="profile-badges">
                            <span class="badge badge-<?php echo strtolower($resident['verification_status']); ?>">
                                <i class="fas fa-<?php echo $resident['verification_status'] === 'Verified' ? 'check-circle' : 'clock'; ?>"></i>
                                <?php echo htmlspecialchars($resident['verification_status']); ?>
                            </span>
                            <span class="badge badge-<?php echo strtolower($resident['activity_status']); ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo htmlspecialchars($resident['activity_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="profile-header-actions">
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i>
                        Print Profile
                    </button>
                    <?php if (hasPermission('perm_resident_edit')): ?>
                    <button class="btn btn-primary" onclick="editResident()">
                        <i class="fas fa-edit"></i>
                        Edit Profile
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
                        <a href="#emergency-contact" class="profile-nav-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>Emergency Contact</span>
                        </a>
                        <a href="#additional-info" class="profile-nav-item">
                            <i class="fas fa-info-circle"></i>
                            <span>Additional Information</span>
                        </a>
                        <a href="#household-details" class="profile-nav-item">
                            <i class="fas fa-home"></i>
                            <span>Household Details</span>
                        </a>
                        <a href="#blotter-records" class="profile-nav-item">
                            <i class="fas fa-file-alt"></i>
                            <span>Blotter Records</span>
                        </a>
                        <a href="#service-requests" class="profile-nav-item">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Service Requests</span>
                        </a>
                        <a href="#incident-report" class="profile-nav-item">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Incident Report</span>
                        </a>
                    </nav>
                </div>
                
                <!-- Main Content Area -->
                <div class="profile-main-content">
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
                                    <p><?php echo strtoupper($resident['first_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Middle Name</label>
                                    <p><?php echo strtoupper($resident['middle_name'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Name</label>
                                    <p><?php echo strtoupper($resident['last_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Suffix</label>
                                    <p><?php echo htmlspecialchars($resident['suffix'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Sex</label>
                                    <p><?php echo htmlspecialchars($resident['sex']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Date of Birth</label>
                                    <p><?php echo htmlspecialchars($resident['date_of_birth'] ? date('F d, Y', strtotime($resident['date_of_birth'])) : 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Age</label>
                                    <p><?php echo $age; ?> years old</p>
                                </div>
                               
                                <div class="info-item">
                                    <label>Religion</label>
                                    <p><?php echo htmlspecialchars($resident['religion'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Ethnicity</label>
                                    <p><?php echo htmlspecialchars($resident['ethnicity'] ?: 'N/A'); ?></p>
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
                                <div class="info-item full-width">
                                    <label>Complete Address</label>
                                    <p><?php echo htmlspecialchars($resident['current_address'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Mobile Number</label>
                                    <p>+63 <?php echo htmlspecialchars($resident['mobile_number'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email Address</label>
                                    <p><?php echo htmlspecialchars($resident['email'] ?: 'N/A'); ?></p>
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
                                 <div class="info-item">
                                    <label>Civil Status</label>
                                    <p><?php echo htmlspecialchars($resident['civil_status'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Spouse Name</label>
                                    <p><?php echo htmlspecialchars($resident['spouse_name'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Father's Name</label>
                                    <p><?php echo htmlspecialchars($resident['father_name'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Mother's Name</label>
                                    <p><?php echo htmlspecialchars($resident['mother_name'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Number of Children</label>
                                    <p><?php echo htmlspecialchars($resident['number_of_children'] ?: '0'); ?></p>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                 
                    <!-- Education & Employment Section -->
                    <section id="education-employment" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-graduation-cap"></i> Education & Employment</h2>
                            <p>Educational and employment information</p>
                        </div>
                        <div class="section-content">
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Educational Attainment</label>
                                    <p><?php echo htmlspecialchars($resident['educational_attainment'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Employment Status</label>
                                    <p><?php echo htmlspecialchars($resident['employment_status'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Occupation</label>
                                    <p><?php echo htmlspecialchars($resident['occupation'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Monthly Income</label>
                                    <p><?php echo htmlspecialchars($resident['monthly_income'] ?: 'N/A'); ?></p>
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
                            <h3 class="subsection-title"><i class="fas fa-landmark"></i> Government Programs</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>4Ps Member</label>
                                    <p><?php echo htmlspecialchars($resident['fourps_member'] ?: 'No'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>4Ps ID Number</label>
                                    <p><?php echo htmlspecialchars($resident['fourps_id'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Voter Status</label>
                                    <p><?php echo htmlspecialchars($resident['voter_status'] ?: 'No'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Precinct Number</label>
                                    <p><?php echo htmlspecialchars($resident['precinct_number'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                            
                            <h3 class="subsection-title"><i class="fas fa-heartbeat"></i> Health Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Philhealth ID</label>
                                    <p><?php echo htmlspecialchars($resident['philhealth_id'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Membership Type</label>
                                    <p><?php echo htmlspecialchars($resident['membership_type'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Philhealth Category</label>
                                    <p><?php echo htmlspecialchars($resident['philhealth_category'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Age/Health Group</label>
                                    <p><?php echo htmlspecialchars($resident['age_health_group'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="info-item full-width">
                                    <label>Medical History</label>
                                    <p><?php echo htmlspecialchars($resident['medical_history'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($resident['sex'] === 'Female' && (!empty($resident['lmp_date']) || !empty($resident['using_fp_method']) || !empty($resident['fp_methods_used']) || !empty($resident['fp_status']))): ?>
                                <h3 class="subsection-title"><i class="fas fa-female"></i> Women's Reproductive Health</h3>
                                <div class="info-grid">
                                    <?php if (!empty($resident['lmp_date'])): ?>
                                        <div class="info-item">
                                            <label>Last Menstrual Period</label>
                                            <p><?php echo htmlspecialchars($resident['lmp_date']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($resident['using_fp_method'])): ?>
                                        <div class="info-item">
                                            <label>Using FP Method</label>
                                            <p><?php echo htmlspecialchars($resident['using_fp_method']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($resident['fp_methods_used'])): ?>
                                        <div class="info-item">
                                            <label>FP Methods Used</label>
                                            <p><?php echo htmlspecialchars($resident['fp_methods_used']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($resident['fp_status'])): ?>
                                        <div class="info-item">
                                            <label>FP Status</label>
                                            <p><?php echo htmlspecialchars($resident['fp_status']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($resident['remarks'])): ?>
                                <h3 class="subsection-title"><i class="fas fa-sticky-note"></i> Additional Notes</h3>
                                <div class="info-grid">
                                    <div class="info-item full-width">
                                        <label>Remarks</label>
                                        <p><?php echo htmlspecialchars($resident['remarks']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Household Details Section -->
                    <section id="household-details" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-home"></i> Household Details</h2>
                            <p>Household information and members</p>
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
                    
                    <section id="blotter-records" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-file-alt"></i> Blotter Records</h2>
                            <p>Incident and complaint records</p>
                        </div>
                        <div class="section-content">
                            <p class="no-data">No blotter records found</p>
                        </div>
                    </section>
                    
                    <section id="service-requests" class="profile-section">
                        <div class="section-header">
                            <h2><i class="fas fa-clipboard-list"></i> Service Requests</h2>
                            <p>Document and certificate requests</p>
                        </div>
                        <div class="section-content">
                            <p class="no-data">No service requests found</p>
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
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script>
        // Set active navigation
        document.addEventListener('DOMContentLoaded', () => {
            // Smooth scroll for sidebar navigation
            document.querySelectorAll('.profile-nav-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove active class from all items
                    document.querySelectorAll('.profile-nav-item').forEach(nav => {
                        nav.classList.remove('active');
                    });
                    
                    // Add active class to clicked item
                    item.classList.add('active');
                    
                    // Smooth scroll to section
                    const targetId = item.getAttribute('href');
                    const targetSection = document.querySelector(targetId);
                    if (targetSection) {
                        targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
            
            // Update active nav on scroll
            const sections = document.querySelectorAll('.profile-section');
            const navItems = document.querySelectorAll('.profile-nav-item');
            
            window.addEventListener('scroll', () => {
                let current = '';
                let minDistance = Infinity;
                
                // Find the section that is most in view (closest to top of viewport)
                sections.forEach(section => {
                    const sectionTop = section.offsetTop;
                    const sectionHeight = section.clientHeight;
                    const scrollPosition = window.pageYOffset + 220; // Account for header/offset
                    
                    // Calculate distance from scroll position to section top
                    const distance = Math.abs(scrollPosition - sectionTop);
                    
                    // If this section is closer to the scroll position and is visible
                    if (distance < minDistance && scrollPosition >= sectionTop - 100) {
                        minDistance = distance;
                        current = section.getAttribute('id');
                    }
                });
                
                // Update active state
                navItems.forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('href') === '#' + current) {
                        item.classList.add('active');
                    }
                });
            });
        });
        
        function editResident() {
            // Get resident ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const residentId = urlParams.get('id');
            
            if (residentId) {
                // Redirect to edit resident page
                window.location.href = `model/edit-resident.php?id=${residentId}`;
            } else {
                alert('Resident ID not found');
            }
        }
    </script>
</body>
</html>
