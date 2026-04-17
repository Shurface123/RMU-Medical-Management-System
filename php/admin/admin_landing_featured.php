<?php

require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'landing_featured';
$page_title  = 'Featured Personnel';

$message = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_director') {
        $stmt = mysqli_prepare($conn, "UPDATE landing_director SET name=?, title=?, bio=?, message=?, qualifications=? WHERE director_id=1");
        mysqli_stmt_bind_param($stmt, "sssss", $_POST['n'], $_POST['t'], $_POST['b'], $_POST['m'], $_POST['q']);
        mysqli_stmt_execute($stmt);
        $message = "Director profile updated.";
    } elseif ($action === 'toggle_doctor') {
        $did = (int)$_POST['doc_id'];
        $st = (int)$_POST['state'];
        if ($st === 1) {
            // insert or update
            mysqli_query($conn, "INSERT INTO landing_doctors (doctor_id, is_featured, is_active) VALUES ($did, 1, 1) ON DUPLICATE KEY UPDATE is_featured=1, is_active=1");
        } else {
            mysqli_query($conn, "UPDATE landing_doctors SET is_featured=0, is_active=0 WHERE doctor_id=$did");
        }
        $message = "Doctor featured status updated.";
    } elseif ($action === 'add_staff') {
        $stmt = mysqli_prepare($conn, "INSERT INTO landing_staff (name, role_title, department, is_active) VALUES (?, ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "sss", $_POST['n'], $_POST['r'], $_POST['d']);
        mysqli_stmt_execute($stmt);
        $message = "Featured staff added.";
    } elseif ($action === 'delete_staff') {
        $id = (int)$_POST['entry_id'];
        mysqli_query($conn, "DELETE FROM landing_staff WHERE entry_id=$id");
        $message = "Featured staff removed.";
    }
}

// Fetch Doctors with featured flag mapped
$doctors = [];
$q_docs = mysqli_query($conn, "
    SELECT d.id, u.name, d.specialization, IFNULL(ld.is_featured, 0) as is_featured
    FROM doctors d 
    JOIN users u ON d.user_id = u.id 
    LEFT JOIN landing_doctors ld ON d.id = ld.doctor_id
    WHERE d.approval_status='approved'
");
while($r = mysqli_fetch_assoc($q_docs)) $doctors[] = $r;

// Fetch Director
$director = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM landing_director WHERE director_id=1"));

// Fetch Staff
$staffs = [];
$q_stf = mysqli_query($conn, "SELECT * FROM landing_staff ORDER BY entry_id DESC");
while($r = mysqli_fetch_assoc($q_stf)) $staffs[] = $r;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Featured Personnel - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .cfg-tabs { display:flex; gap:1rem; border-bottom:1px solid var(--border); margin-bottom:2rem; }
        .cfg-tab { padding:1rem 1.5rem; font-weight:600; color:var(--text-muted); cursor:pointer; border-bottom:3px solid transparent; }
        .cfg-tab.active, .cfg-tab:hover { color:var(--primary); border-bottom-color:var(--primary); }
        .cfg-pane { display:none; }
        .cfg-pane.active { display:block; animation:fadeIn 0.3s; }
        
        .lbl { display:block; font-weight:600; margin-bottom:.5rem; font-size:.9rem; color:var(--text-secondary); }
        .inp { width:100%; padding:.8rem 1rem; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font-family:inherit; margin-bottom:1rem; }
        .inp:focus { outline:none; border-color:var(--primary); }
        .card-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
        
        /* Toggle Switch */
        .switch { position:relative; display:inline-block; width:44px; height:24px; }
        .switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:24px; }
        .slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; }
        input:checked + .slider { background-color:var(--primary); }
        input:checked + .slider:before { transform:translateX(20px); }
    </style>
</head>
<body>
<?php include '../includes/_sidebar.php'; ?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-star" style="color:var(--warning);margin-right:10px;"></i> Featured Personnel Managers</span>
        </div>
    </div>
    
    <div class="adm-content">
        <?php if($message): ?>
            <div style="background:#10b98122; color:#10b981; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="cfg-tabs">
            <div class="cfg-tab active" onclick="switchTab('director')">Director's Profile</div>
            <div class="cfg-tab" onclick="switchTab('doctors')">Manage Featured Doctors</div>
            <div class="cfg-tab" onclick="switchTab('staff')">Manage Featured Staff</div>
        </div>

        <!-- DIRECTOR -->
        <div class="cfg-pane active" id="pane-director">
            <form method="POST" class="card-wrap">
                <input type="hidden" name="action" value="update_director">
                <h3>Director's Public Message</h3><br>
                <div style="display:flex; gap:1rem; flex-wrap:wrap;">
                    <div style="flex:1;"><label class="lbl">Full Name</label><input type="text" name="n" class="inp" value="<?= htmlspecialchars($director['name']??'') ?>"></div>
                    <div style="flex:1;"><label class="lbl">Official Title</label><input type="text" name="t" class="inp" value="<?= htmlspecialchars($director['title']??'') ?>"></div>
                </div>
                <label class="lbl">Brief Bio</label>
                <textarea name="b" class="inp" rows="2"><?= htmlspecialchars($director['bio']??'') ?></textarea>
                
                <label class="lbl">Full Welcome Message</label>
                <textarea name="m" class="inp" rows="6"><?= htmlspecialchars($director['message']??'') ?></textarea>
                
                <label class="lbl">Qualifications (comma separated)</label>
                <input type="text" name="q" class="inp" value="<?= htmlspecialchars($director['qualifications']??'') ?>">
                
                <button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Save Director Profile</span></button>
            </form>
        </div>

        <!-- DOCTORS -->
        <div class="cfg-pane" id="pane-doctors">
            <div class="card-wrap">
                <p style="color:var(--text-muted); margin-bottom:1rem;">Toggle which doctors appear on the public "Our Doctors" landing page. Only Approved doctors show here.</p>
                <table class="adm-table" style="width:100%;">
                    <tr><th>Doctor Name</th><th>Specialization</th><th>Featured on Public Site</th></tr>
                    <?php foreach($doctors as $d): ?>
                    <tr>
                        <td><b>Dr. <?= htmlspecialchars($d['name']) ?></b></td>
                        <td><?= htmlspecialchars($d['specialization']) ?></td>
                        <td>
                            <form method="POST" style="margin:0; display:inline-block;">
                                <input type="hidden" name="action" value="toggle_doctor">
                                <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="state" value="<?= $d['is_featured'] ? 0 : 1 ?>">
                                <label class="switch">
                                    <input type="checkbox" onchange="this.form.submit()" <?= $d['is_featured'] ? 'checked' : '' ?>>
                                    <span class="slider"></span>
                                </label>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- STAFF -->
        <div class="cfg-pane" id="pane-staff">
            <div class="card-wrap" style="background:var(--bg-secondary);">
                <h3>Add Featured Staff Member (Non-Doctor)</h3><br>
                <form method="POST" style="display:flex; gap:1rem; align-items:flex-end;">
                    <input type="hidden" name="action" value="add_staff">
                    <div style="flex:1;"><label class="lbl">Name</label><input type="text" name="n" class="inp" style="margin-bottom:0;" required></div>
                    <div style="flex:1;"><label class="lbl">Role / Title</label><input type="text" name="r" class="inp" style="margin-bottom:0;" required></div>
                    <div style="flex:1;"><label class="lbl">Department</label><input type="text" name="d" class="inp" style="margin-bottom:0;"></div>
                    <div><button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-plus"></i></span></button></div>
                </form>
            </div>
            
            <table class="adm-table" style="width:100%;" class="card-wrap">
                <tr><th>Name</th><th>Role</th><th>Department</th><th>Action</th></tr>
                <?php foreach($staffs as $s): ?>
                <tr>
                    <td><b><?= htmlspecialchars($s['name']) ?></b></td>
                    <td><?= htmlspecialchars($s['role_title']) ?></td>
                    <td><?= htmlspecialchars($s['department']) ?></td>
                    <td>
                        <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_staff"><input type="hidden" name="entry_id" value="<?= $s['entry_id'] ?>"><button class="btn btn-ghost" style="color:var(--danger);"><span class="btn-text"><i class="fas fa-trash"></i></span></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</main>
<script>
    function switchTab(t) {
        document.querySelectorAll('.cfg-pane').forEach(p=>p.classList.remove('active'));
        document.querySelectorAll('.cfg-tab').forEach(b=>b.classList.remove('active'));
        document.getElementById('pane-'+t).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>
</body>
</html>
