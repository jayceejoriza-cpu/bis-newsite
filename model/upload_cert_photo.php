<?php
require_once '../config.php';
require_once '../auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cert_id = $_POST['cert_id'] ?? '';
    
    // Validate cert_id
    if (empty($cert_id) || !preg_match('/^[a-zA-Z0-9_-]+$/', $cert_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid certificate ID']);
        exit;
    }

    $uploadDir = '../assets/uploads/certificates/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['cert_photo']) && $_FILES['cert_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['cert_photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
            exit;
        }

        $dest = $uploadDir . $cert_id . '.jpg';
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => true, 'message' => 'Photo uploaded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
