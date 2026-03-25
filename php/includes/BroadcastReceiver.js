/**
 * BroadcastReceiver.js
 * Handles real-time reception of broadcasts via SSE and displays them based on priority.
 */
class BroadcastReceiver {
    constructor(userId) {
        this.userId = userId;
        this.sse = null;
        this.unreadCount = 0;
        this.init();
    }

    init() {
        console.log("BroadcastReceiver initialized for user " + this.userId);
        this.connectSSE();
        this.loadHistory();
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
                break;
            default:
                this.addNotificationEntry(bc, false);
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
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.innerText = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'block' : 'none';
        }
    }

    addNotificationEntry(bc, highlight) {
        // Implementation for adding to the notification panel dropdown
        console.log("Adding notification entry:", bc.subject);
    }

    loadHistory() {
        // Fetch existing broadcasts on load
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

    @keyframes shake { 0% { transform: translate(1px, 1px) rotate(0deg); } 10% { transform: translate(-1px, -2px) rotate(-1deg); } 20% { transform: translate(-3px, 0px) rotate(1deg); } }
    @keyframes slideInDown { from { transform: translate3d(0, -100%, 0); visibility: visible; } to { transform: translate3d(0, 0, 0); } }
    .bc-pulse { animation: bc-pulse-anim 1s infinite alternate; }
    @keyframes bc-pulse-anim { 0% { transform: scale(1); filter: drop-shadow(0 0 0 red); } 100% { transform: scale(1.3); filter: drop-shadow(0 0 10px red); } }
`;
document.head.appendChild(bcStyle);
