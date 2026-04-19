<?php
// Events Page - Barangay Scheduling System
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

requirePermission('perm_events_view');

$pageTitle = 'Barangay Events & Scheduling';

// Fetch upcoming events for the table
$events = [];
if (isset($conn)) {
    try {
        date_default_timezone_set('Asia/Manila');
        $currentDate = date('Y-m-d');

        // Updated query to join with residents table to get the organizer name
        $query = "SELECT e.id, e.title, e.event_date, e.start_time, e.end_time, e.location, e.description, e.event_type, e.status,
                         TRIM(CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, ''))) AS resident_name
                  FROM events e
                  LEFT JOIN residents r ON e.resident_id = r.id
                  WHERE e.event_date >= ?
                  ORDER BY e.event_date ASC, e.start_time ASC";
                  
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Table 'events' might be missing. Please run the SQL setup.");
        }
        $stmt->bind_param("s", $currentDate);
        $stmt->execute();
        $res = $stmt->get_result();
        
        while ($row = $res->fetch_assoc()) {
            $events[] = $row;
        }
    } catch (Throwable $e) {
        $eventError = $e->getMessage();
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
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/events.css">
    <style>
        .no-print { display: block; }
        @media print { .no-print { display: none !important; } }
    </style>
    
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css' rel='stylesheet' />
    
    <!-- Dark Mode Init -->
    <script src="assets/js/dark-mode-init.js"></script>
    <style>
        .cert-tab-btn.active {
            color: var(--primary-color) !important;
            border-bottom-color: var(--primary-color) !important;
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        .autocomplete-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }
        .autocomplete-item:hover {
            background-color: var(--bg-secondary);
        }
        .autocomplete-item strong {
            color: var(--primary-color);
        }
        .day-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-secondary);
            transition: all 0.2s;
        }
        .day-btn.active {
            background-color: var(--primary-color);
            color: white !important;
            border-color: var(--primary-color);
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
        
        <!-- Events Content -->
        <div class="dashboard-content">
            <!-- Page Header -->
            <div class="page-header-section">
                <div>
                    <h1 class="page-title"><?php echo $pageTitle; ?></h1>
                    <p class="page-subtitle">Community events calendar and scheduling system</p>
                </div>
                <div class="page-header-actions">
                    <?php if (hasPermission('perm_events_create')): ?>
                    <button class="btn btn-primary create-event-btn" title="Create New Event">
                        <i class="fas fa-plus"></i>
                        Create New Event
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Big Monthly Calendar -->
            <div class="calendar-container">
                <div id='calendar'></div>
            </div>
            
            <!-- Events List (below calendar) -->
            <div class="events-list-section">
                <div class="section-header no-print">
                    <h3><i class="fas fa-list"></i> Community Events List</h3>
                </div>

                <!-- Filter Tabs -->
                <div class="filter-tabs no-print" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="tab-btn active" data-filter="all">All</button>
                    <button class="tab-btn" data-filter="Active">Rescheduled</button>
                    <button class="tab-btn" data-filter="Postponed">Postponed</button>
                </div>

                <!-- Search and Filter Bar -->
                <div class="search-filter-bar no-print" style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <div class="search-box" style="flex: 1; max-width: 400px; position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 12px; color: var(--text-secondary);"></i>
                        <input type="text" placeholder="Search event title or location..." id="eventSearchInput" style="width: 100%; padding-left: 35px; height: 40px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-secondary);">
                        <button class="btn-clear" id="clearEventSearch" style="display:none; position: absolute; right: 10px; top: 8px; border: none; background: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Filter Button and Panel Wrapper -->
                    <div style="position: relative; display: flex; align-items: center;">
                        <button class="btn btn-icon" id="eventFilterBtn" title="Filter" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary); position: relative;">
                            <i class="fas fa-filter"></i>
                            <span class="filter-notification" id="eventFilterNotification" style="display: none; position: absolute; top: -5px; right: -5px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px;">
                                <span class="filter-count" id="eventFilterCount">0</span>
                            </span>
                        </button>

                        <!-- Advanced Filter Panel -->
                        <div class="filter-panel" id="eventFilterPanel" style="display: none; position: absolute; top: 100%; left: 0; z-index: 1000; width: 300px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: var(--shadow-lg); padding: 20px; margin-top: 10px;">
                            <div class="filter-panel-header" style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                                <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--text-primary);"><i class="fas fa-filter"></i> Select Filters</h3>
                            </div>
                            <div class="filter-panel-body">
                                <div class="filter-item" style="margin-bottom: 15px;">
                                    <label for="filterEventType" style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase;">Event Type</label>
                                    <select id="filterEventType" class="form-control" style="width: 100%;">
                                        <option value="">All Types</option>
                                        <option value="Barangay">Barangay Event</option>
                                        <option value="Resident">Resident Event</option>
                                    </select>
                                </div>
                                <div class="filter-item" style="margin-bottom: 15px;">
                                    <label for="filterLocation" style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase;">Location</label>
                                    <input type="text" id="filterLocation" class="form-control" placeholder="Enter Location">
                                </div>
                                <div class="filter-item" style="margin-bottom: 15px;">
                                    <label for="filterFromDate" style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase;">From Date</label>
                                    <input type="date" id="filterFromDate" class="form-control">
                                </div>
                                <div class="filter-item" style="margin-bottom: 15px;">
                                    <label for="filterToDate" style="display: block; font-size: 12px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase;">To Date</label>
                                    <input type="date" id="filterToDate" class="form-control">
                                </div>
                            </div>
                            <div class="filter-panel-footer" style="display: flex; gap: 10px; border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 5px;">
                                <button class="btn btn-secondary" id="clearEventFiltersBtn" style="flex: 1; height: 36px; padding: 0; justify-content: center; font-size: 13px;">Clear</button>
                                <button class="btn btn-primary" id="applyEventFiltersBtn" style="flex: 1; height: 36px; padding: 0; justify-content: center; font-size: 13px;">Apply</button>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-icon" id="refreshEventsBtn" title="Refresh List" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-secondary);">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                <div style="margin-left: auto;">
                        <div class="pagination-info" style="font-size: 13px; color: var(--text-secondary);">
                            TOTAL: <strong id="eventTotalCount">0</strong>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table" id="upcomingEventsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Title</th>
                                <th>Location</th>
                                <th>Organizer</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="eventsTableBody">
                            <?php if (!empty($eventError)): ?>
                                <tr><td colspan="6" style="text-align: center; color: var(--danger-color); padding: 20px;">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($eventError); ?>
                                </td></tr>
                            <?php elseif (empty($events)): ?>
                                <tr><td colspan="7" style="text-align: center; padding: 20px; color: var(--text-secondary);">No upcoming events found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): 
                                    $status = $event['status'] ?? 'Active';
                                    $type = $event['event_type'] ?? 'Barangay';
                                    $statusClass = 'badge-' . strtolower($status);
                                ?>
                                <tr data-status="<?php echo $status; ?>" data-type="<?php echo $type; ?>" data-location="<?php echo htmlspecialchars($event['location']); ?>" data-date="<?php echo $event['event_date']; ?>">
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $start = !empty($event['start_time']) ? date('g:i A', strtotime($event['start_time'])) : '---';
                                        $end = !empty($event['end_time']) ? date('g:i A', strtotime($event['end_time'])) : '';
                                        echo $start . ($end ? " - $end" : "");
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                  
                                    <td>
                                        <?php 
                                        if ($event['event_type'] === 'Barangay') {
                                            echo 'Barangay Officials';
                                        } else {
                                            echo htmlspecialchars($event['resident_name'] ?? 'Unknown Resident');
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="action-menu-container">
                                            <button class="btn-action" data-event-id="<?php echo $event['id']; ?>">
                                                <i class="fas fa-ellipsis-h"></i>
                                            </button>
                                            <div class="action-menu" data-event-id="<?php echo $event['id']; ?>">
                                                <?php if (hasPermission('perm_events_view')): ?>
                                                <button type="button" class="action-menu-item" data-action="view">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <?php endif; ?>
                                                <?php if (hasPermission('perm_events_edit')): ?>
                                                <button type="button" class="action-menu-item" data-action="edit">
                                                    <i class="fas fa-edit"></i> Edit Event
                                                </button>
                                                <?php endif; ?>
                                                <?php if (hasPermission('perm_events_archive')): ?>
                                                <div class="action-menu-divider"></div>
                                                <button type="button" class="action-menu-item danger" data-action="delete">
                                                    <i class="fas fa-trash"></i> Archive Event
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
            </div>
        </div>
    </main>
    
    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; z-index: 999999 !important; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
        <div class="modal-content" style="background-color: var(--bg-secondary); padding: 2rem; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);  width: 90%; max-width: 500px; margin: 10% auto; position: relative;">
            <div class="modal-header" style="display: flex; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                <div style="width: 54px; height: 54px; background-color: #fee2e2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 1.25rem; flex-shrink: 0;">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h3 id="archiveModalTitle" style="margin: 0 0 0.25rem 0; color: var(--text-primary); font-size: 1.25rem; font-weight: 600; line-height: 1.4; word-wrap: break-word;">Archive Event</h3>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 0.9rem; line-height: 1.4;">Are you sure you want to archive this event? This action will move it to the archives.</p>
                </div>
            </div>
            
            <div class="modal-body">
                <div style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; color: #d97706; font-size: 0.875rem;">
                    <i class="fas fa-shield-alt" style="margin-right: 5px;"></i> For security purposes, please enter your password to confirm.
                </div>
                
                <form id="archiveForm">
                    <input type="hidden" id="archiveRecordId" name="id">
                    
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
                            <i class="fas fa-trash"></i> Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div id="eventDetailModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div class="modal-content" style="max-width: 500px; border-radius: 0;">
            <div class="modal-header">
                <h3 id="eventDetailTitle">Event Details</h3>
                <button id="closeEventDetail" style="background: none; border: none; font-size: 24px;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom: 25px;">
                    <label style="display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Description</label>
                    <p id="eventDetailDesc" style="line-height: 1.6; color: var(--text-primary); white-space: pre-wrap; margin: 0;"></p>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Date</label>
                        <p id="eventDetailDate" style="font-weight: 500; color: var(--text-primary); margin: 0;"></p>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Time</label>
                        <p id="eventDetailTime" style="font-weight: 500; color: var(--text-primary); margin: 0;"></p>
                    </div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Location</label>
                    <p id="eventDetailLocation" style="font-weight: 500; color: var(--text-primary); margin: 0;"></p>
                </div>
            </div>
            <div class="modal-footer" style="padding: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button class="btn btn-secondary" onclick="document.getElementById('eventDetailModal').style.display='none'">Close</button>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit Event Modal -->
    <div id="eventModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
        <div class="modal-content" style="width: 600px; height: 600px; max-width: 95vw; max-height: 95vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.2); background-color: var(--bg-secondary);">
            <div class="modal-header">
                <h5 id="eventModalTitle">New Event</h5>
                <button id="closeEventModal" style="background: none; border: none; font-size: 24px;">&times;</button>
            </div>
            <div class="cert-tabs-container" style="display: flex; border-bottom: 1px solid var(--border-color); background-color: var(--bg-primary);">
                <button type="button" id="tabBarangay" class="cert-tab-btn active" style="flex: 1; padding: 15px; border: none; background: none; font-weight: 600; color: var(--text-secondary); cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">BARANGAY EVENT</button>
                <button type="button" id="tabResident" class="cert-tab-btn" style="flex: 1; padding: 15px; border: none; background: none; font-weight: 600; color: var(--text-secondary); cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">RESIDENT EVENT</button>
            </div>
            <div class="modal-body" style="overflow-y: auto; flex: 1;">
                <form id="eventForm">
                    <input type="hidden" id="eventType" name="event_type" value="Barangay">
                    
                    <!-- Resident Selection (Shown for Resident Events) -->
                    <div id="residentSelectionGroup" class="form-group" style="display: none; margin-bottom: 20px;">
                        <label>Requesting Resident <span style="color: red;">*</span></label>
                        <div class="resident-input-group" style="display: flex; gap: 8px;">
                            <div style="flex: 1; position: relative;">
                                <input type="text" id="eventResidentName" class="form-control" placeholder="Search resident name..." autocomplete="off">
                                <div id="eventResidentDropdown" class="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; width: 100%; z-index: 1000; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0 0 8px 8px; max-height: 200px; overflow-y: auto; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);"></div>
                            </div>
                            <input type="hidden" id="eventResidentId" name="resident_id">
                            <button type="button" class="btn btn-primary" id="openResidentSearchBtn" style="white-space: nowrap; padding: 0 15px;">
                                <i class="fas fa-user"></i> RESIDENT
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 10px;">
                        <label>Title <span style="color: red;">*</span></label>
                        <input type="text" id="eventTitle" name="title" class="form-control" placeholder="Event Title" required>
                    </div>

                    <!-- Action Selection for Edits -->
                    <div id="editEventActionGroup" class="form-group" style="display: none; margin-bottom: 20px;">
                        <label style="font-weight: 600;">Update Schedule Status</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="event_status_radio" id="btnReschedule" value="Active"> 
                                <i class="fas fa-calendar-alt" style="color: var(--primary-color);"></i> Reschedule
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="event_status_radio" id="btnPostpone" value="Postponed"> 
                                <i class="fas fa-pause-circle" style="color: #f59e0b;"></i> Postpone
                            </label>
                        </div>
                        <input type="hidden" id="eventActionStatus" name="status" value="Active">
                    </div>

                    <div id="dateTimeFieldsContainer" style="display: none;">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label>Date <span style="color: red;">*</span></label>
                            <input type="date" id="eventDate" name="event_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                            <div style="flex: 1;">
                                <label>Start Time <span style="color: red;">*</span></label>
                                <input type="time" id="eventStartTime" name="start_time" class="form-control" required>
                            </div>
                            <div style="flex: 1;">
                                <label>End Time</label>
                                <input type="time" id="eventEndTime" name="end_time" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label style="display: block; font-weight: 600; margin-bottom: 8px;">Recurrence</label>
                        <div style="display: flex; gap: 20px; margin-bottom: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="recurrence_type" value="none" checked> One-time
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="radio" name="recurrence_type" value="custom"> Recurring
                            </label>
                        </div>
                        
                        <div id="recurrenceCustomOptions" style="display: none; padding: 15px; background: var(--bg-primary); border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 15px;">
                            <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 10px; display: block;">Repeat on:</label>
                            <div class="day-picker" style="display: flex; gap: 8px; margin-bottom: 15px;">
                                <div class="day-btn" data-day="0">S</div>
                                <div class="day-btn" data-day="1">M</div>
                                <div class="day-btn" data-day="2">T</div>
                                <div class="day-btn" data-day="3">W</div>
                                <div class="day-btn" data-day="4">T</div>
                                <div class="day-btn" data-day="5">F</div>
                                <div class="day-btn" data-day="6">S</div>
                            </div>
                            <label for="eventEndDate" style="font-size: 11px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 5px; display: block;">Until Date</label>
                            <input type="date" id="eventEndDate" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div> 
                    <div class="form-group"  style="margin-bottom: 10px;">
                        <label>Location<span style="color: red;"> *</span></label>
                        <input type="text" id="eventLocation" name="location" class="form-control" placeholder="e.g. Barangay Hall, Covered Court" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="eventDesc" class="form-control" rows="4"></textarea>
                    </div>
                   
                   
                </form>
            </div>
            <div class="modal-footer" style="padding: 15px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; background: var(--bg-primary);">
                <button class="btn btn-secondary" id="cancelEvent" onclick="document.getElementById('eventModal').style.display='none'">Cancel</button>
                <button class="btn btn-primary" id="saveEventBtn">Save Event</button>
            </div>
        </div>
    </div>

    <!-- Search Resident Modal -->
    <div id="searchResidentModal" class="search-resident-modal" style="display: none; position: fixed; z-index: 1100; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center;">
        <div class="search-resident-modal-content" style="background: var(--bg-secondary); padding: 20px; border-radius: 12px; width: 90%; max-width: 500px;">
            <div class="search-resident-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin:0;"><i class="fas fa-search"></i> Search Resident</h4>
                <button type="button" class="btn-close" onclick="closeSearchResidentModal()" style="background:none; border:none; font-size: 20px;">&times;</button>
            </div>
            <div class="search-input-container" style="position: relative; margin-bottom: 15px;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 12px; color: var(--text-secondary);"></i>
                <input type="text" id="residentSearchInput" class="form-control" style="padding-left: 35px;" placeholder="Search Full Name...">
            </div>
            <div id="residentsListContainer" style="max-height: 300px; overflow-y: auto;">
                <p style="text-align: center; color: var(--text-secondary);">Type to search...</p>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script src="assets/js/script.js"></script>
    <script src="assets/js/table.js"></script>
    <script src="assets/js/events.js"></script>
    
    <script>
    window.BIS_PERMS = {
        events_view:    <?php echo hasPermission('perm_events_view')    ? 'true' : 'false'; ?>,
        events_create:  <?php echo hasPermission('perm_events_create')  ? 'true' : 'false'; ?>,
        events_edit:    <?php echo hasPermission('perm_events_edit')    ? 'true' : 'false'; ?>,
        events_archive: <?php echo hasPermission('perm_events_archive') ? 'true' : 'false'; ?>
    };
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabBarangay = document.getElementById('tabBarangay');
        const tabResident = document.getElementById('tabResident');
        const eventTypeInput = document.getElementById('eventType');
        const residentGroup = document.getElementById('residentSelectionGroup');
        const residentInput = document.getElementById('eventResidentName');
        const residentIdInput = document.getElementById('eventResidentId');
        const dropdown = document.getElementById('eventResidentDropdown');
        const openSearchBtn = document.getElementById('openResidentSearchBtn');

        const actionGroup = document.getElementById('editEventActionGroup');
        const dateTimeContainer = document.getElementById('dateTimeFieldsContainer');
        const btnReschedule = document.getElementById('btnReschedule');
        const btnPostpone = document.getElementById('btnPostpone');
        const eventActionStatus = document.getElementById('eventActionStatus');

        // Open Search Modal
        openSearchBtn.addEventListener('click', () => {
            document.getElementById('searchResidentModal').style.display = 'flex';
            document.getElementById('residentSearchInput').focus();
            loadResidentsForModal('');
        });

        window.closeSearchResidentModal = function() {
            document.getElementById('searchResidentModal').style.display = 'none';
        };

        function loadResidentsForModal(query) {
            const container = document.getElementById('residentsListContainer');
            fetch(`model/search_residents.php?search=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    container.innerHTML = '';
                    if (data.success && data.data.length > 0) {
                        data.data.forEach(r => {
                            const div = document.createElement('div');
                            div.className = 'autocomplete-item';
                            div.innerHTML = `${r.full_name} <small style="display:block; color: gray;">${r.resident_id || ''}</small>`;
                            div.onclick = () => {
                                residentInput.value = r.full_name;
                                residentIdInput.value = r.id;
                                closeSearchResidentModal();
                            };
                            container.appendChild(div);
                        });
                    } else {
                        container.innerHTML = '<p style="text-align:center; padding:10px;">No residents found.</p>';
                    }
                });
        }

        document.getElementById('residentSearchInput').addEventListener('input', (e) => {
            loadResidentsForModal(e.target.value);
        });

        // Tab Switching
        tabBarangay.addEventListener('click', () => {
            tabBarangay.classList.add('active');
            tabResident.classList.remove('active');
            eventTypeInput.value = 'Barangay';
            residentGroup.style.display = 'none';
            residentInput.required = false;
        });

        tabResident.addEventListener('click', () => {
            tabResident.classList.add('active');
            tabBarangay.classList.remove('active');
            eventTypeInput.value = 'Resident';
            residentGroup.style.display = 'block';
            residentInput.required = true;
        });

        // Resident Autocomplete
        let timeout = null;
        residentInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                dropdown.style.display = 'none';
                residentIdInput.value = '';
                return;
            }

            timeout = setTimeout(() => {
                fetch(`model/search_residents.php?search=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        dropdown.innerHTML = '';
                        if (data.success && data.data.length > 0) {
                            data.data.forEach(resident => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                
                                const regex = new RegExp(`(${query})`, 'gi');
                                let displayHtml = resident.full_name.replace(regex, '<strong>$1</strong>');
                                item.innerHTML = displayHtml;
                                
                                item.addEventListener('click', () => {
                                    residentInput.value = resident.full_name;
                                    residentIdInput.value = resident.id;
                                    dropdown.style.display = 'none';
                                });
                                dropdown.appendChild(item);
                            });
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.style.display = 'none';
                        }
                    })
                    .catch(err => console.error('Error:', err));
            }, 300);
        });

        // Action selection logic for Edit flow
        if (btnReschedule && btnPostpone) {
            btnReschedule.addEventListener('change', function() {
                if (this.checked) {
                    // Show Date/Time inputs
                    dateTimeContainer.style.display = 'block';
                    if (eventActionStatus) eventActionStatus.value = 'Active';
                    const dateInput = document.getElementById('eventDate');
                    const timeInput = document.getElementById('eventStartTime');
                    if (dateInput) dateInput.required = true;
                    if (timeInput) timeInput.required = true;
                }
            });

            btnPostpone.addEventListener('change', function() {
                if (this.checked) {
                    // Hide Date/Time inputs
                    dateTimeContainer.style.display = 'none';
                    if (eventActionStatus) eventActionStatus.value = 'Postponed';
                    const dateInput = document.getElementById('eventDate');
                    const timeInput = document.getElementById('eventStartTime');
                    if (dateInput) dateInput.required = false;
                    if (timeInput) timeInput.required = false;
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== residentInput && e.target !== dropdown) {
                dropdown.style.display = 'none';
            }
        });
        
        // Reset Modal on Open
        const openModalBtn = document.querySelector('.create-event-btn');
        if(openModalBtn) {
            openModalBtn.addEventListener('click', () => {
                // Reset UI for Create Mode
                actionGroup.style.display = 'none';
                dateTimeContainer.style.display = 'block';
                eventActionStatus.value = 'Active';

                if (btnReschedule) btnReschedule.checked = false;
                if (btnPostpone) btnPostpone.checked = false;

                tabBarangay.click(); 
                
                // Remove event_id to ensure we are in Create mode, not Update mode
                const existingIdInput = document.querySelector('input[name="event_id"]');
                if (existingIdInput) existingIdInput.remove();
                
                document.getElementById('eventForm').reset();
                
                // Reset Recurrence UI
                document.querySelectorAll('.day-btn').forEach(b => b.classList.remove('active'));
                document.getElementById('recurrenceCustomOptions').style.display = 'none';
                document.querySelector('input[name="recurrence_type"][value="none"]').checked = true;

                residentIdInput.value = '';
                const modal = document.getElementById('eventModal');
                modal.style.display = 'flex';
            });
        }

        // Recurrence UI Toggle
        document.querySelectorAll('input[name="recurrence_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const customOptions = document.getElementById('recurrenceCustomOptions');
                customOptions.style.display = e.target.value === 'custom' ? 'block' : 'none';
            });
        });

        // Day Picker Interactivity
        document.querySelectorAll('.day-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('active');
            });
        });
    });
    </script>
</body>
</html>
