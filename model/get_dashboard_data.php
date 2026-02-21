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
 * Get population growth data - Rolling 12-month view
 */
function getPopulationGrowthData($conn) {
    $data = [
        'months' => [],
        'counts' => []
    ];
    
    // Get data for the last 12 months
    $query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as ym,
            COUNT(*) as new_residents
        FROM residents
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND activity_status = 'Active'
        GROUP BY ym
        ORDER BY ym ASC
    ";
    
    $result = $conn->query($query);
    
    // Get base population (before the 12-month window)
    $baseQuery = "
        SELECT COUNT(*) as base_count
        FROM residents
        WHERE created_at < DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND activity_status = 'Active'
    ";
    
    $baseResult = $conn->query($baseQuery);
    $basePopulation = 0;
    
    if ($baseResult) {
        $baseRow = $baseResult->fetch_assoc();
        $basePopulation = (int)$baseRow['base_count'];
    }
    
    // Build month labels for last 12 months
    $monthLabels = [];
    $monthData = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $label = date('M Y', strtotime("-$i months"));
        $monthLabels[$date] = $label;
        $monthData[$date] = 0;
    }
    
    // Fill in actual data
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $yearMonth = $row['ym'];
            if (isset($monthData[$yearMonth])) {
                $monthData[$yearMonth] = (int)$row['new_residents'];
            }
        }
    }
    
    // Calculate cumulative counts
    $cumulativeCount = $basePopulation;
    foreach ($monthLabels as $yearMonth => $label) {
        $cumulativeCount += $monthData[$yearMonth];
        $data['months'][] = $label;
        $data['counts'][] = $cumulativeCount;
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
        'labels' => ['Children (0-17)', 'Young Adults (18-29)', 'Adults (30-59)', 'Seniors (60+)'],
        'values' => [0, 0, 0, 0],
        'percentages' => [0, 0, 0, 0]
    ];
    
    // Get age distribution
    $query = "
        SELECT 
            CASE 
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 0 AND 17 THEN 'children'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 29 THEN 'young_adults'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 30 AND 59 THEN 'adults'
                WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) >= 60 THEN 'seniors'
                ELSE 'unknown'
            END as age_group,
            COUNT(*) as count
        FROM residents
        WHERE activity_status = 'Active' AND date_of_birth IS NOT NULL
        GROUP BY age_group
    ";
    
    $result = $conn->query($query);
    $total = 0;
    
    if ($result) {
        $counts = [
            'children' => 0,
            'young_adults' => 0,
            'adults' => 0,
            'seniors' => 0
        ];
        
        while ($row = $result->fetch_assoc()) {
            if (isset($counts[$row['age_group']])) {
                $counts[$row['age_group']] = (int)$row['count'];
                $total += (int)$row['count'];
            }
        }
        
        // Set values
        $data['values'][0] = $counts['children'];
        $data['values'][1] = $counts['young_adults'];
        $data['values'][2] = $counts['adults'];
        $data['values'][3] = $counts['seniors'];
        
        // Calculate percentages
        if ($total > 0) {
            for ($i = 0; $i < 4; $i++) {
                $data['percentages'][$i] = round(($data['values'][$i] / $total) * 100, 1);
            }
        }
    }
    
    return $data;
}
?>
