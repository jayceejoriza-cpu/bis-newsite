<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

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
    $first = !empty($firstName) ? strtoupper(substr($firstName, 0, 1)) : '';
    $last = !empty($lastName) ? strtoupper(substr($lastName, 0, 1)) : '';
    return $first . $last;
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
    $name = trim($firstName);
    if (!empty($middleName)) {
        $name .= ' ' . trim($middleName);
    }
    $name .= ' ' . trim($lastName);
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
            verification_status,
            voter_status,
            activity_status
        FROM residents
        WHERE activity_status != 'Archived'
        ORDER BY created_at DESC
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
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/residents.css">
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
                <button class="btn btn-primary" id="createResidentBtn">
                    <i class="fas fa-plus"></i>
                    Create Resident
                </button>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="verified">Verified</button>
                <button class="tab-btn" data-filter="voters">Voters</button>
                <button class="tab-btn" data-filter="active">Active</button>
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
            <div class="filter-panel" id="filterPanel" style="display: none;">
                <div class="filter-panel-header">
                    <h3><i class="fas fa-filter"></i> Select Filters</h3>
                </div>
                <div class="filter-panel-body">
                    <div class="filter-grid">
                        <!-- Column 1 -->
                        <div class="filter-item">
                            <label for="filterAgeGroup">Age Group</label>
                            <select id="filterAgeGroup" class="filter-select">
                                <option value="">All</option>
                                <option value="0-17">0-17 (Minor)</option>
                                <option value="18-35">18-35 (Young Adult)</option>
                                <option value="36-59">36-59 (Adult)</option>
                                <option value="60+">60+ (Senior)</option>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="filterReligion">Religion</label>
                            <select id="filterReligion" class="filter-select">
                                <option value="">All</option>
                                <option value="Roman Catholic">Roman Catholic</option>
                                <option value="Islam">Islam</option>
                                <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                                <option value="Protestant">Protestant</option>
                                <option value="Born Again">Born Again</option>
                                <option value="Others">Others</option>
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
                        
                        <!-- Column 2 -->
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
                        
                        <!-- Column 3 -->
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
                            <th class="header-with-info">
                                <span class="header-text">
                                    Full Name
                                    <i class="fas fa-info-circle" title="Click on any resident name to view their profile"></i>
                                </span>
                            </th>
                            <th>Resident ID</th>
                            <th>Verification Status</th>
                            <th>Voter Status</th>
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
                            ?>
                            <tr data-religion="<?php echo htmlspecialchars($resident['religion'] ?? ''); ?>"
                                data-ethnicity="<?php echo htmlspecialchars($resident['ethnicity'] ?? ''); ?>"
                                data-civil-status="<?php echo htmlspecialchars($resident['civil_status'] ?? ''); ?>"
                                data-education="<?php echo htmlspecialchars($resident['educational_attainment'] ?? ''); ?>"
                                data-employment="<?php echo htmlspecialchars($resident['employment_status'] ?? ''); ?>"
                                data-fourps="<?php echo htmlspecialchars($resident['fourps_member'] ?? ''); ?>"
                                data-age-health-group="<?php echo htmlspecialchars($resident['age_health_group'] ?? ''); ?>">
                                <td>
                                    <a href="resident_profile.php?id=<?php echo htmlspecialchars($resident['id']); ?>" class="resident-name-link">
                                        <div class="resident-name">
                                            <span class="avatar <?php echo htmlspecialchars($avatarColor); ?>">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </span>
                                            <span><?php echo htmlspecialchars($fullName); ?></span>
                                        </div>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($residentId); ?></td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars($verificationBadge); ?>">
                                        <?php echo htmlspecialchars($resident['verification_status']); ?>
                                    </span>
                                </td>
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
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong><?php echo number_format($totalResidents); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn">2</button>
                    <button class="page-btn">3</button>
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <span class="page-dots">...</span>
                    <button class="page-btn">335</button>
                    <button class="page-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/table.js"></script>
    <script src="js/residents.js"></script>
</body>
</html>
