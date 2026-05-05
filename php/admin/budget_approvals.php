<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'budget_approvals';
$page_title  = 'Budget Approvals';
include '../includes/_sidebar.php';

// Pending Budgets
$pending = [];
$q_pending = mysqli_query($conn, "
    SELECT b.*, u.name as creator_name
    FROM budget_allocations b
    LEFT JOIN users u ON b.created_by = u.id
    WHERE b.status = 'Draft'
    ORDER BY b.created_at DESC
");
if ($q_pending) while ($row = mysqli_fetch_assoc($q_pending)) $pending[] = $row;

// Recent Actions
$recent = [];
$q_recent = mysqli_query($conn, "
    SELECT b.*, u.name as creator_name, a.name as admin_name
    FROM budget_allocations b
    LEFT JOIN users u ON b.created_by = u.id
    LEFT JOIN users a ON b.approved_by = a.id
    WHERE b.status IN ('Active', 'Rejected', 'Revised')
    ORDER BY b.approved_at DESC LIMIT 50
");
if ($q_recent) while ($row = mysqli_fetch_assoc($q_recent)) $recent[] = $row;

$totalActiveAmt = array_sum(array_column(array_filter($recent, fn($r) => $r['status'] === 'Active'), 'allocated_amount'));
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
<style>
:root{--primary:#f59e0b;--primary-light:rgba(245,158,11,.15);--success:#10b981;--danger:#ef4444;--info:#3b82f6;}
.staff-hero{display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;background:linear-gradient(135deg,#f59e0b,#92400e);border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap;position:relative;overflow:hidden;}
.staff-hero-avatar{width:72px;height:72px;border-radius:50%;border:3px solid rgba(255,255,255,.35);background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0;z-index:2;}
.staff-hero-info{z-index:2;}.staff-hero-info h2{font-size:2rem;font-weight:700;margin:0;}.staff-hero-info p{font-size:1.3rem;margin:.3rem 0 0;opacity:.85;}
.hero-bg-icon{position:absolute;right:-20px;bottom:-40px;font-size:15rem;opacity:.1;transform:rotate(-15deg);z-index:1;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2.5rem;}
.stat-mini{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);padding:1.8rem 1.5rem;text-align:center;transition:var(--transition);box-shadow:var(--shadow-sm);}
.stat-mini:hover{box-shadow:var(--shadow-md);transform:translateY(-3px);border-color:var(--primary);}
.stat-mini-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;font-size:1.8rem;}
.stat-mini-val{font-size:2.5rem;font-weight:800;line-height:1;color:var(--primary);}
.stat-mini-val.success{color:var(--success);}.stat-mini-val.info{color:var(--info);}
.stat-mini-lbl{font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-top:.6rem;text-transform:uppercase;letter-spacing:.05em;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden;margin-bottom:2.5rem;}
.card-header{padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);}
.card-header h3{font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0;}
.stf-table{width:100%;border-collapse:collapse;font-size:1.15rem;}
.stf-table th{background:var(--surface-2);color:var(--text-secondary);font-weight:600;text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left;}
.stf-table td{padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle;}
.stf-table tr:hover td{background:var(--surface-2);}
.btn{display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none;justify-content:center;}
.btn-success{background:var(--success);color:#fff;}.btn-success:hover{opacity:.88;}
.btn-danger{background:var(--danger);color:#fff;}.btn-danger:hover{opacity:.88;}
.btn-ghost{background:transparent;color:var(--text-secondary);}.btn-ghost:hover{background:var(--surface-2);}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(5px);z-index:9999;display:none;align-items:center;justify-content:center;opacity:0;transition:opacity .3s ease;}
.modal-bg.active{display:flex;opacity:1;}
.modal-box{background:var(--surface);width:90%;max-width:500px;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);transform:translateY(20px);transition:transform .3s ease;overflow:hidden;border:1px solid var(--border);}
.modal-bg.active .modal-box{transform:translateY(0);}
.modal-header{padding:1.5rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:var(--surface-2);}
.modal-header h3{margin:0;font-size:1.4rem;color:var(--text-primary);}
.modal-close{background:none;border:none;font-size:1.5rem;color:var(--text-muted);cursor:pointer;}
.modal-body{padding:2rem;}.modal-footer{padding:1.5rem 2rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:1rem;background:var(--surface-2);}
.form-control{width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light);}
#toastWrap{position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem;}
.toast-msg{padding:1.2rem 2rem;border-radius:var(--radius-sm);background:var(--surface);box-shadow:var(--shadow-lg);border-left:5px solid var(--primary);font-size:1.2rem;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:1rem;animation:fadePop .3s ease;}
.toast-msg.toast-danger{border-left-color:var(--danger);}.toast-msg.toast-success{border-left-color:var(--success);}
@keyframes fadePop{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-file-invoice-dollar"></i> Budget Approvals</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-chart-pie hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="staff-hero-info">
                <h2>Budget Authorizations</h2>
                <p>Review and authorize departmental financial budget allocations for the fiscal period.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--warning);background:rgba(245,158,11,.15);"><i class="fas fa-file-alt"></i></div>
                <div class="stat-mini-val"><?= count($pending) ?></div>
                <div class="stat-mini-lbl">Drafts Pending</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--success);background:rgba(16,185,129,.15);"><i class="fas fa-check-circle"></i></div>
                <div class="stat-mini-val success"><?= count(array_filter($recent, fn($r) => $r['status'] === 'Active')) ?></div>
                <div class="stat-mini-lbl">Active Budgets</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-icon" style="color:var(--info);background:rgba(59,130,246,.15);"><i class="fas fa-coins"></i></div>
                <div class="stat-mini-val info" style="font-size:1.8rem;">GHS <?= number_format($totalActiveAmt, 0) ?></div>
                <div class="stat-mini-lbl">Authorized Budget</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-inbox" style="color:var(--warning);"></i> Draft Budgets Pending Authorization</h3>
            </div>
            <?php if (empty($pending)): ?>
                <div style="text-align:center;padding:5rem;color:var(--text-muted);">
                    <i class="fas fa-check-circle" style="font-size:4rem;color:var(--success);margin-bottom:1rem;display:block;"></i>
                    <h3 style="color:var(--text-primary);margin-bottom:.5rem;">All Clear!</h3>
                    <p style="font-size:1.1rem;">No budgets are pending authorization.</p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="stf-table">
                    <thead><tr><th>Department</th><th>Fiscal Year / Period</th><th>Amount (GHS)</th><th>Notes</th><th>Proposed By</th><th style="text-align:right;">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($pending as $p): ?>
                        <tr id="row_<?= $p['allocation_id'] ?>">
                            <td><strong style="font-size:1.1rem;"><?= htmlspecialchars($p['department']) ?></strong></td>
                            <td>
                                <span style="background:rgba(99,102,241,.15);color:#6366f1;padding:.3rem .8rem;border-radius:12px;font-weight:700;font-size:.95rem;border:1px solid rgba(99,102,241,.3);"><?= htmlspecialchars($p['fiscal_year'] . ' · ' . $p['fiscal_period']) ?></span>
                            </td>
                            <td><strong style="font-size:1.4rem;color:var(--primary);">GHS <?= number_format($p['allocated_amount'], 2) ?></strong></td>
                            <td style="max-width:200px;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($p['notes'] ?? '') ?>"><?= htmlspecialchars(substr($p['notes'] ?? '', 0, 60)) ?></td>
                            <td><?= htmlspecialchars($p['creator_name']) ?></td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:.5rem;">
                                    <button class="btn btn-success" style="padding:.6rem 1rem;" onclick="approveBudget(<?= $p['allocation_id'] ?>)"><i class="fas fa-check"></i> Approve</button>
                                    <button class="btn btn-danger" style="padding:.6rem 1rem;" onclick="openRejectModal(<?= $p['allocation_id'] ?>)"><i class="fas fa-times"></i> Reject</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header"><h3><i class="fas fa-history" style="color:var(--text-muted);"></i> Recent Processed Budgets</h3></div>
            <div style="overflow-x:auto;">
                <table class="stf-table" id="recentTable">
                    <thead><tr><th>Department</th><th>Amount (GHS)</th><th>Status</th><th>Actioned By</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <strong style="font-size:1.1rem;"><?= htmlspecialchars($r['department']) ?></strong>
                                <div style="font-size:.9rem;color:var(--text-muted);margin-top:.2rem;"><?= htmlspecialchars($r['fiscal_year'] . ' ' . $r['fiscal_period']) ?></div>
                            </td>
                            <td style="font-size:1.2rem;font-weight:700;"><?= number_format($r['allocated_amount'], 2) ?></td>
                            <td>
                                <?php if ($r['status'] === 'Active'): ?>
                                    <span style="background:rgba(16,185,129,.15);color:var(--success);padding:.3rem .8rem;border-radius:12px;font-weight:700;font-size:.95rem;border:1px solid rgba(16,185,129,.3);">Active</span>
                                <?php else: ?>
                                    <span style="background:rgba(239,68,68,.15);color:var(--danger);padding:.3rem .8rem;border-radius:12px;font-weight:700;font-size:.95rem;border:1px solid rgba(239,68,68,.3);"><?= htmlspecialchars($r['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['admin_name'] ?? 'System') ?></td>
                            <td style="color:var(--text-muted);"><?= date('d M Y', strtotime($r['approved_at'] ?? $r['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal-bg" id="rejectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="color:var(--danger);"><i class="fas fa-times-circle"></i> Reject Budget</h3>
            <button class="modal-close" onclick="closeRejectModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p style="font-size:1.1rem;color:var(--text-secondary);margin-bottom:1rem;">Please provide a reason for rejection or revision prompt.</p>
            <textarea id="rejectReason" class="form-control" rows="3" placeholder="Reason for rejection..."></textarea>
            <input type="hidden" id="rejectBudgetId">
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
            <button class="btn btn-danger" onclick="submitRejectBudget()"><i class="fas fa-ban"></i> Confirm Rejection</button>
        </div>
    </div>
</div>
<div id="toastWrap"></div>

<script>
function showToast(msg,type='success'){const t=document.createElement('div');t.className=`toast-msg toast-${type}`;t.innerHTML=`<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i><span>${msg}</span>`;document.getElementById('toastWrap').appendChild(t);setTimeout(()=>{t.style.opacity='0';setTimeout(()=>t.remove(),300);},3000);}
$(document).ready(function(){if($('#recentTable').length){$('#recentTable').DataTable({responsive:true,pageLength:10,language:{search:'',searchPlaceholder:'Search...'}}); $('.dataTables_filter input').css({width:'200px',display:'inline-block','margin-left':'10px'});}});
const themeIcon=document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click',()=>{const html=document.documentElement;const t=html.getAttribute('data-theme')==='dark'?'light':'dark';html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);if(themeIcon)themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';});
async function approveBudget(id){
    if(!confirm('Approve this budget and make it active?')) return;
    const fd=new FormData(); fd.append('action','approve_budget'); fd.append('budget_id',id);
    try{const res=await fetch('budget_actions.php',{method:'POST',body:fd});const data=await res.json();
    if(data.success){showToast(data.message,'success');setTimeout(()=>location.reload(),1000);}else showToast(data.message||'Error','danger');}catch(e){showToast('Network error','danger');}
}
function openRejectModal(id){document.getElementById('rejectBudgetId').value=id;document.getElementById('rejectReason').value='';document.getElementById('rejectModal').classList.add('active');}
function closeRejectModal(){document.getElementById('rejectModal').classList.remove('active');}
async function submitRejectBudget(){
    const id=document.getElementById('rejectBudgetId').value;const reason=document.getElementById('rejectReason').value.trim();
    if(!reason){showToast('Please provide a reason','danger');return;}
    const fd=new FormData(); fd.append('action','reject_budget'); fd.append('budget_id',id); fd.append('reason',reason);
    try{const res=await fetch('budget_actions.php',{method:'POST',body:fd});const data=await res.json();
    if(data.success){showToast(data.message,'success');closeRejectModal();setTimeout(()=>location.reload(),1000);}else showToast(data.message||'Error','danger');}catch(e){showToast('Network error','danger');}
}
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
