// Phase 3: Advanced Logout System Logic
document.addEventListener('DOMContentLoaded', () => {
    
    // Check if the page already injected a forced/timeout modal directly
    if (document.getElementById('forcedLogoutOverlay')) {
        return; // Interstitial handles itself
    }

    const logoutBtns = document.querySelectorAll('.adm-logout-btn, a[href*="logout.php"]');
    if(logoutBtns.length === 0) return;

    // Build the DOM
    const overlay = document.createElement('div');
    overlay.className = 'logout-overlay';
    overlay.innerHTML = `
        <div class="logout-modal" role="dialog" aria-labelledby="logoutModalTitle" aria-modal="true" tabindex="-1">
            
            <!-- Standard Confirmation View -->
            <div class="logout-confirm-wrapper" id="logoutConfirmView">
                <div class="logout-icon"><i class="fas fa-right-from-bracket"></i></div>
                <h2 id="logoutModalTitle">Confirm Logout</h2>
                <p>Are you sure you want to log out of the RMU Medical Management System?</p>
                <div class="logout-actions">
                    <button class="logout-btn-cancel" id="logoutCancelBtn">Cancel</button>
                    <button class="logout-btn-confirm" id="logoutConfirmBtn">Logout</button>
                </div>
            </div>

            <!-- Countdown View -->
            <div class="logout-countdown-wrapper" id="logoutCountdownView">
                <div class="logout-countdown-circle">
                    <svg class="logout-countdown-svg" viewBox="0 0 100 100">
                        <circle class="logout-countdown-bg" cx="50" cy="50" r="45"></circle>
                        <circle class="logout-countdown-progress" id="logoutProgressCircle" cx="50" cy="50" r="45" style="stroke-dasharray: 282.74; stroke-dashoffset: 0;"></circle>
                    </svg>
                    <div class="logout-countdown-text" id="logoutCountdownText">3</div>
                </div>
                <div class="logout-health-msg" id="logoutHealthMsg">...</div>
                <div class="logout-redirect-text">You will be redirected to the login page shortly...</div>
            </div>

        </div>
    `;
    document.body.appendChild(overlay);

    const confirmView = document.getElementById('logoutConfirmView');
    const countdownView = document.getElementById('logoutCountdownView');
    const cancelBtn = document.getElementById('logoutCancelBtn');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    
    const progressCircle = document.getElementById('logoutProgressCircle');
    const countdownText = document.getElementById('logoutCountdownText');
    const healthMsg = document.getElementById('logoutHealthMsg');
    
    let isCountingDown = false;
    let urlToLoad = null;

    // Attach Intercept
    logoutBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            urlToLoad = btn.getAttribute('href') || '/RMU-Medical-Management-System/php/logout.php';
            
            // Check if CSRF meta tag exists globally
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (csrfMeta && !urlToLoad.includes('csrf=')) {
                const sep = urlToLoad.includes('?') ? '&' : '?';
                urlToLoad += sep + 'csrf=' + encodeURIComponent(csrfMeta.getAttribute('content'));
            }

            // Append dashboard origin
            const dashAttr = btn.getAttribute('data-dashboard') || '';
            if (dashAttr) {
                const sep = urlToLoad.includes('?') ? '&' : '?';
                urlToLoad += sep + 'origin=' + encodeURIComponent(dashAttr);
            }

            openModal();
        });
    });

    function openModal() {
        document.body.classList.add('logout-active');
        overlay.classList.add('active');
        confirmView.style.display = 'block';
        countdownView.style.display = 'none';
        isCountingDown = false;
        setTimeout(() => cancelBtn.focus(), 100);
    }

    function closeModal() {
        if (isCountingDown) return; // Prevent cancelling mid-countdown
        overlay.classList.remove('active');
        document.body.classList.remove('logout-active');
    }

    // Bind escapes
    cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => {
        if(e.target === overlay) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeModal();
        }
    });

    // Handle Confirm
    confirmBtn.addEventListener('click', async () => {
        isCountingDown = true;
        confirmView.style.display = 'none';
        countdownView.style.display = 'flex';
        
        // Fetch health message
        try {
            const r = await fetch('/RMU-Medical-Management-System/php/handlers/get_health_message.php');
            const data = await r.json();
            if(data.success && data.message) {
                healthMsg.textContent = `"${data.message}"`;
                healthMsg.classList.add('show');
            } else {
                healthMsg.textContent = '"Your health is your greatest wealth."';
                healthMsg.classList.add('show');
            }
        } catch (err) {
            healthMsg.textContent = '"Your health is your greatest wealth."';
            healthMsg.classList.add('show');
        }

        // Fetch duration if provided (fallback to 3)
        // You could fetch from an endpoint, but standard is 3s
        // Let's rely on the DB default of 3s mapped manually, or you could ajax config. 
        // We'll stick to 3 for instant responsiveness as requested in design.
        const duration = 3; 
        let currentSecond = duration;
        
        const circumference = 2 * Math.PI * 45; // 282.74
        
        const interval = setInterval(() => {
            currentSecond--;
            countdownText.textContent = currentSecond;
            
            // Calculate stroke dashoffset
            const pct = (duration - currentSecond) / duration;
            progressCircle.style.strokeDashoffset = circumference * pct;

            if (currentSecond <= 0) {
                clearInterval(interval);
                window.location.href = urlToLoad;
            }
        }, 1000);
    });
});
