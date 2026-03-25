<?php
/**
 * Analytics API Endpoint
 * Handles requests for clinical, operational, and system-wide metrics.
 */

function handleAnalytics($method, $userId, $userRole, $metricType) {
    global $conn;

    if ($method !== 'GET') {
        ApiResponse::error('Method not allowed', 405);
    }

    // Role Guard
    if ($userRole !== 'admin') {
        ApiResponse::error('Unauthorized', 403);
    }

    $startDate = $_GET['startDate'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['endDate'] ?? date('Y-m-d');

    // Basic date sanitization
    $startDate = mysqli_real_escape_string($conn, $startDate);
    $endDate = mysqli_real_escape_string($conn, $endDate);

    try {
        switch ($metricType) {
            case 'executive':
                $data = getExecutiveSummary();
                break;
            case 'patient':
                $data = getPatientAnalytics($startDate, $endDate);
                break;
            case 'clinical':
                $data = getClinicalAnalytics($startDate, $endDate);
                break;
            case 'staff':
                $data = getStaffPerformance($startDate, $endDate);
                break;
            case 'pharmacy':
                $data = getPharmacyAnalytics($startDate, $endDate);
                break;
            case 'financial':
                $data = getFinancialAnalytics($startDate, $endDate);
                break;
            case 'system':
                $data = getSystemUsage($startDate, $endDate);
                break;
            default:
                ApiResponse::error('Invalid metric type', 400);
        }

        ApiResponse::success($data);
    } catch (Exception $e) {
        ApiResponse::error('Analytics engine error: ' . $e->getMessage(), 500);
    }
}

/**
 * Live KPI Executive Summary
 */
function getExecutiveSummary() {
    global $conn;
    
    // Total Patients Today
    $patientsRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE user_role = 'patient' AND DATE(created_at) = CURDATE()");
    $patients = mysqli_fetch_assoc($patientsRes)['count'];

    // Active Admissions
    $admissionsRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM bed_assignments WHERE status = 'Active'");
    $admissions = mysqli_fetch_assoc($admissionsRes)['count'];

    // Staff On Duty (Present and not checked out)
    $staffRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM staff_attendance WHERE status = 'present' AND check_out_time IS NULL AND DATE(check_in_time) = CURDATE()");
    $staff = mysqli_fetch_assoc($staffRes)['count'];

    // Pending Emergencies
    $emergenciesRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM emergency_alerts WHERE status = 'Active'");
    $emergencies = mysqli_fetch_assoc($emergenciesRes)['count'];

    // Meds Administered Today
    $medsRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM medication_administration WHERE status = 'Administered' AND DATE(administered_at) = CURDATE()");
    $meds = mysqli_fetch_assoc($medsRes)['count'];

    // Lab Tests Completed Today
    $labsRes = mysqli_query($conn, "SELECT COUNT(*) as count FROM lab_results WHERE result_status = 'Validated' AND DATE(validated_at) = CURDATE()");
    $labs = mysqli_fetch_assoc($labsRes)['count'];

    return [
        'patients_today' => $patients,
        'active_admissions' => $admissions,
        'staff_on_duty' => $staff,
        'pending_emergencies' => $emergencies,
        'meds_today' => $meds,
        'labs_today' => $labs,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Patient Trends & Distribution
 */
function getPatientAnalytics($start, $end) {
    global $conn;
    
    // Admission/Discharge Trend
    $trends = [];
    $res = mysqli_query($conn, "
        SELECT DATE(admission_date) as date, COUNT(*) as admissions
        FROM bed_assignments 
        WHERE admission_date BETWEEN '$start' AND '$end'
        GROUP BY DATE(admission_date)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $trends['admissions'][] = $row;

    $res = mysqli_query($conn, "
        SELECT DATE(discharge_date) as date, COUNT(*) as discharges
        FROM bed_assignments 
        WHERE discharge_date BETWEEN '$start' AND '$end' AND status = 'Discharged'
        GROUP BY DATE(discharge_date)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $trends['discharges'][] = $row;

    // Ward Distribution
    $wards = [];
    $res = mysqli_query($conn, "
        SELECT ward, COUNT(*) as count FROM beds WHERE status = 'Occupied' GROUP BY ward
    ");
    while($row = mysqli_fetch_assoc($res)) $wards[] = $row;

    // Average Length of Stay by Department
    $los = [];
    $res = mysqli_query($conn, "
        SELECT d.name as department, AVG(TIMESTAMPDIFF(DAY, b.admission_date, b.discharge_date)) as avg_los
        FROM bed_assignments b
        JOIN patients p ON b.patient_id = p.id
        JOIN departments d ON p.department_id = d.id
        WHERE b.status = 'Discharged' AND b.admission_date BETWEEN '$start' AND '$end'
        GROUP BY d.id
    ");
    while($row = mysqli_fetch_assoc($res)) $los[] = $row;

    // Readmission Rate Trend
    $readmissionTrend = [];
    $res = mysqli_query($conn, "
        SELECT DATE(b2.admission_date) as date, COUNT(*) as count
        FROM bed_assignments b1
        JOIN bed_assignments b2 ON b1.patient_id = b2.patient_id 
        WHERE b1.status = 'Discharged' 
        AND b2.admission_date > b1.discharge_date
        AND TIMESTAMPDIFF(DAY, b1.discharge_date, b2.admission_date) <= 30
        AND b1.discharge_date BETWEEN '$start' AND '$end'
        GROUP BY DATE(b2.admission_date)
    ");
    while($row = mysqli_fetch_assoc($res)) $readmissionTrend[] = $row;

    // Age/Gender Distribution
    $demographics = ['gender' => [], 'age' => []];
    $res = mysqli_query($conn, "SELECT gender, COUNT(*) as count FROM patients GROUP BY gender");
    while($row = mysqli_fetch_assoc($res)) $demographics['gender'][] = $row;

    // Age groups
    $res = mysqli_query($conn, "
        SELECT 
            CASE 
                WHEN age < 18 THEN '0-17'
                WHEN age BETWEEN 18 AND 30 THEN '18-30'
                WHEN age BETWEEN 31 AND 50 THEN '31-50'
                ELSE '51+'
            END as age_group,
            COUNT(*) as count
        FROM patients
        GROUP BY age_group
    ");
    while($row = mysqli_fetch_assoc($res)) $demographics['age'][] = $row;

    return ['trends' => $trends, 'ward_dist' => $wards, 'los' => $los, 'readmission_trend' => $readmissionTrend, 'demographics' => $demographics];
}

/**
 * Clinical Operational Metrics
 */
function getClinicalAnalytics($start, $end) {
    global $conn;
    
    // Vitals Flags
    $vitals = [];
    $res = mysqli_query($conn, "
        SELECT DATE(recorded_at) as date, SUM(is_flagged) as flagged, COUNT(*) as total
        FROM patient_vitals 
        WHERE recorded_at BETWEEN '$start' AND '$end'
        GROUP BY DATE(recorded_at)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $vitals[] = $row;

    // Lab TAT Average (Minutes)
    $tat = [];
    $res = mysqli_query($conn, "
        SELECT test_name, AVG(TIMESTAMPDIFF(MINUTE, created_at, validated_at)) as avg_tat
        FROM lab_results 
        WHERE validated_at BETWEEN '$start' AND '$end'
        GROUP BY test_name
    ");
    while($row = mysqli_fetch_assoc($res)) $tat[] = $row;

    // Medication administration compliance
    $medCompliance = [];
    $res = mysqli_query($conn, "
        SELECT status, COUNT(*) as count
        FROM medication_administration
        WHERE administered_at BETWEEN '$start' AND '$end' OR (status = 'Missed' AND scheduled_time BETWEEN '$start' AND '$end')
        GROUP BY status
    ");
    while($row = mysqli_fetch_assoc($res)) $medCompliance[] = $row;

    // Emergency alert frequency
    $emergencies = [];
    $res = mysqli_query($conn, "
        SELECT alert_type, COUNT(*) as count
        FROM emergency_alerts
        WHERE triggered_at BETWEEN '$start' AND '$end'
        GROUP BY alert_type
    ");
    while($row = mysqli_fetch_assoc($res)) $emergencies[] = $row;

    // Nurse-to-Patient Ratio (per ward)
    $ratio = [];
    $res = mysqli_query($conn, "
        SELECT b.ward, COUNT(DISTINCT b.patient_id) as patient_count,
               (SELECT COUNT(DISTINCT nurse_id) FROM nurse_shifts WHERE ward_assigned = b.ward AND status = 'Active') as nurse_count
        FROM bed_management b
        WHERE b.assignment_status = 'Active'
        GROUP BY b.ward
    ");
    while($row = mysqli_fetch_assoc($res)) $ratio[] = $row;

    return ['vitals_trends' => $vitals, 'lab_tat' => $tat, 'med_compliance' => $medCompliance, 'emergencies' => $emergencies, 'nurse_patient_ratio' => $ratio];
}

/**
 * Staff Performance
 */
function getStaffPerformance($start, $end) {
    global $conn;
    
    // Task Completion Rate
    $tasks = [];
    $res = mysqli_query($conn, "
        SELECT u.name, COUNT(t.task_id) as total, SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed
        FROM users u
        JOIN staff_tasks t ON u.id = t.assigned_to
        WHERE t.created_at BETWEEN '$start' AND '$end'
        GROUP BY u.id
    ");
    while($row = mysqli_fetch_assoc($res)) $tasks[] = $row;

    // Login activity per staff member
    $logins = [];
    $res = mysqli_query($conn, "
        SELECT u.name, u.user_role, COUNT(al.id) as sessions, MAX(al.created_at) as last_login
        FROM users u
        LEFT JOIN audit_log al ON u.id = al.user_id AND al.action = 'login'
        WHERE al.created_at BETWEEN '$start' AND '$end' OR al.id IS NULL
        GROUP BY u.id
    ");
    while($row = mysqli_fetch_assoc($res)) $logins[] = $row;

    // Medication administration rate per nurse
    $nurseMeds = [];
    $res = mysqli_query($conn, "
        SELECT u.name, COUNT(ma.id) as count
        FROM users u
        JOIN medication_administration ma ON u.id = ma.nurse_id
        WHERE ma.administered_at BETWEEN '$start' AND '$end'
        GROUP BY u.id
    ");
    while($row = mysqli_fetch_assoc($res)) $nurseMeds[] = $row;

    // Doctor prescription volume
    $doctorRx = [];
    $res = mysqli_query($conn, "
        SELECT u.name, COUNT(p.id) as count
        FROM users u
        JOIN prescriptions p ON u.id = p.doctor_id
        WHERE p.created_at BETWEEN '$start' AND '$end'
        GROUP BY u.id
    ");
    while($row = mysqli_fetch_assoc($res)) $doctorRx[] = $row;

    // Lab test processing volume
    $labVolume = [];
    $res = mysqli_query($conn, "
        SELECT u.name, COUNT(lr.result_id) as count
        FROM users u
        JOIN lab_results lr ON u.id = lr.technician_id
        WHERE lr.validated_at BETWEEN '$start' AND '$end'
        GROUP BY u.id
    ");
    while($row = mysqli_fetch_assoc($res)) $labVolume[] = $row;

    return ['task_performance' => $tasks, 'login_activity' => $logins, 'nurse_meds' => $nurseMeds, 'doctor_rx' => $doctorRx, 'lab_volume' => $labVolume];
}

/**
 * Pharmacy & Inventory
 */
function getPharmacyAnalytics($start, $end) {
    global $conn;
    
    // Top 10 Medications
    $meds = [];
    $res = mysqli_query($conn, "
        SELECT medication_name, COUNT(*) as count 
        FROM prescriptions 
        WHERE created_at BETWEEN '$start' AND '$end'
        GROUP BY medication_name 
        ORDER BY count DESC LIMIT 10
    ");
    while($row = mysqli_fetch_assoc($res)) $meds[] = $row;

    // Stock Alerts
    $alerts = [];
    $res = mysqli_query($conn, "SELECT medicine_name, stock_quantity, reorder_level FROM medicines WHERE stock_quantity <= reorder_level");
    while($row = mysqli_fetch_assoc($res)) $alerts[] = $row;

    // Prescription fulfillment rate
    $fulfillment = mysqli_query($conn, "
        SELECT status, COUNT(*) as count
        FROM prescriptions
        WHERE created_at BETWEEN '$start' AND '$end'
        GROUP BY status
    ");
    $fulfillmentData = [];
    while($row = mysqli_fetch_assoc($fulfillment)) $fulfillmentData[] = $row;

    // Controlled substance usage
    $controlled = [];
    $res = mysqli_query($conn, "
        SELECT m.medicine_name, COUNT(dr.id) as usage_count
        FROM medicines m
        JOIN dispensing_records dr ON m.id = dr.medicine_id
        WHERE m.is_controlled = 1 AND dr.dispensing_date BETWEEN '$start' AND '$end'
        GROUP BY m.id
    ");
    while($row = mysqli_fetch_assoc($res)) $controlled[] = $row;

    return ['top_meds' => $meds, 'stock_alerts' => $alerts, 'fulfillment' => $fulfillmentData, 'controlled_usage' => $controlled];
}

/**
 * Financial Analytics
 */
function getFinancialAnalytics($start, $end) {
    global $conn;
    
    // Revenue Trend
    $revenue = [];
    $res = mysqli_query($conn, "
        SELECT DATE(payment_date) as date, SUM(amount) as total
        FROM payments 
        WHERE status = 'Paid' AND payment_date BETWEEN '$start' AND '$end'
        GROUP BY DATE(payment_date)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $revenue[] = $row;

    // Outstanding payments
    $outstanding = [];
    $res = mysqli_query($conn, "
        SELECT p.full_name, SUM(pay.amount) as total
        FROM patients p
        JOIN payments pay ON p.id = pay.patient_id
        WHERE pay.status IN ('Pending', 'Overdue')
        GROUP BY p.id
        ORDER BY total DESC
    ");
    while($row = mysqli_fetch_assoc($res)) $outstanding[] = $row;

    // Payment method distribution
    $methods = [];
    $res = mysqli_query($conn, "
        SELECT payment_method, COUNT(*) as count
        FROM payments
        WHERE payment_date BETWEEN '$start' AND '$end'
        GROUP BY payment_method
    ");
    while($row = mysqli_fetch_assoc($res)) $methods[] = $row;

    // Revenue by department
    $deptRev = [];
    $res = mysqli_query($conn, "
        SELECT d.name as department, SUM(pay.amount) as total
        FROM payments pay
        JOIN patients p ON pay.patient_id = p.id
        JOIN departments d ON p.department_id = d.id
        WHERE pay.status = 'Paid' AND pay.payment_date BETWEEN '$start' AND '$end'
        GROUP BY d.id
    ");
    while($row = mysqli_fetch_assoc($res)) $deptRev[] = $row;

    return ['revenue_trend' => $revenue, 'outstanding' => $outstanding, 'methods' => $methods, 'dept_revenue' => $deptRev];
}

/**
 * System Usage
 */
function getSystemUsage($start, $end) {
    global $conn;
    
    // DAU (Daily Active Users)
    $dau = [];
    $res = mysqli_query($conn, "
        SELECT DATE(login_time) as date, COUNT(DISTINCT user_id) as users
        FROM user_sessions 
        WHERE login_time BETWEEN '$start' AND '$end'
        GROUP BY DATE(login_time)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $dau[] = $row;

    // Failed logins line
    $failed = [];
    $res = mysqli_query($conn, "
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM audit_log
        WHERE action = 'login_failed' AND created_at BETWEEN '$start' AND '$end'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    while($row = mysqli_fetch_assoc($res)) $failed[] = $row;

    // Most used modules
    $modules = [];
    $res = mysqli_query($conn, "
        SELECT table_name, COUNT(*) as count
        FROM audit_log
        WHERE created_at BETWEEN '$start' AND '$end' AND table_name IS NOT NULL
        GROUP BY table_name
        ORDER BY count DESC LIMIT 10
    ");
    while($row = mysqli_fetch_assoc($res)) $modules[] = $row;

    return ['dau' => $dau, 'failed_logins' => $failed, 'top_modules' => $modules];
}
