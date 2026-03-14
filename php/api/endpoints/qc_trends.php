<?php
require_once dirname(__DIR__, 2) . '/dashboards/lab_security.php';
initSecureSession();
$user_id = enforceLabTechRole(); // Verify role

require_once dirname(__DIR__, 2) . '/db_conn.php';
require_once dirname(__DIR__) . '/classes/ApiResponse.php';

$eid = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;
if (!$eid) {
    ApiResponse::error("Equipment ID is required", 400);
}

// Fetch last 30 QC results for this equipment, ordered by date ascending so it flows left to right on the chart
$q = $conn->prepare("SELECT qc_date, test_parameter, result_obtained, expected_range_min, expected_range_max, status 
     FROM lab_quality_control 
     WHERE equipment_id = ? 
     ORDER BY qc_date ASC, created_at ASC 
     LIMIT 30");
$q->bind_param("i", $eid);
$q->execute();
$q = $q->get_result();

$data = [
    'dates' => [],
    'results' => [],
    'min_range' => [],
    'max_range' => [],
    'mean' => [],
    'sd_plus_2' => [],
    'sd_minus_2' => [],
    'raw' => []
];

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        // Build raw array for data mapping
        $data['raw'][] = $r;
        
        $data['dates'][] = date('d M', strtotime($r['qc_date']));
        $data['results'][] = (float)$r['result_obtained'];
        
        $min = (float)$r['expected_range_min'];
        $max = (float)$r['expected_range_max'];
        
        $data['min_range'][] = $min;
        $data['max_range'][] = $max;
        
        // Calculate Mean and approx 2SD limits based on the min/max range
        // Standard assumption: Min/Max represent +/- 2SD or 3SD. Let's assume +/- 3SD for the absolute range,
        // and plot Action Limits at +/- 2SD.
        $mean = ($min + $max) / 2;
        $one_sd = ($max - $mean) / 3; // Estimating 1 SD
        
        $data['mean'][] = round($mean, 2);
        $data['sd_plus_2'][] = round($mean + (2 * $one_sd), 2);
        $data['sd_minus_2'][] = round($mean - (2 * $one_sd), 2);
    }
}

ApiResponse::success("QC data retrieved", $data);
