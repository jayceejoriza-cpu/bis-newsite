<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';

// Enforce: redirect if user lacks view permission
requirePermission('perm_resident_view');

// Page title
$pageTitle = 'Residents';

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
// Helper Functions
// ============================================

/**
 * Calculate age from date of birth
 */
function calculateAge($dateOfBirth) {
    if (empty($dateOfBirth)) return 0;
    $dob = new DateTime($dateOfBirth);
    $now = new DateTime();
    return $now->diff($dob)->y;
}

/**
 * Generate formatted resident ID
 */
function generateResidentId($id) {
    // Format: W-00000 (W- followed by 5 random numbers)
    // Use the database ID to generate a consistent 5-digit number
    $fiveDigitNumber = str_pad($id % 100000, 5, '0', STR_PAD_LEFT);
    return "W-{$fiveDigitNumber}";
}

/**
 * Get initials from name
 */
function getInitials($firstName, $lastName) {
 $last = !empty($lastName) ? strtoupper(substr($lastName, 0, 1)) : '';
  return $last;
}

/**
 * Get avatar color class based on index
 */
function getAvatarColor($index) {
    $colors = ['blue', 'pink', 'teal', 'yellow', 'green', 'orange', 'lime', 'indigo', 'cyan', 'purple'];
    return 'avatar-' . $colors[$index % count($colors)];
}

/**
 * Format full name
 */
function formatFullName($firstName, $middleName, $lastName, $suffix) {
   $name = trim($lastName);
    if (!empty($firstName)) {
        $name .= ', ' . trim($firstName);
    }
    if (!empty($suffix)) {
        $name .= ' ' . trim($suffix);
    }
    return $name;
}

// ============================================
// Fetch Residents Data
// ============================================
$residents = [];
$totalResidents = 0;

try {
    // Get total count
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM residents");
    $totalResidents = $countStmt->fetch()['total'];
    
    // Fetch residents data with all filter fields
    $stmt = $pdo->prepare("
        SELECT 
            id,
            resident_id,
            photo,
            first_name,
            middle_name,
            last_name,
            suffix,
            sex,
            date_of_birth,
            religion,
            ethnicity,
            civil_status,
            educational_attainment,
            employment_status,
            fourps_member,
            age_health_group,
            pwd_status,
            verification_status,
            voter_status,
            activity_status,
            purok,
            occupation,
            membership_type,
            philhealth_category,
            medical_history,
            using_fp_method,
            fp_methods_used,
            fp_status
        FROM residents
        WHERE activity_status != 'Archived'
        ORDER BY last_name ASC, first_name ASC
    ");
    
    $stmt->execute();
    $residents = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching residents: " . $e->getMessage());
    $residents = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/residents.css">
    <link rel="stylesheet" href="assets/css/residents-grid.css">
    
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
        
        <!-- Residents Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage resident records</p>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-outline-secondary" id="printMasterlistBtn" title="Print Masterlist">
                        <i class="fas fa-print"></i>
                        Print Masterlist
                    </button>
                    <?php if (hasPermission('perm_resident_create')): ?>
                    <button class="btn btn-primary" id="createResidentBtn">
                        <i class="fas fa-plus"></i>
                        Create Resident
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Print-Only Header (hidden on screen, visible when printing) -->
            <div class="print-only print-header">
                <div class="print-header-logo">
                    <img src="assets/image/brgylogo.jpg" alt="Barangay Logo" class="print-logo">
                </div>
                <div class="print-header-info">
                    <h2 class="print-barangay-name"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Barangay Information System'; ?></h2>
                    <h3 class="print-list-title">Residents Masterlist</h3>
                    <p class="print-meta">
                        Date Printed: <strong><?php echo date('F d, Y'); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Total Records: <strong id="printTotalRecords"><?php echo number_format(count($residents)); ?></strong>
                    </p>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="voters">Registered Voters</button>
               <!-- <button class="tab-btn" data-filter="active">Active</button>-->
            </div>
            
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar" style="position: relative;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="filterBtn">
                    <i class="fas fa-filter"></i>
                    <span class="filter-notification" id="filterNotification" style="display: none;">
                        <span class="filter-count" id="filterCount">0</span>
                    </span>
                </button>
                
                <button class="btn btn-icon" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
                
                <div class="view-toggle">
                    <button class="view-btn active" data-view="list">
                        <i class="fas fa-list"></i>
                    </button>
                    <button class="view-btn" data-view="grid">
                        <i class="fas fa-th"></i>
                    </button>
                </div>
            </div>
            
            <!-- Advanced Filter Panel -->
            <style>
                @media (min-width: 992px) {
                    .filter-panel-body .filter-grid {
                        grid-template-columns: repeat(4, 1fr) !important;
                    }
                }
            </style>
            <div class="filter-panel" id="filterPanel" style="display: none;">
                <div class="filter-panel-header">
                    <h3><i class="fas fa-filter"></i> Select Filters</h3>
                </div>
                <div class="filter-panel-body">
                    <div class="filter-grid">
                        
                        <div class="filter-item">
                            <label for="filterSex">Sex</label>
                            <select id="filterSex" class="filter-select">
                                <option value="">All</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterPurok">Purok</label>
                            <select id="filterPurok" class="filter-select">
                                <option value="">All</option>
                                <option value="1">Purok 1</option>
                                <option value="2">Purok 2</option>
                                <option value="3">Purok 3</option>
                                <option value="4">Purok 4</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <label for="filterAgeHealthGroup">Age/Health Group</label>
                            <select id="filterAgeHealthGroup" class="filter-select">
                                <option value="">All</option>
                                <option value="Newborn (0-28 days)">Newborn (0-28 days)</option>
                                <option value="Infant (29 days - 1 year)">Infant (29 days - 1 year)</option>
                                <option value="Child (1-9 years)">Child (1-9 years)</option>
                                <option value="Adolescent (10-19 years)">Adolescent (10-19 years)</option>
                                <option value="Adult (20-59 years)">Adult (20-59 years)</option>
                                <option value="Senior Citizen (60+ years)">Senior Citizen (60+ years)</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterPwdStatus">Disability Status</label>
                            <select id="filterPwdStatus" class="filter-select">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterReligion">Religion</label>
                            <select id="filterReligion" class="filter-select">
                                <option value="">All</option>
                                <option value="Roman Catholic">Roman Catholic</option>
                                <option value="Christian">Christian</option>
                                <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                                <option value="Catholic">Catholic</option>
                                <option value="Islam">Islam</option>
                                <option value="Baptist">Baptist</option>
                                <option value="Buddhism">Buddhism</option>
                                <option value="Born Again">Born Again</option>
                                <option value="Church of God">Church of God</option>
                                <option value="Jehovahs Witness">Jehovahs Witness</option>
                                <option value="Protestant">Protestant</option>
                                <option value="Seventh Day Adventist">Seventh Day Adventist</option>
                                <option value="LDS-Mormons">LDS-Mormons</option>
                                <option value="Evangelical">Evangelical</option>
                                <option value="Pentecostal">Pentecostal</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterCivilStatus">Civil Status</label>
                            <select id="filterCivilStatus" class="filter-select">
                                <option value="">All</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Separated">Separated</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterDateOfBirth">Date of Birth</label>
                            <input type="date" id="filterDateOfBirth" class="filter-select">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterEthnicity">Ethnicity</label>
                            <select id="filterEthnicity" class="filter-select">
                                <option value="">All</option>
                                <option value="IPS">IPS (Indigenous People)</option>
                                <option value="Non-IPS">Non-IPS</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterEducation">Educational Attainment</label>
                            <select id="filterEducation" class="filter-select">
                                <option value="">All</option>
                                <option value="No Formal Education">No Formal Education</option>
                                <option value="Elementary Level">Elementary Level</option>
                                <option value="Elementary Graduate">Elementary Graduate</option>
                                <option value="High School Level">High School Level</option>
                                <option value="High School Graduate">High School Graduate</option>
                                <option value="College Level">College Level</option>
                                <option value="College Graduate">College Graduate</option>
                                <option value="Vocational">Vocational</option>
                                <option value="Post Graduate">Post Graduate</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterOccupation">Occupation</label>
                            <input type="text" id="filterOccupation" class="filter-select" placeholder="Enter Occupation">
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterEmploymentStatus">Employment Status</label>
                            <select id="filterEmploymentStatus" class="filter-select">
                                <option value="">All</option>
                                <option value="Employed">Employed</option>
                                <option value="Unemployed">Unemployed</option>
                                <option value="Self-Employed">Self-Employed</option>
                                <option value="Student">Student</option>
                                <option value="Retired">Retired</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filter4ps">4Ps Member</label>
                            <select id="filter4ps" class="filter-select">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterVoterStatus">Registered Voter</label>
                            <select id="filterVoterStatus" class="filter-select">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterMembershipType">Philhealth Membership Type</label>
                            <select id="filterMembershipType" class="filter-select">
                                <option value="">All</option>
                                <option value="Direct Contributor">Direct Contributor</option>
                                <option value="Indirect Contributor">Indirect Contributor</option>
                                <option value="Dependent">Dependent</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterPhilhealthCategory">Philhealth Category</label>
                            <select id="filterPhilhealthCategory" class="filter-select">
                                <option value="">All</option>
                                <option value="Employed Private">Employed Private</option>
                                <option value="Employed Gov">Employed Government</option>
                                <option value="Indigent">Indigent</option>
                                <option value="Sponsored">Sponsored</option>
                                <option value="Lifetime">Lifetime Member</option>
                                <option value="Senior Citizen">Senior Citizen</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterMedicalHistory">Medical History</label>
                            <input type="text" id="filterMedicalHistory" class="filter-select" placeholder="Enter Medical History">
                        </div>

                        <!-- Optional Women's Reproductive Health Fields -->
                        <div class="filter-item">
                            <label for="filterUsingFpMethod">Using FP Method</label>
                            <select id="filterUsingFpMethod" class="filter-select">
                                <option value="">All</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterFpMethodsUsed">FP Methods Used</label>
                            <select id="filterFpMethodsUsed" class="filter-select">
                                <option value="">All</option>
                                <option value="Pills">Pills</option>
                                <option value="IUD">IUD</option>
                                <option value="Injectables">Injectables</option>
                                <option value="Implant">Implant</option>
                                <option value="Condom">Condom</option>
                                <option value="BTL">BTL</option>
                                <option value="NSV">NSV</option>
                                <option value="LAM">LAM</option>
                                <option value="Natural">Natural</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterFpStatus">FP Status</label>
                            <select id="filterFpStatus" class="filter-select">
                                <option value="">All</option>
                                <option value="Current User">Current User</option>
                                <option value="Dropout">Dropout</option>
                                <option value="Acceptor">Acceptor</option>
                            </select>
                        </div>
                        
                    </div>
                </div>
                <div class="filter-panel-footer">
                    <button class="btn btn-secondary" id="clearFiltersBtn">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button class="btn btn-primary" id="applyFiltersBtn">
                        <i class="fas fa-check"></i> Apply Now
                    </button>
                </div>
            </div>
            
            <!-- Residents Table -->
            <div class="table-container">
                <table class="data-table residents-table" id="residentsTable">
                    <thead>
                        <tr>

                            <th>Resident ID</th>
                            <th>
                                <span class="header-text">
                                    Full Name
                                </span>
                            </th>
                            <th>Purok</th>
                            <th>Registered Voter</th>
                            <th>Date of Birth</th>
                            <th>Sex</th>
                            <th>Activity Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="residentsTableBody">
                        <?php if (empty($residents)): ?>
                            <!-- Empty state -->
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                    <p style="color: #6b7280; font-size: 16px; margin: 0;">No residents found</p>
                                    <p style="color: #9ca3af; font-size: 14px; margin-top: 8px;">Start by adding a new resident</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $index => $resident): 
                                // Prepare data
                                $fullName = formatFullName(
                                    $resident['first_name'], 
                                    $resident['middle_name'], 
                                    $resident['last_name'], 
                                    $resident['suffix']
                                );
                                $initials = getInitials($resident['first_name'], $resident['last_name']);
                                $avatarColor = getAvatarColor($index);
                                // Use resident_id from database, or generate if not set
                                $residentId = !empty($resident['resident_id']) ? $resident['resident_id'] : generateResidentId($resident['id']);
                                $age = calculateAge($resident['date_of_birth']);
                                $dob = !empty($resident['date_of_birth']) ? date('m/d/Y', strtotime($resident['date_of_birth'])) : 'N/A';
                                
                                // Badge classes
                                $verificationBadge = 'badge-' . strtolower($resident['verification_status']);
                                $voterBadge = ($resident['voter_status'] === 'Yes') ? 'badge-yes' : 'badge-no';
                                $activityBadge = 'badge-' . strtolower($resident['activity_status']);
                                $sortName = $resident['last_name'] . ', ' . $resident['first_name'];
                            ?>
                            <tr data-religion="<?php echo htmlspecialchars($resident['religion'] ?? ''); ?>"
                                data-ethnicity="<?php echo htmlspecialchars($resident['ethnicity'] ?? ''); ?>"
                                data-civil-status="<?php echo htmlspecialchars($resident['civil_status'] ?? ''); ?>"
                                data-education="<?php echo htmlspecialchars($resident['educational_attainment'] ?? ''); ?>"
                                data-employment="<?php echo htmlspecialchars($resident['employment_status'] ?? ''); ?>"
                                data-fourps="<?php echo htmlspecialchars($resident['fourps_member'] ?? ''); ?>"
                                data-age-health-group="<?php echo htmlspecialchars($resident['age_health_group'] ?? ''); ?>"
                                data-activity-status="<?php echo htmlspecialchars($resident['activity_status'] ?? ''); ?>"
                                data-pwd-status="<?php echo htmlspecialchars($resident['pwd_status'] ?? 'No'); ?>"
                                data-sex="<?php echo htmlspecialchars($resident['sex'] ?? ''); ?>"
                                data-purok="<?php echo htmlspecialchars($resident['purok'] ?? ''); ?>"
                                data-voter-status="<?php echo htmlspecialchars($resident['voter_status'] ?? ''); ?>"
                                data-occupation="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>"
                                data-membership-type="<?php echo htmlspecialchars($resident['membership_type'] ?? ''); ?>"
                                data-philhealth-category="<?php echo htmlspecialchars($resident['philhealth_category'] ?? ''); ?>"
                                data-medical-history="<?php echo htmlspecialchars($resident['medical_history'] ?? ''); ?>"
                                data-using-fp-method="<?php echo htmlspecialchars($resident['using_fp_method'] ?? ''); ?>"
                                data-fp-methods-used="<?php echo htmlspecialchars($resident['fp_methods_used'] ?? ''); ?>"
                                data-fp-status="<?php echo htmlspecialchars($resident['fp_status'] ?? ''); ?>">
                               
                                    <td><?php echo htmlspecialchars($residentId); ?></td>
                                     <td data-sort="<?php echo htmlspecialchars($sortName); ?>"><a href="resident_profile.php?id=<?php echo htmlspecialchars($resident['id']); ?>" class="resident-name-link">
                                        <div class="resident-name">
                                            <span class="avatar <?php echo htmlspecialchars($avatarColor); ?>">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </span>
                                            <span><?php echo htmlspecialchars($fullName); ?></span>
                                        </div>
                                    </a>
                                </td>
                                
                                <td><?php echo htmlspecialchars($resident['purok'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($voterBadge); ?>">
                                        <?php echo htmlspecialchars($resident['voter_status'] ?: 'No'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($dob . ' - ' . $age); ?></td>
                                <td><?php echo htmlspecialchars($resident['sex']); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($activityBadge); ?>">
                                        <?php echo htmlspecialchars($resident['activity_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-action" data-resident-id="<?php echo htmlspecialchars($resident['id']); ?>">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Residents Grid View -->
            <div id="residentsGrid" class="residents-grid" style="display: none;">
                <?php if (empty($residents)): ?>
                    <div class="empty-state" style="grid-column: 1/-1; text-align: center; padding: 40px;">
                        <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                        <p style="color: #6b7280; font-size: 16px; margin: 0;">No residents found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($residents as $index => $resident): 
                        $fullName = formatFullName(
                            $resident['first_name'], 
                            $resident['middle_name'], 
                            $resident['last_name'], 
                            $resident['suffix']
                        );
                        $initials = getInitials($resident['first_name'], $resident['last_name']);
                        $avatarColor = getAvatarColor($index);
                        $residentId = !empty($resident['resident_id']) ? $resident['resident_id'] : generateResidentId($resident['id']);
                        $age = calculateAge($resident['date_of_birth']);
                        $sortName = $resident['last_name'] . ', ' . $resident['first_name'];
                    ?>
                    <div class="resident-card" 
                         data-name="<?php echo htmlspecialchars($sortName); ?>"
                         data-religion="<?php echo htmlspecialchars($resident['religion'] ?? ''); ?>"
                         data-ethnicity="<?php echo htmlspecialchars($resident['ethnicity'] ?? ''); ?>"
                         data-civil-status="<?php echo htmlspecialchars($resident['civil_status'] ?? ''); ?>"
                         data-education="<?php echo htmlspecialchars($resident['educational_attainment'] ?? ''); ?>"
                         data-employment="<?php echo htmlspecialchars($resident['employment_status'] ?? ''); ?>"
                         data-fourps="<?php echo htmlspecialchars($resident['fourps_member'] ?? ''); ?>"
                         data-age-health-group="<?php echo htmlspecialchars($resident['age_health_group'] ?? ''); ?>"
                         data-date-of-birth="<?php echo htmlspecialchars($resident['date_of_birth'] ?? ''); ?>"
                         data-pwd-status="<?php echo htmlspecialchars($resident['pwd_status'] ?? 'No'); ?>"
                         data-voter-status="<?php echo htmlspecialchars($resident['voter_status'] ?: 'No'); ?>"
                         data-activity-status="<?php echo htmlspecialchars($resident['activity_status'] ?? 'Active'); ?>"
                         data-sex="<?php echo htmlspecialchars($resident['sex'] ?? ''); ?>"
                         data-purok="<?php echo htmlspecialchars($resident['purok'] ?? ''); ?>"
                         data-occupation="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>"
                         data-membership-type="<?php echo htmlspecialchars($resident['membership_type'] ?? ''); ?>"
                         data-philhealth-category="<?php echo htmlspecialchars($resident['philhealth_category'] ?? ''); ?>"
                         data-medical-history="<?php echo htmlspecialchars($resident['medical_history'] ?? ''); ?>"
                         data-using-fp-method="<?php echo htmlspecialchars($resident['using_fp_method'] ?? ''); ?>"
                         data-fp-methods-used="<?php echo htmlspecialchars($resident['fp_methods_used'] ?? ''); ?>"
                         data-fp-status="<?php echo htmlspecialchars($resident['fp_status'] ?? ''); ?>">
                        <div class="avatar <?php echo htmlspecialchars($avatarColor); ?>">
                            <?php echo htmlspecialchars($initials); ?>
                        </div>
                        <h3 class="resident-name"><?php echo htmlspecialchars($fullName); ?></h3>
                        <p class="resident-id"><?php echo htmlspecialchars($residentId); ?></p>
                        <div class="details">
                            <div class="detail-item">
                                <span class="label">Age</span>
                                <span class="value"><?php echo $age; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Sex</span>
                                <span class="value"><?php echo htmlspecialchars($resident['sex']); ?></span>
                            </div>
                        </div>
                        <div class="badges">
                            <span class="badge <?php echo ($resident['voter_status'] === 'Yes') ? 'badge-yes' : 'badge-no'; ?>">
                                Registered Voter: <?php echo htmlspecialchars($resident['voter_status'] ?: 'No'); ?>
                            </span>
                            <span class="badge badge-<?php echo strtolower($resident['activity_status'] ?? 'Active'); ?>">
                                Activity Status: <?php echo htmlspecialchars($resident['activity_status'] ?? 'Active'); ?>
                            </span>
                        </div>
                        <div class="actions">
                            <a href="resident_profile.php?id=<?php echo htmlspecialchars($resident['id']); ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>Showing <strong>1-10</strong> of <strong><?php echo number_format($totalResidents); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- Print-Only Footer (hidden on screen, visible when printing) -->
            <div class="print-only print-footer" style="margin-top: 60px; width: 100%;">
                <div style="display: flex; justify-content: space-between; padding: 0 50px; width: 100%;">
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Prepared by:</p>
                        <p style="margin: 0; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?></p>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Certified Correct:</p>
                        <p style="margin: 0; font-size: 14px;">Barangay Captain</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        resident_view:   <?php echo hasPermission('perm_resident_view')   ? 'true' : 'false'; ?>,
        resident_create: <?php echo hasPermission('perm_resident_create') ? 'true' : 'false'; ?>,
        resident_edit:   <?php echo hasPermission('perm_resident_edit')   ? 'true' : 'false'; ?>,
        resident_delete: <?php echo hasPermission('perm_resident_delete') ? 'true' : 'false'; ?>
    };
    </script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/residents.js"></script>
</body>
</html>
