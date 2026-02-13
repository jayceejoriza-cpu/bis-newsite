<?php
require_once 'config.php';

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

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
    <link rel="stylesheet" href="login.css">
    <style>
        /* Dark Mode Toggle Button */
        .dark-mode-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            color: #4b5563;
            transition: color 0.3s ease;
            z-index: 1000;
        }
        
        .dark-mode-toggle:hover {
            color: #111827;
        }

        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #111827;
            color: #f3f4f6;
        }
        
        body.dark-mode .dark-mode-toggle {
            color: #fbbf24;
        }
        
        body.dark-mode .dark-mode-toggle:hover {
            color: #f59e0b;
        }
        
        body.dark-mode .login-card {
            background-color: #1f2937;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
        }
        
        body.dark-mode h2, 
        body.dark-mode h3,
        body.dark-mode label {
            color: #f3f4f6;
        }
        
        body.dark-mode .subtitle,
        body.dark-mode .text-muted {
            color: #9ca3af !important;
        }
        
        body.dark-mode .input-wrapper {
            background-color: #374151;
            border: 1px solid #4b5563;
        }
        
        body.dark-mode .input-wrapper input {
            color: #f3f4f6;
            background-color: transparent;
        }
        
        body.dark-mode .input-wrapper input::placeholder {
            color: #9ca3af;
        }
        
        body.dark-mode .input-icon {
            color: #9ca3af;
        }
    </style>
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
