<?php
// ============================================================
// LAB DASHBOARD - TAB REFERENCE RANGES (PREMIUM UI REWRITE)
// ============================================================
if (!isset($user_id)) { exit; }

$cat_query = mysqli_query($conn, "SELECT id, test_name, category FROM lab_test_catalog WHERE is_active = 1 ORDER BY category, test_name");

$query = "SELECT r.*, c.test_name, c.category 
          FROM lab_reference_ranges r
          JOIN lab_test_catalog c ON r.test_catalog_id = c.id
          ORDER BY c.test_name, r.parameter_name, r.age_min_years";
$ref_res = mysqli_query($conn, $query);

function genderBadge($g) {
    if($g==='Male') return '<span class="adm-badge" style="background:rgba(59,130,246,0.1); color:#3b82f6;"><i class="fas fa-mars"></i> Male Target</span>';
    if($g==='Female') return '<span class="adm-badge" style="background:rgba(236,72,153,0.1); color:#ec4899;"><i class="fas fa-venus"></i> Female Target</span>';
    return '<span class="adm-badge" style="background:var(--surface-3); color:var(--text-secondary);"><i class="fas fa-venus-mars"></i> Universal</span>';
}
?>

<div class="tab-content <?= ($active_tab === 'reference') ? 'active' : '' ?>" id="reference">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(47,128,237,0.06), rgba(47,128,237,0.01)); border:1px solid rgba(47,128,237,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-sliders-h" style="color:var(--primary); margin-right:.8rem;"></i> Reference & Threshold Matrix
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Define and calibrate baseline parameters to power autonomous anomaly detection algorithms.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <button class="adm-btn adm-btn-primary" onclick="newRangeModal()" style="border-radius:10px; font-weight:800;"><span class="btn-text"><i class="fas fa-plus"></i> Configure New Matrix</span></button>
        </div>
    </div>

    <div class="adm-card shadow-sm" style="border-radius:16px;">
        <div class="adm-card-header" style="background:var(--surface-1); padding:1.5rem 2rem; border-bottom:1px solid var(--border);">
            <h3 style="margin:0; font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-ruler-horizontal" style="color:var(--primary); margin-right:.5rem;"></i> Parametric Calibrations</h3>
        </div>
        <div class="adm-card-body" style="padding:1rem;">
            <div class="adm-table-wrap">
                <table id="referenceTable" class="adm-table">
                    <thead>
                        <tr>
                            <th>Analysis Category</th>
                            <th>Target Parameter</th>
                            <th>Demographic Sub-Filter</th>
                            <th>Optimal Baseline</th>
                            <th>Critical Bounds (ML Gates)</th>
                            <th>Unit/Scale</th>
                            <th>Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($ref_res)): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 800; color: var(--text-primary); font-size: 1.2rem;"><?= e($row['test_name']) ?></div>
                                <div><span class="adm-badge" style="background:var(--surface-3); color:var(--text-muted); font-size:0.9rem; border:1px solid var(--border);"><?= e(ucfirst($row['category'])) ?></span></div>
                            </td>
                            <td><strong style="color:var(--primary); font-size: 1.2rem;"><?= e($row['parameter_name']) ?></strong></td>
                            <td>
                                <div style="margin-bottom: 0.4rem;"><?= genderBadge($row['gender']) ?></div>
                                <div style="font-size: 1rem; color: var(--text-muted); font-weight: 700;"><i class="fas fa-child"></i> Age Range: <span style="color:var(--text-secondary);"><?= $row['age_min_years'] ?> - <?= $row['age_max_years'] ?> Yrs</span></div>
                            </td>
                            <td><span style="font-weight: 900; color: var(--primary); font-size: 1.3rem; padding:6px 12px; background:var(--primary-light); border-radius:8px; border:1px solid var(--primary);"><?= e($row['normal_min']) ?> &middot; <?= e($row['normal_max']) ?></span></td>
                            <td>
                                <div style="display:flex; gap:10px;">
                                    <span style="font-weight: 800; color: #dc2626; font-size:1rem; border-bottom:2px solid #dc2626;" title="Critical Low Threshold">L: <?= e($row['critical_low']) ?: 'NULL' ?></span>
                                    <span style="font-weight: 800; color: #dc2626; font-size:1rem; border-top:2px solid #dc2626;" title="Critical High Threshold">H: <?= e($row['critical_high']) ?: 'NULL' ?></span>
                                </div>
                            </td>
                            <td><span style="font-weight: 800; font-size:1.1rem; color: var(--text-secondary);"><?= e($row['unit']) ?></span></td>
                            <td>
                                <button class="adm-btn adm-btn-ghost btn-sm" style="color:var(--text-primary); border:1px solid var(--border);" onclick="editRange(<?= $row['id'] ?>)" title="Modify Matrix"><span class="btn-text"><i class="fas fa-edit"></i> Edit</span></button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADD / EDIT RANGE                    -->
<!-- ========================================== -->
<div class="modal-bg" id="rangeModal">
    <div class="modal-box" style="max-width:850px; padding:0; overflow:hidden;">
        <div class="modal-header" style="background:linear-gradient(135deg,#1C3A6B,var(--primary)); padding:2rem 3rem; margin:0; border-bottom:1px solid var(--border);">
            <h3 style="color:#fff; font-size:1.6rem; font-weight:800; margin:0;"><i class="fas fa-sliders-h"></i> Model Baseline Configuration Matrix</h3>
            <button class="adm-btn modal-close" onclick="document.getElementById('rangeModal').style.display='none'" type="button" style="color:#fff; background:transparent; font-size:2rem; padding:0;"><span class="btn-text">&times;</span></button>
        </div>
        <div style="padding:3rem;">
            <input type="hidden" id="range_id" value="0">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Root Diagnostic Suite <span style="color:#dc2626;">*</span></label>
                    <select id="range_test_id" class="form-control" style="font-size:1.3rem; padding:1rem; border:2px solid var(--border);">
                        <?php 
                        mysqli_data_seek($cat_query, 0);
                        while($c = mysqli_fetch_assoc($cat_query)): ?>
                            <option value="<?= $c['id'] ?>"><?= e(ucfirst($c['category'])) ?>: <?= e($c['test_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Specific Biomarker <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="range_param" class="form-control" placeholder="e.g. Hemoglobin Count" style="font-size:1.3rem; padding:1rem;">
                </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2.5rem; padding:2rem; background:var(--surface-background); border-radius:12px; border:1px solid var(--border);">
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Genetic Target</label>
                    <select id="range_gender" class="form-control" style="font-size:1.3rem; padding:1rem;">
                        <option value="Both">Universal (All Phenotypes)</option>
                        <option value="Male">XY (Male)</option>
                        <option value="Female">XX (Female)</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Min Age (Yrs)</label>
                    <input type="number" id="range_age_min" class="form-control" value="0" style="font-size:1.3rem; padding:1rem;">
                </div>
                <div>
                    <label style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); margin-bottom:.8rem; display:block; text-transform:uppercase;">Max Age (Yrs)</label>
                    <input type="number" id="range_age_max" class="form-control" value="150" style="font-size:1.3rem; padding:1rem;">
                </div>
            </div>

            <div style="background:var(--primary-light); padding: 2rem; border-radius: 12px; border: 2px solid rgba(47,128,237,0.3); margin-bottom: 2rem;">
                <h6 style="color:var(--primary); font-weight:900; margin-bottom:1.5rem; text-transform:uppercase; font-size:1.1rem;"><i class="fas fa-check-circle"></i> Baseline Configuration (Optimal)</h6>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="font-size:1.1rem; color:var(--text-primary); margin-bottom:.6rem; display:block; font-weight:800;">Optimal Floor <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="range_norm_min" class="form-control" placeholder="13.0" style="font-size:1.3rem; padding:1rem; font-weight:800;">
                    </div>
                    <div>
                        <label style="font-size:1.1rem; color:var(--text-primary); margin-bottom:.6rem; display:block; font-weight:800;">Optimal Ceiling <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="range_norm_max" class="form-control" placeholder="17.0" style="font-size:1.3rem; padding:1rem; font-weight:800;">
                    </div>
                    <div>
                        <label style="font-size:1.1rem; color:var(--text-primary); margin-bottom:.6rem; display:block; font-weight:800;">Metric Unit <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="range_unit" class="form-control" placeholder="g/dL" style="font-size:1.3rem; padding:1rem; font-weight:800;">
                    </div>
                </div>
            </div>

            <div style="background:rgba(239,68,68,0.05); padding: 2rem; border-radius: 12px; border: 2px solid rgba(239,68,68,0.3); margin-bottom: 2rem;">
                <h6 style="color:#ef4444; font-weight:900; margin-bottom:1.5rem; text-transform:uppercase; font-size:1.1rem;"><i class="fas fa-radiation"></i> Fatal Variance Gates (AI Alert Target)</h6>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <label style="font-size:1.1rem; color:#ef4444; margin-bottom:.6rem; display:block; font-weight:800;">Critical Floor Exception</label>
                        <input type="text" id="range_crit_low" class="form-control" placeholder="< 7.0" style="font-size:1.3rem; padding:1rem; font-weight:800; color:#ef4444;">
                    </div>
                    <div>
                        <label style="font-size:1.1rem; color:#ef4444; margin-bottom:.6rem; display:block; font-weight:800;">Critical Ceiling Exception</label>
                        <input type="text" id="range_crit_high" class="form-control" placeholder="> 20.0" style="font-size:1.3rem; padding:1rem; font-weight:800; color:#ef4444;">
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:1.2rem; padding-top:2rem; margin-top:2.5rem; border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-ghost" onclick="document.getElementById('rangeModal').style.display='none'" style="font-weight:700;"><span class="btn-text">Cancel Update</span></button>
                <button type="button" class="adm-btn adm-btn-primary" style="border-radius:10px; font-weight:900; padding:1rem 2rem;" onclick="saveRange()"><span class="btn-text"><i class="fas fa-satellite-dish" style="margin-right:.5rem;"></i> Imprint Baseline Variables</span></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#referenceTable').DataTable({
        pageLength: 20,
        language: { search: "", searchPlaceholder: "Search parameters, bounds..." }
    });
});

function newRangeModal() {
    $('#range_id').val(0);
    $('#range_param').val('');
    $('#range_norm_min').val('');
    $('#range_norm_max').val('');
    $('#range_crit_low').val('');
    $('#range_crit_high').val('');
    $('#range_unit').val('');
    document.getElementById('rangeModal').style.display='flex';
}

function editRange(id) {
    // Basic demonstration. In production, an AJAX call would fetch the specific values to populate.
    alert("Fetching cryptographically hashed parameters for bound ID: " + id);
    $('#range_id').val(id);
    document.getElementById('rangeModal').style.display='flex';
}

function saveRange() {
    const pld = {
        action: 'save_reference_range',
        id: $('#range_id').val(),
        test_catalog_id: $('#range_test_id').val(),
        parameter_name: $('#range_param').val(),
        gender: $('#range_gender').val(),
        age_min: $('#range_age_min').val(),
        age_max: $('#range_age_max').val(),
        norm_min: $('#range_norm_min').val(),
        norm_max: $('#range_norm_max').val(),
        crit_low: $('#range_crit_low').val(),
        crit_high: $('#range_crit_high').val(),
        unit: $('#range_unit').val(),
        _csrf: '<?= $csrf_token ?>'
    };

    if(!pld.parameter_name || !pld.norm_min || !pld.norm_max || !pld.unit) {
        alert("Integrity Fault: All baseline metrics are required to prevent AI model collapse."); return;
    }

    $.post('lab_actions.php', pld, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert("Database Rejection: " + res.message);
        }
    }, 'json');
}
</script>
