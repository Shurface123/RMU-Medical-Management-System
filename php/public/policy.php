<?php
$active_page = '';
$_base = '/RMU-Medical-Management-System';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy & Terms of Use — RMU Medical Sickbay</title>
    <!-- CSS & Fonts -->
    <link rel="icon" type="image/png" href="<?= $_base ?>/image/logo-ju-small.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $_base ?>/css/landing.css">
    <style>
        .policy-hero { padding: 9rem 2rem 5rem; text-align: center; background: linear-gradient(135deg, #2563EB, #0ea5e9); color: white; }
        .policy-hero h1 { font-size: clamp(2.5rem, 6vw, 4rem); font-weight: 800; margin-bottom: 1rem; }
        .policy-container { max-width: 800px; margin: -3rem auto 5rem; position: relative; z-index: 10; padding: 0 1rem; }
        .policy-card { background: var(--lp-bg-card); border-radius: 20px; padding: 4rem; box-shadow: 0 10px 40px rgba(0,0,0,0.08); border: 1px solid var(--lp-border); }
        .policy-card h2 { font-size: 1.8rem; font-weight: 700; color: var(--lp-text); margin-top: 2rem; margin-bottom: 1rem; }
        .policy-card h2:first-child { margin-top: 0; }
        .policy-card p { font-size: 1.1rem; color: var(--lp-text-muted); line-height: 1.8; margin-bottom: 1.5rem; }
        [data-theme="dark"] .policy-card { background: rgba(20, 30, 50, 0.9); border-color: rgba(255,255,255,0.05); }
    </style>
</head>
<body>
    <div id="lpAnnouncements"></div>
    <?php require_once __DIR__ . '/../includes/nav_landing.php'; ?>

    <section class="policy-hero">
        <div class="lp-container">
            <h1>Legal & Policies</h1>
            <p style="font-size: 1.2rem; opacity: 0.9;">Privacy Policy and Terms of Use for RMU Medical Sickbay</p>
        </div>
    </section>

    <div class="policy-container">
        <div class="policy-card">
            <h2>1. Introduction</h2>
            <p>Welcome to the RMU Medical Sickbay Management System ("System"), operated by Regional Maritime University (RMU) in Accra, Ghana. By registering and using this System, you agree to comply with and be bound by these Terms and Conditions.</p>
            
            <h2>2. Privacy Policy Updates</h2>
            <p>Regional Maritime University (RMU) is committed to protecting the privacy and confidentiality of all personal and medical data processed through the RMU Medical Sickbay Management System.</p>
            <p>Your data is collected exclusively for provision of medical services at RMU Sickbay, coordination of care between healthcare professionals, administrative and record-keeping requirements.</p>

            <h2>3. Acceptable Use</h2>
            <p>You agree to use the System solely for its intended medical management purposes. You must not share your login credentials with any other person or access patient records without clinical necessity.</p>
            
            <h2>4. Disclaimer</h2>
            <p>The RMU Medical Sickbay System is intended to supplement and support administrative and clinical processes. In cases of acute emergency, individuals are strongly advised to contact emergency services immediately or visit the nearest physical healthcare facility.</p>
            
            <h2>5. Governing Law</h2>
            <p>These Terms shall be governed by the laws of the Republic of Ghana. For queries regarding these Terms, contact the Sickbay Administration at <strong>sickbay@rmu.edu.gh</strong>.</p>
        </div>
    </div>

    <?php require_once __DIR__ . '/../includes/footer_landing.php'; ?>
    <script>
        const lpThemeToggle = document.getElementById('lpThemeToggle');
        if (lpThemeToggle) {
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', currentTheme);
            lpThemeToggle.querySelectorAll('.toggle-icon').forEach(i => i.classList.remove('active'));
            if (currentTheme === 'dark') {
                lpThemeToggle.querySelector('.icon-sun').classList.add('active');
            } else {
                lpThemeToggle.querySelector('.icon-moon').classList.add('active');
            }
            lpThemeToggle.addEventListener('click', () => {
                let theme = document.documentElement.getAttribute('data-theme');
                let newTheme = theme === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                lpThemeToggle.querySelectorAll('.toggle-icon').forEach(i => i.classList.toggle('active'));
            });
        }
    </script>
</body>
</html>
