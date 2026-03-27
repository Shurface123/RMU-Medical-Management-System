<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $msg_text = trim($_POST['message_text']);
        $category = $_POST['category'] ?? 'wellness';
        $role = empty($_POST['target_role']) ? null : $_POST['target_role'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO health_messages (message_text, message_category, target_role, is_active, created_by) VALUES (?, ?, ?, ?, ?)");
            $uid = $_SESSION['user_id'];
            mysqli_stmt_bind_param($stmt, 'sssii', $msg_text, $category, $role, $is_active, $uid);
            mysqli_stmt_execute($stmt);
        } else {
            $id = (int)$_POST['id'];
            $stmt = mysqli_prepare($conn, "UPDATE health_messages SET message_text=?, message_category=?, target_role=?, is_active=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssii', $msg_text, $category, $role, $is_active, $id);
            mysqli_stmt_execute($stmt);
        }
        header("Location: settings_health_messages.php?success=1");
        exit;
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        mysqli_query($conn, "DELETE FROM health_messages WHERE id=$id");
        header("Location: settings_health_messages.php?success=1");
        exit;
    }
}

// Fetch all
$query = mysqli_query($conn, "SELECT * FROM health_messages ORDER BY created_at DESC");
$messages = [];
if ($query) {
    while($row = mysqli_fetch_assoc($query)) $messages[] = $row;
}

$active_page = 'health_messages';
$page_title = 'Broadcast & Health Messages';
include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title">Broadcast & Health Messages</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-avatar"><i class="fas fa-user-tie"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-welcome" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2><i class="fas fa-bullhorn" style="margin-right:.5rem;"></i> Message CMS</h2>
                <p>Manage dynamic health, wellness, and safety prompts displayed during the logout countdown sequence.</p>
            </div>
            <button class="adm-btn adm-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> New Message</button>
        </div>

        <div class="adm-card">
            <div class="adm-card-body" style="padding:0;">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Message</th>
                            <th>Category</th>
                            <th>Target Role</th>
                            <th>Status</th>
                            <th align="right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($messages as $msg): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars(substr($msg['message_text'], 0, 80)) . (strlen($msg['message_text']) > 80 ? '...' : '') ?></strong></td>
                            <td><span class="adm-badge adm-badge-info"><?= ucfirst($msg['message_category']) ?></span></td>
                            <td><?= $msg['target_role'] ? ucfirst($msg['target_role']) : '<em>All Roles</em>' ?></td>
                            <td>
                                <?php if($msg['is_active']): ?>
                                    <span class="adm-badge adm-badge-success">Active</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td align="right">
                                <button class="adm-btn adm-btn-sm" onclick='editModal(<?= json_encode($msg) ?>)' style="background:var(--primary);color:#fff;"><i class="fas fa-edit"></i></button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                                    <button type="submit" class="adm-btn adm-btn-sm adm-btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <tr><td colspan="5" align="center">No messages found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal -->
<div id="msgModal" class="adm-overlay" style="z-index:9999; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.5);">
    <div style="background:#fff; padding:2rem; border-radius:12px; width:90%; max-width:500px; box-shadow:0 15px 40px rgba(0,0,0,0.2);">
        <h3 id="modalTitle" style="margin-bottom:1rem; color:var(--text-dark);">Add Message</h3>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="msgId" value="">
            
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:.5rem; font-weight:500; font-size:.9rem;">Message Text</label>
                <textarea name="message_text" id="msgText" required class="adm-input" rows="4" style="width:100%; border:1px solid #ddd; border-radius:8px; padding:10px; font-family:inherit;"></textarea>
            </div>
            
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:.5rem; font-weight:500; font-size:.9rem;">Category</label>
                <select name="category" id="msgCat" class="adm-input" style="width:100%; border:1px solid #ddd; border-radius:8px; padding:10px;">
                    <option value="wellness">Wellness</option>
                    <option value="safety">Safety</option>
                    <option value="reminder">Reminder</option>
                    <option value="motivational">Motivational</option>
                    <option value="health tip">Health Tip</option>
                </select>
            </div>
            
            <div style="margin-bottom:1rem;">
                <label style="display:block; margin-bottom:.5rem; font-weight:500; font-size:.9rem;">Target Role</label>
                <select name="target_role" id="msgRole" class="adm-input" style="width:100%; border:1px solid #ddd; border-radius:8px; padding:10px;">
                    <option value="">All Roles (Global)</option>
                    <option value="doctor">Doctor</option>
                    <option value="patient">Patient</option>
                    <option value="nurse">Nurse</option>
                    <option value="pharmacist">Pharmacist</option>
                    <option value="lab_technician">Lab Technician</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            
            <div style="margin-bottom:1.5rem;">
                <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer;">
                    <input type="checkbox" name="is_active" id="msgActive" value="1" checked>
                    <span style="font-size:.9rem; font-weight:500;">Active (Displayed in UI)</span>
                </label>
            </div>
            
            <div style="display:flex; gap:1rem; justify-content:flex-end;">
                <button type="button" class="adm-btn" onclick="closeModal()" style="background:#f1f5f9; color:#475569;">Cancel</button>
                <button type="submit" class="adm-btn adm-btn-primary">Save Message</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modalTitle').innerText = 'Add Health Message';
    document.getElementById('formAction').value = 'add';
    document.getElementById('msgId').value = '';
    document.getElementById('msgText').value = '';
    document.getElementById('msgCat').value = 'wellness';
    document.getElementById('msgRole').value = '';
    document.getElementById('msgActive').checked = true;
    document.getElementById('msgModal').style.display = 'flex';
}
function editModal(msg) {
    document.getElementById('modalTitle').innerText = 'Edit Health Message';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('msgId').value = msg.id;
    document.getElementById('msgText').value = msg.message_text;
    document.getElementById('msgCat').value = msg.message_category;
    document.getElementById('msgRole').value = msg.target_role || '';
    document.getElementById('msgActive').checked = msg.is_active == 1;
    document.getElementById('msgModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('msgModal').style.display = 'none';
}
</script>
</body>
</html>
