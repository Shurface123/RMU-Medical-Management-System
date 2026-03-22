<?php
// ============================================================
// DOCTOR DASHBOARD - TAB LAB REQUESTS
// ============================================================
if (!isset($doc_pk)) { exit; }

// Fetch Patient List
$pts_q = mysqli_query($conn, "SELECT id, name, p_ref FROM patients ORDER BY name ASC");
$pts = [];
if($pts_q) while($r=mysqli_fetch_assoc($pts_q)) $pts[]=$r;

// Fetch Lab Test Catalog
$cat_q = mysqli_query($conn, "SELECT * FROM lab_test_catalog WHERE is_active = 1 ORDER BY category, test_name");
$catalog = [];
if($cat_q) while($c=mysqli_fetch_assoc($cat_q)) $catalog[]=$c;

// Fetch Previous Lab Orders from this Doctor
$ord_q = mysqli_query($conn, "SELECT o.*, p.name AS patient_name, p.p_ref, c.test_name, c.category
                              FROM lab_test_orders o 
                              JOIN patients p ON o.patient_id = p.id
                              JOIN lab_test_catalog c ON o.test_catalog_id = c.id
                              WHERE o.doctor_id = $doc_pk
                              ORDER BY o.created_at DESC LIMIT 50");
$orders = [];
if($ord_q) while($o=mysqli_fetch_assoc($ord_q)) $orders[]=$o;
?>

<div id="sec-lab_requests" class="dash-section">
    <div class="sec-header">
        <h2><i class="fas fa-flask"></i> Lab Test Requests</h2>
        <button class="adm-btn adm-btn-primary" onclick="openModal('modalNewLabReq')"><i class="fas fa-plus"></i> Request New Test</button>
    </div>

    <!-- Active Orders Table -->
    <div class="adm-card">
        <h3 style="margin-bottom: 1rem; padding: 0 1rem;"><i class="fas fa-list"></i> Your Recent Lab Orders</h3>
        <div class="adm-table-wrap">
            <table class="adm-table" id="labOrdersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Patient</th>
                        <th>Test Requested</th>
                        <th>Date Ordered</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 2rem; color:var(--text-muted);">No lab test orders found.</td></tr>
                    <?php else: foreach($orders as $o): ?>
                        <tr>
                            <td><strong>ORD-<?= $o['id'] ?></strong></td>
                            <td><?= e($o['patient_name']) ?><br><small style="color:var(--text-muted);"><?= e($o['p_ref']) ?></small></td>
                            <td><?= e($o['test_name']) ?> <small>(<?= e($o['category']) ?>)</small></td>
                            <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
                            <td>
                                <?php if($o['priority'] === 'STAT'): ?>
                                    <span class="adm-badge adm-badge-danger">STAT</span>
                                <?php elseif($o['priority'] === 'Urgent'): ?>
                                    <span class="adm-badge adm-badge-warning">Urgent</span>
                                <?php else: ?>
                                    <span class="adm-badge" style="background:var(--surface-2);">Routine</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="adm-badge adm-badge-primary"><?= e($o['status']) ?></span></td>
                            <td>
                                <button class="adm-btn adm-btn-sm" style="background:var(--surface-2);color:var(--primary);" onclick="viewLabOrder(<?= $o['id'] ?>)"><i class="fas fa-eye"></i> Details</button>
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
  <div class="modal-box wide">
    <div class="modal-header">
        <h3><i class="fas fa-flask" style="color:var(--role-accent);"></i> Request Lab Test</h3>
        <button class="modal-close" onclick="closeModal('modalNewLabReq')">&times;</button>
    </div>
    <form id="formNewLabReq" onsubmit="submitLabRequest(event)">
        <div class="form-row">
            <div class="form-group">
                <label>Select Patient</label>
                <select name="patient_id" class="form-control" required>
                    <option value="">-- Choose Patient --</option>
                    <?php foreach($pts as $pt): ?>
                        <option value="<?= $pt['id'] ?>"><?= e($pt['name']) ?> (<?= e($pt['p_ref']) ?>)</option>
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

        <div class="form-group">
            <label>Select Lab Test</label>
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
                    <option value="<?= $c['id'] ?>"><?= e($c['test_name']) ?> (<?= e($c['sample_type_needed']) ?>)</option>
                <?php endforeach; if($cur_cat !== '') echo '</optgroup>'; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Clinical Notes / Reason</label>
            <textarea name="clinical_notes" class="form-control" rows="3" placeholder="Provide clinical context for the lab technician..."></textarea>
        </div>

        <button type="submit" class="adm-btn adm-btn-primary" style="width:100%; justify-content:center; margin-top:.5rem;"><i class="fas fa-paper-plane"></i> Send Request to Lab</button>
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
</script>
