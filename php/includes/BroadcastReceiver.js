/**
 * BroadcastReceiver.js
 * Handles real-time reception of broadcasts via SSE and displays them based on priority.
 */
class BroadcastReceiver {
    constructor(userId) {
        this.userId = userId;
        this.sse = null;
        this.unreadCount = 0;
        this.ensureNotificationUI();
        this.init();
    }

    init() {
        console.log("BroadcastReceiver initialized for user " + this.userId);
        this.connectSSE();
        this.loadHistory();
    }

    ensureNotificationUI() {
        const topbarRight = document.querySelector('.adm-topbar-right');
        if (!topbarRight) return;

        // If bell doesn't exist, inject it
        if (!document.querySelector('.adm-notif-btn')) {
            const bellHtml = `
                <div style="position:relative; margin-right: 1rem;">
                    <button class="adm-notif-btn" id="notifBtn" title="Notifications" style="position:relative; background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-primary);">
                        <i class="fas fa-bell"></i>
                        <span class="adm-notif-badge" id="notifBadge" style="display:none; position:absolute; top:-5px; right:-5px; background:var(--danger, #d32f2f); color:#fff; font-size:0.65rem; padding:2px 5px; border-radius:10px; border:2px solid var(--surface, #fff);">0</span>
                    </button>
                    <!-- Dropdown -->
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--surface, #fff);border-radius:16px;box-shadow:0 12px 40px rgba(0,0,0,.15);border:1px solid var(--border, #eee);z-index:999999;overflow:hidden;color:var(--text-primary);">
                        <div style="padding:.85rem 1.2rem;border-bottom:1px solid var(--border, #eee);display:flex;justify-content:space-between;align-items:center;">
                            <strong style="font-size:.9rem;"><i class="fas fa-bullhorn" style="color:var(--primary, #1976d2);margin-right:.4rem;"></i>System Broadcasts</strong>
                        </div>
                        <div style="max-height:350px;overflow-y:auto;background:var(--surface);">
                            <p style="padding:2rem;text-align:center;color:var(--text-muted);font-size:.85rem;"><i class="fas fa-check-circle" style="display:block;font-size:1.5rem;margin-bottom:.5rem;color:var(--success);"></i>No new broadcasts!</p>
                        </div>
                    </div>
                </div>
            `;
            // Insert before avatar or theme toggle if possible
            const avatar = topbarRight.querySelector('.adm-avatar') || topbarRight.querySelector('.adm-user-profile');
            if (avatar) {
                avatar.insertAdjacentHTML('beforebegin', bellHtml);
            } else {
                topbarRight.insertAdjacentHTML('afterbegin', bellHtml);
            }
        }

        // Add Toggle Event
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('notifBtn');
            const dropdown = document.getElementById('notifDropdown');
            if (!btn || !dropdown) return;

            if (btn.contains(e.target)) {
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                e.stopPropagation();
            } else if (!dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    connectSSE() {
        // Construct the URL for SSE
        const sseUrl = '/RMU-Medical-Management-System/php/api/broadcast_sse.php';
        this.sse = new EventSource(sseUrl);

        this.sse.onmessage = (event) => {
            const res = JSON.parse(event.data);
            if (res.type === 'new_broadcast') {
                res.data.forEach(bc => this.handleNewBroadcast(bc));
            }
        };

        this.sse.onerror = () => {
            console.warn("SSE Connection lost. Reconnecting in 5s...");
            this.sse.close();
            setTimeout(() => this.connectSSE(), 5000);
        };
    }

    handleNewBroadcast(bc) {
        console.log("New Broadcast Received:", bc);
        
        // 1. Play Audio Alert if priority >= Urgent
        if (bc.priority === 'Urgent' || bc.priority === 'Critical') {
            this.playAlert();
        }

        // 2. Display based on priority
        switch (bc.priority) {
            case 'Critical':
                this.showCriticalModal(bc);
                break;
            case 'Urgent':
                this.showUrgentBanner(bc);
                break;
            case 'Important':
                this.flashNotificationBell();
                this.addNotificationEntry(bc, true);
                if (typeof showToast === 'function') showToast(bc.subject, 'info');
                break;
            default:
                this.addNotificationEntry(bc, false);
                if (typeof showToast === 'function') showToast(bc.subject, 'success');
                break;
        }

        this.updateUnreadBadge(1);
    }

    showCriticalModal(bc) {
        // Create full-screen modal
        const modal = document.createElement('div');
        modal.className = 'bc-critical-modal';
        modal.innerHTML = `
            <div class="bc-critical-content">
                <div class="bc-critical-header">
                    <i class="fas fa-exclamation-triangle"></i> CRITICAL SYSTEM BROADCAST
                </div>
                <h2>${bc.subject}</h2>
                <div class="bc-critical-body">${bc.body}</div>
                ${bc.attachment_path ? `<a href="/RMU-Medical-Management-System/php/${bc.attachment_path}" target="_blank" class="bc-attachment"><i class="fas fa-paperclip"></i> View Attachment</a>` : ''}
                <div class="bc-critical-footer">
                    <button onclick="acknowledgeBroadcast(${bc.id}, this)">I HAVE READ AND UNDERSTOOD THIS MESSAGE</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    showUrgentBanner(bc) {
        const banner = document.createElement('div');
        banner.className = 'bc-urgent-banner';
        banner.innerHTML = `
            <div class="bc-urgent-icon"><i class="fas fa-bullhorn"></i></div>
            <div class="bc-urgent-text"><strong>URGENT:</strong> ${bc.subject}</div>
            <button class="bc-urgent-view" onclick="openBroadcastDetail(${bc.id})">VIEW</button>
            <button class="bc-urgent-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        document.body.prepend(banner);
    }

    playAlert() {
        const audio = new Audio('/RMU-Medical-Management-System/assets/audio/notification_alert.mp3');
        audio.play().catch(e => console.log("Audio play blocked by browser."));
    }

    flashNotificationBell() {
        const bell = document.querySelector('.fa-bell');
        if (bell) {
            bell.classList.add('bc-pulse');
            setTimeout(() => bell.classList.remove('bc-pulse'), 10000);
        }
    }

    updateUnreadBadge(increment) {
        this.unreadCount += increment;
        const badge = document.querySelector('.adm-notif-badge') || document.querySelector('.notification-badge');
        if (badge) {
            badge.innerText = this.unreadCount;
            badge.style.display = 'block';
        }
    }

    addNotificationEntry(bc, highlight) {
        const notifContainer = document.querySelector('#notifDropdown div[style*="max-height:280px"]');
        if (!notifContainer) return;

        // Remove "All caught up" message if exists
        const emptyMsg = notifContainer.querySelector('p');
        if (emptyMsg) emptyMsg.remove();

        const entry = document.createElement('div');
        entry.style.padding = '.75rem 1.2rem';
        entry.style.borderBottom = '1px solid var(--border)';
        if (highlight) entry.style.background = 'rgba(var(--primary-rgb, 0, 123, 255), 0.1)';
        
        entry.innerHTML = `
            <div style="font-weight:600; font-size:.82rem; margin-bottom:.2rem;">
                <span class="bc-priority-dot ${bc.priority.toLowerCase()}"></span> ${bc.subject}
            </div>
            <div style="font-size:.78rem; color:var(--text-muted); line-height:1.3;">${bc.body}</div>
            <div style="font-size:.7rem; color:var(--primary); margin-top:0.4rem; cursor:pointer;" onclick="acknowledgeBroadcast(${bc.id}, this)">
                <i class="fas fa-check"></i> Mark as Read
            </div>
        `;
        
        notifContainer.prepend(entry);
    }

    loadHistory() {
        console.log("Loading broadcast history...");
        fetch('/RMU-Medical-Management-System/php/api/get_broadcasts.php')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.broadcasts) {
                const unread = res.broadcasts.filter(b => !b.read_at);
                unread.forEach(b => {
                    this.addNotificationEntry(b, false);
                });
                this.updateUnreadBadge(unread.length);
            }
        });
    }
}

// Global helper for acknowledgement
window.acknowledgeBroadcast = function(id, btn) {
    const fd = new FormData();
    fd.append('action', 'acknowledge');
    fd.append('id', id);
    
    fetch('/RMU-Medical-Management-System/php/api/broadcast_utils.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const modal = btn.closest('.bc-critical-modal');
            if (modal) modal.remove();
        }
    });
};

// CSS Injection
const bcStyle = document.createElement('style');
bcStyle.innerHTML = `
    .bc-critical-modal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:99999; display:flex; align-items:center; justify-content:center; padding:2rem; }
    .bc-critical-content { background:var(--bg-primary, #fff); width:100%; max-width:700px; border-radius:12px; border:4px solid #d32f2f; overflow:hidden; animation: shake 0.5s; }
    .bc-critical-header { background:#d32f2f; color:#fff; padding:1.5rem; font-weight:bold; font-size:1.2rem; display:flex; align-items:center; gap:0.8rem; }
    .bc-critical-body { padding:2rem; font-size:1.1rem; line-height:1.6; color:var(--text-primary, #333); max-height:60vh; overflow-y:auto; }
    .bc-critical-footer { padding:1.5rem; border-top:1px solid #eee; text-align:center; }
    .bc-critical-footer button { background:#d32f2f; color:#fff; border:none; padding:1rem 2rem; border-radius:8px; font-weight:bold; cursor:pointer; width:100%; }
    
    .bc-urgent-banner { background:#d32f2f; color:#fff; padding:0.8rem 1.5rem; display:flex; align-items:center; gap:1rem; position:sticky; top:0; z-index:9999; animation: slideInDown 0.5s; }
    .bc-urgent-text { flex:1; font-size:0.95rem; }
    .bc-urgent-view { background:rgba(255,255,255,0.2); border:1px solid #fff; color:#fff; padding:0.3rem 0.8rem; border-radius:4px; font-size:0.8rem; cursor:pointer; }
    .bc-urgent-close { background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer; }

    .bc-priority-dot { display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px; }
    .bc-priority-dot.critical { background:#d32f2f; box-shadow:0 0 5px #d32f2f; }
    .bc-priority-dot.urgent { background:#f57c00; }
    .bc-priority-dot.important { background:#1976d2; }
    .bc-priority-dot.informational { background:#757575; }

    @keyframes shake { 0% { transform: translate(1px, 1px) rotate(0deg); } 10% { transform: translate(-1px, -2px) rotate(-1deg); } 20% { transform: translate(-3px, 0px) rotate(1deg); } }
    @keyframes slideInDown { from { transform: translate3d(0, -100%, 0); visibility: visible; } to { transform: translate3d(0, 0, 0); } }
    .bc-pulse { animation: bc-pulse-anim 1s infinite alternate; }
    @keyframes bc-pulse-anim { 0% { transform: scale(1); filter: drop-shadow(0 0 0 red); } 100% { transform: scale(1.3); filter: drop-shadow(0 0 10px red); } }
`;
document.head.appendChild(bcStyle);
