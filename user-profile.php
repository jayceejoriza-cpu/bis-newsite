<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($username)) {
        $error = "Full Name and Username are required.";
    } else {
        // Check if username exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            // Update logic
            if (!empty($password)) {
                if ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, password = ? WHERE id = ?");
                    $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_id);
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
                $stmt->bind_param("ssi", $full_name, $username, $user_id);
            }

            if (empty($error)) {
                if ($stmt->execute()) {
                    $message = "Profile updated successfully!";
                    // Update session
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['username'] = $username;
                } else {
                    $error = "Error updating profile: " . $conn->error;
                }
            }
        }
        $stmt->close();
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo defined('SITE_NAME') ? SITE_NAME : 'BIS'; ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Page specific styles that complement header.php variables */
        .profile-card {
            background-color: var(--bg-surface, #ffffff);
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2.5rem;
            border: 1px solid var(--border-color, #e5e7eb);
            max-width: 800px;
            margin: 2rem auto;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
        }

        .profile-avatar-large {
            width: 100px;
            height: 100px;
            background-color: #e5e7eb;
            color: #9ca3af;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-right: 2rem;
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.875rem;
            font-weight: 700;
            color: var(--text-primary, #111827);
        }

        .profile-info p {
            margin: 0;
            color: var(--text-secondary, #6b7280);
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary, #374151);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color, #d1d5db);
            border-radius: 0.5rem;
            background-color: var(--bg-primary, #f9fafb);
            color: var(--text-primary, #111827);
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            background-color: var(--bg-surface, #ffffff);
        }

        .section-divider {
            border-top: 1px solid var(--border-color, #e5e7eb);
            margin: 2.5rem 0 2rem;
            padding-top: 1.5rem;
        }

        .section-title {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary, #111827);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .section-subtitle {
            color: var(--text-secondary, #6b7280);
            font-size: 0.875rem;
            margin: 0 0 1.5rem 0;
        }

        .btn-primary {
            background-color: #2563eb;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        /* Dark mode overrides for specific elements not covered by vars */
        body.dark-mode .alert-success {
            background-color: rgba(6, 78, 59, 0.5);
            color: #d1fae5;
            border-color: #065f46;
        }
        
        body.dark-mode .alert-danger {
            background-color: rgba(127, 29, 29, 0.5);
            color: #fecaca;
            border-color: #7f1d1d;
        }
        
        body.dark-mode .profile-avatar-large {
            background-color: #374151;
            color: #9ca3af;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
        <div class="profile-card">
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-avatar-large">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p><i class="fas fa-id-badge" style="margin-right: 8px;"></i><?php echo htmlspecialchars($user['role']); ?></p>
                    <p style="margin-top: 5px; font-size: 0.9rem;"><i class="fas fa-circle" style="color: #10b981; font-size: 0.7rem; margin-right: 8px;"></i><?php echo htmlspecialchars($user['status']); ?></p>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="section-divider">
                    <h3 class="section-title">Security</h3>
                    <p class="section-subtitle">Update your password to keep your account secure.</p>
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
        </div>
    </main>

    <!-- Custom JavaScript -->
    <script src="js/script.js"></script>

</body>
</html>