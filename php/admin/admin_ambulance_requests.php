<?php
session_start();
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'public_ambulance';
$page_title  = 'Web Ambulance Requests';
$message = '';

// Handle Actions (Dispatch / Complete / Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $a_id = (int)$_POST['req_id'];
    if ($_POST['action'] === 'dispatch') {
        mysqli_query($conn, "UPDATE ambulance_requests SET status='Dispatched', dispatch_time=NOW(), updated_at=NOW() WHERE id=$a_id");
        $message = "Ambulance Dispatched.";
    } elseif ($_POST['action'] === 'complete') {
        mysqli_query($conn, "UPDATE ambulance_requests SET status='Completed', completion_time=NOW(), updated_at=NOW() WHERE id=$a_id");
        $message = "Request marked as Completed.";
    } elseif ($_POST['action'] === 'cancel') {
        mysqli_query($conn, "UPDATE ambulance_requests SET status='Cancelled', updated_at=NOW() WHERE id=$a_id");
        $message = "Request Cancelled.";
    } elseif ($_POST['action'] === 'delete') {
        mysqli_query($conn, "DELETE FROM ambulance_requests WHERE id=$a_id");
        $message = "Record permanently deleted.";
    }
}

// Fetch Requests
$requests = [];
$q_req = mysqli_query($conn, "SELECT * FROM ambulance_requests ORDER BY FIELD(status, 'Pending', 'Dispatched', 'In Transit', 'Completed', 'Cancelled'), request_time DESC");
while($r = mysqli_fetch_assoc($q_req)) $requests[] = $r;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Web Ambulance Requests - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .card-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
        .st-Pending   { background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:4px 10px; border-radius:12px; font-weight:800; font-size:0.85rem; text-transform:uppercase; animation: pulse 2s infinite; }
        .st-Dispatched{ background:rgba(245, 158, 11, 0.1); color:#f59e0b; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
        .st-Completed { background:rgba(16, 185, 129, 0.1); color:#10b981; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
        .st-Cancelled { background:rgba(107, 114, 128, 0.1); color:#6b7280; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow: 0 0 0 6px rgba(239,68,68,0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); } }
    </style>
</head>
<body>
<?php include '../includes/_sidebar.php'; ?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-truck-medical" style="color:var(--danger);margin-right:10px;"></i> Public Ambulance Dispatch</span>
        </div>
    </div>
    
    <div class="adm-content">
        <?php if($message): ?>
            <div style="background:#10b98122; color:#10b981; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card-wrap">
            <p style="color:var(--text-muted); margin-bottom:1.5rem;">These are LIVE emergency transport requests submitted securely via the public website's Ambulance portal.</p>
            <table class="adm-table" style="width:100%;">
                <tr>
                    <th>Ref ID</th>
                    <th>Patient Details</th>
                    <th>Pickup / Destination</th>
                    <th>Emergency Type</th>
                    <th>Requested Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if(empty($requests)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No ambulance requests found.</td></tr>
                <?php else: foreach($requests as $r): ?>
                <tr>
                    <td><b><?= htmlspecialchars($r['request_id']) ?></b></td>
                    <td>
                        <b><?= htmlspecialchars($r['patient_name']) ?></b><br>
                        <i class="fas fa-phone" style="font-size:.8rem; color:var(--danger);"></i> <small><?= htmlspecialchars($r['patient_phone']) ?></small>
                    </td>
                    <td style="max-width:200px;">
                        <span style="color:var(--primary); font-size:0.85rem;"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($r['pickup_location']) ?></span><br>
                        <span style="color:var(--success); font-size:0.85rem;"><i class="fas fa-hospital"></i> <?= htmlspecialchars($r['destination']) ?></span>
                    </td>
                    <td><span style="font-size:0.85rem; font-weight:600; color:var(--danger);"><?= htmlspecialchars($r['emergency_type']) ?></span></td>
                    <td>
                        <b style="color:var(--text);"><?= date('M d, Y', strtotime($r['request_time'])) ?></b><br>
                        <small style="color:var(--danger); font-weight:700;"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($r['request_time'])) ?></small>
                    </td>
                    <td><span class="st-<?= str_replace(' ','',htmlspecialchars($r['status'])) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                    <td>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <?php if($r['status'] === 'Pending'): ?>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="dispatch"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button title="Mark Dispatched" class="btn btn-primary btn-sm" style="padding:6px; font-size:12px; background:var(--warning); border-color:var(--warning);"><span class="btn-text"><i class="fas fa-truck-fast"></i> Dispatch</span></button></form>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="cancel"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button title="Cancel Request" class="btn btn-outline btn-sm" style="padding:6px; font-size:12px; border-color:var(--danger); color:var(--danger);"><span class="btn-text"><i class="fas fa-times"></i></span></button></form>
                            <?php elseif($r['status'] === 'Dispatched' || $r['status'] === 'In Transit'): ?>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="complete"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button title="Mark Completed" class="btn btn-success btn-sm" style="padding:6px; font-size:12px;"><span class="btn-text"><i class="fas fa-check"></i> Complete</span></button></form>
                            <?php else: ?>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this record permanently?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="req_id" value="<?= $r['id'] ?>"><button title="Delete Record" class="btn btn-ghost btn-sm" style="padding:6px; font-size:12px; color:var(--text-muted);"><span class="btn-text"><i class="fas fa-trash"></i></span></button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </table>
        </div>

    </div>
</main>
</body>
</html>
