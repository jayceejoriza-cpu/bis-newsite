<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['avatar_path'])) {
        $response['message'] = 'Avatar path is required.';
        echo json_encode($response);
        exit();
    }
    
    $avatarPath = $data['avatar_path'];
    
    // Verify the file exists and belongs to this user
    if (!file_exists($avatarPath)) {
        $response['message'] = 'Avatar file not found.';
        echo json_encode($response);
        exit();
    }
    
    // Verify the file belongs to this user
    if (strpos($avatarPath, 'avatar_' . $user_id . '_') === false) {
        $response['message'] = 'Unauthorized access to avatar.';
        echo json_encode($response);
        exit();
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $avatarPath, $user_id);
    
    if ($stmt->execute()) {
        // Log activity
        $username = $_SESSION['username'];
        $log_action = 'Update Profile Image';
        $log_desc = 'User selected a previous profile image';
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $username, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
        
        $response['success'] = true;
        $response['message'] = 'Profile image updated successfully!';
        $response['avatar_url'] = $avatarPath;
    } else {
        $response['message'] = 'Database update failed: ' . $conn->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
