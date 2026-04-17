<?php
// Events Page - Barangay Scheduling System
require_once 'config.php';
require_once 'auth_check.php';
require_once 'permissions.php';

$pageTitle = 'Barangay Events & Scheduling';

// Fetch upcoming events for the table
$events = [];
if (isset($conn)) {
    try {
        date_default_timezone_set('Asia/Manila');
        $currentDate = date('Y-m-d');

        // Updated query to join with residents table to get the organizer name
        $query = "SELECT e.id, e.title, e.event_date, e.start_time, e.end_time, e.location, e.description, e.event_type,
                         TRIM(CONCAT(r.first_name, ' ', IFNULL(CONCAT(r.middle_name, ' '), ''), r.last_name, ' ', IFNULL(r.suffix, ''))) AS resident_name
                  FROM events e
                  LEFT JOIN residents r ON e.resident_id = r.id
                  WHERE e.event_date >= ? 
                  ORDER BY event_date ASC LIMIT 10";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $currentDate);
        $stmt->execute();
        $res = $stmt->get_result();
        
        while ($row = $res->fetch_assoc()) {
            $events[] = $row;
        }
    } catch (Exception $e) {
        error_log("Database Error in events.php: " . $e->getMessage());
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
                    <button class="btn btn-primary create-event-btn" title="Create New Event">
                        <i class="fas fa-plus"></i>
                        Create New Event
                    </button>
                </div>
            </div>
            
            <!-- Big Monthly Calendar -->
            <div class="calendar-container">
                <div id='calendar'></div>
            </div>
            
            <!-- Events List (below calendar) -->
            <div class="events-list-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Upcoming Events</h3>
                </div>
                <div class="table-container">
                    <table class="data-table">
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
                            <?php if (empty($events)): ?>
                                <tr><td colspan="6" style="text-align: center;">No upcoming events found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): 
                                ?>
                                <tr>
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
                                                <button type="button" class="action-menu-item" data-action="view">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                <button type="button" class="action-menu-item" data-action="edit">
                                                    <i class="fas fa-edit"></i> Edit Event
                                                </button>
                                                <div class="action-menu-divider"></div>
                                                <button type="button" class="action-menu-item danger" data-action="delete">
                                                    <i class="fas fa-trash"></i> Delete Event
                                                </button>
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
    
    <!-- Event Details Modal -->
    <div id="eventDetailModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
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

                    <div class="form-group">
                        <label>Title <span style="color: red;">*</span></label>
                        <input type="text" id="eventTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Date <span style="color: red;">*</span></label>
                        <input type="date" id="eventDate" class="form-control" required>
                    </div>
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div style="flex: 1;">
                            <label>Start Time <span style="color: red;">*</span></label>
                            <input type="time" id="eventStartTime" class="form-control" required>
                        </div>
                        <div style="flex: 1;">
                            <label>End Time</label>
                            <input type="time" id="eventEndTime" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="eventDesc" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" id="eventLocation" name="location" class="form-control" placeholder="e.g. Barangay Hall, Covered Court">
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
    <script src="assets/js/events.js"></script>
    
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
                tabBarangay.click(); 
                document.getElementById('eventForm').reset();
                residentIdInput.value = '';
                const modal = document.getElementById('eventModal');
                modal.style.display = 'flex';
            });
        }
    });
    </script>
</body>
</html>
