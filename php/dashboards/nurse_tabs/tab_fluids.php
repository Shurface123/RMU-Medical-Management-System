<?php
// ============================================================
// NURSE DASHBOARD - IV & FLUID MANAGEMENT (MODULE 8)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'Unknown Ward';

// ── FETCH PATIENTS IN WARD ───────────────────────────────────
$patients_in_ward = [];
$q_pw = mysqli_query($conn, "
    SELECT p.id, p.patient_id, u.name, b.bed_number 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    JOIN beds b ON ba.bed_id = b.id
    WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'
    ORDER BY u.name ASC
");
if ($q_pw) {
    while($r = mysqli_fetch_assoc($q_pw)) $patients_in_ward[] = $r;
}

// ── FETCH ACTIVE IV INFUSIONS ────────────────────────────────
$iv_records = [];
$q_iv = mysqli_query($conn, "
    SELECT iv.*, p.patient_id as pid, u.name AS patient_name, b.bed_number 
    FROM iv_fluid_records iv
    JOIN patients p ON iv.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR iv.nurse_id = $nurse_pk)
      AND iv.status IN ('Ordered', 'Running', 'Paused')
    ORDER BY iv.start_time DESC
");
if ($q_iv) {
    while($r = mysqli_fetch_assoc($q_iv)) $iv_records[] = $r;
}

// ── FETCH TODAY'S I&O CHARTS ─────────────────────────────────
$io_records = [];
$q_io = mysqli_query($conn, "
    SELECT fb.*, p.patient_id as pid, u.name AS patient_name, b.bed_number 
    FROM fluid_balance fb
    JOIN patients p ON fb.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE fb.record_date = '$today' AND (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR fb.nurse_id = $nurse_pk)
    ORDER BY p.id ASC
");
if ($q_io) {
    while($r = mysqli_fetch_assoc($q_io)) {
        // Parse JSON strings
        $r['in'] = json_decode($r['intake_sources'] ?? '{"oral":0, "iv":0, "ng_tube":0}', true);
        $r['out'] = json_decode($r['output_sources'] ?? '{"urine":0, "drain":0, "emesis":0}', true);
        $io_records[] = $r;
    }
}
?>

<div class="tab-content" id="fluids">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0"><i class="fas fa-tint text-info me-2"></i> IV & Fluid Management</h4>
            <p class="text-muted mb-0">Track IV infusions and daily Intake & Output (I&O) charts.</p>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <button class="btn btn-primary shadow-sm" style="border-radius:20px;" onclick="document.getElementById('ioForm').reset(); new bootstrap.Modal(document.getElementById('ioModal')).show();">
                <i class="fas fa-plus"></i> Record I&O
            </button>
            <button class="btn btn-outline-info shadow-sm ms-2" style="border-radius:20px;" onclick="document.getElementById('ivForm').reset(); new bootstrap.Modal(document.getElementById('ivModal')).show();">
                <i class="fas fa-syringe"></i> Start IV
            </button>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="fluidTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold px-4" id="iv-tab" data-bs-toggle="tab" data-bs-target="#iv-content" type="button" role="tab">
                <i class="fas fa-syringe text-info me-1"></i> Active IV Infusions
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold px-4" id="io-tab" data-bs-toggle="tab" data-bs-target="#io-content" type="button" role="tab">
                <i class="fas fa-balance-scale text-primary me-1"></i> Daily I&O Charts
            </button>
        </li>
    </ul>

    <div class="tab-content" id="fluidTabsContent">
        
        <!-- IV Infusions Tab -->
        <div class="tab-pane fade show active border-0" id="iv-content" role="tabpanel">
            <div class="card" style="border-radius: 12px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Patient</th>
                                    <th>Fluid Type</th>
                                    <th>Volume (ml)</th>
                                    <th>Rate (ml/hr)</th>
                                    <th>Site</th>
                                    <th>Started</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($iv_records)): ?>
                                    <tr><td colspan="8" class="text-center py-5 text-muted">No active IV infusions in this ward.</td></tr>
                                <?php else: foreach($iv_records as $iv): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold"><?= e($iv['patient_name']) ?></div>
                                            <small class="text-muted">Bed <?= e($iv['bed_number']) ?> (<?= e($iv['pid']) ?>)</small>
                                        </td>
                                        <td class="fw-bold text-dark"><?= e($iv['fluid_type']) ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <?php $pct = min(100, ($iv['volume_ordered']>0) ? ($iv['volume_infused'] / $iv['volume_ordered'] * 100) : 0); ?>
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                                </div>
                                                <small class="fw-bold"><?= $iv['volume_infused'] ?>/<?= $iv['volume_ordered'] ?></small>
                                            </div>
                                        </td>
                                        <td><?= e($iv['infusion_rate']) ?></td>
                                        <td><?= e($iv['site'] ?: 'N/A') ?></td>
                                        <td><?= date('H:i', strtotime($iv['start_time'])) ?></td>
                                        <td>
                                            <?php
                                                $bg = 'bg-secondary';
                                                if($iv['status']=='Running') $bg = 'bg-success';
                                                elseif($iv['status']=='Ordered') $bg = 'bg-warning text-dark';
                                                elseif($iv['status']=='Paused') $bg = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?= $bg ?> rounded-pill px-3 py-2"><?= e($iv['status']) ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light shadow-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                                                    Manage
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 10px;">
                                                    <li><a class="dropdown-item text-primary" href="#" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Running')"><i class="fas fa-play me-2"></i> Start / Resume</a></li>
                                                    <li><a class="dropdown-item text-warning" href="#" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Paused')"><i class="fas fa-pause me-2"></i> Pause</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-success" href="#" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Completed')"><i class="fas fa-check-double me-2"></i> Mark Completed</a></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="updateIvStatus(<?= $iv['id'] ?>, 'Stopped')"><i class="fas fa-stop me-2"></i> Stop Early</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- I&O Charts Tab -->
        <div class="tab-pane fade border-0" id="io-content" role="tabpanel">
            <div class="row g-4">
                <?php if(empty($io_records)): ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-balance-scale fs-1 mb-3 opacity-25 text-primary"></i>
                        <h5>No I&O charted today</h5>
                        <p>Record fluid intakes and outputs to track patient fluid balances.</p>
                    </div>
                <?php else: foreach($io_records as $io): 
                    $net = floatval($io['net_balance']);
                    $net_color = 'text-success';
                    if($net < -500) $net_color = 'text-warning';
                    if($net < -1000) $net_color = 'text-danger';
                    if($net > 1000) $net_color = 'text-info';
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; border-top: 4px solid var(--primary-color) !important;">
                            <div class="card-body">
                                <h5 class="fw-bold mb-1"><?= e($io['patient_name']) ?></h5>
                                <p class="text-muted small mb-3">Bed <?= e($io['bed_number']) ?> (<?= e($io['pid']) ?>)</p>
                                
                                <div class="row text-center mb-3">
                                    <div class="col-6 border-end">
                                        <div class="text-muted small fw-bold">INTAKE (ml)</div>
                                        <h4 class="text-primary mb-0"><?= number_format($io['total_intake'],1) ?></h4>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small fw-bold">OUTPUT (ml)</div>
                                        <h4 class="text-danger mb-0"><?= number_format($io['total_output'],1) ?></h4>
                                    </div>
                                </div>
                                <div class="text-center py-2 bg-light rounded mb-3">
                                    <small class="text-muted fw-bold">NET BALANCE:</small> 
                                    <h5 class="<?= $net_color ?> d-inline mb-0 align-middle">
                                        <?= $net > 0 ? '+' : '' ?><?= number_format($net,1) ?> ml
                                    </h5>
                                </div>

                                <div class="row small text-muted">
                                    <div class="col-6">
                                        <ul class="list-unstyled mb-0 list-bordered">
                                            <li>Oral: <?= $io['in']['oral'] ?? 0 ?></li>
                                            <li>IV: <?= $io['in']['iv'] ?? 0 ?></li>
                                            <li>Tube: <?= $io['in']['ng_tube'] ?? 0 ?></li>
                                        </ul>
                                    </div>
                                    <div class="col-6">
                                        <ul class="list-unstyled mb-0 list-bordered-start">
                                            <li>Urine: <?= $io['out']['urine'] ?? 0 ?></li>
                                            <li>Drain: <?= $io['out']['drain'] ?? 0 ?></li>
                                            <li>Emesis: <?= $io['out']['emesis'] ?? 0 ?></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
.list-bordered li { border-bottom: 1px dashed #ddd; padding: 2px 0; }
.list-bordered li:last-child { border-bottom: none; }
.list-bordered-start li { border-bottom: 1px dashed #ddd; padding: 2px 0; }
.list-bordered-start li:last-child { border-bottom: none; }
</style>

<!-- ========================================== -->
<!-- MODAL: START IV INFUSION                   -->
<!-- ========================================== -->
<div class="modal fade" id="ivModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header bg-info text-white" style="border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-syringe me-2"></i> Start / Order IV Fluid</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="ivForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="start_iv">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Patient</label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">-- Select Patient --</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Fluid Type</label>
                            <input type="text" class="form-control" name="fluid_type" placeholder="Normal Saline, RL..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Site / Line</label>
                            <input type="text" class="form-control" name="site" placeholder="RFA, Left hand..." required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Vol. Ordered (ml)</label>
                            <input type="number" step="0.1" class="form-control" name="volume_ordered" placeholder="e.g. 1000" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Rate (ml/hr)</label>
                            <input type="number" step="0.1" class="form-control" name="infusion_rate" placeholder="e.g. 125" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Immediate Action</label>
                        <select class="form-select" name="initial_status">
                            <option value="Ordered">Just Ordered (Not Started)</option>
                            <option value="Running">Start Infusion Now</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-radius:0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info text-white rounded-pill px-4">Submit Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RECORD INTAKE & OUTPUT              -->
<!-- ========================================== -->
<div class="modal fade" id="ioModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 15px; border: none;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title"><i class="fas fa-balance-scale me-2"></i> Update Daily I&O</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="ioForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_io">
                <div class="modal-body p-4 bg-light">
                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Patient</label>
                        <select class="form-select" name="patient_id" required>
                            <option value="">-- Select Patient --</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1"><i class="fas fa-info-circle"></i> This adds to the cumulative total for today.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card border-primary h-100">
                                <div class="card-header bg-primary text-white py-2">Add INTAKE (ml)</div>
                                <div class="card-body p-3">
                                    <div class="mb-2"><label class="small text-muted">Oral</label><input type="number" step="0.1" name="in_oral" class="form-control form-control-sm" value="0"></div>
                                    <div class="mb-2"><label class="small text-muted">IV/Fluids</label><input type="number" step="0.1" name="in_iv" class="form-control form-control-sm" value="0"></div>
                                    <div class="mb-0"><label class="small text-muted">NG Tube/Other</label><input type="number" step="0.1" name="in_ng" class="form-control form-control-sm" value="0"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-danger h-100">
                                <div class="card-header bg-danger text-white py-2">Add OUTPUT (ml)</div>
                                <div class="card-body p-3">
                                    <div class="mb-2"><label class="small text-muted">Urine</label><input type="number" step="0.1" name="out_urine" class="form-control form-control-sm" value="0"></div>
                                    <div class="mb-2"><label class="small text-muted">Drain</label><input type="number" step="0.1" name="out_drain" class="form-control form-control-sm" value="0"></div>
                                    <div class="mb-0"><label class="small text-muted">Emesis/Other</label><input type="number" step="0.1" name="out_emesis" class="form-control form-control-sm" value="0"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-white" style="border-radius:0 0 15px 15px;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" id="btnSaveIo">Save Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateIvStatus(iv_id, status) {
    if(confirm('Change IV status to ' + status + '?')) {
        $.post('../nurse/process_fluids.php', {
            action: 'update_iv_status',
            iv_id: iv_id,
            status: status,
            _csrf: '<?= generateCsrfToken() ?>'
        }, function(res) {
            if(res.success) location.reload(); else alert(res.message);
        }, 'json');
    }
}

$(document).ready(function() {
    $('#ivForm, #ioForm').on('submit', function(e) {
        e.preventDefault();
        const formId = $(this).attr('id');
        const btn = formId === 'ioForm' ? $('#btnSaveIo') : $(this).find('button[type=submit]');
        const origText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '../nurse/process_fluids.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    location.reload();
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).html(origText);
                }
            },
            error: function() {
                alert('An error occurred.');
                btn.prop('disabled', false).html(origText);
            }
        });
    });
});
</script>
