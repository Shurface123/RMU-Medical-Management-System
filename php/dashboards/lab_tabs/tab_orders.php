<!-- ═══════════════ MODULE 2: TEST ORDER MANAGEMENT ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-clipboard-list" style="color:var(--role-accent);margin-right:.6rem;"></i> Test Order Management</h1>
    <p>Incoming orders from doctors — accept, reject, track processing status</p>
  </div>
</div>

<!-- Summary Strip -->
<div class="adm-summary-strip">
  <?php
  $o_stats=['Pending'=>0,'Accepted'=>0,'Sample Collected'=>0,'Processing'=>0,'Completed'=>0,'Rejected'=>0];
  foreach($all_orders as $o) if(isset($o_stats[$o['order_status']])) $o_stats[$o['order_status']]++;
  $s_colors=['Pending'=>'orange','Accepted'=>'','Sample Collected'=>'','Processing'=>'','Completed'=>'green','Rejected'=>'red'];
  foreach($o_stats as $st=>$cnt):?>
  <div class="adm-mini-card"><div class="adm-mini-card-num <?=$s_colors[$st]??''?>"><?=$cnt?></div><div class="adm-mini-card-label"><?=$st?></div></div>
  <?php endforeach;?>
</div>

<!-- Filters -->
<div class="adm-card" style="margin-bottom:1.5rem;">
  <div class="adm-card-body" style="padding:1.2rem 2rem;">
    <div style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label>Status</label>
        <select id="ordFilterStatus" class="form-control" onchange="applyOrderFilters()">
          <option value="">All</option>
          <?php foreach(array_keys($o_stats) as $st):?><option value="<?=$st?>"><?=$st?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label>Urgency</label>
        <select id="ordFilterUrg" class="form-control" onchange="applyOrderFilters()">
          <option value="">All</option><option>Routine</option><option>Urgent</option><option>STAT</option><option>Critical</option>
        </select>
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label>Date From</label>
        <input id="ordFilterFrom" type="date" class="form-control" value="<?=$month_start?>" onchange="applyOrderFilters()">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:140px;">
        <label>Date To</label>
        <input id="ordFilterTo" type="date" class="form-control" value="<?=$today?>" onchange="applyOrderFilters()">
      </div>
      <div class="form-group" style="margin:0;flex:1;min-width:160px;">
        <label>Search</label>
        <input id="ordSearch" class="form-control" placeholder="Patient, doctor, test..." onkeyup="applyOrderFilters()">
      </div>
    </div>
  </div>
</div>

<!-- Orders Table -->
<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="ordersTable">
        <thead><tr>
          <th>Order ID</th><th>Patient</th><th>Doctor</th><th>Test</th><th>Urgency</th><th>Ordered</th><th>Required By</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($all_orders)):?>
          <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-clipboard-list" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No test orders yet</td></tr>
        <?php else: foreach($all_orders as $o):
          $urg_cls=['STAT'=>'urgency-stat stat-val','Critical'=>'urgency-critical','Urgent'=>'urgency-urgent','Routine'=>'urgency-routine'][$o['urgency']]??'adm-badge adm-badge-info';
          $st_cls=['Pending'=>'warning','Accepted'=>'info','Sample Collected'=>'primary','Processing'=>'info','Completed'=>'success','Rejected'=>'danger'][$o['order_status']]??'info';
          
          // Phase 6: Turnaround Time (TAT) Monitoring
          $is_overdue = false;
          $tat_pct = 0;
          $tat_text = '--';
          $tat_color = 'var(--text-muted)';
          
          if ($o['order_status'] !== 'Completed' && $o['order_status'] !== 'Rejected') {
              $ordered_ts = strtotime($o['created_at']);
              $now_ts = time();
              $elapsed_hrs = ($now_ts - $ordered_ts) / 3600;
              $allowed_hrs = (float)($o['normal_turnaround_hours'] ?? 24.0); // Default 24h if null
              
              if ($allowed_hrs > 0) {
                  $tat_pct = min(100, ($elapsed_hrs / $allowed_hrs) * 100);
                  $remaining_hrs = $allowed_hrs - $elapsed_hrs;
                  
                  if ($remaining_hrs < 0) {
                      $is_overdue = true;
                      $tat_text = abs(round($remaining_hrs, 1)) . 'h overdue';
                      $tat_color = 'var(--danger)';
                  } else {
                      $tat_text = round($remaining_hrs, 1) . 'h left';
                      if ($tat_pct > 80) $tat_color = 'var(--warning)';
                      else $tat_color = 'var(--success)';
                  }
              }
          }
        ?>
          <tr class="<?=$is_overdue?'row-danger':''?>" data-status="<?=e($o['order_status'])?>" data-urgency="<?=e($o['urgency'])?>" data-date="<?=date('Y-m-d',strtotime($o['created_at']))?>">
            <td>
              <span style="font-family:monospace;font-weight:700;color:var(--role-accent);"><?=e($o['order_id'])?></span>
              <?php if($is_overdue):?><br><span class="adm-badge adm-badge-danger" style="font-size:1rem;margin-top:.4rem;animation:pulse-emergency 1.5s infinite;"><i class="fas fa-exclamation-triangle"></i> OVERDUE</span><?php endif;?>
            </td>
            <td><?=e($o['patient_name']??'—')?></td>
            <td><?=e($o['doctor_name']??'—')?></td>
            <td><strong><?=e($o['test_name']??'—')?></strong></td>
            <td><span class="<?=$urg_cls?>"><?=e($o['urgency'])?></span></td>
            <td style="white-space:nowrap;font-size:1.2rem;">
              <?=date('d M, h:i A',strtotime($o['created_at']))?>
              <?php if($o['order_status'] !== 'Completed' && $o['order_status'] !== 'Rejected'): ?>
                <div style="margin-top:.5rem;width:100px;background:#eee;height:6px;border-radius:3px;overflow:hidden;" title="Turnaround Time (<?=$allowed_hrs??24?>h)">
                  <div style="width:<?=$tat_pct?>%;height:100%;background:<?=$tat_color?>;"></div>
                </div>
                <div style="font-size:1rem;color:<?=$tat_color?>;margin-top:.2rem;"><strong><?=$tat_text?></strong></div>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;font-size:1.2rem;"><?=$o['required_by_date']?date('d M Y',strtotime($o['required_by_date'])):'—'?></td>
            <td><span class="adm-badge adm-badge-<?=$st_cls?>"><?=e($o['order_status'])?></span></td>
            <td class="adm-table-actions">
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='viewOrderDetail(<?=json_encode($o)?>)' title="View Details"><i class="fas fa-eye"></i></button>
              <?php if($o['order_status']==='Pending'):?>
                <button class="adm-btn adm-btn-sm adm-btn-success" onclick="acceptOrder(<?=$o['id']?>)" title="Accept"><i class="fas fa-check"></i></button>
                <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="openRejectModal(<?=$o['id']?>)" title="Reject"><i class="fas fa-times"></i></button>
              <?php endif;?>
              <?php if($o['order_status']==='Accepted'):?>
                <button class="adm-btn adm-btn-sm adm-btn-primary" onclick="collectSample(<?=$o['id']?>)" title="Collect Sample"><i class="fas fa-vial"></i></button>
              <?php endif;?>
              <?php if($o['order_status']==='Sample Collected'):?>
                <button class="adm-btn adm-btn-sm adm-btn-primary" onclick="markSampleReceived(<?=$o['id']?>)" title="Mark Received"><i class="fas fa-inbox"></i></button>
              <?php endif;?>
              <?php if(in_array($o['order_status'],['Accepted','Sample Collected'])):?>
                <button class="adm-btn adm-btn-sm adm-btn-warning" onclick="startProcessing(<?=$o['id']?>)" title="Start Processing"><i class="fas fa-cog"></i></button>
              <?php endif;?>
              <!-- Phase 6: Reassign Workload -->
              <?php if(in_array($o['order_status'],['Pending','Accepted','Sample Collected','Processing'])):?>
                <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick="openReassignModal(<?=$o['id']?>)" title="Reassign Order"><i class="fas fa-exchange-alt"></i></button>
              <?php endif;?>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Reassign Order Modal (Phase 6) -->
<div class="modal-bg" id="reassignOrderModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-exchange-alt" style="color:var(--role-accent);"></i> Reassign Order</h3><button class="modal-close" onclick="closeModal('reassignOrderModal')">&times;</button></div>
    <input type="hidden" id="reassign_order_id">
    <div class="form-group">
      <label>Select New Technician *</label>
      <select id="reassign_tech_id" class="form-control">
        <option value="">-- Choose Technician --</option>
        <?php 
          // Re-query active techs for the dropdown
          $techs = mysqli_query($conn,"SELECT id, full_name, specialization FROM lab_technicians WHERE status='Active' ORDER BY full_name ASC");
          if($techs) while($t = mysqli_fetch_assoc($techs)):
        ?>
        <option value="<?=$t['id']?>"><?=e($t['full_name'])?> (<?=e($t['specialization']??'General')?>)</option>
        <?php endwhile; ?>
      </select>
    </div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="confirmReassign()"><i class="fas fa-save"></i> Save Reassignment</button>
  </div>
</div>

<!-- Order Detail Modal -->
<div class="modal-bg" id="orderDetailModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-clipboard-list"></i> Order Details</h3><button class="modal-close" onclick="closeModal('orderDetailModal')">&times;</button></div>
    <div id="orderDetailBody" style="font-size:1.3rem;"></div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal-bg" id="rejectOrderModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Order</h3><button class="modal-close" onclick="closeModal('rejectOrderModal')">&times;</button></div>
    <input type="hidden" id="reject_order_id">
    <div class="form-group"><label>Rejection Reason *</label><textarea id="reject_reason" class="form-control" rows="3" placeholder="Explain why this order is being rejected..."></textarea></div>
    <div class="adm-alert adm-alert-warning" style="margin-bottom:1.5rem;"><i class="fas fa-exclamation-triangle"></i> The requesting doctor will be immediately notified with this reason.</div>
    <button class="adm-btn adm-btn-danger" style="width:100%;" onclick="confirmReject()"><i class="fas fa-times"></i> Confirm Rejection</button>
  </div>
</div>

<!-- Sample Condition Modal -->
<div class="modal-bg" id="sampleConditionModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-vial"></i> Sample Collection</h3><button class="modal-close" onclick="closeModal('sampleConditionModal')">&times;</button></div>
    <input type="hidden" id="sc_order_id">
    <div class="form-row">
      <div class="form-group"><label>Sample Type</label><select id="sc_type" class="form-control"><option>Blood</option><option>Urine</option><option>Stool</option><option>Swab</option><option>CSF</option><option>Tissue</option><option>Other</option></select></div>
      <div class="form-group"><label>Container</label><select id="sc_container" class="form-control"><option>EDTA</option><option>Plain</option><option>Heparin</option><option>Citrate</option><option>Fluoride</option><option>Sterile Cup</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Volume (mL)</label><input id="sc_volume" type="number" step="0.1" class="form-control" value="5"></div>
      <div class="form-group"><label>Condition</label><select id="sc_condition" class="form-control"><option>Good</option><option>Haemolysed</option><option>Clotted</option><option>Lipemic</option><option>Insufficient</option><option>Contaminated</option></select></div>
    </div>
    <div class="form-group"><label>Storage Location</label><input id="sc_storage" class="form-control" placeholder="e.g. Rack A, Shelf 2"></div>
    <div class="form-group"><label>Notes</label><textarea id="sc_notes" class="form-control" rows="2"></textarea></div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="confirmCollectSample()"><i class="fas fa-vial"></i> Log Sample</button>
  </div>
</div>

<script>
function applyOrderFilters(){
  const st=document.getElementById('ordFilterStatus').value;
  const urg=document.getElementById('ordFilterUrg').value;
  const from=document.getElementById('ordFilterFrom').value;
  const to=document.getElementById('ordFilterTo').value;
  const q=document.getElementById('ordSearch').value.toLowerCase();
  document.querySelectorAll('#ordersTable tbody tr').forEach(r=>{
    const mS=!st||r.dataset.status===st;
    const mU=!urg||r.dataset.urgency===urg;
    const d=r.dataset.date;
    const mD=(!from||d>=from)&&(!to||d<=to);
    const mQ=!q||r.textContent.toLowerCase().includes(q);
    r.style.display=(mS&&mU&&mD&&mQ)?'':'none';
  });
}

function viewOrderDetail(o){
  let h='<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">';
  h+='<div class="adm-card" style="margin:0;"><div class="adm-card-header"><h3><i class="fas fa-user"></i> Patient</h3></div><div class="adm-card-body">';
  h+='<p><strong>Name:</strong> '+(o.patient_name||'—')+'</p>';
  h+='<p><strong>ID:</strong> '+(o.p_ref||'—')+'</p></div></div>';
  h+='<div class="adm-card" style="margin:0;"><div class="adm-card-header"><h3><i class="fas fa-user-md"></i> Doctor</h3></div><div class="adm-card-body">';
  h+='<p><strong>Name:</strong> '+(o.doctor_name||'—')+'</p></div></div>';
  h+='</div>';
  h+='<div clas="adm-card" style="margin-top:1.5rem;padding:1.5rem;background:var(--surface-2);border-radius:var(--radius-md);">';
  h+='<h4 style="margin-bottom:1rem;font-weight:700;"><i class="fas fa-flask" style="color:var(--role-accent);"></i> Test Details</h4>';
  h+='<p><strong>Order ID:</strong> <span style="font-family:monospace;color:var(--role-accent);">'+o.order_id+'</span></p>';
  h+='<p><strong>Test:</strong> '+(o.test_name||'—')+'</p>';
  h+='<p><strong>Urgency:</strong> '+o.urgency+'</p>';
  h+='<p><strong>Status:</strong> '+o.order_status+'</p>';
  h+='<p><strong>Clinical Notes:</strong> '+(o.clinical_notes||'None provided')+'</p>';
  h+='<p><strong>Ordered:</strong> '+new Date(o.created_at).toLocaleString()+'</p>';
  if(o.required_by_date) h+='<p><strong>Required By:</strong> '+o.required_by_date+'</p>';
  if(o.rejection_reason) h+='<div class="adm-alert adm-alert-danger" style="margin-top:1rem;"><i class="fas fa-times-circle"></i> <strong>Rejected:</strong> '+o.rejection_reason+'</div>';
  h+='</div>';
  // Status pipeline
  h+='<div style="margin-top:1.5rem;"><h4 style="margin-bottom:.8rem;font-weight:700;">Status Pipeline</h4><div class="status-pipeline">';
  ['Pending','Accepted','Sample Collected','Processing','Completed'].forEach(s=>{
    const idx=['Pending','Accepted','Sample Collected','Processing','Completed'].indexOf(o.order_status);
    const sidx=['Pending','Accepted','Sample Collected','Processing','Completed'].indexOf(s);
    h+='<span class="pipeline-step '+(sidx<idx?'completed':sidx===idx?'active':'')+'">'+s+'</span>';
    if(s!=='Completed') h+='<span class="pipeline-arrow"><i class="fas fa-chevron-right"></i></span>';
  });
  h+='</div></div>';
  document.getElementById('orderDetailBody').innerHTML=h;
  openModal('orderDetailModal');
}

async function acceptOrder(id){
  if(!confirmAction('Accept this order?'))return;
  const r=await labAction({action:'accept_order',order_id:id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}

// Phase 6: Order Reassignment
function openReassignModal(id){
  document.getElementById('reassign_order_id').value=id;
  document.getElementById('reassign_tech_id').value='';
  openModal('reassignOrderModal');
}
async function confirmReassign(){
  const oid = document.getElementById('reassign_order_id').value;
  const tid = document.getElementById('reassign_tech_id').value;
  if(!tid){ showToast('Please select a technician required','error'); return; }
  const r=await labAction({action:'reassign_order',order_id:oid,new_tech_id:tid});
  showToast(r.message,r.success?'success':'error');
  if(r.success){ closeModal('reassignOrderModal'); setTimeout(()=>location.reload(),800); }
}

function openRejectModal(id){document.getElementById('reject_order_id').value=id;document.getElementById('reject_reason').value='';openModal('rejectOrderModal');}
async function confirmReject(){
  const id=document.getElementById('reject_order_id').value;const reason=document.getElementById('reject_reason').value;
  if(!reason){showToast('Rejection reason is required','error');return;}
  const r=await labAction({action:'reject_order',order_id:id,reason:reason});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('rejectOrderModal');setTimeout(()=>location.reload(),800);}
}
function collectSample(id){document.getElementById('sc_order_id').value=id;openModal('sampleConditionModal');}
async function confirmCollectSample(){
  const id=document.getElementById('sc_order_id').value;
  const cond=document.getElementById('sc_condition').value;
  // If condition is bad, confirm
  if(['Haemolysed','Clotted','Insufficient','Contaminated'].includes(cond)){
    if(!confirmAction('Sample condition is '+cond+'. This will reject the sample and notify the doctor for a new collection. Proceed?')){return;}
  }
  const r=await labAction({action:'collect_sample_detailed',order_id:id,sample_type:document.getElementById('sc_type').value,container_type:document.getElementById('sc_container').value,volume:document.getElementById('sc_volume').value,condition:cond,storage:document.getElementById('sc_storage').value,notes:document.getElementById('sc_notes').value});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('sampleConditionModal');setTimeout(()=>location.reload(),800);}
}
async function markSampleReceived(id){
  if(!confirmAction('Mark sample as received in lab?'))return;
  const r=await labAction({action:'mark_sample_received',order_id:id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
async function startProcessing(id){
  if(!confirmAction('Start processing this order?'))return;
  const r=await labAction({action:'start_processing',order_id:id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
</script>
