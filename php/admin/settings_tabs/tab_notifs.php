<!-- Notifications & Alerts Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-bell"></i> System Notifications & Escalation Rules</h2>
        <span class="badge bg-info">Automation</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Event Trigger</th>
                    <th>Recipient Role</th>
                    <th>Alert Channels</th>
                    <th>Escalation (Min)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $triggers = [
                    ['event' => 'Low Stock Alert', 'role' => 'Pharmacist', 'channels' => ['push', 'email'], 'esc' => 30],
                    ['event' => 'Emergency vitals', 'role' => 'Doctor', 'channels' => ['push', 'sms'], 'esc' => 5],
                    ['event' => 'New Lab Request', 'role' => 'Lab Technician', 'channels' => ['push'], 'esc' => 60],
                    ['event' => 'New Leave Request', 'role' => 'Admin', 'channels' => ['push', 'email'], 'esc' => 0],
                    ['event' => 'Patient Admission', 'role' => 'Nurse', 'channels' => ['push'], 'esc' => 0],
                    ['event' => 'System Backup Fail', 'role' => 'Admin', 'channels' => ['push', 'email', 'sms'], 'esc' => 120]
                ];

                foreach ($triggers as $t):
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= $t['event'] ?></td>
                    <td><span class="badge bg-light text-dark"><?= $t['role'] ?></span></td>
                    <td>
                        <?php foreach($t['channels'] as $c): ?>
                            <i class="fas fa-<?= $c=='push'?'mobile-alt':($c=='email'?'envelope':'sms') ?> text-primary" style="margin-right: 0.5rem;" title="<?= ucfirst($c) ?>"></i>
                        <?php endforeach; ?>
                    </td>
                    <td><?= $t['esc'] > 0 ? $t['esc'] . ' mins' : 'No escalation' ?></td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" checked>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 2rem; background: var(--surface-2); padding: 1.5rem; border-radius: var(--radius-lg);">
        <h4><i class="fas fa-plug"></i> Integration Endpoints</h4>
        <div class="grid-2 mt-3">
            <div class="form-group">
                <label>SMS Provider API Key (BulkSMS)</label>
                <input type="password" class="form-control" value="xxxxxxxxxxxxxxxxxxxx">
            </div>
            <div class="form-group">
                <label>Push Notification Public Key (VAPID)</label>
                <input type="text" class="form-control" value="BH-xxxx-xxxx-xxxx-xxxx" readonly>
            </div>
        </div>
    </div>
</div>
