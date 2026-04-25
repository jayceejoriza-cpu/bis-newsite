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
            sidebar.classList.remove('hover-expanded');
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

// Hover expansion for collapsed sidebar
if (sidebar) {
    sidebar.addEventListener('mouseenter', () => {
        if (sidebar.classList.contains('collapsed') && window.innerWidth > 768) {
            sidebar.classList.add('hover-expanded');
        }
    });

    sidebar.addEventListener('mouseleave', () => {
        sidebar.classList.remove('hover-expanded');
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
    
    // Restore sidebar scroll position
    const savedScrollPosition = sessionStorage.getItem('sidebarScrollPosition');
    if (savedScrollPosition && sidebar) {
        sidebar.scrollTop = parseInt(savedScrollPosition, 10);
    }
});

// Save sidebar scroll position before page unload
if (sidebar) {
    // Save scroll position when navigating away
    window.addEventListener('beforeunload', () => {
        sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
    });
    
    // Also save on link clicks for faster response
    const allLinks = sidebar.querySelectorAll('a');
    allLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Save scroll position immediately when clicking any link
            sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
            
            // Prevent the browser from scrolling the sidebar to bring the link into view
            // This is especially important for submenu items at the bottom
            setTimeout(() => {
                if (sidebar) {
                    const savedPosition = sessionStorage.getItem('sidebarScrollPosition');
                    if (savedPosition) {
                        sidebar.scrollTop = parseInt(savedPosition, 10);
                    }
                }
            }, 0);
        });
    });
    
    // Additional handler to maintain scroll position during page transitions
    sidebar.addEventListener('scroll', () => {
        sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
    });
}

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

// Sync body class and icon with the html class already set by dark-mode-init.js
// dark-mode-init.js runs in <head>
// <link rel="icon" type="image/png" href="uploads/favicon.png"> and applies dark-mode to <html> immediately.
// Here we just sync <body> and update the toggle icon — no flash, no re-animation.
(function syncThemeOnLoad() {
    const isDark = document.documentElement.classList.contains('dark-mode');

    if (isDark) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }

    if (themeToggle) {
        const icon = themeToggle.querySelector('i');
        if (icon) {
            if (isDark) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            } else {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }
    }
})();

// Theme toggle click handler
if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const icon = themeToggle.querySelector('i');

        // Toggle dark-mode on both <html> and <body>
        document.documentElement.classList.toggle('dark-mode');
        document.body.classList.toggle('dark-mode');

        const isDarkMode = document.body.classList.contains('dark-mode');

        // Update toggle icon
        if (icon) {
            if (isDarkMode) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            } else {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }

        // Persist preference
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    });
}

// ===================================
// Population Growth Chart
// ===================================
const populationChartEl = document.getElementById('populationChart');
let populationChart = null;

if (populationChartEl) {
    const populationCtx = populationChartEl.getContext('2d');
    
    // Initialize chart with empty data
    populationChart = new Chart(populationCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Population',
                data: [],
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
                    grace: '20%',
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        precision: 0,
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
    
    // Fetch real data from API
    const popYearSelect = document.getElementById('populationYearSelect');
    fetchPopulationData(popYearSelect ? popYearSelect.value : new Date().getFullYear());
}

// ===================================
// Blotter Records Chart
// ===================================
const blotterChartEl = document.getElementById('blotterChart');
let blotterChart = null;

if (blotterChartEl) {
    const blotterCtx = blotterChartEl.getContext('2d');
    
    // Initialize chart with empty data
    blotterChart = new Chart(blotterCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                {
                    label: 'Pending',
                    data: [],
                    backgroundColor: 'rgba(255, 107, 107, 0.6)',
                    borderColor: 'rgba(255, 107, 107, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Scheduled for Mediation',
                    data: [],
                    backgroundColor: 'rgba(59, 130, 246, 0.6)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Under Investigation',
                    data: [],
                    backgroundColor: 'rgba(255, 165, 0, 0.6)',
                    borderColor: 'rgba(255, 165, 0, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Settled',
                    data: [],
                    backgroundColor: 'rgba(144, 238, 144, 0.6)',
                    borderColor: 'rgba(144, 238, 144, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Dismissed',
                    data: [],
                    backgroundColor: 'rgba(211, 211, 211, 0.6)',
                    borderColor: 'rgba(211, 211, 211, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0
                },
                {
                    label: 'Endorsed to Police',
                    data: [],
                    backgroundColor: 'rgba(163, 73, 164, 0.6)',
                    borderColor: 'rgba(163, 73, 164, 1)',
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
                    grace: '20%',
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                    },
                    ticks: {
                        color: '#6b7280',
                        precision: 0,
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
    
    // Fetch real data from API
    fetchBlotterData(new Date().getFullYear());
}

// ===================================
// Age Demographics Chart (Pie Chart)
// ===================================
const demographicsChartEl = document.getElementById('demographicsChart');
let demographicsChart = null;

if (demographicsChartEl) {
    const demographicsCtx = demographicsChartEl.getContext('2d');
    
    // Initialize chart with empty data
    demographicsChart = new Chart(demographicsCtx, {
        type: 'doughnut',
        data: {
            labels: [
                'Newborn (0-28 days)', 
                'Infant (29 days - 1 year)', 
                'Child (1-9 years)', 
                'Adolescent (10-19 years)', 
                'Adult (20-59 years)', 
                'Senior Citizen (60+ years)'
            ],
            datasets: [{
                data: [],
                backgroundColor: [
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(245, 158, 11, 0.8)'
                ],
                borderColor: [
                    'rgba(236, 72, 153, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(59, 130, 246, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(245, 158, 11, 1)'
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
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#6b7280',
                        padding: 15
                    }
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
    
    // Fetch real data from API
    fetchDemographicsData();
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
        link.addEventListener('click', function(e) {
            // Save current scroll position before navigation
            if (sidebar) {
                sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
            }
            
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
        const href = this.getAttribute('href');
        if (href === '#') return;
        
        e.preventDefault();
        try {
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        } catch (error) {
            // Ignore invalid selector errors
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
// API Data Fetching Functions
// ===================================

/**
 * Fetch population growth data from API
 * @param {number|string} year - The year to fetch data for (e.g. 2025)
 */
function fetchPopulationData(year) {
    const selectedYear = year || new Date().getFullYear();
    fetch(`model/get_dashboard_data.php?type=population&year=${selectedYear}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                const data = result.data;

                // Update chart labels and data
                if (populationChart && data.months && data.counts) {
                    populationChart.data.labels = data.months;
                    populationChart.data.datasets[0].data = data.counts;
                    // Dynamically set suggestedMax with 30% headroom above the peak value
                    const maxVal = Math.max(...data.counts.map(v => v || 0), 0);
                    populationChart.options.scales.y.suggestedMax = maxVal > 0 ? Math.ceil(maxVal * 1.3) : 10;
                    populationChart.update();
                }

                // Update the Monthly Data Breakdown table
                updatePopulationTrendTable(data);
            } else {
                console.error('Failed to fetch population data:', result.error);
            }
        })
        .catch(error => {
            console.error('Error fetching population data:', error);
        });
}

/**
 * Populate the Monthly Data Breakdown table under the Population Growth chart
 * @param {object} data - { months: string[], counts: number[] }
 */
function updatePopulationTrendTable(data) {
    const tbody = document.getElementById('populationTrendTableBody');
    if (!tbody) return;

    const months = data.months || [];
    const counts = data.counts || [];

    if (months.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:16px;">No data available</td></tr>';
        return;
    }

    let html = '';
    for (let i = 0; i < months.length; i++) {
        const current  = counts[i] || 0;
        const previous = i > 0 ? (counts[i - 1] || 0) : current;
        const growth   = current - previous;

        let growthHtml;
        if (i === 0) {
            growthHtml = '<span style="color:var(--text-secondary);">—</span>';
        } else if (growth > 0) {
            growthHtml = `<span style="color:#10b981;">▲ ${growth.toLocaleString()}</span>`;
        } else if (growth < 0) {
            growthHtml = `<span style="color:#ef4444;">▼ ${Math.abs(growth).toLocaleString()}</span>`;
        } else {
            growthHtml = '<span style="color:var(--text-secondary);">— 0</span>';
        }

        html += `
            <tr>
                <td style="padding:4px 8px;">${months[i]}</td>
                <td class="text-right" style="padding:4px 8px;"><strong>${current.toLocaleString()}</strong></td>
                <td class="text-right" style="padding:4px 8px;">${growthHtml}</td>
            </tr>`;
    }

    tbody.innerHTML = html;
}

/**
 * Fetch blotter records data from API
 */
function fetchBlotterData(year = 'all') {
    const url = `model/get_dashboard_data.php?type=blotter&year=${year}`;
    
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                const data = result.data;
                
                // Update chart with real data (including labels)
                if (blotterChart && data.months) {
                    blotterChart.data.labels = data.months;
                    blotterChart.data.datasets[0].data = data.pending;
                    blotterChart.data.datasets[1].data = data.mediation;
                    blotterChart.data.datasets[2].data = data.underInvestigation;
                    blotterChart.data.datasets[3].data = data.settled;
                    blotterChart.data.datasets[4].data = data.dismissed;
                    blotterChart.data.datasets[5].data = data.endorsed;
                    blotterChart.update();
                }
            } else {
                console.error('Failed to fetch blotter data:', result.error);
            }
        })
        .catch(error => {
            console.error('Error fetching blotter data:', error);
        });
}

/**
 * Initialize population year filter
 */
function initializePopulationYearFilter() {
    const yearFilter = document.getElementById('populationYearSelect');
    
    if (yearFilter) {
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            fetchPopulationData(selectedYear);
        });
    }
}

/**
 * Initialize blotter year filter
 */
function initializeBlotterYearFilter() {
    const yearFilter = document.getElementById('blotterYearFilter');
    
    if (yearFilter) {
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            fetchBlotterData(selectedYear);
        });
    }
}

// Initialize year filter when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeBlotterYearFilter();
        initializePopulationYearFilter();
    });
} else {
    initializeBlotterYearFilter();
    initializePopulationYearFilter();
}

/**
 * Fetch demographics data from API
 */
function fetchDemographicsData() {
    fetch('model/get_dashboard_data.php?type=demographics')
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                const data = result.data;
                
                // Update chart with real data (use percentages for display)
                if (demographicsChart && data.percentages) {
                    demographicsChart.data.labels = data.labels;
                    demographicsChart.data.datasets[0].data = data.percentages;
                    demographicsChart.update();
                }
            } else {
                console.error('Failed to fetch demographics data:', result.error);
            }
        })
        .catch(error => {
            console.error('Error fetching demographics data:', error);
        });
}


// ===================================
// Console Welcome Message
// ===================================
console.log('%c🏘️ Barangay Management System', 'color: #3b82f6; font-size: 20px; font-weight: bold;');
console.log('%cDashboard Template v1.0.0', 'color: #6b7280; font-size: 14px;');
console.log('%cBuilt with ❤️ for community management', 'color: #10b981; font-size: 12px;');

// ===================================
// Auto-Backup Background Trigger
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        // Detect if the current page is in a subdirectory to fix relative path resolution
        const isSubdirectory = window.location.pathname.includes('/model/') || 
                               window.location.pathname.includes('/certifications/');
        const backupUrl = isSubdirectory ? '../model/run_auto_backup.php' : 'model/run_auto_backup.php';

        fetch(backupUrl).catch(e => console.log('Auto backup check error:', e));
    }, 5000); // Trigger check 5 seconds after page loads
});
