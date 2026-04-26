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
    $diff = $now->diff($dob);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' old';

    if ($diff->m > 0) {
        return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' old';
    }
    return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' old';
}

/**
 * Generate formatted resident ID
 */
function generateResidentId($id) {
    // Format: W-YY0001
    $year = date('y');
    $sequence = str_pad($id % 10000, 4, '0', STR_PAD_LEFT);
    return "W-{$year}{$sequence}";
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
    if (!empty($middleName)) {
        $name .= ' ' . strtoupper(substr(trim($middleName), 0, 1)) . '.';
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
            voter_status,
            activity_status,
            purok,
            street_name,
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
<link rel="icon" type="image/png" href="uploads/favicon.png">
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
    
    <style>
        .print-only { display: none !important; }

        .btn-print {
            padding: 9px 18px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--color-transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }
    </style>
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
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
                    <?php if (hasPermission('perm_resident_print')): ?>
                    <div class="dropdown d-inline-block">
                        <button class="btn-print dropdown-toggle" type="button" id="exportPrintDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export"></i>
                            Export / Print Masterlist
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="exportPrintDropdown" style="font-size: 14px;">
                            <li><button class="dropdown-item py-2" id="exportCsvBtn"><i class="fas fa-file-csv me-2 text-success"></i> Export Csv</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item py-2" id="printMasterlistBtn"><i class="fas fa-print me-2 text-primary"></i> Print Masterlist</button></li>
                        </ul>
                    </div>
                    <?php endif; ?>
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
                    <h2 class="print-barangay-name"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Barangay Management System'; ?></h2>
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
                    <input type="text" placeholder="Search resident ID or name " id="searchInput">
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
                                <option value="Live-In">Live-In</option>
                                 <option value="Widow/er">Widow/er</option>
                                   <option value="Separated">Separated</option>
                                 <option value="Cohabitation">Cohabitation</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label>Birth Date (Month & Day)</label>
                            <div class="d-flex gap-1">
                                <select id="filterBirthMonth" class="filter-select" style="flex: 2;">
                                    <option value="">Month</option>
                                    <?php
                                    for ($m = 1; $m <= 12; $m++) {
                                        $m_padded = str_pad($m, 2, '0', STR_PAD_LEFT);
                                        $m_name = date('F', mktime(0, 0, 0, $m, 1));
                                        echo "<option value='$m_padded'>$m_name</option>";
                                    }
                                    ?>
                                </select>
                                <select id="filterBirthDay" class="filter-select" style="flex: 1;">
                                    <option value="">Day</option>
                                    <?php
                                    for ($d = 1; $d <= 31; $d++) {
                                        $d_padded = str_pad($d, 2, '0', STR_PAD_LEFT);
                                        echo "<option value='$d_padded'>$d_padded</option>";
                                    }
                                    ?>
                                </select>
                            </div>
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
                                            <option value="Pre-School">Pre-School</option>
                                            <option value="Elementary Level">Elementary Level</option>
                                            <option value="Elementary Graduate">Elementary Graduate</option>
                                            <option value="Elementary Undergraduate">Elementary Undergraduate</option>
                                            <option value="High School Level">High School Level</option>
                                            <option value="High School Graduate">High School Graduate</option>
                                            <option value="High School Undergraduate">High School Undergraduate</option>
                                            <option value="Senior High School">Senior High School</option>
                                            <option value="Adv Learning System">Adv Learning System</option>
                                            <option value="Vocational Course">Vocational Course</option>
                                            <option value="College Level">College Level</option>
                                            <option value="College Undergraduate">College Undergraduate</option>
                                            <option value="College Graduate">College Graduate</option>
                                            <option value="Post Graduate/ Material/ Doctorate">Post Graduate/ Material/ Doctorate</option>
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
                                <option value="OFW">OFW</option>
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
                                <option value="Member">Member</option>
                                <option value="Dependent">Dependent</option>
                                <option value="None">None</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterPhilhealthCategory">Philhealth Category</label>
                            <select id="filterPhilhealthCategory" class="filter-select">
                                <option value="">All</option>
                                <option value="Direct Contributor">Direct Contributor</option>
                                 <option value="Indirect Contributor">Indirect Contributor</option>
                                 <option value="Sponsored">Sponsored</option>
                                  <option value="None">None</option>
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
                                <option value="Injectable">Injectable</option>
                                <option value="IUD">IUD</option>
                                <option value="Condom">Condom</option>
                                <option value="Implant">Implant</option>
                                <option value="Natural">Natural</option>
                            </select>   
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterFpStatus">FP Status</label>
                            <select id="filterFpStatus" class="filter-select">
                                <option value="">All</option>
                                 <option value="Current User">Current User</option>
                                 <option value="Dropout">Dropout</option>
                                  <option value="New Acceptor">New Acceptor</option>
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
                                $dob = !empty($resident['date_of_birth']) ? date('M d, Y', strtotime($resident['date_of_birth'])) : 'N/A';
                                $sortDob = !empty($resident['date_of_birth']) ? date('Y-m-d', strtotime($resident['date_of_birth'])) : '9999-12-31';
                                
                                // Badge classes
                                $voterBadge = ($resident['voter_status'] === 'Yes') ? 'badge-yes' : 'badge-no';
                                $activityBadge = 'badge-' . strtolower($resident['activity_status']);
                                $sortName = $resident['last_name'] . ', ' . $resident['first_name'];
                                $photo = !empty($resident['photo']) ? htmlspecialchars($resident['photo']) : null;
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
                                data-date-of-birth="<?php echo htmlspecialchars($resident['date_of_birth'] ?? ''); ?>"
                                data-voter-status="<?php echo htmlspecialchars($resident['voter_status'] ?? ''); ?>"
                                data-occupation="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>"
                                data-membership-type="<?php echo htmlspecialchars($resident['membership_type'] ?? ''); ?>"
                                data-philhealth-category="<?php echo htmlspecialchars($resident['philhealth_category'] ?? ''); ?>"
                                data-medical-history="<?php echo htmlspecialchars($resident['medical_history'] ?? ''); ?>"
                                data-using-fp-method="<?php echo htmlspecialchars($resident['using_fp_method'] ?? ''); ?>"
                                data-fp-methods-used="<?php echo htmlspecialchars($resident['fp_methods_used'] ?? ''); ?>"
                                data-fp-status="<?php echo htmlspecialchars($resident['fp_status'] ?? ''); ?>"
                                data-street-name="<?php echo htmlspecialchars($resident['street_name'] ?? '') ?>">
                               
                                    <td><?php echo htmlspecialchars($residentId); ?></td>
                                     <td data-sort="<?php echo htmlspecialchars($sortName); ?>"><a href="resident_profile.php?id=<?php echo htmlspecialchars($resident['id']); ?>" class="resident-name-link">
                                        <div class="resident-name">
                                            <?php if ($photo): ?>
                                                <img src="<?php echo $photo; ?>" alt="Photo" class="avatar" style="object-fit: cover;">
                                            <?php else: ?>
                                                <span class="avatar <?php echo htmlspecialchars($avatarColor); ?>">
                                                    <?php echo htmlspecialchars($initials); ?>
                                                </span>
                                            <?php endif; ?>
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
                                <td data-sort="<?php echo htmlspecialchars($sortDob); ?>"><?php echo htmlspecialchars($dob . ' - ' . $age); ?></td>
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
                    <?php 
                    foreach ($residents as $index => $resident): 
                        
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
                        $photo = !empty($resident['photo']) ? htmlspecialchars($resident['photo']) : null;
                    ?>
                    <div class="resident-card" 
                         data-resident-id="<?php echo htmlspecialchars($resident['id']); ?>"
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
                         data-fp-status="<?php echo htmlspecialchars($resident['fp_status'] ?? ''); ?>"
                         data-street-name="<?php echo htmlspecialchars($resident['street_name'] ?? '') ?>">
                        <?php if ($photo): ?>
                            <img src="<?php echo $photo; ?>" alt="Photo" class="avatar" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar <?php echo htmlspecialchars($avatarColor); ?>">
                                <?php echo htmlspecialchars($initials); ?>
                            </div>
                        <?php endif; ?>
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
                    <span>Showing 
                        <select class="form-select form-select-sm" id="pageSizeList" style="display: inline-block; width: auto; margin: 0 5px;">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        of <strong><?php echo number_format($totalResidents); ?></strong></span>
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
    
     <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  margin: 10% auto; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="archiveModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Archive Resident</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure you want to archive this resident? This action will move the record to the archives.</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm.
                </div>
                
                <form id="archiveForm">
                    <input type="hidden" id="archiveResidentId" name="id">
                    
                    <div style="margin-bottom: 1.25rem;">
                        <label for="archiveReason" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-comment-alt" style="margin-right: 5px;"></i> Reason for Archiving <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="archiveReason" name="reason" rows="2" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box; font-family: inherit;" placeholder="Please state the reason..." required></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="archivePassword" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-key" style="margin-right: 5px;"></i> Your Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="archivePassword" name="password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box;" placeholder="Enter your password" required>
                            <button type="button" id="toggleArchivePassword" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="cancelArchive" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #6b7280; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" id="confirmArchiveBtn" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #ef4444; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash"></i> Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Deceased Confirmation Modal -->
    <div id="deceasedModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  margin: 10% auto; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-user-times"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="deceasedModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Confirm Deceased Status</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure that this person is deceased? This selection will update the resident's status in the system.</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm this action.
                </div>
                
                <form id="deceasedForm">
                    <input type="hidden" id="deceasedResidentId" name="id">
                    <div style="margin-bottom: 1.5rem;">
                        <label for="deceasedPassword" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-key" style="margin-right: 5px;"></i> Your Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="deceasedPassword" name="password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box;" placeholder="Enter your password" required>
                            <button type="button" id="toggleDeceasedPassword" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="cancelDeceased" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #6b7280; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-times"></i> No, Cancel
                        </button>
                        <button type="submit" id="confirmDeceasedBtn" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #ef4444; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-check"></i> Yes, Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        resident_view:   <?php echo hasPermission('perm_resident_view')   ? 'true' : 'false'; ?>,
        resident_create: <?php echo hasPermission('perm_resident_create') ? 'true' : 'false'; ?>,
        resident_edit:   <?php echo hasPermission('perm_resident_edit')   ? 'true' : 'false'; ?>,
        resident_delete: <?php echo hasPermission('perm_resident_delete') ? 'true' : 'false'; ?>,
        resident_status: <?php echo hasPermission('perm_resident_status') ? 'true' : 'false'; ?>,
        resident_print_id: <?php echo hasPermission('perm_resident_print') ? 'true' : 'false'; ?>,
        resident_archive: <?php echo hasPermission('perm_resident_archive') ? 'true' : 'false'; ?>
    };
    </script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/residents.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const exportCsvBtn = document.getElementById('exportCsvBtn');
        if (exportCsvBtn) {
            exportCsvBtn.addEventListener('click', function() {
                // Trigger server-side export with current URL filters to match the table display
                window.location.href = 'model/export_residents_csv.php' + window.location.search;
            });
        }
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const archiveModal = document.getElementById('archiveModal');
        const archiveForm = document.getElementById('archiveForm');
        const cancelBtn = document.getElementById('cancelArchive');
        const togglePasswordBtn = document.getElementById('toggleArchivePassword');
        const passwordInput = document.getElementById('archivePassword');

        // Deceased Modal Elements
        const deceasedModal = document.getElementById('deceasedModal');
        const deceasedForm = document.getElementById('deceasedForm');
        const cancelDeceased = document.getElementById('cancelDeceased');
        const toggleDeceasedPassword = document.getElementById('toggleDeceasedPassword');
        const deceasedPasswordInput = document.getElementById('deceasedPassword');
        
        // Override function in case residents.js calls it directly
        window.deleteResident = window.archiveResident = function(residentId) {
            if (window.BIS_PERMS && window.BIS_PERMS.resident_archive === false) {
                showNotification('Permission denied to archive residents.', 'error');
                return;
            }
            document.getElementById('archiveResidentId').value = residentId;
            passwordInput.value = '';
            const reasonInput = document.getElementById('archiveReason');
            if (reasonInput) reasonInput.value = '';
            
            const modalTitle = document.getElementById('archiveModalTitle');
            if (modalTitle) {
                if (lastClickedResidentId == residentId && lastClickedResidentName && lastClickedResidentCode) {
                    modalTitle.textContent = `Archive ${lastClickedResidentName} (${lastClickedResidentCode})`;
                } else {
                    modalTitle.textContent = 'Archive Resident';
                }
            }
            
            archiveModal.style.display = 'block';
            if (reasonInput) {
                reasonInput.focus();
            } else {
                passwordInput.focus();
            }
        };

        let lastClickedResidentId = null;
        let lastClickedResidentName = '';
        let lastClickedResidentCode = '';

        // Capture clicks on .btn-action to store the resident ID
        document.addEventListener('click', function(e) {
            const actionBtn = e.target.closest('.btn-action');
            if (actionBtn) {
                const residentId = actionBtn.getAttribute('data-resident-id') || actionBtn.getAttribute('data-id');
                if (residentId) {
                    lastClickedResidentId = residentId;
                    lastClickedResidentName = '';
                    lastClickedResidentCode = '';
                    
                    const row = actionBtn.closest('tr');
                    if (row) {
                        lastClickedResidentCode = row.cells[0].textContent.trim();
                        const nameSpan = row.querySelector('.resident-name span:last-child');
                        lastClickedResidentName = nameSpan ? nameSpan.textContent.trim() : '';
                    } else {
                        const card = actionBtn.closest('.resident-card');
                        if (card) {
                            const nameEl = card.querySelector('.resident-name');
                            lastClickedResidentName = nameEl ? nameEl.textContent.trim() : '';
                            const idEl = card.querySelector('.resident-id');
                            lastClickedResidentCode = idEl ? idEl.textContent.trim() : '';
                        }
                    }
                }
            }
        }, true);

        // Intercept the delete action via capturing phase
        // This catches the click before the original event delegation handles it
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.action-menu-item[data-action="delete"], .action-menu-item[data-action="archive"]');
            if (deleteBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                if (window.BIS_PERMS && window.BIS_PERMS.resident_archive === false) {
                    showNotification('Permission denied to archive residents.', 'error');
                    return;
                }
                
                const menu = deleteBtn.closest('.action-menu');
                let residentId = null;
                
                if (menu) {
                    residentId = menu.getAttribute('data-resident-id') || menu.getAttribute('data-id') || menu.getAttribute('data-record-id');
                }
                
                if (!residentId) {
                    residentId = lastClickedResidentId;
                }
                
                if (residentId) {
                    document.getElementById('archiveResidentId').value = residentId;
                    passwordInput.value = '';
                const reasonInput = document.getElementById('archiveReason');
                if (reasonInput) reasonInput.value = '';
                
                const modalTitle = document.getElementById('archiveModalTitle');
                if (modalTitle) {
                    if (lastClickedResidentName && lastClickedResidentCode) {
                        modalTitle.innerHTML = `Archive <u>${lastClickedResidentName} (${lastClickedResidentCode})</u>`;
                        
                    } else {
                        modalTitle.textContent = 'Archive Resident';
                    }
                }
                    
                    archiveModal.style.display = 'block';
                if (reasonInput) {
                    reasonInput.focus();
                } else {
                    passwordInput.focus();
                }
                    if (menu) menu.remove(); // Close the action menu
                }
            }
        }, true);
        
        // Modal close handlers
        cancelBtn.addEventListener('click', () => archiveModal.style.display = 'none');
        cancelDeceased.addEventListener('click', () => deceasedModal.style.display = 'none');
        
        // Password toggle
        togglePasswordBtn.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            togglePasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        toggleDeceasedPassword.addEventListener('click', () => {
            const type = deceasedPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            deceasedPasswordInput.setAttribute('type', type);
            toggleDeceasedPassword.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // Deceased Modal Global Function
        window.openDeceasedModal = function(residentId, row, currentStatus) {
            document.getElementById('deceasedResidentId').value = residentId;
            deceasedPasswordInput.value = '';
            
            const modalTitle = document.getElementById('deceasedModalTitle');
            if (modalTitle) {
                if (lastClickedResidentName && lastClickedResidentCode) {
                    modalTitle.innerHTML = `Confirm Deceased Status for <u>${lastClickedResidentName} (${lastClickedResidentCode})</u>`;
                } else {
                    modalTitle.textContent = 'Confirm Deceased Status';
                }
            }
            
            deceasedModal.style.display = 'block';
            deceasedPasswordInput.focus();
        };

        deceasedForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const password = deceasedPasswordInput.value;
            const residentId = document.getElementById('deceasedResidentId').value;
            const actionBtn = document.querySelector(`.btn-action[data-resident-id="${residentId}"]`);
            const row = actionBtn ? actionBtn.closest('tr') : null;
            const submitBtn = document.getElementById('confirmDeceasedBtn');
            
            if (typeof updateActivityStatus === 'function' && row) {
                updateActivityStatus(residentId, 'Deceased', row, 'Alive', password, submitBtn, deceasedPasswordInput);
            }
        });

        // Form submit
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmArchiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
            
            const formData = new FormData(this);
            
            fetch('model/delete_resident.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    archiveModal.style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Error archiving resident', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        });
        
        // Notification helper
        function showNotification(message, type = 'info') {
            document.querySelectorAll('.notification').forEach(n => n.remove());
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            const icon = type === 'success' ? 'check-circle' : (type === 'error' || type === 'warning' ? 'exclamation-circle' : 'info-circle');
            notification.innerHTML = `<i class="fas fa-${icon}"></i><span>${message}</span>`;
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; background: ${type === 'success' ? '#10b981' : (type === 'error' || type === 'warning' ? '#ef4444' : '#3b82f6')};
                color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                display: flex; align-items: center; gap: 10px; z-index: 10000000; animation: slideInRight 0.3s ease;
            `;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    });
    </script>
</body>
</html>
