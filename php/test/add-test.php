<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'tests';
$page_title  = 'Add Test';
include '../includes/_sidebar.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_name   = mysqli_real_escape_string($conn, trim($_POST['test_name'] ?? ''));
    $category    = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $cost        = (float)($_POST['cost'] ?? 0);
    $duration    = (int)($_POST['duration_minutes'] ?? 30);
    $is_active   = isset($_POST['is_active']) ? 1 : 0;

    if (!$test_name) {
        $error = 'Test name is required.';
    } else {
        // Generate test_id
        $last_t = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tests"))[0] ?? 0;
        $test_id = 'TST-' . str_pad($last_t + 1, 4, '0', STR_PAD_LEFT);

        $desc_val = $description ? "'$description'" : "NULL";
        $sql = "INSERT INTO tests (test_id, test_name, category, description, cost, duration_minutes, is_active)
                VALUES ('$test_id','$test_name','$category',$desc_val,$cost,$duration,$is_active)";
        if (mysqli_query($conn, $sql)) {
            header('Location: /RMU-Medical-Management-System/php/test/test.php?success=Test+added+successfully');
            exit();
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}

$categories = ['Haematology', 'Biochemistry', 'Microbiology', 'Immunology', 'Radiology',
                'Urinalysis', 'Parasitology', 'Serology', 'Histopathology', 'Other'];
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-flask" style="color:var(--primary);margin-right:.8rem;"></i>Add Diagnostic Test</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:.6rem;"></i>Add New Diagnostic Test</h1>
                <p>Register a new lab or diagnostic test to the system catalogue.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/test/test.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Tests
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
                        <div class="adm-card-header"><h3><i class="fas fa-flask"></i> Test Information</h3></div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Test Name <span class="req">*</span></label>
                                    <input type="text" name="test_name" class="adm-input" required
                                           placeholder="e.g. Full Blood Count (FBC)"
                                           value="<?php echo htmlspecialchars($_POST['test_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Category <span class="req">*</span></label>
                                    <select name="category" class="adm-input" required>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>" <?php echo (($_POST['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Estimated Duration (minutes)</label>
                                    <input type="number" name="duration_minutes" class="adm-input" min="5" max="480"
                                           placeholder="30" value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '30'); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Cost (GH₵) <span class="req">*</span></label>
                                    <input type="number" name="cost" class="adm-input" min="0" step="0.01" required
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['cost'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Description / Instructions</label>
                                    <textarea name="description" class="adm-input" rows="4"
                                              placeholder="Patient preparation, procedure description, expected results range..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header"><h3><i class="fas fa-toggle-on"></i> Status</h3></div>
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <label class="adm-switch-wrap">
                                <input type="checkbox" name="is_active" id="activeSwitch" <?php echo (!isset($_POST['test_name']) || isset($_POST['is_active'])) ? 'checked' : ''; ?>>
                                <div class="adm-switch"></div>
                                <span class="adm-switch-label">Test is Active</span>
                            </label>
                            <p style="font-size:1.2rem;color:var(--text-muted);margin-top:1rem;">Only active tests will be available for ordering.</p>
                        </div>
                    </div>

                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body" style="text-align:center;padding:2rem;">
                            <div style="width:64px;height:64px;background:linear-gradient(135deg,#9B59B6,#8E44AD);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                                <i class="fas fa-microscope" style="color:#fff;font-size:2rem;"></i>
                            </div>
                            <p style="font-size:1.2rem;color:var(--text-secondary);">A unique Test ID will be auto-generated (e.g. TST-0001).</p>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Add Test
                    </button>
                    <a href="/RMU-Medical-Management-System/php/test/test.php"
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
.adm-switch-wrap{display:flex;flex-direction:column;align-items:center;gap:1rem;cursor:pointer;}
.adm-switch{position:relative;width:52px;height:28px;background:var(--border);border-radius:50px;transition:var(--transition);}
.adm-switch::after{content:'';position:absolute;left:3px;top:3px;width:22px;height:22px;background:#fff;border-radius:50%;transition:var(--transition);}
input[type=checkbox]:checked ~ .adm-switch{background:var(--primary);}
input[type=checkbox]:checked ~ .adm-switch::after{left:27px;}
.adm-switch-wrap input{display:none;}
.adm-switch-label{font-size:1.3rem;font-weight:600;color:var(--text-primary);}
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
</script>
</body>
</html>
