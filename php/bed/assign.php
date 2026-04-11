<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
include 'db_conn.php';

$active_page = 'beds';
$page_title  = 'Assign Bed';

// Validate bed_id param
$bed_id = (int)($_GET['bed_id'] ?? 0);
if (!$bed_id) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Invalid bed ID'));
    exit();
}

// Fetch bed info
$bed_query = mysqli_query($conn, "SELECT * FROM beds WHERE id = $bed_id");
if (!$bed_query || mysqli_num_rows($bed_query) === 0) {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Bed not found'));
    exit();
}
$bed = mysqli_fetch_assoc($bed_query);

if ($bed['status'] !== 'Available') {
    header('Location: /RMU-Medical-Management-System/php/bed/bed.php?error=' . urlencode('Bed is not available for assignment'));
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
            header('Location: /RMU-Medical-Management-System/php/bed/bed.php?success=' . urlencode('Patient assigned to bed successfully'));
            exit();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = $e->getMessage();
        }
    }
}

include '../includes/_sidebar.php';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-plus"></i> Assign Bed</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Assign Patient to Bed</h1>
                <p>Dashboard &rarr; Bed Management &rarr; Assign Bed</p>
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

        <div style="display:flex;flex-wrap:wrap;gap:2rem;">
            <!-- Assignment Form -->
            <div style="flex:2;min-width:300px;">
                <div class="adm-card">
                    <div class="adm-card-header" style="background:var(--primary);">
                        <h3 style="color:#fff;"><i class="fas fa-clipboard-list" style="color:#fff;"></i> Assignment Details</h3>
                    </div>
                    <div class="adm-card-body">
                        <?php if (mysqli_num_rows($patients_query) === 0): ?>
                        <div class="adm-alert adm-alert-warning" style="margin-bottom:1.5rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div>No patients available for assignment. All registered patients are already assigned to a bed, or no patients have been registered yet.</div>
                        </div>
                        <a href="/RMU-Medical-Management-System/php/patient/add-patient.php" class="btn btn-primary"><span class="btn-text">
                            <i class="fas fa-user-plus"></i> Register a Patient First
                        </span></a>
                        <?php else: ?>
                        <form method="POST" action="?bed_id=<?php echo $bed_id; ?>" novalidate onsubmit="return handleFormSubmit(this);">
                            <div class="adm-form-group" style="margin-bottom:2rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;" for="patient_select">Select Patient <span style="color:var(--danger);">*</span></label>
                                <div style="position:relative;margin-bottom:.8rem;">
                                    <input type="text" id="patientSearch" class="adm-search-input" placeholder="Search patient by name or ID..." autocomplete="off">
                                </div>
                                <select name="patient_id" id="patient_select" class="adm-search-input" required size="6" style="height:auto;overflow-y:auto;padding:.5rem;">
                                    <?php while ($p = mysqli_fetch_assoc($patients_query)): ?>
                                    <option value="<?php echo $p['id']; ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($p['full_name'])); ?>"
                                            data-pid="<?php echo htmlspecialchars(strtolower($p['patient_id'])); ?>" style="padding:.8rem;cursor:pointer;">
                                        <?php echo htmlspecialchars($p['patient_id'] . ' — ' . $p['full_name']); ?>
                                        [<?php echo htmlspecialchars($p['gender']); ?>, <?php echo htmlspecialchars($p['age'] ?? '?'); ?> yrs, <?php echo htmlspecialchars($p['patient_type']); ?>]
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                                <small style="display:block;margin-top:.5rem;font-size:1.1rem;color:var(--text-muted);">Click to highlight a patient, or type to filter the list.</small>
                            </div>

                            <div style="display:flex;gap:1.5rem;margin-bottom:2rem;flex-wrap:wrap;">
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Assign Date</label>
                                    <input type="date" name="assign_date" class="adm-search-input"
                                           value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="adm-form-group" style="flex:1;min-width:250px;">
                                    <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Expected Stay (days)</label>
                                    <input type="number" name="expected_days" class="adm-search-input" min="1" value="1" id="expectedDays">
                                    <small style="display:block;margin-top:.5rem;font-size:1.1rem;color:var(--text-muted);" id="dischargeDateNote">Expected discharge: <?php echo date('d M Y', strtotime('+1 day')); ?></small>
                                </div>
                            </div>

                            <!-- Cost estimate -->
                            <div id="costEstimate" class="adm-alert adm-alert-info" style="margin-bottom:2rem;display:none;">
                                <i class="fas fa-calculator"></i>
                                <div>Estimated cost: <strong id="costAmount">GH₵ 0.00</strong> (<?php echo number_format((float)($bed['daily_rate'] ?? 0), 2); ?>/day)</div>
                            </div>

                            <div class="adm-form-group" style="margin-bottom:2rem;">
                                <label style="display:block;margin-bottom:.5rem;color:var(--text-secondary);font-weight:600;">Assignment Notes</label>
                                <textarea name="notes" class="adm-search-input" rows="3" style="resize:vertical;"
                                          placeholder="Reason for admission, special requirements, doctor's note..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:1.4rem;font-size:1.5rem;"><span class="btn-text">
                                <i class="fas fa-save"></i> Complete Assignment
                            </span></button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bed Info Card -->
            <div style="flex:1;min-width:250px;">
                <div class="adm-card" style="border-top:4px solid var(--success);">
                    <div class="adm-card-header" style="background:var(--primary);">
                        <h3 style="color:#fff;"><i class="fas fa-info-circle" style="color:#fff;"></i> Bed Information</h3>
                    </div>
                    <div class="adm-card-body">
                        <div style="display:flex;flex-direction:column;gap:1.5rem;">
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Bed Number</span>
                                <span style="font-size:2.4rem;font-weight:700;color:var(--primary);">
                                    <?php echo htmlspecialchars($bed['bed_number']); ?>
                                </span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Ward</span>
                                <span style="font-size:1.5rem;font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($bed['ward'] ?? '—'); ?></span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Bed Type</span>
                                <span style="font-size:1.5rem;font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($bed['bed_type'] ?? 'Standard'); ?></span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Daily Rate</span>
                                <span style="font-size:1.5rem;font-weight:600;color:var(--text-primary);">GH₵ <?php echo number_format((float)($bed['daily_rate'] ?? 0), 2); ?></span>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Status</span>
                                <div><span class="adm-badge adm-badge-success"><i class="fas fa-check-circle"></i> Available</span></div>
                            </div>
                            <?php if (!empty($bed['notes'])): ?>
                            <div style="display:flex;flex-direction:column;gap:.4rem;">
                                <span style="font-size:1.1rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;">Notes</span>
                                <span style="font-size:1.5rem;font-weight:600;color:var(--text-primary);"><?php echo htmlspecialchars($bed['notes']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

// Expected days -> discharge date + cost estimate
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
