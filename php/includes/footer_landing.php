<?php
/**
 * footer_landing.php — Unified Landing Page Footer
 * Include this at the bottom of any public PHP page (before </body>).
 */
$_base = '/RMU-Medical-Management-System';
?>
<!-- ═══════════════════════════════════════════════════════
     FOOTER — RMU Medical Sickbay
     ══════════════════════════════════════════════════════ -->
<footer class="lp-footer" id="lpFooter">
  <div class="lp-footer-inner">
    <div class="lp-footer-grid">

      <!-- Col 1 — Brand -->
      <div class="lp-footer-brand">
        <img src="<?= $_base ?>/image/logo-ju-small.png" alt="RMU Medical Sickbay Logo">
        <div class="lp-footer-brand-name">RMU <span>Medical</span> Sickbay</div>
        <p class="lp-footer-desc">Compassionate care for the RMU community. Serving students, faculty, and staff with expert healthcare since 2004.</p>
        <div class="lp-footer-socials">
          <a href="https://www.facebook.com/rmuofficial/" target="_blank" rel="noopener" class="lp-footer-social" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="https://x.com/rmuofficial" target="_blank" rel="noopener" class="lp-footer-social" aria-label="Twitter / X"><i class="fab fa-x-twitter"></i></a>
          <a href="https://www.instagram.com/rmuofficial/" target="_blank" rel="noopener" class="lp-footer-social" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="https://www.linkedin.com/school/regional-maritime-university/" target="_blank" rel="noopener" class="lp-footer-social" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>

      <!-- Col 2 — Quick Links -->
      <div>
        <div class="lp-footer-col-title">Quick Links</div>
        <ul class="lp-footer-links">
          <li><a href="<?= $_base ?>/html/index.html"><i class="fas fa-chevron-right"></i> Home</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Services</a></li>
          <li><a href="<?= $_base ?>/html/about.html"><i class="fas fa-chevron-right"></i> About Us</a></li>
          <li><a href="<?= $_base ?>/html/doctors.html"><i class="fas fa-chevron-right"></i> Our Doctors</a></li>
          <li><a href="<?= $_base ?>/html/staff.html"><i class="fas fa-chevron-right"></i> Medical Staff</a></li>
          <li><a href="<?= $_base ?>/html/director.html"><i class="fas fa-chevron-right"></i> Director</a></li>
          <li><a href="<?= $_base ?>/php/booking.php"><i class="fas fa-chevron-right"></i> Book Appointment</a></li>
        </ul>
      </div>

      <!-- Col 3 — Services -->
      <div>
        <div class="lp-footer-col-title">Our Services</div>
        <ul class="lp-footer-links">
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Free Checkups</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> 24/7 Ambulance</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Pharmacy</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Bed Facilities</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Laboratory</a></li>
          <li><a href="<?= $_base ?>/html/services.html"><i class="fas fa-chevron-right"></i> Emergency Care</a></li>
        </ul>
      </div>

      <!-- Col 4 — Contact & Emergency -->
      <div>
        <div class="lp-footer-col-title">Contact &amp; Emergency</div>
        <div class="lp-footer-emergency-badge">
          <i class="fas fa-phone-volume"></i>
          <span>Emergency Hotline</span>
          <a href="tel:153" class="lp-footer-emergency-num">153</a>
        </div>
        <ul class="lp-footer-contact-list">
          <li><i class="fas fa-phone"></i> <a href="tel:0302716071" style="color:inherit;">0302716071</a></li>
          <li><i class="fas fa-envelope"></i> <a href="mailto:sickbay@rmu.edu.gh" style="color:inherit;">sickbay@rmu.edu.gh</a></li>
          <li><i class="fas fa-map-marker-alt"></i> <span>Regional Maritime University,<br>Nungua, Accra, Ghana</span></li>
        </ul>
        <div class="lp-footer-hours">
          <div class="lp-footer-col-title" style="margin-top:1.2rem;margin-bottom:0.6rem;">Operating Hours</div>
          <ul class="lp-footer-hours-list">
            <li><span>Mon – Fri</span><span>8:00 AM – 8:00 PM</span></li>
            <li><span>Sat – Sun</span><span>9:00 AM – 5:00 PM</span></li>
            <li class="lp-footer-hours-24"><span>Emergency</span><span>24 / 7</span></li>
          </ul>
        </div>
      </div>

    </div><!-- /.lp-footer-grid -->

    <!-- Bottom Bar -->
    <div class="lp-footer-bottom">
      <div class="lp-footer-copy">
        &copy; <span id="footerYear"></span> <span>RMU Medical Sickbay</span>. All Rights Reserved.
      </div>
      <ul class="lp-footer-bottom-links">
        <li><a href="<?= $_base ?>/php/public/policy.php">Privacy Policy</a></li>
        <li><a href="<?= $_base ?>/php/public/policy.php">Terms of Use</a></li>
      </ul>
    </div>

  </div><!-- /.lp-footer-inner -->
</footer>
<script>const _fy=document.getElementById('footerYear');if(_fy)_fy.textContent=new Date().getFullYear();</script>
