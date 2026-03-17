<!-- Dashboard Content Component -->
<div class="dashboard-content">

    <!-- Official Print Header -->
    <div class="print-only print-header">
        <div class="header-logos">
            <img src="<?php echo !empty($barangayInfo['barangay_logo']) ? $barangayInfo['barangay_logo'] : 'assets/image/brgylogo.jpg'; ?>" class="logo-placeholder" alt="Barangay Logo">
            <div class="header-text">
                <p>Republic of the Philippines</p>
                <p>Province of <?php echo htmlspecialchars($barangayInfo['province_name'] ?? '[Province Name]'); ?>, City/Municipality of <?php echo htmlspecialchars($barangayInfo['town_name'] ?? '[City Name]'); ?></p>
                <p class="office-name">OFFICE OF THE SANGGUNIANG BARANGAY OF <?php echo strtoupper(htmlspecialchars($barangayInfo['barangay_name'] ?? '[BARANGAY NAME]')); ?></p>
            </div>
            <img src="<?php echo !empty($barangayInfo['municipal_logo']) ? $barangayInfo['municipal_logo'] : 'assets/image/citylogo.png'; ?>" class="logo-placeholder" alt="City Logo">
        </div>
        <h2 class="report-title">MONTHLY SUMMARY REPORT</h2>
    </div>

    <!-- Page Header -->
    <div class="reports-header">
        <div class="reports-header-left">
            <h1 class="page-title">Dashboard & Analytics</h1>
            <p class="page-subtitle">Comprehensive barangay statistics and data summaries</p>
        </div>
        <div class="reports-header-right no-print">
            <button class="btn-print" id="printReportBtn">
                <i class="fas fa-print"></i>
                Print Report
            </button>
        </div>
    </div>

    <!-- ================================
         Summary Stats Cards
         ================================ -->
    <div class="reports-stats-grid">
        <div class="report-stat-card">
            <div class="report-stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="report-stat-info">
                <div class="report-stat-value"><?php echo number_format($totalResidents); ?></div>
                <div class="report-stat-label">Total Residents</div>
            </div>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon green">
                <i class="fas fa-home"></i>
            </div>
            <div class="report-stat-info">
                <div class="report-stat-value"><?php echo number_format($totalHouseholds); ?></div>
                <div class="report-stat-label">Total Households</div>
            </div>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon orange">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="report-stat-info">
                <div class="report-stat-value"><?php echo number_format($totalBlotter); ?></div>
                <div class="report-stat-label">Blotter Records</div>
            </div>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon purple">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="report-stat-info">
                <div class="report-stat-value"><?php echo number_format($totalCertReqs); ?></div>
                <div class="report-stat-label">Certificate Requests</div>
            </div>
        </div>

        <div class="report-stat-card">
            <div class="report-stat-icon red">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="report-stat-info">
                <div class="report-stat-value"><?php echo number_format($pendingRequests); ?></div>
                <div class="report-stat-label">Purok</div>
            </div>
        </div>
    </div>

    <!-- ================================
         Tabbed Report Sections
         ================================ -->
    <div class="report-tabs-wrapper">

        <!-- Tab Navigation -->
        <div class="report-tabs-nav no-print">
            <button class="report-tab-btn active" data-tab="overview">
                <i class="fas fa-th-large"></i> Overview
            </button>
            <button class="report-tab-btn" data-tab="population">
                <i class="fas fa-users"></i> Population
            </button>
            <button class="report-tab-btn" data-tab="blotter">
                <i class="fas fa-file-alt"></i> Blotter Records
            </button>
            <button class="report-tab-btn" data-tab="certificates">
                <i class="fas fa-certificate"></i> Certificate Requests
            </button>
            <button class="report-tab-btn" data-tab="households">
                <i class="fas fa-home"></i> Households
            </button>
        </div>

        <!-- ============================
             TAB 0: Overview
             ============================ -->
        <div class="report-tab-content active" id="tab-overview">
            <!-- Population Growth Chart -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-chart-line"></i> Population Growth
                </h3>
                <div style="display:flex; gap:16px; align-items:flex-start;">

                    <!-- Left: Chart -->
                    <div class="report-chart-box" style="flex:1; min-width:0;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                            <div class="report-chart-box-title" style="margin-bottom:0;">Total Population Trend</div>
                            <select id="populationYearSelect" class="year-select" style="font-size:13px;padding:5px 10px;border:1px solid var(--border-color);border-radius:4px;background:var(--bg-secondary);color:var(--text-primary);">
                                <?php 
                                $currentYear = date('Y');
                                for($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                    echo "<option value=\"$y\">$y</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="report-chart-canvas-wrap tall">
                            <canvas id="populationChart"></canvas>
                        </div>
                    </div>

                    <!-- Right: Monthly Data Breakdown Table -->
                    <div class="report-table-box" style="width:260px; flex-shrink:0; align-self:stretch; display:flex; flex-direction:column;">
                        <div class="report-table-box-title" style="font-size:12px; margin-bottom:6px; color:var(--text-secondary);">Monthly Data Breakdown</div>
                        <div style="flex:1; overflow-y:auto; max-height:300px;">
                            <table class="report-table" style="font-size:11px;">
                                <thead>
                                    <tr>
                                        <th style="padding:5px 8px;">Month</th>
                                        <th class="text-right" style="padding:5px 8px;">Population</th>
                                        <th class="text-right" style="padding:5px 8px;">Growth</th>
                                    </tr>
                                </thead>
                                <tbody id="populationTrendTableBody">
                                    <!-- Data will be injected by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Bottom Charts Grid -->
            <div class="report-two-col">
                <!-- Blotter Records Chart -->
                <div class="report-chart-box">
                    <div class="report-chart-box-title">Blotter Records (Monthly Status)</div>
                    <div class="report-chart-canvas-wrap">
                        <canvas id="blotterChart"
                            data-labels="<?php echo jsonAttr($popGrowthLabels); ?>"
                            data-pending="<?php echo jsonAttr($blotterStackedData['Pending']); ?>"
                            data-investigation="<?php echo jsonAttr($blotterStackedData['Under Investigation']); ?>"
                            data-dismissed="<?php echo jsonAttr($blotterStackedData['Dismissed']); ?>"
                            data-resolved="<?php echo jsonAttr($blotterStackedData['Resolved']); ?>">
                        </canvas>
                    </div>
                </div>
                
                <!-- Age Demographics Chart -->
                <div class="report-chart-box">
                    <div class="report-chart-box-title">Age Demographics</div>
                    <div class="report-chart-canvas-wrap">
                        <canvas id="demographicsChart"
                            data-labels="<?php echo jsonAttr(array_keys($ageGroupData)); ?>"
                            data-values="<?php echo jsonAttr(array_values($ageGroupData)); ?>">
                        </canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================
             TAB 1: Population
             ============================ -->
        <div class="report-tab-content" id="tab-population">

            <!-- Special Groups -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-star"></i> Special Groups
                </h3>
                <div class="special-groups-grid">
                    <div class="special-group-card">
                        <div class="special-group-icon" style="background: linear-gradient(135deg,#3b82f6,#1d4ed8);">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <div class="special-group-value"><?php echo number_format($specialGroups['fourps']); ?></div>
                        <div class="special-group-label">4Ps Members</div>
                    </div>
                    <div class="special-group-card">
                        <div class="special-group-icon" style="background: linear-gradient(135deg,#10b981,#059669);">
                            <i class="fas fa-vote-yea"></i>
                        </div>
                        <div class="special-group-value"><?php echo number_format($specialGroups['voters']); ?></div>
                        <div class="special-group-label">Registered Voters</div>
                    </div>
                    <div class="special-group-card">
                        <div class="special-group-icon" style="background: linear-gradient(135deg,#8b5cf6,#7c3aed);">
                            <i class="fas fa-wheelchair"></i>
                        </div>
                        <div class="special-group-value"><?php echo number_format($specialGroups['pwd']); ?></div>
                        <div class="special-group-label">PWD</div>
                    </div>
                    <div class="special-group-card">
                        <div class="special-group-icon" style="background: linear-gradient(135deg,#f59e0b,#d97706);">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="special-group-value"><?php echo number_format($specialGroups['seniors']); ?></div>
                        <div class="special-group-label">Senior Citizens</div>
                    </div>
                    <div class="special-group-card">
                        <div class="special-group-icon" style="background: linear-gradient(135deg,#ef4444,#dc2626);">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <div class="special-group-value"><?php echo number_format($specialGroups['indigent']); ?></div>
                        <div class="special-group-label">Indigent</div>
                    </div>
                </div>
            </div>

            <!-- Gender + Age Group -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-venus-mars"></i> Gender & Age Distribution
                </h3>
                <div class="report-two-col">
                    <!-- Gender Chart -->
                    <div>
                        <div class="report-chart-box">
                            <div class="report-chart-box-title">Gender Distribution</div>
                            <div class="report-chart-canvas-wrap">
                                <canvas id="genderChart"
                                    data-labels="<?php echo jsonAttr(array_keys($genderData)); ?>"
                                    data-values="<?php echo jsonAttr(array_values($genderData)); ?>">
                                </canvas>
                            </div>
                        </div>
                        <!-- Gender Table -->
                        <div class="report-table-box" style="margin-top:16px;">
                            <div class="report-table-box-title">Gender Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Gender</th>
                                        <th class="text-right">Count</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($genderData)): ?>
                                        <tr><td colspan="3" class="report-empty"><i class="fas fa-inbox"></i><p>No data</p></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($genderData as $label => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td class="text-right"><?php echo pct($count, $totalResidents); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:600; border-top: 2px solid var(--border-color);">
                                            <td>Total</td>
                                            <td class="text-right"><?php echo number_format($totalResidents); ?></td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Age Group Chart -->
                    <div>
                        <div class="report-chart-box">
                            <div class="report-chart-box-title">Age Group Distribution</div>
                            <div class="report-chart-canvas-wrap">
                                <canvas id="ageGroupChart"
                                    data-labels="<?php echo jsonAttr(array_keys($ageGroupData)); ?>"
                                    data-values="<?php echo jsonAttr(array_values($ageGroupData)); ?>">
                                </canvas>
                            </div>
                        </div>
                        <!-- Age Group Table -->
                        <div class="report-table-box" style="margin-top:16px;">
                            <div class="report-table-box-title">Age Group Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Age Group</th>
                                        <th class="text-right">Count</th>
                                        <th>Distribution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $ageColors = ['green','blue','orange','red'];
                                    $ai = 0;
                                    foreach ($ageGroupData as $label => $count):
                                        $color = $ageColors[$ai % count($ageColors)];
                                        $ai++;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                        <td>
                                            <div class="report-bar-wrap">
                                                <div class="report-bar">
                                                    <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                </div>
                                                <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Civil Status + Employment -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-briefcase"></i> Civil Status & Employment
                </h3>
                <div class="report-two-col">
                    <!-- Civil Status -->
                    <div class="report-table-box">
                        <div class="report-table-box-title">Civil Status</div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-right">Count</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($civilStatusData)): ?>
                                    <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                                <?php else: ?>
                                    <?php $ci = 0; foreach ($civilStatusData as $label => $count): $color = barColor($ci++, $barColors); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                        <td>
                                            <div class="report-bar-wrap">
                                                <div class="report-bar">
                                                    <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                </div>
                                                <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Employment Status -->
                    <div class="report-table-box">
                        <div class="report-table-box-title">Employment Status</div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th class="text-right">Count</th>
                                    <th>Distribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employmentData)): ?>
                                    <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                                <?php else: ?>
                                    <?php $ei = 0; foreach ($employmentData as $label => $count): $color = barColor($ei++, $barColors); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                        <td>
                                            <div class="report-bar-wrap">
                                                <div class="report-bar">
                                                    <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                                </div>
                                                <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Educational Attainment -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-graduation-cap"></i> Educational Attainment
                </h3>
                <div class="report-table-box">
                    <div class="report-table-box-title">Education Level Breakdown</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Education Level</th>
                                <th class="text-right">Count</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($educationData)): ?>
                                <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No data available</p></div></td></tr>
                            <?php else: ?>
                                <?php $edi = 0; foreach ($educationData as $label => $count): $color = barColor($edi++, $barColors); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($label); ?></td>
                                    <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                    <td>
                                        <div class="report-bar-wrap">
                                            <div class="report-bar">
                                                <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalResidents); ?>%"></div>
                                            </div>
                                            <span class="report-bar-pct"><?php echo pct($count, $totalResidents); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

           
        </div><!-- end tab-population -->

        <!-- ============================
             TAB 2: Blotter Records
             ============================ -->
        <div class="report-tab-content" id="tab-blotter">

            <!-- Blotter Status + Monthly Trend -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-chart-pie"></i> Case Status Overview
                </h3>
                <div class="report-two-col">
                    <!-- Status Doughnut -->
                    <div>
                        <div class="report-chart-box">
                            <div class="report-chart-box-title">Status Distribution</div>
                            <div class="report-chart-canvas-wrap">
                                <canvas id="blotterStatusChart"
                                    data-labels="<?php echo jsonAttr(array_keys($blotterStatusData)); ?>"
                                    data-values="<?php echo jsonAttr(array_values($blotterStatusData)); ?>">
                                </canvas>
                            </div>
                        </div>
                        <div class="report-table-box" style="margin-top:16px;">
                            <div class="report-table-box-title">Status Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-right">Count</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($blotterStatusData)): ?>
                                        <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No blotter records</p></div></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($blotterStatusData as $label => $count): ?>
                                        <tr>
                                            <td>
                                                <span class="report-badge <?php echo strtolower(str_replace(' ','-',$label)); ?>">
                                                    <?php echo htmlspecialchars($label); ?>
                                                </span>
                                            </td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td class="text-right"><?php echo pct($count, $totalBlotter); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <tr style="font-weight:600; border-top:2px solid var(--border-color);">
                                            <td>Total</td>
                                            <td class="text-right"><?php echo number_format($totalBlotter); ?></td>
                                            <td class="text-right">100%</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Monthly Trend -->
                    <div>
                        <div class="report-chart-box">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                                <div class="report-chart-box-title" style="margin-bottom:0;">Monthly Trend</div>
                                <select id="blotterYearSelect" class="year-select" style="font-size:13px;padding:5px 10px;">
                                    <?php foreach ($availableYears as $yr): ?>
                                        <option value="<?php echo $yr; ?>" <?php echo ($yr == $currentYear) ? 'selected' : ''; ?>>
                                            <?php echo $yr; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="report-chart-canvas-wrap tall">
                                <canvas id="blotterMonthlyChart"
                                    data-values="<?php echo jsonAttr($blotterMonthly); ?>">
                                </canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incident Type Breakdown -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-exclamation-triangle"></i> Incident Type Breakdown
                </h3>
                <div class="report-two-col">
                    <div class="report-chart-box">
                        <div class="report-chart-box-title">Top Incident Types</div>
                        <div class="report-chart-canvas-wrap tall">
                            <canvas id="blotterTypeChart"
                                data-labels="<?php echo jsonAttr(array_keys($blotterTypeData)); ?>"
                                data-values="<?php echo jsonAttr(array_values($blotterTypeData)); ?>">
                            </canvas>
                        </div>
                    </div>
                    <div class="report-table-box">
                        <div class="report-table-box-title">Incident Type Summary</div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Incident Type</th>
                                    <th class="text-right">Cases</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($blotterTypeData)): ?>
                                    <tr><td colspan="4"><div class="report-empty"><i class="fas fa-inbox"></i><p>No incident data</p></div></td></tr>
                                <?php else: ?>
                                    <?php $rank = 1; foreach ($blotterTypeData as $label => $count): $color = barColor($rank-1, $barColors); ?>
                                    <tr>
                                        <td style="color:var(--text-secondary);font-weight:600;"><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                        <td>
                                            <div class="report-bar-wrap">
                                                <div class="report-bar">
                                                    <div class="report-bar-fill <?php echo $color; ?>" style="width:<?php echo pct($count, $totalBlotter); ?>%"></div>
                                                </div>
                                                <span class="report-bar-pct"><?php echo pct($count, $totalBlotter); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- end tab-blotter -->

        <!-- ============================
             TAB 3: Certificate Requests
             ============================ -->
        <div class="report-tab-content" id="tab-certificates">

            <!-- By Type + By Status -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-chart-bar"></i> Requests by Certificate Type
                </h3>
                <div class="report-two-col">
                    <div class="report-chart-box">
                        <div class="report-chart-box-title">Certificate Type Distribution</div>
                        <div class="report-chart-canvas-wrap tall">
                            <canvas id="certTypeChart"
                                data-labels="<?php echo jsonAttr(array_keys($certTypeData)); ?>"
                                data-values="<?php echo jsonAttr(array_values($certTypeData)); ?>">
                            </canvas>
                        </div>
                    </div>
                    <div class="report-table-box">
                        <div class="report-table-box-title">Certificate Type Summary</div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Certificate</th>
                                    <th class="text-right">Requests</th>
                                 
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($certTypeData)): ?>
                                    <tr><td colspan="3"><div class="report-empty"><i class="fas fa-inbox"></i><p>No certificate requests</p></div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($certTypeData as $label => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($label); ?></td>
                                        <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                       
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr style="font-weight:600; border-top:2px solid var(--border-color);">
                                        <td>Total</td>
                                        <td class="text-right"><?php echo number_format($totalCertReqs); ?></td>
                                    
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


        </div><!-- end tab-certificates -->

        <!-- ============================
             TAB 4: Households
             ============================ -->
        <div class="report-tab-content" id="tab-households">

            <!-- Household Summary Card -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-home"></i> Household Overview
                </h3>
                <div class="reports-stats-grid" style="margin-bottom:0;">
                    <div class="report-stat-card">
                        <div class="report-stat-icon teal">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="report-stat-info">
                            <div class="report-stat-value"><?php echo number_format($totalHouseholds); ?></div>
                            <div class="report-stat-label">Total Households</div>
                        </div>
                    </div>
                    <div class="report-stat-card">
                        <div class="report-stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="report-stat-info">
                            <div class="report-stat-value"><?php echo number_format($totalResidents); ?></div>
                            <div class="report-stat-label">Total Residents</div>
                        </div>
                    </div>
                    <div class="report-stat-card">
                        <div class="report-stat-icon green">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="report-stat-info">
                            <div class="report-stat-value">
                                <?php echo $totalHouseholds > 0 ? number_format($totalResidents / $totalHouseholds, 1) : '0'; ?>
                            </div>
                            <div class="report-stat-label">Avg. Residents/Household</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Water Source + Toilet Facility -->
            <div class="report-section">
                <h3 class="report-section-title">
                    <i class="fas fa-tint"></i> Facilities & Utilities
                </h3>
                <div class="report-two-col">
                    <!-- Water Source -->
                    <div>
                        <?php if (!empty($waterSourceData)): ?>
                        <div class="report-chart-box">
                            <div class="report-chart-box-title">Water Source Types</div>
                            <div class="report-chart-canvas-wrap">
                                <canvas id="waterSourceChart"
                                    data-labels="<?php echo jsonAttr(array_keys($waterSourceData)); ?>"
                                    data-values="<?php echo jsonAttr(array_values($waterSourceData)); ?>">
                                </canvas>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="report-table-box" style="margin-top:<?php echo !empty($waterSourceData) ? '16px' : '0'; ?>;">
                            <div class="report-table-box-title">Water Source Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Water Source</th>
                                        <th class="text-right">Households</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($waterSourceData)): ?>
                                        <tr><td colspan="3"><div class="report-empty"><i class="fas fa-tint-slash"></i><p>No water source data recorded</p></div></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($waterSourceData as $label => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td class="text-right"><?php echo pct($count, $totalHouseholds); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Toilet Facility -->
                    <div>
                        <?php if (!empty($toiletData)): ?>
                        <div class="report-chart-box">
                            <div class="report-chart-box-title">Toilet Facility Types</div>
                            <div class="report-chart-canvas-wrap">
                                <canvas id="toiletChart"
                                    data-labels="<?php echo jsonAttr(array_keys($toiletData)); ?>"
                                    data-values="<?php echo jsonAttr(array_values($toiletData)); ?>">
                                </canvas>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="report-table-box" style="margin-top:<?php echo !empty($toiletData) ? '16px' : '0'; ?>;">
                            <div class="report-table-box-title">Toilet Facility Breakdown</div>
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>Facility Type</th>
                                        <th class="text-right">Households</th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($toiletData)): ?>
                                        <tr><td colspan="3"><div class="report-empty"><i class="fas fa-toilet"></i><p>No toilet facility data recorded</p></div></td></tr>
                                    <?php else: ?>
                                        <?php foreach ($toiletData as $label => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($label); ?></td>
                                            <td class="text-right"><strong><?php echo number_format($count); ?></strong></td>
                                            <td class="text-right"><?php echo pct($count, $totalHouseholds); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end tab-households -->

        <!-- Official Print Footer -->
        <div class="print-only print-footer">
            <div class="signatories">
                <div class="signatory-item">
                    <p>Prepared by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Authorized Staff'); ?></p>
                    <p class="sig-title">Barangay Secretary / Staff</p>
                </div>
                <div class="signatory-item">
                    <p>Attested by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name"><?php echo htmlspecialchars($captainName); ?></p>
                    <p class="sig-title">Barangay Captain</p>
                </div>
            </div>
            <div class="print-metadata">
                <p>Generated on: <?php echo date('F d, Y h:i A'); ?> | <span class="page-number"></span></p>
            </div>
        </div>

    </div><!-- end report-tabs-wrapper -->

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const cardLinks = {
            'Total Residents': 'residents.php',
            'Total Households': 'households.php',
            'Blotter Records': 'blotter.php',
            'Certificate Requests': 'requests.php',
            '4Ps Members': 'residents.php?filter4ps=Yes',
            'Registered Voters': 'residents.php',
            'PWD': 'residents.php?filterPwdStatus=Yes',
            'Senior Citizens': 'residents.php?filterAgeGroup=60%2B',
            'Indigent': 'residents.php'
        };

        document.querySelectorAll('.report-stat-card, .special-group-card').forEach(card => {
            card.addEventListener('dblclick', () => {
            
                const label = card.querySelector('.report-stat-label, .special-group-label')?.textContent.trim();
                if (label && cardLinks[label]) {
                    window.location.href = cardLinks[label];
                }
            });
            // Add visual cue
            card.style.cursor = 'pointer';
            card.setAttribute('title', 'Double-click to view details');

        });

        // Print Button Handler
        document.getElementById('printReportBtn')?.addEventListener('click', () => window.print());
    });
    </script>
</div>
