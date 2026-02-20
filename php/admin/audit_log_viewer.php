<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$auditLogger = new AuditLogger($conn);

// Get filter parameters
$filterAction = $_GET['action'] ?? '';
$filterUser = $_GET['user'] ?? '';
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Build filter array
$filters = [];
if ($filterAction) $filters['action'] = $filterAction;
if ($filterUser) $filters['user_id'] = $filterUser;
if ($filterDateFrom) $filters['date_from'] = $filterDateFrom;
if ($filterDateTo) $filters['date_to'] = $filterDateTo;

// Get audit logs
$logs = $auditLogger->getAuditLogs($filters, $perPage, $offset);

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM audit_log WHERE 1=1";
$params = [];
$types = '';

if ($filterAction) {
    $countQuery .= " AND action = ?";
    $params[] = $filterAction;
    $types .= 's';
}
if ($filterUser) {
    $countQuery .= " AND user_id = ?";
    $params[] = $filterUser;
    $types .= 'i';
}
if ($filterDateFrom) {
    $countQuery .= " AND created_at >= ?";
    $params[] = $filterDateFrom . ' 00:00:00';
    $types .= 's';
}
if ($filterDateTo) {
    $countQuery .= " AND created_at <= ?";
    $params[] = $filterDateTo . ' 23:59:59';
    $types .= 's';
}

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalCount = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $perPage);

// Get statistics
$stats = $auditLogger->getAuditStatistics();

// Get unique actions for filter
$actionsQuery = "SELECT DISTINCT action FROM audit_log ORDER BY action";
$actionsResult = mysqli_query($conn, $actionsQuery);

// Get users for filter
$usersQuery = "SELECT id, user_name, name FROM users ORDER BY user_name";
$usersResult = mysqli_query($conn, $usersQuery);

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $auditLogger->exportToCSV($filters);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log Viewer - RMU Medical Sickbay</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #F4F8FF;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            padding: 25px;
            border-radius: 24px;
            margin-bottom: 20px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: white;
            font-size: 28px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 24px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 24px;
            margin-bottom: 20px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
        }
        
        .filters h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.3);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .table-container {
            background: white;
            border-radius: 24px;
            padding: 20px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.08);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-login {
            background: #3498db;
            color: white;
        }
        
        .badge-create {
            background: #27ae60;
            color: white;
        }
        
        .badge-update {
            background: #f39c12;
            color: white;
        }
        
        .badge-delete {
            background: #e74c3c;
            color: white;
        }
        
        .badge-config {
            background: #9b59b6;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .pagination a.active {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: white;
            border-color: #2F80ED;
        }
        
        .pagination a:hover {
            border-color: #2F80ED;
        }
        
        .details-cell {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-list"></i> Audit Log Viewer</h1>
            <a href="?export=csv<?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>" class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Events</h3>
                <div class="value"><?php echo number_format($stats['total_events']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Events</h3>
                <div class="value"><?php echo number_format($stats['today_events']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Failed Logins (24h)</h3>
                <div class="value"><?php echo number_format($stats['failed_logins_24h']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Users (24h)</h3>
                <div class="value"><?php echo number_format($stats['unique_users_24h']); ?></div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filters</h3>
            <form method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Action</label>
                        <select name="action">
                            <option value="">All Actions</option>
                            <?php while ($action = mysqli_fetch_assoc($actionsResult)): ?>
                                <option value="<?php echo htmlspecialchars($action['action']); ?>" <?php echo $filterAction === $action['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['action']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>User</label>
                        <select name="user">
                            <option value="">All Users</option>
                            <?php while ($user = mysqli_fetch_assoc($usersResult)): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $filterUser == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['user_name']) . ' (' . htmlspecialchars($user['name']) . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filterDateFrom); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filterDateTo); ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="audit_log_viewer.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Audit Logs Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #7f8c8d;">
                                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                                <p>No audit logs found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong>
                                    <br>
                                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($log['name'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $actionClass = 'login';
                                    if (strpos($log['action'], 'create') !== false) $actionClass = 'create';
                                    elseif (strpos($log['action'], 'update') !== false) $actionClass = 'update';
                                    elseif (strpos($log['action'], 'delete') !== false) $actionClass = 'delete';
                                    elseif (strpos($log['action'], 'config') !== false) $actionClass = 'config';
                                    ?>
                                    <span class="badge badge-<?php echo $actionClass; ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($log['record_id'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td class="details-cell" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>" 
                           class="<?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $filterAction ? '&action=' . urlencode($filterAction) : ''; ?><?php echo $filterUser ? '&user=' . urlencode($filterUser) : ''; ?><?php echo $filterDateFrom ? '&date_from=' . urlencode($filterDateFrom) : ''; ?><?php echo $filterDateTo ? '&date_to=' . urlencode($filterDateTo) : ''; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 10px; color: #7f8c8d; font-size: 14px;">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalCount); ?> of <?php echo number_format($totalCount); ?> entries
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="../home.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
