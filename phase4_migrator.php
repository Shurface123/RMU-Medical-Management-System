<?php
// Phase 4 Deployment Script
$rootDir = __DIR__;

// Create assets directories
if(!is_dir("$rootDir/assets")) mkdir("$rootDir/assets");
if(!is_dir("$rootDir/assets/css")) mkdir("$rootDir/assets/css");
if(!is_dir("$rootDir/assets/js")) mkdir("$rootDir/assets/js");

// Copy CSS explicitly
if(file_exists("$rootDir/css/logout.css")) {
    copy("$rootDir/css/logout.css", "$rootDir/assets/css/logout.css");
} else {
    // Write if missing
    file_put_contents("$rootDir/assets/css/logout.css", "
    body.logout-active { overflow: hidden; }
    .logout-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
    .logout-overlay.active { opacity: 1; pointer-events: auto; }
    .logout-modal { background: #fff; width: 90%; max-width: 440px; border-radius: 16px; padding: 2.5rem; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.15); transform: translateY(20px); transition: transform 0.4s; }
    .logout-overlay.active .logout-modal { transform: translateY(0); }
    .logout-actions { display:flex; gap:1rem; }
    .logout-btn-cancel, .logout-btn-confirm { border:none; padding:0.75rem 1.5rem; border-radius:8px; width:100%; font-weight:600; cursor:pointer; font-family:'Poppins',sans-serif; }
    .logout-btn-cancel { background:transparent; border:1px solid #E2E8F0; color:#64748b; }
    .logout-btn-confirm { background:#2F80ED; color:white; }
    .logout-countdown-wrapper { display:none; flex-direction:column; align-items:center; }
    .logout-countdown-svg { transform: rotate(-90deg); width:120px; height:120px; margin-bottom:1.5rem; }
    .logout-countdown-bg { fill:none; stroke:rgba(47,128,237,0.1); stroke-width:6; }
    .logout-countdown-progress { fill:none; stroke:#2F80ED; stroke-width:6; stroke-linecap:round; transition: stroke-dashoffset 1s linear; }
    .logout-countdown-text { position:absolute; top:42px; left:0; right:0; font-size:2rem; font-weight:800; color:#2F80ED; }
    .logout-health-msg { font-style:italic; color:#64748b; margin-top:1rem; opacity:0; transition:opacity 0.5s; min-height:45px; }
    ");
}

// Rewrite JS to hit logout_handler.php instead of logout.php natively, and place in assets/js
$jsContent = <<<JS
document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('forcedLogoutOverlay')) return;
    const logoutBtns = document.querySelectorAll('a[href*="logout.php"], .adm-logout-btn');
    if(logoutBtns.length === 0) return;
    
    // Inject CSRF if missing globally
    if(!document.querySelector('meta[name="csrf-token"]')) {
        const m = document.createElement('meta'); m.name = 'csrf-token'; m.content = ''; document.head.appendChild(m);
    }
    
    const overlay = document.createElement('div');
    overlay.className = 'logout-overlay';
    overlay.innerHTML = `
        <div class="logout-modal">
            <div id="logoutConfirmView">
                <i class="fas fa-right-from-bracket" style="font-size:3rem;color:#2F80ED;background:rgba(47,128,237,0.1);padding:1.5rem;border-radius:50%;margin-bottom:1rem;"></i>
                <h2>Confirm Logout</h2>
                <p style="color:#64748b;margin-bottom:2rem;">Are you sure you want to log out of the system?</p>
                <div class="logout-actions">
                    <button class="btn btn-ghost logout-btn-cancel" id="logoutCancelBtn"><span class="btn-text">Cancel</span></button>
                    <button class="btn btn-primary logout-btn-confirm" id="logoutConfirmBtn"><span class="btn-text">Logout</span></button>
                </div>
            </div>
            <div id="logoutCountdownView" class="logout-countdown-wrapper" style="position:relative;">
                <svg class="logout-countdown-svg" viewBox="0 0 100 100">
                    <circle class="logout-countdown-bg" cx="50" cy="50" r="45"></circle>
                    <circle class="logout-countdown-progress" id="logoutProgressCircle" cx="50" cy="50" r="45" style="stroke-dasharray: 282.74; stroke-dashoffset: 0;"></circle>
                </svg>
                <div class="logout-countdown-text" id="logoutCountdownText">3</div>
                <div class="logout-health-msg" id="logoutHealthMsg"></div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const cv = document.getElementById('logoutConfirmView');
    const cdt = document.getElementById('logoutCountdownView');
    const ring = document.getElementById('logoutProgressCircle');
    const btnCancel = document.getElementById('logoutCancelBtn');
    const btnConfirm = document.getElementById('logoutConfirmBtn');
    
    let isCounting = false;
    let origin = '';
    
    logoutBtns.forEach(b => b.addEventListener('click', e => {
        e.preventDefault();
        document.body.classList.add('logout-active');
        overlay.classList.add('active');
        cv.style.display = 'block'; cdt.style.display = 'none';
        isCounting = false;
        origin = window.location.pathname.split('/').pop();
    }));
    
    btnCancel.addEventListener('click', () => { if(!isCounting) { overlay.classList.remove('active'); document.body.classList.remove('logout-active'); } });
    
    btnConfirm.addEventListener('click', async () => {
        isCounting = true;
        cv.style.display = 'none'; cdt.style.display = 'flex';
        
        let msg = "Your health is your greatest wealth.";
        try {
            let res = await fetch('/RMU-Medical-Management-System/php/get_health_message.php');
            let data = await res.json();
            if(data.success) msg = data.message;
        } catch(e){}
        const el = document.getElementById('logoutHealthMsg');
        el.innerText = `"\${msg}"`;
        setTimeout(()=>el.style.opacity = '1', 100);
        
        // Execute AJAX Logout immediately, but still wait 3s before redirecting for UX
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        let redirectDest = '/RMU-Medical-Management-System/php/index.php';
        fetch('/RMU-Medical-Management-System/php/logout_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `csrf=\${encodeURIComponent(csrfToken)}&origin=\${encodeURIComponent(origin)}`
        }).then(r=>r.json()).then(d => { if(d.redirect) redirectDest = d.redirect; }).catch(console.error);

        let s = 3;
        const intr = setInterval(() => {
            s--;
            document.getElementById('logoutCountdownText').innerText = s;
            ring.style.strokeDashoffset = 282.74 * ((3-s)/3);
            if(s <= 0) {
                clearInterval(intr);
                window.location.href = redirectDest;
            }
        }, 1000);
    });
});
JS;
file_put_contents("$rootDir/assets/js/logout.js", $jsContent);

// Copy or create get_health_message.php in php/
if(file_exists("$rootDir/php/handlers/get_health_message.php")) {
    copy("$rootDir/php/handlers/get_health_message.php", "$rootDir/php/get_health_message.php");
}

// Create logout_handler.php
$handlerContent = <<<PHP
<?php
session_start();
require_once 'db_conn.php';
require_once 'classes/AuditLogger.php';
header('Content-Type: application/json');

\$uid = \$_SESSION['user_id'] ?? null;
\$role = \$_SESSION['role'] ?? \$_SESSION['user_role'] ?? 'unknown';
\$sid = session_id();
\$csrf = \$_POST['csrf'] ?? '';
\$origin = \$_POST['origin'] ?? 'unknown';

// Verifications
if (!empty(\$_SESSION['csrf_token']) && !hash_equals(\$_SESSION['csrf_token'], \$csrf)) {
    echo json_encode(['success'=>false, 'error'=>'Invalid CSRF token', 'redirect'=>'/RMU-Medical-Management-System/php/index.php']);
    exit;
}

\$ip = \$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
\$ua = \$_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
function _os(\$ua) { if (preg_match('/win/i', \$ua)) return 'Windows'; if (preg_match('/mac/i', \$ua)) return 'Mac'; return 'Unknown'; }
function _br(\$ua) { if (preg_match('/chrome/i', \$ua)) return 'Chrome'; if(preg_match('/safari/i', \$ua)) return 'Safari'; return 'Unknown'; }

// Health Cache
\$msg = \$_SESSION['health_message_shown'] ?? 'None';

// Redirect config
\$qCfg = mysqli_query(\$conn, "SELECT redirect_url, countdown_duration_seconds FROM logout_config LIMIT 1");
\$cfg = mysqli_fetch_assoc(\$qCfg);
\$redir = (\$cfg && !empty(\$cfg['redirect_url'])) ? \$cfg['redirect_url'] : '/RMU-Medical-Management-System/php/index.php';
\$dur = (\$cfg) ? \$cfg['countdown_duration_seconds'] : 3;

if (\$uid) {
    // 1. active_sessions
    mysqli_query(\$conn, "DELETE FROM active_sessions WHERE session_id='\$sid'");
    // 2. logout_logs
    \$sL = mysqli_prepare(\$conn, "INSERT INTO logout_logs (user_id, role, session_id, logout_type, logout_confirmed_at, countdown_duration, ip_address, device_info, browser, dashboard_logged_out_from, health_message_shown) VALUES (?,?,?, 'manual', NOW(), ?, ?, ?, ?, ?, ?)");
    if (\$sL) {
        \$os = _os(\$ua); \$br = _br(\$ua);
        mysqli_stmt_bind_param(\$sL, "ississsss", \$uid, \$role, \$sid, \$dur, \$ip, \$os, \$br, \$origin, \$msg);
        mysqli_stmt_execute(\$sL);
    }
    // 3. cookies
    if (isset(\$_COOKIE['rmumss_remember'])) {
        \$h = hash('sha256', \$_COOKIE['rmumss_remember']);
        mysqli_query(\$conn, "DELETE FROM remember_me_tokens WHERE token_hash='\$h'");
        setcookie('rmumss_remember', '', time() - 3600, '/');
    }
    // 4. Audit
    if (class_exists('AuditLogger')) AuditLogger::log(\$conn, \$uid, 'manual_logout', 'User logged out via AJAX handler.', '{}');
}

\$_SESSION = [];
session_destroy();

echo json_encode(['success'=>true, 'redirect'=>\$redir]);
PHP;
file_put_contents("$rootDir/php/logout_handler.php", $handlerContent);

// Inject into dashboards
// Search for dashboard files and _sidebar.php
function rsearch($folder) {
    $iti = new RecursiveDirectoryIterator($folder);
    foreach(new RecursiveIteratorIterator($iti) as $file) {
        if(strpos($file , 'logout.php') !== false) {
           // We only want to inject files that reference logout.php as a UI link, not backend controllers
           yield $file;
        }
    }
}

$files = [
    "$rootDir/php/dashboards/doctor_dashboard.php",
    "$rootDir/php/dashboards/patient_dashboard.php",
    "$rootDir/php/dashboards/nurse_dashboard.php",
    "$rootDir/php/dashboards/pharmacy_dashboard.php",
    "$rootDir/php/dashboards/lab_dashboard.php",
    "$rootDir/php/dashboards/medical_records.php",
    "$rootDir/php/dashboards/staff_dashboard.php",
    "$rootDir/php/finance/finance_dashboard.php",
    "$rootDir/php/includes/_sidebar.php"
];

$injected = 0;
foreach($files as $f) {
    if(!file_exists($f)) continue;
    $content = file_get_contents($f);
    
    // Safety check - Did we already inject assets/js/logout.js? If so, skip.
    if(strpos($content, '/assets/js/logout.js') !== false) continue;
    
    // We want to safely insert `<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">` into <head>
    // and `<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>` before </body>
    
    // Fix <head>
    if (preg_match('/<\/head>/i', $content)) {
        $meta = '<!-- Phase 4 Hooks --><link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css"><meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\'); ?>"></head>';
        $content = preg_replace('/<\/head>/i', $meta, $content, 1);
    }
    
    // Fix <body>
    if (preg_match('/<\/body>/i', $content)) {
        $js = '<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script></body>';
        $content = preg_replace('/<\/body>/i', $js, $content, 1);
    }
    
    // Also convert any previous /js/logout.js injections to /assets/js/logout.js (specifically _sidebar.php)
    $content = str_replace('/css/logout.css', '/assets/css/logout.css', $content);
    $content = str_replace('/js/logout.js', '/assets/js/logout.js', $content);
    
    file_put_contents($f, $content);
    $injected++;
}

echo "Deployment complete. $injected dashboards hooked into assets/ architectures.";
