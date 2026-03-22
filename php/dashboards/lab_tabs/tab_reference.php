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
    <h2><i class="fas fa-sliders-h"></i> Reference Range Management</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-success" onclick="newRangeModal()"><i class="fas fa-plus"></i> Add Reference Range</button>
    </div>
</div>

<div class="adm-table-wrap" style="background: var(--surface); padding: 1.5rem;">
    <table id="referenceTable" class="adm-table display" style="font-size: 1.05rem;">
        <thead>
            <tr>
                <th>Test / Parameter</th>
                <th>Category</th>
                <th>Demographic</th>
                <th>Normal Range</th>
                <th>Critical Low</th>
                <th>Critical High</th>
                <th>Unit</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($ref_res)): ?>
            <tr>
                <td>
                    <strong><?= e($row['test_name']) ?></strong><br>
                    <span style="color:var(--text-secondary);"><?= e($row['parameter_name']) ?></span>
                </td>
                <td><?= e(ucfirst($row['category'])) ?></td>
                <td>
                    <?= genderBadge($row['gender']) ?><br>
                    <small><?= $row['age_min_years'] ?> - <?= $row['age_max_years'] ?> yrs</small>
                </td>
                <td><strong style="color:var(--success);"><?= e($row['normal_min']) ?> - <?= e($row['normal_max']) ?></strong></td>
                <td><strong style="color:var(--danger);"><?= e($row['critical_low']) ?: '-' ?></strong></td>
                <td><strong style="color:var(--danger);"><?= e($row['critical_high']) ?: '-' ?></strong></td>
                <td><?= e($row['unit']) ?></td>
                <td><small><?= date('d M Y', strtotime($row['updated_at'])) ?></small></td>
                <td>
                    <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);" onclick="editRange(<?= $row['id'] ?>)"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Edit / Add Range Modal -->
<div class="modal fade" id="rangeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:var(--surface); color:var(--text-primary); border-radius:var(--radius-lg);">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title"><i class="fas fa-sliders-h" style="color:var(--role-accent);"></i> Configure Reference Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: var(--btn-close-filter);"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="range_id" value="0">
                <div class="form-row">
                    <div class="form-group">
                        <label>Target Test Catalog <span style="color:var(--danger);">*</span></label>
                        <select id="range_test_id" class="form-select">
                            <?php 
                            mysqli_data_seek($cat_query, 0);
                            while($c = mysqli_fetch_assoc($cat_query)): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['test_name']) ?> (<?= ucfirst($c['category']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parameter Name <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="range_param" class="form-control" placeholder="e.g. Hemoglobin, White Blood Cells">
                    </div>
                </div>

                <div class="form-row" style="grid-template-columns: 1fr 1fr 1fr;">
                    <div class="form-group">
                        <label>Gender</label>
                        <select id="range_gender" class="form-select">
                            <option value="Both">Both</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Age Min (Years)</label>
                        <input type="number" id="range_age_min" class="form-control" value="0">
                    </div>
                    <div class="form-group">
                        <label>Age Max (Years)</label>
                        <input type="number" id="range_age_max" class="form-control" value="150">
                    </div>
                </div>

                <div class="form-row" style="background:var(--surface-2); padding: 1rem; border-radius: 8px;">
                    <div class="form-group mb-0">
                        <label>Normal Min <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="range_norm_min" class="form-control" placeholder="e.g. 13.0">
                    </div>
                    <div class="form-group mb-0">
                        <label>Normal Max <span style="color:var(--danger);">*</span></label>
                        <input type="text" id="range_norm_max" class="form-control" placeholder="e.g. 17.0">
                    </div>
                </div>

                <div class="form-row mt-3">
                    <div class="form-group">
                        <label>Critical Low (Trigger Alert)</label>
                        <input type="text" id="range_crit_low" class="form-control" placeholder="e.g. < 7.0">
                    </div>
                    <div class="form-group">
                        <label>Critical High (Trigger Alert)</label>
                        <input type="text" id="range_crit_high" class="form-control" placeholder="e.g. > 20.0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Measurement Unit <span style="color:var(--danger);">*</span></label>
                    <input type="text" id="range_unit" class="form-control" placeholder="e.g. g/dL, x10^9/L">
                </div>

            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border);">
                <button type="button" class="adm-btn adm-btn-sm" style="background:var(--surface-2);" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="adm-btn adm-btn-primary adm-btn-sm" onclick="saveRange()">Save Configuration</button>
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
    alert('Loading data for Range ID: ' + id + ' via AJAX...');
    // Real implementation fills the form fields with AJAX data
    $('#range_id').val(id);
    new bootstrap.Modal(document.getElementById('rangeModal')).show();
}

function saveRange() {
    alert('Saving reference range configuration (requires backend action "save_reference_range" in lab_actions.php).');
}
</script>
