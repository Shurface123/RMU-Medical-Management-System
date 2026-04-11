<!-- Hospital Profile Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-hospital"></i> Hospital Profile Details</h2>
        <span class="badge bg-primary">Core Profile</span>
    </div>

    <form id="hospitalProfileForm" onsubmit="event.preventDefault(); saveSettings('hospitalProfileForm', 'save_hospital_profile');" enctype="multipart/form-data">
        <div class="grid-2">
            <!-- Basic Info -->
            <div class="form-group">
                <label>Sickbay / Hospital Name</label>
                <input type="text" name="hospital_name" class="form-control" value="<?= htmlspecialchars($hospital['hospital_name'] ?? 'RMU Medical Sickbay') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Official Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($hospital['email'] ?? 'sickbay@rmu.edu.gh') ?>" required>
            </div>

            <div class="form-group">
                <label>Website URL</label>
                <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($hospital['website'] ?? 'https://rmu.edu.gh') ?>">
            </div>

            <div class="form-group">
                <label>Accreditation Number</label>
                <input type="text" name="accreditation_number" class="form-control" value="<?= htmlspecialchars($hospital['accreditation_number'] ?? '') ?>" placeholder="e.g. HEFRA-2026-X">
            </div>

            <div class="form-group">
                <label>Facility License Number</label>
                <input type="text" name="license_number" class="form-control" value="<?= htmlspecialchars($hospital['license_number'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Facility Type</label>
                <select name="facility_type" class="form-control">
                    <option value="University Sickbay" <?= ($hospital['facility_type']??'')=='University Sickbay'?'selected':'' ?>>University Sickbay</option>
                    <option value="General Hospital" <?= ($hospital['facility_type']??'')=='General Hospital'?'selected':'' ?>>General Hospital</option>
                    <option value="Specialized Clinic" <?= ($hospital['facility_type']??'')=='Specialized Clinic'?'selected':'' ?>>Specialized Clinic</option>
                </select>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Physical Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($hospital['address'] ?? '') ?></textarea>
            </div>

            <!-- Logo Upload -->
            <div class="form-group" style="grid-column: span 2;">
                <label>Hospital Logo</label>
                <div style="display: flex; align-items: center; gap: 2rem; padding: 1.5rem; border: 2px dashed var(--border); border-radius: var(--radius-md);">
                    <div style="width: 100px; height: 100px; background: var(--surface-2); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img id="logoPreview" src="/RMU-Medical-Management-System/<?= $hospital['logo_path'] ?? 'image/logo-ju-small.png' ?>" style="max-width: 100%; max-height: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <input type="file" name="logo" class="form-control" style="margin-bottom: 0.5rem;" onchange="document.getElementById('logoPreview').src = window.URL.createObjectURL(this.files[0])">
                        <small class="text-muted">Recommended size: 512x512px. PNG or SVG preferred.</small>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save Hospital Profile
            </span></button>
        </div>
    </form>
</div>
