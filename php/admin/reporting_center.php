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

<main class="adm-main">
    <div class="adm-topbar">
        <div class="adm-topbar-left">
            <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <span class="adm-page-title"><i class="fas fa-file-invoice"></i> BI & Reporting Center</span>
        </div>
        <div class="adm-topbar-right">
            <div class="adm-topbar-datetime">
                <i class="far fa-calendar-alt"></i>
                <span><?php echo date('D, M d, Y'); ?></span>
            </div>
            <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
            <div class="adm-avatar"><?php echo strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1)); ?></div>
        </div>
    </div>

    <div class="adm-content">
        <div class="adm-page-header">
            <div class="adm-page-header-left">
                <h1>Decision Intelligence</h1>
                <p>Generate high-fidelity medical reports, audit logs, and institutional performance metrics.</p>
            </div>
            <div style="display:flex; gap:1rem;">
                <button class="btn btn-outline" style="background:var(--surface); border:1px solid var(--border);"><span class="btn-text">
                    <i class="fas fa-history"></i> Report History
                </span></button>
                <button class="btn-icon btn btn-primary" onclick="window.print()"><span class="btn-text">
                    <i class="fas fa-print"></i> Print View
                </span></button>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: 340px 1fr; gap:2.5rem;">
            <!-- Report Builder Controls -->
            <div style="display:flex; flex-direction:column; gap:2.5rem;">
                <div class="adm-card shadow-sm" style="border-radius:20px; height:fit-content;">
                    <div class="adm-card-header" style="padding: 1.8rem 2rem;">
                        <h3><i class="fas fa-sliders-h"></i> Report Builder</h3>
                    </div>
                    <div class="adm-card-body" style="padding: 2rem;">
                        <form id="generateForm">
                            <div style="margin-bottom:1.8rem;">
                                <label style="display:block; font-size:0.85rem; font-weight:600; color:var(--text-secondary); margin-bottom:0.6rem;">Data Domain</label>
                                <select class="adm-form-input" id="reportCategory" style="width:100%; height:48px; border-radius:12px; font-weight:500;">
                                    <option value="patient">Patient Reports</option>
                                    <option value="clinical">Clinical Reports</option>
                                    <option value="staff">Staff Reports</option>
                                    <option value="pharmacy">Pharmacy Reports</option>
                                    <option value="financial">Financial Reports</option>
                                    <option value="system">System Reports</option>
                                </select>
                            </div>

                            <div style="margin-bottom:1.8rem;">
                                <label style="display:block; font-size:0.85rem; font-weight:600; color:var(--text-secondary); margin-bottom:0.6rem;">Report Type</label>
                                <select class="adm-form-input" id="reportType" style="width:100%; height:48px; border-radius:12px; font-weight:500;" required>
                                    <!-- Populated via JS -->
                                </select>
                            </div>

                            <div style="margin-bottom:1.8rem;">
                                <label style="display:block; font-size:0.85rem; font-weight:600; color:var(--text-secondary); margin-bottom:0.6rem;">Time Horizon</label>
                                <div style="display:grid; grid-template-columns:1fr; gap:0.8rem;">
                                    <input type="date" class="adm-form-input" id="startDate" style="height:45px; border-radius:10px;" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                                    <input type="date" class="adm-form-input" id="endDate" style="height:45px; border-radius:10px;" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <button type="submit" id="genBtn" class="btn btn-primary" style="width:100%; justify-content:center; height:50px;"><span class="btn-text">
                                <i class="fas fa-sync-alt"></i> Generate Intelligence
                            </span></button>
                        </form>

                        <div style="margin-top:2.5rem; padding-top:2rem; border-top:1px solid var(--border);">
                            <h3 style="font-size:0.85rem; font-weight:600; color:var(--text-muted); margin-bottom:1.2rem; text-transform:uppercase; letter-spacing:1px;">Export Options</h3>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.8rem;">
                                <button id="exportCsv" class="btn btn-outline" style="justify-content:center; background:var(--bg); border:1px solid var(--border); color:var(--success);"><span class="btn-text"><i class="fas fa-file-csv"></i> CSV</span></button>
                                <button id="exportXlsx" class="btn btn-outline" style="justify-content:center; background:var(--bg); border:1px solid var(--border); color:var(--primary);"><span class="btn-text"><i class="fas fa-file-excel"></i> XLSX</span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Preview -->
            <div id="printArea">
                <div class="adm-card shadow-sm" style="border-radius:20px; min-height:800px; background:#fff; display:flex; flex-direction:column;">
                    <div class="adm-card-header no-print" style="background:var(--bg-surface); padding: 1.5rem 2.5rem; border-radius:20px 20px 0 0;">
                        <div style="display:flex; align-items:center; gap:1rem;">
                            <div style="width:12px; height:12px; border-radius:50%; background:#ff5f57;"></div>
                            <div style="width:12px; height:12px; border-radius:50%; background:#ffbd2e;"></div>
                            <div style="width:12px; height:12px; border-radius:50%; background:#28c940;"></div>
                            <span style="margin-left:1rem; font-size:0.85rem; color:var(--text-muted); font-weight:600;">INTEL_REPORT_PREVIEW.v4</span>
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 4rem; flex:1;">
                        <!-- Report Header (for Print) -->
                        <div style="text-align:center; padding-bottom:3rem; border-bottom:2px solid var(--text-primary); margin-bottom:3rem;">
                            <img src="/RMU-Medical-Management-System/<?php echo htmlspecialchars($hospitalLogo); ?>" alt="Logo" style="height:80px; margin: 0 auto 1.5rem;">
                            <h2 style="font-size:1.8rem; font-weight:800; color:var(--text-primary); margin:0; text-transform:uppercase; letter-spacing:1px;"><?php echo htmlspecialchars($hospitalName); ?></h2>
                            <h3 id="rptTitle" style="font-size:1.4rem; font-weight:600; color:var(--primary); margin:0.8rem 0 0 0;">INTELLIGENCE REPORT</h3>
                            <div id="rptMeta" style="color:var(--text-muted); font-size:0.9rem; margin-top:0.8rem; font-weight:500;">Please configure and generate to view real-time intelligence.</div>
                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.5rem; text-transform:uppercase; letter-spacing:1px;">AUTH_SIG: <?php echo strtoupper($adminName); ?></div>
                        </div>

                        <div id="previewContainer" style="overflow-x:auto;">
                            <div id="rptTable" style="width:100%;">
                                <div class="adm-empty-state" style="padding:10rem 2rem;">
                                    <i class="fas fa-file-waveform"></i>
                                    <h3>No Intelligence Report Active</h3>
                                    <p>Select a data domain and time horizon from the builder panel to generate a high-fidelity clinical report.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
@media print {
    body { background: white !important; }
    .adm-sidebar, .no-print, .adm-topbar, .adm-card-header, form, .adm-page-header, .adm-card:first-child { display: none !important; }
    .adm-main { margin: 0 !important; width: 100% !important; max-width: 100% !important; }
    .adm-content { padding: 0 !important; }
    #printArea { box-shadow: none !important; border: none !important; }
    .adm-card { border: none !important; box-shadow: none !important; }
    .adm-card-body { padding: 2rem 0 !important; }
    #rptTable { border: 1px solid #000 !important; }
    #rptTable th { background: #f0f0f0 !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
    #rptTable td { border-bottom: 1px solid #ddd !important; }
}
</style>

<script src="../admin/reporting_actions.js"></script>
<script>
    // UI Toggles
    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    const menuToggle = document.getElementById('menuToggle');

    if (menuToggle) {
        menuToggle.onclick = () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        };
    }
    if (overlay) {
        overlay.onclick = () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        };
    }

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    if (themeToggle) {
        themeToggle.onclick = () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'light';
            const target = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', target);
            localStorage.setItem('rmu_theme', target);
            if (themeIcon) themeIcon.className = target === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        };
    }
</script>
</body>
</html>
