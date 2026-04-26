/**
 * Barangay Events & Scheduling System
 * Handles FullCalendar initialization and Event CRUD operations
 */

let calendar;

let eventsTable;

/**
 * Helper to show toast notifications
 */
function showNotification(message, type = 'info') {
    document.querySelectorAll('.notification').forEach(n => n.remove());
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    else if (type === 'error') icon = 'exclamation-circle';
    else if (type === 'warning') icon = 'exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    notification.style.animation = 'slideIn 0.3s ease';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 0. Initialize Table and Filtering
    // ============================================
    const upcomingEventsTableEl = document.getElementById('upcomingEventsTable');
    if (upcomingEventsTableEl) {
        eventsTable = new EnhancedTable('upcomingEventsTable', {
            sortable: true,
            searchable: true,
            paginated: true,
            pageSize: 10,
            customSearch: (row, term) => {
                const title = row.cells[2]?.textContent.toLowerCase() || '';
                const location = row.cells[3]?.textContent.toLowerCase() || '';
                return title.includes(term) || location.includes(term);
            }
        });

        // Bind Export/Print listeners using direct DOM access
        document.addEventListener('click', function(e) {
            if (e.target.closest('#exportCsvBtn')) {
                if (eventsTable) {
                    eventsTable.exportToCSV('Barangay_Events_Masterlist_' + new Date().toISOString().slice(0, 10) + '.csv');
                    
                    const logData = new FormData();
                    logData.append('action', 'Export Masterlist');
                    logData.append('description', 'Exported the events masterlist to CSV');
                    fetch('model/log_print_masterlist.php', { method: 'POST', body: logData }).catch(e => console.error(e));
                } else {
                    showNotification('Table data is not ready for export', 'error');
                }
            }
            
            if (e.target.closest('#printMasterlistBtn')) {
                handlePrintEventsMasterlist();
            }
        });

        const filterBtn = document.getElementById('eventFilterBtn');
        const filterPanel = document.getElementById('eventFilterPanel');
        
        if (filterBtn && filterPanel) {
            filterBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const isVisible = filterPanel.style.display !== 'none';
                filterPanel.style.display = isVisible ? 'none' : 'block';
                filterBtn.classList.toggle('active', !isVisible);
            });

            document.addEventListener('click', (e) => {
                if (!filterPanel.contains(e.target) && !filterBtn.contains(e.target)) {
                    filterPanel.style.display = 'none';
                    filterBtn.classList.remove('active');
                }
            });
        }

        // Safe helper to update the total count display
        const updateCountDisplay = (count) => {
            const countEl = document.getElementById('eventTotalCount');
            if (countEl) {
                countEl.textContent = count;
            }
        };

        const applyEventFilters = () => {
            const activeTab = document.querySelector('.tab-btn.active');
            const statusFilter = activeTab ? activeTab.getAttribute('data-filter') : 'all';
            
            const typeFilter = document.getElementById('filterEventType')?.value || '';
            const locationFilter = document.getElementById('filterLocation')?.value.toLowerCase() || '';
            const fromDate = document.getElementById('filterFromDate')?.value || '';
            const toDate = document.getElementById('filterToDate')?.value || '';

            let activeCount = 0;
            if (typeFilter) activeCount++;
            if (locationFilter) activeCount++;
            if (fromDate) activeCount++;
            if (toDate) activeCount++;

            updateEventFilterNotification(activeCount);

            eventsTable.filter(row => {
                if (row.querySelector('td[colspan]')) return false;

                const rowStatus = row.getAttribute('data-status');
                if (statusFilter === 'all') {
                    if (rowStatus === 'Postponed') return false;
                } else if (rowStatus !== statusFilter) {
                    return false;
                }

                const rowType = row.getAttribute('data-type');
                if (typeFilter && rowType !== typeFilter) return false;

                if (locationFilter) {
                    const rowLoc = row.getAttribute('data-location')?.toLowerCase() || '';
                    if (!rowLoc.includes(locationFilter)) return false;
                }

                const rowDate = row.getAttribute('data-date');
                if (fromDate && rowDate < fromDate) return false;
                if (toDate && rowDate > toDate) return false;

                return true;
            });
            updateCountDisplay(eventsTable.getFilteredRows());
        };

        // Tab filtering logic
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                applyEventFilters();
            });
        });

        document.getElementById('applyEventFiltersBtn')?.addEventListener('click', () => {
            applyEventFilters();
            filterPanel.style.display = 'none';
            filterBtn.classList.remove('active');
        });

        document.getElementById('clearEventFiltersBtn')?.addEventListener('click', () => {
            if (document.getElementById('filterEventType')) document.getElementById('filterEventType').value = '';
            if (document.getElementById('filterLocation')) document.getElementById('filterLocation').value = '';
            if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
            if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
            applyEventFilters();
        });

        // Search logic
        const searchInput = document.getElementById('eventSearchInput');
        const clearBtn = document.getElementById('clearEventSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const term = e.target.value;
                eventsTable.search(term);
                if (clearBtn) clearBtn.style.display = term ? 'block' : 'none';
                updateCountDisplay(eventsTable.getFilteredRows());
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                eventsTable.search('');
                clearBtn.style.display = 'none';
                updateCountDisplay(eventsTable.getFilteredRows());
            });
        }

        // Refresh/Reset logic to match residents.php behavior
        document.getElementById('refreshEventsBtn')?.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon) icon.style.animation = 'spin 0.5s linear';
            
            setTimeout(() => {
                if (icon) icon.style.animation = '';
                
                // 1. Reset Search UI
                if (searchInput) {
                    searchInput.value = '';
                    if (clearBtn) clearBtn.style.display = 'none';
                }
                
                // 2. Reset Filter Tabs UI
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelector('.tab-btn[data-filter="all"]')?.classList.add('active');
                
                // 3. Reset Advanced Filter Panel UI
                if (document.getElementById('filterEventType')) document.getElementById('filterEventType').value = '';
                if (document.getElementById('filterLocation')) document.getElementById('filterLocation').value = '';
                if (document.getElementById('filterFromDate')) document.getElementById('filterFromDate').value = '';
                if (document.getElementById('filterToDate')) document.getElementById('filterToDate').value = '';
                
                // 4. Reset Table State and Notification
                eventsTable.reset(); // Internal reset of filters and search
                updateEventFilterNotification(0);
                updateCountDisplay(eventsTable.getTotalRows());
            }, 500);
        });

        applyEventFilters();
    }

    // ============================================
    // Archive Modal Handlers
    // ============================================
    const archiveModal = document.getElementById('archiveModal');
    const archiveForm = document.getElementById('archiveForm');
    const cancelArchiveBtn = document.getElementById('cancelArchive');
    const toggleArchivePasswordBtn = document.getElementById('toggleArchivePassword');
    const archivePasswordInput = document.getElementById('archivePassword');

    if (cancelArchiveBtn) {
        cancelArchiveBtn.addEventListener('click', () => {
            if (archiveModal) archiveModal.style.display = 'none';
        });
    }

    if (toggleArchivePasswordBtn && archivePasswordInput) {
        toggleArchivePasswordBtn.addEventListener('click', () => {
            const type = archivePasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            archivePasswordInput.setAttribute('type', type);
            toggleArchivePasswordBtn.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }

    if (archiveForm) {
        archiveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const confirmBtn = document.getElementById('confirmArchiveBtn');
            const originalText = confirmBtn.innerHTML;
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
            
            const formData = new FormData(this);
            
            fetch('model/archive_event.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Event archived successfully!', 'success');
                    if (archiveModal) archiveModal.style.display = 'none';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'Error archiving event', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            });
        });
    }

    function updateEventFilterNotification(count) {
        const notification = document.getElementById('eventFilterNotification');
        const countSpan = document.getElementById('eventFilterCount');
        const filterBtn = document.getElementById('eventFilterBtn');
        
        if (!notification || !countSpan || !filterBtn) return;
        
        if (count > 0) {
            countSpan.textContent = count;
            notification.style.display = 'flex';
        } else {
            notification.style.display = 'none';
        }
    }

    // ============================================
    // 1. Initialize FullCalendar
    // ============================================
    const calendarEl = document.getElementById('calendar');
    if (calendarEl) {
        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth'
            },
            eventTimeFormat: { // like '10:30 AM'
                hour: 'numeric',
                minute: '2-digit',
            },
            themeSystem: 'bootstrap5',
            events: 'model/get_events.php', // Fetches data from your API
            eventClick: function(info) {
                // Show details when a calendar event is clicked
                showEventDetails(info.event.id);
            },
            eventDidMount: function(info) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const eventStart = info.event.start;
                const status = info.event.extendedProps.status;

                // 1. Style Past Events (Grayed out)
                if (eventStart < today) {
                    info.el.style.backgroundColor = '#cbd5e1';
                    info.el.style.borderColor = '#94a3b8';
                    info.el.style.color = '#475569';
                    info.el.style.opacity = '0.7';
                } 
                // 2. Style Postponed Events (Red with opacity)
                else if (status === 'Postponed') {
                    info.el.style.backgroundColor = 'rgba(239, 68, 68, 0.4)';
                    info.el.style.borderColor = '#ef4444';
                    info.el.style.color = '#7f1d1d';
                } 
                // 3. Original Resident Type Styling
                else if (info.event.extendedProps.event_type === 'Resident') {
                    info.el.style.borderLeft = '4px solid #f59e0b'; // Amber for residents
                }
            }
        });
        calendar.render();
    }

    // ============================================
    // 2. Modal Toggle Logic
    // ============================================
    const eventModal = document.getElementById('eventModal');
    const closeEventModal = document.getElementById('closeEventModal');
    const eventDetailModal = document.getElementById('eventDetailModal');
    const closeEventDetail = document.getElementById('closeEventDetail');

    if (closeEventModal) {
        closeEventModal.onclick = () => eventModal.style.display = "none";
    }

    if (closeEventDetail) {
        closeEventDetail.onclick = () => eventDetailModal.style.display = "none";
    }

    // Close modals when clicking background
    window.onclick = (event) => {
        if (event.target == eventModal) eventModal.style.display = "none";
        if (event.target == eventDetailModal) eventDetailModal.style.display = "none";
    };

    // ============================================
    // 3. Save Event Functionality
    // ============================================
    window.saveEventData = function(shouldClose = true) {
        const form = document.getElementById('eventForm');
        const saveEventBtn = document.getElementById('saveEventBtn');
        const title = document.getElementById('eventTitle').value;
        const date = document.getElementById('eventDate').value;
        const startTime = document.getElementById('eventStartTime').value;
        const endTime = document.getElementById('eventEndTime').value;
        const type = document.getElementById('eventType').value;
        const statusInput = document.getElementById('eventActionStatus');
        const currentStatus = statusInput ? statusInput.value : 'Active';

        // Allow saving without a date/time only if the event is being Postponed
        if (!title || (currentStatus !== 'Postponed' && (!date || !startTime))) {
            alert('Title, Date, and Start Time are required.');
            return;
        }

        // Prepare data
        const formData = new FormData();
        formData.append('title', title);
        formData.append('event_date', date);
        formData.append('start_time', startTime);
        formData.append('end_time', endTime);
        formData.append('description', document.getElementById('eventDesc').value);
        formData.append('location', document.getElementById('eventLocation').value);
        formData.append('event_type', type);
        formData.append('resident_id', document.getElementById('eventResidentId').value);
        formData.append('organizer', document.getElementById('eventOrganizer').value);
        formData.append('approved_by', document.getElementById('eventApprovedBy').value);
        
        // Recurrence Data
        const recurrenceType = form.querySelector('input[name="recurrence_type"]:checked').value;
        formData.append('recurrence_type', recurrenceType);

        // Include the status (Active or Postponed)
        formData.append('status', statusInput ? statusInput.value : 'Active');
        
        if (recurrenceType === 'custom') {
            const selectedDays = [];
            document.querySelectorAll('.day-btn.active').forEach(btn => {
                selectedDays.push(btn.getAttribute('data-day'));
            });
            
            if (selectedDays.length === 0) {
                alert('Please select at least one day (S, M, T, W...) for the recurring schedule.');
                return;
            }
            
            if (!document.getElementById('eventEndDate').value) {
                alert('Please select an "Until Date" for the recurrence.');
                return;
            }
            
            formData.append('recurrence_days', JSON.stringify(selectedDays));
            formData.append('recurrence_end_date', document.getElementById('eventEndDate').value);
        }
        
        const eventId = form.querySelector('input[name="event_id"]')?.value;
        const url = eventId ? 'model/update_event.php' : 'model/create_event.php';
        if (eventId) formData.append('id', eventId);

        saveEventBtn.disabled = true;
        saveEventBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Server returned an invalid JSON response:', text);
                throw new Error('Server returned an invalid response format.');
            }
        })
            .then(data => {
                if (data.success) {
                    showNotification(data.message || 'Event saved successfully!', 'success');
                    
                    if (calendar) calendar.refetchEvents();

                    if (shouldClose) {
                        // Delay closing and reloading so user sees the message
                        setTimeout(() => {
                            const modal = document.getElementById('eventModal');
                            if (modal) modal.style.display = "none";
                            form.reset();
                            location.reload(); 
                        }, 1500);
                    }
                } else {
                    // Displays the specific conflict message or other errors from PHP
                    showNotification(data.message || 'An error occurred while saving.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while saving the event.', 'error');
            })
            .finally(() => {
                saveEventBtn.disabled = false;
                saveEventBtn.innerHTML = 'Save Event';
            });
    };

    const saveEventBtn = document.getElementById('saveEventBtn');
    if (saveEventBtn) {
        saveEventBtn.addEventListener('click', () => saveEventData(true));
    }

    // ============================================
    // 4. Action Menu Handler
    // ============================================
    let currentOpenMenu = null;

    document.addEventListener('click', function(e) {
        const actionBtn = e.target.closest('.btn-action');
        if (actionBtn) {
            e.stopPropagation();
            e.preventDefault();
            
            const container = actionBtn.closest('.action-menu-container');
            const eventId = actionBtn.getAttribute('data-event-id');
            
            let menu = container.querySelector('.action-menu');
            if (!menu) {
                menu = document.querySelector(`body > .action-menu[data-event-id="${eventId}"]`);
            }
            
            if (!menu) return;

            if (currentOpenMenu && currentOpenMenu !== menu) {
                currentOpenMenu.classList.remove('show');
            }
            
            // Append to body immediately to escape parent container transforms/clipping
            document.body.appendChild(menu);
            
            const rect = actionBtn.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;
            
            // Temporarily show to measure dimensions
            const originalDisplay = menu.style.display;
            menu.style.display = 'block';
            const menuHeight = menu.offsetHeight;
            menu.style.display = originalDisplay;

            menu.style.position = 'fixed';
            menu.style.zIndex = '10001';
            
            // Vertical positioning: flip up if it hits the bottom
            if (rect.bottom + menuHeight + 5 > windowHeight) {
                menu.style.top = (rect.top - menuHeight - 5) + 'px';
            } else {
                menu.style.top = (rect.bottom + 5) + 'px';
            }

            // Horizontal positioning: align right edge of menu with right edge of button
            menu.style.left = 'auto';
            menu.style.right = (windowWidth - rect.right) + 'px';

            menu.classList.toggle('show');
            currentOpenMenu = menu.classList.contains('show') ? menu : null;
            return;
        }
        
        const menuItem = e.target.closest('.action-menu-item');
        if (menuItem) {
            const action = menuItem.getAttribute('data-action');
            const menu = menuItem.closest('.action-menu');
            const eventId = menu.getAttribute('data-event-id');
            
            if (action === 'view') {
                showEventDetails(eventId);
            } else if (action === 'edit') {
                editEvent(eventId);
            } else if (action === 'delete') {
                deleteEvent(eventId);
            }
            
            menu.classList.remove('show');
            currentOpenMenu = null;
            return;
        }
        
        if (currentOpenMenu && !currentOpenMenu.contains(e.target)) {
            currentOpenMenu.classList.remove('show');
            currentOpenMenu = null;
        }
    });

    // Add print button listener
    const printApprovalBtn = document.getElementById('printEventApprovalBtn');
    if (printApprovalBtn) {
        printApprovalBtn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            if (eventId) printEventApproval(eventId);
        });
    }
});

/**
 * Fetches event details and opens the detail modal
 * @param {number|string} id - The database ID of the event
 */
window.showEventDetails = function(id) {
    const detailModal = document.getElementById('eventDetailModal');
    
    // You can either fetch from your API or find it in the calendar's local cache
    const eventObj = calendar.getEventById(id);

    if (eventObj) {
        document.getElementById('eventDetailTitle').textContent = eventObj.title;
        document.getElementById('eventDetailDesc').textContent = eventObj.extendedProps.description || 'No description provided.';
        document.getElementById('eventDetailLocation').textContent = eventObj.extendedProps.location || 'Not specified';
        
        // Format Date
        const isResidentEvent = eventObj.extendedProps.event_type === 'Resident';
        const organizerText = isResidentEvent ? eventObj.extendedProps.resident_name : (eventObj.extendedProps.organizer || 'Not specified');
        document.getElementById('eventDetailOrganizer').textContent = organizerText;
        const approverName = eventObj.extendedProps.approved_by_name;
        document.getElementById('eventDetailApprovedBy').textContent = approverName ? 'HON. ' + approverName : 'Not specified';
        const start = eventObj.start;
        document.getElementById('eventDetailDate').textContent = start.toLocaleDateString('en-US', { 
            weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
        });

      let timeText = '';
        if (eventObj.allDay) {
            timeText = 'All Day';
        } else {
            const end = eventObj.end;
            timeText = start.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            if (end) {
                timeText += ' - ' + end.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }
        }
        document.getElementById('eventDetailTime').textContent = timeText;

        // Store ID for printing
        const printBtn = document.getElementById('printEventApprovalBtn');
        if (printBtn) printBtn.setAttribute('data-event-id', id);

        detailModal.style.display = "flex";
    } else {
        // Fallback: Fetch from database if not in current calendar view
        fetch(`model/get_event_details.php?id=${id}`)
            .then(async res => {
                const text = await res.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server response is not valid JSON:', text);
                    return { success: false, message: 'Invalid server response' };
                }
            })
            .then(data => {
                if (data.success) {
                    // Populate and show (similar to above)
                }
            });
    }
};

/**
 * Opens the event modal in edit mode
 */
window.editEvent = function(id) {
    const eventObj = calendar.getEventById(id);
    // Prevent editing if the event is already Postponed
    if (eventObj && eventObj.extendedProps.status === 'Postponed') {
        showNotification('Postponed events cannot be edited.', 'warning');
        return;
    }

    if (eventObj) {
        const modal = document.getElementById('eventModal');
        const title = document.getElementById('eventModalTitle');
        const form = document.getElementById('eventForm');
        
        title.textContent = 'Edit Event';
        
        document.getElementById('eventType').value = eventObj.extendedProps.event_type;
        document.getElementById('eventTitle').value = eventObj.title;
        document.getElementById('eventDate').value = eventObj.startStr.split('T')[0];
        
        const start = new Date(eventObj.start);
        document.getElementById('eventStartTime').value = start.toTimeString().slice(0, 5);
        
        if (eventObj.end) {
            const end = new Date(eventObj.end);
            document.getElementById('eventEndTime').value = end.toTimeString().slice(0, 5);
        } else {
            document.getElementById('eventEndTime').value = '';
        }
        
        document.getElementById('eventDesc').value = eventObj.extendedProps.description || '';
        document.getElementById('eventLocation').value = eventObj.extendedProps.location || '';
        document.getElementById('eventOrganizer').value = eventObj.extendedProps.organizer || ''; // New field
        document.getElementById('eventApprovedBy').value = eventObj.extendedProps.approved_by || ''; // New field
        
        if (eventObj.extendedProps.event_type === 'Resident') {
            document.getElementById('tabResident').click();
            document.getElementById('eventResidentId').value = eventObj.extendedProps.resident_id || '';
            document.getElementById('eventResidentName').value = eventObj.extendedProps.resident_name || '';
        } else {
            document.getElementById('tabBarangay').click();
            document.getElementById('eventResidentId').value = '';
            document.getElementById('eventResidentName').value = '';
        }

        // Hide organizer and approved by fields when editing
        if (document.getElementById('organizerGroup')) document.getElementById('organizerGroup').style.display = 'none';
        if (document.getElementById('approvalGroup')) document.getElementById('approvalGroup').style.display = 'none';
        
        // Setup UI for Reschedule/Postpone logic in Edit mode
        const actionGroup = document.getElementById('editEventActionGroup');
        const dateTimeContainer = document.getElementById('dateTimeFieldsContainer');
        const btnReschedule = document.getElementById('btnReschedule');
        const btnPostpone = document.getElementById('btnPostpone');
        const eventActionStatus = document.getElementById('eventActionStatus');

        if (eventActionStatus) eventActionStatus.value = 'Active';
        if (actionGroup) actionGroup.style.display = 'block';
        if (dateTimeContainer) {
            dateTimeContainer.style.display = 'none';
            document.getElementById('eventDate').required = false;
            document.getElementById('eventStartTime').required = false;
        }
        if (btnReschedule) btnReschedule.checked = false;
        if (btnPostpone) btnPostpone.checked = false;

        let idInput = form.querySelector('input[name="event_id"]');
        if (!idInput) {
            idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'event_id';
            form.appendChild(idInput);
        }
        idInput.value = id;
        
        modal.style.display = "flex";
    }
};

/**
 * Deletes an event after confirmation
 */
window.deleteEvent = window.archiveEvent = function(id) {
    const archiveModal = document.getElementById('archiveModal');
    const archiveRecordIdInput = document.getElementById('archiveRecordId');
    const archivePasswordInput = document.getElementById('archivePassword');
    const archiveReasonInput = document.getElementById('archiveReason');
    
    if (archiveModal && archiveRecordIdInput) {
        archiveRecordIdInput.value = id;
        if (archivePasswordInput) archivePasswordInput.value = '';
        if (archiveReasonInput) archiveReasonInput.value = '';
        
        const eventObj = calendar ? calendar.getEventById(id) : null;
        const modalTitle = document.getElementById('archiveModalTitle');
        if (modalTitle) {
            if (eventObj) {
                modalTitle.innerHTML = `Archive Event <u>${eventObj.title}</u>`;
            } else {
                modalTitle.textContent = 'Archive Event';
            }
        }
        
        archiveModal.style.display = 'block';
        if (archiveReasonInput) {
            archiveReasonInput.focus();
        }
    }
};

/**
 * Prints the event approval document
 */
async function printEventApproval(id) {
    const eventObj = calendar.getEventById(id);
    if (!eventObj) return;

    // Fetch Barangay Info for header
    let brgyInfo = { province_name: 'Province', town_name: 'Municipality', barangay_name: 'Barangay', barangay_logo: '', official_emblem: '' };
    try {
        const response = await fetch('model/get_barangay_info.php');
        if (response.ok) {
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if (data.success && data.data) brgyInfo = data.data;
            } catch (e) {
                console.error('Barangay info response is not valid JSON:', text);
            }
        }
    } catch (e) { console.error('Error fetching barangay info:', e); }

    const brgyLogoHtml = brgyInfo.barangay_logo ? `<img src="${brgyInfo.barangay_logo}" style="width: 80px; height: 80px; object-fit: contain;">` : `<div style="width: 80px; height: 80px;"></div>`;
    const skLogoHtml = brgyInfo.sk_logo ? `<img src="${brgyInfo.sk_logo}" style="width: 80px; height: 80px; object-fit: contain;">` : `<div style="width: 80px; height: 80px;"></div>`;

    const isResidentEvent = eventObj.extendedProps.event_type === 'Resident';
    const organizerText = isResidentEvent ? eventObj.extendedProps.resident_name : (eventObj.extendedProps.organizer || 'Not specified');
    const approverName = eventObj.extendedProps.approved_by_name || 'Authorized Official';
    
    // Fetch the actual date range for the entire series from the database
    // This ensures we get the correct range even if some events are in months not currently loaded in the calendar view
    let dateText = '';
    try {
        const rangeResponse = await fetch(`model/get_event_series_range.php?title=${encodeURIComponent(eventObj.title)}&location=${encodeURIComponent(eventObj.extendedProps.location || '')}&type=${encodeURIComponent(eventObj.extendedProps.event_type)}`);
        const rangeText = await rangeResponse.text();
        let rangeResult = { success: false };
        try {
            rangeResult = JSON.parse(rangeText);
        } catch (e) {
            console.error('Event series range response is not valid JSON:', rangeText);
        }
        
        if (rangeResult.success && rangeResult.data.count > 1) {
            const startDate = new Date(rangeResult.data.first_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const endDate = new Date(rangeResult.data.last_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            // Format the recurring days into a readable note (e.g., "Every Monday, Tuesday and Friday")
            let recurrenceNote = "";
            if (rangeResult.data.days) {
                const dayMap = {
                    'Monday': 'MON',
                    'Tuesday': 'TUE',
                    'Wednesday': 'WED',
                    'Thursday': 'THUR',
                    'Friday': 'FRI',
                    'Saturday': 'SAT',
                    'Sunday': 'SUN'
                };
                
                const daysArray = rangeResult.data.days.split(',').map(d => dayMap[d] || d);
                if (daysArray.length > 0 && daysArray.length < 7) {
                    let daysStr = "";
                    if (daysArray.length === 1) {
                        daysStr = daysArray[0];
                    } else {
                        const lastDay = daysArray.pop();
                        daysStr = daysArray.join(', ') + ' and ' + lastDay;
                    }
                    recurrenceNote = ` (Every ${daysStr})`;
                } else if (daysArray.length === 7) {
                    recurrenceNote = " (Every day)";
                }
            }
            dateText = `${startDate} until ${endDate}${recurrenceNote}`;
        } else {
            dateText = eventObj.start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        }
    } catch (e) {
        console.error('Error detecting event range:', e);
        dateText = eventObj.start.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }
    
    let timeText = eventObj.allDay ? 'All Day' : eventObj.start.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    if (!eventObj.allDay && eventObj.end) timeText += ' - ' + eventObj.end.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    let printFrame = document.getElementById('eventApprovalPrintFrame');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'eventApprovalPrintFrame';
        printFrame.style.position = 'fixed'; printFrame.style.bottom = '0'; printFrame.style.right = '0'; printFrame.style.width = '0'; printFrame.style.height = '0'; printFrame.style.border = 'none';
        document.body.appendChild(printFrame);
    }

    const doc = printFrame.contentWindow.document;
    doc.open();
    doc.write(`
        <html>
        <head>
            <link rel="icon" type="image/png" href="uploads/favicon.png">
            <title>Event Approval - ${eventObj.title}</title>
            <style>
                @page { size: A4; margin: 20mm; }
                body { font-family: 'Inter', Arial, sans-serif; margin: 0; padding: 0; color: #1f2937; line-height: 1.5; }
                .cert-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 3px double #7a51c9; padding-bottom: 10px; }
                .header-center { flex: 1; text-align: center; }
                .header-center p { margin: 2px 0; font-size: 14px; }
                .brgy-name { font-weight: bold; font-size: 18px; text-transform: uppercase; margin-top: 5px; color: #1e1b4b; }
                .content { padding: 20px 0; }
                .title { text-align: center; font-size: 22px; font-weight: 800; margin-bottom: 40px; text-transform: uppercase; color: #1e1b4b; text-decoration: underline; }
                .salutation { font-weight: 600; margin-bottom: 20px; }
                .body-text { text-indent: 50px; text-align: justify; margin-bottom: 30px; }
                .detail-table { width: 100%; margin-bottom: 40px; border-collapse: collapse; }
                .detail-table td { padding: 8px 10px; vertical-align: top; }
                .label { font-weight: bold; width: 150px; color: #4b5563; text-transform: uppercase; font-size: 13px; }
                .value { border-bottom: 1px solid #e5e7eb; font-weight: 500; }
                .approval-section { margin-top: 60px; float: right; width: 250px; text-align: center; }
                .signature-line { border-top: 2px solid #000; margin-top: 40px; padding-top: 5px; }
                .approver-name { font-weight: bold; text-transform: uppercase; font-size: 15px; }
                .approver-title { font-size: 13px; color: #6b7280; }
            </style>
        </head>
        <body>
            <div class="cert-header">
                ${brgyLogoHtml}
                <div class="header-center">
                    <p>Republic of the Philippines</p>
                    <p>Province of ${brgyInfo.province_name}</p>
                    <p>Municipality of ${brgyInfo.town_name}</p>
                    <p class="brgy-name">${brgyInfo.barangay_name}</p>
                </div>
                ${skLogoHtml}
            </div>
            <div class="content">
                <div class="title">Notice of Event Approval</div>
                <p class="salutation">TO WHOM IT MAY CONCERN:</p>
                <p class="body-text">This is to officially notify that the request to conduct the community event detailed below has been formally reviewed and <strong>APPROVED</strong> by the Barangay Council. This approval is subject to the strict adherence to community guidelines and safety protocols.</p>
                <table class="detail-table">
                    <tr><td class="label">Event Title:</td><td class="value">${eventObj.title}</td></tr>
                    <tr><td class="label">Date:</td><td class="value">${dateText}</td></tr>
                    <tr><td class="label">Time:</td><td class="value">${timeText}</td></tr>
                    <tr><td class="label">Location:</td><td class="value">${eventObj.extendedProps.location || 'N/A'}</td></tr>
                    <tr><td class="label">Organizer:</td><td class="value">${organizerText}</td></tr>
                    <tr><td class="label">Description:</td><td class="value">${eventObj.extendedProps.description || 'No additional details.'}</td></tr>
                </table>
                <p class="body-text">Issued this ${new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' })} at ${brgyInfo.barangay_name}, ${brgyInfo.town_name}, ${brgyInfo.province_name}.</p>
                <div class="approval-section">
                    <div class="signature-line">
                        <div class="approver-name">HON.${approverName}</div>
                        <div class="approver-title">Approving Official</div>
                    </div>
                </div>
            </div>
        </body></html>`);
    doc.close();

    // Add activity log
    const logData = new FormData();
    logData.append('action', 'Print Event Approval');
    logData.append('description', 'Printed the event approval for: ' + eventObj.title);
    fetch('model/log_print_masterlist.php', { method: 'POST', body: logData }).catch(e => console.error(e));

    setTimeout(() => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); }, 500);
}

/**
 * Prints the filtered events masterlist
 */
async function handlePrintEventsMasterlist() {
    if (!eventsTable || !eventsTable.filteredRows) {
        showNotification('Unable to print: table data not ready', 'error');
        return;
    }

    // Fetch Barangay Info for header
    let brgyInfo = {
        province_name: 'Province',
        town_name: 'Municipality',
        barangay_name: 'Barangay',
        barangay_logo: '',
        official_emblem: ''
    };
    
    try {
        const response = await fetch('model/get_barangay_info.php');
        if (response.ok) {
            const text = await response.text();
            try {
                const data = JSON.parse(text);
                if (data.success && data.data) {
                    brgyInfo = data.data;
                }
            } catch (e) {
                console.error('Barangay info response for masterlist is not valid JSON:', text);
            }
        }
    } catch (error) {
        console.error('Error fetching barangay info:', error);
    }
    
    // Prepare Print Iframe
    let printFrame = document.getElementById('eventsMasterlistPrintFrame');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = 'eventsMasterlistPrintFrame';
        printFrame.style.position = 'fixed';
        printFrame.style.bottom = '0';
        printFrame.style.right = '0';
        printFrame.style.width = '0';
        printFrame.style.height = '0';
        printFrame.style.border = 'none';
        document.body.appendChild(printFrame);
    }

    const doc = printFrame.contentWindow.document;
    doc.open();

    let rowsHtml = '';
    eventsTable.filteredRows.forEach((row, index) => {
        if (row.cells.length < 6) return;
        
        const no = index + 1;
        const date = row.cells[0]?.textContent.trim() || '';
        const time = row.cells[1]?.textContent.trim() || '';
        const title = row.cells[2]?.textContent.trim() || '';
        const location = row.cells[3]?.textContent.trim() || '';
        const organizer = row.cells[4]?.textContent.trim() || '';

        rowsHtml += `
            <tr>
                <td style="text-align: center;">${no}</td>
                <td>${date}</td>
                <td>${time}</td>
                <td><strong>${title}</strong></td>
                <td>${location}</td>
                <td>${organizer}</td>
            </tr>`;
    });

    const brgyLogoHtml = brgyInfo.barangay_logo 
        ? `<img src="${brgyInfo.barangay_logo}" style="width: 80px; height: 80px; object-fit: contain;">`
        : `<div style="width: 80px; height: 80px; border: 1px solid #ddd;"></div>`;
        
    const govLogoHtml = brgyInfo.official_emblem
        ? `<img src="${brgyInfo.official_emblem}" style="width: 80px; height: 80px; object-fit: contain;">`
        : `<div style="width: 80px; height: 80px; border: 1px solid #ddd;"></div>`;

    // Get active filter labels for the title
    let filterInfo = "";
    const activeTab = document.querySelector('.tab-btn.active');
    if (activeTab && activeTab.getAttribute('data-filter') !== 'all') {
        filterInfo = ` - Status: ${activeTab.textContent.trim()}`;
    }
    const searchTerm = document.getElementById('eventSearchInput')?.value.trim();
    if (searchTerm) {
        filterInfo += ` (Search: "${searchTerm}")`;
    }

    doc.write(`
        <html>
        <head>
            <title>Events Masterlist - ${new Date().toLocaleDateString()}</title>
            <style>
                @page { size: A4 landscape; margin: 15mm; }
                body { font-family: "Times New Roman", Times, serif; margin: 0; padding: 0; line-height: 1.4; color: #000; }
                .cert-header { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center; 
                    margin-bottom: 20px; 
                    border-bottom: 3px double #7a51c9; 
                    padding-bottom: 10px; 
                    text-align: center;
                }
                .header-center { flex: 1; text-align: center; }
                .header-center p { margin: 2px 0; font-size: 14px; }
                .brgy-name { font-weight: bold; font-size: 16px; text-transform: uppercase; }
                .report-title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; text-transform: uppercase; text-decoration: underline; }
                .data-table { width: 100%; border-collapse: collapse; font-size: 11px; }
                .data-table th, .data-table td { border: 1px solid #000; padding: 8px; text-align: left; }
                .data-table th { background-color: #f3f4f6 !important; -webkit-print-color-adjust: exact; }
                .print-footer { margin-top: 40px; display: flex; justify-content: space-between; }
                .sig-box { width: 250px; text-align: center; }
                .sig-line { border-top: 1px solid #000; margin-top: 40px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="cert-header">${brgyLogoHtml}<div class="header-center"><p>Republic of the Philippines</p><p>Province of ${brgyInfo.province_name}</p><p>Municipality of ${brgyInfo.town_name}</p><p class="brgy-name">${brgyInfo.barangay_name}</p></div>${govLogoHtml}</div>
            <div class="report-title">Barangay Events Masterlist${filterInfo}</div>
            <table class="data-table"><thead><tr><th>#</th><th>Date</th><th>Time</th><th>Title</th><th>Location</th><th>Organizer</th></tr></thead><tbody>${rowsHtml}</tbody></table>
            <div class="print-footer">
                <div class="sig-box"><div class="sig-line">Prepared By</div><div style="font-size:11px;">Barangay Secretary</div></div>
                <div class="sig-box"><div class="sig-line">Certified Correct</div><div style="font-size:11px;">Punong Barangay</div></div>
            </div>
        </body></html>`);
    doc.close();

    // Add activity log
    const logData = new FormData();
    logData.append('action', 'Print Masterlist');
    logData.append('description', 'Printed the events masterlist');
    fetch('model/log_print_masterlist.php', { method: 'POST', body: logData }).catch(e => console.error(e));

    setTimeout(() => { printFrame.contentWindow.focus(); printFrame.contentWindow.print(); }, 500);
}