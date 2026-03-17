<?php
/**
 * tab_profile.php — Module 14: Advanced Staff Profile
 * Sections A-G: Identity, Personal Info, Employment, Qualifications, 
 *               Documents, Account/Security, Notifications, Completeness
 */

// Fetch qualifications
$quals = dbSelect($conn,"SELECT * FROM staff_qualifications WHERE staff_id=? ORDER BY qualification_id DESC","i",[$staff_id]);
// Fetch documents
$docs  = dbSelect($conn,"SELECT * FROM staff_documents WHERE staff_id=? ORDER BY document_id DESC","i",[$staff_id]);
// Fetch active sessions
$sessions = dbSelect($conn,"SELECT * FROM staff_sessions WHERE staff_id=? ORDER BY session_id DESC LIMIT 5","i",[$staff_id]);
// Settings
$settings = dbRow($conn,"SELECT * FROM staff_settings WHERE staff_id=? LIMIT 1","i",[$staff_id]);
// Completeness
$compl_pct = $completeness ?? 0;

// Calculate age
$dob = $staff['date_of_birth'] ?? '';
$age = $dob ? floor((time() - strtotime($dob)) / (365.25 * 24 * 3600)) : '—';
?>
<div id="sec-profile" class="dash-section">

    <!-- Section A: Profile Identity Card -->
    <div class="card" style="margin-bottom:2rem;overflow:visible;">
        <div style="height:130px;background:linear-gradient(135deg,var(--role-accent),color-mix(in srgb,var(--role-accent) 60%,#000 40%));border-radius:var(--radius-md) var(--radius-md) 0 0;position:relative;">
            <div style="position:absolute;bottom:-50px;left:2.5rem;display:flex;align-items:flex-end;gap:2rem;">
                <div id="profilePhotoWrap" style="width:100px;height:100px;border-radius:50%;border:4px solid var(--surface);overflow:hidden;background:var(--role-accent);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:3.5rem;font-weight:700;color:#fff;position:relative;"
                    onclick="document.getElementById('photoInput').click()" title="Click to change photo">
                    <?php if(!empty($staff['profile_photo'])): ?>
                        <img id="profilePhotoImg" src="/RMU-Medical-Management-System/<?=e($staff['profile_photo'])?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                        <span id="profilePhotoInitial"><?=strtoupper(substr($displayName,0,1))?></span>
                    <?php endif; ?>
                    <div style="position:absolute;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;border-radius:50%;" class="photo-overlay">
                        <i class="fas fa-camera" style="font-size:2rem;color:#fff;"></i>
                    </div>
                </div>
            </div>
        </div>
        <input type="file" id="photoInput" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="uploadPhoto(this)">
        <div style="padding:1.5rem 2.5rem 2rem;margin-top:4rem;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h2 style="font-size:2.4rem;font-weight:800;margin:0;"><?=e($displayName)?></h2>
                    <div style="display:flex;flex-wrap:wrap;gap:.8rem;margin-top:.8rem;">
                        <span class="badge" style="background:var(--role-accent-light);color:var(--role-accent);font-size:1.2rem;"><i class="fas <?=e($roleIcon)?>"></i> <?=e($displayRole)?></span>
                        <span class="badge badge-done" style="font-size:1.2rem;"><i class="fas fa-id-badge"></i> <?=e($staff['employee_id']??'Pending')?></span>
                        <span class="badge" style="background:var(--surface-2);color:var(--text-secondary);font-size:1.2rem;"><i class="fas fa-building"></i> <?=e($staff['dept_name']??'—')?></span>
                        <?php $stColor = ($staff['status']??'Active') === 'Active' ? 'success' : 'danger'; ?>
                        <span class="badge" style="background:var(--<?=$stColor?>-light);color:var(--<?=$stColor?>);font-size:1.2rem;">
                            <i class="fas fa-circle" style="font-size:.7rem;"></i> <?=e($staff['status']??'Active')?></span>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.2rem;color:var(--text-muted);margin-bottom:.5rem;">Profile Completion</div>
                    <div style="font-size:3.5rem;font-weight:800;color:var(--role-accent);line-height:1;"><?=$compl_pct?>%</div>
                    <div class="completeness-bar" style="width:200px;margin-top:.5rem;"><div class="completeness-fill" style="width:<?=$compl_pct?>%;"></div></div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.5rem;margin-top:2rem;padding-top:2rem;border-top:1px solid var(--border);">
                <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><i class="fas fa-briefcase"></i> DESIGNATION</span><strong><?=e($staff['designation']??'—')?></strong></div>
                <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><i class="fas fa-calendar-check"></i> JOINED</span><strong><?=$staff['date_joined']?date('d M Y',strtotime($staff['date_joined'])):'—'?></strong></div>
                <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><i class="fas fa-clock"></i> SHIFT</span><strong><?=e(ucfirst($staff['shift_type']??'—'))?></strong></div>
                <div><span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;"><i class="fas fa-user-clock"></i> LAST LOGIN</span><strong><?=$staff['last_login']??'—'?></strong></div>
            </div>
        </div>
    </div>

    <!-- Sub-tabs for Profile Sections -->
    <div class="filter-tabs" style="margin-bottom:2rem;">
        <?php foreach(['personal'=>'Personal Info','documents'=>'Qualifications & Docs','security'=>'Security'] as $k=>$l): ?>
        <button class="ftab <?=$k==='personal'?'active':''?>" onclick="showProfileSec('psec-<?=$k?>',this)"><?=$l?></button>
        <?php endforeach; ?>
    </div>

    <!-- Section B: Personal Info -->
    <div id="psec-personal" class="psec">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('btnSavePersonal').click()"><i class="fas fa-save"></i> Save Changes</button>
            </div>
            <div class="card-body">
                <form id="frmPersonal" onsubmit="event.preventDefault();savePersonal();">
                    <input type="hidden" name="action" value="update_personal_info">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name *</label><input name="full_name" type="text" class="form-control" value="<?=e($staff['full_name']??'')?>" required></div>
                        <div class="form-group"><label>Date of Birth</label><input name="date_of_birth" type="date" class="form-control" value="<?=e($staff['date_of_birth']??'')?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                <option value="Male" <?=$staff['gender']==='Male'?'selected':''?>>Male</option>
                                <option value="Female" <?=$staff['gender']==='Female'?'selected':''?>>Female</option>
                                <option value="Other" <?=$staff['gender']==='Other'?'selected':''?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Nationality</label><input name="nationality" type="text" class="form-control" value="<?=e($staff['nationality']??'Ghana')?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Marital Status</label>
                            <select name="marital_status" class="form-control">
                                <option value="">Select</option>
                                <?php foreach(['Single','Married','Divorced','Widowed','Separated'] as $m): ?>
                                <option value="<?=$m?>" <?=($staff['marital_status']??'')===$m?'selected':''?>><?=$m?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>National ID</label><input name="national_id" type="text" class="form-control" value="<?=e($staff['national_id']??'')?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Primary Phone</label><input name="phone" type="tel" class="form-control" value="<?=e($staff['phone']??'')?>"></div>
                        <div class="form-group"><label>Secondary Phone</label><input name="secondary_phone" type="tel" class="form-control" value="<?=e($staff['secondary_phone']??'')?>"></div>
                    </div>
                    <div class="form-group"><label>Email Address *</label><input name="email" type="email" class="form-control" value="<?=e($staff['email']??'')?>" required></div>
                    <div class="form-group"><label>Residential Address</label><textarea name="address" class="form-control" rows="2"><?=e($staff['address']??'')?></textarea></div>
                    <button type="submit" id="btnSavePersonal" style="display:none;"></button>
                </form>

                <!-- Emergency Contact (read-only for staff) -->
                <div style="margin-top:2rem;padding-top:2rem;border-top:1px solid var(--border);">
                    <h4 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem;color:var(--text-secondary);">
                        <i class="fas fa-phone-alt" style="color:var(--danger);"></i> Emergency Contact
                    </h4>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
                        <div>
                            <span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;">Contact Name</span>
                            <strong><?=e($staff['emergency_contact_name']??'—')?></strong>
                        </div>
                        <div>
                            <span style="font-size:1.1rem;color:var(--text-muted);display:block;margin-bottom:.3rem;">Contact Phone</span>
                            <strong><?=e($staff['emergency_contact_phone']??'—')?></strong>
                        </div>
                    </div>
                    <p style="font-size:1.2rem;color:var(--text-muted);margin-top:1rem;"><i class="fas fa-info-circle"></i> Emergency contact is set by admin. Contact your administrator to update.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section C+D: Qualifications & Documents -->
    <div id="psec-documents" class="psec" style="display:none;">
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-header">
                <h3><i class="fas fa-graduation-cap"></i> Qualifications (<?=count($quals)?>)</h3>
                <button class="btn btn-primary btn-sm" onclick="openModal('addQualModal')"><i class="fas fa-plus"></i> Add</button>
            </div>
            <?php if(empty($quals)): ?>
            <div class="card-body"><p style="text-align:center;color:var(--text-muted);">No qualifications added yet.</p></div>
            <?php else: ?>
            <div class="card-body-flush"><table class="stf-table">
                <thead><tr><th>Certificate</th><th>Institution</th><th>Year</th><th>Document</th><th></th></tr></thead>
                <tbody>
                <?php foreach($quals as $q): ?>
                <tr>
                    <td><strong><?=e($q['certificate_name'])?></strong></td>
                    <td><?=e($q['institution']??'—')?></td>
                    <td><?=$q['year_awarded']??'—'?></td>
                    <td><?=$q['file_path']?'<a href="/RMU-Medical-Management-System/'.e($q['file_path']).'" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a>':'<span style="color:var(--text-muted);">No file</span>'?></td>
                    <td><button class="btn btn-sm" style="background:var(--danger-light);color:var(--danger);" onclick="deleteQual(<?=$q['id']?>)"><i class="fas fa-trash"></i></button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-folder-open"></i> Documents (<?=count($docs)?>)</h3>
                <button class="btn btn-primary btn-sm" onclick="openModal('addDocModal')"><i class="fas fa-upload"></i> Upload</button>
            </div>
            <?php if(empty($docs)): ?>
            <div class="card-body"><p style="text-align:center;color:var(--text-muted);">No documents uploaded yet.</p></div>
            <?php else: ?>
            <div class="card-body-flush"><table class="stf-table">
                <thead><tr><th>Document Name</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($docs as $d): ?>
                <tr>
                    <td><i class="fas fa-file-alt" style="color:var(--role-accent);margin-right:.5rem;"></i><strong><?=e($d['document_name'])?></strong></td>
                    <td><?=e(ucfirst($d['document_type']??'—'))?></td>
                    <td><?=date('d M Y',strtotime($d['uploaded_at']))?></td>
                    <td style="display:flex;gap:.5rem;">
                        <a href="/RMU-Medical-Management-System/<?=e($d['file_path'])?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i></a>
                        <button class="btn btn-sm" style="background:var(--danger-light);color:var(--danger);" onclick="deleteDoc(<?=$d['id']?>)"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Section E: Account & Security -->
    <div id="psec-security" class="psec" style="display:none;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:2rem;">
            <!-- Change Password -->
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-lock"></i> Change Password</h3></div>
                <div class="card-body">
                    <form id="frmPassword" onsubmit="event.preventDefault();savePassword();">
                        <input type="hidden" name="action" value="update_password">
                        <div class="form-group"><label>Current Password *</label><input name="current_password" type="password" class="form-control" required></div>
                        <div class="form-group"><label>New Password *</label><input name="new_password" type="password" id="newPassInp" class="form-control" required oninput="checkPassStrength(this.value)">
                            <div id="passStrength" style="height:4px;border-radius:2px;background:var(--border);margin-top:.5rem;overflow:hidden;"><div id="passStrengthFill" style="height:100%;width:0;transition:.3s;"></div></div>
                            <span id="passStrengthLabel" style="font-size:1.1rem;color:var(--text-muted);"></span>
                        </div>
                        <div class="form-group"><label>Confirm New Password *</label><input name="confirm_password" type="password" class="form-control" required></div>
                        <button type="submit" class="btn btn-primary btn-wide" id="btnPassword"><i class="fas fa-lock"></i> Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Active Sessions -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-desktop"></i> Active Sessions (<?=count($sessions)?>)</h3>
                    <button class="btn btn-outline btn-sm btn-danger" onclick="logoutAllSessions()"><i class="fas fa-sign-out-alt"></i> Logout All Others</button>
                </div>
                <div class="card-body">
                    <?php if(empty($sessions)): ?>
                    <p style="text-align:center;color:var(--text-muted);">No active sessions found.</p>
                    <?php else: foreach($sessions as $s):
                        $is_cur = (bool)($s['is_current']??false);
                    ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 0;border-bottom:1px solid var(--border);">
                        <div>
                            <p style="font-weight:600;font-size:1.3rem;margin:0;"><?=e(substr($s['device_info']??'Unknown Device',0,40))?></p>
                            <p style="font-size:1.1rem;color:var(--text-muted);margin:.2rem 0 0;"><?=e($s['ip_address']??'—')?> | <?php $sess_time=$s['login_time']??$s['last_active']??$s['created_at']??null; echo $sess_time?date('d M, H:i',strtotime($sess_time)):'—'; ?></p>
                        </div>
                        <?php if($is_cur): ?>
                        <span class="badge badge-done">Current</span>
                        <?php else: ?>
                        <button class="btn btn-sm" style="background:var(--danger-light);color:var(--danger);" onclick="logoutSession(<?=$s['session_id']?>)"><i class="fas fa-times"></i></button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Qualification Modal -->
<div class="modal-bg" id="addQualModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-graduation-cap" style="color:var(--role-accent);"></i> Add Qualification</h3>
            <button class="modal-close" onclick="closeModal('addQualModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmQual" onsubmit="event.preventDefault();saveQual();">
            <input type="hidden" name="action" value="save_qualification">
            <div class="form-group"><label>Certificate Name *</label><input name="certificate_name" type="text" class="form-control" required placeholder="e.g. BTECH in Public Health"></div>
            <div class="form-row">
                <div class="form-group"><label>Institution *</label><input name="institution" type="text" class="form-control" required placeholder="University / College name"></div>
                <div class="form-group"><label>Year Awarded *</label><input name="year_awarded" type="number" class="form-control" required min="1960" max="<?=date('Y')?>"></div>
            </div>
            <div class="form-group"><label>Upload Certificate / Document</label><input name="document" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnQual"><i class="fas fa-plus"></i> Add Qualification</button>
        </form>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal-bg" id="addDocModal">
    <div class="modal-box" style="max-width:420px;">
        <div class="modal-header">
            <h3><i class="fas fa-upload" style="color:var(--role-accent);"></i> Upload Document</h3>
            <button class="modal-close" onclick="closeModal('addDocModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmDoc" onsubmit="event.preventDefault();saveDoc();">
            <input type="hidden" name="action" value="upload_document">
            <div class="form-group"><label>Document Name *</label><input name="doc_name" type="text" class="form-control" required placeholder="e.g. Employment Contract"></div>
            <div class="form-group"><label>Document Type</label>
                <select name="doc_type" class="form-control">
                    <option value="contract">Employment Contract</option><option value="id_scan">National ID Scan</option>
                    <option value="certificate">Certificate</option><option value="reference">Reference Letter</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>File *</label><input name="document" type="file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png"></div>
            <button type="submit" class="btn btn-primary btn-wide" id="btnDoc"><i class="fas fa-upload"></i> Upload Document</button>
        </form>
    </div>
</div>

<style>
.psec { display:none; }
.psec:first-child { display:block; }
#profilePhotoWrap:hover .photo-overlay { opacity:1 !important; }
</style>
<script>
function showProfileSec(id, btn) {
    document.querySelectorAll('.psec').forEach(s => s.style.display='none');
    document.getElementById(id).style.display='block';
    document.querySelectorAll('.filter-tabs .ftab').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');
}

async function uploadPhoto(input) {
    if(!input.files[0]) return;
    const fd=new FormData(); fd.append('action','upload_photo'); fd.append('photo',input.files[0]);
    showToast('Uploading photo...','info');
    const res=await doAction(fd,'Profile photo updated!');
    if(res && res.photo_url){
        const img=document.getElementById('profilePhotoImg');
        const init=document.getElementById('profilePhotoInitial');
        if(img){ img.src=res.photo_url+'?t='+Date.now(); }
        else {
            const wrap=document.getElementById('profilePhotoWrap');
            if(init) init.remove();
            const newImg=document.createElement('img'); newImg.id='profilePhotoImg';
            newImg.src=res.photo_url+'?t='+Date.now(); newImg.style='width:100%;height:100%;object-fit:cover;';
            wrap.prepend(newImg);
        }
    }
}
async function savePersonal(){
    const btn=document.getElementById('btnSavePersonal').closest('form').querySelector('.btn-primary, button[type="submit"]');
    const fd=new FormData(document.getElementById('frmPersonal'));
    const res=await doAction(fd,'Personal information saved!');
    if(res) { /* Already toasted */ }
}
async function savePassword(){
    const btn=document.getElementById('btnPassword'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmPassword'));
    const res=await doAction(fd,'Password updated successfully!');
    btn.innerHTML='<i class="fas fa-lock"></i> Update Password'; btn.disabled=false;
    if(res) document.getElementById('frmPassword').reset();
}
async function saveQual(){
    const btn=document.getElementById('btnQual'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmQual'));
    const res=await doAction(fd,'Qualification added!');
    btn.innerHTML='<i class="fas fa-plus"></i> Add Qualification'; btn.disabled=false;
    if(res){ closeModal('addQualModal'); setTimeout(()=>location.reload(),700); }
}
async function saveDoc(){
    const btn=document.getElementById('btnDoc'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'; btn.disabled=true;
    const fd=new FormData(document.getElementById('frmDoc'));
    const res=await doAction(fd,'Document uploaded!');
    btn.innerHTML='<i class="fas fa-upload"></i> Upload Document'; btn.disabled=false;
    if(res){ closeModal('addDocModal'); setTimeout(()=>location.reload(),700); }
}
async function deleteQual(id){
    if(!confirmAction('Delete this qualification?')) return;
    const res=await doAction({action:'delete_qualification', qual_id:id},'Qualification deleted.');
    if(res) setTimeout(()=>location.reload(),700);
}
async function deleteDoc(id){
    if(!confirmAction('Delete this document?')) return;
    const res=await doAction({action:'delete_document', doc_id:id},'Document deleted.');
    if(res) setTimeout(()=>location.reload(),700);
}
async function logoutSession(id){
    if(!confirmAction('Terminate this session?')) return;
    const res=await doAction({action:'logout_session', session_id:id},'Session terminated.');
    if(res) setTimeout(()=>location.reload(),700);
}
async function logoutAllSessions(){
    if(!confirmAction('Terminate all other sessions?')) return;
    const res=await doAction({action:'logout_all_sessions'},'All other sessions terminated.');
    if(res) setTimeout(()=>location.reload(),700);
}
function checkPassStrength(pw) {
    let score=0;
    if(pw.length>=8) score++;
    if(/[A-Z]/.test(pw)) score++;
    if(/[0-9]/.test(pw)) score++;
    if(/[^A-Za-z0-9]/.test(pw)) score++;
    const colors=['#E74C3C','#E67E22','#F39C12','#27AE60'];
    const labels=['Weak','Fair','Good','Strong'];
    const fill=document.getElementById('passStrengthFill');
    const lbl=document.getElementById('passStrengthLabel');
    fill.style.width=(score*25)+'%'; fill.style.background=colors[score-1]||'#E74C3C';
    lbl.textContent=pw.length?labels[score-1]||'Very Weak':''; lbl.style.color=colors[score-1]||'#E74C3C';
}
// Save personal on form button click
document.getElementById('frmPersonal').querySelector('button[type="submit"]') && null;
// Trigger form save on visible save button
</script>
