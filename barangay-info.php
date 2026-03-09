<?php
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Fetch current barangay info
$barangay_info = null;
$stmt = $conn->prepare("SELECT * FROM barangay_info WHERE id = 1 LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $barangay_info = $result->fetch_assoc();
}
$stmt->close();

// If no record exists, create default
if (!$barangay_info) {
    $conn->query("INSERT INTO barangay_info (id, province_name, town_name, barangay_name) VALUES (1, 'Province Name', 'Town/City Name', 'Barangay Name')");
    $barangay_info = [
        'id' => 1,
        'province_name' => 'Province Name',
        'town_name' => 'Town/City Name',
        'barangay_name' => 'Barangay Name',
        'contact_number' => '',
        'dashboard_text' => '',
        'municipal_logo' => null,
        'barangay_logo' => null,
        'official_emblem' => null,
        'dashboard_image' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dark Mode Init: must be at the very top of <head> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>

    <title>Barangay Info - <?php echo defined('SITE_NAME') ? SITE_NAME : 'BIS'; ?></title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        .barangay-info-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .info-card {
            background-color: var(--bg-secondary);
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            padding: 1.25rem;
            margin-bottom: 1.25rem;
            border: 1px solid var(--border-color);
            transition: var(--color-transition);
        }
        
        .card-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
            transition: var(--color-transition);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            transition: var(--color-transition);
        }
        
        .form-label.required::after {
            content: ' *';
            color: var(--danger-color);
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: var(--color-transition);
            box-sizing: border-box;
            font-family: inherit;
        }
        
        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .logo-upload-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .logo-upload-box {
            text-align: center;
        }
        
        .logo-preview-container {
            width: 100%;
            height: 150px;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            background-color: var(--bg-primary);
            transition: var(--color-transition);
            position: relative;
            overflow: hidden;
        }
        
        .logo-preview-container:hover {
            border-color: var(--primary-color);
        }
        
        .logo-preview {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .logo-placeholder {
            color: var(--text-secondary);
            font-size: 2.5rem;
            transition: var(--color-transition);
        }
        
        .dashboard-image-preview-container {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
            background-color: var(--bg-primary);
            transition: var(--color-transition);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-image-preview-container:hover {
            border-color: var(--primary-color);
        }
        
        .dashboard-image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }
        
        .file-input {
            display: none;
        }
        
        .file-input-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.6rem 1.25rem;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            transition: var(--color-transition);
            font-size: 0.85rem;
            font-weight: 500;
            width: 100%;
        }
        
        .file-input-label:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .file-name {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
            transition: var(--color-transition);
        }
        
        .btn-save {
            padding: 0.65rem 1.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--color-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-save:hover {
            background-color: #2563eb;
            box-shadow: var(--shadow-md);
        }
        
        .btn-save:active {
            transform: scale(0.98);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-color);
        }
        
        /* Dark mode styles */
        html.dark-mode .info-card {
            background-color: #1f2937;
            border-color: #374151;
        }
        
        html.dark-mode .card-title {
            color: #f9fafb;
            border-bottom-color: #374151;
        }
        
        html.dark-mode .form-label {
            color: #f9fafb;
        }
        
        html.dark-mode .form-input,
        html.dark-mode .form-textarea {
            background-color: #111827;
            color: #f9fafb;
            border-color: #374151;
        }
        
        html.dark-mode .form-input:focus,
        html.dark-mode .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
        
        html.dark-mode .logo-preview-container,
        html.dark-mode .dashboard-image-preview-container {
            background-color: #111827;
            border-color: #374151;
        }
        
        html.dark-mode .logo-preview-container:hover,
        html.dark-mode .dashboard-image-preview-container:hover {
            border-color: var(--primary-color);
        }
        
        html.dark-mode .logo-placeholder {
            color: #6b7280;
        }
        
        html.dark-mode .file-input-label {
            background-color: #111827;
            color: #f9fafb;
            border-color: #374151;
        }
        
        html.dark-mode .file-input-label:hover {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        html.dark-mode .file-name {
            color: #9ca3af;
        }
        
        html.dark-mode .alert-success {
            background-color: #064e3b;
            color: #6ee7b7;
            border-color: #047857;
        }
        
        html.dark-mode .alert-danger {
            background-color: #7f1d1d;
            color: #fca5a5;
            border-color: #dc2626;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .logo-upload-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <h1 class="page-title">Update Barangay Info</h1>
                <p class="page-subtitle">Manage barangay information and branding</p>
            </div>
            
            <div class="barangay-info-container">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>
                
                <form id="barangayInfoForm" method="POST" enctype="multipart/form-data">
                    <!-- Basic Information Card -->
                    <div class="info-card">
                        <h2 class="card-title">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="province_name" class="form-label required">Province Name</label>
                                <input 
                                    type="text" 
                                    id="province_name" 
                                    name="province_name" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($barangay_info['province_name'] ?? ''); ?>"
                                    placeholder="Enter province name"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="town_name" class="form-label required">Town Name</label>
                                <input 
                                    type="text" 
                                    id="town_name" 
                                    name="town_name" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($barangay_info['town_name'] ?? ''); ?>"
                                    placeholder="Enter town/city name"
                                    required
                                >
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="barangay_name" class="form-label required">Barangay Name</label>
                                <input 
                                    type="text" 
                                    id="barangay_name" 
                                    name="barangay_name" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($barangay_info['barangay_name'] ?? ''); ?>"
                                    placeholder="Enter barangay name"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input 
                                    type="text" 
                                    id="contact_number" 
                                    name="contact_number" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($barangay_info['contact_number'] ?? ''); ?>"
                                    placeholder="Enter contact number"
                                >
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="dashboard_text" class="form-label">Dashboard Text</label>
                            <textarea 
                                id="dashboard_text" 
                                name="dashboard_text" 
                                class="form-textarea" 
                                placeholder="Enter dashboard welcome text or description"
                            ><?php echo htmlspecialchars($barangay_info['dashboard_text'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Logos Card -->
                    <div class="info-card">
                        <h2 class="card-title">
                            <i class="fas fa-image"></i> Logos
                        </h2>
                        
                        <div class="logo-upload-section">
                            <!-- Municipal Logo -->
                            <div class="logo-upload-box">
                                <label class="form-label">Municipality/City Logo</label>
                                <div class="logo-preview-container" id="municipalLogoPreview">
                                    <?php if (!empty($barangay_info['municipal_logo']) && file_exists($barangay_info['municipal_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($barangay_info['municipal_logo']); ?>?v=<?php echo time(); ?>" alt="Municipal Logo" class="logo-preview">
                                    <?php else: ?>
                                        <i class="fas fa-building logo-placeholder"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="file-input-wrapper">
                                    <input 
                                        type="file" 
                                        id="municipal_logo" 
                                        name="municipal_logo" 
                                        class="file-input" 
                                        accept="image/*"
                                        onchange="previewImage(this, 'municipalLogoPreview')"
                                    >
                                    <label for="municipal_logo" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        <span>Choose File</span>
                                    </label>
                                    <span class="file-name" id="municipal_logoName">No file chosen</span>
                                </div>
                            </div>
                            
                            <!-- Barangay Logo -->
                            <div class="logo-upload-box">
                                <label class="form-label">Barangay Logo</label>
                                <div class="logo-preview-container" id="barangayLogoPreview">
                                    <?php if (!empty($barangay_info['barangay_logo']) && file_exists($barangay_info['barangay_logo'])): ?>
                                        <img src="<?php echo htmlspecialchars($barangay_info['barangay_logo']); ?>?v=<?php echo time(); ?>" alt="Barangay Logo" class="logo-preview">
                                    <?php else: ?>
                                        <i class="fas fa-flag logo-placeholder"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="file-input-wrapper">
                                    <input 
                                        type="file" 
                                        id="barangay_logo" 
                                        name="barangay_logo" 
                                        class="file-input" 
                                        accept="image/*"
                                        onchange="previewImage(this, 'barangayLogoPreview')"
                                    >
                                    <label for="barangay_logo" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        <span>Choose File</span>
                                    </label>
                                    <span class="file-name" id="barangay_logoName">No file chosen</span>
                                </div>
                            </div>
                            
                            <!-- Official Emblem -->
                            <div class="logo-upload-box">
                                <label class="form-label">Official Emblem</label>
                                <div class="logo-preview-container" id="officialEmblemPreview">
                                    <?php if (!empty($barangay_info['official_emblem']) && file_exists($barangay_info['official_emblem'])): ?>
                                        <img src="<?php echo htmlspecialchars($barangay_info['official_emblem']); ?>?v=<?php echo time(); ?>" alt="Official Emblem" class="logo-preview">
                                    <?php else: ?>
                                        <i class="fas fa-medal logo-placeholder"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="file-input-wrapper">
                                    <input 
                                        type="file" 
                                        id="official_emblem" 
                                        name="official_emblem" 
                                        class="file-input" 
                                        accept="image/*"
                                        onchange="previewImage(this, 'officialEmblemPreview')"
                                    >
                                    <label for="official_emblem" class="file-input-label">
                                        <i class="fas fa-upload"></i>
                                        <span>Choose File</span>
                                    </label>
                                    <span class="file-name" id="official_emblemName">No file chosen</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Login Background Image Card -->
                    <div class="info-card">
                        <h2 class="card-title">
                            <i class="fas fa-panorama"></i> Login Background Image
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label">Login Page Background Image</label>
                            <div class="dashboard-image-preview-container" id="dashboardImagePreview">
                                <?php if (!empty($barangay_info['dashboard_image']) && file_exists($barangay_info['dashboard_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($barangay_info['dashboard_image']); ?>?v=<?php echo time(); ?>" alt="Login Background Image" class="dashboard-image-preview">
                                <?php else: ?>
                                    <i class="fas fa-image logo-placeholder"></i>
                                <?php endif; ?>
                            </div>
                            <div class="file-input-wrapper">
                                <input 
                                    type="file" 
                                    id="dashboard_image" 
                                    name="dashboard_image" 
                                    class="file-input" 
                                    accept="image/*"
                                    onchange="previewImage(this, 'dashboardImagePreview', true)"
                                >
                                <label for="dashboard_image" class="file-input-label">
                                    <i class="fas fa-upload"></i>
                                    <span>Choose File</span>
                                </label>
                                <span class="file-name" id="dashboard_imageName">No file chosen</span>
                            </div>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem; margin-bottom: 0;">
                                <i class="fas fa-info-circle"></i> This image will be used as the background for the login page
                            </p>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    
    <script>
        // Image preview function
        function previewImage(input, previewContainerId, isDashboard = false) {
            const container = document.getElementById(previewContainerId);
            const fileNameSpan = document.getElementById(input.id + 'Name');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileNameSpan.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (isDashboard) {
                        container.innerHTML = `<img src="${e.target.result}" alt="Preview" class="dashboard-image-preview">`;
                    } else {
                        container.innerHTML = `<img src="${e.target.result}" alt="Preview" class="logo-preview">`;
                    }
                };
                reader.readAsDataURL(file);
            } else {
                fileNameSpan.textContent = 'No file chosen';
            }
        }
        
        // Form submission
        document.getElementById('barangayInfoForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.btn-save');
            const originalBtnText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            try {
                const response = await fetch('model/save_barangay_info.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    window.scrollTo(0, 0);
                    location.reload();
                } else {
                    // Show error message
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('An error occurred while saving. Please try again.');
                console.error('Error:', error);
            } finally {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    </script>
</body>
</html>
