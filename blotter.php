<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Page title
$pageTitle = 'Blotter Records';

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

function getInitials($name) {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getAvatarColor($index) {
    $colors = ['blue', 'pink', 'teal', 'yellow', 'green', 'orange', 'lime', 'indigo', 'cyan', 'purple'];
    return 'avatar-' . $colors[$index % count($colors)];
}

function formatDateShort($date) {
    if (empty($date)) return 'N/A';
    return date('F j, Y', strtotime($date));
}

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
// Fetch Blotter Records Data
// ============================================
$blotterRecords = [];
$totalRecords = 0;

try {
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM blotter_records");
    $totalRecords = $countStmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT 
            br.id, br.record_number, br.incident_type, br.incident_date,
            br.date_reported, br.status,
            COUNT(DISTINCT bc.id) AS complainant_count,
            COUNT(DISTINCT brd.id) AS respondent_count,
            GROUP_CONCAT(DISTINCT bc.name ORDER BY bc.id SEPARATOR '|||') AS complainant_names,
            GROUP_CONCAT(DISTINCT brd.name ORDER BY brd.id SEPARATOR '|||') AS respondent_names
        FROM blotter_records br
        LEFT JOIN blotter_complainants bc ON br.id = bc.blotter_id
        LEFT JOIN blotter_respondents brd ON br.id = brd.blotter_id
        GROUP BY br.id
        ORDER BY br.date_reported DESC
    ");
    
    $stmt->execute();
    $blotterRecords = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching blotter records: " . $e->getMessage());
}

// Fetch residents for dropdown
$residents = [];
try {
    $residentStmt = $pdo->query("SELECT id, first_name, middle_name, last_name, suffix FROM residents WHERE activity_status = 'Active' ORDER BY last_name, first_name");
    $residents = $residentStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching residents: " . $e->getMessage());
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
    <link rel="stylesheet" href="assets/css/blotter.css">
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        /* View Modal Styles */
        .party-view-item {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
            border-left: 4px solid #e9ecef;
        }
        .party-view-item:hover {
            border-color: #dee2e6;
            border-left-color: var(--primary-color);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transform: translateY(-2px);
        }
        .action-view-item {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .view-info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage official barangay blotter entries, including complaints, incidents, and case statuses.</p>
                </div>
                <button class="btn btn-primary" id="createRecordBtn">
                    <i class="fas fa-plus"></i>
                    Create Record
                </button>
            </div>
            
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="pending">Pending</button>
                <button class="tab-btn" data-filter="resolved">Resolve</button>
                <button class="tab-btn" data-filter="under-investigation">Under Investigation</button>
                <button class="tab-btn" data-filter="dismissed">Dismissed</button>
            </div>
            
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button class="btn btn-icon" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <div class="table-container">
                <table class="data-table blotter-table" id="blotterTable">
                    <thead>
                        <tr>
                            <th>RECORD #</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <th>Complainants</th>
                            <th>Respondents</th>
                            <th>Incident Type</th>
                            <th>Incident Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="blotterTableBody">
                        <?php if (empty($blotterRecords)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-file-alt" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                    <p style="color: #6b7280; font-size: 16px; margin: 0;">No blotter records found</p>
                                    <p style="color: #9ca3af; font-size: 14px; margin-top: 8px;">Blotter records will appear here</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blotterRecords as $index => $record): 
                                $recordNumber = htmlspecialchars($record['record_number']);
                                $dateReported = formatDateShort($record['date_reported']);
                                $incidentDate = formatDateShort($record['incident_date']);
                                $status = $record['status'];
                                $incidentType = htmlspecialchars($record['incident_type']);
                                $statusBadge = 'badge-' . strtolower(str_replace(' ', '-', $status));
                                $complainants = !empty($record['complainant_names']) ? explode('|||', $record['complainant_names']) : [];
                                $respondents = !empty($record['respondent_names']) ? explode('|||', $record['respondent_names']) : [];
                                $complainantCount = $record['complainant_count'];
                                $respondentCount = $record['respondent_count'];
                            ?>
                            <tr data-status="<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
                                <td><span class="record-number"><?php echo $recordNumber; ?></span></td>
                                <td><?php echo $dateReported; ?></td>
                                <td><span class="badge <?php echo $statusBadge; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                <td>
                                    <div class="person-group">
                                        <?php 
                                        $displayCount = min(2, count($complainants));
                                        for ($i = 0; $i < $displayCount; $i++): 
                                            $initials = getInitials($complainants[$i]);
                                            $avatarColor = getAvatarColor($i);
                                        ?>
                                            <span class="avatar-sm <?php echo $avatarColor; ?>" title="<?php echo htmlspecialchars($complainants[$i]); ?>">
                                                <?php echo $initials; ?>
                                            </span>
                                        <?php endfor; ?>
                                        <?php if ($complainantCount > 2): ?>
                                            <span class="person-count">+<?php echo ($complainantCount - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="person-group">
                                        <?php 
                                        $displayCount = min(2, count($respondents));
                                        for ($i = 0; $i < $displayCount; $i++): 
                                            $initials = getInitials($respondents[$i]);
                                            $avatarColor = getAvatarColor($i + 5);
                                        ?>
                                            <span class="avatar-sm <?php echo $avatarColor; ?>" title="<?php echo htmlspecialchars($respondents[$i]); ?>">
                                                <?php echo $initials; ?>
                                            </span>
                                        <?php endfor; ?>
                                        <?php if ($respondentCount > 2): ?>
                                            <span class="person-count">+<?php echo ($respondentCount - 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo $incidentType; ?></td>
                                <td><?php echo $incidentDate; ?></td>
                                <td>
                                    <div class="action-menu-container">
                                        <button class="btn-action" data-record-id="<?php echo $record['id']; ?>">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="action-menu" data-record-id="<?php echo $record['id']; ?>">
                                            <button class="action-menu-item" data-action="view">
                                                <i class="fas fa-eye"></i>
                                                <span>View Details</span>
                                            </button>
                                            <button class="action-menu-item" data-action="edit">
                                                <i class="fas fa-edit"></i>
                                                <span>Edit</span>
                                            </button>
                                            <button class="action-menu-item has-submenu" data-action="status">
                                                <i class="fas fa-circle status-dot"></i>
                                                <span>Status</span>
                                                <i class="fas fa-chevron-right submenu-arrow"></i>
                                            </button>
                                            <div class="action-submenu">
                                                <button class="action-menu-item" data-action="status-pending">
                                                    <span>Pending</span>
                                                </button>
                                                <button class="action-menu-item" data-action="status-investigation">
                                                    <span>Under Investigation</span>
                                                </button>
                                                <button class="action-menu-item" data-action="status-resolved">
                                                    <span>Resolved</span>
                                                </button>
                                                <button class="action-menu-item" data-action="status-dismissed">
                                                    <span>Dismissed</span>
                                                </button>
                                            </div>
                                            <button class="action-menu-item" data-action="archive">
                                                <i class="fas fa-archive"></i>
                                                <span>Archive</span>
                                            </button>
                                             <div class="action-menu-divider" style="
                                                height: 1px;
                                                background-color: #e5e7eb;
                                                margin: 8px 0;">
                                                </div>
                                            <button class="action-menu-item danger" data-action="delete">
                                                <i class="fas fa-trash"></i>
                                                <span>Delete Permanently</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong><?php echo number_format($totalRecords); ?></strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Blotter Record Modal -->
    <div class="modal fade" id="createRecordModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Barangay Blotter Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createRecordForm">
                        <div class="step-indicator">
                            <div class="step-item active" data-step="0" id="step-basic-info">
                                <div class="step-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="step-label">Basic Info</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="1" id="step-parties">
                                <div class="step-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="step-label">Parties Involved</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="2" id="step-incident">
                                <div class="step-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="step-label">Incident Details</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="step-item" data-step="3" id="step-actions">
                                <div class="step-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="step-label">Actions & Resolution</div>
                            </div>
                        </div>
                        
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="basic-info">
                                <div class="mt-4">
                                    <div class="row mb-3">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-control" name="status" required>
                                                <option value="Pending">Pending</option>
                                                <option value="Under Investigation">Under Investigation</option>
                                                <option value="Resolved">Resolved</option>
                                                <option value="Dismissed">Dismissed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Incident Date</label>
                                            <input type="datetime-local" class="form-control" id="incidentDate" name="incident_date" required>
                                        </div>
                                    </div>
                                    
                                    <!-- Complainant Section -->
                                    <div class="party-section mb-4">
                                        <div class="party-header">
                                            <h6 class="party-title"><i class="fas fa-user"></i> Complainant</h6>
                                            <button type="button" class="btn btn-sm btn-primary" id="addComplainantBtn"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="complainantsContainer">
                                            <div class="party-entry">
                                                <div class="party-entry-header"><span>Complainant 1</span></div>
                                                <div class="party-entry-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                        <div class="member-name-input-group">
                                                            <input type="text" class="form-control complainant-name-required" name="complainant_name[]" placeholder="Enter full name" required>
                                                            <button type="button" class="btn-resident-search" data-target="complainant" data-index="0">
                                                                RESIDENT
                                                            </button>
                                                            <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                                                <i class="fas fa-redo"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="complainant_resident_id[]" value="">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Address</label>
                                                            <input type="text" class="form-control" name="complainant_address[]">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Mobile Number</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
                                                                <input type="text" class="form-control" name="complainant_contact[]" placeholder="9XX XXX XXXX">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Victims Section -->
                                    <div class="party-section">
                                        <div class="party-header">
                                            <h6 class="party-title"><i class="fas fa-user-injured"></i> Victims</h6>
                                            <button type="button" class="btn btn-sm btn-primary" id="addVictimBtn"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="victimsContainer">
                                            <div class="party-entry">
                                                <div class="party-entry-header"><span>Victim 1</span></div>
                                                <div class="party-entry-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                        <div class="member-name-input-group">
                                                            <input type="text" class="form-control victim-name-required" name="victim_name[]" placeholder="Enter full name" required>
                                                            <button type="button" class="btn-resident-search" data-target="victim" data-index="0">
                                                                RESIDENT
                                                            </button>
                                                            <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                                                <i class="fas fa-redo"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="victim_resident_id[]" value="">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Address</label>
                                                            <input type="text" class="form-control" name="victim_address[]">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Mobile Number</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
                                                                <input type="text" class="form-control" name="victim_contact[]" placeholder="9XX XXX XXXX">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="parties">
                                <div class="mt-4">
                                    <div class="party-section mb-4">
                                        <div class="party-header">
                                            <h6 class="party-title"><i class="fas fa-user-shield"></i> Respondents</h6>
                                            <button type="button" class="btn btn-sm btn-primary" id="addRespondentBtn"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="respondentsContainer">
                                            <div class="party-entry">
                                                <div class="party-entry-header"><span>Respondents 1</span></div>
                                                <div class="party-entry-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                        <div class="member-name-input-group">
                                                            <input type="text" class="form-control respondent-name-required" name="respondent_name[]" placeholder="Enter full name" required>
                                                            <button type="button" class="btn-resident-search" data-target="respondent" data-index="0">
                                                                RESIDENT
                                                            </button>
                                                            <button type="button" class="btn-reset-resident" style="display: none;" title="Reset">
                                                                <i class="fas fa-redo"></i>
                                                            </button>
                                                        </div>
                                                        <input type="hidden" name="respondent_resident_id[]" value="">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Address</label>
                                                            <input type="text" class="form-control" name="respondent_address[]">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Mobile Number</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text"><img src="https://flagcdn.com/w20/ph.png" alt="PH" style="width:20px;"> +63</span>
                                                                <input type="text" class="form-control" name="respondent_contact[]" placeholder="9XX XXX XXXX">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="party-section">
                                        <div class="party-header">
                                            <h6 class="party-title"><i class="fas fa-eye"></i> Witnesses</h6>
                                            <button type="button" class="btn btn-sm btn-primary" id="addWitnessBtn"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="witnessesContainer"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="incident">
                                <div class="mt-4">
                                    <div class="mb-3">
                                        <label class="form-label">Incident Date <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" name="incident_date_details" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Incident Type <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="incident_type" placeholder="Incident type is required." required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Incident Location <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="incident_location" rows="2" placeholder="Incident location is required." required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Incident Details <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="incident_description" rows="6" placeholder="Incident details is required." required></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="tab-pane fade" id="actions">
                                <div class="mt-4">
                                    <div class="party-section mb-4">
                                        <div class="party-header">
                                            <h6 class="party-title">Action Taken</h6>
                                            <button type="button" class="btn btn-sm btn-primary" id="addActionBtn"><i class="fas fa-plus"></i></button>
                                        </div>
                                        <div id="actionsContainer"></div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Resolution</label>
                                        <textarea class="form-control" name="resolution" rows="4" placeholder="Enter final resolution details..."></textarea>
                                        <small class="text-danger">Resolution is required.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <div style="margin-left: auto; display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" id="modalBackBtn" style="display: none;">Back</button>
                        <button type="button" class="btn btn-primary" id="modalNextBtn">Next</button>
                        <button type="button" class="btn btn-primary" id="saveRecordBtn" style="display: none;">Save Record</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- View Blotter Record Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Blotter Record Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="viewRecordForm">
                        <!-- Basic Info -->
                        <h5 class="mb-3 text-primary"><i class="fas fa-info-circle"></i> Basic Information</h5>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <input type="text" class="form-control" id="view_status" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Incident Date</label>
                                <input type="text" class="form-control" id="view_incident_date" readonly>
                            </div>
                        </div>
                        
                        <!-- Parties Involved -->
                        <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-users"></i> Parties Involved</h5>
                        
                        <!-- Complainant Section -->
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-user"></i> Complainant</h6>
                            </div>
                            <div id="viewComplainantsContainer"></div>
                        </div>
                        
                        <!-- Victims Section -->
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-user-injured"></i> Victims</h6>
                            </div>
                            <div id="viewVictimsContainer"></div>
                        </div>
                        
                        <!-- Respondents Section -->
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-user-shield"></i> Respondents</h6>
                            </div>
                            <div id="viewRespondentsContainer"></div>
                        </div>
                        
                        <!-- Witnesses Section -->
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title"><i class="fas fa-eye"></i> Witnesses</h6>
                            </div>
                            <div id="viewWitnessesContainer"></div>
                        </div>
                        
                        <!-- Incident Details -->
                        <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-exclamation-triangle"></i> Incident Details</h5>
                        <div class="mb-3">
                            <label class="form-label">Incident Type</label>
                            <input type="text" class="form-control" id="view_incident_type" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Incident Location</label>
                            <textarea class="form-control" id="view_incident_location" rows="2" readonly></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Incident Details</label>
                            <textarea class="form-control" id="view_incident_description" rows="6" readonly></textarea>
                        </div>
                        
                        <!-- Actions & Resolution -->
                        <h5 class="mb-3 mt-4 text-primary"><i class="fas fa-clipboard-check"></i> Actions & Resolution</h5>
                        <div class="party-section mb-4">
                            <div class="party-header">
                                <h6 class="party-title">Action Taken</h6>
                            </div>
                            <div id="viewActionsContainer"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Resolution</label>
                            <textarea class="form-control" id="view_resolution" rows="4" readonly></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printRecordBtn">
                        <i class="fas fa-print"></i> Print Record
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Resident Modal -->
    <div id="searchResidentModal" class="search-resident-modal">
        <div class="search-resident-modal-content">
            <div class="search-resident-modal-header">
                <h4><i class="fas fa-search"></i> Search Resident</h4>
                <button type="button" class="btn-close-search-modal" onclick="closeSearchResidentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-resident-modal-body">
                <p class="search-subtitle">Add from Resident List</p>
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="residentSearchInput" class="search-input" placeholder="Search Full Name, Barangay ID">
                </div>
                <div class="residents-list" id="residentsListContainer">
                    <div class="loading-residents">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading residents...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'model/edit_blotter.php'; ?>
    
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/blotter.js"></script>
    <script src="assets/js/edit-blotter.js"></script>
</body>
</html>
