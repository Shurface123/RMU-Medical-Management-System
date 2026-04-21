<?php
/**
 * tab_laundry.php — Module 5: Laundry Staff Module (Modernized)
 */
if ($staffRole !== 'laundry_staff') { echo '<div id="sec-laundry" class="dash-section"></div>'; return; }

$batches   = dbSelect($conn,"SELECT * FROM laundry_batches WHERE assigned_to=? ORDER BY created_at DESC LIMIT 50","i",[$staff_id]);
$inventory = dbSelect($conn,"SELECT * FROM laundry_inventory ORDER BY available_quantity ASC LIMIT 50");
$damage_reports = dbSelect($conn,"SELECT * FROM laundry_damage_reports ORDER BY reported_at DESC LIMIT 20");
?>
<div id="sec-laundry" class="dash-section">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3rem;flex-wrap:wrap;gap:1.5rem;">
        <div>
            <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><i class="fas fa-tshirt" style="color:var(--role-accent);"></i> Textile Logistics</h2>
            <p style="font-size:1.35rem;color:var(--text-muted);margin:0.5rem 0 0;">Manage linen inventory, sterilization, and batch processing</p>
        </div>
        <div style="display:flex;gap:1rem;">
            <button class="btn btn-primary" onclick="openModal('newBatchModal')"><i class="fas fa-layer-plus mr-2"></i> Register Batch</button>
            <button class="btn btn-outline" onclick="openModal('damageModal')"><i class="fas fa-exclamation-triangle mr-2"></i> Report Damage</button>
        </div>
    </div>

    <!-- Inventory KPIs -->
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:2.5rem; margin-bottom:3rem;">
        <?php 
        $stock_counts = array_reduce($inventory, function($c, $i) { 
            $c['total'] += $i['available_quantity']; 
            if($i['available_quantity'] <= ($i['reorder_level']??10)) $c['low']++;
            return $c;
        }, ['total'=>0, 'low'=>0]);
        $active_batches = count(array_filter($batches, fn($b)=>$b['delivery_status']!=='delivered'));
        ?>
        <div class="stat-card-v2">
            <div class="s-icon" style="background:#2F80ED15; color:#2F80ED;"><i class="fas fa-boxes-stacked"></i></div>
            <div class="s-data"><span>Total Linen</span><strong><?= number_format($stock_counts['total']) ?></strong></div>
        </div>
        <div class="stat-card-v2">
            <div class="s-icon" style="background:#F2994A15; color:#F2994A;"><i class="fas fa-hourglass-half"></i></div>
            <div class="s-data"><span>Active Batches</span><strong><?= $active_batches ?></strong></div>
        </div>
        <div class="stat-card-v2">
            <div class="s-icon" style="background:#EB575715; color:#EB5757;"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="s-data"><span>Stock Alerts</span><strong><?= $stock_counts['low'] ?></strong></div>
        </div>
        <div class="stat-card-v2">
            <div class="s-icon" style="background:#27AE6015; color:#27AE60;"><i class="fas fa-check-double"></i></div>
            <div class="s-data"><span>Processed Today</span><strong>14</strong></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1.8fr 1fr; gap:3rem; margin-bottom:3rem;">
        <!-- Process Queue -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-sync-alt mr-2 text-primary"></i> Sterilization Pipeline</h3>
                <span class="p-badge"><?= $active_batches ?> Active</span>
            </div>
            <div style="padding:1.5rem 2.5rem;">
                <table id="tblBatches" class="display responsive nowrap" style="width:100%">
                    <thead><tr><th>Batch Code</th><th>Specs</th><th>Origin</th><th>Phase status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach($batches as $b):
                            $bs = $b['delivery_status']==='delivered'?'delivered':($b['washing_status']==='in progress'?'washing':($b['collection_status']==='collected'?'collected':'pending'));
                            $pipeline = ['collected','washing','ironing','quality check','delivered'];
                            $pi = array_search($bs,$pipeline);
                            $sc_map = ['collected'=>'#F2994A','washing'=>'#2F80ED','ironing'=>'#9B51E0','quality check'=>'var(--role-accent)','delivered'=>'#27AE60'];
                            $sc = $sc_map[$bs]??'#999';
                            $next_status = $pipeline[min($pi+1, 4)]??$bs;
                        ?>
                        <tr>
                            <td>
                                <div style="font-family:monospace; font-weight:800; color:var(--text-primary);"><?= e($b['batch_code']) ?></div>
                                <?php if($b['contaminated_items_count']): ?><span class="p-badge" style="background:#EB575722; color:#EB5757; font-size:1rem; margin-top:.3rem;"><i class="fas fa-biohazard"></i> BIO</span><?php endif; ?>
                            </td>
                            <td><div style="font-size:1.2rem; font-weight:600;"><?= e($b['item_count']) ?> pcs • <?= e(ucfirst($b['item_type'])) ?></div></td>
                            <td><span style="font-size:1.15rem; color:var(--text-muted);"><?= e($b['requested_by']??'General') ?></span></td>
                            <td>
                                <div class="mini-pipe">
                                    <?php foreach($pipeline as $i=>$pst): $done=($i<=$pi); ?>
                                    <div class="pipe-dot <?= $done?'done':'' ?>" style="--dot-clr:<?= $sc ?>"></div>
                                    <?php endforeach; ?>
                                    <span style="font-size:1rem; font-weight:900; color:<?= $sc ?>; margin-left:.5rem;"><?= strtoupper($bs) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if($bs!=='delivered'): ?>
                                <button class="btn btn-primary btn-xs" onclick="updateBatch(<?= $b['batch_id'] ?>,'<?= e($next_status) ?>')">
                                    <i class="fas fa-chevron-right mr-1"></i> <?= strtoupper($next_status) ?>
                                </button>
                                <?php else: ?>
                                <span class="p-badge status active"><i class="fas fa-check"></i> LOGGED</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Inventory Watch -->
        <div class="card">
            <div class="card-header" style="padding:1.8rem 2.5rem;">
                <h3 style="font-size:1.6rem; font-weight:800;"><i class="fas fa-box-open mr-2 text-warning"></i> Inventory Watch</h3>
            </div>
            <div style="padding:2rem 2.5rem; max-height:600px; overflow-y:auto;">
                <?php foreach($inventory as $inv):
                    $qty = (int)$inv['available_quantity']; 
                    $reorder = (int)($inv['reorder_level']??10);
                    $is_low = ($qty <= $reorder);
                    $prog = min(100, round(($qty/($reorder*3))*100));
                ?>
                <div class="inv-item" style="margin-bottom:2.2rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.8rem;">
                        <strong style="font-size:1.35rem;"><?= e(ucfirst($inv['item_type'])) ?></strong>
                        <span style="font-size:1.4rem; font-weight:900; color:<?= $is_low?'#EB5757':'var(--text-primary)' ?>;"><?= $qty ?></span>
                    </div>
                    <div class="prog-track" style="height:6px;"><div class="prog-fill" style="width:<?= $prog ?>%; background:<?= $is_low?'#EB5757':'#27AE60' ?>;"></div></div>
                    <div style="display:flex; justify-content:space-between; margin-top:.5rem; font-size:1.05rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">
                        <span>Min Safety: <?= $reorder ?></span>
                        <span><?= $is_low ? 'CRITICAL' : 'SUFFICIENT' ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<style>
.stat-card-v2 { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:2rem; display:flex; align-items:center; gap:1.8rem; box-shadow:var(--shadow-sm); }
.s-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:2.2rem; }
.s-data span { display:block; font-size:1.15rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); margin-bottom:.2rem; }
.s-data strong { font-size:2.2rem; font-weight:900; color:var(--text-primary); }

.mini-pipe { display:flex; align-items:center; gap:.4rem; }
.pipe-dot { width:10px; height:4px; border-radius:3px; background:var(--border); transition:.3s; }
.pipe-dot.done { background:var(--dot-clr); box-shadow:0 0 5px var(--dot-clr); }

.inv-item:last-child { margin-bottom:0; }
.btn-xs { padding:.4rem .9rem; font-size:1rem; font-weight:900; border-radius:8px; }
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#tblBatches').DataTable({
            responsive: true,
            pageLength: 10,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: { search: "_INPUT_", searchPlaceholder: "Filter batches..." }
        });
    }
});

async function updateBatch(id, st){
    const res = await doAction({action:'update_batch_status',batch_id:id,status:st}, 'Batch phase transitioned flawlessly.');
    if(res) setTimeout(()=>location.reload(), 800);
}
async function submitBatch(){
    const fd = new FormData(document.getElementById('frmNewBatch'));
    const res = await doAction(fd, 'Batch registered. Sterilization sequence queued.');
    if(res){ closeModal('newBatchModal'); setTimeout(()=>location.reload(), 800); }
}
async function submitDamage(){
    const fd = new FormData(document.getElementById('frmDamage'));
    const res = await doAction(fd, 'Asset damage logged. Inventory audit synchronized.');
    if(res){ closeModal('damageModal'); setTimeout(()=>location.reload(), 800); }
}
</script>
