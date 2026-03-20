<?php
// ============================================================
// NURSE DASHBOARD - REPORTS & EXPORTS (MODULE 12)
// ============================================================
if (!isset($conn)) exit;
?>

<div class="tab-content" id="reports">

    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0 text-primary fw-bold"><i class="fas fa-file-export me-2"></i> Clinical Reports Hub</h4>
            <p class="text-muted mb-0">Generate, print, and export structured CSV/PDF reports for auditing.</p>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-lg-8">
            <div class="card" style="border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08);">
                <div class="card-header text-white text-center py-4" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); border-radius: 15px 15px 0 0;">
                    <h4 class="mb-0"><i class="fas fa-clipboard-check me-2"></i> Report Generator</h4>
                    <p class="text-white-50 mb-0 small mt-1">Select parameters below to extract your clinical data</p>
                </div>
                
                <div class="card-body p-5 bg-white" style="border-radius: 0 0 15px 15px;">
                    <form action="../nurse/process_reports.php" method="POST" target="_blank" id="reportForm">
                        <?= csrfField() ?>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark"><i class="fas fa-filter text-primary me-2"></i> Data Category (Required)</label>
                            <select class="form-select form-select-lg border-primary" name="report_type" required style="border-radius: 10px;">
                                <option value="">-- Select Report Type --</option>
                                <option value="vitals">Patient Vitals Flowsheet</option>
                                <option value="medications">Medication Administration Log (MAR)</option>
                                <option value="fluids">I&O / Fluid Balance Charts</option>
                                <option value="emergencies">Emergency Alert History</option>
                                <option value="tasks">Completed Clinical Tasks & Handovers</option>
                            </select>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="far fa-calendar-alt text-primary me-2"></i> Start Date</label>
                                <input type="date" class="form-control form-control-lg" name="start_date" value="<?= date('Y-m-d', strtotime('-7 days')) ?>" required style="border-radius: 10px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="far fa-calendar-check text-primary me-2"></i> End Date</label>
                                <input type="date" class="form-control form-control-lg" name="end_date" value="<?= date('Y-m-d') ?>" required style="border-radius: 10px;">
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3">
                            <button type="submit" name="export_format" value="csv" class="btn btn-success btn-lg rounded-pill px-5 shadow-sm fw-bold hover-lift">
                                <i class="fas fa-file-csv me-2"></i> Download CSV
                            </button>
                            <!-- In a full implementation, PDF would route to something like TCPDF/Dompdf or use window.print. 
                                 We will handle a "Print View" HTML response for PDF saving. -->
                            <button type="submit" name="export_format" value="print" class="btn btn-outline-primary btn-lg rounded-pill px-5 shadow-sm fw-bold hover-lift">
                                <i class="fas fa-print me-2"></i> Print View (PDF)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-warning mt-4 text-center rounded-pill shadow-sm" style="border: 1px dashed #ffc107;">
                <i class="fas fa-lock me-2"></i> <strong>Confidentiality Notice:</strong> Generated reports contain PHI (Protected Health Information). Ensure rigorous compliance with HIPAA / RMU privacy policies when distributing exported files.
            </div>

        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>
