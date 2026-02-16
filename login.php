<?php
require_once 'config.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Fetch barangay info for login background
$login_bg_image = null;
$stmt = $conn->prepare("SELECT dashboard_image FROM barangay_info WHERE id = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $barangay_data = $result->fetch_assoc();
    if (!empty($barangay_data['dashboard_image']) && file_exists($barangay_data['dashboard_image'])) {
        $login_bg_image = $barangay_data['dashboard_image'];
    }
}
$stmt->close();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Check if account is Active
            if ($user['status'] !== 'Active') {
                $error = "Your account is inactive. Please contact administrator.";
            }
            // Verify password
            elseif (password_verify($password, $user['password'])) {

                // Store session data
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Log Activity
                $log_user = $user['username'];
                $log_action = 'Login';
                $log_desc = 'User logged in successfully';
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
                $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
                $log_stmt->execute();
                $log_stmt->close();

                header("Location: index.php");
                exit();
            } 
            else {
                $error = "Invalid password.";
            }

        } else {
            $error = "User not found.";
        }

        $stmt->close();
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login-style.css">
    <?php if ($login_bg_image): ?>
    <style>
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?php echo htmlspecialchars($login_bg_image); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            z-index: -2;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        
        body.dark-mode::after {
            background-color: rgba(0, 0, 0, 0.7);
        }
    </style>
    <?php endif; ?>
</head>
<body>

<button id="darkModeToggle" class="dark-mode-toggle" title="Toggle Dark Mode">
    <i class="fas fa-moon"></i>
</button>

<div class="login-card">

    <div class="logo-section">
        <img src="image/brgylogo.jpg" alt="Logo">
        <div>
            <h3>BARANGGAY WAWANDUE, TARIMA NI MIMON</h3>
            <p class="text-muted">Barangay Information System</p>
        </div>
    </div>

    <h2>Sign In</h2>
    <p class="subtitle">Please login to your account</p>

    <form method="POST">

        <div class="input-group">
            <label>Username</label>
            <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
        </div>

        <div class="input-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <button type="submit" class="btn">Sign In</button>

    </form>

</div>

<script>
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    const icon = toggleBtn.querySelector('i');

    // Check for saved user preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        enableDarkMode();
    }

    toggleBtn.addEventListener('click', () => {
        if (body.classList.contains('dark-mode')) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    });

    function enableDarkMode() {
        body.classList.add('dark-mode');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        localStorage.setItem('theme', 'dark');
    }

    function disableDarkMode() {
        body.classList.remove('dark-mode');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        localStorage.setItem('theme', 'light');
    }
</script>

</body>
</html>
