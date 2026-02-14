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
    const dateTimeElement = document.getElementById('currentDateTime');
    if (dateTimeElement) {
        dateTimeElement.textContent = dateTimeString;
    }
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

// Create mobile backdrop element
let mobileBackdrop = document.querySelector('.mobile-backdrop');
if (!mobileBackdrop) {
    mobileBackdrop = document.createElement('div');
    mobileBackdrop.className = 'mobile-backdrop';
    document.body.appendChild(mobileBackdrop);
}

// Function to open mobile sidebar
function openMobileSidebar() {
    if (window.innerWidth <= 768) {
        sidebar.classList.add('active');
        mobileBackdrop.classList.add('active');
        document.body.classList.add('sidebar-open');
    }
}

// Function to close mobile sidebar
function closeMobileSidebar() {
    sidebar.classList.remove('active');
    mobileBackdrop.classList.remove('active');
    document.body.classList.remove('sidebar-open');
}

// Desktop sidebar collapse toggle (inside sidebar)
if (menuToggle) {
    menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        
        if (window.innerWidth > 768) {
            // Desktop behavior - collapse/expand sidebar
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        } else {
            // Mobile behavior - close the sidebar
            closeMobileSidebar();
        }
    });
}

// Mobile menu toggle
if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        
        if (sidebar.classList.contains('active')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });
}

// Close sidebar when clicking backdrop
if (mobileBackdrop) {
    mobileBackdrop.addEventListener('click', () => {
        closeMobileSidebar();
    });
}

// Close sidebar when clicking outside on mobile (improved)
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        // Check if sidebar is open
        if (sidebar.classList.contains('active')) {
            // Don't close if clicking inside sidebar or on mobile menu toggle
            if (!sidebar.contains(e.target) && 
                mobileMenuToggle && 
                !mobileMenuToggle.contains(e.target)) {
                closeMobileSidebar();
            }
        }
    }
});

// Prevent clicks inside sidebar from closing it
if (sidebar) {
    sidebar.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            e.stopPropagation();
        }
    });
}

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
        // Desktop mode
        closeMobileSidebar();
        
        // Restore collapsed state on desktop
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
        }
    } else {
        // Mobile mode - remove collapsed class
        sidebar.classList.remove('collapsed');
        mainContent.classList.remove('expanded');
    }
});

// ===================================
// Theme Toggle
// ===================================
const themeToggle = document.getElementById('themeToggle');

// Check for saved theme preference or default to light mode
const currentTheme = localStorage.getItem('theme') || 'light';

// Apply the theme on page load
if (currentTheme === 'dark') {
    document.body.classList.add('dark-mode');
    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
}

// Theme toggle functionality
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const icon = themeToggle.querySelector('i');
        
        // Toggle dark mode class on body
        document.body.classList.toggle('dark-mode');
        
        // Check if dark mode is now active
        const isDarkMode = document.body.classList.contains('dark-mode');
        
        // Update icon
        if (icon) {
            if (isDarkMode) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            } else {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
        
        // Save theme preference to localStorage
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    });
}

// ===================================
// Population Growth Chart
// ===================================
const populationChartEl = document.getElementById('populationChart');

if (populationChartEl) {
const populationCtx = populationChartEl.getContext('2d');
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
}

// ===================================
// Blotter Records Chart
// ===================================
const blotterChartEl = document.getElementById('blotterChart');

if (blotterChartEl) {
const blotterCtx = blotterChartEl.getContext('2d');

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
}

// ===================================
// Age Demographics Chart (Pie Chart)
// ===================================
const demographicsChartEl = document.getElementById('demographicsChart');

if (demographicsChartEl) {
const demographicsCtx = demographicsChartEl.getContext('2d');

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
}

// ===================================
// Navigation Active State & Submenu Toggle
// ===================================
function initializeSidebar() {
    // Handle submenu toggle
    const navItems = document.querySelectorAll('.nav-item.has-submenu');
    
    navItems.forEach(item => {
        const navLink = item.querySelector('.nav-link');
        const submenu = item.querySelector('.submenu');
        
        if (navLink && submenu) {
            // Set initial max-height for submenus that are already open
            // Use requestAnimationFrame to ensure DOM is fully rendered
            if (item.classList.contains('open')) {
                requestAnimationFrame(() => {
                    // Force a reflow to ensure scrollHeight is calculated correctly
                    submenu.style.display = 'block';
                    const height = submenu.scrollHeight;
                    submenu.style.display = '';
                    
                    // Set the max-height to the calculated height
                    if (height > 0) {
                        submenu.style.maxHeight = height + 'px';
                    } else {
                        // Fallback: set a large enough value if scrollHeight is still 0
                        submenu.style.maxHeight = '500px';
                    }
                });
            }
            
            navLink.addEventListener('click', function(e) {
                e.preventDefault();
                
                const isOpen = item.classList.contains('open');
                
                // Close other submenus first
                navItems.forEach(otherItem => {
                    if (otherItem !== item && otherItem.classList.contains('open')) {
                        const otherSubmenu = otherItem.querySelector('.submenu');
                        otherItem.classList.remove('open');
                        if (otherSubmenu) {
                            otherSubmenu.style.maxHeight = '0px';
                        }
                    }
                });
                
                // Toggle the current submenu
                if (isOpen) {
                    // Close it
                    item.classList.remove('open');
                    submenu.style.maxHeight = '0px';
                } else {
                    // Open it
                    item.classList.add('open');
                    // Calculate the actual height needed
                    const height = submenu.scrollHeight;
                    submenu.style.maxHeight = height + 'px';
                }
            });
        }
    });
    
    // Handle regular nav links (non-submenu)
    const regularNavLinks = document.querySelectorAll('.nav-item:not(.has-submenu) .nav-link');
    
    regularNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked item's parent
            this.parentElement.classList.add('active');
        });
    });
    
    // Handle submenu links
    const submenuLinks = document.querySelectorAll('.submenu-link');
    
    submenuLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all submenu items
            document.querySelectorAll('.submenu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked submenu item
            this.parentElement.classList.add('active');
        });
    });
}

// Initialize sidebar when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeSidebar);
} else {
    initializeSidebar();
}

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
// window.addEventListener('resize', () => {
//     populationChart.resize();
//     blotterChart.resize();
//     demographicsChart.resize();
// });

// ===================================
// Console Welcome Message
// ===================================
console.log('%c🏘️ Barangay Management System', 'color: #3b82f6; font-size: 20px; font-weight: bold;');
console.log('%cDashboard Template v1.0.0', 'color: #6b7280; font-size: 14px;');
console.log('%cBuilt with ❤️ for community management', 'color: #10b981; font-size: 12px;');
