<?php
// ============================================================
// LAB DASHBOARD — TAB PROFILE (PREMIUM UI REWRITE)
// Advanced 12-Section Technician Profile
// ============================================================
if (!isset($user_id)) { exit; }

$today = date('Y-m-d');

// ── Core profile row ──────────────────────────────────────
$prof_stmt = $conn->prepare("SELECT l.*, u.email, u.phone, u.created_at AS member_since, u.last_login
    FROM lab_technicians l JOIN users u ON l.user_id = u.id WHERE l.user_id = ? LIMIT 1");
$prof_stmt->bind_param("i", $user_id);
$prof_stmt->execute();
$profile = $prof_stmt->get_result()->fetch_assoc();
$prof_stmt->close();

if (!$profile) {
    echo "<div class='alert alert-warning'>Technician profile not initialized. Contact administrator.</div>";
    exit;
}
$tech_pk = (int)$profile['id'];

// ── Professional profile ──────────────────────────────────
$pp_stmt = $conn->prepare("SELECT * FROM lab_technician_professional_profile WHERE technician_id = ? LIMIT 1");
$pp_stmt->bind_param("i", $tech_pk);
$pp_stmt->execute();
$pro_profile = $pp_stmt->get_result()->fetch_assoc() ?? [];
$pp_stmt->close();

// ── Completeness ──────────────────────────────────────────
$cp_stmt = $conn->prepare("SELECT * FROM lab_technician_profile_completeness WHERE technician_id = ? LIMIT 1");
$cp_stmt->bind_param("i", $tech_pk);
$cp_stmt->execute();
$completeness_row = $cp_stmt->get_result()->fetch_assoc();
$cp_stmt->close();
$completeness_pct = (int)($completeness_row['overall_percentage'] ?? 0);

// ── Notification settings ─────────────────────────────────
$ns_stmt = $conn->prepare("SELECT * FROM lab_technician_settings WHERE technician_id = ? LIMIT 1");
$ns_stmt->bind_param("i", $tech_pk);
$ns_stmt->execute();
$notif_settings = $ns_stmt->get_result()->fetch_assoc() ?? [];
$ns_stmt->close();

// ── Qualifications ────────────────────────────────────────
$qual_stmt = $conn->prepare("SELECT * FROM lab_technician_qualifications WHERE technician_id = ? ORDER BY year_awarded DESC");
$qual_stmt->bind_param("i", $tech_pk);
$qual_stmt->execute();
$qualifications = $qual_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$qual_stmt->close();

// ── Certifications ────────────────────────────────────────
$cert_stmt = $conn->prepare("SELECT * FROM lab_technician_certifications WHERE technician_id = ? ORDER BY expiry_date ASC");
$cert_stmt->bind_param("i", $tech_pk);
$cert_stmt->execute();
$certifications = $cert_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cert_stmt->close();

// ── Documents ─────────────────────────────────────────────
$doc_stmt = $conn->prepare("SELECT * FROM lab_technician_documents WHERE technician_id = ? ORDER BY uploaded_at DESC");
$doc_stmt->bind_param("i", $tech_pk);
$doc_stmt->execute();
$documents = $doc_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$doc_stmt->close();

// ── Equipment assigned ────────────────────────────────────
$equip_res = mysqli_query($conn, "SELECT * FROM lab_equipment WHERE status NOT IN ('Decommissioned') ORDER BY next_calibration_date ASC LIMIT 10");

// ── Reagents ──────────────────────────────────────────────
$reag_res = mysqli_query($conn, "SELECT * FROM reagent_inventory ORDER BY status ASC LIMIT 10");

// ── Sessions ──────────────────────────────────────────────
$sess_stmt = $conn->prepare("SELECT * FROM lab_technician_sessions WHERE technician_id = ? ORDER BY last_active DESC LIMIT 10");
$sess_stmt->bind_param("i", $tech_pk);
$sess_stmt->execute();
$sessions = $sess_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sess_stmt->close();

// ── Audit trail (personal) ────────────────────────────────
$aud_stmt = $conn->prepare("SELECT * FROM lab_audit_trail WHERE technician_id = ? ORDER BY created_at DESC LIMIT 100");
$aud_stmt->bind_param("i", $user_id);
$aud_stmt->execute();
$audit_rows = $aud_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$aud_stmt->close();

// ── Age calculation ───────────────────────────────────────
$dob = $profile['date_of_birth'] ?? null;
$age = $dob ? (int)((time() - strtotime($dob)) / 31557600) : null;

// ── License expiry warning ────────────────────────────────
$lic_exp = $pro_profile['license_expiry_date'] ?? $profile['license_expiry'] ?? null;
$lic_days_left = $lic_exp ? (int)((strtotime($lic_exp) - time()) / 86400) : null;
$lic_warning = $lic_days_left !== null && $lic_days_left <= 60 && $lic_days_left >= 0;

// ── Active orders count ───────────────────────────────────
$active_orders_q = $conn->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE technician_id=? AND order_status NOT IN ('Completed','Cancelled','Rejected')");
$active_orders_q->bind_param("i", $user_id);
$active_orders_q->execute();
$active_orders_count = (int)$active_orders_q->get_result()->fetch_row()[0];
$active_orders_q->close();

// ── Departments ───────────────────────────────────────────
$depts_res = mysqli_query($conn, "SELECT id, name FROM departments ORDER BY name");
$depts = [];
while($d = mysqli_fetch_assoc($depts_res)) $depts[] = $d;
?>

<div class="tab-content <?= ($active_tab === 'profile') ? 'active' : '' ?>" id="profile">

    <div class="sec-header" style="background:linear-gradient(135deg, rgba(14,165,233,0.06), rgba(14,165,233,0.01)); border:1px solid rgba(14,165,233,0.15); padding: 2.5rem; border-radius: 16px; margin-bottom: 2rem;">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--text-primary); margin-bottom:.5rem;">
                <i class="fas fa-id-card-alt" style="color:#0ea5e9; margin-right:.8rem;"></i> Technician Identity Vault
            </h2>
            <p style="font-size:1.3rem; color:var(--text-muted); margin:0;">Complete dossier of credentials, performance metrics, and operational clearance.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center; margin-top:1.5rem; flex-wrap:wrap;">
            <?php if($lic_warning): ?>
            <span class="adm-badge" style="background:rgba(231,76,60,0.1); color:var(--danger); border:1px solid var(--danger); font-weight:800; padding:.6rem 1.2rem;">
                <i class="fas fa-exclamation-triangle"></i> License Renewal Critical: <?= $lic_days_left ?>d
            </span>
            <?php endif; ?>
            <span class="adm-badge" style="background:rgba(14,165,233,0.1); color:#0ea5e9; font-weight:800; padding:.6rem 1.2rem;"><i class="fas fa-microscope text-primary"></i> <?= $active_orders_count ?> Active Assignments</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="profileToast" style="position:fixed; bottom:2.5rem; right:2.5rem; z-index:9999; min-width:320px; display:none;"></div>

    <div style="display:grid; grid-template-columns:300px 1fr; gap:2.5rem; align-items:start;">

    <!-- ─── LEFT: Section Navigation ─── -->
    <div class="adm-card shadow-sm" style="padding:0; overflow:hidden; position:sticky; top:100px; border-radius:16px;">
        <div style="padding:2rem; background:linear-gradient(135deg, #0ea5e9, #0284c7); color:white;">
            <div style="font-size:0.85rem; text-transform:uppercase; letter-spacing:.15em; font-weight:800; opacity:.9; margin-bottom:0.4rem;"><i class="fas fa-cogs"></i> Registry Control</div>
            <div style="font-size:1.4rem; font-weight:900;">Profile Modules</div>
        </div>
        <nav id="profileNav" style="padding:1rem 0;">
            <?php
            $sections = [
                'sec-a' => ['fa-user-astronaut', 'Identity Matrix'],
                'sec-b' => ['fa-address-card', 'Personal Dossier'],
                'sec-c' => ['fa-briefcase-medical', 'Expertise Profile'],
                'sec-d' => ['fa-certificate', 'Credentials & Awards'],
                'sec-e' => ['fa-chart-line', 'Performance Metrics'],
                'sec-f' => ['fa-microscope', 'Asset Custody'],
                'sec-g' => ['fa-calendar-day', 'Shift Schedule'],
                'sec-h' => ['fa-shield-halved', 'Security & Access'],
                'sec-i' => ['fa-bell', 'Alert Preferences'],
                'sec-j' => ['fa-folder-tree', 'Document Archive'],
                'sec-k' => ['fa-file-shield', 'Action History'],
                'sec-l' => ['fa-tasks-alt', 'Integrity Status'],
            ];
            foreach($sections as $id => [$icon, $label]):
            ?>
            <a href="javascript:void(0)" class="prof-nav-item <?= $id === 'sec-a' ? 'active' : '' ?>"
               onclick="showSection('<?= $id ?>', this)"
               style="display:flex; align-items:center; gap:1.2rem; padding:1.2rem 2rem; color:var(--text-secondary); text-decoration:none; font-size:1.05rem; font-weight:700; transition:var(--transition); border-left:4px solid transparent;">
                <i class="fas <?= $icon ?>" style="width:20px; text-align:center; color:#0ea5e9;"></i>
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <!-- Completeness bar -->
        <div style="padding:2rem; border-top:1px solid var(--border); background:var(--surface-1);">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.8rem;">
                <div style="font-size:0.85rem; color:var(--text-muted); font-weight:800; text-transform:uppercase;">Data Integrity Target</div>
                <div id="completeness-pct-side" style="font-size:1.1rem; font-weight:900; color:#0ea5e9;"><?= $completeness_pct ?>%</div>
            </div>
            <div style="background:rgba(0,0,0,0.05); border-radius:30px; height:10px; overflow:hidden; border:1px solid var(--border); box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
                <div id="completeness-bar-side" style="width:<?= $completeness_pct ?>%; height:100%; background:#0ea5e9; border-radius:30px; transition:width 0.8s ease; box-shadow:0 0 10px rgba(14,165,233,0.5);"></div>
            </div>
        </div>
    </div>

    <!-- ─── RIGHT: Section Panels ─── -->
    <div id="profileContent">

    <!-- ══════════════════════════════════════════════════ -->
    <!-- SECTION A: IDENTITY MATRIX -->
    <!-- ══════════════════════════════════════════════════ -->
    <div id="sec-a" class="profile-section">
        <div class="adm-card shadow-sm" style="margin-bottom:2rem; padding:3rem; border-radius:16px;">
            <div style="display:flex; gap:3.5rem; align-items:center; flex-wrap:wrap;">
                <!-- Profile Photo -->
                <div style="text-align:center; flex-shrink:0;">
                    <div style="position:relative; display:inline-block;">
                        <div style="padding: 8px; background: var(--surface-1); border-radius: 50%; border: 3px solid #0ea5e9; box-shadow: 0 10px 30px rgba(14,165,233,0.2);">
                            <img id="profile-photo-img"
                                 src="/RMU-Medical-Management-System/uploads/profiles/<?= e(!empty($profile['profile_photo']) ? $profile['profile_photo'] : 'default-avatar.png') ?>"
                                 onerror="this.src='/RMU-Medical-Management-System/image/default-avatar.png'"
                                 style="width:200px; height:200px; border-radius:50%; object-fit:cover;">
                        </div>
                        <label for="photoInput" title="Update Biometrics"
                               style="position:absolute; bottom:15px; right:15px; width:50px; height:50px; border-radius:50%; background:#0ea5e9; color:white; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:var(--shadow-md); border:4px solid var(--surface-1); transition:var(--transition);">
                            <i class="fas fa-camera-retro" style="font-size:1.3rem;"></i>
                        </label>
                        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="uploadPhoto(this)">
                    </div>
                    <div style="margin-top:2rem;">
                        <!-- Availability Toggle -->
                        <select id="availabilitySelect" class="form-control" style="font-size:1.1rem; font-weight:800; border-radius:30px; padding:0.8rem 1.5rem; border:2px solid var(--border); text-align:center;" onchange="setAvailability(this.value)">
                            <?php foreach(['Available','Busy','On Break','Off Duty'] as $st):
                                $cur = $profile['availability_status'] ?? 'Available';
                                $col = ['Available'=>'#10b981','Busy'=>'#ef4444','On Break'=>'#f59e0b','Off Duty'=>'#64748b'][$st] ?? '#64748b';
                            ?>
                            <option value="<?= $st ?>" <?= $cur===$st?'selected':'' ?> style="color:<?= $col ?>; font-weight:800;"><?= $st ?> State</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <!-- Identity Details -->
                <div style="flex:1; min-width:300px;">
                    <h1 style="margin:0 0 1rem; font-size:2.8rem; font-weight:900; letter-spacing:-0.03em; color:var(--text-primary);"><?= e($profile['full_name']) ?></h1>
                    <div style="display:flex; gap:1.2rem; flex-wrap:wrap; margin-bottom:2rem;">
                        <span class="adm-badge" style="background:rgba(14,165,233,0.1); color:#0ea5e9; padding:0.6rem 1.2rem; font-weight:800; font-size:1.1rem; border:1px solid rgba(14,165,233,0.3);"><?= e($profile['designation'] ?? 'Lab Technician') ?></span>
                        <span class="adm-badge" style="background:var(--surface-3); color:var(--text-primary); padding:0.6rem 1.2rem; font-weight:800; font-size:1.1rem; border:1px solid var(--border);"><i class="fas fa-stethoscope text-success"></i> <?= e($profile['specialization'] ?? 'General Diagnostics') ?></span>
                        <?php if(!empty($profile['lab_section'])): ?>
                        <span class="adm-badge" style="background:var(--surface-2); color:var(--text-secondary); border:1px solid var(--border); padding:0.6rem 1.2rem; font-weight:800; font-size:1.1rem;"><i class="fas fa-building text-warning"></i> <?= e($profile['lab_section']) ?> Section</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; font-size:1.1rem; color:var(--text-primary); background:var(--surface-1); padding:2rem; border-radius:12px; border:2px solid rgba(14,165,233,0.2);">
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-fingerprint" style="color:#0ea5e9; font-size:1.3rem;"></i> <span><strong>System UID:</strong> <?= e($profile['technician_id'] ?? 'N/A') ?></span></div>
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-envelope-open-text" style="color:#0ea5e9; font-size:1.3rem;"></i> <span style="font-weight:600;"><?= e($profile['email'] ?? '') ?></span></div>
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-phone-volume" style="color:#0ea5e9; font-size:1.3rem;"></i> <span style="font-weight:600;"><?= e($profile['phone'] ?? '') ?></span></div>
                        <?php if($age): ?>
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-dna" style="color:#0ea5e9; font-size:1.3rem;"></i> <span style="font-weight:600;">Biological Age: <?= $age ?></span></div>
                        <?php endif; ?>
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-calendar-check" style="color:#0ea5e9; font-size:1.3rem;"></i> <span>Operational Since <?= $profile['member_since'] ? date('M Y', strtotime($profile['member_since'])) : 'N/A' ?></span></div>
                        <?php if(!empty($profile['last_login'])): ?>
                        <div style="display:flex; align-items:center; gap:1rem;"><i class="fas fa-satellite-dish" style="color:#0ea5e9; font-size:1.3rem;"></i> <span>Last Ping: <?= date('d M, H:i', strtotime($profile['last_login'])) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <?php if($lic_warning): ?>
                    <div style="margin-top:2rem; padding:1.5rem 2rem; background:rgba(239,68,68,0.1); border-left:6px solid #ef4444; border-radius:12px; font-size:1.1rem; color:#dc2626; font-weight:700; display:flex; align-items:center; gap:1.5rem;">
                        <i class="fas fa-radiation" style="font-size:2rem;"></i>
                        <div>
                            <strong>Credential Overhaul Imminent:</strong> Your operational clearance expires on <span style="text-decoration:underline;"><?= date('d M Y', strtotime($lic_exp)) ?></span> (<?= $lic_days_left ?> cycles remaining). Immediate upload of certified physical renewal documentation required.
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Completeness -->
                <div style="flex-shrink:0; min-width:220px; text-align:center; padding:2rem; background:var(--surface-1); border-radius:24px; border:1px solid var(--border); box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                    <canvas id="completenessDonut" width="160" height="160"></canvas>
                    <div style="font-size:1.1rem; font-weight:900; color:#0ea5e9; margin-top:1.5rem; text-transform:uppercase;" id="completeness-label"><?= $completeness_pct ?>% Metric</div>
                    <div style="font-size:0.9rem; color:var(--text-muted); font-weight:600; margin-top:.5rem;">Data Integration Path</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════ -->
    <!-- SECTION B: PERSONAL DOSSIER -->
    <!-- ══════════════════════════════════════════════════ -->
    <div id="sec-b" class="profile-section" style="display:none;">
        <div class="adm-card shadow-sm" style="padding:3rem; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-weight:900; font-size:1.8rem; color:var(--text-primary);"><i class="fas fa-address-card" style="color:#0ea5e9; margin-right:.8rem;"></i> Personal Dossier Matrix</h3>
                <button class="adm-btn adm-adm-btn adm-btn-primary" style="background:#0ea5e9; border-radius:10px; font-weight:900;" onclick="savePersonalInfo()"><span class="btn-text"><i class="fas fa-code-branch" style="margin-right:.5rem;"></i> Synchronize Array</span></button>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem;">
                <!-- Form Group Elements -->
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Personnel Legal Name <span style="color:#ef4444">*</span></label>
                    <input type="text" id="pi_full_name" class="form-control" value="<?= e($profile['full_name']) ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Date of Birth</label>
                    <input type="date" id="pi_dob" class="form-control" value="<?= e($profile['date_of_birth'] ?? '') ?>" onchange="calcAge()" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Age (Verified Calculus)</label>
                    <input type="text" id="pi_age" class="form-control" value="<?= $age ? $age . ' cycles' : '' ?>" readonly style="background:var(--surface-3); font-weight:900; color:#0ea5e9; padding:1.2rem; font-size:1.2rem;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Genetic/Gender Target</label>
                    <select id="pi_gender" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                        <?php foreach(['Male','Female','Other'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected':'' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Nationality Sector</label>
                    <input type="text" id="pi_nationality" class="form-control" value="<?= e($profile['nationality'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Matrimonial State</label>
                    <select id="pi_marital" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                        <?php foreach(['Single','Married','Divorced','Widowed'] as $m): ?>
                        <option value="<?= $m ?>" <?= ($profile['marital_status'] ?? '') === $m ? 'selected':'' ?>><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Primary Comm Channel <span style="color:#ef4444">*</span></label>
                    <input type="tel" id="pi_phone" class="form-control" value="<?= e($profile['phone'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Secondary Comm Link</label>
                    <input type="tel" id="pi_phone2" class="form-control" value="<?= e($profile['phone2'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Residential Vector Coordinates (Address)</label>
                    <input type="text" id="pi_address" class="form-control" value="<?= e($profile['address'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Jurisdiction / Grid Center</label>
                    <input type="text" id="pi_city" class="form-control" value="<?= e($profile['city'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Administrative Territory</label>
                    <input type="text" id="pi_region" class="form-control" value="<?= e($profile['region'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════ -->
    <!-- SECTION C: EXPERTISE PROFILE -->
    <!-- ══════════════════════════════════════════════════ -->
    <div id="sec-c" class="profile-section" style="display:none;">
        <div class="adm-card shadow-sm" style="padding:3rem; border-radius:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
                <h3 style="margin:0; font-weight:900; font-size:1.8rem; color:var(--text-primary);"><i class="fas fa-briefcase-medical" style="color:#0ea5e9; margin-right:.8rem;"></i> Professional Expertise Payload</h3>
                <button class="adm-btn adm-adm-btn adm-btn-primary" style="background:#0ea5e9; border-radius:10px; font-weight:900;" onclick="saveProfessionalProfile()"><span class="btn-text"><i class="fas fa-server" style="margin-right:.5rem;"></i> Update Domain Record</span></button>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem;">
                <div class="form-group" style="grid-column:1/-1;">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Technician Bio / Mission Protocol</label>
                    <textarea id="pp_bio" class="form-control" rows="3" placeholder="Define critical competencies, core mission parameters, and historical trajectory..." style="padding:1.5rem; font-size:1.2rem; font-weight:500; border:2px solid var(--role-accent); border-radius:12px;"><?= e($pro_profile['bio'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Primary Faculty Node</label>
                    <select id="pp_dept" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                        <option value="">— Unassigned Node —</option>
                        <?php foreach($depts as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($d['id'] == ($pro_profile['department_id'] ?? $profile['department_id'] ?? '')) ? 'selected' : '' ?>>
                            <?= e($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Diagnostic Specialization</label>
                    <select id="pp_spec" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                        <?php foreach(['Clinical Chemistry','Hematology','Microbiology','Immunology','Histopathology','Serology','Urinalysis','Molecular Biology','Parasitology','General Diagnostics'] as $sp): ?>
                        <option value="<?= $sp ?>" <?= ($pro_profile['specialization'] ?? $profile['specialization'] ?? '') === $sp ? 'selected':'' ?>><?= $sp ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Niche Sub-Domain Specialization</label>
                    <input type="text" id="pp_sub_spec" class="form-control" value="<?= e($pro_profile['sub_specialization'] ?? '') ?>" placeholder="e.g. Rare Blood Group Synthesis" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Operational Designation</label>
                    <select id="pp_desig" class="form-control" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                        <?php foreach(['Lab Technician','Senior Lab Technician','Lab Supervisor','Lab Manager','Chief Medical Laboratory Scientist'] as $ds): ?>
                        <option value="<?= $ds ?>" <?= ($pro_profile['designation'] ?? $profile['designation'] ?? '') === $ds ? 'selected':'' ?>><?= $ds ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Legacy Service Count (Cycles Yrs)</label>
                    <input type="number" id="pp_yoe" class="form-control" min="0" max="60" value="<?= e($pro_profile['years_of_experience'] ?? $profile['years_of_experience'] ?? '') ?>" style="padding:1.2rem; font-size:1.2rem; font-weight:900; color:#0ea5e9;">
                </div>
                <div style="grid-column:1/-1; background:rgba(239,68,68,0.05); border:2px solid rgba(239,68,68,0.2); border-radius:12px; padding:2rem; display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                    <div style="grid-column:1/-1;">
                        <h4 style="margin:0; font-size:1.2rem; font-weight:900; color:#ef4444; text-transform:uppercase;"><i class="fas fa-radiation" style="margin-right:.5rem;"></i> Licensing & Security Clearance Parameters</h4>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:#ef4444; margin-bottom:.6rem; display:block; text-transform:uppercase;">Cryptographic License Identifier <span style="color:#ef4444">*</span></label>
                        <input type="text" id="pp_lic" class="form-control" value="<?= e($pro_profile['license_number'] ?? $profile['license_number'] ?? '') ?>" style="padding:1.2rem; font-size:1.4rem; font-family:monospace; font-weight:900; letter-spacing:0.1em; color:#ef4444; border-color:rgba(239,68,68,0.3);">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:800; font-size:1.1rem; color:#ef4444; margin-bottom:.6rem; display:block; text-transform:uppercase;">Terminal Expiry Node <?= $lic_warning ? '<span class="adm-badge" style="background:#ef4444; color:white; font-size:0.85rem; margin-left:.5rem; padding:4px 8px;">CRITICAL EVENT</span>' : '' ?></label>
                        <input type="date" id="pp_lic_exp" class="form-control" value="<?= e($lic_exp ?? '') ?>" style="padding:1.2rem; font-size:1.3rem; font-weight:900; <?= $lic_warning ? 'border:3px solid #ef4444; background:rgba(239,68,68,0.1); color:#ef4444;' : '' ?>">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label style="font-weight:800; font-size:1.1rem; color:#ef4444; margin-bottom:.6rem; display:block; text-transform:uppercase;">Credentialing Governance Authority</label>
                        <input type="text" id="pp_lic_body" class="form-control" value="<?= e($pro_profile['license_issuing_body'] ?? '') ?>" placeholder="e.g. Allied Health Professions Council" style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label style="font-weight:800; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.6rem; display:block; text-transform:uppercase;">Semantic/Linguistic Neural Match (comma-separated)</label>
                    <input type="text" id="pp_langs" class="form-control" value="<?= e(is_array(json_decode($pro_profile['languages_spoken'] ?? 'null', true)) ? implode(', ', json_decode($pro_profile['languages_spoken'], true)) : '') ?>" placeholder="English, French, Twi..." style="padding:1.2rem; font-size:1.2rem; font-weight:800;">
                </div>
            </div>
        </div>
    </div>

    </div> <!-- End Content Array -->
</div> <!-- End Grid System -->

<?php // PART 2 inclusion contains panels D-L ?>
<?php include 'tab_profile_part2.php'; ?>
