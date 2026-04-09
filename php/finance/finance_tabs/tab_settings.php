<?php
// TAB: SETTINGS — Module 13
$ps_config = [];
$pcq = mysqli_query($conn,"SELECT * FROM paystack_config WHERE is_active=1");
if($pcq) while($r=mysqli_fetch_assoc($pcq)) $ps_config[$r['config_key']] = $r;
$fs_settings = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM finance_settings WHERE user_id=$user_id LIMIT 1")) ?: [];
$base_url = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!='off'?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/RMU-Medical-Management-System';
?>
<div id="sec-settings" class="dash-section">
<div class="adm-page-header">
  <div class="adm-page-header-left">
    <h1><i class="fas fa-gear" style="color:var(--role-accent);"></i> Finance Settings</h1>
    <p>Configure Paystack integration, billing rules, and system preferences</p>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">

<!-- ─────────── PAYSTACK CONFIG ─────────── -->
<div class="adm-card" style="grid-column:1/-1;">
  <div class="adm-card-header">
    <h3><i class="fas fa-plug" style="color:var(--role-accent);"></i> Paystack API Configuration</h3>
    <button onclick="testPaystackConn()" class="adm-btn adm-btn-ghost adm-btn-sm" id="testConnBtn"><i class="fas fa-wifi"></i> Test Connection</button>
  </div>
  <div class="adm-card-body">
    <?php if($user_role!=='finance_manager'): ?>
    <div class="adm-alert adm-alert-warning" style="margin-bottom:1.5rem;"><i class="fas fa-lock"></i><div>Paystack credentials can only be updated by a <strong>Finance Manager</strong>.</div></div>
    <?php endif;?>
    <form id="formPaystackConfig">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
      <div class="adm-form-group">
        <label><i class="fas fa-key"></i> Public Key</label>
        <div style="position:relative;">
          <input type="text" id="cfgPublicKey" name="paystack_public_key" class="adm-search-input" value="<?=!empty($ps_config['paystack_public_key'])?'pk_••••••••••••••••':''?>" placeholder="pk_test_..." <?=$user_role!=='finance_manager'?'readonly':''?>>
          <button type="button" onclick="toggleReveal('cfgPublicKey','paystack_public_key')" class="adm-btn adm-btn-sm" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);" title="Reveal"><i class="fas fa-eye"></i></button>
        </div>
        <div style="font-size:1.15rem;color:var(--text-muted);margin-top:.3rem;">Starts with pk_test_ or pk_live_</div>
      </div>
      <div class="adm-form-group">
        <label><i class="fas fa-shield-keyhole"></i> Secret Key <span style="color:var(--danger);">⚠ Never expose</span></label>
        <div style="position:relative;">
          <input type="password" id="cfgSecretKey" name="paystack_secret_key" class="adm-search-input" value="<?=!empty($ps_config['paystack_secret_key'])?'sk_••••••••••••••••':''?>" placeholder="sk_test_..." <?=$user_role!=='finance_manager'?'readonly':''?>>
          <button type="button" onclick="togglePwdReveal('cfgSecretKey')" class="adm-btn adm-btn-sm" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);" title="Show"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="adm-form-group">
        <label><i class="fas fa-webhook"></i> Webhook Secret</label>
        <div style="position:relative;">
          <input type="password" id="cfgWebhookSecret" name="paystack_webhook_secret" class="adm-search-input" value="<?=!empty($ps_config['paystack_webhook_secret'])?'whsec_••••••••••••••':''?>" placeholder="Webhook signing secret" <?=$user_role!=='finance_manager'?'readonly':''?>>
          <button type="button" onclick="togglePwdReveal('cfgWebhookSecret')" class="adm-btn adm-btn-sm" style="position:absolute;right:.5rem;top:50%;transform:translateY(-50%);background:none;color:var(--text-muted);" title="Show"><i class="fas fa-eye"></i></button>
        </div>
      </div>
      <div class="adm-form-group">
        <label><i class="fas fa-toggle-on"></i> Environment</label>
        <select name="paystack_env" id="cfgEnv" class="adm-search-input" <?=$user_role!=='finance_manager'?'disabled':''?>>
          <option value="test" <?=($ps_config['paystack_env']['config_value']??'test')==='test'?'selected':''?>>🧪 Test Mode</option>
          <option value="live" <?=($ps_config['paystack_env']['config_value']??'test')==='live'?'selected':''?>>🚀 Live Mode</option>
        </select>
      </div>
    </div>
    <div style="background:var(--surface-2);border-radius:10px;padding:1.5rem;border:1px solid var(--border);margin-top:.5rem;">
      <div style="font-weight:600;font-size:1.3rem;margin-bottom:1rem;">Auto-generated URLs (read-only)</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
        <div class="adm-form-group">
          <label>Callback URL</label>
          <input type="text" value="<?=$base_url?>/php/finance/paystack_callback.php" class="adm-search-input" readonly onclick="this.select()" style="font-size:1.2rem;cursor:pointer;">
        </div>
        <div class="adm-form-group">
          <label>Webhook URL</label>
          <input type="text" value="<?=$base_url?>/php/finance/paystack_webhook.php" class="adm-search-input" readonly onclick="this.select()" style="font-size:1.2rem;cursor:pointer;">
        </div>
      </div>
    </div>
    </form>
    <?php if($user_role==='finance_manager'): ?>
    <div style="margin-top:1.5rem;">
      <button onclick="savePaystackConfig()" class="adm-btn adm-btn-primary"><i class="fas fa-floppy-disk"></i> Save Paystack Config</button>
    </div>
    <?php endif;?>
  </div>
</div>

<!-- Invoice Settings -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-file-invoice"></i> Invoice Settings</h3></div>
  <div class="adm-card-body">
    <form id="formInvSettings">
      <div class="adm-form-group">
        <label>Invoice Number Prefix</label>
        <input type="text" name="invoice_prefix" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['invoice_prefix']??'RMU-INV')?>" placeholder="RMU-INV">
      </div>
      <div class="adm-form-group">
        <label>Default Due Date (days after issue)</label>
        <input type="number" name="default_due_days" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['default_due_days']??'30')?>" min="1" max="365">
      </div>
      <div class="adm-form-group">
        <label>Default Tax Rate (%)</label>
        <input type="number" name="default_tax_rate" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['default_tax_rate']??'0')?>" min="0" max="100" step="0.01">
      </div>
      <div class="adm-form-group">
        <label>Currency</label>
        <select name="currency" class="adm-search-input">
          <option value="GHS" selected>GHS — Ghanaian Cedi</option>
          <option value="USD">USD — US Dollar</option>
        </select>
      </div>
      <button type="button" onclick="saveFinanceSettings('formInvSettings','invoice')" class="adm-btn adm-btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </form>
  </div>
</div>

<!-- Waiver & Refund Settings -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-sliders"></i> Waiver & Refund Rules</h3></div>
  <div class="adm-card-body">
    <form id="formWaiverSettings">
      <div class="adm-form-group">
        <label>Waiver Approval Threshold (GHS)</label>
        <div style="font-size:1.2rem;color:var(--text-muted);margin-bottom:.5rem;">Waivers above this amount require finance manager approval</div>
        <input type="number" name="waiver_approval_threshold" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['waiver_approval_threshold']??'500')?>" step="0.01" min="0">
      </div>
      <div class="adm-form-group">
        <label>Refund Approval Threshold (GHS)</label>
        <div style="font-size:1.2rem;color:var(--text-muted);margin-bottom:.5rem;">Refunds above this require manager approval</div>
        <input type="number" name="refund_approval_threshold" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['refund_approval_threshold']??'200')?>" step="0.01" min="0">
      </div>
      <div class="adm-form-group">
        <label>Maximum Refund Percentage (%)</label>
        <input type="number" name="max_refund_pct" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['max_refund_pct']??'100')?>" min="1" max="100">
      </div>
      <button type="button" onclick="saveFinanceSettings('formWaiverSettings','waiver_refund')" class="adm-btn adm-btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </form>
  </div>
</div>

<!-- Notification Alert Settings -->
<div class="adm-card">
  <div class="adm-card-header"><h3><i class="fas fa-bell"></i> Alert Thresholds</h3></div>
  <div class="adm-card-body">
    <form id="formAlertSettings">
      <div class="adm-form-group">
        <label>Overdue Invoice Alert (days)</label>
        <div style="font-size:1.2rem;color:var(--text-muted);margin-bottom:.5rem;">Send alerts when invoices are overdue by this many days</div>
        <input type="number" name="overdue_alert_days" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['overdue_alert_days']??'7')?>" min="1">
      </div>
      <div class="adm-form-group">
        <label>Budget Overrun Alert (%)</label>
        <div style="font-size:1.2rem;color:var(--text-muted);margin-bottom:.5rem;">Alert when budget utilization hits this percentage</div>
        <input type="number" name="budget_alert_pct" class="adm-search-input" value="<?=htmlspecialchars($fs_settings['budget_alert_pct']??'80')?>" min="1" max="100">
      </div>
      <button type="button" onclick="saveFinanceSettings('formAlertSettings','alerts')" class="adm-btn adm-btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </form>
  </div>
</div>

</div><!-- /grid -->
</div><!-- /sec-settings -->

<script>
let _paystackRevealed = {};
async function toggleReveal(inputId, key){
  if(!_paystackRevealed[key]){
    const d=await finAction({action:'reveal_paystack_key',key});
    if(d.success){ document.getElementById(inputId).value=d.value; _paystackRevealed[key]=true; }
    else toast(d.message||'Cannot reveal key.','danger');
  } else { document.getElementById(inputId).value='pk_••••••••••••••••'; _paystackRevealed[key]=false; }
}
function togglePwdReveal(id){
  const el=document.getElementById(id);
  el.type=el.type==='password'?'text':'password';
}
async function savePaystackConfig(){
  const f=document.getElementById('formPaystackConfig');
  const data={action:'save_paystack_config',env:document.getElementById('cfgEnv').value};
  const pk=document.getElementById('cfgPublicKey').value;
  const sk=document.getElementById('cfgSecretKey').value;
  const ws=document.getElementById('cfgWebhookSecret').value;
  if(pk&&!pk.includes('••••')) data.public_key=pk;
  if(sk&&!sk.includes('••••')) data.secret_key=sk;
  if(ws&&!ws.includes('••••')) data.webhook_secret=ws;
  const d=await finAction(data);
  if(d.success) toast('Paystack config saved & encrypted!','success');
  else toast(d.message||'Error saving config.','danger');
}
async function testPaystackConn(){
  const btn=document.getElementById('testConnBtn');
  btn.classList.add('loading'); btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Testing...';
  const d=await finAction({action:'test_paystack_connection'});
  btn.classList.remove('loading'); btn.innerHTML='<i class="fas fa-wifi"></i> Test Connection';
  if(d.success) toast('✅ Paystack connection successful! ('+d.env+' mode)','success');
  else toast('❌ Paystack test failed: '+(d.message||'Check your keys.'),'danger');
}
async function saveFinanceSettings(formId, section){
  const form=document.getElementById(formId);
  const fd=new FormData(form);
  const data={action:'save_finance_settings',section};
  fd.forEach((v,k)=>data[k]=v);
  const d=await finAction(data);
  if(d.success) toast('Settings saved!','success');
  else toast(d.message||'Error.','danger');
}
</script>
