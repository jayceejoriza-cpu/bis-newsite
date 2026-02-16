<?php
require_once 'config.php';

echo "<h2>Login Background Debug Info</h2>";

// Check database
$stmt = $conn->prepare("SELECT dashboard_image FROM barangay_info WHERE id = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $barangay_data = $result->fetch_assoc();
    $dashboard_image = $barangay_data['dashboard_image'];
    
    echo "<h3>Database Value:</h3>";
    echo "<p><strong>dashboard_image:</strong> " . htmlspecialchars($dashboard_image ?? 'NULL') . "</p>";
    
    if (!empty($dashboard_image)) {
        echo "<h3>File Check:</h3>";
        echo "<p><strong>File exists:</strong> " . (file_exists($dashboard_image) ? "✓ Yes" : "✗ No") . "</p>";
        echo "<p><strong>Full path:</strong> " . realpath($dashboard_image) . "</p>";
        echo "<p><strong>Is readable:</strong> " . (is_readable($dashboard_image) ? "✓ Yes" : "✗ No") . "</p>";
        
        if (file_exists($dashboard_image)) {
            echo "<h3>Image Preview:</h3>";
            echo "<p>Trying to load: " . htmlspecialchars($dashboard_image) . "</p>";
            echo "<img src='" . htmlspecialchars($dashboard_image) . "?v=" . time() . "' style='max-width: 400px; border: 2px solid #ccc;' onerror='this.style.border=\"2px solid red\"; this.alt=\"Image failed to load\";'>";
            
            echo "<h3>CSS that would be generated:</h3>";
            echo "<pre>";
            echo htmlspecialchars("body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('" . $dashboard_image . "');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: -2;
}");
            echo "</pre>";
        }
    } else {
        echo "<p style='color: red;'>No dashboard_image value in database</p>";
    }
} else {
    echo "<p style='color: red;'>No barangay_info record found</p>";
}

$stmt->close();

echo "<hr>";
echo "<p><a href='login.php'>View Login Page</a> | <a href='barangay-info.php'>Go to Barangay Info</a></p>";
?>
