<?php
/**
 * Notification Bell Include
 * /php/dashboards/includes/notif_bell.php
 *
 * Drop this into any dashboard's <head> (the CSS/JS parts)
 * and into the topbar (the bell button part).
 *
 * Usage:
 *   In <head>:            <?php include 'includes/notif_bell.php'; // head ?>
 *   In topbar:            <?php include 'includes/notif_bell.php'; // bell ?>
 *   Before </body>:       <?php include 'includes/notif_bell.php'; // scripts ?>
 *
 * OR: pass $notif_part = 'head'|'bell'|'scripts' before including.
 */
$notif_part = $notif_part ?? 'all'; // default: output all (for old callers)

if (in_array($notif_part, ['head','all'])): ?>
<link rel="stylesheet" href="/RMU-Medical-Management-System/css/notifications.css">
<?php endif;

if (in_array($notif_part, ['bell','all'])):
  $notif_unread     = (int)($unread ?? 0);
  $notif_class      = $notif_unread > 0 ? 'adm-notif-btn has-unread' : 'adm-notif-btn';
  $notif_display    = $notif_unread > 0 ? 'flex' : 'none';
  $notif_label      = $notif_unread > 99 ? '99+' : $notif_unread;
?>
<button id="rmuBellBtn" class="btn btn-primary <?=$notif_class?>" title="Notifications" aria-label="Notifications"><span class="btn-text">
  <i class="fas fa-bell"></i>
  <span id="rmuBellCount" style="display:<?=$notif_display?>"><?=$notif_label?></span>
</span></button>
<?php endif;

if (in_array($notif_part, ['scripts','all'])): ?>
<script src="/RMU-Medical-Management-System/js/notifications.js"></script>
<?php endif;
