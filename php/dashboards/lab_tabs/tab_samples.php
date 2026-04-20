<?php
// ============================================================
// LAB DASHBOARD - TAB SAMPLES (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

$status_filter = $_GET['filter_status'] ?? 'All';

$query = "SELECT s.*, p.full_name AS patient_name, p.patient_id as patient_code, c.test_name, o.id AS order_id_clean
          FROM lab_samples s
          JOIN lab_test_orders o ON s.order_id = o.id
          JOIN patients p ON o.patient_id = p.id
          JOIN lab_test_catalog c ON o.test_catalog_id = c.id";
if ($status_filter !== 'All') {
    $query .= " WHERE s.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
$query .= " ORDER BY s.created_at DESC";
$samples_res = mysqli_query($conn, $query);

$eligible_orders = [];
$eo_q = mysqli_query($conn, "SELECT o.id, p.full_name AS patient_name, c.test_name, p.patient_id as patient_code
    FROM lab_test_orders o
    JOIN patients p ON o.patient_id = p.id
    JOIN lab_test_catalog c ON o.test_catalog_id = c.id
    WHERE o.order_status = 'Accepted'
    ORDER BY o.created_at DESC");
while ($r = mysqli_fetch_assoc($eo_q)) { $eligible_orders[] = $r; }

function getSampleStageColor($status) {
    if ($status === 'Collected') return ['bg' => 'rgba(245,158,11,0.1)', 'color' => '#f59e0b', 'border' => '#f59e0b', 'icon' => 'fa-box'];
    if ($status === 'Received')  return ['bg' => 'rgba(59,130,246,0.1)', 'color' => '#3b82f6', 'border' => '#3b82f6', 'icon' => 'fa-check-double'];
    if ($status === 'Processing')return ['bg' => 'rgba(13,148,136,0.1)', 'color' => '#0d9488', 'border' => '#0d9488', 'icon' => 'fa-microscope fa-spin'];
    if (in_array($status, ['Stored','Disposed'])) return ['bg' => 'rgba(34,197,94,0.1)', 'color' => '#22c55e', 'border' => '#22c55e', 'icon' => 'fa-archive'];
    if ($status === 'Rejected')  return ['bg' => 'rgba(244,63,94,0.1)',  'color' => '#f43f5e', 'border' => '#f43f5e', 'icon' => 'fa-times-circle'];
    return ['bg' => 'var(--surface-2)', 'color' => 'var(--text-primary)', 'border' => 'var(--border)', 'icon' => 'fa-vial'];
}
?>

<div class="tab-content <?= ($active_tab === 'samples') ? 'active' : '' ?>" id="samples">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-vial" style="color:var(--primary); margin-right:.8rem;"></i> Sample Tracking Matrix
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Log, transport, and archive biological specimens securely.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('registerSampleModal').style.display='flex';" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-plus-circle"></i> Register Sample</span></button>
            <select class="form-control" style="width:250px; font-weight:700; border:2px solid var(--border);" onchange="location.href='?tab=samples&filter_status='+this.value">
                <option value="All" <?= $status_filter=='All'?'selected':'' ?>>📑 All Tracked Samples</option>
                <option value="Collected" <?= $status_filter=='Collected'?'selected':'' ?>>📦 Collected (In Transit)</option>
                <option value="Received"  <?= $status_filter=='Received' ?'selected':'' ?>>📥 Received at Lab</option>
                <option value="Processing"<?= $status_filter=='Processing'?'selected':'' ?>>🔬 Processing Phase</option>
                <option value="Stored"    <?= $status_filter=='Stored'   ?'selected':'' ?>>❄️ Archived / Stored</option>
                <option value="Rejected"  <?= $status_filter=='Rejected' ?'selected':'' ?>>🚫 Rejected</option>
            </select>
        </div>
    </div>

    <!-- Sample Grid View -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:2rem;margin-bottom:2rem;">
        <?php while($row = mysqli_fetch_assoc($samples_res)):
            $st = getSampleStageColor($row['status']); ?>
        <div class="adm-card shadow-sm" style="border-radius:16px; transition:transform 0.3s; border-top: 5px solid <?= $st['border'] ?>; overflow:hidden;">
            <div class="adm-card-header" style="background:linear-gradient(135deg, rgba(0,0,0,0.01), rgba(0,0,0,0.03)); padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                    <h4 style="margin:0; font-size:1.4rem; font-weight:900; color:var(--text-primary);"><i class="fas fa-barcode" style="opacity:0.5; margin-right:.4rem;"></i> <?= e($row['sample_code']) ?></h4>
                    <div style="font-size:1.1rem; color:var(--text-muted); font-weight:600; margin-top:.4rem;">Parent Order: <span style="color:var(--text-primary);">#ORD-<?= str_pad($row['order_id_clean'],5,'0',STR_PAD_LEFT) ?></span></div>
                </div>
                <span class="adm-badge" style="background:<?= $st['bg'] ?>; color:<?= $st['color'] ?>; font-weight:800; font-size:1.1rem;"><i class="fas <?= $st['icon'] ?>" style="margin-right:.5rem;"></i> <?= e($row['status']) ?></span>
            </div>
            <div class="adm-card-body" style="padding:2rem;">
                <div style="margin-bottom:1.5rem;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                        <span style="color:var(--text-muted); font-weight:600; font-size:1.1rem;">Patient Identity:</span>
                        <strong style="color:var(--text-primary); font-size:1.2rem;"><?= e($row['patient_name']) ?> <small>(<?= e($row['patient_code']) ?>)</small></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                        <span style="color:var(--text-muted); font-weight:600; font-size:1.1rem;">Target Analysis:</span>
                        <strong style="color:var(--text-primary); font-size:1.2rem;"><?= e($row['test_name']) ?></strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:.8rem;">
                        <span style="color:var(--text-muted); font-weight:600; font-size:1.1rem;">Specimen Info:</span>
                        <div style="text-align:right;">
                            <span class="adm-badge" style="background:var(--surface-3); color:var(--text-secondary); margin-bottom:.3rem;"><?= e($row['sample_type']) ?></span><br>
                            <span style="font-weight:600; font-size:1.1rem;"><i class="fas fa-prescription-bottle" style="color:var(--role-accent);"></i> <?= e($row['container_type']) ?></span>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:space-between;">
                        <span style="color:var(--text-muted); font-weight:600; font-size:1.1rem;">Draw Time:</span>
                        <strong style="color:var(--text-secondary); font-size:1.2rem;"><i class="far fa-clock"></i> <?= date('d M, h:i A', strtotime($row['collection_date'].' '.$row['collection_time'])) ?></strong>
                    </div>
                </div>

                <?php if($row['condition_on_receipt']): ?>
                    <div style="padding:1.2rem; border-radius:8px; border-left:4px solid <?= in_array($row['condition_on_receipt'],['Haemolysed','Clotted','Insufficient','Contaminated'])?'var(--danger)':'var(--success)' ?>; background:var(--surface-2); font-size:1.2rem; font-weight:600; margin-bottom:1.5rem;">
                        <span style="color:var(--text-muted); text-transform:uppercase; font-size:1rem; display:block;">Receipt Condition</span>
                        <span style="color:<?= in_array($row['condition_on_receipt'],['Haemolysed','Clotted','Insufficient','Contaminated'])?'var(--danger)':'var(--success)' ?>">
                            <?= e($row['condition_on_receipt']) ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div style="display:flex; gap:.8rem; flex-wrap:wrap; border-top:1px solid var(--border); padding-top:1.5rem;">
                    <button class="adm-btn adm-btn-ghost btn-sm" style="flex:1;" onclick="printBarcode('<?= $row['sample_code'] ?>')"><span class="btn-text"><i class="fas fa-print"></i> Barcode</span></button>
                    
                    <?php if($row['status']==='Collected'): ?>
                        <button class="adm-btn adm-btn-primary btn-sm" style="flex:1; border-radius:8px;" onclick="receiveSample(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-boxes"></i> Log Arrival</span></button>
                    <?php endif; ?>
                    
                    <?php if($row['status']==='Received'): ?>
                        <button class="adm-btn adm-btn-primary btn-sm" style="flex:1; border-radius:8px;" onclick="processSample(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-microscope"></i> Prep &amp; Process</span></button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if(mysqli_num_rows($samples_res)===0): ?>
            <div style="grid-column:1/-1; padding:6rem 2rem; text-align:center; color:var(--text-muted); background:var(--surface-1); border:2px dashed var(--border); border-radius:16px;">
                <i class="fas fa-vial" style="font-size:5rem; opacity:0.2; margin-bottom:1.5rem; display:block;"></i>
                <h3 style="font-weight:800; font-size:1.6rem; color:var(--text-primary); margin:0;">Specimen Matrix Empty</h3>
                <p style="font-size:1.2rem; margin-top:.5rem;">No biological samples track to this specific status filter.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: REGISTER SAMPLE                     -->
<!-- ========================================== -->
<div class="modal-bg" id="registerSampleModal">
    <div class="modal-box" style="max-width:750px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-vial"></i> Register Biological Sample</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('registerSampleModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <div id="sample-reg-alert" style="display:none; padding:1.2rem; border-radius:8px; margin-bottom:2rem; font-size:1.2rem; font-weight:700;"></div>

            <div style="margin-bottom:2rem;">
                <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Link To Clinical Order <span style="color:var(--danger);">*</span></label>
                <select id="reg_order_id" class="form-control" style="font-size:1.3rem; padding:1rem; border:2px solid var(--border);" required>
                    <option value="">— Select an Authorized Order Framework —</option>
                    <?php foreach($eligible_orders as $eo): ?>
                        <option value="<?= $eo['id'] ?>">#ORD-<?= str_pad($eo['id'],5,'0',STR_PAD_LEFT) ?> &middot; <?= e($eo['patient_name']) ?> (<?= e($eo['patient_code']) ?>) &middot; <?= e($eo['test_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if(empty($eligible_orders)): ?>
                    <small style="color:var(--danger); display:block; margin-top:.8rem; font-weight:700;"><i class="fas fa-exclamation-triangle"></i> Protocol Error: No 'Accepted' clinical orders exist in the active queue.</small>
                <?php endif; ?>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Specimen Matrix <span style="color:var(--danger);">*</span></label>
                    <select id="reg_sample_type" class="form-control" style="font-size:1.3rem; padding:1rem;" required>
                        <option value="">— Specify Material —</option>
                        <option>Whole Blood</option><option>Serum</option><option>Plasma</option>
                        <option>Urine</option><option>Urine (24h)</option><option>Stool</option>
                        <option>Sputum</option><option>CSF</option><option>Swab</option>
                        <option>Saliva</option><option>Tissue</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Container Geometry <span style="color:var(--danger);">*</span></label>
                    <select id="reg_container" class="form-control" style="font-size:1.3rem; padding:1rem;" required>
                        <option value="">— Specify Receptacle —</option>
                        <option>EDTA Tube (Purple)</option><option>SST (Gold/Yellow)</option>
                        <option>Heparin Tube (Green)</option><option>Sodium Citrate (Blue)</option>
                        <option>Fluoride Oxalate (Grey)</option><option>Plain Red Top</option>
                        <option>Sterile Container</option><option>Transport Medium</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem; margin-bottom:2.5rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Extraction Date <span style="color:var(--danger);">*</span></label>
                    <input type="date" id="reg_coll_date" class="form-control" value="<?= date('Y-m-d') ?>" style="font-size:1.3rem; padding:1rem;" required>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Time <span style="color:var(--danger);">*</span></label>
                    <input type="time" id="reg_coll_time" class="form-control" value="<?= date('H:i') ?>" style="font-size:1.3rem; padding:1rem;" required>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('registerSampleModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel Registration</span></button>
                <button type="button" class="adm-btn adm-btn-primary" id="btnConfirmSample" style="border-radius:10px; font-weight:900;" onclick="submitSampleReg()"><span class="btn-text"><i class="fas fa-check-circle" style="margin-right:.5rem;"></i> Confirmed Intake</span></button>
            </div>
        </div>
    </div>
</div>

<script>
function printBarcode(code) {
    alert("SYSTEM CALL: Initializing Zebra Barcode Printer protocol for Code: " + code);
}

function submitSampleReg() {
    const oId = $('#reg_order_id').val();
    const stype = $('#reg_sample_type').val();
    const cont = $('#reg_container').val();
    const dt = $('#reg_coll_date').val();
    const tm = $('#reg_coll_time').val();

    if(!oId || !stype || !cont || !dt || !tm) {
        showSampleAlert('Exception: All mandatory clinical inputs are required.', 'danger');
        return;
    }

    const btn = $('#btnConfirmSample');
    const oldHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-compact-disc fa-spin"></i> Indexing...');

    $.post('lab_actions.php', {
        action: 'register_sample',
        order_id: oId,
        sample_type: stype,
        container_type: cont,
        collection_date: dt,
        collection_time: tm,
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) {
            showSampleAlert(res.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showSampleAlert(res.message, 'danger');
            btn.prop('disabled', false).html(oldHtml);
        }
    }, 'json');
}

function showSampleAlert(msg, type) {
    const box = $('#sample-reg-alert');
    box.removeClass('bg-danger text-white bg-success');
    if(type==='danger') { box.css({background: 'rgba(231,76,60,0.1)', color: '#e74c3c', border: '1px solid #e74c3c'}); }
    else { box.css({background: 'rgba(46,204,113,0.1)', color: '#2ecc71', border: '1px solid #2ecc71'}); }
    box.html(msg).slideDown();
    setTimeout(()=>box.slideUp(), 4000);
}

function receiveSample(sid) {
    if(!confirm("Affirm sample receipt in central lab?")) return;
    updateSampleStatus(sid, 'Received');
}

function processSample(sid) {
    if(!confirm("Authorize transition of specimen to processing phase?")) return;
    updateSampleStatus(sid, 'Processing');
}

function updateSampleStatus(sid, status) {
    $.post('lab_actions.php', {
        action: 'update_sample_status',
        sample_id: sid,
        status: status,
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Workflow Exception: ' + res.message);
        }
    }, 'json');
}
</script>