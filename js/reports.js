/**
 * Reports Page JavaScript
 * Handles: Tab switching, Chart.js charts, Print functionality
 */

// ============================================
// Chart instances (kept for destroy on re-render)
// ============================================
const reportCharts = {};

// ============================================
// Color Palettes
// ============================================
const COLORS = {
    blue:   '#3b82f6',
    green:  '#10b981',
    orange: '#f59e0b',
    red:    '#ef4444',
    purple: '#8b5cf6',
    teal:   '#14b8a6',
    pink:   '#ec4899',
    indigo: '#6366f1',
    yellow: '#eab308',
    gray:   '#6b7280',
};

const PALETTE = Object.values(COLORS);

const STATUS_COLORS = {
    'Pending':            '#f59e0b',
    'Under Investigation':'#6366f1',
    'Resolved':           '#10b981',
    'Dismissed':          '#6b7280',
    'Approved':           '#10b981',
    'Rejected':           '#ef4444',
    'Completed':          '#3b82f6',
};

const PAYMENT_COLORS = {
    'Paid':   '#10b981',
    'Unpaid': '#ef4444',
    'Waived': '#8b5cf6',
};

// ============================================
// Utility: get dark-mode aware text color
// ============================================
function getTextColor() {
    return document.body.classList.contains('dark-mode') ? '#f9fafb' : '#1f2937';
}

function getGridColor() {
    return document.body.classList.contains('dark-mode') ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
}

// ============================================
// Destroy chart if exists
// ============================================
function destroyChart(id) {
    if (reportCharts[id]) {
        reportCharts[id].destroy();
        delete reportCharts[id];
    }
}

// ============================================
// Tab Switching
// ============================================
function initTabs() {
    const tabBtns = document.querySelectorAll('.report-tab-btn');
    const tabContents = document.querySelectorAll('.report-tab-content');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tab;

            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            btn.classList.add('active');
            const targetContent = document.getElementById('tab-' + target);
            if (targetContent) {
                targetContent.classList.add('active');
                // Render charts for the newly visible tab
                renderChartsForTab(target);
            }
        });
    });
}

// ============================================
// Render charts for a specific tab
// ============================================
function renderChartsForTab(tab) {
    switch (tab) {
        case 'population':
            renderGenderChart();
            renderAgeGroupChart();
            break;
        case 'blotter':
            renderBlotterStatusChart();
            renderBlotterMonthlyChart();
            renderBlotterTypeChart();
            break;
        case 'certificates':
            renderCertTypeChart();
            renderCertStatusChart();
            break;
        case 'households':
            renderWaterSourceChart();
            renderToiletChart();
            break;
    }
}

// ============================================
// POPULATION CHARTS
// ============================================
function renderGenderChart() {
    destroyChart('genderChart');
    const canvas = document.getElementById('genderChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    reportCharts['genderChart'] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [COLORS.blue, COLORS.pink, COLORS.teal],
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1f2937' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getTextColor(),
                        padding: 16,
                        font: { family: 'Inter', size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${((ctx.parsed / values.reduce((a,b)=>a+b,0))*100).toFixed(1)}%)`
                    }
                }
            },
            cutout: '60%',
        }
    });
}

function renderAgeGroupChart() {
    destroyChart('ageGroupChart');
    const canvas = document.getElementById('ageGroupChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    reportCharts['ageGroupChart'] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Residents',
                data: values,
                backgroundColor: [COLORS.green, COLORS.blue, COLORS.orange, COLORS.red],
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y.toLocaleString()} residents`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 } },
                    grid: { color: getGridColor() }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 }, precision: 0 },
                    grid: { color: getGridColor() }
                }
            }
        }
    });
}

// ============================================
// BLOTTER CHARTS
// ============================================
function renderBlotterStatusChart() {
    destroyChart('blotterStatusChart');
    const canvas = document.getElementById('blotterStatusChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');
    const bgColors = labels.map(l => STATUS_COLORS[l] || COLORS.gray);

    reportCharts['blotterStatusChart'] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1f2937' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getTextColor(),
                        padding: 14,
                        font: { family: 'Inter', size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = values.reduce((a,b)=>a+b,0);
                            return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${total > 0 ? ((ctx.parsed/total)*100).toFixed(1) : 0}%)`;
                        }
                    }
                }
            },
            cutout: '60%',
        }
    });
}

function renderBlotterMonthlyChart() {
    destroyChart('blotterMonthlyChart');
    const canvas = document.getElementById('blotterMonthlyChart');
    if (!canvas) return;

    const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const values = JSON.parse(canvas.dataset.values || '[]');

    reportCharts['blotterMonthlyChart'] = new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Blotter Records',
                data: values,
                borderColor: COLORS.indigo,
                backgroundColor: 'rgba(99,102,241,0.12)',
                borderWidth: 2.5,
                pointBackgroundColor: COLORS.indigo,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} records`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 } },
                    grid: { color: getGridColor() }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 }, precision: 0 },
                    grid: { color: getGridColor() }
                }
            }
        }
    });
}

function renderBlotterTypeChart() {
    destroyChart('blotterTypeChart');
    const canvas = document.getElementById('blotterTypeChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    reportCharts['blotterTypeChart'] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cases',
                data: values,
                backgroundColor: PALETTE.slice(0, labels.length),
                borderRadius: 5,
                borderSkipped: false,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.x} cases`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 }, precision: 0 },
                    grid: { color: getGridColor() }
                },
                y: {
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });
}

// ============================================
// CERTIFICATE CHARTS
// ============================================
function renderCertTypeChart() {
    destroyChart('certTypeChart');
    const canvas = document.getElementById('certTypeChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    reportCharts['certTypeChart'] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Requests',
                data: values,
                backgroundColor: PALETTE.slice(0, labels.length),
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.parsed.y} requests`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 }, maxRotation: 30 },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: { color: getTextColor(), font: { family: 'Inter', size: 11 }, precision: 0 },
                    grid: { color: getGridColor() }
                }
            }
        }
    });
}

function renderCertStatusChart() {
    destroyChart('certStatusChart');
    const canvas = document.getElementById('certStatusChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');
    const bgColors = labels.map(l => STATUS_COLORS[l] || COLORS.gray);

    reportCharts['certStatusChart'] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: bgColors,
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1f2937' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getTextColor(),
                        padding: 14,
                        font: { family: 'Inter', size: 12 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = values.reduce((a,b)=>a+b,0);
                            return ` ${ctx.label}: ${ctx.parsed.toLocaleString()} (${total > 0 ? ((ctx.parsed/total)*100).toFixed(1) : 0}%)`;
                        }
                    }
                }
            },
            cutout: '60%',
        }
    });
}

// ============================================
// HOUSEHOLD CHARTS
// ============================================
function renderWaterSourceChart() {
    destroyChart('waterSourceChart');
    const canvas = document.getElementById('waterSourceChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    if (labels.length === 0) return;

    reportCharts['waterSourceChart'] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: PALETTE.slice(0, labels.length),
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1f2937' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getTextColor(),
                        padding: 12,
                        font: { family: 'Inter', size: 12 }
                    }
                }
            },
            cutout: '55%',
        }
    });
}

function renderToiletChart() {
    destroyChart('toiletChart');
    const canvas = document.getElementById('toiletChart');
    if (!canvas) return;

    const labels = JSON.parse(canvas.dataset.labels || '[]');
    const values = JSON.parse(canvas.dataset.values || '[]');

    if (labels.length === 0) return;

    reportCharts['toiletChart'] = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: [COLORS.teal, COLORS.blue, COLORS.purple, COLORS.orange, COLORS.green],
                borderWidth: 2,
                borderColor: document.body.classList.contains('dark-mode') ? '#1f2937' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: getTextColor(),
                        padding: 12,
                        font: { family: 'Inter', size: 12 }
                    }
                }
            },
            cutout: '55%',
        }
    });
}

// ============================================
// Year filter for blotter monthly chart
// ============================================
function initYearFilter() {
    const yearSelect = document.getElementById('blotterYearSelect');
    if (!yearSelect) return;

    yearSelect.addEventListener('change', function () {
        const year = this.value;
        fetch(`get_report_data.php?type=blotter_monthly&year=${year}`)
            .then(r => r.json())
            .then(data => {
                const canvas = document.getElementById('blotterMonthlyChart');
                if (canvas) {
                    canvas.dataset.values = JSON.stringify(data.values || []);
                    renderBlotterMonthlyChart();
                }
            })
            .catch(() => {});
    });
}

// ============================================
// Print Report
// ============================================
function initPrint() {
    const printBtn = document.getElementById('printReportBtn');
    if (!printBtn) return;

    printBtn.addEventListener('click', () => {
        window.print();
    });
}

// ============================================
// Re-render charts on dark mode toggle
// ============================================
function watchDarkMode() {
    const observer = new MutationObserver(() => {
        const activeTab = document.querySelector('.report-tab-btn.active');
        if (activeTab) {
            renderChartsForTab(activeTab.dataset.tab);
        }
    });
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
}

// ============================================
// Init
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initPrint();
    initYearFilter();
    watchDarkMode();

    // Render charts for the default active tab (population)
    renderChartsForTab('population');
});
