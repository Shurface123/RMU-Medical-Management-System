<?php
// ============================================================
// MIGRATION: Phase 5 Admin Settings (reCAPTCHA)
// ============================================================
require_once dirname(__DIR__) . '/db_conn.php';

$log    = [];
$errors = [];

function run_sql($conn, $label, $sql) {
    global $log, $errors;
    if (@mysqli_query($conn, $sql)) {
        $log[]    = "✅ $label";
    } else {
        $errors[] = "❌ $label — " . mysqli_error($conn);
    }
}

// 1. Insert reCAPTCHA config keys into system_config
$recaptcha_keys = [
    'recaptcha_site_key'        => '6Lc01sYqAAAAAA5E5v1B_0L2aN9d2oM_0-E45l6k',
    'recaptcha_secret_key'      => '6Lc01sYqAAAAAHk0K8OpxX4O86N07V0q4K9lY3-X',
    'recaptcha_score_threshold' => '0.5'
];

foreach ($recaptcha_keys as $k => $v) {
    // Check if exists
    $ch = mysqli_query($conn, "SELECT id FROM system_config WHERE config_key = '$k'");
    if (mysqli_num_rows($ch) == 0) {
        $v_esc = mysqli_real_escape_string($conn, $v);
        run_sql($conn, "Insert $k", "INSERT INTO system_config (config_key, config_value, created_at) VALUES ('$k', '$v_esc', NOW())");
    } else {
        $log[] = "⏭ $k already exists. Skipped.";
    }
}

// Output Report
$total  = count($log) + count($errors);
$passed = count($log);
$failed = count($errors);
?>
<div style="font-family:sans-serif; padding:2rem; background:#f4f8ff; border-radius:12px;">
    <h2>Migration Report: Phase 5 Settings</h2>
    <?php if (!empty($errors)): ?>
        <h3 style="color:#d32f2f">Errors:</h3>
        <ul><?php foreach($errors as $e) echo "<li>$e</li>"; ?></ul>
    <?php endif; ?>
    <h3 style="color:#2e7d32">Log:</h3>
    <ul><?php foreach($log as $l) echo "<li>$l</li>"; ?></ul>
    <p><b>Passed:</b> <?= $passed ?> | <b>Failed:</b> <?= $failed ?></p>
</div>
