<?php
require_once 'config.php';
$pageTitle = 'Activity Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    <main class="main-content">
        <?php include 'components/header.php'; ?>
        <div class="dashboard-content">
            <h1 class="page-title"><?php echo $pageTitle; ?></h1>
            <div class="chart-container">
                <p>System activity logs will be displayed here.</p>
            </div>
        </div>
    </main>
    <script src="js/script.js"></script>
</body>
</html>