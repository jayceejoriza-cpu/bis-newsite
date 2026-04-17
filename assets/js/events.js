// Events Page JavaScript - FullCalendar + AJAX
document.addEventListener('DOMContentLoaded', function() {
  let calendar;
  let currentEvents = [];

  // Initialize FullCalendar
  function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      height: 'auto',
      events: 'model/get_events.php', // AJAX endpoint
      eventClick: function(info) {
        showEventDetails(info.event);
      },
      dateClick: function(info) {
        showEventModal(null, info.dateStr);
      },
      editable: false,
      selectable: false,
      eventDisplay: 'block',
      eventTimeFormat: {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      }
    });
    calendar.render();
  }

  // Load and display events
  function loadEvents() {
    if (calendar) {
      calendar.refetchEvents();
    }
  }

  // Show event details modal
  function showEventDetails(event) {
    document.getElementById('eventDetailTitle').textContent = event.title;
    document.getElementById('eventDetailDesc').textContent = event.extendedProps.description || 'No description';
    document.getElementById('eventDetailDate').textContent = new Date(event.start).toLocaleDateString();
    document.getElementById('eventDetailTime').textContent = event.extendedProps.start_time || 'All day';
    document.getElementById('eventDetailLocation').textContent = event.extendedProps.location || 'Barangay Hall';
    document.getElementById('eventDetailModal').style.display = 'flex';
  }

  // Show create/edit event modal
  function showEventModal(eventId = null, date = null) {
    const modal = document.getElementById('eventModal');
    const titleInput = document.getElementById('eventTitle');
    const dateInput = document.getElementById('eventDateTime');
    const descInput = document.getElementById('eventDesc');
    const locationInput = document.getElementById('eventLocation');
    const modalTitle = document.getElementById('eventModalTitle');

    if (date) dateInput.value = date;
    if (eventId) {
      modalTitle.textContent = 'Edit Event';
      // Load event data via AJAX
      fetch(`model/get_event.php?id=${eventId}`)
        .then(r => r.json())
        .then(data => {
          titleInput.value = data.title;
          dateInput.value = data.event_date;
          descInput.value = data.description;
          locationInput.value = data.location;
        });
    } else {
      modalTitle.textContent = 'Create New Event';
      titleInput.value = '';
      descInput.value = '';
      locationInput.value = '';
    }
    modal.style.display = 'flex';
  }

  // Save event
  document.getElementById('saveEventBtn')?.addEventListener('click', function() {
    const formData = new FormData();
    formData.append('title', document.getElementById('eventTitle').value);
    formData.append('event_date', document.getElementById('eventDateTime').value);
    formData.append('description', document.getElementById('eventDesc').value);
    formData.append('location', document.getElementById('eventLocation').value);
    formData.append('event_type', document.getElementById('eventType').value);
    formData.append('resident_id', document.getElementById('eventResidentId').value);

    fetch('model/create_event.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        loadEvents();
        closeModal('eventModal');
        showNotification('Event saved successfully!', 'success');
      } else {
        showNotification(data.message || 'Save failed', 'error');
      }
    });
  });

  // Close modals
  function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }

  // Notification helper (matches other pages)
  function showNotification(message, type = 'info') {
    // Reuse existing notification system or create similar
    const notif = document.createElement('div');
    notif.className = `notification notification-${type}`;
    notif.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(notif);
    setTimeout(() => notif.remove(), 3000);
  }

  // Event listeners
  document.querySelector('.create-event-btn')?.addEventListener('click', () => showEventModal());
  document.getElementById('closeEventModal')?.addEventListener('click', () => closeModal('eventModal'));
  document.getElementById('closeEventDetail')?.addEventListener('click', () => closeModal('eventDetailModal'));

  // Close modals on outside click
  document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal(modal.id);
    });
  });

  // Initialize
  initCalendar();
  loadEvents();
});
