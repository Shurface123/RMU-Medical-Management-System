<?php
// ============================================================
// NURSE DASHBOARD - PATIENT EDUCATION & DISCHARGE (MODULE 9)
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

// ── FETCH RECENT EDUCATION LOGS ──────────────────────────────
$edu_logs = [];
$q_edu = mysqli_query($conn, "
    SELECT pe.*, u.name AS patient_name, u.gender, p.patient_id as pid, n.full_name as author_name
    FROM patient_education pe
    JOIN patients p ON pe.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n ON pe.nurse_id = n.id
    WHERE pe.nurse_id = $nurse_pk OR pe.recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY pe.recorded_at DESC LIMIT 30
");
if ($q_edu) {
    while($r = mysqli_fetch_assoc($q_edu)) $edu_logs[] = $r;
}

// ── FETCH RECENT DISCHARGE INSTRUCTIONS ──────────────────────
$discharge_logs = [];
$q_dis = mysqli_query($conn, "
    SELECT di.*, u.name AS patient_name, u.gender, p.patient_id as pid, n.full_name as author_name
    FROM discharge_instructions di
    JOIN patients p ON di.patient_id = p.id
    JOIN users u ON p.user_id = u.id
    JOIN nurses n ON di.nurse_id = n.id
    WHERE di.nurse_id = $nurse_pk OR di.given_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY di.given_at DESC LIMIT 30
");
if ($q_dis) {
    while($r = mysqli_fetch_assoc($q_dis)) $discharge_logs[] = $r;
}
?>

<div class="tab-content active" id="education">

    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-chalkboard-teacher pulse-fade"></i> Education & Discharge</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Coordinate clinical health teaching and professional discharge preparation.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
             <div style="background:rgba(var(--primary-rgb),0.05); border:1px solid rgba(var(--primary-rgb),0.1); padding:.8rem 1.5rem; border-radius:12px; display:flex; align-items:center; gap:1rem;">
                <span style="width:10px; height:10px; border-radius:50%; background:var(--primary); display:inline-block;"></span>
                <div style="font-size:1.4rem; font-weight:800; color:var(--text-primary);">Readiness <small style="font-weight:700; color:var(--primary);">OPTIMAL</small></div>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('eduForm').reset(); document.getElementById('eduModal').style.display='flex';" style="border-radius:12px; font-weight:700;">
                <i class="fas fa-book-medical"></i> Log Education
            </button>
            <button class="adm-btn adm-btn-ghost" onclick="document.getElementById('dischargeForm').reset(); document.getElementById('dischargeModal').style.display='flex';" style="border-radius:12px; font-weight:700; border-color:var(--primary); color:var(--primary);">
                <i class="fas fa-door-open"></i> Discharge Plan
            </button>
        </div>
    </div>

    <!-- Premium Sub-Navigation -->
    <div style="margin-bottom:2.5rem; border-bottom:2px solid var(--border); display:flex; gap:3rem;">
        <button class="tab-link active" onclick="switchEduTab('log')" id="btn-edu-tab" style="padding:1rem 0; font-weight:800; font-size:1.3rem; color:var(--primary); border-bottom:3px solid var(--primary); background:none; border-top:none; border-left:none; border-right:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;">
            <i class="fas fa-list-alt"></i> EDUCATION LOGS
        </button>
        <button class="tab-link" onclick="switchEduTab('disc')" id="btn-disc-tab" style="padding:1rem 0; font-weight:700; font-size:1.3rem; color:var(--text-muted); border-bottom:3px solid transparent; background:none; border-top:none; border-left:none; border-right:none; cursor:pointer; display:flex; align-items:center; gap:.8rem;">
            <i class="fas fa-file-export"></i> DISCHARGE INSTRUCTIONS
        </button>
    </div>

    <div class="tab-content active">
        
        <!-- Education Logs Panel -->
        <div id="log-content" class="edu-panel">
            <div class="adm-card shadow-sm">
                <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.4rem; font-weight:700; color:var(--primary);"><i class="fas fa-journal-whills"></i> Clinical Teaching History</h3>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if(empty($edu_logs)): ?>
                        <div style="padding:5rem; text-align:center; color:var(--text-muted);">
                            <i class="fas fa-book-open" style="font-size:4rem; opacity:0.15; margin-bottom:1.5rem; display:block;"></i>
                            <h5 style="font-size:1.6rem; font-weight:700; color:var(--text-primary);">No Recent Logs Found</h5>
                            <p style="font-size:1.2rem;">Health education teaching sessions recorded in the last 7 days will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div style="padding:0;">
                            <?php foreach($edu_logs as $e): 
                                $lvl_color = 'var(--success)';
                                $lvl_bg = 'rgba(46,204,113,0.1)';
                                if($e['understanding_level'] == 'Fair') { $lvl_color = 'var(--warning)'; $lvl_bg = 'rgba(241,196,15,0.1)'; }
                                elseif($e['understanding_level'] == 'Poor') { $lvl_color = 'var(--danger)'; $lvl_bg = 'rgba(231,76,60,0.1)'; }
                                elseif($e['understanding_level'] == 'Unable to Assess') { $lvl_color = 'var(--info)'; $lvl_bg = 'rgba(52,152,219,0.1)'; }
                            ?>
                                <div class="activity-item" style="padding:2rem 3rem; border-bottom:1.5px solid var(--border); transition:0.2s ease;">
                                    <div class="activity-dot shadow-sm" style="background:<?= $lvl_color ?>; width:14px; height:14px; left:23px; top:35px;"></div>
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem;">
                                            <div>
                                                <h5 style="font-size:1.6rem; font-weight:800; color:var(--text-primary); margin-bottom:.6rem;"><?= e($e['education_topic']) ?></h5>
                                                <div style="display:flex; gap:.8rem; flex-wrap:wrap; align-items:center;">
                                                    <span style="font-weight:700; color:var(--primary); background:rgba(var(--primary-rgb),0.08); padding:.4rem 1.2rem; border-radius:10px; font-size:1.1rem; text-transform:uppercase;"><i class="fas fa-user-injured"></i> <?= e($e['patient_name']) ?></span>
                                                    <span style="font-weight:600; color:var(--text-muted); background:var(--surface-3); padding:.4rem 1.2rem; border-radius:10px; font-size:1.1rem;"><i class="fas fa-laptop-medical"></i> <?= strtoupper(e($e['method'])) ?></span>
                                                    <span style="font-weight:800; color:<?= $lvl_color ?>; background:<?= $lvl_bg ?>; padding:.4rem 1.2rem; border-radius:10px; font-size:1rem; text-transform:uppercase;">UNDERSTANDING: <?= e($e['understanding_level']) ?></span>
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <div style="color:var(--text-muted); font-size:1.2rem; font-weight:700;"><?= date('d M, Y', strtotime($e['recorded_at'])) ?></div>
                                                <div style="color:var(--text-muted); font-size:1.1rem; opacity:0.6;"><?= date('H:i', strtotime($e['recorded_at'])) ?></div>
                                            </div>
                                        </div>

                                        <?php if($e['follow_up_notes']): ?>
                                            <div style="background:var(--surface-2); padding:1.5rem; border-radius:12px; margin:1rem 0; font-size:1.3rem; color:var(--text-primary); border-left:5px solid var(--primary); line-height:1.6;">
                                                <i class="fas fa-quote-left" style="color:var(--primary); opacity:0.3; font-size:1.5rem; margin-right:1rem;"></i>
                                                <?= nl2br(e($e['follow_up_notes'])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem;">
                                            <div style="display:flex; align-items:center; gap:1rem;">
                                                <div style="width:28px; height:28px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:.9rem;">
                                                    <i class="fas fa-user-nurse"></i>
                                                </div>
                                                <small style="color:var(--text-muted); font-size:1.1rem; font-weight:600;">Logged by: <strong style="color:var(--text-primary);"><?= e($e['author_name']) ?></strong></small>
                                            </div>
                                            <?php if($e['requires_follow_up']): ?>
                                                <span class="adm-badge" style="background:rgba(231,76,60,0.1); color:var(--danger); border:none; font-weight:800; font-size:1rem; padding:.5rem 1.5rem;"><i class="fas fa-exclamation-triangle pulse-animation"></i> NEEDS RETRAINING</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Discharge Instructions Tab -->
        <div id="disc-content" class="edu-panel" style="display:none;">
            <div class="adm-card shadow-sm">
                <div class="adm-card-header" style="background:rgba(var(--primary-rgb),0.02); border-bottom:1.5px solid var(--border);">
                    <h3 style="font-size:1.4rem; font-weight:700; color:var(--primary);"><i class="fas fa-file-invoice-dollar"></i> Formulated Discharge Plans</h3>
                </div>
                <div class="adm-card-body" style="padding:0;">
                    <?php if(empty($discharge_logs)): ?>
                        <div style="padding:5rem; text-align:center; color:var(--text-muted);">
                            <i class="fas fa-door-open" style="font-size:4rem; opacity:0.15; margin-bottom:1.5rem; display:block;"></i>
                            <h5 style="font-size:1.6rem; font-weight:700; color:var(--text-primary);">No Discharges Recorded</h5>
                            <p style="font-size:1.2rem;">Discharge instructions formulated in the last 7 days will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div style="padding:0;">
                            <?php foreach($discharge_logs as $d): ?>
                                <div class="activity-item" style="padding:2rem 3rem; border-bottom:1.5px solid var(--border);">
                                    <div class="activity-dot shadow-sm" style="background:<?= $d['gender']=='Male'?'var(--primary)':'var(--danger)' ?>; width:14px; height:14px; left:23px; top:35px;"></div>
                                    <div style="flex:1;">
                                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.8rem;">
                                            <div style="display:flex; align-items:center; gap:1.5rem;">
                                                <div style="width:48px; height:48px; border-radius:12px; background:rgba(var(--primary-rgb),0.1); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.6rem;">
                                                    <i class="fas fa-door-open"></i>
                                                </div>
                                                <div>
                                                    <h5 style="font-size:1.7rem; font-weight:800; color:var(--text-primary); margin:0;"><?= e($d['patient_name']) ?></h5>
                                                    <div style="font-size:1.1rem; color:var(--primary); font-weight:700; text-transform:uppercase; margin-top:.2rem;">ID: <?= e($d['pid']) ?></div>
                                                </div>
                                            </div>
                                            <div style="text-align:right;">
                                                <div style="color:var(--text-muted); font-size:1.2rem; font-weight:700;"><?= date('d M Y', strtotime($d['given_at'])) ?></div>
                                                <div style="margin-top:.5rem;">
                                                    <?php if($d['patient_acknowledged']): ?>
                                                        <span class="adm-badge" style="background:rgba(46,204,113,0.1); color:var(--success); border:none; font-weight:800; font-size:1rem;"><i class="fas fa-check-double"></i> ACKNOWLEDGED</span>
                                                    <?php else: ?>
                                                        <span class="adm-badge" style="background:rgba(241,196,15,0.1); color:var(--warning); border:none; font-weight:800; font-size:1rem;"><i class="fas fa-clock"></i> PENDING ACK.</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="background:var(--surface-1); padding:2rem; border-radius:12px; margin:1.2rem 0; font-family:'Menlo', 'Monaco', 'Courier New', monospace; font-size:1.25rem; border:1px dashed var(--border); line-height:1.7; color:var(--text-primary);">
                                            <div style="border-bottom:1px solid var(--border); padding-bottom:.8rem; margin-bottom:1.2rem; font-weight:800; color:var(--primary); display:flex; justify-content:space-between;">
                                                <span>DISCHARGE CONTENT</span>
                                                <i class="fas fa-print" style="cursor:pointer; opacity:0.5;"></i>
                                            </div>
                                            <?= nl2br(e($d['instruction_content'])) ?>
                                        </div>

                                        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem;">
                                            <div style="display:flex; align-items:center; gap:1rem;">
                                                <div style="width:28px; height:28px; border-radius:50%; background:var(--surface-3); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:.9rem;">
                                                    <i class="fas fa-user-check"></i>
                                                </div>
                                                <small style="color:var(--text-muted); font-size:1.1rem; font-weight:600;">Prepared by: <strong style="color:var(--text-primary);"><?= e($d['author_name']) ?></strong></small>
                                            </div>
                                            <?php if($d['notes']): ?><span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:none; font-weight:600; font-size:.9rem;"><i class="fas fa-info-circle"></i> <?= e($d['notes']) ?></span><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: LOG EDUCATION                       -->
<!-- ========================================== -->
<div class="modal-bg" id="eduModal">
    <div class="modal-box" style="max-width:700px;">
        <div class="modal-header" style="background:var(--primary);">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-book-medical"></i> Clinical Teaching Log</h3>
            <button class="modal-close" onclick="document.getElementById('eduModal').style.display='none'" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        <div style="padding:2.5rem;">
            <form id="eduForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_education">
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.8rem; margin-bottom:1.8rem;">
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Select Patient</label>
                        <select class="form-control" name="patient_id" required style="padding:.8rem; font-weight:800; font-size:1.3rem;">
                            <option value="">-- clinical subject --</option>
                            <?php foreach($patients_in_ward as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (Bed <?= e($p['bed_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Teaching Method</label>
                        <select class="form-control" name="method" required style="padding:.8rem; font-weight:600;">
                            <option value="Verbal">Verbal Instruction</option>
                            <option value="Written" selected>Written Materials (Leaflets)</option>
                            <option value="Demonstration">Return Demonstration</option>
                            <option value="Video">Multimedia Presentation</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Subject / Topic</label>
                    <input type="text" class="form-control" name="topic" placeholder="e.g. Diabeteic Foot Care, Insulin Administration..." required style="padding:.8rem; font-weight:700; font-size:1.3rem; color:var(--primary);">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.8rem; margin-bottom:1.8rem; align-items:center;">
                    <div class="form-group">
                        <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Understanding Level</label>
                        <select class="form-control" name="understanding" required style="padding:.8rem; font-weight:700; color:var(--success);">
                            <option value="Good">Excellent - Verbalized / Re-demonstrated</option>
                            <option value="Fair">Fair - Requires further reinforcement</option>
                            <option value="Poor">Poor - Retrospective teaching required</option>
                            <option value="Unable to Assess">Unable to assess (Clinical reasons)</option>
                        </select>
                    </div>
                    <div class="form-check" style="background:rgba(231,76,60,0.05); padding:1rem 1.5rem; border-radius:10px; border:1px solid rgba(231,76,60,0.1); display:flex; align-items:center; gap:1.5rem; margin-top:2rem;">
                        <input type="checkbox" name="needs_followup" id="chkFollow" value="1" style="width:20px; height:20px; cursor:pointer;">
                        <label for="chkFollow" style="font-weight:700; color:var(--danger); font-size:1.1rem; margin:0; cursor:pointer;">
                            Require Retraining / Follow-up
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Teaching Notes / Remarks</label>
                    <textarea class="form-control" name="notes" rows="3" placeholder="Document specific questions asked or patient reaction..." required style="padding:1rem; font-size:1.3rem; font-weight:500;"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('eduModal').style.display='none'" style="font-weight:700;">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary" id="btnSaveEdu" style="padding:.8rem 3.5rem; font-weight:900; border-radius:12px; font-size:1.2rem;">
                        <i class="fas fa-save" style="margin-right:.6rem;"></i> LOG TEACHING
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD DISCHARGE INSTRUCTIONS          -->
<!-- ========================================== -->
<div class="modal-bg" id="dischargeModal">
    <div class="modal-box" style="max-width:750px;">
        <div class="modal-header" style="background:linear-gradient(135deg, var(--primary), var(--primary-dark));">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-paper-plane"></i> Formulate Discharge Instructions</h3>
            <button class="modal-close" onclick="document.getElementById('dischargeModal').style.display='none'" type="button" style="color:#fff; opacity:0.8;">×</button>
        </div>
        <div style="padding:2.5rem;">
            <form id="dischargeForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="log_discharge">
                
                <div class="form-group" style="margin-bottom:1.8rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Select Patient for Discharge</label>
                    <select class="form-control" name="patient_id" required style="padding:.8rem; font-weight:800; font-size:1.4rem; border:1.5px solid var(--primary);">
                        <option value="">-- candidate for discharge --</option>
                        <?php foreach($patients_in_ward as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (Bed <?= e($p['bed_number']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:2rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Official Instruction Content</label>
                    <textarea class="form-control" name="content" rows="8" placeholder="Format clearly: 1. Medications 2. Follow-up 3. Danger Signs..." required style="padding:1.5rem; font-family:monospace; font-size:1.3rem; font-weight:600; background:rgba(var(--primary-rgb),0.02); line-height:1.6;"></textarea>
                    <div style="margin-top:.8rem; font-size:1rem; color:var(--text-muted); font-style:italic;">
                        <i class="fas fa-info-circle"></i> This text will be printed and provided to the patient as official clinical guidance.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:2.5rem;">
                    <label style="display:block; font-size:1.1rem; font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; text-transform:uppercase;">Internal Coordination Notes (Optional)</label>
                    <input type="text" class="form-control" name="notes" placeholder="e.g. Needs ambulance transport, family already briefed..." style="padding:.8rem; font-weight:600;">
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; border-top:1px solid var(--border);">
                    <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('dischargeModal').style.display='none'" style="font-weight:700;">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary" id="btnSaveDisc" style="padding:.8rem 4rem; font-weight:900; border-radius:12px; font-size:1.3rem;">
                        <i class="fas fa-door-open" style="margin-right:.8rem;"></i> FINALIZE DISCHARGE PLAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchEduTab(tab) {
    $('.tab-link').removeClass('active').css({'color': 'var(--text-muted)', 'border-bottom-color': 'transparent', 'font-weight': '700'});
    $('#btn-'+tab+'-tab').addClass('active').css({'color': 'var(--primary)', 'border-bottom-color': 'var(--primary)', 'font-weight': '800'});
    
    if(tab === 'log') {
        $('#log-content').show();
        $('#disc-content').hide();
    } else {
        $('#log-content').hide();
        $('#disc-content').show();
    }
}

$(document).ready(function() {
    $('#eduForm, #dischargeForm').on('submit', function(e) {
        e.preventDefault();
        const formId = $(this).attr('id');
        const isEdu = formId === 'eduForm';
        const btn = isEdu ? $('#btnSaveEdu') : $('#btnSaveDisc');
        const origHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing Clinical Data...');
        
        $.ajax({
            url: '../nurse/process_education.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: isEdu ? 'Teaching Logged' : 'Discharge Formulated',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Documentation Error',
                        text: res.message
                    });
                    btn.prop('disabled', false).html(origHtml);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'System Failure',
                    text: 'Unable to commit clinical documentation to server.'
                });
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });
});
</script>
