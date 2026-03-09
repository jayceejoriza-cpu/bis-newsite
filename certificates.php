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
    ],
    [
        'title'       => 'Certificate of Residency',
        'description' => 'For residents who need proof of residency',
        'icon'        => 'fa-home',
        'modal'       => 'residencyModal',
    ],
    [
        'title'       => 'Certificate of Low Income',
        'description' => 'For residents who need proof of low income',
        'icon'        => 'fa-money-bill-wave',
        'modal'       => 'lowIncomeModal',
    ],
    [
        'title'       => 'Certificate of Solo Parent',
        'description' => 'For residents who need proof of solo parent status',
        'icon'        => 'fa-user-friends',
        'modal'       => 'soloParentModal',
    ],
    [
        'title'       => 'Registration of Birth Certificate',
        'description' => 'For late registration of birth',
        'icon'        => 'fa-baby',
        'modal'       => 'rbcModal',
    ],
    [
        'title'       => 'Barangay Clearance',
        'description' => 'For residents who need barangay clearance',
        'icon'        => 'fa-file-signature',
        'modal'       => 'brgyClearanceModal',
    ],
    [
        'title'       => 'Barangay Business Clearance',
        'description' => 'For business owners who need clearance',
        'icon'        => 'fa-briefcase',
        'modal'       => 'brgyBusinessClearanceModal',
    ],
    [
        'title'       => 'Business Permit',
        'description' => 'For business permit endorsement',
        'icon'        => 'fa-store',
        'modal'       => 'businessPermitModal',
    ],
    [
        'title'       => 'Fishing Clearance',
        'description' => 'For residents who need fishing clearance',
        'icon'        => 'fa-fish',
        'modal'       => 'fishingClearanceModal',
    ],
    [
        'title'       => 'First Time Jobseeker Assistance',
        'description' => 'For residents availing RA 11261 benefits',
        'icon'        => 'fa-briefcase',
        'modal'       => 'ftJobseekerModal',
    ],
    [
        'title'       => 'Certificate of Good Moral Character',
        'description' => 'For residents who need proof of good moral character',
        'icon'        => 'fa-id-card',
        'modal'       => 'gmrcModal',
    ],
    [
        'title'       => 'Oath of Undertaking',
        'description' => 'For First-Time Jobseeker applicants',
        'icon'        => 'fa-file-signature',
        'modal'       => 'oathModal',
    ],
    [
        'title'       => 'Certificate for Vessel Docking',
        'description' => 'For vessel owners who need docking certification',
        'icon'        => 'fa-anchor',
        'modal'       => 'vesselDockingModal',
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
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
    <!-- Dark Mode Init: must be in <head> to prevent flash of light mode -->
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

                <button class="btn btn-icon" id="refreshBtn" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <!-- Certificates Grid -->
            <div class="certificates-grid" id="certificatesGrid">
                <?php if (empty($certificateTypes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-certificate"></i>
                        <p>No certificates available</p>
                        <p class="empty-subtitle">Add certificate entries to the <code>$certificateTypes</code> array in <code>certificates.php</code></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($certificateTypes as $cert): ?>
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
                            <div class="certificate-icon">
                                <i class="fas <?php echo htmlspecialchars($cert['icon']); ?>"></i>
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
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- ============================================
         Certificate of Indigency Modal
         ============================================ -->
    <div class="modal fade" id="indigencyModal" tabindex="-1" aria-labelledby="indigencyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cert-request-modal">
                <!-- Modal Header -->
                <div class="modal-header cert-modal-header">
                    <div class="cert-modal-title-wrap">
                        <i class="fas fa-file-alt cert-modal-icon"></i>
                        <h5 class="modal-title" id="indigencyModalLabel">Create Certificate of Indigency Request</h5>
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
                                       id="indigencyResidentName"
                                       class="form-control cert-input"
                                       placeholder="Select resident"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" id="indigencyResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="indigencyResidentBtn">
                                    <i class="fas fa-user"></i>
                                    RESIDENT
                                </button>
                            </div>
                            <!-- Autocomplete Dropdown -->
                            <div class="resident-dropdown" id="indigencyResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">
                             ISSUED DATE <span class="required-star">*</span>
                        </label>
                        <input type="date"
                               id="indigencyDate"
                               class="form-control cert-input"
                               value="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>

                    <!-- Type of Assistance -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">TYPE OF ASSISTANCE<span class="required-star">*</span></label>
                        <select id="indigencyAssistance" class="form-control cert-input" required>
                            <option value="FINANCIAL">FINANCIAL</option>
                            <option value="MEDICAL">MEDICAL</option>
                            <option value="BURIAL">BURIAL</option>
                            <option value="SCHOLARSHIP">SCHOLARSHIP</option>
                            <option value="EDUCATIONAL">EDUCATIONAL</option>
                        </select>
                    </div>

                </div>

                <!-- Modal Footer -->
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
                                       placeholder="Select resident"
                                       autocomplete="off"
                                       required>
                                <input type="hidden" id="residencyResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="residencyResidentBtn">
                                    <i class="fas fa-user"></i>
                                    RESIDENT
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
                               required>
                    </div>

                    <!-- Purpose -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <select id="residencyPurpose" class="form-control cert-input">
                            <option value="">Select assistance type</option>
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
                        <h5 class="modal-title" id="lowIncomeModalLabel">Create Low Income Certificate Request</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body cert-modal-body">
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
<input type="text" id="lowIncomeResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="lowIncomeResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="lowIncomeResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="lowIncomeResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="lowIncomeDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="lowIncomePurpose" class="form-control cert-input" value="Low Income Verification" placeholder="e.g., Medical Assistance">
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
<input type="text" id="soloParentResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="soloParentResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="soloParentResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="soloParentResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="soloParentDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="soloParentPurpose" class="form-control cert-input" value="Solo Parent Verification" placeholder="e.g., Financial Assistance">
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
                                <button type="button" class="btn btn-primary btn-resident" id="rbcResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
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
                                <button type="button" class="btn btn-primary btn-resident" id="rbcChildBtn"><i class="fas fa-child"></i> CHILD</button>
                            </div>
                            <div class="resident-dropdown" id="rbcChildDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="rbcDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="rbcPurpose" class="form-control cert-input" value="Birth Certificate Registration" placeholder="e.g., Late Registration">
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
<input type="text" id="fishingResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="fishingResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="fishingResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
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
                        <input type="date" id="fishingDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="fishingPurpose" class="form-control cert-input" value="Boat Registration" placeholder="e.g., Boat Registration">
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
<input type="text" id="ftJobseekerResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="ftJobseekerResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="ftJobseekerResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="ftJobseekerResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="ftJobseekerDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="ftJobseekerPurpose" class="form-control cert-input" value="First Time Jobseeker Assistance (RA 11261)" placeholder="e.g., Employment" readonly>
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
<input type="text" id="gmrcResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="gmrcResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="gmrcResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="gmrcResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="gmrcDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <select id="gmrcPurpose" class="form-control cert-input">
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
<input type="text" id="oathResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="oathResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="oathResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="oathResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="oathDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
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
<input type="text" id="brgyClearanceResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="brgyClearanceResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="brgyClearanceResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="brgyClearanceResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">ISSUED DATE <span class="required-star">*</span></label>
                        <input type="date" id="brgyClearanceDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <select id="brgyClearancePurpose" class="form-control cert-input">
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
                                <input type="text" id="brgyBusinessClearanceResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="brgyBusinessClearanceResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="brgyBusinessClearanceResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="brgyBusinessClearanceResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS NAME <span class="required-star">*</span></label>
                        <input type="text" id="brgyBusinessClearanceBusinessName" class="form-control cert-input" placeholder="Enter business name" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS ADDRESS</label>
                        <input type="text" id="brgyBusinessClearanceBusinessAddress" class="form-control cert-input" placeholder="Enter business address">
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NATURE OF BUSINESS</label>
                        <select id="brgyBusinessClearanceNature" class="form-control cert-input">
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
                        <input type="date" id="brgyBusinessClearanceDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
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
                                <input type="text" id="businessPermitResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="businessPermitResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="businessPermitResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="businessPermitResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS NAME <span class="required-star">*</span></label>
                        <input type="text" id="businessPermitBusinessName" class="form-control cert-input" placeholder="Enter business name" required>

                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">BUSINESS ADDRESS</label>
                        <input type="text" id="businessPermitBusinessAddress" class="form-control cert-input" placeholder="Enter business address">
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">NATURE OF BUSINESS</label>
                        <select id="businessPermitNature" class="form-control cert-input">
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
                        <input type="date" id="businessPermitDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
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
<input type="text" id="vesselDockingResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off" required>
                                <input type="hidden" id="vesselDockingResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="vesselDockingResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
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
                        <input type="date" id="vesselDockingDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="vesselDockingPrintBtn"><i class="fas fa-print"></i> Generate Certificate</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/certificates.js"></script>
</body>
</html>
