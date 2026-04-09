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
                    <button class="logout-btn-cancel" id="logoutCancelBtn">Cancel</button>
                    <button class="logout-btn-confirm" id="logoutConfirmBtn">Logout</button>
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
    
    btnConfirm.addEventListener('click', () => {
        isCounting = true;
        cv.style.display = 'none'; cdt.style.display = 'flex';
        
        let dur = 3;
        let redirectDest = '/RMU-Medical-Management-System/php/index.php';
        let s = dur;
        let totalS = dur;
        
        const msgEl = document.getElementById('logoutHealthMsg');
        const txtEl = document.getElementById('logoutCountdownText');
        txtEl.innerText = s;
        ring.style.strokeDashoffset = 0;
        
        const csrfEl = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = (csrfEl ? csrfEl.getAttribute('content') : '') || window.RMU_CSRF_TOKEN || '';
        
        let fetchedMsg = "Your health is your greatest wealth.";
        
        // 1. Fetch Health Config & Msg Concurrently
        fetch('/RMU-Medical-Management-System/php/get_health_message.php')
            .then(r => r.json())
            .then(data => {
                if(data.success && data.message) {
                    fetchedMsg = data.message;
                }
                if(data.duration) {
                    dur = parseInt(data.duration);
                    if(s === 3) { s = dur; totalS = dur; txtEl.innerText = s; }
                }
                if(data.redirect) redirectDest = data.redirect;
            }).catch(e => {
                // fallback remains
            });
            
        // 2. Execute Backend Logout Cleanly
        fetch('/RMU-Medical-Management-System/php/logout_handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `csrf=${encodeURIComponent(csrfToken)}&origin=${encodeURIComponent(origin)}`
        }).then(r => r.json()).then(d => {
            if(d.redirect) redirectDest = d.redirect;
        }).catch(console.error);

        // 3. UI Real-time Interval
        const intr = setInterval(() => {
            s--;
            txtEl.innerText = s;
            ring.style.strokeDashoffset = 282.74 * ((totalS - s) / totalS);
            if (s <= 0) {
                clearInterval(intr);
                // Countdown finished. Hide timer UI and show Warm Message.
                document.querySelector('.logout-countdown-svg').style.display = 'none';
                txtEl.style.display = 'none';
                
                msgEl.innerText = `"${fetchedMsg}"`;
                msgEl.style.opacity = '1';
                msgEl.style.fontSize = '1.25rem';
                msgEl.style.color = '#2F80ED';
                msgEl.style.fontWeight = '500';
                msgEl.style.marginTop = '1rem';
                msgEl.style.lineHeight = '1.5';
                msgEl.style.textAlign = 'center';
                msgEl.style.transform = 'scale(1.05)';
                msgEl.style.transition = 'all 0.5s ease';
                
                // Wait 3 seconds to let the user read the warm message, then redirect
                setTimeout(() => {
                    window.location.href = redirectDest;
                }, 3000);
            }
        }, 1000);
    });
});