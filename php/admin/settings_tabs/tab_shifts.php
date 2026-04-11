<!-- Shifts & Schedule Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-clock"></i> Shift & Schedule Configuration</h2>
        <span class="badge bg-primary">Operations</span>
    </div>

    <form id="shiftsForm" onsubmit="event.preventDefault(); saveSettings('shiftsForm', 'save_shift_types');">
        <div class="grid-2">
            <?php
            $shifts = mysqli_query($conn, "SELECT * FROM shift_types");
            while($s = mysqli_fetch_assoc($shifts)):
            ?>
            <div class="card" style="padding: 1.5rem; border: 1px solid var(--border); border-left: 5px solid <?= $s['color_code'] ?>;">
                <div style="font-weight: 700; margin-bottom: 1rem; display: flex; justify-content: space-between;">
                    <span><?= $s['shift_name'] ?> Shift</span>
                    <input type="color" name="shifts[<?= $s['id'] ?>][color]" value="<?= $s['color_code'] ?>" style="width:30px; border:none; background:none; cursor:pointer;">
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <div class="form-group" style="flex: 1;">
                        <label>Start Time</label>
                        <input type="time" name="shifts[<?= $s['id'] ?>][start]" class="form-control" value="<?= $s['start_time'] ?>" required>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>End Time</label>
                        <input type="time" name="shifts[<?= $s['id'] ?>][end]" class="form-control" value="<?= $s['end_time'] ?>" required>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save Shift Schedule
            </span></button>
        </div>
    </form>

    <hr style="margin: 3rem 0; opacity: 0.1;">

    <!-- Leave & Availability Settings -->
    <div class="settings-card-header">
        <h2 class="settings-card-title" style="font-size: 1.2rem;"><i class="fas fa-umbrella-beach"></i> Global Leave Policies</h2>
    </div>
    <div class="grid-2">
        <div class="form-group">
            <label>Max Consecutive Leave Days</label>
            <input type="number" class="form-control" value="14">
        </div>
        <div class="form-group">
            <label>Emergency Leave Notifications</label>
            <select class="form-control">
                <option>Admin & Head of Dept</option>
                <option>Admin Only</option>
            </select>
        </div>
    </div>
</div>
