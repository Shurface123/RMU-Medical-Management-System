/**
 * landing.js — RMU Medical Sickbay Advanced Landing Page
 * Handles: Navigation, Theme, Parallax, Scroll Animations,
 *          Stats Counter, Modals, Gallery, Testimonials,
 *          Doctor/Staff Filters, FAQ Accordion, Dynamic Content
 */

const BASE = '/RMU-Medical-Management-System';
const API  = `${BASE}/php/public/landing_api.php`;

/* ============================================================
   THEME MANAGEMENT
   ============================================================ */
const ThemeManager = (() => {
  const STORAGE_KEY = 'lp-theme';

  function get()    { return localStorage.getItem(STORAGE_KEY) || 'light'; }
  function set(t)   { localStorage.setItem(STORAGE_KEY, t); apply(t); }
  function toggle() { set(get() === 'dark' ? 'light' : 'dark'); }

  function apply(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelectorAll('.lp-theme-toggle').forEach(btn => {
      const sunIcon  = btn.querySelector('.icon-sun');
      const moonIcon = btn.querySelector('.icon-moon');
      if (theme === 'dark') {
        sunIcon?.classList.add('active');
        moonIcon?.classList.remove('active');
      } else {
        moonIcon?.classList.add('active');
        sunIcon?.classList.remove('active');
      }
    });
  }

  function init() {
    apply(get());
    document.querySelectorAll('.lp-theme-toggle').forEach(btn => {
      btn.addEventListener('click', toggle);
    });
  }

  return { init, get, set, toggle };
})();


/* ============================================================
   NAVIGATION
   ============================================================ */
const Nav = (() => {
  const nav         = document.getElementById('lpNav');
  const hamburger   = document.getElementById('lpHamburger');
  const mobileMenu  = document.getElementById('lpMobileMenu');
  let menuOpen      = false;

  function init() {
    if (!nav) return;

    // Scroll behaviour
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });

    // Hamburger toggle
    hamburger?.addEventListener('click', () => {
      menuOpen = !menuOpen;
      hamburger.classList.toggle('open', menuOpen);
      mobileMenu?.classList.toggle('open', menuOpen);
    });

    // Close mobile menu on link click
    mobileMenu?.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        menuOpen = false;
        hamburger?.classList.remove('open');
        mobileMenu?.classList.remove('open');
      });
    });

    // Close on outside click
    document.addEventListener('click', e => {
      if (menuOpen && !nav.contains(e.target) && !mobileMenu?.contains(e.target)) {
        menuOpen = false;
        hamburger?.classList.remove('open');
        mobileMenu?.classList.remove('open');
      }
    });

    // Highlight current page
    highlightActive();
  }

  function highlightActive() {
    const path = window.location.pathname.split('/').pop();
    document.querySelectorAll('.lp-nav-links a, .lp-mobile-links a').forEach(a => {
      const href = a.getAttribute('href')?.split('/').pop()?.split('?')[0] || '';
      if (href === path || (path === '' && href === 'index.html')) {
        a.classList.add('active');
      }
    });
  }

  return { init };
})();


/* ============================================================
   PARALLAX HERO
   ============================================================ */
const Parallax = (() => {
  function init() {
    const bg = document.querySelector('.lp-hero-bg');
    if (!bg) return;
    // Only on non-mobile for performance
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    let ticking = false;
    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(() => {
          const y = window.scrollY;
          bg.style.transform = `translateY(${y * 0.35}px)`;
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
  }
  return { init };
})();


/* ============================================================
   TYPEWRITER EFFECT
   ============================================================ */
function initTypewriter(el, texts, speed = 80, pause = 2200) {
  if (!el) return;
  let ti = 0, ci = 0, deleting = false;

  function type() {
    const text = texts[ti];
    el.textContent = deleting ? text.slice(0, ci--) : text.slice(0, ci++);

    if (!deleting && ci > text.length) {
      setTimeout(() => { deleting = true; type(); }, pause);
      return;
    }
    if (deleting && ci < 0) {
      deleting = false;
      ti = (ti + 1) % texts.length;
      ci = 0;
    }
    setTimeout(type, deleting ? speed / 2 : speed);
  }
  type();
}


/* ============================================================
   SCROLL ANIMATIONS (Intersection Observer)
   ============================================================ */
const ScrollAnim = (() => {
  function init() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          // Don't unobserve — keeps state once visible
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.lp-animate, .lp-animate-children').forEach(el => {
      observer.observe(el);
    });
  }
  return { init };
})();


/* ============================================================
   STATS COUNTER ANIMATION
   ============================================================ */
const StatsCounter = (() => {
  function countUp(el, target, suffix, duration = 1800) {
    const start = Date.now();
    const isNum  = /^\d+$/.test(target);
    if (!isNum) { el.textContent = target; return; }
    const end    = parseInt(target);

    function update() {
      const elapsed = Date.now() - start;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
      el.textContent  = Math.floor(eased * end) + suffix;
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  }

  function init() {
    const section = document.querySelector('.lp-stats-section');
    if (!section) return;

    const observer = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) {
        section.querySelectorAll('[data-count]').forEach(el => {
          const raw    = el.dataset.count || '0';
          const suffix = el.dataset.suffix || '';
          countUp(el, raw, suffix);
        });
        observer.disconnect();
      }
    }, { threshold: 0.3 });

    observer.observe(section);
  }
  return { init };
})();


/* ============================================================
   MODAL SYSTEM
   ============================================================ */
const Modal = (() => {
  let activeBackdrop = null;

  function open(id) {
    const backdrop = document.getElementById(id);
    if (!backdrop) return;
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
    activeBackdrop = backdrop;
  }

  function close(id) {
    const backdrop = id ? document.getElementById(id) : activeBackdrop;
    if (!backdrop) return;
    backdrop.classList.remove('open');
    document.body.style.overflow = '';
    activeBackdrop = null;
  }

  function init() {
    // Backdrop click to close
    document.querySelectorAll('.lp-modal-backdrop').forEach(bd => {
      bd.addEventListener('click', e => {
        if (e.target === bd) close(bd.id);
      });
    });

    // Close buttons
    document.querySelectorAll('.lp-modal-close').forEach(btn => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.lp-modal-backdrop');
        if (modal) close(modal.id);
      });
    });

    // ESC key
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && activeBackdrop) close(activeBackdrop.id);
    });
  }

  return { init, open, close };
})();


/* ============================================================
   FAQ ACCORDION
   ============================================================ */
const FAQ = (() => {
  function init(container) {
    const root = container || document;
    root.querySelectorAll('.lp-faq-item').forEach(item => {
      const question = item.querySelector('.lp-faq-question');
      question?.addEventListener('click', () => {
        const isOpen = item.classList.contains('open');
        // Close others in same container
        root.querySelectorAll('.lp-faq-item.open').forEach(o => o.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
      });
    });
  }
  return { init };
})();


/* ============================================================
   GALLERY + LIGHTBOX
   ============================================================ */
const Gallery = (() => {
  let lb = null;

  function init() {
    lb = document.getElementById('lpLightbox');
    if (!lb) return;

    const img   = lb.querySelector('img');
    const close = lb.querySelector('.lp-lightbox-close');

    close?.addEventListener('click', closeLightbox);
    lb.addEventListener('click', e => { if (e.target === lb) closeLightbox(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

    // Gallery items
    document.querySelectorAll('.lp-gallery-item').forEach(item => {
      item.addEventListener('click', () => {
        const src = item.querySelector('img')?.src || item.dataset.src;
        if (src && img) {
          img.src = src;
          lb.classList.add('open');
          document.body.style.overflow = 'hidden';
        }
      });
    });
  }

  function closeLightbox() {
    lb?.classList.remove('open');
    document.body.style.overflow = '';
  }

  return { init };
})();


/* ============================================================
   TESTIMONIALS CAROUSEL
   ============================================================ */
const Carousel = (() => {
  function init(trackId) {
    const track = document.getElementById(trackId);
    if (!track) return;

    let index = 0;
    const cards      = track.querySelectorAll('.lp-testimonial-card');
    const prevBtn    = track.previousElementSibling;
    const nextBtn    = track.nextElementSibling;
    const total      = cards.length;
    const perView    = () => window.innerWidth < 768 ? 1 : window.innerWidth < 1024 ? 2 : 3;
    const maxIndex   = () => Math.max(0, total - perView());

    function move(n) {
      index = Math.min(Math.max(0, index + n), maxIndex());
      const w = cards[0]?.offsetWidth + 24 || 360;
      track.style.transform = `translateX(-${index * w}px)`;
    }

    prevBtn?.addEventListener('click', () => move(-1));
    nextBtn?.addEventListener('click', () => move(1));

    // Auto-advance
    let timer = setInterval(() => move(1), 5000);
    track.closest('.lp-testimonials')?.addEventListener('mouseenter', () => clearInterval(timer));
    track.closest('.lp-testimonials')?.addEventListener('mouseleave', () => {
      timer = setInterval(() => move(1), 5000);
    });
  }

  return { init };
})();


/* ============================================================
   FILTER / SEARCH
   ============================================================ */
function initFilter({ inputId, selectId, chipClass, cardClass, nameAttr, deptAttr }) {
  const input  = document.getElementById(inputId);
  const select = document.getElementById(selectId);

  function filter() {
    const query = (input?.value || '').toLowerCase();
    const dept  = (select?.value || '').toLowerCase();
    const chips = document.querySelectorAll(`.${chipClass}`);

    document.querySelectorAll(`.${cardClass}`).forEach(card => {
      const name = (card.dataset[nameAttr] || '').toLowerCase();
      const d    = (card.dataset[deptAttr] || '').toLowerCase();
      const show = (!query || name.includes(query)) && (!dept || d.includes(dept));
      card.style.display = show ? '' : 'none';
    });
  }

  input?.addEventListener('input', filter);
  select?.addEventListener('change', () => {
    document.querySelectorAll(`.${chipClass}`).forEach(c => c.classList.remove('active'));
    filter();
  });

  document.querySelectorAll(`.${chipClass}`).forEach(chip => {
    chip.addEventListener('click', () => {
      document.querySelectorAll(`.${chipClass}`).forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      if (select) select.value = chip.dataset.value || '';
      filter();
    });
  });
}


/* ============================================================
   SCROLL TO TOP
   ============================================================ */
function initScrollTop() {
  const btn = document.getElementById('lpScrollTop');
  if (!btn) return;

  window.addEventListener('scroll', () => {
    btn.classList.toggle('visible', window.scrollY > 300);
  }, { passive: true });

  btn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}


/* ============================================================
   DYNAMIC CONTENT LOADER
   ============================================================ */
const Loader = (() => {
  const cache = {};

  async function fetch(action, params = {}) {
    const key = action + JSON.stringify(params);
    if (cache[key]) return cache[key];
    const url = new URL(API, window.location.origin);
    url.searchParams.set('action', action);
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));
    const res  = await window.fetch(url);
    const data = await res.json();
    cache[key] = data;
    return data;
  }

  // Render stats section dynamically
  async function loadStats(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const res = await fetch('stats');
    if (!res.success || !res.data.length) return;
    container.innerHTML = res.data.map(s => `
      <div class="lp-stat-item lp-animate">
        <div class="lp-stat-icon"><i class="${escHtml(s.icon_class)}"></i></div>
        <div class="lp-stat-number" data-count="${parseStatNum(s.stat_value)}"
             data-suffix="${parseStatSuffix(s.stat_value)}">${escHtml(s.stat_value)}</div>
        <div class="lp-stat-label">${escHtml(s.label)}</div>
      </div>
    `).join('');
    ScrollAnim.init();
    StatsCounter.init();
  }

  // Render services grid
  async function loadServices(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const res = await fetch('services');
    if (!res.success) return;
    container.innerHTML = res.data.map((s, i) => `
      <div class="lp-card lp-animate" style="animation-delay:${i*0.07}s"
           data-service-id="${s.service_id}" onclick="openServiceModal(${s.service_id})">
        <div class="lp-card-icon"><i class="${escHtml(s.icon_class)}"></i></div>
        <h3 class="lp-card-title">${escHtml(s.name)}</h3>
        <p class="lp-card-text">${escHtml(s.description || '')}</p>
        <span class="lp-card-action">Learn More <i class="fas fa-arrow-right"></i></span>
      </div>
    `).join('');
    ScrollAnim.init();
    FAQ.init();
  }

  // Render doctors
  async function loadDoctors(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const res = await fetch('doctors');
    if (!res.success) return;

    if (!res.data.length) {
      container.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--lp-text-muted);grid-column:1/-1;">
        <i class="fas fa-user-doctor" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
        <p>Doctor profiles coming soon. Please call our front desk for doctor availability.</p>
      </div>`;
      return;
    }

    container.innerHTML = res.data.map(doc => `
      <div class="lp-card lp-doctor-card lp-animate"
           data-name="${escHtml(doc.name)}" data-dept="${escHtml(doc.department || '')}"
           onclick="openDoctorModal(${doc.doctor_id})">
        <img class="lp-doctor-photo"
             src="${doc.profile_image ? BASE + '/' + escHtml(doc.profile_image) : 'data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><circle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%232F80ED22\'/><text x=\'50\' y=\'60\' font-size=\'40\' text-anchor=\'middle\' fill=\'%232F80ED\'>👨‍⚕️</text></svg>'}"
             alt="${escHtml(doc.name)}" onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'><circle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%232F80ED22\'/><text x=\'50\' y=\'62\' font-size=\'42\' text-anchor=\'middle\'>👨‍⚕️</text></svg>'">
        <div class="lp-doctor-name">${escHtml(doc.name)}</div>
        <div class="lp-doctor-spec">${escHtml(doc.specialization || 'General Physician')}</div>
        <div class="lp-doctor-info"><i class="fas fa-building"></i> ${escHtml(doc.department || 'General Medicine')}</div>
        <span class="lp-card-action" style="justify-content:center">View Profile <i class="fas fa-arrow-right"></i></span>
      </div>
    `).join('');

    window._doctorData = res.data;
    ScrollAnim.init();
    initFilter({
      inputId: 'doctorSearch', selectId: 'doctorDeptFilter',
      chipClass: 'dept-chip', cardClass: 'lp-doctor-card',
      nameAttr: 'name', deptAttr: 'dept'
    });
  }

  // Render staff
  async function loadStaff(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const res = await fetch('staff');
    if (!res.success) return;

    if (!res.data.length) {
      container.innerHTML = `<div style="text-align:center;padding:3rem;color:var(--lp-text-muted);grid-column:1/-1;">
        <i class="fas fa-users" style="font-size:3rem;margin-bottom:1rem;display:block;"></i>
        <p>Staff directory is being updated. Please check back soon.</p>
      </div>`;
      return;
    }

    container.innerHTML = res.data.map(s => `
      <div class="lp-staff-flip" data-dept="${escHtml(s.department || '')}"
           onclick="openStaffModal(${s.entry_id})">
        <div class="lp-staff-flip-inner">
          <div class="lp-staff-front">
            ${s.photo_path
              ? `<img class="lp-staff-photo" src="${BASE}/${escHtml(s.photo_path)}" alt="${escHtml(s.name)}">`
              : `<div class="lp-staff-photo" style="background:var(--lp-primary-bg);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:var(--lp-primary)"><i class="fas fa-user-nurse"></i></div>`}
            <div style="font-size:1rem;font-weight:700;">${escHtml(s.name)}</div>
            <div style="font-size:0.85rem;color:var(--lp-text-muted);">${escHtml(s.role_title)}</div>
          </div>
          <div class="lp-staff-back">
            <div class="lp-staff-back-name">${escHtml(s.name)}</div>
            <div class="lp-staff-back-role">${escHtml(s.role_title)}</div>
            ${s.department ? `<div class="lp-staff-back-dept">${escHtml(s.department)}</div>` : ''}
            <div style="margin-top:0.8rem;font-size:0.8rem;opacity:0.8;">Click for full profile</div>
          </div>
        </div>
      </div>
    `).join('');

    window._staffData = res.data;
  }

  // Render FAQ
  async function loadFAQ(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const res = await fetch('faq');
    if (!res.success) return;
    container.innerHTML = res.data.map(f => `
      <div class="lp-faq-item">
        <div class="lp-faq-question">
          <span>${escHtml(f.question)}</span>
          <div class="lp-faq-toggle"><i class="fas fa-plus"></i></div>
        </div>
        <div class="lp-faq-answer">${escHtml(f.answer)}</div>
      </div>
    `).join('');
    FAQ.init(container);
  }

  // Render testimonials
  async function loadTestimonials(trackId) {
    const track = document.getElementById(trackId);
    if (!track) return;
    const res = await fetch('testimonials');
    if (!res.success || !res.data.length) {
      // Show placeholder testimonials
      const placeholders = [
        { patient_name: 'Kofi Asante', rating: 5, content: 'The care I received at RMU Sickbay was exceptional. The doctors were thorough and the staff were incredibly kind.' },
        { patient_name: 'Ama Boateng', rating: 5, content: 'Quick, professional, and compassionate service. I was seen within minutes of arriving during an emergency.' },
        { patient_name: 'James Mensah', rating: 4, content: 'Great facility with modern equipment. The pharmacy always has my prescribed medication in stock.' },
      ];
      track.innerHTML = placeholders.map(t => renderTestimonial(t)).join('');
      return;
    }
    track.innerHTML = res.data.map(t => renderTestimonial(t)).join('');
    Carousel.init(trackId);
  }

  function renderTestimonial(t) {
    const stars = '★'.repeat(t.rating || 5) + '☆'.repeat(5 - (t.rating || 5));
    return `
      <div class="lp-testimonial-card">
        <div class="lp-testimonial-stars">${stars}</div>
        <p class="lp-testimonial-text">"${escHtml(t.content)}"</p>
        <div class="lp-testimonial-author">
          <div class="lp-testimonial-avatar"><i class="fas fa-user"></i></div>
          <div>
            <div class="lp-testimonial-name">${escHtml(t.patient_name || 'Patient')}</div>
            <div class="lp-testimonial-role">RMU Community Member</div>
          </div>
        </div>
      </div>
    `;
  }

  // Hero dynamic load
  async function loadHero() {
    const res = await fetch('hero');
    if (!res.success) return;
    const d = res.data;

    const bg = document.querySelector('.lp-hero-bg');
    if (bg && d.hero_bg_image_url) {
      bg.style.backgroundImage = `url('${d.hero_bg_image_url}')`;
    }

    const overlay = document.querySelector('.lp-hero-overlay');
    if (overlay && d.overlay_opacity) {
      overlay.style.background = `rgba(47, 128, 237, ${d.overlay_opacity})`;
    }

    const tw = document.getElementById('heroTypewriter');
    if (tw && d.headline_text) {
      const texts = [d.headline_text, 'Expert Healthcare, 24/7', 'Your Campus Medical Partner'];
      initTypewriter(tw, texts);
    }

    const subEl = document.getElementById('heroSubheadline');
    if (subEl && d.subheadline_text) subEl.textContent = d.subheadline_text;

    const btn1 = document.getElementById('heroCta1');
    if (btn1 && d.cta1_text) { btn1.textContent = ''; btn1.innerHTML = `<i class="fas fa-calendar-check"></i> ${escHtml(d.cta1_text)}`; if (d.cta1_url) btn1.href = d.cta1_url; }

    const btn2 = document.getElementById('heroCta2');
    if (btn2 && d.cta2_text) { btn2.textContent = ''; btn2.innerHTML = `<i class="fas fa-info-circle"></i> ${escHtml(d.cta2_text)}`; if (d.cta2_url) btn2.href = d.cta2_url; }
  }

  return { loadHero, loadStats, loadServices, loadDoctors, loadStaff, loadFAQ, loadTestimonials, fetch };
})();


/* ============================================================
   MODAL OPENERS — Doctor / Staff / Service
   ============================================================ */
window.openDoctorModal = function(id) {
  const data = (window._doctorData || []).find(d => d.doctor_id == id);
  if (!data) return;

  const m = document.getElementById('lpDoctorModal');
  if (!m) return;

  const quali = data.qualifications ? data.qualifications.split('|').map(q => `<li>${escHtml(q)}</li>`).join('') : '';

  m.querySelector('.lp-modal-title').textContent = data.name;
  m.querySelector('.lp-modal-body').innerHTML = `
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
      ${data.profile_image
        ? `<img src="${BASE}/${escHtml(data.profile_image)}" alt="${escHtml(data.name)}"
               style="width:110px;height:110px;border-radius:50%;object-fit:cover;border:3px solid var(--lp-primary-bg);">`
        : `<div style="width:110px;height:110px;border-radius:50%;background:var(--lp-primary-bg);display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--lp-primary);">👨‍⚕️</div>`}
      <div style="flex:1;">
        <h3 style="font-size:1.3rem;font-weight:700;margin-bottom:0.3rem;">${escHtml(data.name)}</h3>
        <div style="color:var(--lp-primary);font-weight:600;margin-bottom:0.5rem;">${escHtml(data.specialization || 'General Physician')}</div>
        <div style="font-size:0.88rem;color:var(--lp-text-muted);margin-bottom:0.3rem;"><i class="fas fa-building"></i> ${escHtml(data.department || 'General Medicine')}</div>
        ${data.experience_years ? `<div style="font-size:0.88rem;color:var(--lp-text-muted);"><i class="fas fa-clock"></i> ${data.experience_years} years experience</div>` : ''}
      </div>
    </div>
    ${data.bio ? `<p style="font-size:0.92rem;line-height:1.7;color:var(--lp-text);margin-bottom:1.2rem;">${escHtml(data.bio)}</p>` : ''}
    ${quali ? `<h4 style="font-size:0.9rem;font-weight:700;margin-bottom:0.6rem;">Qualifications</h4><ul style="list-style:none;font-size:0.88rem;color:var(--lp-text-muted);">${quali}</ul>` : ''}
    ${data.consultation_hours ? `<div style="margin-top:1rem;padding:0.8rem 1rem;background:var(--lp-bg-alt);border-radius:10px;font-size:0.88rem;"><i class="fas fa-calendar-alt" style="color:var(--lp-primary);margin-right:0.4rem;"></i> <strong>Consultation Hours:</strong> ${escHtml(data.consultation_hours)}</div>` : ''}
    <div style="margin-top:1.2rem;display:flex;gap:0.8rem;flex-wrap:wrap;">
      <a href="${BASE}/php/booking.php" class="lp-hero-btn lp-hero-btn-primary" style="font-size:0.88rem;padding:0.6rem 1.2rem;background:var(--lp-primary);color:#fff;border-radius:8px;">
        <i class="fas fa-calendar-check"></i> Book Appointment
      </a>
    </div>
  `;

  Modal.open('lpDoctorModal');
};

window.openStaffModal = function(id) {
  const data = (window._staffData || []).find(s => s.entry_id == id);
  if (!data) return;
  const m = document.getElementById('lpStaffModal');
  if (!m) return;

  m.querySelector('.lp-modal-title').textContent = data.name;
  m.querySelector('.lp-modal-body').innerHTML = `
    <div style="text-align:center;margin-bottom:1.5rem;">
      ${data.photo_path
        ? `<img src="${BASE}/${escHtml(data.photo_path)}" alt="${escHtml(data.name)}"
               style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--lp-primary-bg);margin:0 auto;">`
        : `<div style="width:120px;height:120px;border-radius:50%;background:var(--lp-primary-bg);display:flex;align-items:center;justify-content:center;font-size:3rem;color:var(--lp-primary);margin:0 auto;"><i class="fas fa-user-nurse"></i></div>`}
      <h3 style="font-size:1.2rem;font-weight:700;margin-top:1rem;">${escHtml(data.name)}</h3>
      <div style="color:var(--lp-primary);font-weight:600;font-size:0.95rem;">${escHtml(data.role_title)}</div>
      ${data.department ? `<div style="font-size:0.85rem;color:var(--lp-text-muted);margin-top:0.3rem;"><i class="fas fa-building"></i> ${escHtml(data.department)}</div>` : ''}
    </div>
  `;

  Modal.open('lpStaffModal');
};

window.openServiceModal = async function(id) {
  const m = document.getElementById('lpServiceModal');
  if (!m) return;
  // Find in cached services
  const res = await Loader.fetch('services');
  const data = (res.data || []).find(s => s.service_id == id);
  if (!data) return;

  m.querySelector('.lp-modal-title').innerHTML = `<i class="${escHtml(data.icon_class)}" style="color:var(--lp-primary);margin-right:0.5rem;"></i>${escHtml(data.name)}`;
  m.querySelector('.lp-modal-body').innerHTML = `
    <p style="font-size:0.95rem;line-height:1.7;color:var(--lp-text);margin-bottom:1.5rem;">${escHtml(data.description || 'Comprehensive service provided by our experienced medical team.')}</p>
    <a href="${BASE}/php/booking.php" class="lp-hero-btn" style="background:var(--lp-primary);color:#fff;font-size:0.88rem;padding:0.6rem 1.4rem;border-radius:8px;display:inline-flex;align-items:center;gap:0.5rem;">
      <i class="fas fa-calendar-check"></i> Book This Service
    </a>
  `;

  Modal.open('lpServiceModal');
};


/* ============================================================
   HELPERS
   ============================================================ */
function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function parseStatNum(val) {
  const m = String(val).match(/\d+/);
  return m ? m[0] : val;
}

function parseStatSuffix(val) {
  return String(val).replace(/[\d,]/g, '');
}


/* ============================================================
   ANNOUNCEMENTS BANNER
   ============================================================ */
async function loadAnnouncements() {
  const container = document.getElementById('lpAnnouncements');
  if (!container) return;
  const res = await Loader.fetch('announcements');
  if (!res.success || !res.data.length) { container.style.display = 'none'; return; }

  const typeColors = { news: '#2F80ED', event: '#27AE60', alert: '#E74C3C', notice: '#F39C12' };
  container.innerHTML = `
    <div style="
      background: linear-gradient(135deg, #0d1b3e, #1a3a6e);
      color: #fff; padding: 0.7rem 5%;
      display: flex; gap: 1rem; align-items: center;
      font-size: 0.88rem; overflow: hidden;">
      <span style="background:${typeColors[res.data[0].type]||'#2F80ED'};padding:0.2rem 0.6rem;border-radius:4px;font-weight:700;text-transform:uppercase;font-size:0.7rem;flex-shrink:0;">
        ${res.data[0].type}
      </span>
      <span style="flex:1;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">${escHtml(res.data[0].title)}: ${escHtml(res.data[0].content.substring(0, 120))}</span>
      <button onclick="this.closest('[id=lpAnnouncements]').style.display='none'" style="background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;font-size:1rem;padding:0.2rem;">&times;</button>
    </div>
  `;
}


/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  ThemeManager.init();
  Nav.init();
  Parallax.init();
  ScrollAnim.init();
  StatsCounter.init();
  Modal.init();
  FAQ.init();
  Gallery.init();
  initScrollTop();

  // Typewriter on index page (static fallback)
  const tw = document.getElementById('heroTypewriter');
  if (tw && !tw.dataset.dynamic) {
    initTypewriter(tw, ['Your Health, Our Priority', 'Expert Healthcare, 24/7', 'Your Campus Medical Partner']);
  }

  // Dynamic content (each page checks for containers)
  Loader.loadHero();
  if (document.getElementById('statsGrid'))      Loader.loadStats('statsGrid');
  if (document.getElementById('servicesGrid'))   Loader.loadServices('servicesGrid');
  if (document.getElementById('doctorsGrid'))    Loader.loadDoctors('doctorsGrid');
  if (document.getElementById('staffGrid'))      Loader.loadStaff('staffGrid');
  if (document.getElementById('faqContainer'))   Loader.loadFAQ('faqContainer');
  if (document.getElementById('testimonialTrack')) Loader.loadTestimonials('testimonialTrack');
  loadAnnouncements();

  // Expose for other scripts
  window.lpModal = Modal;
  window.lpLoader = Loader;
});
