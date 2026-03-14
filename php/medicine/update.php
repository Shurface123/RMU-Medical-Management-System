<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once 'db_conn.php';

$active_page = 'medicine';
$page_title  = 'Update Medicine';

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['M_Code']) ? $_GET['M_Code'] : 0);
if (!$id && isset($_POST['id'])) $id = intval($_POST['id']);
if (!$id && isset($_POST['M_Code'])) $id = $_POST['M_Code'];

if (!$id) {
    header('Location: /RMU-Medical-Management-System/php/medicine/medicine.php?error=' . urlencode('Invalid medicine ID'));
    exit;
}

// Support for both numeric IDs and string codes to handle migration gradually
$is_numeric_id = is_numeric($id);

// Try new schema first 'medicines'
$stmt = null;
if ($is_numeric_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM medicines WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
} else {
    // If passed a code (e.g. MED-0001)
    $stmt = mysqli_prepare($conn, "SELECT * FROM medicines WHERE medicine_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $id);
}
mysqli_stmt_execute($stmt);
$med_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Fallback to legacy 'medicine' table
if (!$med_data) {
    $stmt2 = mysqli_prepare($conn, "SELECT * FROM medicine WHERE M_Code = ?");
    mysqli_stmt_bind_param($stmt2, "s", $id);
    mysqli_stmt_execute($stmt2);
    $med_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));
    mysqli_stmt_close($stmt2);
}

if (!$med_data) {
    header('Location: /RMU-Medical-Management-System/php/medicine/medicine.php?error=' . urlencode('Medicine record not found'));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF validation failed.");
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

    empty($expiry) ? $expiry = null : null;
    empty($desc) ? $desc = null : null;

    if (!$name) {
        $error = 'Medicine name is required.';
    } else {
        // Detect if updating new 'medicines' or legacy 'medicine'
        // If 'id' is in $med_data, it's the new schema table.
        if (isset($med_data['id']) && $med_data['id'] > 0) {
            $stmt_u = mysqli_prepare($conn, "UPDATE medicines SET medicine_name=?, generic_name=?, category=?, stock_quantity=?, reorder_level=?, unit_price=?, expiry_date=?, is_prescription_required=?, description=? WHERE id=?");
            mysqli_stmt_bind_param($stmt_u, "sssiiddisi", $name, $generic, $category, $stock, $reorder, $price, $expiry, $rx, $desc, $med_data['id']);
            $success = mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);
        } else {
            // Legacy schema: medicine (M_Code, M_Name, Quantity)
            $mCode = $med_data['M_Code'];
            $stmt_u = mysqli_prepare($conn, "UPDATE medicine SET M_Name=?, Quantity=? WHERE M_Code=?");
            mysqli_stmt_bind_param($stmt_u, "sis", $name, $stock, $mCode);
            $success = mysqli_stmt_execute($stmt_u);
            mysqli_stmt_close($stmt_u);
        }

        if ($success) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: /RMU-Medical-Management-System/php/medicine/medicine.php?success=' . urlencode('Medicine updated successfully'));
            exit;
        } else {
            $error = 'Database error updating medicine record.';
        }
    }
}

$cur_code = $med_data['medicine_id'] ?? ($med_data['M_Code'] ?? '');
$cur_name = isset($med_data['medicine_name']) ? $med_data['medicine_name'] : ($med_data['M_Name'] ?? '');
$cur_generic = $med_data['generic_name'] ?? '';
$cur_category = $med_data['category'] ?? '';
$cur_stock = isset($med_data['stock_quantity']) ? $med_data['stock_quantity'] : ($med_data['Quantity'] ?? 0);
$cur_reorder = $med_data['reorder_level'] ?? 10;
$cur_price = $med_data['unit_price'] ?? 0.00;
$cur_expiry = $med_data['expiry_date'] ?? '';
$cur_rx = isset($med_data['is_prescription_required']) ? $med_data['is_prescription_required'] : 0;
$cur_desc = $med_data['description'] ?? '';

$categories = ['Analgesics', 'Antibiotics', 'Antihistamines', 'Antifungals', 'Antiparasitics',
                'Antiseptics', 'Cardiovascular', 'Dermatological', 'Diabetic', 'Gastrointestinal',
                'Immunosuppressants', 'Multivitamins', 'Respiratory', 'Supplements', 'Other'];

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-edit"></i> Update Medicine</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Update Medicine</h1>
                <p>Dashboard &rarr; Inventory Management &rarr; Update Medicine</p>
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

        <?php if(!isset($med_data['id'])): ?>
        <div class="adm-alert adm-alert-info" style="margin-bottom:1.5rem;">
            <i class="fas fa-info-circle"></i>
            <div>You are editing a <strong>legacy record</strong> (Medicine). Only Name and Quantity changes are supported until safely migrated to <code>medicines</code> table format.</div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" novalidate onsubmit="return handleFormSubmit(this);">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            
            <div style="display:flex;flex-wrap:wrap;gap:2rem;">
                <div style="flex:2;min-width:300px;">
                    <!-- Details Info -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-info-circle" style="color:#fff;"></i> Medicine Details</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Medicine Code</label>
                                    <input type="text" class="adm-search-input" value="<?php echo htmlspecialchars($cur_code); ?>" disabled>
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Medicine Name <span style="color:var(--danger);">*</span></label>
                                    <input type="text" name="medicine_name" class="adm-search-input" required
                                           value="<?php echo htmlspecialchars($cur_name); ?>">
                                </div>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Generic Name</label>
                                    <input type="text" name="generic_name" class="adm-search-input" <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>
                                           value="<?php echo htmlspecialchars($cur_generic); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Category</label>
                                    <select name="category" class="adm-search-input" <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>>
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($cur_category === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                                        <?php endforeach; ?>
                                        <?php if (!in_array($cur_category, $categories) && $cur_category): ?>
                                        <option value="<?php echo htmlspecialchars($cur_category); ?>" selected><?php echo htmlspecialchars($cur_category); ?></option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="adm-form-group" style="margin-bottom:1.5rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Description / Notes</label>
                                <textarea name="description" class="adm-search-input" rows="3" style="resize:vertical;" <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>><?php echo htmlspecialchars($cur_desc); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Info -->
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-boxes" style="color:#fff;"></i> Stock Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Stock Quantity <span style="color:var(--danger);">*</span></label>
                                    <input type="number" name="stock_quantity" class="adm-search-input" min="0" required
                                           value="<?php echo htmlspecialchars($cur_stock); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Reorder Level</label>
                                    <input type="number" name="reorder_level" class="adm-search-input" min="0" <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>
                                           value="<?php echo htmlspecialchars($cur_reorder); ?>">
                                </div>
                            </div>
                            
                            <div style="display:flex;gap:1.5rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Unit Price (GH₵) <span style="color:var(--danger);">*</span></label>
                                    <input type="number" name="unit_price" class="adm-search-input" min="0" step="0.01" required <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>
                                           value="<?php echo htmlspecialchars($cur_price); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="adm-search-input" <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?>
                                           value="<?php echo htmlspecialchars($cur_expiry); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="flex:1;min-width:250px;">
                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-tag" style="color:#fff;"></i> Classification</h3>
                        </div>
                        <div class="adm-card-body">
                            <label style="display:flex;align-items:center;gap:1rem;cursor:pointer;margin-bottom:2rem;">
                                <input type="checkbox" name="is_prescription_required" <?php echo $cur_rx ? 'checked' : ''; ?> <?php echo !isset($med_data['id']) ? 'disabled' : ''; ?> style="width:20px;height:20px;accent-color:var(--primary);">
                                <span style="font-size:1.4rem;font-weight:600;color:var(--text-primary);">Prescription Required (Rx)</span>
                            </label>
                        </div>
                    </div>

                    <div class="adm-card" style="margin-bottom:2rem;">
                        <div class="adm-card-header" style="background:var(--primary);">
                            <h3 style="color:#fff;"><i class="fas fa-check-circle" style="color:#fff;"></i> Save Changes</h3>
                        </div>
                        <div class="adm-card-body">
                            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;justify-content:center;margin-bottom:1rem;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-save"></i> Update Medicine
                            </button>
                            <a href="/RMU-Medical-Management-System/php/medicine/medicine.php"
                               class="adm-btn adm-btn-ghost" style="width:100%;justify-content:center;">
                                Cancel
                            </a>
                        </div>
                    </div>
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
