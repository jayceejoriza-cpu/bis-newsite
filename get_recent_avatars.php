<?php
require_once 'config.php';
require_once 'auth_check.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the 6 most recent avatars for this user
$avatars = [];
$uploadDir = 'uploads/avatars/';

if (file_exists($uploadDir)) {
    // Get all avatar files for this user
    $files = glob($uploadDir . 'avatar_' . $user_id . '_*.*');
    
    if ($files) {
        // Sort by modification time (newest first)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Get only the 6 most recent
        $recentFiles = array_slice($files, 0, 6);
        
        foreach ($recentFiles as $file) {
            $avatars[] = [
                'path' => $file,
                'url' => $file,
                'timestamp' => filemtime($file)
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'avatars' => $avatars
]);
