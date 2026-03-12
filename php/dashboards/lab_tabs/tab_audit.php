<!-- ═══════════════ MODULE 12: AUDIT TRAIL SYSTEM ═══════════════ -->
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-shield-halved" style="color:var(--role-accent);margin-right:.6rem;"></i> Audit Trail</h1>
    <p>Complete read-only log of every action — cannot be edited or deleted</p>
  </div>
  <div style="display:flex;gap:1rem;">
    <button class="adm-btn adm-btn-primary" onclick="exportAudit('pdf')"><i class="fas fa-file-pdf"></i> Export PDF</button>
    <button class="adm-btn adm-btn-success" onclick="exportAudit('csv')"><i class="fas fa-file-csv"></i> Export CSV</button>
  </div>
</div>

<!-- Filters -->
<div class="adm-card" style="margin-bottom:2rem;">
  <div class="adm-card-body">
    <div class="form-row" style="grid-template-columns:1fr 1fr 1fr 1fr;">
      <div class="form-group" style="margin:0;">
        <label>Action Type</label>
        <select id="auditFilterAction" class="form-control" onchange="filterAudit()">
          <option value="">All Actions</option>
          <?php $action_types=[]; foreach($audit_trail as $at){$action_types[$at['action_type']]=1;} ksort($action_types); foreach(array_keys($action_types) as $act):?>
          <option value="<?=e($act)?>"><?=e(ucwords(str_replace('_',' ',$act)))?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div class="form-group" style="margin:0;">
        <label>Module</label>
        <select id="auditFilterModule" class="form-control" onchange="filterAudit()">
          <option value="">All Modules</option>
          <?php $modules=[]; foreach($audit_trail as $at){$modules[$at['module_affected']??'']=1;} ksort($modules); foreach(array_keys($modules) as $mod):if($mod):?>
          <option value="<?=e($mod)?>"><?=e(ucfirst($mod))?></option>
          <?php endif; endforeach;?>
        </select>
      </div>
      <div class="form-group" style="margin:0;"><label>From</label><input id="auditDateFrom" type="date" class="form-control" value="<?=$month_start?>" onchange="filterAudit()"></div>
      <div class="form-group" style="margin:0;"><label>To</label><input id="auditDateTo" type="date" class="form-control" value="<?=$today?>" onchange="filterAudit()"></div>
    </div>
  </div>
</div>

<!-- Audit Summary -->
<div class="adm-summary-strip">
  <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--role-accent);"><?=count($audit_trail)?></div><div class="adm-mini-card-label">Total Entries</div></div>
  <?php
  $today_entries=0; $create_count=0; $update_count=0; $delete_count=0;
  foreach($audit_trail as $at){
    if(date('Y-m-d',strtotime($at['created_at']))===$today) $today_entries++;
    if(strpos($at['action_type'],'add')!==false||strpos($at['action_type'],'create')!==false||strpos($at['action_type'],'save')!==false) $create_count++;
    if(strpos($at['action_type'],'update')!==false||strpos($at['action_type'],'edit')!==false||strpos($at['action_type'],'validate')!==false) $update_count++;
    if(strpos($at['action_type'],'delete')!==false||strpos($at['action_type'],'reject')!==false) $delete_count++;
  }?>
  <div class="adm-mini-card"><div class="adm-mini-card-num green"><?=$today_entries?></div><div class="adm-mini-card-label">Today</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num" style="color:var(--info);"><?=$create_count?></div><div class="adm-mini-card-label">Created</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num orange"><?=$update_count?></div><div class="adm-mini-card-label">Updated</div></div>
  <div class="adm-mini-card"><div class="adm-mini-card-num red"><?=$delete_count?></div><div class="adm-mini-card-label">Deleted/Rejected</div></div>
</div>

<!-- Audit Table -->
<div class="adm-card">
  <div class="adm-card-body" style="padding:0;">
    <div class="adm-table-wrap">
      <table class="adm-table" id="auditTable">
        <thead><tr>
          <th>Timestamp</th><th>Technician</th><th>Action</th><th>Module</th><th>Record ID</th><th>Details</th><th>IP Address</th>
        </tr></thead>
        <tbody>
        <?php if(empty($audit_trail)):?>
          <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--text-muted);"><i class="fas fa-shield-halved" style="font-size:2.5rem;display:block;margin-bottom:1rem;"></i>No audit entries</td></tr>
        <?php else: foreach($audit_trail as $at):
          $action_colors=['accept'=>'success','create'=>'success','add'=>'success','save'=>'success','log'=>'info','update'=>'warning','edit'=>'warning','validate'=>'primary','release'=>'primary','reject'=>'danger','delete'=>'danger','change'=>'warning'];
          $badge_color='info';
          foreach($action_colors as $key=>$color){if(stripos($at['action_type'],$key)!==false){$badge_color=$color;break;}}
        ?>
          <tr data-action="<?=e($at['action_type'])?>" data-module="<?=e($at['module_affected']??'')?>" data-date="<?=date('Y-m-d',strtotime($at['created_at']))?>">
            <td style="white-space:nowrap;font-family:monospace;font-size:1.15rem;"><?=date('d M Y H:i:s',strtotime($at['created_at']))?></td>
            <td><strong><?=e($at['tech_name']??'System')?></strong></td>
            <td><span class="adm-badge adm-badge-<?=$badge_color?>"><?=e(ucwords(str_replace('_',' ',$at['action_type'])))?></span></td>
            <td><?=e(ucfirst($at['module_affected']??'—'))?></td>
            <td style="font-family:monospace;"><?=e($at['record_id']??'—')?></td>
            <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;">
              <?php if($at['old_value']||$at['new_value']):?>
                <span style="color:var(--danger);text-decoration:line-through;font-size:1.1rem;"><?=e(substr($at['old_value']??'',0,60))?></span>
                <?php if($at['new_value']):?> → <span style="color:var(--success);font-size:1.1rem;"><?=e(substr($at['new_value']??'',0,60))?></span><?php endif;?>
              <?php else:?>—<?php endif;?>
            </td>
            <td style="font-family:monospace;font-size:1.1rem;color:var(--text-muted);"><?=e($at['ip_address']??'—')?></td>
          </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function filterAudit(){
  const action=document.getElementById('auditFilterAction').value;
  const module=document.getElementById('auditFilterModule').value;
  const from=document.getElementById('auditDateFrom').value;
  const to=document.getElementById('auditDateTo').value;
  document.querySelectorAll('#auditTable tbody tr').forEach(r=>{
    const matchA=!action||r.dataset.action===action;
    const matchM=!module||r.dataset.module===module;
    const d=r.dataset.date;
    const matchD=(!from||d>=from)&&(!to||d<=to);
    r.style.display=(matchA&&matchM&&matchD)?'':'none';
  });
}
async function exportAudit(fmt){
  const r=await labAction({action:'export_audit_trail',format:fmt,from:document.getElementById('auditDateFrom').value,to:document.getElementById('auditDateTo').value,action_type:document.getElementById('auditFilterAction').value,module:document.getElementById('auditFilterModule').value});
  showToast(r.message,r.success?'success':'error');
  if(r.success&&r.file_path) window.open(BASE+'/'+r.file_path,'_blank');
}
</script>
