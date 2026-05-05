<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';
require_once '../classes/BroadcastManager.php';

$bm = new BroadcastManager($conn);

$active_page = 'health_messages';
$page_title = 'Broadcast Center';

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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #ec4899; /* Pink for broadcasts/messages */
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #3b82f6;
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), #be185d);
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Badges ── */
.badge { display:inline-block; padding:0.3rem 0.8rem; border-radius:12px; font-size:0.9rem; font-weight:700; text-transform:uppercase; border:1px solid transparent; }
.badge-info { background:rgba(59,130,246,0.15); color:var(--info); border-color:rgba(59,130,246,0.3); }
.badge-success { background:rgba(16,185,129,0.15); color:var(--success); border-color:rgba(16,185,129,0.3); }
.badge-muted { background:var(--surface-2); color:var(--text-muted); border-color:var(--border); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }
.btn-sm { padding:0.6rem 1.2rem; font-size:1rem; }

/* ── Modal & Form ── */
.modal { position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); z-index:9999; display:none; align-items:center; justify-content:center; padding:1.5rem; }
.modal.open { display:flex; animation:fadeIn 0.3s ease; }
.modal-box { background:var(--surface); width:100%; max-width:600px; border-radius:var(--radius-md); box-shadow:var(--shadow-lg); border:1px solid var(--border); overflow:hidden; }
.modal-header { padding:1.5rem 2rem; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; background:var(--surface-2); }
.modal-body { padding:2rem; }
.modal-footer { padding:1.5rem 2rem; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:1rem; }

.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

.alert { padding:1.2rem 2rem; border-radius:var(--radius-sm); margin-bottom:2rem; display:flex; align-items:center; gap:1rem; font-weight:600; font-size:1.15rem; }
.alert-success { background:rgba(16,185,129,0.15); color:var(--success); border-left:5px solid var(--success); }

/* ── Toggle Switch ── */
.switch { position:relative; display:inline-block; width:44px; height:24px; margin:0;}
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:24px; }
.slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);}
input:checked + .slider { background-color:var(--primary); }
input:checked + .slider:before { transform:translateX(20px); }

@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bullhorn"></i> Broadcast & Health Messages</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadeIn .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-satellite-dish hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-paper-plane"></i></div>
            <div class="staff-hero-info">
                <h2>Real-time Communication Hub</h2>
                <p>Draft wellness tips, safety guidelines, and trigger critical system-wide broadcasts in real-time.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" style="background:#fff; color:var(--primary);" onclick="openModal('add')">
                    <i class="fas fa-plus"></i> Draft New Message
                </button>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-history" style="color:var(--primary);"></i> Message Archive</h3>
            </div>
            <div class="card-body" style="padding:1rem;">
                <table class="stf-table" id="messagesTable">
                    <thead>
                        <tr>
                            <th>Message Content</th>
                            <th>Category</th>
                            <th>Audience</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($messages)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:3rem; color:var(--text-muted);">No messages drafted yet.</td></tr>
                        <?php else: foreach($messages as $m): ?>
                        <tr>
                            <td>
                                <div style="max-width:350px; font-size:1.1rem; color:var(--text-primary); line-height:1.5;">
                                    <?= htmlspecialchars($m['message_text']) ?>
                                </div>
                            </td>
                            <td><span class="badge badge-info"><?= ucfirst(htmlspecialchars($m['message_category'])) ?></span></td>
                            <td>
                                <div style="font-weight:700; color:var(--text-primary);">
                                    <?= $m['target_role'] ? ucfirst(str_replace('_',' ',$m['target_role'])) : 'All Users' ?>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <label class="switch">
                                        <input type="checkbox" onchange="toggleStatus(<?= $m['message_id'] ?>)" <?= $m['is_active'] ? 'checked' : '' ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <span style="font-weight:600; font-size:0.95rem; color:<?= $m['is_active'] ? 'var(--success)' : 'var(--text-muted)' ?>;">
                                        <?= $m['is_active'] ? 'Active' : 'Draft' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight:600; color:var(--text-muted); font-size:0.95rem;">
                                    <?= date('M j, Y', strtotime($m['updated_at'])) ?>
                                </div>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; justify-content:flex-end; gap:0.8rem;">
                                    <button class="btn btn-ghost btn-sm" onclick='editMsg(<?= json_encode($m) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Permanently remove this message?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?= $m['message_id'] ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- MODAL -->
<div class="modal" id="msgModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Draft Message</h3>
            <button class="btn btn-ghost" onclick="closeModal()" style="padding:0.5rem; width:40px; height:40px; border-radius:50%;"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="message_id" id="modalId" value="">
                
                <div class="form-group">
                    <label>Message Content</label>
                    <textarea name="message_text" id="modalText" rows="4" class="form-control" placeholder="Type your tip or broadcast message here..." required maxlength="255"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="message_category" id="modalCat" class="form-control" required>
                        <option value="wellness">Wellness Tip</option>
                        <option value="safety">Safety Protocol</option>
                        <option value="reminder">Clinical Reminder</option>
                        <option value="motivational">Motivational Quotes</option>
                        <option value="health tip">General Health Tip</option>
                    </select>
                </div>

                <div class="switch-group" style="background:var(--surface-2); padding:1.2rem; border-radius:var(--radius-sm); border:1px solid var(--border); margin-bottom:1.6rem;">
                    <label style="display:flex; align-items:center; gap:0.8rem; cursor:pointer; margin:0;">
                        <input type="checkbox" name="is_broadcast" id="modalIsBroadcast" onchange="toggleBroadcastFields(this.checked)" style="width:20px; height:20px; accent-color:var(--primary);">
                        <strong style="font-size:1.1rem; color:var(--text-primary);">Trigger as Real-time Broadcast</strong>
                    </label>
                    <p style="font-size:0.9rem; color:var(--text-muted); margin:0.4rem 0 0 2.8rem;">Forces an immediate notification popup for all target users.</p>
                </div>

                <div id="broadcastFields" style="display:none; padding:1.5rem; background:rgba(0,0,0,0.02); border:1px dashed var(--border); border-radius:var(--radius-sm); margin-bottom:1.6rem;">
                    <div class="form-group">
                        <label>Broadcast Subject</label>
                        <input type="text" name="subject" id="modalSubject" class="form-control" placeholder="e.g. Urgent Health Update">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Priority Escalation</label>
                        <select name="priority" id="modalPriority" class="form-control">
                            <option value="Informational">Informational (Bell Only)</option>
                            <option value="Important">Important (Toast + Bell)</option>
                            <option value="Urgent">Urgent (Red Banner)</option>
                            <option value="Critical">Critical (Full Screen Alert)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Target Role (Everyone if empty)</label>
                    <select name="target_role" id="modalRole" class="form-control">
                        <option value="">-- All Active Users --</option>
                        <option value="admin">Administrators</option>
                        <option value="doctor">Doctors</option>
                        <option value="nurse">Nurses</option>
                        <option value="pharmacist">Pharmacists</option>
                        <option value="lab_technician">Laboratory Staff</option>
                        <option value="patient">Registered Patients</option>
                        <option value="staff">Facility Staff</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom:0; display:flex; align-items:center; gap:1rem;">
                    <input type="checkbox" name="is_active" id="modalActive" checked style="width:20px; height:20px; accent-color:var(--primary);">
                    <label style="margin:0; text-transform:none; font-size:1.1rem;">Activate this message immediately</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal()">Discard</button>
                <button type="submit" class="btn btn-primary">Publish Message</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($('#messagesTable').length) {
        $('#messagesTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[4, 'desc']],
            language: { search: "", searchPlaceholder: "Search communications..." }
        });
        $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
    }
});

function openModal(type) {
    document.getElementById('msgModal').classList.add('open');
    if (type === 'add') {
        document.getElementById('modalTitle').innerText = 'Draft New Message';
        document.getElementById('modalAction').value = 'add';
        document.getElementById('modalId').value = '';
        document.getElementById('modalText').value = '';
        document.getElementById('modalIsBroadcast').checked = false;
        toggleBroadcastFields(false);
    }
}

function closeModal() {
    document.getElementById('msgModal').classList.remove('open');
}

function editMsg(m) {
    document.getElementById('modalTitle').innerText = 'Edit Message Content';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalId').value = m.message_id;
    document.getElementById('modalText').value = m.message_text;
    document.getElementById('modalCat').value = m.message_category;
    document.getElementById('modalRole').value = m.target_role || '';
    document.getElementById('modalActive').checked = m.is_active == 1;
    document.getElementById('modalIsBroadcast').checked = false;
    toggleBroadcastFields(false);
    document.getElementById('msgModal').classList.add('open');
}

function toggleBroadcastFields(show) {
    document.getElementById('broadcastFields').style.display = show ? 'block' : 'none';
    document.getElementById('modalSubject').required = show;
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

const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
