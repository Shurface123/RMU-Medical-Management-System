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
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
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

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.6rem; }
@media(max-width:768px){.form-row{grid-template-columns:1fr;}}
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 2rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem; }
.filter-tabs .ftab { padding:.8rem 1.8rem;border-radius:20px;font-size:1.15rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }
.cfg-pane { display:none; animation:fadeIn 0.3s; }
.cfg-pane.active { display:block; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

/* ── Toggle Switch ── */
.switch { position:relative; display:inline-block; width:44px; height:24px; margin:0;}
.switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; inset:0; background-color:var(--border); transition:.3s; border-radius:24px; }
.slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background-color:white; transition:.3s; border-radius:50%; box-shadow:0 2px 4px rgba(0,0,0,0.2);}
input:checked + .slider { background-color:var(--success); }
input:checked + .slider:before { transform:translateX(20px); }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

/* ── Detail Grid ── */
.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-star" style="color:var(--warning);margin-right:10px;"></i> Featured Personnel Managers</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-user-md hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-star"></i></div>
            <div class="staff-hero-info">
                <h2>Featured Personnel</h2>
                <p>Manage the director's profile and control which staff members appear on the public landing page.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="../index.php#doctors" target="_blank" class="btn" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-external-link-alt"></i> Preview Public Site
                </a>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="ftab active" onclick="switchTab('director', this)">Director's Profile</button>
            <button class="ftab" onclick="switchTab('doctors', this)">Manage Featured Doctors</button>
            <button class="ftab" onclick="switchTab('staff', this)">Manage Featured Staff</button>
        </div>

        <!-- DIRECTOR -->
        <div class="cfg-pane active" id="pane-director">
            <form method="POST">
                <input type="hidden" name="action" value="update_director">
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-user-tie" style="color:var(--primary);"></i> Director's Public Message</h3></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Full Name</label>
                                <input type="text" name="n" class="form-control" value="<?= htmlspecialchars($director['name']??'') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Official Title</label>
                                <input type="text" name="t" class="form-control" value="<?= htmlspecialchars($director['title']??'') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Brief Bio</label>
                            <textarea name="b" class="form-control" rows="3"><?= htmlspecialchars($director['bio']??'') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Full Welcome Message</label>
                            <textarea name="m" class="form-control" rows="6"><?= htmlspecialchars($director['message']??'') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Qualifications (comma separated)</label>
                            <input type="text" name="q" class="form-control" value="<?= htmlspecialchars($director['qualifications']??'') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Director Profile</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- DOCTORS -->
        <div class="cfg-pane" id="pane-doctors">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-stethoscope" style="color:var(--primary);"></i> Public Doctors Registry</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div style="padding:1.5rem 2rem; background:var(--surface-2); border-bottom:1px solid var(--border);">
                        <p style="color:var(--text-secondary); font-size:1.1rem; margin:0;"><i class="fas fa-info-circle"></i> Toggle which doctors appear on the public "Our Doctors" landing page. Only Approved doctors show here.</p>
                    </div>
                    <table class="stf-table" id="doctorsTable">
                        <thead>
                            <tr>
                                <th>Doctor Name</th>
                                <th>Specialization</th>
                                <th style="text-align:right;">Featured on Public Site</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($doctors as $d): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <div style="width:40px;height:40px;border-radius:50%;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;">
                                            <?= strtoupper(substr($d['name'], 0, 1)) ?>
                                        </div>
                                        <strong style="font-size:1.2rem;">Dr. <?= htmlspecialchars($d['name']) ?></strong>
                                    </div>
                                </td>
                                <td><span style="background:var(--surface-2); padding:0.4rem 0.8rem; border-radius:12px; font-size:1rem; font-weight:600; color:var(--text-secondary); border:1px solid var(--border);"><?= htmlspecialchars($d['specialization']) ?></span></td>
                                <td style="text-align:right;">
                                    <form method="POST" style="margin:0; display:inline-flex; align-items:center; gap:0.5rem;">
                                        <input type="hidden" name="action" value="toggle_doctor">
                                        <input type="hidden" name="doc_id" value="<?= $d['id'] ?>">
                                        <input type="hidden" name="state" value="<?= $d['is_featured'] ? 0 : 1 ?>">
                                        <span style="font-size:1rem; color:<?= $d['is_featured'] ? 'var(--success)' : 'var(--text-muted)' ?>; font-weight:600;">
                                            <?= $d['is_featured'] ? 'Visible' : 'Hidden' ?>
                                        </span>
                                        <label class="switch">
                                            <input type="checkbox" onchange="this.form.submit()" <?= $d['is_featured'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STAFF -->
        <div class="cfg-pane" id="pane-staff">
            <div class="card" style="background:var(--surface-2);">
                <div class="card-body">
                    <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.4rem; color:var(--text-primary);">Add Featured Staff Member (Non-Doctor)</h3>
                    <form method="POST" class="form-row" style="align-items:flex-end; grid-template-columns:1fr 1fr 1fr auto; margin-bottom:0;">
                        <input type="hidden" name="action" value="add_staff">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Name</label>
                            <input type="text" name="n" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Role / Title</label>
                            <input type="text" name="r" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Department</label>
                            <input type="text" name="d" class="form-control">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <button type="submit" class="btn btn-primary" style="height:54px;"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <table class="stf-table">
                    <thead>
                        <tr>
                            <th>Staff Detail</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($staffs as $s): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:1rem;">
                                    <div style="width:40px;height:40px;border-radius:50%;background:var(--success-light);color:var(--success);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;">
                                        <?= strtoupper(substr($s['name'], 0, 1)) ?>
                                    </div>
                                    <strong style="font-size:1.2rem;"><?= htmlspecialchars($s['name']) ?></strong>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($s['role_title']) ?></td>
                            <td><span style="background:var(--surface-2); padding:0.3rem 0.6rem; border-radius:12px; font-size:0.9rem; font-weight:600; border:1px solid var(--border);"><?= htmlspecialchars($s['department']) ?></span></td>
                            <td style="text-align:right;">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_staff">
                                    <input type="hidden" name="entry_id" value="<?= $s['entry_id'] ?>">
                                    <button class="btn btn-ghost" style="color:var(--danger);"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    <?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', () => { showToast(<?= json_encode($message) ?>, 'success'); });
    <?php endif; ?>

    $(document).ready(function() {
        $('#doctorsTable').DataTable({
            responsive: true,
            pageLength: 10,
            language: { search: "", searchPlaceholder: "Search doctors..." }
        });
        
        // style datatables
        $('.dataTables_filter input').addClass('form-control').css({'width':'250px','display':'inline-block', 'margin-left':'10px'});
    });

    function switchTab(t, btn) {
        document.querySelectorAll('.cfg-pane').forEach(p=>p.classList.remove('active'));
        document.querySelectorAll('.ftab').forEach(b=>b.classList.remove('active'));
        document.getElementById('pane-'+t).classList.add('active');
        btn.classList.add('active');
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
