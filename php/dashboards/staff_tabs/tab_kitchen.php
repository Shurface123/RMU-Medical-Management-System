<?php
/**
 * tab_kitchen.php — Module 8: Kitchen Staff Module (Modernized)
 */
if ($staffRole !== 'kitchen_staff') { echo '<div id="sec-kitchen" class="dash-section"></div>'; return; }

$kitchen_tasks = dbSelect($conn,"SELECT * FROM kitchen_tasks WHERE assigned_to=? ORDER BY FIELD(preparation_status,'pending','in preparation','ready'),scheduled_time ASC LIMIT 50","i",[$staff_id]);
$dietary_flags = dbSelect($conn,"SELECT * FROM kitchen_dietary_flags WHERE DATE(flagged_at)=? ORDER BY flag_id DESC LIMIT 15","s",[$today]);
?>
<div id="sec-kitchen" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-hat-chef" style="color:var(--role-accent);"></i> Culinary Command</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Meal production monitoring and dietary safety management</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-danger" onclick="openModal('dietaryModal')"><i class="fas fa-allergies mr-2"></i> Report Allergy Flag</button>
            <button class="btn btn-outline" onclick="location.reload()"><i class="fas fa-sync"></i></button>
        </div>
    </div>

    <!-- Urgent Dietary Alerts Strip -->
    <?php if(!empty($dietary_flags)): ?>
    <div class="dietary-ticker card mb-8" style="background:#EB575708; border:1px solid #EB575733; padding:1.5rem 2.5rem; display:flex; align-items:center; gap:2rem;">
        <div style="width:40px; height:40px; border-radius:10px; background:#EB575715; color:#EB5757; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
            <i class="fas fa-triangle-exclamation"></i>
        </div>
        <div style="flex:1; overflow:hidden;">
            <strong style="display:block; font-size:1.1rem; text-transform:uppercase; color:#EB5757; letter-spacing:1px; margin-bottom:.3rem;">Critical Dietary Alerts</strong>
            <div style="display:flex; gap:1.5rem; flex-wrap:wrap;">
                <?php foreach($dietary_flags as $df): ?>
                <div style="font-size:1.25rem; font-weight:700; color:var(--text-primary);">
                    <i class="fas fa-user-tag text-muted mr-1"></i> <?= e($df['patient_name']) ?>: <span style="color:#EB5757;"><?= e($df['issue_description']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Meal Production Board -->
    <div class="card">
        <div class="card-header" style="background:var(--surface-2); padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center;">
             <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-fire-burner mr-2"></i> Active Production Board</h3>
             <span class="p-badge"><?= count($kitchen_tasks) ?> Orders En Queue</span>
        </div>
        <div style="padding:1.5rem 2.5rem;">
            <table id="tblKitchen" class="display responsive nowrap" style="width:100%">
                <thead><tr><th>Order Detail</th><th>Location</th><th>Quantity</th><th>Dietary Logic</th><th>Target Time</th><th>Status</th><th>Workflow</th></tr></thead>
                <tbody>
                    <?php foreach($kitchen_tasks as $t):
                        $st = strtolower($t['preparation_status']??'pending');
                        $st_clr = ['pending'=>'#F2C94C','in preparation'=>'#2F80ED','ready'=>'#27AE60','delivered'=>'var(--text-muted)'][$st]??'#999';
                        $has_dietary = !empty($t['dietary_requirements']) && $t['dietary_requirements'] !== 'null';
                        $next_st = ['pending'=>'in preparation','in preparation'=>'ready','ready'=>'delivered'][$st]??null;
                        $n_icons = ['in preparation'=>'fa-fork-knife','ready'=>'fa-check-circle','delivered'=>'fa-truck-ramp-box'];
                    ?>
                    <tr style="<?= $has_dietary?'background:rgba(235, 87, 87, 0.02);':'' ?>">
                        <td>
                            <div style="font-weight:800; font-size:1.35rem; display:flex; align-items:center; gap:.8rem;">
                                <?php if($has_dietary): ?><i class="fas fa-shield-virus text-danger" title="Dietary Restriction"></i><?php endif; ?>
                                <?= e(ucfirst($t['meal_type'])) ?>
                            </div>
                        </td>
                        <td><div style="font-size:1.2rem; font-weight:600;"><?= e($t['ward_department']) ?></div></td>
                        <td><strong>x<?= (int)$t['quantity'] ?></strong></td>
                        <td>
                            <?php if($has_dietary): ?>
                            <span class="p-badge" style="background:#EB575722; color:#EB5757; font-weight:900;"><?= strtoupper(e($t['dietary_requirements']??'FLAGGED')) ?></span>
                            <?php else: ?><span class="p-badge" style="background:#27AE6015; color:#27AE60; font-weight:800;">STANDARD</span><?php endif; ?>
                        </td>
                        <td><span style="font-size:1.2rem; font-weight:700; color:var(--text-muted);"><?= date('H:i', strtotime($t['scheduled_time'])) ?></span></td>
                        <td><span class="p-badge status active" style="background:<?= $st_clr ?>22; color:<?= $st_clr ?>;"><i class="fas fa-radar-pulse mr-2"></i><?= strtoupper($st) ?></span></td>
                        <td>
                            <?php if($next_st): ?>
                            <button class="btn btn-primary btn-xs" onclick="updateKitchenTask(<?= (int)$t['task_id'] ?>,'<?= e($next_st) ?>')">
                                <i class="fas <?= $n_icons[$next_st]??'fa-arrow-right' ?> mr-1"></i> <?= strtoupper($next_st) ?>
                            </button>
                            <?php else: ?><i class="fas fa-check-double text-success"></i><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ════════════════ KITCHEN MODALS ════════════════ -->
<div class="modal-bg" id="dietaryModal">
    <div class="modal-box" style="max-width:450px;">
        <div class="modal-header" style="background:#EB5757; color:#fff; border:none; padding:1.5rem 2rem;">
            <h3 style="font-size:1.6rem; font-weight:700;"><i class="fas fa-allergies mr-2"></i> Report Dietary Alarm</h3>
            <button class="modal-close" onclick="closeModal('dietaryModal')" style="background:rgba(255,255,255,0.2); color:#fff; border:none; width:34px; height:34px; border-radius:10px; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:2.5rem;">
            <form id="frmDietary" onsubmit="event.preventDefault(); submitDietary();">
                <input type="hidden" name="action" value="report_dietary_issue">
                <div class="form-group mb-6">
                    <label style="font-weight:700; display:block; margin-bottom:.5rem;">Patient Name *</label>
                    <input type="text" name="patient_name" class="form-control" placeholder="Identify patient..." required>
                </div>
                <div class="form-group mb-8">
                    <label style="font-weight:700; display:block; margin-bottom:.5rem;">Dietary / Allergy Issue *</label>
                    <textarea name="issue" class="form-control" rows="3" placeholder="e.g. Severe nut allergy discovered..." required></textarea>
                </div>
                <button type="submit" class="btn btn-danger btn-wide">TRANSMIT ALARM</button>
            </form>
        </div>
    </div>
</div>

<style>
.btn-xs { padding:.4rem .9rem; font-size:1rem; font-weight:900; border-radius:8px; }
.dietary-ticker { animation: alert-pulse 4s infinite; }
@keyframes alert-pulse { 0% { box-shadow: 0 0 0 0 rgba(235, 87, 87, 0.1); } 50% { box-shadow: 0 0 0 10px rgba(235, 87, 87, 0); } 100% { box-shadow: 0 0 0 0 rgba(235, 87, 87, 0); } }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblKitchen').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Filter production board..." }
        });
    }
});

async function updateKitchenTask(id, status){
    showToast('Updating production state...', 'info');
    const res = await doAction({action:'update_kitchen_task_status', task_id:id, status}, 'Kitchen telemetry updated.');
    if(res) setTimeout(()=>location.reload(), 800);
}
async function submitDietary(){
    const fd = new FormData(document.getElementById('frmDietary'));
    const res = await doAction(fd, 'Dietary alarm successfully transmitted.');
    if(res){ closeModal('dietaryModal'); setTimeout(()=>location.reload(), 800); }
}
</script>
