<?php
// ============================================================
// NURSE DASHBOARD - ADVANCED PROFILE (MODULE 12)
// ============================================================
if (!isset($conn)) exit;

// ── GET CORE DATA ──────────────────────────────
$q_core = mysqli_query($conn, "
    SELECT n.*, u.email as login_email, u.name as login_name, u.created_at as member_since 
    FROM nurses n 
    JOIN users u ON n.user_id = u.id 
    WHERE n.user_id = " . (int)($_SESSION['user_id'] ?? 0) . " LIMIT 1
");
$core = mysqli_fetch_assoc($q_core);
$nurse_id = (int)($core['id'] ?? 0);

// ── INIT DEFAULT ROWS IF MISSING ─────────────────
if ($nurse_id > 0) {
    // Professional Profile
    $q_prof = mysqli_query($conn, "SELECT * FROM nurse_professional_profile WHERE nurse_id = $nurse_id LIMIT 1");
    if(mysqli_num_rows($q_prof) == 0) {
        mysqli_query($conn, "INSERT INTO nurse_professional_profile (nurse_id) VALUES ($nurse_id)");
        $q_prof = mysqli_query($conn, "SELECT * FROM nurse_professional_profile WHERE nurse_id = $nurse_id LIMIT 1");
    }
    $prof = mysqli_fetch_assoc($q_prof);

    // Completeness Engine
    $q_comp = mysqli_query($conn, "SELECT * FROM nurse_profile_completeness WHERE nurse_id = $nurse_id LIMIT 1");
    if(mysqli_num_rows($q_comp) == 0) {
        mysqli_query($conn, "INSERT INTO nurse_profile_completeness (nurse_id) VALUES ($nurse_id)");
        $q_comp = mysqli_query($conn, "SELECT * FROM nurse_profile_completeness WHERE nurse_id = $nurse_id LIMIT 1");
    }
    $comp = mysqli_fetch_assoc($q_comp);

    // Settings
    $q_set = mysqli_query($conn, "SELECT * FROM nurse_settings WHERE nurse_id = $nurse_id LIMIT 1");
    if(mysqli_num_rows($q_set) == 0) {
        mysqli_query($conn, "INSERT INTO nurse_settings (nurse_id) VALUES ($nurse_id)");
        $q_set = mysqli_query($conn, "SELECT * FROM nurse_settings WHERE nurse_id = $nurse_id LIMIT 1");
    }
    $settings = mysqli_fetch_assoc($q_set);
} else {
    $prof = $comp = $settings = [];
}

// ── FETCH ASSOCIATED DATA ────────────────────────
$qualifications = mysqli_query($conn, "SELECT * FROM nurse_qualifications WHERE nurse_id = $nurse_id ORDER BY year_awarded DESC");
$certifications = mysqli_query($conn, "SELECT * FROM nurse_certifications WHERE nurse_id = $nurse_id ORDER BY issue_date DESC");
$documents = mysqli_query($conn, "SELECT * FROM nurse_documents WHERE nurse_id = $nurse_id ORDER BY uploaded_at DESC");

// Stats for sidebar
$stat_pts = mysqli_query($conn, "SELECT COUNT(*) as c FROM bed_assignments WHERE attending_nurse_id = $nurse_id AND status='Occupied'")->fetch_assoc()['c'];
$stat_vit = mysqli_query($conn, "SELECT COUNT(*) as c FROM patient_vitals WHERE nurse_id = $nurse_id AND DATE(recorded_at) = CURDATE()")->fetch_assoc()['c'];
$stat_med = mysqli_query($conn, "SELECT COUNT(*) as c FROM medication_administration WHERE nurse_id = $nurse_id AND DATE(administered_at) = CURDATE() AND status='Administered'")->fetch_assoc()['c'];

$comp_pct = $comp['overall_percentage'] ?? 0;
$comp_color = 'var(--danger)';
if($comp_pct > 50) $comp_color = 'var(--warning)';
if($comp_pct > 80) $comp_color = 'var(--info)';
if($comp_pct == 100) $comp_color = 'var(--success)';
?>

<div class="tab-content active" id="profile">
    
    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-user-shield pulse-fade" style="margin-right:.8rem;"></i> Advanced Clinical Profile</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Comprehensive professional identity and clinical verification system.</p>
        </div>
        <div style="display:flex; gap:1.2rem; align-items:center;">
            <div class="adm-badge shadow-sm" style="background:rgba(var(--success-rgb),0.1); color:var(--success); border:1px solid rgba(var(--success-rgb),0.2); font-weight:800; padding:.6rem 1.2rem; border-radius:10px; display:flex; align-items:center; gap:.8rem;">
                <i class="fas fa-check-circle"></i> SYSTEM STATUS: <?= strtoupper(e($core['status'])) ?>
            </div>
            <button class="btn btn-ghost" onclick="location.reload();" style="border-radius:10px; padding:.5rem 1rem;"><span class="btn-text"><i class="fas fa-sync-alt"></i></span></button>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 350px 1fr; gap:2.5rem; align-items:start;">
        
        <!-- Left Column: Identity & Specs -->
        <div style="display:flex; flex-direction:column; gap:2.5rem;">
            
            <!-- Profile Identity Card -->
            <div class="adm-card shadow-sm" style="overflow:hidden; text-align:center; border:none; border-radius:24px;">
                <div style="height:120px; background:linear-gradient(135deg, var(--primary), var(--primary-dark)); position:relative;">
                     <div style="position:absolute; bottom:-40px; left:0; width:100%; display:flex; justify-content:center;">
                        <?php 
                            $photoPath = '../../uploads/profiles/' . ($core['profile_photo'] ?? '');
                            if(empty($core['profile_photo']) || !file_exists(dirname(__DIR__, 2) . '/uploads/profiles/' . $core['profile_photo'])) {
                                $photoPath = '../assets/images/default-avatar.png';
                            }
                        ?>
                        <div style="position:relative;">
                            <img src="<?= e($photoPath) ?>" alt="Profile" style="width:130px; height:130px; border-radius:40px; border:6px solid #fff; box-shadow:0 15px 35px rgba(0,0,0,0.15); object-fit:cover; background:#fff;">
                            <button class="btn btn-primary" style="position:absolute; bottom:5px; right:5px; width:42px; height:42px; border-radius:14px; padding:0; border:4px solid #fff; box-shadow:0 5px 15px rgba(var(--primary-rgb),0.3);" onclick="$('#avatarUploadInput').click()"><span class="btn-text">
                                <i class="fas fa-camera" style="font-size:1.4rem;"></i>
                            </span></button>
                        </div>
                     </div>
                </div>
                
                <div class="adm-card-body" style="padding:4rem 3rem 3rem;">
                    <form id="avatarForm" style="display:none;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="upload_photo">
                        <input type="file" id="avatarUploadInput" name="profile_photo" accept=".jpg,.jpeg,.png" onchange="uploadProfilePhoto()">
                    </form>

                    <h3 style="margin:2rem 0 0.5rem; color:var(--text-primary); font-size:2.2rem; font-weight:800; letter-spacing:-0.5px;"><?= e($core['full_name'] ?: 'Clinical Staff') ?></h3>
                    <div style="font-size:1.4rem; color:var(--primary); font-weight:700; margin-bottom:2rem; display:flex; align-items:center; justify-content:center; gap:.8rem;">
                        <i class="fas fa-user-md"></i> <?= e($prof['designation'] ?: 'Registered Nurse') ?>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:1rem; margin-bottom:3rem;">
                        <span style="background:rgba(var(--primary-rgb),0.05); color:var(--primary); padding:0.6rem 1.2rem; font-size:1.15rem; font-weight:800; border-radius:10px;"><i class="fas fa-fingerprint shadow-sm"></i> <?= e($core['nurse_id']) ?></span>
                        <span style="background:rgba(var(--info-rgb),0.05); color:var(--info); padding:0.6rem 1.2rem; font-size:1.15rem; font-weight:800; border-radius:10px;"><i class="fas fa-briefcase shadow-sm"></i> <?= (int)$prof['years_of_experience'] ?>+ Yrs</span>
                    </div>

                    <div style="text-align:left; background:var(--surface-2); padding:2rem; border-radius:18px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                            <span style="font-size:1.2rem; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em;">Profile Integrity</span>
                            <span style="font-weight:900; color:<?= $comp_color ?>; font-size:1.4rem;"><?= $comp_pct ?>%</span>
                        </div>
                        <div style="height:12px; background:rgba(0,0,0,0.05); border-radius:10px; overflow:hidden; box-shadow:inset 0 2px 4px rgba(0,0,0,0.05);">
                            <div style="width:<?= $comp_pct ?>%; height:100%; background:linear-gradient(90deg, <?= $comp_color ?>, color-mix(in srgb, <?= $comp_color ?> 80%, white)); transition:width 1s cubic-bezier(0.4, 0, 0.2, 1); border-radius:10px;"></div>
                        </div>
                        <?php if($comp_pct < 100): ?>
                            <p style="font-size:1.1rem; color:var(--danger); margin-top:1.2rem; font-weight:700; display:flex; align-items:center; gap:.6rem;">
                                <i class="fas fa-shield-alt"></i> Compliance check required.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Stats Module -->
            <div class="adm-card shadow-sm" style="border-radius:24px;">
                <div class="adm-card-header" style="background:transparent; border-bottom:1px solid var(--border); padding:1.5rem 2rem;">
                    <h3 style="font-size:1.4rem; font-weight:800; color:var(--text-primary);"><i class="fas fa-chart-line text-primary"></i> Clinical Footprint</h3>
                </div>
                <div class="adm-card-body" style="display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; padding:2rem;">
                    <div style="padding:1.8rem 1rem; background:rgba(var(--primary-rgb),0.03); border:1px solid rgba(var(--primary-rgb),0.05); border-radius:20px; text-align:center; transition:0.3s ease;" class="stat-box-hover">
                        <div style="font-size:2.8rem; font-weight:900; color:var(--primary); line-height:1;"><?= $stat_pts ?></div>
                        <div style="font-size:1rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; margin-top:0.8rem; letter-spacing:0.05em;">Current Care</div>
                    </div>
                    <div style="padding:1.8rem 1rem; background:rgba(var(--success-rgb),0.03); border:1px solid rgba(var(--success-rgb),0.05); border-radius:20px; text-align:center;" class="stat-box-hover">
                        <div style="font-size:2.8rem; font-weight:900; color:var(--success); line-height:1;"><?= $stat_med ?></div>
                        <div style="font-size:1rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; margin-top:0.8rem; letter-spacing:0.05em;">Meds Handled</div>
                    </div>
                    <div style="padding:1.8rem 1rem; background:rgba(var(--danger-rgb),0.03); border:1px solid rgba(var(--danger-rgb),0.05); border-radius:20px; text-align:center;" class="stat-box-hover">
                        <div style="font-size:2.8rem; font-weight:900; color:var(--danger); line-height:1;"><?= $stat_vit ?></div>
                        <div style="font-size:1rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; margin-top:0.8rem; letter-spacing:0.05em;">Vital Entries</div>
                    </div>
                    <div style="padding:1.8rem 1rem; background:rgba(var(--info-rgb),0.03); border:1px solid rgba(var(--info-rgb),0.05); border-radius:20px; text-align:center;" class="stat-box-hover">
                        <div style="font-size:2.4rem; font-weight:900; color:var(--info); line-height:1;">B+</div>
                        <div style="font-size:1rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; margin-top:0.8rem; letter-spacing:0.05em;">Core Rating</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Tabbed Content -->
        <div class="adm-card shadow-sm" style="min-height:800px; border-radius:24px; border:none; overflow:hidden;">
            <div class="adm-card-header" style="justify-content:flex-start; overflow-x:auto; padding:0 3rem; gap:3.5rem; white-space:nowrap; background:var(--surface-2); border-bottom:1.5px solid var(--border); height:80px;">
                <button class="btn btn-primary adm-tab-btn active" onclick="switchProfileTab('personal', this)" style="font-size:1.4rem; letter-spacing:0.02em;"><span class="btn-text">IDENTITY</span></button>
                <button class="btn btn-primary adm-tab-btn" onclick="switchProfileTab('professional', this)" style="font-size:1.4rem; letter-spacing:0.02em;"><span class="btn-text">CLINICAL</span></button>
                <button class="btn btn-primary adm-tab-btn" onclick="switchProfileTab('credentials', this)" style="font-size:1.4rem; letter-spacing:0.02em;"><span class="btn-text">ACADEMIC</span></button>
                <button class="btn btn-primary adm-tab-btn" onclick="switchProfileTab('documents', this)" style="font-size:1.4rem; letter-spacing:0.02em;"><span class="btn-text">HR VAULT</span></button>
                <button class="btn btn-primary adm-tab-btn" onclick="switchProfileTab('security', this)" style="font-size:1.4rem; letter-spacing:0.02em; color:var(--danger);"><span class="btn-text">SECURITY</span></button>
            </div>

            <div class="adm-card-body" style="padding:4rem 5rem;">
                
                <!-- Tab: Personal -->
                <div id="tab-personal" class="profile-tab-panel active">
                    <form id="form-personal" class="ajax-profile-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_personal">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Full Legal Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?= e($core['full_name']) ?>" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" value="<?= e($core['date_of_birth']) ?>" onchange="calcAge(this.value)" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Gender</label>
                                <select class="form-control" name="gender" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                                    <option value="Female" <?= $core['gender']=='Female'?'selected':'' ?>>Female</option>
                                    <option value="Male" <?= $core['gender']=='Male'?'selected':'' ?>>Male</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Nationality</label>
                                <input type="text" class="form-control" name="nationality" value="<?= e($core['nationality']) ?>" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Primary Phone</label>
                                <input type="tel" class="form-control" name="phone" value="<?= e($core['phone']) ?>" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Contact Email</label>
                                <input type="email" class="form-control" name="email" value="<?= e($core['email']) ?>" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:4rem;">
                            <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Residential Address</label>
                            <input type="text" class="form-control" name="address" value="<?= e($core['address']) ?>" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                        </div>
                        <div style="margin-top:3rem; padding-top:2rem; border-top:1.5px solid var(--border); display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary" style="padding:1.4rem 4rem; border-radius:14px; font-weight:900; font-size:1.4rem; box-shadow:0 10px 20px rgba(var(--primary-rgb),0.2);"><span class="btn-text">UPDATE IDENTITY PROFILE</span></button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Professional -->
                <div id="tab-professional" class="profile-tab-panel">
                    <form id="form-professional" class="ajax-profile-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_professional">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Designation</label>
                                <input type="text" class="form-control" name="designation" value="<?= e($prof['designation']) ?>" placeholder="e.g. Senior Staff Nurse" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Departmental Specialization</label>
                                <input type="text" class="form-control" name="specialization" value="<?= e($prof['specialization']) ?>" placeholder="e.g. ICU, Maternity" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Years of Clinical Experience</label>
                                <input type="number" class="form-control" name="years_of_experience" value="<?= e($prof['years_of_experience']) ?>" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                            <div class="form-group">
                                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Nursing Council ID</label>
                                <input type="text" class="form-control" name="license_number" value="<?= e($prof['license_number']) ?>" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:4rem;">
                            <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Professional Bio</label>
                            <textarea class="form-control" name="bio" rows="6" style="border-radius:12px; font-weight:500; font-size:1.4rem; padding:1.5rem; border:1.5px solid var(--border);"><?= e($prof['bio']) ?></textarea>
                        </div>
                        <div style="margin-top:3rem; padding-top:2rem; border-top:1.5px solid var(--border); display:flex; justify-content:flex-end;">
                            <button type="submit" class="btn btn-primary" style="padding:1.4rem 4rem; border-radius:14px; font-weight:900; font-size:1.4rem; box-shadow:0 10px 20px rgba(var(--primary-rgb),0.2);"><span class="btn-text">SAVE CLINICAL COMPETENCIES</span></button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Credentials -->
                <div id="tab-credentials" class="profile-tab-panel">
                    <div style="margin-bottom:5rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
                            <h4 style="margin:0; font-weight:900; font-size:1.8rem; color:var(--text-primary);"><i class="fas fa-graduation-cap text-primary"></i> Academic Credentials</h4>
                            <button class="btn btn-primary" onclick="openQualModal()" style="border-radius:12px; font-weight:800; padding:.8rem 1.8rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Qualification</span></button>
                        </div>
                        <div class="adm-table-wrap" style="border-radius:18px; border:1.5px solid var(--border); overflow:hidden;">
                            <table class="adm-table">
                                <thead style="background:var(--surface-2);">
                                    <tr>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Degree / Technical Title</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Granting Institution</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Fiscal Year</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Status</th>
                                        <th style="width:80px; padding:1.8rem 2rem;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($qualifications) > 0): while($q = mysqli_fetch_assoc($qualifications)): ?>
                                        <tr>
                                            <td style="padding:1.8rem 2rem;"><strong style="font-size:1.4rem; color:var(--text-primary);"><?= e($q['degree_name']) ?></strong></td>
                                            <td style="padding:1.8rem 2rem; font-weight:600; color:var(--text-secondary);"><?= e($q['institution']) ?></td>
                                            <td style="padding:1.8rem 2rem;"><span class="adm-badge" style="background:rgba(var(--info-rgb),0.1); color:var(--info); font-weight:800;"><?= e($q['year_awarded']) ?></span></td>
                                            <td style="padding:1.8rem 2rem;"><span class="adm-badge" style="background:rgba(var(--success-rgb),0.1); color:var(--success); font-weight:800;"><i class="fas fa-check-double"></i> VERIFIED</span></td>
                                            <td style="text-align:right; padding:1.8rem 2rem;">
                                                <button class="btn btn-ghost text-danger" onclick="delRecord('qualification', <?= $q['qualification_id'] ?>)" style="padding:.5rem; border-radius:8px;"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="5" style="text-align:center; padding:5rem; color:var(--text-muted); font-weight:600; font-size:1.3rem;">No academic qualifications documented.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2.5rem;">
                            <h4 style="margin:0; font-weight:900; font-size:1.8rem; color:var(--text-primary);"><i class="fas fa-certificate text-warning"></i> Professional Boards & Certs</h4>
                            <button class="btn btn-primary" onclick="openCertModal()" style="border-radius:12px; font-weight:800; padding:.8rem 1.8rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add Certification</span></button>
                        </div>
                        <div class="adm-table-wrap" style="border-radius:18px; border:1.5px solid var(--border); overflow:hidden;">
                            <table class="adm-table">
                                <thead style="background:var(--surface-2);">
                                    <tr>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Specialization / Cert</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Issuing Body</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem;">Clinical Expiry</th>
                                        <th style="width:80px; padding:1.8rem 2rem;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($certifications) > 0): while($c = mysqli_fetch_assoc($certifications)): ?>
                                        <tr>
                                            <td style="padding:1.8rem 2rem;"><strong style="font-size:1.4rem; color:var(--text-primary);"><?= e($c['certification_name']) ?></strong></td>
                                            <td style="padding:1.8rem 2rem; font-weight:600; color:var(--text-secondary);"><?= e($c['issuing_organization']) ?></td>
                                            <td style="padding:1.8rem 2rem;">
                                                <?php 
                                                    $cd = strtotime($c['expiry_date']);
                                                    if($cd < time()) echo '<span class="adm-badge" style="background:rgba(var(--danger-rgb),0.1); color:var(--danger); font-weight:800;"><i class="fas fa-times-circle"></i> EXPIRED</span>';
                                                    else echo '<span style="font-weight:700; color:var(--primary);">' . date('D, d M Y', $cd) . '</span>';
                                                ?>
                                            </td>
                                            <td style="text-align:right; padding:1.8rem 2rem;">
                                                <button class="btn btn-ghost text-danger" onclick="delRecord('certification', <?= $c['certification_id'] ?>)" style="padding:.5rem; border-radius:8px;"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                                            </td>
                                        </tr>
                                    <?php endwhile; else: ?>
                                        <tr><td colspan="4" style="text-align:center; padding:5rem; color:var(--text-muted); font-weight:600; font-size:1.3rem;">No certifications documented.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Documents -->
                <div id="tab-documents" class="profile-tab-panel">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3.5rem;">
                        <div>
                            <h4 style="margin:0; font-weight:900; font-size:1.8rem; color:var(--text-primary);"><i class="fas fa-folder-open text-primary"></i> HR Repository</h4>
                            <p style="margin:0.5rem 0 0; font-size:1.2rem; color:var(--text-muted); font-weight:600;">Secure digitized copies of clinical credentials.</p>
                        </div>
                        <button class="btn btn-primary" onclick="openDocModal()" style="border-radius:12px; font-weight:800; padding:.8rem 1.8rem;"><span class="btn-text"><i class="fas fa-cloud-upload-alt"></i> Upload Files</span></button>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:2.5rem;">
                        <?php if(mysqli_num_rows($documents) > 0): while($doc = mysqli_fetch_assoc($documents)): ?>
                            <div class="adm-card shadow-sm" style="border:1.5px solid var(--border); background:var(--surface-2); border-radius:20px; transition:0.3s ease;" class="doc-card-hover">
                                <div class="adm-card-body" style="padding:2.5rem; display:flex; align-items:center; gap:2rem;">
                                    <div style="width:60px; height:60px; border-radius:15px; background:rgba(var(--primary-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2.8rem; color:var(--primary); flex-shrink:0;">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-weight:800; color:var(--text-primary); font-size:1.4rem; margin-bottom:0.4rem;" class="truncate" title="<?= e($doc['file_name']) ?>"><?= e($doc['file_name']) ?></div>
                                        <div style="font-size:1.15rem; color:var(--text-muted); font-weight:700;"><?= strtoupper(substr(strrchr($doc['file_path'], "."), 1)) ?> &bull; <?= round($doc['file_size']/1024) ?> KB</div>
                                    </div>
                                    <div style="display:flex; flex-direction:column; gap:0.8rem;">
                                        <a href="<?= e($doc['file_path']) ?>" download class="btn btn-icon btn-ghost text-primary" style="padding:.5rem; border-radius:8px;"><span class="btn-text"><i class="fas fa-download"></i></span></a>
                                        <button class="btn btn-ghost text-danger" onclick="delRecord('document', <?= $doc['document_id'] ?>)" style="padding:.5rem; border-radius:8px;"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; else: ?>
                            <div style="grid-column: 1/-1; text-align:center; padding:8rem 2rem; background:rgba(0,0,0,0.02); border:2px dashed var(--border); border-radius:24px;">
                                <i class="fas fa-cloud-open" style="font-size:6rem; color:var(--border); margin-bottom:2.5rem;"></i>
                                <h4 style="font-weight:800; color:var(--text-muted); font-size:1.8rem;">HR Repository is Empty</h4>
                                <p style="font-size:1.2rem; color:var(--text-muted); max-width:400px; margin:0 auto;">Please upload your digitized certificates and identification documents for HR compliance.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Security -->
                <div id="tab-security" class="profile-tab-panel">
                    <div style="background:rgba(var(--danger-rgb),0.02); padding:3.5rem; border-radius:24px; margin-bottom:5rem; border:1.5px solid rgba(var(--danger-rgb),0.1);">
                        <div style="display:flex; align-items:center; gap:1.5rem; margin-bottom:2.5rem;">
                            <div style="width:50px; height:50px; border-radius:12px; background:rgba(var(--danger-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2rem; color:var(--danger);">
                                <i class="fas fa-fingerprint"></i>
                            </div>
                            <div>
                                <h4 style="color:var(--danger); margin:0; font-weight:900; font-size:1.8rem; letter-spacing:-0.4px;">Credentials Management</h4>
                                <p style="margin:0; font-size:1.25rem; color:var(--text-secondary); font-weight:600;">Authorized modification of system access tokens.</p>
                            </div>
                        </div>
                        
                        <form id="form-password" class="ajax-profile-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group" style="margin-bottom:2.5rem;">
                                <label style="font-weight:800; color:var(--danger); margin-bottom:.8rem; display:block; font-size:1.1rem; text-transform:uppercase;">Current Secure Password</label>
                                <input type="password" class="form-control" name="current_password" required style="height:55px; border-radius:12px; font-weight:700; border-color:rgba(var(--danger-rgb),0.3);">
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:3rem;">
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">New Password (min 8 chars)</label>
                                    <input type="password" class="form-control" name="new_password" id="pw_new" required oninput="checkStrength()" style="height:55px; border-radius:12px; font-weight:700;">
                                    <div style="height:6px; background:rgba(0,0,0,0.05); border-radius:10px; margin-top:1rem; overflow:hidden;">
                                        <div id="pw_meter" style="width:0; height:100%; transition:width 0.4s cubic-bezier(0.4, 0, 0.2, 1); background:var(--danger);"></div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Confirm New Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required style="height:55px; border-radius:12px; font-weight:700;">
                                </div>
                            </div>
                            <div style="display:flex; justify-content:flex-end;">
                                <button type="submit" class="btn btn-danger" style="padding:1.4rem 4.5rem; border-radius:14px; font-weight:900; font-size:1.3rem; box-shadow:0 10px 20px rgba(var(--danger-rgb),0.2);"><span class="btn-text">UPDATE ACCESS CREDENTIALS</span></button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h4 style="font-weight:900; font-size:1.8rem; margin-bottom:2.5rem; display:flex; align-items:center; gap:1.2rem; color:var(--text-primary);">
                            <i class="fas fa-id-card-alt text-primary"></i> Active System Sessions
                        </h4>
                        <div class="adm-table-wrap" style="border-radius:18px; border:1.5px solid var(--border); overflow:hidden;">
                            <table class="adm-table">
                                <thead style="background:var(--surface-2);">
                                    <tr>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.04em;">Device / Access Point</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.04em;">Secure IP</th>
                                        <th style="padding:1.8rem 2rem; font-weight:800; text-transform:uppercase; font-size:1.1rem; letter-spacing:0.04em;">Last Activity</th>
                                        <th style="width:120px; padding:1.8rem 2rem;"></th>
                                    </tr>
                                </thead>
                                <tbody id="sessions_table_body">
                                    <tr><td colspan="4" style="text-align:center; padding:5rem;"><i class="fas fa-spinner fa-spin fa-2x text-primary" style="margin-bottom:1rem;"></i><br><span style="font-weight:700; color:var(--text-muted);">Synchronizing authentication logs...</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab: Settings -->
                <div id="tab-settings" class="profile-tab-panel">
                    <form id="form-settings" class="ajax-profile-form">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_settings">
                        
                        <h4 style="font-weight:800; margin-bottom:2.5rem; border-bottom:1px solid var(--border); padding-bottom:1rem;">Dashboard Notifications</h4>
                        <div style="display:flex; flex-direction:column; gap:2rem;">
                            <label style="display:flex; align-items:center; gap:1.5rem; cursor:pointer; background:var(--surface-2); padding:1.5rem; border-radius:10px;">
                                <input type="checkbox" name="notify_new_task" value="1" <?= $settings['notify_new_task']?'checked':'' ?> style="width:22px; height:22px;">
                                <div style="flex:1;">
                                    <div style="font-weight:700; font-size:1.4rem;">New Task Alerts</div>
                                    <div style="color:var(--text-muted); font-size:1.15rem;">Get notified when a doctor assigns a new clinical task to your ward.</div>
                                </div>
                            </label>

                            <label style="display:flex; align-items:center; gap:1.5rem; cursor:pointer; background:var(--surface-2); padding:1.5rem; border-radius:10px;">
                                <input type="checkbox" name="notify_abnormal_vitals" value="1" <?= $settings['notify_abnormal_vitals']?'checked':'' ?> style="width:22px; height:22px;">
                                <div style="flex:1;">
                                    <div style="font-weight:700; font-size:1.4rem; color:var(--red);">Critical Vitals Flags</div>
                                    <div style="color:var(--text-muted); font-size:1.15rem;">Real-time notifications for patient vitals outside normal physiologic parameters.</div>
                                </div>
                            </label>

                            <label style="display:flex; align-items:center; gap:1.5rem; cursor:pointer; background:var(--surface-2); padding:1.5rem; border-radius:10px;">
                                <input type="checkbox" name="notify_med_reminder" value="1" <?= $settings['notify_med_reminder']?'checked':'' ?> style="width:22px; height:22px;">
                                <div style="flex:1;">
                                    <div style="font-weight:700; font-size:1.4rem;">Medication Schedule Reminders</div>
                                    <div style="color:var(--text-muted); font-size:1.15rem;">Notifications when medications are due for administration.</div>
                                </div>
                            </label>
                            
                            <div class="form-group" style="margin-top:2rem;">
                                <label>Notification Sound Protocol</label>
                                <select class="form-control" name="alert_sound_enabled">
                                    <option value="1" <?= $settings['alert_sound_enabled']?'selected':'' ?>>Audible Chimes Enabled</option>
                                    <option value="0" <?= !$settings['alert_sound_enabled']?'selected':'' ?>>Silent / Visual Only</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top:3rem; padding-top:2rem; border-top:1px solid var(--border);">
                            <button type="submit" class="btn btn-primary"><span class="btn-text">Preserve Preferences</span></button>
                        </div>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<style>
.profile-tab-panel { display: none; }
.profile-tab-panel.active { display: block; }
.truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.adm-tab-btn {
    border: none; background: none; font-size: 1.3rem; font-weight: 700; color: var(--text-muted); cursor: pointer; position: relative; padding: 2rem 0;
}
.adm-tab-btn.active { color: var(--primary); }
.adm-tab-btn.active::after {
    content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; background: var(--primary);
}
</style>

<!-- ════════════════════════════════════════════════ -->
<!-- MODALS (Native)                                  -->
<!-- ════════════════════════════════════════════════ -->

<!-- Qual Modal -->
<div class="modal-bg" id="qualModal">
    <div class="modal-box" style="max-width:600px; border-radius:24px;">
        <div class="modal-header" style="padding:2.5rem 3rem;">
            <div style="display:flex; align-items:center; gap:1.5rem;">
                <div style="width:45px; height:45px; border-radius:12px; background:rgba(var(--primary-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2rem; color:var(--primary);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3 style="margin:0; font-weight:900; font-size:1.8rem; letter-spacing:-0.5px;">Add Academic Credential</h3>
            </div>
            <button class="btn btn-primary modal-close" onclick="closeQualModal()" type="button" style="font-size:2.5rem;"><span class="btn-text">×</span></button>
        </div>
        <form id="form-qual" class="ajax-profile-form" style="padding:3rem;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_qual">
            <div class="form-group" style="margin-bottom:2.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Degree Title</label>
                <input type="text" class="form-control" name="degree_name" required placeholder="e.g. Bachelor of Science in Nursing" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div class="form-group" style="margin-bottom:2.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Granting Institution</label>
                <input type="text" class="form-control" name="institution" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Year Graduated</label>
                <input type="number" class="form-control" name="year_awarded" required min="1960" max="<?= date('Y') ?>" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1.5rem; margin-top:4rem; padding-top:2rem; border-top:1.5px solid var(--border);">
                <button type="button" class="btn btn-ghost" onclick="closeQualModal()" style="padding:1.2rem 3rem; border-radius:12px; font-weight:800;"><span class="btn-text">DISCARD</span></button>
                <button type="submit" class="btn btn-primary" style="padding:1.2rem 4rem; border-radius:12px; font-weight:900; box-shadow:0 8px 15px rgba(var(--primary-rgb),0.2);"><span class="btn-text">ADD CREDENTIAL</span></button>
            </div>
        </form>
    </div>
</div>

<!-- Cert Modal -->
<div class="modal-bg" id="certModal">
    <div class="modal-box" style="max-width:600px; border-radius:24px;">
        <div class="modal-header" style="padding:2.5rem 3rem;">
            <div style="display:flex; align-items:center; gap:1.5rem;">
                <div style="width:45px; height:45px; border-radius:12px; background:rgba(var(--warning-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2rem; color:var(--warning);">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3 style="margin:0; font-weight:900; font-size:1.8rem; letter-spacing:-0.5px;">Add Professional Cert</h3>
            </div>
            <button class="btn btn-primary modal-close" onclick="closeCertModal()" type="button" style="font-size:2.5rem;"><span class="btn-text">×</span></button>
        </div>
        <form id="form-cert" class="ajax-profile-form" style="padding:3rem;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add_cert">
            <div class="form-group" style="margin-bottom:2.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Certification Name</label>
                <input type="text" class="form-control" name="certification_name" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div class="form-group" style="margin-bottom:2.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Issuing Body</label>
                <input type="text" class="form-control" name="issuing_organization" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem; margin-bottom:1.5rem;">
                <div class="form-group">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Effective From</label>
                    <input type="date" class="form-control" name="issue_date" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                </div>
                <div class="form-group">
                    <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Clinical Expiry</label>
                    <input type="date" class="form-control" name="expiry_date" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1.5rem; margin-top:4rem; padding-top:2rem; border-top:1.5px solid var(--border);">
                <button type="button" class="btn btn-ghost" onclick="closeCertModal()" style="padding:1.2rem 3rem; border-radius:12px; font-weight:800;"><span class="btn-text">DISCARD</span></button>
                <button type="submit" class="btn btn-primary" style="padding:1.2rem 4rem; border-radius:12px; font-weight:900; box-shadow:0 8px 15px rgba(var(--primary-rgb),0.2);"><span class="btn-text">REGISTER RECORD</span></button>
            </div>
        </form>
    </div>
</div>

<!-- Doc Modal -->
<div class="modal-bg" id="docModal">
    <div class="modal-box" style="max-width:600px; border-radius:24px;">
        <div class="modal-header" style="padding:2.5rem 3rem;">
            <div style="display:flex; align-items:center; gap:1.5rem;">
                <div style="width:45px; height:45px; border-radius:12px; background:rgba(var(--success-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2rem; color:var(--success);">
                    <i class="fas fa-file-upload"></i>
                </div>
                <h3 style="margin:0; font-weight:900; font-size:1.8rem; letter-spacing:-0.5px;">Secure File Transfer</h3>
            </div>
            <button class="btn btn-primary modal-close" onclick="closeDocModal()" type="button" style="font-size:2.5rem;"><span class="btn-text">×</span></button>
        </div>
        <form id="form-doc" class="ajax-profile-form" style="padding:3rem;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="upload_doc">
            <div class="form-group" style="margin-bottom:2.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Internal Document Alias</label>
                <input type="text" class="form-control" name="file_name" required placeholder="e.g. License ID Card" style="height:55px; border-radius:12px; font-weight:700; font-size:1.4rem;">
            </div>
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label style="font-weight:700; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Select Encrypted Object</label>
                <input type="file" class="form-control" name="doc_file" required accept=".pdf,.jpg,.jpeg,.png" style="padding:1.2rem; height:auto; border-radius:12px;">
                <div style="margin-top:1rem; padding:1.2rem; background:rgba(var(--info-rgb),0.05); border-radius:10px; border:1px solid rgba(var(--info-rgb),0.1); display:flex; align-items:center; gap:1rem;">
                    <i class="fas fa-user-shield text-info" style="font-size:1.5rem;"></i>
                    <span style="font-size:1.1rem; color:var(--text-muted); font-weight:600;">Files are encrypted and stored in HIPAA-compliant volumes.</span>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:1.5rem; margin-top:4rem; padding-top:2rem; border-top:1.5px solid var(--border);">
                <button type="button" class="btn btn-ghost" onclick="closeDocModal()" style="padding:1.2rem 3rem; border-radius:12px; font-weight:800;"><span class="btn-text">CANCEL</span></button>
                <button type="submit" class="btn btn-primary" style="padding:1.2rem 4rem; border-radius:12px; font-weight:900; box-shadow:0 8px 15px rgba(var(--primary-rgb),0.2);"><span class="btn-text">START UPLOAD</span></button>
            </div>
        </form>
    </div>
</div>

<script>
function switchProfileTab(tabId, btn) {
    document.querySelectorAll('.profile-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.adm-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
}

function openQualModal() { document.getElementById('qualModal').style.display='flex'; }
function closeQualModal() { document.getElementById('qualModal').style.display='none'; }
function openCertModal() { document.getElementById('certModal').style.display='flex'; }
function closeCertModal() { document.getElementById('certModal').style.display='none'; }
function openDocModal() { document.getElementById('docModal').style.display='flex'; }
function closeDocModal() { document.getElementById('docModal').style.display='none'; }

function checkStrength() {
    let pw = $('#pw_new').val();
    let meter = $('#pw_meter');
    if(pw.length < 8) { meter.css({'width':'30%', 'background':'var(--danger)'}); return; }
    if(pw.match(/[A-Z]/) && pw.match(/[0-9]/)) { meter.css({'width':'70%', 'background':'var(--warning)'}); }
    if(pw.match(/[^a-zA-Z0-9]/)) { meter.css({'width':'100%', 'background':'var(--success)'}); }
}

function uploadProfilePhoto() {
    const fd = new FormData(document.getElementById('avatarForm'));
    $.ajax({
        url: '../nurse/process_profile_advanced.php', type: 'POST',
        data: fd, processData: false, contentType: false, dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire({ icon: 'success', title: 'Identity Verified', text: 'Profile photograph updated successfully.', timer: 1500, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Upload Failed', text: res.message });
            }
        }
    });
}

function loadActiveSessions() {
    $.post('../nurse/process_profile_advanced.php', {action: 'get_sessions', csrf_token: '<?= generateCsrfToken() ?>'}, function(res) {
        if(res.success) {
            let html = '';
            res.data.forEach(s => {
                let badge = s.is_current_session ? '<span class="adm-badge" style="background:rgba(var(--success-rgb),0.1); color:var(--success); border:1px solid rgba(var(--success-rgb),0.2); font-weight:800; padding:.4rem .8rem; font-size:.9rem; border-radius:6px;">LIVE NOW</span>' : '';
                let btn = s.is_current_session ? '' : `<button class="btn btn-ghost text-danger" onclick="killSession(${s.session_id})" style="padding:.4rem 1rem; border-radius:8px; font-size:1.1rem;"><span class="btn-text"><i class="fas fa-sign-out-alt"></i></span></button>`;
                html += `<tr>
                    <td style="padding:1.8rem 2rem;"><strong style="font-size:1.4rem; color:var(--text-primary);">${s.browser}</strong><br><small style="font-weight:600; color:var(--text-muted); opacity:0.8;">${s.device_info}</small> ${badge}</td>
                    <td style="padding:1.8rem 2rem; font-weight:700; color:var(--primary);">${s.ip_address}</td>
                    <td style="padding:1.8rem 2rem; font-weight:600; color:var(--text-muted);">${s.last_active}</td>
                    <td style="text-align:right; padding:1.8rem 2rem;">${btn}</td>
                </tr>`;
            });
            $('#sessions_table_body').html(html);
        }
    }, 'json');
}

function killSession(sid) {
    Swal.fire({
        title: 'Revoke Access?',
        text: 'This session will be immediately disconnected from the system.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Kill Session'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../nurse/process_profile_advanced.php', {action: 'kill_session', session_id: sid, csrf_token: '<?= generateCsrfToken() ?>'}, function(res) {
                if(res.success) {
                    Swal.fire('Terminated', 'Access revoked successfully.', 'success');
                    loadActiveSessions();
                }
            }, 'json');
        }
    });
}

function delRecord(type, id) {
    Swal.fire({
        title: `Delete ${type}?`,
        text: 'This action is permanent and will be logged.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger)',
        confirmButtonText: 'Confirm Deletion'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('../nurse/process_profile_advanced.php', {action: 'delete_record', type: type, id: id, csrf_token: '<?= generateCsrfToken() ?>'}, function(res) {
                if(res.success) {
                    Swal.fire({ icon: 'success', title: 'Record Expunged', showConfirmButton: false, timer: 1000 });
                    setTimeout(() => location.reload(), 1000);
                }
            }, 'json');
        }
    });
}

$(document).ready(function() {
    loadActiveSessions();
    $('.ajax-profile-form').on('submit', function(e) {
        if(this.id === 'avatarForm') return;
        e.preventDefault();
        const btn = $(this).find('button[type=submit]');
        const origHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Finalizing Sync...');
        
        let fd = new FormData(this);
        $.ajax({
            url: '../nurse/process_profile_advanced.php', type: 'POST',
            data: fd, processData: false, contentType: false, dataType: 'json',
            success: function(res) {
                if(res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Data Synchronized',
                        text: 'Professional metadata updated successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Constraint Violation', text: res.message });
                    btn.prop('disabled', false).html(origHtml);
                }
            },
            error: function() {
                Swal.fire({ icon: 'error', title: 'Network Fault', text: 'Communication with clinical server failed.' });
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });
});
</script>
