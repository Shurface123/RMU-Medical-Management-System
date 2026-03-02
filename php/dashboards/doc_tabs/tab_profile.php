<?php
// ============================================================
// TAB: DOCTOR PROFILE — Main container
// Loads sub-sections from profile_sections/ directory
// ============================================================

// Fetch profile data needed across sections
$prof = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT d.*, u.name, u.email, u.phone, u.gender, u.profile_image, u.date_of_birth,
            u.created_at AS member_since, u.last_login_at, u.last_active_at
     FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.id=$doc_pk LIMIT 1")) ?? [];

$departments = [];
$dq = mysqli_query($conn,"SELECT id,name FROM departments WHERE is_active=1 ORDER BY name");
if($dq) while($r=mysqli_fetch_assoc($dq)) $departments[]=$r;

$qualifications = [];
$qq = mysqli_query($conn,"SELECT * FROM doctor_qualifications WHERE doctor_id=$doc_pk ORDER BY year_awarded DESC");
if($qq) while($r=mysqli_fetch_assoc($qq)) $qualifications[]=$r;

$certifications = [];
$cq = mysqli_query($conn,"SELECT * FROM doctor_certifications WHERE doctor_id=$doc_pk ORDER BY expiry_date ASC");
if($cq) while($r=mysqli_fetch_assoc($cq)) $certifications[]=$r;

$availability = [];
$aq = mysqli_query($conn,"SELECT * FROM doctor_availability WHERE doctor_id=$doc_pk ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
if($aq) while($r=mysqli_fetch_assoc($aq)) $availability[$r['day_of_week']]=$r;

$leave_exceptions = [];
$lq = mysqli_query($conn,"SELECT * FROM doctor_leave_exceptions WHERE doctor_id=$doc_pk AND exception_date>=CURDATE() ORDER BY exception_date");
if($lq) while($r=mysqli_fetch_assoc($lq)) $leave_exceptions[]=$r;

$documents = [];
$doq = mysqli_query($conn,"SELECT * FROM doctor_documents WHERE doctor_id=$doc_pk ORDER BY uploaded_at DESC");
if($doq) while($r=mysqli_fetch_assoc($doq)) $documents[]=$r;

$settings_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM doctor_settings WHERE doctor_id=$doc_pk LIMIT 1")) ?? [];

$completeness = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM doctor_profile_completeness WHERE doctor_id=$doc_pk LIMIT 1")) ?? ['overall_pct'=>0];
$comp_pct = (int)($completeness['overall_pct'] ?? $prof['profile_completion_pct'] ?? 0);

// Age calculation
$age = $prof['date_of_birth'] ? (int)date_diff(date_create($prof['date_of_birth']),date_create('today'))->y : null;

// Availability status
$avail_status = $prof['availability_status'] ?? 'Offline';
?>

<div id="sec-profile" class="dash-section">
  <div class="sec-header"><h2><i class="fas fa-user-doctor"></i> My Profile</h2></div>

  <!-- Profile sub-nav -->
  <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:2rem;">
    <?php foreach([
      ['header','Identity','fa-id-card'],['personal','Personal Info','fa-user'],
      ['professional','Professional','fa-stethoscope'],['qualifications','Qualifications','fa-graduation-cap'],
      ['availability','Availability','fa-calendar-alt'],['statistics','Statistics','fa-chart-bar'],
      ['security','Account & Security','fa-shield-halved'],['notifications','Notifications','fa-bell'],
      ['documents','Documents','fa-file-upload'],['completeness','Completeness','fa-tasks']
    ] as [$id,$label,$icon]):?>
    <button class="ftab prof-nav <?=$id==='header'?'active':''?>" onclick="showProfSection('<?=$id?>',this)">
      <i class="fas <?=$icon?>"></i> <?=$label?>
    </button>
    <?php endforeach;?>
  </div>

  <?php
    $profDir = __DIR__.'/profile_sections/';
    include $profDir.'prof_header.php';
    include $profDir.'prof_personal.php';
    include $profDir.'prof_professional.php';
    include $profDir.'prof_qualifications.php';
    include $profDir.'prof_availability.php';
    include $profDir.'prof_statistics.php';
    include $profDir.'prof_security.php';
    include $profDir.'prof_notifications.php';
    include $profDir.'prof_documents.php';
    include $profDir.'prof_completeness.php';
  ?>
</div>

<script>
const PROF_API='/RMU-Medical-Management-System/php/dashboards/doctor_profile_actions.php';
async function profAction(data,isFormData=false){
  const opts={method:'POST'};
  if(isFormData){opts.body=data;}
  else{opts.headers={'Content-Type':'application/json'};opts.body=JSON.stringify(data);}
  try{const r=await fetch(PROF_API,opts);return await r.json();}catch(e){return{success:false,message:e.message};}
}
function showProfSection(id,btn){
  document.querySelectorAll('.prof-section').forEach(s=>s.style.display='none');
  document.getElementById('prof-'+id).style.display='block';
  document.querySelectorAll('.prof-nav').forEach(b=>b.classList.remove('active'));
  if(btn)btn.classList.add('active');
}
// Show header by default
document.addEventListener('DOMContentLoaded',()=>{showProfSection('header');});
</script>
