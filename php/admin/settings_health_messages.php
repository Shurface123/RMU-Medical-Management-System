<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'health_messages';
$page_title  = 'Broadcast Management Panel';
include '../includes/_sidebar.php';

// Fetch existing broadcasts for the table
$broadcasts = [];
$q = mysqli_query($conn, "SELECT b.*, u.user_name as sender_name FROM broadcasts b JOIN users u ON b.sender_id = u.id ORDER BY b.created_at DESC LIMIT 100");
if ($q) while ($r = mysqli_fetch_assoc($q)) $broadcasts[] = $r;

// Fetch Departments & Wards for targeting
$depts = [];
$res = mysqli_query($conn, "SELECT name FROM departments WHERE is_active = 1");
if ($res) while ($r = mysqli_fetch_assoc($res)) $depts[] = $r['name'];

$wards = [];
$res = mysqli_query($conn, "SELECT ward_name FROM wards");
if ($res) while ($r = mysqli_fetch_assoc($res)) $wards[] = $r['ward_name'];
?>

<!-- Quill Rich Text Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bullhorn"></i> Broadcast Hub</span>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Broadcast & Communication Center</h1>
                <p>Advanced system-wide messaging with real-time delivery and targeting layers.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="openComposer()">
                <i class="fas fa-plus"></i> New Broadcast
            </button>
        </div>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-history"></i> Global Transmission History</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table responsive-cards">
                    <thead>
                        <tr>
                            <th>Status / Release</th>
                            <th>Subject & Sender</th>
                            <th>Audience Matrix</th>
                            <th>Priority Tier</th>
                            <th>Metrics</th>
                            <th>Operations</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($broadcasts)): ?>
                            <tr><td colspan="6">
                                <div class="adm-empty-state">
                                    <i class="fas fa-bullhorn"></i>
                                    <h3>No transmissions recorded</h3>
                                    <p>Start a new system-wide broadcast to communicate with staff and patients.</p>
                                </div>
                            </td></tr>
                        <?php else: foreach ($broadcasts as $b): 
                            $status_class = strtolower($b['status']);
                            $p_class = strtolower($b['priority']);
                            $priority_badge = 'adm-badge-info';
                            if ($p_class === 'urgent') $priority_badge = 'adm-badge-warning';
                            if ($p_class === 'critical') $priority_badge = 'adm-badge-danger';
                            
                            $status_badge = 'adm-badge-success';
                            if ($status_class === 'scheduled') $status_badge = 'adm-badge-warning';
                        ?>
                            <tr>
                                <td data-label="Status / Release">
                                    <span class="adm-badge <?= $status_badge ?>"><?= strtoupper($b['status']) ?></span><br>
                                    <small class="text-muted" style="font-size:1.1rem;"><?= date('M d, H:i', strtotime($b['created_at'])) ?></small>
                                </td>
                                <td data-label="Subject & Sender">
                                    <strong style="color:var(--text-primary);"><?= htmlspecialchars($b['subject']) ?></strong><br>
                                    <small class="text-muted" style="font-size:1.1rem;"><?= $b['sender_name'] ?></small>
                                </td>
                                <td data-label="Audience Matrix">
                                    <span class="adm-badge adm-badge-info"><?= $b['audience_type'] ?></span>
                                    <?php if ($b['audience_type'] !== 'Everyone'): ?>
                                        <div style="font-size:1rem; color:var(--text-muted); max-width:180px; overflow:hidden; text-overflow:ellipsis;">
                                            <?= implode(', ', json_decode($b['audience_ids'], true)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Priority Tier"><span class="adm-badge <?= $priority_badge ?>"><?= strtoupper($b['priority']) ?></span></td>
                                <td data-label="Metrics">
                                    <button class="adm-btn adm-btn-outline sm" onclick="viewStats(<?= $b['id'] ?>)">
                                        <i class="fas fa-chart-line"></i> Analysis
                                    </button>
                                </td>
                                <td data-label="Operations">
                                    <?php if ($b['status'] === 'Scheduled'): ?>
                                        <button class="adm-btn adm-btn-danger sm" onclick="cancelBroadcast(<?= $b['id'] ?>)">Revoke</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Composer Modal -->
<div class="adm-modal" id="composerModal">
    <div class="adm-modal-content" style="max-width:800px;">
        <div class="adm-modal-header">
            <h3><i class="fas fa-bullhorn"></i> New Broadcast Composer</h3>
            <button class="adm-modal-close" onclick="closeComposer()"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="broadcastForm">
                <input type="hidden" name="action" value="create">
                
                <div class="adm-form-group">
                    <label>Broadcast Subject</label>
                    <input type="text" name="subject" class="adm-search-input" required placeholder="Main heading for the notification...">
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label>Audience Targeting Layer</label>
                        <select name="audience_type" id="audienceType" class="adm-search-input" onchange="toggleAudienceOptions()">
                            <option value="Everyone">Everyone (System-wide)</option>
                            <option value="Role">Role-Specific</option>
                            <option value="Department">Department/Ward Specific</option>
                            <option value="Individual">Individual User</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Priority Level</label>
                        <select name="priority" class="adm-search-input">
                            <option value="Informational">Informational (Standard)</option>
                            <option value="Important">Important (Badge Highlighting)</option>
                            <option value="Urgent">Urgent (Red Banner + Alert)</option>
                            <option value="Critical">Critical (Full Modal + Audio)</option>
                        </select>
                    </div>
                </div>

                <!-- Contextual Audience Options -->
                <div id="roleOptions" class="audience-sub-options" style="display:none;">
                    <label>Select Roles</label>
                    <div class="chk-grid">
                        <label><input type="checkbox" value="doctor" class="role-chk"> Doctors</label>
                        <label><input type="checkbox" value="nurse" class="role-chk"> Nurses</label>
                        <label><input type="checkbox" value="pharmacist" class="role-chk"> Pharmacists</label>
                        <label><input type="checkbox" value="lab_tech" class="role-chk"> Lab Techs</label>
                        <label><input type="checkbox" value="patient" class="role-chk"> Patients</label>
                        <label><input type="checkbox" value="ambulance_driver" class="role-chk"> Ambulance</label>
                    </div>
                </div>

                <div id="deptOptions" class="audience-sub-options" style="display:none;">
                    <label>Select Department/Ward</label>
                    <div class="chk-grid">
                        <?php foreach (array_merge($depts, $wards) as $dw): ?>
                            <label><input type="checkbox" value="<?= $dw ?>" class="dept-chk"> <?= $dw ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="individualOptions" class="audience-sub-options" style="display:none;">
                    <label>User ID(s) <small>(Comma separated)</small></label>
                    <input type="text" id="individualIds" class="adm-search-input" placeholder="e.g. 101, 102, 105">
                </div>

                <div class="adm-form-group">
                    <label>Message Body (Rich Text)</label>
                    <div id="editor" style="height: 200px;"></div>
                    <textarea name="body" id="bodyInput" style="display:none;"></textarea>
                </div>

                <div class="adm-grid-2">
                    <div class="adm-form-group">
                        <label>Schedule Send (Release Date/Time)</label>
                        <input type="datetime-local" name="scheduled_at" class="adm-search-input" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="adm-form-group">
                        <label>Auto-Expiry (Optional)</label>
                        <input type="datetime-local" name="expires_at" class="adm-search-input">
                    </div>
                </div>

                <div class="adm-form-group">
                    <label><input type="checkbox" name="requires_acknowledgement"> Require Acknowledgement (Recipients must click 'Read & Understood')</label>
                </div>

                <div class="adm-form-group">
                    <label>Attach File (PDF/Images)</label>
                    <input type="file" name="attachment" class="adm-search-input">
                </div>

                <button type="submit" class="adm-btn adm-btn-primary" style="width:100%; padding:1rem;">
                    <i class="fas fa-paper-plane" style="margin-right:0.5rem;"></i> Send Broadcast Now
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Stats Modal -->
<div class="adm-modal" id="statsModal">
    <div class="adm-modal-content">
        <div class="adm-modal-header">
            <h3><i class="fas fa-chart-pie"></i> Delivery Intelligence</h3>
            <button class="adm-modal-close" onclick="document.getElementById('statsModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body" id="statsBody">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</div>

<style>
.audience-sub-options { margin-bottom: 1.5rem; background: var(--bg-secondary); padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color); }
.chk-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem; margin-top: 0.5rem; }
.chk-grid label { font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; }
.status-sent { background: #e6ffed; color: #28a745; }
.status-scheduled { background: #fff8e1; color: #fbc02d; }
.priority-critical { background: #ffebee; color: #d32f2f; font-weight: bold; }
.priority-urgent { background: #fff3e0; color: #f57c00; }
</style>

<script>
// Initialize Quill
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'clean']
        ]
    }
});

function openComposer() { document.getElementById('composerModal').classList.add('active'); }
function closeComposer() { document.getElementById('composerModal').classList.remove('active'); }

function toggleAudienceOptions() {
    const type = document.getElementById('audienceType').value;
    document.querySelectorAll('.audience-sub-options').forEach(el => el.style.display = 'none');
    if (type === 'Role') document.getElementById('roleOptions').style.display = 'block';
    if (type === 'Department') document.getElementById('deptOptions').style.display = 'block';
    if (type === 'Individual') document.getElementById('individualOptions').style.display = 'block';
}

document.getElementById('broadcastForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    // Get rich text content
    document.getElementById('bodyInput').value = quill.root.innerHTML;
    formData.set('body', quill.root.innerHTML);

    // Resolve audience IDs
    let audienceIds = [];
    const type = document.getElementById('audienceType').value;
    if (type === 'Role') {
        document.querySelectorAll('.role-chk:checked').forEach(c => audienceIds.push(c.value));
    } else if (type === 'Department') {
        document.querySelectorAll('.dept-chk:checked').forEach(c => audienceIds.push(c.value));
    } else if (type === 'Individual') {
        audienceIds = document.getElementById('individualIds').value.split(',').map(s => s.trim());
    }
    formData.append('audience_ids', JSON.stringify(audienceIds));

    fetch('broadcast_actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            location.reload();
        } else {
            alert('Error: ' + res.message);
        }
    });
};

function cancelBroadcast(id) {
    if (!confirm('Cancel this scheduled broadcast?')) return;
    const fd = new FormData();
    fd.append('action', 'cancel');
    fd.append('id', id);
    fetch('broadcast_actions.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => location.reload());
}

function viewStats(id) {
    document.getElementById('statsModal').classList.add('active');
    document.getElementById('statsBody').innerHTML = 'Loading intelligence...';
    fetch('broadcast_actions.php?action=get_stats&id=' + id)
    .then(r => r.json())
    .then(res => {
        const s = res.stats;
        document.getElementById('statsBody').innerHTML = `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem; text-align:center;">
                <div class="adm-card" style="padding:1rem;"><h1>${s.total}</h1><small>Recipients</small></div>
                <div class="adm-card" style="padding:1rem;"><h1>${s.delivered}</h1><small>Delivered</small></div>
                <div class="adm-card" style="padding:1rem;"><h1>${s.read_count}</h1><small>Read</small></div>
                <div class="adm-card" style="padding:1rem;"><h1>${s.ack_count}</h1><small>Acknowledged</small></div>
            </div>
            <hr style="margin:2rem 0;">
            <button class="adm-btn adm-btn-outline" style="width:100%;" onclick="viewRecipientList(${id})">View Detailed Recipient Log</button>
        `;
    });
}

function viewRecipientList(id) {
    fetch('broadcast_actions.php?action=get_recipients&id=' + id)
    .then(r => r.json())
    .then(res => {
        let html = '<table class="adm-table sm"><thead><tr><th>User</th><th>Role</th><th>Status</th></tr></thead><tbody>';
        res.recipients.forEach(r => {
            const status = r.acknowledged_at ? 'Acked' : (r.read_at ? 'Read' : (r.delivered_at ? 'Delivered' : 'Pending'));
            html += `<tr><td>${r.name}</td><td>${r.recipient_role}</td><td>${status}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('statsBody').innerHTML = html;
    });
}
</script>
</body>
</html>
