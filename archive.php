<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

$pageTitle = 'Archives';

// Initialize variables
$archives = [];
$counts = [
    'resident' => 0, 
    'official' => 0, 
    'blotter' => 0,
    'total' => 0
];

// Fetch data from archive table
if (isset($conn)) {
    // Check if table exists to avoid errors
    $checkTable = $conn->query("SHOW TABLES LIKE 'archive'");
    
    if ($checkTable && $checkTable->num_rows > 0) {
        $query = "SELECT * FROM archive ORDER BY deleted_at DESC";
        $result = $conn->query($query);

        if ($result) {
            while($row = $result->fetch_assoc()){
                $archives[] = $row;
                $counts['total']++;
                
                $type = $row['archive_type'];
                if (isset($counts[$type])) {
                    $counts[$type]++;
                } else {
                    // Initialize if not in default list
                    $counts[$type] = 1;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">Manage deleted records and restoration</p>
                </div>
            </div>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['success'] == 'danger' ? 'danger' : 'success'; ?>">
                    <i class="fas fa-<?php echo $_SESSION['success'] == 'danger' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($counts['resident']); ?></h3>
                        <p class="stat-label">Archived Residents</p>
                    </div>
                    <div class="stat-icon blue">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($counts['official']); ?></h3>
                        <p class="stat-label">Archived Officials</p>
                    </div>
                    <div class="stat-icon green">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($counts['blotter']); ?></h3>
                        <p class="stat-label">Archived Blotters</p>
                    </div>
                    <div class="stat-icon orange">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-content">
                        <h3 class="stat-value"><?php echo number_format($counts['total']); ?></h3>
                        <p class="stat-label">Total Archives</p>
                    </div>
                    <div class="stat-icon purple">
                        <i class="fas fa-archive"></i>
                    </div>
                </div>
            </div>
            
            <!-- Archives Table -->
            <div class="table-container">
                <div class="table-header">
                    <h3>Archived Records</h3>
                    <div class="table-tools">
                        <select id="filterType" class="form-control" style="width: 200px;">
                            <option value="">All Types</option>
                            <option value="resident">Residents</option>
                            <option value="official">Officials</option>
                            <option value="blotter">Blotters</option>
                            <option value="permit">Business Permits</option>
                            <option value="user">Users</option>
                        </select>
                    </div>
                </div>
                
                <table class="data-table" id="archivesTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Record ID</th>
                            <th>Details</th>
                            <th>Deleted By</th>
                            <th>Deleted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($archives)): ?>
                            <?php foreach($archives as $archive): 
                                $badgeClass = 'badge-default';
                                if($archive['archive_type'] == 'resident') $badgeClass = 'badge-resident';
                                elseif($archive['archive_type'] == 'official') $badgeClass = 'badge-official';
                                elseif($archive['archive_type'] == 'blotter') $badgeClass = 'badge-blotter';
                                elseif($archive['archive_type'] == 'permit') $badgeClass = 'badge-permit';
                            ?>
                            <tr data-type="<?php echo htmlspecialchars($archive['archive_type']); ?>">
                                <td>
                                    <span class="archive-type-badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($archive['archive_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($archive['record_id']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewDetails(<?php echo htmlspecialchars($archive['record_data'], ENT_QUOTES, 'UTF-8'); ?>, '<?php echo $archive['archive_type']; ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                                <td><?php echo htmlspecialchars(ucwords($archive['deleted_by'])); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($archive['deleted_at']))); ?></td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <?php if(isset($_SESSION['username'])): ?>
                                        <a href="model/restore_archive.php?id=<?php echo htmlspecialchars($archive['id']); ?>" onclick="return confirm('Restore this record?')" class="btn btn-sm btn-success" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($_SESSION['username']) && (isset($_SESSION['role']) && $_SESSION['role'] == 'Administrator')): ?>
                                        <a href="model/delete_archive.php?id=<?php echo $archive['id']; ?>" onclick="return confirm('Permanently delete this archive? This cannot be undone!')" class="btn btn-sm btn-danger" title="Permanent Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px; color: var(--text-secondary);">No archived records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive Details</h5>
                <button type="button" class="btn-close-modal" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/script.js"></script>
    <script>
        // Filter functionality
        document.getElementById('filterType').addEventListener('change', function() {
            const selectedType = this.value.toLowerCase();
            const rows = document.querySelectorAll('#archivesTable tbody tr');
            
            rows.forEach(row => {
                const type = row.getAttribute('data-type');
                if (selectedType === '' || type === selectedType) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Modal functionality
        const modal = document.getElementById('detailsModal');
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        function viewDetails(data, type) {
            let html = '<div class="record-data">';
            
            // Format based on type
            if (type === 'resident') {
                html += createRow('Name', (data.first_name || '') + ' ' + (data.middle_name || '') + ' ' + (data.last_name || ''));
                html += createRow('Age', data.age || 'N/A');
                html += createRow('Gender', data.gender || data.sex || 'N/A');
                html += createRow('Address', data.address || data.current_address || 'N/A');
                html += createRow('Contact', data.number || data.mobile_number || 'N/A');
            } else if (type === 'official') {
                // Build full name from archived data
                let fullName = '';
                if (data.first_name) {
                    fullName = data.first_name;
                    if (data.middle_name) fullName += ' ' + data.middle_name;
                    if (data.last_name) fullName += ' ' + data.last_name;
                    if (data.suffix) fullName += ' ' + data.suffix;
                } else {
                    fullName = data.name || 'N/A';
                }
                
                html += createRow('Name', fullName);
                html += createRow('Position', data.position || 'N/A');
                html += createRow('Committee', data.committee || data.chairmanship || 'N/A');
                html += createRow('Hierarchy Level', data.hierarchy_level || 'N/A');
                html += createRow('Term Start', data.term_start || data.termstart || 'N/A');
                html += createRow('Term End', data.term_end || data.termend || 'N/A');
                html += createRow('Status', data.status || 'N/A');
                html += createRow('Appointment Type', data.appointment_type || 'N/A');
                html += createRow('Contact Number', data.contact_number || 'N/A');
                html += createRow('Email', data.email || 'N/A');
                html += createRow('Resident ID', data.resident_id || 'N/A');
            } else if (type === 'blotter') {
                html += createRow('Complainant', data.complainant || 'N/A');
                html += createRow('Respondent', data.respondent || 'N/A');
                html += createRow('Complaint', data.complaint || 'N/A');
                html += createRow('Status', data.status || 'N/A');
                html += createRow('Date', data.date || 'N/A');
            } else if (type === 'permit') {
                html += createRow('Business Name', data.business_name || 'N/A');
                html += createRow('Owner', data.owner_name || 'N/A');
                html += createRow('Nature', data.nature || 'N/A');
                html += createRow('Address', data.address || 'N/A');
            } else if (type === 'user') {
                html += createRow('Username', data.username || 'N/A');
                html += createRow('Role', data.type || data.role || 'N/A');
                html += createRow('Created', data.created_at || 'N/A');
            } else {
                // Generic display for other types
                for (var key in data) {
                    if (data.hasOwnProperty(key) && key !== 'avatar' && key !== 'picture' && key !== 'img' && key !== 'photo') {
                        html += createRow(key.replace(/_/g, ' ').toUpperCase(), data[key] || 'N/A');
                    }
                }
            }
            
            html += '</div>';
            
            document.getElementById('detailsContent').innerHTML = html;
            modal.classList.add('active');
        }
        
        function createRow(label, value) {
            return `
                <div class="record-data-item">
                    <span class="record-data-label">${label}:</span>
                    <span class="record-data-value">${value}</span>
                </div>
            `;
        }
    </script>
</body>
</html>