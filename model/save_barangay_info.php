<?php
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

try {
    // Get form data
    $province_name = trim($_POST['province_name'] ?? '');
    $town_name = trim($_POST['town_name'] ?? '');
    $barangay_name = trim($_POST['barangay_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $dashboard_text = trim($_POST['dashboard_text'] ?? '');
    
    // Validate required fields
    if (empty($province_name) || empty($town_name) || empty($barangay_name)) {
        echo json_encode(['success' => false, 'message' => 'Province, Town, and Barangay names are required']);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_base_dir = '../assets/uploads/barangay';
    $logos_dir = $upload_base_dir . '/logos';
    $dashboard_dir = $upload_base_dir . '/dashboard';
    
    if (!file_exists($logos_dir)) {
        mkdir($logos_dir, 0755, true);
    }
    if (!file_exists($dashboard_dir)) {
        mkdir($dashboard_dir, 0755, true);
    }
    
    // Get current barangay info for existing file paths
    $stmt = $conn->prepare("SELECT municipal_logo, barangay_logo, official_emblem, dashboard_image FROM barangay_info WHERE id = 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $current_info = $result->fetch_assoc();
    $stmt->close();
    
    $municipal_logo_path = $current_info['municipal_logo'] ?? null;
    $barangay_logo_path = $current_info['barangay_logo'] ?? null;
    $official_emblem_path = $current_info['official_emblem'] ?? null;
    $dashboard_image_path = $current_info['dashboard_image'] ?? null;
    
    // Handle Municipal Logo Upload
    if (isset($_FILES['municipal_logo']) && $_FILES['municipal_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['municipal_logo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Municipal logo must be an image file (JPEG, PNG, GIF, or WebP)']);
            exit();
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['success' => false, 'message' => 'Municipal logo file size must not exceed 5MB']);
            exit();
        }
        
        // Delete old file if exists
        if ($municipal_logo_path && file_exists($municipal_logo_path)) {
            unlink($municipal_logo_path);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'municipal_logo_' . time() . '.' . $extension;
        $municipal_logo_path = 'assets/uploads/barangay/logos/' . $filename; // DB path
        
        if (!move_uploaded_file($file['tmp_name'], $logos_dir . '/' . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload municipal logo']);
            exit();
        }
    }
    
    // Handle Barangay Logo Upload
    if (isset($_FILES['barangay_logo']) && $_FILES['barangay_logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['barangay_logo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Barangay logo must be an image file (JPEG, PNG, GIF, or WebP)']);
            exit();
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['success' => false, 'message' => 'Barangay logo file size must not exceed 5MB']);
            exit();
        }
        
        // Delete old file if exists
        if ($barangay_logo_path && file_exists($barangay_logo_path)) {
            unlink($barangay_logo_path);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'barangay_logo_' . time() . '.' . $extension;
        $barangay_logo_path = 'assets/uploads/barangay/logos/' . $filename; // DB path
        
        if (!move_uploaded_file($file['tmp_name'], $logos_dir . '/' . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload barangay logo']);
            exit();
        }
    }
    
    // Handle Official Emblem Upload
    if (isset($_FILES['official_emblem']) && $_FILES['official_emblem']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['official_emblem'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Official emblem must be an image file (JPEG, PNG, GIF, or WebP)']);
            exit();
        }
        
        if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            echo json_encode(['success' => false, 'message' => 'Official emblem file size must not exceed 5MB']);
            exit();
        }
        
        // Delete old file if exists
        if ($official_emblem_path && file_exists($official_emblem_path)) {
            unlink($official_emblem_path);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'official_emblem_' . time() . '.' . $extension;
        $official_emblem_path = 'assets/uploads/barangay/logos/' . $filename; // DB path
        
        if (!move_uploaded_file($file['tmp_name'], $logos_dir . '/' . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload official emblem']);
            exit();
        }
    }
    
    // Handle Dashboard Image Upload
    if (isset($_FILES['dashboard_image']) && $_FILES['dashboard_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['dashboard_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Dashboard image must be an image file (JPEG, PNG, GIF, or WebP)']);
            exit();
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit for dashboard image
            echo json_encode(['success' => false, 'message' => 'Dashboard image file size must not exceed 10MB']);
            exit();
        }
        
        // Delete old file if exists
        if ($dashboard_image_path && file_exists($dashboard_image_path)) {
            unlink($dashboard_image_path);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'dashboard_' . time() . '.' . $extension;
        $dashboard_image_path = 'assets/uploads/barangay/dashboard/' . $filename; // DB path
        
        if (!move_uploaded_file($file['tmp_name'], $dashboard_dir . '/' . $filename)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload dashboard image']);
            exit();
        }
    }
    
    // Update database
    $stmt = $conn->prepare("
        INSERT INTO barangay_info 
        (id, province_name, town_name, barangay_name, contact_number, dashboard_text, 
         municipal_logo, barangay_logo, official_emblem, dashboard_image, updated_by) 
        VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        province_name = VALUES(province_name),
        town_name = VALUES(town_name),
        barangay_name = VALUES(barangay_name),
        contact_number = VALUES(contact_number),
        dashboard_text = VALUES(dashboard_text),
        municipal_logo = VALUES(municipal_logo),
        barangay_logo = VALUES(barangay_logo),
        official_emblem = VALUES(official_emblem),
        dashboard_image = VALUES(dashboard_image),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param(
        "sssssssssi",
        $province_name,
        $town_name,
        $barangay_name,
        $contact_number,
        $dashboard_text,
        $municipal_logo_path,
        $barangay_logo_path,
        $official_emblem_path,
        $dashboard_image_path,
        $user_id
    );
    
    if ($stmt->execute()) {
        // Log activity
        $log_user = $_SESSION['username'];
        $log_action = 'Update Barangay Info';
        $log_desc = "Updated barangay information: $barangay_name, $town_name, $province_name";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user, action, description) VALUES (?, ?, ?)");
        $log_stmt->bind_param("sss", $log_user, $log_action, $log_desc);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Barangay information updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update barangay information: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
