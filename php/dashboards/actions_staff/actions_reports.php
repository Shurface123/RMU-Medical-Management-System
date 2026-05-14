<?php
/**
 * actions_reports.php — Staff Dashboard Reporting & Data Export Logic
 */
if (!defined('AJAX_REQUEST')) exit;

switch ($action) {

    case 'export_report':
    case 'get_report':
        $report_key = sanitize($_REQUEST['report_key'] ?? '');
        $from       = sanitize($_REQUEST['from']       ?? date('Y-m-01'));
        $to         = sanitize($_REQUEST['to']         ?? date('Y-m-d'));
        $fmt        = strtolower(sanitize($_REQUEST['format'] ?? 'csv'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
        if (!$staff_id) { 
            if ($action === 'get_report') json_err('Staff profile not found.', 403);
            header('Content-Type: application/json'); json_err('Staff profile not found.', 403); 
        }

        $report_map = [
            'tasks_completed' => [
                'title'  => 'Tasks Completed',
                'sql'    => 'SELECT task_title AS "Task", priority AS "Priority", due_date AS "Due Date", completed_at AS "Completed At", completion_notes AS "Notes" FROM staff_tasks WHERE assigned_to=? AND status="completed" AND completed_at BETWEEN ? AND ? ORDER BY completed_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to . ' 23:59:59'],
            ],
            'tasks_all' => [
                'title'  => 'All Tasks',
                'sql'    => 'SELECT task_title AS "Task", priority AS "Priority", status AS "Status", due_date AS "Due Date", created_at AS "Created" FROM staff_tasks WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'shifts' => [
                'title'  => 'Shift Schedule',
                'sql'    => 'SELECT shift_date AS "Date", shift_type AS "Shift", start_time AS "Start", end_time AS "End", location_ward_assigned AS "Ward", status AS "Status" FROM staff_shifts WHERE staff_id=? AND shift_date BETWEEN ? AND ? ORDER BY shift_date DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'leave_requests' => [
                'title'  => 'Leave Requests',
                'sql'    => 'SELECT leave_type AS "Type", start_date AS "From", end_date AS "To", total_days AS "Days", status AS "Status", reason AS "Reason" FROM staff_leaves WHERE staff_id=? AND start_date BETWEEN ? AND ? ORDER BY start_date DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'repairs_completed' => [
                'title'  => 'Repairs Completed',
                'sql'    => 'SELECT request_id AS "Request #", equipment_or_area AS "Issue Area", location AS "Location", priority AS "Priority", status AS "Status", reported_at AS "Reported", completed_at AS "Completed" FROM maintenance_requests WHERE assigned_to=? AND status="completed" AND DATE(completed_at) BETWEEN ? AND ? ORDER BY completed_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'pending_jobs' => [
                'title'  => 'Outstanding Jobs',
                'sql'    => 'SELECT request_id AS "Request #", equipment_or_area AS "Issue Area", location AS "Location", issue_category AS "Category", priority AS "Priority", status AS "Status", reported_at AS "Reported At" FROM maintenance_requests WHERE assigned_to=? AND status NOT IN ("completed","cancelled") AND DATE(reported_at) BETWEEN ? AND ? ORDER BY FIELD(priority,"urgent","high","medium","low") ASC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'task_report' => [
                'title'  => 'My Task Report',
                'sql'    => 'SELECT task_title AS "Task", priority AS "Priority", status AS "Status", due_date AS "Due Date", completed_at AS "Completed At", completion_notes AS "Notes" FROM staff_tasks WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'attendance_log' => [
                'title'  => 'Attendance / Shift Log',
                'sql'    => 'SELECT shift_date AS "Date", shift_type AS "Shift", start_time AS "Start", end_time AS "End", location_ward_assigned AS "Location", status AS "Status" FROM staff_shifts WHERE staff_id=? AND shift_date BETWEEN ? AND ? ORDER BY shift_date DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'leave_history' => [
                'title'  => 'Leave Request History',
                'sql'    => 'SELECT leave_type AS "Type", start_date AS "From", end_date AS "To", reason AS "Reason", status AS "Status", created_at AS "Submitted" FROM staff_leaves WHERE staff_id=? AND start_date BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'cleaning_logs' => [
                'title'  => 'Cleaning Logs',
                'sql'    => 'SELECT c.cleaning_type AS "Type", c.sanitation_status AS "Status", c.started_at AS "Started", c.completed_at AS "Completed", c.notes AS "Notes" FROM cleaning_logs c WHERE c.staff_id=? AND DATE(c.started_at) BETWEEN ? AND ? ORDER BY c.started_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'laundry_batches' => [
                'title'  => 'Laundry Batches',
                'sql'    => 'SELECT batch_code AS "Batch", batch_type AS "Type", item_count AS "Items", delivery_status AS "Status", collected_at AS "Collected", delivered_at AS "Delivered" FROM laundry_batches WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'incidents' => [
                'title'  => 'Security Incidents',
                'sql'    => 'SELECT incident_type AS "Type", location AS "Location", severity AS "Severity", status AS "Status", reported_at AS "Reported" FROM security_incidents WHERE staff_id=? AND DATE(reported_at) BETWEEN ? AND ? ORDER BY reported_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'cleaning_history' => [
                'title'  => 'Sanitation Logs',
                'sql'    => 'SELECT ward_room_area AS "Location", cleaning_type AS "Type", sanitation_status AS "Status", started_at AS "Started", completed_at AS "Completed" FROM cleaning_logs WHERE staff_id=? AND DATE(started_at) BETWEEN ? AND ? ORDER BY started_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'contamination_report' => [
                'title'  => 'Hazard Incident Reports',
                'sql'    => 'SELECT location AS "Location", contamination_type AS "Type", severity AS "Severity", status AS "Status", reported_at AS "Date Reported" FROM contamination_reports WHERE reported_by=? AND DATE(reported_at) BETWEEN ? AND ? ORDER BY reported_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'meal_deliveries' => [
                'title'  => 'Meal Deliveries',
                'sql'    => 'SELECT meal_type AS "Meal", ward_department AS "Ward", quantity AS "Qty", delivery_status AS "Status", scheduled_time AS "Scheduled" FROM kitchen_tasks WHERE assigned_to=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'trip_summary' => [
                'title'  => 'Dispatch History',
                'sql'    => 'SELECT pickup_location AS "Pickup", destination AS "Destination", request_type AS "Type", trip_status AS "Status", distance_km AS "Distance (km)", completed_at AS "Completed" FROM ambulance_trips WHERE driver_id=? AND trip_status="completed" AND DATE(completed_at) BETWEEN ? AND ? ORDER BY completed_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'fuel_log' => [
                'title'  => 'Fuel Consumption Logs',
                'sql'    => 'SELECT fuel_litres AS "Litres", cost AS "Cost", odometer_reading AS "Odometer", notes AS "Notes", logged_at AS "Logged" FROM vehicle_fuel_logs WHERE logged_by_staff_id=? AND DATE(logged_at) BETWEEN ? AND ? ORDER BY logged_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
            'vehicle_issues' => [
                'title'  => 'Vehicle Maintenance Issues',
                'sql'    => 'SELECT issue_description AS "Issue", maintenance_type AS "Type", status AS "Status", cost AS "Cost", performed_at AS "Performed", created_at AS "Reported" FROM vehicle_maintenance_logs WHERE reported_by=? AND DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC',
                'types'  => 'iss',
                'params' => [$staff_id, $from, $to],
            ],
        ];

        if (!isset($report_map[$report_key])) {
            if ($action === 'get_report') json_err('Unknown report key: ' . $report_key);
            header('Content-Type: application/json');
            json_err('Unknown report key: ' . $report_key, 400);
        }

        $rpt    = $report_map[$report_key];
        $rows   = dbSelect($conn, $rpt['sql'], $rpt['types'], $rpt['params']);
        $title  = $rpt['title'];

        if ($fmt === 'csv') {
            $fname  = 'RMU_' . str_replace(' ', '_', $title) . '_' . $from . '_to_' . $to;
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $fname . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');
            $out = fopen('php://output', 'w');
            fputs($out, "ï»¿");
            fputcsv($out, ['RMU Medical Management System']);
            fputcsv($out, ['Report: ' . $title]);
            fputcsv($out, ['Period: ' . $from . ' to ' . $to]);
            fputcsv($out, ['Generated: ' . date('d M Y H:i')]);
            fputcsv($out, []);
            if (!empty($rows)) {
                fputcsv($out, array_keys($rows[0]));
                foreach ($rows as $row) fputcsv($out, array_values($row));
            } else {
                fputcsv($out, ['No data found for the selected period.']);
            }
            fclose($out);
            exit;
        }

        if ($fmt === 'html') {
            if (empty($rows)) json_ok('No data', ['html' => '']);
            $html = '<table class="display rpt-table" style="width:100%"><thead><tr>';
            foreach (array_keys($rows[0]) as $h) $html .= "<th>" . e($h) . "</th>";
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($row as $v) $html .= "<td>" . e($v) . "</td>";
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            json_ok('Report generated', ['html' => $html]);
        }

        if ($action === 'get_report') json_err('Unsupported format: ' . $fmt);
        header('Content-Type: application/json');
        json_err('Unsupported format: ' . $fmt . '. Use csv.', 400);
}
