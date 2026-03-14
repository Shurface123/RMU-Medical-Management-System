<?php
/**
 * tab_profile.php
 * Module 1: Personal Information, Qualifications & Completeness.
 */
?>
<div id="sec-profile" class="dash-section <?=($active_tab==='profile')?'active':''?>">
    <div class="adm-card" style="margin-bottom:2rem;">
        <div class="adm-card-header">
            <h3><i class="fas fa-id-card" style="color:var(--role-accent);"></i> Profile Overview</h3>
        </div>
        <div class="adm-card-body" style="display:flex;gap:3rem;align-items:center;">
            <div style="width:120px;height:120px;border-radius:50%;background:var(--role-accent-light);border:4px solid var(--role-accent);overflow:hidden;position:relative;">
                <?php if(!empty($staff_row['profile_photo'])): ?>
                    <img id="profImgWrap" src="/RMU-Medical-Management-System/<?=e($staff_row['profile_photo'])?>" style="width:100%;height:100%;object-fit:cover;">
                <?php else: ?>
                    <i id="profImgWrap" class="fas fa-user" style="font-size:5rem;color:var(--role-accent);position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"></i>
                <?php endif; ?>
            </div>
            <div style="flex:1;">
                <h2 style="font-size:2.2rem;margin-bottom:.5rem;"><?=$displayName?></h2>
                <p style="font-size:1.4rem;color:var(--text-secondary);margin-bottom:1rem;">
                    <i class="fas fa-briefcase"></i> <?=e($displayRole)?> &nbsp;|&nbsp; 
                    <i class="fas fa-id-badge"></i> ID: <?=e($staff_row['employee_id']??'Pending')?>
                </p>
                <div style="display:inline-block;padding:.6rem 1.2rem;background:var(--success-light);color:var(--success);border-radius:20px;font-weight:600;font-size:1.2rem;">
                    <i class="fas fa-check-circle"></i> Status: <?=ucfirst($staff_row['status']??'Active')?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        
        <!-- Personal Information Form -->
        <div class="adm-card">
            <div class="adm-card-header">
                <h3><i class="fas fa-user-edit" style="color:var(--role-accent);"></i> Personal Details</h3>
            </div>
            <div class="adm-card-body">
                <form id="frmPersonal" onsubmit="event.preventDefault(); savePersonalInfo();">
                    <input type="hidden" name="action" value="update_personal_info">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?=e($displayName)?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="<?=e($staff_row['date_of_birth']??'')?>">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="">Select</option>
                                <option value="Male" <?=($staff_row['gender']??'')==='Male'?'selected':''?>>Male</option>
                                <option value="Female" <?=($staff_row['gender']??'')==='Female'?'selected':''?>>Female</option>
                                <option value="Other" <?=($staff_row['gender']??'')==='Other'?'selected':''?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?=e($staff_row['phone']??'')?>">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?=e($staff_row['email']??'')?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nationality</label>
                        <input type="text" name="nationality" class="form-control" value="<?=e($staff_row['nationality']??'')?>">
                    </div>

                    <div class="form-group">
                        <label>Residential Address</label>
                        <textarea name="address" class="form-control" rows="2"><?=e($staff_row['address']??'')?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Emergency Contact</label>
                            <input type="text" name="emergency_contact_name" class="form-control" value="<?=e($staff_row['emergency_contact_name']??'')?>">
                        </div>
                        <div class="form-group">
                            <label>Emergency Phone</label>
                            <input type="tel" name="emergency_contact_phone" class="form-control" value="<?=e($staff_row['emergency_contact_phone']??'')?>">
                        </div>
                    </div>

                    <div style="margin-top:1.5rem;text-align:right;">
                        <button type="submit" class="adm-btn adm-btn-primary" id="btnSavePersonal">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Qualifications / Documents -->
        <div>
            <div class="adm-card" style="margin-bottom:2rem;">
                <div class="adm-card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h3><i class="fas fa-certificate" style="color:var(--role-accent);"></i> Qualifications</h3>
                    <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="openModal('addQualModal')"><i class="fas fa-plus"></i> Add</button>
                </div>
                <div class="adm-card-body">
                    <?php
                    $quals = dbSelect($conn, "SELECT * FROM staff_qualifications WHERE staff_id=? ORDER BY year_awarded DESC", "i", [$staff_id]);
                    if(empty($quals)): ?>
                        <p style="color:var(--text-muted);text-align:center;padding:2rem;">No qualifications uploaded yet.</p>
                    <?php else: ?>
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                        <?php foreach($quals as $q): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:1.2rem;background:var(--surface-2);border-radius:8px;border:1px solid var(--border);">
                                <div>
                                    <strong style="font-size:1.3rem;display:block;"><?=e($q['certificate_name'])?></strong>
                                    <span style="color:var(--text-secondary);font-size:1.2rem;"><?=e($q['institution'])?> (<?=e($q['year_awarded'])?>)</span>
                                </div>
                                <?php if($q['file_path']): ?>
                                    <a href="/RMU-Medical-Management-System/<?=e($q['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm adm-btn-outline" title="View Document"><i class="fas fa-eye"></i></a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Employment Information (Read-Only) -->
            <div class="adm-card">
                <div class="adm-card-header">
                    <h3><i class="fas fa-building" style="color:var(--role-accent);"></i> Employment Details</h3>
                </div>
                <div class="adm-card-body">
                    <table style="width:100%;font-size:1.3rem;">
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:.8rem 0;color:var(--text-secondary);">Department</td>
                            <td style="padding:.8rem 0;font-weight:600;text-align:right;">
                                <?php 
                                $dept = dbVal($conn, "SELECT name FROM staff_departments WHERE department_id=?", "i", [$staff_row['department_id']]); 
                                echo e($dept ?: 'N/A');
                                ?>
                            </td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:.8rem 0;color:var(--text-secondary);">Designation</td>
                            <td style="padding:.8rem 0;font-weight:600;text-align:right;"><?=e($staff_row['designation']??'N/A')?></td>
                        </tr>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:.8rem 0;color:var(--text-secondary);">Employment Type</td>
                            <td style="padding:.8rem 0;font-weight:600;text-align:right;"><?=ucfirst(e($staff_row['employment_type']??'N/A'))?></td>
                        </tr>
                        <tr>
                            <td style="padding:.8rem 0;color:var(--text-secondary);">Date Joined</td>
                            <td style="padding:.8rem 0;font-weight:600;text-align:right;"><?=($staff_row['date_joined']) ? date('M d, Y', strtotime($staff_row['date_joined'])) : 'N/A'?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add Qualification -->
<div class="modal-bg" id="addQualModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="fas fa-certificate"></i> Add Qualification</h3>
            <button class="modal-close" onclick="closeModal('addQualModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="frmQual" onsubmit="event.preventDefault(); saveQual();">
            <input type="hidden" name="action" value="save_qualification">
            <div class="form-group">
                <label>Certificate Name *</label>
                <input type="text" name="certificate_name" class="form-control" required placeholder="e.g. BSc Public Health">
            </div>
            <div class="form-group">
                <label>Institution Awarded *</label>
                <input type="text" name="institution" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Year Awarded *</label>
                <input type="number" name="year_awarded" class="form-control" required min="1950" max="<?=date('Y')?>">
            </div>
            <div class="form-group">
                <label>Upload Document (PDF/JPG/PNG)</label>
                <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <button type="submit" class="adm-btn adm-btn-primary" style="width:100%;padding:1rem;font-size:1.4rem;">Save Qualification</button>
        </form>
    </div>
</div>

<script>
async function savePersonalInfo() {
    const btn = document.getElementById('btnSavePersonal');
    const form = document.getElementById('frmPersonal');
    const fd = new FormData(form);
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    btn.disabled = true;

    const res = await staffAction(fd);
    showToast(res.message, res.success ? 'success' : 'error');
    
    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    btn.disabled = false;
}

async function saveQual() {
    const form = document.getElementById('frmQual');
    const fd = new FormData(form);
    const res = await staffAction(fd);
    
    showToast(res.message, res.success ? 'success' : 'error');
    if(res.success) {
        closeModal('addQualModal');
        setTimeout(() => location.reload(), 1000); // Reload to show new item
    }
}
</script>
