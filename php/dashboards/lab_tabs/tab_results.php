<!-- ═══════════════ MODULE 4: LAB RESULT ENTRY & VALIDATION ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-microscope" style="color:var(--role-accent);margin-right:.6rem;"></i> Lab Results</h1>
    <p>Enter, validate, and release lab test results — Draft → Pending Validation → Validated → Released</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addResultModal')"><i class="fas fa-plus-circle"></i> Enter Result</button>
</div>

<!-- Summary Strip -->
<div class="adm-summary-strip">
  <?php
  $r_stats=['Draft'=>0,'Pending Validation'=>0,'Validated'=>0,'Released'=>0,'Amended'=>0];
  foreach($results as $r) if(isset($r_stats[$r['result_status']])) $r_stats[$r['result_status']]++;
  $rcolors=['Draft'=>'orange','Pending Validation'=>'','Validated'=>'green','Released'=>'','Amended'=>'red'];
  foreach($r_stats as $st=>$cnt):?>
  <div class="adm-mini-card"><div class="adm-mini-card-num <?=$rcolors[$st]?>"><?=$cnt?></div><div class="adm-mini-card-label"><?=$st?></div></div>
  <?php endforeach;?>
</div>

<!-- Filters -->
<div class="filter-tabs">
  <span class="ftab active" onclick="filterResults('all',this)">All (<?=count($results)?>)</span>
  <?php foreach($r_stats as $st=>$cnt):if($cnt>0):?><span class="ftab" onclick="filterResults('<?=$st?>',this)"><?=$st?> (<?=$cnt?>)</span><?php endif;endforeach;?>
</div>

<!-- Results Table -->
<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="resultsTable">
        <thead><tr>
          <th>Result ID</th><th>Patient</th><th>Doctor</th><th>Test</th><th>Value</th><th>Interpretation</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($results)):?>
          <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-microscope" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No results entered</td></tr>
        <?php else: foreach($results as $r):
          $int_cls=['Normal'=>'success','Abnormal'=>'warning','Critical'=>'danger','Inconclusive'=>'info','Low'=>'warning','High'=>'warning','Critical Low'=>'danger','Critical High'=>'danger'][$r['result_interpretation']??'']??'info';
          $st_cls=['Draft'=>'warning','Pending Validation'=>'info','Validated'=>'success','Released'=>'primary','Amended'=>'danger'][$r['result_status']]??'info';
        ?>
          <tr class="<?=($r['result_interpretation']==='Critical'||$r['result_interpretation']==='Critical Low'||$r['result_interpretation']==='Critical High')?'row-danger':''?>" data-status="<?=e($r['result_status'])?>">
            <td><span style="font-family:monospace;font-weight:700;color:var(--role-accent);"><?=e($r['result_id'])?></span></td>
            <td><?=e($r['patient_name']??'—')?></td>
            <td><?=e($r['doctor_name']??'—')?></td>
            <td><strong><?=e($r['test_name']??'—')?></strong></td>
            <td>
              <span style="font-weight:700;font-size:1.4rem;"><?=e($r['result_values']??'—')?></span>
              <?php if($r['unit_of_measurement']):?><span style="color:var(--text-muted);font-size:1.1rem;"> <?=e($r['unit_of_measurement'])?></span><?php endif;?>
              <?php if($r['reference_range_min']!==null||$r['reference_range_max']!==null):?><br><span style="color:var(--text-muted);font-size:1.05rem;">Ref: <?=$r['reference_range_min']??'—'?> – <?=$r['reference_range_max']??'—'?></span><?php endif;?>
            </td>
            <td><span class="adm-badge adm-badge-<?=$int_cls?>"><?=e($r['result_interpretation']??'—')?></span></td>
            <td><span class="adm-badge adm-badge-<?=$st_cls?>"><?=e($r['result_status'])?></span></td>
            <td class="adm-table-actions">
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='viewResult(<?=json_encode($r)?>)' title="View"><i class="fas fa-eye"></i></button>
              <?php if($r['result_status']==='Draft'):?>
                <button class="adm-btn adm-btn-sm adm-btn-primary" onclick="submitForValidation(<?=$r['id']?>)" title="Submit for Validation"><i class="fas fa-paper-plane"></i></button>
              <?php endif;?>
              <?php if($r['result_status']==='Pending Validation'):?>
                <button class="adm-btn adm-btn-sm adm-btn-success" onclick="validateResult(<?=$r['id']?>)" title="Validate"><i class="fas fa-check-double"></i></button>
              <?php endif;?>
              <?php if($r['result_status']==='Validated' && !$r['released_to_doctor']):?>
                <button class="adm-btn adm-btn-sm adm-btn-warning" onclick="releaseToDoctor(<?=$r['id']?>)" title="Release to Doctor"><i class="fas fa-share"></i></button>
              <?php endif;?>
              <?php if(in_array($r['result_status'],['Validated','Released'])):?>
                <button class="adm-btn adm-btn-sm adm-btn-danger" onclick="openAmendModal(<?=$r['id']?>,'<?=e($r['result_values']??'')?>')" title="Amend"><i class="fas fa-edit"></i></button>
              <?php endif;?>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Enter Result Modal -->
<div class="modal-bg" id="addResultModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-microscope"></i> Enter Lab Result</h3><button class="modal-close" onclick="closeModal('addResultModal')">&times;</button></div>
    <div class="form-group"><label>Order *</label>
      <select id="res_order" class="form-control" onchange="loadOrderParams()">
        <option value="">Select completed order...</option>
        <?php foreach($all_orders as $o):if(in_array($o['order_status'],['Processing','Sample Collected','Accepted'])):?>
        <option value="<?=$o['id']?>" data-test="<?=e($o['test_name']??'')?>" data-catalog="<?=$o['test_catalog_id']??0?>"><?=e($o['order_id'])?> — <?=e($o['test_name']??'')?> — <?=e($o['patient_name']??'')?></option>
        <?php endif;endforeach;?>
      </select>
    </div>
    <!-- Parameters container - populated dynamically -->
    <div id="paramContainer">
      <div class="form-group"><label>Test Name</label><input id="res_test" class="form-control" readonly></div>
      <div class="form-row">
        <div class="form-group"><label>Result Value *</label><input id="res_value" class="form-control" placeholder="Enter result"></div>
        <div class="form-group"><label>Unit</label><input id="res_unit" class="form-control" placeholder="e.g. g/dL, mmol/L"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Reference Min</label><input id="res_ref_min" type="number" step="0.0001" class="form-control"></div>
        <div class="form-group"><label>Reference Max</label><input id="res_ref_max" type="number" step="0.0001" class="form-control"></div>
      </div>
      <div class="form-group"><label>Interpretation</label>
        <select id="res_interp" class="form-control">
          <option>Normal</option><option>Abnormal</option><option>Low</option><option>High</option><option>Critical Low</option><option>Critical High</option><option>Critical</option><option>Inconclusive</option>
        </select>
      </div>
    </div>
    <!-- Multi-parameter entry area -->
    <div id="multiParamArea" style="display:none;"></div>
    <div class="form-group"><label>Comments</label><textarea id="res_comments" class="form-control" rows="2" placeholder="Interpretation notes, methodology, observations..."></textarea></div>
    <div class="form-group"><label>Upload Report (PDF/Image)</label><input id="res_file" type="file" class="form-control" accept=".pdf,.jpg,.png"></div>
    <!-- Auto-flag indicator -->
    <div id="autoFlagResult" style="display:none;margin-bottom:1.5rem;"></div>
    <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveResult()"><i class="fas fa-save"></i> Save as Draft</button>
  </div>
</div>

<!-- Amend Result Modal -->
<div class="modal-bg" id="amendResultModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-edit" style="color:var(--danger);"></i> Amend Result</h3><button class="modal-close" onclick="closeModal('amendResultModal')">&times;</button></div>
    <input type="hidden" id="amend_id">
    <div class="adm-alert adm-alert-warning"><i class="fas fa-exclamation-triangle"></i> Amendments are logged in the audit trail. Doctor will be notified.</div>
    <div class="form-group"><label>Current Value</label><input id="amend_old" class="form-control" readonly></div>
    <div class="form-group"><label>New Value *</label><input id="amend_new" class="form-control"></div>
    <div class="form-group"><label>Reason for Amendment *</label><textarea id="amend_reason" class="form-control" rows="3" placeholder="Mandatory: Explain why this result needs correction..."></textarea></div>
    <button class="adm-btn adm-btn-danger" style="width:100%;" onclick="confirmAmend()"><i class="fas fa-edit"></i> Submit Amendment</button>
  </div>
</div>

<!-- Critical Value Protocol Modal -->
<div class="modal-bg" id="criticalProtocolModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-exclamation-triangle" style="color:var(--danger);"></i> ⚠️ Critical Value Protocol</h3><button class="modal-close" onclick="closeModal('criticalProtocolModal')">&times;</button></div>
    <div class="adm-alert adm-alert-danger"><i class="fas fa-exclamation-triangle"></i> <strong>This result contains a CRITICAL value.</strong><br>You must notify the requesting doctor before proceeding.</div>
    <input type="hidden" id="crit_result_id">
    <div class="form-group" style="margin-bottom:1.2rem;">
      <label><strong>How did you notify the doctor?</strong></label>
      <select id="crit_method" class="form-control">
        <option value="dashboard_notification">Dashboard Notification (auto-sent)</option>
        <option value="phone">Telephone / Phone Call</option>
        <option value="in_person">In-Person (verbally)</option>
        <option value="sms">SMS / Text Message</option>
        <option value="email">Email</option>
        <option value="pager">Pager</option>
      </select>
    </div>
    <div class="form-group">
      <label style="display:flex;align-items:center;gap:.8rem;cursor:pointer;font-size:1.3rem;">
        <input type="checkbox" id="crit_ack"> I confirm that the requesting doctor has been notified of this critical value
      </label>
    </div>
    <button class="adm-btn adm-btn-danger" style="width:100%;" onclick="confirmCriticalRelease()" id="critReleaseBtn" disabled><i class="fas fa-share"></i> Release Critical Result &amp; Log Acknowledgement</button>
  </div>
</div>

<script>
function filterResults(st,el){
  el.parentNode.querySelectorAll('.ftab').forEach(f=>f.classList.remove('active'));el.classList.add('active');
  document.querySelectorAll('#resultsTable tbody tr').forEach(r=>{r.style.display=(st==='all'||r.dataset.status===st)?'':'none';});
}
function viewResult(r){
  let h='<div style="display:grid;gap:1rem;font-size:1.3rem;">';
  h+='<div style="display:flex;justify-content:space-between;align-items:center;"><span class="adm-badge adm-badge-'+(r.result_status==='Released'?'primary':'info')+'">'+r.result_status+'</span>';
  if(r.result_interpretation){const ic={Normal:'success',Abnormal:'warning',Critical:'danger','Critical Low':'danger','Critical High':'danger'}[r.result_interpretation]||'info';h+='<span class="adm-badge adm-badge-'+ic+'">'+r.result_interpretation+'</span>';}
  h+='</div>';
  h+='<div><strong>Result ID:</strong> <span style="font-family:monospace;color:var(--role-accent);">'+r.result_id+'</span></div>';
  h+='<div><strong>Patient:</strong> '+(r.patient_name||'—')+'</div>';
  h+='<div><strong>Doctor:</strong> '+(r.doctor_name||'—')+'</div>';
  h+='<div><strong>Test:</strong> '+(r.test_name||'—')+'</div>';
  h+='<div style="background:var(--surface-2);padding:1.5rem;border-radius:var(--radius-sm);"><strong style="font-size:1.5rem;">Result:</strong> <span style="font-size:2rem;font-weight:800;color:var(--role-accent);">'+r.result_values+'</span> <span style="color:var(--text-muted);">'+(r.unit_of_measurement||'')+'</span>';
  if(r.reference_range_min!==null||r.reference_range_max!==null) h+='<br><span style="font-size:1.2rem;color:var(--text-muted);">Reference: '+(r.reference_range_min??'—')+' – '+(r.reference_range_max??'—')+'</span>';
  h+='</div>';
  if(r.technician_comments) h+='<div><strong>Comments:</strong> '+r.technician_comments+'</div>';
  if(r.validated_at) h+='<div><strong>Validated:</strong> '+r.validated_at+'</div>';
  if(r.released_at) h+='<div><strong>Released:</strong> '+r.released_at+'</div>';
  if(r.amended_reason) h+='<div class="adm-alert adm-alert-warning" style="margin:0;"><i class="fas fa-edit"></i> <strong>Amended:</strong> '+r.amended_reason+'</div>';
  if(r.report_file_path) h+='<div><a href="lab_download.php?type=result&id='+r.id+'" target="_blank" class="adm-btn adm-btn-sm adm-btn-ghost"><i class="fas fa-file-pdf"></i> Download Report</a></div>';
  h+='</div>';
  document.getElementById('orderDetailBody').innerHTML=h;openModal('orderDetailModal');
}
async function loadOrderParams(){
  const sel=document.getElementById('res_order');const opt=sel.selectedOptions[0];
  if(!opt)return;
  document.getElementById('res_test').value=opt.dataset.test||'';
  const catId=opt.dataset.catalog;
  if(catId&&catId!=='0'){
    const r=await labAction({action:'get_ref_params',test_catalog_id:catId});
    if(r.success&&r.params&&r.params.length>1){
      // Multi-parameter test
      document.getElementById('paramContainer').style.display='none';
      let h='<h4 style="font-weight:700;margin-bottom:1rem;"><i class="fas fa-list" style="color:var(--role-accent);"></i> Enter Parameters</h4>';
      r.params.forEach((p,i)=>{
        h+='<div style="background:var(--surface-2);padding:1rem;border-radius:var(--radius-sm);margin-bottom:.8rem;">';
        h+='<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;"><strong>'+p.parameter_name+'</strong><span class="adm-badge adm-badge-info">'+p.unit+'</span></div>';
        h+='<div style="display:flex;gap:1rem;align-items:center;">';
        h+='<input class="form-control param-val" data-param="'+p.parameter_name+'" data-unit="'+p.unit+'" data-min="'+p.normal_min+'" data-max="'+p.normal_max+'" data-clow="'+(p.critical_low||'')+'" data-chigh="'+(p.critical_high||'')+'" placeholder="Value" style="flex:1;" oninput="autoFlagParam(this)">';
        h+='<span style="color:var(--text-muted);font-size:1.1rem;min-width:100px;">Ref: '+p.normal_min+' – '+p.normal_max+'</span>';
        h+='<span class="param-flag" style="min-width:80px;"></span>';
        h+='</div></div>';
      });
      document.getElementById('multiParamArea').innerHTML=h;
      document.getElementById('multiParamArea').style.display='block';
    } else if(r.success&&r.params&&r.params.length===1){
      document.getElementById('paramContainer').style.display='block';
      document.getElementById('multiParamArea').style.display='none';
      const p=r.params[0];
      document.getElementById('res_unit').value=p.unit||'';
      document.getElementById('res_ref_min').value=p.normal_min||'';
      document.getElementById('res_ref_max').value=p.normal_max||'';
    }
  }
}
function autoFlagParam(el){
  const val=parseFloat(el.value);if(isNaN(val)){el.nextElementSibling.nextElementSibling.innerHTML='';return;}
  const min=parseFloat(el.dataset.min),max=parseFloat(el.dataset.max),clow=parseFloat(el.dataset.clow),chigh=parseFloat(el.dataset.chigh);
  let flag='Normal',cls='success';
  if(!isNaN(clow)&&val<clow){flag='Critical Low';cls='danger';}
  else if(!isNaN(chigh)&&val>chigh){flag='Critical High';cls='danger';}
  else if(val<min){flag='Low';cls='warning';}
  else if(val>max){flag='High';cls='warning';}
  el.nextElementSibling.nextElementSibling.innerHTML='<span class="adm-badge adm-badge-'+cls+'">'+flag+'</span>';
}
async function saveResult(){
  const orderId=document.getElementById('res_order').value;
  if(!orderId){showToast('Select an order','error');return;}
  // Check if multi-param
  const multiParams=document.querySelectorAll('.param-val');
  if(multiParams.length>0){
    let allVals=[];let hasCritical=false;
    multiParams.forEach(p=>{
      const val=p.value;const pn=p.dataset.param;const unit=p.dataset.unit;
      const min=parseFloat(p.dataset.min),max=parseFloat(p.dataset.max);
      let interp='Normal';const v=parseFloat(val);
      if(!isNaN(v)){
        if(!isNaN(parseFloat(p.dataset.clow))&&v<parseFloat(p.dataset.clow)){interp='Critical Low';hasCritical=true;}
        else if(!isNaN(parseFloat(p.dataset.chigh))&&v>parseFloat(p.dataset.chigh)){interp='Critical High';hasCritical=true;}
        else if(v<min) interp='Low';
        else if(v>max) interp='High';
      }
      allVals.push({param:pn,value:val,unit:unit,interp:interp});
    });
    const resultStr=allVals.map(v=>v.param+': '+v.value+' '+v.unit+' ('+v.interp+')').join('; ');
    const overallInterp=hasCritical?'Critical':(allVals.some(v=>v.interp!=='Normal')?'Abnormal':'Normal');
    const fd=new FormData();fd.append('action','save_result');fd.append('order_id',orderId);fd.append('test_name',document.getElementById('res_test').value);
    fd.append('result_values',resultStr);fd.append('unit','');fd.append('ref_min','');fd.append('ref_max','');
    fd.append('interpretation',overallInterp);fd.append('comments',document.getElementById('res_comments').value);
    if(document.getElementById('res_file').files[0]) fd.append('report_file',document.getElementById('res_file').files[0]);
    const r=await labAction(fd);
    showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addResultModal');setTimeout(()=>location.reload(),800);}
  }else{
    const fd=new FormData();fd.append('action','save_result');fd.append('order_id',orderId);fd.append('test_name',document.getElementById('res_test').value);
    fd.append('result_values',document.getElementById('res_value').value);fd.append('unit',document.getElementById('res_unit').value);
    fd.append('ref_min',document.getElementById('res_ref_min').value);fd.append('ref_max',document.getElementById('res_ref_max').value);
    fd.append('interpretation',document.getElementById('res_interp').value);fd.append('comments',document.getElementById('res_comments').value);
    if(document.getElementById('res_file').files[0]) fd.append('report_file',document.getElementById('res_file').files[0]);
    const r=await labAction(fd);
    showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addResultModal');setTimeout(()=>location.reload(),800);}
  }
}
async function submitForValidation(id){if(!confirmAction('Submit for validation?'))return;const r=await labAction({action:'submit_for_validation',result_id:id});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
async function validateResult(id){if(!confirmAction('Validate this result?'))return;const r=await labAction({action:'validate_result',result_id:id});showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);}
function releaseToDoctor(id){
  // Check if critical — if so, show critical protocol modal
  const row=document.querySelector('#resultsTable tr[data-status]');
  // Simple: just check if row has row-danger class
  const isCritical=document.querySelector('#resultsTable tr.row-danger button[onclick*="'+id+'"]');
  if(isCritical){document.getElementById('crit_result_id').value=id;openModal('criticalProtocolModal');return;}
  if(!confirmAction('Release this result to the requesting doctor?'))return;
  doRelease(id);
}
document.getElementById('crit_ack')?.addEventListener('change',function(){document.getElementById('critReleaseBtn').disabled=!this.checked;});
async function confirmCriticalRelease(){
  const id=document.getElementById('crit_result_id').value;
  const method=document.getElementById('crit_method')?.value||'dashboard_notification';
  // Step 1: Log the critical value acknowledgement in audit trail
  const ack=await labAction({action:'acknowledge_critical',result_id:id,notification_method:method});
  if(!ack.success){showToast(ack.message||'Acknowledgement failed','error');return;}
  showToast('Acknowledgement logged. Releasing result…','success');
  // Step 2: Release to doctor
  await doRelease(id);
  closeModal('criticalProtocolModal');
}
async function doRelease(id){
  const r=await labAction({action:'release_to_doctor',result_id:id});
  showToast(r.message,r.success?'success':'error');if(r.success)setTimeout(()=>location.reload(),800);
}
function openAmendModal(id,oldVal){
  document.getElementById('amend_id').value=id;document.getElementById('amend_old').value=oldVal;document.getElementById('amend_new').value='';document.getElementById('amend_reason').value='';openModal('amendResultModal');
}
async function confirmAmend(){
  const id=document.getElementById('amend_id').value;const nv=document.getElementById('amend_new').value;const reason=document.getElementById('amend_reason').value;
  if(!nv||!reason){showToast('New value and reason are required','error');return;}
  const r=await labAction({action:'amend_result',result_id:id,new_value:nv,reason:reason});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('amendResultModal');setTimeout(()=>location.reload(),800);}
}
</script>
