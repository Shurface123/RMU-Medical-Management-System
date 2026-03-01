/**
 * RMU Medical Sickbay — Unified Notification Panel
 * js/notifications.js
 *
 * Usage: include this file after admin-dashboard.css is loaded.
 * Requires: a <div id="rmuNotifPanel"> in the page (injected automatically).
 * The bell button must have id="rmuBellBtn" and contain a <span id="rmuBellCount">
 *
 * Config (set before including this script):
 *   window.RMU_API_BASE = '/RMU-Medical-Management-System/php/api';
 */
(function () {
    'use strict';

    const API = (window.RMU_API_BASE || '/RMU-Medical-Management-System/php/api') + '/get_notifications.php';
    const POLL_MS = 30000; // 30s
    let panelOpen = false;
    let lastFetchedIds = new Set();

    // ── Type → icon / colour map ──────────────────────────
    const TYPE_META = {
        appointment: { icon: 'fa-calendar-check', color: '#2F80ED' },
        prescription: { icon: 'fa-prescription-bottle-medical', color: '#27AE60' },
        lab: { icon: 'fa-flask', color: '#9B59B6' },
        inventory: { icon: 'fa-pills', color: '#E74C3C' },
        system: { icon: 'fa-bell', color: '#F39C12' },
        default: { icon: 'fa-circle-info', color: '#56CCF2' },
    };

    function getMeta(type) { return TYPE_META[type] ?? TYPE_META.default; }

    // ── Relative time helper ──────────────────────────────
    function relTime(ts) {
        const d = new Date(ts.replace(' ', 'T'));
        const diff = Math.floor((Date.now() - d) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        return Math.floor(diff / 86400) + 'd ago';
    }

    // ── Build panel HTML ───────────────────────────────────
    function buildPanel() {
        const panel = document.createElement('div');
        panel.id = 'rmuNotifPanel';
        panel.innerHTML = `
      <div class="rnp-header">
        <span class="rnp-title"><i class="fas fa-bell"></i> Notifications</span>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <button id="rnpMarkAll" class="rnp-action-btn" title="Mark all as read"><i class="fas fa-check-double"></i></button>
          <button id="rnpClose" class="rnp-action-btn" title="Close"><i class="fas fa-xmark"></i></button>
        </div>
      </div>
      <div class="rnp-filters">
        <button class="rnp-filter active" data-f="all">All</button>
        <button class="rnp-filter" data-f="unread">Unread</button>
        <button class="rnp-filter" data-f="appointment">Appts</button>
        <button class="rnp-filter" data-f="lab">Lab</button>
        <button class="rnp-filter" data-f="inventory">Inventory</button>
      </div>
      <div class="rnp-list" id="rnpList"><div class="rnp-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div></div>
      <div class="rnp-footer">
        <span id="rnpCount" style="font-size:1.1rem;color:var(--text-muted);"></span>
        <button id="rnpLoadMore" class="rnp-action-btn" style="display:none;"><i class="fas fa-chevron-down"></i> Load more</button>
      </div>`;
        document.body.appendChild(panel);
        return panel;
    }

    // ── Render item ────────────────────────────────────────
    function renderItem(n) {
        const meta = getMeta(n.type);
        const div = document.createElement('div');
        div.className = 'rnp-item' + (n.is_read ? '' : ' rnp-unread');
        div.dataset.id = n.id;
        div.dataset.type = n.type;
        div.innerHTML = `
      <div class="rnp-icon" style="background:${meta.color}22;color:${meta.color}"><i class="fas ${meta.icon}"></i></div>
      <div class="rnp-body">
        <div class="rnp-item-title">${escHtml(n.title)}</div>
        <div class="rnp-item-msg">${escHtml(n.message)}</div>
        <div class="rnp-item-time">${relTime(n.time)}${n.is_read ? '' : ' · <strong style="color:var(--role-accent,#2F80ED)">Unread</strong>'}</div>
      </div>
      ${n.is_read ? '' : '<div class="rnp-dot"></div>'}`;
        div.addEventListener('click', () => {
            if (!n.is_read) markRead(n.id, div);
            if (n.link && n.link !== '#') window.location.href = n.link;
        });
        return div;
    }

    function escHtml(s) { return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    // ── Fetch & render ─────────────────────────────────────
    let currentFilter = 'all';
    let allNotifs = [];

    async function fetchNotifs(opts = {}) {
        const params = new URLSearchParams({ action: 'list', limit: opts.limit || 30 });
        if (opts.unread_only) params.set('unread_only', '1');
        try {
            const res = await fetch(API + '?' + params);
            const data = await res.json();
            if (!data.success) return;
            allNotifs = data.notifications || [];
            renderList(currentFilter);
            updateBadge(data.unread_count);
        } catch (e) { console.warn('RMU Notif fetch failed', e); }
    }

    function renderList(filter) {
        currentFilter = filter;
        const list = document.getElementById('rnpList');
        if (!list) return;
        let items = allNotifs;
        if (filter === 'unread') items = items.filter(n => !n.is_read);
        else if (filter !== 'all') items = items.filter(n => n.type === filter);
        list.innerHTML = '';
        if (!items.length) {
            list.innerHTML = `<div class="rnp-empty"><i class="fas fa-bell-slash"></i><p>${filter === 'unread' ? 'All caught up!' : 'No notifications here.'}</p></div>`;
        } else {
            items.forEach(n => list.appendChild(renderItem(n)));
        }
        const countEl = document.getElementById('rnpCount');
        if (countEl) countEl.textContent = `${items.length} notification${items.length !== 1 ? 's' : ''}`;
    }

    function updateBadge(count) {
        const badge = document.getElementById('rmuBellCount');
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    async function markRead(id, el) {
        await fetch(API, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_read', notification_id: id })
        });
        // Update locally
        const n = allNotifs.find(x => x.id === id);
        if (n) { n.is_read = true; }
        if (el) { el.classList.remove('rnp-unread'); el.querySelector('.rnp-dot')?.remove(); }
        const unread = allNotifs.filter(x => !x.is_read).length;
        updateBadge(unread);
    }

    async function markAllRead() {
        await fetch(API, {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'mark_all_read' })
        });
        allNotifs.forEach(n => n.is_read = true);
        renderList(currentFilter);
        updateBadge(0);
    }

    // ── Toggle panel ───────────────────────────────────────
    function openPanel() {
        const panel = document.getElementById('rmuNotifPanel');
        if (!panel) return;
        panel.classList.add('open');
        panelOpen = true;
        fetchNotifs();
    }
    function closePanel() {
        const panel = document.getElementById('rmuNotifPanel');
        if (panel) panel.classList.remove('open');
        panelOpen = false;
    }

    // ── Toast for new notifications ────────────────────────
    function toastNotif(n) {
        if (lastFetchedIds.has(n.id)) return;
        lastFetchedIds.add(n.id);
        const meta = getMeta(n.type);
        const t = document.createElement('div');
        t.className = 'rnp-toast';
        t.innerHTML = `<div class="rnp-toast-icon" style="background:${meta.color};"><i class="fas ${meta.icon}"></i></div>
      <div><div style="font-weight:700;font-size:1.25rem;">${escHtml(n.title)}</div><div style="font-size:1.1rem;color:#aaa;">${escHtml(n.message.slice(0, 80))}…</div></div>
      <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#aaa;font-size:1.4rem;cursor:pointer;margin-left:.5rem;">×</button>`;
        t.style.cursor = 'pointer';
        t.addEventListener('click', e => { if (e.target.tagName !== 'BUTTON') { closePanel(); if (n.link && n.link !== '#') window.location.href = n.link; } });
        document.body.appendChild(t);
        setTimeout(() => t.classList.add('show'), 50);
        setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 5000);
    }

    // ── Polling ────────────────────────────────────────────
    async function poll() {
        try {
            const res = await fetch(API + '?action=list&limit=10');
            const data = await res.json();
            if (!data.success) return;
            updateBadge(data.unread_count);
            if (!panelOpen) {
                // Show toasts for new unread items
                (data.notifications || [])
                    .filter(n => !n.is_read && !lastFetchedIds.has(n.id))
                    .slice(0, 3)
                    .forEach(n => toastNotif(n));
                (data.notifications || []).forEach(n => lastFetchedIds.add(n.id));
            }
        } catch (e) { }
    }

    // ── Init ───────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const panel = buildPanel();

        // Bell button
        const bell = document.getElementById('rmuBellBtn');
        if (bell) {
            bell.addEventListener('click', e => {
                e.stopPropagation();
                panelOpen ? closePanel() : openPanel();
            });
        }

        // Panel internal events
        document.getElementById('rnpClose')?.addEventListener('click', closePanel);
        document.getElementById('rnpMarkAll')?.addEventListener('click', markAllRead);

        // Filter tabs
        panel.querySelectorAll('.rnp-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                panel.querySelectorAll('.rnp-filter').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                renderList(btn.dataset.f);
            });
        });

        // Close on outside click
        document.addEventListener('click', e => {
            if (panelOpen && !panel.contains(e.target) && e.target.id !== 'rmuBellBtn' && !e.target.closest('#rmuBellBtn')) {
                closePanel();
            }
        });

        // Initial fetch for badge
        poll();

        // Poll every 30s
        setInterval(poll, POLL_MS);
    });

})();
