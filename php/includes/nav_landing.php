<?php
/**
 * nav_landing.php — Unified Landing Page Navigation
 * Include this file in any public PHP page.
 * Before including, optionally set:
 *   $active_page — 'home'|'about'|'services'|'doctors'|'staff'|'director'|'contact'|'booking'
 *   $base        — URL prefix e.g. '/RMU-Medical-Management-System' (default)
 */
$active_page = $active_page ?? '';
$_base       = '/RMU-Medical-Management-System';

function nav_active(string $page, string $current): string {
    return $page === $current ? ' class="active"' : '';
}
?>
<!-- ═══════════════════════════════════════════════════════
     NAVIGATION — RMU Medical Sickbay
     ══════════════════════════════════════════════════════ -->
<nav class="lp-nav" id="lpNav">
  <div class="lp-nav-inner">

    <!-- Logo -->
    <a href="<?= $_base ?>/html/index.html" class="lp-nav-logo">
      <img src="<?= $_base ?>/image/logo-ju-small.png" alt="RMU Medical Sickbay Logo">
      <span class="lp-nav-logo-text">RMU <span>Medical</span> Sickbay</span>
    </a>

    <!-- Centre Nav Links -->
    <ul class="lp-nav-links">
      <li><a href="<?= $_base ?>/html/index.html"<?= nav_active('home', $active_page) ?>><i class="fas fa-home"></i> Home</a></li>
      <li><a href="<?= $_base ?>/html/about.html"<?= nav_active('about', $active_page) ?>><i class="fas fa-info-circle"></i> About</a></li>
      <li class="lp-has-dropdown">
        <a href="<?= $_base ?>/html/services.html"<?= nav_active('services', $active_page) ?>><i class="fas fa-stethoscope"></i> Services <i class="fas fa-chevron-down lp-caret"></i></a>
        <ul class="lp-nav-dropdown">
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-truck-medical"></i> Emergency Care</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-flask"></i> Laboratory Services</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-pills"></i> Pharmacy</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-bed"></i> Inpatient Care</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-brain"></i> Mental Health</a></li>
        </ul>
      </li>
      <li class="lp-has-dropdown">
        <a href="<?= $_base ?>/html/doctors.html"<?= (in_array($active_page, ['doctors','staff','director']) ? ' class="active"' : '') ?>><i class="fas fa-users"></i> Team <i class="fas fa-chevron-down lp-caret"></i></a>
        <ul class="lp-nav-dropdown">
          <li><a href="<?= $_base ?>/html/director.html"<?= nav_active('director', $active_page) ?>><i class="fas fa-user-tie"></i> Director</a></li>
          <li><a href="<?= $_base ?>/html/doctors.html"<?= nav_active('doctors', $active_page) ?>><i class="fas fa-user-doctor"></i> Doctors</a></li>
          <li><a href="<?= $_base ?>/html/staff.html"<?= nav_active('staff', $active_page) ?>><i class="fas fa-users"></i> Staff</a></li>
        </ul>
      </li>
      <li><a href="<?= $_base ?>/php/booking.php"<?= nav_active('booking', $active_page) ?>><i class="fas fa-calendar-check"></i> Book Now</a></li>
    </ul>

    <!-- Right Actions -->
    <div class="lp-nav-right">
      <a href="tel:153" class="lp-emergency-pill" title="Emergency Hotline">
        <i class="fas fa-phone-volume"></i> 153
      </a>
      <button class="lp-theme-toggle" id="lpThemeToggle" aria-label="Toggle theme" title="Switch Theme">
        <div class="toggle-icon icon-moon active"><i class="fas fa-moon"></i></div>
        <div class="toggle-icon icon-sun"><i class="fas fa-sun"></i></div>
      </button>
      <a href="<?= $_base ?>/php/index.php" class="lp-nav-btn lp-nav-btn-outline">Login</a>
      <a href="<?= $_base ?>/php/register.php" class="lp-nav-btn lp-nav-btn-solid">Register</a>
      <div class="lp-hamburger" id="lpHamburger" role="button" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </div>
    </div>

  </div>
</nav>

<!-- Mobile Menu -->
<div class="lp-mobile-menu" id="lpMobileMenu" role="navigation" aria-label="Mobile navigation">
  <ul class="lp-mobile-links">
    <li><a href="<?= $_base ?>/html/index.html"<?= nav_active('home', $active_page) ?>><i class="fas fa-home"></i> Home</a></li>
    <li><a href="<?= $_base ?>/html/about.html"<?= nav_active('about', $active_page) ?>><i class="fas fa-info-circle"></i> About</a></li>
    <li><a href="<?= $_base ?>/html/services.html"<?= nav_active('services', $active_page) ?>><i class="fas fa-stethoscope"></i> Services</a></li>
    <li><a href="<?= $_base ?>/html/director.html"<?= nav_active('director', $active_page) ?>><i class="fas fa-user-tie"></i> Director</a></li>
    <li><a href="<?= $_base ?>/html/doctors.html"<?= nav_active('doctors', $active_page) ?>><i class="fas fa-user-doctor"></i> Doctors</a></li>
    <li><a href="<?= $_base ?>/html/staff.html"<?= nav_active('staff', $active_page) ?>><i class="fas fa-users"></i> Staff</a></li>
    <li><a href="<?= $_base ?>/php/booking.php"<?= nav_active('booking', $active_page) ?>><i class="fas fa-calendar-check"></i> Book Appointment</a></li>
  </ul>
  <div class="lp-mobile-actions">
    <a href="tel:153" class="lp-emergency-pill"><i class="fas fa-phone-volume"></i> Emergency: 153</a>
    <a href="<?= $_base ?>/php/index.php" class="lp-nav-btn lp-nav-btn-outline">Login</a>
    <a href="<?= $_base ?>/php/register.php" class="lp-nav-btn lp-nav-btn-solid">Register</a>
  </div>
</div>
