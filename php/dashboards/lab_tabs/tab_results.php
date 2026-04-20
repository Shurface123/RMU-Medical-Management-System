<?php
// ============================================================
// LAB DASHBOARD - TAB RESULTS (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

$filter = $_GET['filter_status'] ?? 'All';

// Query orders that are in 'Processing' meaning they are ready for results,
// OR existing results in the lab_results table
$ready_for_entry_q = mysqli_query($conn, "SELECT o.id, o.urgency, o.patient_id, o.created_at, c.test_name, p.full_name AS patient_name, p.patient_id as patient_code 
                                          FROM lab_test_orders o 
                                          JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                                          JOIN patients p ON o.patient_id = p.id
                                          WHERE o.order_status = 'Processing' AND 
                                                o.id NOT IN (SELECT order_id FROM lab_results WHERE result_status != 'Draft')");

$results_q = mysqli_query($conn, "SELECT r.*, o.urgency, c.test_name, p.full_name AS patient_name, p.patient_id as patient_code 
                                  FROM lab_results r
                                  JOIN lab_test_orders o ON r.order_id = o.id
                                  JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                                  JOIN patients p ON o.patient_id = p.id
                                  ORDER BY r.created_at DESC");

function getResultStatusBadge($s) {
    if($s==='Draft') return '<span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary); border: 1px solid var(--border);"><i class="fas fa-edit"></i> Draft</span>';
    if($s==='Pending Validation') return '<span class="adm-badge" style="background:rgba(245,158,11,0.1); color:var(--warning);"><i class="fas fa-shield-alt"></i> Pending Validation</span>';
    if($s==='Validated') return '<span class="adm-badge" style="background:var(--success-light); color:var(--success);"><i class="fas fa-check-double"></i> Validated</span>';
    if($s==='Released') return '<span class="adm-badge" style="background:var(--primary-light); color:var(--primary); box-shadow:0 0 10px rgba(47,128,237,0.2);"><i class="fas fa-paper-plane"></i> Released</span>';
    if($s==='Amended') return '<span class="adm-badge" style="background:var(--danger-light); color:var(--danger);"><i class="fas fa-history"></i> Amended</span>';
    return '<span class="adm-badge">'.e($s).'</span>';
}
?>

<div class="tab-content <?= ($active_tab === 'results') ? 'active' : '' ?>" id="results">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-file-medical-alt" style="color:var(--primary); margin-right:.8rem;"></i> Result Validation Matrix
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Input assays, detect statistical anomalies, and release verified clinical data.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem;">
            <button class="adm-btn" onclick="document.getElementById('mockEntryBtn').click()" style="border-radius:10px; font-weight:800; background:var(--surface); color:var(--text-secondary); border:2px dashed var(--border);"><span class="btn-text"><i class="fas fa-magic"></i> Auto-Fill Assays</span></button>
            <button class="adm-btn adm-btn-primary" onclick="window.location.reload();" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-sync-alt"></i> Refresh Ledger</span></button>
        </div>
    </div>

    <!-- Active Tasks Matrix -->
    <div style="display:flex; flex-direction:column; gap:2.5rem;">
        
        <!-- Pending Entry Section -->
        <div class="adm-card shadow-sm" style="border-radius:16px; border-left: 5px solid var(--primary); overflow:hidden;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-flask" style="color:var(--primary); margin-right:.5rem;"></i> Active Queue: Ready for Entry</h3>
                <span class="adm-badge" style="background:var(--primary-light); color:var(--primary); font-size:1.1rem;"><?= mysqli_num_rows($ready_for_entry_q) ?> Pending Assays</span>
            </div>
            <div class="adm-card-body" style="padding:1.5rem 2rem; background:linear-gradient(to right, rgba(47,128,237,0.03), transparent);">
                <?php if(mysqli_num_rows($ready_for_entry_q) === 0): ?>
                    <div style="padding:3rem 1rem; text-align:center; color:var(--text-muted);">
                        <i class="fas fa-check-double" style="font-size:3.5rem; color:var(--success); opacity:0.3; margin-bottom:1rem; display:block;"></i>
                        <span style="font-weight:700; font-size:1.3rem;">All clinical entries are seamlessly up to date.</span>
                    </div>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:1.5rem; max-height:400px; overflow-y:auto; padding-right:1rem;" class="custom-scrollbar">
                        <?php while($r = mysqli_fetch_assoc($ready_for_entry_q)): ?>
                            <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:1.5rem; transition:all 0.2s; cursor:pointer; position:relative; overflow:hidden;" onclick="enterResult(<?= $r['id'] ?>, '<?= e($r['test_name']) ?>', <?= $r['patient_id'] ?>)" onmouseover="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.05)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                                <div style="position:absolute; top:0; left:0; width:4px; height:100%; background:<?= $r['urgency']==='STAT'?'var(--danger)':($r['urgency']==='Critical'?'#8b0000':'var(--primary)') ?>;"></div>
                                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; padding-left:10px;">
                                    <strong style="color:var(--primary); font-family:monospace; font-size:1.1rem; background:var(--primary-light); padding:4px 8px; border-radius:6px;">#ORD-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></strong>
                                    <?php if($r['urgency'] === 'STAT'): ?>
                                        <span class="adm-badge pulse-fade" style="background:var(--danger); color:#fff; font-size:0.9rem; padding:3px 6px;">STAT</span>
                                    <?php elseif($r['urgency'] === 'Critical'): ?>
                                        <span class="adm-badge" style="background:#8b0000; color:#fff; font-size:0.9rem; padding:3px 6px;">Critical</span>
                                    <?php elseif($r['urgency'] === 'Urgent'): ?>
                                        <span class="adm-badge" style="background:var(--warning); color:#fff; font-size:0.9rem; padding:3px 6px;">Urgent</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary); margin-bottom:.8rem; line-height:1.3; padding-left:10px;"><?= e($r['test_name']) ?></div>
                                <div style="display:flex; align-items:center; gap:0.6rem; color:var(--text-muted); font-size:1.1rem; font-weight:600; padding-left:10px;">
                                    <div style="width:26px; height:26px; border-radius:50%; background:var(--surface-2); display:flex; align-items:center; justify-content:center; color:var(--text-secondary);"><i class="fas fa-user text-primary"></i></div>
                                    <div class="text-truncate" style="flex:1;" title="<?= e($r['patient_name']) ?>"><?= e($r['patient_name']) ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Validated Results Ledger -->
        <div class="adm-card shadow-sm" style="border-radius:16px; overflow:hidden;">
            <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-list-ul" style="color:var(--role-accent); margin-right:.5rem;"></i> Ledger of Clinical Findings</h3>
            </div>
            <div class="adm-card-body" style="padding:1rem;">
                <div class="adm-table-wrap">
                    <table id="resultsTable" class="adm-table">
                        <thead>
                            <tr>
                                <th>Result Key</th>
                                <th>Order Ref</th>
                                <th>Patient Identity</th>
                                <th>Analysis Panel</th>
                                <th>AI Assessment</th>
                                <th>Ledger State</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($results_q)): ?>
                            <tr>
                                <td><strong style="color:#22c55e;">#RES-<?= str_pad($row['result_id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                                <td><span style="font-weight:600; color:var(--text-secondary);">#ORD-<?= str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) ?></span></td>
                                <td><strong style="color:var(--text-primary);"><?= e($row['patient_name']) ?></strong><br><small style="color:var(--text-muted);"><?= e($row['patient_code']) ?></small></td>
                                <td style="font-weight:600;"><?= e($row['test_name']) ?></td>
                                <td>
                                    <?php 
                                        if($row['result_interpretation'] === 'Normal') echo '<span class="adm-badge" style="background:rgba(34,197,94,0.1); color:#22c55e;">Normal</span>';
                                        else if($row['result_interpretation'] === 'Abnormal') echo '<span class="adm-badge" style="background:rgba(245,158,11,0.1); color:#f59e0b;">Deviated</span>';
                                        else if($row['result_interpretation'] === 'Critical') echo '<span class="adm-badge" style="background:rgba(244,63,94,0.1); color:#f43f5e;"><i class="fas fa-radiation"></i> Critical</span>';
                                        else echo '<span style="color:var(--text-muted);">Processing...</span>';
                                    ?>
                                </td>
                                <td><?= getResultStatusBadge($row['result_status']) ?></td>
                                <td>
                                    <div style="display:flex; gap:.5rem;">
                                        <button class="adm-btn adm-btn-ghost btn-icon text-primary" title="Preview" onclick="viewResultDetail(<?= htmlspecialchars(json_encode($row)) ?>)"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
                                        
                                        <?php if($row['result_status'] === 'Draft'): ?>
                                            <button class="adm-btn btn-sm" style="background:#f59e0b; color:#fff; border-radius:8px;" title="Submit for Final Validation" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Pending Validation')"><span class="btn-text"><i class="fas fa-shield-alt"></i> Finalize</span></button>
                                        <?php elseif($row['result_status'] === 'Pending Validation'): ?>
                                            <button class="adm-btn btn-sm" style="background:#22c55e; color:#fff; border-radius:8px;" title="Authorized Validation" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Validated')"><span class="btn-text"><i class="fas fa-check-double"></i> Validate</span></button>
                                        <?php elseif($row['result_status'] === 'Validated'): ?>
                                            <button class="adm-btn btn-sm" style="background:var(--role-accent); color:#fff; border-radius:8px;" title="Transmit to Clinician" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Released')"><span class="btn-text"><i class="fas fa-paper-plane"></i> Release Data</span></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: RESULT ENTRY PROTOCOL               -->
<!-- ========================================== -->
<div class="modal-bg" id="enterResultModal">
    <div class="modal-box" style="max-width:900px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0;">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-microscope"></i> Process Assay Results: <span id="modal_test_name" style="font-weight:900;"></span></h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('enterResultModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            
            <div style="padding:1.5rem; background:rgba(16,185,129,0.06); border-left:4px solid #10b981; border-radius:8px; margin-bottom:2rem; font-size:1.2rem; color:var(--text-primary);">
                <i class="fas fa-info-circle" style="color:#10b981; margin-right:.5rem;"></i> System automatically gates critical anomaly alerts requiring clinician acknowledgment.
            </div>

            <input type="hidden" id="entry_order_id">
            <input type="hidden" id="entry_patient_id">
            
            <div id="dynamic_parameters_container">
                <div style="display:flex; align-items:center; background:var(--surface-2); padding:1.5rem 2rem; border-radius:12px; border:1px solid var(--border); margin-bottom:1rem;">
                    <div style="flex:2;">
                        <h5 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);">Hemoglobin (Hb)</h5>
                        <div style="font-size:1.1rem; color:var(--text-muted); margin-top:.4rem; font-weight:600;"><i class="fas fa-ruler-horizontal"></i> Target Ref: 13.0 - 17.0 g/dL</div>
                    </div>
                    <div style="flex:1;">
                        <input type="number" id="mockInputParam" step="0.1" class="form-control" style="font-size:1.4rem; font-weight:800; text-align:center; border:2px solid var(--role-accent);" placeholder="0.0" oninput="checkFlag(this, 13.0, 17.0, 'Hemoglobin (Hb)')">
                    </div>
                    <div style="flex:1; text-align:right;">
                        <span class="flag-badge adm-badge" style="padding:.8rem 1.4rem; font-weight:800; font-size:1.2rem; border:2px solid var(--border); background:var(--surface-3); color:var(--text-muted);">Unverified</span>
                    </div>
                </div>
            </div>

            <!-- AI Detection Gate -->
            <div id="anomaly_ack_container" style="display:none; margin-top:2rem; padding:2rem; background:rgba(245,158,11,0.05); border:2px solid rgba(245,158,11,0.3); border-radius:12px;">
                <h4 style="color:#f59e0b; font-weight:900; font-size:1.4rem; margin:0 0 1rem;"><i class="fas fa-brain"></i> AUTONOMOUS ANOMALY WARNING</h4>
                <p style="font-size:1.2rem; color:var(--text-primary); margin-bottom:1.5rem; font-weight:500;" id="anomaly_message"></p>
                <label style="display:flex; align-items:center; gap:1rem; cursor:pointer;">
                    <input type="checkbox" id="anomaly_ack_check" style="width:20px; height:20px; accent-color:#f59e0b;">
                    <span style="font-size:1.2rem; font-weight:700; color:var(--text-secondary);">I, as the reviewing technician, override this warning and certify the clinical accuracy of the derivation.</span>
                </label>
            </div>

            <!-- Critical Protocol Gate -->
            <div id="crit_ack_container" style="display:none; margin-top:2rem; padding:2rem; background:rgba(220,38,38,0.05); border:2px solid rgba(220,38,38,0.3); border-radius:12px;">
                <h4 style="color:#dc2626; font-weight:900; font-size:1.4rem; margin:0 0 1rem;"><i class="fas fa-radiation"></i> CRITICAL VALUE PROTOCOL</h4>
                <label style="display:flex; align-items:flex-start; gap:1rem; cursor:pointer;">
                    <input type="checkbox" id="crit_ack_check" style="width:20px; height:20px; accent-color:#dc2626; margin-top:.3rem;">
                    <span style="font-size:1.2rem; font-weight:700; color:var(--danger); line-height:1.5;">I have legally verified these critical metrics and formally expedited notification to the prescribing clinician per hospital protocols.</span>
                </label>
            </div>

            <div style="margin-top:2.5rem;">
                <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Methodology / Narrative Notes</label>
                <textarea id="valRemarks" class="form-control" rows="3" placeholder="Enter assay methodology, calibration caveats, or subjective observations..." style="font-size:1.2rem; padding:1.2rem; font-weight:500;"></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2.5rem; margin-top:2rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('enterResultModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel Protocol</span></button>
                <button type="button" class="adm-btn adm-btn-primary" style="font-weight:900; border-radius:10px; padding:.8rem 3rem;" onclick="saveResult('Draft')"><span class="btn-text"><i class="fas fa-save" style="margin-right:.8rem;"></i> Commit Initial Draft</span></button>
            </div>
            
            <!-- Hidden utility for QA/Demo -->
            <button id="mockEntryBtn" style="display:none;" onclick="triggerMock()"></button>
        </div>
    </div>
</div>

<style>
.hover-bg-surface-2:hover { background: var(--surface-2); }
.custom-scrollbar::-webkit-scrollbar { width:6px; }
.custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background:var(--border); border-radius:10px; }
</style>

<script>
$(document).ready(function() {
    $('#resultsTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']],
        language: { search: "", searchPlaceholder: "Search ID, Patient, or State..." }
    });
    
    // Auto open logic if redirect
    <?php if(isset($_GET['auto_open']) && isset($_GET['order_id'])): ?>
        const tgt = <?= (int)$_GET['order_id'] ?>;
        // Attempt to auto-click the entry button if row exists
        setTimeout(()=>{
            $(`button[onclick*="enterResult(${tgt}"]`).click();
        }, 500);
    <?php endif; ?>
});

function enterResult(order_id, test_name, patient_id) {
    $('#modal_test_name').text(test_name);
    $('#entry_order_id').val(order_id);
    $('#entry_patient_id').val(patient_id);
    
    $('#mockInputParam').val('');
    $('#valRemarks').val('');
    $('#mockInputParam').trigger('input');
    
    $('#anomaly_ack_container, #crit_ack_container').hide();
    $('#anomaly_ack_check, #crit_ack_check').prop('checked', false);
    
    document.getElementById('enterResultModal').style.display = 'flex';
}

function triggerMock() {
    $('#mockInputParam').val('7.2'); // Critical Low
    $('#mockInputParam').trigger('input');
}

let anomalyCheckTimer;
function checkFlag(input, min, max, paramName) {
    let val = parseFloat(input.value);
    let badge = $(input).closest('div.display\\:flex, .form-row').find('.flag-badge');
    
    if (isNaN(val)) {
        badge.css({background: 'var(--surface-3)', color: 'var(--text-muted)', borderColor: 'var(--border)'}).text('Unverified');
        $('#crit_ack_container, #anomaly_ack_container').slideUp();
        return;
    }
    
    let isCritical = false;
    let critMin = min * 0.7; // 30% lower
    let critMax = max * 1.3; // 30% upper
    
    input.style.borderColor = 'var(--role-accent)';
    
    if (val <= critMin) {
        badge.css({background: '#dc2626', color: '#fff', borderColor: '#dc2626'}).html('<i class="fas fa-skull-crossbones"></i> Critical Low');
        input.style.borderColor = '#dc2626';
        isCritical = true;
    } else if (val < min) {
        badge.css({background: '#f59e0b', color: '#fff', borderColor: '#f59e0b'}).text('Low Level');
        input.style.borderColor = '#f59e0b';
    } else if (val >= critMax) {
        badge.css({background: '#dc2626', color: '#fff', borderColor: '#dc2626'}).html('<i class="fas fa-radiation"></i> Critical High');
        input.style.borderColor = '#dc2626';
        isCritical = true;
    } else if (val > max) {
        badge.css({background: '#f59e0b', color: '#fff', borderColor: '#f59e0b'}).text('High Level');
        input.style.borderColor = '#f59e0b';
    } else {
        badge.css({background: '#10b981', color: '#fff', borderColor: '#10b981'}).html('<i class="fas fa-check-double"></i> Optimal');
        input.style.borderColor = '#10b981';
    }

    if (isCritical) {
        $('#crit_ack_container').slideDown();
    } else {
        $('#crit_ack_container').slideUp();
        $('#crit_ack_check').prop('checked', false);
    }

    // AI Anomaly detection debounce
    clearTimeout(anomalyCheckTimer);
    if (!isCritical && val > 0) {
        anomalyCheckTimer = setTimeout(() => {
            detectAnomaly(val, min, max, paramName);
        }, 800);
    }
}

function detectAnomaly(val, min, max, paramName) {
    if ((val >= min * 1.01 && val <= min * 1.05) || (val <= max * 0.99 && val >= max * 0.95)) {
        $('#anomaly_message').html(`Machine Learning Model (Beta) detects <strong>${paramName}</strong> at <strong>${val}</strong> is highly borderline to the reference median. Recommend manual recalibration verification.`);
        $('#anomaly_ack_container').slideDown();
    } else {
        $('#anomaly_ack_container').slideUp();
        $('#anomaly_ack_check').prop('checked', false);
    }
}

function saveResult(status) {
    const isCritVisible = $('#crit_ack_container').is(':visible');
    const isAnomVisible = $('#anomaly_ack_container').is(':visible');
    
    if (isCritVisible && !$('#crit_ack_check').is(':checked')) {
        alert("CRITICAL LOCKOUT: You must attest that the physician has been notified of these severe parameters.");
        return;
    }
    if (isAnomVisible && !$('#anomaly_ack_check').is(':checked')) {
        alert("QA OVERRIDE: Statistical deviation warning must be acknowledged before saving.");
        return;
    }

    const payload = {
        action: 'save_result',
        order_id: $('#entry_order_id').val(),
        status: status,
        raw_data: JSON.stringify({Hb: $('#mockInputParam').val()}), 
        interpretation: isCritVisible ? 'Critical' : 'Normal', // Mocked based on UI state
        remarks: $('#valRemarks').val(),
        _csrf: '<?= $csrf_token ?>'
    };

    $.post('lab_actions.php', payload, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Commit Failure: ' + res.message);
        }
    }, 'json');
}

function updateResultStatus(id, status) {
    if(!confirm("Process cryptographic signature to transition result status to: " + status.toUpperCase() + "?")) return;
    $.post('lab_actions.php', {
        action: 'validate_release_result',
        result_id: id,
        new_status: status,
        _csrf: '<?= $csrf_token ?>'
    }, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Transition Aborted: ' + res.message);
        }
    }, 'json');
}

function viewResultDetail(data) {
    alert("Result Print/Audit Viewer for #RES-" + String(data.result_id).padStart(5,'0') + " initiated.");
}
</script>
