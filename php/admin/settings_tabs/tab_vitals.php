<!-- Vital Signs Thresholds Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-heartbeat"></i> Clinical Vital Sign Thresholds</h2>
        <span class="badge bg-warning">Clinically Critical</span>
    </div>

    <form id="vitalsForm" onsubmit="event.preventDefault(); saveSettings('vitalsForm', 'save_vital_thresholds');">
        <?php
        $categories = ['General', 'Adult', 'Pediatric', 'Elderly', 'Pregnant'];
        foreach ($categories as $cat):
            $v_res = mysqli_query($conn, "SELECT * FROM vital_thresholds WHERE patient_category = '$cat'");
            if (mysqli_num_rows($v_res) > 0):
        ?>
        <div style="margin-bottom: 3rem;">
            <h3 style="margin-bottom: 1.5rem; color: var(--primary); border-left: 4px solid var(--primary); padding-left: 1rem;"><?= $cat ?> Category</h3>
            <div class="table-responsive">
                <table class="table table-bordered" style="background: var(--surface);">
                    <thead class="bg-light">
                        <tr>
                            <th>Vital Sign</th>
                            <th>Unit</th>
                            <th>Min Normal</th>
                            <th>Max Normal</th>
                            <th>Crit. Low</th>
                            <th>Crit. High</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($v = mysqli_fetch_assoc($v_res)): ?>
                        <tr>
                            <td style="font-weight: 600;"><?= htmlspecialchars($v['display_name']) ?></td>
                            <td><code><?= htmlspecialchars($v['unit']) ?></code></td>
                            <td><input type="number" step="0.1" name="vitals[<?= $v['id'] ?>][min]" class="form-control form-control-sm" value="<?= $v['min_normal'] ?>"></td>
                            <td><input type="number" step="0.1" name="vitals[<?= $v['id'] ?>][max]" class="form-control form-control-sm" value="<?= $v['max_normal'] ?>"></td>
                            <td><input type="number" step="0.1" name="vitals[<?= $v['id'] ?>][low]" class="form-control form-control-sm" value="<?= $v['critical_low'] ?>"></td>
                            <td><input type="number" step="0.1" name="vitals[<?= $v['id'] ?>][high]" class="form-control form-control-sm" value="<?= $v['critical_high'] ?>"></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php 
            endif;
        endforeach; 
        ?>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem;">
            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()"><span class="btn-text">Reset Defaults</span></button>
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save Thresholds
            </span></button>
        </div>
    </form>
</div>
