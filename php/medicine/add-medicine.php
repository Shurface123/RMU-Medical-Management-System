<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'medicine';
$page_title  = 'Add Medicine';

// Generate CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF verification
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $name       = trim($_POST['medicine_name'] ?? '');
    $generic    = trim($_POST['generic_name'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $stock      = (int)($_POST['stock_quantity'] ?? 0);
    $reorder    = (int)($_POST['reorder_level'] ?? 10);
    $price      = (float)($_POST['unit_price'] ?? 0);
    $expiry     = $_POST['expiry_date'] ?? '';
    $rx         = isset($_POST['is_prescription_required']) ? 1 : 0;
    $desc       = trim($_POST['description'] ?? '');

    // Auto-generate medicine_id
    $last = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM medicines"))[0] ?? 0;
    $med_id = 'MED-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);

    if (!$name || !$category) {
        $error = 'Medicine name and category are required.';
    } else {
        empty($expiry) ? $expiry = null : null;
        empty($desc) ? $desc = null : null;

        $stmt = mysqli_prepare($conn, "INSERT INTO medicines (medicine_id, medicine_name, generic_name, category, stock_quantity, reorder_level, unit_price, expiry_date, is_prescription_required, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "ssssiiddis", $med_id, $name, $generic, $category, $stock, $reorder, $price, $expiry, $rx, $desc);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/medicine/medicine.php?success=' . urlencode('Medicine added successfully'));
            exit();
        } else {
            $error = 'Database error adding medicine record.';
        }
        mysqli_stmt_close($stmt);
    }
}

// Medicine categories
$categories = ['Analgesics', 'Antibiotics', 'Antihistamines', 'Antifungals', 'Antiparasitics',
                'Antiseptics', 'Cardiovascular', 'Dermatological', 'Diabetic', 'Gastrointestinal',
                'Immunosuppressants', 'Multivitamins', 'Respiratory', 'Supplements', 'Other'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <!-- Topbar -->
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-pills"></i> Add Medicine</span>
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
                <h1>Add New Medicine</h1>
                <p>Dashboard &rarr; Inventory Management &rarr; Add Medicine</p>
            </div>
            <a href="/RMU-Medical-Management-System/php/medicine/medicine.php" class="btn btn-ghost"><span class="btn-text">
                <i class="fas fa-arrow-left"></i> Back to Inventory
            </span></a>
        </div>

        <?php if ($error): ?>
        <div class="adm-alert adm-alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="addMedForm" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <!-- Left Column — Main Info -->
                <div style="flex:2;min-width:300px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-info-circle" style="color:#fff;"></i> Medicine Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Medicine Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="medicine_name" class="adm-search-input" required
                                           placeholder="e.g. Paracetamol 500mg"
                                           value="<?php echo htmlspecialchars($_POST['medicine_name'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Generic Name</label>
                                    <input type="text" name="generic_name" class="adm-search-input"
                                           placeholder="e.g. Acetaminophen"
                                           value="<?php echo htmlspecialchars($_POST['generic_name'] ?? ''); ?>">
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Category <span style="color:var(--danger);">*</span></label>
                                    <select name="category" class="adm-search-input" required>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (($_POST['category'] ?? '') === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Description / Notes</label>
                                <textarea name="description" class="adm-search-input" rows="3" style="resize:vertical;"
                                          placeholder="Optional: dosage notes, contraindications, storage instructions..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Info -->
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-boxes" style="color:#fff;"></i> Stock Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Initial Stock Quantity <span style="color:var(--danger);">*</span></label>
                                    <input type="number" name="stock_quantity" class="adm-search-input" min="0" required
                                           placeholder="0" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Reorder Level</label>
                                    <input type="number" name="reorder_level" class="adm-search-input" min="0"
                                           placeholder="10" value="<?php echo htmlspecialchars($_POST['reorder_level'] ?? '10'); ?>">
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Unit Price (GH₵) <span style="color:var(--danger);">*</span></label>
                                    <input type="number" name="unit_price" class="adm-search-input" min="0" step="0.01" required
                                           placeholder="0.00" value="<?php echo htmlspecialchars($_POST['unit_price'] ?? ''); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="adm-search-input"
                                           min="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column — Type & Submit -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-tag" style="color:#fff;"></i> Classification</h3>
                        </div>
                        <div class="adm-card-body">
                            <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;margin-bottom:2rem;">
                                <input type="checkbox" name="is_prescription_required" <?php echo isset($_POST['is_prescription_required']) ? 'checked' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary);">
                                <span style="font-size:1.4rem;font-weight:600;color:var(--text-primary);">Prescription Required (Rx)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Summary Card -->
                    <div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;" style="background:var(--primary-light);border:1.5px solid var(--primary);margin-bottom:2rem;">
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

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;"><span class="btn-text">
                        <i class="fas fa-save"></i> Add Medicine to Inventory
                    </span></button>
                    <a href="/RMU-Medical-Management-System/php/medicine/medicine.php"
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
