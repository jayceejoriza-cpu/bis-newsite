<?php
require_once 'config.php';

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
                bo.email,
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
                bo.email,
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
} catch (PDOException $e) {
    error_log("Error fetching officials: " . $e->getMessage());
    $officials = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/officials.css">
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
                <button class="btn btn-primary" id="createOfficialBtn">
                    <i class="fas fa-plus"></i> Create Brgy Official
                </button>
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
                        <input type="text" id="officialsSearch" placeholder="Search" oninput="searchOfficials(this.value)">
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
                    <table class="data-table officials-table" id="officialsTable">
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
                            <?php if (empty($officials)): ?>
                                <tr id="officialsEmptyRow">
                                    <td colspan="8" style="text-align:center; padding:40px;">
                                        <i class="fas fa-users" style="font-size:48px; color:#d1d5db; display:block; margin-bottom:16px;"></i>
                                        <p style="color:#6b7280; font-size:16px; margin:0;">No officials found for this term period</p>
                                        <p style="color:#9ca3af; font-size:14px; margin-top:8px;">Try selecting a different term or create a new official</p>
                                    </td>
                                </tr>
                            <?php else: ?>
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
                                        <div class="action-buttons">
                                            <button class="btn-view" data-official-id="<?php echo $official['id']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn-edit" data-official-id="<?php echo $official['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="btn-delete" data-official-id="<?php echo $official['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- Empty state row (hidden by default, shown when filters yield no results) -->
                                <tr id="officialsEmptyRow" style="display:none;">
                                    <td colspan="8" style="text-align:center; padding:40px;">
                                        <i class="fas fa-search" style="font-size:40px; color:#d1d5db; display:block; margin-bottom:12px;"></i>
                                        <p style="color:#6b7280; font-size:15px; margin:0;">No officials match your filter</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /table-section -->

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
                                    <button type="button" class="btn btn-primary btn-sm" id="selectResidentBtn" onclick="openResidentPicker()">
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
                                <option value="Executive">Executive</option>
                                <option value="Health and Sanitation">Health and Sanitation</option>
                                <option value="Peace and Order">Peace and Order</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Education">Education</option>
                                <option value="Youth Development">Youth Development</option>
                                <option value="Administration">Administration</option>
                                <option value="Finance">Finance</option>
                                <option value="Agriculture">Agriculture</option>
                                <option value="Environment">Environment</option>
                                <option value="Social Services">Social Services</option>
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
                                <option value="Executive">Executive</option>
                                <option value="Health and Sanitation">Health and Sanitation</option>
                                <option value="Peace and Order">Peace and Order</option>
                                <option value="Infrastructure">Infrastructure</option>
                                <option value="Education">Education</option>
                                <option value="Youth Development">Youth Development</option>
                                <option value="Administration">Administration</option>
                                <option value="Finance">Finance</option>
                                <option value="Agriculture">Agriculture</option>
                                <option value="Environment">Environment</option>
                                <option value="Social Services">Social Services</option>
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

    <!-- Bootstrap JS -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/officials.js"></script>
</body>
</html>
