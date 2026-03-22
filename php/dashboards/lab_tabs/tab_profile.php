<?php
// ============================================================
// LAB DASHBOARD - TAB PROFILE (Module 13)
// ============================================================
if (!isset($user_id)) { exit; }

// Fetch full technician details
$prof_query = mysqli_query($conn, "SELECT l.*, u.email, u.phone 
                                   FROM lab_technicians l 
                                   JOIN users u ON l.user_id = u.id 
                                   WHERE l.user_id = $user_id LIMIT 1");
$profile = mysqli_fetch_assoc($prof_query);

// Handle missing profile row gracefully
if (!$profile) {
    echo "<div class='alert alert-warning'>Technician profile not initialized. Please contact the administrator.</div>";
    exit;
}

// Calculate Profile Completeness
$fields_tracked = ['full_name', 'phone', 'address', 'profile_photo', 'license_number', 'license_expiry', 'specialization', 'years_of_experience'];
$filled = 0;
foreach ($fields_tracked as $f) {
    if (!empty($profile[$f])) $filled++;
}

// Check for uploaded documents
$doc_count_q = mysqli_query($conn, "SELECT COUNT(*) FROM lab_technician_documents WHERE technician_id = {$profile['id']}");
$has_docs = mysqli_fetch_row($doc_count_q)[0] > 0;
if ($has_docs) $filled++; 

$total_fields = count($fields_tracked) + 1; // +1 for documents
$completeness = round(($filled / $total_fields) * 100);

// Fetch Documents
$docs_res = mysqli_query($conn, "SELECT * FROM lab_technician_documents WHERE technician_id = {$profile['id']} ORDER BY uploaded_at DESC");
?>

<div class="sec-header">
    <h2><i class="fas fa-id-card"></i> Advanced Technician Profile</h2>
    <div style="display:flex; gap:1rem;">
        <button class="adm-btn adm-btn-success" onclick="editProfile()"><i class="fas fa-edit"></i> Edit Profile</button>
    </div>
</div>

<div class="charts-grid" style="grid-template-columns: 1fr 2fr;">
    
    <!-- Profile Card & Completeness -->
    <div>
        <div class="info-card" style="text-align: center; margin-bottom: 1.5rem;">
            <div style="position: relative; display: inline-block;">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= !empty($profile['profile_photo']) ? e($profile['profile_photo']) : 'default-avatar.png' ?>" 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--role-accent); padding: 4px; box-shadow: var(--shadow-md);">
                <button class="adm-btn adm-btn-primary" style="position: absolute; bottom: 0; right: 0; border-radius: 50%; width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-sm);" title="Update Photo" onclick="uploadPhoto()">
                    <i class="fas fa-camera"></i>
                </button>
            </div>
            
            <h3 style="margin-top: 1rem; color: var(--text-primary); font-size: 1.4rem;"><?= e($profile['full_name']) ?></h3>
            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;"><?= e($profile['designation']) ?> - <?= e($profile['specialization']) ?></p>
            <span class="adm-badge adm-badge-success" style="font-size: 0.9rem;"><?= e($profile['status']) ?></span>
            
            <div style="margin-top: 2rem; text-align: left;">
                <div style="display:flex; justify-content:space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--text-secondary);">Profile Completeness</span>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--role-accent);"><?= $completeness ?>%</span>
                </div>
                <!-- Progress Bar -->
                <div style="width: 100%; height: 10px; background: var(--surface-2); border-radius: 5px; overflow: hidden;">
                    <div style="width: <?= $completeness ?>%; height: 100%; background: var(--role-accent); border-radius: 5px; transition: width 0.5s ease;"></div>
                </div>
                <?php if($completeness < 100): ?>
                    <p style="font-size: 0.8rem; color: var(--danger); margin-top: 0.5rem;"><i class="fas fa-exclamation-circle"></i> Upload required licenses/certs to reach 100%.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- License Info -->
        <div class="info-card">
            <h4 style="font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.8rem; margin-bottom: 1rem; color: var(--text-primary);">Licensing</h4>
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">License Number</div>
                <div style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary);"><?= e($profile['license_number']) ?: '<span style="color:var(--danger)">Missing</span>' ?></div>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">License Expiry</div>
                <div style="font-size: 1.05rem; font-weight: 500; color: var(--text-primary);">
                    <?php 
                        if ($profile['license_expiry']) {
                            $exp_dt = strtotime($profile['license_expiry']);
                            if ($exp_dt < time()) echo '<span style="color:var(--danger)">Expired on '.date('d M Y', $exp_dt).'</span>';
                            else echo date('d M Y', $exp_dt);
                        } else {
                            echo '<span style="color:var(--danger)">Missing</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details & Documents Tab -->
    <div>
        <div class="info-card" style="margin-bottom: 1.5rem;">
            <h4 style="font-size: 1.2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1.5rem; color: var(--primary);">Personal Information</h4>
            <div class="form-row">
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Technician ID</div>
                    <div style="font-size: 1.1rem; color: var(--text-primary);"><i class="fas fa-hashtag" style="color:var(--role-accent);"></i> <?= e($profile['technician_id']) ?></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Years of Experience</div>
                    <div style="font-size: 1.1rem; color: var(--text-primary);"><i class="fas fa-briefcase" style="color:var(--role-accent);"></i> <?= e($profile['years_of_experience']) ?> Years</div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Contact Phone</div>
                    <div style="font-size: 1.1rem; color: var(--text-primary);"><i class="fas fa-phone-alt" style="color:var(--role-accent);"></i> <?= e($profile['phone']) ?: '-' ?></div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Email Address</div>
                    <div style="font-size: 1.1rem; color: var(--text-primary);"><i class="fas fa-envelope" style="color:var(--role-accent);"></i> <?= e($profile['email']) ?></div>
                </div>
            </div>
            <div style="margin-bottom: 1rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Residential Address</div>
                <div style="font-size: 1.1rem; color: var(--text-primary);"><i class="fas fa-map-marker-alt" style="color:var(--role-accent);"></i> <?= e($profile['address']) ?: '-' ?></div>
            </div>
        </div>

        <div class="info-card">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border); padding-bottom: 1rem; margin-bottom: 1rem;">
                <h4 style="font-size: 1.2rem; color: var(--primary); margin:0;">Uploaded Documents & Certificates</h4>
                <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="uploadDoc()"><i class="fas fa-upload"></i> Upload</button>
            </div>
            
            <?php if(mysqli_num_rows($docs_res) === 0): ?>
                <div style="text-align: center; padding: 2rem; background: var(--surface-2); border-radius: 8px; border: 1px dashed var(--border);">
                    <i class="fas fa-file-alt" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 0.5rem;"></i>
                    <p style="color: var(--text-secondary); margin:0;">No documents uploaded yet.</p>
                </div>
            <?php else: ?>
                <div class="adm-table-wrap" style="box-shadow: none; border: none;">
                    <table class="adm-table">
                        <thead><tr><th>Document Name</th><th>Type</th><th>Uploaded</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php while($d = mysqli_fetch_assoc($docs_res)): ?>
                            <tr>
                                <td><?= e($d['document_name']) ?></td>
                                <td><span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);"><?= e($d['document_type']) ?></span></td>
                                <td><small><?= date('d M Y', strtotime($d['uploaded_at'])) ?></small></td>
                                <td>
                                    <a href="/RMU-Medical-Management-System/php/api/download_doc.php?file=<?= e($d['file_path']) ?>" target="_blank" class="adm-btn adm-btn-sm" style="background:var(--surface-2);"><i class="fas fa-eye"></i></a>
                                    <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="deleteDoc(<?= $d['id'] ?>)"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function editProfile() {
    alert("Profile editing modal and AJAX form submission to be linked.");
}

function uploadPhoto() {
    alert("Profile picture upload logic.");
}

function uploadDoc() {
    alert("Document upload modal for Licenses and Certifications.");
}

function deleteDoc(id) {
    if(confirm('Are you sure you want to delete this document?')) {
        alert("Deleting Document ID " + id + " via AJAX...");
    }
}
</script>
