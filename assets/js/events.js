/**
 * Barangay Events & Scheduling System
 * Handles FullCalendar initialization and Event CRUD operations
 */

let calendar;

document.addEventListener('DOMContentLoaded', function() {
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
                // Optional: Add tooltips or color coding based on event_type
                if (info.event.extendedProps.event_type === 'Resident') {
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
    const saveEventBtn = document.getElementById('saveEventBtn');
    if (saveEventBtn) {
        saveEventBtn.addEventListener('click', function() {
            const form = document.getElementById('eventForm');
            const title = document.getElementById('eventTitle').value;
            const date = document.getElementById('eventDate').value;
            const startTime = document.getElementById('eventStartTime').value;
            const endTime = document.getElementById('eventEndTime').value;
            const type = document.getElementById('eventType').value;

            if (!title || !date || !startTime) {
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
            
            const eventId = form.querySelector('input[name="event_id"]')?.value;
            const url = eventId ? 'model/update_event.php' : 'model/create_event.php';
            if (eventId) formData.append('id', eventId);

            saveEventBtn.disabled = true;
            saveEventBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Event scheduled successfully!');
                    eventModal.style.display = "none";
                    form.reset();
                    calendar.refetchEvents(); // Refresh calendar view
                    location.reload(); // Refresh table below
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the event.');
            })
            .finally(() => {
                saveEventBtn.disabled = false;
                saveEventBtn.innerHTML = 'Save Event';
            });
        });
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
            
            const rect = actionBtn.getBoundingClientRect();
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;
            
            // Temporarily show to measure dimensions
            const originalDisplay = menu.style.display;
            menu.style.display = 'block';
            const menuHeight = menu.offsetHeight;
            const menuWidth = menu.offsetWidth || 180;
            menu.style.display = originalDisplay;

            menu.style.position = 'fixed';
            
            // Vertical positioning: flip up if it hits the bottom
            if (rect.bottom + menuHeight + 5 > windowHeight) {
                menu.style.top = (rect.top - menuHeight - 5) + 'px';
            } else {
                menu.style.top = (rect.bottom + 5) + 'px';
            }

            // Horizontal positioning: align to right and prevent left overflow
            let leftPos = rect.right - menuWidth;
            if (leftPos < 10) leftPos = 10; 
            menu.style.left = leftPos + 'px';

            menu.classList.toggle('show');
            
            if (menu.classList.contains('show')) {
                document.body.appendChild(menu);
                currentOpenMenu = menu;
            } else {
                currentOpenMenu = null;
            }
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

        detailModal.style.display = "flex";
    } else {
        // Fallback: Fetch from database if not in current calendar view
        fetch(`model/get_event_details.php?id=${id}`)
            .then(res => res.json())
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
        
        if (eventObj.extendedProps.event_type === 'Resident') {
            document.getElementById('tabResident').click();
            document.getElementById('eventResidentId').value = eventObj.extendedProps.resident_id || '';
            document.getElementById('eventResidentName').value = eventObj.extendedProps.resident_name || '';
        } else {
            document.getElementById('tabBarangay').click();
            document.getElementById('eventResidentId').value = '';
            document.getElementById('eventResidentName').value = '';
        }

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
window.deleteEvent = function(id) {
    if (confirm('Are you sure you want to delete this event?')) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('model/delete_event.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Event deleted successfully!');
                calendar.refetchEvents();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An error occurred while deleting the event.');
        });
    }
};