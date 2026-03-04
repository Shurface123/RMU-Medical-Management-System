<!-- ═══════════════════════════════════════════════════════════
     MODULE 6: TASK & SHIFT MANAGEMENT — tab_tasks.php
     ═══════════════════════════════════════════════════════════ -->
<?php
$my_tasks = dbSelect($conn,
    "SELECT nt.*, u.name AS patient_name, ua.name AS assigned_by_name
     FROM nurse_tasks nt
     LEFT JOIN patients p ON nt.patient_id=p.id LEFT JOIN users u ON p.user_id=u.id
     LEFT JOIN users ua ON nt.assigned_by=ua.id
     WHERE nt.nurse_id=?
     ORDER BY FIELD(nt.status,'Overdue','Pending','In Progress','Completed','Cancelled'),
              FIELD(nt.priority,'Urgent','High','Medium','Low'), nt.due_time ASC
     LIMIT 100","i",[$nurse_pk]);

$my_shifts = dbSelect($conn,
    "SELECT * FROM nurse_shifts WHERE nurse_id=? AND shift_date >= DATE_SUB(?,INTERVAL 7 DAY) ORDER BY shift_date ASC, start_time ASC","is",[$nurse_pk,$today]);

$shift_today = dbRow($conn,"SELECT * FROM nurse_shifts WHERE nurse_id=? AND shift_date=? LIMIT 1","is",[$nurse_pk,$today]);

$handovers = dbSelect($conn,
    "SELECT sh.*, un.name AS outgoing_name, ui.name AS incoming_name
     FROM shift_handover sh
     LEFT JOIN nurses no2 ON sh.outgoing_nurse_id=no2.id LEFT JOIN users un ON no2.user_id=un.id
     LEFT JOIN nurses ni ON sh.incoming_nurse_id=ni.id LEFT JOIN users ui ON ni.user_id=ui.id
     WHERE sh.outgoing_nurse_id=? OR sh.incoming_nurse_id=?
     ORDER BY sh.submitted_at DESC LIMIT 20","ii",[$nurse_pk,$nurse_pk]);

$priority_colors = ['Urgent'=>'danger','High'=>'warning','Medium'=>'info','Low'=>'success'];
$status_colors   = ['Pending'=>'warning','In Progress'=>'primary','Completed'=>'success','Overdue'=>'danger','Cancelled'=>'secondary'];
?>
<div id="sec-tasks" class="dash-section">
  <div class="sec-header">
    <h2><i class="fas fa-clipboard-list"></i> Tasks & Shifts</h2>
    <div style="display:flex;gap:.8rem;">
      <?php if($shift_today && !$shift_today['handover_submitted']):?>
        <button class="btn btn-primary" onclick="openModal('handoverModal')"><i class="fas fa-exchange-alt"></i> Submit Handover</button>
      <?php endif;?>
    </div>
  </div>

  <!-- ── Filter Tabs ── -->
  <div class="filter-tabs">
    <span class="ftab active" onclick="filterTasks('all',this)">All Tasks</span>
    <span class="ftab" onclick="filterTasks('Pending',this)">⏳ Pending</span>
    <span class="ftab" onclick="filterTasks('In Progress',this)">🔄 In Progress</span>
    <span class="ftab" onclick="filterTasks('Overdue',this)">🔴 Overdue</span>
    <span class="ftab" onclick="filterTasks('Completed',this)">✅ Completed</span>
  </div>

  <!-- ── Task List ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-tasks" style="color:var(--role-accent);"></i> My Tasks</h3>
    <div class="table-responsive"><table class="adm-table" id="tasksTable"><thead><tr>
      <th>Task</th><th>Patient</th><th>Priority</th><th>Due</th><th>Assigned By</th><th>Status</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($my_tasks)):?>
      <tr><td colspan="7" class="text-center text-muted" style="padding:3rem;">No tasks assigned</td></tr>
    <?php else: foreach($my_tasks as $t):?>
      <tr data-task-status="<?=e($t['status'])?>" <?=$t['status']==='Overdue'?'style="border-left:3px solid var(--danger);"':''?>>
        <td><strong><?=e($t['task_title'])?></strong><?php if($t['task_description']):?><br><small class="text-muted"><?=e(substr($t['task_description'],0,80))?></small><?php endif;?></td>
        <td><?=e($t['patient_name']??'General')?></td>
        <td><span class="badge badge-<?=$priority_colors[$t['priority']]??'secondary'?>"><?=e($t['priority'])?></span></td>
        <td><?=$t['due_time']?date('d M h:i A',strtotime($t['due_time'])):'—'?></td>
        <td><?=e($t['assigned_by_name']??'—')?><br><small class="text-muted"><?=e($t['assigned_by_role']??'')?></small></td>
        <td><span class="badge badge-<?=$status_colors[$t['status']]??'secondary'?>"><?=e($t['status'])?></span></td>
        <td class="action-btns">
          <?php if(in_array($t['status'],['Pending','Overdue'])):?>
            <button class="btn btn-xs btn-primary" onclick="updateTask(<?=$t['id']?>,'In Progress')" title="Start"><i class="fas fa-play"></i></button>
          <?php endif;?>
          <?php if(in_array($t['status'],['Pending','In Progress','Overdue'])):?>
            <button class="btn btn-xs btn-success" onclick="completeTask(<?=$t['id']?>)" title="Complete"><i class="fas fa-check"></i></button>
          <?php endif;?>
        </td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>

  <!-- ── Weekly Shift Schedule ── -->
  <div class="info-card" style="margin-bottom:1.5rem;">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-calendar-week" style="color:var(--primary);"></i> Shift Schedule (This Week)</h3>
    <?php if(empty($my_shifts)):?>
      <p class="text-center text-muted" style="padding:2rem;">No shifts scheduled this week</p>
    <?php else:?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
      <?php foreach($my_shifts as $sh):
        $is_today = ($sh['shift_date'] === $today);
        $shift_colors = ['Morning'=>'#f39c12','Afternoon'=>'#3498db','Night'=>'#8e44ad'];
        $bg_col = $shift_colors[$sh['shift_type']] ?? '#95a5a6';
      ?>
        <div style="background:<?=$bg_col?>15;border:1.5px solid <?=$bg_col?>;border-radius:var(--radius-sm);padding:1.2rem;<?=$is_today?'box-shadow:0 0 0 2px '.$bg_col.';':''?>">
          <div style="font-weight:700;font-size:1.3rem;color:<?=$bg_col?>;"><?=date('D, d M',strtotime($sh['shift_date']))?></div>
          <div style="font-size:1.2rem;margin-top:.3rem;"><i class="fas fa-clock"></i> <?=date('h:i A',strtotime($sh['start_time']))?> – <?=date('h:i A',strtotime($sh['end_time']))?></div>
          <div style="margin-top:.4rem;"><span class="badge" style="background:<?=$bg_col?>;color:#fff;"><?=e($sh['shift_type'])?></span></div>
          <?php if($sh['ward_assigned']):?><div style="font-size:1.1rem;margin-top:.3rem;color:var(--text-secondary);"><i class="fas fa-hospital"></i> <?=e($sh['ward_assigned'])?></div><?php endif;?>
          <div style="margin-top:.4rem;"><span class="badge badge-<?=($sh['status']==='Completed')?'success':(($sh['status']==='Active')?'primary':'secondary')?>"><?=e($sh['status'])?></span></div>
        </div>
      <?php endforeach;?>
    </div>
    <?php endif;?>
  </div>

  <!-- ── Handover History ── -->
  <div class="info-card">
    <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:1rem;"><i class="fas fa-exchange-alt" style="color:var(--warning);"></i> Shift Handovers</h3>
    <div class="table-responsive"><table class="data-table"><thead><tr>
      <th>Date</th><th>Outgoing Nurse</th><th>Incoming Nurse</th><th>Ward</th><th>Acknowledged</th><th>Actions</th>
    </tr></thead><tbody>
    <?php if(empty($handovers)):?>
      <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">No handovers recorded</td></tr>
    <?php else: foreach($handovers as $ho):?>
      <tr>
        <td><?=date('d M h:i A',strtotime($ho['submitted_at']))?></td>
        <td><?=e($ho['outgoing_name']??'—')?></td>
        <td><?=e($ho['incoming_name']??'Pending')?></td>
        <td><?=e($ho['ward']??'—')?></td>
        <td><?=$ho['acknowledged']?'<span class="badge badge-success"><i class="fas fa-check"></i> Yes</span>':'<span class="badge badge-warning">Pending</span>'?></td>
        <td>
          <button class="btn btn-xs btn-outline" onclick="viewHandover(<?=$ho['id']?>)"><i class="fas fa-eye"></i></button>
          <?php if(!$ho['acknowledged'] && $ho['incoming_nurse_id']==$nurse_pk):?>
            <button class="btn btn-xs btn-success" onclick="acknowledgeHandover(<?=$ho['id']?>)"><i class="fas fa-check-double"></i> Ack</button>
          <?php endif;?>
        </td>
      </tr>
    <?php endforeach; endif;?></tbody></table></div>
  </div>
</div>

<!-- ═══════ HANDOVER MODAL ═══════ -->
<div class="modal-bg" id="handoverModal">
  <div class="modal-box wide" style="max-width:800px;">
    <div class="modal-header"><h3><i class="fas fa-exchange-alt" style="color:var(--role-accent);"></i> Shift Handover</h3><button class="modal-close" onclick="closeModal('handoverModal')"><i class="fas fa-times"></i></button></div>
    <p style="font-size:1.2rem;color:var(--text-secondary);margin-bottom:1.5rem;">Complete your end-of-shift handover. System will auto-populate your shift activities.</p>
    <div class="form-group"><label>Incoming Nurse</label>
      <select id="ho_incoming" class="form-control"><option value="">Select Incoming Nurse</option>
        <?php $other_nurses = dbSelect($conn,"SELECT n.id, n.full_name FROM nurses n WHERE n.id!=? AND n.status='Active'","i",[$nurse_pk]);
        foreach($other_nurses as $on):?><option value="<?=$on['id']?>"><?=e($on['full_name'])?></option><?php endforeach;?>
      </select>
    </div>
    <div class="form-group"><label>Patient Summaries (auto-filled, editable)</label>
      <textarea id="ho_patients" class="form-control" rows="5" placeholder="Loading shift summary..."><?php
        $shift_patients = dbSelect($conn,"SELECT u.name FROM bed_assignments ba JOIN patients p ON ba.patient_id=p.id JOIN users u ON p.user_id=u.id WHERE ba.status='Active' LIMIT 20");
        $summary_lines = [];
        foreach($shift_patients as $sp) $summary_lines[] = "• " . $sp['name'] . ": Stable (update as needed)";
        echo implode("\n", $summary_lines);
      ?></textarea>
    </div>
    <div class="form-group"><label>Pending Incomplete Tasks</label>
      <textarea id="ho_pending" class="form-control" rows="3" readonly><?php
        $pending_t = dbSelect($conn,"SELECT task_title, priority FROM nurse_tasks WHERE nurse_id=? AND status IN('Pending','In Progress') ORDER BY FIELD(priority,'Urgent','High','Medium','Low')","i",[$nurse_pk]);
        $plines=[];foreach($pending_t as $pt) $plines[]="• [{$pt['priority']}] {$pt['task_title']}";
        echo implode("\n",$plines)?:'No pending tasks';
      ?></textarea>
    </div>
    <div class="form-group"><label>Critical Patients to Watch</label><textarea id="ho_critical" class="form-control" rows="2" placeholder="Note any patients requiring close monitoring..."></textarea></div>
    <div class="form-group"><label>Additional Handover Notes</label><textarea id="ho_notes" class="form-control" rows="3" placeholder="Any other important information for the incoming nurse..."></textarea></div>
    <button class="btn btn-primary" onclick="submitHandover()" style="width:100%;"><i class="fas fa-paper-plane"></i> Submit Handover</button>
  </div>
</div>

<!-- ═══════ TASK COMPLETION MODAL ═══════ -->
<div class="modal-bg" id="taskCompleteModal">
  <div class="modal-box">
    <div class="modal-header"><h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Complete Task</h3><button class="modal-close" onclick="closeModal('taskCompleteModal')"><i class="fas fa-times"></i></button></div>
    <input type="hidden" id="tc_task_id">
    <div class="form-group"><label>Completion Notes (optional)</label><textarea id="tc_notes" class="form-control" rows="3" placeholder="Describe how the task was completed..."></textarea></div>
    <button class="btn btn-success" onclick="submitTaskComplete()" style="width:100%;"><i class="fas fa-check"></i> Mark Complete</button>
  </div>
</div>

<!-- ═══════ VIEW HANDOVER MODAL ═══════ -->
<div class="modal-bg" id="viewHandoverModal">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-exchange-alt" style="color:var(--role-accent);"></i> Handover Details</h3><button class="modal-close" onclick="closeModal('viewHandoverModal')"><i class="fas fa-times"></i></button></div>
    <div id="viewHandoverContent"><p class="text-center text-muted">Loading...</p></div>
  </div>
</div>

<script>
function filterTasks(status,el){
  document.querySelectorAll('#sec-tasks .ftab').forEach(f=>f.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('#tasksTable tbody tr').forEach(row=>{
    if(status==='all') row.style.display='';
    else row.style.display=(row.dataset.taskStatus===status)?'':'none';
  });
}

async function updateTask(taskId,newStatus){
  const r=await nurseAction({action:'update_task_status',task_id:taskId,status:newStatus});
  showToast(r.message||'Updated',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1000);
}

function completeTask(taskId){
  document.getElementById('tc_task_id').value=taskId;
  document.getElementById('tc_notes').value='';
  openModal('taskCompleteModal');
}

async function submitTaskComplete(){
  const r=await nurseAction({action:'complete_task',task_id:document.getElementById('tc_task_id').value,
    notes:document.getElementById('tc_notes').value});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success){closeModal('taskCompleteModal');setTimeout(()=>location.reload(),1000);}
}

async function submitHandover(){
  const r=await nurseAction({action:'submit_handover',
    incoming_nurse_id:document.getElementById('ho_incoming').value,
    patient_summaries:document.getElementById('ho_patients').value,
    pending_tasks:document.getElementById('ho_pending').value,
    critical_patients:document.getElementById('ho_critical').value,
    handover_notes:document.getElementById('ho_notes').value});
  showToast(r.message||'Submitted',r.success?'success':'error');
  if(r.success){closeModal('handoverModal');setTimeout(()=>location.reload(),1200);}
}

async function acknowledgeHandover(hoId){
  if(!confirmAction('Acknowledge this handover?')) return;
  const r=await nurseAction({action:'acknowledge_handover',handover_id:hoId});
  showToast(r.message||'Done',r.success?'success':'error');
  if(r.success) setTimeout(()=>location.reload(),1000);
}

async function viewHandover(hoId){
  openModal('viewHandoverModal');
  const r=await nurseAction({action:'get_handover_detail',handover_id:hoId});
  if(!r.success){document.getElementById('viewHandoverContent').innerHTML='<p class="text-center" style="color:var(--danger);">Error</p>';return;}
  const h=r.data;
  document.getElementById('viewHandoverContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
      <div><p><strong>Outgoing:</strong> ${h.outgoing_name||'—'}</p><p><strong>Incoming:</strong> ${h.incoming_name||'Pending'}</p></div>
      <div><p><strong>Ward:</strong> ${h.ward||'—'}</p><p><strong>Submitted:</strong> ${h.submitted_at||'—'}</p></div>
    </div>
    <div style="margin-bottom:1rem;"><strong>Patient Summaries:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:var(--radius-sm);white-space:pre-wrap;margin-top:.5rem;">${h.patient_summaries||'—'}</div></div>
    <div style="margin-bottom:1rem;"><strong>Pending Tasks:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:var(--radius-sm);white-space:pre-wrap;margin-top:.5rem;">${h.pending_tasks||'—'}</div></div>
    <div style="margin-bottom:1rem;"><strong>Critical Patients:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:var(--radius-sm);white-space:pre-wrap;margin-top:.5rem;">${h.critical_patients||'—'}</div></div>
    <div><strong>Notes:</strong><div style="background:var(--surface-2);padding:1rem;border-radius:var(--radius-sm);white-space:pre-wrap;margin-top:.5rem;">${h.handover_notes||'—'}</div></div>`;
}
</script>
