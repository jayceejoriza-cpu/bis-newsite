<?php
// Include configuration
require_once '../config.php';

// Check authentication
require_once '../auth_check.php';

// Load permissions and enforce edit access
require_once '../permissions.php';
requirePermission('perm_resident_edit', '../index.php');

// Check if resident ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ../residents.php');
    exit;
}

$residentId = intval($_GET['id']);

// Page title
$pageTitle = 'Edit Resident';
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
    
    <style>
        /* Two-column layout for photo section */
        .photo-section-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .photo-upload-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .status-fields-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .photo-section-wrapper {
                grid-template-columns: 1fr;
            }
        }
        
        .phone-input-group {
            display: flex;
            align-items: stretch;
        }
        .phone-prefix {
            background-color: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 0 10px;
            border-right: none;
            border-radius: 4px 0 0 4px;
        }
        .phone-input {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .flag-icon {
            width: 20px;
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
        
        <!-- Edit Resident Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header-section">
                <div class="page-header-left">
                    <button class="btn-back" onclick="window.location.href='../resident_profile.php?id=<?php echo $residentId; ?>'">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                        <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                        <p class="page-subtitle">Update resident information</p>
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
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <span class="step-label">Emergency Contact</span>
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
                <form id="editResidentForm" method="POST" enctype="multipart/form-data">
                    <!-- Hidden field for resident ID -->
                    <input type="hidden" id="residentId" name="residentId" value="<?php echo $residentId; ?>">
                    <input type="hidden" id="existingPhoto" name="existingPhoto" value="">
                    
                    <!-- Step 1: Personal Details -->
                    <div class="form-step active" data-step="1">
                        <div class="form-content">
                            <!-- Two-column layout: Photo Upload + Status Fields -->
                            <div class="photo-section-wrapper">
                                <!-- Left Column: Photo Upload Section -->
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
                                        <button type="button" class="btn btn-primary" id="takePhotoBtn" onclick="toggleInlineWebcam()">
                                            <i class="fas fa-camera"></i>
                                            <span id="cameraButtonText">Start Camera</span>
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
                                    <div class="form-group">
                                        <label>Resident ID</label>
                                        <input type="text" id="displayResidentId" class="form-control" disabled>
                                        <small class="form-text text-muted">Resident ID (Format: W-XXXXX)</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column: Status Fields -->
                                <div class="status-fields-section">
                                    <div class="form-group">
                                        <label for="verificationStatus">Verification Status <span class="required">*</span></label>
                                        <select id="verificationStatus" name="verificationStatus" class="form-control" required>
                                            <option value="">Select Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Verified">Verified</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                        <small class="form-hint">Current verification status of the resident</small>
                                    </div>
                                    
                                    <div class="form-group" id="rejectionReasonGroup" style="display: none;">
                                        <label for="rejectionReason">Rejection Reason</label>
                                        <textarea id="rejectionReason" name="rejectionReason" class="form-control" rows="3" placeholder="Enter reason for rejection..."></textarea>
                                        <small class="form-hint">Required if status is Rejected</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="activityStatus">Activity Status <span class="required">*</span></label>
                                        <select id="activityStatus" name="activityStatus" class="form-control" required>
                                            <option value="">Select Status</option>
                                            <option value="Active">Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Deceased">Deceased</option>
                                        </select>
                                        <small class="form-hint">Current activity status of the resident</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="statusRemarks">Status Remarks</label>
                                        <textarea id="statusRemarks" name="statusRemarks" class="form-control" rows="3" placeholder="Additional notes about status changes..."></textarea>
                                        <small class="form-hint">Optional remarks about status</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Personal Information Fields -->
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="firstName">First Name <span class="required">*</span></label>
                                    <input type="text" id="firstName" name="firstName" class="form-control" required>
                                    <small class="form-hint">First name is required</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="middleName">Middle Name (Optional)</label>
                                    <input type="text" id="middleName" name="middleName" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="lastName">Last Name <span class="required">*</span></label>
                                    <input type="text" id="lastName" name="lastName" class="form-control" required>
                                    <small class="form-hint">Last name is required</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="suffix">Suffix (Optional)</label>
                                    <input type="text" id="suffix" name="suffix" class="form-control" placeholder="Jr., Sr., III, etc.">
                                </div>
                                
                                <div class="form-group">
                                    <label for="sex">Sex <span class="required">*</span></label>
                                    <select id="sex" name="sex" class="form-control" required>
                                        <option value="">Select</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <small class="form-hint">Sex is required</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="dateOfBirth">Date of Birth <span class="required">*</span></label>
                                    <input type="date" id="dateOfBirth" name="dateOfBirth" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="religion">Religion<span class="required">*</span></label>
                                    <input type="text" id="religion" name="religion" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="ethnicity">Ethnicity<span class="required">*</span></label>
                                    <select id="ethnicity" class="form-control" name="ethnicity">
                                        <option value="">Select Ethnicity</option>
                                        <option value="IPS">IPS (Indigenous People)</option>
                                        <option value="Non-IPS">Non-IPS</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Contact Information -->
                    <div class="form-step" data-step="2">
                        <div class="form-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="mobileNumber">Mobile Number <span class="required">*</span></label>
                                    <div class="phone-input-group">
                                        <span class="phone-prefix">
                                            <img src="../assets/image/contactph.png" alt="PH" class="flag-icon">
                                            +63
                                        </span>
                                        <input type="tel" id="mobileNumber" name="mobileNumber" class="form-control phone-input" placeholder="XXX XXX XXXX" pattern="[0-9 ]+" maxlength="12" oninput="let v=this.value.replace(/\D/g,'').substring(0,10);if(v.length>6)this.value=v.slice(0,3)+' '+v.slice(3,6)+' '+v.slice(6);else if(v.length>3)this.value=v.slice(0,3)+' '+v.slice(3);else this.value=v;" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" id="email" name="email" class="form-control" placeholder="example@email.com">
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="currentAddress">Current Address <span class="required">*</span></label>
                                    <textarea id="currentAddress" name="currentAddress" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Family Information -->
                    <div class="form-step" data-step="3">
                        <div class="form-content">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="civilStatus">Civil Status <span class="required">*</span></label>
                                    <select id="civilStatus" name="civilStatus" class="form-control" required>
                                        <option value="">Select</option>
                                        <option value="Single">Single</option>
                                        <option value="Married">Married</option>
                                        <option value="Widowed">Widowed</option>
                                        <option value="Separated">Separated</option>
                                        <option value="Divorced">Divorced</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="spouseName">Spouse Name</label>
                                    <input type="text" id="spouseName" name="spouseName" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="fatherName">Father's Name</label>
                                    <input type="text" id="fatherName" name="fatherName" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="motherName">Mother's Name</label>
                                    <input type="text" id="motherName" name="motherName" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="numberOfChildren">Number of Children</label>
                                    <input type="number" id="numberOfChildren" name="numberOfChildren" class="form-control" min="0" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4: Emergency Contact -->
                    <div class="form-step" data-step="4">
                        <div class="form-content">
                            <div class="emergency-contacts-header" style="margin-bottom: 15px">
                                <button type="button" class="btn btn-primary btn-sm" id="addContactBtn">
                                    <i class="fas fa-plus"></i>
                                    Add Contact
                                </button>
                            </div>
                            
                            <!-- Emergency Contacts Container -->
                            <div id="emergencyContactsContainer">
                                <!-- Contacts will be loaded dynamically -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 5: Education & Employment -->
                    <div class="form-step" data-step="5">
                        <div class="form-content">
                            <div class="form-grid">
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
                                
                                <div class="form-group">
                                    <label for="employmentStatus">Employment Status</label>
                                    <select id="employmentStatus" name="employmentStatus" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Employed">Employed</option>
                                        <option value="Unemployed">Unemployed</option>
                                        <option value="Self-Employed">Self-Employed</option>
                                        <option value="Student">Student</option>
                                        <option value="Retired">Retired</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="occupation">Occupation</label>
                                    <input type="text" id="occupation" name="occupation" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="monthlyIncome">Monthly Income</label>
                                    <select id="monthlyIncome" name="monthlyIncome" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Below 5000">Below ₱5,000</option>
                                        <option value="5000-10000">₱5,000 - ₱10,000</option>
                                        <option value="10000-20000">₱10,000 - ₱20,000</option>
                                        <option value="20000-30000">₱20,000 - ₱30,000</option>
                                        <option value="30000-50000">₱30,000 - ₱50,000</option>
                                        <option value="Above 50000">Above ₱50,000</option>
                                    </select>
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
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="fourPs">4Ps Member</label>
                                    <select id="fourPs" name="fourPs" class="form-control">
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="fourpsIdGroup" style="display: none;">
                                    <label for="fourpsId">4Ps ID Number</label>
                                    <input type="text" id="fourpsId" name="fourpsId" class="form-control" placeholder="Enter 4Ps ID Number">
                                </div>
                            </div>
                            
                            <div class="form-grid" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label for="voterStatus">Voter Status</label>
                                    <select id="voterStatus" name="voterStatus" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                
                                <div class="form-group" id="precinctNumberGroup" style="display: none;">
                                    <label for="precinctNumber">Precinct Number</label>
                                    <input type="text" id="precinctNumber" name="precinctNumber" class="form-control" placeholder="Enter Precinct Number">
                                </div>
                            </div>
                            
                            <!-- Health Information Section -->
                            <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-heartbeat"></i> Health Information</h5>
                            <hr style="margin: 0 0 20px 0;">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="philhealthId">Philhealth ID Number</label>
                                    <input type="number" id="philhealthId" name="philhealthId" class="form-control" placeholder="Enter Philhealth ID">
                                </div>
                                
                                <div class="form-group">
                                    <label for="membershipType">Membership Type</label>
                                    <select id="membershipType" name="membershipType" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Member">Member</option>
                                        <option value="Dependent">Dependent</option>
                                        <option value="None">None</option>
                                    </select>
                                </div>
                                
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
                                
                                <div class="form-group">
                                    <label for="ageHealthGroup">Classification by Age/Health Group</label>
                                    <select id="ageHealthGroup" name="ageHealthGroup" class="form-control">
                                        <option value="">Select</option>
                                        <option value="Newborn (0-28 days)">Newborn (0-28 days)</option>
                                        <option value="Infant (29 days - 1 year)">Infant (29 days - 1 year)</option>
                                        <option value="Child (1-9 years)">Child (1-9 years)</option>
                                        <option value="Adolescent (10-19 years)">Adolescent (10-19 years)</option>
                                        <option value="Adult (20-59 years)">Adult (20-59 years)</option>
                                        <option value="Senior Citizen (60+ years)">Senior Citizen (60+ years)</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="medicalHistory">Medical History</label>
                                    <textarea id="medicalHistory" name="medicalHistory" class="form-control" rows="3" placeholder="Enter any medical conditions, allergies, or health concerns..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Women's Reproductive Health Section (Conditional) -->
                            <div id="wraSection" style="display: none;">
                                <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-female"></i> Women's Reproductive Health (WRA)</h5>
                                <hr style="margin: 0 0 20px 0;">
                                
                                <div class="form-grid">
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
                            
                            <!-- Remarks Section -->
                            <h5 style="margin: 30px 0 15px 0; color: var(--primary-color);"><i class="fas fa-sticky-note"></i> Additional Notes</h5>
                            <hr style="margin: 0 0 20px 0;">
                            
                            <div class="form-grid">
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
    
    <!-- Custom JavaScript -->
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/edit-resident.js"></script>
   <script>
        // Override phone number formatting to use spaces instead of hyphens
        function formatPhoneNumber(value) {
            const numbers = value.replace(/\D/g, '');
            const limited = numbers.substring(0, 10);
            if (limited.length <= 3) return limited;
            if (limited.length <= 6) return limited.substring(0, 3) + ' ' + limited.substring(3);
            return limited.substring(0, 3) + ' ' + limited.substring(3, 6) + ' ' + limited.substring(6);
        }

        // Fix sidebar links for subdirectory (handles hardcoded links in sidebar)
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarLinks = document.querySelectorAll('.sidebar a, .sidebar-wrapper a, .nav-item a');
            sidebarLinks.forEach(link => {
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
    <div id="reviewModal" class="review-modal">
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
                    
                    <!-- Emergency Contact Section -->
                    <div class="review-section">
                        <div class="review-section-header">
                            <div class="review-section-icon" style="background-color: #ef4444;">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="review-section-title">
                                <h4>Emergency Contact</h4>
                                <p>Emergency contact persons</p>
                            </div>
                        </div>
                        <div class="review-section-content" id="reviewEmergencyContact">
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
                <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">
                    <i class="fas fa-edit"></i>
                    Edit Information
                </button>
                <button type="button" class="btn btn-success" id="finalSubmitBtn" onclick="submitFormFromReview()">
                    <i class="fas fa-check"></i>
                    Confirm & Update
                </button>
            </div>
        </div>
    </div>
</body>
</html>
