<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';
requirePermission('perm_settings_archive');

$pageTitle = 'Archives';

// Initialize variables
$archives = [];
$counts = [
    'resident' => 0, 
    'official' => 0, 
    'blotter' => 0,
    'household' => 0,
    'user' => 0,
    'role' => 0,
    'total' => 0
];
$all_users = [];
$filter_user = isset($_GET['filter_user']) ? trim($_GET['filter_user']) : '';

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
                
                $countType = $type;
                if ($type === 'household_member' || $type === 'household member') {
                    $countType = 'household';
                }
                
                if (isset($counts[$countType])) {
                    $counts[$countType]++;
                } else {
                    // Initialize if not in default list
                    $counts[$countType] = 1;
                }
            }
            
            // Fetch all users for the filter dropdown
            $uRes = $conn->query("SELECT username FROM users ORDER BY username ASC");
            if ($uRes) {
                while($uRow = $uRes->fetch_assoc()) {
                    $all_users[] = $uRow['username'];
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        /* Table Styling (Matched from Activity Logs) */
        .archive-table {
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
        }
        .archive-table th {
            font-size: 13px;
            padding: 15px 20px;
            position: relative;
            border-bottom: 2px solid var(--border-color);
            background-color: var(--bg-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text-primary);
            font-weight: 600;
        }
        .archive-table th:not(:last-child) {
            border-right: 1px solid var(--border-color);
        }
        .archive-table td {
            padding: 14px 20px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            vertical-align: middle;
        }
        .archive-table td:not(:last-child) {
            border-right: 1px solid var(--border-color);
        }
        .archive-table tbody tr:last-child td {
            border-bottom: none;
        }
        .archive-table tbody tr {
            transition: all 0.2s ease;
            background-color: var(--bg-secondary);
        }
        .archive-table tbody tr:hover {
            background-color: var(--bg-primary);
        }
        
        /* Pagination Styling */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            background-color: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }
        .pagination-info {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .pagination-info strong {
            color: var(--text-primary);
            font-weight: 600;
        }
        .pagination {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .page-btn {
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-secondary);
            color: var(--text-secondary);
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .page-btn:hover:not(:disabled) {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--primary-color);
        }
        .page-btn.active {
            background-color: var(--primary-color);
            color: white !important;
            border-color: var(--primary-color);
        }
        .page-btn:disabled {
            opacity: 0.5;
            pointer-events: none;
        }
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
                        <h3 class="stat-value"><?php echo number_format($counts['household']); ?></h3>
                        <p class="stat-label">Archived Households & Members</p>
                    </div>
                    <div class="stat-icon teal">
                        <i class="fas fa-home"></i>
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
            
            <!-- Search and Filter Bar -->
            <div class="search-filter-bar" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                <div class="search-box" style="position: relative; width: 300px; display: flex; align-items: center;">
                    <i class="fas fa-search" style="position: absolute; left: 12px; color: var(--text-secondary);"></i>
                    <input type="text" placeholder="Search by name..." id="searchInput" style="width: 100%; height: 40px; padding: 0 35px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary); font-size: 13px;">
                    <button class="btn-clear" id="clearSearchBtn" style="display:none; position: absolute; right: 10px; background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Button and Panel Wrapper -->
                <div style="position: relative; display: flex; align-items: center;">
                    <button type="button" class="btn btn-icon" id="filterBtn" title="Filter" style="position: relative;">
                        <i class="fas fa-filter"></i>
                        <span class="filter-notification" id="filterCountBadge" style="display: none; position: absolute; top: -5px; right: -5px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; line-height: 1;">0</span>
                    </button>

                    <!-- Advanced Filter Panel -->
                    <div class="filter-panel" id="filterPanel" style="display: none; position: absolute; top: 100%; left: 0; margin-top: 10px; width: 350px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; text-align: left;">
                        <div class="filter-panel-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color);">
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-filter" style="color: var(--primary-color); font-size: 14px;"></i> Select Filters
                            </h3>
                        </div>
                        <div class="filter-panel-body" style="padding: 20px;">
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="filterDeletedBy" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">Deleted By</label>
                                    <select id="filterDeletedBy" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                                        <option value="">All Users</option>
                                        <?php foreach($all_users as $u): ?>
                                            <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filter_user === $u ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucwords($u)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="filterType" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">Type</label>
                                    <select id="filterType" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                                        <option value="">All Types</option>
                                        <option value="resident">Residents</option>
                                        <option value="household">Households & Members</option>
                                        <option value="blotter">Blotters</option>
                                        <option value="official">Officials</option>
                                       
                                        <option value="user">Users</option>
                                        <option value="role">Roles</option>
                                    </select>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="filterFromDate" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">From Date</label>
                                    <input type="date" id="filterFromDate" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <label for="filterToDate" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">To Date</label>
                                    <input type="date" id="filterToDate" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                                </div>
                            </div>
                        </div>
                        <div class="filter-panel-footer" style="padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; gap: 10px;">
                            <button type="button" class="btn btn-secondary" id="clearFiltersBtn" style="font-size: 13px; padding: 8px 15px; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary); border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-times" style="margin-right: 5px;"></i> Clear
                            </button>
                            <button type="button" class="btn btn-primary" id="applyFiltersBtn" style="font-size: 13px; padding: 8px 15px; background: var(--primary-color); border: none; color: white; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-check" style="margin-right: 5px;"></i> Apply Now
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-icon" title="Refresh" onclick="window.location.href='archive.php'">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <!-- Archives Table -->
            <div class="table-container">
                <table class="data-table archive-table" id="archivesTable">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Record ID</th>
                            <th>Name</th>
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
                                $badgeIcon = 'fa-archive';
                                if($archive['archive_type'] == 'resident') { $badgeClass = 'badge-resident'; $badgeIcon = 'fa-user'; }
                                elseif($archive['archive_type'] == 'official') { $badgeClass = 'badge-official'; $badgeIcon = 'fa-user-tie'; }
                                elseif($archive['archive_type'] == 'blotter') { $badgeClass = 'badge-blotter'; $badgeIcon = 'fa-file-alt'; }
                                elseif($archive['archive_type'] == 'household') { $badgeClass = 'badge-household'; $badgeIcon = 'fa-home'; }
                                elseif($archive['archive_type'] == 'household_member' || $archive['archive_type'] == 'household member') { $badgeClass = 'badge-household'; $badgeIcon = 'fa-user-friends'; }
                                elseif($archive['archive_type'] == 'user') { $badgeClass = 'badge-default'; $badgeIcon = 'fa-user-circle'; }
                                elseif($archive['archive_type'] == 'role') { $badgeClass = 'badge-default'; $badgeIcon = 'fa-user-shield'; }
                                
                                $data = json_decode($archive['record_data'], true) ?: [];
                                $displayName = 'N/A';
                                $recordIdDisplay = $archive['record_id'];
                                if ($archive['archive_type'] == 'resident') {
                                    $firstName = $data['first_name'] ?? '';
                                    $middleName = $data['middle_name'] ?? '';
                                    $lastName = $data['last_name'] ?? '';
                                    $suffix = $data['suffix'] ?? '';
                                    $displayName = trim(preg_replace('/\s+/', ' ', "$firstName $middleName $lastName $suffix"));
                                } elseif ($archive['archive_type'] == 'official') {
                                    if (!empty($data['fullname'])) {
                                        $displayName = $data['fullname'];
                                    } else {
                                        $firstName = $data['first_name'] ?? '';
                                        $middleName = $data['middle_name'] ?? '';
                                        $lastName = $data['last_name'] ?? '';
                                        $suffix = $data['suffix'] ?? '';
                                        $displayName = trim(preg_replace('/\s+/', ' ', "$firstName $middleName $lastName $suffix"));
                                    }
                                } elseif ($archive['archive_type'] == 'blotter') {
                                    $displayName = (!empty($data['complainants']) && isset($data['complainants'][0]['name'])) ? $data['complainants'][0]['name'] : ($data['complainant'] ?? 'N/A');
                                } elseif ($archive['archive_type'] == 'household') {
                                    $displayName = $data['head_name'] ?? 'N/A';
                                } elseif ($archive['archive_type'] == 'household_member' || $archive['archive_type'] == 'household member') {
                                    $displayName = $data['resident_name'] ?? 'N/A';
                                    if (!empty($data['household_number'])) {
                                        $recordIdDisplay = $data['household_number'];
                                    }
                                } elseif ($archive['archive_type'] == 'user') {
                                    $displayName = $data['full_name'] ?? $data['name'] ?? 'N/A';
                                } elseif ($archive['archive_type'] == 'role') {
                                    $displayName = $data['name'] ?? 'N/A';
                                }
                                
                                if (empty(trim($displayName))) {
                                    $displayName = 'N/A';
                                }
                            ?>
                            <tr data-type="<?php echo htmlspecialchars($archive['archive_type']); ?>"
                                data-name="<?php echo htmlspecialchars(strtolower($displayName)); ?>"
                                data-deleted-by="<?php echo htmlspecialchars(strtolower($archive['deleted_by'] ?? '')); ?>"
                                data-deleted-at="<?php echo htmlspecialchars(date('Y-m-d', strtotime($archive['deleted_at']))); ?>">
                                <td>
                                    <span class="archive-type-badge <?php echo $badgeClass; ?>">
                                        <i class="fas <?php echo $badgeIcon; ?>"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $archive['archive_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($recordIdDisplay); ?></td>
                                <td><?php echo htmlspecialchars($displayName); ?></td>
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
                                <td colspan="7" style="text-align: center; padding: 20px; color: var(--text-secondary);">No archived records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info"></div>
                <div class="pagination"></div>
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
    <script src="assets/js/table.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const archivesTable = new EnhancedTable('archivesTable', {
                sortable: true,
                searchable: false,
                paginated: true,
                pageSize: 10,
                defaultFilter: row => !row.querySelector('td[colspan]')
            });
            
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearchBtn');
            const filterBtn = document.getElementById('filterBtn');
            const filterPanel = document.getElementById('filterPanel');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const filterCountBadge = document.getElementById('filterCountBadge');
            
            const filterDeletedBy = document.getElementById('filterDeletedBy');
            const filterType = document.getElementById('filterType');
            const filterFromDate = document.getElementById('filterFromDate');
            const filterToDate = document.getElementById('filterToDate');
            
            function applyFilters() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedDeletedBy = filterDeletedBy.value.toLowerCase();
                const selectedType = filterType.value.toLowerCase();
                const fromDate = filterFromDate.value;
                const toDate = filterToDate.value;
                
                let activeFilters = 0;
                if (selectedDeletedBy) activeFilters++;
                if (selectedType) activeFilters++;
                if (fromDate) activeFilters++;
                if (toDate) activeFilters++;
                
                if (activeFilters > 0) {
                    filterCountBadge.textContent = activeFilters;
                    filterCountBadge.style.display = 'inline-block';
                    filterBtn.classList.add('has-active-filters');
                } else {
                    filterCountBadge.style.display = 'none';
                    filterBtn.classList.remove('has-active-filters');
                }
                
                archivesTable.filter(row => {
                    // Skip empty state row
                    if (row.querySelector('td[colspan]')) return false;
                    
                    const name = row.getAttribute('data-name') || '';
                    const type = row.getAttribute('data-type') || '';
                    const deletedBy = row.getAttribute('data-deleted-by') || '';
                    const deletedAt = row.getAttribute('data-deleted-at') || '';
                    
                    let show = true;
                    
                    // Search filter (searches by Name only)
                    if (searchTerm && !name.includes(searchTerm)) show = false;
                    
                    // Advanced filters
                    if (selectedDeletedBy && deletedBy !== selectedDeletedBy) show = false;
                    
                    if (selectedType) {
                        if (selectedType === 'household' && !(type === 'household' || type === 'household_member' || type === 'household member')) {
                            show = false;
                        } else if (selectedType !== 'household' && type !== selectedType) {
                            show = false;
                        }
                    }
                    
                    if (fromDate && deletedAt < fromDate) show = false;
                    if (toDate && deletedAt > toDate) show = false;
                    
                    return show;
                });
            }
            
            // Search Input Event
            searchInput.addEventListener('input', function() {
                clearSearchBtn.style.display = this.value.trim() ? 'flex' : 'none';
                applyFilters();
            });
            
            // Clear Search Event
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.style.display = 'none';
                applyFilters();
            });
            
            // Filter Button Toggle
            filterBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
            });
            
            // Click outside to close filter panel
            document.addEventListener('click', function(e) {
                if (!filterPanel.contains(e.target) && !filterBtn.contains(e.target)) {
                    filterPanel.style.display = 'none';
                }
            });
            
            // Notification Function
            function showNotification(message, type = 'info') {
                document.querySelectorAll('.notification').forEach(n => n.remove());
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                `;
                notification.style.cssText = `
                    position: fixed; top: 20px; right: 20px;
                    background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                    color: white; padding: 15px 20px; border-radius: 8px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: flex;
                    align-items: center; gap: 10px; z-index: 10000;
                    animation: slideIn 0.3s ease;
                `;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }
            
            // Add animation styles
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
                @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
            `;
            document.head.appendChild(style);

            // Apply Filters Button
            applyFiltersBtn.addEventListener('click', function() {
                let active = 0;
                if (filterDeletedBy.value) active++;
                if (filterType.value) active++;
                if (filterFromDate.value) active++;
                if (filterToDate.value) active++;
                
                if (active > 0) {
                    showNotification(`${active} filter(s) applied successfully`, 'success');
                } else {
                    showNotification('No filters selected', 'info');
                }
                applyFilters();
                filterPanel.style.display = 'none';
            });
            
            // Clear Filters Button
            clearFiltersBtn.addEventListener('click', function() {
                filterDeletedBy.value = '';
                filterType.value = '';
                filterFromDate.value = '';
                filterToDate.value = '';
                applyFilters();
                showNotification('Filters cleared', 'info');
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
                html += createRow('Record Number', data.record_number || 'N/A');
                html += createRow('Incident Type', data.incident_type || data.complaint || 'N/A');
                html += createRow('Status', data.status || 'N/A');
                html += createRow('Date Reported', data.date_reported || data.date || 'N/A');
                html += createRow('Incident Date', data.incident_date || 'N/A');
                html += createRow('Incident Location', data.incident_location || 'N/A');

                // Complainants list
                if (data.complainants && data.complainants.length > 0) {
                    var cNames = data.complainants.map(function(c){ return c.name; }).join(', ');
                    html += createRow('Complainant(s)', cNames);
                } else {
                    html += createRow('Complainant', data.complainant || 'N/A');
                }

                // Victims list
                if (data.victims && data.victims.length > 0) {
                    var vNames = data.victims.map(function(v){ return v.name; }).join(', ');
                    html += createRow('Victim(s)', vNames);
                }

                // Respondents list
                if (data.respondents && data.respondents.length > 0) {
                    var rNames = data.respondents.map(function(r){ return r.name; }).join(', ');
                    html += createRow('Respondent(s)', rNames);
                } else {
                    html += createRow('Respondent', data.respondent || 'N/A');
                }

                // Witnesses list
                if (data.witnesses && data.witnesses.length > 0) {
                    var wNames = data.witnesses.map(function(w){ return w.name; }).join(', ');
                    html += createRow('Witness(es)', wNames);
                }

                html += createRow('Incident Details', data.incident_description || 'N/A');
                html += createRow('Resolution', data.resolution || 'N/A');
            } else if (type === 'household') {
                html += createRow('Household No.', data.household_number || 'N/A');
                html += createRow('Head', data.head_name || 'N/A');
                html += createRow('Address', data.address || 'N/A');
                html += createRow('Contact', data.household_contact || 'N/A');
                html += createRow('Members Count', (data.members ? data.members.length : 0));
            } else if (type === 'household_member' || type === 'household member') {
                html += createRow('Household Number', data.household_number || data.household_id || 'N/A');
                html += createRow('Resident Name', data.resident_name || data.resident_id || 'N/A');
                html += createRow('Relationship to Head', data.relationship_to_head || 'N/A');
            } else if (type === 'user') {
                html += createRow('Username', data.username || 'N/A');
                html += createRow('Role', data.type || data.role || 'N/A');
                html += createRow('Created', data.created_at || 'N/A');
            } else if (type === 'role') {
                html += createRow('Role Name', data.name || 'N/A');
                html += createRow('Description', data.description || 'N/A');
                html += createRow('Color', `<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:${data.color||'#ccc'};margin-right:5px;"></span> ${data.color||'N/A'}`);
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