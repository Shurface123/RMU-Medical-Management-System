<?php
// ============================================================
// NURSE DASHBOARD - SYSTEM SETTINGS (MODULE 14)
// ============================================================
if (!isset($conn)) exit;
?>

<div class="tab-content active" id="settings">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-sliders-h pulse-fade" style="margin-right:.8rem;"></i> Display & Alert Configuration</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Personalize terminal aesthetics and auditory clinical triggers.</p>
        </div>
        <div>
            <span class="adm-badge" style="background:rgba(var(--primary-rgb),0.1); color:var(--primary); font-weight:800; padding:.6rem 1.2rem; border-radius:10px; border:1px solid rgba(var(--primary-rgb),0.2); display:flex; align-items:center; gap:.8rem;">
                <i class="fas fa-save"></i> PREFERENCES AUTO-SYNC
            </span>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:3rem; align-items:start;">
        
        <!-- Theme & UI Preferences -->
        <div class="adm-card shadow-sm" style="border-radius:24px; border:none; overflow:hidden;">
            <div class="adm-card-header" style="background:var(--surface-2); padding:2rem 3rem; border-bottom:1.5px solid var(--border);">
                <h3 style="margin:0; font-weight:900; font-size:1.6rem; color:var(--text-primary); letter-spacing:-0.4px;">
                    <i class="fas fa-palette text-primary" style="margin-right:.8rem;"></i> Aesthetic Interface
                </h3>
            </div>
            <div class="adm-card-body" style="padding:4rem 3.5rem;">
                <p style="font-size:1.3rem; line-height:1.6; color:var(--text-secondary); margin-bottom:3.5rem; font-weight:500;">
                    The RMU Nurse Dashboard defaults to a high-fidelity **Clinical Standard** for maximum clarity. Switch to **Night Shift** mode for reduced ocular strain during nocturnal duties.
                </p>
                
                <div style="display:flex; flex-direction:column; gap:2rem;">
                    <label style="display:flex; align-items:center; justify-content:space-between; padding:2rem; background:var(--surface-2); border-radius:18px; border:1.8px solid var(--border); cursor:pointer; transition:0.3s cubic-bezier(0.4, 0, 0.2, 1);" class="pref-card">
                        <div style="display:flex; align-items:center; gap:2rem;">
                            <div style="width:60px; height:60px; border-radius:18px; background:#fff; display:flex; align-items:center; justify-content:center; color:var(--warning); font-size:2.2rem; border:1.5px solid var(--border); box-shadow:0 10px 20px rgba(0,0,0,0.05);">
                                <i class="fas fa-sun"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; font-size:1.6rem; color:var(--text-primary);">Clinical Standard</h4>
                                <div style="font-size:1.15rem; color:var(--text-muted); font-weight:600;">High contrast with safety orange.</div>
                            </div>
                        </div>
                        <input type="radio" name="theme_mode" value="light" class="theme-toggle" checked style="width:28px; height:28px; accent-color:var(--primary);">
                    </label>

                    <label style="display:flex; align-items:center; justify-content:space-between; padding:2rem; background:rgba(44, 62, 80, 0.03); border-radius:18px; border:1.8px solid var(--border); cursor:pointer; transition:0.3s cubic-bezier(0.4, 0, 0.2, 1);" class="pref-card">
                        <div style="display:flex; align-items:center; gap:2rem;">
                            <div style="width:60px; height:60px; border-radius:18px; background:#1a1a2e; display:flex; align-items:center; justify-content:center; color:#fff; font-size:2.2rem; box-shadow:0 10px 20px rgba(0,0,0,0.15);">
                                <i class="fas fa-moon"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; font-size:1.6rem; color:var(--text-primary);">Night Shift Protocol</h4>
                                <div style="font-size:1.15rem; color:var(--text-muted); font-weight:600;">Deep clinical palette for low light.</div>
                            </div>
                        </div>
                        <input type="radio" name="theme_mode" value="dark" class="theme-toggle" style="width:28px; height:28px; accent-color:var(--primary);">
                    </label>
                </div>
            </div>
        </div>

        <!-- Notification & Alert Preferences -->
        <div class="adm-card shadow-sm" style="border-radius:24px; border:none; overflow:hidden;">
            <div class="adm-card-header" style="background:var(--surface-2); padding:2rem 3rem; border-bottom:1.5px solid var(--border);">
                <h3 style="margin:0; font-weight:900; font-size:1.6rem; color:var(--text-primary); letter-spacing:-0.4px;">
                    <i class="fas fa-volume-up text-danger" style="margin-right:.8rem;"></i> Audio Telemetry
                </h3>
            </div>
            <div class="adm-card-body" style="padding:4rem 3.5rem;">
                <p style="font-size:1.3rem; line-height:1.6; color:var(--text-secondary); margin-bottom:3.5rem; font-weight:500;">
                    Configure real-time terminal audio behavior. Auditory clinical triggers assist in immediate response times to critical ward status changes.
                </p>
                
                <div style="display:flex; flex-direction:column; gap:2rem; margin-bottom:4rem;">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:2rem; background:rgba(var(--danger-rgb),0.02); border-radius:18px; border:1.5px solid var(--border);">
                        <div style="display:flex; align-items:center; gap:2rem;">
                            <div style="width:50px; height:50px; border-radius:14px; background:rgba(var(--danger-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2.2rem; color:var(--danger);">
                                <i class="fas fa-ambulance"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; font-size:1.5rem; color:var(--text-primary);">Code Blue Siren</h4>
                                <div style="font-size:1.15rem; color:var(--text-muted); font-weight:600;">Looping trigger for incoming emergencies.</div>
                            </div>
                        </div>
                        <label class="adm-switch" style="width:65px; height:34px;">
                            <input type="checkbox" id="sndEmergency" checked>
                            <span class="adm-slider round" style="border-radius:34px;"></span>
                        </label>
                    </div>

                    <div style="display:flex; align-items:center; justify-content:space-between; padding:2rem; background:rgba(var(--primary-rgb),0.02); border-radius:18px; border:1.5px solid var(--border);">
                        <div style="display:flex; align-items:center; gap:2rem;">
                            <div style="width:50px; height:50px; border-radius:14px; background:rgba(var(--primary-rgb),0.1); display:flex; align-items:center; justify-content:center; font-size:2.2rem; color:var(--primary);">
                                <i class="fas fa-comment-medical"></i>
                            </div>
                            <div>
                                <h4 style="margin:0; font-weight:800; font-size:1.5rem; color:var(--text-primary);">Intercom Chimes</h4>
                                <div style="font-size:1.15rem; color:var(--text-muted); font-weight:600;">Soft cues for doctor-staff coordination.</div>
                            </div>
                        </div>
                        <label class="adm-switch" style="width:65px; height:34px;">
                            <input type="checkbox" id="sndMessages" checked>
                            <span class="adm-slider round" style="border-radius:34px;"></span>
                        </label>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <button class="adm-btn adm-btn-ghost" style="padding:1.4rem; border-radius:14px; font-weight:800; border-width:2px; font-size:1.2rem; display:flex; align-items:center; justify-content:center; gap:1rem;" onclick="testEmergencySound()">
                        <i class="fas fa-play-circle" style="font-size:1.6rem; color:var(--danger);"></i> TEST SIREN
                    </button>
                    <button class="adm-btn adm-btn-ghost" style="padding:1.4rem; border-radius:14px; font-weight:800; border-width:2px; font-size:1.2rem; display:flex; align-items:center; justify-content:center; gap:1rem;" onclick="testMessageSound()">
                        <i class="fas fa-play-circle" style="font-size:1.6rem; color:var(--primary);"></i> TEST CHIME
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Hidden Audio Elements -->
    <audio id="audioEmergency" src="../assets/audio/emergency_siren.mp3" preload="auto"></audio>
    <audio id="audioMessage" src="../assets/audio/message_chime.mp3" preload="auto"></audio>

</div>

<style>
.adm-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
.adm-switch input { opacity: 0; width: 0; height: 0; }
.adm-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; }
.adm-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 4px; bottom: 4px; background-color: white; transition: .4s; }
input:checked + .adm-slider { background-color: var(--primary); }
input:checked + .adm-slider:before { transform: translateX(24px); }
.adm-slider.round { border-radius: 34px; }
.adm-slider.round:before { border-radius: 50%; }
</style>

<?php include __DIR__.'/../../includes/active_sessions_panel.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Load existing settings
    const theme = localStorage.getItem('rmu_theme') || 'light';
    const sndEmerg = localStorage.getItem('rmu_snd_emerg') !== 'false';
    const sndMsg = localStorage.getItem('rmu_snd_msg') !== 'false';

    // Apply UI states
    if(theme === 'dark') {
        document.querySelector('input[name="theme_mode"][value="dark"]').checked = true;
    }
    document.getElementById('sndEmergency').checked = sndEmerg;
    document.getElementById('sndMessages').checked = sndMsg;

    // Theme Switcher Event
    document.querySelectorAll('.theme-toggle').forEach(el => {
        el.addEventListener('change', (e) => {
            if(window.applyTheme) applyTheme(e.target.value);
            else {
                document.documentElement.setAttribute('data-theme', e.target.value);
                localStorage.setItem('rmu_theme', e.target.value);
            }
        });
    });

    // Audio Switcher Events
    document.getElementById('sndEmergency').addEventListener('change', (e) => localStorage.setItem('rmu_snd_emerg', e.target.checked));
    document.getElementById('sndMessages').addEventListener('change', (e) => localStorage.setItem('rmu_snd_msg', e.target.checked));
});

function testEmergencySound() {
    const a = document.getElementById('audioEmergency');
    a.currentTime = 0;
    a.play().catch(e => alert("Audio playback failed. Please interact with the page first."));
}

function testMessageSound() {
    const a = document.getElementById('audioMessage');
    a.currentTime = 0;
    a.play().catch(e => alert("Audio playback failed. Please interact with the page first."));
}
</script>
