<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';
requirePermission('perm_settings_restore');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Handle Password Verification and SQL Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore'])) {
    $password = trim($_POST['password'] ?? '');

    if (empty($password)) {
        $error = "Password is required to perform a restore.";
    } elseif (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid SQL file.";
    } else {
        $file = $_FILES['sql_file'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($fileExt !== 'sql') {
            $error = "Only .sql files are allowed.";
        } else {
            // Verify user's password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Password correct — read and execute SQL file
                    if (filesize($file['tmp_name']) == 0) {
                        $error = "The uploaded SQL file is empty.";
                    } else {
                        try {
                            $conn->set_charset("utf8");

                            $executedCount = 0;
                            $failedStatements = [];
                            $query = '';

                            // Execute SET FOREIGN_KEY_CHECKS = 0; at the start
                            $conn->query("SET FOREIGN_KEY_CHECKS = 0");

                            // Fetch all table and view names from the current database and DROP them
                            $dbName = DB_NAME;
                            $tablesResult = $conn->query("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbName'");
                            if ($tablesResult) {
                                while ($row = $tablesResult->fetch_assoc()) {
                                    $name = $row['TABLE_NAME'];
                                    if ($row['TABLE_TYPE'] === 'VIEW') {
                                        $conn->query("DROP VIEW IF EXISTS `$name` ");
                                    } else {
                                        $conn->query("DROP TABLE IF EXISTS `$name` ");
                                    }
                                }
                            }

                            // Read the uploaded .sql file and execute it query-by-query
                            $handle = fopen($file['tmp_name'], "r");
                            if ($handle) {
                                while (($line = fgets($handle)) !== false) {
                                    $trimmedLine = trim($line);
                                    
                                    // Skip empty lines and SQL comments
                                    // Handles --, #, and single-line /* */ comments
                                    if ($trimmedLine === '' || strpos($trimmedLine, '--') === 0 || strpos($trimmedLine, '#') === 0 || preg_match('/^\/\*.*\*\/$/', $trimmedLine)) {
                                        continue;
                                    }

                                    $query .= $line;

                                    // If the line ends with a semicolon, it's a complete statement
                                    if (substr($trimmedLine, -1) === ';') {
                                        if (!$conn->query($query)) {
                                            // Error Handling: Log specific SQL line that caused the error
                                            $errorSnippet = htmlspecialchars(substr(trim($query), 0, 250));
                                            $failedStatements[] = "<strong>MySQL Error:</strong> " . $conn->error . "<br><strong>Problematic SQL:</strong> <code>" . $errorSnippet . "...</code>";
                                        } else {
                                            // Add a counter to show exactly how many statements were executed
                                            $executedCount++;
                                        }
                                        $query = '';
                                    }
                                }
                                fclose($handle);
                            }

                            // Execute SET FOREIGN_KEY_CHECKS = 1; at the end
                            $conn->query("SET FOREIGN_KEY_CHECKS = 1");

                            if (!empty($failedStatements)) {
                                $error = "Restore completed with " . count($failedStatements) . " error(s). " . $executedCount . " statement(s) were successful. <br><br><strong>Detailed Error:</strong> " . end($failedStatements);
                            } else {
                                $success = "Database restored successfully! $executedCount SQL statement(s) executed from <strong>" . htmlspecialchars($file['name']) . "</strong>.";
                            }

                            // Log the restore activity
                            $log_user = $_SESSION['username'];
                            $log_action = 'Restore Database';
                            $log_desc = "Restored database from file: " . htmlspecialchars($file['name']) . ". Executed $executedCount statements. Errors: " . count($failedStatements);
                            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                            $log_stmt->execute();
                            $log_stmt->close();

                        } catch (Exception $e) {
                            $conn->query("SET FOREIGN_KEY_CHECKS=1");
                            $error = "Restore failed: " . $e->getMessage();

                            // Log failed restore
                            $log_user = $_SESSION['username'];
                            $log_action = 'Restore Failed';
                            $log_desc = "Database restore failed: " . $e->getMessage();
                            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                            $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                            $log_stmt->execute();
                            $log_stmt->close();
                        }
                    }
                } else {
                    $error = "Invalid password. Restore cancelled.";

                    // Log failed authentication attempt
                    $log_user = $_SESSION['username'];
                    $log_action = 'Restore Auth Failed';
                    $log_desc = "Restore attempt failed due to invalid password";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
            } else {
                $error = "User not found.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Restore - <?php echo defined('SITE_NAME') ? SITE_NAME : 'BIS'; ?></title>

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .restore-container {
            max-width: 640px;
            margin: 0 auto;
            padding: 2rem;
        }

        .restore-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 3rem 2rem;
            text-align: center;
            border: 1px solid var(--border-color);
            transition: var(--color-transition);
        }

        .restore-icon-wrapper {
            width: 80px;
            height: 80px;
            background-color: #d1fae5;
            color: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1.5rem;
        }

        .restore-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            transition: var(--color-transition);
        }

        .restore-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
            transition: var(--color-transition);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            text-align: left;
            font-size: 0.9rem;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-warning {
            background-color: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
        }

        .btn-success {
            background-color: #059669;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background-color 0.2s;
        }

        .btn-success:hover {
            background-color: #047857;
        }

        /* Warning notice box */
        .warning-notice {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            text-align: left;
            font-size: 0.875rem;
            color: #92400e;
            line-height: 1.6;
        }

        .warning-notice i {
            color: #f59e0b;
            margin-right: 0.4rem;
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
            background-color: var(--bg-secondary);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 480px;
            width: 90%;
            animation: slideIn 0.3s;
            transition: var(--color-transition);
        }

        .modal-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-icon {
            width: 50px;
            height: 50px;
            background-color: #d1fae5;
            color: #059669;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            transition: var(--color-transition);
        }

        .modal-body {
            margin-bottom: 1.5rem;
        }

        .modal-text {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.6;
            transition: var(--color-transition);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            transition: var(--color-transition);
        }

        .password-input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: var(--color-transition);
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1rem;
            transition: var(--color-transition);
        }

        .toggle-password:hover {
            color: var(--text-primary);
        }

        /* File info display */
        .file-info {
            background-color: var(--bg-primary, #f9fafb);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .file-info.visible {
            display: flex;
        }

        .file-info i {
            color: #059669;
        }

        .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background-color 0.2s;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Dark mode support */
        body.dark-mode .restore-card {
            background-color: #1f2937;
            border-color: #374151;
        }

        body.dark-mode .restore-icon-wrapper {
            background-color: rgba(5, 150, 105, 0.2);
            color: #34d399;
        }

        body.dark-mode .restore-title {
            color: #f9fafb;
        }

        body.dark-mode .restore-description {
            color: #9ca3af;
        }

        body.dark-mode .alert-danger {
            background-color: #7f1d1d;
            color: #fca5a5;
            border-color: #991b1b;
        }

        body.dark-mode .alert-success {
            background-color: #064e3b;
            color: #6ee7b7;
            border-color: #047857;
        }

        body.dark-mode .alert-warning {
            background-color: #78350f;
            color: #fde68a;
            border-color: #92400e;
        }

        body.dark-mode .warning-notice {
            background-color: rgba(120, 53, 15, 0.3);
            border-color: #92400e;
            color: #fde68a;
        }

        body.dark-mode .modal {
            background-color: rgba(0, 0, 0, 0.7);
        }

        body.dark-mode .modal-content {
            background-color: #1f2937;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        body.dark-mode .modal-icon {
            background-color: rgba(5, 150, 105, 0.2);
            color: #34d399;
        }

        body.dark-mode .modal-title {
            color: #f9fafb;
        }

        body.dark-mode .modal-text {
            color: #9ca3af;
        }

        body.dark-mode .form-label {
            color: #f9fafb;
        }

        body.dark-mode .form-input {
            background-color: #111827;
            color: #f9fafb;
            border-color: #374151;
        }

        body.dark-mode .form-input:focus {
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.2);
        }

        body.dark-mode .toggle-password {
            color: #9ca3af;
        }

        body.dark-mode .toggle-password:hover {
            color: #f9fafb;
        }

        body.dark-mode .file-info {
            background-color: #111827;
            border-color: #374151;
            color: #9ca3af;
        }

        body.dark-mode .btn-success {
            background-color: #059669;
        }

        body.dark-mode .btn-success:hover {
            background-color: #047857;
        }
    </style>
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <h1 class="page-title">Database Restore</h1>
            </div>

            <div class="restore-container">
                <div class="restore-card">
                    <div class="restore-icon-wrapper">
                        <i class="fas fa-upload"></i>
                    </div>

                    <h2 class="restore-title">Restore from SQL Backup</h2>
                    <p class="restore-description">
                        Upload a previously generated <strong>.sql</strong> backup file to restore the system database.
                        All existing data will be overwritten by the backup contents.
                    </p>

                    <div class="warning-notice">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> Restoring a database backup will overwrite existing data. This action cannot be undone.
                        Make sure you have a current backup before proceeding.
                        <br><br>
                        <i class="fas fa-shield-alt"></i>
                        <strong>Security Notice:</strong> You will be required to enter your password to confirm this action.
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <button type="button" id="openRestoreModal" class="btn btn-success btn-lg">
                        <i class="fas fa-upload"></i> Select & Restore SQL File
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Restore Confirmation Modal -->
    <div id="restoreModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h3 class="modal-title">Restore Database</h3>
            </div>

            <div class="modal-body">
                <p class="modal-text">
                    Select a <strong>.sql</strong> backup file and enter your password to confirm the restore operation.
                </p>

                <form method="POST" id="restoreForm" enctype="multipart/form-data">
                    <!-- File Upload -->
                    <div class="form-group">
                        <label for="sql_file" class="form-label">
                            <i class="fas fa-file-code"></i> SQL Backup File
                        </label>
                        <input
                            type="file"
                            id="sql_file"
                            name="sql_file"
                            class="form-input"
                            accept=".sql"
                            required
                            style="padding: 0.5rem 1rem; cursor: pointer;"
                        >
                        <div class="file-info" id="fileInfo">
                            <i class="fas fa-file-alt"></i>
                            <span id="fileInfoText"></span>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-key"></i> Your Password
                        </label>
                        <div class="password-input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancelRestore">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="restore" class="btn-success">
                            <i class="fas fa-upload"></i> Confirm & Restore
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>

    <script>
        const modal = document.getElementById('restoreModal');
        const openModalBtn = document.getElementById('openRestoreModal');
        const cancelBtn = document.getElementById('cancelRestore');
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const sqlFileInput = document.getElementById('sql_file');
        const fileInfo = document.getElementById('fileInfo');
        const fileInfoText = document.getElementById('fileInfoText');

        // Open modal
        openModalBtn.addEventListener('click', function () {
            modal.classList.add('active');
            passwordInput.focus();
        });

        // Close modal
        function closeModal() {
            modal.classList.remove('active');
            passwordInput.value = '';
            sqlFileInput.value = '';
            fileInfo.classList.remove('visible');
            fileInfoText.textContent = '';
        }

        cancelBtn.addEventListener('click', closeModal);

        // Close modal when clicking outside
        window.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('active')) {
                closeModal();
            }
        });

        // Toggle password visibility
        togglePasswordBtn.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Show selected file info
        sqlFileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const sizeMB = (file.size / 1024 / 1024).toFixed(2);
                fileInfoText.textContent = file.name + ' (' + sizeMB + ' MB)';
                fileInfo.classList.add('visible');
            } else {
                fileInfo.classList.remove('visible');
                fileInfoText.textContent = '';
            }
        });

        // Auto-open modal if there was a POST error (file was selected but password wrong, etc.)
        <?php if ($error && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        modal.classList.add('active');
        <?php endif; ?>
    </script>
</body>
</html>
