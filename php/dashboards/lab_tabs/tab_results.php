<?php
// ============================================================
// LAB DASHBOARD - TAB RESULTS (Module 4)
// ============================================================
if (!isset($user_id)) { exit; }

$filter = $_GET['filter_status'] ?? 'All';

// Query orders that are in 'Processing' meaning they are ready for results,
// OR existing results in the lab_results table
$ready_for_entry_q = mysqli_query($conn, "SELECT o.id, o.urgency, o.patient_id, o.created_at, c.test_name, p.full_name AS patient_name 
                                          FROM lab_test_orders o 
                                          JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                                          JOIN patients p ON o.patient_id = p.id
                                          WHERE o.status = 'Processing' AND 
                                                o.id NOT IN (SELECT order_id FROM lab_results WHERE result_status != 'Draft')");

$results_q = mysqli_query($conn, "SELECT r.*, o.urgency, c.test_name, p.full_name AS patient_name 
                                  FROM lab_results r
                                  JOIN lab_test_orders o ON r.order_id = o.id
                                  JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                                  JOIN patients p ON o.patient_id = p.id
                                  ORDER BY r.created_at DESC");

function getResultStatusBadge($s) {
    if($s==='Draft') return '<span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary); border: 1px solid var(--border);">Draft</span>';
    if($s==='Pending Validation') return '<span class="adm-badge adm-badge-warning">Pending Validation</span>';
    if($s==='Validated') return '<span class="adm-badge adm-badge-primary">Validated</span>';
    if($s==='Released') return '<span class="adm-badge adm-badge-success">Released</span>';
    if($s==='Amended') return '<span class="adm-badge adm-badge-danger">Amended</span>';
    return '<span class="adm-badge">'.e($s).'</span>';
}
?>

<div class="sec-header">
    <h2><i class="fas fa-file-medical-alt"></i> Lab Result Entry & Validation</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-primary" onclick="newResultModal()"><i class="fas fa-plus"></i> Enter New Result</button>
    </div>
</div>

<div class="cards-grid">
    <!-- Queue: Ready for Entry -->
    <div class="info-card" style="border-left: 4px solid var(--primary);">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-flask"></i> Awaiting Result Entry</h3>
        <table class="adm-table" style="font-size: 1.1rem;">
            <thead><tr><th>Order ID</th><th>Test</th><th>Patient</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($r = mysqli_fetch_assoc($ready_for_entry_q)): ?>
                    <tr>
                        <td>#ORD-<?= str_pad($r['id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($r['test_name']) ?></td>
                        <td><?= e($r['patient_name']) ?></td>
                        <td><button class="adm-btn adm-btn-primary adm-btn-sm" onclick="enterResult(<?= $r['id'] ?>, '<?= e($r['test_name']) ?>', <?= $r['patient_id'] ?>)">Enter</button></td>
                    </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($ready_for_entry_q) === 0): ?>
                    <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No orders ready for result entry.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Active Results -->
    <div class="info-card" style="grid-column: span 2;">
        <h3 style="margin-bottom: 1rem;"><i class="fas fa-list-ul"></i> Result Ledger</h3>
        <div class="adm-table-wrap">
            <table class="adm-table" id="resultsTable" style="font-size: 1.1rem;">
                <thead>
                    <tr>
                        <th>Result ID</th>
                        <th>Order</th>
                        <th>Patient</th>
                        <th>Test Name</th>
                        <th>Interpretation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($results_q)): ?>
                    <tr>
                        <td><strong>#RES-<?= str_pad($row['result_id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                        <td>#ORD-<?= str_pad($row['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($row['patient_name']) ?></td>
                        <td><?= e($row['test_name']) ?></td>
                        <td>
                            <?php 
                                if($row['result_interpretation'] === 'Normal') echo '<span class="adm-badge adm-badge-success">Normal</span>';
                                else if($row['result_interpretation'] === 'Abnormal') echo '<span class="adm-badge adm-badge-warning">Abnormal</span>';
                                else if($row['result_interpretation'] === 'Critical') echo '<span class="adm-badge adm-badge-danger">Critical</span>';
                                else echo '-';
                            ?>
                        </td>
                        <td><?= getResultStatusBadge($row['result_status']) ?></td>
                        <td>
                            <div class="action-btns">
                                <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="viewResult(<?= $row['result_id'] ?>)"><i class="fas fa-eye"></i></button>
                                <?php if($row['result_status'] === 'Draft'): ?>
                                    <button class="adm-btn adm-btn-primary adm-btn-sm" title="Submit for Validation" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Pending Validation')"><i class="fas fa-paper-plane"></i></button>
                                <?php elseif($row['result_status'] === 'Pending Validation'): ?>
                                    <button class="adm-btn adm-btn-success adm-btn-sm" title="Validate Result" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Validated')"><i class="fas fa-check-double"></i></button>
                                <?php elseif($row['result_status'] === 'Validated'): ?>
                                    <button class="adm-btn adm-btn-teal adm-btn-sm" title="Release to Doctor" onclick="updateResultStatus(<?= $row['result_id'] ?>, 'Released')"><i class="fas fa-share-square"></i></button>
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

<!-- Enter Result Modal Placeholder -->
<div class="modal fade" id="enterResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title"><i class="fas fa-microscope" style="color:var(--role-accent);"></i> Enter Results: <span id="modal_test_name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-muted); font-size: 0.9em; margin-bottom: 1.5rem;">The system will automatically flag Normal/Low/High/Critical Low/Critical High values based on configured reference ranges.</p>
                <input type="hidden" id="entry_order_id">
                
                <!-- Dynamic Parameter Form (Mocked for UI purposes) -->
                <div id="dynamic_parameters_container">
                    <div class="form-row" style="align-items: center; background: var(--surface-2); padding: 1rem; border-radius: 8px;">
                        <div style="flex: 1;">
                            <label style="font-size: 0.95em; font-weight:600; color:var(--text-primary);">Hemoglobin (Hb)</label>
                            <small style="display:block; color:var(--text-muted);">Ref: 13.0 - 17.0 g/dL</small>
                        </div>
                        <div style="flex: 1;">
                            <input type="number" step="0.1" class="form-control" placeholder="Value" oninput="checkFlag(this, 13.0, 17.0, 'Hemoglobin (Hb)')">
                        </div>
                        <div style="flex: 1; text-align: center;">
                            <span class="flag-badge" style="padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border); color:var(--text-muted);">No Flag</span>
                        </div>
                    </div>
                </div>

                <!-- Phase 8: Statistical Anomaly Acknowledgment Gate -->
                <div id="anomaly_ack_container" style="display:none; margin-top:1.5rem; padding: 1rem; background: rgba(241,196,15,0.1); border-left: 4px solid var(--warning); border-radius: 4px;">
                    <div style="color:var(--warning); font-weight:600; margin-bottom: 0.5rem;"><i class="fas fa-brain"></i> AI ANOMALY DETECTED</div>
                    <p style="font-size:0.9em; margin-bottom:0.5rem;" id="anomaly_message"></p>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="anomaly_ack_check">
                        <label class="form-check-label" for="anomaly_ack_check" style="font-weight:500; font-size: 0.9em;">
                            I verify this statistical deviation is medically accurate and not a processing error.
                        </label>
                    </div>
                </div>

                <!-- Phase 5: Critical Value Acknowledgment Gate -->
                <div id="crit_ack_container" style="display:none; margin-top:1.5rem; padding: 1rem; background: rgba(231,76,60,0.1); border-left: 4px solid var(--danger); border-radius: 4px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="crit_ack_check">
                        <label class="form-check-label" for="crit_ack_check" style="color:var(--danger); font-weight:600; font-size: 0.9em; text-transform:none;">
                            <i class="fas fa-exclamation-triangle"></i> CRITICAL VALUE DETECTED: I verify that I have notified the prescribing doctor.
                        </label>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <label>Technician Comments</label>
                    <textarea class="form-control" rows="3" placeholder="Optional comments, interpretation notes, or methodology..."></textarea>
                </div>
                
                <div class="form-group mt-3">
                    <label>Upload Instrument PDF (Optional)</label>
                    <input type="file" class="form-control" accept=".pdf">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" onclick="saveResult('Draft')">Save as Draft</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#resultsTable').DataTable({
        pageLength: 10,
        language: { search: "", searchPlaceholder: "Search results..." }
    });
});

function enterResult(order_id, test_name, patient_id) {
    $('#modal_test_name').text(test_name);
    $('#entry_order_id').val(order_id);
    
    // Inject hidden patient_id if not present
    if($('#entry_patient_id').length === 0) {
        $('<input>').attr({type: 'hidden', id: 'entry_patient_id'}).appendTo('#enterResultModal .modal-body');
    }
    $('#entry_patient_id').val(patient_id);
    
    // Reset flags
    $('#anomaly_ack_container').hide();
    $('#anomaly_ack_check').prop('checked', false);
    $('#crit_ack_container').hide();
    $('#crit_ack_check').prop('checked', false);
    
    // Fetch test parameters dynamically...
    // (Existing placeholder logic assumes dynamic loading is handled elsewhere or is hardcoded for Phase UI)

    new bootstrap.Modal(document.getElementById('enterResultModal')).show();
}

let anomalyCheckTimer;
function checkFlag(input, min, max, paramName) {
    let val = parseFloat(input.value);
    let badge = $(input).closest('.form-row').find('.flag-badge');
    if (isNaN(val)) {
        badge.css({background: 'transparent', color: 'var(--text-muted)', borderColor: 'var(--border)'}).text('No Flag');
        $('#crit_ack_container').slideUp();
        return;
    }
    
    let isCritical = false;
    let critMin = min * 0.7; // 30% lower threshold
    let critMax = max * 1.3; // 30% over threshold
    
    if (val <= critMin) {
        badge.css({background: '#8b0000', color: 'white', borderColor: '#8b0000'}).text('Critical Low');
        isCritical = true;
    } else if (val < min) {
        badge.css({background: 'var(--warning)', color: 'white', borderColor: 'var(--warning)'}).text('Low');
    } else if (val >= critMax) {
        badge.css({background: '#8b0000', color: 'white', borderColor: '#8b0000'}).text('Critical High');
        isCritical = true;
    } else if (val > max) {
        badge.css({background: 'var(--warning)', color: 'white', borderColor: 'var(--warning)'}).text('High');
    } else {
        badge.css({background: 'var(--success)', color: 'white', borderColor: 'var(--success)'}).text('Normal');
    }

    if (isCritical) {
        $('#crit_ack_container').slideDown();
    } else {
        $('#crit_ack_container').slideUp();
    }

    // Phase 8: Statistical Anomaly Detection (Debounced)
    clearTimeout(anomalyCheckTimer);
    anomalyCheckTimer = setTimeout(() => {
        let patient_id = $('#entry_patient_id').val();
        let param_name_clean = paramName || $(input).closest('.form-row').find('label').text().trim();
        
        $.ajax({
            url: 'lab_actions.php',
            type: 'POST',
            data: { 
                action: 'check_historical_anomaly', 
                patient_id: patient_id, 
                parameter_name: param_name_clean, 
                current_value: val, 
                csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            dataType: 'json',
            success: function(res) {
                if(res.success && res.is_anomaly) {
                    $('#anomaly_message').html(`<strong>${param_name_clean}</strong> value is <strong>${res.deviation_percent}%</strong> ${res.direction} than this patient's historical baseline (Mean: ${res.mean}, SD: ${res.sd}).`);
                    $('#anomaly_ack_container').slideDown();
                }
            }
        });
    }, 800);
}

function saveResult(mode) {
    if ($('#anomaly_ack_container').is(':visible') && !$('#anomaly_ack_check').is(':checked')) {
        alert("SECURITY GATE: You must acknowledge the statistical anomaly warning before saving.");
        return;
    }
    if ($('#crit_ack_container').is(':visible') && !$('#crit_ack_check').is(':checked')) {
        alert("SECURITY GATE: You must acknowledge notifying the doctor about the critical value before proceeding.");
        return;
    }
    alert("This action will serialize the parameter values to JSON and save them to the `lab_results` table with status: " + mode);
}

function updateResultStatus(result_id, new_status) {
    if(!confirm("Change status to " + new_status + "?")) return;
    $.ajax({
        url: 'lab_actions.php',
        type: 'POST',
        data: { action: 'update_result_status', result_id: result_id, status: new_status, csrf_token: '<?= $csrf_token ?>' },
        dataType: 'json',
        success: function(res) {
            if(res.success) location.reload();
            else alert("Error: " + res.message);
        }
    });
}
</script>
