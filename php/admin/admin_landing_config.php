<?php
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

include '../includes/_sidebar.php';
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">

<style>
/* ── Premium Admin Variables ── */
:root {
  --primary: #2F80ED;
  --primary-light: color-mix(in srgb, var(--primary) 15%, transparent);
}

/* ── Hero Banner ── */
.staff-hero { display:flex;align-items:center;gap:2rem;padding:2rem 2.5rem;margin-bottom:2.5rem;
  background:linear-gradient(135deg, var(--primary), color-mix(in srgb, var(--primary) 60%, #000 40%));
  border-radius:var(--radius-lg);color:#fff;box-shadow:var(--shadow-md);flex-wrap:wrap; position:relative; overflow:hidden;}
.staff-hero-avatar { width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid rgba(255,255,255,.35);
  background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:2.6rem;flex-shrink:0; z-index:2;}
.staff-hero-info { z-index:2; }
.staff-hero-info h2 { font-size:2rem;font-weight:700;margin:0; }
.staff-hero-info p  { font-size:1.3rem;margin:.3rem 0 0;opacity:.85; }
.hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }

/* ── Cards ── */
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; margin-bottom:2.5rem; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; background:var(--surface-2); }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-row { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.6rem; }
@media(max-width:768px){.form-row{grid-template-columns:1fr;}}
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Filter Tabs ── */
.filter-tabs { display:flex;gap:.8rem;flex-wrap:wrap; margin-bottom: 2rem; border-bottom:1px solid var(--border); padding-bottom:1.5rem; }
.filter-tabs .ftab { padding:.8rem 1.8rem;border-radius:20px;font-size:1.15rem;font-weight:600;cursor:pointer;
  border:1.5px solid var(--border);background:var(--surface);color:var(--text-secondary);transition:var(--transition); }
.filter-tabs .ftab.active, .filter-tabs .ftab:hover { background:var(--primary);color:#fff;border-color:var(--primary); box-shadow: 0 4px 10px var(--primary-light); }
.cfg-pane { display:none; animation:fadeIn 0.3s; }
.cfg-pane.active { display:block; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }

/* ── Table ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; }
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; }
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-ghost { background:transparent; color:var(--text-secondary); }
.btn-ghost:hover { background:var(--surface-2); color:var(--text-primary); }

/* ── FAQ Block ── */
.faq-block { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:1.5rem; margin-bottom:1rem; display:flex; justify-content:space-between; align-items:flex-start; }
.faq-block h4 { font-size:1.3rem; margin:0 0 0.5rem 0; color:var(--text-primary); }
.faq-block p { font-size:1.1rem; color:var(--text-secondary); margin:0 0 0.8rem 0; }
.faq-badge { background:var(--surface-2); color:var(--text-muted); padding:0.3rem 0.8rem; border-radius:12px; font-size:0.9rem; font-weight:600; }

/* ── Toast ── */
#toastWrap { position:fixed;bottom:2.5rem;right:2.5rem;z-index:99999;display:flex;flex-direction:column;gap:.8rem; }
.toast-msg { padding:1.2rem 2rem; border-radius:var(--radius-sm); background:var(--surface); box-shadow:var(--shadow-lg); border-left:5px solid var(--primary); font-size:1.2rem; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:1rem; animation:fadePop .3s ease; }
@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-globe"></i> Site Configuration</span>
        </div>
        <div class="adm-topbar-right">
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>
    
    <div class="adm-content" style="animation:fadePop .35s ease;">
        <div class="staff-hero">
            <i class="fas fa-laptop-code hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-cogs"></i></div>
            <div class="staff-hero-info">
                <h2>Landing Page Content Manager</h2>
                <p>Configure public-facing website details, hero content, statistics, and FAQs.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <a href="../index.php" target="_blank" class="btn" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-external-link-alt"></i> View Public Site
                </a>
            </div>
        </div>

        <div class="filter-tabs">
            <button class="ftab active" onclick="switchTab('general', this)">General Settings</button>
            <button class="ftab" onclick="switchTab('hero', this)">Hero Section</button>
            <button class="ftab" onclick="switchTab('about', this)">About Blocks</button>
            <button class="ftab" onclick="switchTab('stats', this)">Statistics Map</button>
            <button class="ftab" onclick="switchTab('faq', this)">FAQs</button>
        </div>

        <!-- GENERAL -->
        <div class="cfg-pane active" id="pane-general">
            <form method="POST">
                <input type="hidden" name="action" value="update_config">
                
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-address-book" style="color:var(--primary);"></i> Contact Information</h3></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Primary Phone</label>
                                <input type="text" class="form-control" name="config[contact_phonePrimary]" value="<?= htmlspecialchars($configs['contact_phonePrimary']??'') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Emergency Hotline (e.g. 153)</label>
                                <input type="text" class="form-control" name="config[contact_emergencyHotline]" value="<?= htmlspecialchars($configs['contact_emergencyHotline']??'') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Email Support</label>
                                <input type="email" class="form-control" name="config[contact_emailSupport]" value="<?= htmlspecialchars($configs['contact_emailSupport']??'') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Address</label>
                                <input type="text" class="form-control" name="config[contact_address]" value="<?= htmlspecialchars($configs['contact_address']??'') ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Working Hours</label>
                            <input type="text" class="form-control" name="config[contact_workingHours]" value="<?= htmlspecialchars($configs['contact_workingHours']??'') ?>">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-hashtag" style="color:var(--primary);"></i> Social Media Links</h3></div>
                    <div class="card-body">
                        <div class="form-row" style="grid-template-columns:1fr 1fr 1fr;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Facebook</label>
                                <input type="url" class="form-control" name="config[social_facebook]" value="<?= htmlspecialchars($configs['social_facebook']??'') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Twitter/X</label>
                                <input type="url" class="form-control" name="config[social_twitter]" value="<?= htmlspecialchars($configs['social_twitter']??'') ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Instagram</label>
                                <input type="url" class="form-control" name="config[social_instagram]" value="<?= htmlspecialchars($configs['social_instagram']??'') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="margin-bottom:2rem;"><i class="fas fa-save"></i> Save General Configuration</button>
            </form>
        </div>

        <!-- HERO -->
        <div class="cfg-pane" id="pane-hero">
            <form method="POST">
                <input type="hidden" name="action" value="update_hero">
                <div class="card">
                    <div class="card-header"><h3><i class="fas fa-image" style="color:var(--primary);"></i> Hero Content Editor</h3></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Main Headline</label>
                            <input type="text" class="form-control" name="headline" value="<?= htmlspecialchars($hero['headline_text']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Sub Headline</label>
                            <textarea class="form-control" name="subheadline" rows="4"><?= htmlspecialchars($hero['subheadline_text']) ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Primary Button Text</label>
                                <input type="text" class="form-control" name="cta1_t" value="<?= htmlspecialchars($hero['cta1_text']) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Primary Button URL</label>
                                <input type="text" class="form-control" name="cta1_u" value="<?= htmlspecialchars($hero['cta1_url']) ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Secondary Button Text</label>
                                <input type="text" class="form-control" name="cta2_t" value="<?= htmlspecialchars($hero['cta2_text']) ?>">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label>Secondary Button URL</label>
                                <input type="text" class="form-control" name="cta2_u" value="<?= htmlspecialchars($hero['cta2_url']) ?>">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:0; display:flex; align-items:center; gap:1rem;">
                            <input type="checkbox" name="is_active" <?= $hero['is_active']?'checked':'' ?> style="width:20px; height:20px; accent-color:var(--primary);">
                            <label style="margin:0; text-transform:none; font-size:1.2rem; color:var(--text-primary);">Active on Public Site</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom:2rem;"><i class="fas fa-save"></i> Save Hero Content</button>
            </form>
        </div>

        <!-- ABOUT -->
        <div class="cfg-pane" id="pane-about">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:2.5rem;">
                <?php foreach($abouts as $a): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="update_about">
                    <input type="hidden" name="about_id" value="<?= $a['about_id'] ?>">
                    <div class="card" style="margin-bottom:0;">
                        <div class="card-header">
                            <h3>Section: <?= htmlspecialchars($a['section_name']) ?></h3>
                            <label style="display:flex; align-items:center; gap:0.5rem; margin:0; cursor:pointer;">
                                <input type="checkbox" name="is_active" <?= $a['is_active']?'checked':'' ?> style="width:16px;height:16px;accent-color:var(--primary);">
                                <span style="font-weight:600; color:var(--text-secondary);">Visible</span>
                            </label>
                        </div>
                        <div class="card-body">
                            <div class="form-group" style="margin-bottom:1.5rem;">
                                <textarea name="content_text" class="form-control" rows="6"><?= htmlspecialchars($a['content_text']) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary" style="width:100%;"><i class="fas fa-save"></i> Update Block</button>
                        </div>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STATS -->
        <div class="cfg-pane" id="pane-stats">
            <div class="card" style="background:var(--surface-2);">
                <div class="card-body">
                    <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.4rem; color:var(--text-primary);">Add New Statistic Metric</h3>
                    <form method="POST" class="form-row" style="align-items:flex-end; margin-bottom:0;">
                        <input type="hidden" name="action" value="add_stat">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Label (e.g. Years Experience)</label>
                            <input type="text" name="l" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Value (e.g. 50+)</label>
                            <input type="text" name="v" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>FontAwesome Icon (e.g. fas fa-star)</label>
                            <input type="text" name="i" class="form-control" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <button type="submit" class="btn btn-primary" style="height:54px; width:100%;"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <table class="stf-table">
                    <thead>
                        <tr>
                            <th style="width:80px; text-align:center;">Icon</th>
                            <th>Value</th>
                            <th>Label</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($stats as $s): ?>
                        <tr>
                            <td style="text-align:center; font-size:1.8rem; color:var(--primary);"><i class="<?= htmlspecialchars($s['icon_class']) ?>"></i></td>
                            <td><strong style="font-size:1.4rem; color:var(--text-primary);"><?= htmlspecialchars($s['stat_value']) ?></strong></td>
                            <td style="font-size:1.2rem;"><?= htmlspecialchars($s['label']) ?></td>
                            <td style="text-align:right;">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="delete_stat">
                                    <input type="hidden" name="stat_id" value="<?= $s['stat_id'] ?>">
                                    <button class="btn btn-ghost" style="color:var(--danger);"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FAQ -->
        <div class="cfg-pane" id="pane-faq">
            <div class="card" style="background:var(--surface-2);">
                <div class="card-body">
                    <h3 style="margin-top:0; margin-bottom:1.5rem; font-size:1.4rem; color:var(--text-primary);">Add New FAQ</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_faq">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="c" class="form-control" value="General" required>
                        </div>
                        <div class="form-group">
                            <label>Question</label>
                            <input type="text" name="q" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Answer</label>
                            <textarea name="a" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add FAQ</button>
                    </form>
                </div>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:1.5rem;">
                <?php foreach($faqs as $f): ?>
                <div class="faq-block">
                    <div style="flex:1;">
                        <h4><?= htmlspecialchars($f['question']) ?></h4>
                        <p><?= htmlspecialchars($f['answer']) ?></p>
                        <span class="faq-badge"><?= htmlspecialchars($f['category']) ?></span>
                    </div>
                    <form method="POST" style="margin:0; padding-left:1.5rem;">
                        <input type="hidden" name="action" value="delete_faq">
                        <input type="hidden" name="faq_id" value="<?= $f['faq_id'] ?>">
                        <button class="btn btn-ghost" style="color:var(--danger);" title="Delete FAQ"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</main>

<div id="toastWrap"></div>

<script>
    function showToast(msg, type='success') {
        const toast = document.createElement('div');
        toast.className = `toast-msg toast-${type}`;
        toast.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i> <span>${msg}</span>`;
        document.getElementById('toastWrap').appendChild(toast);
        setTimeout(()=> { toast.style.opacity='0'; setTimeout(()=>toast.remove(),300); }, 3000);
    }

    <?php if ($message): ?>
    document.addEventListener('DOMContentLoaded', () => { showToast(<?= json_encode($message) ?>, 'success'); });
    <?php endif; ?>

    function switchTab(t, btn) {
        document.querySelectorAll('.cfg-pane').forEach(p=>p.classList.remove('active'));
        document.querySelectorAll('.ftab').forEach(b=>b.classList.remove('active'));
        document.getElementById('pane-'+t).classList.add('active');
        btn.classList.add('active');
    }

    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
