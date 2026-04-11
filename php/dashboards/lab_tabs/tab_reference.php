<?php
// ============================================================
// LAB DASHBOARD - TAB REFERENCE RANGES (Module 5)
// ============================================================
if (!isset($user_id)) { exit; }

$cat_query = mysqli_query($conn, "SELECT id, test_name, category FROM lab_test_catalog WHERE is_active = 1 ORDER BY category, test_name");

$query = "SELECT r.*, c.test_name, c.category 
          FROM lab_reference_ranges r
          JOIN lab_test_catalog c ON r.test_catalog_id = c.id
          ORDER BY c.test_name, r.parameter_name, r.age_min_years";
$ref_res = mysqli_query($conn, $query);

function genderBadge($g) {
    if($g==='Male') return '<span class="adm-badge" style="background:var(--primary-light);color:var(--primary);"><i class="fas fa-mars"></i> Male</span>';
    if($g==='Female') return '<span class="adm-badge" style="background:#fce4ec;color:#c2185b;"><i class="fas fa-venus"></i> Female</span>';
    return '<span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);"><i class="fas fa-venus-mars"></i> Both</span>';
}
?>

<div class="sec-header">
    <h2 style="font-size: 1.8rem; font-weight: 700;"><i class="fas fa-sliders-h"></i> Reference Range Management</h2>
    <div style="display:flex; gap:1rem;">
        <button class="btn btn-success" onclick="newRangeModal()"><span class="btn-text"><i class="fas fa-plus-circle"></i> Add Reference Range</span></button>
    </div>
</div>

<div class="info-card">
    <div class="adm-table-wrap">
        <table id="referenceTable" class="adm-table display">
            <thead>
                <tr>
                    <th>Test / Parameter</th>
                    <th>Category</th>
                    <th>Demographic</th>
                    <th>Normal Range</th>
                    <th>Critical Low</th>
                    <th>Critical High</th>
                    <th>Unit</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($ref_res)): ?>
                <tr>
                    <td>
                        <div style="font-weight: 700; color: var(--text-primary); font-size: 1.1rem;"><?= e($row['test_name']) ?></div>
                        <div style="color: var(--primary); font-size: 0.9rem; font-weight: 600;"><?= e($row['parameter_name']) ?></div>
                    </td>
                    <td><span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary);"><?= e(ucfirst($row['category'])) ?></span></td>
                    <td>
                        <div style="margin-bottom: 0.3rem;"><?= genderBadge($row['gender']) ?></div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;"><?= $row['age_min_years'] ?> - <?= $row['age_max_years'] ?> Yrs</div>
                    </td>
                    <td><span style="font-weight: 700; color: var(--success); font-size: 1.1rem;"><?= e($row['normal_min']) ?> - <?= e($row['normal_max']) ?></span></td>
                    <td><span style="font-weight: 700; color: var(--danger);"><?= e($row['critical_low']) ?: '-' ?></span></td>
                    <td><span style="font-weight: 700; color: var(--danger);"><?= e($row['critical_high']) ?: '-' ?></span></td>
                    <td><span style="font-weight: 600; color: var(--text-secondary);"><?= e($row['unit']) ?></span></td>
                    <td>
                        <button class="btn btn-primary btn btn-sm" style="background:var(--surface-2); color:var(--text-primary);" onclick="editRange(<?= $row['id'] ?>)" title="Edit Range"><span class="btn-text"><i class="fas fa-edit"></i> Edit</span></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit / Add Range Modal -->
<div class="modal fade" id="rangeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg); border:none; box-shadow:0 15px 35px rgba(0,0,0,0.2);">
            <div class="modal-header" style="border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                <h5 class="modal-title" style="font-weight:700; font-size:1.4rem;"><i class="fas fa-sliders-h" style="color:var(--role-accent); margin-right:.5rem;"></i> Configure Reference Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body" style="padding:2rem;">
                <input type="hidden" id="range_id" value="0">
                <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Target Test <span style="color:var(--danger);">*</span></label>
                        <select id="range_test_id" class="form-select" style="font-size:1.1rem; padding:.8rem;">
                            <?php 
                            mysqli_data_seek($cat_query, 0);
                            while($c = mysqli_fetch_assoc($cat_query)): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['test_name']) ?> (<?= ucfirst($c['category']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Parameter Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="range_param" class="form-control" placeholder="e.g. Hemoglobin" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Gender</label>
                        <select id="range_gender" class="form-select" style="font-size:1.1rem; padding:.8rem;">
                            <option value="Both">Both</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Age Min (Yrs)</label>
                        <input type="number" id="range_age_min" class="form-control" value="0" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Age Max (Yrs)</label>
                        <input type="number" id="range_age_max" class="form-control" value="150" style="font-size:1.1rem; padding:.8rem;">
                    </div>
                </div>

                <div style="background:var(--surface-2); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 1.5rem;">
                    <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group mb-0">
                            <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; font-weight:600;">Normal Min <span style="color:var(--danger);">*</span></label>
                            <input type="text" id="range_norm_min" class="form-control" placeholder="e.g. 13.0" style="font-size:1.1rem; padding:.8rem;">
                        </div>
                        <div class="form-group mb-0">
                            <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; font-weight:600;">Normal Max <span style="color:var(--danger);">*</span></label>
                            <input type="text" id="range_norm_max" class="form-control" placeholder="e.g. 17.0" style="font-size:1.1rem; padding:.8rem;">
                        </div>
                    </div>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Critical Low Threshold</label>
                        <input type="text" id="range_crit_low" class="form-control" placeholder="e.g. < 7.0" style="font-size:1.1rem; padding:.8rem; border-left: 4px solid var(--danger);">
                    </div>
                    <div class="form-group mb-0">
                        <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Critical High Threshold</label>
                        <input type="text" id="range_crit_high" class="form-control" placeholder="e.g. > 20.0" style="font-size:1.1rem; padding:.8rem; border-left: 4px solid var(--danger);">
                    </div>
                </div>
                
                <div class="form-group mb-0">
                    <label style="font-size:1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block;">Measurement Unit <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="range_unit" class="form-control" placeholder="e.g. g/dL, x10^9/L" style="font-size:1.1rem; padding:.8rem;">
                </div>

            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border); padding:1.5rem 2rem;">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><span class="btn-text">Cancel</span></button>
                <button type="button" class="btn btn-primary" onclick="saveRange()"><span class="btn-text"><i class="fas fa-save"></i> Save Configuration</span></button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#referenceTable').DataTable({
        pageLength: 25,
        language: { search: "", searchPlaceholder: "Search parameters or tests..." }
    });
});

function newRangeModal() {
    $('#range_id').val('0');
    $('#range_param, #range_norm_min, #range_norm_max, #range_unit, #range_crit_low, #range_crit_high').val('');
    $('#range_age_min').val('0'); $('#range_age_max').val('150');
    new bootstrap.Modal(document.getElementById('rangeModal')).show();
}

function editRange(id) {
    Swal.fire({
        title: 'Loading Data...',
        text: 'Fetching range configuration for ID: ' + id,
        icon: 'info',
        showConfirmButton: false,
        timer: 800,
        didOpen: () => { Swal.showLoading(); }
    }).then(() => {
        // Mocking the data load
        $('#range_id').val(id);
        new bootstrap.Modal(document.getElementById('rangeModal')).show();
    });
}

function saveRange() {
    const data = {
        test_id: $('#range_test_id').val(),
        param: $('#range_param').val().trim(),
        min: $('#range_norm_min').val().trim(),
        max: $('#range_norm_max').val().trim(),
        unit: $('#range_unit').val().trim()
    };

    if(!data.param || !data.min || !data.max || !data.unit) {
        return Swal.fire('Error', 'Please fill in all mandatory fields (*)', 'error');
    }

    Swal.fire({
        title: 'Save Configuration?',
        text: "Updating reference ranges will affect historical interpretation flagging.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary)',
        confirmButtonText: 'Yes, Save it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Success', 'Configuration saved successfully (Backend link pending)', 'success');
            setTimeout(() => location.reload(), 1000);
        }
    });
}
</script>
