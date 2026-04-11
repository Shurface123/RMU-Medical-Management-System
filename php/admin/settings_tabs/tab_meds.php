<!-- Medication Formulary Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-pills"></i> Medication Formulary & Drug Settings</h2>
        <button class="btn btn-sm btn-primary" onclick="openModal('medModal')"><span class="btn-text"><i class="fas fa-plus"></i> Add Medication</span></button>
    </div>

    <!-- Medications Filter & Stats -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--surface-2); border-radius: var(--radius-md);">
        <div class="card" style="flex: 1; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">
                <?php $mcount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM medicines"))['c']; echo $mcount; ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">Total Drugs</div>
        </div>
        <div class="card" style="flex: 1; padding: 1rem; text-align: center;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--danger);">
                <?php $ccount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM medicines WHERE is_controlled = 1"))['c']; echo $ccount; ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">Controlled Substances</div>
        </div>
        <div style="flex: 2; display: flex; align-items: center; padding: 0 1rem;">
            <input type="text" class="form-control" placeholder="Search medications..." onkeyup="filterMeds(this.value)">
        </div>
    </div>

    <!-- Medications Table -->
    <div class="table-responsive">
        <table class="table table-hover" id="medsTable">
            <thead>
                <tr>
                    <th>Medication Name</th>
                    <th>Category</th>
                    <th>Controlled</th>
                    <th>Stock Policy</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $meds = mysqli_query($conn, "SELECT * FROM medicines ORDER BY medicine_name ASC LIMIT 50");
                while($m = mysqli_fetch_assoc($meds)):
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($m['medicine_name']) ?></td>
                    <td><span class="badge bg-light text-dark"><?= htmlspecialchars($m['category'] ?? 'General') ?></span></td>
                    <td>
                        <?php if($m['is_controlled']): ?>
                            <span class="text-danger"><i class="fas fa-lock"></i> Controlled</span>
                        <?php else: ?>
                            <span class="text-success"><i class="fas fa-lock-open"></i> Standard</span>
                        <?php endif; ?>
                    </td>
                    <td>Min: <?= $m['reorder_level'] ?? '10' ?> units</td>
                    <td>
                        <span class="badge <?= ($m['status']??'active')=='active'?'bg-success':'bg-danger' ?>">
                            <?= ucfirst($m['status'] ?? 'active') ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-icon btn-outline-primary"><span class="btn-text"><i class="fas fa-edit"></i></span></button>
                        <button class="btn btn-sm btn-icon btn-outline-danger"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="medModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--surface); margin:10% auto; padding:2rem; width:500px; border-radius:var(--radius-lg);">
        <h3>New Medication Entry</h3>
        <form id="medForm" onsubmit="event.preventDefault(); saveSettings('medForm', 'add_medication');">
            <div class="form-group">
                <label>Medication Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Paracetamol 500mg" required>
            </div>
            <div class="form-group" style="margin-bottom: 2rem;">
                <label>Category</label>
                <select name="category" class="form-control">
                    <option value="Analgesic">Analgesic</option>
                    <option value="Antibiotic">Antibiotic</option>
                    <option value="Antipyretic">Antipyretic</option>
                    <option value="Antiviral">Antiviral</option>
                    <option value="Supplement">Supplement</option>
                </select>
            </div>
            <div class="form-group">
                <div class="form-check form-switch" style="font-size: 1.1rem;">
                    <input class="form-check-input" type="checkbox" name="is_controlled" id="ctrlSwitch">
                    <label class="form-check-label" for="ctrlSwitch">Controlled Substance (NARCOTIC/DD)</label>
                </div>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top: 2rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('medModal')"><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span class="btn-text">Add to Formulary</span></button>
            </div>
        </form>
    </div>
</div>

<script>
function filterMeds(val) {
    const rows = document.querySelectorAll('#medsTable tbody tr');
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(val.toLowerCase()) ? '' : 'none';
    });
}
</script>
