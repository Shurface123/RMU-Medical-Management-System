<!-- ═══════════════ MODULE 8: QUALITY CONTROL ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-check-double" style="color:var(--role-accent);margin-right:.6rem;"></i> Quality Control</h1>
    <p>QC checks, Levy-Jennings tracking, and compliance monitoring</p>
  </div>
  <button class="adm-btn adm-btn-primary" onclick="openModal('addQCModal')"><i class="fas fa-plus-circle"></i> Run QC Check</button>
</div>

<div class="adm-summary-strip">
  <?php
  $qc_pass=0;$qc_fail=0;$qc_today=0;
  foreach($qc_records as $qc){if($qc['qc_result']==='PASS')$qc_pass++;else $qc_fail++;if(date('Y-m-d',strtotime($qc['qc_date']))===$today)$qc_today++;}
  ?>
  <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--role-accent);"><?=count($qc_records)?></div><div class="adm-mini-card-label">Total QC Runs</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$qc_pass?></div><div class="adm-mini-card-label">Passed</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num red"><?=$qc_fail?></div><div class="adm-mini-card-label">Failed</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$qc_today?></div><div class="adm-mini-card-label">Today</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--info);"><?=count($qc_records)>0?round(($qc_pass/count($qc_records))*100,1):0?>%</div><div class="adm-mini-card-label">Pass Rate</div></div>
</div>

<div class="filter-tabs">
  <span class="ftab active" onclick="filterTable('qcTable','all',this)">All</span>
  <span class="ftab" onclick="filterTable('qcTable','PASS',this)">Pass (<?=$qc_pass?>)</span>
  <span class="ftab" onclick="filterTable('qcTable','FAIL',this)">Fail (<?=$qc_fail?>)</span>
</div>

<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="qcTable">
        <thead><tr><th>Date</th><th>Test</th><th>Equipment</th><th>QC Level</th><th>Expected</th><th>Actual</th><th>SD</th><th>Result</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($qc_records)):?>
          <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-check-double" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No QC checks recorded</td></tr>
        <?php else: foreach($qc_records as $qc):?>
          <tr class="<?=$qc['qc_result']==='FAIL'?'row-danger':''?>" data-status="<?=e($qc['qc_result'])?>">
            <td style="white-space:nowrap;"><?=date('d M Y',strtotime($qc['qc_date']))?></td>
            <td><?=e($qc['test_name']??'—')?></td>
            <td><?=e($qc['equip_name']??'—')?></td>
            <td><span class="adm-badge adm-badge-<?=($qc['qc_level']??'')==='High'?'danger':(($qc['qc_level']??'')==='Low'?'warning':'info')?>"><?=e($qc['qc_level']??'Normal')?></span></td>
            <td style="font-family:monospace;"><?=e($qc['expected_value']??'—')?></td>
            <td style="font-family:monospace;font-weight:700;"><?=e($qc['actual_value']??'—')?></td>
            <td><?=e($qc['standard_deviation']??'—')?></td>
            <td><span class="adm-badge adm-badge-<?=$qc['qc_result']==='PASS'?'success':'danger'?>"><?=e($qc['qc_result'])?></span></td>
            <td class="adm-table-actions">
              <button class="adm-btn adm-btn-sm adm-btn-ghost" onclick='viewQCDetail(<?=json_encode($qc)?>)' title="Details"><i class="fas fa-eye"></i></button>
              <?php if($qc['qc_result']==='FAIL'):?><button class="adm-btn adm-btn-sm adm-btn-danger" onclick="logCorrectiveAction(<?=$qc['id']?>)" title="Corrective Action"><i class="fas fa-exclamation-circle"></i></button><?php endif;?>
            </td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add QC Modal -->
<div class="modal-bg" id="addQCModal"><div class="modal-box wide">
  <div class="modal-header"><h3><i class="fas fa-check-double"></i> Run QC Check</h3><button class="modal-close" onclick="closeModal('addQCModal')">&times;</button></div>
  <div class="form-row">
    <div class="form-group"><label>Test *</label><select id="qc_test" class="form-control"><option value="">Select test...</option><?php foreach($test_catalog as $tc):?><option value="<?=$tc['id']?>"><?=e($tc['test_name'])?></option><?php endforeach;?></select></div>
    <div class="form-group"><label>Equipment</label><select id="qc_equip" class="form-control"><option value="">Select equipment...</option><?php foreach($equipment as $eq):?><option value="<?=$eq['id']?>"><?=e($eq['name'])?></option><?php endforeach;?></select></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>QC Level</label><select id="qc_level" class="form-control"><option>Low</option><option>Normal</option><option>High</option></select></div>
    <div class="form-group"><label>QC Material</label><input id="qc_material" class="form-control" placeholder="Control material name"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Expected Value</label><input id="qc_expected" type="number" step="0.001" class="form-control"></div>
    <div class="form-group"><label>Actual Value *</label><input id="qc_actual" type="number" step="0.001" class="form-control"></div>
  </div>
  <div class="form-row">
    <div class="form-group"><label>Standard Deviation</label><input id="qc_sd" type="number" step="0.001" class="form-control"></div>
    <div class="form-group"><label>Lot Number</label><input id="qc_lot" class="form-control"></div>
  </div>
  <div class="form-group"><label>Remarks</label><textarea id="qc_remarks" class="form-control" rows="2"></textarea></div>
  <button class="adm-btn adm-btn-primary" style="width:100%;" onclick="saveQC()"><i class="fas fa-save"></i> Save QC Result</button>
</div></div>

<script>
function viewQCDetail(qc){
  let h='<div style="font-size:1.3rem;display:grid;gap:.8rem;">';
  h+='<div style="display:flex;justify-content:space-between;"><strong>'+qc.qc_date+'</strong><span class="adm-badge adm-badge-'+(qc.qc_result==='PASS'?'success':'danger')+'">'+qc.qc_result+'</span></div>';
  h+='<div><strong>Test:</strong> '+(qc.test_name||'—')+'</div><div><strong>Equipment:</strong> '+(qc.equip_name||'—')+'</div>';
  h+='<div><strong>Level:</strong> '+qc.qc_level+'</div>';
  h+='<div style="background:var(--surface-2);padding:1.2rem;border-radius:var(--radius-sm);">';
  h+='<strong>Expected:</strong> '+(qc.expected_value||'—')+' | <strong>Actual:</strong> <span style="font-weight:800;">'+qc.actual_value+'</span> | <strong>SD:</strong> '+(qc.standard_deviation||'—')+'</div>';
  if(qc.remarks) h+='<div><strong>Remarks:</strong> '+qc.remarks+'</div>';
  if(qc.corrective_action) h+='<div class="adm-alert adm-alert-warning" style="margin:0;"><i class="fas fa-wrench"></i> <strong>Corrective Action:</strong> '+qc.corrective_action+'</div>';
  h+='</div>';document.getElementById('orderDetailBody').innerHTML=h;openModal('orderDetailModal');
}
async function saveQC(){
  if(!document.getElementById('qc_actual').value){showToast('Actual value is required','error');return;}
  const exp=parseFloat(document.getElementById('qc_expected').value);const act=parseFloat(document.getElementById('qc_actual').value);const sd=parseFloat(document.getElementById('qc_sd').value);
  let result='PASS';
  if(exp&&sd&&Math.abs(act-exp)>2*sd) result='FAIL';
  const r=await labAction({action:'save_qc',test_catalog_id:document.getElementById('qc_test').value,equipment_id:document.getElementById('qc_equip').value,qc_level:document.getElementById('qc_level').value,qc_material:document.getElementById('qc_material').value,expected_value:document.getElementById('qc_expected').value,actual_value:document.getElementById('qc_actual').value,standard_deviation:document.getElementById('qc_sd').value,lot_number:document.getElementById('qc_lot').value,remarks:document.getElementById('qc_remarks').value,qc_result:result});
  showToast(r.message,r.success?'success':'error');if(r.success){closeModal('addQCModal');setTimeout(()=>location.reload(),800);}
}
async function logCorrectiveAction(id){const action=prompt('Describe the corrective action taken:');if(!action)return;const r=await labAction({action:'log_corrective_action',qc_id:id,corrective_action:action});showToast(r.message,r.success?'success':'error');if(r.success) setTimeout(()=>location.reload(),800);}
</script>
