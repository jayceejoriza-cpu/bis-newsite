<?php
require_once 'config.php';
require_once 'auth_check.php';

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
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($username)) {
        $error = "Full Name and Username are required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->bind_param("si", $username, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already taken.";
        } else {
            if (!empty($password)) {
                if ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } else {
                    // Check if new password is the same as current password
                    $current_password_stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $current_password_stmt->bind_param("i", $user_id);
                    $current_password_stmt->execute();
                    $current_password_result = $current_password_stmt->get_result();
                    $current_user_data = $current_password_result->fetch_assoc();
                    $current_password_stmt->close();
                    
                    if (password_verify($password, $current_user_data['password'])) {
                        $error = "The new password cannot be the same as your current password.";
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ?, password = ? WHERE id = ?");
                        $stmt->bind_param("sssi", $full_name, $username, $hashed_password, $user_id);
                    }
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
                $stmt->bind_param("ssi", $full_name, $username, $user_id);
            }

            if (empty($error)) {
                if ($stmt->execute()) {
                    $message = "Profile updated successfully!";
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['username'] = $username;

                    $log_user = $username;
                    $log_action = 'Update Profile';
                    $log_desc = 'User updated their profile information';
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                    $log_stmt->execute();
                    $log_stmt->close();
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

// Get last login from activity logs (excluding current session)
$last_login = null;
$stmt = $conn->prepare("SELECT timestamp FROM activity_logs WHERE user = ? AND action = 'Login' ORDER BY timestamp DESC LIMIT 1, 1");
$stmt->bind_param("s", $user['username']);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $login_data = $result->fetch_assoc();
    $last_login = $login_data['timestamp'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dark Mode Init: must be at the very top of <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>

    <title>User Profile - <?php echo defined('SITE_NAME') ? SITE_NAME : 'BIS'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* Profile Container */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Profile Banner */
        .profile-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 200px;
            border-radius: 12px 12px 0 0;
            position: relative;
            overflow: hidden;
        }

        .profile-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        /* Profile Card */
        .profile-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 2rem;
            transition: var(--color-transition);
            overflow: hidden;
        }

        /* Profile Header */
        .profile-header {
            padding: 0 2.5rem 2rem;
            margin-top: -60px;
            position: relative;
        }

        .profile-header-content {
            display: flex;
            align-items: flex-start;
            gap: 2rem;
            padding-top: 1.5rem;
        }

        .profile-avatar-container {
            position: relative;
            flex-shrink: 0;
        }

        .profile-avatar-large {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 600;
            border: 5px solid var(--bg-secondary);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            transition: var(--color-transition), transform 0.3s ease;
            overflow: hidden;
        }

        .profile-avatar-large:hover {
            transform: scale(1.05);
        }

        .profile-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .change-avatar-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            background-color: #3b82f6;
            color: white;
            border: 4px solid var(--bg-secondary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4);
        }

        .change-avatar-btn:hover {
            background-color: #2563eb;
            transform: scale(1.1);
        }

        .profile-info {
            flex: 1;
            padding-bottom: 0.5rem;
            padding-top: 4rem;
        }

        .profile-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            transition: var(--color-transition);
        }

        .profile-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 0.75rem;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.95rem;
            transition: var(--color-transition);
        }

        .profile-meta-item i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.9rem;
            background-color: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            transition: var(--color-transition);
        }

        .status-badge i {
            font-size: 0.7rem;
        }

        html.dark-mode .status-badge {
            background-color: rgba(6, 78, 59, 0.3);
            color: #6ee7b7;
        }

        /* Profile Stats */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem 2.5rem;
            background-color: var(--bg-primary);
            border-top: 1px solid var(--border-color);
            transition: var(--color-transition);
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            transition: var(--color-transition);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            transition: var(--color-transition);
        }

        /* Profile Content */
        .profile-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .content-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: var(--color-transition);
        }

        .content-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            transition: var(--color-transition);
        }

        .content-card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
        }

        .content-card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            transition: var(--color-transition);
        }

        .content-card-subtitle {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
            transition: var(--color-transition);
        }

        /* Form Styles */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: var(--color-transition);
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: var(--primary-color);
            font-size: 0.875rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: var(--color-transition), border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background-color: var(--bg-secondary);
        }

        .form-control:disabled {
            background-color: var(--bg-primary);
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background-color: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            transition: var(--color-transition);
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        .password-strength-bar.weak {
            width: 33%;
            background-color: #ef4444;
        }

        .password-strength-bar.medium {
            width: 66%;
            background-color: #f59e0b;
        }

        .password-strength-bar.strong {
            width: 100%;
            background-color: #10b981;
        }

        .password-hint {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            transition: var(--color-transition);
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--color-transition);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        body.dark-mode .alert-success {
            background-color: rgba(6, 78, 59, 0.3);
            color: #6ee7b7;
            border-color: #065f46;
        }
        
        body.dark-mode .alert-danger {
            background-color: rgba(127, 29, 29, 0.3);
            color: #fca5a5;
            border-color: #991b1b;
        }

        /* Button Styles */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            padding: 0.875rem 2rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--color-transition), transform 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background-color: var(--bg-secondary);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            transition: var(--color-transition);
        }

        /* Info Box */
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            transition: var(--color-transition);
        }

        .info-box i {
            color: #3b82f6;
            margin-right: 0.5rem;
        }

        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 0.9rem;
        }

        html.dark-mode .info-box {
            background-color: rgba(59, 130, 246, 0.1);
            border-left-color: #60a5fa;
        }

        html.dark-mode .info-box p {
            color: #93c5fd;
        }

        html.dark-mode .info-box i {
            color: #60a5fa;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-banner {
                height: 150px;
            }

            .profile-header {
                padding: 0 1.5rem 1.5rem;
                margin-top: -50px;
            }

            .profile-header-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .profile-avatar-large {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }

            .profile-info h1 {
                font-size: 1.5rem;
            }

            .profile-meta {
                justify-content: center;
            }

            .profile-stats {
                padding: 1.5rem;
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }

            .content-card {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        /* Avatar Modal */
        .avatar-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
        }

        .avatar-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-modal-content {
            background-color: var(--bg-secondary);
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            transition: var(--color-transition);
        }

        .avatar-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--color-transition);
        }

        .avatar-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
            transition: var(--color-transition);
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: 6px;
            transition: var(--color-transition);
        }

        .close-modal-btn:hover {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .avatar-modal-body {
            padding: 1.5rem;
            max-height: calc(90vh - 80px);
            overflow-y: auto;
        }

        .upload-area {
            background-color: var(--bg-primary);
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--color-transition), border-color 0.3s ease;
        }

        .upload-area:hover {
            border-color: #3b82f6;
        }

        .upload-area i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .upload-area p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .recent-avatars-section {
            margin-top: 2rem;
        }

        .recent-avatars-title {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .recent-avatars-subtitle {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .recent-avatars-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.75rem;
        }

        .recent-avatar-item {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid var(--border-color);
            transition: transform 0.2s ease, border-color 0.3s ease, box-shadow 0.2s ease;
        }

        .recent-avatar-item:hover {
            transform: scale(1.1);
            border-color: #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }

        .recent-avatar-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 768px) {
            .recent-avatars-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 480px) {
            .recent-avatars-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Image Cropper Modal */
        .crop-modal {
            display: none;
            position: fixed;
            z-index: 2001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
        }

        .crop-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .crop-modal-content {
            background-color: #2b2d31;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
        }

        .crop-modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #3f4147;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #2b2d31;
        }

        .crop-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: #f2f3f5;
        }

        .crop-close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #b5bac1;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .crop-close-btn:hover {
            background-color: #3f4147;
            color: #f2f3f5;
        }

        .crop-modal-body {
            padding: 1.5rem;
            background-color: #313338;
        }

        .crop-container {
            position: relative;
            width: 100%;
            height: 400px;
            background-color: #1e1f22;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .crop-container img {
            max-width: 100%;
            display: block;
        }

        /* Circular crop overlay */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50%;
        }

        .cropper-view-box {
            box-shadow: 0 0 0 1px #39aef0;
            outline: 0;
        }

        .crop-controls {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .zoom-control {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .zoom-control label {
            color: #b5bac1;
            font-size: 0.875rem;
            font-weight: 500;
            min-width: 60px;
        }

        .zoom-slider {
            flex: 1;
            height: 4px;
            border-radius: 2px;
            background: #3f4147;
            outline: none;
            -webkit-appearance: none;
        }

        .zoom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #5865f2;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .zoom-slider::-webkit-slider-thumb:hover {
            background: #4752c4;
        }

        .zoom-slider::-moz-range-thumb {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #5865f2;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .zoom-slider::-moz-range-thumb:hover {
            background: #4752c4;
        }

        .zoom-value {
            color: #f2f3f5;
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 45px;
            text-align: right;
        }

        .crop-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            padding-top: 1rem;
            border-top: 1px solid #3f4147;
        }

        .crop-btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .crop-btn-reset {
            background-color: transparent;
            color: #f2f3f5;
        }

        .crop-btn-reset:hover {
            background-color: #3f4147;
        }

        .crop-btn-cancel {
            background-color: transparent;
            color: #f2f3f5;
        }

        .crop-btn-cancel:hover {
            background-color: #3f4147;
        }

        .crop-btn-apply {
            background-color: #5865f2;
            color: #ffffff;
        }

        .crop-btn-apply:hover {
            background-color: #4752c4;
        }

        .crop-btn-apply:disabled {
            background-color: #3f4147;
            color: #6d6f78;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .crop-container {
                height: 300px;
            }

            .crop-modal-content {
                width: 95%;
            }

            .crop-actions {
                flex-direction: column;
            }

            .crop-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'components/header.php'; ?>
        
        <div class="dashboard-content">
            <div class="profile-container">
                <!-- Profile Card with Banner -->
                <div class="profile-card">
                    <!-- Profile Banner -->
                    <div class="profile-banner"></div>
                    
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-header-content">
                            <div class="profile-avatar-container">
                                <div class="profile-avatar-large">
                                    <?php if (!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile">
                                    <?php else: ?>
                                        <i class="fas fa-user"></i>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="change-avatar-btn" id="changeAvatarBtn" title="Change Avatar">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="profile-info">
                                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                                <div class="profile-meta">
                                    <div class="profile-meta-item">
                                        <i class="fas fa-id-badge"></i>
                                        <span><?php echo htmlspecialchars($user['role']); ?></span>
                                    </div>
                                    <div class="profile-meta-item">
                                        <i class="fas fa-user-circle"></i>
                                        <span>@<?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                    <span class="status-badge">
                                        <i class="fas fa-circle"></i>
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Stats -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-label">Role</div>
                            <div class="stat-value"><?php echo htmlspecialchars($user['role']); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Member Since</div>
                            <div class="stat-value">
                                <?php 
                                if (!empty($user['created_at'])) {
                                    $created = new DateTime($user['created_at']);
                                    echo $created->format('M Y');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Last Login</div>
                            <div class="stat-value">
                                <?php 
                                if (!empty($last_login)) {
                                    $lastLoginDate = new DateTime($last_login);
                                    echo $lastLoginDate->format('M d, Y');
                                } else {
                                    echo 'First Login';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <!-- Account Information Card -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon">
                                <i class="fas fa-user-edit"></i>
                            </div>
                            <div>
                                <h3 class="content-card-title">Account Information</h3>
                                <p class="content-card-subtitle">Update your personal details and account settings</p>
                            </div>
                        </div>

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

                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">
                                        <i class="fas fa-user"></i>
                                        Full Name
                                    </label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="username">
                                        <i class="fas fa-at"></i>
                                        Username
                                    </label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-shield-alt"></i>
                                        Role
                                    </label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                                </div>

                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-toggle-on"></i>
                                        Status
                                    </label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['status']); ?>" disabled>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="window.location.reload()">
                                    <i class="fas fa-undo"></i>
                                    Reset
                                </button>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-save"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security Settings Card -->
                    <div class="content-card">
                        <div class="content-card-header">
                            <div class="content-card-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <h3 class="content-card-title">Security Settings</h3>
                                <p class="content-card-subtitle">Manage your password and account security</p>
                            </div>
                        </div>

                        <div class="info-box">
                            <i class="fas fa-info-circle"></i>
                            <p>For your security, we recommend using a strong password with at least 8 characters, including uppercase, lowercase, numbers, and special characters.</p>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">

                            <div class="form-group">
                                <label for="password">
                                    <i class="fas fa-key"></i>
                                    New Password
                                </label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <p class="password-hint" id="passwordHint">Password strength: <span id="strengthText">Not set</span></p>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">
                                    <i class="fas fa-check-circle"></i>
                                    Confirm New Password
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your new password">
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-shield-alt"></i>
                                    Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Avatar Modal -->
    <div class="avatar-modal" id="avatarModal">
        <div class="avatar-modal-content">
            <div class="avatar-modal-header">
                <h3 class="avatar-modal-title">Select an Image</h3>
                <button type="button" class="close-modal-btn" id="closeAvatarModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="avatar-modal-body">
                <div class="upload-section">
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Upload Image</p>
                    </div>
                    <input type="file" id="avatarUploadInput" accept="image/*" style="display: none;">
                </div>

                <div class="recent-avatars-section">
                    <h4 class="recent-avatars-title">Recent Avatars</h4>
                    <p class="recent-avatars-subtitle">Access your 6 most recent avatar uploads.</p>
                    <div class="recent-avatars-grid" id="recentAvatarsGrid">
                        <!-- Recent avatars will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Crop Modal -->
    <div class="crop-modal" id="cropModal">
        <div class="crop-modal-content">
            <div class="crop-modal-header">
                <h3 class="crop-modal-title">Edit Image</h3>
                <button type="button" class="crop-close-btn" id="closeCropModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="crop-modal-body">
                <div class="crop-container">
                    <img id="cropImage" src="" alt="Image to crop">
                </div>
                <div class="crop-controls">
                    <div class="zoom-control">
                        <label for="zoomSlider">
                            <i class="fas fa-search-plus"></i> Zoom
                        </label>
                        <input type="range" id="zoomSlider" class="zoom-slider" min="0" max="100" value="0" step="1">
                        <span class="zoom-value" id="zoomValue">100%</span>
                    </div>
                </div>
                <div class="crop-actions">
                    <button type="button" class="crop-btn crop-btn-reset" id="resetCropBtn">
                        <i class="fas fa-undo"></i>
                        Reset
                    </button>
                    <button type="button" class="crop-btn crop-btn-cancel" id="cancelCropBtn">
                        Cancel
                    </button>
                    <button type="button" class="crop-btn crop-btn-apply" id="applyCropBtn">
                        <i class="fas fa-check"></i>
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/user-profile.js"></script>
</body>
</html>
