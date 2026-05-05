<?php
require_once '../includes/auth_middleware.php';
enforceSingleDashboard('admin');
require_once '../db_conn.php';

$active_page = 'reporting_center';
$page_title = 'Reporting Center';
include '../includes/_sidebar.php';

$adminName = $_SESSION['name'] ?? 'Admin';
$facilityRes = mysqli_query($conn, "SELECT hospital_name, logo_path FROM hospital_settings LIMIT 1");
$hospital = mysqli_fetch_assoc($facilityRes);
$hospitalName = $hospital['hospital_name'] ?? 'RMU Medical Sickbay';
$hospitalLogo = $hospital['logo_path'] ?? 'image/logo-ju-small.png';
?>

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
.card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);overflow:hidden; height:100%; }
.card-header { padding:1.8rem 2rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between; }
.card-header h3 { font-size:1.4rem;font-weight:700;color:var(--text-primary);display:flex;align-items:center;gap:.9rem;margin:0; }
.card-body { padding:2rem; }

/* ── Form Controls ── */
.form-group { margin-bottom:1.6rem; }
.form-group label { display:block;font-size:1.1rem;font-weight:600;color:var(--text-secondary);margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.05em; }
.form-control { width:100%;padding:1rem 1.3rem;border:1.5px solid var(--border);border-radius:var(--radius-sm);
  background:var(--surface);color:var(--text-primary);font-family:'Poppins',sans-serif;font-size:1.15rem;
  transition:var(--transition);outline:none;box-sizing:border-box; }
.form-control:focus { border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light); }

/* ── Buttons ── */
.btn { display:inline-flex;align-items:center;gap:.6rem;padding:.9rem 1.8rem;border-radius:var(--radius-sm);
  font-family:'Poppins',sans-serif;font-size:1.2rem;font-weight:600;cursor:pointer;border:none;transition:var(--transition);text-decoration:none; justify-content:center;}
.btn-primary { background:var(--primary);color:#fff; }
.btn-primary:hover { opacity:.88;transform:translateY(-1px); }
.btn-outline { background:transparent;color:var(--primary);border:1.5px solid var(--primary); }
.btn-outline:hover { background:var(--primary-light); }
.btn-outline.success { color:var(--success); border-color:var(--success); }
.btn-outline.success:hover { background:var(--success-light); }
.btn-icon { padding:0.8rem; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; }

/* ── Print Preview ── */
.report-preview { background:#fff; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border); min-height:800px; display:flex; flex-direction:column; overflow:hidden;}
.preview-header { background:var(--surface-2); padding:1.5rem 2.5rem; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:1rem;}
.preview-dots { display:flex; gap:0.5rem; }
.preview-dot { width:12px; height:12px; border-radius:50%; }
.dot-red { background:#ff5f57; }
.dot-yellow { background:#ffbd2e; }
.dot-green { background:#28c940; }
.preview-body { padding:4rem; flex:1; background:#fff; }

/* ── Table Inside Preview ── */
.stf-table { width:100%;border-collapse:collapse;font-size:1.15rem; }
.stf-table th { background:var(--surface-2);color:var(--text-secondary);font-weight:600;
  text-transform:uppercase;font-size:1rem;letter-spacing:.04em;padding:1.2rem 1.6rem;text-align:left; border-bottom:2px solid var(--border);}
.stf-table td { padding:1.2rem 1.6rem;border-bottom:1px solid var(--border);color:var(--text-primary);vertical-align:middle; }
.stf-table tr:hover td { background:var(--surface-2); }

@media print {
    body { background: white !important; }
    .adm-sidebar, .no-print, .adm-topbar, .card:first-child, .staff-hero, .btn { display: none !important; }
    .adm-main { margin: 0 !important; width: 100% !important; max-width: 100% !important; padding:0 !important; }
    .adm-content { padding: 0 !important; }
    #printArea { box-shadow: none !important; border: none !important; margin:0 !important; padding:0 !important;}
    .report-preview { border: none !important; box-shadow: none !important; }
    .preview-header { display:none !important; }
    .preview-body { padding: 0 !important; }
    .stf-table th { background: #f0f0f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
    .stf-table td { border-bottom: 1px solid #ddd !important; }
}

@keyframes fadePop { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
</style>

<main class="adm-main">
    <div class="adm-topbar no-print">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-file-invoice"></i> BI & Reporting Center</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime" style="color:var(--text-secondary); font-weight:600; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem;">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><i class="fas fa-user"></i></div>
        </div>
    </div>

    <div class="adm-content" style="animation:fadePop .35s ease;">
        
        <div class="staff-hero no-print">
            <i class="fas fa-chart-bar hero-bg-icon"></i>
            <div class="staff-hero-avatar"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="staff-hero-info">
                <h2>Decision Intelligence</h2>
                <p>Generate high-fidelity medical reports, audit logs, and institutional performance metrics.</p>
            </div>
            <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                <button class="btn" style="background:rgba(255,255,255,0.2); color:#fff; border:1px solid rgba(255,255,255,0.3); backdrop-filter:blur(5px);">
                    <i class="fas fa-history"></i> Report History
                </button>
                <button class="btn btn-primary" onclick="window.print()" style="background:#fff; color:var(--primary);">
                    <i class="fas fa-print"></i> Print View
                </button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 350px 1fr; gap:2.5rem;" class="no-print-grid">
            <!-- Report Builder Controls -->
            <div class="no-print">
                <div class="card" style="height:fit-content;">
                    <div class="card-header">
                        <h3><i class="fas fa-sliders-h" style="color:var(--primary);"></i> Report Builder</h3>
                    </div>
                    <div class="card-body">
                        <form id="generateForm">
                            <div class="form-group">
                                <label>Data Domain</label>
                                <select class="form-control" id="reportCategory" required>
                                    <option value="patient">Patient Reports</option>
                                    <option value="clinical">Clinical Reports</option>
                                    <option value="staff">Staff Reports</option>
                                    <option value="pharmacy">Pharmacy Reports</option>
                                    <option value="financial">Financial Reports</option>
                                    <option value="system">System Reports</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Report Type</label>
                                <select class="form-control" id="reportType" required>
                                    <!-- Populated via JS -->
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Time Horizon</label>
                                <div style="display:flex; flex-direction:column; gap:0.8rem;">
                                    <input type="date" class="form-control" id="startDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                                    <input type="date" class="form-control" id="endDate" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <button type="submit" id="genBtn" class="btn btn-primary" style="width:100%; margin-top:1rem;">
                                <i class="fas fa-sync-alt"></i> Generate Intelligence
                            </button>
                        </form>

                        <div style="margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                            <label style="display:block;font-size:1rem;font-weight:600;color:var(--text-muted);margin-bottom:1.2rem;text-transform:uppercase;letter-spacing:1px;">Export Options</label>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                                <button id="exportCsv" class="btn btn-outline success" style="width:100%;">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                                <button id="exportXlsx" class="btn btn-outline" style="width:100%;">
                                    <i class="fas fa-file-excel"></i> XLSX
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Preview -->
            <div id="printArea" style="width:100%;">
                <div class="report-preview">
                    <div class="preview-header no-print">
                        <div class="preview-dots">
                            <div class="preview-dot dot-red"></div>
                            <div class="preview-dot dot-yellow"></div>
                            <div class="preview-dot dot-green"></div>
                        </div>
                        <span style="font-size:1rem; color:var(--text-muted); font-weight:600; font-family:monospace;">INTEL_REPORT_PREVIEW.v4</span>
                    </div>
                    <div class="preview-body">
                        <!-- Report Header (for Print) -->
                        <div style="text-align:center; padding-bottom:3rem; border-bottom:2px solid #000; margin-bottom:3rem;">
                            <img src="/RMU-Medical-Management-System/<?php echo htmlspecialchars($hospitalLogo); ?>" alt="Logo" style="height:80px; margin: 0 auto 1.5rem;">
                            <h2 style="font-size:2rem; font-weight:800; color:#000; margin:0; text-transform:uppercase; letter-spacing:1px;"><?php echo htmlspecialchars($hospitalName); ?></h2>
                            <h3 id="rptTitle" style="font-size:1.5rem; font-weight:600; color:#333; margin:0.8rem 0 0 0;">INTELLIGENCE REPORT</h3>
                            <div id="rptMeta" style="color:#666; font-size:1.1rem; margin-top:0.8rem; font-weight:500;">Please configure and generate to view real-time intelligence.</div>
                            <div style="font-size:0.9rem; color:#888; margin-top:0.8rem; text-transform:uppercase; letter-spacing:1px;">AUTH_SIG: <?php echo strtoupper($adminName); ?></div>
                        </div>

                        <div id="previewContainer" style="overflow-x:auto;">
                            <div id="rptTable" style="width:100%;">
                                <div style="text-align:center; padding:8rem 2rem; color:var(--text-muted);">
                                    <i class="fas fa-file-waveform" style="font-size:5rem; color:var(--border); margin-bottom:1.5rem;"></i>
                                    <h3 style="font-size:1.8rem; font-weight:700; color:var(--text-primary); margin-bottom:0.5rem;">No Intelligence Report Active</h3>
                                    <p style="font-size:1.2rem;">Select a data domain and time horizon from the builder panel to generate a high-fidelity clinical report.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="../admin/reporting_actions.js"></script>

<script>
    // Styling the dynamically injected table from reporting_actions.js
    // Override the table classes after they are generated
    const originalRender = window.renderTable; // Assuming there is a render function, or we observe mutations
    if (typeof originalRender !== 'undefined') {
        // Just adding a global style block for standard tables inside #rptTable is easier
    }
    
    // UI Toggles
    const themeIcon = document.getElementById('themeIcon');
    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const html = document.documentElement;
        const t = html.getAttribute('data-theme')==='dark'?'light':'dark';
        html.setAttribute('data-theme', t);
        localStorage.setItem('rmu_theme', t);
        if (themeIcon) themeIcon.className = t==='dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
<style>
    /* Force table inside previewContainer to use premium styles */
    #rptTable table { width:100%; border-collapse:collapse; font-size:1.15rem; color:var(--text-primary); }
    #rptTable th { background:var(--surface-2); color:var(--text-secondary); font-weight:600; text-transform:uppercase; font-size:1rem; letter-spacing:.04em; padding:1.2rem 1.6rem; text-align:left; border-bottom:2px solid var(--border); }
    #rptTable td { padding:1.2rem 1.6rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    #rptTable tr:hover td { background:var(--surface-2); }
</style>
<script src="/RMU-Medical-Management-System/assets/js/logout.js"></script>
</body>
</html>
