<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Assign Bed';
include '../includes/_sidebar.php';

// Validate bed_id param
$bed_id = (int)($_GET['bed_id'] ?? 0);
if (!$bed_id) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=Invalid+bed+ID');
    exit();
}

// Fetch bed info
$bed_query = mysqli_query($conn, "SELECT * FROM beds WHERE id = $bed_id");
if (!$bed_query || mysqli_num_rows($bed_query) === 0) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=Bed+not+found');
    exit();
}
$bed = mysqli_fetch_assoc($bed_query);

if ($bed['status'] !== 'Available') {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=Bed+is+not+available+for+assignment');
    exit();
}

// Fetch all patients who aren't already occupying a bed
$patients_query = mysqli_query($conn, "
    SELECT p.id, p.patient_id, p.full_name, p.gender, p.age, p.patient_type,
           COALESCE(u.name, p.full_name) AS display_name
    FROM patients p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id NOT IN (
        SELECT ba.patient_id FROM bed_assignments ba WHERE ba.status = 'Active'
    )
    ORDER BY p.full_name ASC
");

$error = $success = '';

// Handle assignment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id_form = (int)($_POST['patient_id'] ?? 0);
    $assign_date     = mysqli_real_escape_string($conn, $_POST['assign_date'] ?? date('Y-m-d'));
    $expected_days   = (int)($_POST['expected_days'] ?? 1);
    $notes           = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if (!$patient_id_form) {
        $error = 'Please select a patient.';
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);
        try {
            $notes_val = $notes ? "'$notes'" : 'NULL';
            $discharge_date = date('Y-m-d', strtotime("$assign_date +$expected_days days"));

            // Insert bed_assignment
            $sql_assign = "INSERT INTO bed_assignments (bed_id, patient_id, assign_date, expected_discharge_date, status, notes)
                           VALUES ($bed_id, $patient_id_form, '$assign_date', '$discharge_date', 'Active', $notes_val)";
            if (!mysqli_query($conn, $sql_assign)) {
                throw new Exception('Assignment error: ' . mysqli_error($conn));
            }

            // Update bed status
            if (!mysqli_query($conn, "UPDATE beds SET status='Occupied', updated_at=NOW() WHERE id=$bed_id")) {
                throw new Exception('Bed status update error: ' . mysqli_error($conn));
            }

            mysqli_commit($conn);
            header('Location: /RMU-Medical-Management-System/php/bed/bed.php?success=Patient+assigned+to+bed+successfully');
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-plus" style="color:var(--primary);margin-right:.8rem;"></i>Assign Bed</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1><i class="fas fa-bed" style="color:var(--primary);margin-right:.6rem;"></i>Assign Patient to Bed</h1>
                <p>Allocate an available bed to a registered patient.</p>
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

        <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:2rem;" class="adm-form-layout">
            <!-- Bed Info Card -->
            <div>
                <div class="adm-card" style="border-top:4px solid var(--success);">
                    <div class="adm-card-header"><h3><i class="fas fa-bed"></i> Bed Information</h3></div>
                    <div class="adm-card-body">
                        <div class="bed-info-grid">
                            <div class="bed-info-item">
                                <span class="bed-info-label">Bed Number</span>
                                <span class="bed-info-value" style="font-size:2.4rem;font-weight:700;color:var(--primary);">
                                    <?php echo htmlspecialchars($bed['bed_number']); ?>
                                </span>
                            </div>
                            <div class="bed-info-item">
                                <span class="bed-info-label">Ward</span>
                                <span class="bed-info-value"><?php echo htmlspecialchars($bed['ward'] ?? '—'); ?></span>
                            </div>
                            <div class="bed-info-item">
                                <span class="bed-info-label">Bed Type</span>
                                <span class="bed-info-value"><?php echo htmlspecialchars($bed['bed_type'] ?? 'Standard'); ?></span>
                            </div>
                            <div class="bed-info-item">
                                <span class="bed-info-label">Daily Rate</span>
                                <span class="bed-info-value">GH₵ <?php echo number_format((float)($bed['daily_rate'] ?? 0), 2); ?></span>
                            </div>
                            <div class="bed-info-item">
                                <span class="bed-info-label">Status</span>
                                <span class="adm-badge adm-badge-success">
                                    <i class="fas fa-check-circle"></i> Available
                                </span>
                            </div>
                            <?php if (!empty($bed['notes'])): ?>
                            <div class="bed-info-item adm-span-2">
                                <span class="bed-info-label">Notes</span>
                                <span class="bed-info-value"><?php echo htmlspecialchars($bed['notes']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assignment Form -->
            <div>
                <div class="adm-card">
                    <div class="adm-card-header"><h3><i class="fas fa-clipboard-list"></i> Assignment Details</h3></div>
                    <div class="adm-card-body">
                        <?php if (mysqli_num_rows($patients_query) === 0): ?>
                        <div class="adm-alert" style="background:var(--warning-light);border-left:4px solid var(--warning);color:var(--text-primary);">
                            <i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i>
                            <div>No patients available for assignment. All registered patients are already assigned to a bed, or no patients have been registered yet.</div>
                        </div>
                        <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="adm-btn adm-btn-primary" style="margin-top:1.5rem;">
                            <i class="fas fa-user-plus"></i> Register a Patient First
                        </a>
                        <?php else: ?>
                        <form method="POST" action="?bed_id=<?php echo $bed_id; ?>" novalidate>
                            <div class="adm-form-group" style="margin-bottom:2rem;">
                                <label class="adm-label" for="patient_select">Select Patient <span class="req">*</span></label>
                                <div style="position:relative;">
                                    <input type="text" id="patientSearch" class="adm-input" placeholder="&#xf002; Search patient by name or ID..." style="font-family:'Poppins',sans-serif;padding-right:3rem;margin-bottom:.8rem;" autocomplete="off">
                                </div>
                                <select name="patient_id" id="patient_select" class="adm-input" required size="6" style="height:auto;overflow-y:auto;">
                                    <?php while ($p = mysqli_fetch_assoc($patients_query)): ?>
                                    <option value="<?php echo $p['id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($p['full_name'])); ?>"
                                            data-pid="<?php echo htmlspecialchars(strtolower($p['patient_id'])); ?>">
                                        <?php echo htmlspecialchars($p['patient_id'] . ' — ' . $p['full_name']); ?>
                                        [<?php echo htmlspecialchars($p['gender']); ?>, <?php echo htmlspecialchars($p['age'] ?? '?'); ?> yrs, <?php echo htmlspecialchars($p['patient_type']); ?>]
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="adm-hint">Click to highlight a patient, or type to filter the list.</small>
                            </div>

                            <div class="adm-form-grid" style="margin-bottom:2rem;">
                                <div class="adm-form-group">
                                    <label class="adm-label">Assign Date</label>
                                    <input type="date" name="assign_date" class="adm-input"
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="adm-form-group">
                                    <label class="adm-label">Expected Stay (days)</label>
                                    <input type="number" name="expected_days" class="adm-input" min="1" value="1" id="expectedDays">
                                    <small class="adm-hint" id="dischargeDateNote">Expected discharge: <?php echo date('d M Y', strtotime('+1 day')); ?></small>
                                </div>
                            </div>

                            <!-- Cost estimate -->
                            <div id="costEstimate" class="adm-alert" style="background:var(--info-light);border-left:4px solid var(--primary);margin-bottom:2rem;display:none;">
                                <i class="fas fa-calculator" style="color:var(--primary);"></i>
                                <div>Estimated cost: <strong id="costAmount">GH₵ 0.00</strong> (<?php echo number_format((float)($bed['daily_rate'] ?? 0), 2); ?>/day)</div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:2rem;">
                                <label class="adm-label">Assignment Notes</label>
                                <textarea name="notes" class="adm-input" rows="3"
                                          placeholder="Reason for admission, special requirements, doctor's note..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="adm-btn adm-btn-success" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;">
                                <i class="fas fa-user-check"></i> Assign Patient to Bed
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
select.adm-input option{padding:.8rem;}
select.adm-input option:checked{background:var(--primary);color:#fff;}
textarea.adm-input{resize:vertical;}
.adm-hint{font-size:1.1rem;color:var(--text-muted);}
.bed-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;}
.bed-info-item{display:flex;flex-direction:column;gap:.4rem;}
.bed-info-label{font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;}
.bed-info-value{font-size:1.5rem;font-weight:600;color:var(--text-primary);}
.adm-badge-success{display:inline-flex;align-items:center;gap:.5rem;background:rgba(39,174,96,.15);color:#27AE60;padding:.5rem 1.2rem;border-radius:50px;font-size:1.3rem;font-weight:600;}
.adm-btn-success{background:linear-gradient(135deg,#27AE60,#2ECC71);color:#fff;border:none;}
.adm-btn-success:hover{background:linear-gradient(135deg,#219A52,#27AE60);transform:translateY(-2px);}
@media(max-width:900px){.adm-form-layout{grid-template-columns:1fr!important;}.adm-form-grid{grid-template-columns:1fr!important;}}
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

// Patient search filter
const searchInput = document.getElementById('patientSearch');
const select = document.getElementById('patient_select');
const dailyRate = <?php echo (float)($bed['daily_rate'] ?? 0); ?>;

searchInput?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    Array.from(select?.options || []).forEach(o => {
        const match = o.dataset.name?.includes(q) || o.dataset.pid?.includes(q) || o.text.toLowerCase().includes(q);
        o.style.display = match ? '' : 'none';
    });
});

// Expected days → discharge date + cost estimate
const daysInput = document.getElementById('expectedDays');
const assignDateInput = document.querySelector('input[name="assign_date"]');
const dischargeDateNote = document.getElementById('dischargeDateNote');
const costEstimate = document.getElementById('costEstimate');
const costAmount = document.getElementById('costAmount');

function updateEstimate() {
    const days = parseInt(daysInput?.value) || 1;
    const assignDate = new Date(assignDateInput?.value || Date.now());
    const discharge = new Date(assignDate.getTime() + days * 86400000);
    if (dischargeDateNote) {
        dischargeDateNote.textContent = 'Expected discharge: ' + discharge.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
    }
    if (dailyRate > 0) {
        const total = (dailyRate * days).toFixed(2);
        if (costEstimate) costEstimate.style.display = 'flex';
        if (costAmount) costAmount.textContent = 'GH₵ ' + parseFloat(total).toLocaleString('en-GH', {minimumFractionDigits:2});
    }
}

daysInput?.addEventListener('input', updateEstimate);
assignDateInput?.addEventListener('change', updateEstimate);
updateEstimate();
</script>
</body>
</html>
