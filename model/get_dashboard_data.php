<?php
/**
 * Dashboard Data API Endpoint
 * Returns JSON data for dashboard charts
 */

// Include configuration
require_once '../config.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'data' => [],
    'error' => null
];

try {
    // Get the requested data type
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    
    switch ($type) {
        case 'population':
            $response['data'] = getPopulationGrowthData($conn);
            break;
            
        case 'blotter':
            $response['data'] = getBlotterData($conn);
            break;
            
        case 'demographics':
            $response['data'] = getDemographicsData($conn);
            break;
            
        case 'all':
        default:
            $response['data'] = [
                'population' => getPopulationGrowthData($conn),
                'blotter' => getBlotterData($conn),
                'demographics' => getDemographicsData($conn)
            ];
            break;
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Get population growth data
 * - If ?year=YYYY is provided: returns Jan–Dec for that year with short labels ("Jan", "Feb", …)
 * - Otherwise: rolling 12-month view with "M Y" labels
 */
function getPopulationGrowthData($conn) {
    $data = [
        'months' => [],
        'counts' => []
    ];

    $yearFilter = isset($_GET['year']) ? (int)$_GET['year'] : null;

    if ($yearFilter) {
        // ── Specific year: Jan–Dec ──────────────────────────────────────
        $monthLabels = [];
        $monthData   = [];

        for ($month = 1; $month <= 12; $month++) {
            $date  = sprintf('%04d-%02d', $yearFilter, $month);
            $label = date('M', mktime(0, 0, 0, $month, 1)); // "Jan", "Feb", …
            $monthLabels[$date] = $label;
            $monthData[$date]   = 0;
        }

        // Base population: all active residents created BEFORE this year
        $baseQuery = "
            SELECT COUNT(*) as base_count
            FROM residents
            WHERE YEAR(created_at) < $yearFilter
              AND activity_status != 'Archived'
        ";
        $baseResult    = $conn->query($baseQuery);
        $basePopulation = 0;
        if ($baseResult) {
            $baseRow        = $baseResult->fetch_assoc();
            $basePopulation = (int)$baseRow['base_count'];
        }

        // Monthly new residents for the selected year
        $query = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as ym,
                COUNT(*) as new_residents
            FROM residents
            WHERE YEAR(created_at) = $yearFilter
              AND activity_status != 'Archived'
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $result = $conn->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ym = $row['ym'];
                if (isset($monthData[$ym])) {
                    $monthData[$ym] = (int)$row['new_residents'];
                }
            }
        }

        // Build cumulative counts Jan → Dec
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');

        $cumulativeCount = $basePopulation;
        foreach ($monthLabels as $ym => $label) {
            $cumulativeCount   += $monthData[$ym];

            // Truncate future months for the current year
            $monthNum = (int)substr($ym, 5, 2);
            if ($yearFilter == $currentYear && $monthNum > $currentMonth) {
                break;
            }

            $data['months'][]   = $label;
            $data['counts'][]   = $cumulativeCount;
        }

    } else {
        // ── Rolling 12-month view ───────────────────────────────────────
        $query = "
            SELECT
                DATE_FORMAT(created_at, '%Y-%m') as ym,
                COUNT(*) as new_residents
            FROM residents
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND activity_status != 'Archived'
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $result = $conn->query($query);

        // Base population before the 12-month window
        $baseQuery = "
            SELECT COUNT(*) as base_count
            FROM residents
            WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND activity_status != 'Archived'
        ";
        $baseResult    = $conn->query($baseQuery);
        $basePopulation = 0;
        if ($baseResult) {
            $baseRow        = $baseResult->fetch_assoc();
            $basePopulation = (int)$baseRow['base_count'];
        }

        // Build month labels for last 12 months
        $monthLabels = [];
        $monthData   = [];
        for ($i = 11; $i >= 0; $i--) {
            $date  = date('Y-m', strtotime("-$i months"));
            $label = date('M Y', strtotime("-$i months")); // "Jun 2024"
            $monthLabels[$date] = $label;
            $monthData[$date]   = 0;
        }

        // Fill in actual data
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $ym = $row['ym'];
                if (isset($monthData[$ym])) {
                    $monthData[$ym] = (int)$row['new_residents'];
                }
            }
        }

        // Calculate cumulative counts
        $cumulativeCount = $basePopulation;
        foreach ($monthLabels as $ym => $label) {
            $cumulativeCount  += $monthData[$ym];
            $data['months'][]  = $label;
            $data['counts'][]  = $cumulativeCount;
        }
    }

    return $data;
}

/**
 * Get blotter records data - Rolling 12-month view or specific year
 */
function getBlotterData($conn) {
    $data = [
        'months' => [],
        'pending' => [],
        'underInvestigation' => [],
        'dismissed' => [],
        'resolved' => []
    ];
    
    // Get year filter from request (default to 'all' for rolling 12 months)
    $yearFilter = isset($_GET['year']) ? $_GET['year'] : 'all';
    
    $monthLabels = [];
    $monthData = [];
    
    if ($yearFilter === 'all') {
        // Rolling 12-month view
        for ($i = 11; $i >= 0; $i--) {
            $date = date('Y-m', strtotime("-$i months"));
            $label = date('M Y', strtotime("-$i months"));
            $monthLabels[$date] = $label;
            $monthData[$date] = [
                'pending' => 0,
                'underInvestigation' => 0,
                'dismissed' => 0,
                'resolved' => 0
            ];
        }
        
        // Query for last 12 months
        $query = "
            SELECT 
                DATE_FORMAT(incident_date, '%Y-%m') as ym,
                status,
                COUNT(*) as count
            FROM blotter_records
            WHERE incident_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY ym, status
            ORDER BY ym ASC
        ";
    } else {
        // Specific year view (all 12 months of that year)
        $year = (int)$yearFilter;
        
        for ($month = 1; $month <= 12; $month++) {
            $date = sprintf('%04d-%02d', $year, $month);
            $label = date('M Y', strtotime($date . '-01'));
            $monthLabels[$date] = $label;
            $monthData[$date] = [
                'pending' => 0,
                'underInvestigation' => 0,
                'dismissed' => 0,
                'resolved' => 0
            ];
        }
        
        // Query for specific year
        $query = "
            SELECT 
                DATE_FORMAT(incident_date, '%Y-%m') as ym,
                status,
                COUNT(*) as count
            FROM blotter_records
            WHERE YEAR(incident_date) = $year
            GROUP BY ym, status
            ORDER BY ym ASC
        ";
    }
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $yearMonth = $row['ym'];
            $count = (int)$row['count'];
            
            if (isset($monthData[$yearMonth])) {
                switch ($row['status']) {
                    case 'Pending':
                        $monthData[$yearMonth]['pending'] = $count;
                        break;
                    case 'Under Investigation':
                        $monthData[$yearMonth]['underInvestigation'] = $count;
                        break;
                    case 'Dismissed':
                        $monthData[$yearMonth]['dismissed'] = $count;
                        break;
                    case 'Resolved':
                        $monthData[$yearMonth]['resolved'] = $count;
                        break;
                }
            }
        }
    }
    
    // Build final arrays
    foreach ($monthLabels as $yearMonth => $label) {
        $data['months'][] = $label;
        $data['pending'][] = $monthData[$yearMonth]['pending'];
        $data['underInvestigation'][] = $monthData[$yearMonth]['underInvestigation'];
        $data['dismissed'][] = $monthData[$yearMonth]['dismissed'];
        $data['resolved'][] = $monthData[$yearMonth]['resolved'];
    }
    
    return $data;
}

/**
 * Get age demographics data
 */
function getDemographicsData($conn) {
    $data = [
        'labels' => [
            'Newborn (0-28 days)',
            'Infant (29 days - 1 year)',
            'Child (1-9 years)',
            'Adolescent (10-19 years)',
            'Adult (20-59 years)',
            'Senior Citizen (60+ years)'
        ],
        'values' => [0, 0, 0, 0, 0, 0],
        'percentages' => [0, 0, 0, 0, 0, 0]
    ];
    
    // Get age distribution
    $query = "
        SELECT 
            age_health_group as age_group,
            COUNT(*) as count
        FROM residents
        WHERE activity_status != 'Archived' AND age_health_group IS NOT NULL
        GROUP BY age_group
    ";
    
    $result = $conn->query($query);
    $total = 0;
    
    if ($result) {
        $counts = [
            'Newborn (0-28 days)' => 0,
            'Infant (29 days - 1 year)' => 0,
            'Child (1-9 years)' => 0,
            'Adolescent (10-19 years)' => 0,
            'Adult (20-59 years)' => 0,
            'Senior Citizen (60+ years)' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($counts[$row['age_group']])) {
                $counts[$row['age_group']] += (int)$row['count'];
                $total += (int)$row['count'];
            }
        }
        
        // Set values
        $data['values'][0] = $counts['Newborn (0-28 days)'];
        $data['values'][1] = $counts['Infant (29 days - 1 year)'];
        $data['values'][2] = $counts['Child (1-9 years)'];
        $data['values'][3] = $counts['Adolescent (10-19 years)'];
        $data['values'][4] = $counts['Adult (20-59 years)'];
        $data['values'][5] = $counts['Senior Citizen (60+ years)'];
        
        // Calculate percentages
        if ($total > 0) {
            for ($i = 0; $i < 6; $i++) {
                $data['percentages'][$i] = round(($data['values'][$i] / $total) * 100, 1);
            }
        }
    }
    
    return $data;
}
?>
