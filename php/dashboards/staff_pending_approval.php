<?php
/**
 * staff_pending_approval.php — RMU Medical Sickbay
 * Standalone page shown to staff members whose account
 * has not yet been approved (or has been rejected) by admin.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$role_raw   = $_SESSION['user_role'] ?? 'staff';
$name       = htmlspecialchars($_SESSION['name'] ?? 'Staff Member', ENT_QUOTES, 'UTF-8');
$role_label = htmlspecialchars(ucwords(str_replace('_', ' ', $role_raw)), ENT_QUOTES, 'UTF-8');

// Determine status
$status = 'pending';
$reason = '';

// Check DB if we can
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../db_conn.php';
    $stmt = mysqli_prepare($conn, "SELECT approval_status, rejection_reason FROM staff WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        $uid = (int)$_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($r) {
            $status = $r['approval_status'] ?? 'pending';
            $reason = htmlspecialchars($r['rejection_reason'] ?? '', ENT_QUOTES, 'UTF-8');
        }
    }
}
// Also check session-stored reason (set by staff_security.php)
if (empty($reason) && !empty($_SESSION['_rejection_reason'])) {
    $reason = htmlspecialchars($_SESSION['_rejection_reason'], ENT_QUOTES, 'UTF-8');
}

$is_rejected = ($status === 'rejected');
$status_color = $is_rejected ? '#E74C3C' : '#F39C12';
$status_bg    = $is_rejected ? '#fde8e8' : '#fff8e1';
$status_label = $is_rejected ? 'Account Rejected' : 'Pending Approval';
$status_icon  = $is_rejected ? 'fa-times-circle' : 'fa-clock';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $is_rejected ? 'Account Rejected' : 'Account Pending Approval' ?> — RMU Medical</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #1C3A6B 0%, #4F46E5 50%, #818CF8 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
  }
  .card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    padding: 4rem 3.5rem;
    max-width: 540px;
    width: 100%;
    text-align: center;
    animation: slideIn .5s cubic-bezier(.4,0,.2,1);
  }
  @keyframes slideIn { from{opacity:0;transform:translateY(-20px)} to{opacity:1;transform:translateY(0)} }

  .logo { font-size: 1.4rem; font-weight: 700; color: #4F46E5; margin-bottom: 2.5rem;
          display:flex; align-items:center; justify-content:center; gap:.5rem; }
  .logo i { font-size: 2rem; }

  .status-bubble {
    width: 90px; height: 90px; border-radius: 50%;
    background: <?= $status_bg ?>; border: 3px solid <?= $status_color ?>;
    display: inline-flex; align-items: center; justify-content: center;
    margin-bottom: 2rem;
  }
  .status-bubble i { font-size: 3.5rem; color: <?= $status_color ?>; }

  h1 { font-size: 2.4rem; font-weight: 800; color: #1a1a2e; margin-bottom: 1rem; }

  .badge {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .4rem 1.2rem; border-radius: 20px; font-size: 1.3rem; font-weight: 600;
    background: <?= $status_bg ?>; color: <?= $status_color ?>;
    border: 1.5px solid <?= $status_color ?>; margin-bottom: 2rem;
  }

  .desc { font-size: 1.45rem; color: #555; line-height: 1.7; margin-bottom: 2.5rem; }

  .rejection-box {
    background: #fff5f5; border: 1.5px solid #E74C3C; border-radius: 12px;
    padding: 1.5rem 2rem; text-align: left; margin-bottom: 2rem;
  }
  .rejection-box strong { font-size: 1.3rem; color: #E74C3C; display: block; margin-bottom: .5rem; }
  .rejection-box p { font-size: 1.3rem; color: #555; }

  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2.5rem; text-align: left; }
  .info-box { background: #f8faff; border: 1px solid #e0e7ff; border-radius: 12px; padding: 1.2rem 1.5rem; }
  .info-box span { font-size: 1.1rem; color: #999; display: block; margin-bottom: .2rem; text-transform: uppercase; letter-spacing: .05em; }
  .info-box strong { font-size: 1.35rem; color: #1a1a2e; }

  .btn {
    display: inline-flex; align-items: center; gap: .6rem;
    padding: 1rem 2.5rem; border-radius: 50px; font-family: 'Poppins', sans-serif;
    font-size: 1.4rem; font-weight: 600; cursor: pointer; border: none; text-decoration: none;
    transition: transform .2s, opacity .2s;
  }
  .btn:hover { transform: translateY(-2px); opacity: .9; }
  .btn-logout { background: linear-gradient(135deg,#E74C3C,#C0392B); color: #fff; }
  .btn-check   { background: linear-gradient(135deg,#4F46E5,#818CF8); color: #fff; margin-right: 1rem; }

  .steps-list { text-align: left; background: #f0f4ff; border-radius: 14px; padding: 1.8rem 2rem; margin-bottom: 2.5rem; }
  .steps-list h4 { font-size: 1.3rem; font-weight: 700; color: #4F46E5; margin-bottom: 1rem; }
  .steps-list li { font-size: 1.3rem; color: #444; padding: .4rem 0; list-style: none; display: flex; align-items: flex-start; gap: .8rem; }
  .steps-list li i { color: #4F46E5; margin-top: .2rem; flex-shrink: 0; }
</style>
</head>
<body>
<div class="card">

  <!-- Logo -->
  <div class="logo"><i class="fas fa-hospital-alt"></i> RMU Medical Sickbay</div>

  <!-- Status Icon -->
  <div class="status-bubble">
    <i class="fas <?= $status_icon ?>"></i>
  </div>

  <h1><?= $is_rejected ? 'Account Rejected' : 'Pending Approval' ?></h1>

  <div class="badge">
    <i class="fas <?= $status_icon ?>"></i>
    <?= $status_label ?>
  </div>

  <!-- User Info -->
  <div class="info-grid">
    <div class="info-box"><span>Name</span><strong><?= $name ?></strong></div>
    <div class="info-box"><span>Role Applied For</span><strong><?= $role_label ?></strong></div>
  </div>

  <?php if ($is_rejected): ?>
  <!-- Rejection Reason -->
  <div class="rejection-box">
    <strong><i class="fas fa-exclamation-triangle"></i> Reason for Rejection</strong>
    <p><?= $reason ?: 'No specific reason given. Please contact the administration office for details.' ?></p>
  </div>
  <p class="desc">Your account application has been declined. Please contact the Human Resources or Administration department for further assistance.</p>

  <?php else: ?>
  <!-- Pending info -->
  <p class="desc">
    Your account has been created successfully and is currently under review.
    An administrator will approve your account shortly.
  </p>

  <!-- What Happens Next -->
  <div class="steps-list">
    <h4><i class="fas fa-list-ol"></i> What happens next?</h4>
    <ul>
      <li><i class="fas fa-check-circle"></i> Admin reviews your registration details</li>
      <li><i class="fas fa-check-circle"></i> Your role and department are verified</li>
      <li><i class="fas fa-check-circle"></i> You receive notification when approved</li>
      <li><i class="fas fa-check-circle"></i> Log back in to access your dashboard</li>
    </ul>
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div>
    <?php if (!$is_rejected): ?>
    <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/RMU-Medical-Management-System/php/dashboards/staff_dashboard.php', ENT_QUOTES) ?>" class="btn btn-primary btn btn-check"><span class="btn-text">
      <i class="fas fa-sync-alt"></i> Check Again
    </span></a>
    <?php endif; ?>
    <a href="/RMU-Medical-Management-System/php/logout.php" class="btn btn-primary btn btn-logout"><span class="btn-text">
      <i class="fas fa-sign-out-alt"></i> Logout
    </span></a>
  </div>

</div>


</body>
</html>
