<?php
/**
 * tab_maintenance.php — Module 6: Maintenance Staff Module (Modernized)
 */
if ($staffRole !== 'maintenance') { echo '<div id="sec-maintenance" class="dash-section"></div>'; return; }

$open_requests   = dbSelect($conn, "SELECT * FROM maintenance_requests WHERE status='reported' AND (assigned_to IS NULL OR assigned_to=0) ORDER BY FIELD(priority,'urgent','high','medium','low'), reported_at ASC LIMIT 100");
$my_requests     = dbSelect($conn, "SELECT * FROM maintenance_requests WHERE assigned_to=? AND status NOT IN ('completed','cancelled') ORDER BY FIELD(status,'in progress','on hold','assigned'), priority DESC LIMIT 100", "i", [$staff_id]);
$completed_today = dbSelect($conn, "SELECT * FROM maintenance_requests WHERE assigned_to=? AND status='completed' AND DATE(completed_at)=? ORDER BY completed_at DESC LIMIT 50", "is", [$staff_id, $today]);
?>
<div id="sec-maintenance" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2.5rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-tools" style="color:var(--role-accent);"></i> Facility Maintenance</h2>
            <p style="font-size:1.3rem;color:var(--text-muted);margin:0.5rem 0 0;">Manage your work orders and field requests</p>
        </div>
        <div style="display:flex;gap:1rem;">
           <button class="btn btn-outline" onclick="location.reload()"><span class="btn-text"><i class="fas fa-sync"></i> Refresh</span></button>
        </div>
    </div>

    <!-- Active Assignments -->
    <div class="card" style="margin-bottom:2.5rem; border-top:4px solid var(--role-accent); background:rgba(255,255,255,0.02); backdrop-filter:blur(10px);">
        <div class="card-header" style="padding:1.8rem 2rem;">
            <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-hard-hat" style="color:var(--role-accent);"></i> My Active Jobs <span class="badge" style="background:var(--role-accent); color:#fff; margin-left:.8rem;"><?= count($my_requests) ?></span></h3>
        </div>
        <div class="card-body" style="padding:0;">
            <?php if(empty($my_requests)): ?>
                <div style="padding:5rem 2rem; text-align:center; color:var(--text-muted);">
                    <i class="fas fa-calendar-check" style="font-size:4rem; opacity:.2; display:block; margin-bottom:1.5rem;"></i>
                    <p style="font-size:1.4rem; font-weight:600;">You have no active assignments.</p>
                    <p style="font-size:1.2rem;">Accept a new task from the queue below.</p>
                </div>
            <?php else: ?>
                <div style="padding:1.5rem 2rem;">
                    <table id="tblMyJobs" class="display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Location / Area</th>
                                <th>Issue Details</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="all">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_requests as $r): 
                                $pri = strtolower($r['priority'] ?? 'medium');
                                $pri_class = ['urgent'=>'pr-urgent','critical'=>'pr-urgent','high'=>'pr-high','medium'=>'pr-medium','low'=>'pr-low'][$pri] ?? 'pr-low';
                                $st = strtolower($r['status'] ?? 'assigned');
                                $st_data = [
                                    'assigned'    => ['color'=>'#2F80ED', 'icon'=>'fa-thumbtack', 'label'=>'Assigned'],
                                    'in progress' => ['color'=>'#F39C12', 'icon'=>'fa-spinner fa-spin', 'label'=>'In Progress'],
                                    'on hold'     => ['color'=>'#95A5A6', 'icon'=>'fa-pause-circle', 'label'=>'On Hold']
                                ][$st] ?? ['color'=>'#95A5A6', 'icon'=>'fa-circle', 'label'=>ucwords($st)];
                            ?>
                            <tr data-id="<?= $r['request_id'] ?>">
                                <td style="font-weight:700; color:var(--role-accent);">#<?= $r['request_id'] ?></td>
                                <td>
                                    <div style="font-weight:600;"><?= e($r['location'] ?? '—') ?></div>
                                    <div style="font-size:1.1rem; color:var(--text-muted);"><?= e($r['equipment_or_area'] ?? 'General') ?></div>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:var(--text-secondary);"><?= e($r['issue_category'] ?? 'Maintenance') ?></div>
                                    <div style="font-size:1.1rem; opacity:.8;" class="text-truncate" title="<?= e($r['issue_description']) ?>">
                                        <?= e(mb_strimwidth($r['issue_description'], 0, 45, '…')) ?>
                                    </div>
                                </td>
                                <td><span class="ov-pill <?= $pri_class ?>"><?= ucfirst($pri) ?></span></td>
                                <td>
                                    <span class="ov-pill" style="background:color-mix(in srgb, <?= $st_data['color'] ?> 13%, transparent 87%); color:<?= $st_data['color'] ?>;">
                                        <i class="fas <?= $st_data['icon'] ?> fa-fw" style="margin-right:.4rem;"></i><?= $st_data['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:.6rem;">
                                        <?php if($st === 'assigned' || $st === 'on hold'): ?>
                                            <button class="btn btn-primary btn-sm" onclick="maintAction(<?= $r['request_id'] ?>, 'in progress', this)" title="Start Work">
                                                <span class="btn-text"><i class="fas fa-play"></i> Start</span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($st === 'in progress'): ?>
                                            <button class="btn btn-outline btn-sm" onclick="maintAction(<?= $r['request_id'] ?>, 'on hold', this)" title="Pause Work">
                                                <span class="btn-text"><i class="fas fa-pause"></i> Hold</span>
                                            </button>
                                            <button class="btn btn-success btn-sm" onclick="openMaintComplete(<?= $r['request_id'] ?>)" title="Complete Task">
                                                <span class="btn-text"><i class="fas fa-check-double"></i> Done</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Request Queue -->
    <div class="card" style="margin-bottom:2.5rem;">
        <div class="card-header" style="background:var(--surface-2); padding:1.5rem 2rem; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-clipboard-list"></i> Available Work Orders</h3>
            <span style="font-size:1.2rem; font-weight:600; color:var(--text-muted);"><?= count($open_requests) ?> requests waiting</span>
        </div>
        <div class="card-body" style="padding:1.5rem 2rem;">
            <table id="tblQueue" class="display responsive nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Summary</th>
                        <th>Reported</th>
                        <th class="all">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($open_requests as $r): 
                        $pri = strtolower($r['priority'] ?? 'medium');
                        $pri_class = ['urgent'=>'pr-urgent','critical'=>'pr-urgent','high'=>'pr-high','medium'=>'pr-medium','low'=>'pr-low'][$pri] ?? 'pr-low';
                    ?>
                    <tr>
                        <td style="font-weight:700;">#<?= $r['request_id'] ?></td>
                        <td><strong><?= e($r['location'] ?? '—') ?></strong></td>
                        <td><?= e(ucfirst($r['issue_category'] ?? 'Other')) ?></td>
                        <td><span class="ov-pill <?= $pri_class ?>"><?= ucfirst($pri) ?></span></td>
                        <td class="text-truncate" style="max-width:200px;"><?= e(mb_strimwidth($r['issue_description'], 0, 50, '…')) ?></td>
                        <td style="font-size:1.15rem; color:var(--text-muted);"><?= date('d M, H:i', strtotime($r['reported_at'])) ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm btn-wide" onclick="maintAction(<?= $r['request_id'] ?>, 'accept', this)">
                                <span class="btn-text"><i class="fas fa-hand-pointer"></i> Accept Job</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recently Completed -->
    <?php if(!empty($completed_today)): ?>
    <div class="card">
        <div class="card-header" style="padding:1.5rem 2rem;">
            <h3 style="font-size:1.5rem; font-weight:700; color:var(--success);"><i class="fas fa-check-circle"></i> Accomplishments Today <span class="badge" style="background:var(--success); color:#fff;"><?= count($completed_today) ?></span></h3>
        </div>
        <div class="card-body" style="padding:1.5rem 2rem;">
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:1.5rem;">
                <?php foreach($completed_today as $r): ?>
                <div style="background:var(--surface-2); border-radius:12px; padding:1.5rem; border-left:4px solid var(--success); display:flex; gap:1.2rem; align-items:center;">
                    <div style="width:45px; height:45px; border-radius:10px; background:rgba(39,174,96,0.1); color:var(--success); display:flex; align-items:center; justify-content:center; font-size:1.8rem; flex-shrink:0;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:1.3rem;">#<?= $r['request_id'] ?> — <?= e($r['location']) ?></div>
                        <div style="font-size:1.15rem; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e($r['issue_description']) ?></div>
                        <div style="font-size:1.1rem; color:var(--success); font-weight:600; margin-top:.3rem;">
                            <i class="fas fa-clock"></i> Completed at <?= date('H:i', strtotime($r['completed_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ════════════════ COMPLETION MODAL ════════════════ -->
<div class="modal-bg" id="mdlMaintComplete">
    <div class="modal-box" style="max-width:550px;">
        <div class="modal-header" style="background:linear-gradient(135deg, #27AE60, #2ECC71); color:#fff; border:none; padding:1.8rem 2.5rem;">
            <h3 style="font-size:1.8rem; font-weight:700;"><i class="fas fa-check-double"></i> Complete Work Order</h3>
            <button class="modal-close" onclick="closeModal('mdlMaintComplete')" style="background:rgba(255,255,255,0.2); color:#fff; border:none; width:34px; height:34px; border-radius:10px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:2.5rem;">
            <p style="font-size:1.3rem; margin-bottom:2rem; color:var(--text-secondary);">Please provide details about the resolution for order <strong id="txtMaintOrderId" style="color:var(--role-accent);">#0</strong>.</p>
            
            <form id="frmMaintComplete" onsubmit="event.preventDefault(); submitMaintComplete();" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_maintenance_status">
                <input type="hidden" name="status" value="completed">
                <input type="hidden" name="request_id" id="hidMaintId">
                
                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;">Completion Notes / Work Performed *</label>
                    <textarea name="completion_notes" class="form-control" rows="4" required style="resize:none; padding:1.2rem; border-radius:10px;" placeholder="Describe how the issue was resolved..."></textarea>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2.5rem;">
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;"><i class="fas fa-arrow-circle-up"></i> Before Image</label>
                        <input type="file" name="before_photo" accept="image/*" class="form-control" style="padding:.8rem;">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700; display:block; margin-bottom:.8rem; font-size:1.2rem;"><i class="fas fa-check-circle"></i> After Image</label>
                        <input type="file" name="after_photo" accept="image/*" class="form-control" style="padding:.8rem;">
                    </div>
                </div>
                
                <div style="display:flex; gap:1.2rem;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('mdlMaintComplete')" style="flex:1;">Cancel</button>
                    <button type="submit" class="btn btn-success" id="btnSubmitMaint" style="flex:2; padding:1.3rem; font-weight:700; font-size:1.4rem;">
                        <span class="btn-text">Confirm Completion</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if ($.fn.DataTable) {
        $('#tblMyJobs, #tblQueue').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search orders..."
            }
        });
    }
});

async function maintAction(id, act, btn) {
    let confirmMsg = null;
    let payload = { request_id: id };
    let successMsg = "Order updated!";
    
    if (act === 'accept') {
        payload.action = 'accept_maintenance_request';
        confirmMsg = "Do you want to accept this work order and assign it to yourself?";
        successMsg = "Job accepted! Moving to your active list.";
    } else {
        payload.action = 'update_maintenance_status';
        payload.status = act;
        if (act === 'in progress') successMsg = "Clock started! Repair is now in progress.";
        if (act === 'on hold') successMsg = "Work order placed on hold.";
    }
    
    if (confirmMsg && !confirm(confirmMsg)) return;
    
    const icon = btn.querySelector('i');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    const res = await doAction(payload, successMsg);
    if (res) {
        setTimeout(() => location.reload(), 1200);
    } else {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}

function openMaintComplete(id) {
    document.getElementById('hidMaintId').value = id;
    document.getElementById('txtMaintOrderId').innerText = '#' + id;
    openModal('mdlMaintComplete');
}

async function submitMaintComplete() {
    const btn = document.getElementById('btnSubmitMaint');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizing...';
    btn.disabled = true;
    
    const fd = new FormData(document.getElementById('frmMaintComplete'));
    const res = await doAction(fd, "Excellent work! Repair completed and logged.");
    
    if (res) {
        closeModal('mdlMaintComplete');
        setTimeout(() => location.reload(), 1500);
    } else {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    }
}
</script>

<style>
/* ── DataTables Premium Overrides ── */
.dataTables_wrapper .top { margin-bottom: 1.5rem; display: flex; justify-content: flex-end; }
.dataTables_filter input {
    background: var(--surface-2); border: 1.5px solid var(--border); border-radius: 10px;
    padding: .8rem 1.5rem; color: var(--text-primary); font-size: 1.2rem; width: 280px;
}
.dataTables_filter input:focus { border-color: var(--role-accent); outline: none; }
table.dataTable thead th {
    background: var(--surface-2); color: var(--text-secondary); font-weight: 700;
    text-transform: uppercase; font-size: 1.05rem; letter-spacing: .05em; padding: 1.5rem; border-bottom: 2px solid var(--border);
}
table.dataTable tbody td { padding: 1.4rem 1.5rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
table.dataTable.no-footer { border-bottom: 1px solid var(--border); }
.dataTables_info, .dataTables_paginate { margin-top: 1.5rem; font-size: 1.15rem; }
.paginate_button { border-radius: 8px !important; border: 1px solid var(--border) !important; background: var(--surface) !important; }
.paginate_button.current { background: var(--role-accent) !important; color: #fff !important; border-color: var(--role-accent) !important; }

/* ── Typography & Layout ── */
.text-truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── Badge / Pill colors are defined in overview but added here for safety ── */
.pr-urgent { color: #E74C3C; background: rgba(231,76,60,.12); }
.pr-high   { color: #E67E22; background: rgba(230,126,34,.12); }
.pr-medium { color: #F39C12; background: rgba(243,156,18,.12); }
.pr-low    { color: #27AE60; background: rgba(39,174,96,.12); }
.ov-pill   { display: inline-flex; align-items: center; padding: .3rem .9rem; border-radius: 20px; font-size: 1.1rem; font-weight: 700; }
</style>

