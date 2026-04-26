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

    // AJAX / Export Handler: Fetch ALL filtered data (ignoring pagination)
    if (isset($_GET['action'])) {
        $sql_all = "SELECT * FROM activity_logs" . $where_sql . " ORDER BY timestamp DESC";
        $stmt_all = $conn->prepare($sql_all);
        if (!empty($params)) {
            $stmt_all->bind_param($types, ...$params);
        }
        $stmt_all->execute();
        $res_all = $stmt_all->get_result();

        if ($_GET['action'] === 'export_csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=Activity_Logs_Full_' . date('Y-m-d') . '.csv');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($output, ['ID', 'User', 'Action', 'Description', 'Timestamp']);
            while ($row = $res_all->fetch_assoc()) {
                fputcsv($output, [$row['id'], $row['user'], $row['action'], $row['description'], date('M d, Y h:i A', strtotime($row['timestamp']))]);
            }
            fclose($output);
            
            // Log activity
            $log_user = $_SESSION['username'] ?? 'System';
            $conn->query("INSERT INTO activity_logs (user, action, description) VALUES ('$log_user', 'Export Masterlist', 'Exported full filtered logs to CSV')");
            exit;
        } elseif ($_GET['action'] === 'fetch_all') {
            $data = [];
            while ($r = $res_all->fetch_assoc()) {
                $r['timestamp_fmt'] = date('M d, Y h:i A', strtotime($r['timestamp']));
                $data[] = $r;
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
        }
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
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
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
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn-print dropdown-toggle" type="button" id="exportPrintDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export"></i>
                            Export / Print Masterlist
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="exportPrintDropdown" style="font-size: 14px;">
                            <li><button class="dropdown-item py-2" type="button" id="exportCsvBtn"><i class="fas fa-file-csv me-2 text-success"></i> Export Csv</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item py-2" type="button" id="printMasterlistBtn"><i class="fas fa-print me-2 text-primary"></i> Print Masterlist</button></li>
                        </ul>
                    </div>
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
                        <input type="text" name="search" id="searchInput" placeholder="Search description..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
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
       class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
        <i class="fas fa-chevron-left"></i>
    </a>

    <?php
    $range = 2; // How many numbers to show around the current page
    $show_initial_pages = 5; // Initial numbers before ellipsis

    for ($i = 1; $i <= $total_pages; $i++) {
        // Condition to show page numbers:
        // 1. Show first 5 pages
        // 2. Show the last page
        // 3. Show pages around the current page
        if ($i <= $show_initial_pages || $i == $total_pages || ($i >= $page - $range && $i <= $page + $range)) {
            $activeClass = ($i == $page) ? 'active' : '';
            $url = "?page=$i&limit=$limit&search=".urlencode($search)."&filter_user=".urlencode($filter_user)."&filter_action=".urlencode($filter_action)."&filter_from_date=".urlencode($filter_from_date)."&filter_to_date=".urlencode($filter_to_date);
            
            echo "<a href='$url' class='page-btn $activeClass'>$i</a>";
        } 
        // Show ellipsis if there is a gap
        elseif ($i == $show_initial_pages + 1 || $i == $total_pages - 1) {
            echo "<span class='spacer'>...</span>";
        }
    }
    ?>

    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&limit=<?php echo $limit; ?>&search=<?php echo urlencode($search); ?>&filter_user=<?php echo urlencode($filter_user); ?>&filter_action=<?php echo urlencode($filter_action); ?>&filter_from_date=<?php echo urlencode($filter_from_date); ?>&filter_to_date=<?php echo urlencode($filter_to_date); ?>" 
       class="page-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
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
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtn = document.getElementById('filterBtn');
            const searchInput = document.getElementById('searchInput');
            
            // Sync search input with URL params
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function(e) {
                    const term = e.target.value.trim();
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const url = new URL(window.location);
                        if (term) {
                            url.searchParams.set('search', term);
                        } else {
                            url.searchParams.delete('search');
                        }
                        window.history.replaceState({}, '', url);
                    }, 300);
                });
            }

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
            
            const printBtn = document.getElementById('printMasterlistBtn');
            const exportCsvBtn = document.getElementById('exportCsvBtn');

            if (exportCsvBtn) {
                exportCsvBtn.addEventListener('click', () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('action', 'export_csv');
                    window.location.href = url.toString();
                });
            }

            if (printBtn) {
                printBtn.addEventListener('click', async () => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('action', 'fetch_all');
                    
                    const response = await fetch(url.toString());
                    const result = await response.json();

                    if (!result.success || result.data.length === 0) {
                        alert('No data to print.');
                        return;
                    }

                    const brgyInfo = <?php echo json_encode($barangayInfo); ?> || {
                        province_name: 'Province',
                        town_name: 'Municipality',
                        barangay_name: 'Barangay',
                        barangay_logo: '',
                        municipal_logo: ''
                    };
                    
                    const captainName = <?php echo json_encode($captainName); ?> || 'Barangay Captain';
                    const preparedBy = <?php echo json_encode($_SESSION['full_name'] ?? 'Authorized Staff'); ?>;

                    let printFrame = document.getElementById('activityLogsPrintFrame');
                    if (!printFrame) {
                        printFrame = document.createElement('iframe');
                        printFrame.id = 'activityLogsPrintFrame';
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

                    let rowsHtml = '';
                    result.data.forEach(log => {
                        rowsHtml += `
                            <tr>
                                <td style="text-align: center;">${log.id}</td>
                                <td>${log.user}</td>
                                <td>${log.action}</td>
                                <td>${log.description}</td>
                                <td>${log.timestamp_fmt}</td>
                            </tr>
                        `;
                    });

                    const brgyLogoHtml = brgyInfo.barangay_logo 
                        ? `<img src="${brgyInfo.barangay_logo}" style="width: 80px; height: 80px; object-fit: contain;">`
                        : `<div style="width: 80px; height: 80px;"></div>`;
                        
                    const municipalLogoHtml = brgyInfo.municipal_logo
                        ? `<img src="${brgyInfo.municipal_logo}" style="width: 80px; height: 80px; object-fit: contain;">`
                        : `<div style="width: 80px; height: 80px;"></div>`;

                    doc.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <title>Activity Logs Masterlist</title>
                            <style>
                                @page { size: A4 landscape; margin: 15mm; }
                                body { font-family: 'Inter', sans-serif; color: #000; background: #fff; margin: 0; padding: 0; }
                                .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; text-align: center; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                                .header-center { flex: 1; }
                                .header-center p { margin: 2px 0; font-size: 14px; }
                                .brgy-name { font-weight: bold; font-size: 16px; text-transform: uppercase; margin-top: 5px; }
                                .report-title { text-align: center; margin: 20px 0; text-transform: uppercase; font-size: 18px; font-weight: bold; text-decoration: underline; }
                                .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
                                .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                                .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                                .print-footer { margin-top: 50px; display: flex; justify-content: space-between; padding: 0 50px; }
                                .sig-box { text-align: center; width: 250px; }
                                .sig-line { border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px; }
                                .sig-name { font-weight: bold; text-transform: uppercase; font-size: 13px; margin: 0; }
                                .sig-title { font-size: 11px; color: #666; margin: 0; }
                            </style>
                        </head>
                        <body>
                            <div class="cert-header">
                                ${brgyLogoHtml}
                                <div class="header-center">
                                    <p>Republic of the Philippines</p>
                                    <p>Province of ${brgyInfo.province_name}</p>
                                    <p>Municipality of ${brgyInfo.town_name}</p>
                                    <p class="brgy-name">${brgyInfo.barangay_name.toUpperCase()}</p>
                                </div>
                                ${municipalLogoHtml}
                            </div>
                            <h2 class="report-title">Activity Logs Masterlist</h2>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">ID</th>
                                        <th style="width: 120px;">User</th>
                                        <th style="width: 150px;">Action</th>
                                        <th>Description</th>
                                        <th style="width: 180px;">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                            <div class="print-footer">
                                <div class="sig-box">
                                    <p style="text-align: left; font-size: 12px; margin-bottom: 5px;">Prepared by:</p>
                                    <div class="sig-line"></div>
                                    <p class="sig-name">${preparedBy}</p>
                                    <p class="sig-title">Authorized Staff</p>
                                </div>
                                <div class="sig-box">
                                    <p style="text-align: left; font-size: 12px; margin-bottom: 5px;">Certified Correct:</p>
                                    <div class="sig-line"></div>
                                    <p class="sig-name">${captainName}</p>
                                    <p class="sig-title">Barangay Captain</p>
                                </div>
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