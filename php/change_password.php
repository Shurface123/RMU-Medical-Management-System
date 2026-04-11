<?php
/**
 * change_password.php — Forced Password Change Screen
 * Shown when force_password_change = 1 on the users table.
 * Preserves exact login page color scheme.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db_conn.php';

$forced = isset($_GET['forced']);
$err    = '';

// Must be logged in
if (empty($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}

if (empty($_SESSION['_cp_csrf'])) {
    $_SESSION['_cp_csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Change Password — RMU Medical Sickbay</title>
<link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root{--primary:#2F80ED;--success:#27ae60;--danger:#e74c3c;--text-dark:#2c3e50;--text-muted:#7f8c8d;--border:#e0e0e0;--white:#fff;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2F80ED 0%,#56CCF2 50%,#2F80ED 100%);padding:2rem 1rem;position:relative;overflow-x:hidden;}
body::before{content:'';position:absolute;width:200%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.1) 1px,transparent 1px);background-size:50px 50px;animation:bgMove 20s linear infinite;pointer-events:none;}
@keyframes bgMove{0%{transform:translate(0,0)}100%{transform:translate(50px,50px)}}
.card{position:relative;z-index:10;background:var(--white);padding:3rem 2.5rem;border-radius:24px;box-shadow:0 15px 40px rgba(47,128,237,.15);width:90%;max-width:440px;animation:slideIn .4s ease-out;}
@keyframes slideIn{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}
.hdr{text-align:center;margin-bottom:2rem;}
.logo-icon{width:72px;height:72px;background:linear-gradient(135deg,#2F80ED,#56CCF2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.2rem;box-shadow:0 8px 24px rgba(47,128,237,.25);}
.logo-icon i{font-size:2.8rem;color:#fff;}
.hdr h1{font-size:1.7rem;font-weight:700;color:var(--text-dark);margin-bottom:.3rem;}
.hdr p{font-size:.95rem;color:var(--text-muted);}
.notice{background:#FEF9E7;border-left:4px solid #F39C12;border-radius:8px;padding:.8rem 1rem;font-size:.9rem;color:#935116;margin-bottom:1.2rem;display:flex;gap:.6rem;}
.form-group{margin-bottom:1.4rem;}
.form-group label{display:block;font-size:.95rem;font-weight:600;color:var(--text-dark);margin-bottom:.5rem;}
.input-wrapper{position:relative;}
.fi{position:absolute;left:1.2rem;top:50%;transform:translateY(-50%);font-size:1.15rem;color:var(--text-muted);pointer-events:none;}
.form-control{width:100%;padding:.8rem 3rem .8rem 3rem;font-size:.95rem;border:2px solid var(--border);border-radius:12px;font-family:'Poppins',sans-serif;transition:all .2s;}
.form-control:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(47,128,237,.1);}
.form-control.valid{border-color:var(--success);}
.form-control.invalid{border-color:var(--danger);}
.pw-toggle{position:absolute;right:1.2rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1.15rem;color:var(--text-muted);}
.pw-toggle:hover{color:var(--primary);}
.pw-meter{height:5px;background:#e0e0e0;border-radius:3px;margin-top:.6rem;overflow:hidden;}
.pw-bar{height:100%;width:0;border-radius:3px;transition:all .35s;}
.pw-bar.s1{width:20%;background:#e74c3c;}.pw-bar.s2{width:40%;background:#e67e22;}
.pw-bar.s3{width:60%;background:#f39c12;}.pw-bar.s4{width:80%;background:#7dcea0;}.pw-bar.s5{width:100%;background:#27ae60;}
.pw-label{font-size:.85rem;color:var(--text-muted);margin-top:.3rem;}
.pw-checks{display:grid;grid-template-columns:1fr 1fr;gap:.3rem;margin-top:.5rem;}
.pw-check{font-size:.85rem;color:#aaa;display:flex;align-items:center;gap:.3rem;transition:color .2s;}
.pw-check.met{color:var(--success);}
.match-msg{font-size:.85rem;margin-top:.3rem;}
.match-msg.ok{color:var(--success);}.match-msg.err{color:var(--danger);}
.btn-submit{width:100%;padding:1rem;font-size:1rem;font-weight:600;background:linear-gradient(135deg,#2F80ED,#56CCF2);color:#fff;border:none;border-radius:12px;cursor:pointer;transition:all .2s;text-transform:uppercase;letter-spacing:1px;box-shadow:0 8px 20px rgba(47,128,237,.25);display:flex;align-items:center;justify-content:center;gap:.6rem;}
.btn-submit:hover:not(:disabled){transform:translateY(-1px);box-shadow:0 10px 24px rgba(47,128,237,.3);}
.btn-submit:disabled{opacity:.7;cursor:not-allowed;transform:none;}
</style>
</head>
<body>
<div class="card">
    <div class="hdr">
        <div class="logo-icon"><i class="fas fa-key"></i></div>
        <h1>Change Password</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></p>
    </div>

    <?php if ($forced): ?>
    <div class="notice">
        <i class="fas fa-exclamation-triangle"></i>
        <span>For your security, you must set a new password before continuing.</span>
    </div>
    <?php endif; ?>

    <form method="POST" action="password_handler.php" id="cpForm" novalidate>
        <input type="hidden" name="_csrf"  value="<?= htmlspecialchars($_SESSION['_cp_csrf']) ?>">
        <input type="hidden" name="action" value="force_change_password">

        <div class="form-group">
            <label for="new_password">New Password</label>
            <div class="input-wrapper">
                <input type="password" id="new_password" name="new_password" class="form-control"
                       placeholder="Minimum 8 characters" required autocomplete="new-password">
                <i class="fas fa-lock fi"></i>
                <button type="button" class="pw-toggle" onclick="togglePw('new_password','eye1')">
                    <i class="fas fa-eye" id="eye1"></i>
                </button>
            </div>
            <div class="pw-meter"><div class="pw-bar" id="pwBar"></div></div>
            <div class="pw-label" id="pwLabel">Enter a password</div>
            <div class="pw-checks">
                <span class="pw-check" id="c-len"><i class="fas fa-times"></i> 8+ characters</span>
                <span class="pw-check" id="c-upper"><i class="fas fa-times"></i> Uppercase</span>
                <span class="pw-check" id="c-lower"><i class="fas fa-times"></i> Lowercase</span>
                <span class="pw-check" id="c-num"><i class="fas fa-times"></i> Number</span>
                <span class="pw-check" id="c-sym"><i class="fas fa-times"></i> Special char</span>
            </div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <div class="input-wrapper">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                       placeholder="Re-enter new password" required autocomplete="new-password">
                <i class="fas fa-lock fi"></i>
                <button type="button" class="pw-toggle" onclick="togglePw('confirm_password','eye2')">
                    <i class="fas fa-eye" id="eye2"></i>
                </button>
            </div>
            <div class="match-msg" id="matchMsg"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-submit" id="submitBtn" disabled><span class="btn-text">
            <i class="fas fa-save"></i> Set New Password
        </span></button>
    </form>
</div>
<script>
function togglePw(id, ico) {
    const f = document.getElementById(id), i = document.getElementById(ico);
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
const pwField = document.getElementById('new_password');
const cfField = document.getElementById('confirm_password');
const barCls = ['s1','s2','s3','s4','s5'];
const barClrs = ['#e74c3c','#e67e22','#f39c12','#7dcea0','#27ae60'];
const labels = ['Very Weak','Weak','Fair','Strong','Very Strong'];
let pwOk = false, cfOk = false;

function strength(v){let s=0;if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[a-z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;return s;}
function setChk(id, met){const el=document.getElementById(id);if(!el)return;el.classList.toggle('met',met);el.querySelector('i').className=met?'fas fa-check':'fas fa-times';}
function checkMatch(){
    const mm=document.getElementById('matchMsg'), ok=pwField.value&&cfField.value===pwField.value;
    cfOk=ok;
    if(!cfField.value){mm.textContent='';mm.className='match-msg';}
    else if(ok){mm.textContent='✓ Passwords match';mm.className='match-msg ok';}
    else{mm.textContent='✗ Passwords do not match';mm.className='match-msg err';}
    cfField.classList.toggle('valid',cfOk);
    cfField.classList.toggle('invalid',!cfOk&&cfField.value.length>0);
    document.getElementById('submitBtn').disabled=!(pwOk&&cfOk);
}

pwField.addEventListener('input',()=>{
    const v=pwField.value, s=strength(v);
    const bar=document.getElementById('pwBar'), lbl=document.getElementById('pwLabel');
    bar.className='pw-bar '+(s>0?barCls[s-1]:'');
    lbl.textContent=s>0?labels[s-1]:'Enter a password';
    lbl.style.color=s>0?barClrs[s-1]:'var(--text-muted)';
    setChk('c-len',v.length>=8); setChk('c-upper',/[A-Z]/.test(v));
    setChk('c-lower',/[a-z]/.test(v)); setChk('c-num',/[0-9]/.test(v));
    setChk('c-sym',/[^A-Za-z0-9]/.test(v));
    pwOk=s>=4;
    pwField.classList.toggle('valid',pwOk);
    pwField.classList.toggle('invalid',!pwOk&&v.length>0);
    checkMatch();
});
cfField.addEventListener('input', checkMatch);
document.getElementById('cpForm').addEventListener('submit', function(e){
    if(!pwOk||!cfOk){e.preventDefault();return;}
    const btn=document.getElementById('submitBtn');
    btn.disabled=true;
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Saving...';
});
</script>
</body>
</html>
