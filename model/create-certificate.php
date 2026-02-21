<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Page title
$pageTitle = 'Create Certificate';

// ============================================
// Check if editing existing certificate
// ============================================
$certificateData = null;
$isEditing = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $certificateId = intval($_GET['id']);
    $isEditing = true;
    $pageTitle = 'Edit Certificate';
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        
        // Fetch certificate data
        $stmt = $pdo->prepare("
            SELECT 
                id,
                title,
                description,
                fee,
                status,
                pdf_path,
                fields,
                date_issued,
                date_expired,
                created_at,
                updated_at
            FROM certificates
            WHERE id = ?
        ");
        
        $stmt->execute([$certificateId]);
        $certificateData = $stmt->fetch();
        
        if (!$certificateData) {
            // Certificate not found, redirect back
            header('Location: ../certificates.php');
            exit;
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching certificate: " . $e->getMessage());
        header('Location: ../certificates.php');
        exit;
    }
}
?>
=======
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- PDF.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/create-certificate.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include '../components/header.php'; ?>
        
        <!-- Create Certificate Content -->
        <div class="create-certificate-wrapper">
            <!-- Top Action Bar -->
            <div class="certificate-top-bar">
                <div class="top-bar-left">
                    <button class="btn-back" onclick="window.location.href='../certificates.php'">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h2 class="page-title-inline"><?php echo $isEditing ? 'Edit' : 'Create'; ?> Certificate</h2>
                    <?php if ($isEditing): ?>
                    <input type="hidden" id="certificateId" value="<?php echo htmlspecialchars($certificateData['id']); ?>">
                    <?php endif; ?>
                </div>
                <div class="top-bar-right">
                    <button class="btn btn-outline" id="uploadPdfBtn">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Upload
                    </button>
                    <button class="btn btn-outline" id="previewBtn">
                        <i class="fas fa-eye"></i>
                        Preview
                    </button>
                    <button class="btn btn-primary" id="saveCertificateBtn">
                        <i class="fas fa-save"></i>
                        Save
                    </button>
                </div>
            </div>
            
            <!-- Main Container -->
            <div class="certificate-editor-container">
                <!-- Left Sidebar - Form Fields -->
                <div class="certificate-form-sidebar">
                    <div class="form-sidebar-content">
                        <!-- Published Toggle -->
                        <div class="form-group-toggle">
                            <label class="toggle-label">Published</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="publishedToggle" name="published">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <!-- Certificate Name -->
                        <div class="form-group">
                            <label for="certificateName">Name</label>
                            <input type="text" id="certificateName" name="certificateName" class="form-control" placeholder="Certificate Name" required>
                        </div>
                        
                        <!-- Certificate Fee -->
                        <div class="form-group">
                            <label for="certificateFee">Certificate Fee</label>
                            <input type="number" id="certificateFee" name="certificateFee" class="form-control" placeholder="0.00" step="0.01" min="0" value="0.00">
                        </div>
                        
                        <!-- Date Issued -->
                        <div class="form-group">
                            <label for="dateIssued">Date Issued</label>
                            <div class="date-input-group">
                                <input type="date" id="dateIssued" name="dateIssued" class="form-control">
                                <button type="button" class="btn-date-add" id="addDateIssuedBtn" data-date-type="date_issued" title="Add to Template">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Date Expired -->
                        <div class="form-group">
                            <label for="dateExpired">Date Expired</label>
                            <div class="date-input-group">
                                <input type="date" id="dateExpired" name="dateExpired" class="form-control">
                                <button type="button" class="btn-date-add" id="addDateExpiredBtn" data-date-type="date_expired" title="Add to Template">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="certificate-tabs">
                            <button type="button" class="tab-button active" data-tab="resident">RESIDENT</button>
                            <button type="button" class="tab-button" data-tab="form">FORM</button>
                            <button type="button" class="tab-button" data-tab="custom">CUSTOM</button>
                        </div>
                        
                        <!-- Tab Content -->
                        <div class="tab-content-container">
                            <!-- Resident Tab -->
                            <div class="tab-content active" id="residentTab">
                                <!-- Personal Information Section -->
                                <div class="collapsible-section">
                                    <button type="button" class="section-header active" data-section="personalInfo">
                                        <span>Personal Information</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="section-content active" id="personalInfo">
                                        <div class="field-list">
                                            <div class="field-item draggable-field" data-field="full_name" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Full Name</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="first_name" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">First Name</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="middle_name" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Middle Name</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="last_name" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Last Name</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="suffix" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Suffix</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="date_of_birth" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Date of Birth</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="age" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Age</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="sex" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Sex</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="civil_status" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Civil Status</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contact Information Section -->
                                <div class="collapsible-section">
                                    <button type="button" class="section-header" data-section="contactInfo">
                                        <span>Contact Information</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="section-content" id="contactInfo">
                                        <div class="field-list">
                                            <div class="field-item draggable-field" data-field="address" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Address</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="mobile_number" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Mobile Number</span>
                                            </div>
                                            <div class="field-item draggable-field" data-field="email" draggable="true">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <span class="field-name">Email Address</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form Tab -->
                            <div class="tab-content" id="formTab">
                                <!-- Text Placeholder Section -->
                                <div class="collapsible-section">
                                    <button type="button" class="section-header" data-section="textPlaceholder">
                                        <span>Text Placeholder</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="section-content" id="textPlaceholder">
                                        <div class="field-list">
                                            <p class="field-description">Add a text input field that will show when user inputs text</p>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="addTextPlaceholderBtn">
                                                <i class="fas fa-plus"></i>
                                                Add Text Placeholder
                                            </button>
                                            <div id="textPlaceholderList" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Number Placeholder Section -->
                                <div class="collapsible-section">
                                    <button type="button" class="section-header" data-section="numberPlaceholder">
                                        <span>Number Placeholder</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="section-content" id="numberPlaceholder">
                                        <div class="field-list">
                                            <p class="field-description">Add a number input field that will show when user inputs number</p>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="addNumberPlaceholderBtn">
                                                <i class="fas fa-plus"></i>
                                                Add Number Placeholder
                                            </button>
                                            <div id="numberPlaceholderList" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dropdown Placeholder Section -->
                                <div class="collapsible-section">
                                    <button type="button" class="section-header" data-section="dropdownPlaceholder">
                                        <span>Dropdown Placeholder</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="section-content" id="dropdownPlaceholder">
                                        <div class="field-list">
                                            <p class="field-description">Add a dropdown field with custom options (e.g., Type of Assistance)</p>
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100" id="addDropdownPlaceholderBtn">
                                                <i class="fas fa-plus"></i>
                                                Add Dropdown Placeholder
                                            </button>
                                            <div id="dropdownPlaceholderList" class="mt-2"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custom Tab -->
                            <div class="tab-content" id="customTab">
                                <div class="tab-empty-state">
                                    <i class="fas fa-cog"></i>
                                    <p>Custom fields will be displayed here</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel - PDF Editor -->
                <div class="certificate-pdf-panel">
                    <!-- PDF Formatting Toolbar -->
                    <div class="pdf-formatting-toolbar" id="pdfFormattingToolbar" style="display: none;">
                        <div class="toolbar-group">
                            <select class="toolbar-select" id="toolbarFontFamily">
                                <option value="Helvetica">Helvetica</option>
                                <option value="Arial">Arial</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Georgia">Georgia</option>
                            </select>
                        </div>
                        
                        <div class="toolbar-group">
                            <input type="number" class="toolbar-input" id="toolbarFontSize" value="20" min="8" max="72">
                        </div>
                        
                        <div class="toolbar-divider"></div>
                        
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="toolbarBold" title="Bold">
                                <i class="fas fa-bold"></i>
                            </button>
                            <button class="toolbar-btn" id="toolbarItalic" title="Italic">
                                <i class="fas fa-italic"></i>
                            </button>
                            <button class="toolbar-btn" id="toolbarUnderline" title="Underline">
                                <i class="fas fa-underline"></i>
                            </button>
                        </div>
                        
                        <div class="toolbar-divider"></div>
                        
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="toolbarAlignLeft" title="Align Left">
                                <i class="fas fa-align-left"></i>
                            </button>
                            <button class="toolbar-btn" id="toolbarAlignCenter" title="Align Center">
                                <i class="fas fa-align-center"></i>
                            </button>
                            <button class="toolbar-btn" id="toolbarAlignRight" title="Align Right">
                                <i class="fas fa-align-right"></i>
                            </button>
                        </div>
                        
                        <div class="toolbar-divider"></div>
                        
                        <div class="toolbar-group">
                            <button class="toolbar-btn" id="toolbarCalendar" title="Insert Date">
                                <i class="fas fa-calendar"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- PDF Canvas Area -->
                    <div class="pdf-canvas-area" id="pdfCanvasArea">
                        <div class="no-pdf-state" id="noPdfState">
                            <div class="pdf-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <h3>NO PDF UPLOADED</h3>
                            <p>Upload a PDF template to start placing fields</p>
                            <button type="button" class="btn btn-primary" onclick="document.getElementById('pdfFileInput').click()">
                                <i class="fas fa-upload"></i>
                                Upload PDF
                            </button>
                        </div>
                        
                        <!-- PDF Display Container -->
                        <div class="pdf-display-container" id="pdfDisplayContainer" style="display: none;">
                            <canvas id="pdfCanvas"></canvas>
                            <!-- Field placeholders will be added here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Hidden File Input -->
        <input type="file" id="pdfFileInput" accept=".pdf" style="display: none;">
    </main>
    
    <!-- Add Text Placeholder Modal -->
    <div class="modal fade" id="addTextPlaceholderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Text</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="textPlaceholderLabel" class="form-label">Label</label>
                        <input type="text" class="form-control" id="textPlaceholderLabel" placeholder="Enter label">
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Required</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="textPlaceholderRequired">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addTextPlaceholderSubmit">
                        <i class="fas fa-plus"></i>
                        ADD
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Number Placeholder Modal -->
    <div class="modal fade" id="addNumberPlaceholderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="numberPlaceholderLabel" class="form-label">Label</label>
                        <input type="text" class="form-control" id="numberPlaceholderLabel" placeholder="Enter label">
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Required</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="numberPlaceholderRequired">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addNumberPlaceholderSubmit">
                        <i class="fas fa-plus"></i>
                        ADD
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Dropdown Placeholder Modal -->
    <div class="modal fade" id="addDropdownPlaceholderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dropdown</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="dropdownPlaceholderLabel" class="form-label">Label</label>
                        <input type="text" class="form-control" id="dropdownPlaceholderLabel" placeholder="e.g., Type of Assistance">
                    </div>
                    
                    <div class="mb-3">
                        <label for="dropdownPlaceholderOptions" class="form-label">Options (one per line)</label>
                        <textarea class="form-control" id="dropdownPlaceholderOptions" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        <small class="text-muted">Enter each option on a new line</small>
                    </div>
                    
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Required</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="dropdownPlaceholderRequired">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addDropdownPlaceholderSubmit">
                        <i class="fas fa-plus"></i>
                        ADD
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye"></i>
                        Certificate Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="preview-container">
                        <div class="preview-toolbar">
                            <button class="btn btn-sm btn-outline-secondary" id="previewZoomOut">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="zoom-level" id="previewZoomLevel">100%</span>
                            <button class="btn btn-sm btn-outline-secondary" id="previewZoomIn">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <div class="toolbar-divider"></div>
                            <button class="btn btn-sm btn-outline-secondary" id="previewDownload">
                                <i class="fas fa-download"></i>
                                Download
                            </button>
                            <button class="btn btn-sm btn-primary" id="previewPrint">
                                <i class="fas fa-print"></i>
                                Print
                            </button>
                        </div>
                        <div class="preview-content" id="previewContent">
                            <canvas id="previewCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Date Format Modal -->
    <div class="modal fade" id="dateFormatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar"></i>
                        Date Format
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="selectedDateType">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Date Format</label>
                        <div class="date-format-options">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatYYYYMMDD" value="YYYY-MM-DD" checked>
                                <label class="form-check-label" for="formatYYYYMMDD">
                                    YYYY-MM-DD <span class="text-muted">(2026-02-14)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatDDMMYYYY" value="DD-MM-YYYY">
                                <label class="form-check-label" for="formatDDMMYYYY">
                                    DD-MM-YYYY <span class="text-muted">(14-02-2026)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatMMDDYYYY" value="MM-DD-YYYY">
                                <label class="form-check-label" for="formatMMDDYYYY">
                                    MM-DD-YYYY <span class="text-muted">(02-14-2026)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatYYMMDD" value="YY-MM-DD">
                                <label class="form-check-label" for="formatYYMMDD">
                                    YY-MM-DD <span class="text-muted">(26-02-14)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatDDMMYY" value="DD-MM-YY">
                                <label class="form-check-label" for="formatDDMMYY">
                                    DD-MM-YY <span class="text-muted">(14-02-26)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatMMDDYY" value="MM-DD-YY">
                                <label class="form-check-label" for="formatMMDDYY">
                                    MM-DD-YY <span class="text-muted">(02-14-26)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatMonthDDYYYY" value="Month DD, YYYY">
                                <label class="form-check-label" for="formatMonthDDYYYY">
                                    Month DD, YYYY <span class="text-muted">(February 14, 2026)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatDDMonthYYYY" value="DD Month YYYY">
                                <label class="form-check-label" for="formatDDMonthYYYY">
                                    DD Month YYYY <span class="text-muted">(14 February 2026)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatMonthDDthYYYY" value="Month DDth, YYYY">
                                <label class="form-check-label" for="formatMonthDDthYYYY">
                                    Month DDth, YYYY <span class="text-muted">(February 14th, 2026)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatDDth" value="DDth">
                                <label class="form-check-label" for="formatDDth">
                                    DDth <span class="text-muted">(14th)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatMonthOnly" value="Month">
                                <label class="form-check-label" for="formatMonthOnly">
                                    Month <span class="text-muted">(February)</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="dateFormat" id="formatYearOnly" value="YYYY">
                                <label class="form-check-label" for="formatYearOnly">
                                    YYYY <span class="text-muted">(2026)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="addDateFieldBtn">
                        <i class="fas fa-plus"></i>
                        Add to Template
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Field Properties Modal -->
    <div class="modal fade" id="fieldPropertiesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Field Properties</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="currentFieldId">
                    
                    <div class="mb-3">
                        <label for="fieldLabel" class="form-label">Field Label</label>
                        <input type="text" class="form-control" id="fieldLabel" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fieldFontFamily" class="form-label">Font Family</label>
                        <select class="form-control" id="fieldFontFamily">
                            <option value="Arial">Arial</option>
                            <option value="Times New Roman">Times New Roman</option>
                            <option value="Courier New">Courier New</option>
                            <option value="Georgia">Georgia</option>
                            <option value="Verdana">Verdana</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fieldFontSize" class="form-label">Font Size (px)</label>
                        <input type="number" class="form-control" id="fieldFontSize" min="8" max="72" value="16">
                    </div>
                    
                    <div class="mb-3">
                        <label for="fieldFontColor" class="form-label">Font Color</label>
                        <input type="color" class="form-control" id="fieldFontColor" value="#000000">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Font Style</label>
                        <div class="btn-group w-100" role="group">
                            <input type="checkbox" class="btn-check" id="fieldBold">
                            <label class="btn btn-outline-secondary" for="fieldBold"><i class="fas fa-bold"></i></label>
                            
                            <input type="checkbox" class="btn-check" id="fieldItalic">
                            <label class="btn btn-outline-secondary" for="fieldItalic"><i class="fas fa-italic"></i></label>
                            
                            <input type="checkbox" class="btn-check" id="fieldUnderline">
                            <label class="btn btn-outline-secondary" for="fieldUnderline"><i class="fas fa-underline"></i></label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fieldTextCase" class="form-label">Text Case</label>
                        <select class="form-control" id="fieldTextCase">
                            <option value="normal">Standard Case</option>
                            <option value="uppercase">UPPERCASE</option>
                            <option value="lowercase">lowercase</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="middleNameFormatGroup" style="display: none;">
                        <label class="form-label">Middle Name Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="middleNameFormat" id="middleNameFull" value="full" checked>
                            <label class="form-check-label" for="middleNameFull">
                                Full (Santos)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="middleNameFormat" id="middleNameInitial" value="initial">
                            <label class="form-check-label" for="middleNameInitial">
                                Initial Only (S.)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3" id="dateFormatGroup" style="display: none;">
                        <label class="form-label">Date Format</label>
                        <select class="form-control" id="fieldDateFormat">
                            <option value="YYYY-MM-DD">YYYY-MM-DD (2026-02-14)</option>
                            <option value="DD-MM-YYYY">DD-MM-YYYY (14-02-2026)</option>
                            <option value="MM-DD-YYYY">MM-DD-YYYY (02-14-2026)</option>
                            <option value="YY-MM-DD">YY-MM-DD (26-02-14)</option>
                            <option value="DD-MM-YY">DD-MM-YY (14-02-26)</option>
                            <option value="MM-DD-YY">MM-DD-YY (02-14-26)</option>
                            <option value="Month DD, YYYY">Month DD, YYYY (February 14, 2026)</option>
                            <option value="DD Month YYYY">DD Month YYYY (14 February 2026)</option>
                            <option value="Month DDth, YYYY">Month DDth, YYYY (February 14th, 2026)</option>
                            <option value="DDth">DDth (14th)</option>
                            <option value="Month">Month (February)</option>
                            <option value="YYYY">YYYY (2026)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="applyFieldProperties">Apply</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/script.js"></script>
    <?php if ($isEditing && $certificateData): ?>
    <script>
        // Pass certificate data to JavaScript
        window.certificateData = <?php echo json_encode($certificateData); ?>;
    </script>
    <?php endif; ?>
    <script src="../assets/js/create-certificate.js"></script>
</body>
</html>
