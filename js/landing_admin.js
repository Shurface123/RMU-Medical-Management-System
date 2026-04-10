/**
 * landing_admin.js
 * Frontend logic for the Landing Page Admin Manager.
 * Handles tab switching, AJAX CRUD operations, modals, and live previews.
 */

const API = '/RMU-Medical-Management-System/php/admin/landing_page_actions.php';

/* ══════════════════════════════════════════════════════
   CORE UTILITIES
══════════════════════════════════════════════════════ */

const lpa = {
  async post(action, formData) {
    formData.append('action', action);
    try {
      const res = await fetch(API, { method: 'POST', body: formData });
      return await res.json();
    } catch (e) {
      return { success: false, message: 'Network error: ' + e.message };
    }
  },

  async get(action, extra = '') {
    try {
      const res = await fetch(`${API}?action=${action}${extra}`);
      return await res.json();
    } catch (e) {
      return { success: false, message: 'Network error: ' + e.message };
    }
  },

  toast(msg, type = 'success') {
    const t = document.createElement('div');
    t.className = `la-toast la-toast-${type}`;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
    document.body.appendChild(t);
    requestAnimationFrame(() => t.classList.add('visible'));
    setTimeout(() => { t.classList.remove('visible'); setTimeout(() => t.remove(), 400); }, 3200);
  },

  confirm(msg) { return window.confirm(msg); },

  escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  },

  loading(el, state) {
    if (state) {
      el.dataset.orig = el.innerHTML;
      el.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';
      el.disabled = true;
    } else {
      el.innerHTML = el.dataset.orig || 'Save';
      el.disabled = false;
    }
  },

  badge(val, trueLabel = 'Active', falseLabel = 'Inactive') {
    return val == 1
      ? `<span class="la-badge la-badge-success">${trueLabel}</span>`
      : `<span class="la-badge la-badge-muted">${falseLabel}</span>`;
  },

  starRating(n) {
    return '★'.repeat(n) + '☆'.repeat(5 - n);
  }
};

/* ══════════════════════════════════════════════════════
   TAB SYSTEM
══════════════════════════════════════════════════════ */

function initTabs() {
  const tabs    = document.querySelectorAll('.la-tab-btn');
  const panels  = document.querySelectorAll('.la-tab-panel');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.tab;
      tabs.forEach(t => t.classList.remove('active'));
      panels.forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      const panel = document.getElementById('panel-' + target);
      if (panel) panel.classList.add('active');
      loadTab(target);
    });
  });

  // Load first tab
  const first = tabs[0];
  if (first) { first.classList.add('active'); loadTab(first.dataset.tab); }
}

function loadTab(tab) {
  const loaders = {
    general:       loadHero,
    stats:         loadStats,
    announcements: loadAnnouncements,
    services:      loadServices,
    faq:           loadFaq,
    gallery:       loadGallery,
    testimonials:  loadTestimonials,
    team:          loadTeam,
    director:      loadDirector,
    chatbot:       loadChatbotKB,
    logs:          loadChatLogs,
    bookings:      loadBookings,
    config:        loadSiteConfig,
  };
  if (loaders[tab]) loaders[tab]();
}

/* ══════════════════════════════════════════════════════
   MODAL
══════════════════════════════════════════════════════ */

function showModal(title, bodyHtml, onSave) {
  const m = document.getElementById('laModal');
  document.getElementById('laModalTitle').textContent = title;
  document.getElementById('laModalBody').innerHTML = bodyHtml;
  m.classList.add('open');

  document.getElementById('laModalSaveBtn').onclick = () => {
    const btn = document.getElementById('laModalSaveBtn');
    lpa.loading(btn, true);
    onSave(btn);
  };
}

function closeModal() {
  document.getElementById('laModal').classList.remove('open');
}

/* ══════════════════════════════════════════════════════
   TAB: HERO CONFIG
══════════════════════════════════════════════════════ */

async function loadHero() {
  const panel = document.getElementById('panel-general');
  panel.querySelector('.la-loading')?.remove();

  const res = await lpa.get('get_hero');
  const d = res.data || {};

  panel.innerHTML = `
    <h3 class="la-section-head"><i class="fas fa-image"></i> Hero Section</h3>
    <form id="heroForm" class="la-form-grid">
      <div class="la-fg la-full">
        <label>Main Headline</label>
        <input type="text" name="headline" value="${lpa.escHtml(d.headline || 'Your Health, Our Priority')}" class="la-inp">
      </div>
      <div class="la-fg la-full">
        <label>Subheadline</label>
        <textarea name="subheadline" class="la-inp" rows="2">${lpa.escHtml(d.subheadline || '')}</textarea>
      </div>
      <div class="la-fg">
        <label>Button 1 Text</label>
        <input type="text" name="cta1_text" value="${lpa.escHtml(d.cta1_text || 'Book Appointment')}" class="la-inp">
      </div>
      <div class="la-fg">
        <label>Button 1 URL</label>
        <input type="text" name="cta1_url" value="${lpa.escHtml(d.cta1_url || '/RMU-Medical-Management-System/php/booking.php')}" class="la-inp">
      </div>
      <div class="la-fg">
        <label>Button 2 Text</label>
        <input type="text" name="cta2_text" value="${lpa.escHtml(d.cta2_text || 'Explore Services')}" class="la-inp">
      </div>
      <div class="la-fg">
        <label>Button 2 URL</label>
        <input type="text" name="cta2_url" value="${lpa.escHtml(d.cta2_url || '/RMU-Medical-Management-System/html/services.html')}" class="la-inp">
      </div>
      <div class="la-fg la-full">
        <label>Background Image Path</label>
        <input type="text" name="bg_image" value="${lpa.escHtml(d.bg_image || 'image/home.jpg')}" class="la-inp" placeholder="image/home.jpg">
      </div>
      <div class="la-fg la-full">
        <button type="submit" class="la-btn la-btn-primary"><i class="fas fa-save"></i> Save Hero Config</button>
      </div>
    </form>
  `;

  document.getElementById('heroForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('[type=submit]');
    lpa.loading(btn, true);
    const fd = new FormData(e.target);
    const res = await lpa.post('save_hero', fd);
    lpa.loading(btn, false);
    lpa.toast(res.message, res.success ? 'success' : 'error');
  });
}

/* ══════════════════════════════════════════════════════
   TAB: STATS
══════════════════════════════════════════════════════ */

async function loadStats() {
  const panel = document.getElementById('panel-stats');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;

  const res = await lpa.get('get_stats');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-chart-bar"></i> Stats Counter</h3>
      <button class="la-btn la-btn-primary" onclick="editStat(0)"><i class="fas fa-plus"></i> Add Stat</button>
    </div>
    <div class="la-table-wrap">
      <table class="la-table">
        <thead><tr><th>Icon</th><th>Number</th><th>Suffix</th><th>Label</th><th>Order</th><th>Actions</th></tr></thead>
        <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td><i class="${lpa.escHtml(r.icon_class)}"></i></td>
      <td>${lpa.escHtml(r.stat_number)}</td>
      <td>${lpa.escHtml(r.stat_suffix)}</td>
      <td>${lpa.escHtml(r.stat_label)}</td>
      <td>${r.display_order}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editStat(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteStat(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editStat(id) {
  showModal(id ? 'Edit Stat' : 'New Stat', `
    <form id="statForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg"><label>Icon Class (FontAwesome)</label><input type="text" name="icon_class" class="la-inp" placeholder="fas fa-users" id="statIcon"></div>
      <div class="la-fg"><label>Number</label><input type="text" name="stat_number" class="la-inp" id="statNum"></div>
      <div class="la-fg"><label>Suffix (e.g. +)</label><input type="text" name="stat_suffix" class="la-inp" id="statSuf"></div>
      <div class="la-fg"><label>Label</label><input type="text" name="stat_label" class="la-inp" id="statLabel"></div>
      <div class="la-fg"><label>Display Order</label><input type="number" name="display_order" class="la-inp" value="0" id="statOrder"></div>
    </form>
  `, async btn => {
    if (id) {
      const res2 = await lpa.get('get_stats');
      const found = (res2.data || []).find(x => x.id == id);
      if (found) {
        document.getElementById('statIcon').value  = found.icon_class;
        document.getElementById('statNum').value   = found.stat_number;
        document.getElementById('statSuf').value   = found.stat_suffix;
        document.getElementById('statLabel').value = found.stat_label;
        document.getElementById('statOrder').value = found.display_order;
      }
    }
    const fd = new FormData(document.getElementById('statForm'));
    const r = await lpa.post('save_stat', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadStats(); }
  });

  if (id) {
    lpa.get('get_stats').then(res => {
      const found = (res.data || []).find(x => x.id == id);
      if (found) {
        document.getElementById('statIcon').value  = found.icon_class || '';
        document.getElementById('statNum').value   = found.stat_number || '';
        document.getElementById('statSuf').value   = found.stat_suffix || '';
        document.getElementById('statLabel').value = found.stat_label || '';
        document.getElementById('statOrder').value = found.display_order || 0;
      }
    });
  }
}

async function deleteStat(id) {
  if (!lpa.confirm('Delete this stat?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_stat', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadStats();
}

/* ══════════════════════════════════════════════════════
   TAB: ANNOUNCEMENTS
══════════════════════════════════════════════════════ */

async function loadAnnouncements() {
  const panel = document.getElementById('panel-announcements');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_announcements');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-bullhorn"></i> Announcements</h3>
      <button class="la-btn la-btn-primary" onclick="editAnnouncement(0)"><i class="fas fa-plus"></i> Add Announcement</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    const typeColors = { info: '#2F80ED', warning: '#f39c12', danger: '#e74c3c', success: '#27ae60' };
    const color = typeColors[r.type] || '#2F80ED';
    html += `<tr>
      <td><strong>${lpa.escHtml(r.title)}</strong><br><small style="color:var(--text-secondary)">${lpa.escHtml(r.body || '').substring(0, 60)}…</small></td>
      <td><span class="la-chip" style="background:${color}20;color:${color}">${r.type}</span></td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>${r.start_date || '—'}</td>
      <td>${r.end_date || '—'}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editAnnouncement(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-toggle" onclick="toggleAnnouncement(${r.id})" title="Toggle active"><i class="fas fa-toggle-on"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteAnnouncement(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editAnnouncement(id) {
  const bodyHtml = `
    <form id="annForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg la-full"><label>Title</label><input type="text" name="title" class="la-inp" id="annTitle"></div>
      <div class="la-fg la-full"><label>Body</label><textarea name="body" class="la-inp" rows="3" id="annBody"></textarea></div>
      <div class="la-fg"><label>Type</label>
        <select name="type" class="la-inp" id="annType">
          <option value="info">Info</option><option value="warning">Warning</option>
          <option value="danger">Danger</option><option value="success">Success</option>
        </select>
      </div>
      <div class="la-fg"><label>Status</label>
        <select name="is_active" class="la-inp" id="annActive">
          <option value="1">Active</option><option value="0">Inactive</option>
        </select>
      </div>
      <div class="la-fg"><label>Start Date</label><input type="date" name="start_date" class="la-inp" id="annStart"></div>
      <div class="la-fg"><label>End Date</label><input type="date" name="end_date" class="la-inp" id="annEnd"></div>
      <div class="la-fg"><label>Display Order</label><input type="number" name="display_order" class="la-inp" value="0" id="annOrder"></div>
    </form>`;

  showModal(id ? 'Edit Announcement' : 'New Announcement', bodyHtml, async btn => {
    const fd = new FormData(document.getElementById('annForm'));
    const r = await lpa.post('save_announcement', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadAnnouncements(); }
  });

  if (id) {
    lpa.get('get_announcements').then(res => {
      const found = (res.data || []).find(x => x.id == id);
      if (!found) return;
      document.getElementById('annTitle').value  = found.title || '';
      document.getElementById('annBody').value   = found.body || '';
      document.getElementById('annType').value   = found.type || 'info';
      document.getElementById('annActive').value = found.is_active || '1';
      document.getElementById('annStart').value  = found.start_date || '';
      document.getElementById('annEnd').value    = found.end_date || '';
      document.getElementById('annOrder').value  = found.display_order || '0';
    });
  }
}

async function toggleAnnouncement(id) {
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('toggle_announcement', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadAnnouncements();
}

async function deleteAnnouncement(id) {
  if (!lpa.confirm('Delete this announcement?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_announcement', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadAnnouncements();
}

/* ══════════════════════════════════════════════════════
   TAB: SERVICES
══════════════════════════════════════════════════════ */

async function loadServices() {
  const panel = document.getElementById('panel-services');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_services');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-stethoscope"></i> Services</h3>
      <button class="la-btn la-btn-primary" onclick="editService(0)"><i class="fas fa-plus"></i> Add Service</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Icon</th><th>Name</th><th>Category</th><th>Featured</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td><i class="${lpa.escHtml(r.icon_class)} la-icon"></i></td>
      <td>${lpa.escHtml(r.name)}</td>
      <td>${lpa.escHtml(r.category || '—')}</td>
      <td>${lpa.badge(r.is_featured, 'Yes', 'No')}</td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>${r.display_order}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editService(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteService(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editService(id) {
  const bodyHtml = `
    <form id="svcForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg"><label>Service Name</label><input type="text" name="name" class="la-inp" id="svcName"></div>
      <div class="la-fg"><label>Icon Class</label><input type="text" name="icon_class" class="la-inp" id="svcIcon" placeholder="fas fa-stethoscope"></div>
      <div class="la-fg la-full"><label>Description</label><textarea name="description" class="la-inp" rows="3" id="svcDesc"></textarea></div>
      <div class="la-fg"><label>Category</label><input type="text" name="category" class="la-inp" id="svcCat"></div>
      <div class="la-fg"><label>Availability</label><input type="text" name="availability" class="la-inp" id="svcAvail" placeholder="Mon–Fri, 8AM–5PM"></div>
      <div class="la-fg"><label>Featured</label><select name="is_featured" class="la-inp" id="svcFeat"><option value="0">No</option><option value="1">Yes</option></select></div>
      <div class="la-fg"><label>Status</label><select name="is_active" class="la-inp" id="svcActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="la-fg"><label>Order</label><input type="number" name="display_order" class="la-inp" value="0" id="svcOrder"></div>
    </form>`;

  showModal(id ? 'Edit Service' : 'New Service', bodyHtml, async btn => {
    const fd = new FormData(document.getElementById('svcForm'));
    const r = await lpa.post('save_service', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadServices(); }
  });

  if (id) {
    lpa.get('get_services').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('svcName').value   = f.name || '';
      document.getElementById('svcIcon').value   = f.icon_class || '';
      document.getElementById('svcDesc').value   = f.description || '';
      document.getElementById('svcCat').value    = f.category || '';
      document.getElementById('svcAvail').value  = f.availability || '';
      document.getElementById('svcFeat').value   = f.is_featured || '0';
      document.getElementById('svcActive').value = f.is_active || '1';
      document.getElementById('svcOrder').value  = f.display_order || '0';
    });
  }
}

async function deleteService(id) {
  if (!lpa.confirm('Delete this service?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_service', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadServices();
}

/* ══════════════════════════════════════════════════════
   TAB: FAQ
══════════════════════════════════════════════════════ */

async function loadFaq() {
  const panel = document.getElementById('panel-faq');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_faq');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-circle-question"></i> FAQ</h3>
      <button class="la-btn la-btn-primary" onclick="editFaq(0)"><i class="fas fa-plus"></i> Add FAQ</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Question</th><th>Category</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td>${lpa.escHtml(r.question || '').substring(0, 65)}…</td>
      <td>${lpa.escHtml(r.category || '—')}</td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>${r.display_order}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editFaq(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteFaq(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editFaq(id) {
  showModal(id ? 'Edit FAQ' : 'New FAQ', `
    <form id="faqForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg la-full"><label>Question</label><input type="text" name="question" class="la-inp" id="faqQ"></div>
      <div class="la-fg la-full"><label>Answer</label><textarea name="answer" class="la-inp" rows="4" id="faqA"></textarea></div>
      <div class="la-fg"><label>Category</label><input type="text" name="category" class="la-inp" id="faqCat" value="General"></div>
      <div class="la-fg"><label>Status</label><select name="is_active" class="la-inp" id="faqActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="la-fg"><label>Order</label><input type="number" name="display_order" class="la-inp" value="0" id="faqOrder"></div>
    </form>`, async btn => {
    const fd = new FormData(document.getElementById('faqForm'));
    const r = await lpa.post('save_faq', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadFaq(); }
  });

  if (id) {
    lpa.get('get_faq').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('faqQ').value      = f.question || '';
      document.getElementById('faqA').value      = f.answer || '';
      document.getElementById('faqCat').value    = f.category || 'General';
      document.getElementById('faqActive').value = f.is_active || '1';
      document.getElementById('faqOrder').value  = f.display_order || '0';
    });
  }
}

async function deleteFaq(id) {
  if (!lpa.confirm('Delete this FAQ?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_faq', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadFaq();
}

/* ══════════════════════════════════════════════════════
   TAB: GALLERY
══════════════════════════════════════════════════════ */

async function loadGallery() {
  const panel = document.getElementById('panel-gallery');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_gallery');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-images"></i> Gallery</h3>
      <button class="la-btn la-btn-primary" onclick="editGallery(0)"><i class="fas fa-plus"></i> Add Image</button>
    </div>
    <div class="la-gallery-grid">`;

  rows.forEach(r => {
    const imgSrc = '/RMU-Medical-Management-System/' + (r.image_path || 'image/home.jpg');
    html += `
      <div class="la-gallery-card">
        <img src="${imgSrc}" alt="${lpa.escHtml(r.alt_text || '')}" onerror="this.style.background='var(--bg-alt)';this.alt='No image'">
        <div class="la-gallery-info">
          <div class="la-gallery-title">${lpa.escHtml(r.title || 'Untitled')}</div>
          <div class="la-gallery-actions">
            <button class="la-btn-sm la-btn-edit" onclick="editGallery(${r.id})"><i class="fas fa-edit"></i></button>
            <button class="la-btn-sm la-btn-delete" onclick="deleteGallery(${r.id})"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      </div>`;
  });

  html += `</div>`;
  panel.innerHTML = html;
}

function editGallery(id) {
  showModal(id ? 'Edit Gallery Image' : 'Add Gallery Image', `
    <form id="galForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg la-full"><label>Title</label><input type="text" name="title" class="la-inp" id="galTitle"></div>
      <div class="la-fg la-full"><label>Image Path (relative)</label><input type="text" name="image_path" class="la-inp" id="galPath" placeholder="image/home.jpg"></div>
      <div class="la-fg la-full"><label>Alt Text</label><input type="text" name="alt_text" class="la-inp" id="galAlt"></div>
      <div class="la-fg"><label>Category</label><input type="text" name="category" class="la-inp" id="galCat"></div>
      <div class="la-fg"><label>Order</label><input type="number" name="display_order" class="la-inp" value="0" id="galOrder"></div>
    </form>`, async btn => {
    const fd = new FormData(document.getElementById('galForm'));
    const r = await lpa.post('save_gallery', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadGallery(); }
  });

  if (id) {
    lpa.get('get_gallery').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('galTitle').value  = f.title || '';
      document.getElementById('galPath').value   = f.image_path || '';
      document.getElementById('galAlt').value    = f.alt_text || '';
      document.getElementById('galCat').value    = f.category || '';
      document.getElementById('galOrder').value  = f.display_order || '0';
    });
  }
}

async function deleteGallery(id) {
  if (!lpa.confirm('Remove this gallery image?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_gallery', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadGallery();
}

/* ══════════════════════════════════════════════════════
   TAB: TESTIMONIALS
══════════════════════════════════════════════════════ */

async function loadTestimonials() {
  const panel = document.getElementById('panel-testimonials');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_testimonials');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-star"></i> Testimonials</h3>
      <button class="la-btn la-btn-primary" onclick="editTestimonial(0)"><i class="fas fa-plus"></i> Add Testimonial</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Name</th><th>Role</th><th>Rating</th><th>Featured</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td><strong>${lpa.escHtml(r.patient_name)}</strong></td>
      <td>${lpa.escHtml(r.patient_role || '—')}</td>
      <td style="color:#f59e0b">${lpa.starRating(r.rating)}</td>
      <td>${lpa.badge(r.is_featured, 'Yes', 'No')}</td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editTestimonial(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteTestimonial(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editTestimonial(id) {
  showModal(id ? 'Edit Testimonial' : 'New Testimonial', `
    <form id="testForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg"><label>Patient Name</label><input type="text" name="patient_name" class="la-inp" id="testName"></div>
      <div class="la-fg"><label>Role/Position</label><input type="text" name="patient_role" class="la-inp" id="testRole" placeholder="Level 300 Student"></div>
      <div class="la-fg la-full"><label>Review Text</label><textarea name="review_text" class="la-inp" rows="3" id="testText"></textarea></div>
      <div class="la-fg"><label>Rating (1-5)</label><input type="number" name="rating" class="la-inp" min="1" max="5" value="5" id="testRating"></div>
      <div class="la-fg"><label>Featured</label><select name="is_featured" class="la-inp" id="testFeat"><option value="0">No</option><option value="1">Yes</option></select></div>
      <div class="la-fg"><label>Status</label><select name="is_active" class="la-inp" id="testActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="la-fg"><label>Order</label><input type="number" name="display_order" class="la-inp" value="0" id="testOrder"></div>
    </form>`, async btn => {
    const fd = new FormData(document.getElementById('testForm'));
    const r = await lpa.post('save_testimonial', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadTestimonials(); }
  });

  if (id) {
    lpa.get('get_testimonials').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('testName').value   = f.patient_name || '';
      document.getElementById('testRole').value   = f.patient_role || '';
      document.getElementById('testText').value   = f.review_text || '';
      document.getElementById('testRating').value = f.rating || '5';
      document.getElementById('testFeat').value   = f.is_featured || '0';
      document.getElementById('testActive').value = f.is_active || '1';
      document.getElementById('testOrder').value  = f.display_order || '0';
    });
  }
}

async function deleteTestimonial(id) {
  if (!lpa.confirm('Delete this testimonial?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_testimonial', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadTestimonials();
}

/* ══════════════════════════════════════════════════════
   TAB: DIRECTOR
══════════════════════════════════════════════════════ */

async function loadDirector() {
  const panel = document.getElementById('panel-director');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_director');
  const d = res.data || {};

  panel.innerHTML = `
    <h3 class="la-section-head"><i class="fas fa-user-tie"></i> Director Profile</h3>
    <form id="dirForm" class="la-form-grid">
      <div class="la-fg"><label>Full Name</label><input type="text" name="name" value="${lpa.escHtml(d.name || 'Dr. Emmanuel Mensah')}" class="la-inp"></div>
      <div class="la-fg"><label>Title/Position</label><input type="text" name="title" value="${lpa.escHtml(d.title || 'Chief Medical Officer')}" class="la-inp"></div>
      <div class="la-fg la-full"><label>Biography</label><textarea name="bio" class="la-inp" rows="4">${lpa.escHtml(d.bio || '')}</textarea></div>
      <div class="la-fg la-full"><label>Director's Message</label><textarea name="message" class="la-inp" rows="4">${lpa.escHtml(d.message || '')}</textarea></div>
      <div class="la-fg la-full"><label>Qualifications (separate with |)</label><input type="text" name="qualifications" value="${lpa.escHtml(d.qualifications || '')}" class="la-inp" placeholder="MBChB, UG|MPH, Johns Hopkins"></div>
      <div class="la-fg"><label>Photo Path</label><input type="text" name="photo_path" value="${lpa.escHtml(d.photo_path || 'image/director.jpg')}" class="la-inp"></div>
      <div class="la-fg"><label>Email</label><input type="email" name="email" value="${lpa.escHtml(d.email || '')}" class="la-inp"></div>
      <div class="la-fg la-full">
        <button type="submit" class="la-btn la-btn-primary"><i class="fas fa-save"></i> Save Director Profile</button>
      </div>
    </form>`;

  document.getElementById('dirForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('[type=submit]');
    lpa.loading(btn, true);
    const fd = new FormData(e.target);
    const r = await lpa.post('save_director', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
  });
}

/* ══════════════════════════════════════════════════════
   TAB: CHATBOT KNOWLEDGE BASE
══════════════════════════════════════════════════════ */

async function loadChatbotKB() {
  const panel = document.getElementById('panel-chatbot');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_chatbot_kb');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-robot"></i> Chatbot Knowledge Base</h3>
      <button class="la-btn la-btn-primary" onclick="editKB(0)"><i class="fas fa-plus"></i> Add Entry</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Category</th><th>Keywords</th><th>Question</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td><span class="la-chip">${lpa.escHtml(r.category || '—')}</span></td>
      <td style="font-size:.8rem;color:var(--text-muted)">${lpa.escHtml((r.keywords || '').substring(0, 40))}</td>
      <td>${lpa.escHtml((r.question || '').substring(0, 55))}…</td>
      <td>${r.priority}</td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editKB(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteKB(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editKB(id) {
  showModal(id ? 'Edit Knowledge Base Entry' : 'New Knowledge Base Entry', `
    <form id="kbForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg"><label>Category</label><input type="text" name="category" class="la-inp" id="kbCat" placeholder="Services, Hours, Emergency…"></div>
      <div class="la-fg"><label>Priority (1=high)</label><input type="number" name="priority" class="la-inp" min="1" max="10" value="5" id="kbPri"></div>
      <div class="la-fg la-full"><label>Keywords (comma-separated)</label><input type="text" name="keywords" class="la-inp" id="kbKeys" placeholder="hours, open, when, schedule"></div>
      <div class="la-fg la-full"><label>Question</label><input type="text" name="question" class="la-inp" id="kbQ"></div>
      <div class="la-fg la-full"><label>Answer</label><textarea name="answer" class="la-inp" rows="4" id="kbA"></textarea></div>
      <div class="la-fg"><label>Status</label><select name="is_active" class="la-inp" id="kbActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
    </form>`, async btn => {
    const fd = new FormData(document.getElementById('kbForm'));
    const r = await lpa.post('save_chatbot_kb', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadChatbotKB(); }
  });

  if (id) {
    lpa.get('get_chatbot_kb').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('kbCat').value    = f.category || '';
      document.getElementById('kbPri').value    = f.priority || '5';
      document.getElementById('kbKeys').value   = f.keywords || '';
      document.getElementById('kbQ').value      = f.question || '';
      document.getElementById('kbA').value      = f.answer || '';
      document.getElementById('kbActive').value = f.is_active || '1';
    });
  }
}

async function deleteKB(id) {
  if (!lpa.confirm('Delete this knowledge base entry?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_chatbot_kb', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadChatbotKB();
}

/* ══════════════════════════════════════════════════════
   TAB: CHAT LOGS
══════════════════════════════════════════════════════ */

async function loadChatLogs() {
  const panel = document.getElementById('panel-logs');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_chat_logs', '&limit=100');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-comments"></i> Chat Logs (${rows.length})</h3>
      <button class="la-btn la-btn-danger" onclick="clearAllLogs()"><i class="fas fa-trash-alt"></i> Clear All</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Session</th><th>User Message</th><th>Bot Reply</th><th>Time</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td style="font-size:.75rem;color:var(--text-muted)">${(r.session_id || '').substring(0, 12)}…</td>
      <td style="font-size:.85rem">${lpa.escHtml((r.user_message || '').substring(0, 60))}</td>
      <td style="font-size:.85rem;color:var(--text-muted)">${lpa.escHtml((r.bot_response || '').substring(0, 60))}</td>
      <td style="font-size:.8rem;white-space:nowrap">${r.created_at || '—'}</td>
      <td><button class="la-btn-sm la-btn-delete" onclick="deleteChatLog(${r.id})"><i class="fas fa-trash"></i></button></td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

async function deleteChatLog(id) {
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_chat_log', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadChatLogs();
}

async function clearAllLogs() {
  if (!lpa.confirm('Clear ALL chat logs? This cannot be undone.')) return;
  const fd = new FormData();
  const r = await lpa.post('clear_all_chat_logs', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadChatLogs();
}

/* ══════════════════════════════════════════════════════
   TAB: PUBLIC BOOKINGS
══════════════════════════════════════════════════════ */

async function loadBookings() {
  const panel = document.getElementById('panel-bookings');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_public_bookings');
  const rows = res.data || [];

  const statusColors = { Pending: '#f59e0b', Confirmed: '#27ae60', Cancelled: '#e74c3c', Completed: '#2F80ED' };

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-calendar-check"></i> Public Appointment Requests (${rows.length})</h3>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Name</th><th>Phone</th><th>Date Requested</th><th>Preferred Doctor</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    const col = statusColors[r.status] || '#6b7280';
    html += `<tr>
      <td><strong>${lpa.escHtml(r.patient_name || '—')}</strong><br><small>${lpa.escHtml(r.patient_email || '')}</small></td>
      <td>${lpa.escHtml(r.patient_phone || '—')}</td>
      <td>${lpa.escHtml(r.preferred_date || '—')} ${lpa.escHtml(r.preferred_time || '')}</td>
      <td>${lpa.escHtml(r.preferred_doctor_name || 'Any')}</td>
      <td><span class="la-chip" style="background:${col}20;color:${col}">${r.status}</span></td>
      <td>
        <select class="la-inp-sm" onchange="updateBookingStatus(${r.id}, this.value)">
          <option value="">Change…</option>
          <option value="Pending">Pending</option>
          <option value="Confirmed">Confirm</option>
          <option value="Cancelled">Cancel</option>
          <option value="Completed">Complete</option>
        </select>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

async function updateBookingStatus(id, status) {
  if (!status) return;
  const fd = new FormData(); fd.append('id', id); fd.append('status', status);
  const r = await lpa.post('update_booking_status', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadBookings();
}

/* ══════════════════════════════════════════════════════
   TAB: SITE CONFIG
══════════════════════════════════════════════════════ */

async function loadSiteConfig() {
  const panel = document.getElementById('panel-config');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_site_config');
  const cfg = res.data || {};

  const fields = [
    ['site_name',       'Site Name',       'RMU Medical Sickbay'],
    ['site_tagline',    'Site Tagline',    'Your Health, Our Priority'],
    ['contact_phone',   'Phone Number',    '0502371207'],
    ['contact_email',   'Email Address',   'sickbay.text@st.rmu.edu.gh'],
    ['contact_address', 'Address',         'Regional Maritime University, Nungua, Accra'],
    ['emergency_line',  'Emergency Line',  '153'],
    ['facebook_url',    'Facebook URL',    ''],
    ['twitter_url',     'Twitter/X URL',   ''],
    ['instagram_url',   'Instagram URL',   ''],
    ['linkedin_url',    'LinkedIn URL',    ''],
    ['chatbot_name',    'Chatbot Name',    'RMU Medical Assistant'],
    ['chatbot_greeting','Chatbot Greeting','Hello! How can I help you today?'],
  ];

  let html = `
    <h3 class="la-section-head"><i class="fas fa-cog"></i> Site Configuration</h3>
    <form id="configForm" class="la-form-grid">`;

  fields.forEach(([key, label, placeholder]) => {
    const val = lpa.escHtml(cfg[key] || '');
    html += `
      <div class="la-fg">
        <label>${label}</label>
        <input type="text" name="config[${key}]" value="${val}" class="la-inp" placeholder="${lpa.escHtml(placeholder)}">
      </div>`;
  });

  html += `
      <div class="la-fg la-full">
        <button type="submit" class="la-btn la-btn-primary"><i class="fas fa-save"></i> Save All Config</button>
      </div>
    </form>`;

  panel.innerHTML = html;

  document.getElementById('configForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn    = e.target.querySelector('[type=submit]');
    lpa.loading(btn, true);
    const fd     = new FormData(e.target);
    const res2   = await fetch(API + '?action=save_site_config', { method: 'POST', body: fd });
    const data   = await res2.json();
    lpa.loading(btn, false);
    lpa.toast(data.message, data.success ? 'success' : 'error');
  });
}

/* ══════════════════════════════════════════════════════
   TAB: TEAM (placeholder — same pattern as above)
══════════════════════════════════════════════════════ */

async function loadTeam() {
  const panel = document.getElementById('panel-team');
  panel.innerHTML = `<div class="la-loading"><i class="fas fa-spinner fa-spin"></i> Loading…</div>`;
  const res = await lpa.get('get_team');
  const rows = res.data || [];

  let html = `
    <div class="la-toolbar">
      <h3 class="la-section-head"><i class="fas fa-users"></i> Team Members</h3>
      <button class="la-btn la-btn-primary" onclick="editTeam(0)"><i class="fas fa-plus"></i> Add Member</button>
    </div>
    <div class="la-table-wrap"><table class="la-table">
      <thead><tr><th>Name</th><th>Title</th><th>Department</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>`;

  rows.forEach(r => {
    html += `<tr>
      <td><strong>${lpa.escHtml(r.name)}</strong></td>
      <td>${lpa.escHtml(r.title || '—')}</td>
      <td>${lpa.escHtml(r.department || '—')}</td>
      <td><span class="la-chip">${lpa.escHtml(r.member_type || 'staff')}</span></td>
      <td>${lpa.badge(r.is_active)}</td>
      <td>
        <button class="la-btn-sm la-btn-edit" onclick="editTeam(${r.id})"><i class="fas fa-edit"></i></button>
        <button class="la-btn-sm la-btn-delete" onclick="deleteTeam(${r.id})"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
  });

  html += `</tbody></table></div>`;
  panel.innerHTML = html;
}

function editTeam(id) {
  showModal(id ? 'Edit Team Member' : 'New Team Member', `
    <form id="teamForm" class="la-form-grid">
      <input type="hidden" name="id" value="${id}">
      <div class="la-fg"><label>Full Name</label><input type="text" name="name" class="la-inp" id="tmName"></div>
      <div class="la-fg"><label>Title/Role</label><input type="text" name="title" class="la-inp" id="tmTitle"></div>
      <div class="la-fg"><label>Department</label><input type="text" name="department" class="la-inp" id="tmDept"></div>
      <div class="la-fg"><label>Type</label>
        <select name="member_type" class="la-inp" id="tmType">
          <option value="staff">Staff</option><option value="doctor">Doctor</option><option value="nurse">Nurse</option>
        </select>
      </div>
      <div class="la-fg la-full"><label>Bio</label><textarea name="bio" class="la-inp" rows="3" id="tmBio"></textarea></div>
      <div class="la-fg la-full"><label>Qualifications</label><input type="text" name="qualifications" class="la-inp" id="tmQuals"></div>
      <div class="la-fg"><label>Photo Path</label><input type="text" name="photo_path" class="la-inp" id="tmPhoto"></div>
      <div class="la-fg"><label>Email</label><input type="email" name="email" class="la-inp" id="tmEmail"></div>
      <div class="la-fg"><label>Status</label><select name="is_active" class="la-inp" id="tmActive"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="la-fg"><label>Order</label><input type="number" name="display_order" class="la-inp" value="0" id="tmOrder"></div>
    </form>`, async btn => {
    const fd = new FormData(document.getElementById('teamForm'));
    const r = await lpa.post('save_team', fd);
    lpa.loading(btn, false);
    lpa.toast(r.message, r.success ? 'success' : 'error');
    if (r.success) { closeModal(); loadTeam(); }
  });

  if (id) {
    lpa.get('get_team').then(res => {
      const f = (res.data || []).find(x => x.id == id);
      if (!f) return;
      document.getElementById('tmName').value   = f.name || '';
      document.getElementById('tmTitle').value  = f.title || '';
      document.getElementById('tmDept').value   = f.department || '';
      document.getElementById('tmType').value   = f.member_type || 'staff';
      document.getElementById('tmBio').value    = f.bio || '';
      document.getElementById('tmQuals').value  = f.qualifications || '';
      document.getElementById('tmPhoto').value  = f.photo_path || '';
      document.getElementById('tmEmail').value  = f.email || '';
      document.getElementById('tmActive').value = f.is_active || '1';
      document.getElementById('tmOrder').value  = f.display_order || '0';
    });
  }
}

async function deleteTeam(id) {
  if (!lpa.confirm('Remove this team member?')) return;
  const fd = new FormData(); fd.append('id', id);
  const r = await lpa.post('delete_team', fd);
  lpa.toast(r.message, r.success ? 'success' : 'error');
  if (r.success) loadTeam();
}

/* ══════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  initTabs();

  // Modal close handlers
  document.getElementById('laModalClose')?.addEventListener('click', closeModal);
  document.getElementById('laModalCancelBtn')?.addEventListener('click', closeModal);
  document.getElementById('laModal')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
  });
});
