<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Add Bed';
include '../includes/_sidebar.php';

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
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-bed" style="color:var(--primary);margin-right:.8rem;"></i>Add Bed</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:.6rem;"></i>Register New Bed</h1>
                <p>Add a new bed to the facility inventory with ward and type details.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/bed/bed.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Beds
            </a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;" class="adm-form-layout">
                <div>
                    <div class="adm-card">
                        <div class="adm-card-header"><h3><i class="fas fa-bed"></i> Bed Details</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Bed Number <span class="req">*</span></label>
                                    <input type="text" name="bed_number" class="adm-input" required
                                           placeholder="e.g. B-001"
                                           value="<?php echo htmlspecialchars($_POST['bed_number'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Ward <span class="req">*</span></label>
                                    <select name="ward" class="adm-input" required>
                                        <option value="">— Select Ward —</option>
                                        <?php foreach ($wards as $w): ?>
                                        <option value="<?php echo $w; ?>" <?php echo (($_POST['ward'] ?? '') === $w) ? 'selected' : ''; ?>><?php echo $w; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Bed Type</label>
                                    <select name="bed_type" class="adm-input">
                                        <?php foreach ($bed_types as $bt): ?>
                                        <option value="<?php echo $bt; ?>" <?php echo (($_POST['bed_type'] ?? 'Standard') === $bt) ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Daily Rate (GH₵)</label>
                                    <input type="number" name="daily_rate" class="adm-input" min="0" step="0.50"
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['daily_rate'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Initial Status</label>
                                    <div style="display:flex;gap:1.2rem;flex-wrap:wrap;">
                                        <?php
                                        $statuses = [
                                            'Available' => ['fas fa-check-circle','success'],
                                            'Maintenance' => ['fas fa-tools','warning'],
                                            'Reserved' => ['fas fa-clock','info'],
                                        ];
                                        $sel_status = $_POST['status'] ?? 'Available';
                                        foreach ($statuses as $s => [$icon, $color]):
                                        ?>
                                        <label class="adm-status-option <?php echo ($sel_status === $s) ? 'selected' : ''; ?>">
                                            <input type="radio" name="status" value="<?php echo $s; ?>" <?php echo ($sel_status === $s) ? 'checked' : ''; ?>>
                                            <i class="<?php echo $icon; ?>" style="color:var(--<?php echo $color; ?>);font-size:2rem;"></i>
                                            <span><?php echo $s; ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Notes</label>
                                    <textarea name="notes" class="adm-input" rows="2"
                                              placeholder="Special equipment, accessibility notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#27AE60,#2ECC71);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-bed" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);">New beds are available for patient assignment immediately after registration.</p>
                        </div>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Add Bed
                    </button>
                    <a href="/RMU-Medical-Management-System/php/bed/bed.php"
                       class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</main>

<style>
.adm-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.8rem;}
.adm-span-2{grid-column:span 2;}
.adm-form-group{display:flex;flex-direction:column;gap:.6rem;}
.adm-label{font-size:1.3rem;font-weight:600;color:var(--text-secondary);}
.adm-label .req{color:var(--danger);}
.adm-input{padding:1.1rem 1.4rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:1.4rem;color:var(--text-primary);background:var(--surface);outline:none;transition:var(--transition);width:100%;}
.adm-input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.1);}
textarea.adm-input{resize:vertical;}
.adm-status-option{display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:1.2rem 2rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:var(--transition);}
.adm-status-option:hover,.adm-status-option.selected{border-color:var(--primary);background:var(--primary-light);}
.adm-status-option input{display:none;}
.adm-status-option span{font-size:1.2rem;font-weight:600;color:var(--text-primary);}
@media(max-width:900px){.adm-form-layout{grid-template-columns:1fr!important;}.adm-form-grid{grid-template-columns:1fr!important;}.adm-span-2{grid-column:span 1;}}
</style>

<script>
const sidebar=document.getElementById('admSidebar');
const overlay=document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click',()=>{sidebar.classList.toggle('active');overlay.classList.toggle('active');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('active');overlay.classList.remove('active');});
const themeToggle=document.getElementById('themeToggle');
const themeIcon=document.getElementById('themeIcon');
const html=document.documentElement;
function applyTheme(t){html.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
themeToggle?.addEventListener('click',()=>applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
document.querySelectorAll('.adm-status-option').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.adm-status-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
    });
});
</script>
</body>
</html>
