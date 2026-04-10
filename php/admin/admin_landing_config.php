<?php
session_start();
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'landing_config';
$page_title  = 'Site Configuration';

// Process Post Requests
$message = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_config') {
        foreach ($_POST['config'] as $key => $val) {
            $stmt = mysqli_prepare($conn, "UPDATE landing_page_config SET setting_value=?, updated_at=NOW() WHERE setting_key=?");
            mysqli_stmt_bind_param($stmt, "ss", $val, $key);
            mysqli_stmt_execute($stmt);
        }
        $message = "General site configurations updated.";
    } elseif ($action === 'update_hero') {
        $stmt = mysqli_prepare($conn, "UPDATE landing_hero_content SET headline_text=?, subheadline_text=?, cta1_text=?, cta1_url=?, cta2_text=?, cta2_url=?, is_active=?, updated_at=NOW() WHERE content_id=1");
        $a = isset($_POST['is_active']) ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "ssssssi", $_POST['headline'], $_POST['subheadline'], $_POST['cta1_t'], $_POST['cta1_u'], $_POST['cta2_t'], $_POST['cta2_u'], $a);
        mysqli_stmt_execute($stmt);
        $message = "Hero configuration updated.";
    } elseif ($action === 'update_about') {
        $stmt = mysqli_prepare($conn, "UPDATE landing_about SET content_text=?, is_active=?, updated_at=NOW() WHERE about_id=?");
        $id = (int)$_POST['about_id'];
        $a = isset($_POST['is_active']) ? 1 : 0;
        mysqli_stmt_bind_param($stmt, "sii", $_POST['content_text'], $a, $id);
        mysqli_stmt_execute($stmt);
        $message = "About block updated.";
    } elseif ($action === 'add_faq') {
        $s = mysqli_prepare($conn, "INSERT INTO landing_faq (question, answer, category, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        mysqli_stmt_bind_param($s, "sss", $_POST['q'], $_POST['a'], $_POST['c']);
        mysqli_stmt_execute($s);
        $message = "FAQ added.";
    } elseif ($action === 'delete_faq') {
        $id = (int)$_POST['faq_id'];
        mysqli_query($conn, "DELETE FROM landing_faq WHERE faq_id=$id");
        $message = "FAQ deleted.";
    } elseif ($action === 'add_stat') {
        $s = mysqli_prepare($conn, "INSERT INTO landing_statistics (label, stat_value, icon_class, is_active) VALUES (?, ?, ?, 1)");
        mysqli_stmt_bind_param($s, "sss", $_POST['l'], $_POST['v'], $_POST['i']);
        mysqli_stmt_execute($s);
        $message = "Stat added.";
    } elseif ($action === 'delete_stat') {
        $id = (int)$_POST['stat_id'];
        mysqli_query($conn, "DELETE FROM landing_statistics WHERE stat_id=$id");
        $message = "Stat deleted.";
    }
}

// Fetch Data
$configs = [];
$q = mysqli_query($conn, "SELECT setting_key, setting_value FROM landing_page_config");
while($r = mysqli_fetch_assoc($q)) $configs[$r['setting_key']] = $r['setting_value'];

$hero = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM landing_hero_content WHERE content_id=1"));
$abouts = []; $qa = mysqli_query($conn, "SELECT * FROM landing_about ORDER BY display_order");
while($r = mysqli_fetch_assoc($qa)) $abouts[] = $r;

$stats = []; $qs = mysqli_query($conn, "SELECT * FROM landing_statistics");
while($r = mysqli_fetch_assoc($qs)) $stats[] = $r;

$faqs = []; $qf = mysqli_query($conn, "SELECT * FROM landing_faq ORDER BY faq_id DESC");
while($r = mysqli_fetch_assoc($qf)) $faqs[] = $r;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Site Configuration - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .cfg-tabs { display:flex; gap:1rem; border-bottom:1px solid var(--border); margin-bottom:2rem; }
        .cfg-tab { padding:1rem 1.5rem; font-weight:600; color:var(--text-muted); cursor:pointer; border-bottom:3px solid transparent; }
        .cfg-tab.active, .cfg-tab:hover { color:var(--primary); border-bottom-color:var(--primary); }
        .cfg-pane { display:none; }
        .cfg-pane.active { display:block; animation:fadeIn 0.3s; }
        .form-row { display:flex; gap:1.5rem; flex-wrap:wrap; margin-bottom:1rem; }
        .form-row > div { flex:1; min-width:250px; }
        .lbl { display:block; font-weight:600; margin-bottom:.5rem; font-size:.9rem; color:var(--text-secondary); }
        .inp { width:100%; padding:.8rem 1rem; border:1px solid var(--border); border-radius:8px; background:var(--bg); color:var(--text); font-family:inherit; }
        .inp:focus { outline:none; border-color:var(--primary); }
        .card-wrap { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; margin-bottom:1.5rem; }
    </style>
</head>
<body>
<?php include '../includes/_sidebar.php'; ?>
<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-globe" style="color:var(--primary);margin-right:10px;"></i> Landing Page Content Manager</span>
        </div>
    </div>
    
    <div class="adm-content">
        <?php if($message): ?>
            <div style="background:#10b98122; color:#10b981; padding:1rem; border-radius:8px; margin-bottom:1.5rem; font-weight:600;"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="cfg-tabs">
            <div class="cfg-tab active" onclick="switchTab('general')">General Settings</div>
            <div class="cfg-tab" onclick="switchTab('hero')">Hero Section</div>
            <div class="cfg-tab" onclick="switchTab('about')">About Blocks</div>
            <div class="cfg-tab" onclick="switchTab('stats')">Statistics Map</div>
            <div class="cfg-tab" onclick="switchTab('faq')">FAQs</div>
        </div>

        <!-- GENERAL -->
        <div class="cfg-pane active" id="pane-general">
            <form method="POST">
                <input type="hidden" name="action" value="update_config">
                <div class="card-wrap">
                    <h3>Contact Information</h3><br>
                    <div class="form-row">
                        <div><label class="lbl">Primary Phone</label><input type="text" class="inp" name="config[contact_phonePrimary]" value="<?= htmlspecialchars($configs['contact_phonePrimary']??'') ?>"></div>
                        <div><label class="lbl">Emergency Hotline (e.g. 153)</label><input type="text" class="inp" name="config[contact_emergencyHotline]" value="<?= htmlspecialchars($configs['contact_emergencyHotline']??'') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div><label class="lbl">Email Support</label><input type="text" class="inp" name="config[contact_emailSupport]" value="<?= htmlspecialchars($configs['contact_emailSupport']??'') ?>"></div>
                        <div><label class="lbl">Address</label><input type="text" class="inp" name="config[contact_address]" value="<?= htmlspecialchars($configs['contact_address']??'') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div><label class="lbl">Working Hours</label><input type="text" class="inp" name="config[contact_workingHours]" value="<?= htmlspecialchars($configs['contact_workingHours']??'') ?>"></div>
                    </div>
                </div>
                <div class="card-wrap">
                    <h3>Social Media Links</h3><br>
                    <div class="form-row">
                        <div><label class="lbl">Facebook</label><input type="url" class="inp" name="config[social_facebook]" value="<?= htmlspecialchars($configs['social_facebook']??'') ?>"></div>
                        <div><label class="lbl">Twitter/X</label><input type="url" class="inp" name="config[social_twitter]" value="<?= htmlspecialchars($configs['social_twitter']??'') ?>"></div>
                        <div><label class="lbl">Instagram</label><input type="url" class="inp" name="config[social_instagram]" value="<?= htmlspecialchars($configs['social_instagram']??'') ?>"></div>
                    </div>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Save General Configuration</button>
            </form>
        </div>

        <!-- HERO -->
        <div class="cfg-pane" id="pane-hero">
            <form method="POST">
                <input type="hidden" name="action" value="update_hero">
                <div class="card-wrap">
                    <h3>Hero Content Editor</h3><br>
                    <div class="form-row">
                        <div style="flex:auto; width:100%;"><label class="lbl">Main Headline</label><input type="text" class="inp" name="headline" value="<?= htmlspecialchars($hero['headline_text']) ?>"></div>
                    </div>
                    <div class="form-row">
                        <div style="flex:auto; width:100%;"><label class="lbl">Sub Headline</label><textarea class="inp" name="subheadline" rows="3"><?= htmlspecialchars($hero['subheadline_text']) ?></textarea></div>
                    </div>
                    <div class="form-row">
                        <div><label class="lbl">Primary Button Text</label><input type="text" class="inp" name="cta1_t" value="<?= htmlspecialchars($hero['cta1_text']) ?>"></div>
                        <div><label class="lbl">Primary Button URL</label><input type="text" class="inp" name="cta1_u" value="<?= htmlspecialchars($hero['cta1_url']) ?>"></div>
                    </div>
                    <div class="form-row">
                        <div><label class="lbl">Secondary Button Text</label><input type="text" class="inp" name="cta2_t" value="<?= htmlspecialchars($hero['cta2_text']) ?>"></div>
                        <div><label class="lbl">Secondary Button URL</label><input type="text" class="inp" name="cta2_u" value="<?= htmlspecialchars($hero['cta2_url']) ?>"></div>
                    </div>
                    <div class="form-row">
                        <div><label class="lbl"><input type="checkbox" name="is_active" <?= $hero['is_active']?'checked':'' ?>> Active on Public Site</label></div>
                    </div>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-save"></i> Save Hero Content</button>
            </form>
        </div>

        <!-- ABOUT -->
        <div class="cfg-pane" id="pane-about">
            <?php foreach($abouts as $a): ?>
            <form method="POST" class="card-wrap">
                <input type="hidden" name="action" value="update_about">
                <input type="hidden" name="about_id" value="<?= $a['about_id'] ?>">
                <h3>Section: <?= htmlspecialchars($a['section_name']) ?></h3><br>
                <div class="form-row">
                    <div style="width:100%; flex:auto;"><textarea name="content_text" class="inp" rows="5"><?= htmlspecialchars($a['content_text']) ?></textarea></div>
                </div>
                <label style="margin-bottom:1rem; display:block;"><input type="checkbox" name="is_active" <?= $a['is_active']?'checked':'' ?>> Visible</label>
                <button type="submit" class="adm-btn adm-btn-primary adm-btn-sm"><i class="fas fa-save"></i> Update Block</button>
            </form>
            <?php endforeach; ?>
        </div>

        <!-- STATS -->
        <div class="cfg-pane" id="pane-stats">
            <div class="card-wrap" style="background:var(--bg-secondary);">
                <h3>Add New Statistic Metric</h3><br>
                <form method="POST" class="form-row" style="align-items:flex-end;">
                    <input type="hidden" name="action" value="add_stat">
                    <div><label class="lbl">Label (e.g. Years Experience)</label><input type="text" name="l" class="inp" required></div>
                    <div><label class="lbl">Value (e.g. 50+)</label><input type="text" name="v" class="inp" required></div>
                    <div><label class="lbl">FontAwesome Icon (e.g. fas fa-star)</label><input type="text" name="i" class="inp" required></div>
                    <div><button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i></button></div>
                </form>
            </div>
            
            <table class="adm-table" style="width:100%; background:var(--surface); border-radius:12px;">
                <tr><th>Icon</th><th>Value</th><th>Label</th><th>Action</th></tr>
                <?php foreach($stats as $s): ?>
                <tr>
                    <td><i class="<?= htmlspecialchars($s['icon_class']) ?>"></i></td>
                    <td><b><?= htmlspecialchars($s['stat_value']) ?></b></td>
                    <td><?= htmlspecialchars($s['label']) ?></td>
                    <td>
                        <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_stat"><input type="hidden" name="stat_id" value="<?= $s['stat_id'] ?>"><button class="adm-btn adm-btn-ghost" style="color:var(--danger);"><i class="fas fa-trash"></i></button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- FAQ -->
        <div class="cfg-pane" id="pane-faq">
            <div class="card-wrap" style="background:var(--bg-secondary);">
                <h3>Add New FAQ</h3><br>
                <form method="POST">
                    <input type="hidden" name="action" value="add_faq">
                    <div class="form-row">
                        <div><label class="lbl">Category</label><input type="text" name="c" class="inp" value="General" required></div>
                    </div>
                    <div class="form-row">
                        <div style="width:100%;flex:auto;"><label class="lbl">Question</label><input type="text" name="q" class="inp" required></div>
                    </div>
                    <div class="form-row">
                        <div style="width:100%;flex:auto;"><label class="lbl">Answer</label><textarea name="a" class="inp" rows="3" required></textarea></div>
                    </div>
                    <button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-plus"></i> Add FAQ</button>
                </form>
            </div>
            
            <?php foreach($faqs as $f): ?>
            <div class="card-wrap">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <b>Q: <?= htmlspecialchars($f['question']) ?></b>
                        <p style="margin-top:5px; color:var(--text-muted);">A: <?= htmlspecialchars($f['answer']) ?></p>
                        <span style="font-size:0.8rem; background:var(--bg-secondary); padding:2px 8px; border-radius:4px;"><?= htmlspecialchars($f['category']) ?></span>
                    </div>
                    <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_faq"><input type="hidden" name="faq_id" value="<?= $f['faq_id'] ?>"><button class="adm-btn adm-btn-ghost" style="color:var(--danger);"><i class="fas fa-trash"></i></button></form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>
<script>
    function switchTab(t) {
        document.querySelectorAll('.cfg-pane').forEach(p=>p.classList.remove('active'));
        document.querySelectorAll('.cfg-tab').forEach(b=>b.classList.remove('active'));
        document.getElementById('pane-'+t).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>
</body>
</html>
