<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'kitchen';
$page_title = 'Dietary & Kitchen';
include '../includes/_sidebar.php';

// Fetch Active Patients for Dropdown
$patients = [];
$res = mysqli_query($conn, "SELECT id, full_name, patient_id, ward_department, allergies, assigned_doctor FROM patients WHERE registration_status='Active'");
if ($res) while ($row = mysqli_fetch_assoc($res)) $patients[] = $row;

// Fetch Doctors
$doctors = [];
$res = mysqli_query($conn, "SELECT id, name FROM users WHERE user_role='doctor' AND is_active=1");
if ($res) while ($row = mysqli_fetch_assoc($res)) $doctors[] = $row;

// Fetch Kitchen Staff
$kitchen_staff = [];
$res = mysqli_query($conn, "SELECT u.id, u.name, s.shift_type AS current_shift FROM users u INNER JOIN staff s ON s.user_id = u.id WHERE u.user_role='staff' AND s.role='kitchen_staff' AND u.is_active=1 AND s.approval_status='approved' AND s.status='active'");
if ($res) while ($row = mysqli_fetch_assoc($res)) $kitchen_staff[] = $row;

// Wards
$wards = ['Emergency', 'ICU', 'Maternity', 'Pediatrics', 'Surgery', 'General Ward A', 'General Ward B', 'Isolation'];

$orders = [];
$q = mysqli_query($conn, "SELECT * FROM kitchen_tasks ORDER BY FIELD(preparation_status,'pending','in preparation','ready','delivered'), created_at DESC LIMIT 50");
if ($q)
    while ($r = mysqli_fetch_assoc($q))
        $orders[] = $r;
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-utensils"></i> Dietary & Kitchen</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div>
                <h1>Meal & Dietary Orders</h1>
                <p>Assign dietary requirements and meal prep tasks to the kitchen staff.</p>
            </div>
            <button class="btn btn-primary" onclick="document.getElementById('dietModal').classList.add('active')"><span class="btn-text">
                <i class="fas fa-plus"></i> New Dietary Order
            </span></button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Dietary order dispatched to kitchen.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-concierge-bell"></i> Kitchen Ticket Queue</h3>
            </div>
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr>
                            <th>Time Created</th>
                            <th>Patient / Location</th>
                            <th>Meal Time</th>
                            <th>Dietary Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:4rem;color:var(--text-muted);">No active kitchen orders.</td></tr>
                        <?php else:
                            foreach ($orders as $o):
                                $sc = $o['preparation_status'] === 'delivered' ? 'success' : ($o['preparation_status'] === 'ready' ? 'success' : ($o['preparation_status'] === 'in preparation' ? 'warning' : 'info'));
                                $created = strtotime($o['created_at']);
                                $is_overdue = ($o['preparation_status'] === 'pending' || $o['preparation_status'] === 'in preparation') && (time() - $created > 7200); 
                                
                                $diet = json_decode($o['dietary_requirements'], true) ?? [];
                                $allergies = json_decode($diet['allergies'] ?? '[]', true) ?? [];
                                $is_npo = ($diet['type'] ?? '') === 'NPO';
                                $prio = $o['priority'] ?? 'Routine';
                        ?>
                        <tr <?php if($is_overdue) echo 'style="background-color: var(--warning-light);"'; ?>>
                            <td style="white-space:nowrap;">
                                <strong><?php echo date('d M, g:i A', $created); ?></strong>
                                <?php if($prio === 'Emergency'): ?><br><span class="adm-badge adm-badge-danger" style="margin-top:4px;"><i class="fas fa-siren-on"></i> EMERGENCY</span>
                                <?php elseif($prio === 'Urgent'): ?><br><span class="adm-badge adm-badge-warning" style="margin-top:4px;">URGENT</span><?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($o['patient_name'] ?? 'Unregistered Patient'); ?></strong>
                                <div style="font-size:.8rem;color:var(--text-muted);"><i class="fas fa-bed"></i> <?php echo htmlspecialchars($o['ward_department']); ?> <?php echo !empty($o['bed_number']) ? ' - ' . htmlspecialchars($o['bed_number']) : ''; ?></div>
                            </td>
                            <td><span class="adm-badge adm-badge-secondary"><?php echo strtoupper($o['meal_type']); ?></span></td>
                            <td>
                                <strong><?php echo ucfirst($diet['type'] ?? 'Standard'); ?></strong>
                                <?php if($is_npo): ?><span class="adm-badge adm-badge-danger"><i class="fas fa-ban"></i> NPO</span><?php endif; ?>
                                <?php if(!empty($allergies)): ?>
                                    <div style="margin-top:4px;">
                                        <span class="adm-badge adm-badge-danger" style="background:var(--danger);color:#fff;"><i class="fas fa-exclamation-triangle"></i> ALLERGIES</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($o['notes']) echo '<div style="font-size:.75rem;color:var(--danger); margin-top:4px;"><i class="fas fa-notes-medical"></i> ' . htmlspecialchars($o['notes']) . '</div>'; ?>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php if($o['preparation_status'] === 'pending'): ?> <i class="fas fa-circle" style="color:var(--info); font-size:0.6rem;"></i>
                                    <?php elseif($o['preparation_status'] === 'in preparation'): ?> <i class="fas fa-spinner fa-spin" style="color:var(--warning);"></i>
                                    <?php elseif($o['preparation_status'] === 'ready'): ?> <i class="fas fa-check-circle" style="color:var(--success);"></i>
                                    <?php elseif($o['preparation_status'] === 'delivered'): ?> <i class="fas fa-check-double" style="color:var(--success);"></i>
                                    <?php endif; ?>
                                    <span class="adm-badge adm-badge-<?php echo $sc; ?>"><?php echo ucfirst($o['preparation_status']); ?></span>
                                </div>
                                <?php if ($o['delivery_status'] === 'delivered' && $o['preparation_status'] !== 'delivered'): ?>
                                    <span class="adm-badge adm-badge-success" style="margin-top:4px;">Delivered</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Diet Modal -->
<div class="adm-modal" id="dietModal">
    <div class="adm-modal-content" style="max-width: 900px;">
        <div class="adm-modal-header">
            <h3><i class="fas fa-utensils"></i> New Dietary Order</h3>
            <button class="btn btn-primary adm-modal-close" onclick="document.getElementById('dietModal').classList.remove('active')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <div class="adm-modal-body">
            <form id="dietForm" onsubmit="submitDietOrder(event)">
                <input type="hidden" name="action" value="create_diet_order">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <h4 class="adm-section-title">Patient & Ward Information</h4>
                <div class="adm-form-grid">
                    <div class="adm-form-group">
                        <label>Select Registered Patient</label>
                        <select id="patient_id" name="patient_id" class="adm-form-input" onchange="fillPatientData()">
                            <option value="">-- Custom/Unregistered Patient --</option>
                            <?php foreach($patients as $p): ?>
                                <option value="<?php echo $p['id']; ?>" data-ward="<?php echo htmlspecialchars($p['ward_department'] ?? ''); ?>" data-doctor="<?php echo $p['assigned_doctor'] ?? ''; ?>" data-allergies="<?php echo htmlspecialchars($p['allergies'] ?? ''); ?>"><?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['patient_id']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small id="allergy_badge" class="adm-input-hint" style="display:none; color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Pre-filled from records</small>
                    </div>
                    <div class="adm-form-group">
                        <label>Patient Name (Unregistered)</label>
                        <input type="text" id="patient_name_fallback" name="patient_name_fallback" class="adm-form-input" placeholder="Enter name if not listed">
                    </div>
                    <div class="adm-form-group">
                        <label>Ward / Department *</label>
                        <select id="ward" name="ward" class="adm-form-input" required>
                            <option value="">Select Ward</option>
                            <?php foreach($wards as $w): echo "<option value=\"$w\">$w</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Bed Number</label>
                        <input type="text" id="bed_number" name="bed_number" class="adm-form-input" placeholder="e.g. Bed 12">
                    </div>
                    <div class="adm-form-group">
                        <label>Attending Doctor</label>
                        <select id="doctor_id" name="doctor_id" class="adm-form-input">
                            <option value="">Select Doctor (Optional)</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Ordered By</label>
                        <input type="text" class="adm-form-input" value="Admin <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" disabled>
                    </div>
                </div>

                <h4 class="adm-section-title">Dietary Requirements</h4>
                <div class="adm-form-grid">
                    <div class="adm-form-group">
                        <label>Diet Type *</label>
                        <select name="diet_type" id="diet_type" class="adm-form-input" required>
                            <option value="Regular">Regular</option><option value="Soft">Soft</option><option value="Liquid">Liquid</option>
                            <option value="Pureed">Pureed</option><option value="NPO">NPO (Nothing by Mouth)</option><option value="High Protein">High Protein</option>
                            <option value="Low Sodium">Low Sodium</option><option value="Diabetic">Diabetic</option><option value="Renal">Renal</option>
                            <option value="Low Fat">Low Fat</option><option value="Vegetarian">Vegetarian</option><option value="Vegan">Vegan</option>
                            <option value="Halal">Halal</option><option value="Kosher">Kosher</option><option value="Custom">Custom</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Texture Modification</label>
                        <select name="texture" class="adm-form-input">
                            <option value="Normal">Normal</option><option value="Minced">Minced</option>
                            <option value="Minced and Moist">Minced and Moist</option><option value="Pureed">Pureed</option>
                            <option value="Liquidised">Liquidised</option><option value="Thickened Fluids">Thickened Fluids</option>
                        </select>
                    </div>
                    <div class="adm-form-group" style="grid-column: span 2;">
                        <label>Allergies & Intolerances</label>
                        <div class="adm-checkbox-group">
                            <?php 
                            $alg = ['Nuts', 'Dairy', 'Gluten', 'Shellfish', 'Eggs', 'Soy', 'Lactose'];
                            foreach($alg as $a): ?>
                                <label class="adm-checkbox-label"><input type="checkbox" name="allergies[]" value="<?php echo $a; ?>"> <?php echo $a; ?></label>
                            <?php endforeach; ?>
                            <input type="text" name="other_allergies" class="adm-form-input" placeholder="Other..." style="width: auto; flex: 1;">
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label>Caloric Target (kcal/day)</label>
                        <input type="number" name="caloric_target" class="adm-form-input" placeholder="e.g. 2000">
                    </div>
                    <div class="adm-form-group">
                        <label>Fluid Restriction (ml/day)</label>
                        <input type="number" name="fluid_restriction" class="adm-form-input" placeholder="e.g. 1500">
                    </div>
                    <div class="adm-form-group" style="grid-column: span 2;">
                        <label>Special Dietary Notes</label>
                        <textarea name="special_notes" class="adm-form-input" rows="2" placeholder="Additional instructions..."></textarea>
                    </div>
                </div>

                <h4 class="adm-section-title">Schedule & Assignment</h4>
                <div class="adm-form-grid">
                    <div class="adm-form-group" style="grid-column: span 2;">
                        <label>Meals Included *</label>
                        <div class="adm-checkbox-group">
                            <?php 
                            $meals = ['Breakfast', 'Morning Snack', 'Lunch', 'Afternoon Snack', 'Dinner', 'Evening Snack'];
                            foreach($meals as $m): ?>
                                <label class="adm-checkbox-label"><input type="checkbox" name="meals[]" value="<?php echo $m; ?>"> <?php echo $m; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="adm-form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" id="start_date" class="adm-form-input" required value="<?=date('Y-m-d')?>">
                    </div>
                    <div class="adm-form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="end_date" class="adm-form-input">
                    </div>
                    <div class="adm-form-group">
                        <label>Feeding Method</label>
                        <select name="feeding_method" class="adm-form-input">
                            <option value="Oral">Oral</option><option value="Nasogastric Tube">Nasogastric Tube</option>
                            <option value="PEG Tube">PEG Tube</option><option value="Parenteral">Parenteral</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Portion Size</label>
                        <select name="portion_size" class="adm-form-input">
                            <option value="Regular">Regular</option><option value="Small">Small</option><option value="Large">Large</option>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Assign To Kitchen Staff</label>
                        <select name="assigned_staff_id" id="assigned_staff_id" class="adm-form-input">
                            <option value="">Any Available Staff</option>
                            <?php foreach($kitchen_staff as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="adm-form-group">
                        <label>Priority</label>
                        <select name="priority" id="priority" class="adm-form-input">
                            <option value="Routine">Routine</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                </div>

                <div class="adm-modal-footer">
                    <button type="button" class="btn btn-ghost btn" onclick="document.getElementById('dietModal').classList.remove('active')"><span class="btn-text">Cancel</span></button>
                    <button type="submit" id="submitBtn" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Submit Order</span></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Allergy Warning Modal -->
<div class="adm-modal" id="allergyWarningModal">
    <div class="adm-modal-content" style="max-width: 500px; border-left: 5px solid var(--danger);">
        <div class="adm-modal-header">
            <h3 style="color:var(--danger);"><i class="fas fa-exclamation-triangle"></i> Allergy Warning</h3>
            <button class="btn btn-primary adm-modal-close" onclick="document.getElementById('allergyWarningModal').classList.remove('active')"><span class="btn-text"><i class="fas fa-times"></i></span></button>
        </div>
        <div class="adm-modal-body">
            <p>This order may conflict with the patient's recorded allergies: <strong id="conflict_allergies_list" style="color:var(--danger);"></strong></p>
            <p>Do you want to override and proceed?</p>
            <div class="adm-modal-footer">
                <button type="button" class="btn btn-ghost btn" onclick="document.getElementById('allergyWarningModal').classList.remove('active')"><span class="btn-text">Go Back</span></button>
                <button type="button" class="btn btn-danger" onclick="overrideAllergyAndSubmit()"><span class="btn-text"><i class="fas fa-check"></i> Proceed Anyway</span></button>
            </div>
        </div>
    </div>
</div>

<style>
.adm-section-title { margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; color: var(--primary); font-size: 1.1rem; margin-top: 1.5rem; }
.adm-section-title:first-child { margin-top: 0; }
.adm-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.adm-checkbox-group { display: flex; flex-wrap: wrap; gap: 0.75rem; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-body); }
.adm-checkbox-label { display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; }
.adm-input-hint { font-size: 0.8rem; margin-top: 0.25rem; display: block; }
@media (max-width: 768px) { .adm-form-grid { grid-template-columns: 1fr; } }
</style>

<script>
let override_allergy = false;

function fillPatientData() {
    const sel = document.getElementById('patient_id');
    const opt = sel.options[sel.selectedIndex];
    const fb = document.getElementById('patient_name_fallback');
    const ward = document.getElementById('ward');
    const doc = document.getElementById('doctor_id');
    const badge = document.getElementById('allergy_badge');
    
    document.querySelectorAll('input[name="allergies[]"]').forEach(cb => cb.checked = false);
    
    if(sel.value !== "") {
        fb.value = ''; fb.disabled = true;
        ward.value = opt.getAttribute('data-ward') || '';
        if(opt.getAttribute('data-doctor')) doc.value = opt.getAttribute('data-doctor');
        
        let algRaw = opt.getAttribute('data-allergies');
        if(algRaw && algRaw.trim() !== '' && algRaw.trim().toLowerCase() !== 'none') {
            badge.style.display = 'block';
            let pt_allergies = algRaw.toLowerCase().split(',').map(s=>s.trim());
            document.querySelectorAll('input[name="allergies[]"]').forEach(cb => {
                let v = cb.value.toLowerCase();
                if(pt_allergies.some(a => a.includes(v) || v.includes(a))) cb.checked = true;
            });
        } else badge.style.display = 'none';
    } else {
        fb.disabled = false;
        ward.value = ''; doc.value = '';
        badge.style.display = 'none';
    }
}

function submitDietOrder(e) {
    e.preventDefault();
    if(!override_allergy) {
        const sel = document.getElementById('patient_id');
        const opt = sel.options[sel.selectedIndex];
        if(sel.value !== "") {
            let algRaw = opt.getAttribute('data-allergies');
            if(algRaw && algRaw.trim() !== '' && algRaw.trim().toLowerCase() !== 'none') {
                let still_checked = false;
                document.querySelectorAll('input[name="allergies[]"]:checked').forEach(cb => {
                    if(algRaw.toLowerCase().includes(cb.value.toLowerCase())) still_checked = true;
                });
                if(!still_checked || document.getElementById('diet_type').value === 'Regular') {
                    document.getElementById('conflict_allergies_list').textContent = algRaw;
                    document.getElementById('allergyWarningModal').classList.add('active');
                    return;
                }
            }
        }
    }
    
    const fd = new FormData(document.getElementById('dietForm'));
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    btn.disabled = true;

    fetch('admin_kitchen_actions.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(res => {
        if(res.success) {
            alert(res.message);
            window.location.href = 'facility_kitchen.php?success=1';
        } else {
            alert(res.message || 'Validation failed.');
            btn.innerHTML = '<i class="fas fa-save"></i> Submit Order';
            btn.disabled = false;
        }
    }).catch(err => {
        alert('Error: ' + err);
        btn.innerHTML = '<i class="fas fa-save"></i> Submit Order';
        btn.disabled = false;
    });
}

function overrideAllergyAndSubmit() {
    override_allergy = true;
    document.getElementById('allergyWarningModal').classList.remove('active');
    submitDietOrder(new Event('submit'));
}

const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
document.getElementById('menuToggle').onclick = () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); };
overlay.onclick = () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); };

document.getElementById('themeToggle').onclick = () => {
    const html = document.documentElement;
    const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
    html.setAttribute('data-theme', t);
    localStorage.setItem('rmu_theme', t);
    document.getElementById('themeIcon').className = t==='dark'?'fas fa-sun':'fas fa-moon';
};
</script>
</body>
</html>