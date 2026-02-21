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
    
    // Check if file was uploaded
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'No file uploaded or upload error occurred.';
        echo json_encode($response);
        exit();
    }
    
    $file = $_FILES['avatar'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    // Get file extension
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validate file type
    if (!in_array($fileExt, $allowed)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.';
        echo json_encode($response);
        exit();
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($fileSize > $maxSize) {
        $response['message'] = 'File size too large. Maximum size is 5MB.';
        echo json_encode($response);
        exit();
    }
    
    // Validate image dimensions and type
    $imageInfo = getimagesize($fileTmpName);
    if ($imageInfo === false) {
        $response['message'] = 'Invalid image file.';
        echo json_encode($response);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = '../assets/uploads/avatars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $newFileName = 'avatar_' . $user_id . '_' . time() . '.' . $fileExt;
    $uploadPath = 'assets/uploads/avatars/' . $newFileName; // DB path
    
    // Get user's current avatar to delete old one
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $oldAvatar = $user['profile_image'];
    $stmt->close();
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $uploadDir . $newFileName)) {
        
        // Update database
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $uploadPath, $user_id);
        
        if ($stmt->execute()) {
            // Keep old avatars for "Recent Avatars" feature
            // Only delete if we have more than 10 avatars to prevent unlimited storage
            $allAvatars = glob($uploadDir . 'avatar_' . $user_id . '_*.*');
            if (count($allAvatars) > 10) {
                // Sort by modification time (oldest first)
                usort($allAvatars, function($a, $b) {
                    return filemtime($a) - filemtime($b);
                });
                // Delete oldest avatars, keeping only 10 most recent
                $toDelete = array_slice($allAvatars, 0, count($allAvatars) - 10);
                foreach ($toDelete as $oldFile) {
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
            }
            
            // Log activity
            $username = $_SESSION['username'];
            $log_action = 'Update Profile Image';
            $log_desc = 'User updated their profile image';
            $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
            $log_stmt->bind_param("sss", $username, $log_action, $log_desc);
            $log_stmt->execute();
            $log_stmt->close();
            
            $response['success'] = true;
            $response['message'] = 'Profile image updated successfully!';
            $response['avatar_url'] = $uploadPath;
        } else {
            $response['message'] = 'Database update failed: ' . $conn->error;
            // Delete uploaded file if database update fails
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
        }
        
        $stmt->close();
    } else {
        $response['message'] = 'Failed to upload file.';
    }
    
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
