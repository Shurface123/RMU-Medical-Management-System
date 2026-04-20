<?php
// ============================================================
// DOCTOR DASHBOARD - TAB LAB REQUESTS
// ============================================================
if (!isset($doc_pk)) { exit; }
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
}

// Fetch Patient List
$pts_q = mysqli_query($conn, "SELECT p.id, u.name AS full_name, p.patient_id FROM patients p JOIN users u ON p.user_id = u.id ORDER BY u.name ASC");
$pts = [];
if($pts_q) while($r=mysqli_fetch_assoc($pts_q)) $pts[]=$r;

// Fetch Lab Test Catalog
$cat_q = mysqli_query($conn, "SELECT * FROM lab_test_catalog WHERE is_active = 1 ORDER BY category, test_name");
$catalog = [];
if($cat_q) while($c=mysqli_fetch_assoc($cat_q)) $catalog[]=$c;

// Fetch Previous Lab Orders from this Doctor
$ord_q = mysqli_query($conn, "SELECT o.*, u.name AS patient_name, p.patient_id AS pat_ref, c.test_name AS catalog_test, c.category, t.name AS tech_name
                              FROM lab_test_orders o 
                              JOIN patients p ON o.patient_id = p.id
                              JOIN users u ON p.user_id = u.id
                              LEFT JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                              LEFT JOIN lab_technicians lt ON o.technician_id = lt.id
                              LEFT JOIN users t ON lt.user_id = t.id
                              WHERE o.doctor_id = $doc_pk
                              ORDER BY o.created_at DESC LIMIT 50");
$orders = [];
if($ord_q) while($o=mysqli_fetch_assoc($ord_q)) $orders[]=$o;

// Fetch Lab Technicians for Directed Assignment
$lt_q = mysqli_query($conn, "SELECT l.id, u.name AS full_name FROM lab_technicians l JOIN users u ON l.user_id = u.id");
$technicians = [];
if($lt_q) while($t=mysqli_fetch_assoc($lt_q)) $technicians[]=$t;
?>

<div id="sec-lab_requests" class="dash-section">

<style>
.premium-modal { border-radius:18px; border:1px solid rgba(255,255,255,0.1); }
</style>
    <div class="sec-header">
        <h2><i class="fas fa-flask" style="color:var(--primary);"></i> Lab Requests & Routing</h2>
        <button class="btn btn-primary" style="border-radius:12px;padding:.8rem 1.4rem;" onclick="openModal('modalNewLabReq')"><span class="btn-text"><i class="fas fa-plus"></i> Request New Test</span></button>
    </div>

    <!-- Active Orders Table -->
    <div class="adm-card shadow-sm" style="overflow:hidden;">
        <h3 style="margin: 0; padding: 1.8rem 2rem; border-bottom: 1px solid var(--border); background:var(--surface);"><i class="fas fa-list" style="color:var(--primary);margin-right:.5rem;"></i> Active Dispatched Orders</h3>
        <div class="adm-table-wrap">
            <table class="adm-table" id="labOrdersTable">
                <thead>
                    <tr style="background:linear-gradient(90deg, var(--surface-2), var(--surface));">
                        <th>Order ID</th>
                        <th>Patient</th>
                        <th>Test Requested</th>
                        <th>Assigned Tech</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date Ordered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="8" style="text-align:center; padding: 3rem; color:var(--text-muted);"><i class="fas fa-flask" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:1rem;"></i>No lab test orders found.</td></tr>
                    <?php else: foreach($orders as $o): 
                        $tname = $o['test_name'] ?? $o['catalog_test'] ?? 'Unknown Assay';
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($o['order_id'] ?? 'ORD-'.$o['id']) ?></code></td>
                            <td><strong><?= e($o['patient_name']) ?></strong><br><small style="color:var(--text-muted);"><?= e($o['pat_ref'] ?? '') ?></small></td>
                            <td><?= e($tname) ?> <br><small style="color:var(--text-muted);">(<?= e($o['category'] ?? 'General') ?>)</small></td>
                            <td>
                                <?php if($o['technician_id']): ?>
                                  <span style="font-weight:600;color:var(--primary);"><i class="fas fa-user-astronaut"></i> <?= e($o['tech_name'] ?? 'Tech') ?></span>
                                <?php else: ?>
                                  <span style="color:var(--text-muted);"><i class="fas fa-users"></i> Department Queue</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($o['urgency'] === 'STAT' || $o['urgency'] === 'Critical'): ?>
                                    <span class="adm-badge adm-badge-danger"><?= e($o['urgency']) ?></span>
                                <?php elseif($o['urgency'] === 'Urgent'): ?>
                                    <span class="adm-badge adm-badge-warning">Urgent</span>
                                <?php else: ?>
                                    <span class="adm-badge" style="background:var(--surface-2);color:var(--text-secondary);">Routine</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $s_sc=match($o['order_status']??''){'Completed'=>'success','Processing'=>'info','Cancelled'=>'danger',default=>'warning'};
                                ?>
                                <span class="adm-badge adm-badge-<?=$s_sc?>"><?= e($o['order_status'] ?? 'Pending') ?></span>
                            </td>
                            <td style="color:var(--text-muted);"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm" onclick="viewLabOrder(<?= $o['id'] ?>)"><span class="btn-text"><i class="fas fa-eye"></i> Track</span></button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Lab Request -->
<div class="modal-bg" id="modalNewLabReq">
  <div class="modal-box wide premium-modal">
    <div class="modal-header">
        <h3><i class="fas fa-flask" style="color:#fff;"></i> Request Lab Test</h3>
        <button class="modal-close" onclick="closeModal('modalNewLabReq')">&times;</button>
    </div>
    <form id="formNewLabReq" onsubmit="submitLabRequest(event)" style="padding:1rem;">
        <div class="form-row">
            <div class="form-group">
                <label>Select Patient</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">-- Choose Patient --</option>
                    <?php foreach($pts as $pt): ?>
                        <option value="<?= $pt['id'] ?>"><?= e($pt['full_name']) ?> (<?= e($pt['patient_id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Priority / Urgency</label>
                <select name="priority" class="form-control">
                    <option value="Routine">Routine</option>
                    <option value="Urgent">Urgent</option>
                    <option value="STAT">STAT (Immediate)</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Select Lab Test <span style="color:var(--danger);">*</span></label>
                <select name="test_catalog_id" class="form-control" required>
                    <option value="">-- Select Test from Catalog --</option>
                    <?php 
                    $cur_cat = '';
                    foreach($catalog as $c): 
                        if ($c['category'] !== $cur_cat) {
                            if ($cur_cat !== '') echo '</optgroup>';
                            echo '<optgroup label="'.e($c['category']).'">';
                            $cur_cat = $c['category'];
                        }
                    ?>
                        <option value="<?= $c['id'] ?>"><?= e($c['test_name']) ?> (<?= e($c['sample_type'] ?? '') ?>)</option>
                    <?php endforeach; if($cur_cat !== '') echo '</optgroup>'; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Target Technician (Optional)</label>
                <select name="technician_id" class="form-control">
                    <option value="">Any Available Technician</option>
                    <?php foreach($technicians as $lt): ?>
                        <option value="<?= $lt['id'] ?>"><?= e($lt['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Clinical Notes / Reason</label>
            <textarea name="clinical_notes" class="form-control" rows="3" placeholder="Provide clinical context for the lab technician..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; margin-top:.5rem;"><span class="btn-text"><i class="fas fa-paper-plane"></i> Send Request to Lab</span></button>
    </form>
  </div>
</div>

<script>
async function submitLabRequest(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = { action: 'request_lab_test' };
    fd.forEach((v, k) => data[k] = v);

    try {
        const res = await docAction(data); // docAction handles the POST to doctor_actions.php
        if (res.success) {
            toast('Lab test ordered successfully!');
            closeModal('modalNewLabReq');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast(res.error || 'Failed to submit lab request', 'danger');
        }
    } catch (err) {
        console.error(err);
        toast('Connection error', 'danger');
    }
}

function viewLabOrder(id) {
    alert("View tracking timeline and results for Lab Order " + id);
}

$(document).ready(function() {
    if($.fn.DataTable) {
        $('#labOrdersTable').DataTable({
            pageLength: 10,
            order: [[5, 'desc']],
            language: { search: "", searchPlaceholder: "Quick search lab orders..." }
        });
    }
});
</script>
