/**
 * RMU Medical Sickbay — Admin Profile Actions
 */

class AdminProfile {
    constructor() {
        this.apiBase = '/RMU-Medical-Management-System/php/api/router.php?path=profile/';
        this.init();
    }

    init() {
        this.setupTabs();
        this.loadOverview();
        this.setupForms();
        this.setupPhotoUpload();
    }

    setupTabs() {
        const tabs = document.querySelectorAll('.prof-tab');
        const panes = document.querySelectorAll('.prof-pane');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));
                
                tab.classList.add('active');
                const target = document.getElementById(tab.dataset.target);
                target.classList.add('active');

                // Load data based on tab
                if(tab.dataset.target === 'tab-security') this.loadSessions();
                if(tab.dataset.target === 'tab-notifications') this.loadNotifications();
                if(tab.dataset.target === 'tab-activity') this.loadActivity();
            });
        });
    }

    async loadOverview() {
        try {
            const res = await fetch(this.apiBase + 'overview');
            const data = await res.json();
            if (data.success) {
                const user = data.data;
                document.getElementById('profName').value = user.name;
                document.getElementById('profEmail').value = user.email;
                document.getElementById('profEmName').value = user.emergency_contact_name || '';
                document.getElementById('profEmPhone').value = user.emergency_contact_phone || '';
                
                document.getElementById('displayRole').textContent = (user.user_role || 'Admin').toUpperCase();
                document.getElementById('displayJoined').textContent = 'Joined: ' + user.created_at.split(' ')[0];
                document.getElementById('displayLastLogin').textContent = user.last_login_at || 'Never';
                document.getElementById('displaySessionCount').textContent = user.session_count || 1;
            }
        } catch (e) {
            console.error('Failed to load profile:', e);
        }
    }

    setupForms() {
        document.getElementById('formPersonalInfo')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;
            
            const payload = {
                name: document.getElementById('profName').value,
                email: document.getElementById('profEmail').value,
                emergency_contact_name: document.getElementById('profEmName').value,
                emergency_contact_phone: document.getElementById('profEmPhone').value
            };

            try {
                const res = await fetch(this.apiBase + 'update-info', {
                    method: 'POST', body: JSON.stringify(payload)
                });
                const r = await res.json();
                if (typeof showToast === 'function') {
                    showToast(r.success ? 'Profile information updated successfully' : r.message, r.success ? 'success' : 'error');
                } else {
                    alert(r.success ? 'Profile information updated successfully' : r.message);
                }
                this.loadOverview();
            } catch (error) {
                showToast('Failed to update profile info', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        document.getElementById('formPassword')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            
            const payload = {
                current_password: document.getElementById('pwdCurrent').value,
                new_password: document.getElementById('pwdNew').value
            };
            if(payload.new_password !== document.getElementById('pwdConfirm').value) {
                return showToast("Passwords do not match", "error");
            }
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
            btn.disabled = true;

            try {
                const res = await fetch(this.apiBase + 'change-password', {
                    method: 'POST', body: JSON.stringify(payload)
                });
                const r = await res.json();
                showToast(r.success ? 'Password Changed Successfully' : r.message, r.success ? 'success' : 'error');
                if (r.success) e.target.reset();
            } catch (error) {
                showToast('Failed to change password', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        document.getElementById('formNotifications')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            btn.disabled = true;

            const payload = {
                event_type: 'all_system_events',
                in_app: document.getElementById('notifInApp').checked ? 1 : 0,
                email: document.getElementById('notifEmail').checked ? 1 : 0,
                push: document.getElementById('notifPush').checked ? 1 : 0
            };

            try {
                const res = await fetch(this.apiBase + 'update-notifications', {
                    method: 'POST', body: JSON.stringify(payload)
                });
                const r = await res.json();
                showToast(r.success ? 'Notification preferences saved' : 'Failed to save', r.success ? 'success' : 'error');
            } catch (error) {
                showToast('Failed to update notifications', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    setupPhotoUpload() {
        const uploadBtn = document.getElementById('btnUploadPhoto');
        const fileInput = document.getElementById('profPhotoInput');
        
        uploadBtn?.addEventListener('click', () => fileInput.click());
        
        fileInput?.addEventListener('change', async (e) => {
            if (e.target.files.length === 0) return;
            
            const file = e.target.files[0];
            const formData = new FormData();
            formData.append('photo', file);
            
            const originalHtml = uploadBtn.innerHTML;
            uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            uploadBtn.disabled = true;
            
            try {
                const res = await fetch(this.apiBase + 'upload-photo', {
                    method: 'POST',
                    body: formData
                });
                const r = await res.json();
                if (r.success) {
                    showToast('Profile photo updated', 'success');
                    // Update all profile image instances
                    const newUrl = '/RMU-Medical-Management-System/' + r.data.url;
                    document.querySelectorAll('.adm-avatar img, .prof-display-img').forEach(img => img.src = newUrl);
                } else {
                    showToast(r.message, 'error');
                }
            } catch (error) {
                showToast('Photo upload failed', 'error');
            } finally {
                uploadBtn.innerHTML = originalHtml;
                uploadBtn.disabled = false;
            }
        });
    }

    async loadSessions() {
        const div = document.getElementById('activeSessionsList');
        div.innerHTML = 'Loading...';
        try {
            const res = await fetch(this.apiBase + 'sessions');
            const data = await res.json();
            if(data.success && data.data.active) {
                div.innerHTML = data.data.active.map(s => `
                    <div class="session-card">
                        <div>
                            <div style="font-weight:600; font-size:0.875rem;">${s.browser || 'Unknown Browser'} on ${s.device_type || 'Unknown Device'}</div>
                            <div style="font-size:0.75rem; color:var(--prof-muted);">IP: ${s.ip_address || '127.0.0.1'} | Last active: ${s.last_active}</div>
                        </div>
                        <button class="btn-revoke" onclick="window.AdminProfile.revokeSession(${s.id})">Revoke</button>
                    </div>
                `).join('');
                
                const histDiv = document.getElementById('loginHistoryList');
                if(data.data.history.length === 0) {
                    histDiv.innerHTML = '<p style="color:var(--prof-muted); font-size:0.875rem;">No recent logins found.</p>';
                } else {
                    histDiv.innerHTML = data.data.history.map(h => `
                        <div style="padding:0.75rem 0; border-bottom:1px solid #e2e8f0; font-size:0.875rem;">
                            <span style="font-weight:600;">${h.ip_address || '127.0.0.1'}</span>
                            <span style="color:var(--prof-muted); margin-left:1rem;">${h.created_at}</span>
                        </div>
                    `).join('');
                }
            } else {
                div.innerHTML = 'No active sessions found.';
            }
        } catch(e) { console.error(e); }
    }

    async revokeSession(id) {
        if(!confirm("Are you sure you want to revoke this session?")) return;
        try {
            const res = await fetch(this.apiBase + 'revoke-session', {
                method: 'POST', body: JSON.stringify({session_id: id})
            });
            const r = await res.json();
            if(r.success) this.loadSessions();
        } catch(e) { console.error(e); }
    }

    async loadNotifications() {
        try {
            const res = await fetch(this.apiBase + 'notifications');
            const data = await res.json();
            if(data.success && data.data.length > 0) {
                const prefs = data.data[0];
                document.getElementById('notifInApp').checked = prefs.in_app == 1;
                document.getElementById('notifEmail').checked = prefs.email == 1;
                document.getElementById('notifPush').checked = prefs.push == 1;
            }
        } catch(e) { console.error(e); }
    }

    async loadActivity() {
        const div = document.getElementById('activityFeedList');
        div.innerHTML = 'Loading...';
        try {
            const res = await fetch(this.apiBase + 'activity');
            const data = await res.json();
            if(data.success) {
                document.getElementById('monthActionsCount').textContent = data.data.monthly_actions;
                if(data.data.feed.length === 0) {
                    div.innerHTML = '<p style="color:var(--prof-muted); font-size:0.875rem;">No recent activity.</p>';
                } else {
                    div.innerHTML = data.data.feed.map(a => `
                        <div style="padding:1rem; border-left:2px solid var(--prof-primary); background:#f1f5f9; margin-bottom:1rem; border-radius:0 0.5rem 0.5rem 0;">
                            <div style="font-weight:600; font-size:0.875rem;">${a.action_type || 'Action'}</div>
                            <div style="font-size:0.875rem; color:#475569; margin: 4px 0;">${a.description || ''}</div>
                            <div style="font-size:0.75rem; color:var(--prof-muted);">${a.created_at}</div>
                        </div>
                    `).join('');
                }
            }
        } catch(e) { console.error(e); }
    }
}

window.AdminProfile = new AdminProfile();
