<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';
$pageTitle = 'Activity Logs';

// Pagination and Search Settings
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$logs = [];
$total_pages = 0;

if (isset($conn)) {
    // Count total records for pagination
    $count_query = "SELECT COUNT(*) as total FROM activity_logs";
    if (!empty($search)) {
        $count_query .= " WHERE description LIKE ?";
        $stmt = $conn->prepare($count_query);
        $search_param = "%$search%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $total_records = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    } else {
        $total_records = $conn->query($count_query)->fetch_assoc()['total'];
    }
    
    $total_pages = ceil($total_records / $limit);

    // Fetch records with LIMIT and OFFSET
    $sql = "SELECT * FROM activity_logs";
    if (!empty($search)) {
        $sql .= " WHERE description LIKE ?";
        $sql .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $search_param, $limit, $offset);
    } else {
        $sql .= " ORDER BY timestamp DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
    }

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
        /* Search Input Dark Mode */
        html.dark-mode .search-input {
            background-color: #212529 !important;
            color: #ffffff !important;
            border-color: #495057 !important;
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
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            
            <!-- Search Bar -->
            <div class="search-container mb-4">
                <form action="" method="GET" class="d-flex align-items-center justify-content-end gap-3 flex-wrap">
                    <div class="d-flex gap-2" style="max-width: 500px;">
                        <input type="text" name="search" class="form-control search-input" placeholder="Search description..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if($search): ?>
                            <a href="activity-logs.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table table-hover">
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
                    <span>TOTAL: <strong><?php echo number_format($total_records); ?></strong></span>
                </div>
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-btn <?php echo ($page <= 1) ? 'disabled' : ''; ?>" title="Previous">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <button class="page-btn active"><?php echo $page; ?></button>
                    
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-btn <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>" title="Next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <script src="assets/js/script.js"></script>
</body>
</html>