<!-- Integrations & APIs Tab -->
<?php
// Fetch SMTP settings
$email_res = mysqli_query($conn, "SELECT * FROM system_email_config ORDER BY id LIMIT 1");
$email_cfg = mysqli_fetch_assoc($email_res) ?: [];
if ($email_cfg) {
    $pwResult = mysqli_query($conn, "SELECT CAST(AES_DECRYPT(smtp_password, SHA2('RMU_SICKBAY_2025_SECRET',256)) AS CHAR) AS pw FROM system_email_config WHERE id={$email_cfg['id']}");
    $pwRow = mysqli_fetch_assoc($pwResult);
    $email_cfg['smtp_password_plain'] = $pwRow['pw'] ?? '';
}

// Fetch Paystack settings
$ps_res = mysqli_query($conn, "SELECT config_key, CAST(AES_DECRYPT(config_value, SHA2('RMU_SICKBAY_2025_SECRET',256)) AS CHAR) AS val, environment, is_active FROM paystack_config");
$ps_cfg = ['test' => [], 'live' => [], 'active_env' => 'test'];
if ($ps_res) {
    while($r = mysqli_fetch_assoc($ps_res)){
        if($r['is_active'] == 1) $ps_cfg['active_env'] = $r['environment'];
        $ps_cfg[$r['environment']][$r['config_key']] = $r['val'];
    }
}
?>

<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-envelope-open-text"></i> SMTP Mail Configuration</h2>
        <span class="badge" style="background:var(--primary);color:#fff;padding:4px 8px;border-radius:4px;">Secure</span>
    </div>

    <form id="smtpConfigForm" onsubmit="event.preventDefault(); saveSettings('smtpConfigForm', 'save_smtp_config');">
        <div class="grid-2">
            <div class="form-group">
                <label>SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($email_cfg['smtp_host'] ?? '') ?>" placeholder="e.g. smtp.gmail.com" required>
            </div>
            
            <div class="form-group">
                <label>SMTP Port</label>
                <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($email_cfg['smtp_port'] ?? '587') ?>" required>
            </div>

            <div class="form-group">
                <label>SMTP Username</label>
                <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars($email_cfg['smtp_username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>SMTP Password / App Password</label>
                <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($email_cfg['smtp_password_plain'] ?? '') ?>" placeholder="Leave blank to keep current password" required>
                <small style="color:var(--text-muted);">Passwords are encrypted with AES-256 before storage.</small>
            </div>

            <div class="form-group">
                <label>From Email Address</label>
                <input type="email" name="from_email" class="form-control" value="<?= htmlspecialchars($email_cfg['from_email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>From Name</label>
                <input type="text" name="from_name" class="form-control" value="<?= htmlspecialchars($email_cfg['from_name'] ?? 'RMU Medical Sickbay') ?>" required>
            </div>

            <div class="form-group" style="grid-column: span 2;">
                <label>Encryption Type</label>
                <select name="encryption" class="form-control">
                    <option value="tls" <?= ($email_cfg['encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>STARTTLS (Recommended for 587)</option>
                    <option value="ssl" <?= ($email_cfg['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                    <option value="none" <?= ($email_cfg['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None (Not Recommended)</option>
                </select>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save SMTP Configuration
            </span></button>
        </div>
    </form>
</div>

<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-shield-alt"></i> Google reCAPTCHA v3</h2>
        <span class="badge" style="background:var(--success);color:#fff;padding:4px 8px;border-radius:4px;">Active Validation</span>
    </div>

    <form id="recaptchaConfigForm" onsubmit="event.preventDefault(); saveSettings('recaptchaConfigForm', 'save_recaptcha_config');">
        <div class="grid-2">
            <div class="form-group" style="grid-column: span 2;">
                <label>Site Key (Public)</label>
                <input type="text" name="recaptcha_site_key" class="form-control" value="<?= htmlspecialchars($config['recaptcha_site_key'] ?? '') ?>" required>
            </div>
            
            <div class="form-group" style="grid-column: span 2;">
                <label>Secret Key (Private)</label>
                <input type="password" name="recaptcha_secret_key" class="form-control" value="<?= htmlspecialchars($config['recaptcha_secret_key'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Minimum Score Threshold (0.0 to 1.0)</label>
                <input type="number" step="0.1" min="0" max="1" name="recaptcha_score_threshold" class="form-control" value="<?= htmlspecialchars($config['recaptcha_score_threshold'] ?? '0.5') ?>" required>
                <small style="color:var(--text-muted);">Scores below this threshold are treated as bots.</small>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save reCAPTCHA config
            </span></button>
        </div>
    </form>
</div>

<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-credit-card"></i> Paystack API Configuration</h2>
        <span class="badge" style="background:var(--primary);color:#fff;padding:4px 8px;border-radius:4px;">Payments</span>
    </div>

    <form id="paystackConfigForm" onsubmit="event.preventDefault(); saveSettings('paystackConfigForm', 'save_paystack_config');">
        <div class="grid-2">
            <div class="form-group" style="grid-column: span 2;">
                <label>Active Environment</label>
                <select name="active_environment" class="form-control">
                    <option value="test" <?= ($ps_cfg['active_env'] === 'test') ? 'selected' : '' ?>>Test Mode</option>
                    <option value="live" <?= ($ps_cfg['active_env'] === 'live') ? 'selected' : '' ?>>Live Mode</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Test Public Key</label>
                <input type="text" name="test_public_key" class="form-control" value="<?= htmlspecialchars($ps_cfg['test']['public_key'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Test Secret Key</label>
                <input type="password" name="test_secret_key" class="form-control" value="<?= htmlspecialchars($ps_cfg['test']['secret_key'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Live Public Key</label>
                <input type="text" name="live_public_key" class="form-control" value="<?= htmlspecialchars($ps_cfg['live']['public_key'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Live Secret Key</label>
                <input type="password" name="live_secret_key" class="form-control" value="<?= htmlspecialchars($ps_cfg['live']['secret_key'] ?? '') ?>">
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2rem;"><span class="btn-text">
                <i class="fas fa-save"></i> Save Paystack Keys
            </span></button>
        </div>
    </form>
</div>

