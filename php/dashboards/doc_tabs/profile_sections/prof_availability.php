<?php // SECTION E: Availability Schedule ?>
<div id="prof-availability" class="prof-section" style="display:none;">
  <div class="adm-card" style="margin-bottom:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-calendar-alt"></i> Weekly Availability</h3></div>
    <div style="padding:2rem;">
      <form id="formAvailSchedule" onsubmit="saveAvailSchedule(event)">
        <div style="display:grid;gap:1rem;">
        <?php
        $days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        foreach($days as $d):
          $av=$availability[$d]??['is_available'=>0,'start_time'=>'08:00:00','end_time'=>'17:00:00','max_appointments'=>20,'slot_duration_min'=>30];
          $checked=$av['is_available']?'checked':'';
        ?>
        <div style="display:grid;grid-template-columns:140px 50px 1fr 1fr 1fr 1fr;gap:.8rem;align-items:center;padding:.8rem 1rem;background:var(--surface-2);border-radius:10px;">
          <span style="font-weight:600;font-size:1.25rem;"><?=$d?></span>
          <label style="position:relative;width:42px;height:24px;cursor:pointer;">
            <input type="checkbox" class="avail-toggle" data-day="<?=$d?>" <?=$checked?> style="opacity:0;width:0;height:0;position:absolute;" onchange="this.closest('div').style.opacity=this.checked?1:.5">
            <span class="notif-slider"></span>
          </label>
          <div><label style="font-size:1.05rem;color:var(--text-muted);">From</label><input type="time" class="form-control avail-start" data-day="<?=$d?>" value="<?=substr($av['start_time'],0,5)?>" style="font-size:1.15rem;padding:.4rem .6rem;"></div>
          <div><label style="font-size:1.05rem;color:var(--text-muted);">To</label><input type="time" class="form-control avail-end" data-day="<?=$d?>" value="<?=substr($av['end_time'],0,5)?>" style="font-size:1.15rem;padding:.4rem .6rem;"></div>
          <div><label style="font-size:1.05rem;color:var(--text-muted);">Max Appts</label><input type="number" class="form-control avail-max" data-day="<?=$d?>" value="<?=(int)$av['max_appointments']?>" min="1" max="100" style="font-size:1.15rem;padding:.4rem .6rem;"></div>
          <div><label style="font-size:1.05rem;color:var(--text-muted);">Slot (min)</label><input type="number" class="form-control avail-slot" data-day="<?=$d?>" value="<?=(int)$av['slot_duration_min']?>" min="10" max="120" step="5" style="font-size:1.15rem;padding:.4rem .6rem;"></div>
        </div>
        <?php endforeach;?>
        </div>
        <button type="submit" class="adm-btn adm-btn-success" style="width:100%;justify-content:center;margin-top:1.5rem;"><i class="fas fa-save"></i> Save Availability</button>
      </form>
    </div>
  </div>

  <!-- Leave Exceptions -->
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-plane-departure"></i> Leave / Unavailable Dates</h3>
      <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="document.getElementById('addLeaveForm').style.display='block'"><i class="fas fa-plus"></i> Add</button>
    </div>
    <div style="padding:1.5rem;">
      <div id="addLeaveForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
        <form onsubmit="addLeave(event)">
          <div class="form-row">
            <div class="form-group"><label>Date *</label><input type="date" name="exception_date" class="form-control" required min="<?=date('Y-m-d')?>"></div>
            <div class="form-group"><label>Reason</label><input type="text" name="reason" class="form-control" placeholder="e.g. Conference, Personal leave"></div>
          </div>
          <div style="display:flex;gap:.8rem;"><button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Add</button><button type="button" class="adm-btn" onclick="this.closest('div[id]').style.display='none'">Cancel</button></div>
        </form>
      </div>
      <div id="leaveList">
      <?php if(empty($leave_exceptions)):?><p style="color:var(--text-muted);text-align:center;padding:1rem;">No leave dates set.</p>
      <?php else: foreach($leave_exceptions as $le):?>
        <div class="file-row" data-id="<?=$le['id']?>">
          <div class="file-icon-box" style="background:var(--warning);"><i class="fas fa-calendar-xmark"></i></div>
          <div style="flex:1;"><strong><?=date('D, d M Y',strtotime($le['exception_date']))?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($le['reason']??'No reason')?></span></div>
          <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="delLeave(<?=$le['id']?>,this)"><i class="fas fa-trash"></i></button>
        </div>
      <?php endforeach; endif;?>
      </div>
    </div>
  </div>
</div>
<script>
async function saveAvailSchedule(e){
  e.preventDefault();
  const days=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  const schedule=days.map(d=>({
    day:d,
    is_available:document.querySelector(`.avail-toggle[data-day="${d}"]`).checked?1:0,
    start_time:document.querySelector(`.avail-start[data-day="${d}"]`).value,
    end_time:document.querySelector(`.avail-end[data-day="${d}"]`).value,
    max_appointments:+document.querySelector(`.avail-max[data-day="${d}"]`).value,
    slot_duration_min:+document.querySelector(`.avail-slot[data-day="${d}"]`).value
  }));
  const res=await profAction({action:'save_availability',schedule});
  if(res.success)toast('Availability saved!');else toast(res.message||'Error','danger');
}
async function addLeave(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'add_leave_exception'};
  fd.forEach((v,k)=>data[k]=v);
  const res=await profAction(data);
  if(res.success){toast('Leave date added!');location.reload();}else toast(res.message||'Error','danger');
}
async function delLeave(id,btn){
  if(!confirm('Remove this leave date?'))return;
  const res=await profAction({action:'delete_leave_exception',id});
  if(res.success){toast('Removed');btn.closest('.file-row').remove();}else toast(res.message||'Error','danger');
}
</script>
