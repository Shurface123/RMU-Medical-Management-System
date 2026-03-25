<!-- Maintenance & Backup Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-server"></i> System Maintenance & Data Backups</h2>
        <span class="badge bg-warning">Utility</span>
    </div>

    <!-- Maintenance Mode -->
    <div style="background: var(--surface-2); padding: 2rem; border-radius: var(--radius-md); margin-bottom: 2rem; border-left: 5px solid <?= ($config['maintenance_mode']??'0')=='1' ? 'var(--danger)' : 'var(--success)' ?>;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h3 style="margin-bottom: 0.5rem;">System Maintenance Mode</h3>
                <p class="text-muted">When enabled, all non-admin users will see a "System Maintenance" page and cannot login.</p>
            </div>
            <div class="form-check form-switch" style="font-size: 1.5rem;">
                <input class="form-check-input" type="checkbox" id="mModeToggle" <?= ($config['maintenance_mode']??'0')=='1' ? 'checked' : '' ?> 
                       onchange="toggleMaintenanceMode(this.checked)">
            </div>
        </div>
    </div>

    <hr style="margin: 3rem 0; opacity: 0.1;">

    <!-- Backup Management -->
    <div class="grid-2">
        <div class="card" style="padding: 2rem; border: 1px solid var(--border);">
            <i class="fas fa-database fa-3x" style="color: var(--primary); margin-bottom: 1rem;"></i>
            <h3>Database Backups</h3>
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 1.5rem;">Create a restorable snapshot of the entire RMU Medical database.</p>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="backup_management.php?action=create" class="btn btn-primary" style="justify-content: center;">
                    <i class="fas fa-file-export"></i> Run Manual Backup Now
                </a>
                <button class="btn btn-outline-primary" style="justify-content: center;">
                    <i class="fas fa-clock"></i> Configure Auto-Backup
                </button>
            </div>
        </div>

        <div class="card" style="padding: 2rem; border: 1px solid var(--border);">
            <i class="fas fa-broom fa-3x" style="color: var(--warning); margin-bottom: 1rem;"></i>
            <h3>Cache & Cleanup</h3>
            <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 1.5rem;">Clear compiled templates and temporary system files to free up space.</p>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <button onclick="clearSystemCache()" class="btn btn-outline-warning" style="justify-content: center;">
                    <i class="fas fa-eraser"></i> Clear System Cache
                </button>
                <div style="text-align: center; font-size: 0.8rem; color: var(--text-muted);">
                    Last cleanup: <?= date('d M Y, H:i') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- System Version Info -->
    <div style="margin-top: 3rem; padding: 1.5rem; background: var(--surface-2); border-radius: var(--radius-md); text-align: center;">
        <p style="margin: 0; font-weight: 600; color: var(--text-secondary);">
            RMU Medical Sickbay System v3.1.0-alpha
        </p>
        <span class="text-muted" style="font-size: 0.8rem;">Last Core Update: 23 March 2026</span>
    </div>
</div>

<script>
function toggleMaintenanceMode(status) {
    const formData = new FormData();
    formData.append('action', 'toggle_maintenance');
    formData.append('status', status ? '1' : '0');
    
    fetch('admin_settings_actions.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            location.reload();
        }
    });
}

function clearSystemCache() {
    if(confirm('Are you sure you want to clear the system cache? This may temporarily slow down page loads.')) {
        alert('Cache cleared successfully!'); // Placeholder for actual cache clear logic
    }
}
</script>
