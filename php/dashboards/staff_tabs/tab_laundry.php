<?php
/**
 * tab_laundry.php — Module 5: Laundry Staff Module
 */
if ($staffRole !== 'laundry_staff') { echo '<div id="sec-laundry" class="dash-section"></div>'; return; }

$batches   = dbSelect($conn,"SELECT * FROM laundry_batches WHERE staff_id=? ORDER BY FIELD(status,'collected','washing','ironing','quality check','delivered'), created_at DESC LIMIT 30","i",[$staff_id]);
$inventory = dbSelect($conn,"SELECT * FROM laundry_inventory ORDER BY quantity ASC LIMIT 20");
$damage_reports = dbSelect($conn,"SELECT * FROM laundry_damage_reports WHERE staff_id=? ORDER BY reported_at DESC LIMIT 10","i",[$staff_id]);
?>
<div id="sec-laundry" class="dash-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
        <h2 style="font-size:2.2rem;font-weight:700;"><i class="fas fa-tshirt" style="color:var(--role-accent);"></i> Laundry Management</h2>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <button class="btn btn-primary" onclick="openModal('newBatchModal')"><i class="fas fa-plus"></i> Register Batch</button>
            <button class="btn btn-outline" onclick="openModal('damageModal')"><i class="fas fa-exclamation"></i> Report Damage</button>
        </div>
    </div>

    <!-- Active Batches -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3><i class="fas fa-layer-group"></i> Active Batches (<?=count(array_filter($batches,fn($b)=>$b['status']!=='delivered'))?>)</h3></div>
        <?php if(empty($batches)): ?>
        <div class="card-body" style="text-align:center;padding:4rem;"><p style="color:var(--text-muted);">No batches registered yet.</p></div>
        <?php else: ?>
        <div class="card-body-flush">
        <table class="stf-table">
            <thead><tr><th>Batch Code</th><th>Items</th><th>Weight</th><th>Ward Origin</th><th>Status Pipeline</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach($batches as $b):
                $bs=$b['status']??'collected';
                $pipeline=['collected','washing','ironing','quality check','delivered'];
                $pi=array_search($bs,$pipeline);
                $sc_map=['collected'=>'var(--warning)','washing'=>'var(--info)','ironing'=>'var(--primary)','quality check'=>'var(--role-accent)','delivered'=>'var(--success)'];
                $sc=$sc_map[$bs]??'var(--text-muted)';
                $next_status=$pipeline[min($pi+1,4)]??$bs;
            ?>
            <tr>
                <td><strong style="font-family:monospace;"><?=e($b['batch_code'])?></strong>
                    <?php if($b['contamination_flag']): ?><span class="badge badge-urgent" style="font-size:1rem;margin-left:.5rem;"><i class="fas fa-biohazard"></i> Contaminated</span><?php endif; ?>
                </td>
                <td><?=e($b['item_count'])?> pcs | <?=e($b['item_type']??'—')?></td>
                <td><?=$b['weight_kg']??'—'?> kg</td>
                <td><?=e($b['origin_ward']??'—')?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.3rem;">
                        <?php foreach($pipeline as $i=>$pst): $done=($i<=$pi); ?>
                        <div style="width:28px;height:6px;border-radius:3px;background:<?=$done?$sc:'var(--border)'?>;transition:.3s;"></div>
                        <?php endforeach; ?>
                        <span class="badge" style="background:color-mix(in srgb,<?=$sc?> 15%,#fff 85%);color:<?=$sc?>;margin-left:.5rem;font-size:1rem;"><?=ucfirst($bs)?></span>
                    </div>
                </td>
                <td>
                    <?php if($bs!=='delivered'): ?>
                    <button class="btn btn-primary btn-sm" onclick="updateBatch(<?=$b['id']?>,'<?=e($next_status)?>')">
                        <i class="fas fa-chevron-right"></i> <?=ucfirst($next_status)?>
                    </button>
                    <?php else: ?>
                    <span style="color:var(--success);font-size:1.2rem;"><i class="fas fa-check-circle"></i></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Inventory Status -->
    <div class="card" style="margin-bottom:2rem;">
        <div class="card-header"><h3><i class="fas fa-boxes"></i> Linen Inventory</h3></div>
        <div class="card-body-flush">
            <?php if(empty($inventory)): ?>
            <p style="text-align:center;padding:3rem;color:var(--text-muted);">No inventory data found.</p>
            <?php else: ?>
            <table class="stf-table">
                <thead><tr><th>Item</th><th>Quantity</th><th>Reorder Level</th><th>Alert</th></tr></thead>
                <tbody>
                <?php foreach($inventory as $inv):
                    $qty=(int)$inv['quantity']; $reorder=(int)($inv['reorder_level']??10);
                    $is_low=($qty<=$reorder); $is_out=($qty===0);
                ?>
                <tr>
                    <td><?=e($inv['item_name']??'Unknown')?></td>
                    <td><strong style="font-size:1.6rem;color:<?=$is_out?'var(--danger)':($is_low?'var(--warning)':'var(--success)')?>;"><?=$qty?></strong></td>
                    <td style="color:var(--text-muted);"><?=$reorder?></td>
                    <td><?php if($is_out): ?><span class="badge badge-urgent"><i class="fas fa-times"></i> Out of Stock</span>
                    <?php elseif($is_low): ?><span class="badge badge-medium"><i class="fas fa-exclamation"></i> Low Stock</span>
                    <?php else: ?><span class="badge badge-done"><i class="fas fa-check"></i> OK</span><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Damage Reports -->
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-exclamation-triangle"></i> Damage Reports</h3></div>
        <?php if(empty($damage_reports)): ?>
        <div class="card-body"><p style="text-align:center;color:var(--text-muted);">No damage reports filed.</p></div>
        <?php else: ?>
        <div class="card-body-flush"><table class="stf-table">
            <thead><tr><th>Batch</th><th>Item</th><th>Qty</th><th>Description</th><th>Date</th></tr></thead>
            <tbody><?php foreach($damage_reports as $d): ?>
            <tr><td style="font-family:monospace;"><?=e($d['batch_id'])?></td><td><?=e($d['item_type']??'—')?></td>
                <td><?=$d['quantity']?></td><td><?=e(mb_strimwidth($d['description'],0,60,'…'))?></td>
                <td><?=date('d M Y',strtotime($d['reported_at']))?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
        <?php endif; ?>
    </div>
</div>

<!-- New Batch Modal -->
<div class="modal-bg" id="newBatchModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-plus-circle" style="color:var(--role-accent);"></i> Register New Batch</h3>
            <button class="modal-close" onclick="closeModal('newBatchModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmNewBatch" onsubmit="event.preventDefault();submitBatch();">
            <input type="hidden" name="action" value="register_laundry_batch">
            <div class="form-row">
                <div class="form-group"><label>Item Type *</label>
                    <select name="item_type" class="form-control" required>
                        <option value="">Select</option>
                        <option value="bed sheets">Bed Sheets</option><option value="pillow cases">Pillow Cases</option>
                        <option value="patient gowns">Patient Gowns</option><option value="towels">Towels</option>
                        <option value="scrubs">Scrubs</option><option value="theatre linen">Theatre Linen</option>
                        <option value="curtains">Curtains</option><option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Origin Ward *</label><input type="text" name="origin_ward" class="form-control" required placeholder="e.g. Ward A"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Item Count *</label><input type="number" name="count" class="form-control" min="1" required></div>
                <div class="form-group"><label>Weight (kg) *</label><input type="number" name="weight" step="0.1" class="form-control" min="0" required></div>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;">
                    <input type="checkbox" name="contaminated" value="1" style="width:16px;height:16px;accent-color:var(--danger);">
                    <span>⚠️ Flag as Contaminated (biohazard protocol)</span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnBatch"><i class="fas fa-plus"></i> Register Batch</button>
        </form>
    </div>
</div>

<!-- Damage Report Modal -->
<div class="modal-bg" id="damageModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> Report Damage</h3>
            <button class="modal-close" onclick="closeModal('damageModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmDamage" onsubmit="event.preventDefault();submitDamage();">
            <input type="hidden" name="action" value="report_laundry_damage">
            <div class="form-group"><label>Batch ID (Optional)</label><input type="number" name="batch_id" class="form-control" placeholder="Leave 0 if not batch-specific"></div>
            <div class="form-row">
                <div class="form-group"><label>Item Type *</label><input type="text" name="item_type" class="form-control" required placeholder="e.g. Bed Sheet"></div>
                <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" class="form-control" min="1" required></div>
            </div>
            <div class="form-group"><label>Damage Description *</label><textarea name="description" class="form-control" rows="3" required placeholder="Describe the damage..."></textarea></div>
            <div class="form-group"><label>Photo (Optional)</label><input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-danger btn-wide" id="btnDamage"><i class="fas fa-paper-plane"></i> Submit Report</button>
        </form>
    </div>
</div>

<script>
async function updateBatch(id,st){
    const res=await doAction({action:'update_batch_status',batch_id:id,status:st},'Batch status updated!');
    if(res) setTimeout(()=>location.reload(),700);
}
async function submitBatch(){
    const btn=document.getElementById('btnBatch'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmNewBatch'));
    const res=await doAction(fd);
    btn.innerHTML='<i class="fas fa-plus"></i> Register Batch'; btn.disabled=false;
    if(res){ closeModal('newBatchModal'); document.getElementById('frmNewBatch').reset(); setTimeout(()=>location.reload(),700); }
}
async function submitDamage(){
    const btn=document.getElementById('btnDamage'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmDamage'));
    const res=await doAction(fd,'Damage report submitted!');
    btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Report'; btn.disabled=false;
    if(res){ closeModal('damageModal'); document.getElementById('frmDamage').reset(); setTimeout(()=>location.reload(),700); }
}
</script>
