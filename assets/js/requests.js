document.addEventListener('DOMContentLoaded', function() {
    loadRequests();
});

function loadRequests() {
    const tableBody = document.getElementById('requestsTableBody');
    if (!tableBody) return;

    fetch('model/get_requests.php')
        .then(response => response.json())
        .then(data => {
            tableBody.innerHTML = '';
            if (data.data && data.data.length > 0) {
                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.resident_id || 'N/A'}</td>
                        <td>${row.resident_name || 'N/A'}</td>
                        <td>${row.certificate_name || 'N/A'}</td>
                        <td>${row.purpose || 'N/A'}</td>
                        <td>${new Date(row.date_requested).toLocaleDateString()}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No requests found</td></tr>';
            }
        })
        .catch(error => console.error('Error loading requests:', error));
}