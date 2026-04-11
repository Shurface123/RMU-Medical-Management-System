<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/db_conn.php';
require_once __DIR__ . '/login_router.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'patient';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_terms'])) {
    mysqli_query($conn, "UPDATE users SET accepted_terms=1 WHERE id=$uid");
    login_route($role);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Accept Privacy Policy — RMU Medical Sickbay</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background: #f4f8ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.card { background: #fff; width: 90%; max-width: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; }
.card-header { background: #2f80ed; color: #fff; padding: 20px; text-align: center; font-size: 1.2rem; font-weight: bold; }
.card-body { padding: 20px; overflow-y: auto; flex: 1; line-height: 1.6; color: #333; }
.card-footer { padding: 20px; border-top: 1px solid #eee; text-align: center; }
.btn { background: #2f80ed; color: #fff; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: bold; }
.btn:disabled { background: #ccc; cursor: not-allowed; }
</style>
</head>
<body>
<div class="card">
    <div class="card-header">Please Accept Our Updated Terms & Privacy Policy</div>
    <div class="card-body" id="policyBody" onscroll="checkScroll(this)">
        <h3>1. Introduction</h3>
        <p>Welcome to the RMU Medical Sickbay Management System ("System"), operated by Regional Maritime University (RMU) in Accra, Ghana. By registering and using this System, you agree to comply with and be bound by these Terms and Conditions.</p>
        
        <h3>2. Privacy Policy Updates</h3>
        <p>Regional Maritime University (RMU) is committed to protecting the privacy and confidentiality of all personal and medical data processed through the RMU Medical Sickbay Management System.</p>
        <p>Your data is collected exclusively for provision of medical services at RMU Sickbay, coordination of care between healthcare professionals, administrative and record-keeping requirements.</p>

        <h3>3. Acceptable Use</h3>
        <p>You agree to use the System solely for its intended medical management purposes. You must not share your login credentials with any other person or access patient records without clinical necessity.</p>
        
        <p><em>Please scroll to the bottom to enable the accept button.</em></p>
        <!-- padding element to force scroll -->
        <div style="height: 400px;"></div>
        <p>End of document.</p>
    </div>
    <div class="card-footer">
        <form method="POST">
            <input type="hidden" name="accept_terms" value="1">
            <button type="submit" class="btn btn-outline btn-icon btn" id="btnAccept" disabled><span class="btn-text">I have read and accept</span></button>
        </form>
    </div>
</div>
<script>
function checkScroll(el) {
    if (el.scrollHeight - el.scrollTop <= el.clientHeight + 20) {
        document.getElementById('btnAccept').disabled = false;
    }
}
setTimeout(() => checkScroll(document.getElementById('policyBody')), 200);
</script>
</body>
</html>
