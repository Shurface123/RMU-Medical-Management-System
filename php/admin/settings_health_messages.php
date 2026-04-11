<?php
session_start();
// Include authentication middleware
require_once '../includes/auth_middleware.php';
requireRole('admin');
require_once '../db_conn.php';
require_once '../classes/BroadcastManager.php';

$bm = new BroadcastManager($conn);

$active_page = 'health_messages';
$page_title = 'Broadcast & Health Messages';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $msg = mysqli_real_escape_string($conn, $_POST['message_text']);
        $cat = mysqli_real_escape_string($conn, $_POST['message_category']);
        $role = empty($_POST['target_role']) ? "NULL" : "'" . mysqli_real_escape_string($conn, $_POST['target_role']) . "'";
        $status = isset($_POST['is_active']) ? 1 : 0;
        $adminId = $_SESSION['user_id'] ?? 1;
        
        $isBroadcast = isset($_POST['is_broadcast']) ? 1 : 0;
        $priority = mysqli_real_escape_string($conn, $_POST['priority'] ?? 'Informational');
        $subject = mysqli_real_escape_string($conn, $_POST['subject'] ?? 'System Announcement');
        
        if ($action === 'add') {
            $q = "INSERT INTO health_messages (message_text, message_category, target_role, is_active, created_by) VALUES ('$msg', '$cat', $role, $status, $adminId)";
            mysqli_query($conn, $q);
            $msgId = mysqli_insert_id($conn);
            $success = "Message saved.";
            
            if ($isBroadcast && $status) {
                $audienceType = 'Everyone';
                $audienceIds = null;
                if (!empty($_POST['target_role'])) {
                    $audienceType = 'Role';
                    $audienceIds = [$_POST['target_role']];
                }
                
                $bm->createBroadcast([
                    'subject' => $subject,
                    'body' => $msg,
                    'priority' => $priority,
                    'sender_id' => $adminId,
                    'audience_type' => $audienceType,
                    'audience_ids' => $audienceIds,
                    'requires_acknowledgement' => ($priority === 'Critical' ? 1 : 0)
                ]);
                $success .= " Real-time broadcast triggered!";
            }
        } else {
            $id = intval($_POST['message_id']);
            $q = "UPDATE health_messages SET message_text='$msg', message_category='$cat', target_role=$role, is_active=$status WHERE id=$id";
            mysqli_query($conn, $q);
            $success = "Message updated successfully.";
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['message_id']);
        mysqli_query($conn, "DELETE FROM health_messages WHERE id=$id");
        $success = "Message deleted successfully.";
    } elseif ($action === 'toggle') {
        $id = intval($_POST['message_id']);
        mysqli_query($conn, "UPDATE health_messages SET is_active = 1 - is_active WHERE id=$id");
        echo json_encode(['success'=>true]);
        exit;
    }
}

// Fetch Messages
$messages = [];
$q = mysqli_query($conn, "SELECT h.id AS message_id, h.*, u.name as admin_name FROM health_messages h LEFT JOIN users u ON h.created_by = u.id ORDER BY h.updated_at DESC");
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $messages[] = $r;
    }
}

include '../includes/_sidebar.php';
?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">Broadcast & Health Messages</span>
        </div>
    </div>
    <div class="adm-content">
        <?php if (!empty($success)): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-bullhorn"></i> Manage Health & Logout Messages</h3>
                <button class="btn btn-primary" onclick="document.getElementById('msgModal').classList.add('open')"><span class="btn-text">
                    <i class="fas fa-plus"></i> New Message
                </span></button>
            </div>
            <div style="padding:1.5rem;overflow-x:auto;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Message Text</th>
                            <th>Category</th>
                            <th>Target Role</th>
                            <th>Active</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($messages)): ?>
                        <tr><td colspan="6" style="text-align:center;">No messages found.</td></tr>
                        <?php else: foreach($messages as $m): ?>
                        <tr>
                            <td style="max-width: 300px; line-height: 1.4;"><?= htmlspecialchars($m['message_text']) ?></td>
                            <td><span class="adm-badge adm-badge-info"><?= ucfirst(htmlspecialchars($m['message_category'])) ?></span></td>
                            <td><?= $m['target_role'] ? ucfirst(htmlspecialchars($m['target_role'])) : '<span style="color:var(--text-muted);">All Roles</span>' ?></td>
                            <td>
                                <label style="cursor:pointer;">
                                    <input type="checkbox" onchange="toggleStatus(<?= $m['message_id'] ?>)" <?= $m['is_active'] ? 'checked' : '' ?>>
                                    <span style="font-size:0.8rem; margin-left:0.3rem;"><?= $m['is_active'] ? 'Active' : 'Draft' ?></span>
                                </label>
                            </td>
                            <td><?= date('M j, Y', strtotime($m['updated_at'])) ?></td>
                            <td>
                                <button class="btn btn-ghost btn-sm" onclick='editMsg(<?= json_encode($m) ?>)'><span class="btn-text"><i class="fas fa-edit"></i> Edit</span></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message permanently?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                                    <button class="btn btn-ghost btn-sm" style="color:var(--danger);"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal-bg" id="msgModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9999;">
    <div class="modal-box" style="background:var(--surface);width:90%;max-width:500px;border-radius:12px;padding:2rem;">
        <h3 id="modalTitle" style="margin-bottom:1.5rem;">Add New Message</h3>
        <form method="POST">
            <input type="hidden" name="action" id="modalAction" value="add">
            <input type="hidden" name="message_id" id="modalId" value="">
            
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Message Content</label>
                <textarea name="message_text" id="modalText" rows="3" class="form-control" style="width:100%;" required maxlength="255"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom:1rem;">
                <label>Category</label>
                <select name="message_category" id="modalCat" class="form-control" style="width:100%;" required>
                    <option value="wellness">Wellness</option>
                    <option value="safety">Safety Guidelines</option>
                    <option value="reminder">Clinical Reminder</option>
                    <option value="motivational">Motivational</option>
                    <option value="health tip">Health Tip</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:1rem;">
                <label><input type="checkbox" name="is_broadcast" id="modalIsBroadcast" onchange="toggleBroadcastFields(this.checked)"> Trigger as Real-time Broadcast</label>
            </div>

            <div id="broadcastFields" style="display:none; background:rgba(0,0,0,0.05); padding:1rem; border-radius:8px; margin-bottom:1rem; border-left:4px solid var(--primary);">
                <div class="form-group" style="margin-bottom:1rem;">
                    <label>Broadcast Subject</label>
                    <input type="text" name="subject" id="modalSubject" class="form-control" style="width:100%;" placeholder="e.g. Scheduled Maintenance">
                </div>
                <div class="form-group">
                    <label>Priority Level</label>
                    <select name="priority" id="modalPriority" class="form-control" style="width:100%;">
                        <option value="Informational">Informational (Toast + Bell)</option>
                        <option value="Important">Important (Pulse + Toast)</option>
                        <option value="Urgent">Urgent (Top Banner + Alert)</option>
                        <option value="Critical">Critical (Full Modal + Audio)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label>Target Audience (Leave empty for Everyone)</label>
                <select name="target_role" id="modalRole" class="form-control" style="width:100%;">
                    <option value="">-- All Roles --</option>
                    <option value="admin">Admin</option>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="lab_technician">Lab Technician</option>
                    <option value="patient">Patient</option>
                    <option value="staff">Other Staff</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label><input type="checkbox" name="is_active" id="modalActive" checked> Make this message active immediately</label>
            </div>
            
            <div style="display:flex;justify-content:flex-end;gap:1rem;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('msgModal').classList.remove('open')"><span class="btn-text">Cancel</span></button>
                <button type="submit" class="btn btn-primary"><span class="btn-text">Save Message</span></button>
            </div>
        </form>
    </div>
</div>
<style>
.modal-bg.open { display: flex !important; }
.form-control { border:1px solid var(--border); padding:0.5rem; border-radius:6px; background:var(--surface-2); color:var(--text-primary); }
</style>
<script>
function toggleBroadcastFields(show) {
    document.getElementById('broadcastFields').style.display = show ? 'block' : 'none';
    document.getElementById('modalSubject').required = show;
}

function editMsg(m) {
    document.getElementById('modalTitle').innerText = 'Edit Message';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = m.message_id;
    document.getElementById('modalText').value = m.message_text;
    document.getElementById('modalCat').value = m.message_category;
    document.getElementById('modalRole').value = m.target_role || '';
    document.getElementById('modalActive').checked = m.is_active == 1;
    
    // Hide broadcast fields for edit (simplifies logic for now, or you could allow re-triggering)
    document.getElementById('modalIsBroadcast').checked = false;
    toggleBroadcastFields(false);
    
    document.getElementById('msgModal').classList.add('open');
}

async function toggleStatus(id) {
    try {
        await fetch('settings_health_messages.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action=toggle&message_id=${id}`
        });
    } catch(e) {}
}
</script>
