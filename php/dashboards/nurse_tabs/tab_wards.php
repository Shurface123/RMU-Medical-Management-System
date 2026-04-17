<?php
// ============================================================
// NURSE DASHBOARD - WARD & BEDS (MODULE 4)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? null;

// Handle edge case where nurse has no ward assigned
if (!$ward_assigned || $ward_assigned === 'Not Assigned') {
    echo '<div class="tab-content active" id="wards">
            <div style="height:400px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1.5rem; text-align:center;">
                <div style="width:100px; height:100px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:4rem; color:var(--text-muted); opacity:0.5;">
                    <i class="fas fa-bed"></i>
                </div>
                <div>
                    <h3 style="font-weight:700; color:var(--text-primary); margin-bottom:.5rem;">No Ward Assigned</h3>
                    <p style="font-size:1.3rem; color:var(--text-muted);">Please activate a shift or contact the Administrator to assign your duty station.</p>
                </div>
            </div></div>';
    return;
}

// ── FETCH BED DATA FOR ACTIVE WARD ───────────────────────────
$beds = [];
$q_beds = mysqli_query($conn, "
    SELECT 
        b.id AS bed_id, b.bed_number, b.status AS bed_status, b.bed_type, b.price_per_day,
        ba.id AS assignment_id, ba.admission_date, ba.expected_discharge_date,
        p.id AS patient_pk, p.patient_id, 
        u.name AS patient_name, u.gender,
        (SELECT COUNT(*) FROM isolation_records ir WHERE ir.patient_id = p.id AND ir.status = 'Active') AS is_isolated
    FROM beds b
    LEFT JOIN bed_assignments ba ON b.id = ba.bed_id AND ba.status = 'Occupied'
    LEFT JOIN patients p ON ba.patient_id = p.id
    LEFT JOIN users u ON p.user_id = u.id
    WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'
    ORDER BY b.bed_number ASC
");

if ($q_beds) {
    while ($r = mysqli_fetch_assoc($q_beds)) {
        $beds[] = $r;
    }
}

// Stats for header
$total_beds = count($beds);
$occupied = array_reduce($beds, fn($c,$b) => $c + ($b['bed_status']=='Occupied'?1:0), 0);
$available = $total_beds - $occupied;
$isolation = array_reduce($beds, fn($c,$b) => $c + ($b['is_isolated']>0?1:0), 0);

// ── FETCH AVAILABLE WARDS/BEDS FOR TRANSFER DROPDOWN ─────────
$available_beds = [];
$q_avail = mysqli_query($conn, "SELECT id, ward, bed_number FROM beds WHERE status='Available' ORDER BY ward, bed_number");
if ($q_avail) {
    while($r = mysqli_fetch_assoc($q_avail)) {
        $available_beds[$r['ward']][] = $r;
    }
}
?>

<div class="tab-content active" id="wards">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;"><i class="fas fa-hospital-user text-primary"></i> <?= e($ward_assigned) ?> Management</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Real-time bed utilization and patient placement tracking.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <div style="background:var(--surface-2); border:1px solid var(--border); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1.2rem;">
                 <div style="text-align:right;">
                    <div style="font-size:1rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Occupancy</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><?= $occupied ?> / <?= $total_beds ?> <small style="font-weight:500;">Beds</small></div>
                 </div>
                 <div style="width:1px; height:30px; background:var(--border);"></div>
                 <div style="text-align:right;">
                    <div style="font-size:1rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Utilization</div>
                    <div style="font-size:1.4rem; font-weight:800; color:var(--primary);"><?= $total_beds > 0 ? round(($occupied / $total_beds) * 100) : 0 ?>%</div>
                 </div>
            </div>
            <button class="btn btn-ghost" onclick="location.reload();" style="width:45px; height:45px; border-radius:12px; padding:0;"><span class="btn-text">
                <i class="fas fa-sync-alt"></i>
            </span></button>
        </div>
    </div>

    <!-- Capacity Summary Strip -->
    <div class="adm-summary-strip" style="margin-bottom:2.5rem;">
        <div class="adm-mini-card">
            <div class="adm-mini-card-num green"><?= $available ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-check-circle text-success" style="margin-right:.5rem;"></i>Available Beds</div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num orange"><?= $occupied ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-user-injured text-warning" style="margin-right:.5rem;"></i>Occupied</div>
        </div>
        <div class="adm-mini-card" style="background:<?= $isolation > 0 ? 'rgba(231,76,60,0.03)' : '' ?>;">
            <div class="adm-mini-card-num red"><?= $isolation ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-biohazard text-danger" style="margin-right:.5rem;"></i>Isolation Active</div>
        </div>
    </div>


    <!-- Bed Grid -->
    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:2rem;">
        <?php foreach ($beds as $bed): ?>
            <div class="adm-card shadow-sm" style="margin:0; border:1.5px solid <?= $bed['bed_status'] == 'Available' ? 'var(--border)' : ($bed['is_isolated'] > 0 ? 'var(--danger)' : 'var(--primary)') ?>; transition:transform .2s ease, box-shadow .2s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.box_shadow='0 10px 20px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='translateY(0)'; this.style.box_shadow='none';">
                
                <?php if($bed['is_isolated'] > 0): ?>
                    <div style="background:var(--danger); color:#fff; padding:.6rem; text-align:center; font-weight:800; font-size:1rem; letter-spacing:.05em;">
                        <i class="fas fa-biohazard pulse-fade"></i> ACTIVE ISOLATION
                    </div>
                <?php endif; ?>

                <div class="adm-card-body" style="padding:1.8rem;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.8rem;">
                        <div style="width:55px; height:55px; border-radius:12px; border:2.5px solid <?= $bed['bed_status'] == 'Available' ? 'var(--border)' : 'var(--primary)' ?>; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                            <small style="font-size:0.8rem; font-weight:700; color:var(--text-muted); line-height:1;">BED</small>
                            <span style="font-size:1.8rem; font-weight:900; color:<?= $bed['bed_status'] == 'Available' ? 'var(--text-muted)' : 'var(--primary)' ?>;"><?= e($bed['bed_number']) ?></span>
                        </div>
                        <?php if($bed['bed_status'] == 'Available'): ?>
                            <span class="adm-badge adm-badge-success" style="padding:.5rem 1.2rem; font-weight:700;"><i class="fas fa-check-circle"></i> AVAILABLE</span>
                        <?php else: ?>
                            <span class="adm-badge adm-badge-info" style="padding:.5rem 1.2rem; font-weight:700;"><i class="fas fa-user-injured"></i> OCCUPIED</span>
                        <?php endif; ?>
                    </div>

                    <?php if($bed['bed_status'] == 'Occupied' && !empty($bed['patient_pk'])): ?>
                        <div style="background:var(--surface-2); border-radius:12px; padding:1.2rem; margin-bottom:1.5rem; border:1px solid var(--border);">
                            <div style="font-weight:800; font-size:1.45rem; color:var(--text-primary); margin-bottom:.3rem;"><?= e($bed['patient_name']) ?></div>
                            <div style="display:flex; align-items:center; gap:.8rem; font-size:1.15rem; color:var(--text-muted); font-weight:600;">
                                <span style="font-family:monospace; color:var(--text-secondary);">#<?= e($bed['patient_id']) ?></span>
                                <span>·</span>
                                <span><i class="fas fa-calendar-alt"></i> Adm: <?= date('d M', strtotime($bed['admission_date'])) ?></span>
                            </div>
                        </div>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.8rem;">
                            <?php if($bed['is_isolated'] == 0): ?>
                                <button class="btn btn-ghost btn-sm" onclick="openIsolationModal(<?= $bed['patient_pk'] ?>, <?= $bed['bed_id'] ?>)" style="color:var(--danger); border-color:rgba(231,76,60,0.2); background:rgba(231,76,60,0.03);"><span class="btn-text">
                                    <i class="fas fa-shield-virus"></i> Isolate
                                </span></button>
                            <?php else: ?>
                                <button class="btn btn-ghost btn-sm" disabled style="opacity:0.5;"><span class="btn-text">
                                    <i class="fas fa-biohazard"></i> Isolated
                                </span></button>
                            <?php endif; ?>
                            <button class="btn btn-primary btn-sm" onclick="openTransferModal(<?= $bed['patient_pk'] ?>, <?= $bed['bed_id'] ?>, '<?= e($bed['bed_number']) ?>')"><span class="btn-text">
                                <i class="fas fa-exchange-alt"></i> Transfer
                            </span></button>
                        </div>
                    <?php else: ?>
                        <div style="text-align:center; padding:2rem 0; opacity:0.4;">
                            <i class="fas fa-bed-pulse" style="font-size:3.5rem; margin-bottom:1rem; color:var(--text-muted);"></i>
                            <div style="font-weight:700; color:var(--text-muted); text-transform:uppercase; font-size:1rem; letter-spacing:.05em;">Ready for Intake</div>
                        </div>
                        <div style="border-top:1px solid var(--border); padding-top:1.2rem; display:flex; justify-content:space-between; align-items:center;">
                            <small style="color:var(--text-muted); font-weight:600;"><i class="fas fa-tag"></i> <?= e($bed['bed_type']) ?></small>
                            <span style="font-weight:800; color:var(--text-secondary); font-size:1.2rem;">GHC <?= number_format($bed['price_per_day'], 2) ?>/d</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: BED TRANSFER REQUEST                -->
<!-- ========================================== -->
</div>

<!-- ========================================== -->
<!-- MODAL: BED TRANSFER REQUEST                -->
<!-- ========================================== -->
<div class="modal-bg" id="transferModal">
    <div class="modal-box" style="max-width:620px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--primary); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-exchange-alt" style="margin-right:.8rem;"></i> Bed Transfer Request</h3>
            <button class="btn btn-primary modal-close" onclick="closeTransferModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">×</span></button>
        </div>
        
        <div style="padding:2.5rem;">
            <form id="transferForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="request_transfer">
                <input type="hidden" name="patient_id" id="t_patient_id">
                <input type="hidden" name="from_bed_id" id="t_from_bed_id">
                <input type="hidden" name="from_ward" value="<?= e($ward_assigned) ?>">
                
                <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.2rem;">
                    <div style="width:45px; height:45px; background:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div style="flex:1;">
                        <small style="text-transform:uppercase; font-size:0.9rem; font-weight:700; color:var(--text-muted);">Current Location</small>
                        <div id="t_current_loc_display" style="font-weight:700; font-size:1.3rem; color:var(--text-primary);">Ward Name - Bed 000</div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Destination Unit/Bed</label>
                    <select class="form-control" name="to_bed_id" required style="font-weight:600; padding:.8rem;">
                        <option value="">-- Select Destination --</option>
                        <?php foreach($available_beds as $w => $bds): ?>
                            <optgroup label="<?= e($w) ?>">
                                <?php foreach($bds as $bb): ?>
                                    <option value="<?= $bb['id'] ?>">Bed <?= e($bb['bed_number']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Clinical/Operational Reason</label>
                    <textarea class="form-control" name="transfer_reason" rows="3" placeholder="Condition change, isolation requirement, step-down care, etc..." required></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="closeTransferModal()" style="font-weight:600;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-primary" id="btnTransfer" style="padding:.8rem 2.5rem; font-weight:700; border-radius:12px; box-shadow:0 4px 12px rgba(var(--primary-rgb), 0.3);"><span class="btn-text">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ISOLATION RECORD                    -->
<!-- ========================================== -->
<div class="modal-bg" id="isolationModal">
    <div class="modal-box" style="max-width:620px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--danger); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-biohazard" style="margin-right:.8rem;"></i> Isolation Protocol</h3>
            <button class="btn btn-primary modal-close" onclick="closeIsolationModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">×</span></button>
        </div>
        
        <div style="padding:2.5rem;">
            <form id="isolationForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_isolation">
                <input type="hidden" name="patient_id" id="i_patient_id">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Isolation Type</label>
                        <select class="form-control" name="isolation_type" required style="font-weight:600; padding:.8rem;">
                            <option value="">-- Select --</option>
                            <option value="Contact">Contact</option>
                            <option value="Droplet">Droplet</option>
                            <option value="Airborne">Airborne</option>
                            <option value="Protective">Protective (Reverse)</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Infection/Diagnosis</label>
                        <input type="text" class="form-control" name="reason" placeholder="e.g. MRSA, COVID, TB" required style="font-weight:600;">
                    </div>
                </div>

                <div class="form-group">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:1rem;">Barrier Precautions Required</label>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; background:var(--surface-2); padding:1.5rem; border-radius:12px; border:1px solid var(--border);">
                        <?php foreach(['Gloves','Gowns','N95 Mask','Eye Protection'] as $idx => $p): ?>
                            <label class="adm-tab-pill" style="display:flex; align-items:center; gap:.8rem; cursor:pointer; background:#fff; margin:0;">
                                <input type="checkbox" name="precautions[]" value="<?= $p ?>" style="width:18px; height:18px;">
                                <span style="font-weight:600;"><?= $p ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="closeIsolationModal()" style="font-weight:600;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn btn-danger" id="btnIsolate" style="padding:.8rem 2.5rem; font-weight:700; border-radius:12px; box-shadow:0 4px 12px rgba(var(--danger-rgb), 0.3);"><span class="btn-text">
                        <i class="fas fa-biohazard"></i> Activate Protocol
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTransferModal(patientId, bedId, bedNum) {
    document.getElementById('t_patient_id').value = patientId;
    document.getElementById('t_from_bed_id').value = bedId;
    document.getElementById('t_current_loc_display').textContent = "<?= e($ward_assigned) ?> - Bed " + bedNum;
    document.getElementById('transferModal').style.display = 'flex';
}
function closeTransferModal() {
    document.getElementById('transferModal').style.display = 'none';
}

function openIsolationModal(patientId, bedId) {
    document.getElementById('i_patient_id').value = patientId;
    document.getElementById('isolationModal').style.display = 'flex';
}
function closeIsolationModal() {
    document.getElementById('isolationModal').style.display = 'none';
}

$(document).ready(function() {
    // Bed Transfer
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Confirm Transfer?',
            text: "Requesting a bed transfer requires administrative approval. Proceed?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary)',
            confirmButtonText: 'Yes, Submit Request'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnTransfer');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
                $.ajax({
                    url: '../nurse/process_ward.php',
                    type: 'POST', data: $(this).serialize(), dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire({ icon: 'success', title: 'Requested!', text: res.message, timer: 1500, showConfirmButton: false });
                            setTimeout(() => window.location.href = '?tab=wards', 1500);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Failed', text: res.message });
                            btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Request');
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Communication loss with Bed Manager.' });
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Request');
                    }
                });
            }
        });
    });

    // Isolation Protocol
    $('#isolationForm').on('submit', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Initiate Isolation?',
            text: "You are setting clinical barrier precautions for this area. This will flag the unit for all personnel.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger)',
            confirmButtonText: 'Yes, Activate Protocol'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnIsolate');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                $.ajax({
                    url: '../nurse/process_ward.php',
                    type: 'POST', data: $(this).serialize(), dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire({ icon: 'success', title: 'Activated!', text: res.message, timer: 1500, showConfirmButton: false });
                            setTimeout(() => window.location.href = '?tab=wards', 1500);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Failed', text: res.message });
                            btn.prop('disabled', false).html('<i class="fas fa-biohazard"></i> Activate Protocol');
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Failed to commit isolation record.' });
                        btn.prop('disabled', false).html('<i class="fas fa-biohazard"></i> Activate Protocol');
                    }
                });
            }
        });
    });
});
</script>

