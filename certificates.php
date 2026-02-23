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
                                       autocomplete="off">
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
                            DATE <span class="required-star">*</span>
                        </label>
                        <input type="date"
                               id="indigencyDate"
                               class="form-control cert-input"
                               value="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>

                    <!-- Type of Assistance -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">TYPE OF ASSISTANCE</label>
                        <input type="text"
                               id="indigencyAssistance"
                               class="form-control cert-input"
                               placeholder="e.g., Medical, Educational, Financial">
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
                        Print Certificate
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
                                       autocomplete="off">
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
                            DATE <span class="required-star">*</span>
                        </label>
                        <input type="date"
                               id="residencyDate"
                               class="form-control cert-input"
                               value="<?php echo date('Y-m-d'); ?>"
                               required>
                    </div>

                    <!-- Purpose -->
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE <small class="text-muted" style="text-transform:none;font-weight:400;">(for "issued upon request... for ___")</small></label>
                        <input type="text"
                               id="residencyPurpose"
                               class="form-control cert-input"
                               placeholder="e.g., Employment, Scholarship, Loan Application">
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
                        Print Certificate
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
                                <input type="text" id="lowIncomeResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off">
                                <input type="hidden" id="lowIncomeResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="lowIncomeResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="lowIncomeResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">DATE <span class="required-star">*</span></label>
                        <input type="date" id="lowIncomeDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="lowIncomePurpose" class="form-control cert-input" value="Low Income Verification" placeholder="e.g., Medical Assistance">
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="lowIncomePrintBtn"><i class="fas fa-print"></i> Print Certificate</button>
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
                                <input type="text" id="soloParentResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off">
                                <input type="hidden" id="soloParentResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="soloParentResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="soloParentResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">DATE <span class="required-star">*</span></label>
                        <input type="date" id="soloParentDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="soloParentPurpose" class="form-control cert-input" value="Solo Parent Verification" placeholder="e.g., Financial Assistance">
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="soloParentPrintBtn"><i class="fas fa-print"></i> Print Certificate</button>
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
                    <div class="cert-field-group">
                        <label class="cert-field-label">RESIDENT FULL NAME <span class="required-star">*</span></label>
                        <div class="resident-search-wrap">
                            <div class="resident-input-group">
                                <input type="text" id="rbcResidentName" class="form-control cert-input" placeholder="Select resident" autocomplete="off">
                                <input type="hidden" id="rbcResidentId">
                                <button type="button" class="btn btn-primary btn-resident" id="rbcResidentBtn"><i class="fas fa-user"></i> RESIDENT</button>
                            </div>
                            <div class="resident-dropdown" id="rbcResidentDropdown" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">DATE <span class="required-star">*</span></label>
                        <input type="date" id="rbcDate" class="form-control cert-input" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="cert-field-group">
                        <label class="cert-field-label">PURPOSE</label>
                        <input type="text" id="rbcPurpose" class="form-control cert-input" value="Birth Certificate Registration" placeholder="e.g., Late Registration">
                    </div>
                </div>
                <div class="modal-footer cert-modal-footer">
                    <button type="button" class="btn btn-cancel" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
                    <button type="button" class="btn btn-print-cert" id="rbcPrintBtn"><i class="fas fa-print"></i> Print Certificate</button>
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
