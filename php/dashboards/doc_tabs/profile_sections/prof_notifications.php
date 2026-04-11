<?php // SECTION H: Notification & Communication Preferences ?>
<div id="prof-notifications" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-bell"></i> Notification Preferences</h3></div>
    <div style="padding:2rem;">
      <form id="formNotifPrefs" onsubmit="saveNotifPrefs(event)">
        <?php
        $toggles=[
          ['notif_new_appointment','New Appointment Booking','When a patient books an appointment'],
          ['notif_appt_reminders','Appointment Reminders','1 hour and 24 hours before scheduled appointment'],
          ['notif_appt_cancellations','Appointment Cancellations','When a patient cancels an appointment'],
          ['notif_lab_results','Lab Results Submitted','When lab technician submits test results'],
          ['notif_rx_refills','Prescription Refill Requests','When a patient requests a prescription refill'],
          ['notif_record_updates','Medical Record Updates','New medical record update alerts'],
          ['notif_nurse_messages','Nurse Messages & Tasks','Direct messages and task acknowledgements from nurses'],
          ['notif_inventory_alerts','Medicine Inventory Alerts','Low stock and expiry warnings'],
          ['notif_license_expiry','License / Cert Expiry','Warnings when your license or certifications are expiring'],
          ['notif_system_announcements','System Announcements','General announcements from admin'],
        ];
        foreach($toggles as [$name,$label,$desc]):
          $checked=($settings_row[$name]??1)?'checked':'';
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.9rem 0;border-bottom:1px solid var(--border);">
          <div><div style="font-weight:600;font-size:1.3rem;"><?=$label?></div><div style="font-size:1.1rem;color:var(--text-muted);"><?=$desc?></div></div>
          <label style="position:relative;width:42px;height:24px;cursor:pointer;">
            <input type="checkbox" name="<?=$name?>" value="1" <?=$checked?> style="opacity:0;width:0;height:0;position:absolute;">
            <span class="notif-slider"></span>
          </label>
        </div>
        <?php endforeach;?>

        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">
        <h4 style="margin-bottom:1rem;"><i class="fas fa-satellite-dish" style="color:var(--role-accent);"></i> Communication Channel</h4>
        <div class="form-row">
          <div class="form-group"><label>Preferred Channel</label>
            <select name="preferred_channel" class="form-control">
              <?php foreach(['dashboard'=>'In-Dashboard','email'=>'Email','sms'=>'SMS','dashboard,email'=>'Dashboard + Email','dashboard,email,sms'=>'All Channels'] as $v=>$l):?>
              <option value="<?=$v?>" <?=($settings_row['preferred_channel']??'dashboard')===$v?'selected':''?>><?=$l?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div class="form-group"><label>Notification Language</label>
            <select name="preferred_language" class="form-control">
              <?php foreach(['English','French','Twi','Ga','Ewe'] as $l):?>
              <option value="<?=$l?>" <?=($settings_row['preferred_language']??'English')===$l?'selected':''?>><?=$l?></option>
              <?php endforeach;?>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1rem;"><span class="btn-text"><i class="fas fa-save"></i> Save Preferences</span></button>
      </form>
    </div>
  </div>
</div>
<script>
async function saveNotifPrefs(e){
  e.preventDefault();const fd=new FormData(e.target),data={action:'save_notification_prefs'};
  // Unchecked checkboxes won't be in FormData — set them to 0
  ['notif_new_appointment','notif_appt_reminders','notif_appt_cancellations','notif_lab_results','notif_rx_refills','notif_record_updates','notif_nurse_messages','notif_inventory_alerts','notif_license_expiry','notif_system_announcements'].forEach(k=>data[k]=fd.has(k)?1:0);
  data.preferred_channel=fd.get('preferred_channel');
  data.preferred_language=fd.get('preferred_language');
  const res=await profAction(data);
  if(res.success)toast('Preferences saved!');else toast(res.message||'Error','danger');
}
</script>
