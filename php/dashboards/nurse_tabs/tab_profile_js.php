<!-- ═══ PROFILE JAVASCRIPT ═══ -->
<script>
// ── Age calculator ──
function calcAge(dob){
  if(!dob) return;
  const bd=new Date(dob), now=new Date(), age=now.getFullYear()-bd.getFullYear()-(now<new Date(now.getFullYear(),bd.getMonth(),bd.getDate())?1:0);
  const el=document.getElementById('ageDisplay');
  if(el) el.textContent='Age: '+age; else{const s=document.createElement('small');s.className='text-muted';s.id='ageDisplay';s.textContent='Age: '+age;document.querySelector('[data-field="date_of_birth"]').parentNode.appendChild(s);}
}

// ── Toggle all editing mode ──
let editMode=false;
function toggleAllEditing(){
  editMode=!editMode;
  document.querySelectorAll('.pf-value').forEach(v=>v.style.display=editMode?'none':'block');
  document.querySelectorAll('.pf-edit-input').forEach(i=>i.style.display=editMode?'block':'none');
  document.querySelectorAll('.section-save-btn').forEach(b=>b.style.display=editMode?'inline-flex':'none');
}

// ── Gather field values from a section ──
function gatherFields(sectionId){
  const data={};
  document.querySelectorAll('#'+sectionId+' .pf-edit-input').forEach(i=>{
    data[i.dataset.field]=i.value;
  });
  return data;
}

// ── Save Personal Info ──
async function savePersonalInfo(){
  const data=gatherFields('section-personal');
  data.action='update_personal_info';
  const r=await nurseAction(data);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1200);
}

// ── Save Professional Info ──
async function saveProfessionalInfo(){
  const data=gatherFields('section-professional');
  data.action='update_professional_profile';
  // Convert languages to JSON array
  if(data.languages_spoken) data.languages_spoken=data.languages_spoken.split(',').map(s=>s.trim()).filter(Boolean);
  const r=await nurseAction(data);
  showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1200);
}

// ── Availability Status ──
async function updateAvailability(status){
  const r=await nurseAction({action:'update_availability',status:status});
  showToast(r.message||'Status updated',r.success?'success':'error');
}

// ── Shift Preference Notes ──
async function saveShiftPrefNotes(){
  const r=await nurseAction({action:'save_shift_pref_notes',notes:document.getElementById('shiftPrefNotes').value});
  showToast(r.message||'Saved',r.success?'success':'error');
}

// ── Password Strength ──
function checkPasswordStrength(pw){
  let s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
  const labels=['','Weak','Fair','Strong','Very Strong','Very Strong'];
  const colors=['','#E74C3C','#F39C12','#2ECC71','#27AE60','#27AE60'];
  document.getElementById('pw_strength').innerHTML=pw?`<span style="color:${colors[s]};">Strength: ${labels[s]}</span> ${'█'.repeat(s)}${'░'.repeat(5-s)}`:'';
}

async function changePassword(){
  const cur=document.getElementById('pw_current').value, nw=document.getElementById('pw_new').value, cnf=document.getElementById('pw_confirm').value;
  if(!cur||!nw){showToast('Fill all password fields','error');return;}
  if(nw!==cnf){showToast('Passwords do not match','error');return;}
  if(nw.length<8){showToast('Password must be at least 8 characters','error');return;}
  const r=await nurseAction({action:'change_password',current:cur,new_password:nw});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){document.getElementById('pw_current').value='';document.getElementById('pw_new').value='';document.getElementById('pw_confirm').value='';document.getElementById('pw_strength').innerHTML='';}
}

// ── 2FA Toggle ──
async function toggle2FA(enabled){
  const r=await nurseAction({action:'toggle_2fa',enabled:enabled?1:0});
  showToast(r.message||'Updated',r.success?'success':'error');
}

// ── Session Management ──
async function logoutSession(sid){
  if(!confirmAction('Log out this device?')) return;
  const r=await nurseAction({action:'logout_session',session_id:sid});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}
async function logoutAllSessions(){
  if(!confirmAction('Log out all other devices?')) return;
  const r=await nurseAction({action:'logout_all_sessions'});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),800);
}

// ── Account Deactivation ──
async function requestDeactivation(){
  if(!confirmAction('Request account deactivation? This will be sent to admin for review.')) return;
  const r=await nurseAction({action:'request_account_deletion'});
  showToast(r.message||'Done',r.success?'success':'error');
}

// ── Photo Upload ──
async function uploadProfilePhoto(input){
  if(!input.files[0]) return;
  if(input.files[0].size>2*1024*1024){showToast('Max 2MB allowed','error');return;}
  const fd=new FormData();fd.append('action','upload_profile_photo');fd.append('photo',input.files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1000);
}

// ── Qualification / Certification / Document ──
async function submitQual(){
  if(!validateForm({qual_degree:'Degree',qual_inst:'Institution',qual_year:'Year'})) return;
  const fd=new FormData();fd.append('action','add_qualification');
  fd.append('degree',document.getElementById('qual_degree').value);
  fd.append('institution',document.getElementById('qual_inst').value);
  fd.append('year',document.getElementById('qual_year').value);
  if(document.getElementById('qual_file').files[0]) fd.append('certificate',document.getElementById('qual_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addQualModal');setTimeout(()=>location.reload(),1000);}
}
async function submitCert(){
  if(!validateForm({cert_name:'Certification name',cert_body:'Issuing body'})) return;
  const fd=new FormData();fd.append('action','add_certification');
  fd.append('name',document.getElementById('cert_name').value);
  fd.append('issuing_body',document.getElementById('cert_body').value);
  fd.append('issue_date',document.getElementById('cert_issue').value);
  fd.append('expiry_date',document.getElementById('cert_expiry').value);
  if(document.getElementById('cert_file').files[0]) fd.append('certificate_file',document.getElementById('cert_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Saved',r.success?'success':'error');
  if(r.success){closeModal('addCertModal');setTimeout(()=>location.reload(),1000);}
}
async function submitDoc(){
  if(!document.getElementById('doc_file').files[0]){showToast('Select a file','error');return;}
  const fd=new FormData();fd.append('action','upload_document');
  fd.append('document_type',document.getElementById('doc_type').value);
  fd.append('document_name',document.getElementById('doc_name').value||document.getElementById('doc_file').files[0].name);
  fd.append('file',document.getElementById('doc_file').files[0]);
  const r=await nurseAction(fd);showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('uploadDocModal');setTimeout(()=>location.reload(),1000);}
}
async function deleteQual(id){if(!confirmAction('Delete this qualification?'))return;
  const r=await nurseAction({action:'delete_qualification',qual_id:id});showToast(r.message,'success');if(r.success) setTimeout(()=>location.reload(),800);}
async function deleteCert(id){if(!confirmAction('Delete this certification?'))return;
  const r=await nurseAction({action:'delete_certification',cert_id:id});showToast(r.message,'success');if(r.success) setTimeout(()=>location.reload(),800);}
async function deleteDoc(id){if(!confirmAction('Delete this document?'))return;
  const r=await nurseAction({action:'delete_document',doc_id:id});showToast(r.message,'success');if(r.success) setTimeout(()=>location.reload(),800);}

// ── Notification Preferences ──
async function saveNotifToggles(){
  const data={action:'save_notification_toggles'};
  document.querySelectorAll('.notif_toggle').forEach(cb=>{data[cb.dataset.key]=cb.checked?1:0;});
  data.preferred_channel=document.getElementById('prefChannel').value;
  data.critical_sound_enabled=document.getElementById('critSound').checked?1:0;
  data.preferred_notif_lang=document.getElementById('prefNotifLang').value;
  const r=await nurseAction(data);showToast(r.success?'Preferences saved':'Error',r.success?'success':'error');
}

// ── Charts (Section F) ──
document.addEventListener('DOMContentLoaded',function(){
  const task7Data=<?=json_encode(['labels'=>array_column($task7,'d'),'data'=>array_map('intval',array_column($task7,'c'))])?>;
  if(document.getElementById('taskVolumeChart')){
    new Chart(document.getElementById('taskVolumeChart'),{type:'bar',data:{labels:task7Data.labels.map(d=>d.slice(5)),datasets:[{label:'Tasks Completed',data:task7Data.data,backgroundColor:'rgba(233,30,99,.6)',borderRadius:4}]},options:{responsive:true,plugins:{legend:{display:false},title:{display:true,text:'Task Volume — Last 7 Days'}},scales:{y:{beginAtZero:true,ticks:{stepSize:1}}}}});
  }
  if(document.getElementById('taskTypeChart')){
    new Chart(document.getElementById('taskTypeChart'),{type:'doughnut',data:{labels:['Vitals','Medications','Notes','Emergency'],datasets:[{data:[<?=$type_vitals?>,<?=$type_meds?>,<?=$type_notes?>,<?=$type_emerg?>],backgroundColor:['#2F80ED','#E91E63','#27AE60','#E74C3C']}]},options:{responsive:true,plugins:{title:{display:true,text:'Activity Distribution'}}}});
  }
});
</script>
