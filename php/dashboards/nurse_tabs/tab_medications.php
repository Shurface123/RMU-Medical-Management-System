<?php
// ============================================================
// NURSE DASHBOARD - MEDICATION ADMINISTRATION (MODULE 3)
// ============================================================
if (!isset($conn)) exit;

// ── GET SHIFT & WARD ─────────────────────────────────────────
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';

// ── FETCH MEDS FOR TODAY ─────────────────────────────────────
// We fetch records from `medication_administration` for today 
// matching patients in the nurse's ward.
$meds = [];
$q_str = "
    SELECT 
        ma.id AS admin_pk, ma.admin_id, ma.medicine_name, ma.dosage, ma.route, 
        ma.scheduled_time, ma.administered_at, ma.status, ma.notes,
        p.patient_id, u.name AS patient_name, u.gender, u.date_of_birth,
        b.ward, b.bed_number,
        '' AS profile_photo
    FROM medication_administration ma
    JOIN patients p ON ma.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status = 'Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
    WHERE DATE(ma.scheduled_time) = '$today'
";

if ($ward_assigned !== 'All Wards' && $ward_assigned !== 'Not Assigned') {
    $q_str .= " AND b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'";
}
$q_str .= " ORDER BY ma.scheduled_time ASC";

$q = mysqli_query($conn, $q_str);
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        // Calculate age
        $r['age'] = 'N/A';
        if (!empty($r['date_of_birth'])) {
            $dob = new DateTime($r['date_of_birth']);
            $now = new DateTime();
            $r['age'] = $now->diff($dob)->y;
        }
        
        // Categorize overdueness
        $sched = new DateTime($r['scheduled_time']);
        $now = new DateTime();
        $is_overdue = ($r['status'] == 'Pending' && $now > $sched);
        $r['is_overdue'] = $is_overdue;
        
        $meds[] = $r;
    }
}
?>

<div class="tab-content active" id="medications">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.2rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;"><i class="fas fa-prescription-bottle-alt text-primary"></i> Medication Administration</h2>
            <p style="font-size:1.25rem; color:var(--text-muted);">Current MAR schedule for ward: <strong style="color:var(--text-primary);"><?= e($ward_assigned) ?></strong></p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <div style="display:flex; align-items:center; gap:.8rem; background:rgba(var(--primary-rgb), 0.05); padding:.7rem 1.2rem; border-radius:12px; border:1px solid rgba(var(--primary-rgb), 0.1);">
                <i class="far fa-calendar-alt text-primary"></i>
                <span style="font-size:1.2rem; font-weight:700; color:var(--text-primary);"><?= date('D, d M Y') ?></span>
            </div>
            <button class="btn btn-ghost" onclick="location.reload();" style="width:42px; height:42px; padding:0; border-radius:12px;"><span class="btn-text">
                <i class="fas fa-sync-alt"></i>
            </span></button>
        </div>
    </div>

    <!-- Stats Summary -->
    <?php
        $t_pending = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Pending'?1:0), 0);
        $t_admin   = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Administered'?1:0), 0);
        $t_missed  = array_reduce($meds, fn($c,$m) => $c + (in_array($m['status'],['Missed','Refused','Held'])?1:0), 0);
        $overdue_count = array_reduce($meds, fn($c,$m) => $c + ($m['is_overdue']?1:0), 0);
    ?>
    <div class="adm-summary-strip" style="margin-bottom:2.5rem;">
        <div class="adm-mini-card">
            <div class="adm-mini-card-num orange"><?= $t_pending ?></div>
            <div class="adm-mini-card-label">
                <i class="fas fa-clock text-warning" style="margin-right:.5rem;"></i>Scheduled
                <?php if($overdue_count > 0): ?>
                    <span class="adm-badge adm-badge-danger pulse-fade" style="margin-left:.8rem; font-size:1rem;"><?= $overdue_count ?> OVERDUE</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="adm-mini-card">
            <div class="adm-mini-card-num green"><?= $t_admin ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-check-double text-success" style="margin-right:.5rem;"></i>Administered</div>
        </div>
        <div class="adm-mini-card" style="background:<?= $t_missed > 0 ? 'rgba(231,76,60,0.03)' : '' ?>;">
            <div class="adm-mini-card-num red"><?= $t_missed ?></div>
            <div class="adm-mini-card-label"><i class="fas fa-exclamation-circle text-danger" style="margin-right:.5rem;"></i>Missed / Held</div>
        </div>
    </div>

    <!-- Medication List -->
    <div class="adm-card shadow-sm">
        <div class="adm-card-header" style="justify-content:space-between;">
            <h3 style="font-size:1.5rem; font-weight:700;"><i class="fas fa-list-check text-primary"></i> Daily Administration Record</h3>
            <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); font-weight:700;">WARD MAR · <?= strtoupper($ward_assigned) ?></span>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <div class="adm-table-wrap">
                <table class="adm-table" id="medsTable">
                    <thead>
                        <tr>
                            <th>Sched. Time</th>
                            <th>Patient Identity</th>
                            <th>Medication / Route</th>
                            <th>Dosage</th>
                            <th>Status</th>
                            <th style="text-align:right;">Clinical Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($meds)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:5rem 2rem; color:var(--text-muted);">
                                    <div style="opacity:0.2; margin-bottom:1.5rem;">
                                        <i class="fas fa-pills" style="font-size:4rem;"></i>
                                    </div>
                                    <h5 style="font-weight:600; font-size:1.4rem;">No Scheduled Medications</h5>
                                    <p style="font-size:1.2rem;">All clear for the current shift in this ward.</p>
                                </td>
                            </tr>
                        <?php else: foreach($meds as $m): 
                            $is_high_alert = preg_match('/(Insulin|Warfarin|Heparin|Digoxin|Morphine|Dopamine)/i', $m['medicine_name']);
                        ?>
                            <tr class="<?= $m['is_overdue'] ? 'row-overdue' : '' ?>" style="transition:all .2s ease;">
                                <td>
                                    <div style="display:flex; align-items:center; gap:.8rem;">
                                        <i class="fas fa-clock <?= $m['is_overdue']?'text-danger':($m['status']=='Administered'?'text-success':'text-warning') ?>" style="font-size:1.2rem;"></i>
                                        <span style="font-family:monospace; font-weight:800; font-size:1.4rem; color:var(--text-primary);">
                                            <?= date('H:i', strtotime($m['scheduled_time'])) ?>
                                        </span>
                                    </div>
                                    <?php if($m['is_overdue']): ?>
                                        <small class="text-danger" style="font-weight:700; text-transform:uppercase; font-size:0.9rem;">Overdue</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:1.2rem;">
                                        <div style="width:38px; height:38px; border-radius:10px; background:<?= $m['gender']=='Male' ? 'rgba(52, 152, 219, 0.1)' : 'rgba(231, 76, 60, 0.1)' ?>; color:<?= $m['gender']=='Male' ? 'var(--primary)' : 'var(--danger)' ?>; display:flex; align-items:center; justify-content:center; font-size:1.4rem; font-weight:800; border:1px solid rgba(0,0,0,0.05); flex-shrink:0;">
                                            <?= strtoupper(substr($m['patient_name'],0,1)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:1.35rem; color:var(--text-primary); margin-bottom:.1rem;"><?= e($m['patient_name']) ?></div>
                                            <small style="color:var(--text-muted); font-weight:600;"><?= e($m['ward']) ?> (Bed <?= e($m['bed_number']) ?>) · <span style="font-family:monospace;">#<?= e($m['patient_id']) ?></span></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:.8rem;">
                                        <div style="flex:1;">
                                            <div style="font-weight:700; font-size:1.4rem; color:var(--text-primary); display:flex; align-items:center; gap:.5rem;">
                                                <?= e($m['medicine_name']) ?>
                                                <?php if($is_high_alert): ?>
                                                    <span class="adm-badge adm-badge-danger" style="font-size:0.85rem; padding:.2rem .5rem;" title="High Alert Medication"><i class="fas fa-skull"></i> ALERT</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size:1.15rem; color:var(--text-muted); font-weight:500;">
                                                <i class="fas fa-directions"></i> Via: <span style="color:var(--text-secondary); font-weight:600;"><?= e($m['route']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:800; font-size:1.4rem; color:var(--primary); background:rgba(var(--primary-rgb),0.05); padding:.3rem .8rem; border-radius:8px; border:1px solid rgba(var(--primary-rgb),0.1);">
                                        <?= e($m['dosage']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($m['status'] == 'Pending'): ?>
                                        <span class="adm-badge adm-badge-warning" style="font-weight:700; padding:.4rem 1rem;"><i class="fas fa-hourglass-start"></i> PENDING</span>
                                    <?php elseif($m['status'] == 'Administered'): ?>
                                        <div style="display:flex; flex-direction:column; gap:.3rem;">
                                            <span class="adm-badge adm-badge-success" style="font-weight:700; padding:.4rem 1rem;"><i class="fas fa-check-double"></i> GIVEN</span>
                                            <small style="color:var(--text-muted); font-weight:600; text-align:center; font-size:1rem;"><?= date('H:i', strtotime($m['administered_at'])) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <span class="adm-badge adm-badge-danger" style="font-weight:700; padding:.4rem 1rem;"><i class="fas fa-ban"></i> <?= strtoupper(e($m['status'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <?php if($m['status'] == 'Pending'): ?>
                                        <button class="btn btn-primary" onclick="openAdministerModal(<?= $m['admin_pk'] ?>, '<?= e(addslashes($m['patient_name'])) ?>', '<?= e(addslashes($m['medicine_name'])) ?>', '<?= e(addslashes($m['dosage'])) ?>', '<?= e(addslashes($m['route'])) ?>')" style="padding:.6rem 1.5rem; border-radius:10px; font-weight:700;"><span class="btn-text">
                                            <i class="fas fa-syringe"></i> Verify
                                        </span></button>
                                    <?php else: ?>
                                        <button class="btn btn-ghost" style="opacity:0.6;" disabled><span class="btn-text">
                                            <i class="fas fa-lock-open"></i> Archive
                                        </span></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADMINISTER MEDICATION               -->
<!-- ========================================== -->
<!-- ========================================== -->
<!-- MODAL: ADMINISTER MEDICATION               -->
<!-- ========================================== -->
<div class="modal-bg" id="administerModal">
    <div class="modal-box" style="max-width:680px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--primary); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-prescription-bottle-alt" style="margin-right:.8rem;"></i> Medication Verification</h3>
            <button class="btn btn-primary modal-close" onclick="closeAdministerModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">×</span></button>
        </div>
        
        <div style="padding:2.5rem;">
            <!-- Safety Alert -->
            <div style="background:rgba(231,76,60,0.05); border:1px solid rgba(231,76,60,0.15); border-radius:12px; padding:1.2rem 1.5rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.2rem;">
                <i class="fas fa-shield-alt text-danger" style="font-size:2rem;"></i>
                <div>
                    <strong style="color:var(--danger); font-size:1.2rem; display:block; margin-bottom:.2rem;">SAFETY PROTOCOL: THE 5 RIGHTS</strong>
                    <p style="margin:0; font-size:1.1rem; color:var(--text-secondary); font-weight:600;">Right Patient · Right Drug · Right Dose · Right Route · Right Time</p>
                </div>
            </div>

            <form id="administerForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="administer_med">
                <input type="hidden" name="admin_id" id="med_admin_id">
                
                <!-- Medication Context Banner -->
                <div style="background:var(--surface-2); border:1px solid var(--border); border-radius:15px; padding:1.5rem 1.8rem; margin-bottom:2.2rem;">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                        <div class="info-item">
                            <small style="text-transform:uppercase; font-size:0.9rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:.3rem;">Patient</small>
                            <div id="med_patient_name" style="font-weight:700; font-size:1.3rem; color:var(--text-primary);">Loading...</div>
                        </div>
                        <div class="info-item">
                            <small style="text-transform:uppercase; font-size:0.9rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:.3rem;">Route</small>
                            <div id="med_route" style="font-weight:700; font-size:1.3rem; color:var(--text-primary);">Loading...</div>
                        </div>
                        <div class="info-item" style="grid-column: span 2; background:rgba(var(--primary-rgb),0.05); padding:1rem; border-radius:10px; border:1px solid rgba(var(--primary-rgb),0.1);">
                            <small style="text-transform:uppercase; font-size:0.9rem; font-weight:700; color:var(--text-muted); display:block; margin-bottom:.3rem;">Medication &amp; Dosage</small>
                            <div style="display:flex; align-items:center; gap:.8rem;">
                                <span id="med_drug_name" style="font-weight:800; font-size:1.6rem; color:var(--primary);">Drug Name</span>
                                <span id="med_dosage" style="font-weight:700; font-size:1.4rem; color:var(--text-secondary); opacity:0.8;">(Dose)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Outcome Status</label>
                        <select class="form-control" name="med_status" id="med_status" required style="font-weight:600; padding:.8rem;">
                            <option value="Administered">Administered</option>
                            <option value="Refused">Refused by Patient</option>
                            <option value="Held">Held (Doctor's Orders)</option>
                            <option value="Missed">Missed / Unavailable</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Verification Mode</label>
                        <select class="form-control" name="verification_method" required style="font-weight:600; padding:.8rem;">
                            <option value="Manual">Manual Visual Check</option>
                            <option value="Barcode">Barcode Scanned (Wristband)</option>
                            <option value="eMAR">Double Nurse eMAR Check</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="med_notes_div">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Clinical Documentation / Reasoning</label>
                    <textarea class="form-control" name="notes" id="med_notes" rows="2" placeholder="Mandatory if medication was Refused or Held..."></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="btn btn-ghost" onclick="closeAdministerModal()" style="font-weight:600;"><span class="btn-text">Cancel</span></button>
                    <button type="submit" class="btn-icon btn btn-primary" id="btnAdminSave" style="padding:.8rem 2.5rem; font-weight:700; border-radius:12px; box-shadow:0 4px 12px rgba(var(--primary-rgb), 0.3);"><span class="btn-text">
                        <i class="fas fa-check-double"></i> Confirm Administration
                    </span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAdministerModal(adminId, patientName, drugName, dosage, route) {
    document.getElementById('med_admin_id').value = adminId;
    document.getElementById('med_patient_name').textContent = patientName;
    document.getElementById('med_drug_name').textContent = drugName;
    document.getElementById('med_dosage').textContent = '(' + dosage + ')';
    document.getElementById('med_route').textContent = route;
    
    document.getElementById('administerForm').reset();
    $('#med_status').trigger('change');
    document.getElementById('administerModal').style.display = 'flex';
}
function closeAdministerModal() {
    document.getElementById('administerModal').style.display = 'none';
}

$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#medsTable').DataTable({
            pageLength: 10, ordering: true, order: [[0, "asc"]],
            language: { search: "", searchPlaceholder: "Search MAR schedule..." }
        });
        $('.dataTables_filter input').addClass('form-control').css({'width':'250px','border-radius':'10px'});
    }

    $('#med_status').on('change', function() {
        const status = $(this).val();
        if(status !== 'Administered') {
            $('#med_notes').prop('required', true).attr('placeholder', 'Please provide clinical reason for: ' + status);
            $('#med_notes_div label').html('Reason for Non-Administration <span class="text-danger">*</span>');
        } else {
            $('#med_notes').prop('required', false).attr('placeholder', 'Optional clinical observations...');
            $('#med_notes_div label').html('Clinical Documentation / Reasoning');
        }
    });

    $('#administerForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirm Administration?',
            text: "Are you certain you have verified the 5 Rights and wish to commit this medical record?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: 'var(--text-muted)',
            confirmButtonText: 'Yes, Confirm & Sign'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnAdminSave');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Authenticating...').prop('disabled', true);
                
                $.ajax({
                    url: '../nurse/process_medication.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire({ icon: 'success', title: 'Committed!', text: 'MAR record updated successfully.', timer: 1500, showConfirmButton: false });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Commit Failed', text: res.message });
                            btn.html('<i class="fas fa-check-double"></i> Confirm Administration').prop('disabled', false);
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Loss of communication with Pharmacy Information System.' });
                        btn.html('<i class="fas fa-check-double"></i> Confirm Administration').prop('disabled', false);
                    }
                });
            }
        });
    });
});
</script>

