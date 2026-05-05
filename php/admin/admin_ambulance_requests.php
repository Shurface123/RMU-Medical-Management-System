<?php
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
if($q_req) {
    while($r = mysqli_fetch_assoc($q_req)) {
        $requests[] = $r;
    }
}

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #ef4444; /* Danger red for emergency */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --warning: #f59e0b;
  --info: #3b82f6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #991b1b);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 2rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem; }
.filter-tabs .ftab { padding:.8rem 1.8rem;border-radius:20px;font-size:1.15rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge-warning { background:rgba(245, 158, 11, 0.15); color:#f59e0b; padding:0.4rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(245, 158, 11, 0.3);}
.badge-success { background:rgba(16, 185, 129, 0.15); color:#10b981; padding:0.4rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(16, 185, 129, 0.3);}
.badge-danger { background:rgba(239, 68, 68, 0.15); color:#ef4444; padding:0.4rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(239, 68, 68, 0.3);}
.badge-muted { background:rgba(107, 114, 128, 0.15); color:#6b7280; padding:0.4rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; border:1px solid rgba(107, 114, 128, 0.3);}
.badge-pulse { animation: pulse 2s infinite; }
@keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 70% { box-shadow: 0 0 0 8px rgba(239,68,68,0); } 100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); } }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-success { background:var(--success); color:#fff; }
.btn-warning { background:var(--warning); color:#fff; }
.btn-danger { background:var(--danger); color:#fff; }
.btn-outline-danger { background:transparent; border:1.5px solid var(--danger); color:var(--danger); }
.btn-outline-danger:hover { background:var(--primary-light); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

/* ── Modals ── */
.modal-bg { position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(5px);
  z-index:9999;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease; }
.modal-bg.active { display:flex;opacity:1; }
.modal-box { background:var(--surface);width:90%;max-width:500px;border-radius:var(--radius-lg);
  box-shadow:var(--shadow-lg);transform:translateY(20px);transition:transform 0.3s ease;overflow:hidden; border:1px solid var(--border);}
.modal-bg.active .modal-box { transform:translateY(0); }
.modal-header { padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2); }
.modal-header h3 { margin:0;font-size:1.4rem;color:var(--text-primary); }
.modal-close { background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer; }
.modal-body { padding:2rem; }
.modal-footer { padding:1.5rem 2rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:1rem;background:var(--surface-2); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-truck-medical"></i> Public Ambulance Dispatch</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-truck-fast hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-truck-medical" style="animation:pulse 2s infinite;"></i></div>
            <div class="staff-hero-info">
                <h2>Emergency Ambulance Portal</h2>
                <p>Monitor and dispatch LIVE emergency transport requests submitted securely via the public website.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list" style="color:var(--primary);"></i> Transport Requests</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                
                <div class="filter-tabs" style="border-bottom:none; margin-bottom:1rem; padding-bottom:0; padding:1rem;">
                    <button class="ftab active" data-filter="">All Requests</button>
                    <button class="ftab" data-filter="Pending">Pending</button>
                    <button class="ftab" data-filter="Dispatched|In Transit">Dispatched/Transit</button>
                    <button class="ftab" data-filter="Completed">Completed</button>
                    <button class="ftab" data-filter="Cancelled">Cancelled</button>
                </div>

                <table class="stf-table" id="requestsTable">
                    <thead>
                        <tr>
                            <th>Ref ID</th>
                            <th>Patient Details</th>
                            <th>Pickup / Destination</th>
                            <th>Emergency Type</th>
                            <th>Requested Time</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $r): ?>
                        <tr>
                            <td><strong style="color:var(--primary); font-size:1.2rem;"><?= htmlspecialchars($r['request_id'] ?? '') ?></strong></td>
                            <td>
                                <div style="font-weight:700; font-size:1.15rem; color:var(--text-primary);"><?= htmlspecialchars($r['patient_name'] ?? '') ?></div>
                                <div style="font-size:1rem; color:var(--text-secondary); margin-top:0.2rem;"><i class="fas fa-phone" style="font-size:0.9rem; color:var(--danger);"></i> <?= htmlspecialchars($r['patient_phone'] ?? '') ?></div>
                            </td>
                            <td style="max-width:250px;">
                                <div style="color:var(--info); font-size:1rem; margin-bottom:0.3rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($r['pickup_location'] ?? '') ?>">
                                    <i class="fas fa-map-marker-alt" style="width:16px;"></i> <?= htmlspecialchars($r['pickup_location'] ?? '') ?>
                                </div>
                                <div style="color:var(--success); font-size:1rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($r['destination'] ?? '') ?>">
                                    <i class="fas fa-hospital" style="width:16px;"></i> <?= htmlspecialchars($r['destination'] ?? '') ?>
                                </div>
                            </td>
                            <td><strong style="color:var(--danger); font-size:1.1rem;"><?= htmlspecialchars($r['emergency_type'] ?? '') ?></strong></td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);"><?= date('M d, Y', strtotime($r['request_time'])) ?></div>
                                <div style="color:var(--danger); margin-top:0.2rem; font-weight:700;"><i class="far fa-clock"></i> <?= date('h:i A', strtotime($r['request_time'])) ?></div>
                            </td>
                            <td>
                                <?php 
                                    $status = htmlspecialchars($r['status'] ?? '');
                                    $sClass = 'badge-muted';
                                    if ($status === 'Pending') $sClass = 'badge-danger badge-pulse';
                                    elseif ($status === 'Dispatched' || $status === 'In Transit') $sClass = 'badge-warning';
                                    elseif ($status === 'Completed') $sClass = 'badge-success';
                                ?>
                                <span class="<?= $sClass ?>"><?= $status ?></span>
                                <span style="display:none;"><?= $status ?></span> <!-- For DataTables filter -->
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex; gap:0.5rem; justify-content:flex-end;">
                                    <?php if($status === 'Pending'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="dispatch">
                                            <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                            <button title="Mark Dispatched" class="btn btn-warning" style="padding:0.6rem 1rem;"><i class="fas fa-truck-fast"></i></button>
                                        </form>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                            <button title="Cancel Request" class="btn btn-outline-danger" style="padding:0.6rem 1rem;"><i class="fas fa-times"></i></button>
                                        </form>
                                    <?php elseif($status === 'Dispatched' || $status === 'In Transit'): ?>
                                        <form method="POST" style="margin:0;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="req_id" value="<?= $r['id'] ?>">
                                            <button title="Mark Completed" class="btn btn-success" style="padding:0.6rem 1rem;"><i class="fas fa-check-double"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <button title="Delete Record Permanently" class="btn btn-ghost" style="padding:0.6rem 1rem; color:var(--danger);" onclick="openDeleteModal(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- Delete Modal -->
<div class="modal-bg" id="deleteModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
            <button class="modal-close" onclick="closeModal('deleteModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:1.15rem; color:var(--text-secondary); margin-bottom:1rem;">Are you sure you want to permanently delete this ambulance request record? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('deleteModal')">Cancel</button>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="req_id" id="deleteReqId" value="">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete Permanently</button>
            </form>
        </div>
    </div>
</div>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    <?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', () => { showToast(<?= json_encode($message) ?>, 'success'); });
    <?php endif; ?>

    $(document).ready(function() {
        if ($('#requestsTable').length) {
            const reqTable = $('#requestsTable').DataTable({
                responsive: true,
                pageLength: 10,
                language: { search: "", searchPlaceholder: "Search requests..." }
            });
            
            // style datatables
            $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
            
            $('.ftab').on('click', function() {
                $('.ftab').removeClass('active');
                $(this).addClass('active');
                const filterVal = $(this).data('filter');
                reqTable.column(5).search(filterVal ? filterVal : '', true, false).draw();
            });
        }
    });

    function openDeleteModal(id) {
        document.getElementById('deleteReqId').value = id;
        document.getElementById('deleteModal').classList.add('active');
    }
    
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
    }

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
