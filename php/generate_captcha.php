<?php
session_start();
require_once 'classes/CaptchaManager.php';

$captcha = new CaptchaManager();
$captcha->generateCaptcha();
?>
