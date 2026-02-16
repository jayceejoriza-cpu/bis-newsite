<?php
// Include configuration
require_once 'config.php';

// Page title
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
 * Get initials from name
 */
function getInitials($firstName, $lastName) {
    $first = !empty($firstName) ? strtoupper(substr($firstName, 0, 1)) : '';
    $last = !empty($lastName) ? strtoupper(substr($lastName, 0, 1)) : '';
    return $first . $last;
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
// Fetch Officials Data
// ============================================
$officials = [];
$totalOfficials = 0;
$currentYear = date('Y');

try {
    // Get total count of active officials
    $countStmt = $pdo->query("SELECT COUNT(*) as total FROM barangay_officials WHERE status = 'Active'");
    $totalOfficials = $countStmt->fetch()['total'];
    
    // Fetch officials data with resident information
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
            r.first_name,
            r.middle_name,
            r.last_name,
            r.suffix,
            r.photo as resident_photo
        FROM barangay_officials bo
        LEFT JOIN residents r ON bo.resident_id = r.id
        WHERE bo.status = 'Active'
        ORDER BY bo.hierarchy_level ASC, bo.position ASC
    ");
    
    $stmt->execute();
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
    <link rel="stylesheet" href="css/officials.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Officials Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View and manage barangay officials</p>
                </div>
                <button class="btn btn-primary" id="createOfficialBtn">
                    <i class="fas fa-plus"></i>
                    Create Brgy Official
                </button>
            </div>
            
            <!-- Organizational Chart Section -->
            <div class="org-chart-section">
                <div class="org-chart-header">
                    <h2 class="org-chart-title">
                        <i class="fas fa-sitemap"></i>
                        PRESENT OFFICIALS
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
            
            <!-- Table Section -->
            <div class="table-section">
                <div class="table-section-header">
                    <h2 class="table-section-title">TABLE FOR LIST OF THE BARANGAY OFFICIALS</h2>
                    <button class="term-filter-btn" id="termFilterBtn">
                        <i class="fas fa-calendar-alt"></i>
                        PRESENT - <?php echo $currentYear; ?> ^
                    </button>
                </div>
                
                <div class="table-container">
                    <table class="data-table officials-table" id="officialsTable">
                        <thead>
                            <tr>
                                <th>Official Name</th>
                                <th>Position</th>
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
                                <!-- Empty state -->
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 40px;">
                                        <i class="fas fa-users" style="font-size: 48px; color: #d1d5db; margin-bottom: 16px;"></i>
                                        <p style="color: #6b7280; font-size: 16px; margin: 0;">No officials found</p>
                                        <p style="color: #9ca3af; font-size: 14px; margin-top: 8px;">Start by adding a new official</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($officials as $official): 
                                    $fullName = !empty($official['first_name']) 
                                        ? formatFullName($official['first_name'], $official['middle_name'], $official['last_name'], $official['suffix'])
                                        : 'Vacant';
                                    $initials = !empty($official['first_name']) 
                                        ? getInitials($official['first_name'], $official['last_name'])
                                        : 'V';
                                    $photo = $official['photo'] ?? $official['resident_photo'] ?? null;
                                    $termPeriod = date('M d, Y', strtotime($official['term_start'])) . ' - ' . date('M d, Y', strtotime($official['term_end']));
                                    
                                    // Badge classes
                                    $statusBadge = 'badge-' . strtolower($official['status']);
                                    $typeBadge = 'badge-' . strtolower($official['appointment_type']);
                                ?>
                                <tr>
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
                                    <td><?php echo htmlspecialchars($official['position']); ?></td>
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
                                    <td><?php echo htmlspecialchars($official['contact_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <div class="action-buttons">
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
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>
    <script src="js/officials.js"></script>
</body>
</html>
