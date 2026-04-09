<?php
// Include configuration
require_once 'config.php';

// Check authentication
require_once 'auth_check.php';

// Load permissions
require_once 'permissions.php';

// Enforce: redirect if user lacks view permission
requirePermission('perm_household_view');

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
    <style>
        .print-only { display: none !important; }
    </style>
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
        
        <!-- Households Content -->
        <div class="dashboard-content">
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">View all household profiles, including heads and members. <i class="fas fa-info-circle info-icon"></i></p>
                </div>
                <div class="page-header-actions">
                    <?php if (hasPermission('perm_household_view')): ?>
                    <button class="btn btn-outline-secondary" id="printMasterlistBtn" title="Print Masterlist">
                        <i class="fas fa-print"></i>
                        Print Masterlist
                    </button>
                    <?php endif; ?>
                    <?php if (hasPermission('perm_household_create')): ?>
                    <button class="btn btn-primary" id="createHouseholdBtn" onclick="showCreateHouseholdModal()">
                        <i class="fas fa-plus"></i>
                        Create Household
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Print-Only Header (hidden on screen, visible when printing) -->
            <div class="print-only print-header">
                <div class="print-header-logo">
                    <img src="assets/image/brgylogo.jpg" alt="Barangay Logo" class="print-logo">
                </div>
                <div class="print-header-info">
                    <h2 class="print-barangay-name"><?php echo defined('SITE_NAME') ? htmlspecialchars(SITE_NAME) : 'Barangay Information System'; ?></h2>
                    <h3 class="print-list-title">Household Masterlist</h3>
                    <p class="print-meta">
                        Date Printed: <strong><?php echo date('F d, Y'); ?></strong>
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        Total Records: <strong id="printTotalRecords">0</strong>
                    </p>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="tab-btn active" data-filter="all">All</button>
                <button class="tab-btn" data-filter="single-person">Single-person (1)</button>
                <button class="tab-btn" data-filter="small">Small (2-4)</button>
                <button class="tab-btn" data-filter="medium">Medium (5-7)</button>
                <button class="tab-btn" data-filter="large">Large (8-10)</button>
                <button class="tab-btn" data-filter="very-large">Very Large (11+)</button>
            </div>

              <!-- Search and Filter Bar -->
            <div class="search-filter-bar" style="position: relative;">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search household number or head " id="searchInput">
                    <button class="btn-clear" id="clearSearch">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Filter Button and Panel Wrapper -->
                <div style="position: relative; display: flex; align-items: center;">
                    <button class="btn btn-icon" id="filterBtn" title="Filter" style="position: relative;">
                        <i class="fas fa-filter"></i>
                        <span class="filter-notification" id="filterNotification" style="display: none; position: absolute; top: -5px; right: -5px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px;">
                            <span class="filter-count" id="filterCount">0</span>
                        </span>
                    </button>
                
                    <!-- Advanced Filter Panel -->
                    <div class="filter-panel" id="filterPanel" style="display: none;">
                        <div class="filter-panel-header">
                            <h3><i class="fas fa-filter"></i> Select Filters</h3>
                        </div>
                        <div class="filter-panel-body">
                            <div class="filter-grid">
                                <div class="filter-item">
                                    <label for="filterFamilySize">Household Family Size</label>
                                    <input type="number" id="filterFamilySize" class="filter-select" placeholder="Enter Family Size" min="1">
                                </div>
                            <div class="filter-item">
                                <label for="filterWaterSource">Water Source Type</label>
                                <select id="filterWaterSource" class="filter-select">
                                    <option value="">All</option>
                                    <option value="Level I (Point Spring)">Level I (Point Spring)</option>
                                    <option value="Level II (Communal Faucet system or stand post)">Level II (Communal Faucet system or stand post)</option>
                                    <option value="Level III (Waterworks system or individual house connection)">Level III (Waterworks system or individual house connection)</option>
                                    <option value="O (For doubtful sources, open dug well etc.)">O (For doubtful sources, open dug well etc.)</option>
                                </select>
                            </div>
                            <div class="filter-item">
                                <label for="filterToiletFacility">Toilet Facility Type</label>
                                <select id="filterToiletFacility" class="filter-select">
                                    <option value="">All</option>
                                    <option value="P - Pour/Flush toilet connected to septic tank)">P - (Pour/Flush toilet connected to septic tank)</option>
                                    <option value="PF - Pour/Flush toilet connected to septic tank and sewerage system">PF - Pour/Flush toilet connected to septic tank and sewerage system</option>
                                    <option value="VIP - Ventilated impoved pit latrine (VIP) or composting">VIP - Ventilated impoved pit latrine (VIP) or composting</option>
                                    <option value="WS - Water-sealed connected to open drain">WS - Water-sealed connected to open drain</option>
                                    <option value="OH - Overhung Latrine">OH - Overhung Latrine</option>
                                    <option value="OP - Overpit Latrine">OP - Overpit Latrine</option>
                                    <option value="WO - Without Latrine">WO - Without Latrine</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="filter-panel-footer">
                        <button class="btn btn-secondary" id="clearFiltersBtn">
                            <i class="fas fa-times"></i> Clear
                        </button>
                        <button class="btn btn-primary" id="applyFiltersBtn">
                            <i class="fas fa-check"></i> Apply Now
                        </button>
                    </div>
                </div>
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
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="householdsTableBody">
                        <!-- Data will be loaded from database via JavaScript -->
                        <!-- When loading data, ensure Household Head's name is a link to resident_profile.php -->
                        <!-- Example: <td><a href="resident_profile.php?id={head_resident_id}#household-details">{Household Head Name}</a></td> -->
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
            
            <!-- Print-Only Footer (hidden on screen, visible when printing) -->
            <div class="print-only print-footer" style="margin-top: 60px; width: 100%;">
                <div style="display: flex; justify-content: space-between; padding: 0 50px; width: 100%;">
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Prepared by:</p>
                        <p style="margin: 0; font-size: 14px;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?></p>
                    </div>
                    <div style="text-align: center;">
                        <div style="border-bottom: 1px solid #000; width: 220px; margin-bottom: 8px;"></div>
                        <p style="margin: 0; font-size: 12px; font-weight: 600; text-transform: uppercase;">Certified Correct:</p>
                        <p style="margin: 0; font-size: 14px;">Barangay Captain</p>
                    </div>
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
                                    <option value="Level I (Point Spring)">Level I (Point Spring)</option>
                                    <option value="Level II (Communal Faucet system or stand post)">Level II (Communal Faucet system or stand post)</option>
                                    <option value="Level III (Waterworks system or individual house connection)">Level III (Waterworks system or individual house connection)</option>
                                    <option value="O (For doubtful sources, open dug well etc.)">O (For doubtful sources, open dug well etc.)</option>
                                </select>
                            </div>
                            
                            <div class="form-group half-width">
                                <label for="toiletFacility"><i class="fas fa-toilet"></i> Type of Toilet Facility</label>
                                <select id="toiletFacility" name="toiletFacility" class="form-control">
                                    <option value="">Select</option>
                                     <option value="" readonly>----Sanitary Toilet----</option>
                                     <option value="P - Pour/Flush toilet connected to septic tank)">P - (Pour/Flush toilet connected to septic tank)</option>
                                     <option value="PF - Pour/Flush toilet connected to septic tank and sewerage system">PF - Pour/Flush toilet connected to septic tank and sewerage system</option>
                                     <option value="VIP - Ventilated impoved pit latrine (VIP) or composting">VIP - Ventilated impoved pit latrine (VIP) or composting</option>
                                     <option value="" readonly>----Unsanitary Toilet----</option>
                                     <option value="WS - Water-sealed connected to open drain">WS - Water-sealed connected to open drain</option>
                                     <option value="OH - Overhung Latrine">OH - Overhung Latrine</option>
                                     <option value="OP - Overpit Latrine">OP - Overpit Latrine</option>
                                     <option value="WO - Without Latrine">WO - Without Latrine</option>
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
    
    <!-- Transfer Head Modal -->
    <div id="transferHeadModal" class="household-modal">
        <div class="household-modal-content">
            <div class="household-modal-header">
                <h3><i class="fas fa-exchange-alt"></i> Transfer Household Head</h3>
                <button type="button" class="btn-close-modal" onclick="closeTransferHeadModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="household-modal-body">
                <form id="transferHeadForm">
                    <input type="hidden" id="transferHouseholdId" name="householdId" value="">
                    <input type="hidden" id="transferOldHeadId" name="oldHeadId" value="">
                    
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label>Current Household Head</label>
                        <input type="text" id="transferCurrentHead" class="form-control" readonly style="background-color: #f3f4f6;">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="transferNewHead">Select New Household Head</label>
                        <select id="transferNewHead" name="newHeadId" class="form-control" onchange="updateTransferRelationships()" required>
                            <option value="">Select a member</option>
                        </select>
                    </div>
                    
                    <div class="modal-section" id="transferRelationshipsSection" style="display: none; margin-top: 20px;">
                        <h5 class="section-title">Update Relationships</h5>
                        <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 10px;">Please review and adjust the relationships of the members to the NEW household head.</p>
                        
                        <div class="members-table-container">
                            <table class="members-table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Previous Relationship</th>
                                        <th>New Relationship to Head</th>
                                    </tr>
                                </thead>
                                <tbody id="transferMembersBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="household-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTransferHeadModal()">Cancel</button>
                <button type="button" class="btn btn-success" id="saveTransferHeadBtn" onclick="saveTransferHead()">Save Transfer</button>
            </div>
        </div>
    </div>
    
    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  width: 90%; margin: 10% auto; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="archiveModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Archive Household</h3>
                    <p id="archiveModalDesc" style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure you want to archive this record?</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm.
                </div>
                
                <form id="archiveForm">
                    <input type="hidden" id="archiveRecordId" name="id">
                    <input type="hidden" id="archiveRecordType" name="type">
                    <input type="hidden" id="archiveMemberId" name="member_id">
                    
                    <div style="margin-bottom: 1.25rem;">
                        <label for="archiveReason" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-comment-alt" style="margin-right: 5px;"></i> Reason for Archiving <span style="color: #ef4444;">*</span>
                        </label>
                        <textarea id="archiveReason" name="reason" rows="2" style="width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box; font-family: inherit;" placeholder="Please state the reason..." required></textarea>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label for="archivePassword" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.9rem;">
                            <i class="fas fa-key" style="margin-right: 5px;"></i> Your Password
                        </label>
                        <div style="position: relative;">
                            <input type="password" id="archivePassword" name="password" style="width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--bg-primary); color: var(--text-primary); box-sizing: border-box;" placeholder="Enter your password" required>
                            <button type="button" id="toggleArchivePassword" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; padding: 0;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" id="cancelArchive" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #6b7280; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" id="confirmArchiveBtn" style="padding: 0.6rem 1.5rem; border-radius: 8px; border: none; background-color: #ef4444; color: white; cursor: pointer; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-trash"></i> Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Permission flags for JS -->
    <script>
    window.BIS_PERMS = {
        household_view:   <?php echo hasPermission('perm_household_view')   ? 'true' : 'false'; ?>,
        household_create: <?php echo hasPermission('perm_household_create') ? 'true' : 'false'; ?>,
        household_edit:   <?php echo hasPermission('perm_household_edit')   ? 'true' : 'false'; ?>,
        household_delete: <?php echo hasPermission('perm_household_delete') ? 'true' : 'false'; ?>
    };
    </script>

    <!-- Custom JavaScript -->
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/households.js?v=<?php echo filemtime('assets/js/households.js'); ?>"></script>
</body>
</html>
