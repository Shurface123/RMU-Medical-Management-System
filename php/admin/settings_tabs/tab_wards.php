<!-- Departments & Wards Management Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-procedures"></i> Department & Ward Configuration</h2>
        <button class="btn btn-sm btn-primary" onclick="openModal('deptModal')"><i class="fas fa-plus"></i> New Department</button>
    </div>

    <!-- Departments List -->
    <div class="grid-2" style="margin-bottom: 3rem;">
        <?php
        $depts = mysqli_query($conn, "SELECT d.*, (SELECT COUNT(*) FROM wards w WHERE w.department_id = d.id) as ward_count FROM departments d");
        while($d = mysqli_fetch_assoc($depts)):
        ?>
        <div class="card" style="padding: 1.5rem; border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                <div>
                    <h4 style="margin: 0; color: var(--primary);"><?= htmlspecialchars($d['name']) ?></h4>
                    <small class="text-muted"><?= $d['ward_count'] ?> Wards Assigned</small>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" <?= $d['is_active']?'checked':'' ?>>
                </div>
            </div>
            <p style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-secondary);"><?= htmlspecialchars($d['description'] ?? 'No description.') ?></p>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-sm btn-outline-secondary">Edit</button>
                <button class="btn btn-sm btn-outline-primary" onclick="document.getElementById('ward_dept_id').value='<?= $d['id'] ?>'; openModal('wardModal');">Add Ward</button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <hr style="margin: 3rem 0; opacity: 0.1;">

    <!-- Wards Occupancy Visualizer -->
    <div class="settings-card-header">
        <h2 class="settings-card-title" style="font-size: 1.2rem;"><i class="fas fa-bed"></i> Current Ward Occupancy</h2>
    </div>
    
    <div class="grid-2">
        <?php
        $wards = mysqli_query($conn, "SELECT w.*, d.name as dept_name FROM wards w JOIN departments d ON w.department_id = d.id");
        while($w = mysqli_fetch_assoc($wards)):
            $occ_pct = $w['capacity'] > 0 ? ($w['current_occupancy'] / $w['capacity']) * 100 : 0;
            $color = $occ_pct > 90 ? 'var(--danger)' : ($occ_pct > 70 ? 'var(--warning)' : 'var(--success)');
        ?>
        <div class="card" style="padding: 1.5rem; border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <div>
                    <h5 style="margin: 0;"><?= htmlspecialchars($w['ward_name']) ?></h5>
                    <small class="badge bg-secondary"><?= htmlspecialchars($w['dept_name']) ?></small>
                </div>
                <div style="text-align: right;">
                    <span style="font-weight: 700; color: <?= $color ?>;"><?= $w['current_occupancy'] ?>/<?= $w['capacity'] ?></span>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Beds Occupied</div>
                </div>
            </div>
            <div style="height: 8px; background: var(--surface-2); border-radius: 10px; overflow: hidden;">
                <div style="width: <?= $occ_pct ?>%; height: 100%; background: <?= $color ?>; transition: width 0.5s ease;"></div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal Placeholders -->
<div id="deptModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--surface); margin:10% auto; padding:2rem; width:400px; border-radius:var(--radius-lg);">
        <h3>New Department</h3>
        <form id="deptForm" onsubmit="event.preventDefault(); saveSettings('deptForm', 'add_department');">
            <div class="form-group">
                <label>Department Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('deptModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<div id="wardModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--surface); margin:10% auto; padding:2rem; width:400px; border-radius:var(--radius-lg);">
        <h3>New Ward Assignment</h3>
        <form id="wardForm" onsubmit="event.preventDefault(); saveSettings('wardForm', 'add_ward');">
            <input type="hidden" name="department_id" id="ward_dept_id">
            <div class="form-group">
                <label>Ward Name</label>
                <input type="text" name="ward_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Bed Capacity</label>
                <input type="number" name="capacity" class="form-control" min="1" required>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('wardModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Ward</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'block'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
</script>
