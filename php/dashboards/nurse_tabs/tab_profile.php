<?php
// ============================================================
// NURSE DASHBOARD - ADVANCED PROFILE (MODULE 13)
// ============================================================
if (!isset($conn)) exit;

// ── FETCH COMPREHENSIVE NURSE PROFILE ────────────────────────
$q_profile = mysqli_query($conn, "
    SELECT n.*, u.email as login_email, u.name as login_name 
    FROM nurses n 
    JOIN users u ON n.user_id = u.id 
    WHERE n.user_id = $nurse_id 
    LIMIT 1
");

if (!$q_profile || mysqli_num_rows($q_profile) == 0) {
    echo "<div class='alert alert-danger'>Critical Error: Nurse profile corrupted or unlinked.</div>";
    exit;
}
$profile = mysqli_fetch_assoc($q_profile);

// ── CALCULATE PROFILE COMPLETENESS ───────────────────────────
$fields_tracked = [
    'full_name', 'date_of_birth', 'gender', 'nationality', 'phone', 'email', 'address',
    'license_number', 'license_expiry', 'specialization', 'designation', 'years_of_experience'
];
$filled = 0;
foreach($fields_tracked as $f) {
    if(!empty($profile[$f])) $filled++;
}
$completeness = round(($filled / count($fields_tracked)) * 100);
$comp_color = 'bg-danger';
if($completeness > 50) $comp_color = 'bg-warning';
if($completeness > 80) $comp_color = 'bg-info';
if($completeness == 100) $comp_color = 'bg-success';
?>

<div class="tab-content" id="profile">

    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-id-card-alt me-2"></i> Professional Profile</h4>
            <p class="text-muted mb-0">Manage your clinical credentials, personal information, and secure access.</p>
        </div>
    </div>

    <div class="row g-4 border-top pt-4">
        
        <!-- Left Column: Avatar & Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-body text-center p-4">
                    <div class="position-relative d-inline-block mb-3">
                        <?php 
                            $photoPath = '../../uploads/profiles/' . $profile['profile_photo'];
                            // Fallback if not physically present
                            if(!file_exists(dirname(__DIR__, 2) . '/uploads/profiles/' . $profile['profile_photo'])) {
                                $photoPath = '../assets/images/default-avatar.png'; // standard fallback
                            }
                        ?>
                        <img src="<?= e($photoPath) ?>" alt="Profile" class="rounded-circle shadow-sm" style="width: 140px; height: 140px; object-fit: cover; border: 4px solid white;">
                        <button class="btn btn-sm btn-primary rounded-circle position-absolute bottom-0 end-0 shadow" style="width:35px;height:35px;" onclick="$('#avatarUploadInput').click()">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                    
                    <!-- Hidden File Input -->
                    <form id="avatarForm" style="display:none;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="upload_photo">
                        <input type="file" id="avatarUploadInput" name="profile_photo" accept="image/jpeg, image/png, image/jpg" onchange="uploadAvatar()">
                    </form>

                    <h4 class="fw-bold mb-1"><?= e($profile['full_name']) ?></h4>
                    <p class="text-muted mb-2"><?= e($profile['designation']) ?> - <?= e($profile['specialization'] ?: 'General') ?></p>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3 rounded-pill">
                        <i class="fas fa-fingerprint me-1"></i> <?= e($profile['nurse_id']) ?>
                    </span>

                    <hr class="my-4">

                    <div class="text-start">
                        <p class="text-muted fw-bold small text-uppercase mb-2">Profile Completeness</p>
                        <div class="progress" style="height: 10px; border-radius: 5px;">
                            <div class="progress-bar <?= $comp_color ?> progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $completeness ?>%;"></div>
                        </div>
                        <p class="mt-2 mb-0 fw-bold <?= str_replace('bg-','text-',$comp_color) ?>"><?= $completeness ?>% Complete</p>
                        <?php if($completeness < 100): ?>
                            <small class="text-muted d-block mt-1">Please fill out all missing fields for HR compliance.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Important Credentials Summary -->
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-4">
                    <h6 class="fw-bold text-muted text-uppercase border-bottom pb-2 mb-3"><i class="fas fa-certificate me-2 text-primary"></i> Registration Data</h6>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Nursing License #</small>
                        <strong class="text-dark fs-5"><?= e($profile['license_number'] ?: 'Not Provided') ?></strong>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted d-block">License Expiry</small>
                        <?php 
                            $exp = $profile['license_expiry'];
                            $exp_style = 'text-dark';
                            if($exp) {
                                $exp_date = new DateTime($exp);
                                $now = new DateTime();
                                if($exp_date < $now) $exp_style = 'text-danger fw-bold';
                                elseif($exp_date < (new DateTime('+30 days'))) $exp_style = 'text-warning text-dark fw-bold';
                            }
                        ?>
                        <strong class="fs-5 <?= $exp_style ?>"><?= $exp ? date('d M, Y', strtotime($exp)) : 'Not Provided' ?></strong>
                        <?php if($exp && $exp_style == 'text-danger fw-bold'): ?>
                            <span class="badge bg-danger ms-2 pulse-fade">EXPIRED</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted d-block">Years of Experience</small>
                        <strong class="text-dark fs-5"><?= $profile['years_of_experience'] ?> Years</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Forms -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-body p-0">
                    
                    <ul class="nav nav-tabs nav-justified px-3 pt-3" id="profileTabs">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold" data-bs-toggle="tab" data-bs-target="#personal">
                                <i class="fas fa-user text-primary me-1"></i> Personal
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold" data-bs-toggle="tab" data-bs-target="#professional">
                                <i class="fas fa-briefcase text-primary me-1"></i> Professional
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold text-danger" data-bs-toggle="tab" data-bs-target="#security">
                                <i class="fas fa-lock me-1"></i> Security
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-4">
                        
                        <!-- Personal Info Tab -->
                        <div class="tab-pane fade show active" id="personal">
                            <form id="personalForm">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_personal">
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Full Legal Name</label>
                                        <input type="text" class="form-control" name="full_name" value="<?= e($profile['full_name']) ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Date of Birth</label>
                                        <input type="date" class="form-control" name="date_of_birth" value="<?= e($profile['date_of_birth']) ?>" required>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-bold">Gender</label>
                                        <select class="form-select" name="gender">
                                            <option value="Female" <?= $profile['gender']=='Female'?'selected':'' ?>>Female</option>
                                            <option value="Male" <?= $profile['gender']=='Male'?'selected':'' ?>>Male</option>
                                            <option value="Other" <?= $profile['gender']=='Other'?'selected':'' ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-bold">Nationality</label>
                                        <input type="text" class="form-control" name="nationality" value="<?= e($profile['nationality']) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-bold">Phone Number</label>
                                        <input type="text" class="form-control" name="phone" value="<?= e($profile['phone']) ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Contact Email (Secondary)</label>
                                    <input type="email" class="form-control" name="email" value="<?= e($profile['email']) ?>">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label text-muted small fw-bold">Residential Address</label>
                                    <textarea class="form-control" name="address" rows="2"><?= e($profile['address']) ?></textarea>
                                </div>
                                
                                <div class="text-end border-top pt-3">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold"><i class="fas fa-save me-2"></i> Save Changes</button>
                                </div>
                            </form>
                        </div>

                        <!-- Professional Details Tab -->
                        <div class="tab-pane fade" id="professional">
                            <form id="professionalForm">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_professional">
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Nursing License Number</label>
                                        <input type="text" class="form-control" name="license_number" value="<?= e($profile['license_number']) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">License Expiry Date</label>
                                        <input type="date" class="form-control <?= $exp_style == 'text-danger fw-bold'?'is-invalid':'' ?>" name="license_expiry" value="<?= e($profile['license_expiry']) ?>">
                                        <?php if($exp_style == 'text-danger fw-bold'): ?>
                                            <div class="invalid-feedback">License is expired! RMU HR requires immediate renewal.</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-5">
                                        <label class="form-label text-muted small fw-bold">Designation / Rank</label>
                                        <select class="form-select" name="designation">
                                            <?php $desigs = ['Staff Nurse', 'Senior Staff Nurse', 'Nursing Officer', 'Senior Nursing Officer', 'Principal Nursing Officer', 'Chief Nursing Officer'];
                                            foreach($desigs as $d): ?>
                                                <option value="<?= $d ?>" <?= $profile['designation']==$d?'selected':'' ?>><?= $d ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small fw-bold">Clinical Specialization</label>
                                        <input type="text" class="form-control" name="specialization" value="<?= e($profile['specialization']) ?>" placeholder="e.g. ICU, Midwifery, Pediatric">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-muted small fw-bold">Yrs Experience</label>
                                        <input type="number" class="form-control" name="years_of_experience" value="<?= (int)$profile['years_of_experience'] ?>" min="0" max="60">
                                    </div>
                                </div>
                                
                                <div class="text-end border-top pt-3">
                                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold"><i class="fas fa-save me-2"></i> Update Credentials</button>
                                </div>
                            </form>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security">
                            <div class="alert alert-info border-0 rounded" style="background-color: var(--primary-light); color: var(--primary-dark);">
                                <i class="fas fa-info-circle me-2"></i> Your login email is <strong><?= e($profile['login_email']) ?></strong>. Contact IT Admin to change your core User ID.
                            </div>

                            <form id="passwordForm" class="mt-4">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_password">
                                
                                <h6 class="fw-bold mb-3"><i class="fas fa-key text-danger me-2"></i> Change Account Password</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-bold">Current Password</label>
                                    <input type="password" class="form-control" name="current_password" required>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">New Password</label>
                                        <input type="password" class="form-control" name="new_password" required minlength="8" placeholder="Min. 8 characters">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold">Confirm New Password</label>
                                        <input type="password" class="form-control" name="confirm_password" required minlength="8">
                                    </div>
                                </div>
                                
                                <div class="text-end border-top pt-3">
                                    <button type="submit" class="btn btn-danger rounded-pill px-5 fw-bold"><i class="fas fa-lock me-2"></i> Update Security</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function uploadAvatar() {
    const fd = new FormData(document.getElementById('avatarForm'));
    $.ajax({
        url: '../nurse/process_profile.php',
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if(res.success) location.reload(); else alert('Error: ' + res.message);
        }
    });
}

$(document).ready(function() {
    $('#personalForm, #professionalForm, #passwordForm').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const btn = form.find('button[type=submit]');
        const origText = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: '../nurse/process_profile.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    alert(res.message);
                    if(form.attr('id') === 'passwordForm') form[0].reset();
                    else location.reload(); // Reload to update completeness bar
                } else {
                    alert('Error: ' + res.message);
                }
                btn.prop('disabled', false).html(origText);
            },
            error: function() {
                alert('A server error occurred.');
                btn.prop('disabled', false).html(origText);
            }
        });
    });
});
</script>
