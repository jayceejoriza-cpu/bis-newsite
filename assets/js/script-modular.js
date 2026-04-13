// ===================================
// Main Initialization Function
// Called after components are loaded
// ===================================
function initializeApp() {
    initializeDateTime();
    initializeMobileMenu();
    initializeThemeToggle();
    initializeCharts();
    initializeNavigation();
    
    console.log('%c🏘️ Barangay Management System', 'color: #3b82f6; font-size: 20px; font-weight: bold;');
    console.log('%cDashboard Template v1.0.0 (Modular)', 'color: #6b7280; font-size: 14px;');
    console.log('%cBuilt with ❤️ for community management', 'color: #10b981; font-size: 12px;');
}

// ===================================
// Date and Time Display
// ===================================
function initializeDateTime() {
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
}

// ===================================
// Mobile Menu Toggle
// ===================================
function initializeMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');

    if (mobileMenuToggle && sidebar) {
        mobileMenuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
}

// ===================================
// Theme Toggle
// ===================================
function initializeThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    let isDarkMode = false;

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            isDarkMode = !isDarkMode;
            const icon = themeToggle.querySelector('i');
            
            if (icon) {
                if (isDarkMode) {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    // Add dark mode styles here if needed
                } else {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    // Remove dark mode styles here if needed
                }
            }
        });
    }
}

// ===================================
// Charts Initialization
// ===================================
function initializeCharts() {
    initializePopulationChart();
    initializeBlotterChart();
    initializeDemographicsChart();
    
    // Chart Resize Handler
    window.addEventListener('resize', () => {
        if (window.populationChart) window.populationChart.resize();
        if (window.blotterChart) window.blotterChart.resize();
        if (window.demographicsChart) window.demographicsChart.resize();
    });
}

// ===================================
// Population Growth Chart
// ===================================
function initializePopulationChart() {
    const canvas = document.getElementById('populationChart');
    if (!canvas) return;
    
    const populationCtx = canvas.getContext('2d');

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

    window.populationChart = new Chart(populationCtx, {
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
function initializeBlotterChart() {
    const canvas = document.getElementById('blotterChart');
    if (!canvas) return;
    
    const blotterCtx = canvas.getContext('2d');

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Generate data for blotter records
    const blotterData = {
        pending: months.map(() => Math.random() * 0.5 + 0.2),
        underInvestigation: months.map(() => Math.random() * 0.8 + 0.3),
        dismissed: months.map(() => Math.random() * 0.3 + 0.1),
        settled: months.map(() => Math.random() * 1.5 + 0.5)
    };

    window.blotterChart = new Chart(blotterCtx, {
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
                    label: 'Settled',
                    data: blotterData.settled,
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
function initializeDemographicsChart() {
    const canvas = document.getElementById('demographicsChart');
    if (!canvas) return;
    
    const demographicsCtx = canvas.getContext('2d');

    window.demographicsChart = new Chart(demographicsCtx, {
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
                data: [5, 10, 20, 20, 35, 10],
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
}

// ===================================
// Navigation Active State
// ===================================
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            // Remove active class from all items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to clicked item
            link.parentElement.classList.add('active');
        });
    });
    
    // Smooth Scroll
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
}
