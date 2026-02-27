<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'medicine';
$page_title  = 'Add Medicine';
include '../includes/_sidebar.php';

// Handle form submission (POST to self)
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = mysqli_real_escape_string($conn, trim($_POST['medicine_name'] ?? ''));
    $generic    = mysqli_real_escape_string($conn, trim($_POST['generic_name'] ?? ''));
    $category   = mysqli_real_escape_string($conn, trim($_POST['category'] ?? ''));
    $stock      = (int)($_POST['stock_quantity'] ?? 0);
    $reorder    = (int)($_POST['reorder_level'] ?? 10);
    $price      = (float)($_POST['unit_price'] ?? 0);
    $expiry     = mysqli_real_escape_string($conn, $_POST['expiry_date'] ?? '');
    $rx         = isset($_POST['is_prescription_required']) ? 1 : 0;
    $desc       = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));

    // Auto-generate medicine_id
    $last = mysqli_fetch_row(mysqli_query($conn, "SELECT MAX(id) FROM medicines"))[0] ?? 0;
    $med_id = 'MED-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);

    if (!$name) {
        $error = 'Medicine name is required.';
    } else {
        $expiry_val = $expiry ? "'$expiry'" : "NULL";
        $desc_val   = $desc   ? "'$desc'"   : "NULL";
        $sql = "INSERT INTO medicines (medicine_id, medicine_name, generic_name, category, stock_quantity, reorder_level, unit_price, expiry_date, is_prescription_required, description)
                VALUES ('$med_id','$name','$generic','$category',$stock,$reorder,$price,$expiry_val,$rx,$desc_val)";
        if (mysqli_query($conn, $sql)) {
            header('Location: /RMU-Medical-Management-System/php/medicine/medicine.php?success=Medicine+added+successfully');
            exit();
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}

// Medicine categories
$categories = ['Analgesics', 'Antibiotics', 'Antihistamines', 'Antifungals', 'Antiparasitics',
                'Antiseptics', 'Cardiovascular', 'Dermatological', 'Diabetic', 'Gastrointestinal',
                'Immunosuppressants', 'Multivitamins', 'Respiratory', 'Supplements', 'Other'];
?>

<main class="adm-main">
    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-pills" style="color:var(--primary);margin-right:.8rem;"></i>Add Medicine</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Page Header -->
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:.6rem;"></i>Add New Medicine</h1>
                <p>Register a new medicine or pharmaceutical product to the inventory.</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" class="adm-btn adm-btn-ghost">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="addMedForm" novalidate>
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;" class="adm-form-layout">

                <!-- Left Column — Main Info -->
                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-info-circle"></i> Medicine Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">

                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Medicine Name <span class="req">*</span></label>
                                    <input type="text" name="medicine_name" class="adm-input" required
                                           placeholder="e.g. Paracetamol 500mg"
                                           value="<?php echo htmlspecialchars($_POST['medicine_name'] ?? ''); ?>">
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-label">Generic Name</label>
                                    <input type="text" name="generic_name" class="adm-input"
                                           placeholder="e.g. Acetaminophen"
                                           value="<?php echo htmlspecialchars($_POST['generic_name'] ?? ''); ?>">
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

                                <div class="adm-form-group adm-span-2">
                                    <label class="adm-label">Description / Notes</label>
                                    <textarea name="description" class="adm-input" rows="3"
                                              placeholder="Optional: dosage notes, contraindications, storage instructions..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Info -->
                    <div class="adm-card">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-boxes"></i> Stock Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div class="adm-form-grid">
                                <div class="adm-form-group">
                                    <label class="adm-label">Initial Stock Quantity <span class="req">*</span></label>
                                    <input type="number" name="stock_quantity" class="adm-input" min="0" required
                                           placeholder="0" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-label">Reorder Level</label>
                                    <input type="number" name="reorder_level" class="adm-input" min="0"
                                           placeholder="10" value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '10'); ?>">
                                    <small class="adm-hint">Alert when stock falls to this level</small>
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-label">Unit Price (GH₵) <span class="req">*</span></label>
                                    <input type="number" name="unit_price" class="adm-input" min="0" step="0.01" required
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>">
                                </div>

                                <div class="adm-form-group">
                                    <label class="adm-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="adm-input"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column — Type & Submit -->
                <div>
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-tag"></i> Classification</h3>
                        </div>
                        <div class="adm-card-body">
                            <label class="adm-label" style="margin-bottom:1rem;display:block;">Dispensing Type</label>
                            <div class="adm-toggle-group">
                                <label class="adm-toggle-option <?php echo !($rx_post = ($_POST['is_prescription_required'] ?? false)) ? 'selected' : ''; ?>">
                                    <input type="radio" name="is_prescription_required" value="0" <?php echo !$rx_post ? 'checked' : ''; ?>>
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>OTC</span>
                                    <small>Over-the-Counter</small>
                                </label>
                                <label class="adm-toggle-option <?php echo $rx_post ? 'selected' : ''; ?>">
                                    <input type="radio" name="is_prescription_required" value="1" <?php echo $rx_post ? 'checked' : ''; ?>>
                                    <i class="fas fa-prescription"></i>
                                    <span>Rx</span>
                                    <small>Prescription Required</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="adm-card" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
                        <div class="adm-card-body">
                            <div style="text-align:center;padding:1rem 0;">
                                <div style="width:64px;height:64px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;">
                                    <i class="fas fa-pills" style="color:#fff;font-size:2rem;"></i>
                                </div>
                                <h3 style="font-size:1.4rem;color:var(--primary);margin-bottom:.4rem;">Ready to Add</h3>
                                <p style="font-size:1.2rem;color:var(--text-secondary);">Fill in the required fields and click Submit to register this medicine.</p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                        <i class="fas fa-save"></i> Add Medicine to Inventory
                    </button>
                    <a href="/RMU-Medical-Management-System/php/medicine/medicine.php"
                       class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;margin-top:1rem;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</main>

<style>
.adm-form-grid { display:grid;grid-template-columns:1fr 1fr;gap:1.8rem; }
.adm-span-2 { grid-column: span 2; }
.adm-form-group { display:flex;flex-direction:column;gap:.6rem; }
.adm-label { font-size:1.3rem;font-weight:600;color:var(--text-secondary); }
.adm-label .req { color:var(--danger); }
.adm-input { padding:1.1rem 1.4rem;border:1.5px solid var(--border);border-radius:10px;font-family:'Poppins',sans-serif;font-size:1.4rem;color:var(--text-primary);background:var(--surface);outline:none;transition:var(--transition);width:100%; }
.adm-input:focus { border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.1); }
.adm-hint { font-size:1.1rem;color:var(--text-muted); }
textarea.adm-input { resize:vertical; }
.adm-toggle-group { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
.adm-toggle-option { display:flex;flex-direction:column;align-items:center;gap:.4rem;padding:1.5rem 1rem;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:var(--transition);text-align:center; }
.adm-toggle-option:hover,.adm-toggle-option.selected { border-color:var(--primary);background:var(--primary-light); }
.adm-toggle-option input { display:none; }
.adm-toggle-option i { font-size:2rem;color:var(--primary); }
.adm-toggle-option span { font-weight:700;font-size:1.3rem;color:var(--text-primary); }
.adm-toggle-option small { font-size:1.1rem;color:var(--text-muted); }
@media(max-width:900px){.adm-form-layout{grid-template-columns:1fr!important;}.adm-form-grid{grid-template-columns:1fr!important;}.adm-span-2{grid-column:span 1;}}
</style>

<script>
const sidebar = document.getElementById('admSidebar');
const overlay = document.getElementById('admOverlay');
document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const html = document.documentElement;
function applyTheme(t){ html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
applyTheme(localStorage.getItem('rmu_theme') || 'light');
themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));

// Toggle Rx/OTC visual highlight
document.querySelectorAll('.adm-toggle-option').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.adm-toggle-option').forEach(o => o.classList.remove('selected'));
        opt.classList.add('selected');
    });
});
</script>
</body>
</html>
