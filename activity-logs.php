<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';
requirePermission('perm_settings_logs_view');

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

    // Fetch Barangay Info and Captain for Official Header/Footer
    $barangayInfo = null;
    $captainName = 'BARANGAY CAPTAIN';
    $infoStmt = $conn->query("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
    if ($infoStmt && $infoStmt->num_rows > 0) {
        $barangayInfo = $infoStmt->fetch_assoc();
    }
    $capStmt = $conn->query("SELECT fullname FROM barangay_officials WHERE position = 'Barangay Captain' AND status = 'Active' LIMIT 1");
    if ($capStmt && $capStmt->num_rows > 0) {
        $cap = $capStmt->fetch_assoc();
        $captainName = $cap['fullname'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
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
        .print-only { display: none !important; }
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
                <div class="page-header-actions">
                    <?php if (hasPermission('perm_settings_logs_print')): ?>
                    <button class="btn-print" id="printMasterlistBtn" title="Print Masterlist">
                        <i class="fas fa-print"></i>
                        Print Masterlist
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
                    <h3 class="print-list-title">Activity Logs Masterlist</h3>
                    <p class="print-meta">
                        Date Printed: <strong><?php echo date('F d, Y'); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Total Records: <strong id="printTotalRecords"><?php echo number_format($total_records); ?></strong>
                    </p>
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
                                        <input type="date" name="filter_from_date" id="filterFromDate" class="form-control" max="<?php echo date('Y-m-d'); ?>"  style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);" value="<?php echo htmlspecialchars($filter_from_date); ?>">
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <label for="filterToDate" style="font-size: 13px; font-weight: 500; color: var(--text-secondary); margin: 0;">To Date</label>
                                        <input type="date" name="filter_to_date" id="filterToDate" class="form-control" max="<?php echo date('Y-m-d'); ?>" style="font-size: 13px; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-primary); color: var(--text-primary);" value="<?php echo htmlspecialchars($filter_to_date); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="filter-panel-footer" style="padding: 15px 20px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; gap: 10px;">
                                <button type="button" class="btn btn-secondary" id="clearFiltersBtn" style="font-size: 13px; padding: 8px 15px; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary); border-radius: 6px; cursor: pointer;">
                                    <i class="fas fa-times" style="margin-right: 5px;"></i> Clear
                                </button>
                            <button type="submit" class="btn btn-primary" id="applyFiltersBtn" style="font-size: 13px; padding: 8px 15px; background: var(--primary-color); border: none; color: white; border-radius: 6px; cursor: pointer;">
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
                            <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
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
                        <p style="margin: 0; font-size: 14px;"><?php echo htmlspecialchars($captainName); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtn = document.getElementById('filterBtn');
            const filterPanel = document.getElementById('filterPanel');
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            const applyFiltersBtn = document.getElementById('applyFiltersBtn');
            const filterUser = document.getElementById('filterUser');
            const filterAction = document.getElementById('filterAction');
            const filterFromDate = document.getElementById('filterFromDate');
            const filterToDate = document.getElementById('filterToDate');

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
            
            // Check for saved notification
            const alertMsg = sessionStorage.getItem('activity_filter_alert');
            if (alertMsg) {
                showNotification(alertMsg, alertMsg.includes('cleared') || alertMsg.includes('selected') ? 'info' : 'success');
                sessionStorage.removeItem('activity_filter_alert');
            }

            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', function() {
                    let active = 0;
                    if (filterUser.value) active++;
                    if (filterAction.value) active++;
                    if (filterFromDate.value) active++;
                    if (filterToDate.value) active++;
                    
                    if (active > 0) {
                        sessionStorage.setItem('activity_filter_alert', `${active} filter(s) applied successfully`);
                    } else {
                        sessionStorage.setItem('activity_filter_alert', 'No filters selected');
                    }
                });
            }

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
                    sessionStorage.setItem('activity_filter_alert', 'Filters cleared');
                    filterUser.value = '';
                    filterAction.value = '';
                    filterFromDate.value = '';
                    filterToDate.value = '';
                    document.getElementById('activityLogForm').submit();
                });
            }
            
            // Print Masterlist
            const printBtn = document.getElementById('printMasterlistBtn');
            if (printBtn) {
                printBtn.addEventListener('click', async () => {
                    let brgyInfo = {
                        province_name: 'Province',
                        town_name: 'Municipality',
                        barangay_name: 'Barangay',
                        barangay_logo: '',
                        official_emblem: ''
                    };
                    
                    try {
                        const response = await fetch('model/get_barangay_info.php');
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success && data.data) {
                                brgyInfo = data.data;
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching barangay info:', error);
                    }
                    
                    const brgyLogoHtml = brgyInfo.barangay_logo 
                        ? `<img src="${brgyInfo.barangay_logo}" class="logo-img" alt="Barangay Logo">`
                        : `<div class="logo-placeholder-box"></div>`;
                        
                    const govLogoHtml = brgyInfo.official_emblem
                        ? `<img src="${brgyInfo.official_emblem}" class="logo-img" alt="Official Emblem">`
                        : `<div class="logo-placeholder-box"></div>`;

                    let printFrame = document.getElementById('logsPrintFrame');
                    if (!printFrame) {
                        printFrame = document.createElement('iframe');
                        printFrame.id = 'logsPrintFrame';
                        printFrame.style.position = 'fixed';
                        printFrame.style.bottom = '0';
                        printFrame.style.right = '0';
                        printFrame.style.width = '0';
                        printFrame.style.height = '0';
                        printFrame.style.border = 'none';
                        document.body.appendChild(printFrame);
                    }

                    const doc = printFrame.contentWindow.document;
                    doc.open();

                    const tableHeaderHtml = `
                        <thead>
                            <tr>
                                <th style="width: 40px; text-align: center;">No.</th>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                    `;

                    let rowsHtml = '';
                    let rowsToPrint = Array.from(document.querySelectorAll('.activity-table tbody tr:not([style*="display: none"])'));
                    
                    if (rowsToPrint.length === 1 && rowsToPrint[0].cells.length === 1) {
                        rowsHtml = `<tr><td colspan="6" style="text-align: center;">No activity logs found.</td></tr>`;
                    } else {
                        rowsToPrint.forEach((row, index) => {
                            if (row.cells.length < 5) return;
                            const no = index + 1;
                            const id = row.cells[0]?.textContent.trim() || '';
                            const user = row.cells[1]?.textContent.trim() || '';
                            const action = row.cells[2]?.textContent.trim() || '';
                            const desc = row.cells[3]?.textContent.trim() || '';
                            const time = row.cells[4]?.textContent.trim() || '';

                            rowsHtml += `
                                <tr style="display: table-row;">
                                    <td style="text-align: center;">${no}</td>
                                    <td>${id}</td>
                                    <td>${user}</td>
                                    <td>${action}</td>
                                    <td>${desc}</td>
                                    <td>${time}</td>
                                </tr>
                            `;
                        });
                    }

                    const styles = Array.from(document.querySelectorAll('link[rel="stylesheet"], style')).map(s => s.outerHTML).join('\n');
                    const printFooter = document.querySelector('.print-footer') ? document.querySelector('.print-footer').cloneNode(true) : null;

                    let finalTitle = "Activity Logs Masterlist";
                    const printHeader = document.querySelector('.print-header');
                    if (printHeader) {
                        const countBadge = printHeader.querySelector('#printTotalRecords');
                        if (countBadge) countBadge.textContent = "<?php echo $total_records; ?>";
                        
                        const activeFilters = [];
                        const fUser = document.getElementById('filterUser')?.value;
                        const fAction = document.getElementById('filterAction')?.value;
                        const fFrom = document.getElementById('filterFromDate')?.value;
                        const fTo = document.getElementById('filterToDate')?.value;
                        
                        if (fUser) activeFilters.push(`User: ${fUser}`);
                        if (fAction) activeFilters.push(`Action: ${fAction}`);
                        if (fFrom) activeFilters.push(`From: ${fFrom}`);
                        if (fTo) activeFilters.push(`To: ${fTo}`);
                        
                        const searchInput = document.querySelector('input[name="search"]');
                        if (searchInput && searchInput.value.trim()) {
                            activeFilters.push(`Search: "${searchInput.value.trim()}"`);
                        }
                        if (activeFilters.length > 0) {
                            finalTitle += " - " + activeFilters.join(', ');
                        }
                    }

                    doc.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
                            <title>Activity Logs Masterlist</title>
                            ${styles}
                            <style>
                                body { background: white !important; color: black !important; padding: 20px !important; }
                                .main-content, .dashboard-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
                                .print-only { display: flex !important; }
                                .data-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
                                .data-table th, .data-table td { border: 1px solid #333 !important; padding: 6px !important; font-size: 9px !important; text-align: left; }
                                .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                                .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                                .header-center { flex: 1; }
                                .header-center p { margin: 2px 0; font-size: 14px; }
                                .header-center .brgy-name { font-weight: bold; font-size: 16px; margin-top: 5px; }
                                .logo-img { width: 80px; height: 80px; object-fit: contain; }
                                .logo-placeholder-box { width: 80px; height: 80px; }
                                @page { size: A4; margin: 15mm; }
                            </style>
                        </head>
                        <body>
                            <div class="dashboard-content">
                                <div class="cert-header">
                                    ${brgyLogoHtml}
                                    <div class="header-center">
                                        <p>Republic of the Philippines</p>
                                        <p>Province of ${brgyInfo.province_name || 'Province'}</p>
                                        <p>Municipality of ${brgyInfo.town_name || 'Municipality'}</p>
                                        <p class="brgy-name">${(brgyInfo.barangay_name || 'Barangay').toUpperCase()}</p>
                                    </div>
                                    ${govLogoHtml}
                                </div>
                                <div style="text-align: center; margin: 15px 0;">
                                    <h3 style="margin: 0; text-transform: uppercase;">${finalTitle}</h3>
                                    <p style="margin: 5px 0 0 0; font-size: 12px;">Records on Page: ${rowsToPrint.length > 0 && rowsToPrint[0].cells.length > 1 ? rowsToPrint.length : 0} (Total: <?php echo $total_records; ?>)</p>
                                </div>
                                <table class="data-table">
                                    ${tableHeaderHtml}
                                    <tbody>${rowsHtml}</tbody>
                                </table>
                                ${printFooter ? printFooter.outerHTML : ''}
                            </div>
                        </body>
                        </html>
                    `);
                    doc.close();

                    setTimeout(() => {
                        fetch('model/log_print_masterlist.php', { method: 'POST' }).catch(e => console.error(e));
                        printFrame.contentWindow.focus();
                        printFrame.contentWindow.print();
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>