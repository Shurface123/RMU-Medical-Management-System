<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'staff';
$page_title  = 'Staff Management';
include '../includes/_sidebar.php';

// ── Search ────────────────────────────────────────────────────────────────
$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';
$role_f = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';

$where_parts = ["u.user_role NOT IN ('doctor','patient','admin')"];
if ($search) $where_parts[] = "(u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR s.staff_id LIKE '%$search%')";
if ($role_f) $where_parts[] = "u.user_role = '$role_f'";
$where = 'WHERE ' . implode(' AND ', $where_parts);

// ── Stats ─────────────────────────────────────────────────────────────────
$total_staff  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.is_active=1 AND u.user_role NOT IN ('doctor','patient','admin')"))[0] ?? 0;
$total_all    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.user_role NOT IN ('doctor','patient','admin')"))[0] ?? 0;
$pharma       = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.user_role='pharmacist' AND u.is_active=1"))[0] ?? 0;
$nurses       = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.user_role='nurse' AND u.is_active=1"))[0] ?? 0;
$others       = $total_staff - $pharma - $nurses;
?>

<!-- DataTables Dependencies -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<style>
/* ── V2 Design System ── */
.staff-v2-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: white;
    padding: 3rem;
    border-radius: var(--radius-lg);
    margin-bottom: 3rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.staff-v2-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url('/RMU-Medical-Management-System/image/pattern.png');
    opacity: 0.05;
    pointer-events: none;
}

.stat-v2-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.stat-v2-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 2.2rem;
    display: flex;
    align-items: center;
    gap: 1.8rem;
    transition: var(--transition);
}
.stat-v2-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
}

.staff-avatar {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--primary-light);
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: 800;
    flex-shrink: 0;
}
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-users-cog" style="color:var(--primary);margin-right:.8rem;"></i>Staff Hub</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid var(--primary-light);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;" class="prof-display-img">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <!-- V2 Hero Section -->
        <div class="staff-v2-hero">
            <div style="z-index:10;">
                <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 0.5rem;">Personnel Registry</h1>
                <p style="opacity: 0.9; font-size: 1.3rem;">Manage pharmacists, nurses, technicians, and support staff across all departments.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/register.php?role=staff" class="btn btn-primary" style="background:white; color:#0f172a; border:none; padding:1.2rem 2.5rem; font-weight:700; border-radius:15px; box-shadow:0 10px 20px rgba(0,0,0,0.2); z-index:10;"><span class="btn-text">
                <i class="fas fa-user-plus"></i> Onboard Staff
            </span></a>
        </div>

        <!-- V2 Telemetry Stats -->
        <div class="stat-v2-grid">
            <div class="stat-v2-card" style="border-bottom:4px solid var(--primary);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--primary);"><?= $total_all ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Total Records</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--success);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--success);"><?= $total_staff ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Active Personnel</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid #9b59b6;">
                <div style="font-size:2.8rem; font-weight:900; color:#9b59b6;"><?= $nurses ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Active Nurses</div>
            </div>
            <div class="stat-v2-card" style="border-bottom:4px solid var(--warning);">
                <div style="font-size:2.8rem; font-weight:900; color:var(--warning);"><?= $pharma ?></div>
                <div style="font-size:1rem; color:var(--text-muted); text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-left:1rem;">Pharmacists</div>
            </div>
        </div>

        <!-- V2 Filters -->
        <form method="get" class="adm-card" style="padding:1.5rem 2.5rem; display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-end; margin-bottom:2.5rem; border-radius:20px;">
            <div style="flex:1; min-width:280px;">
                <label style="display:block; font-weight:700; color:var(--text-muted); margin-bottom:0.5rem; text-transform:uppercase; font-size:0.9rem;">Search Database</label>
                <input type="text" name="q" class="adm-search-input" style="width:100%; padding:1rem; border-radius:12px;" placeholder="Name, Email, or Staff ID..." value="<?= htmlspecialchars($_GET['q']??'') ?>">
            </div>
            <div style="min-width:220px;">
                <label style="display:block; font-weight:700; color:var(--text-muted); margin-bottom:0.5rem; text-transform:uppercase; font-size:0.9rem;">Role Filter</label>
                <select name="role" class="adm-search-input" style="width:100%; padding:1rem; border-radius:12px;">
                    <option value="">All Staff Roles</option>
                    <?php 
                    $roles_list = ['pharmacist','nurse','lab_technician','receptionist',
                                   'ambulance_driver','cleaner','laundry_staff','maintenance','security','kitchen_staff','finance_officer','staff'];
                    foreach ($roles_list as $r): 
                    ?>
                    <option value="<?= $r ?>" <?= ($role_f===$r)?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:1rem;">
                <button type="submit" class="btn btn-primary" style="padding:1rem 2rem; border-radius:12px;"><span class="btn-text"><i class="fas fa-filter"></i> Apply Filters</span></button>
                <a href="staff.php" class="btn btn-outline" style="padding:1rem 2rem; border-radius:12px;"><i class="fas fa-undo"></i> Reset</a>
            </div>
        </form>

        <!-- V2 DataTables Integration -->
        <div class="adm-card" style="padding:2.5rem; border-radius:24px;">
            <table class="clinical-table display responsive nowrap" id="staffTable" style="width:100%;">
                <thead>
                    <tr>
                        <th>Personnel</th>
                        <th>Identifier / Role</th>
                        <th>Contact Matrix</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Profile</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT s.id, s.staff_id, s.department, s.position, s.approval_status, s.profile_completeness,
                                   u.name, u.email, u.phone, u.user_role, u.is_active, u.created_at
                            FROM staff s
                            JOIN users u ON s.user_id = u.id
                            $where
                            ORDER BY u.name ASC";
                    $sq = mysqli_query($conn, $sql);
                    while ($st = mysqli_fetch_assoc($sq)):
                        $role_label = ucfirst(str_replace('_', ' ', $st['user_role']));
                        $role_colors = [
                            'ambulance_driver' => 'primary', 'cleaner' => 'info', 'laundry_staff' => 'warning',
                            'maintenance' => 'success', 'security' => 'danger', 'kitchen_staff' => 'warning',
                            'pharmacist' => 'warning', 'nurse' => 'success', 'lab_technician' => 'primary'
                        ];
                        $role_cls = $role_colors[$st['user_role']] ?? 'secondary';
                        $comp = (int)($st['profile_completeness'] ?? 0);
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:1rem;">
                                <div class="staff-avatar"><?= strtoupper(substr($st['name'],0,1)) ?></div>
                                <div>
                                    <div style="font-weight:800; font-size:1.1rem; color:var(--text-primary);"><?= htmlspecialchars($st['name']) ?></div>
                                    <div style="font-size:0.85rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-calendar-alt"></i> Joined <?= $st['created_at'] ? date('M Y', strtotime($st['created_at'])) : '—' ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:800; font-size:1.1rem; color:var(--text-primary); letter-spacing:1px; margin-bottom:0.3rem;"><?= htmlspecialchars($st['staff_id']) ?></div>
                            <span class="adm-badge adm-badge-<?= $role_cls ?>" style="font-size:0.8rem;"><?= $role_label ?></span>
                        </td>
                        <td>
                            <div style="font-weight:600; color:var(--text-primary); margin-bottom:0.2rem;"><i class="fas fa-envelope" style="color:var(--text-muted); margin-right:0.5rem;"></i> <?= htmlspecialchars($st['email']) ?></div>
                            <div style="font-size:0.9rem; color:var(--text-muted);"><i class="fas fa-phone" style="margin-right:0.5rem;"></i> <?= htmlspecialchars($st['phone'] ?? 'N/A') ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700; color:var(--text-primary);"><?= htmlspecialchars($st['department'] ?? 'General') ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($st['position'] ?? 'Staff') ?></div>
                        </td>
                        <td>
                            <?php if (isset($st['approval_status']) && $st['approval_status'] === 'pending'): ?>
                                <span class="adm-badge" style="background:rgba(243,156,18,0.1); color:#F39C12;"><i class="fas fa-clock"></i> Pending Review</span>
                            <?php elseif (isset($st['approval_status']) && $st['approval_status'] === 'rejected'): ?>
                                <span class="adm-badge adm-badge-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                            <?php elseif ($st['is_active']): ?>
                                <span class="adm-badge adm-badge-success"><i class="fas fa-check-circle"></i> Active</span>
                            <?php else: ?>
                                <span class="adm-badge adm-badge-danger"><i class="fas fa-ban"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="min-width:120px;">
                            <div style="font-size:0.8rem; font-weight:700; color:var(--text-secondary); margin-bottom:0.4rem; display:flex; justify-content:space-between;">
                                <span>Completeness</span>
                                <span style="color:<?= $comp>=80?'var(--success)':($comp>=50?'var(--warning)':'var(--danger)') ?>"><?= $comp ?>%</span>
                            </div>
                            <div style="width:100%; height:6px; background:var(--border); border-radius:3px; overflow:hidden;">
                                <div style="width:<?= $comp ?>%; height:100%; background:<?= $comp>=80?'var(--success)':($comp>=50?'var(--warning)':'var(--danger)') ?>; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; gap:0.5rem;">
                                <a href="/RMU-Medical-Management-System/php/staff/edit_staff.php?id=<?= $st['id'] ?>" class="btn btn-primary btn-sm" style="padding:0.5rem 1rem; border-radius:8px;" title="Edit Profile"><i class="fas fa-user-edit"></i></a>
                                <a href="/RMU-Medical-Management-System/php/staff/deactivate_staff.php?id=<?= $st['id'] ?>" class="btn btn-outline btn-sm" style="padding:0.5rem 1rem; border-radius:8px; color:<?= $st['is_active']?'var(--danger)':'var(--success)' ?>; border-color:currentcolor;" onclick="return confirm('Toggle activation status for this staff member?');" title="<?= $st['is_active']?'Deactivate':'Activate' ?>"><i class="fas fa-<?= $st['is_active']?'user-slash':'user-check' ?>"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    $('#staffTable').DataTable({
        responsive: true,
        pageLength: 15,
        language: { search: "_INPUT_", searchPlaceholder: "Quick filter..." },
        dom: '<"top"f>rt<"bottom"lip><"clear">',
    });

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const html        = document.documentElement;
    function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
    themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
    
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>