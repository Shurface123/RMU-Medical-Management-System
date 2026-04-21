<?php
/**
 * tab_profile.php — Module 14: Advanced Staff Profile (Modernized)
 */
$quals = dbSelect($conn,"SELECT * FROM staff_qualifications WHERE staff_id=? ORDER BY qualification_id DESC","i",[$staff_id]);
$docs  = dbSelect($conn,"SELECT * FROM staff_documents WHERE staff_id=? ORDER BY document_id DESC","i",[$staff_id]);
$sessions = dbSelect($conn,"SELECT * FROM staff_sessions WHERE staff_id=? ORDER BY session_id DESC LIMIT 5","i",[$staff_id]);
$settings = dbRow($conn,"SELECT * FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
$compl_pct = $completeness ?? 0;
$dob = $staff['date_of_birth'] ?? '';
$age = $dob ? floor((time() - strtotime($dob)) / (365.25 * 24 * 3600)) : '—';
?>
<div id="sec-profile" class="dash-section">

    <!-- Hero Identity Section -->
    <div class="profile-hero card">
        <div class="hero-banner"></div>
        <div class="hero-content">
            <div class="avatar-stack">
                <div id="profilePhotoWrap" class="p-avatar" onclick="document.getElementById('photoInput').click()">
                    <?php if(!empty($staff['profile_photo'])): ?>
                        <img id="profilePhotoImg" src="/RMU-Medical-Management-System/<?=e($staff['profile_photo'])?>">
                    <?php else: ?>
                        <span class="avatar-init"><?=strtoupper(substr($displayName,0,1))?></span>
                    <?php endif; ?>
                    <div class="avatar-edit-overlay"><i class="fas fa-camera"></i></div>
                </div>
                <input type="file" id="photoInput" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="uploadPhoto(this)">
            </div>
            
            <div class="hero-info">
                <div class="top-row">
                    <h2 class="display-name"><?=e($displayName)?></h2>
                    <div class="completeness-mini">
                        <div class="comp-label">Profile Integrity</div>
                        <div class="comp-val-wrap">
                            <span class="comp-val"><?=$compl_pct?>%</span>
                            <div class="comp-bar"><div class="comp-fill" style="width:<?=$compl_pct?>%;"></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="badge-row">
                    <span class="p-badge accent"><i class="fas <?=e($roleIcon)?>"></i> <?=e($displayRole)?></span>
                    <span class="p-badge"><i class="fas fa-id-card"></i> <?=e($staff['employee_id']??'REF-PENDING')?></span>
                    <span class="p-badge"><i class="fas fa-building"></i> <?=e($staff['dept_name']??'General Services')?></span>
                    <span class="p-badge status <?= ($staff['status']??'Active')==='Active'?'active':'' ?>">
                        <i class="fas fa-signal"></i> <?=e($staff['status']??'Active')?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="hero-stats">
            <div class="h-stat"><span>Designation</span><strong><?=e($staff['designation']??'Facility Staff')?></strong></div>
            <div class="h-stat"><span>Tenure</span><strong><?=$staff['date_joined']?date('d M Y',strtotime($staff['date_joined'])):'—'?></strong></div>
            <div class="h-stat"><span>Shift Model</span><strong><?=e(ucfirst($staff['shift_type']??'Morning'))?></strong></div>
            <div class="h-stat"><span>Last Active</span><strong><?php $last = $staff['last_login'] ?? 0; echo $last ? (is_numeric($last) ? date('d M, H:i', $last) : date('d M, H:i', strtotime($last))) : 'Just now'; ?></strong></div>

        </div>
    </div>

    <!-- Navigation Segments -->
    <div class="profile-nav">
        <button class="p-nav-btn active" onclick="switchProfileTab('p-personal', this)"><i class="fas fa-user-circle"></i> Personal Data</button>
        <button class="p-nav-btn" onclick="switchProfileTab('p-docs', this)"><i class="fas fa-certificate"></i> Credentials & Docs</button>
        <button class="p-nav-btn" onclick="switchProfileTab('p-security', this)"><i class="fas fa-shield-check"></i> Security & Access</button>
    </div>

    <!-- Personal Tab -->
    <div id="p-personal" class="profile-tab-content">
        <div class="grid-layout">
            <div class="card p-4">
                <div class="card-header pb-4 border-b">
                    <h3 class="text-xl font-bold"><i class="fas fa-fingerprint text-blue-500 mr-2"></i> Bio Information</h3>
                    <button class="btn btn-primary btn-sm" onclick="savePersonal()"><i class="fas fa-sync-alt mr-2"></i> Synchronize Data</button>
                </div>
                <form id="frmPersonal" class="pt-6">
                    <input type="hidden" name="action" value="update_personal_info">
                    <div class="form-grid-2">
                        <div class="form-group"><label>Nominal Name</label><input name="full_name" class="form-control" value="<?=e($staff['full_name']??'')?>"></div>
                        <div class="form-group"><label>Birth Date</label><input name="date_of_birth" type="date" class="form-control" value="<?=e($staff['date_of_birth']??'')?>"></div>
                        <div class="form-group"><label>Gender Orientation</label>
                            <select name="gender" class="form-control">
                                <option value="Male" <?=$staff['gender']==='Male'?'selected':''?>>Male</option>
                                <option value="Female" <?=$staff['gender']==='Female'?'selected':''?>>Female</option>
                                <option value="Other" <?=$staff['gender']==='Other'?'selected':''?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Nationality / Origin</label><input name="nationality" class="form-control" value="<?=e($staff['nationality']??'Ghana')?>"></div>
                    </div>
                </form>
            </div>
            
            <div class="card p-4">
                <div class="card-header pb-4 border-b"><h3 class="text-xl font-bold"><i class="fas fa-address-book text-green-500 mr-2"></i> Contact Matrix</h3></div>
                <div class="pt-6">
                    <div class="form-grid-2">
                        <div class="form-group"><label>Primary Comms</label><input name="phone" class="form-control" value="<?=e($staff['phone']??'')?>"></div>
                        <div class="form-group"><label>Secondary Comms</label><input name="secondary_phone" class="form-control" value="<?=e($staff['secondary_phone']??'')?>"></div>
                    </div>
                    <div class="form-group mt-4"><label>Digital Correspondence</label><input name="email" class="form-control" value="<?=e($staff['email']??'')?>"></div>
                    <div class="form-group mt-4"><label>Residential Coordinates</label><textarea name="address" class="form-control" rows="2"><?=e($staff['address']??'')?></textarea></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Credentials Tab -->
    <div id="p-docs" class="profile-tab-content hide">
        <div class="card mb-8">
            <div class="card-header"><h3 class="text-xl font-bold"><i class="fas fa-graduation-cap"></i> Academic Qualifications</h3>
            <button class="btn btn-primary btn-sm" onclick="openModal('addQualModal')"><i class="fas fa-plus mr-1"></i> Add Entry</button></div>
            <div class="table-wrap">
                <table class="p-table">
                    <thead><tr><th>Certification</th><th>Institution</th><th>Year</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($quals as $q): ?>
                        <tr>
                            <td><strong><?=e($q['certificate_name'])?></strong></td>
                            <td><?=e($q['institution'])?></td>
                            <td><?=$q['year_awarded']?></td>
                            <td><button class="btn-del" onclick="deleteQual(<?=$q['id']?>)"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Security Tab -->
    <div id="p-security" class="profile-tab-content hide">
        <div class="grid-layout">
            <div class="card p-4">
                <div class="card-header mb-6"><h3><i class="fas fa-key"></i> Cryptographic Update</h3></div>
                <form id="frmPassword">
                    <div class="form-group mb-4"><label>Existing Password</label><input name="current_password" type="password" class="form-control"></div>
                    <div class="form-group mb-4">
                        <label>New Secret Key</label><input name="new_password" type="password" class="form-control" oninput="testEntropy(this.value)">
                        <div class="entropy-meter"><div id="entropyBar"></div></div>
                    </div>
                    <button class="btn btn-primary btn-wide" onclick="savePassword()"><i class="fas fa-shield-alt mr-2"></i> Update Security</button>
                </form>
            </div>
            
            <div class="card p-4">
                <div class="card-header mb-6"><h3><i class="fas fa-history"></i> Access Audit</h3></div>
                <div class="session-list">
                    <?php foreach($sessions as $s): ?>
                    <div class="session-item">
                        <i class="fas fa-laptop-code text-2xl opacity-50"></i>
                        <div class="flex-1">
                            <strong><?=e(substr($s['device_info'],0,30))?></strong>
                            <p><?=$s['ip_address']?> • <?=date('H:i, d M', strtotime($s['login_time']))?></p>
                        </div>
                        <?php if($s['is_current']): ?><span class="p-badge active">This Client</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-hero { margin-bottom:2.5rem; position:relative; overflow:hidden; border-radius: 24px; }
.hero-banner { height:160px; background:linear-gradient(135deg, var(--role-accent), #1a1a1a); }
.hero-content { display:flex; padding:0 3rem 2.5rem; margin-top:-60px; gap:2.5rem; align-items:flex-end; }
.avatar-stack { position:relative; z-index:10; }
.p-avatar { width:140px; height:140px; border-radius:35px; border:6px solid var(--surface); background:var(--surface-2); box-shadow:0 10px 25px rgba(0,0,0,0.15); overflow:hidden; cursor:pointer; position:relative; display:flex; align-items:center; justify-content:center; }
.p-avatar img { width:100%; height:100%; object-fit:cover; }
.avatar-init { font-size:4rem; font-weight:900; color:var(--role-accent); }
.avatar-edit-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.5); color:#fff; display:flex; align-items:center; justify-content:center; opacity:0; transition:.3s; font-size:2rem; }
.p-avatar:hover .avatar-edit-overlay { opacity:1; }

.hero-info { flex:1; margin-bottom:0.5rem; }
.top-row { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:1.5rem; }
.display-name { font-size:2.8rem; font-weight:900; margin:0; line-height:1; color: var(--text-primary); }
.completeness-mini { width:240px; }
.comp-label { font-size:1.1rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); margin-bottom:.5rem; }
.comp-val-wrap { display:flex; align-items:center; gap:1.5rem; }
.comp-val { font-size:1.8rem; font-weight:900; color:var(--role-accent); }
.comp-bar { flex:1; height:8px; background:rgba(0,0,0,0.05); border-radius:4px; overflow:hidden; }
.comp-fill { height:100%; background:var(--role-accent); border-radius:4px; }

.badge-row { display:flex; gap:1rem; flex-wrap:wrap; }
.p-badge { background:var(--surface-2); padding:.6rem 1.4rem; border-radius:12px; font-size:1.2rem; font-weight:700; color:var(--text-secondary); display:flex; align-items:center; gap:.7rem; border: 1px solid var(--border); }
.p-badge.accent { background:color-mix(in srgb, var(--role-accent) 15%, transparent); color:var(--role-accent); border-color: transparent; }
.p-badge.status.active { color:#27AE60; border-color: #27AE6033; }
.p-badge i { opacity:.6; }

.hero-stats { display:grid; grid-template-columns:repeat(4,1fr); padding:2rem 3rem; border-top:1px solid var(--border); background:var(--surface-2); gap: 2rem; }
.h-stat span { display:block; font-size:1.1rem; font-weight:800; text-transform:uppercase; color:var(--text-muted); margin-bottom:.4rem; }
.h-stat strong { font-size:1.5rem; font-weight:800; color:var(--text-primary); }

.profile-nav { display:flex; gap:1.5rem; margin-bottom:2.5rem; background:var(--surface-2); padding:.6rem; border-radius:16px; width:fit-content; }
.p-nav-btn { border:none; background:none; padding:.8rem 2rem; border-radius:12px; font-size:1.3rem; font-weight:700; color:var(--text-muted); cursor:pointer; transition:.2s; display:flex; align-items:center; gap:.8rem; }
.p-nav-btn.active { background:var(--surface); color:var(--role-accent); box-shadow:var(--shadow-sm); }

.grid-layout { display:grid; grid-template-columns:1.2fr 1fr; gap:2.5rem; }
.form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:2rem; }
.p-table { width:100%; border-collapse:collapse; }
.p-table th { text-align:left; padding:1.5rem; background:var(--surface-2); color:var(--text-muted); font-weight:800; text-transform:uppercase; font-size:1.1rem; }
.p-table td { padding:1.5rem; border-bottom:1px solid var(--border); font-size:1.35rem; }
.btn-del { width:36px; height:36px; border-radius:10px; border:none; background:#EB575722; color:#EB5757; cursor:pointer; transition:.2s; }
.btn-del:hover { background:#EB5757; color:#fff; }

.entropy-meter { height:6px; background:rgba(0,0,0,0.05); margin-top:.8rem; border-radius:3px; overflow:hidden; }
#entropyBar { height:100%; width:0; transition:width .3s; }
.session-item { display:flex; align-items:center; gap:1.5rem; padding:1.5rem 0; border-bottom:1px solid var(--border); }
.session-item:last-child { border-bottom:none; }
.session-item p { font-size:1.15rem; color:var(--text-muted); margin-top:.3rem; }
.hide { display:none; }
</style>

<script>
function switchProfileTab(tabId, btn) {
    document.querySelectorAll('.profile-tab-content').forEach(c => c.classList.add('hide'));
    document.getElementById(tabId).classList.remove('hide');
    document.querySelectorAll('.p-nav-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
async function savePersonal() {
    const fd = new FormData(document.getElementById('frmPersonal'));
    const res = await doAction(fd, 'Biological profile metrics synchronized.');
}
function testEntropy(pw) {
    let score = 0;
    if(pw.length>=8) score++;
    if(/[A-Z]/.test(pw)) score++;
    if(/[0-9]/.test(pw)) score++;
    if(/[^A-Za-z0-9]/.test(pw)) score++;
    const colors = ['#EB5757', '#F2994A', '#F2C94C', '#27AE60'];
    const bar = document.getElementById('entropyBar');
    bar.style.width = (score*25)+'%';
    bar.style.background = colors[score-1] || '#EB5757';
}
</script>
