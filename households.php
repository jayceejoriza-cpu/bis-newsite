<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Page title
$pageTitle = 'Households';
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
    <link rel="stylesheet" href="assets/css/households.css">
</head>
<body>
    <!-- Sidebar -->
    <?php include 'components/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include 'components/header.php'; ?>
        
        <!-- Households Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View all household profiles, including heads and members. <i class="fas fa-info-circle info-icon"></i></p>
                </div>
                <button class="btn btn-primary" id="createHouseholdBtn">
                    <i class="fas fa-plus"></i>
                    Create Household
                </button>
            </div>
            
          
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="single-person">Single-person</button>
                <button class="tab-btn" data-filter="small">Small</button>
                <button class="tab-btn" data-filter="medium">Medium</button>
                <button class="tab-btn" data-filter="large">Large</button>
                <button class="tab-btn" data-filter="very-large">Very Large</button>
            </div>

              <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search" id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <button class="btn btn-icon" id="refreshBtn">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            
            <!-- Households Table -->
            <div class="table-container">
                <table class="data-table households-table" id="householdsTable">
                    <thead>
                        <tr>
                            <th>Household Number </th>
                            <th>Household Head </th>
                            <th>Household Member</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="householdsTableBody">
                        <!-- Data will be loaded from database via JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="pagination-container">
                <div class="pagination-info">
                    <span>TOTAL: <strong id="totalCount">10</strong></span>
                </div>
                <div class="pagination">
                    <button class="page-btn" id="prevPage">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="page-btn active">1</button>
                    <button class="page-btn" id="nextPage">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Create Household Modal -->
    <div id="createHouseholdModal" class="household-modal">
        <div class="household-modal-content">
            <div class="household-modal-header">
                <h3><i class="fas fa-home"></i> Community Household</h3>
                <button type="button" class="btn-close-modal" onclick="closeCreateHouseholdModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="household-modal-body">
                <form id="createHouseholdForm">
                    <!-- Information Section -->
                    <div class="modal-section">
                        <h5 class="section-title">Information</h5>
                        
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="householdNumber">Household Number</label>
                                <input type="text" id="householdNumber" name="householdNumber" class="form-control" placeholder="Enter Household Number" required>
                            </div>
                            
                            <div class="form-group half-width">
                                <label for="householdContact">Household Contact Number</label>
                                <div class="phone-input-group">
                                    <span class="phone-prefix">
                                        <img src="assets\image\contactph.png" alt="PH" class="flag-icon">
                                        +63
                                    </span>
                                    <input type="tel" id="householdContact" name="householdContact" class="form-control phone-input" placeholder="XXX XXX XXXX">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="householdAddress">Address</label>
                            <textarea id="householdAddress" name="householdAddress" class="form-control" rows="3" placeholder="Enter household address"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half-width">
                                <label for="waterSource"><i class="fas fa-tint"></i> Type of Water Source</label>
                                <select id="waterSource" name="waterSource" class="form-control">
                                    <option value="">Select</option>
                                    <option value="Level I (Point Source)">Level I (Point Source)</option>
                                    <option value="Level II (Communal Faucet)">Level II (Communal Faucet)</option>
                                    <option value="Level III (Individual Connection)">Level III (Individual Connection)</option>
                                    <option value="Dug Well">Dug Well</option>
                                    <option value="Spring">Spring</option>
                                    <option value="Rainwater">Rainwater</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            
                            <div class="form-group half-width">
                                <label for="toiletFacility"><i class="fas fa-toilet"></i> Type of Toilet Facility</label>
                                <select id="toiletFacility" name="toiletFacility" class="form-control">
                                    <option value="">Select</option>
                                    <option value="Water Sealed">Water Sealed</option>
                                    <option value="Closed Pit">Closed Pit</option>
                                    <option value="Open Pit">Open Pit</option>
                                    <option value="None">None</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="householdNotes">Notes</label>
                            <textarea id="householdNotes" name="householdNotes" class="form-control" rows="3" placeholder="Additional notes or remarks"></textarea>
                        </div>
                        
                        <button type="button" class="btn btn-search" id="searchResidentBtn">
                            <i class="fas fa-search"></i>
                            Search Resident
                        </button>
                    </div>
                    
                    <!-- Household Head Section -->
                    <div class="modal-section">
                        <h5 class="section-title">Household Head</h5>
                        
                        <div class="household-head-info">
                            <div class="head-info-row">
                                <div class="head-info-item">
                                    <span class="head-info-label">Full Name</span>
                                    <span class="head-info-value" id="headFullName">N/A</span>
                                </div>
                                <div class="head-info-item">
                                    <span class="head-info-label">Date of Birth</span>
                                    <span class="head-info-value" id="headDateOfBirth">N/A</span>
                                </div>
                                <div class="head-info-item">
                                    <span class="head-info-label">Sex</span>
                                    <span class="head-info-value" id="headSex">N/A</span>
                                </div>
                                <div class="head-info-item">
                                    <span class="head-info-label">Mobile Number</span>
                                    <span class="head-info-value" id="headmobilenumber">N/A</span>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" id="selectedResidentId" name="selectedResidentId" value="">
                    </div>
                    
                    <!-- Household Members Section -->
                    <div class="modal-section">
                        <div class="members-header">
                            <h5 class="section-title">Household Members</h5>
                            <button type="button" class="btn btn-primary btn-sm" id="addMemberBtn">
                                <i class="fas fa-plus"></i>
                                Add Member
                            </button>
                        </div>
                        
                        <div class="members-table-container">
                            <table class="members-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Date of Birth</th>
                                        <th>Sex</th>
                                        <th>Relationship to Head</th>
                                        <th>Mobile Number</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="membersTableBody">
                                    <tr class="no-members-row">
                                        <td colspan="7" style="text-align: center; color: var(--text-secondary); padding: 20px;">
                                            No members added yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="household-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateHouseholdModal()">
                    Cancel
                </button>
                <button type="button" class="btn btn-success" id="saveHouseholdBtn">
                    Save
                </button>
            </div>
        </div>
    </div>
    
    <!-- Search Resident Modal -->
    <div id="searchResidentModal" class="search-resident-modal">
        <div class="search-resident-modal-content">
            <div class="search-resident-modal-header">
                <h4><i class="fas fa-search"></i> Search Resident</h4>
                <button type="button" class="btn-close-search-modal" onclick="closeSearchResidentModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-resident-modal-body">
                <p class="search-subtitle">Add from Resident List</p>
                <div class="search-input-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="residentSearchInput" class="search-input" placeholder="Search Full Name, Barangay ID">
                </div>
                <div class="residents-list" id="residentsListContainer">
                    <div class="loading-residents">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Loading residents...</span>
                    </div>
                </div>
                <button type="button" class="btn-show-more" id="showMoreBtn" style="display: none;">
                    Show More <i class="fas fa-chevron-down"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Add Member Modal -->
    <div id="addMemberModal" class="add-member-modal">
        <div class="add-member-modal-content">
            <div class="add-member-modal-body">
                <form id="addMemberForm">
                    <div class="form-group">
                        <label for="memberFullName">Full Name</label>
                        <div class="member-name-input-group">
                            <input type="text" id="memberFullName" name="memberFullName" class="form-control member-name-input" placeholder="Enter full name">
                            <button type="button" class="btn-resident-search" id="searchMemberResidentBtn">
                                RESIDENT
                            </button>
                            <button type="button" class="btn-reset-member" id="resetMemberBtn" title="Reset form">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="memberSex">Sex</label>
                            <select id="memberSex" name="memberSex" class="form-control">
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        
                        <div class="form-group half-width">
                            <label for="memberDateOfBirth">Date of Birth</label>
                            <input type="date" id="memberDateOfBirth" name="memberDateOfBirth" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="memberRelationship">Relationship to Head</label>
                        <input type="text" id="memberRelationship" name="memberRelationship" class="form-control" placeholder="e.g., Spouse, Child, Parent">
                    </div>
                    
                    <div class="form-group">
                        <label for="memberMobile">Mobile Number</label>
                        <div class="phone-input-group">
                            <span class="phone-prefix">
                               <img src="assets\image\contactph.png" alt="PH" class="flag-icon">
                                +63
                            </span>
                            <input type="tel" id="memberMobile" name="memberMobile" class="form-control phone-input" placeholder="XXX XXX XXXX">
                        </div>
                    </div>
                    
                    <input type="hidden" id="selectedMemberResidentId" name="selectedMemberResidentId" value="">
                    
                    <div class="add-member-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()">
                            CANCEL
                        </button>
                        <button type="button" class="btn btn-primary" id="confirmAddMemberBtn">
                            <i class="fas fa-plus"></i>
                            ADD
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/households.js"></script>
</body>
</html>
