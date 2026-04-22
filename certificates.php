<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Page title
$pageTitle = 'Certificates';

// ============================================
// Static Certificate Types
// Each entry can have either:
//   'link'  => 'some-file.php'   (navigates directly)
//   'modal' => 'modalId'         (opens a modal first)
// ============================================
$certificateTypes = [
    [
        'title'       => 'Certificate of Indigency',
        'description' => 'For residents who need proof of indigency',
        'icon'        => 'fa-file-alt',
        'modal'       => 'indigencyModal',
        'category'    => 'Social Services & Financial Assistance'
    ],
    [
        'title'       => 'Certificate of Residency',
        'description' => 'For residents who need proof of residency',
        'icon'        => 'fa-home',
        'modal'       => 'residencyModal',
        'category'    => 'Personal Identification & Records'
    ],
    [
        'title'       => 'Certificate of Low Income',
        'description' => 'For residents who need proof of low income',
        'icon'        => 'fa-money-bill-wave',
        'modal'       => 'lowIncomeModal',
        'category'    => 'Social Services & Financial Assistance'
    ],
    [
        'title'       => 'Certificate of Solo Parent',
        'description' => 'For residents who need proof of solo parent status',
        'icon'        => 'fa-user-friends',
        'modal'       => 'soloParentModal',
        'category'    => 'Social Services & Financial Assistance'
    ],
    [
        'title'       => 'Registration of Birth Certificate',
        'description' => 'For late registration of birth',
        'icon'        => 'fa-baby',
        'modal'       => 'rbcModal',
        'category'    => 'Personal Identification & Records'
    ],
    [
        'title'       => 'Barangay Clearance',
        'description' => 'For residents who need barangay clearance',
        'icon'        => 'fa-file-signature',
        'modal'       => 'brgyClearanceModal',
        'category'    => 'Personal Identification & Records'
    ],
    [
        'title'       => 'Barangay Business Clearance',
        'description' => 'For business owners who need clearance',
        'icon'        => 'fa-briefcase',
        'modal'       => 'brgyBusinessClearanceModal',
        'category'    => 'Business & Livelihood'
    ],
    [
        'title'       => 'Business Permit',
        'description' => 'For business permit endorsement',
        'icon'        => 'fa-store',
        'modal'       => 'businessPermitModal',
        'category'    => 'Business & Livelihood'
    ],
    [
        'title'       => 'Fishing Clearance',
        'description' => 'For residents who need fishing clearance',
        'icon'        => 'fa-fish',
        'modal'       => 'fishingClearanceModal',
        'category'    => 'Business & Livelihood'
    ],
    [
        'title'       => 'First Time Jobseeker Assistance',
        'description' => 'For residents availing RA 11261 benefits',
        'icon'        => 'fa-briefcase',
        'modal'       => 'ftJobseekerModal',
        'category'    => 'Employment & Education'
    ],
    [
        'title'       => 'Certificate of Good Moral Character',
        'description' => 'For residents who need proof of good moral character',
        'icon'        => 'fa-id-card',
        'modal'       => 'gmrcModal',
        'category'    => 'Employment & Education'
    ],
    [
        'title'       => 'Oath of Undertaking',
        'description' => 'For First-Time Jobseeker applicants',
        'icon'        => 'fa-file-signature',
        'modal'       => 'oathModal',
        'category'    => 'Employment & Education'
    ],
    [
        'title'       => 'Certificate for Vessel Docking',
        'description' => 'For vessel owners who need docking certification',
        'icon'        => 'fa-anchor',
        'modal'       => 'vesselDockingModal',
        'category'    => 'Business & Livelihood'
    ],
];

// Group certificates by category
$groupedCertificates = [];
foreach ($certificateTypes as $cert) {
    $category = $cert['category'] ?? 'Other';
    if (!isset($groupedCertificates[$category])) {
        $groupedCertificates[$category] = [];
    }
    $groupedCertificates[$category][] = $cert;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="uploads/favicon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/certificates.css">
    <style>
        .modal-body {
        max-height: 100vh;
        overflow-y: auto;
        background-color: var(--bg-primary);
        transition: var(--color-transition);
}
        .certificate-card .card-body {
            position: relative;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .certificate-photo {
            width: 100%;
            height: 190px;
            background-size: cover;
            background-position: center;
            transition: transform 0.3s ease;
        }
        .certificate-card:hover .certificate-photo {
            transform: scale(1.1); /* hover preview effect */
        }
        .preview-photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        body:not(.edit-mode-active) .certificate-card:hover .preview-photo-overlay {
            opacity: 1;
        }
        .edit-photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        body.edit-mode-active .certificate-card-clickable {
            cursor: default; /* Disable normal click navigation */
        }
        body.edit-mode-active .edit-photo-overlay {
            display: flex !important;
        }
        #editCertPhotoBtn.active {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
    <!-- Dark Mode Init: must be in <head>
<link rel="icon" type="image/png" href="uploads/favicon.png"> to prevent flash of light mode -->
    <script src="assets/js/dark-mode-init.js"></script>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>

        <!-- Certificates Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">Select a certificate type to issue</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="search-actions-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search certificates..." id="searchInput">
                    <button class="btn-clear" id="clearSearch" style="display:none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <?php if (hasPermission('perm_cert_edit')): ?>
                <button class="btn btn-icon" id="editCertPhotoBtn" title="Edit Photos">
                    <i class="fas fa-edit"></i>
                </button>
                <?php endif; ?>
               
            </div>

            <!-- Certificates Container -->
            <div id="certificatesGridContainer">
                <?php if (empty($certificateTypes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-certificate"></i>
                        <p>No certificates available</p>
                        <p class="empty-subtitle">Add certificate entries to the <code>$certificateTypes</code> array in <code>certificates.php</code></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupedCertificates as $categoryName => $certs): ?>
                        <div class="category-section" style="margin-bottom: 30px;">
                            <h3 class="category-title" style="margin-bottom: 15px; font-size: 1.1rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">
                                <?php echo htmlspecialchars($categoryName); ?>
                            </h3>
                            <div class="certificates-grid" style="margin-bottom: 0;">
                                <?php foreach ($certs as $cert): ?>
                                <?php 
                                    $certId = !empty($cert['modal']) ? $cert['modal'] : md5($cert['title']);
                                    $photoPath = "assets/uploads/certificates/{$certId}.jpg";
                                    $hasPhoto = file_exists($photoPath);
                                    $photoUrl = $hasPhoto ? $photoPath . '?v=' . filemtime($photoPath) : '';
                                ?>
                                <div class="certificate-card certificate-card-clickable"
                                     data-title="<?php echo htmlspecialchars(strtolower($cert['title'])); ?>"
                                     <?php if (!empty($cert['modal'])): ?>
                                     data-modal="<?php echo htmlspecialchars($cert['modal']); ?>"
                                     <?php elseif (!empty($cert['link'])): ?>
                                     data-link="<?php echo htmlspecialchars($cert['link']); ?>"
                                     <?php endif; ?>>
                                    <div class="card-header">
                                        <h3 class="card-title"><?php echo htmlspecialchars($cert['title']); ?></h3>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($hasPhoto): ?>
                                        <div class="certificate-photo" style="background-image: url('<?php echo htmlspecialchars($photoUrl); ?>');"></div>
                                        <?php else: ?>
                                        <div class="certificate-icon">
                                            <i class="fas <?php echo htmlspecialchars($cert['icon']); ?>"></i>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($hasPhoto): ?>
                                        <div class="preview-photo-overlay">
                                            <button class="btn btn-primary btn-sm preview-cert-btn" data-photo-url="<?php echo htmlspecialchars($photoUrl); ?>">
                                                <i class="fas fa-search-plus"></i> Preview
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="edit-photo-overlay" style="display: none;">
                                            <button class="btn btn-light btn-sm upload-cert-btn" data-cert-id="<?php echo htmlspecialchars($certId); ?>">
                                                <i class="fas fa-camera"></i> <?php echo $hasPhoto ? 'Change Photo' : 'Add Photo'; ?>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <span class="cert-description"><?php echo htmlspecialchars($cert['description']); ?></span>
                                        <span class="cert-open-hint">
                                            <i class="fas fa-arrow-right"></i>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>


 <div class="modal fade" id="indigencyModal" tabindex="-1" aria-labelledby="indigencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-file-alt cert-modal-icon"></i>
                        <h5 class="modal-title" id="indigencyModalLabel">Create Certificate of Indigency Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="cert-tabs-container">
                    <button type="button" id="tabForSelf" class="cert-tab-btn active">FOR LEGAL AGE</button>
                    <button type="button" id="tabGuardian" class="cert-tab-btn">FOR MINORS</button>
                </div>

                <div class="modal-body cert-modal-body">
                    
                    <input type="hidden" id="indigencyRequestType" value="self">

                    <div class="cert-field-group">
                        <label class="cert-field-label" id="primaryResidentLabel">
                            RESIDENT FULL NAME <span class="required-star">*</span>
                        </label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text"
                                       id="indigencyResidentName"
                                       class="cert-input"
                                       style="width: 100%;"
                                       placeholder="Enter Resident Name or ID"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" id="indigencyResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="indigencyResidentBtn">
                                    <i class="fas fa-user"></i>
                                    
                                </button>
                            </div>
                            <div class="resident-dropdown" id="indigencyResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="cert-field-group" id="minorResidentGroup" style="display: none;">
                        <label class="cert-field-label">
                            MINOR RESIDENT FULL NAME <span class="required-star">*</span>
                        </label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text"
                                       id="indigencyMinorName"
                                       class="cert-input"
                                       style="width: 100%;"
                                       placeholder="Select minor resident"
                                       autocomplete="off">
                                <input type="hidden" id="indigencyMinorId">
                                <button type="button" class="btn btn-secondary btn-resident" id="indigencyMinorBtn" style="background-color: #6b7280; border-color: #6b7280; color: white;">
                                    <i class="fas fa-child"></i>
                                    MINOR
                                </button>
                            </div>
                            <div class="resident-dropdown" id="indigencyMinorDropdown" style="display:none;"></div>
                        </div>
                    </div>

                    <div class="cert-field-group">
                        <label class="cert-field-label">
                             ISSUED DATE <span class="required-star">*</span>
                        </label>
                        <input type="date"
                               id="indigencyDate"
                               class="cert-input"
                               value="<?php echo date('Y-m-d'); ?>"
                               disabled>
                    </div>

                    <div class="cert-field-group">
                        <label class="cert-field-label">TYPE OF ASSISTANCE<span class="required-star">*</span></label>
                        <select id="indigencyAssistance" class="cert-input" required>
                              <option value="" disabled>Select assistance type</option>
                            <option value="FINANCIAL">FINANCIAL</option>
                            <option value="MEDICAL">MEDICAL</option>
                            <option value="BURIAL">BURIAL</option>
                            <option value="SCHOLARSHIP">SCHOLARSHIP</option>
                            <option value="EDUCATIONAL">EDUCATIONAL</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-print-cert" id="indigencyPrintBtn">
                        <i class="fas fa-print"></i>
                        Generate Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Certificate of Residency Modal
         ============================================ -->
    <div class="modal fade" id="residencyModal" tabindex="-1" aria-labelledby="residencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <!-- Modal Header -->
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-home cert-modal-icon"></i>
                        <h5 class="modal-title" id="residencyModalLabel">Create Certificate of Residency Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body cert-modal-body">

                    <!-- Resident Full Name -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">
                            RESIDENT FULL NAME <span class="required-star">*</span>
                        </label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text"
                                       id="residencyResidentName"
                                       class="form-control cert-input"
                                       placeholder="Enter Resident Name or ID"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" id="residencyResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="residencyResidentBtn">
                                    <i class="fas fa-user"></i>
                                    
                                </button>
                            </div>
                            <!-- Autocomplete Dropdown -->
                            <div class="resident-dropdown" id="residencyResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">
                            ISSUED DATE <span class="required-star">*</span>
                        </label>
                        <input type="date"
                               id="residencyDate"
                               class="form-control cert-input"
                               value="<?php echo date('Y-m-d'); ?>"
                               disabled>
                    </div>

                    <!-- Purpose -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE <span class="required-star">*</span></label>
                        <select id="residencyPurpose" class="form-control cert-input" required>
                            <option value="" disabled>Select assistance type</option>
                            <option value="DEPED RANKING">DEPED RANKING</option>
                            <option value="BANK PURPOSES">BANK PURPOSES</option>
                            <option value="SCHOOL PURPOSES">SCHOOL PURPOSES</option>
                            <option value="MARRIAGE COUNSELLING">MARRIAGE COUNSELLING</option>
                            <option value="SENIOR CETIZEN MEMBERSHIP">SENIOR CETIZEN MEMBERSHIP</option>
                            <option value="PWD MEMBERSHIP">PWD MEMBERSHIP</option>
                         </select>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="button" class="btn btn-print-cert" id="residencyPrintBtn">
                        <i class="fas fa-print"></i>
                        Generate Certificate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Certificate of Low Income Modal
         ============================================ -->
    <div class="modal fade" id="lowIncomeModal" tabindex="-1" aria-labelledby="lowIncomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content cert-request-modal">
            <div class="modal-header cert-modal-header">
                <div class="cert-modal-title-wrap">
                    <i class="fas fa-money-bill-wave cert-modal-icon"></i>
                    <h5 class="modal-title" id="lowIncomeModalLabel">Create Low Income Certificate</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body cert-modal-body">
                <div class="cert-field-group">
                    <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                    <div class="resident-search-wrap">
                        <div class="resident-input-group">
                            <input type="text" id="lowIncomeResidentName" class="form-control cert-input" placeholder="Search resident..." autocomplete="off" required>
                            <input type="hidden" id="lowIncomeResidentId">
                            <button type="button" class="btn btn-primary btn-resident" id="lowIncomeResidentBtn"><i class="fas fa-user"></i></button>
                        </div>
                        <div class="resident-dropdown" id="lowIncomeResidentDropdown" style="display:none;"></div>
                    </div>
                </div>

                <div class="cert-field-group">
                    <label class="cert-field-label">TYPE OF WORK <span class="required-star">*</span></label>
                    <input type="text" id="lowIncomeWork" class="form-control cert-input" placeholder="e.g., Construction Worker" required>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="cert-field-group">
                            <label class="cert-field-label">WORK SINCE (YEAR) <span class="required-star">*</span></label>
                            <input type="number" id="lowIncomeWorkYear" class="form-control cert-input" value="<?= date('Y') ?>" min="1900" max="<?= date('Y') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="cert-field-group">
                            <label class="cert-field-label">MONTHLY INCOME <span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" id="lowIncomeAmount" class="form-control cert-input" placeholder="0.00" step="0.01" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cert-field-group">
                    <label class="cert-field-label">PURPOSE <span class="required-star">*</span></label>
                    <select id="lowIncomePurpose" class="form-control cert-input" required> 
                        <option value="" disabled>Select purpose type</option>
                        <option value="MEDICAL">MEDICAL</option>
                        <option value="FINANCIAL">FINANCIAL</option>
                        <option value="SCHOLARSHIP">SCHOLARSHIP</option>
                    </select>
                </div>

                <div class="cert-field-group">
                    <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                    <input type="date" id="lowIncomeDate" class="form-control cert-input" value="<?= date('Y-m-d') ?>" disabled>
                </div>
            </div>
            <div class="modal-footer cert-modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                <button type="button" class="btn btn-print-cert" id="lowIncomePrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
            </div>
        </div>
    </div>
</div>

    <!-- ============================================
         Certificate of Solo Parent Modal
         ============================================ -->
    <div class="modal fade" id="soloParentModal" tabindex="-1" aria-labelledby="soloParentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-user-friends cert-modal-icon"></i>
                        <h5 class="modal-title" id="soloParentModalLabel">Create Solo Parent Certificate Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text" id="soloParentResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="soloParentResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="soloParentResidentBtn"><i class="fas fa-user"></i></button>
                            </div>
                            <div class="resident-dropdown" id="soloParentResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="soloParentDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="soloParentPurpose" class="form-control cert-input" value="Solo Parent" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="soloParentPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Registration of Birth Certificate Modal
         ============================================ -->
    <div class="modal fade" id="rbcModal" tabindex="-1" aria-labelledby="rbcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-baby cert-modal-icon"></i>
                        <h5 class="modal-title" id="rbcModalLabel">Registration of Birth Certificate Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <!-- Parent/Applicant Full Name -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">PARENT/APPLICANT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="rbcResidentName" class="form-control cert-input" placeholder="Select parent/applicant" autocomplete="off" required>
                                <input type="hidden" id="rbcResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="rbcResidentBtn"><i class="fas fa-user"></i></button>
                            </div>
                            <div class="resident-dropdown" id="rbcResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <!-- Child Full Name -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">CHILD NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="rbcChildName" class="form-control cert-input" placeholder="Select child" autocomplete="off" required>
                                <input type="hidden" id="rbcChildId">
                                <button type="button" class="btn btn-secondary btn-resident" id="rbcChildBtn"><i class="fas fa-child"></i> CHILD</button>
                            </div>
                            <div class="resident-dropdown" id="rbcChildDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="rbcDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="rbcPurpose" class="form-control cert-input" value="Birth Certificate Registration" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="rbcPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Fishing Clearance Modal
         ============================================ -->
    <div class="modal fade" id="fishingClearanceModal" tabindex="-1" aria-labelledby="fishingClearanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-fish cert-modal-icon"></i>
                        <h5 class="modal-title" id="fishingClearanceModalLabel">Create Fishing Clearance Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="fishingResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="fishingResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="fishingResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="fishingResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NAME OF BOAT <span class="required-star">*</span></label>
<input type="text" id="fishingBoatName" class="form-control cert-input" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="fishingDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="fishingPurpose" class="form-control cert-input" value="Boat Registration" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="fishingPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         First Time Jobseeker Assistance Modal
         ============================================ -->
    <div class="modal fade" id="ftJobseekerModal" tabindex="-1" aria-labelledby="ftJobseekerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-briefcase cert-modal-icon"></i>
                        <h5 class="modal-title" id="ftJobseekerModalLabel">First Time Jobseeker Assistance Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="ftJobseekerResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="ftJobseekerResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="ftJobseekerResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="ftJobseekerResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="ftJobseekerDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="ftJobseekerPurpose" class="form-control cert-input" value="First Time Jobseeker Assistance (RA 11261)" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="ftJobseekerPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Certificate of Good Moral Character Modal
         ============================================ -->
    <div class="modal fade" id="gmrcModal" tabindex="-1" aria-labelledby="gmrcModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-id-card cert-modal-icon"></i>
                        <h5 class="modal-title" id="gmrcModalLabel">Create Certificate of Good Moral Character Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="gmrcResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="gmrcResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="gmrcResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="gmrcResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="gmrcDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE <span class="required-star">*</span></label>
                        <select id="gmrcPurpose" class="form-control cert-input" required>
                              <option value="" disabled>Select purpose type</option>
                            <option value="EDUCATIONAL">EDUCATIONAL</option>
                            <option value="DEPED RANKING">DEPED RANKING</option>
                            <option value="BOARD EXAMINATION">BOARD EXAMINATION</option>
                         </select>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="gmrcPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Oath of Undertaking Modal
         ============================================ -->
    <div class="modal fade" id="oathModal" tabindex="-1" aria-labelledby="oathModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-file-signature cert-modal-icon"></i>
                        <h5 class="modal-title" id="oathModalLabel">Oath of Undertaking Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="oathResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="oathResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="oathResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="oathResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="oathDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="oathPurpose" class="form-control cert-input" value="Oath of Undertaking (RA 11261)" placeholder="First-Time Jobseeker" disabled readonly>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="oathPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Barangay Clearance Modal
         ============================================ -->
    <div class="modal fade" id="brgyClearanceModal" tabindex="-1" aria-labelledby="brgyClearanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-file-signature cert-modal-icon"></i>
                        <h5 class="modal-title" id="brgyClearanceModalLabel">Create Barangay Clearance Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="brgyClearanceResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="brgyClearanceResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="brgyClearanceResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="brgyClearanceResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="brgyClearanceDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE <span class="required-star">*</span></label>
                        <select id="brgyClearancePurpose" class="form-control cert-input" required>
                            <option value="">Select purpose</option>
                            <option value="FISHING PERMIT">FISHING PERMIT</option>
                            <option value="FOR EMPLOYMENT">FOR EMPLOYMENT</option>
                            <option value="WORKING PERMIT">WORKING PERMIT</option>
                            <option value="TRICYCLE RENEWAL PERMIT">TRICYCLE RENEWAL PERMIT</option>
                            <option value="LOAN APPLICATION">LOAN APPLICATION</option>
                            <option value="TRICYLE LOAN">TRICYLE LOAN</option>
                            <option value="MOTORCYCLE LOAN">MOTORCYCLE LOAN</option>
                            <option value="CAR LOAN">CAR LOANN</option>
                            <option value="WORK IMMERSION">WORK IMMERSION</option>
                            <option value="BANK PURPOSES">BANK PURPOSES</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="brgyClearancePrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Barangay Business Clearance Modal
         ============================================ -->
    <div class="modal fade" id="brgyBusinessClearanceModal" tabindex="-1" aria-labelledby="brgyBusinessClearanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-briefcase cert-modal-icon"></i>
                        <h5 class="modal-title" id="brgyBusinessClearanceModalLabel">Create Barangay Business Clearance Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS OWNER NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text" id="brgyBusinessClearanceResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="brgyBusinessClearanceResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="brgyBusinessClearanceResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="brgyBusinessClearanceResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS NAME <span class="required-star">*</span></label>
                        <input type="text" id="brgyBusinessClearanceBusinessName" class="form-control cert-input" placeholder="Enter business name" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS ADDRESS <span class="required-star">*</span></label>
                        <input type="text" id="brgyBusinessClearanceBusinessAddress" class="form-control cert-input" placeholder="Enter business address" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NATURE OF BUSINESS <span class="required-star">*</span></label>
                        <select id="brgyBusinessClearanceNature" class="form-control cert-input" required>
                            <option value="">Select nature of business</option>
                            <option value="RETAIL">RETAIL</option>
                            <option value="WHOLESALE">WHOLESALE</option>
                            <option value="SERVICE">SERVICE</option>
                            <option value="MANUFACTURING">MANUFACTURING</option>
                            <option value="RESTAURANT">RESTAURANT</option>
                            <option value="SARI-SARI STORE">SARI-SARI STORE</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="brgyBusinessClearanceDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="brgyBusinessClearancePrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Business Permit Modal
         ============================================ -->
    <div class="modal fade" id="businessPermitModal" tabindex="-1" aria-labelledby="businessPermitModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-store cert-modal-icon"></i>
                        <h5 class="modal-title" id="businessPermitModalLabel">Create Business Permit Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS OWNER NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text" id="businessPermitResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="businessPermitResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="businessPermitResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="businessPermitResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS NAME <span class="required-star">*</span></label>
                        <input type="text" id="businessPermitBusinessName" class="form-control cert-input" placeholder="Enter business name" required>

                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS ADDRESS <span class="required-star">*</span></label>
                        <input type="text" id="businessPermitBusinessAddress" class="form-control cert-input" placeholder="Enter business address"  required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NATURE OF BUSINESS <span class="required-star">*</span></label>
                        <select id="businessPermitNature" class="form-control cert-input" required>
                            <option value="">Select nature of business</option>
                            <option value="RETAIL">RETAIL</option>
                            <option value="WHOLESALE">WHOLESALE</option>
                            <option value="SERVICE">SERVICE</option>
                            <option value="MANUFACTURING">MANUFACTURING</option>
                            <option value="RESTAURANT">RESTAURANT</option>
                            <option value="SARI-SARI STORE">SARI-SARI STORE</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="businessPermitDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="businessPermitPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================
         Certificate for Vessel Docking Modal
         ============================================ -->
    <div class="modal fade" id="vesselDockingModal" tabindex="-1" aria-labelledby="vesselDockingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-anchor cert-modal-icon"></i>
                        <h5 class="modal-title" id="vesselDockingModalLabel">Create Certificate for Vessel Docking Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="vesselDockingResidentName" class="form-control cert-input" placeholder="Enter Resident Name or ID" autocomplete="off" required>
                                <input type="hidden" id="vesselDockingResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="vesselDockingResidentBtn"><i class="fas fa-user"></i> </button>
                            </div>
                            <div class="resident-dropdown" id="vesselDockingResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NAME OF VESSEL <span class="required-star">*</span></label>
<input type="text" id="vesselDockingVesselName" class="form-control cert-input" placeholder="Enter vessel name" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">FROM DATE <span class="required-star">*</span></label>
                        <input type="date" id="vesselDockingFromDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">TO DATE <span class="required-star">*</span></label>
                        <input type="date" id="vesselDockingToDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="vesselDockingDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" disabled>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="vesselDockingPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Certificate Photo Modal -->
    <div class="modal fade" id="uploadCertPhotoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Certificate Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="uploadCertPhotoForm">
                        <input type="hidden" name="cert_id" id="uploadCertId">
                        <div class="mb-3">
                            <label class="form-label">Select Photo (JPG, PNG)</label>
                            <input type="file" name="cert_photo" id="certPhotoInput" class="form-control" accept="image/jpeg, image/png, image/webp" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveCertPhotoBtn">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: fit-content;">
            <div class="modal-content bg-transparent border-0 shadow-none" style="position: relative;">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: -35px; right: 0; filter: invert(1) grayscale(100%) brightness(200%); opacity: 0.8; z-index: 1060;"></button>
                <div class="modal-body text-center p-0">
                    <img src="" id="previewModalImage" class="img-fluid rounded shadow-lg" alt="Certificate Preview" style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        cert_edit: <?php echo hasPermission('perm_cert_edit') ? 'true' : 'false'; ?>,
        cert_generate: <?php echo hasPermission('perm_cert_generate') ? 'true' : 'false'; ?>
    };
    </script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/certificates.js"></script>
</body>
</html>
