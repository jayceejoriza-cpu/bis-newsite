<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Load permissions and enforce create access
require_once '../permissions.php';
requirePermission('perm_resident_create', '../index.php');

// Fix sidebar menu links for subdirectory
if (isset($menu_items)) {
    foreach ($menu_items as &$item) {
        if (isset($item['url']) && !empty($item['url']) && !preg_match('/^(http|\/|#|javascript)/', $item['url'])) {
            $item['url'] = '../' . $item['url'];
        }
    }
    unset($item);
}

// Page title
$pageTitle = 'Create Resident';

// Calculate next Resident ID
$nextResidentId = 'W-00001'; // Default fallback
if (isset($conn)) {
    $result = $conn->query("SHOW TABLE STATUS LIKE 'residents'");
    if ($result && $row = $result->fetch_assoc()) {
        $nextId = $row['Auto_increment'];
        $nextResidentId = "W-" . str_pad($nextId % 100000, 5, '0', STR_PAD_LEFT);
    }
}
?>
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
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/create-resident.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
/* Phone Input Group */
.phone-input-group {
    display: flex;
    align-items: center;
    gap: 0;
}

.phone-prefix {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 12px;
    background-color: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-right: none;
    border-radius: 8px 0 0 8px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text-primary);
    white-space: nowrap;
}

.flag-icon {
    width: 20px;
    height: 14px;
    border-radius: 2px;
    object-fit: cover;
}

.phone-input {
    border-radius: 0 8px 8px 0 !important;
    flex: 1;
}

.form-card {
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
    margin-bottom: 20px;
    background-color: var(--bg-secondary, #f8fafc);
}

.minor-only, .adult-only {
    transition: all 0.3s ease-in-out;
}

.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    z-index: 1000;
    background: var(--bg-primary, #ffffff);
    border: 1px solid var(--border-color, #ccc);
    border-radius: 0 0 8px 8px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-top: 2px;
}
.autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--border-color, #eee);
    color: var(--text-primary, #333);
    font-size: 14px;
}
.autocomplete-item:last-child {
    border-bottom: none;
}
.autocomplete-item:hover {
    background-color: var(--bg-secondary, #f8f9fa);
}
.autocomplete-item strong {
    color: var(--primary-color, #0d6efd);
    font-weight: bold;
}
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include '../components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include '../components/header.php'; ?>
        
        <!-- Create Resident Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header-section">
                <div class="page-header-left">
                    <button class="btn-back" onclick="window.location.href='../residents.php'">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                        <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    </div>
                </div>
            </div>
            
            <!-- Progress Steps -->
            <div class="progress-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="step-label">Personal Details</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-circle">
                        <i class="fas fa-phone"></i>
                    </div>
                    <span class="step-label">Contact Information</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-circle">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="step-label">Family Information</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="4">
                    <div class="step-circle">
                        <i class="fas fa-home"></i>
                    </div>
                    <span class="step-label">Household Information</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="5">
                    <div class="step-circle">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span class="step-label">Education & Employment</span>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="6">
                    <div class="step-circle">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <span class="step-label">Additional Information</span>
                </div>
            </div>
            
            <!-- Form Container -->
            <div class="form-container">
                <form id="createResidentForm" method="POST" enctype="multipart/form-data">
                    
                    <!-- Step 1: Personal Details -->
                    <div class="form-step active" data-step="1">
                        <div class="form-content">
                            <!-- Photo Upload Section -->
                            <div class="photo-upload-section">
                                <div class="photo-preview" id="photoPreviewContainer">
                                    <img id="photoPreview" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect fill='%2393c5fd' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='80' fill='%23ffffff'%3E%F0%9F%91%A4%3C/text%3E%3C/svg%3E" alt="Photo Preview">
                                    <div id="inlineWebcamPreview" style="display: none;"></div>
                                </div>
                                <div class="photo-upload-actions">
                                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/gif" style="display: none;">
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('photoInput').click()">
                                        <i class="fas fa-upload"></i>
                                        Upload Photo
                                    </button>
                                    <button type="button" class="btn btn-primary" id="takePhotoBtn" onclick="openWebcamModal()">
                                        <i class="fas fa-camera"></i>
                                        <span id="cameraButtonText">Open Camera</span>
                                    </button>
                                    <button type="button" class="btn btn-success" id="captureInlineBtn" onclick="captureInlinePhoto()" style="display: none;">
                                        <i class="fas fa-camera"></i>
                                        Capture
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="resetPhotoBtn">
                                        <i class="fas fa-redo"></i>
                                        Reset
                                    </button>
                                    <p class="upload-hint">Allowed JPG, GIF or PNG. Max size of 1MB</p>
                                </div>
                            </div>

                            <!-- Minor Alert (hidden by default) -->
                            <div id="minorAlert" class="alert alert-warning" style="display: none; margin-top: 15px; animation: fadeIn 0.3s;">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Minor Detected:</strong> Guardian Information will be required in Step 3.
                            </div>
                            
                            <!-- Personal Information Fields -->
                            <div class="row">
                                <div class="col-sm-2">
                                    <div class="form-group">
                                        <label>Resident ID</label>
                                        <input type="hidden" name="resident_id" value="<?php echo $nextResidentId; ?>">
                                        <input type="text" class="form-control" value="<?php echo $nextResidentId; ?>" disabled>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="firstName">First Name <span class="required">*</span></label>
                                        <input type="text" id="firstName" name="firstName" class="form-control" autocomplete="given-name" required>
                                        <small class="form-hint">First name is required</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="middleName">Middle Name (Optional)</label>
                                        <input type="text" id="middleName" name="middleName" class="form-control" autocomplete="additional-name">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="lastName">Last Name <span class="required">*</span></label>
                                        <input type="text" id="lastName" name="lastName" class="form-control" autocomplete="family-name" required>
                                        <small class="form-hint">Last name is required</small>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="suffix">Suffix (Optional)</label>
                                        <input type="text" id="suffix" name="suffix" class="form-control" placeholder="Jr., Sr., III, etc." autocomplete="honorific-suffix">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="sex">Sex <span class="required">*</span></label>
                                        <select id="sex" name="sex" class="form-control" required>
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        <small class="form-hint">Sex is required</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                                        <input type="text" id="dateOfBirth" name="dateOfBirth" class="form-control" placeholder="Select Date" autocomplete="bday" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="placeOfBirth">Place of Birth <span class="required"></span></label>
                                        <input type="text" id="placeOfBirth" name="placeOfBirth" class="form-control" placeholder="Enter Place of Birth" >
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="religion">Religion<span class="required">*</span></label>
                                        <select id="religion" name="religion" class="form-control" required>
                                            <option value="">Select Religion</option>
                                            <option value="Roman Catholic">Roman Catholic</option>
                                            <option value="Christian">Christian</option>
                                            <option value="Iglesia ni Cristo">Iglesia ni Cristo</option>
                                            <option value="Catholic">Catholic</option>
                                            <option value="Islam">Islam</option>
                                            <option value="Baptist">Baptist</option>
                                            <option value="Buddhism">Buddhism</option>
                                            <option value="Born Again">Born Again</option>
                                            <option value="Church of God">Church of God</option>
                                            <option value="Jehovahs Witness">Jehovahs Witness</option>
                                            <option value="Protestant">Protestant</option>
                                            <option value="Seventh Day Adventist">Seventh Day Adventist</option>
                                            <option value="LDS-Mormons">LDS-Mormons</option>
                                            <option value="Evangelical">Evangelical</option>
                                            <option value="Pentecostal">Pentecostal</option>
                                            <option value="Unknown">Unknown</option>
                                            <option value="Other">Other (pls. Specify)</option>
                                        </select>
                                        <input type="text" id="religionOther" name="religion_other" class="form-control mt-2" placeholder="Specify Religion" style="display: none;">
                                        <small class="form-hint">Religion is required</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="ethnicity">Ethnicity<span class="required">*</span></label>
                                        <select id="ethnicity" class="form-control" name="ethnicity" required>
                                                <option value="">Select Ethnicity</option>
                                                <option value="IPS">IPS (Indigenous People)</option>
                                                <option value="Non-IPS">Non-IPS</option>
                                            </select>
                                            <small class="form-hint">Ethnicity is required</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Contact Information -->
                    <div class="form-step" data-step="2">
                        <div class="form-content">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="mobileNumber">Mobile Number <span class="required">*</span></label>
                                        <div class="phone-input-group">
                                            <span class="phone-prefix">
                                                <img src="../assets/image/contactph.png" alt="PH" class="flag-icon">
                                                +63
                                            </span>
                                            <input type="tel" id="mobileNumber" name="mobileNumber" class="form-control phone-input" placeholder="XXX XXX XXXX" pattern="[0-9 ]+" maxlength="12" oninput="let v=this.value.replace(/\D/g,'').substring(0,10);if(v.length>6)this.value=v.slice(0,3)+' '+v.slice(3,6)+' '+v.slice(6);else if(v.length>3)this.value=v.slice(0,3)+' '+v.slice(3);else this.value=v;" autocomplete="tel" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="purok">Purok <span class="required">*</span></label>
                                        <select id="purok" name="purok" class="form-control" required>
                                            <option value="">Select</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>  
                                            <option value="4">4</option>
                                        </select>       
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="streetName">Street Name</label>
                                        <input type="text" id="streetName" name="streetName" class="form-control" placeholder="Street Name" autocomplete="address-line1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Family Information -->
                    <div class="form-step" data-step="3">
                        <div class="form-content">
                            <div class="row">
                                <div class="col-md-2 adult-only">
                                    <div class="form-group">
                                        <label for="civilStatus">Civil Status <span class="required">*</span></label>
                                        <select id="civilStatus" name="civilStatus" class="form-control" required>
                                            <option value="">Select</option>
                                            <option value="Single">Single</option>
                                            <option value="Married">Married</option>
                                            <option value="Widow/er">Widow/er</option>
                                            <option value="Separated">Separated</option>
                                            <option value="Cohabitation">Cohabitation</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3 adult-only position-relative" id="spouseNameGroup" style="display: none; position: relative;">
                                    <div class="form-group position-relative">
                                        <label for="spouseName">Spouse Name</label>
                                        <input type="hidden" id="spouseNameId" name="spouseResidentId" value="">
                                        <input type="text" id="spouseName" name="spouseName" class="form-control" autocomplete="off">
                                        <div id="spouseNameDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group position-relative">
                                        <label for="fatherName">Father's Name</label>
                                        <input type="hidden" id="fatherNameId" name="fatherResidentId" value="">
                                        <input type="text" id="fatherName" name="fatherName" class="form-control" autocomplete="off">
                                        <div id="fatherNameDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group position-relative">
                                        <label for="motherName">Mother's Maiden Name <span class="required">*</span></label>
                                        <input type="hidden" id="motherNameId" name="motherResidentId" value="">
                                        <input type="text" id="motherName" name="motherName" class="form-control" required autocomplete="off">
                                        <div id="motherNameDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                                    </div>
                                </div>
                                <div class="col-md-2  adult-only">
                                    <div class="form-group">
                                        <label for="numberOfChildren">Number of Children</label>
                                        <input type="number" id="numberOfChildren" name="numberOfChildren" class="form-control" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Guardian Section (Hidden by default) -->
                            <div id="guardianSection" class="form-card minor-only" style="display: none;">
                                <h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-user-shield"></i> Guardian Information (for Minors)</h5>
                                <hr style="margin: 0 0 20px 0;">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="guardianName">Guardian's Full Name <span class="required">*</span></label>
                                            <input type="text" id="guardianName" name="guardianName" class="form-control">
                                            <small class="form-hint">Required for minors</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="guardianRelationship">Relationship to Guardian <span class="required">*</span></label>
                                            <select id="guardianRelationship" name="guardianRelationship" class="form-control">
                                                <option value="">Select Relationship</option>
                                                <option value="Father">Father</option>
                                                <option value="Mother">Mother</option>
                                                <option value="Grandparent">Grandparent</option>
                                                <option value="Legal Guardian">Legal Guardian</option>
                                                <option value="Other">Other</option>
                                            </select>
                                            <small class="form-hint">Required for minors</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="guardianContact">Guardian's Mobile Number <span class="required">*</span></label>
                                            <div class="phone-input-group">
                                                <span class="phone-prefix">
                                                    <img src="../assets/image/contactph.png" alt="PH" class="flag-icon">
                                                    +63
                                                </span>
                                                <input type="tel" id="guardianContact" name="guardianContact" class="form-control phone-input" placeholder="XXX XXX XXXX" maxlength="12">
                                            </div>
                                            <small class="form-hint">Required for minors</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4: Household Information -->
                    <div class="form-step" data-step="4">
                        <div class="form-content">

                            <h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-home"></i> Household Information</h5>
                            <hr style="margin: 0 0 20px 0;">

                            <!-- Household Head Question -->
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="font-weight: 600; font-size: 15px;">Are you a Household Head? <span class="required"></span></label>
                                <div style="display: flex; gap: 20px; margin-top: 10px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                        <input type="radio" name="householdHead" id="householdHeadYes" value="Yes" style="width: 18px; height: 18px; cursor: pointer;">
                                        Yes
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                        <input type="radio" name="householdHead" id="householdHeadNo" value="No" style="width: 18px; height: 18px; cursor: pointer;">
                                        No
                                    </label>
                                </div>
                            </div>

                            <!-- YES Panel: Create Household -->
                            <div id="householdYesPanel" style="display: none;">
                                <div style="background: var(--bg-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 20px; margin-bottom: 10px;">
                                    <h6 style="margin: 0 0 15px 0; color: var(--primary-color); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-plus-circle"></i> Create New Household
                                    </h6>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="householdNumber">Household Number <span class="required">*</span></label>
                                            <input type="text" id="householdNumber" name="householdNumber" class="form-control" placeholder="e.g. HH-00001" required>
                                            <small class="form-hint">Household number is required</small>
                                        </div>

                                        <div class="form-group">
                                            <label for="householdContact">Household Contact</label>
                                            <div class="phone-input-group">
                                                <span class="phone-prefix">
                                                    <img src="../assets/image/contactph.png" alt="PH" class="flag-icon">
                                                    +63
                                                </span>
                                                <input type="tel" id="householdContact" name="householdContact" class="form-control phone-input" placeholder="Enter household contact" maxlength="10">
                                            </div>
                                        </div>

                                        <div class="form-group full-width">
                                            <label for="householdAddress">Household Address</label>
                                            <input type="text" id="householdAddress" name="householdAddress" class="form-control" placeholder="Enter household address">
                                            
                                        </div>

                                        <div class="form-group">
                                            <label for="waterSourceType">Water Source Type</label>
                                            <select id="waterSourceType" name="waterSourceType" class="form-control">
                                                <option value="">Select Water Source</option>
                                                <option value="Level I (Point Spring)">Level I (Point Spring)</option>
                                                <option value="Level II (Communal Faucet system or stand post)">Level II (Communal Faucet system or stand post)</option>
                                                <option value="Level III (Waterworks system or individual house connection)">Level III (Waterworks system or individual house connection)</option>
                                                <option value="O (For doubtful sources, open dug well etc.)">O (For doubtful sources, open dug well etc.)</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="toiletFacilityType">Toilet Facility Type</label>
                                            <select id="toiletFacilityType" name="toiletFacilityType" class="form-control">
                                                <option value="">Select Toilet Facility</option>
                                                <option value="" disabled>----Sanitary Toilet----</option>
                                                <option value="P - Pour/Flush toilet connected to septic tank)">P - (Pour/Flush toilet connected to septic tank)</option>
                                                <option value="PF - Pour/Flush toilet connected to septic tank and sewerage system">PF - Pour/Flush toilet connected to septic tank and sewerage system</option>
                                                <option value="VIP - Ventilated impoved pit latrine (VIP) or composting">VIP - Ventilated impoved pit latrine (VIP) or composting</option>
                                                <option value="" disabled>----Unsanitary Toilet----</option>
                                                <option value="WS - Water-sealed connected to open drain">WS - Water-sealed connected to open drain</option>
                                                <option value="OH - Overhung Latrine">OH - Overhung Latrine</option>
                                                <option value="OP - Overpit Latrine">OP - Overpit Latrine</option>
                                                <option value="WO - Without Latrine">WO - Without Latrine</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Ownership Status <span class="required">*</span></label>
                                            <div style="display: flex; gap: 20px; margin-top: 10px;">
                                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                                    <input type="radio" name="ownershipStatus" id="ownershipOwned" value="Owned" style="width: 18px; height: 18px; cursor: pointer;" checked>
                                                    Owned
                                                </label>
                                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                                    <input type="radio" name="ownershipStatus" id="ownershipRent" value="Rent" style="width: 18px; height: 18px; cursor: pointer;">
                                                    Rent
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-group position-relative" id="landlordNameGroup" style="display: none;">
                                            <label for="landlordName">Landlord's Name <span class="required">*</span></label>
                                            <input type="hidden" id="landlordNameId" name="landlordResidentId" value="">
                                            <input type="text" id="landlordName" name="landlordName" class="form-control" placeholder="Search resident..." autocomplete="off">
                                            <div id="landlordNameDropdown" class="autocomplete-dropdown" style="display: none;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- NO Panel: Find Existing Household -->
                            <div id="householdNoPanel" style="display: none;">
                                <div style="background: var(--bg-secondary, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 20px; margin-bottom: 10px;">
                                    <h6 style="margin: 0 0 15px 0; color: var(--primary-color); font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                        <i class="fas fa-search"></i> Find Existing Household
                                    </h6>

                                    <!-- Search Box -->
                                    <div class="form-group">
                                        <label for="householdSearch">Search Household</label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" id="householdSearch" class="form-control" placeholder="Search by household number, head name, or address...">
                                            <button type="button" class="btn btn-primary" id="searchHouseholdBtn" style="white-space: nowrap;">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Search Results -->
                                    <div id="householdSearchResults" style="display: none; margin-top: 15px;">
                                        <div id="householdResultsList"></div>
                                    </div>

                                    <!-- Selected Household Display -->
                                    <div id="selectedHouseholdCard" style="display: none; margin-top: 15px;">
                                        <div style="background: #fff; border: 2px solid var(--primary-color, #3b82f6); border-radius: 10px; padding: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                                <h6 style="margin: 0; color: var(--primary-color); font-weight: 600;">
                                                    <i class="fas fa-check-circle" style="color: #10b981;"></i> Selected Household
                                                </h6>
                                                <button type="button" class="btn btn-sm btn-secondary" id="clearHouseholdBtn" style="padding: 3px 10px; font-size: 12px;">
                                                    <i class="fas fa-times"></i> Clear
                                                </button>
                                            </div>
                                            <div id="selectedHouseholdInfo" class="form-grid" style="margin: 0;"></div>
                                        </div>
                                    </div>

                                    <!-- Hidden input to store selected household ID -->
                                    <input type="hidden" id="selectedHouseholdId" name="selectedHouseholdId" value="">
                                    <!-- Hidden input to store relationship to head -->
                                    <input type="hidden" id="householdRelationship" name="householdRelationship" value="">
                                </div>
                            </div>

                            <!-- Hidden input to store household head answer -->
                            <input type="hidden" id="householdHeadValue" name="householdHeadValue" value="">

                        </div>
                    </div>
                    
                    <!-- Step 5: Education & Employment -->
                    <div class="form-step" data-step="5">
                        <div class="form-content">
                            <div class="row">
                                <div id="educationContainer" class="col-md-4">
                                    <div class="form-group">
                                        <label for="educationalAttainment">Educational Attainment</label>
                                        <select id="educationalAttainment" name="educationalAttainment" class="form-control">
                                            <option value="">Select</option>
                                            <option value="No Formal Education">No Formal Education</option>
                                            <option value="Elementary Level">Elementary Level</option>
                                            <option value="Elementary Graduate">Elementary Graduate</option>
                                            <option value="High School Level">High School Level</option>
                                            <option value="High School Graduate">High School Graduate</option>
                                            <option value="College Level">College Level</option>
                                            <option value="College Graduate">College Graduate</option>
                                            <option value="Vocational">Vocational</option>
                                            <option value="Post Graduate">Post Graduate</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="employmentContainer" class="col-md-4">
                                    <div class="form-group">
                                        <label for="employmentStatus">Employment Status</label>
                                        <select id="employmentStatus" name="employmentStatus" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Employed">Employed</option>
                                            <option value="Unemployed">Unemployed</option>
                                            <option value="Self-Employed">Self-Employed</option>
                                            <option value="Student">Student</option>
                                            <option value="OFW">OFW</option>
                                            <option value="Retired">Retired</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="occupationContainer" class="col-md-4">
                                    <div class="form-group">
                                        <label for="occupation">Occupation</label>
                                        <input type="text" id="occupation" name="occupation" class="form-control">
                                    </div>
                                </div>
        
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 6: Additional Information -->
                    <div class="form-step" data-step="6"> 
                        <div class="form-content">
                            <!-- Government Programs Section -->
                            <h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-landmark"></i> Government Programs</h5>
                            <hr style="margin: 0 0 20px 0;">
                            
                            <div class="row">
                                <div class="col-md-2 adult-only">
                                    <div class="form-group">
                                        <label for="fourPs">4Ps Member</label>
                                        <select id="fourPs" name="fourPs" class="form-control">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 adult-only" id="fourpsIdGroup" style="display: none;">
                                    <div class="form-group">
                                        <label for="fourpsId">4Ps ID Number</label>
                                        <input type="text" id="fourpsId" name="fourpsId" class="form-control" placeholder="XX-YYYY-ZZZZ" maxlength="12">
                                    </div>
                                </div>
                                <div id="voterStatusContainer" class="col-md-2">
                                    <div class="form-group">
                                        <label for="voterStatus">Registered Voter</label>
                                        <select id="voterStatus" name="voterStatus" class="form-control">
                                              <option value="No">No</option>
                                              <option value="Yes">Yes</option>
                                          
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 adult-only" id="precinctNumberGroup" style="display: none;">
                                    <div class="form-group">
                                        <label for="precinctNumber">Precinct Number</label>
                                        <input type="text" id="precinctNumber" name="precinctNumber" class="form-control" placeholder="Enter Precinct Number">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Health Information Section -->
                            <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-heartbeat"></i> Health Information</h5>
                            <hr style="margin: 0 0 20px 0;">
                            
                            <div class="row">
                                <div class="col-md-2 adult-only">
                                    <div class="form-group">
                                        <label for="philhealthId">Philhealth ID Number</label>
                                        <input type="text" id="philhealthId" name="philhealthId" class="form-control" placeholder="1234-5678-9012" maxlength="14">
                                    </div>
                                </div>
                                <div class="col-md-2 adult-only">
                                    <div class="form-group">
                                        <label for="membershipType">Membership Type</label>
                                        <select id="membershipType" name="membershipType" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Member">Member</option>
                                            <option value="Dependent">Dependent</option>
                                            <option value="None">None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 adult-only">
                                    <div class="form-group">
                                        <label for="philhealthCategory">Philhealth Category</label>
                                        <select id="philhealthCategory" name="philhealthCategory" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Direct Contributor">Direct Contributor</option>
                                            <option value="Indirect Contributor">Indirect Contributor</option>
                                            <option value="Sponsored">Sponsored</option>
                                            <option value="None">None</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="ageHealthGroup">Classification by Age/Health Group</label>
                                        <select id="ageHealthGroup" name="ageHealthGroup" class="form-control" disabled>
                                            <option value="">Select</option>
                                            <option value="Newborn (0-28 days)">Newborn (0-28 days)</option>
                                            <option value="Infant (29 days - 1 year)">Infant (29 days - 1 year)</option>
                                            <option value="Child (1-9 years)">Child (1-9 years)</option>
                                            <option value="Adolescent (10-19 years)">Adolescent (10-19 years)</option>
                                            <option value="Adult (20-59 years)">Adult (20-59 years)</option>
                                            <option value="Senior Citizen (60+ years)">Senior Citizen (60+ years)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="font-weight: 600; font-size: 15px;">Are you a Person with Disability (PWD)? <span class="required">*</span></label>
                                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                                <input type="radio" name="pwdStatusRadio" id="pwdStatusYes" value="Yes" style="width: 18px; height: 18px; cursor: pointer;">
                                                Yes
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 500;">
                                                <input type="radio" name="pwdStatusRadio" id="pwdStatusNo" value="No" style="width: 18px; height: 18px; cursor: pointer;">
                                                No
                                            </label>
                                        </div>
                                        <input type="hidden" id="pwdStatus" name="pwdStatus" value="" required>
                                        <small class="form-hint">Disability status is required</small>
                                    </div>
                                </div>
                                <div class="col-md-4" id="pwdTypeGroup" style="display: none;">
                                    <div class="form-group">
                                        <label for="pwdType">Type of Disability <span class="required">*</span></label>
                                        <input type="text" id="pwdType" name="pwdType" class="form-control" placeholder="Specify disability">
                                    </div>
                                </div>
                                <div class="col-md-4" id="pwdIdGroup" style="display: none;">
                                    <div class="form-group">
                                        <label for="pwdIdNumber">PWD ID Number <span class="text-muted">(Optional)</span></label>
                                        <input type="text" id="pwdIdNumber" name="pwdIdNumber" class="form-control" placeholder="Enter PWD ID">
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="medicalHistory">Medical History</label>
                                        <textarea id="medicalHistory" name="medicalHistory" class="form-control" rows="3" placeholder="Enter any medical conditions, allergies, or health concerns..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Women's Reproductive Health Section (Conditional) -->
                            <div id="wraSection" style="display: none;">
                                <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-female"></i> Women's Reproductive Health (WRA)</h5>
                                <hr style="margin: 0 0 20px 0;">
                                
                                <div class="row">
                                    <div class="form-group">
                                        <label for="lmpDate">Last Menstrual Period (LMP)</label>
                                        <input type="date" id="lmpDate" name="lmpDate" class="form-control">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="usingFpMethod">Using FP Method</label>
                                        <select id="usingFpMethod" name="usingFpMethod" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Yes">Yes</option>
                                            <option value="No">No</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" id="fpMethodsGroup" style="display: none;">
                                        <label for="fpMethodsUsed">FP Methods Used</label>
                                        <select id="fpMethodsUsed" name="fpMethodsUsed" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Pills">Pills</option>
                                            <option value="Injectable">Injectable</option>
                                            <option value="IUD">IUD</option>
                                            <option value="Condom">Condom</option>
                                            <option value="Implant">Implant</option>
                                            <option value="Natural">Natural</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" id="fpStatusGroup" style="display: none;">
                                        <label for="fpStatus">FP Status</label>
                                        <select id="fpStatus" name="fpStatus" class="form-control">
                                            <option value="">Select</option>
                                            <option value="Current User">Current User</option>
                                            <option value="Dropout">Dropout</option>
                                            <option value="New Acceptor">New Acceptor</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                                <!-- OFW Section (Conditional) -->
                            <div id="ofwHouseSection" class="form-card" style="display: none; margin-top: 30px;">
                                <h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-plane-departure"></i> OFW Additional Information</h5>
                                <hr style="margin: 0 0 20px 0;">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="isHouseOccupied">Is someone currently residing in your house while you are abroad? <span class="required">*</span></label>
                                            <select id="isHouseOccupied" name="isHouseOccupied" class="form-control">
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="caretakerInfoGroup" class="col-md-6" style="display: none;">
                                        <div class="form-group">
                                            <label for="caretakerName">Caretaker Name <span class="required">*</span></label>
                                            <input type="text" id="caretakerName" name="caretakerName" class="form-control" placeholder="Enter Full Name">
                                        </div>
                                        <div class="form-group">
                                            <label for="caretakerContact">Caretaker Contact Number <span class="required">*</span></label>
                                            <div class="phone-input-group">
                                                <span class="phone-prefix">
                                                    <img src="../assets/image/contactph.png" alt="PH" class="flag-icon">
                                                    +63
                                                </span>
                                                <input type="tel" id="caretakerContact" name="caretakerContact" class="form-control phone-input" placeholder="9XX XXX XXXX" maxlength="12">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Remarks Section -->
                            <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-sticky-note"></i> Additional Notes</h5>
                            <hr style="margin: 0 0 20px 0;">
                            
                            <div class="row">
                                <div class="form-group full-width">
                                    <label for="remarks">Remarks/Notes</label>
                                    <textarea id="remarks" name="remarks" class="form-control" rows="4" placeholder="Additional information or notes..."></textarea>
                                </div>
                            </div>

                        
                        </div>
                    </div>
                    
                    <!-- Form Navigation -->
                    <div class="form-navigation">
                        <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </button>
                        <div class="nav-spacer"></div>
                        <button type="button" class="btn btn-primary" id="nextBtn">
                            Next
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="button" class="btn btn-success" id="reviewBtn" style="display: none;" onclick="openReviewModal()">
                            <i class="fas fa-eye"></i>
                            Review Before Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <!-- WebcamJS Library -->
    <script src="../assets/webcamjs/webcam.min.js"></script>
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/create-resident.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#dateOfBirth", {
                defaultDate: "2000-01-01", // The calendar opens to this date when clicked
                maxDate: "today",          // Prevents them from picking a future birthdate
                altInput: true,            // Makes the input UI look clean
                altFormat: "F j, Y",       // How the user sees it (e.g., January 1, 2000)
                dateFormat: "Y-m-d",       // How your PHP/Database receives it (e.g., 2000-01-01)
            });
        });

    </script>
    <script>
        // Ownership Status Handler
        document.addEventListener('DOMContentLoaded', function() {
            const ownershipRadios = document.querySelectorAll('input[name="ownershipStatus"]');
            const landlordGroup = document.getElementById('landlordNameGroup');
            const landlordInput = document.getElementById('landlordName');

            function toggleLandlordField() {
                if (document.getElementById('ownershipRent').checked) {
                    landlordGroup.style.display = 'block';
                    landlordInput.required = true;
                } else {
                    landlordGroup.style.display = 'none';
                    landlordInput.required = false;
                    landlordInput.value = '';
                }
            }

            ownershipRadios.forEach(radio => {
                radio.addEventListener('change', toggleLandlordField);
            });

            // Initial check
            toggleLandlordField();
        });

        // Override phone number formatting to use spaces instead of hyphens
        function formatPhoneNumber(value) {
            const numbers = value.replace(/\D/g, '');
            const limited = numbers.substring(0, 10);
            if (limited.length <= 3) return limited;
            if (limited.length <= 6) return limited.substring(0, 3) + ' ' + limited.substring(3);
            return limited.substring(0, 3) + ' ' + limited.substring(3, 6) + ' ' + limited.substring(6);
        }

        // Fix sidebar and header links/images for subdirectory (handles hardcoded elements)
        document.addEventListener('DOMContentLoaded', function() {
            // Fix relative links (href) in sidebar, header, and dropdowns
            const links = document.querySelectorAll('.sidebar a, .header a, .nav-item a, .dropdown-item, .user-profile-link, .logout-link');
            links.forEach(link => {
                const href = link.getAttribute('href');
                // Check if link is relative and doesn't start with ../ or other protocols
                if (href && 
                    !href.startsWith('http') && 
                    !href.startsWith('/') && 
                    !href.startsWith('#') && 
                    !href.startsWith('javascript') && 
                    !href.startsWith('../')) {
                    
                    link.setAttribute('href', '../' + href);
                }
            });

            // Fix relative image paths (src) - needed for profile pictures and logos in header/sidebar
            const images = document.querySelectorAll('.sidebar img, .header img, .user-avatar img');
            images.forEach(img => {
                const src = img.getAttribute('src');
                if (src && !/^(http|https|\/|data:|\.\.\/)/.test(src)) {
                    img.setAttribute('src', '../' + src);
                }
            });
        });
    </script>
    
    <!-- Webcam Modal -->
    <div id="webcamModal" class="webcam-modal">
        <div class="webcam-modal-content">
            <div class="webcam-modal-header">
                <h3><i class="fas fa-camera"></i> Take Photo</h3>
                <button type="button" class="btn-close-modal" onclick="closeWebcamModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="webcam-modal-body">
                <div id="webcamContainer" class="webcam-container">
                    <div id="webcamPreview"></div>
                </div>
                <div id="capturedImageContainer" class="captured-image-container" style="display: none;">
                    <img id="capturedImage" src="" alt="Captured Photo">
                </div>
            </div>
            <div class="webcam-modal-footer">
                <div id="webcamInitialActions" class="webcam-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeWebcamModal()">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="captureBtn" onclick="capturePhoto()">
                        <i class="fas fa-camera"></i>
                        Capture Photo
                    </button>
                </div>
                <div id="webcamCapturedActions" class="webcam-actions" style="display: none;">
                    <button type="button" class="btn btn-secondary" onclick="retakePhoto()">
                        <i class="fas fa-redo"></i>
                        Retake
                    </button>
                    <button type="button" class="btn btn-success" onclick="useWebcamPhoto()">
                        <i class="fas fa-check"></i>
                        Use This Photo
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal" style="z-index: 4000;">
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h3><i class="fas fa-clipboard-check"></i> Review Before Submit</h3>
                <button type="button" class="btn-close-modal" onclick="closeReviewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="review-modal-body">
                <div class="review-sections">
                    <!-- Personal Information Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Personal Information</h4>
                                <p>Basic personal details and identification</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewPersonalInfo">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Contact Information Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #10b981;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Contact Information</h4>
                                <p>Address and communication details</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewContactInfo">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Family Information Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #f59e0b;">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Family Information</h4>
                                <p>Family details and connections</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewFamilyInfo">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Household Information Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #0ea5e9;">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Household Information</h4>
                                <p>Household head status and household details</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewHouseholdInfo">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Education & Employment Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #8b5cf6;">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Education & Employment</h4>
                                <p>Educational and employment information</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewEducationEmployment">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Additional Information Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #06b6d4;">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Additional Information</h4>
                                <p>Government programs and health information</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewAdditionalInfo">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="review-modal-footer">
                <!-- Confirmation Checkbox -->
                <div class="confirmation-checkbox-container">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="confirmDetailsCheckbox" class="confirmation-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                        <span class="checkbox-text">
                            I confirm and consent to the processing of my data as stated in the 
                            <button type="button" id="viewPrivacyLink" style="background: none; border: none; color: var(--primary-color); text-decoration: underline; padding: 0; font: inherit; cursor: pointer;">Privacy Notice</button>
                        </span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="review-modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">
                        <i class="fas fa-edit"></i>
                        Edit Information
                    </button>
                    <button type="button" class="btn btn-success" id="finalSubmitBtn" onclick="submitFormFromReview()" disabled>
                        <i class="fas fa-check-circle"></i>
                        Confirm & Consent
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Notice Modal -->
    <div id="privacyNoticeModal" class="review-modal" style="z-index: 5000;">
        <div class="review-modal-content" style="max-width: 600px;">
            <div class="review-modal-header">
                <h3><i class="fas fa-shield-alt"></i> Barangay Privacy Notice</h3>
                <button type="button" class="btn-close-modal" onclick="closePrivacyNoticeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="review-modal-body" id="privacyNoticeBody" style="max-height: 400px; overflow-y: auto; padding: 20px; line-height: 1.6;">
                <div class="policy-content">
                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">English</h4>
                    <p>In compliance with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>, this Barangay collects and processes your personal and sensitive personal information, including but not limited to <strong>Health Data, Disability (PWD) Status, and Voter Registration</strong>.</p>
                    <p>This information is collected solely for the purpose of:</p>
                    <ul style="margin-bottom: 15px; padding-left: 20px;">
                        <li>Facilitating the delivery of government services and social protection programs.</li>
                        <li>Mandatory administrative reporting to the Department of the Interior and Local Government (DILG).</li>
                        <li>Maintaining an accurate registry of barangay inhabitants.</li>
                    </ul>
                    <p>Your data is stored securely and will not be shared with unauthorized third parties. As a data subject, you have the <strong>right to access, review, and request corrections</strong> to your personal data at any time by visiting the Barangay Office.</p>
                    
                    <hr style="margin: 20px 0; border-color: var(--border-color);">
                    
                    <h4 style="color: var(--primary-color); margin-bottom: 10px;">Tagalog</h4>
                    <p>Alinsunod sa <strong>Data Privacy Act of 2012 (RA 10173)</strong>, ang Barangay na ito ay nangongolekta at nagpoproseso ng inyong personal at sensitibong personal na impormasyon, kabilang ang <strong>Datos sa Kalusugan, Katayuan ng Kapansanan (PWD), at Rehistrasyon ng Botante</strong>.</p>
                    <p>Ang impormasyong ito ay kinokolekta para sa mga sumusunod na layunin:</p>
                    <ul style="margin-bottom: 15px; padding-left: 20px;">
                        <li>Pagpapadali ng paghahatid ng mga serbisyo ng gobyerno at mga programa sa proteksyong panlipunan.</li>
                        <li>Mandatoryong pag-uulat sa Department of the Interior and Local Government (DILG).</li>
                        <li>Pagpapanatili ng tumpak na listahan ng mga residente ng barangay.</li>
                    </ul>
                    <p>Ang inyong datos ay itatago nang ligtas at hindi ibabahagi sa mga hindi awtorisadong partido. Bilang "data subject," kayo ay may <strong>karapatang i-access, suriin, at hilingin ang pagtatama</strong> ng inyong personal na datos anumang oras sa pamamagitan ng pagbisita sa Opisina ng Barangay.</p>
                    
                    <div id="scrollIndicator" style="text-align: center; color: #ef4444; font-weight: 600; margin-top: 20px; font-size: 0.85rem;">
                        <div id="minorConsentNote" style="display: none; margin-top: 20px; padding: 10px; border: 1px solid #3b82f6; background-color: #e0f2fe; border-radius: 5px; font-size: 0.9em; color: #1e40af;">
                            <i class="fas fa-info-circle me-2"></i> Since the registrant is a minor, consent is provided by the undersigned Parent/Guardian.
                        </div>

                        <i class="fas fa-arrow-down"></i> Please scroll to the bottom to acknowledge
                    </div>
                </div>
            </div>
            <div class="review-modal-footer">
                <button type="button" class="btn btn-primary" id="acknowledgePrivacyBtn" disabled onclick="closePrivacyNoticeModal()">
                    I have read and understood
                </button>
            </div>
        </div>
    </div>
</body>
</html>
