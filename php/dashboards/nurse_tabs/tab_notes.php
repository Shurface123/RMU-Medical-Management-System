<?php
// ============================================================
// NURSE DASHBOARD - NURSING NOTES (MODULE 5)
// ============================================================
if (!isset($conn)) exit;

$shift_q = mysqli_query($conn, "SELECT id, ward_assigned FROM nurse_shifts WHERE nurse_id=$nurse_pk AND shift_date='$today' AND status='Active' LIMIT 1");
$current_shift = mysqli_fetch_assoc($shift_q);
$ward_assigned = $current_shift['ward_assigned'] ?? 'All Wards';
$shift_pk      = $current_shift['id'] ?? null;

$patients_in_ward = [];
$q_pw = mysqli_query($conn, "
    SELECT p.id, p.patient_id, u.name 
    FROM patients p 
    JOIN users u ON p.user_id = u.id 
    JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status='Occupied'
    JOIN beds b ON ba.bed_id = b.id
    WHERE b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."'
    ORDER BY u.name ASC
");
if ($q_pw) { while($r = mysqli_fetch_assoc($q_pw)) $patients_in_ward[] = $r; }

$notes = [];
$q_str = "
    SELECT nn.id AS note_pk, nn.note_id, nn.note_type, nn.note_content, nn.is_locked, nn.created_at,
        p.patient_id, u.name AS patient_name, u.gender,
        n_author.full_name AS author_name, b.ward, b.bed_number
    FROM nursing_notes nn
    JOIN patients p ON nn.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n_author ON nn.nurse_id = n_author.id
    LEFT JOIN bed_assignments ba ON p.id = ba.patient_id AND ba.status = 'Occupied'
    LEFT JOIN beds b ON ba.bed_id = b.id
";
if ($ward_assigned !== 'All Wards' && $ward_assigned !== 'Not Assigned') {
    $q_str .= " WHERE (b.ward = '".mysqli_real_escape_string($conn, $ward_assigned)."' OR nn.nurse_id = $nurse_pk)";
}
$q_str .= " ORDER BY nn.created_at DESC LIMIT 50";
$q_notes = mysqli_query($conn, $q_str);
if ($q_notes) { while ($r = mysqli_fetch_assoc($q_notes)) $notes[] = $r; }
?>

<div class="tab-content active" id="notes">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.3rem;"><i class="fas fa-clipboard-list text-primary"></i> Nursing Notes &amp; Observations</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Real-time clinical documentation and patient progress tracking.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <div style="background:var(--surface-2); border:1px solid var(--border); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
                <i class="fas fa-file-medical text-muted" style="font-size:1.4rem;"></i>
                <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><?= count($notes) ?> <small style="font-weight:500; font-size:1rem; color:var(--text-muted);">Recent Entries</small></div>
            </div>
            <?php if($shift_pk): ?>
                <button class="adm-btn adm-btn-primary" onclick="openNoteModal()" style="padding:.8rem 2rem; border-radius:12px; font-weight:700; box-shadow:0 4px 12px rgba(var(--primary-rgb), 0.3);">
                    <i class="fas fa-plus"></i> New Note
                </button>
            <?php else: ?>
                <button class="adm-btn adm-btn-ghost" disabled title="Active shift required" style="padding:.8rem 2rem; border-radius:12px; opacity:0.6;">
                    <i class="fas fa-lock"></i> Add Note
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Notes Feed Container -->
    <div class="adm-card shadow-sm">
        <div class="adm-card-header" style="justify-content:space-between; border-bottom:1.5px solid var(--border);">
            <h3 style="font-size:1.5rem; font-weight:700;"><i class="fas fa-history text-primary"></i> Ward Clinical Timeline</h3>
            <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); font-weight:700;">LIVE FEED · <?= strtoupper($ward_assigned) ?></span>
        </div>
        <div class="adm-card-body" style="padding:0;">
            <?php if(empty($notes)): ?>
                <div style="height:400px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:1.5rem; text-align:center;">
                    <div style="width:100px; height:100px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:4rem; color:var(--text-muted); opacity:0.3;">
                        <i class="fas fa-file-signature"></i>
                    </div>
                    <div>
                        <h3 style="font-weight:700; color:var(--text-primary); margin-bottom:.5rem;">No Clinical Records Found</h3>
                        <p style="font-size:1.3rem; color:var(--text-muted);">Documentation for patients in this ward will appear here in chronological order.</p>
                    </div>
                </div>
            <?php else: ?>                <?php foreach($notes as $n):
                    $accent_color = 'var(--primary)';
                    $bg_soft = 'rgba(var(--primary-rgb), 0.05)';
                    switch($n['note_type']) {
                        case 'Wound Care':  $accent_color = 'var(--danger)';  $bg_soft = 'rgba(231,76,60,0.05)'; break;
                        case 'Assessment':  $accent_color = 'var(--success)'; $bg_soft = 'rgba(46,204,113,0.05)'; break;
                        case 'Incident':    $accent_color = 'var(--warning)'; $bg_soft = 'rgba(241,196,15,0.05)'; break;
                        case 'Handover':    $accent_color = '#9b59b6';         $bg_soft = 'rgba(155,89,182,0.05)'; break;
                    }
                ?>
                <div class="activity-item" style="padding:2.5rem; border-bottom:1.5px solid var(--border); transition:background .2s ease; cursor:default;" onmouseover="this.style.background='var(--surface-2)';" onmouseout="this.style.background='transparent';">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                        <div style="display:flex; align-items:center; gap:1.5rem;">
                            <!-- Patient Badge -->
                            <div style="width:48px; height:48px; border-radius:12px; background:<?= $n['gender']=='Male' ? 'rgba(52, 152, 219, 0.1)' : 'rgba(231, 76, 60, 0.1)' ?>; color:<?= $n['gender']=='Male' ? 'var(--primary)' : 'var(--danger)' ?>; display:flex; align-items:center; justify-content:center; font-size:1.8rem; font-weight:800; border:1px solid rgba(0,0,0,0.05);">
                                <?= strtoupper(substr($n['patient_name'],0,1)) ?>
                            </div>
                            <div>
                                <div style="font-weight:800; font-size:1.5rem; color:var(--text-primary); display:flex; align-items:center; gap:.8rem;">
                                    <?= e($n['patient_name']) ?>
                                    <span class="adm-badge" style="font-family:monospace; font-weight:700;">#<?= e($n['patient_id']) ?></span>
                                </div>
                                <div style="font-size:1.2rem; color:var(--text-muted); font-weight:600; margin-top:.2rem;">
                                    <i class="fas fa-map-marker-alt"></i> <?= e($n['ward'] ?: 'Emergency') ?> · Bed <?= e($n['bed_number'] ?: 'N/A') ?>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:1.15rem; font-weight:700; color:var(--text-muted); margin-bottom:.5rem;">
                                <i class="far fa-clock"></i> <?= date('d M Y, H:i', strtotime($n['created_at'])) ?>
                            </div>
                            <div style="display:flex; gap:.6rem; justify-content:flex-end; align-items:center;">
                                <span class="adm-badge" style="background:<?= $bg_soft ?>; color:<?= $accent_color ?>; border:1px solid <?= $accent_color ?>; font-weight:800; padding:.4rem 1rem;">
                                    <i class="fas fa-tag"></i> <?= strtoupper(e($n['note_type'])) ?>
                                </span>
                                <?php if($n['is_locked']): ?>
                                    <i class="fas fa-shield-alt text-success" style="font-size:1.4rem;" title="Clinically Verified & Locked"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Clinical Content -->
                    <div style="background:#fff; border-radius:12px; border:1.5px solid var(--border); border-left:4px solid <?= $accent_color ?>; padding:1.8rem; position:relative;">
                        <div style="font-family:'Georgia', serif; font-size:1.45rem; line-height:1.75; color:var(--text-primary); white-space:pre-wrap;"><?= e($n['note_content']) ?></div>
                        
                        <div style="margin-top:1.5rem; padding-top:1.2rem; border-top:1px dashed var(--border); display:flex; justify-content:space-between; align-items:center;">
                            <div style="display:flex; align-items:center; gap:.8rem;">
                                <div style="width:24px; height:24px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; font-size:1rem; color:var(--text-muted);">
                                    <i class="fas fa-user-nurse"></i>
                                </div>
                                <span style="font-weight:700; font-size:1.15rem; color:var(--text-secondary);">Author: <?= e($n['author_name']) ?></span>
                            </div>
                            <small style="font-family:monospace; color:var(--text-muted); opacity:0.6;">ENTRY_ID: <?= e($n['note_id']) ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>iv>

<!-- ══════════════════════════════════════════════ -->
<!-- MODAL: ADD NURSING NOTE (Native Admin Style)   -->
<!-- ══════════════════════════════════════════════ -->
<!-- ========================================== -->
<!-- MODAL: DOCUMENT CLINICAL NOTE              -->
<!-- ========================================== -->
<div class="modal-bg" id="newNoteModal">
    <div class="modal-box" style="max-width:680px; border:none; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
        <div class="modal-header" style="background:var(--primary); padding:1.8rem 2.5rem;">
            <h3 style="color:#fff; font-size:1.8rem; font-weight:800; letter-spacing:-0.01em; margin:0;"><i class="fas fa-file-signature" style="margin-right:.8rem;"></i> Document Clinical Note</h3>
            <button class="modal-close" onclick="closeNoteModal()" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        
        <div style="padding:2.5rem;">
            <form id="noteForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_note">
                <input type="hidden" name="shift_id" value="<?= $shift_pk ?>">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; margin-bottom:2rem;">
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Subject Patient</label>
                        <select class="form-control" name="patient_id" required style="font-weight:600; padding:.8rem;">
                            <option value="">— Select Patient —</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['patient_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($patients_in_ward)): ?>
                            <small style="color:var(--danger); font-weight:700; display:block; margin-top:.5rem;"><i class="fas fa-exclamation-triangle"></i> No patients in assigned ward.</small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Note Category</label>
                        <select class="form-control" name="note_type" required style="font-weight:600; padding:.8rem;">
                            <option value="General">General Progress</option>
                            <option value="Assessment">Clinical Assessment</option>
                            <option value="Observation">Nursing Observation</option>
                            <option value="Wound Care">Wound/Skin Care</option>
                            <option value="Handover">Shift Handover</option>
                            <option value="Incident">Clinical Incident</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.15rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem;">Clinical Documentation</label>
                    <textarea class="form-control" name="note_content" rows="6" placeholder="Enter objective findings, interventions, and evaluations using professional clinical language..." required style="resize:vertical; font-family:'Georgia', serif; font-size:1.3rem; padding:1.2rem;"></textarea>
                </div>

                <div style="background:rgba(241,196,15,0.05); border:1px solid rgba(241,196,15,0.2); border-radius:12px; padding:1.2rem 1.5rem; margin-bottom:2rem; display:flex; align-items:center; gap:1.2rem;">
                    <i class="fas fa-shield-alt text-warning" style="font-size:1.8rem;"></i>
                    <div style="font-size:1.1rem; color:var(--text-secondary); font-weight:600;">
                        <strong style="color:var(--text-primary);">Audit Notification:</strong> Verified clinical notes are immutable. Please ensure accuracy before final submission.
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="closeNoteModal()" style="font-weight:600;">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary" id="btnSaveNote" <?= empty($patients_in_ward) ? 'disabled' : '' ?> style="padding:.8rem 2.5rem; font-weight:700; border-radius:12px; box-shadow:0 4px 12px rgba(var(--primary-rgb), 0.3);">
                        <i class="fas fa-check-circle"></i> Complete &amp; Sign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNoteModal() {
    document.getElementById('noteForm').reset();
    document.getElementById('newNoteModal').style.display = 'flex';
}
function closeNoteModal() {
    document.getElementById('newNoteModal').style.display = 'none';
}

$(document).ready(function() {
    $('#noteForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Finalize Clinical Entry?',
            text: "This note will be permanently logged under your name and cannot be modified later.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary)',
            confirmButtonText: 'Yes, Sign & Save'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#btnSaveNote');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Authenticating...');
                
                $.ajax({
                    url: '../nurse/process_notes.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if(res.success) {
                            Swal.fire({ icon: 'success', title: 'Note Logged!', text: 'Clinical record has been secured.', timer: 1500, showConfirmButton: false });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Submission Failed', text: res.message });
                            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Complete & Sign');
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'System Error', text: 'Loss of communication with Clinical Data Server.' });
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Complete & Sign');
                    }
                });
            }
        });
    });
});
</script>

