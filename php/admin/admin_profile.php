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
            <span class="adm-page-title"><i class="fas fa-user-shield"></i> Administrative Profile</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><?php echo strtoupper(substr($adminName, 0, 1)); ?></div>
        </div>
    </div>

    <div class="adm-content">
        <!-- Profile Header -->
        <div class="adm-card" style="border-radius:24px; margin-bottom:2.5rem; overflow:hidden; position:relative;">
            <div style="height:140px; background:linear-gradient(135deg, var(--primary), var(--secondary)); position:relative;">
                <div style="position:absolute; top:-20px; right:-20px; width:150px; height:150px; border-radius:50%; background:rgba(255,255,255,0.1);"></div>
            </div>
            <div style="padding:0 3rem 2.8rem; margin-top:-60px; display:flex; align-items:flex-end; gap:2.5rem; flex-wrap:wrap; position:relative;">
                <div style="position:relative;">
                    <div style="width:140px; height:140px; border-radius:35px; background:var(--surface); padding:8px; box-shadow:var(--shadow-lg);">
                        <div id="profPhotoContainer" style="width:100%; height:100%; border-radius:30px; overflow:hidden; background:var(--primary-light); display:flex; align-items:center; justify-content:center;">
                            <?php 
                            $photo = $_SESSION['profile_image'] ?? null;
                            if ($photo): ?>
                                <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $photo ?>" class="prof-display-img" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <span style="font-size:4rem; font-weight:800; color:var(--primary);"><?= strtoupper(substr($adminName, 0, 1)) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button id="btnUploadPhoto" class="btn btn-primary" style="position:absolute; bottom:-5px; right:-5px; width:45px; height:45px; border-radius:15px; padding:0; justify-content:center; box-shadow:var(--shadow-md);"><span class="btn-text">
                        <i class="fas fa-camera" style="font-size:1.4rem;"></i>
                    </span></button>
                    <input type="file" id="profPhotoInput" style="display:none;" accept="image/*">
                </div>
                
                <div style="flex:1; padding-bottom:1rem;">
                    <h1 style="font-size:2.8rem; font-weight:800; color:var(--text-primary); margin:0;"><?= htmlspecialchars($adminName) ?></h1>
                    <div style="display:flex; align-items:center; gap:1.8rem; margin-top:0.8rem;">
                        <span class="adm-badge adm-badge-primary" style="padding:0.6rem 1.2rem; font-size:1.1rem;"><i class="fas fa-id-shield"></i> SYSTEM ADMINISTRATOR</span>
                        <span style="font-size:1.3rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-calendar-check" style="color:var(--primary);"></i> Joined Mar 2024</span>
                    </div>
                </div>

                <div style="display:flex; gap:1.5rem; padding-bottom:1rem;">
                    <div style="text-align:center; padding:0 2rem; border-right:1px solid var(--border);">
                        <div style="font-size:2rem; font-weight:800; color:var(--text-primary);" id="displaySessionCount">...</div>
                        <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); font-weight:700;">Sessions</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:2rem; font-weight:800; color:var(--success);">ACTIVE</div>
                        <div style="font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); font-weight:700;">Status</div>
                    </div>
                </div>
            </div>
            
            <div class="adm-tabs" style="margin-bottom:0; padding:0 2rem;">
                <button class="btn btn-outline btn-icon adm-tab-btn active" data-target="tab-overview"><span class="btn-text"><i class="fas fa-id-card"></i> Overview</span></button>
                <button class="btn btn-primary adm-tab-btn" data-target="tab-security"><span class="btn-text"><i class="fas fa-shield-halved"></i> Security</span></button>
                <button class="btn btn-primary adm-tab-btn" data-target="tab-notifications"><span class="btn-text"><i class="fas fa-bell"></i> Notifications</span></button>
                <button class="btn btn-primary adm-tab-btn" data-target="tab-activity"><span class="btn-text"><i class="fas fa-clock-rotate-left"></i> Audit Feed</span></button>
            </div>
        </div>

        <div class="adm-tab-content">
            <!-- TAB: OVERVIEW -->
            <div class="adm-tab-pane active" id="tab-overview">
                <div class="adm-grid-2">
                    <div class="adm-card">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-user-pen"></i> Personal Information</h3>
                        </div>
                        <div class="adm-card-body">
                            <form id="formPersonalInfo">
                                <div class="adm-grid-2">
                                    <div class="adm-form-group">
                                        <label>Full Legal Name</label>
                                        <input type="text" id="profName" class="adm-search-input" value="<?= htmlspecialchars($adminName) ?>" required>
                                    </div>
                                    <div class="adm-form-group">
                                        <label>System Email Address</label>
                                        <input type="email" id="profEmail" class="adm-search-input" value="<?= $_SESSION['email'] ?? '' ?>" required>
                                    </div>
                                    <div class="adm-form-group">
                                        <label>Emergency Contact Name</label>
                                        <input type="text" id="profEmName" class="adm-search-input">
                                    </div>
                                    <div class="adm-form-group">
                                        <label>Emergency Contact Phone</label>
                                        <input type="tel" id="profEmPhone" class="adm-search-input">
                                    </div>
                                </div>
                                <div style="display:flex; justify-content:flex-end; margin-top:1.5rem;">
                                    <button type="submit" class="btn btn-primary"><span class="btn-text">
                                        <i class="fas fa-cloud-arrow-up"></i> Save Profile Updates
                                    </span></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="adm-card">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-circle-info"></i> Account Intelligence</h3>
                        </div>
                        <div class="adm-card-body">
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem;">
                                <div class="adm-stat-mini">
                                    <label style="display:block; font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.5rem;">Last Authentication</label>
                                    <strong id="displayLastLogin" style="font-size:1.5rem; color:var(--text-primary);">...</strong>
                                </div>
                                <div class="adm-stat-mini">
                                    <label style="display:block; font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.5rem;">Account Privilege</label>
                                    <div class="adm-badge adm-badge-success">SUPER USER</div>
                                </div>
                                <div class="adm-stat-mini">
                                    <label style="display:block; font-size:1.1rem; color:var(--text-muted); text-transform:uppercase; margin-bottom:0.5rem;">Two-Factor Auth</label>
                                    <span class="adm-badge" style="background:rgba(239, 68, 68, 0.1); color:#ef4444;">NOT CONFIGURED</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SECURITY -->
            <div class="adm-tab-pane" id="tab-security">
                <div class="adm-grid-2">
                    <div class="adm-card">
                        <div class="adm-card-header">
                            <h3><i class="fas fa-lock"></i> Mandatory Security Update</h3>
                        </div>
                        <div class="adm-card-body">
                            <form id="formPassword">
                                <div class="adm-form-group">
                                    <label>Current Master Password</label>
                                    <input type="password" id="pwdCurrent" class="adm-search-input" required>
                                </div>
                                <div class="adm-form-group">
                                    <label>New Administrative Password</label>
                                    <input type="password" id="pwdNew" class="adm-search-input" required>
                                </div>
                                <div class="adm-form-group">
                                    <label>Confirm Global Access Password</label>
                                    <input type="password" id="pwdConfirm" class="adm-search-input" required>
                                </div>
                                <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1.5rem; height:50px; justify-content:center;"><span class="btn-text">
                                    Confirm Security Credentials Update
                                </span></button>
                            </form>
                        </div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:2.5rem;">
                        <div class="adm-card">
                            <div class="adm-card-header">
                                <h3><i class="fas fa-microchip"></i> Active Sessions</h3>
                            </div>
                            <div class="adm-card-body" id="activeSessionsList">
                                <!-- Dynamic JS -->
                            </div>
                        </div>
                        <div class="adm-card">
                            <div class="adm-card-header">
                                <h3><i class="fas fa-list-check"></i> Recent Access Events</h3>
                            </div>
                            <div class="adm-card-body" id="loginHistoryList" style="max-height:250px; overflow-y:auto;">
                                <!-- Dynamic JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: NOTIFICATIONS -->
            <div class="adm-tab-pane" id="tab-notifications">
                <div class="adm-card" style="max-width:850px; margin:0 auto;">
                    <div class="adm-card-header">
                        <h3><i class="fas fa-bullhorn"></i> Global Notification Matrix</h3>
                    </div>
                    <div class="adm-card-body">
                        <form id="formNotifications">
                            <div style="display:flex; flex-direction:column; gap:2.5rem;">
                                <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
                                    <div>
                                        <div style="font-weight:700; font-size:1.5rem; color:var(--text-primary);">Dashboard Pulse Alerts</div>
                                        <div style="color:var(--text-muted); font-size:1.3rem;">Real-time critical banners within the management console.</div>
                                    </div>
                                    <input type="checkbox" id="notifInApp" style="width:2.2rem; height:2.2rem; accent-color:var(--primary);" checked>
                                </div>
                                <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:1.5rem; border-bottom:1px solid var(--border);">
                                    <div>
                                        <div style="font-weight:700; font-size:1.5rem; color:var(--text-primary);">Email Intelligence Digests</div>
                                        <div style="color:var(--text-muted); font-size:1.3rem;">Automated summary reports sent to your institutional inbox.</div>
                                    </div>
                                    <input type="checkbox" id="notifEmail" style="width:2.2rem; height:2.2rem; accent-color:var(--primary);">
                                </div>
                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                    <div>
                                        <div style="font-weight:700; font-size:1.5rem; color:var(--text-primary);">PWA Hub Notifications</div>
                                        <div style="color:var(--text-muted); font-size:1.3rem;">Browser-level push alerts for offline-ready monitoring.</div>
                                    </div>
                                    <input type="checkbox" id="notifPush" style="width:2.2rem; height:2.2rem; accent-color:var(--primary);">
                                </div>
                            </div>
                            <div style="display:flex; justify-content:center; margin-top:3.5rem;">
                                <button type="submit" class="btn btn-primary" style="padding:1.2rem 4rem; font-size:1.4rem;"><span class="btn-text">
                                    Synchronize Preferences
                                </span></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- TAB: ACTIVITY -->
            <div class="adm-tab-pane" id="tab-activity">
                <div class="adm-card">
                    <div class="adm-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3><i class="fas fa-fingerprint"></i> Administrative Audit Trail</h3>
                            <p style="color:var(--text-muted); font-size:1.2rem;">Live stream of your secure interactions within this node.</p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:2.5rem; font-weight:900; color:var(--primary); line-height:1;" id="monthActionsCount">0</div>
                            <div style="font-size:0.9rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Actions Index</div>
                        </div>
                    </div>
                    <div class="adm-card-body" id="activityFeedList">
                        <!-- Dynamic JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.adm-tab-pane { display: none; animation: admFadeUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.adm-tab-pane.active { display: block; }

@keyframes admFadeUp { from { opacity:0; transform:translateY(15px); } to { opacity:1; transform:translateY(0); } }

.session-card { display:flex; align-items:center; justify-content:space-between; padding:1.5rem; background:var(--surface-2); border-radius:15px; border:1px solid var(--border); margin-bottom:1.2rem; }
.btn-revoke { color:#ef4444; background:rgba(239, 68, 68, 0.1); padding:0.6rem 1.2rem; border-radius:10px; font-weight:700; font-size:1.1rem; border:none; cursor:pointer; transition:var(--transition); }
.btn-revoke:hover { background:#ef4444; color:#fff; }
</style>

<script src="admin_profile_actions.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new AdminProfile();
    });
</script>
</body>
</html>
</body>
</html>
