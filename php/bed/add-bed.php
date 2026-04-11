<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Add Bed';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bed_number = mysqli_real_escape_string($conn, trim($_POST['bed_number'] ?? ''));
    $ward       = mysqli_real_escape_string($conn, trim($_POST['ward'] ?? ''));
    $bed_type   = mysqli_real_escape_string($conn, $_POST['bed_type'] ?? 'Standard');
    $daily_rate = (float)($_POST['daily_rate'] ?? 0);
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
    $notes      = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if (!$bed_number || !$ward) {
        $error = 'Bed number and ward are required.';
    } else {
        // Check uniqueness
        $check = mysqli_query($conn, "SELECT id FROM beds WHERE bed_number='$bed_number' AND ward='$ward'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Bed number '{$bed_number}' already exists in ward '{$ward}'.";
        } else {
            $notes_val = $notes ? "'$notes'" : 'NULL';
            $sql = "INSERT INTO beds (bed_number, ward, bed_type, daily_rate, status, notes)
                    VALUES ('$bed_number','$ward','$bed_type',$daily_rate,'$status',$notes_val)";
            if (mysqli_query($conn, $sql)) {
                header('Location: /RMU-Medical-Management-System/php/bed/bed.php?success=Bed+added+successfully');
                exit();
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}

$wards = ['General Ward','Male Ward','Female Ward','Paediatric Ward','Maternity Ward',
          'Emergency Ward','ICU','HDU','Isolation Ward','Observation Ward'];
$bed_types = ['Standard','Semi-Private','Private','ICU','Recovery','Bariatric'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bed"></i> Add Bed</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Register New Bed</h1>
                <p>Add a new bed to the facility inventory with ward and type details.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="btn btn-ghost"><span class="btn-text">
                <i class="fas fa-arrow-left"></i> Back to Beds
            </span></a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate onsubmit="return handleFormSubmit(this);">
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-bed" style="color:#fff;"></i> Bed Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Bed Number <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="bed_number" class="adm-search-input" required
                                           placeholder="e.g. B-001"
                                           value="<?php echo htmlspecialchars($_POST['bed_number'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Ward <span style="color:var(--danger);">*</span></label>
                                    <select name="ward" class="adm-search-input" required>
                                        <option value="">— Select Ward —</option>
                                        <?php foreach ($wards as $w): ?>
                                        <option value="<?php echo htmlspecialchars($w); ?>" <?php echo (($_POST['ward'] ?? '') === $w) ? 'selected' : ''; ?>><?php echo htmlspecialchars($w); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Bed Type</label>
                                    <select name="bed_type" class="adm-search-input">
                                        <?php foreach ($bed_types as $bt): ?>
                                        <option value="<?php echo htmlspecialchars($bt); ?>" <?php echo (($_POST['bed_type'] ?? 'Standard') === $bt) ? 'selected' : ''; ?>><?php echo htmlspecialchars($bt); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Daily Rate (GH₵)</label>
                                    <input type="number" name="daily_rate" class="adm-search-input" min="0" step="0.50"
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['daily_rate'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Initial Status</label>
                                <div style="display:flex;flex-wrap:wrap;gap:1.2rem;">
                                    <?php
                                    $statuses = [
                                        'Available' => 'success',
                                        'Maintenance' => 'warning',
                                        'Reserved' => 'info',
                                    ];
                                    $sel_status = $_POST['status'] ?? 'Available';
                                    foreach ($statuses as $s => $color):
                                        $checked = ($sel_status === $s) ? 'checked' : '';
                                    ?>
                                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;background:var(--surface-2);padding:.6rem 1.2rem;border:1px solid var(--border);border-radius:6px;font-size:1.3rem;">
                                        <input type="radio" name="status" value="<?php echo htmlspecialchars($s); ?>" <?php echo $checked; ?> style="accent-color:var(--<?php echo $color; ?>);">
                                        <?php echo htmlspecialchars($s); ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Notes</label>
                                <textarea name="notes" class="adm-search-input" rows="3" style="resize:vertical;"
                                          placeholder="Special equipment, accessibility notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-bed" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);">New beds are available for patient assignment immediately after registration.</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;margin-bottom:1rem;"><span class="btn-text">
                        <i class="fas fa-save"></i> Add Bed
                    </span></button>
                    <a href="/RMU-Medical-Management-System/php/bed/bed.php"
                       class="btn btn-ghost" style="width:100%;justify-content:center;"><span class="btn-text">
                        Cancel
                    </span></a>
                </div>
            </div>
        </form>
    </div>
</main>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeIcon = document.getElementById('themeIcon');
document.getElementById('themeToggle')?.addEventListener('click', () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
});

function handleFormSubmit(form) {
    if(!form.checkValidity()) return true;
    const btn = form.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.style.pointerEvents = 'none';
    return true;
}
</script>
</body>
</html>
