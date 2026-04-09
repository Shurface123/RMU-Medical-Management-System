<?php
// TAB: FEE SCHEDULE — full CRUD management
$fee_categories = [];
$fcq = mysqli_query($conn, "SELECT * FROM fee_categories WHERE is_active=1 ORDER BY category_name");
if ($fcq) while ($r = mysqli_fetch_assoc($fcq)) $fee_categories[] = $r;

$fees = [];
$fq = mysqli_query($conn,
    "SELECT fs.*, fc.category_name
     FROM fee_schedule fs
     LEFT JOIN fee_categories fc ON fs.category_id = fc.category_id
     WHERE fs.is_active = 1
     ORDER BY fc.category_name, fs.service_name");
if ($fq) while ($r = mysqli_fetch_assoc($fq)) $fees[] = $r;
?>
<div id="sec-fee_schedule" class="dash-section">
  <div class="adm-page-header">
    <div class="adm-page-header-left">
      <h1><i class="fas fa-list-ol" style="color:var(--role-accent);"></i> Fee Schedule</h1>
      <p>Manage service fees, tax rates and categories</p>
    </div>
    <?php if (in_array($user_role, ['finance_manager','admin'])): ?>
    <button onclick="openModal('modalAddFee')" class="adm-btn adm-btn-primary">
      <i class="fas fa-plus"></i> Add Service Fee
    </button>
    <?php endif; ?>
  </div>

  <!-- Search/Filter -->
  <div class="fin-filter-row" style="margin-bottom:1.5rem;">
    <div class="adm-search-wrap" style="flex:2;min-width:220px;">
      <i class="fas fa-search"></i>
      <input type="text" id="feeSearch" class="adm-search-input" placeholder="Search service name or code..."
        oninput="filterTable('feeSearch','feeTable')">
    </div>
    <select id="feeCatFilter" onchange="filterFeesByCategory()">
      <option value="">All Categories</option>
      <?php foreach ($fee_categories as $fc): ?>
      <option value="<?= htmlspecialchars($fc['category_name']) ?>"><?= htmlspecialchars($fc['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="feeTable">
        <thead><tr>
          <th>Service Name</th>
          <th>Code</th>
          <th>Category</th>
          <th>Base Amount (GHS)</th>
          <th>Student Rate (GHS)</th>
          <th>Tax Rate (%)</th>
          <th>Effective From</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($fees)): ?>
          <tr><td colspan="8" style="text-align:center;padding:4rem;color:var(--text-muted);">
            <i class="fas fa-list-ol" style="font-size:3rem;opacity:.3;display:block;margin-bottom:1rem;"></i>
            No fees configured yet. <?php if (in_array($user_role, ['finance_manager','admin'])): ?>
            <a href="#" onclick="openModal('modalAddFee')" style="color:var(--role-accent);">Add the first service fee</a>
            <?php endif; ?>
          </td></tr>
        <?php else: foreach ($fees as $f): ?>
          <tr data-category="<?= htmlspecialchars($f['category_name'] ?? '') ?>">
            <td><strong><?= htmlspecialchars($f['service_name']) ?></strong></td>
            <td><code style="background:var(--surface-2);padding:.2rem .5rem;border-radius:5px;font-size:1.1rem;"><?= htmlspecialchars($f['service_code'] ?? '—') ?></code></td>
            <td><?= htmlspecialchars($f['category_name'] ?? '—') ?></td>
            <td><strong>GHS <?= number_format($f['base_amount'], 2) ?></strong></td>
            <td style="color:var(--role-accent);"><?= $f['student_amount'] ? 'GHS '.number_format($f['student_amount'],2) : '<span style="color:var(--text-muted);">—</span>' ?></td>
            <td><?= $f['is_taxable'] ? '<span style="color:var(--warning);">'.$f['tax_rate_pct'].'%</span>' : '<span style="color:var(--text-muted);">None</span>' ?></td>
            <td><?= $f['effective_from'] ? date('d M Y', strtotime($f['effective_from'])) : '—' ?></td>
            <td>
              <div class="adm-table-actions">
                <?php if (in_array($user_role, ['finance_manager','admin'])): ?>
                <button onclick="editFee(<?= htmlspecialchars(json_encode($f)) ?>)" class="adm-btn adm-btn-sm adm-btn-ghost" title="Edit"><i class="fas fa-pen"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div><!-- /sec-fee_schedule -->

<!-- ══ ADD/EDIT FEE MODAL ═══════════════════════════════ -->
<div class="adm-modal" id="modalAddFee">
  <div class="adm-modal-content" style="max-width:640px;">
    <div class="adm-modal-header">
      <h3><i class="fas fa-list-ol" style="color:var(--role-accent);"></i> <span id="feeModalTitle">Add Service Fee</span></h3>
      <button class="adm-modal-close" onclick="closeModal('modalAddFee')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="adm-modal-body">
      <form id="formFee">
        <input type="hidden" id="feeId" value="0">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.2rem;">
          <div class="adm-form-group" style="grid-column:1/-1;">
            <label>Service Name *</label>
            <input type="text" id="feeName" class="adm-search-input" placeholder="e.g. Consultation Fee" required>
          </div>
          <div class="adm-form-group">
            <label>Service Code</label>
            <input type="text" id="feeCode" class="adm-search-input" placeholder="e.g. CONS-001">
          </div>
          <div class="adm-form-group">
            <label>Category</label>
            <select id="feeCatId" class="adm-search-input">
              <option value="">— None —</option>
              <?php foreach ($fee_categories as $fc): ?>
              <option value="<?= $fc['category_id'] ?>"><?= htmlspecialchars($fc['category_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="adm-form-group">
            <label>Base Amount (GHS) *</label>
            <input type="number" id="feeBase" class="adm-search-input" step="0.01" min="0" placeholder="0.00" required>
          </div>
          <div class="adm-form-group">
            <label>Student Rate (GHS) <small style="color:var(--text-muted);">(optional)</small></label>
            <input type="number" id="feeStudent" class="adm-search-input" step="0.01" min="0" placeholder="Same as base if blank">
          </div>
          <div class="adm-form-group">
            <label>Tax Rate (%)</label>
            <input type="number" id="feeTax" class="adm-search-input" step="0.01" min="0" max="100" value="0">
          </div>
          <div class="adm-form-group" style="display:flex;align-items:center;gap:.8rem;padding-top:1.5rem;">
            <input type="checkbox" id="feeTaxable" style="width:18px;height:18px;accent-color:var(--role-accent);">
            <label for="feeTaxable" style="margin:0;cursor:pointer;">Taxable Service</label>
          </div>
          <div class="adm-form-group">
            <label>Effective From</label>
            <input type="date" id="feeEffective" class="adm-search-input" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </form>
    </div>
    <div class="adm-modal-footer">
      <button onclick="closeModal('modalAddFee')" class="adm-btn adm-btn-ghost">Cancel</button>
      <button onclick="saveFee()" class="adm-btn adm-btn-primary"><i class="fas fa-floppy-disk"></i> Save Fee</button>
    </div>
  </div>
</div>

<script>
function filterFeesByCategory() {
  const val = document.getElementById('feeCatFilter').value.toLowerCase();
  document.querySelectorAll('#feeTable tbody tr').forEach(r => {
    const cat = (r.dataset.category || '').toLowerCase();
    r.style.display = (!val || cat === val) ? '' : 'none';
  });
}

function editFee(f) {
  document.getElementById('feeModalTitle').textContent = 'Edit Service Fee';
  document.getElementById('feeId').value      = f.fee_id;
  document.getElementById('feeName').value    = f.service_name;
  document.getElementById('feeCode').value    = f.service_code || '';
  document.getElementById('feeCatId').value   = f.category_id || '';
  document.getElementById('feeBase').value    = f.base_amount;
  document.getElementById('feeStudent').value = f.student_amount || '';
  document.getElementById('feeTax').value     = f.tax_rate_pct || 0;
  document.getElementById('feeTaxable').checked = f.is_taxable == 1;
  document.getElementById('feeEffective').value = f.effective_from || '';
  openModal('modalAddFee');
}

async function saveFee() {
  const name = document.getElementById('feeName').value.trim();
  if (!name) { toast('Service name is required.', 'danger'); return; }
  const base = parseFloat(document.getElementById('feeBase').value);
  if (isNaN(base) || base < 0) { toast('Enter a valid base amount.', 'danger'); return; }

  const btn = document.querySelector('#modalAddFee .adm-btn-primary');
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; btn.disabled = true;

  const res = await finAction({
    action:         'save_fee',
    fee_id:         document.getElementById('feeId').value,
    service_name:   name,
    service_code:   document.getElementById('feeCode').value,
    category_id:    document.getElementById('feeCatId').value,
    base_amount:    base,
    student_amount: document.getElementById('feeStudent').value || null,
    tax_rate_pct:   document.getElementById('feeTax').value,
    is_taxable:     document.getElementById('feeTaxable').checked ? 1 : 0,
    effective_from: document.getElementById('feeEffective').value,
  });

  btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save Fee'; btn.disabled = false;

  if (res.success) {
    toast('Fee saved successfully!', 'success');
    closeModal('modalAddFee');
    setTimeout(() => location.reload(), 1200);
  } else {
    toast(res.message || 'Failed to save fee.', 'danger');
  }
}
// Reset modal on open for new fee
document.querySelector('[onclick="openModal(\'modalAddFee\')"]')?.addEventListener('click', () => {
  document.getElementById('feeModalTitle').textContent = 'Add Service Fee';
  document.getElementById('feeId').value = '0';
  document.getElementById('formFee').reset();
});
</script>
