<?php
// ============================================================
// MEDICAL RECORDS — File Attachment Manager
// Updated: Phase 4 — uses new schema, admin-dashboard.css
// ============================================================
session_start();
require_once '../db_conn.php';
require_once '../classes/FileUploadManager.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); exit;
}
date_default_timezone_set('Africa/Accra');

$fileUploadManager = new FileUploadManager($conn);
$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'patient';
$message  = '';
$error    = '';

// ── Handle file upload ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['medical_file'])) {
    $recordId    = (int)$_POST['record_id'];
    $description = $_POST['description'] ?? '';
    $result = $fileUploadManager->uploadMedicalAttachment($recordId, $_FILES['medical_file'], $description, $userId);
    $result['success'] ? $message = 'File uploaded successfully!' : $error = $result['message'];
}

// ── Handle file deletion ──────────────────────────────────
if (isset($_GET['delete'])) {
    $result = $fileUploadManager->deleteAttachment((int)$_GET['delete'], $userId);
    $result['success'] ? $message = $result['message'] : $error = $result['message'];
}

// ── Fetch records (with correct column names) ─────────────
if ($userRole === 'patient') {
    $pidRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM patients WHERE user_id=$userId LIMIT 1"));
    $patId   = (int)($pidRow['id'] ?? 0);
    $stmt    = $conn->prepare("SELECT mr.*, u2.name AS doctor_name
      FROM medical_records mr LEFT JOIN doctors d ON mr.doctor_id=d.id LEFT JOIN users u2 ON d.user_id=u2.id
      WHERE mr.patient_id=? ORDER BY mr.visit_date DESC");
    $stmt->bind_param('i', $patId);
} elseif ($userRole === 'doctor') {
    $didRow  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM doctors WHERE user_id=$userId LIMIT 1"));
    $docId   = (int)($didRow['id'] ?? 0);
    $stmt    = $conn->prepare("SELECT mr.*, u2.name AS patient_name
      FROM medical_records mr LEFT JOIN patients p ON mr.patient_id=p.id LEFT JOIN users u2 ON p.user_id=u2.id
      WHERE mr.doctor_id=? ORDER BY mr.visit_date DESC");
    $stmt->bind_param('i', $docId);
} else {
    $stmt = $conn->prepare("SELECT mr.*, u2.name AS patient_name, u3.name AS doctor_name
      FROM medical_records mr
      LEFT JOIN patients p ON mr.patient_id=p.id LEFT JOIN users u2 ON p.user_id=u2.id
      LEFT JOIN doctors d ON mr.doctor_id=d.id LEFT JOIN users u3 ON d.user_id=u3.id
      ORDER BY mr.visit_date DESC LIMIT 200");
}
$stmt->execute();
$medicalRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Unread notification count ─────────────────────────────
$unread = (int)(mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM notifications WHERE user_id=$userId AND is_read=0"))[0] ?? 0);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Medical Records — RMU Medical Sickbay</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
<style>
:root{--role-accent:<?=$userRole==='doctor'?'#1abc9c':($userRole==='patient'?'#9B59B6':'#2F80ED')?>;}
.upload-zone{border:2.5px dashed var(--border);border-radius:var(--radius-md);padding:2.5rem;text-align:center;cursor:pointer;transition:var(--transition);background:var(--surface-2);}
.upload-zone:hover{border-color:var(--role-accent);background:var(--role-accent-light,rgba(26,188,156,.06));}
.upload-zone i{font-size:2.5rem;color:var(--text-muted);display:block;margin-bottom:.8rem;}
.file-row{display:flex;align-items:center;gap:1rem;padding:1rem 1.2rem;background:var(--surface-2);border-radius:10px;margin-bottom:.6rem;}
.file-icon-box{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;flex-shrink:0;}
.diagnose-pill{display:inline-block;background:var(--role-accent);color:#fff;border-radius:20px;padding:.2rem .9rem;font-size:1.1rem;font-weight:600;}
</style>
<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>"></head>
<body>

<div class="adm-layout">
<!-- SIDEBAR (minimal — record links only) -->
<aside class="adm-sidebar" id="admSidebar">
  <div class="adm-sidebar-brand">
    <div class="adm-brand-icon"><i class="fas fa-stethoscope"></i></div>
    <div class="adm-brand-text"><span class="adm-brand-name">RMU Sickbay</span><span class="adm-brand-role"><?=ucfirst($userRole)?> Portal</span></div>
  </div>
  <nav class="adm-nav" style="padding:1.5rem 1rem;">
    <a href="/RMU-Medical-Management-System/php/dashboards/<?=$userRole?>_dashboard.php" class="adm-nav-item"><i class="fas fa-house"></i><span>Dashboard</span></a>
    <a href="medical_records.php" class="adm-nav-item active"><i class="fas fa-folder-open"></i><span>Medical Records</span></a>
    <?php if($userRole==='patient'):?>
    <a href="my_appointments.php" class="adm-nav-item"><i class="fas fa-calendar-check"></i><span>My Appointments</span></a>
    <a href="prescription_refills.php" class="adm-nav-item"><i class="fas fa-prescription-bottle-medical"></i><span>Prescription Refills</span></a>
    <?php endif;?>
    <?php if($userRole==='doctor'):?>
    <a href="doctor_dashboard.php?tab=appointments" class="adm-nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
    <a href="doctor_dashboard.php?tab=prescriptions" class="adm-nav-item"><i class="fas fa-prescription-bottle-medical"></i><span>Prescriptions</span></a>
    <?php endif;?>
  </nav>
  <div class="adm-sidebar-footer">
    <a href="/RMU-Medical-Management-System/php/logout.php" class="btn btn-primary adm-logout-btn"><span class="btn-text"><i class="fas fa-right-from-bracket"></i><span>Logout</span></span></a>
  </div>
</aside>
<div class="adm-overlay" id="admOverlay"></div>

<main class="adm-main">
  <!-- TOPBAR -->
  <div class="adm-topbar">
    <div class="adm-topbar-left">
      <button class="adm-menu-toggle" id="menuToggle" style="display:none;"><i class="fas fa-bars"></i></button>
      <span class="adm-page-title"><i class="fas fa-folder-open" style="color:var(--role-accent);margin-right:.6rem;"></i>Medical Records &amp; Attachments</span>
    </div>
    <div class="adm-topbar-right">
      <?php $bd=$unread>0?'flex':'none'; $bl=$unread>99?'99+':$unread; $bc=$unread>0?'adm-notif-btn has-unread':'adm-notif-btn'; ?>
      <div style="position:relative;">
        <button id="rmuBellBtn" class="btn btn-primary <?=$bc?>" title="Notifications"><span class="btn-text"><i class="fas fa-bell"></i>
          <span id="rmuBellCount" style="display:<?=$bd?>"><?=$bl?></span>
        </span></button>
      </div>
      <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
      <div class="adm-avatar" style="background:linear-gradient(135deg,var(--role-accent),var(--primary));"><?=strtoupper(substr($_SESSION['user_name']??$_SESSION['name']??'U',0,1))?></div>
    </div>
  </div>

  <div class="adm-content">
    <?php if($message):?><div style="background:var(--success-light);color:var(--success);border-left:4px solid var(--success);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-check-circle"></i> <?=htmlspecialchars($message)?></div><?php endif;?>
    <?php if($error):?><div style="background:var(--danger-light);color:var(--danger);border-left:4px solid var(--danger);border-radius:0 10px 10px 0;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:1.3rem;"><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?></div><?php endif;?>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
      <h2 style="font-size:2rem;font-weight:700;display:flex;align-items:center;gap:.8rem;"><i class="fas fa-folder-open" style="color:var(--role-accent);"></i> Medical Records</h2>
      <div class="adm-search-wrap" style="max-width:280px;"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="recSearch" placeholder="Search records…" oninput="filterRecs(this.value)"></div>
    </div>

    <?php if(empty($medicalRecords)):?>
      <div class="adm-card" style="text-align:center;padding:4rem;">
        <i class="fas fa-folder-open" style="font-size:3.5rem;opacity:.25;display:block;margin-bottom:1.2rem;"></i>
        <h3 style="color:var(--text-muted);">No Medical Records Found</h3>
        <p style="color:var(--text-muted);margin-top:.5rem;font-size:1.2rem;">Records will appear here after your consultations.</p>
      </div>
    <?php else: foreach($medicalRecords as $rec):
      $visits_date = $rec['visit_date'] ?? $rec['record_date'] ?? null;
      $recId       = (int)$rec['id'];
      // Load attachments
      $atts = $fileUploadManager->getRecordAttachments($rec['record_id'] ?? $recId);
    ?>
    <div class="adm-card rec-card" style="margin-bottom:1.5rem;" data-text="<?=strtolower(htmlspecialchars($rec['diagnosis']??'')).' '.strtolower($rec['patient_name']??$rec['doctor_name']??'')?>">
      <div class="adm-card-header" style="cursor:pointer;" onclick="this.parentElement.querySelector('.rec-body').classList.toggle('hidden')">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
          <div class="diagnose-pill"><?=htmlspecialchars($rec['diagnosis']??'—')?></div>
          <div>
            <?php if(isset($rec['patient_name'])):?><span style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($rec['patient_name'])?></span><?php endif;?>
            <?php if(isset($rec['doctor_name'])):?><span style="font-size:1.2rem;color:var(--text-muted);"> &mdash; Dr. <?=htmlspecialchars($rec['doctor_name'])?></span><?php endif;?>
            <?php if($visits_date):?><span style="font-size:1.1rem;color:var(--text-muted);margin-left:.5rem;"><i class="fas fa-calendar"></i> <?=date('d M Y',strtotime($visits_date))?></span><?php endif;?>
          </div>
          <?php if($rec['follow_up_required']??0):?><span class="adm-badge adm-badge-warning"><i class="fas fa-calendar-check"></i> Follow-up <?=$rec['follow_up_date']?date('d M',strtotime($rec['follow_up_date'])):''?></span><?php endif;?>
        </div>
        <div style="display:flex;align-items:center;gap:.8rem;">
          <span style="font-size:1.1rem;color:var(--text-muted);"><?=count($atts)?> file(s)</span>
          <i class="fas fa-chevron-down" style="color:var(--text-muted);"></i>
        </div>
      </div>

      <div class="rec-body" style="padding:1.5rem;">
        <!-- Record Detail -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
          <?php foreach([
            ['Record ID','record_id','fa-hashtag'],['Symptoms','symptoms','fa-virus'],
            ['Treatment','treatment','fa-pills'],['Notes','notes','fa-note-sticky']
          ] as [$label,$key,$icon]):
            if(empty($rec[$key])) continue;?>
          <div style="background:var(--surface-2);border-radius:10px;padding:1rem;">
            <div style="font-size:1rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.4rem;"><i class="fas <?=$icon?>" style="color:var(--role-accent);margin-right:.4rem;"></i><?=$label?></div>
            <div style="font-size:1.25rem;color:var(--text-primary);"><?=htmlspecialchars($rec[$key]??'')?></div>
          </div>
          <?php endforeach;?>
        </div>

        <!-- Attachments -->
        <div>
          <h4 style="font-size:1.3rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem;"><i class="fas fa-paperclip" style="color:var(--role-accent);"></i> Attachments</h4>
          <?php if(empty($atts)):?>
            <div style="color:var(--text-muted);font-size:1.2rem;text-align:center;padding:1.5rem;background:var(--surface-2);border-radius:10px;"><i class="fas fa-file" style="margin-right:.5rem;"></i>No attachments for this record.</div>
          <?php else: foreach($atts as $att):
            $ext=strtolower(pathinfo($att['file_name'],PATHINFO_EXTENSION));
            [$ic,$bg]=in_array($ext,['jpg','jpeg','png','gif'])?['fa-file-image','#3498db']:(in_array($ext,['doc','docx'])?['fa-file-word','#2F80ED']:['fa-file-pdf','#E74C3C']);
          ?>
          <div class="file-row">
            <div class="file-icon-box" style="background:<?=$bg?>;"><i class="fas <?=$ic?>"></i></div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:1.25rem;"><?=htmlspecialchars($att['file_name']??'')?></div>
              <div style="font-size:1.1rem;color:var(--text-muted);"><?=number_format(($att['file_size']??0)/1024,1)?> KB &middot; <?=date('d M Y',strtotime($att['uploaded_at']??'now'))?>
                <?php if($att['description']??''):?> &middot; <?=htmlspecialchars($att['description'])?><?php endif;?>
              </div>
            </div>
            <div style="display:flex;gap:.5rem;">
              <a href="/RMU-Medical-Management-System/php/download_attachment.php?id=<?=$att['attachment_id']?>" class="btn-icon btn btn-primary btn-sm"><span class="btn-text"><i class="fas fa-download"></i> Download</span></a>
              <?php if(in_array($userRole,['doctor','admin'])):?>
              <a href="?delete=<?=$att['attachment_id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this file?')"><span class="btn-text"><i class="fas fa-trash"></i></span></a>
              <?php endif;?>
            </div>
          </div>
          <?php endforeach; endif;?>

          <!-- Upload area (doctor / admin only) -->
          <?php if(in_array($userRole,['doctor','admin'])):?>
          <form method="POST" enctype="multipart/form-data" style="margin-top:1.2rem;">
            <input type="hidden" name="record_id" value="<?=$recId?>">
            <div class="upload-zone" onclick="document.getElementById('uf<?=$recId?>').click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <strong style="display:block;font-size:1.3rem;">Upload New Attachment</strong>
              <span style="font-size:1.1rem;color:var(--text-muted);">Click or drag &amp; drop · PDF, JPG, PNG, DOC (max 10 MB)</span>
              <input type="file" id="uf<?=$recId?>" name="medical_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="display:none;" onchange="showUploadDetail(this,<?=$recId?>)">
            </div>
            <div id="ud<?=$recId?>" style="display:none;margin-top:1rem;">
              <div class="form-group"><label>File Description (optional)</label><textarea name="description" class="form-control" rows="2" placeholder="Brief note about this file…"></textarea></div>
              <button type="submit" class="btn btn-success"><span class="btn-text"><i class="fas fa-upload"></i> Upload File</span></button>
            </div>
          </form>
          <?php endif;?>
        </div>
      </div><!-- /rec-body -->
    </div>
    <?php endforeach; endif;?>

  </div><!-- /adm-content -->
</main>
</div>

<script>
// Theme
function applyTheme(t){document.documentElement.setAttribute('data-theme',t);localStorage.setItem('rmu_theme',t);document.getElementById('themeIcon').className=t==='dark'?'fas fa-sun':'fas fa-moon';}
applyTheme(localStorage.getItem('rmu_theme')||'light');
document.getElementById('themeToggle')?.addEventListener('click',()=>{applyTheme(document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark');});

// Sidebar
document.getElementById('menuToggle')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.toggle('active');document.getElementById('admOverlay').classList.toggle('active');});
document.getElementById('admOverlay')?.addEventListener('click',()=>{document.getElementById('admSidebar').classList.remove('active');document.getElementById('admOverlay').classList.remove('active');});

// Upload reveal
function showUploadDetail(input,id){if(input.files&&input.files[0])document.getElementById('ud'+id).style.display='block';}

// Drag & drop
document.querySelectorAll('.upload-zone').forEach(z=>{
  z.addEventListener('dragover',e=>{e.preventDefault();z.style.borderColor='var(--role-accent)';});
  z.addEventListener('dragleave',()=>z.style.borderColor='');
  z.addEventListener('drop',e=>{e.preventDefault();z.style.borderColor='';const fi=z.querySelector('input[type=file]');fi.files=e.dataTransfer.files;const id=fi.id.replace('uf','');showUploadDetail(fi,id);});
});

// Search filter
function filterRecs(q){q=q.toLowerCase();document.querySelectorAll('.rec-card').forEach(c=>{c.style.display=c.dataset.text.includes(q)?'':'none';});}
</script>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>


<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>
</html>
