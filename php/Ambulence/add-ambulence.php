<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'ambulance';
$page_title  = 'Add Ambulance';
include '../includes/_sidebar.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ambulance_id    = mysqli_real_escape_string($conn, strtoupper(trim($_POST['ambulance_id'] ?? '')));
    $vehicle_number  = mysqli_real_escape_string($conn, strtoupper(trim($_POST['vehicle_number'] ?? '')));
    $driver_name     = mysqli_real_escape_string($conn, trim($_POST['driver_name'] ?? ''));
    $driver_phone    = mysqli_real_escape_string($conn, trim($_POST['driver_phone'] ?? ''));
    $status          = mysqli_real_escape_string($conn, $_POST['status'] ?? 'Available');
    $last_service    = mysqli_real_escape_string($conn, $_POST['last_service_date'] ?? '');
    $next_service    = mysqli_real_escape_string($conn, $_POST['next_service_date'] ?? '');
    $model           = mysqli_real_escape_string($conn, trim($_POST['vehicle_model'] ?? ''));

    if (!$ambulance_id || !$vehicle_number) {
        $error = 'Ambulance ID and Vehicle Number are required.';
    } else {
        // Check uniqueness
        $check = mysqli_query($conn, "SELECT id FROM ambulances WHERE ambulance_id='$ambulance_id' OR vehicle_number='$vehicle_number'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'An ambulance with this ID or vehicle number already exists.';
        } else {
            $ls_val = $last_service ? "'$last_service'" : 'NULL';
            $ns_val = $next_service ? "'$next_service'" : 'NULL';
            $model_val = $model ? "'$model'" : 'NULL';
            $sql = "INSERT INTO ambulances (ambulance_id, vehicle_number, driver_name, driver_phone, status, last_service_date, next_service_date, vehicle_model)
                    VALUES ('$ambulance_id','$vehicle_number','$driver_name','$driver_phone','$status',$ls_val,$ns_val,$model_val)";
            if (mysqli_query($conn, $sql)) {
                header('Location: /RMU-Medical-Management-System/php/Ambulence/ambulence.php?success=Ambulance+registered+successfully');
                exit();
            } else {
                $error = 'Database error: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-ambulance" style="color:var(--primary);margin-right:.8rem;"></i>Add Ambulance</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:.6rem;"></i>Register New Ambulance</h1>
                <p>Add a new vehicle to the emergency fleet with driver and service details.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Fleet
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
                    <!-- Vehicle Details -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-car"></i> Vehicle Information</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Ambulance ID <span class="req">*</span></label>
                                    <input type="text" name="ambulance_id" class="adm-input" required
                                           placeholder="e.g. AMB-001"
                                           value="<?php echo htmlspecialchars($_POST['ambulance_id'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Vehicle Number <span class="req">*</span></label>
                                    <input type="text" name="vehicle_number" class="adm-input" required
                                           placeholder="e.g. GR-1234-23"
                                           value="<?php echo htmlspecialchars($_POST['vehicle_number'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Vehicle Model / Make</label>
                                    <input type="text" name="vehicle_model" class="adm-input"
                                           placeholder="e.g. Toyota HiAce 2022"
                                           value="<?php echo htmlspecialchars($_POST['vehicle_model'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Details -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-id-badge"></i> Driver Information</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Driver Name</label>
                                    <input type="text" name="driver_name" class="adm-input"
                                           placeholder="Driver's full name"
                                           value="<?php echo htmlspecialchars($_POST['driver_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Driver Phone</label>
                                    <input type="tel" name="driver_phone" class="adm-input"
                                           placeholder="0XXXXXXXXX"
                                           value="<?php echo htmlspecialchars($_POST['driver_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service Dates -->
                    <div class="adm-card">
                        <div class="adm-card-header"><h3><i class="fas fa-tools"></i> Service Schedule</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Last Service Date</label>
                                    <input type="date" name="last_service_date" class="adm-input"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['last_service_date'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Next Service Date</label>
                                    <input type="date" name="next_service_date" class="adm-input"
                                           value="<?php echo htmlspecialchars($_POST['next_service_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Status -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-traffic-light"></i> Initial Status</h3></div>
                        <div class="adm-card-body">
                            <div style="display:flex;flex-direction:column;gap:1rem;">
                                <?php
                                $statuses = [
                                    'Available' => ['fas fa-check-circle','success'],
                                    'On Duty' => ['fas fa-running','warning'],
                                    'Maintenance' => ['fas fa-tools','danger'],
                                ];
                                $sel = $_POST['status'] ?? 'Available';
                                foreach ($statuses as $s => [$icon, $color]):
                                ?>
                                <label class="adm-status-option-row <?php echo ($sel === $s) ? 'selected' : ''; ?>">
                                    <input type="radio" name="status" value="<?php echo $s; ?>" <?php echo ($sel === $s) ? 'checked' : ''; ?>>
                                    <i class="<?php echo $icon; ?>" style="color:var(--<?php echo $color; ?>);font-size:1.8rem;width:2rem;"></i>
                                    <span><?php echo $s; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#E74C3C,#C0392B);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-ambulance" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);">The ambulance will be immediately visible to dispatchers and available for emergency requests.</p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Register Ambulance
                    </button>
                    <a href="/RMU-Medical-Management-System/php/Ambulence/ambulence.php"
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
.adm-status-option-row{display:flex;align-items:center;gap:1.2rem;padding:1.2rem 1.5rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:var(--transition);}
.adm-status-option-row:hover,.adm-status-option-row.selected{border-color:var(--primary);background:var(--primary-light);}
.adm-status-option-row input{display:none;}
.adm-status-option-row span{font-size:1.4rem;font-weight:600;color:var(--text-primary);}
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
document.querySelectorAll('.adm-status-option-row').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.adm-status-option-row').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
    });
});
</script>
</body>
</html>
