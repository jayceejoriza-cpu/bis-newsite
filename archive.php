<?php
require_once 'config.php';
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
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .archive-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-resident { background-color: #dbeafe; color: #1e40af; }
        .badge-official { background-color: #d1fae5; color: #065f46; }
        .badge-blotter { background-color: #fee2e2; color: #991b1b; }
        .badge-permit { background-color: #e0f2fe; color: #075985; }
        .badge-default { background-color: #f3f4f6; color: #374151; }
        
        .record-data {
            background: var(--bg-secondary, #f8f9fa);
            padding: 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: 1px solid var(--border-color, #e5e7eb);
        }
        
        .record-data-item {
            margin-bottom: 8px;
            display: flex;
        }
        
        .record-data-label {
            font-weight: 600;
            color: var(--text-secondary, #4b5563);
            width: 120px;
            flex-shrink: 0;
        }
        
        .record-data-value {
            color: var(--text-primary, #111827);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background-color: var(--bg-surface, #ffffff);
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 90%;
            animation: slideIn 0.3s;
            overflow: hidden;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            background-color: var(--bg-secondary, #f9fafb);
            border-top: 1px solid var(--border-color, #e5e7eb);
            text-align: right;
        }
        
        .btn-close-modal {
            background: none;
            border: none;
            font-size: 1.25rem;
            cursor: pointer;
            color: var(--text-secondary, #6b7280);
        }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
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
                <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color);">
                    <h3 style="margin: 0; font-size: 1.1rem;">Archived Records</h3>
                    <div class="table-tools">
                        <select id="filterType" class="form-control" style="width: 200px; padding: 0.5rem;">
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
                                    <div style="display: flex; gap: 5px;">
                                        <?php if(isset($_SESSION['username'])): ?>
                                        <a href="restore_archive.php?id=<?php echo $archive['id']; ?>" onclick="return confirm('Restore this record?')" class="btn btn-sm btn-success" title="Restore">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if(isset($_SESSION['username']) && (isset($_SESSION['role']) && $_SESSION['role'] == 'Administrator')): ?>
                                        <a href="delete_archive.php?id=<?php echo $archive['id']; ?>" onclick="return confirm('Permanently delete this archive? This cannot be undone!')" class="btn btn-sm btn-danger" title="Permanent Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">No archived records found.</td>
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
    
    <script src="js/script.js"></script>
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
                html += createRow('Name', data.name || 'N/A');
                html += createRow('Position', data.position || 'N/A');
                html += createRow('Chairmanship', data.chairmanship || 'N/A');
                html += createRow('Term Start', data.termstart || 'N/A');
                html += createRow('Term End', data.termend || 'N/A');
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