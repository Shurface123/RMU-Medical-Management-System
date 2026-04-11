<?php
// ============================================================
// LAB DASHBOARD - TAB SAMPLES (Module 3)
// ============================================================
if (!isset($user_id)) { exit; }

$status_filter = $_GET['filter_status'] ?? 'All';

// Query Samples
$query = "SELECT s.*, p.full_name AS patient_name, c.test_name, o.id AS order_id_clean
          FROM lab_samples s
          JOIN lab_test_orders o ON s.order_id = o.id
          JOIN patients p ON s.patient_id = p.id
          JOIN lab_test_catalog c ON o.test_catalog_id = c.id";

if ($status_filter !== 'All') {
    $query .= " WHERE s.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}
$query .= " ORDER BY s.created_at DESC";
$samples_res = mysqli_query($conn, $query);

function getSampleStageColor($status) {
    if ($status === 'Collected') return ['bg' => 'var(--warning)', 'text' => '#fff'];
    if ($status === 'Received') return ['bg' => 'var(--info)', 'text' => '#fff'];
    if ($status === 'Processing') return ['bg' => 'var(--primary)', 'text' => '#fff'];
    if ($status === 'Stored' || $status === 'Disposed') return ['bg' => 'var(--success)', 'text' => '#fff'];
    if ($status === 'Rejected') return ['bg' => 'var(--danger)', 'text' => '#fff'];
    return ['bg' => 'var(--surface-2)', 'text' => 'var(--text-primary)'];
}
?>

<div class="sec-header">
    <h2><i class="fas fa-vials"></i> Sample Tracking System</h2>
    <div style="display:flex; gap:1rem; align-items:center;">
        <button class="btn btn-success" onclick="newSampleModal()"><span class="btn-text"><i class="fas fa-plus-circle"></i> Register Sample</span></button>
        <select class="form-select" id="sampleStatusFilter" style="width:200px;" onchange="window.location.href='?tab=samples&filter_status='+this.value">
            <option value="All" <?= $status_filter=='All'?'selected':'' ?>>All Samples</option>
            <option value="Collected" <?= $status_filter=='Collected'?'selected':'' ?>>Collected (Awaiting)</option>
            <option value="Received" <?= $status_filter=='Received'?'selected':'' ?>>Received in Lab</option>
            <option value="Processing" <?= $status_filter=='Processing'?'selected':'' ?>>Processing</option>
            <option value="Stored" <?= $status_filter=='Stored'?'selected':'' ?>>Stored</option>
            <option value="Rejected" <?= $status_filter=='Rejected'?'selected':'' ?>>Rejected</option>
        </select>
    </div>
</div>

<div class="cards-grid">
    <?php while($row = mysqli_fetch_assoc($samples_res)): 
        $st = getSampleStageColor($row['status']);
    ?>
    <div class="info-card" style="border-top: 4px solid <?= $st['bg'] ?>;">
        <div class="info-card-head">
            <div>
                <h4 style="margin:0; font-size:1.15rem; color:var(--text-primary);"><i class="fas fa-barcode"></i> <?= e($row['sample_code']) ?></h4>
                <div style="font-size:0.85rem; color:var(--text-muted);">Ord: #ORD-<?= str_pad($row['order_id_clean'], 5, '0', STR_PAD_LEFT) ?></div>
            </div>
            <span class="adm-badge" style="background:<?= $st['bg'] ?>; color:<?= $st['text'] ?>;"><?= e($row['status']) ?></span>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <strong>Patient:</strong> <?= e($row['patient_name']) ?><br>
            <strong>Test:</strong> <?= e($row['test_name']) ?><br>
            <strong>Type:</strong> <?= e($row['sample_type']) ?> | <?= e($row['container_type']) ?><br>
            <strong>Collected:</strong> <?= date('d M, h:i A', strtotime($row['collection_date'].' '.$row['collection_time'])) ?>
        </div>
        
        <?php if($row['condition_on_receipt']): ?>
            <div style="margin-bottom: 1rem; padding: .5rem; background: var(--surface-2); border-radius: 4px; font-size: 0.9em;">
                <strong>Condition:</strong> 
                <span style="color: <?= in_array($row['condition_on_receipt'], ['Haemolysed', 'Clotted', 'Insufficient', 'Contaminated']) ? 'var(--danger)' : 'var(--success)' ?>">
                    <?= e($row['condition_on_receipt']) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="action-btns" style="display:flex; gap:0.5rem; flex-wrap:wrap; border-top: 1px dashed var(--border); padding-top: 1rem;">
            <button class="btn btn-outline btn-icon btn btn-sm" style="background:var(--surface-2);" onclick="printBarcode('<?= $row['sample_code'] ?>')"><span class="btn-text"><i class="fas fa-print"></i> Barcode</span></button>
            <?php if($row['status'] === 'Collected'): ?>
                <button class="btn btn-primary btn btn-teal btn-sm" onclick="receiveSample(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-boxes"></i> Receive</span></button>
            <?php endif; ?>
            <?php if($row['status'] === 'Received'): ?>
                <button class="btn btn-primary btn-sm" onclick="processSample(<?= $row['id'] ?>)"><span class="btn-text"><i class="fas fa-flask"></i> Process</span></button>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
    <?php if(mysqli_num_rows($samples_res) === 0): ?>
        <div class="info-card" style="grid-column: 1 / -1; text-align: center; padding: 4rem;">
            <i class="fas fa-vial" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
            <p style="color: var(--text-muted);">No samples currently tracked in this category.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Receive Sample Modal -->
<div class="modal fade" id="receiveSampleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-boxes" style="color:var(--primary); margin-right:.5rem;"></i> Receive Sample in Lab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <input type="hidden" id="rec_sample_id">
                <div class="form-group mb-3">
                    <label style="font-size:1.1rem; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Condition on Receipt <span style="color:var(--danger);">*</span></label>
                    <select id="rec_condition" class="form-select" required style="font-size:1.2rem; padding:1.2rem;">
                        <option value="Good">Good</option>
                        <option value="Haemolysed">Haemolysed</option>
                        <option value="Clotted">Clotted</option>
                        <option value="Insufficient">Insufficient</option>
                        <option value="Contaminated">Contaminated</option>
                    </select>
                    <small style="color:var(--text-muted); display:block; margin-top:.5rem;">If marked as anything other than 'Good', the sample will be rejected and the doctor notified.</small>
                </div>
                <div class="form-group mb-0">
                    <label style="font-size:1.1rem; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Storage Location</label>
                    <input type="text" id="rec_location" class="form-control" placeholder="e.g., Rack A, Shelf 2" style="font-size:1.2rem; padding:1.2rem;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><span class="btn-text">Cancel</span></button>
                <button type="button" class="btn btn-primary" onclick="submitReceiveSample()"><span class="btn-text"><i class="fas fa-check"></i> Update Status</span></button>
            </div>
        </div>
    </div>
</div>

<script>
function printBarcode(code) {
    alert("Opening print dialog for barcode: " + code);
    // Real implementation would open a a popup window with a barcode font/canvas
}

function newSampleModal() {
    alert("Sample Registration flow to link Test Order to a new Barcode ID... Backend Logic pending.");
}

function receiveSample(id) {
    $('#rec_sample_id').val(id);
    $('#rec_condition').val('Good');
    $('#rec_location').val('');
    new bootstrap.Modal(document.getElementById('receiveSampleModal')).show();
}

function processSample(id) {
    if(!confirm('Mark this sample as Processing?')) return;
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: { action: 'update_sample_status', sample_id: id, status: 'Processing', csrf_token: '<?= $csrf_token ?>' },
        dataType: 'json',
        success: function(res) {
            if(res.success) location.reload(); else alert('Error: ' + res.message);
        }
    });
}

function submitReceiveSample() {
    const id = $('#rec_sample_id').val();
    const cond = $('#rec_condition').val();
    const loc = $('#rec_location').val().trim();
    
    // If condition is bad, status becomes Rejected. If good, status becomes Received.
    const newStatus = (cond === 'Good') ? 'Received' : 'Rejected';

    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: {
            action: 'update_sample_status',
            sample_id: id,
            status: newStatus,
            condition: cond,
            location: loc,
            csrf_token: '<?= $csrf_token ?>'
        },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                alert('Sample status updated.');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        }
    });
}
</script>
