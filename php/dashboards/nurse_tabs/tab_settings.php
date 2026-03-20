<?php
// ============================================================
// NURSE DASHBOARD - SYSTEM SETTINGS (MODULE 14)
// ============================================================
if (!isset($conn)) exit;
?>

<div class="tab-content" id="settings">

    <div class="row align-items-center mb-4">
        <div class="col-12">
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-cogs me-2"></i> System Settings</h4>
            <p class="text-muted mb-0">Customize your dashboard experience, alerts, and aesthetic themes.</p>
        </div>
    </div>

    <div class="row g-4 border-top pt-4">

        <!-- Theme & UI Preferences -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-paint-roller text-primary me-2"></i> Aesthetic & Theme Options</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">The RMU Nurse Dashboard defaults to a high-contrast Light Theme for clinical readability. You may switch to Dark Mode for night shifts.</p>
                    
                    <div class="list-group list-group-flush border rounded">
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3 cursor-pointer">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fas fa-sun text-warning me-2"></i> RMU Light Theme (Default)</h6>
                                <small class="text-muted">High contrast, bright background with Orange accents.</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input theme-toggle" type="radio" name="theme_mode" value="light" id="themeLight" checked style="transform: scale(1.3);">
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3 cursor-pointer">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fas fa-moon text-secondary me-2"></i> Night Shift Dark Mode</h6>
                                <small class="text-muted">Low ocular strain, dark UI elements.</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input theme-toggle" type="radio" name="theme_mode" value="dark" id="themeDark" style="transform: scale(1.3);">
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification & Alert Preferences -->
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-bell text-danger me-2"></i> Alert & Audio Settings</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">Configure how the dashboard notifies you of incoming critical alerts, emergency triggers, and direct messages.</p>
                    
                    <div class="list-group list-group-flush border rounded mb-3">
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3 cursor-pointer">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fas fa-volume-up text-primary me-2"></i> Audio Alerts: Emergency</h6>
                                <small class="text-muted">Play loud siren loop on incoming Code Blue / Rapid Response.</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input setting-toggle" type="checkbox" id="sndEmergency" checked style="transform: scale(1.3);">
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3 cursor-pointer">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fas fa-comment-dots text-info me-2"></i> Audio Alerts: Messages</h6>
                                <small class="text-muted">Play soft chime on incoming Doctor/Admin messages.</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input setting-toggle" type="checkbox" id="sndMessages" checked style="transform: scale(1.3);">
                            </div>
                        </label>
                        <label class="list-group-item d-flex justify-content-between align-items-center p-3 cursor-pointer">
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="fas fa-desktop text-success me-2"></i> Browser Notifications</h6>
                                <small class="text-muted">Show OS-level popups for clinical alerts.</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input setting-toggle" type="checkbox" id="sndBrowser" style="transform: scale(1.3);">
                            </div>
                        </label>
                    </div>

                    <button class="btn btn-outline-danger btn-sm rounded-pill fw-bold" onclick="testEmergencySound()">
                        <i class="fas fa-play me-1"></i> Test Emergency Siren
                    </button>
                    <button class="btn btn-outline-info btn-sm rounded-pill fw-bold ms-2" onclick="testMessageSound()">
                        <i class="fas fa-play me-1"></i> Test Message Chime
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Hidden Audio Elements -->
    <audio id="audioEmergency" src="../assets/audio/emergency_siren.mp3" preload="auto"></audio>
    <audio id="audioMessage" src="../assets/audio/message_chime.mp3" preload="auto"></audio>

</div>

<script>
// ==========================================
// LOCAL STORAGE SETTINGS INJECTION
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    
    // Load existing settings
    const theme = localStorage.getItem('rmu_nurse_theme') || 'light';
    const sndEmerg = localStorage.getItem('rmu_snd_emerg') !== 'false';
    const sndMsg = localStorage.getItem('rmu_snd_msg') !== 'false';
    const sndBwsr = localStorage.getItem('rmu_snd_bwsr') === 'true';

    // Apply UI states
    if(theme === 'dark') {
        document.getElementById('themeDark').checked = true;
        document.body.classList.add('dark-mode');
    }
    document.getElementById('sndEmergency').checked = sndEmerg;
    document.getElementById('sndMessages').checked = sndMsg;
    document.getElementById('sndBrowser').checked = sndBwsr;

    // Theme Switcher Event
    document.querySelectorAll('.theme-toggle').forEach(el => {
        el.addEventListener('change', (e) => {
            if(e.target.value === 'dark') {
                document.body.classList.add('dark-mode');
                localStorage.setItem('rmu_nurse_theme', 'dark');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('rmu_nurse_theme', 'light');
            }
        });
    });

    // Audio Switcher Events
    document.getElementById('sndEmergency').addEventListener('change', (e) => localStorage.setItem('rmu_snd_emerg', e.target.checked));
    document.getElementById('sndMessages').addEventListener('change', (e) => localStorage.setItem('rmu_snd_msg', e.target.checked));
    document.getElementById('sndBrowser').addEventListener('change', (e) => {
        localStorage.setItem('rmu_snd_bwsr', e.target.checked);
        if(e.target.checked) requestBrowserNotificationPermission();
    });
});

function requestBrowserNotificationPermission() {
    if ("Notification" in window) {
        Notification.requestPermission().then(permission => {
            if (permission !== "granted") {
                alert("Browser notifications blocked. Please enable them in your browser settings.");
                document.getElementById('sndBrowser').checked = false;
                localStorage.setItem('rmu_snd_bwsr', false);
            }
        });
    } else {
        alert("This browser does not support desktop notification");
    }
}

// Global exposure for playing alerts (can be called by polling scripts later)
window.playEmergencyAlert = function() {
    if(localStorage.getItem('rmu_snd_emerg') !== 'false') {
        document.getElementById('audioEmergency').play().catch(e => console.log('Audio autoplay blocked by browser'));
    }
};

window.playMessageAlert = function() {
    if(localStorage.getItem('rmu_snd_msg') !== 'false') {
        document.getElementById('audioMessage').play().catch(e => console.log('Audio autoplay blocked by browser'));
    }
};

function testEmergencySound() {
    document.getElementById('audioEmergency').currentTime = 0;
    document.getElementById('audioEmergency').play();
}

function testMessageSound() {
    document.getElementById('audioMessage').currentTime = 0;
    document.getElementById('audioMessage').play();
}
</script>

<style>
.cursor-pointer { cursor: pointer; }
.cursor-pointer:hover { background-color: rgba(0,0,0,0.02); }

/* Basic Dark Mode Implementation */
body.dark-mode {
    background-color: #121212 !important;
    color: #e0e0e0 !important;
}
body.dark-mode .card, body.dark-mode .bg-white {
    background-color: #1e1e1e !important;
    border-color: #333 !important;
}
body.dark-mode .text-dark {
    color: #ffffff !important;
}
body.dark-mode .text-muted {
    color: #a0a0a0 !important;
}
body.dark-mode .list-group-item {
    background-color: transparent !important;
    border-color: #333 !important;
}
</style>
