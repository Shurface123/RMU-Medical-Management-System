<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'admin_profile';
$page_title = 'Professional Profile';
include '../includes/_sidebar.php';

$adminName = $_SESSION['name'] ?? 'Admin';
?>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-user-shield" style="color:var(--primary);margin-right:.8rem;"></i>Administrative Hub</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar" style="overflow:hidden; border:2px solid var(--primary-light);">
                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $_SESSION['profile_image'] ?? 'default-avatar.png' ?>" style="width:100%; height:100%; object-fit:cover;" class="prof-display-img">
            </div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Profile Hero Section -->
        <div class="adm-card" style="border-radius:24px; margin-bottom:2.5rem; overflow:hidden; position:relative; background:var(--surface);">
            <div style="height:160px; background:linear-gradient(135deg, var(--primary) 0%, #1a2a6c 100%); position:relative;">
                <div style="position:absolute; inset:0; background:url('/RMU-Medical-Management-System/image/pattern.png'); opacity:0.1;"></div>
            </div>
            
            <div style="padding:0 3rem 3rem; margin-top:-70px; display:flex; align-items:flex-end; gap:3rem; flex-wrap:wrap; position:relative;">
                <div style="position:relative;">
                    <div style="width:150px; height:150px; border-radius:35px; background:var(--surface); padding:8px; box-shadow:var(--shadow-lg); border:1px solid var(--border);">
                        <div id="profPhotoContainer" style="width:100%; height:100%; border-radius:30px; overflow:hidden; background:var(--primary-light); display:flex; align-items:center; justify-content:center;">
                            <?php 
                            $photo = $_SESSION['profile_image'] ?? null;
                            if ($photo): ?>
                                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $photo ?>" class="prof-display-img" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span style="font-size:5rem; font-weight:800; color:var(--primary);"><?= strtoupper(substr($adminName, 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button id="btnUploadPhoto" class="btn btn-primary" style="position:absolute; bottom:0; right:0; width:50px; height:50px; border-radius:15px; padding:0; justify-content:center; box-shadow:var(--shadow-md); border:4px solid var(--surface);"><span class="btn-text">
                        <i class="fas fa-camera" style="font-size:1.5rem;"></i>
                    </span></button>
                    <input type="file" id="profPhotoInput" style="display:none;" accept="image/*">
                </div>
                
                <div style="flex:1; padding-bottom:1rem;">
                    <h1 style="font-size:3.2rem; font-weight:900; color:var(--text-primary); margin:0; letter-spacing:-1px;"><?= htmlspecialchars($adminName) ?></h1>
                    <div style="display:flex; align-items:center; gap:2rem; margin-top:1rem;">
                        <span class="adm-badge adm-badge-primary" style="padding:0.6rem 1.4rem; font-size:1.1rem; font-weight:800;"><i class="fas fa-shield-alt"></i> MASTER ADMINISTRATOR</span>
                        <span style="font-size:1.2rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-calendar-alt" style="color:var(--primary); margin-right:0.5rem;"></i> System Access Established: 2024</span>
                    </div>
                </div>

                <div style="display:flex; gap:2.5rem; padding-bottom:1.5rem;">
                    <div style="text-align:center;">
                        <div style="font-size:2.8rem; font-weight:900; color:var(--text-primary); line-height:1;" id="displaySessionCount">1</div>
                        <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); font-weight:800; margin-top:0.5rem;">Active Nodes</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:2.8rem; font-weight:900; color:var(--success); line-height:1;">VERIFIED</div>
                        <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted); font-weight:800; margin-top:0.5rem;">Identity</div>
                    </div>
                </div>
            </div>
            
            <!-- V2 Profile Tabs -->
            <div class="adm-tabs-container" style="padding:0 2.5rem 1rem; display:flex; gap:1.5rem; border-top:1px solid var(--border);">
                <button class="prof-tab-btn active" data-target="tab-overview"><i class="fas fa-id-card"></i> Profile Overview</button>
                <button class="prof-tab-btn" data-target="tab-security"><i class="fas fa-user-lock"></i> Privacy & Security</button>
                <button class="prof-tab-btn" data-target="tab-notifications"><i class="fas fa-bell"></i> System Alerts</button>
                <button class="prof-tab-btn" data-target="tab-activity"><i class="fas fa-history"></i> Access Audit</button>
            </div>
        </div>

        <div class="prof-tab-panes">
            <!-- TAB: OVERVIEW -->
            <div class="prof-pane active" id="tab-overview">
                <div class="adm-grid-2">
                    <div class="adm-card" style="padding:2.5rem; border-radius:20px;">
                        <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:2.5rem;">
                            <div style="width:50px; height:50px; border-radius:12px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="fas fa-user-edit"></i></div>
                            <h3 style="font-size:1.8rem; font-weight:800; margin:0;">Account Coordinates</h3>
                        </div>
                        <form id="formPersonalInfo">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-muted);">Administrative Name</label>
                                    <input type="text" id="profName" class="form-control" value="<?= htmlspecialchars($adminName) ?>" required style="border-radius:12px; padding:1.2rem;">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-muted);">Gateway Email</label>
                                    <input type="email" id="profEmail" class="form-control" value="<?= $_SESSION['email'] ?? '' ?>" required style="border-radius:12px; padding:1.2rem;">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-muted);">Secondary Liaison Name</label>
                                    <input type="text" id="profEmName" class="form-control" style="border-radius:12px; padding:1.2rem;">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:700; color:var(--text-muted);">Liaison Hotlink (Phone)</label>
                                    <input type="tel" id="profEmPhone" class="form-control" style="border-radius:12px; padding:1.2rem;">
                                </div>
                            </div>
                            <div style="display:flex; justify-content:flex-end; margin-top:3rem;">
                                <button type="submit" class="btn btn-primary" style="padding:1.2rem 3rem; border-radius:14px; font-weight:700;"><span class="btn-text">
                                    <i class="fas fa-sync-alt"></i> Commit Profile Changes
                                </span></button>
                            </div>
                        </form>
                    </div>

                    <div class="adm-card" style="padding:2.5rem; border-radius:20px;">
                        <div style="display:flex; align-items:center; gap:1.2rem; margin-bottom:2.5rem;">
                            <div style="width:50px; height:50px; border-radius:12px; background:var(--info-light); color:var(--info); display:flex; align-items:center; justify-content:center; font-size:1.5rem;"><i class="fas fa-brain"></i></div>
                            <h3 style="font-size:1.8rem; font-weight:800; margin:0;">Operational Intel</h3>
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem;">
                            <div>
                                <label style="display:block; font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-bottom:0.8rem;">Last Successful Login</label>
                                <div id="displayLastLogin" style="font-size:1.6rem; font-weight:800; color:var(--text-primary);">FETCHING...</div>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-bottom:0.8rem;">Access Clearance</label>
                                <span class="adm-badge adm-badge-success" style="font-size:1rem; padding:0.5rem 1rem;">LEVEL 10: SUPERUSER</span>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-bottom:0.8rem;">Multi-Factor Auth</label>
                                <span class="adm-badge" style="background:rgba(231,76,60,0.1); color:#E74C3C; font-weight:800;">DEACTIVATED</span>
                            </div>
                            <div>
                                <label style="display:block; font-size:0.9rem; color:var(--text-muted); text-transform:uppercase; font-weight:800; margin-bottom:0.8rem;">Account Status</label>
                                <div style="display:flex; align-items:center; gap:0.6rem; color:var(--success); font-weight:800; font-size:1.2rem;">
                                    <span style="width:10px; height:10px; border-radius:50%; background:currentColor; display:inline-block; animation:pulse 1.5s infinite;"></span>
                                    ONLINE & SECURED
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SECURITY -->
            <div class="prof-pane" id="tab-security">
                <div class="adm-grid-2">
                    <div class="adm-card" style="padding:2.5rem; border-radius:20px;">
                        <h3 style="font-size:1.8rem; font-weight:800; margin-bottom:2.5rem;"><i class="fas fa-key" style="color:var(--warning);"></i> Credentials Rotation</h3>
                        <form id="formPassword">
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label>Current Master Credentials</label>
                                <input type="password" id="pwdCurrent" class="form-control" required style="border-radius:12px; padding:1.2rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <label>New Administrative Password</label>
                                <input type="password" id="pwdNew" class="form-control" required style="border-radius:12px; padding:1.2rem;">
                            </div>
                            <div class="form-group" style="margin-bottom:2.5rem;">
                                <label>Re-verify New Credentials</label>
                                <input type="password" id="pwdConfirm" class="form-control" required style="border-radius:12px; padding:1.2rem;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%; height:55px; border-radius:15px; justify-content:center; font-weight:800; font-size:1.1rem;"><span class="btn-text">
                                ROTATE SYSTEM CREDENTIALS
                            </span></button>
                        </form>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:2rem;">
                        <div class="adm-card" style="padding:2.5rem; border-radius:20px;">
                            <h3 style="font-size:1.6rem; font-weight:800; margin-bottom:1.5rem;"><i class="fas fa-fingerprint" style="color:var(--primary);"></i> Active Sessions</h3>
                            <div id="activeSessionsList">
                                <!-- Dynamic Sessions -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: NOTIFICATIONS -->
            <div class="prof-pane" id="tab-notifications">
                <div class="adm-card" style="max-width:800px; margin:0 auto; padding:3.5rem; border-radius:24px;">
                    <h2 style="font-size:2.2rem; font-weight:900; margin-bottom:1rem; color:var(--text-primary); text-align:center;">Communication Matrix</h2>
                    <p style="color:var(--text-muted); text-align:center; margin-bottom:4rem; font-size:1.2rem;">Configure how the system relays critical telemetry and administrative logs.</p>
                    
                    <form id="formNotifications">
                        <div style="display:flex; flex-direction:column; gap:3rem;">
                            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:2rem; border-bottom:1px solid var(--border);">
                                <div>
                                    <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);">Real-time Console Alerts</div>
                                    <div style="color:var(--text-muted); font-size:1.1rem; margin-top:0.4rem;">Immediate visual flags for clinical and system exceptions.</div>
                                </div>
                                <label class="v2-switch">
                                    <input type="checkbox" id="notifInApp" checked>
                                    <span class="v2-slider"></span>
                                </label>
                            </div>
                            
                            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:2rem; border-bottom:1px solid var(--border);">
                                <div>
                                    <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);">Institutional Email Digests</div>
                                    <div style="color:var(--text-muted); font-size:1.1rem; margin-top:0.4rem;">Daily consolidated reports of administrative activities.</div>
                                </div>
                                <label class="v2-switch">
                                    <input type="checkbox" id="notifEmail">
                                    <span class="v2-slider"></span>
                                </label>
                            </div>

                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div>
                                    <div style="font-weight:800; font-size:1.4rem; color:var(--text-primary);">Cloud Dispatch (Push)</div>
                                    <div style="color:var(--text-muted); font-size:1.1rem; margin-top:0.4rem;">Direct browser/device notifications for high-priority events.</div>
                                </div>
                                <label class="v2-switch">
                                    <input type="checkbox" id="notifPush">
                                    <span class="v2-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div style="display:flex; justify-content:center; margin-top:5rem;">
                            <button type="submit" class="btn btn-primary" style="padding:1.5rem 5rem; border-radius:18px; font-size:1.3rem; font-weight:800;"><span class="btn-text">
                                SYNCHRONIZE PREFERENCES
                            </span></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB: ACTIVITY -->
            <div class="prof-pane" id="tab-activity">
                <div class="adm-card" style="padding:3rem; border-radius:24px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:3rem;">
                        <div>
                            <h3 style="font-size:2rem; font-weight:900; color:var(--text-primary); margin:0;">Security Audit Trail</h3>
                            <p style="color:var(--text-muted); font-size:1.2rem; margin-top:0.5rem;">Cryptographic log of administrative interactions within this node.</p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:3.5rem; font-weight:950; color:var(--primary); line-height:1;" id="monthActionsCount">0</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:1px; margin-top:0.5rem;">Monthly Events</div>
                        </div>
                    </div>
                    
                    <div id="activityFeedList" style="display:flex; flex-direction:column; gap:1.5rem;">
                        <!-- Dynamic Activity Feed -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* ── V2 Profile Custom Styles ── */
.prof-tab-btn {
    background: transparent;
    border: none;
    padding: 1.5rem 0.5rem;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-muted);
    cursor: pointer;
    position: relative;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.prof-tab-btn:hover { color: var(--primary); }
.prof-tab-btn.active { color: var(--primary); }
.prof-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--primary);
    border-radius: 10px;
}

.prof-pane { display: none; animation: profSlideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.prof-pane.active { display: block; }

@keyframes profSlideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }

/* V2 Toggle Switch */
.v2-switch { position: relative; display: inline-block; width: 60px; height: 32px; }
.v2-switch input { opacity: 0; width: 0; height: 0; }
.v2-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background: var(--surface-2); transition: .4s; border-radius: 34px; border: 1px solid var(--border);
}
.v2-slider:before {
    position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 3px;
    background: white; transition: .4s; border-radius: 50%; box-shadow: var(--shadow-sm);
}
input:checked + .v2-slider { background: var(--primary); border-color: var(--primary); }
input:checked + .v2-slider:before { transform: translateX(28px); }

.session-card {
    background: var(--surface-2); border: 1px solid var(--border); border-radius: 15px;
    padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;
}
.btn-revoke {
    background: rgba(231,76,60,0.1); color: #E74C3C; border: none; padding: 0.6rem 1.2rem;
    border-radius: 10px; font-weight: 800; font-size: 0.9rem; cursor: pointer; transition: var(--transition);
}
.btn-revoke:hover { background: #E74C3C; color: white; }

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
</style>

<script src="admin_profile_actions.js"></script>
<script>
$(document).ready(function() {
    // Tab Switching Logic
    $('.prof-tab-btn').on('click', function() {
        const target = $(this).data('target');
        $('.prof-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.prof-pane').removeClass('active');
        $('#' + target).addClass('active');

        // Trigger data loading via the AdminProfile instance
        if(window.AdminProfile) {
            if(target === 'tab-security') window.AdminProfile.loadSessions();
            if(target === 'tab-notifications') window.AdminProfile.loadNotifications();
            if(target === 'tab-activity') window.AdminProfile.loadActivity();
        }
    });

    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');
    const html        = document.documentElement;
    function applyTheme(t) { html.setAttribute('data-theme',t); localStorage.setItem('rmu_theme',t); themeIcon.className=t==='dark'?'fas fa-sun':'fas fa-moon'; }
    themeToggle?.addEventListener('click', () => applyTheme(html.getAttribute('data-theme')==='dark'?'light':'dark'));
    
    // Sidebar
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
    overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });
});
</script>
</body>
</html>
