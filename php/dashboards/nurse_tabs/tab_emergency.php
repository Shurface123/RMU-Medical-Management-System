<!-- ═══════════════════════════════════════════════════════════
     MODULE 7: EMERGENCY RESPONSE — tab_emergency.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$active_alerts = dbSelect($conn,
    "SELECT ea.*, u.name AS triggered_by_name, up.name AS patient_name,
            ud.name AS responder_name
     FROM emergency_alerts ea
     LEFT JOIN nurses n ON ea.triggered_by=n.id LEFT JOIN users u ON n.user_id=u.id
     LEFT JOIN patients p ON ea.patient_id=p.id LEFT JOIN users up ON p.user_id=up.id
     LEFT JOIN users ud ON ea.responded_by=ud.id
     WHERE ea.status IN('Active','Responded')
     ORDER BY FIELD(ea.status,'Active','Responded'), ea.triggered_at DESC");

$resolved_alerts = dbSelect($conn,
    "SELECT ea.*, u.name AS triggered_by_name, up.name AS patient_name
     FROM emergency_alerts ea
     LEFT JOIN nurses n ON ea.triggered_by=n.id LEFT JOIN users u ON n.user_id=u.id
     LEFT JOIN patients p ON ea.patient_id=p.id LEFT JOIN users up ON p.user_id=up.id
     WHERE ea.status='Resolved'
     ORDER BY ea.resolved_at DESC LIMIT 50");

$type_icons = ['Code Blue'=>'fa-heart-pulse','Rapid Response'=>'fa-bolt','Fall'=>'fa-person-falling-burst','Fire'=>'fa-fire','General Emergency'=>'fa-triangle-exclamation','Security'=>'fa-shield'];
$severity_colors = ['Critical'=>'danger','High'=>'warning','Medium'=>'info','Low'=>'success'];
?>
<div id="sec-emergency" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-truck-medical"></i> Emergency Response</h2>
    <button class="btn btn-danger" onclick="openModal('emergencyModal')" style="animation:pulse-emergency 2s infinite;"><i class="fas fa-triangle-exclamation"></i> TRIGGER ALERT</button>
  </div>

  <!-- ── Code Blue Quick Action ── -->
  <div style="background:linear-gradient(135deg,#2980B9,#1A5276);border-radius:var(--radius-md);padding:1.5rem 2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;box-shadow:var(--shadow-md);">
    <div><h3 style="font-size:1.6rem;margin:0;">🔵 Code Blue — Quick Trigger</h3><p style="opacity:.85;margin:.3rem 0 0;">One-click cardiac/respiratory arrest alert. Notifies all doctors and admin instantly.</p></div>
    <button class="btn" style="background:rgba(255,255,255,.2);color:#fff;border:1.5px solid rgba(255,255,255,.4);font-size:1.4rem;padding:1rem 2rem;" onclick="triggerCodeBlue()"><i class="fas fa-heart-pulse"></i> CODE BLUE</button>
  </div>

  <!-- ── Active Alerts ── -->
  <div class="info-card" style="margin-bottom:1.5rem;border-left:3px solid var(--danger);">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;"><i class="fas fa-bell" style="color:var(--danger);"></i> Active Alerts <span class="badge badge-danger"><?=count($active_alerts)?></span></h3>
    <?php if(empty($active_alerts)):?>
      <p class="text-center text-muted" style="padding:2rem;"><i class="fas fa-check-circle" style="color:var(--success);"></i> No active emergency alerts</p>
    <?php else: foreach($active_alerts as $ea):
      $elapsed = $ea['triggered_at'] ? round((time()-strtotime($ea['triggered_at']))/60) : 0;
    ?>
      <div class="alert-card" style="border-left:3px solid var(--<?=$severity_colors[$ea['severity']]??'warning'?>);">
        <div class="alert-icon <?=($ea['severity']==='Critical')?'red':'orange'?>"><i class="fas <?=$type_icons[$ea['alert_type']]??'fa-triangle-exclamation'?>"></i></div>
        <div style="flex:1;">
          <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.3rem;">
            <strong style="font-size:1.4rem;"><?=e($ea['alert_type'])?></strong>
            <span class="badge badge-<?=$severity_colors[$ea['severity']]??'warning'?>"><?=e($ea['severity'])?></span>
            <span class="badge badge-<?=$ea['status']==='Active'?'danger':'warning'?>"><?=e($ea['status'])?></span>
          </div>
          <?php if($ea['patient_name']):?><p style="font-size:1.2rem;"><strong>Patient:</strong> <?=e($ea['patient_name'])?></p><?php endif;?>
          <p style="font-size:1.15rem;color:var(--text-secondary);"><strong>Location:</strong> <?=e($ea['location']??'—')?></p>
          <?php if($ea['message']):?><p style="font-size:1.15rem;color:var(--text-secondary);"><?=e($ea['message'])?></p><?php endif;?>
          <div style="font-size:1.05rem;color:var(--text-muted);margin-top:.4rem;">
            Triggered by <?=e($ea['triggered_by_name']??'Unknown')?> · <?=$elapsed?> min ago
            <?php if($ea['responder_name']):?> · Responded by Dr. <?=e($ea['responder_name'])?><?php endif;?>
          </div>
        </div>
        <div style="font-size:2rem;font-weight:800;color:var(--danger);text-align:center;min-width:70px;"><?=$elapsed?>m</div>
      </div>
    <?php endforeach; endif;?>
  </div>

  <!-- ── Rapid Patient Status ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-search" style="color:var(--role-accent);"></i> Rapid Patient Lookup</h3>
    <div class="form-row" style="align-items:flex-end;">
      <div class="form-group"><label>Search Patient</label><input id="rapid_search" class="form-control" placeholder="Patient name or ID..."></div>
      <div class="form-group"><button class="btn btn-primary" onclick="rapidLookup()"><i class="fas fa-search"></i> Lookup</button></div>
    </div>
    <div id="rapidResult" style="display:none;margin-top:1rem;"></div>
  </div>

  <!-- ── Resolved History ── -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-history" style="color:var(--text-secondary);"></i> Resolved Alerts</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Type</th><th>Severity</th><th>Patient</th><th>Location</th><th>Response Time</th><th>Resolved</th>
    </tr></thead><tbody>
    <?php if(empty($resolved_alerts)):?>
      <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No resolved alerts</td></tr>
    <?php else: foreach($resolved_alerts as $ra):
      $resp_min = ($ra['triggered_at'] && $ra['resolved_at']) ? round((strtotime($ra['resolved_at'])-strtotime($ra['triggered_at']))/60) : '—';
    ?>
      <tr>
        <td><i class="fas <?=$type_icons[$ra['alert_type']]??'fa-bell'?>" style="margin-right:.5rem;"></i><?=e($ra['alert_type'])?></td>
        <td><span class="badge badge-<?=$severity_colors[$ra['severity']]??'secondary'?>"><?=e($ra['severity'])?></span></td>
        <td><?=e($ra['patient_name']??'—')?></td><td><?=e($ra['location']??'—')?></td>
        <td><?=$resp_min!=='—'?$resp_min.' min':'—'?></td>
        <td><?=$ra['resolved_at']?date('d M h:i A',strtotime($ra['resolved_at'])):'—'?></td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>
</div>

<script>
async function triggerCodeBlue(){
  if(!confirmAction('🔵 CODE BLUE: This will immediately notify ALL doctors and administrators. Continue?')) return;
  const loc=prompt('Confirm location (ward/bed):','<?=e($nurse_row['ward_assigned']??'')?>');
  if(!loc) return;
  const r=await nurseAction({action:'trigger_emergency',alert_type:'Code Blue',severity:'Critical',location:loc,message:'Code Blue activated'});
  showToast(r.message||'Alert sent',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1200);
}

async function rapidLookup(){
  const q=document.getElementById('rapid_search').value.trim();
  if(!q){showToast('Enter patient name or ID','error');return;}
  const r=await nurseAction({action:'rapid_patient_lookup',search:q});
  const div=document.getElementById('rapidResult');
  div.style.display='block';
  if(!r.success||!r.data){div.innerHTML='<p class="text-center" style="color:var(--danger);">Patient not found</p>';return;}
  const p=r.data;
  div.innerHTML=`<div class="info-card" style="border-left:3px solid var(--role-accent);">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
      <div><h4>Patient</h4><p><strong>${p.name}</strong></p><p>${p.patient_id||''}</p><p>DOB: ${p.dob||'—'}</p><p>Gender: ${p.gender||'—'}</p></div>
      <div><h4>Medical</h4><p>Blood Type: <strong>${p.blood_type||'—'}</strong></p><p>Allergies: <strong style="color:var(--danger);">${p.allergies||'None'}</strong></p><p>Doctor: ${p.doctor||'—'}</p></div>
      <div><h4>Latest Vitals</h4><p>BP: ${p.bp||'—'}</p><p>HR: ${p.hr||'—'} bpm</p><p>Temp: ${p.temp||'—'}°C</p><p>SpO2: ${p.spo2||'—'}%</p></div>
    </div></div>`;
}
</script>
