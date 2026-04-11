<!-- ============================================================
     MODULE 10 — SYSTEM SETTINGS
     General, Inventory, Notification, User, Security settings
     All saved dynamically via AJAX — no page reload
     ============================================================ -->
<div id="sec-system_settings" class="dash-section <?=($active_tab==='system_settings')?'active':''?>">
<?php
/* ── Fetch all system settings as key=>value ────────────── */
$sysSettings = [];
$sq = dbSelect($conn, "SELECT setting_key, setting_value, setting_type FROM system_settings ORDER BY setting_key");
foreach ($sq as $s) $sysSettings[$s['setting_key']] = $s['setting_value'];
$gs = function($k, $def='') use ($sysSettings) { return $sysSettings[$k] ?? $def; };
/* ── Pharmacist preferences (from pharmacy_settings) ───── */
$myPrefs = dbRow($conn, "SELECT * FROM pharmacy_settings WHERE pharmacist_id=?", "i", [$pharm_pk]);
?>

<div class="settings-layout">

<!-- ── Settings Sub-Navigation ────────────────────────────── -->
<div class="settings-subnav">
  <button class="btn btn-primary stab active" onclick="showSettingsSection('general',this)"><span class="btn-text"><i class="fas fa-cog"></i> General</span></button>
  <button class="btn btn-primary stab" onclick="showSettingsSection('inventory',this)"><span class="btn-text"><i class="fas fa-boxes"></i> Inventory</span></button>
  <button class="btn btn-primary stab" onclick="showSettingsSection('notifications',this)"><span class="btn-text"><i class="fas fa-bell"></i> Notifications</span></button>
  <button class="btn btn-primary stab" onclick="showSettingsSection('preferences',this)"><span class="btn-text"><i class="fas fa-palette"></i> Preferences</span></button>
  <button class="btn btn-primary stab" onclick="showSettingsSection('security',this)"><span class="btn-text"><i class="fas fa-shield-alt"></i> Security</span></button>
</div>

<!-- ════════════════ GENERAL SETTINGS ════════════════════ -->
<div class="settings-section active" id="sett-general">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-hospital"></i> General Settings</h3><button class="btn btn-sm btn-primary" onclick="saveGeneral()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="form-grid-2">
    <div class="form-group">
      <label>System Name</label>
      <input id="sg_name" class="form-control" value="<?=e($gs('system_name','RMU Medical Sickbay'))?>">
      <small class="text-muted">Displayed in headers and reports</small>
    </div>
    <div class="form-group">
      <label>Time Zone</label>
      <select id="sg_tz" class="form-control">
        <?php foreach(['Africa/Accra','Africa/Lagos','Africa/Nairobi','UTC','America/New_York','Europe/London'] as $tz): ?>
          <option <?=$gs('timezone','Africa/Accra')===$tz?'selected':''?>><?=$tz?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Date Format</label>
      <select id="sg_datefmt" class="form-control">
        <?php foreach(['d M Y'=>'01 Jan 2026','Y-m-d'=>'2026-01-01','d/m/Y'=>'01/01/2026','m/d/Y'=>'01/01/2026 (US)'] as $fmt=>$ex): ?>
          <option value="<?=$fmt?>" <?=$gs('date_format','d M Y')===$fmt?'selected':''?>><?=$ex?> (<?=$fmt?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Currency</label>
      <div style="display:flex;gap:.5rem;">
        <input id="sg_currency" class="form-control" value="<?=e($gs('currency','GHS'))?>" style="max-width:100px;">
        <input id="sg_cursymbol" class="form-control" value="<?=e($gs('currency_symbol','GH₵'))?>" style="max-width:80px;" placeholder="Symbol">
      </div>
    </div>
  </div>
</div>
</div>

<!-- ════════════════ INVENTORY SETTINGS ══════════════════ -->
<div class="settings-section" id="sett-inventory">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-warehouse"></i> Inventory Settings</h3><button class="btn btn-sm btn-primary" onclick="saveInventory()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="settings-info-bar"><i class="fas fa-info-circle"></i> These defaults apply when adding new medicines. Individual medicines can override these values.</div>
  <div class="form-grid-2">
    <div class="form-group">
      <label>Default Reorder Level</label>
      <input id="si_reorder" type="number" min="1" class="form-control" value="<?=e($gs('default_reorder_level','10'))?>">
      <small class="text-muted">Trigger low-stock alert when stock falls below this</small>
    </div>
    <div class="form-group">
      <label>Expiry Warning Period (days)</label>
      <input id="si_expiry" type="number" min="1" class="form-control" value="<?=e($gs('expiry_warning_days','30'))?>">
      <small class="text-muted">Alert when medicine expires within this many days</small>
    </div>
    <div class="form-group">
      <label>Stock Alert Threshold</label>
      <input id="si_alert" type="number" min="1" class="form-control" value="<?=e($gs('stock_alert_threshold','5'))?>">
      <small class="text-muted">Critical alert when stock drops below this number</small>
    </div>
  </div>
</div>
</div>

<!-- ════════════════ NOTIFICATION SETTINGS ═══════════════ -->
<div class="settings-section" id="sett-notifications">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-bell-slash"></i> Notification Triggers</h3><button class="btn btn-sm btn-primary" onclick="saveNotifSettings()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <p class="text-muted" style="font-size:.88rem;margin-bottom:1rem;">Configure when system-wide alerts are triggered. These apply to all pharmacists.</p>
  <div class="form-grid-2">
    <div class="form-group">
      <label>Low Stock Alert — units threshold</label>
      <input id="sn_lowstock" type="number" min="1" class="form-control" value="<?=e($gs('stock_alert_threshold','5'))?>">
      <small class="text-muted">Alert when stock ≤ this many units</small>
    </div>
    <div class="form-group">
      <label>Expiry Alert — days before</label>
      <input id="sn_expiry" type="number" min="1" class="form-control" value="<?=e($gs('expiry_warning_days','30'))?>">
      <small class="text-muted">Alert when days to expiry ≤ this value</small>
    </div>
  </div>
</div>
</div>

<!-- ════════════════ USER PREFERENCES ════════════════════ -->
<div class="settings-section" id="sett-preferences">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-user-cog"></i> User Preferences</h3><button class="btn btn-sm btn-primary" onclick="saveUserPrefs()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="form-grid-2">
    <div class="form-group">
      <label>Theme</label>
      <div class="theme-selector">
        <label class="theme-opt <?=($myPrefs['theme_preference']??$gs('default_theme','light'))==='light'?'active':''?>" onclick="selectTheme('light',this)">
          <i class="fas fa-sun"></i> Light
        </label>
        <label class="theme-opt <?=($myPrefs['theme_preference']??$gs('default_theme','light'))==='dark'?'active':''?>" onclick="selectTheme('dark',this)">
          <i class="fas fa-moon"></i> Dark
        </label>
      </div>
      <input type="hidden" id="sp_theme" value="<?=e($myPrefs['theme_preference']??$gs('default_theme','light'))?>">
    </div>
    <div class="form-group">
      <label>Language</label>
      <select id="sp_lang" class="form-control">
        <?php foreach(['English','French','Spanish','Portuguese'] as $lang): ?>
          <option <?=($myPrefs['language']??$gs('system_language','English'))===$lang?'selected':''?>><?=$lang?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Dashboard Layout</label>
      <select id="sp_layout" class="form-control">
        <option value="default" selected>Default</option>
        <option value="compact">Compact</option>
        <option value="wide">Wide</option>
      </select>
    </div>
  </div>
</div>
</div>

<!-- ════════════════ SECURITY SETTINGS ═══════════════════ -->
<div class="settings-section" id="sett-security">
<div class="section-card">
  <div class="sc-head"><h3><i class="fas fa-lock"></i> Security Settings</h3><button class="btn btn-sm btn-primary" onclick="saveSecuritySettings()"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button></div>
  <div class="settings-info-bar" style="border-left-color:var(--warning);"><i class="fas fa-exclamation-triangle" style="color:var(--warning);"></i> Changes here affect system-wide security policy. Use caution.</div>
  <div class="form-grid-2">
    <div class="form-group">
      <label>Session Timeout (minutes)</label>
      <input id="ss_timeout" type="number" min="5" max="120" class="form-control" value="<?=(int)($gs('session_timeout','1800'))/60?>">
      <small class="text-muted">Auto-logout after this many minutes of inactivity</small>
    </div>
    <div class="form-group">
      <label>Password Change Interval (days)</label>
      <input id="ss_pwchange" type="number" min="0" max="365" class="form-control" value="<?=e($gs('password_change_days','90'))?>">
      <small class="text-muted">0 = never force password change</small>
    </div>
    <div class="form-group">
      <label>Max Login Attempts</label>
      <input id="ss_maxlogin" type="number" min="3" max="20" class="form-control" value="<?=e($gs('max_login_attempts','5'))?>">
      <small class="text-muted">Lock account after this many consecutive failed attempts</small>
    </div>
  </div>
</div>
</div>

</div><!-- /.settings-layout -->

<!-- ════════════════ SETTINGS JS ═════════════════════════ -->
<script>
function showSettingsSection(id,btn){
  document.querySelectorAll('.settings-section').forEach(s=>s.classList.remove('active'));
  document.querySelectorAll('.stab').forEach(b=>b.classList.remove('active'));
  const el=document.getElementById('sett-'+id);
  if(el) el.classList.add('active');
  if(btn) btn.classList.add('active');
}
function selectTheme(theme,el){
  document.querySelectorAll('.theme-opt').forEach(o=>o.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('sp_theme').value=theme;
  document.documentElement.setAttribute('data-theme',theme);
}
/* ── Save General ───────────────────────────── */
async function saveGeneral(){
  const r=await pharmAction({action:'save_system_settings',settings:{
    system_name: document.getElementById('sg_name').value,
    timezone: document.getElementById('sg_tz').value,
    date_format: document.getElementById('sg_datefmt').value,
    currency: document.getElementById('sg_currency').value,
    currency_symbol: document.getElementById('sg_cursymbol').value
  }});
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Save Inventory ─────────────────────────── */
async function saveInventory(){
  if(!validateNumber(document.getElementById('si_reorder').value,1,'Reorder level')) return;
  if(!validateNumber(document.getElementById('si_expiry').value,1,'Expiry period')) return;
  const r=await pharmAction({action:'save_system_settings',settings:{
    default_reorder_level: document.getElementById('si_reorder').value,
    expiry_warning_days: document.getElementById('si_expiry').value,
    stock_alert_threshold: document.getElementById('si_alert').value
  }});
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Save Notification Settings ─────────────── */
async function saveNotifSettings(){
  const r=await pharmAction({action:'save_system_settings',settings:{
    stock_alert_threshold: document.getElementById('sn_lowstock').value,
    expiry_warning_days: document.getElementById('sn_expiry').value
  }});
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Save User Preferences ──────────────────── */
async function saveUserPrefs(){
  const r=await pharmAction({action:'update_settings',
    theme_preference: document.getElementById('sp_theme').value,
    language: document.getElementById('sp_lang').value
  });
  showToast(r.message||'Saved',r.success?'success':'error');
}
/* ── Save Security Settings ─────────────────── */
async function saveSecuritySettings(){
  if(!confirmAction('Changing security settings affects all users. Continue?')) return;
  const r=await pharmAction({action:'save_system_settings',settings:{
    session_timeout: String(parseInt(document.getElementById('ss_timeout').value)*60),
    password_change_days: document.getElementById('ss_pwchange').value,
    max_login_attempts: document.getElementById('ss_maxlogin').value
  }});
  showToast(r.message||'Saved',r.success?'success':'error');
}
</script>

<!-- ════════════════ SETTINGS CSS ════════════════════════ -->
<style>
.settings-layout{max-width:900px;}
.settings-subnav{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.5rem;padding-bottom:.8rem;border-bottom:1px solid var(--border);}
.stab{padding:.55rem 1.1rem;border:none;background:transparent;border-radius:var(--radius);cursor:pointer;font-weight:500;color:var(--text-secondary);transition:.2s;display:flex;align-items:center;gap:.4rem;}
.stab:hover,.stab.active{background:var(--role-accent-light);color:var(--role-accent);}
.settings-section{display:none;}.settings-section.active{display:block;animation:fadeIn .3s;}
.settings-info-bar{display:flex;align-items:center;gap:.6rem;padding:.8rem 1rem;border-radius:var(--radius);background:var(--bg-secondary);border-left:4px solid var(--role-accent);margin-bottom:1.2rem;font-size:.88rem;color:var(--text-secondary);}
.theme-selector{display:flex;gap:.6rem;margin-top:.4rem;}
.theme-opt{padding:.7rem 1.4rem;border:2px solid var(--border);border-radius:var(--radius);cursor:pointer;font-weight:500;transition:.2s;display:flex;align-items:center;gap:.5rem;}
.theme-opt:hover,.theme-opt.active{border-color:var(--role-accent);background:var(--role-accent-light);color:var(--role-accent);}
</style>
</div><!-- /sec-system_settings -->
