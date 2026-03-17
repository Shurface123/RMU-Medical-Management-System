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
$res = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='doctor' AND account_status='active'");
if ($res) while ($row = mysqli_fetch_assoc($res)) $doctors[] = $row;

// Fetch Kitchen Staff
$kitchen_staff = [];
$res = mysqli_query($conn, "SELECT id, full_name, current_shift FROM users WHERE role='kitchen_staff' AND account_status='active'");
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
            <button class="adm-btn adm-btn-primary" onclick="document.getElementById('dietModal').classList.add('active')">
                <i class="fas fa-plus"></i> New Dietary Order
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="adm-alert adm-alert-success"><i class="fas fa-check-circle"></i> Dietary order dispatched to kitchen.</div>
        <?php
endif; ?>

        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-concierge-bell"></i> Kitchen Ticket Queue</h3>
            </div>
            <div class="adm-table-wrap" style="overflow-x: auto; width: 100%;">
                <table class="adm-table" style="width: 100%; min-width: 900px;">
                    <thead><tr><th>Time Created</th><th>Patient / Location</th><th>Meal Time</th><th>Dietary Type</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if (empty($orders)): ?><tr><td colspan="5" style="text-align:center;padding:2rem;">No active kitchen orders.</td></tr>
                        <?php
else:
    foreach ($orders as $o):
        $sc = $o['preparation_status'] === 'delivered' ? 'success' : ($o['preparation_status'] === 'ready' ? 'success' : ($o['preparation_status'] === 'in preparation' ? 'warning' : 'info'));
        $created = strtotime($o['created_at']);
        $is_overdue = ($o['preparation_status'] === 'pending' || $o['preparation_status'] === 'in preparation') && (time() - $created > 7200); // 2 hours late
        $row_style = $is_overdue ? 'background-color: #fffbeb;' : ''; // Amber highlight for overdue

        $diet = json_decode($o['dietary_requirements'], true) ?? [];
        $allergies = json_decode($diet['allergies'] ?? '[]', true) ?? [];
        $is_npo = ($diet['type'] ?? '') === 'NPO';
        $prio = $o['priority'] ?? 'Routine';
?>
                        <tr style="<?php echo $row_style; ?>">
                            <td style="white-space:nowrap;">
                                <?php echo date('d M Y, g:i A', $created); ?>
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
                                        <span class="adm-badge adm-badge-danger" style="background:var(--danger);color:#fff;" title="<?php echo htmlspecialchars(implode(', ', $allergies)); ?>"><i class="fas fa-exclamation-triangle"></i> ALLERGIES</span>
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
                        <?php
    endforeach;
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="adm-modal" id="dietModal">
    <div class="adm-modal-content" style="max-width: 900px; width: 95%;">
        <div class="adm-modal-header">
            <h3><i class="fas fa-utensils"></i> New Dietary Order</h3>
            <button class="adm-modal-close" onclick="document.getElementById('dietModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <form id="dietForm" onsubmit="submitDietOrder(event)">
                <input type="hidden" name="action" value="create_diet_order">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                
                <h4 style="margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);">Patient & Ward Information</h4>
                <div class="row">
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Select Registered Patient</label>
                        <select id="patient_id" name="patient_id" class="adm-search-input" onchange="fillPatientData()">
                            <option value="">-- Custom/Unregistered Patient --</option>
                            <?php foreach($patients as $p): ?>
                                <option value="<?php echo $p['id']; ?>" data-ward="<?php echo htmlspecialchars($p['ward_department']); ?>" data-doctor="<?php echo $p['assigned_doctor']; ?>" data-allergies="<?php echo htmlspecialchars($p['allergies']); ?>"><?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['patient_id']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small id="allergy_badge" style="display:none; color:var(--danger); font-weight:bold; margin-top:5px;"><i class="fas fa-exclamation-triangle"></i> Pre-filled from allergies record</small>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Or Free Text Patient Name</label>
                        <input type="text" id="patient_name_fallback" name="patient_name_fallback" class="adm-search-input" placeholder="If not registered">
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Ward / Department *</label>
                        <select id="ward" name="ward" class="adm-search-input" required>
                            <option value="">Select Ward</option>
                            <?php foreach($wards as $w): echo "<option value=\"$w\">$w</option>"; endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Bed Number</label>
                        <input type="text" id="bed_number" name="bed_number" class="adm-search-input" placeholder="e.g. Bed 12">
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Attending Doctor</label>
                        <select id="doctor_id" name="doctor_id" class="adm-search-input">
                            <option value="">Select Doctor (Optional)</option>
                            <?php foreach($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Ordered By</label>
                        <input type="text" class="adm-search-input" value="Admin <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" disabled>
                    </div>
                </div>

                <h4 style="margin-top:1rem; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);">Dietary Requirements & Restrictions</h4>
                <div class="row">
                    <div class="col-md-6 adm-form-group">
                        <label>Diet Type *</label>
                        <select name="diet_type" id="diet_type" class="adm-search-input" required>
                            <option value="Regular">Regular</option><option value="Soft">Soft</option><option value="Liquid">Liquid</option>
                            <option value="Pureed">Pureed</option><option value="NPO">NPO (Nothing by Mouth)</option><option value="High Protein">High Protein</option>
                            <option value="Low Sodium">Low Sodium</option><option value="Diabetic">Diabetic</option><option value="Renal">Renal</option>
                            <option value="Low Fat">Low Fat</option><option value="Vegetarian">Vegetarian</option><option value="Vegan">Vegan</option>
                            <option value="Halal">Halal</option><option value="Kosher">Kosher</option><option value="Custom">Custom</option>
                        </select>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Texture Modification</label>
                        <select name="texture" class="adm-search-input">
                            <option value="Normal">Normal</option><option value="Minced">Minced</option>
                            <option value="Minced and Moist">Minced and Moist</option><option value="Pureed">Pureed</option>
                            <option value="Liquidised">Liquidised</option><option value="Thickened Fluids">Thickened Fluids</option>
                        </select>
                    </div>
                    <div class="col-md-12 adm-form-group">
                        <label>Allergies & Intolerances</label>
                        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px; padding:10px; border:1px solid var(--border); border-radius:5px; background:var(--surface-2);">
                            <?php 
                            $alg = ['Nuts', 'Dairy', 'Gluten', 'Shellfish', 'Eggs', 'Soy', 'Lactose', 'None'];
                            foreach($alg as $a): ?>
                                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" name="allergies[]" value="<?php echo $a; ?>"> <?php echo $a; ?></label>
                            <?php endforeach; ?>
                            <input type="text" name="other_allergies" class="adm-search-input" placeholder="Other allergies..." style="padding:4px; height:auto;">
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Caloric Target (kcal/day)</label>
                        <input type="number" name="caloric_target" class="adm-search-input" placeholder="e.g. 2000">
                    </div>
                    <div class="col-md-6 col-lg-4 adm-form-group">
                        <label>Fluid Restriction (ml/day)</label>
                        <input type="number" name="fluid_restriction" class="adm-search-input" placeholder="e.g. 1500">
                    </div>
                    <div class="col-md-12 adm-form-group">
                        <label>Special Dietary Notes</label>
                        <textarea name="special_notes" class="adm-search-input" rows="2" placeholder="Any additional instructions outside standard options..."></textarea>
                    </div>
                </div>

                <h4 style="margin-top:1rem; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);">Meal Schedule</h4>
                <div class="row">
                    <div class="col-md-12 adm-form-group">
                        <label>Meals Included *</label>
                        <div style="display:flex; flex-wrap:wrap; gap:15px; margin-top:5px;">
                            <?php 
                            $meals = ['Breakfast', 'Morning Snack', 'Lunch', 'Afternoon Snack', 'Dinner', 'Evening Snack'];
                            foreach($meals as $m): ?>
                                <label style="display:flex; align-items:center; gap:5px; cursor:pointer;"><input type="checkbox" name="meals[]" value="<?php echo $m; ?>"> <?php echo $m; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 adm-form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" id="start_date" class="adm-search-input" required value="<?=date('Y-m-d')?>">
                    </div>
                    <div class="col-md-6 col-lg-3 adm-form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="end_date" class="adm-search-input">
                    </div>
                    <div class="col-md-6 col-lg-3 adm-form-group">
                        <label>Feeding Method</label>
                        <select name="feeding_method" class="adm-search-input">
                            <option value="Oral">Oral</option><option value="Nasogastric Tube">Nasogastric Tube</option>
                            <option value="PEG Tube">PEG Tube</option><option value="Parenteral">Parenteral</option>
                            <option value="Assisted Feeding">Assisted Feeding</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-3 adm-form-group">
                        <label>Portion Size</label>
                        <select name="portion_size" class="adm-search-input">
                            <option value="Regular">Regular</option><option value="Small">Small</option>
                            <option value="Large">Large</option><option value="Extra Large">Extra Large</option>
                        </select>
                    </div>
                </div>

                <h4 style="margin-top:1rem; margin-bottom:1rem; border-bottom:1px solid var(--border); padding-bottom:0.5rem; color:var(--primary);">Assignment & Instructions</h4>
                <div class="row">
                    <div class="col-md-6 adm-form-group">
                        <label>Assign To Kitchen Staff</label>
                        <select name="assigned_staff_id" id="assigned_staff_id" class="adm-search-input">
                            <option value="">Any Available Staff</option>
                            <?php foreach($kitchen_staff as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?> <?php echo !empty($s['current_shift']) ? '('.htmlspecialchars($s['current_shift']).')' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(empty($kitchen_staff)): ?><small style="color:var(--warning);"><i class="fas fa-exclamation-triangle"></i> No kitchen staff shift detected.</small><?php endif; ?>
                    </div>
                    <div class="col-md-6 adm-form-group">
                        <label>Priority</label>
                        <select name="priority" id="priority" class="adm-search-input">
                            <option value="Routine">Routine</option>
                            <option value="Urgent">Urgent</option>
                            <option value="Emergency">Emergency (Immediate)</option>
                        </select>
                    </div>
                    <div class="col-md-12 adm-form-group">
                        <label>Kitchen Preparation Instructions</label>
                        <textarea name="kitchen_instructions" class="adm-search-input" rows="2" placeholder="Instructions directly for kitchen crew..."></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:1.5rem; border-top:1px solid var(--border); padding-top:1.5rem;">
                    <button type="button" class="adm-btn" onclick="document.getElementById('dietModal').classList.remove('active')">Cancel</button>
                    <button type="submit" id="submitBtn" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Submit Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Warning Modal for Allergy Conflict -->
<div class="adm-modal" id="allergyWarningModal">
    <div class="adm-modal-content" style="max-width: 500px; border-left: 5px solid var(--danger);">
        <div class="adm-modal-header" style="color:var(--danger);">
            <h3><i class="fas fa-exclamation-triangle"></i> Allergy Conflict Detected</h3>
            <button class="adm-modal-close" onclick="document.getElementById('allergyWarningModal').classList.remove('active')"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-modal-body">
            <p><strong>Warning:</strong> This dietary order may conflict with the patient's recorded allergies.</p>
            <p>Registered Allergies: <span id="conflict_allergies_list" style="font-weight:bold; color:var(--danger);"></span></p>
            <p style="margin-top:1rem; font-size:1.1rem;">Do you want to explicitly override this warning and proceed with the order?</p>
            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem;">
                <button type="button" class="adm-btn" onclick="document.getElementById('allergyWarningModal').classList.remove('active')">Go Back</button>
                <button type="button" class="adm-btn" style="background:var(--danger);color:#fff;" onclick="overrideAllergyAndSubmit()"><i class="fas fa-check"></i> Acknowledge & Proceed</button>
            </div>
        </div>
    </div>
</div>
<script>
let override_allergy = false;

function fillPatientData() {
    const sel = document.getElementById('patient_id');
    const opt = sel.options[sel.selectedIndex];
    const fb = document.getElementById('patient_name_fallback');
    const ward = document.getElementById('ward');
    const doc = document.getElementById('doctor_id');
    const badge = document.getElementById('allergy_badge');
    
    // reset checks
    document.querySelectorAll('input[name="allergies[]"]').forEach(cb => cb.checked = false);
    
    if(sel.value !== "") {
        fb.value = ''; fb.disabled = true;
        ward.value = opt.getAttribute('data-ward') || '';
        if(opt.getAttribute('data-doctor')) {
            doc.value = opt.getAttribute('data-doctor');
        }
        
        let algRaw = opt.getAttribute('data-allergies');
        if(algRaw && algRaw.trim() !== '' && algRaw.trim() !== 'None') {
            badge.style.display = 'block';
            let pt_allergies = algRaw.toLowerCase().split(',').map(s=>s.trim());
            document.querySelectorAll('input[name="allergies[]"]').forEach(cb => {
                let v = cb.value.toLowerCase();
                if(pt_allergies.some(a => a.includes(v) || v.includes(a))) cb.checked = true;
            });
        } else {
            badge.style.display = 'none';
        }
    } else {
        fb.disabled = false;
        ward.value = ''; doc.value = '';
        badge.style.display = 'none';
    }
}

function submitDietOrder(e) {
    e.preventDefault();
    if(!override_allergy) {
        // allergy conflict check
        const sel = document.getElementById('patient_id');
        const opt = sel.options[sel.selectedIndex];
        if(sel.value !== "") {
            let algRaw = opt.getAttribute('data-allergies');
            if(algRaw && algRaw.trim() !== '' && algRaw.trim().toLowerCase() !== 'none') {
                // If patient has allergies, check if the diet type might be conflicting implicitly or if the user unchecked the boxes
                let still_checked = false;
                document.querySelectorAll('input[name="allergies[]"]:checked').forEach(cb => {
                    let v = cb.value.toLowerCase();
                    if(algRaw.toLowerCase().includes(v)) still_checked = true;
                });
                
                // If user UNCHECKED the pre-filled allergies OR ordered Regular diet for highly allergic patient, warn them.
                let dt = document.getElementById('diet_type').value;
                if(!still_checked || dt === 'Regular') {
                    document.getElementById('conflict_allergies_list').textContent = algRaw;
                    document.getElementById('allergyWarningModal').classList.add('active');
                    return;
                }
            }
        }
    }
    
    // Proceed with AJAX submission
    const form = document.getElementById('dietForm');
    const fd = new FormData(form);
    
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    btn.disabled = true;

    fetch('admin_kitchen_actions.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r=>r.json())
    .then(res => {
        if(res.success) {
            alert('Success: ' + res.message);
            window.location.href = 'facility_kitchen.php?success=1';
        } else {
            alert('Error: ' + (res.message || 'Validation failed.'));
            btn.innerHTML = '<i class="fas fa-save"></i> Submit Order';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Exception: ' + err);
        btn.innerHTML = '<i class="fas fa-save"></i> Submit Order';
        btn.disabled = false;
    });
}

function overrideAllergyAndSubmit() {
    override_allergy = true;
    document.getElementById('allergyWarningModal').classList.remove('active');
    document.getElementById('dietForm').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
}

const sidebar  = document.getElementById('admSidebar');
const overlay  = document.getElementById('admOverlay');
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
</script>
</body>
</html>