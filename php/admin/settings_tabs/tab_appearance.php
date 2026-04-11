<!-- Appearance & Localization Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-palette"></i> System Appearance & Localization</h2>
        <span class="badge bg-info">Branding & Formats</span>
    </div>

    <form id="appearanceForm" onsubmit="event.preventDefault(); saveSettings('appearanceForm', 'save_sys_appearance');">
        <div class="grid-2">
            <!-- Branding -->
            <div class="form-group">
                <label>System Display Name</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($config['site_name'] ?? 'RMU Medical Sickbay') ?>" required>
            </div>

            <div class="form-group">
                <label>Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($config['currency_symbol'] ?? 'GHS') ?>" placeholder="e.g. $, £, GHS">
            </div>

            <!-- Formats -->
            <div class="form-group">
                <label>Date Format</label>
                <select name="date_format" class="form-control">
                    <option value="d M Y" <?= ($config['date_format']??'')=='d M Y'?'selected':'' ?>>23 Mar 2026 (d M Y)</option>
                    <option value="M d, Y" <?= ($config['date_format']??'')=='M d, Y'?'selected':'' ?>>Mar 23, 2026 (M d, Y)</option>
                    <option value="Y-m-d" <?= ($config['date_format']??'')=='Y-m-d'?'selected':'' ?>>2026-03-23 (Y-m-d)</option>
                    <option value="d/m/Y" <?= ($config['date_format']??'')=='d/m/Y'?'selected':'' ?>>23/03/2026 (d/m/Y)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Time Format</label>
                <select name="time_format" class="form-control">
                    <option value="H:i" <?= ($config['time_format']??'')=='H:i'?'selected':'' ?>>24-hour (14:30)</option>
                    <option value="h:i A" <?= ($config['time_format']??'')=='h:i A'?'selected':'' ?>>12-hour (02:30 PM)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Default System Language</label>
                <select name="language_default" class="form-control">
                    <option value="en" <?= ($config['language_default']??'')=='en'?'selected':'' ?>>English (UK)</option>
                    <option value="us" <?= ($config['language_default']??'')=='us'?'selected':'' ?>>English (US)</option>
                    <option value="fr" <?= ($config['language_default']??'')=='fr'?'selected':'' ?>>French (Foundation)</option>
                </select>
                <small class="text-muted">Localization files must be present in /lang directory.</small>
            </div>

            <div class="form-group">
                <label>Primary System Color</label>
                <div style="display: flex; gap: 1rem;">
                    <input type="color" name="primary_color" class="form-control" style="width: 60px; padding: 0.2rem;" value="<?= $config['primary_color'] ?? '#3b82f6' ?>">
                    <input type="text" class="form-control" value="<?= $config['primary_color'] ?? '#3b82f6' ?>" readonly>
                </div>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save Appearance
            </span></button>
        </div>
    </form>
</div>
