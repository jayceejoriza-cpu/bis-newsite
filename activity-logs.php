<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';
$pageTitle = 'Activity Logs';

// Pagination and Search Settings
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_user = isset($_GET['filter_user']) ? trim($_GET['filter_user']) : '';
$filter_action = isset($_GET['filter_action']) ? trim($_GET['filter_action']) : '';
$filter_from_date = isset($_GET['filter_from_date']) ? trim($_GET['filter_from_date']) : '';
$filter_to_date = isset($_GET['filter_to_date']) ? trim($_GET['filter_to_date']) : '';

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

$logs = [];
$total_pages = 0;
$all_users = [];

if (isset($conn)) {
    // Fetch all users for the filter
    $user_query = "SELECT username FROM users ORDER BY username ASC";
    $u_res = $conn->query($user_query);
    if ($u_res) {
        while ($u = $u_res->fetch_assoc()) {
            $all_users[] = $u['username'];
        }
    }

    $params = [];
    $types = "";
    
    $where_sql = " WHERE 1=1";
    if (!empty($search)) {
        $where_sql .= " AND description LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }
    if (!empty($filter_user)) {
        $where_sql .= " AND user = ?";
        $params[] = $filter_user;
        $types .= "s";
    }
    if (!empty($filter_action)) {
        $where_sql .= " AND action LIKE ?";
        $params[] = "%$filter_action%";
        $types .= "s";
    }
    if (!empty($filter_from_date)) {
        $where_sql .= " AND DATE(timestamp) >= ?";
        $params[] = $filter_from_date;
        $types .= "s";
    }
    if (!empty($filter_to_date)) {
        $where_sql .= " AND DATE(timestamp) <= ?";
        $params[] = $filter_to_date;
        $types .= "s";
    }

    // Count total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM activity_logs" . $where_sql;
    if (!empty($params)) {
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $total_records = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    }
    
    $total_pages = ceil($total_records / $limit);

    // Fetch records with LIMIT and OFFSET
    $sql = "SELECT * FROM activity_logs" . $where_sql . " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
    
    $fetch_params = $params;
    $fetch_types = $types . "ii";
    $fetch_params[] = $limit;
    $fetch_params[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($fetch_types, ...$fetch_params);

    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        /* Page Header */
        .page-header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .page-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-top: 5px;
        }

        /* Search Bar */
        .search-filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-filter-bar .search-box {
            width: 300px;
        }
        .search-filter-bar .search-box input {
            height: 40px;
            font-size: 13px;
            width: 100%;
        }

        /* Table Styling */
        .activity-table {
            font-size: 13px;
            border: 1px solid var(--border-color);
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
        }
        .activity-table th {
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
        .activity-table th:not(:last-child) {
            border-right: 1px solid var(--border-color);
        }
        .activity-table td {
            padding: 14px 20px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .activity-table td:not(:last-child) {
            border-right: 1px solid var(--border-color);
        }
        .activity-table tbody tr:last-child td {
            border-bottom: none;
        }
        .activity-table tbody tr {
            transition: all 0.2s ease;
            background-color: var(--bg-secondary);
        }
        .activity-table tbody tr:hover {
            background-color: var(--bg-primary);
        }

        /* Pagination Styling (Consistent with Households) */
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
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .page-btn:hover:not(.disabled) {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border-color: var(--primary-color);
        }
        .page-btn.active {
            background-color: var(--primary-color);
            color: white !important;
            border-color: var(--primary-color);
        }
        .page-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
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
                    <p class="page-subtitle">View system activity and audit logs</p>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="search-filter-bar" style="position: relative;">
                <form action="" method="GET" style="display: flex; gap: 10px; width: 100%; align-items: center;" id="activityLogForm">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search description..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                        <?php if($search || $filter_user || $filter_action || $filter_from_date || $filter_to_date): ?>
                            <a href="activity-logs.php" class="btn-clear" style="display: flex; align-items: center; justify-content: center; text-decoration: none;" title="Clear">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-icon" title="Search">
                        <i class="fas fa-search"></i>
                    </button>
                    
                    <!-- Filter Button and Panel Wrapper -->
                    <div style="position: relative; display: flex; align-items: center;">
                        <button type="button" class="btn btn-icon" id="filterBtn" title="Filter" style="position: relative;">
                            <i class="fas fa-filter"></i>
                            <?php 
                            $activeFilters = 0;
                            if($filter_user) $activeFilters++;
                            if($filter_action) $activeFilters++;
                            if($filter_from_date) $activeFilters++;
                            if($filter_to_date) $activeFilters++;
                            if($activeFilters > 0): 
                            ?>
                            <span class="filter-notification" style="position: absolute; top: -5px; right: -5px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; line-height: 1;">
                                <?php echo $activeFilters; ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <!-- Advanced Filter Panel -->
                        <div class="filter-panel" id="filterPanel" style="display: none; position: absolute; top: 100%; margin-top: 10px; width: 350px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 1000; text-align: left;">
                            <div class="filter-panel-header" style="padding: 15px 20px; border-bottom: 1px solid var(--border-color);">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-filter" style="color: var(--primary-color); font-size: 14px;"></i> Select Filters
                                </h3>
                            </div>
                            <div class="filter-panel-body" style="padding: 20px;">
                                <div style="display: flex; flex-direction: column; gap: 15px;">
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label for="filterUser" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">User</label>
                                        <select name="filter_user" id="filterUser" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);">
                                            <option value="">All Users</option>
                                            <?php foreach($all_users as $u): ?>
                                                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filter_user === $u ? 'selected' : ''; ?>><?php echo htmlspecialchars($u); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label for="filterAction" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">Action</label>
                                        <input type="text" name="filter_action" id="filterAction" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);" placeholder="e.g. Login, Update" value="<?php echo htmlspecialchars($filter_action); ?>">
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label for="filterFromDate" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">From Date</label>
                                        <input type="date" name="filter_from_date" id="filterFromDate" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);" value="<?php echo htmlspecialchars($filter_from_date); ?>">
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label for="filterToDate" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">To Date</label>
                                        <input type="date" name="filter_to_date" id="filterToDate" class="form-control" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);" value="<?php echo htmlspecialchars($filter_to_date); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="filter-panel-footer" style="padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; gap: 10px;">
                                <button type="button" class="btn btn-secondary" id="clearFiltersBtn" style="font-size: 13px; padding: 8px 15px; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary); border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-times" style="margin-right: 5px;"></i> Clear
                                </button>
                                <button type="submit" class="btn btn-primary" style="font-size: 13px; padding: 8px 15px; background: var(--primary-color); border: none; color: white; border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-check" style="margin-right: 5px;"></i> Apply Now
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-icon" title="Refresh" onclick="window.location.href='activity-logs.php'">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table activity-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">No activity logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['id']); ?></td>
                                    <td><?php echo htmlspecialchars($log['user']); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($log['description']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($log['timestamp']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>Showing 
                        <select class="form-select form-select-sm" id="pageSizeList" style="display: inline-block; width: auto; margin: 0 5px; padding: 2px 24px 2px 8px; cursor: pointer; min-height: 26px;" onchange="window.location.href='?limit='+this.value+'&search=<?php echo urlencode($search); ?>&filter_user=<?php echo urlencode($filter_user); ?>&filter_action=<?php echo urlencode($filter_action); ?>&filter_from_date=<?php echo urlencode($filter_from_date); ?>&filter_to_date=<?php echo urlencode($filter_to_date); ?>'">
                            <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                            <?php $half = max(1, ceil($total_records / 2)); ?>
                            <?php if (!in_array($half, [10, 100])): ?>
                            <option value="<?php echo $half; ?>" <?php echo $limit == $half ? 'selected' : ''; ?>><?php echo $half; ?></option>
                            <?php endif; ?>
                            <?php $all = max(1, $total_records); ?>
                            <?php if (!in_array($all, [10, 100, $half])): ?>
                            <option value="<?php echo $all; ?>" <?php echo $limit == $all ? 'selected' : ''; ?>><?php echo $all; ?></option>
                            <?php endif; ?>
                        </select>
                        of <strong><?php echo number_format($total_records); ?></strong></span>
                </div>
                <?php if ($total_pages > 1 || $page > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&filter_user=<?php echo urlencode($filter_user); ?>&filter_action=<?php echo urlencode($filter_action); ?>&filter_from_date=<?php echo urlencode($filter_from_date); ?>&filter_to_date=<?php echo urlencode($filter_to_date); ?>" 
                       class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>" title="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <button class="page-btn active"><?php echo $page; ?></button>
                    
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&filter_user=<?php echo urlencode($filter_user); ?>&filter_action=<?php echo urlencode($filter_action); ?>&filter_from_date=<?php echo urlencode($filter_from_date); ?>&filter_to_date=<?php echo urlencode($filter_to_date); ?>" 
                       class="page-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" title="Next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtn = document.getElementById('filterBtn');
            const filterPanel = document.getElementById('filterPanel');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const filterUser = document.getElementById('filterUser');
            const filterAction = document.getElementById('filterAction');
            const filterFromDate = document.getElementById('filterFromDate');
            const filterToDate = document.getElementById('filterToDate');

            if (filterBtn && filterPanel) {
                filterBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    filterPanel.style.display = filterPanel.style.display === 'none' ? 'block' : 'none';
                });

                document.addEventListener('click', function(e) {
                    if (!filterPanel.contains(e.target) && !filterBtn.contains(e.target)) {
                        filterPanel.style.display = 'none';
                    }
                });
            }

            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function() {
                    filterUser.value = '';
                    filterAction.value = '';
                    filterFromDate.value = '';
                    filterToDate.value = '';
                    document.getElementById('activityLogForm').submit();
                });
            }
        });
    </script>
</body>
</html>