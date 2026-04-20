<?php // TAB: MEDICAL RECORDS ?>
<div id="sec-records" class="dash-section">
  
<style>
.record-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 6px 20px rgba(0,0,0,.04); overflow:hidden; margin-bottom:2rem; }
.record-card-header { padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; background:var(--surface); }
.modal-box.premium-modal { border-radius:18px; border:1px solid rgba(255,255,255,0.1); }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-file-medical" style="color:var(--primary);"></i> Medical Records</h2>
    <div style="display:flex;gap:.7rem;flex-wrap:wrap;">
      <button onclick="openModal('modalNewRecord')" class="btn btn-primary" style="border-radius:12px;padding:.8rem 1.4rem;"><span class="btn-text"><i class="fas fa-plus"></i> Add New Record</span></button>
    </div>
  </div>


  <div class="adm-card shadow-sm" style="overflow:hidden;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="recordTable">
        <thead><tr style="background:linear-gradient(90deg, var(--surface-2), var(--surface));"><th>Record ID</th><th>Patient</th><th>Visit Date</th><th>Diagnosis</th><th>Follow-up</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($med_records)):?>
          <tr><td colspan="6" style="text-align:center;padding:3rem;color:var(--text-muted);">No medical records found.</td></tr>
        <?php else: foreach($med_records as $mr):
          $rj=json_encode(['id'=>$mr['id'],'record_id'=>$mr['record_id'],'patient_name'=>$mr['patient_name'],
            'p_ref'=>$mr['p_ref'],'visit_date'=>$mr['visit_date'],'diagnosis'=>$mr['diagnosis'],
            'symptoms'=>$mr['symptoms'],'treatment'=>$mr['treatment'],'notes'=>$mr['notes'],
            'follow_up_date'=>$mr['follow_up_date']],JSON_HEX_QUOT|JSON_HEX_APOS);
        ?>
        <tr>
          <td><code style="font-size:1.1rem;"><?=htmlspecialchars($mr['record_id'])?></code></td>
          <td><strong><?=htmlspecialchars($mr['patient_name'])?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($mr['p_ref'])?></span></td>
          <td><?=date('d M Y',strtotime($mr['visit_date']))?></td>
          <td><?=htmlspecialchars(substr($mr['diagnosis'],0,50)).(strlen($mr['diagnosis'])>50?'…':'')?></td>
          <td><?=$mr['follow_up_required']?('<span class="adm-badge adm-badge-warning">'.date('d M',strtotime($mr['follow_up_date']??'')).'</span>'):'<span style="color:var(--text-muted);">None</span>'?></td>
          <td>
            <div class="action-btns">
              <button onclick='viewRecord(<?=$rj?>)' class="btn-icon btn btn-ghost btn-sm"><span class="btn-text"><i class="fas fa-eye"></i> View</span></button>
              <a href="/RMU-Medical-Management-System/php/dashboards/medical_records.php?record=<?=$mr['id']?>" class="btn btn-primary btn-sm"><span class="btn-text"><i class="fas fa-paperclip"></i> Files</span></a>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: View Record -->
<div class="modal-bg" id="modalViewRecord">
  <div class="modal-box wide premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-file-medical" style="color:#fff;"></i> Medical Record</h3>
      <button class="modal-close" onclick="closeModal('modalViewRecord')">&times;</button>
    </div>
    <div id="recordDetail" style="padding:1rem;"></div>
  </div>
</div>

<!-- Modal: Add New Record -->
<div class="modal-bg" id="modalNewRecord">
  <div class="modal-box wide premium-modal">
    <div class="modal-header">
      <h3><i class="fas fa-file-circle-plus" style="color:#fff;"></i> Add New Medical Record</h3>
      <button class="modal-close" onclick="closeModal('modalNewRecord')">&times;</button>
    </div>
    <form id="formNewRecord" onsubmit="submitRecord(event)" style="padding:1rem;">
      <div class="form-row">
        <div class="form-group"><label>Select Patient</label>
          <select class="form-control" name="patient_id" required>
            <option value="">-- Choose Patient --</option>
            <?php foreach($patients as $pt):?>
            <option value="<?=$pt['id']?>"><?=htmlspecialchars($pt['name'])?> (<?=htmlspecialchars($pt['p_ref'])?>)</option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="form-group"><label>Visit Date</label><input type="date" name="visit_date" class="form-control" value="<?=$today?>" required></div>
      </div>
      <div class="form-group"><label>Diagnosis</label><input type="text" name="diagnosis" class="form-control" placeholder="Primary diagnosis…" required></div>
      <div class="form-group"><label>Symptoms</label><textarea name="symptoms" class="form-control" rows="2" placeholder="Patient-reported symptoms…"></textarea></div>
      <div class="form-group"><label>Treatment / Plan</label><textarea name="treatment" class="form-control" rows="3" placeholder="Treatment plan, medications, referrals…" required></textarea></div>
      <div class="form-group"><label>Doctor Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Internal clinical notes…"></textarea></div>
      <div class="form-row">
        <div class="form-group">
          <label>Follow-up Required?</label>
          <select name="follow_up_required" class="form-control" onchange="document.getElementById('fuDate').style.display=this.value==='1'?'block':'none'">
            <option value="0">No</option><option value="1">Yes</option>
          </select>
        </div>
        <div class="form-group" id="fuDate" style="display:none;"><label>Follow-up Date</label><input type="date" name="follow_up_date" class="form-control"></div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.5rem;"><span class="btn-text"><i class="fas fa-save"></i> Save Record</span></button>
    </form>
  </div>
</div>

<script>
function viewRecord(r){
  document.getElementById('recordDetail').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;margin-bottom:2rem;font-size:1.3rem;background:var(--surface-2);padding:1.5rem;border-radius:12px;">
      <div><strong style="color:var(--text-secondary);">Patient</strong><br><span style="font-weight:600;color:var(--text-primary);">${r.patient_name}</span></div>
      <div><strong style="color:var(--text-secondary);">ID</strong><br><span style="font-family:monospace;color:var(--primary);">${r.p_ref}</span></div>
      <div><strong style="color:var(--text-secondary);">Visit Date</strong><br><span style="font-weight:600;">${r.visit_date}</span></div>
      <div><strong style="color:var(--text-secondary);">Record ID</strong><br><code>${r.record_id}</code></div>
      ${r.follow_up_date?`<div><strong style="color:var(--warning);">Follow-up</strong><br><span style="font-weight:600;">${r.follow_up_date}</span></div>`:''}
    </div>
    <div style="display:flex;flex-direction:column;gap:1.5rem;">
      <div class="record-card-v2" style="padding:1.8rem;margin:0;border:1.5px solid var(--primary);border-radius:12px;box-shadow:0 4px 15px rgba(47,128,237,0.1);">
        <strong style="color:var(--primary);font-size:1.2rem;text-transform:uppercase;letter-spacing:0.04em;"><i class="fas fa-stethoscope"></i> Diagnosis</strong><p style="margin-top:.8rem;font-size:1.3rem;">${r.diagnosis}</p>
      </div>
      ${r.symptoms?`<div class="record-card-v2" style="padding:1.8rem;margin:0;"><strong><i class="fas fa-head-side-cough"></i> Symptoms</strong><p style="margin-top:.8rem;color:var(--text-secondary);">${r.symptoms}</p></div>`:''}
      ${r.treatment?`<div class="record-card-v2" style="padding:1.8rem;margin:0;"><strong><i class="fas fa-briefcase-medical"></i> Treatment Plan</strong><p style="margin-top:.8rem;color:var(--text-secondary);">${r.treatment}</p></div>`:''}
      ${r.notes?`<div class="record-card-v2" style="padding:1.8rem;margin:0;"><strong><i class="fas fa-notes-medical"></i> Clinical Notes</strong><p style="margin-top:.8rem;color:var(--text-secondary);">${r.notes}</p></div>`:''}
    </div>`;
  openModal('modalViewRecord');
}
async function submitRecord(e){
  e.preventDefault();
  const fd=new FormData(e.target);
  const data={action:'add_record'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await docAction(data);
  if(res.success){toast('Medical record saved!');closeModal('modalNewRecord');setTimeout(()=>location.reload(),1200);}
  else toast(res.message||'Error saving record','danger');
}

$(document).ready(function() {
    if($.fn.DataTable) {
        $('#recordTable').DataTable({
            pageLength: 10,
            order: [[2, 'desc']],
            language: { search: "", searchPlaceholder: "Quick search records..." }
        });
    }
});
</script>
