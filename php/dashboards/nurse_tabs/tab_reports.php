<?php
// ============================================================
// NURSE DASHBOARD - REPORTS & EXPORTS (MODULE 11)
// ============================================================
if (!isset($conn)) exit;
?>

<div class="tab-content active" id="reports">

    <!-- Section Header -->
    <div class="sec-header">
        <div>
            <h2 style="font-size:2.4rem; font-weight:800; color:var(--primary); margin-bottom:.3rem;"><i class="fas fa-file-invoice pulse-fade" style="margin-right:.8rem;"></i> Clinical Reports Hub</h2>
            <p style="font-size:1.3rem; color:var(--text-muted);">Secure extraction of encrypted clinical data for administrative and legal documentation.</p>
        </div>
        <div style="display:flex; align-items:center; gap:1rem;">
             <span class="adm-badge" style="background:rgba(var(--info-rgb),0.1); color:var(--info); font-weight:800; border:1px solid rgba(var(--info-rgb),0.2); padding:.6rem 1.2rem; border-radius:10px; display:flex; align-items:center; gap:.8rem;">
                <i class="fas fa-shield-alt"></i> HIPAA COMPLIANT ACCESS
             </span>
        </div>
    </div>

    <!-- Main Generator Card -->
    <div class="adm-card shadow-sm" style="max-width:1000px; margin: 3rem auto; border:none; overflow:hidden;">
        <div class="adm-card-header" style="background:linear-gradient(135deg, var(--primary), var(--primary-dark)); color:#fff; padding:3rem 4rem; border:none;">
            <div style="display:flex; align-items:center; gap:2.5rem;">
                <div style="width:70px; height:70px; border-radius:18px; background:rgba(255,255,255,0.15); display:flex; align-items:center; justify-content:center; font-size:3rem; backdrop-filter:blur(5px);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h3 style="margin:0; font-weight:900; font-size:2.2rem; letter-spacing:-0.5px;">Clinical Data Engine</h3>
                    <p style="margin:0.5rem 0 0; opacity:0.9; font-size:1.3rem; font-weight:500;">Configure data extraction parameters for secure audit-ready reports.</p>
                </div>
            </div>
        </div>
        
        <div class="adm-card-body" style="padding:5rem 6rem;">
            <form action="../nurse/process_reports.php" method="POST" target="_blank" id="reportForm">
                <?= csrfField() ?>
                
                <div class="form-group" style="margin-bottom:4rem;">
                    <label style="font-weight:800; font-size:1.2rem; color:var(--text-secondary); margin-bottom:1.2rem; display:block; text-transform:uppercase; letter-spacing:0.05em;">Clinical Data Category</label>
                    <select class="form-control" name="report_type" required style="height:65px; font-size:1.5rem; font-weight:800; border:2px solid var(--border); border-radius:14px; padding:0 2rem; color:var(--primary);">
                        <option value="">-- select report subject --</option>
                        <option value="vitals">Physiologic Vitals Flowsheet</option>
                        <option value="medications">Medication Administration Record (MAR)</option>
                        <option value="fluids">Intake & Output / Fluid Balance</option>
                        <option value="emergencies">Code Blue / Emergency Event Audit</option>
                        <option value="tasks">Nurse Task Completion & Handovers</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:3rem; margin-bottom:5rem;">
                    <div class="form-group">
                        <label style="font-weight:700; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Extraction Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?= date('Y-m-d', strtotime('-7 days')) ?>" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.3rem; border:1.5px solid var(--border);">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:700; font-size:1.1rem; color:var(--text-secondary); margin-bottom:.8rem; display:block;">Extraction End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?= date('Y-m-d') ?>" required style="height:55px; border-radius:12px; font-weight:700; font-size:1.3rem; border:1.5px solid var(--border);">
                    </div>
                </div>

                <div style="display:flex; justify-content:center; gap:2.5rem; padding-top:4.5rem; border-top:1.5px solid var(--border);">
                    <button type="submit" name="export_format" value="csv" class="btn-icon btn btn-ghost" style="padding:1.4rem 4rem; border-radius:15px; font-weight:800; font-size:1.4rem; border-width:2px; display:flex; align-items:center; gap:1.2rem;"><span class="btn-text">
                        <i class="fas fa-file-csv" style="font-size:1.8rem; color:var(--success);"></i> Export to CSV
                    </span></button>
                    <button type="submit" name="export_format" value="print" class="btn-icon btn btn-primary" style="padding:1.4rem 6rem; border-radius:15px; font-weight:900; font-size:1.4rem; box-shadow:0 10px 25px rgba(var(--primary-rgb),0.3); display:flex; align-items:center; gap:1.2rem;"><span class="btn-text">
                        <i class="fas fa-print" style="font-size:1.8rem;"></i> GENERATE AUDIT VIEW
                    </span></button>
                </div>
            </form>
        </div>
        <div class="adm-card-footer" style="background:rgba(var(--primary-rgb),0.02); padding:2rem 6rem; font-size:1.2rem; color:var(--text-muted); display:flex; align-items:center; gap:1.5rem; border-top:1.5px solid var(--border);">
            <div style="width:10px; height:10px; border-radius:50%; background:var(--primary); animation:pulse 2s infinite;"></div>
            <span style="font-weight:600;">System is ready for real-time extraction based on current synchronized clinical data.</span>
        </div>
    </div>

    <!-- Security Panel -->
    <div style="max-width:1000px; margin: 4rem auto; padding:2.5rem; background:rgba(231,76,60,0.05); border:1.5px solid rgba(231,76,60,0.15); border-radius:20px; display:flex; gap:2rem; align-items:flex-start;">
        <div style="width:60px; height:60px; border-radius:14px; background:rgba(231,76,60,0.1); display:flex; align-items:center; justify-content:center; font-size:2.4rem; color:var(--danger); flex-shrink:0;">
             <i class="fas fa-user-shield"></i>
        </div>
        <div>
            <h4 style="margin:0; font-size:1.5rem; font-weight:900; color:var(--danger); text-transform:uppercase; letter-spacing:0.05em; margin-bottom:.8rem;">Data Protection Protocol (PHI)</h4>
            <p style="margin:0; font-size:1.3rem; line-height:1.6; color:var(--text-secondary); font-weight:500;">
                All extracted reports contain highly sensitive **Protected Health Information (PHI)**. Data extraction must be performed only for legitimate clinical or administrative purposes. Unauthorized distribution or storage in non-compliant environments is a violation of facility policy and federal health privacy laws.
            </p>
        </div>
    </div>

</div>
