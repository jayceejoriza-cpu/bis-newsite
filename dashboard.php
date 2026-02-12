<!-- Dashboard Content Component -->
<div class="dashboard-content">
    <h1 class="page-title">Dashboard & Analytics</h1>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-content">
                <h3 class="stat-value"><?php echo formatNumber($dashboard_stats['total_residents']); ?></h3>
                <p class="stat-label">Total Residents</p>
            </div>
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <h3 class="stat-value"><?php echo formatNumber($dashboard_stats['total_households']); ?></h3>
                <p class="stat-label">Total Households</p>
            </div>
            <div class="stat-icon green">
                <i class="fas fa-home"></i>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-content">
                <h3 class="stat-value"><?php echo formatNumber($dashboard_stats['pending_requests']); ?></h3>
                <p class="stat-label">Pending Requests</p>
            </div>
            <div class="stat-icon orange">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>
    
    <!-- Population Growth Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <div>
                <h2 class="chart-title">Population Growth</h2>
                <p class="chart-subtitle">Annual Population Statistics</p>
            </div>
        </div>
        <div class="chart-wrapper">
            <canvas id="populationChart"></canvas>
        </div>
    </div>
    
    <!-- Bottom Charts Grid -->
    <div class="charts-grid">
        <!-- Blotter Records Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <div>
                    <h2 class="chart-title">Blotter Records</h2>
                    <p class="chart-subtitle">Monthly Case Breakdown by Status</p>
                </div>
                <div class="chart-year">Year - <?php echo date('Y'); ?></div>
            </div>
            <div class="chart-legend">
                <span class="legend-item">
                    <span class="legend-color" style="background: #ff6b6b;"></span>
                    Pending
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #ffa500;"></span>
                    Under Investigation
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #d3d3d3;"></span>
                    Dismissed
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #90ee90;"></span>
                    Resolve
                </span>
            </div>
            <div class="chart-wrapper">
                <canvas id="blotterChart"></canvas>
            </div>
        </div>
        
        <!-- Age Demographics Chart -->
        <div class="chart-container">
            <div class="chart-header">
                <div>
                    <h2 class="chart-title">Age Demographics</h2>
                    <p class="chart-subtitle">Population distribution by age groups</p>
                </div>
            </div>
            <div class="chart-legend">
                <span class="legend-item">
                    <span class="legend-color" style="background: #4ade80;"></span>
                    Children (0-17)
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #3b82f6;"></span>
                    Young Adults (18-29)
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #fb923c;"></span>
                    Adults (30-59)
                </span>
                <span class="legend-item">
                    <span class="legend-color" style="background: #ef4444;"></span>
                    Seniors (60+)
                </span>
            </div>
            <div class="chart-wrapper">
                <canvas id="demographicsChart"></canvas>
            </div>
        </div>
    </div>
</div>
