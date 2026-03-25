<!-- Security Policies Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-shield-alt"></i> Security & Access Policies</h2>
        <span class="badge bg-danger">Critical</span>
    </div>

    <!-- Password & Session Policies -->
    <form id="securityPolicyForm" onsubmit="event.preventDefault(); saveSettings('securityPolicyForm', 'save_security_policy');">
        <div class="grid-2">
            <div class="form-group">
                <label>Minimum Password Length</label>
                <input type="number" name="password_min_length" class="form-control" value="<?= $config['password_min_length'] ?? '8' ?>" min="6" max="32">
            </div>

            <div class="form-group">
                <label>Password Complexity</label>
                <select name="password_require_special" class="form-control">
                    <option value="0" <?= ($config['password_require_special']??'0')=='0'?'selected':'' ?>>Alphanumeric Only</option>
                    <option value="1" <?= ($config['password_require_special']??'0')=='1'?'selected':'' ?>>Must include Special Characters (@, #, $, etc.)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Admin Session Timeout (Min)</label>
                <input type="number" name="session_timeout_admin" class="form-control" value="<?= $config['session_timeout_admin'] ?? '30' ?>">
            </div>

            <div class="form-group">
                <label>Medical Staff Session Timeout (Min)</label>
                <input type="number" name="session_timeout_doctor" class="form-control" value="<?= $config['session_timeout_doctor'] ?? '60' ?>">
            </div>

            <div class="form-group">
                <label>Max Login Failures (Before Lockout)</label>
                <input type="number" name="max_login_attempts" class="form-control" value="<?= $config['max_login_attempts'] ?? '5' ?>">
            </div>

            <div class="form-group">
                <label>Account Lockout Duration (Min)</label>
                <input type="number" name="lockout_duration" class="form-control" value="<?= $config['lockout_duration'] ?? '15' ?>">
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;">
                <i class="fas fa-lock"></i> Update Security Policies
            </button>
        </div>
    </form>

    <hr style="margin: 3rem 0; opacity: 0.1;">

    <!-- IP Whitelist Section -->
    <div class="settings-card-header">
        <h2 class="settings-card-title" style="font-size: 1.2rem;"><i class="fas fa-network-wired"></i> IP Whitelist (Management Access)</h2>
    </div>

    <div style="margin-bottom: 2rem;">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>IP Address</th>
                    <th>Label</th>
                    <th>Added By</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ip_res = mysqli_query($conn, "SELECT i.*, u.name as admin_name FROM ip_whitelist i LEFT JOIN users u ON i.added_by = u.id");
                while($ip = mysqli_fetch_assoc($ip_res)):
                ?>
                <tr>
                    <td><code><?= $ip['ip_address'] ?></code></td>
                    <td><?= htmlspecialchars($ip['label'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($ip['admin_name'] ?? 'System') ?></td>
                    <td><?= date('d M Y, H:i', strtotime($ip['created_at'])) ?></td>
                    <td><button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></td>
                </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($ip_res) == 0): ?>
                    <tr><td colspan="5" class="text-center text-muted">No IP restrictions active. Global access allowed.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <form id="ipWhitelistForm" onsubmit="event.preventDefault(); saveSettings('ipWhitelistForm', 'add_ip_whitelist');" class="grid-2" style="background: var(--surface-2); padding: 1.5rem; border-radius: var(--radius-md);">
        <div class="form-group">
            <label>New Whitelist IP</label>
            <input type="text" name="ip_address" class="form-control" placeholder="e.g. 192.168.1.1" required>
        </div>
        <div class="form-group">
            <label>IP Label / Location</label>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" name="label" class="form-control" placeholder="Admin Office PC" required>
                <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Add</button>
            </div>
        </div>
    </form>
</div>
