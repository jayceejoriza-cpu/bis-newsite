<?php
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

// Enforce: redirect if user lacks view permission
requirePermission('perm_officials_view');

$pageTitle = 'Barangay Officials';

// ============================================
// Database Connection
// ============================================
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ============================================
// Helper Functions
// ============================================
function getInitials($firstName, $lastName) {
    $first = !empty($firstName) ? strtoupper(substr($firstName, 0, 1)) : '';
    $last  = !empty($lastName)  ? strtoupper(substr($lastName,  0, 1)) : '';
    return $first . $last;
}

function formatFullName($firstName, $middleName, $lastName, $suffix) {
    $name = trim($firstName);
    if (!empty($middleName)) $name .= ' ' . trim($middleName);
    $name .= ' ' . trim($lastName);
    if (!empty($suffix))     $name .= ' ' . trim($suffix);
    return $name;
}

// ============================================
// Fetch Barangay Captain Term Periods (for dropdown)
// ============================================
$captainTerms = [];
try {
    $captainStmt = $pdo->query("
        SELECT id, term_start, term_end
        FROM barangay_officials
        WHERE position = 'Barangay Captain'
        ORDER BY term_start DESC
    ");
    $captainTerms = $captainStmt->fetchAll();
} catch (PDOException $e) {
    $captainTerms = [];
}

// ============================================
// Determine Selected Term Period
// ============================================
$viewAll = isset($_GET['view']) && $_GET['view'] === 'all';

if ($viewAll) {
    $selectedTermStart = null;
    $selectedTermEnd   = null;
} else {
    $selectedTermStart = $_GET['term_start'] ?? ($captainTerms[0]['term_start'] ?? null);
    $selectedTermEnd   = $_GET['term_end']   ?? ($captainTerms[0]['term_end']   ?? null);
}

// ============================================
// Determine Org Chart Title
// ============================================
$orgChartTitle = 'PRESENT OFFICIALS';
if ($viewAll) {
    $orgChartTitle = 'ALL BARANGAY OFFICIALS';
} elseif ($selectedTermStart && $selectedTermEnd) {
    $today = date('Y-m-d');
    if ($today < $selectedTermStart || $today > $selectedTermEnd) {
        $startYear = date('Y', strtotime($selectedTermStart));
        $endYear   = date('Y', strtotime($selectedTermEnd));
        $orgChartTitle = "{$startYear}–{$endYear} OFFICIALS";
    }
}

// ============================================
// Fetch Officials Data
// ============================================
$officials = [];
try {
    if ($selectedTermStart && $selectedTermEnd) {
        $stmt = $pdo->prepare("
            SELECT
                bo.id,
                bo.resident_id,
                bo.position,
                bo.committee,
                bo.hierarchy_level,
                bo.term_start,
                bo.term_end,
                bo.status,
                bo.appointment_type,
                bo.photo,
                bo.contact_number,
                bo.fullname,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.suffix,
                r.photo AS resident_photo
            FROM barangay_officials bo
            LEFT JOIN residents r ON bo.resident_id = r.id
            WHERE bo.term_start <= :term_end
              AND bo.term_end   >= :term_start
            ORDER BY bo.hierarchy_level ASC, bo.position ASC
        ");
        $stmt->execute([
            ':term_start' => $selectedTermStart,
            ':term_end'   => $selectedTermEnd
        ]);
    } else {
        $stmt = $pdo->query("
            SELECT
                bo.id,
                bo.resident_id,
                bo.position,
                bo.committee,
                bo.hierarchy_level,
                bo.term_start,
                bo.term_end,
                bo.status,
                bo.appointment_type,
                bo.photo,
                bo.contact_number,
                bo.fullname,
                r.first_name,
                r.middle_name,
                r.last_name,
                r.suffix,
                r.photo AS resident_photo
            FROM barangay_officials bo
            LEFT JOIN residents r ON bo.resident_id = r.id
            ORDER BY bo.hierarchy_level ASC, bo.position ASC
        ");
    }
    $officials = $stmt->fetchAll();

    // Group officials by hierarchy level
    $officialsByLevel = [
        1 => [], // Top level (Captain)
        2 => [], // Middle level (Kagawads)
        3 => []  // Bottom level (SK, Secretary, Treasurer)
    ];
    
    foreach ($officials as $official) {
        $level = $official['hierarchy_level'] ?? 2;
        $officialsByLevel[$level][] = $official;
    }
} catch (PDOException $e) {
    error_log("Error fetching officials: " . $e->getMessage());
    $officials = [];
    $officialsByLevel = [1 => [], 2 => [], 3 => []];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/officials.css">
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
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
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/header.php'; ?>

        <div class="dashboard-content">

            <!-- Page Header -->
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage barangay officials</p>
                </div>
                <div class="page-header-actions">
                    <?php if (hasPermission('perm_officials_print')): ?>
                    <button class="btn-print" id="printOfficialsBtn" title="Print Officials List">
                        <i class="fas fa-print"></i>
                        Print List
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('perm_officials_create')): ?>
                    <button class="btn btn-primary" id="createOfficialBtn">
                        <i class="fas fa-plus"></i> Create Brgy Official
                    </button>
                    <?php endif; ?>
                </div>
            </div>

             <!-- Organizational Chart Section -->
            <div class="org-chart-section">
                <div class="org-chart-header">
                    <h2 class="org-chart-title">
                        <i class="fas fa-sitemap"></i>
                        <?php echo htmlspecialchars($orgChartTitle); ?>
                    </h2>
                </div>
                
                <?php if (empty($officials)): ?>
                    <!-- Empty State -->
                    <div class="empty-officials">
                        <i class="fas fa-users-slash"></i>
                        <h3>No Active Officials</h3>
                        <p>Start by adding barangay officials to display the organizational structure</p>
                    </div>
                <?php else: ?>
                    <!-- Organizational Hierarchy -->
                    <div class="org-hierarchy">
                        <!-- Top Level (Barangay Captain) -->
                        <?php if (!empty($officialsByLevel[1])): ?>
                        <div class="hierarchy-level top">
                            <?php foreach ($officialsByLevel[1] as $official): 
                                $fullName = !empty($official['first_name']) 
                                    ? formatFullName($official['first_name'], $official['middle_name'], $official['last_name'], $official['suffix'])
                                    : 'Vacant';
                                $initials = !empty($official['first_name']) 
                                    ? getInitials($official['first_name'], $official['last_name'])
                                    : 'V';
                                $photo = $official['photo'] ?? $official['resident_photo'] ?? null;
                            ?>
                            <div class="official-card captain" data-official-id="<?php echo $official['id']; ?>">
                                <div class="official-photo <?php echo empty($photo) ? 'placeholder' : ''; ?>">
                                    <?php if (!empty($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($initials); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="official-name"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="official-position"><?php echo htmlspecialchars($official['position']); ?></div>
                                <?php if (!empty($official['committee'])): ?>
                                    <div class="official-committee"><?php echo htmlspecialchars($official['committee']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Middle Level (Kagawads) -->
                        <?php if (!empty($officialsByLevel[2])): ?>
                        <div class="hierarchy-level middle">
                            <?php foreach ($officialsByLevel[2] as $official): 
                                $fullName = !empty($official['first_name']) 
                                    ? formatFullName($official['first_name'], $official['middle_name'], $official['last_name'], $official['suffix'])
                                    : 'Vacant';
                                $initials = !empty($official['first_name']) 
                                    ? getInitials($official['first_name'], $official['last_name'])
                                    : 'V';
                                $photo = $official['photo'] ?? $official['resident_photo'] ?? null;
                            ?>
                            <div class="official-card" data-official-id="<?php echo $official['id']; ?>">
                                <div class="official-photo <?php echo empty($photo) ? 'placeholder' : ''; ?>">
                                    <?php if (!empty($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($initials); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="official-name"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="official-position"><?php echo htmlspecialchars($official['position']); ?></div>
                                <?php if (!empty($official['committee'])): ?>
                                    <div class="official-committee"><?php echo htmlspecialchars($official['committee']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Bottom Level (SK Chairman, Secretary, Treasurer) -->
                        <?php if (!empty($officialsByLevel[3])): ?>
                        <div class="hierarchy-level bottom">
                            <?php foreach ($officialsByLevel[3] as $official): 
                                $fullName = !empty($official['first_name']) 
                                    ? formatFullName($official['first_name'], $official['middle_name'], $official['last_name'], $official['suffix'])
                                    : 'Vacant';
                                $initials = !empty($official['first_name']) 
                                    ? getInitials($official['first_name'], $official['last_name'])
                                    : 'V';
                                $photo = $official['photo'] ?? $official['resident_photo'] ?? null;
                            ?>
                            <div class="official-card" data-official-id="<?php echo $official['id']; ?>">
                                <div class="official-photo <?php echo empty($photo) ? 'placeholder' : ''; ?>">
                                    <?php if (!empty($photo)): ?>
                                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($initials); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="official-name"><?php echo htmlspecialchars($fullName); ?></div>
                                <div class="official-position"><?php echo htmlspecialchars($official['position']); ?></div>
                                <?php if (!empty($official['committee'])): ?>
                                    <div class="official-committee"><?php echo htmlspecialchars($official['committee']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

                <!-- Filter Tabs -->
                <div class="officials-filter-tabs">
                    <button class="officials-tab active" data-filter="all" onclick="filterByStatus('all', this)">All</button>
                    <button class="officials-tab" data-filter="Active" onclick="filterByStatus('Active', this)">Active</button>
                    <button class="officials-tab" data-filter="Inactive" onclick="filterByStatus('Inactive', this)">Inactive</button>
                    <button class="officials-tab" data-filter="Completed" onclick="filterByStatus('Completed', this)">Completed</button>
                </div>

                <!-- Search + Calendar + Refresh Row -->
                <div class="officials-search-row">
                    <div class="officials-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="officialsSearch" placeholder="Search officials name" oninput="searchOfficials(this.value)">
                        <button class="search-clear-btn" id="searchClearBtn" onclick="clearSearch()" style="display:none;" title="Clear">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Calendar Button + Term Period Dropdown -->
                    <div class="term-period-dropdown-wrap" id="termPeriodDropdownWrap">
                        <button class="btn-calendar" id="calendarBtn" onclick="toggleTermPeriodDropdown()" title="Filter by Term Period">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                        <div class="term-period-dropdown-panel" id="termPeriodDropdownPanel" style="display:none;">
                            <div class="term-period-dropdown-label">
                                <i class="fas fa-calendar-alt"></i> Term Period
                            </div>
                            <?php if (empty($captainTerms)): ?>
                                <div class="term-period-dropdown-empty">No term periods found</div>
                            <?php else: ?>
                                <select class="term-period-select" id="termPeriodSelect" onchange="filterByTermPeriod(this.value)">
                                    <option value="" <?php echo $viewAll ? 'selected' : ''; ?>>All Terms</option>
                                    <?php foreach ($captainTerms as $term): ?>
                                        <?php
                                            $tStart = $term['term_start'];
                                            $tEnd   = $term['term_end'];
                                            $label  = date('Y', strtotime($tStart)) . ' – ' . date('Y', strtotime($tEnd));
                                            $val    = $tStart . '|' . $tEnd;
                                            $sel    = (!$viewAll && $selectedTermStart === $tStart) ? 'selected' : '';
                                        ?>
                                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $sel; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button class="btn-refresh" onclick="refreshOfficials()" title="Refresh">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <!-- Table -->
                <div class="table-container">
                    <table class="data-table officials-table" id="officialsTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Official Name</th>
                                
                                <th>Committee</th>
                                <th>Term Period</th>
                                <th>Status</th>
                                <th>Type</th>
                                <th>Contact</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="officialsTableBody">
                            <?php foreach ($officials as $official):
                                    // Resolve display name
                                    if (!empty($official['fullname'])) {
                                        $fullName = $official['fullname'];
                                    } elseif (!empty($official['first_name'])) {
                                        $fullName = formatFullName($official['first_name'], $official['middle_name'], $official['last_name'], $official['suffix']);
                                    } else {
                                        $fullName = 'Vacant';
                                    }

                                    // Initials
                                    $initials = !empty($official['first_name'])
                                        ? getInitials($official['first_name'], $official['last_name'])
                                        : strtoupper(substr($fullName, 0, 2));

                                    // Photo
                                    $photo = $official['photo'] ?? $official['resident_photo'] ?? null;

                                    // Term period
                                    $termPeriod = date('M d, Y', strtotime($official['term_start']))
                                                . ' – '
                                                . date('M d, Y', strtotime($official['term_end']));

                                    // Badge classes
                                    $statusBadge = 'badge-' . strtolower($official['status']);
                                    $typeBadge   = 'badge-' . strtolower($official['appointment_type']);

                                    // Search data attribute
                                    $searchData = strtolower($fullName . ' ' . $official['position'] . ' ' . ($official['committee'] ?? ''));
                                ?>
                                <tr data-status="<?php echo htmlspecialchars($official['status']); ?>"
                                    data-search="<?php echo htmlspecialchars($searchData); ?>">
                                    <td>
                                        <div class="official-name-cell">
                                            <div class="official-avatar">
                                                <?php if (!empty($photo)): ?>
                                                    <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($fullName); ?>">
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($initials); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="official-info">
                                                <span class="official-info-name"><?php echo htmlspecialchars($fullName); ?></span>
                                                <span class="official-info-position"><?php echo htmlspecialchars($official['position']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                  
                                    <td><?php echo htmlspecialchars($official['committee'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($termPeriod); ?></td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($statusBadge); ?>">
                                            <?php echo htmlspecialchars($official['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo htmlspecialchars($typeBadge); ?>">
                                            <?php echo htmlspecialchars($official['appointment_type']); ?>
                                        </span>
                                    </td>
                                    <td>+63 <?php echo htmlspecialchars($official['contact_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn-action" data-official-id="<?= $official['id'] ?>">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>Showing <strong>1-10</strong> of <strong><?php echo count($officials); ?></strong></span>
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

        </div><!-- /dashboard-content -->
    </main>

    <!-- ============================================
         Create Official Modal
         ============================================ -->
    <div class="modal fade" id="createOfficialModal" tabindex="-1" aria-labelledby="createOfficialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createOfficialModalLabel">
                        <i class="fas fa-user-plus"></i> Create Brgy Official
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createOfficialForm">

                        <!-- Resident Selector -->
                        <div class="resident-selector-section mb-3">
                            <label class="form-label">Resident</label>
                            <div class="resident-selector-box" id="residentSelectorBox">
                                <!-- Before selection -->
                                <div id="residentPlaceholder" class="resident-placeholder">
                                    <div class="resident-placeholder-icon">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="resident-placeholder-text">No resident selected</div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openResidentPicker()">
                                        <i class="fas fa-search"></i> SELECT RESIDENT
                                    </button>
                                </div>
                                <!-- After selection (hidden initially) -->
                                <div id="residentSelected" class="resident-selected" style="display:none;">
                                    <div class="resident-selected-photo" id="residentSelectedPhoto">
                                        <!-- Photo or initials injected by JS -->
                                    </div>
                                    <div class="resident-selected-info">
                                        <div class="resident-selected-name" id="residentSelectedName">—</div>
                                        <div class="resident-selected-contact" id="residentSelectedContact">—</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelectedResident()">
                                        <i class="fas fa-times"></i> Change
                                    </button>
                                </div>
                            </div>
                            <!-- Hidden fields -->
                            <input type="hidden" id="selectedResidentId"      name="resident_id">
                            <input type="hidden" id="selectedResidentFullname" name="fullname">
                            <input type="hidden" id="selectedResidentContact"  name="contact_number">
                            <input type="hidden" id="selectedResidentPhoto"    name="resident_photo_path">
                        </div>

                        <!-- Chairmanship -->
                        <div class="mb-3">
                            <label for="chairmanship" class="form-label">Chairmanship</label>
                            <select class="form-select" id="chairmanship" name="chairmanship">
                                <option value="">Select Official Chairmanship</option>
                                <option value="Culture and Education">Culture and Education</option>
                                <option value="Clean and Green">Clean and Green</option>
                                <option value="Health & Sanitation">Health & Sanitation</option>
                                <option value="Peace In Order">Peace In Order</option>
                                <option value="Social Services">Social Services</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Anti-Red Tape">Anti-Red Tape</option>
                                <option value="Educational and Sports">Educational and Sports</option>
                            </select>
                        </div>

                        <!-- Position -->
                        <div class="mb-3">
                            <label for="position" class="form-label">Position</label>
                            <select class="form-select" id="position" name="position" required>
                                <option value="">Select Official Position</option>
                                <option value="Barangay Captain">Barangay Captain</option>
                                <option value="Kagawad">Kagawad</option>
                                <option value="SK Chairman">SK Chairman</option>
                                <option value="Barangay Secretary">Barangay Secretary</option>
                                <option value="Barangay Treasurer">Barangay Treasurer</option>
                                <option value="Barangay Administator">Barangay Administator</option>
                                <option value="Bookkeeper">Bookkeeper</option>
                            </select>
                        </div>

                        <!-- Term Start -->
                        <div class="mb-3">
                            <label for="termStart" class="form-label">Term Start</label>
                            <input type="date" class="form-control" id="termStart" name="term_start" required>
                        </div>

                        <!-- Term End -->
                        <div class="mb-3">
                            <label for="termEnd" class="form-label">Term End</label>
                            <input type="date" class="form-control" id="termEnd" name="term_end" required>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <small class="text-muted">Status is automatically determined based on term dates</small>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" id="createOfficialSubmitBtn">
                        <i class="fas fa-check"></i> Create
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Resident Picker Modal
         ============================================ -->
    <div class="modal fade" id="residentPickerModal" tabindex="-1" aria-labelledby="residentPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="residentPickerModalLabel">
                        <i class="fas fa-users"></i> Select Resident
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Search -->
                    <div class="picker-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="pickerSearchInput" placeholder="Search by name or contact..." oninput="searchResidentsForPicker(this.value)">
                    </div>
                    <!-- Results -->
                    <div class="picker-results" id="residentPickerResults">
                        <div class="picker-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading residents...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         View Official Modal
         ============================================ -->
    <div class="modal fade" id="viewOfficialModal" tabindex="-1" aria-labelledby="viewOfficialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content view-official-modal-content">
                <div class="view-official-modal-header">
                    <h5 class="view-official-modal-title" id="viewOfficialModalLabel"><i class="fas fa-eye"></i> VIEW BARANGAY OFFICIAL</h5>
                    <button type="button" class="view-official-close-btn" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="view-official-modal-body">
                    <div id="viewOfficialLoading" class="view-official-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading official details...</span>
                    </div>
                    <div id="viewOfficialContent" style="display:none;">
                        <div class="view-official-profile-card">
                            <div class="view-official-photo-wrap">
                                <div class="view-official-photo-box" id="viewOfficialPhotoBox"></div>
                            </div>
                            <div class="view-official-details">
                                <div class="view-official-name"     id="viewOfficialName">—</div>
                                <div class="view-official-position" id="viewOfficialPosition">—</div>
                                <div class="view-official-committee-wrap">
                                    <span class="view-official-committee-badge" id="viewOfficialCommittee">—</span>
                                </div>
                                <div class="view-official-contact"  id="viewOfficialContact">—</div>
                            </div>
                        </div>
                        <div class="view-official-history-card">
                            <div class="view-official-history-title">HISTORY OF RUNNING IN BRGY</div>
                            <div class="view-official-history-table-wrap">
                                <table class="view-official-history-table">
                                    <thead>
                                        <tr>
                                            <th>Position</th>
                                            <th>Committee</th>
                                            <th>Term Period</th>
                                            <th>Status</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody id="viewOfficialHistoryBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="view-official-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Edit Official Modal
         ============================================ -->
    <div class="modal fade" id="editOfficialModal" tabindex="-1" aria-labelledby="editOfficialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOfficialModalLabel">
                        <i class="fas fa-user-edit"></i> Edit Brgy Official
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editOfficialForm">
                        <!-- Hidden official ID -->
                        <input type="hidden" id="editOfficialId" name="official_id">

                        <!-- Resident Selector -->
                        <div class="resident-selector-section mb-3">
                            <label class="form-label">Resident</label>
                            <div class="resident-selector-box" id="editResidentSelectorBox">
                                <!-- Placeholder (no resident) -->
                                <div id="editResidentPlaceholder" class="resident-placeholder" style="display:none;">
                                    <div class="resident-placeholder-icon">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="resident-placeholder-text">No resident selected</div>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openEditResidentPicker()">
                                        <i class="fas fa-search"></i> SELECT RESIDENT
                                    </button>
                                </div>
                                <!-- Selected resident -->
                                <div id="editResidentSelected" class="resident-selected" style="display:none;">
                                    <div class="resident-selected-photo" id="editResidentSelectedPhoto"></div>
                                    <div class="resident-selected-info">
                                        <div class="resident-selected-name"    id="editResidentSelectedName">—</div>
                                        <div class="resident-selected-contact" id="editResidentSelectedContact">—</div>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearEditSelectedResident()">
                                        <i class="fas fa-times"></i> Change
                                    </button>
                                </div>
                            </div>
                            <!-- Hidden fields -->
                            <input type="hidden" id="editSelectedResidentId"      name="resident_id">
                            <input type="hidden" id="editSelectedResidentFullname" name="fullname">
                            <input type="hidden" id="editSelectedResidentContact"  name="contact_number">
                            <input type="hidden" id="editSelectedResidentPhoto"    name="resident_photo_path">
                        </div>

                        <!-- Chairmanship -->
                        <div class="mb-3">
                            <label for="editChairmanship" class="form-label">Chairmanship</label>
                            <select class="form-select" id="editChairmanship" name="chairmanship">
                                <option value="">Select Official Chairmanship</option>
                                <option value="Culture and Education">Culture and Education</option>
                                <option value="Clean and Green">Clean and Green</option>
                                <option value="Health & Sanitation">Health & Sanitation</option>
                                <option value="Peace In Order">Peace In Order</option>
                                <option value="Social Services">Social Services</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Anti-Red Tape">Anti-Red Tape</option>
                                <option value="Educational and Sports">Educational and Sports</option>
                            </select>
                        </div>

                        <!-- Position -->
                        <div class="mb-3">
                            <label for="editPosition" class="form-label">Position</label>
                            <select class="form-select" id="editPosition" name="position" required>
                                 <option value="">Select Official Position</option>
                                <option value="Barangay Captain">Barangay Captain</option>
                                <option value="Kagawad">Kagawad</option>
                                <option value="SK Chairman">SK Chairman</option>
                                <option value="Barangay Secretary">Barangay Secretary</option>
                                <option value="Barangay Treasurer">Barangay Treasurer</option>
                                <option value="Barangay Administator">Barangay Administator</option>
                                <option value="Bookkeeper">Bookkeeper</option>
                            </select>
                        </div>

                        <!-- Term Start -->
                        <div class="mb-3">
                            <label for="editTermStart" class="form-label">Term Start</label>
                            <input type="date" class="form-control" id="editTermStart" name="term_start" required>
                        </div>

                        <!-- Term End -->
                        <div class="mb-3">
                            <label for="editTermEnd" class="form-label">Term End</label>
                            <input type="date" class="form-control" id="editTermEnd" name="term_end" required>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <small class="text-muted">Status is automatically determined based on term dates</small>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="button" class="btn btn-primary" id="editOfficialSubmitBtn">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Resident Picker Modal -->
    <div class="modal fade" id="editResidentPickerModal" tabindex="-1" aria-labelledby="editResidentPickerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editResidentPickerModalLabel">
                        <i class="fas fa-users"></i> Select Resident
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="picker-search-wrap">
                        <i class="fas fa-search"></i>
                        <input type="text" id="editPickerSearchInput" placeholder="Search by name or contact..." oninput="searchResidentsForEditPicker(this.value)">
                    </div>
                    <div class="picker-results" id="editResidentPickerResults">
                        <div class="picker-loading">
                            <i class="fas fa-spinner fa-spin"></i> Loading residents...
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  margin: 10% auto; max-width: 500px; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="archiveModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Archive Official</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure you want to archive this official? This action will move the record to the archives.</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm.
                </div>
                
                <form id="archiveForm">
                    <input type="hidden" id="archiveOfficialId" name="id">
                    
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

    <!-- Bootstrap JS -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        officials_view:   <?php echo hasPermission('perm_officials_view')   ? 'true' : 'false'; ?>,
        officials_create: <?php echo hasPermission('perm_officials_create') ? 'true' : 'false'; ?>,
        officials_edit:   <?php echo hasPermission('perm_officials_edit')   ? 'true' : 'false'; ?>,
        officials_delete: <?php echo hasPermission('perm_officials_delete') ? 'true' : 'false'; ?>,
        officials_status: <?php echo hasPermission('perm_officials_status') ? 'true' : 'false'; ?>,
        officials_archive:<?php echo hasPermission('perm_officials_archive') ? 'true' : 'false'; ?>,
        officials_print:  <?php echo hasPermission('perm_officials_print')   ? 'true' : 'false'; ?>
    };
    </script>

    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/officials.js"></script>
</body>
</html>
