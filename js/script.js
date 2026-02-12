// ===================================
// Date and Time Display
// ===================================
function updateDateTime() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const dateTimeString = now.toLocaleDateString('en-US', options);
    document.getElementById('currentDateTime').textContent = dateTimeString;
}

// Update time every second
updateDateTime();
setInterval(updateDateTime, 1000);

// ===================================
// Sidebar Toggle (Desktop & Mobile)
// ===================================
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content');
const menuToggle = document.getElementById('menuToggle');
const mobileMenuToggle = document.getElementById('mobileMenuToggle');

// Desktop sidebar collapse toggle
if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });
}

// Mobile menu toggle
if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && mobileMenuToggle && !mobileMenuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Restore sidebar state from localStorage on page load
window.addEventListener('DOMContentLoaded', () => {
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && window.innerWidth > 768) {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('expanded');
    }
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        // Remove mobile active class on desktop
        sidebar.classList.remove('active');
        
        // Restore collapsed state on desktop
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    } else {
        // Remove collapsed class on mobile
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('expanded');
    }
});

// ===================================
// Theme Toggle
// ===================================
const themeToggle = document.getElementById('themeToggle');
let isDarkMode = false;

themeToggle.addEventListener('click', () => {
    isDarkMode = !isDarkMode;
    const icon = themeToggle.querySelector('i');
    
    if (isDarkMode) {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
        // Add dark mode styles here if needed
    } else {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
        // Remove dark mode styles here if needed
    }
});

// ===================================
// Population Growth Chart
// ===================================
const populationCtx = document.getElementById('populationChart').getContext('2d');

// Generate years from 2000 to 2025
const years = [];
for (let year = 2000; year <= 2025; year++) {
    years.push(year);
}

// Generate population data with slight variations
const populationData = years.map((year, index) => {
    const baseValue = 650;
    const variation = Math.sin(index * 0.5) * 50;
    const trend = index * 2;
    return baseValue + variation + trend;
});

const populationChart = new Chart(populationCtx, {
    type: 'line',
    data: {
        labels: years,
        datasets: [{
            label: 'Population',
            data: populationData,
            backgroundColor: 'rgba(147, 197, 253, 0.5)',
            borderColor: 'rgba(59, 130, 246, 0.8)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: 'rgba(59, 130, 246, 1)',
            pointHoverBorderColor: '#fff',
            pointHoverBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(59, 130, 246, 0.5)',
                borderWidth: 1,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return 'Population: ' + Math.round(context.parsed.y);
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 15,
                    color: '#6b7280'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280',
                    callback: function(value) {
                        return value;
                    }
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    }
});

// ===================================
// Blotter Records Chart
// ===================================
const blotterCtx = document.getElementById('blotterChart').getContext('2d');

const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Generate data for blotter records
const blotterData = {
    pending: months.map(() => Math.random() * 0.5 + 0.2),
    underInvestigation: months.map(() => Math.random() * 0.8 + 0.3),
    dismissed: months.map(() => Math.random() * 0.3 + 0.1),
    resolved: months.map(() => Math.random() * 1.5 + 0.5)
};

const blotterChart = new Chart(blotterCtx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [
            {
                label: 'Pending',
                data: blotterData.pending,
                backgroundColor: 'rgba(255, 107, 107, 0.6)',
                borderColor: 'rgba(255, 107, 107, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            },
            {
                label: 'Under Investigation',
                data: blotterData.underInvestigation,
                backgroundColor: 'rgba(255, 165, 0, 0.6)',
                borderColor: 'rgba(255, 165, 0, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            },
            {
                label: 'Dismissed',
                data: blotterData.dismissed,
                backgroundColor: 'rgba(211, 211, 211, 0.6)',
                borderColor: 'rgba(211, 211, 211, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            },
            {
                label: 'Resolved',
                data: blotterData.resolved,
                backgroundColor: 'rgba(144, 238, 144, 0.6)',
                borderColor: 'rgba(144, 238, 144, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(59, 130, 246, 0.5)',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#6b7280'
                }
            },
            y: {
                beginAtZero: true,
                stacked: false,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    color: '#6b7280'
                }
            }
        },
        interaction: {
            mode: 'nearest',
            axis: 'x',
            intersect: false
        }
    }
});

// ===================================
// Age Demographics Chart (Pie Chart)
// ===================================
const demographicsCtx = document.getElementById('demographicsChart').getContext('2d');

const demographicsChart = new Chart(demographicsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Children (0-17)', 'Young Adults (18-29)', 'Adults (30-59)', 'Seniors (60+)'],
        datasets: [{
            data: [30, 18, 35, 17],
            backgroundColor: [
                'rgba(74, 222, 128, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(251, 146, 60, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ],
            borderColor: [
                'rgba(74, 222, 128, 1)',
                'rgba(59, 130, 246, 1)',
                'rgba(251, 146, 60, 1)',
                'rgba(239, 68, 68, 1)'
            ],
            borderWidth: 2,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: 'rgba(59, 130, 246, 0.5)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.parsed || 0;
                        return label + ': ' + value + '%';
                    }
                }
            }
        },
        cutout: '60%'
    }
});

// ===================================
// Navigation Active State & Submenu Toggle
// ===================================
const navLinks = document.querySelectorAll('.nav-link');

navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        const parentItem = link.parentElement;
        
        // Check if this is a submenu parent
        if (parentItem.classList.contains('has-submenu')) {
            e.preventDefault();
            
            // Toggle submenu
            parentItem.classList.toggle('open');
            
            // Close other submenus
            document.querySelectorAll('.nav-item.has-submenu').forEach(item => {
                if (item !== parentItem) {
                    item.classList.remove('open');
                }
            });
        } else {
            // Remove active class from all items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked item
            parentItem.classList.add('active');
        }
    });
});

// Submenu links
const submenuLinks = document.querySelectorAll('.submenu-link');

submenuLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        // Remove active class from all submenu items
        document.querySelectorAll('.submenu-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Add active class to clicked submenu item
        link.parentElement.classList.add('active');
    });
});

// ===================================
// Smooth Scroll
// ===================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ===================================
// Chart Resize Handler
// ===================================
window.addEventListener('resize', () => {
    populationChart.resize();
    blotterChart.resize();
    demographicsChart.resize();
});

// ===================================
// Console Welcome Message
// ===================================
console.log('%c🏘️ Barangay Management System', 'color: #3b82f6; font-size: 20px; font-weight: bold;');
console.log('%cDashboard Template v1.0.0', 'color: #6b7280; font-size: 14px;');
console.log('%cBuilt with ❤️ for community management', 'color: #10b981; font-size: 12px;');
