<?php
include 'db_conn.php';

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
$total_staff  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.is_active=1"))[0] ?? 0;
$total_all    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff"))[0] ?? 0;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-users-cog" style="color:var(--primary);margin-right:.8rem;"></i>Staff Management</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Staff Management</h1>
                <p>Manage all non-clinical staff members — pharmacists, nurses, receptionists, and lab technicians.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/register.php?role=staff" class="adm-btn adm-btn-primary">
                <i class="fas fa-user-plus"></i> Add Staff Member
            </a>
        </div>

        <div class="adm-summary-strip">
            <div class="adm-mini-card">
                <div class="adm-mini-card-num"><?php echo $total_all; ?></div>
                <div class="adm-mini-card-label">Total Staff</div>
            </div>
            <div class="adm-mini-card">
                <div class="adm-mini-card-num green"><?php echo $total_staff; ?></div>
                <div class="adm-mini-card-label">Active</div>
            </div>
            <div class="adm-mini-card">
                <?php $nurses    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.user_role='nurse'"))[0] ?? 0; ?>
                <div class="adm-mini-card-num blue"><?php echo $nurses; ?></div>
                <div class="adm-mini-card-label">Nurses</div>
            </div>
            <div class="adm-mini-card">
                <?php $pharma = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM staff s JOIN users u ON s.user_id=u.id WHERE u.user_role='pharmacist'"))[0] ?? 0; ?>
                <div class="adm-mini-card-num orange"><?php echo $pharma; ?></div>
                <div class="adm-mini-card-label">Pharmacists</div>
            </div>
        </div>

        <!-- Search & Filter -->
        <form method="get" class="adm-card" style="padding:1rem 1.5rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-bottom:1rem;">
            <div style="flex:1;min-width:200px;">
                <input type="text" name="q" class="adm-search-input" placeholder="Search by name, email or Staff ID…" value="<?php echo htmlspecialchars($_GET['q']??''); ?>">
            </div>
            <div style="min-width:150px;">
                <select name="role" class="adm-search-input">
                    <option value="">All Roles</option>
                    <?php foreach (['nurse','pharmacist','receptionist','lab_technician','cleaner','security'] as $r): ?>
                    <option value="<?php echo $r; ?>" <?php echo ($role_f===$r)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$r)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="staff.php" class="adm-btn adm-btn-back"><i class="fas fa-times"></i> Clear</a>
            </div>
        </form>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-users"></i> Staff Register</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT s.id, s.staff_id, s.department, s.position,
                                       u.name, u.email, u.phone, u.user_role, u.is_active, u.created_at
                                FROM staff s
                                JOIN users u ON s.user_id = u.id
                                $where
                                ORDER BY u.name ASC";
                        $sq = mysqli_query($conn, $sql);
                        if (!$sq || mysqli_num_rows($sq) === 0) {
                            echo "<tr><td colspan='10' style='text-align:center;padding:3rem;color:var(--text-muted);'>No staff members found.</td></tr>";
                        } else {
                            $n = 1;
                            while ($st = mysqli_fetch_assoc($sq)):
                                $role_label = ucfirst(str_replace('_', ' ', $st['user_role']));
                                $role_cls = ($st['user_role']==='nurse') ? 'info' : (($st['user_role']==='pharmacist') ? 'warning' : 'primary');
                        ?>
                        <tr>
                            <td><?php echo $n++; ?></td>
                            <td><span class="adm-badge adm-badge-primary"><?php echo htmlspecialchars($st['staff_id']); ?></span></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:.75rem;">
                                    <div style="width:34px;height:34px;border-radius:50%;background:var(--primary-light);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.85rem;flex-shrink:0;">
                                        <?php echo strtoupper(substr($st['name'],0,1)); ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($st['name']); ?></div>
                                        <div style="font-size:.75rem;color:var(--text-muted);"><?php echo htmlspecialchars($st['position'] ?? ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="adm-badge adm-badge-<?php echo $role_cls; ?>"><?php echo $role_label; ?></span></td>
                            <td><?php echo htmlspecialchars($st['department'] ?? '—'); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($st['email']); ?>" style="color:var(--primary);"><?php echo htmlspecialchars($st['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($st['phone'] ?? '—'); ?></td>
                            <td><?php echo $st['created_at'] ? date('d M Y', strtotime($st['created_at'])) : '—'; ?></td>
                            <td>
                                <?php if ($st['is_active']): ?>
                                    <span class="adm-badge adm-badge-success">Active</span>
                                <?php else: ?>
                                    <span class="adm-badge adm-badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="adm-table-actions">
                                    <a href="/RMU-Medical-Management-System/php/staff/edit_staff.php?id=<?php echo $st['id']; ?>"
                                       class="adm-btn adm-btn-warning adm-btn-sm"><i class="fas fa-edit"></i></a>
                                    <a href="/RMU-Medical-Management-System/php/staff/deactivate_staff.php?id=<?php echo $st['id']; ?>"
                                       class="adm-btn adm-btn-<?php echo $st['is_active']?'danger':'success'; ?> adm-btn-sm"
                                       onclick="return confirm('<?php echo $st['is_active']?'Deactivate':'Activate'; ?> this staff member?');">
                                        <i class="fas fa-<?php echo $st['is_active']?'user-slash':'user-check'; ?>"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeToggle = document.getElementById('themeToggle');
const themeIcon   = document.getElementById('themeIcon');
const html        = document.documentElement;
function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
</script>
</body>
</html>