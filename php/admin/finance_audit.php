<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'finance_audit';
$page_title  = 'Finance Audit Trail';
include '../includes/_sidebar.php';

// Fetch Logs
$logs = [];
$q = mysqli_query($conn, "
    SELECT f.*, u.name as user_name, u.email as user_email
    FROM finance_audit_trail f
    LEFT JOIN users u ON f.actor_user_id = u.id
    ORDER BY f.created_at DESC
    LIMIT 500
");
if ($q) while ($row = mysqli_fetch_assoc($q)) $logs[] = $row;
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
:root{--primary:#06b6d4;--primary-light:rgba(6,182,212,.15);--success:#10b981;--danger:#ef4444;--warning:#f59e0b;--purple:#8b5cf6;}
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#06b6d4,#0e7490);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.1;transform:rotate(-15deg);z-index:1;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:2.5rem;}
.card-header{padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);}
.card-header h3{font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.1rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:.95rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
.activity-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:8px;}
.dot-payment{background:var(--success);box-shadow:0 0 0 3px rgba(16,185,129,.2);}
.dot-waiver{background:var(--warning);box-shadow:0 0 0 3px rgba(245,158,11,.2);}
.dot-budget{background:var(--primary);box-shadow:0 0 0 3px rgba(6,182,212,.2);}
.dot-invoice{background:var(--purple);box-shadow:0 0 0 3px rgba(139,92,246,.2);}
.dot-default{background:var(--text-muted);}
.btn{display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none;justify-content:center;}
.btn-outline{background:transparent;border:1.5px solid var(--border);color:var(--text-secondary);}.btn-outline:hover{background:var(--surface-2);border-color:var(--primary);color:var(--primary);}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-history"></i> Finance Audit Trail</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-shield-alt hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-file-signature"></i></div>
            <div class="staff-hero-info">
                <h2>Finance Audit Trail</h2>
                <p>Read-only immutable timeline of all financial transactions, waivers, invoices and approvals.</p>
            </div>
            <div style="margin-left:auto;z-index:2;">
                <button class="btn btn-outline" onclick="window.print()" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.3);">
                    <i class="fas fa-print"></i> Print Log
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list-ul" style="color:var(--primary);"></i> System Activity (Last 500 Records)</h3>
                <div style="display:flex;gap:.5rem;font-size:1rem;color:var(--text-muted);">
                    <span style="display:flex;align-items:center;gap:.4rem;"><span class="activity-dot dot-payment"></span>Payment</span>
                    <span style="display:flex;align-items:center;gap:.4rem;"><span class="activity-dot dot-waiver"></span>Waiver</span>
                    <span style="display:flex;align-items:center;gap:.4rem;"><span class="activity-dot dot-budget"></span>Budget</span>
                    <span style="display:flex;align-items:center;gap:.4rem;"><span class="activity-dot dot-invoice"></span>Invoice</span>
                </div>
            </div>
            <?php if (empty($logs)): ?>
                <div style="padding:5rem;text-align:center;color:var(--text-muted);">
                    <i class="fas fa-file-signature" style="font-size:4rem;margin-bottom:1rem;opacity:.3;"></i>
                    <h3 style="color:var(--text-primary);margin-bottom:.5rem;">No Records Found</h3>
                    <p>No financial audit records have been generated yet.</p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="stf-table" id="auditTable">
                    <thead>
                        <tr>
                            <th>Date / Time</th>
                            <th>Module / Action</th>
                            <th>User (Operator)</th>
                            <th>Description</th>
                            <th>Change Data</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): 
                            $mod = strtolower($l['module'] ?? '');
                            $dotClass = 'dot-default';
                            if (str_contains($mod,'payment')) $dotClass = 'dot-payment';
                            elseif (str_contains($mod,'waiver')) $dotClass = 'dot-waiver';
                            elseif (str_contains($mod,'budget')) $dotClass = 'dot-budget';
                            elseif (str_contains($mod,'invoice')) $dotClass = 'dot-invoice';
                        ?>
                        <tr>
                            <td style="white-space:nowrap;">
                                <strong style="color:var(--text-primary);"><?= date('M d, Y', strtotime($l['created_at'])) ?></strong>
                                <div style="font-size:.9rem;color:var(--text-muted);margin-top:.2rem;"><i class="far fa-clock"></i> <?= date('g:i A', strtotime($l['created_at'])) ?></div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;margin-bottom:.3rem;">
                                    <span class="activity-dot <?= $dotClass ?>"></span>
                                    <span style="font-weight:700;font-size:.95rem;text-transform:uppercase;letter-spacing:.05em;color:var(--primary);"><?= htmlspecialchars($l['module']) ?></span>
                                </div>
                                <strong style="font-size:1rem;color:var(--text-primary);"><?= htmlspecialchars($l['action']) ?></strong>
                            </td>
                            <td>
                                <div style="font-weight:700;"><?= htmlspecialchars($l['user_name'] ?? 'System Process') ?></div>
                                <div style="font-size:.85rem;color:var(--text-muted);"><?= htmlspecialchars($l['user_email'] ?? '') ?></div>
                            </td>
                            <td style="max-width:220px;color:var(--text-secondary);"><?= htmlspecialchars($l['description']) ?></td>
                            <td style="max-width:220px;">
                                <?php 
                                $nv = json_decode($l['new_values'] ?? '{}', true);
                                if ($nv && is_array($nv)):
                                ?>
                                <div style="background:var(--surface-2);padding:.6rem .8rem;border-radius:6px;border:1px solid var(--border);font-size:.85rem;font-family:monospace;">
                                    <?php foreach (array_slice($nv, 0, 3) as $k => $v): ?>
                                        <div style="margin-bottom:2px;"><strong style="color:var(--primary);"><?= $k ?></strong>: <?= htmlspecialchars(is_array($v) ? json_encode($v) : $v) ?></div>
                                    <?php endforeach; ?>
                                    <?php if (count($nv) > 3): ?>
                                        <div style="color:var(--text-muted);font-size:.8rem;">+<?= count($nv) - 3 ?> more fields...</div>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <span style="color:var(--text-muted);font-size:.9rem;">No change data</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-family:monospace;font-size:.9rem;color:var(--text-muted);"><?= htmlspecialchars($l['ip_address']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
$(document).ready(function(){
    if($('#auditTable').length){
        $('#auditTable').DataTable({responsive:true,pageLength:25,order:[[0,'desc']],language:{search:'',searchPlaceholder:'Search audit records...'}});
        $('.dataTables_filter input').css({width:'250px',display:'inline-block','margin-left':'10px'});
    }
});
const themeIcon=document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click',()=>{const html=document.documentElement;const t=html.getAttribute('data-theme')==='dark'?'light':'dark';html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);if(themeIcon)themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
