<?php
// ============================================================
// NURSE DASHBOARD - MEDICATION ADMINISTRATION (MODULE 3)
// ============================================================
if (!isset($conn)) exit;

// â”€â”€ GET SHIFT & WARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$shift_q = mysqli_query($conn, "SELECT ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';

// â”€â”€ FETCH MEDS FOR TODAY â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

    <!-- Advanced Stats Strip -->
    <?php
        $t_pending = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Pending'?1:0), 0);
        $t_admin   = array_reduce($meds, fn($c,$m) => $c + ($m['status']=='Administered'?1:0), 0);
        $t_missed  = array_reduce($meds, fn($c,$m) => $c + (in_array($m['status'],['Missed','Refused','Held'])?1:0), 0);
        $overdue_count = array_reduce($meds, fn($c,$m) => $c + ($m['is_overdue']?1:0), 0);
        $total = count($meds);
        $admin_pct = $total > 0 ? round(($t_admin / $total) * 100) : 0;
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;margin-bottom:2.5rem;">
        <div class="ov-stat-card" style="border-left:4px solid var(--warning);">
            <div class="ov-stat-icon" style="background:var(--role-gradient);"><i class="fas fa-clock"></i></div>
            <div>
                <div class="ov-stat-num" style="color:var(--warning);"><?= $t_pending ?></div>
                <div class="ov-stat-label">Scheduled <?= $overdue_count > 0 ? '<span class="adm-badge adm-badge-danger" style="margin-left:.5rem;font-size:.9rem;">'.$overdue_count.' OVERDUE</span>' : '' ?></div>
            </div>
        </div>
        <div class="ov-stat-card" style="border-left:4px solid var(--success);">
            <div class="ov-stat-icon" style="background:var(--success-gradient);"><i class="fas fa-check-double"></i></div>
            <div>
                <div class="ov-stat-num" style="color:var(--success);"><?= $t_admin ?></div>
                <div class="ov-stat-label">Administered</div>
            </div>
        </div>
        <div class="ov-stat-card" style="border-left:4px solid <?= $t_missed > 0 ? 'var(--danger)' : 'var(--border)' ?>;">
            <div class="ov-stat-icon" style="background:<?= $t_missed > 0 ? 'var(--danger-gradient)' : 'linear-gradient(135deg,#636e72,#b2bec3)' ?>;"><i class="fas fa-ban"></i></div>
            <div>
                <div class="ov-stat-num" style="color:<?= $t_missed > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;"><?= $t_missed ?></div>
                <div class="ov-stat-label">Missed / Held</div>
            </div>
        </div>
        <!-- MAR Progress -->
        <div class="ov-stat-card" style="border-left:4px solid var(--primary); flex-direction:column; align-items:flex-start; gap:.8rem;">
            <div style="display:flex;align-items:center;gap:1rem;width:100%;">
                <div class="ov-stat-icon" style="background:var(--info-gradient);"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <div class="ov-stat-num" style="color:var(--primary);"><?= $admin_pct ?>%</div>
                    <div class="ov-stat-label">MAR Adherence</div>
                </div>
            </div>
            <div class="pay-progress-bar" style="width:100%;">
                <div class="pay-progress-fill" style="width:<?= $admin_pct ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- MAR: Medication Administration Records â€” inv-card Layout -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;gap:.6rem;" class="filter-tabs" id="medFilters">
            <span class="ftab active" onclick="filterMeds(this,'all')">All</span>
            <span class="ftab" onclick="filterMeds(this,'pending')"><i class="fas fa-clock"></i> Pending</span>
            <span class="ftab" onclick="filterMeds(this,'administered')"><i class="fas fa-check"></i> Given</span>
            <span class="ftab" onclick="filterMeds(this,'overdue')"><i class="fas fa-exclamation-triangle"></i> Overdue</span>
        </div>
    </div>

    <?php if(empty($meds)): ?>
    <div class="adm-card" style="text-align:center;padding:5rem;">
        <i class="fas fa-pills" style="font-size:3.5rem;opacity:.2;display:block;margin-bottom:1rem;"></i>
        <h3 style="color:var(--text-muted);">No Scheduled Medications</h3>
        <p style="color:var(--text-muted);font-size:1.3rem;margin-top:.5rem;">All clear for the current shift in <?= e($ward_assigned) ?>.</p>
    </div>
    <?php else: foreach($meds as $m):
        $is_high_alert = preg_match('/(Insulin|Warfarin|Heparin|Digoxin|Morphine|Dopamine)/i', $m['medicine_name']);
        $is_male   = strtolower($m['gender'] ?? '') === 'male';
        $av_bg     = $is_male ? 'linear-gradient(135deg,#2F80ED,#56CCF2)' : 'linear-gradient(135deg,#FF6B6B,#FF8E53)';
        $inv_cls   = $m['status'] === 'Administered' ? 'inv-paid' : ($m['is_overdue'] ? 'inv-overdue' : 'inv-pending');
        $badge_cls = $m['status'] === 'Administered' ? 'success' : ($m['is_overdue'] ? 'danger' : 'warning');
        $status_label = $m['is_overdue'] ? 'OVERDUE' : strtoupper($m['status']);
        $med_cat   = $m['status'] === 'Administered' ? 'administered' : ($m['is_overdue'] ? 'overdue' : 'pending');
    ?>
    <div class="inv-card <?= $inv_cls ?> med-row" data-cat="<?= $med_cat ?>">
        <div class="inv-card-header">
            <!-- Left: Time + Patient Avatar -->
            <div style="display:flex;align-items:center;gap:1.5rem;flex:1;flex-wrap:wrap;">
                <!-- Scheduled Time Badge -->
                <div class="ov-appt-date-badge" style="<?= $m['is_overdue'] ? 'background:var(--danger-gradient)' : ($m['status']==='Administered' ? 'background:var(--success-gradient)' : '') ?>">
                    <div class="day" style="font-size:1.4rem;"><?= date('H:i', strtotime($m['scheduled_time'])) ?></div>
                    <div class="mon"><?= $m['is_overdue'] ? 'late' : date('d M', strtotime($m['scheduled_time'])) ?></div>
                </div>
                <!-- Patient Avatar + Info -->
                <div style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:<?= $av_bg ?>;color:#fff;font-size:1.5rem;flex-shrink:0;">
                    <i class="fas <?= $is_male ? 'fa-mars' : 'fa-venus' ?>"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-size:1.5rem;font-weight:700;color:var(--text-primary);"><?= e($m['patient_name']) ?></div>
                    <div style="font-size:1.15rem;color:var(--text-muted);display:flex;gap:1rem;flex-wrap:wrap;margin-top:.2rem;">
                        <span><i class="fas fa-door-open"></i> <?= e($m['ward']) ?> Â· Bed <?= e($m['bed_number']) ?></span>
                        <span style="font-family:monospace;">#<?= e($m['patient_id']) ?></span>
                    </div>
                </div>
            </div>
            <!-- Center: Medication Detail -->
            <div style="flex:1;padding:0 1rem;">
                <div style="font-size:1.4rem;font-weight:800;color:var(--text-primary);display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                    <?= e($m['medicine_name']) ?>
                    <?php if($is_high_alert): ?><span class="adm-badge adm-badge-danger" style="font-size:.85rem;"><i class="fas fa-skull"></i> HIGH ALERT</span><?php endif; ?>
                </div>
                <div style="display:flex;gap:.8rem;margin-top:.5rem;flex-wrap:wrap;">
                    <span class="vital-chip"><i class="fas fa-capsules" style="color:var(--primary);"></i> <?= e($m['dosage']) ?></span>
                    <span class="vital-chip"><i class="fas fa-directions" style="color:var(--info);"></i> <?= e($m['route']) ?></span>
                </div>
            </div>
            <!-- Right: Status + Action -->
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.8rem;flex-shrink:0;">
                <span class="adm-badge adm-badge-<?= $badge_cls ?>" style="font-size:1.1rem;font-weight:700;"><?= $status_label ?></span>
                <?php if($m['status'] === 'Administered' && $m['administered_at']): ?>
                <div style="font-size:1.05rem;color:var(--text-muted);">Given <?= date('H:i', strtotime($m['administered_at'])) ?></div>
                <?php endif; ?>
                <?php if($m['status'] === 'Pending'): ?>
                <button class="btn btn-primary btn-sm" onclick="openAdministerModal(<?= $m['admin_pk'] ?>, '<?= e(addslashes($m['patient_name'])) ?>', '<?= e(addslashes($m['medicine_name'])) ?>', '<?= e(addslashes($m['dosage'])) ?>', '<?= e(addslashes($m['route'])) ?>')">
                    <span class="btn-text"><i class="fas fa-syringe"></i> Verify & Give</span>
                </button>
                <?php else: ?>
                <button class="btn btn-ghost btn-sm" disabled style="opacity:.5;">
                    <span class="btn-text"><i class="fas fa-lock"></i> Archived</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>

<script>
function filterMeds(el, cat) {
    document.querySelectorAll('#medFilters .ftab').forEach(f => f.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.med-row').forEach(r => {
        r.style.display = (cat === 'all' || r.dataset.cat === cat) ? '' : 'none';
    });
}
</script>

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
            <button class="btn btn-primary modal-close" onclick="closeAdministerModal()" type="button" style="color:#fff; opacity:0.8;"><span class="btn-text">Ã—</span></button>
        </div>
        
        <div style="padding:2.5rem;">
            <!-- Safety Alert -->
            <div style="background:rgba(231,76,60,0.05); border:1px solid rgba(231,76,60,0.15); border-radius:12px; padding:1.2rem 1.5rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.2rem;">
                <i class="fas fa-shield-alt text-danger" style="font-size:2rem;"></i>
                <div>
                    <strong style="color:var(--danger); font-size:1.2rem; display:block; margin-bottom:.2rem;">SAFETY PROTOCOL: THE 5 RIGHTS</strong>
                    <p style="margin:0; font-size:1.1rem; color:var(--text-secondary); font-weight:600;">Right Patient Â· Right Drug Â· Right Dose Â· Right Route Â· Right Time</p>
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
                            setTimeout(() => window.location.href = '?tab=medications', 1500);
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

