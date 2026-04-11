<?php
session_start();
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'public_bookings';
$page_title  = 'Web Appointment Bookings';
$message = '';

// Handle Actions (Confirm / Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $b_id = (int)$_POST['booking_id'];
    if ($_POST['action'] === 'confirm') {
        mysqli_query($conn, "UPDATE public_appointment_bookings SET status='confirmed', updated_at=NOW() WHERE booking_id=$b_id");
        $message = "Booking #BK-".str_pad($b_id,5,'0',STR_PAD_LEFT)." Confirmed.";
    } elseif ($_POST['action'] === 'cancel') {
        mysqli_query($conn, "UPDATE public_appointment_bookings SET status='cancelled', updated_at=NOW() WHERE booking_id=$b_id");
        $message = "Booking #BK-".str_pad($b_id,5,'0',STR_PAD_LEFT)." Cancelled.";
    } elseif ($_POST['action'] === 'delete') {
        mysqli_query($conn, "DELETE FROM public_appointment_bookings WHERE booking_id=$b_id");
        $message = "Booking record permanently deleted.";
    }
}

// Fetch Bookings
$bookings = [];
$q_bk = mysqli_query($conn, "
    SELECT b.*, u.name as patient_name, u.phone as patient_phone, d_u.name as doc_name, s.name as service_name
    FROM public_appointment_bookings b
    LEFT JOIN users u ON b.patient_user_id = u.id
    LEFT JOIN doctors d ON b.doctor_id = d.id
    LEFT JOIN users d_u ON d.user_id = d_u.id
    LEFT JOIN landing_services s ON b.service_id = s.service_id
    ORDER BY b.created_at DESC
");
while($r = mysqli_fetch_assoc($q_bk)) $bookings[] = $r;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Web Bookings - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .card-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
        .st-pending   { background:rgba(245, 158, 11, 0.1); color:#f59e0b; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
        .st-confirmed { background:rgba(16, 185, 129, 0.1); color:#10b981; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
        .st-cancelled { background:rgba(239, 68, 68, 0.1); color:#ef4444; padding:4px 10px; border-radius:12px; font-weight:700; font-size:0.85rem; text-transform:uppercase; }
    </style>
</head>
<body>
<?php include '../includes/_sidebar.php'; ?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-calendar-check" style="color:var(--primary);margin-right:10px;"></i> Public Appointment Bookings</span>
        </div>
    </div>
    
    <div class="adm-content">
        <?php if($message): ?>
            <div style="background:#10b98122; color:#10b981; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card-wrap">
            <p style="color:var(--text-muted); margin-bottom:1.5rem;">These are appointment requests submitted securely via the public landing page booking portal.</p>
            <table class="adm-table" style="width:100%; white-space:nowrap;">
                <tr>
                    <th>Ref ID</th>
                    <th>Patient</th>
                    <th>Requested Doctor</th>
                    <th>Service</th>
                    <th>Requested Date & Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if(empty($bookings)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">No booking requests found.</td></tr>
                <?php else: foreach($bookings as $b): ?>
                <tr>
                    <td><b>BK-<?= str_pad($b['booking_id'],5,'0',STR_PAD_LEFT) ?></b><br><small style="color:var(--text-muted);"><?= date('M d, y', strtotime($b['created_at'])) ?></small></td>
                    <td>
                        <b><?= htmlspecialchars($b['patient_name']) ?></b><br>
                        <i class="fas fa-phone" style="font-size:.8rem; color:var(--primary);"></i> <small><?= htmlspecialchars($b['patient_phone']) ?></small>
                    </td>
                    <td>Dr. <?= htmlspecialchars($b['doc_name']) ?></td>
                    <td><?= htmlspecialchars($b['service_name']) ?></td>
                    <td>
                        <b style="color:var(--text);"><?= date('D, M d Y', strtotime($b['preferred_date'])) ?></b><br>
                        <small style="color:var(--text-muted);"><i class="fas fa-clock"></i> <?= date('h:i A', strtotime($b['preferred_time'])) ?></small>
                    </td>
                    <td><span class="st-<?= htmlspecialchars($b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
                    <td>
                        <div style="display:flex; gap:0.5rem;">
                            <?php if($b['status'] === 'pending'): ?>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="confirm"><input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>"><button title="Confirm" class="btn btn-success btn-sm" style="padding:6px; font-size:12px;"><span class="btn-text"><i class="fas fa-check"></i></span></button></form>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>"><button title="Cancel Request" class="btn btn-outline btn-sm" style="padding:6px; font-size:12px; border-color:var(--danger); color:var(--danger);"><span class="btn-text"><i class="fas fa-times"></i></span></button></form>
                            <?php else: ?>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this record permanently?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="booking_id" value="<?= $b['booking_id'] ?>"><button title="Delete Record" class="btn btn-ghost btn-sm" style="padding:6px; font-size:12px; color:var(--text-muted);"><span class="btn-text"><i class="fas fa-trash"></i></span></button></form>
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
