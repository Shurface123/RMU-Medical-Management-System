<!-- ════════════════════════════════════════════════════════════
     MODULE 3: PRESCRIPTION MANAGEMENT  (REVAMPED)
     ════════════════════════════════════════════════════════════ -->
<div id="sec-prescriptions" class="dash-section <?=($active_tab==='prescriptions')?'active':''?>">

<style>
.rx-table-wrap { overflow-x:auto; }
.rx-adv-table { width:100%;border-collapse:collapse;font-size:1.25rem;min-width:900px; }
.rx-adv-table thead tr { background:var(--surface-2);position:sticky;top:0;z-index:2; }
.rx-adv-table th { padding:1.2rem 1.4rem;text-align:left;font-size:1.1rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap; }
.rx-adv-table td { padding:1.1rem 1.4rem;border-bottom:1px solid var(--border);vertical-align:middle; }
.rx-adv-table tr:last-child td { border-bottom:none; }
.rx-adv-table tr:hover td { background:var(--surface-2); }
.rx-patient-cell { display:flex;align-items:center;gap:.9rem; }
.rx-patient-avatar {
  width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;font-weight:700;color:#fff;flex-shrink:0;
  background:linear-gradient(135deg,#08a88a,#27AE60);
}
.rx-stock-ok   { color:var(--success);font-weight:700; }
.rx-stock-low  { color:var(--danger);font-weight:700; }
.status-pill {
  display:inline-flex;align-items:center;gap:.4rem;padding:.3rem 1rem;
  border-radius:20px;font-size:1.05rem;font-weight:600;white-space:nowrap;
}
.sp-pending     { background:rgba(243,156,18,.15);color:#c17f10; }
.sp-dispensed   { background:rgba(39,174,96,.15);color:#1e8a4c; }
.sp-partial     { background:rgba(47,128,237,.15);color:#1561c0; }
.sp-cancelled   { background:rgba(231,76,60,.15);color:#c0392b; }
.sp-expired     { background:rgba(127,140,141,.15);color:#566573; }
.rx-code { font-family:monospace;font-size:1.1rem;font-weight:700;background:var(--surface-2);padding:.2rem .7rem;border-radius:6px; }
/* Dispense Modal */
.dispense-modal-grid { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem; }
@media(max-width:600px){ .dispense-modal-grid{grid-template-columns:1fr;} }
.dm-info-block { background:var(--surface-2);border-radius:12px;padding:1.2rem 1.4rem; }
.dm-info-block .dm-lbl { font-size:1rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:.4rem; }
.dm-info-block .dm-val { font-size:1.3rem;font-weight:600;color:var(--text-primary); }
.stock-bar-container { margin:1.2rem 0; }
.stock-bar-track { width:100%;height:8px;background:var(--border);border-radius:4px;overflow:hidden; }
.stock-bar-fill  { height:100%;border-radius:4px;transition:width .4s; }
.stock-insufficient-warn {
  background:rgba(231,76,60,.08);border-left:4px solid var(--danger);
  border-radius:0 10px 10px 0;padding:1rem 1.4rem;margin-bottom:1.2rem;
  color:var(--danger);font-size:1.2rem;font-weight:500;
}
/* Filter pill nav */
.rx-filter-nav { display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.6rem;padding:.2rem; }
.rx-fnav-btn {
  padding:.5rem 1.4rem;border-radius:20px;border:1px solid var(--border);background:var(--surface);
  font-size:1.15rem;font-weight:500;color:var(--text-secondary);cursor:pointer;transition:all .2s;
}
.rx-fnav-btn.active,
.rx-fnav-btn:hover { background:var(--role-accent);color:#fff;border-color:var(--role-accent); }
.rx-count-badge { background:rgba(255,255,255,.25);border-radius:20px;padding:.05rem .55rem;font-size:.9rem;margin-left:.3rem; }
</style>

  <div class="sec-header">
    <h2><i class="fas fa-prescription-bottle-medical"></i> Prescriptions</h2>
    <div style="display:flex;gap:.8rem;align-items:center;flex-wrap:wrap;">
      <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="rxSearch" placeholder="Search by patient, doctor, medicine…" oninput="filterTable('rxSearch','rxAdvTable')"></div>
    </div>
  </div>

  <nav class="rx-filter-nav" id="rxFilterNav">
    <button class="rx-fnav-btn active" onclick="rxFilter('all',this)">All</button>
    <button class="rx-fnav-btn" onclick="rxFilter('Pending',this)">Pending <span class="rx-count-badge"><?=$stats['pending_rx']?></span></button>
    <button class="rx-fnav-btn" onclick="rxFilter('Dispensed',this)">Dispensed</button>
    <button class="rx-fnav-btn" onclick="rxFilter('Partially Dispensed',this)">Partial</button>
    <button class="rx-fnav-btn" onclick="rxFilter('Cancelled',this)">Cancelled</button>
    <button class="rx-fnav-btn" onclick="rxFilter('Expired',this)">Expired</button>
  </nav>

  <div class="adm-card" style="padding:0;overflow:hidden;">
    <div class="rx-table-wrap">
      <table class="rx-adv-table" id="rxAdvTable">
        <thead><tr>
          <th>Rx ID</th><th>Patient</th><th>Doctor</th>
          <th>Medicine</th><th>Dosage</th><th>Qty</th>
          <th>Date</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php if(empty($all_rx)):?>
          <tr><td colspan="9" style="text-align:center;padding:4rem;color:var(--text-muted);"><i class="fas fa-prescription" style="font-size:3rem;display:block;margin-bottom:1rem;opacity:.3;"></i>No prescriptions found</td></tr>
        <?php else: foreach($all_rx as $rx):
          $scMap=['Pending'=>'sp-pending','Dispensed'=>'sp-dispensed','Partially Dispensed'=>'sp-partial','Cancelled'=>'sp-cancelled','Expired'=>'sp-expired'];
          $sc=$scMap[$rx['status']]??'sp-pending';
          $scIcon=['Pending'=>'fa-clock','Dispensed'=>'fa-check-circle','Partially Dispensed'=>'fa-adjust','Cancelled'=>'fa-times-circle','Expired'=>'fa-calendar-xmark'];
          $icon=$scIcon[$rx['status']]??'fa-circle';
          $medEsc=mysqli_real_escape_string($conn,$rx['medication_name']);
          $medStk=mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_quantity FROM medicines WHERE medicine_name='$medEsc' LIMIT 1"));
          $inStock=($medStk['stock_quantity']??0)>=($rx['quantity']??1);
          $initials=strtoupper(substr($rx['patient_name'],0,1));
        ?>
        <tr data-status="<?=htmlspecialchars($rx['status'])?>" data-id="<?=$rx['id']?>">
          <td data-label="Rx ID"><span class="rx-code"><?=htmlspecialchars($rx['prescription_id']??'#'.$rx['id'])?></span></td>
          <td data-label="Patient">
            <div class="rx-patient-cell">
              <div class="rx-patient-avatar" style="box-shadow:0 2px 5px rgba(0,0,0,.15);"><?=$initials?></div>
              <div>
                <div style="font-weight:600;color:var(--text-primary);"><?=htmlspecialchars($rx['patient_name'])?></div>
                <div style="font-size:1.05rem;color:var(--text-muted);"><?=htmlspecialchars($rx['p_ref']??'')?></div>
              </div>
            </div>
          </td>
          <td data-label="Doctor">
            <div style="display:flex;align-items:center;gap:.6rem;">
               <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2980b9,#56ccf2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;">
                  <i class="fas fa-user-md" style="font-size:0.8rem;"></i>
               </div>
               <span style="color:var(--text-secondary);">Dr. <?=htmlspecialchars($rx['doctor_name']??'')?></span>
            </div>
          </td>
          <td data-label="Medicine">
            <strong style="color:var(--text-primary);"><i class="fas fa-pills" style="color:var(--role-accent);margin-right:4px;"></i> <?=htmlspecialchars($rx['medication_name'])?></strong>
            <?php if(!$inStock && $rx['status']==='Pending'):?>
            <br><span style="background:rgba(231,76,60,.1);color:var(--danger);font-size:.95rem;font-weight:600;padding:.1rem .6rem;border-radius:10px;display:inline-block;margin-top:.2rem;">⚠ Low/No Stock</span>
            <?php endif;?>
          </td>
          <td data-label="Dosage" style="color:var(--text-secondary);"><?=htmlspecialchars($rx['dosage']??'').' · '.htmlspecialchars($rx['frequency']??'')?></td>
          <td data-label="Qty" style="font-weight:800;font-size:1.5rem;color:var(--text-primary);"><?=$rx['quantity']??1?></td>
          <td data-label="Date" style="white-space:nowrap;color:var(--text-muted);"><?=date('d M Y',strtotime($rx['prescription_date']))?></td>
          <td data-label="Status"><span class="status-pill <?=$sc?>"><i class="fas <?=$icon?>"></i><?=htmlspecialchars($rx['status'])?></span></td>
          <td data-label="Actions">
            <div class="action-btns">
              <?php if($rx['status']==='Pending'||$rx['status']==='Partially Dispensed'):?>
              <button class="btn btn-success btn-sm" onclick="openDispenseModal(<?=$rx['id']?>)" style="border-radius:20px;padding:0.3rem 0.8rem;transition:all 0.2s;box-shadow:0 2px 4px rgba(39,174,96,0.2);"><span class="btn-text"><i class="fas fa-check"></i> Dispense</span></button>
              <?php endif;?>
              <button class="btn btn-primary btn-sm" onclick="viewRxDetail(<?=$rx['id']?>)" title="View Details"><span class="btn-text"><i class="fas fa-eye"></i></span></button>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ══ Dispense Modal ══ -->
<div class="modal-bg glass-panel" id="modalDispense">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Dispense Prescription</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalDispense')"><span class="btn-text">&times;</span></button>
    </div>
    <div id="dispenseContent"><p style="text-align:center;color:var(--text-muted);padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:1rem;"></i>Loading prescription details…</p></div>
  </div>
</div>

<!-- ══ Rx Detail Modal ══ -->
<div class="modal-bg glass-panel" id="modalRxDetail">
  <div class="modal-box wide">
    <div class="modal-header">
      <h3><i class="fas fa-prescription" style="color:var(--primary);"></i> Prescription Details</h3>
      <button class="btn btn-primary modal-close" onclick="closeModal('modalRxDetail')"><span class="btn-text">&times;</span></button>
    </div>
    <div id="rxDetailContent"><p style="text-align:center;color:var(--text-muted);padding:3rem;">Loading…</p></div>
  </div>
</div>

<script>
// ── Filter prescriptions by status ──────────────────────────
function rxFilter(status, btn){
  document.querySelectorAll('#rxFilterNav .rx-fnav-btn').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('#rxAdvTable tbody tr').forEach(row=>{
    row.style.display=(status==='all'||row.dataset.status===status)?'':'none';
  });
}

// ── Open Dispense Modal (FIXED: uses addEventListener after injection) ──
async function openDispenseModal(rxId){
  openModal('modalDispense');
  document.getElementById('dispenseContent').innerHTML=`<p style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:1rem;color:var(--role-accent);"></i>Loading...</p>`;
  const r = await pharmAction({action:'get_prescription', id:rxId});
  if(!r.success){
    document.getElementById('dispenseContent').innerHTML=`<div style="text-align:center;padding:2rem;"><i class="fas fa-exclamation-circle" style="font-size:3rem;color:var(--danger);display:block;margin-bottom:1rem;"></i><p style="color:var(--danger);font-size:1.3rem;">${r.message}</p></div>`;
    return;
  }
  const rx = r.prescription;
  const stockPct = Math.min(100, Math.round((rx.stock_available / Math.max(rx.quantity,1)) * 100));
  const stockColor = rx.stock_available >= rx.quantity ? 'var(--success)' : 'var(--danger)';
  const defaultQty = Math.min(rx.quantity, rx.stock_available);

  let warnHtml = '';
  if(rx.stock_available < rx.quantity){
    warnHtml = `<div class="stock-insufficient-warn"><i class="fas fa-exclamation-triangle"></i> Only <strong>${rx.stock_available}</strong> units in stock (prescription needs ${rx.quantity}). You can do a partial dispense.</div>`;
  }

  document.getElementById('dispenseContent').innerHTML = `
    ${warnHtml}
    <div class="dispense-modal-grid">
      <div class="dm-info-block"><div class="dm-lbl">Patient</div><div class="dm-val">${rx.patient_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Prescribing Doctor</div><div class="dm-val">Dr. ${rx.doctor_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Medicine</div><div class="dm-val">${rx.medication_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Dosage & Frequency</div><div class="dm-val">${rx.dosage||'—'} · ${rx.frequency||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Prescribed Qty</div><div class="dm-val" style="font-size:2rem;font-weight:800;">${rx.quantity}</div></div>
      <div class="dm-info-block">
        <div class="dm-lbl">In Stock</div>
        <div class="dm-val" style="color:${stockColor};font-size:2rem;font-weight:800;">${rx.stock_available}</div>
        <div class="stock-bar-container">
          <div class="stock-bar-track"><div class="stock-bar-fill" style="width:${stockPct}%;background:${stockColor};"></div></div>
        </div>
      </div>
    </div>
    ${rx.instructions?`<div class="dm-info-block" style="margin-bottom:1.5rem;"><div class="dm-lbl">Instructions</div><div class="dm-val" style="font-weight:400;">${rx.instructions}</div></div>`:''}
    <div id="dispenseFormContainer">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
        <div class="form-group">
          <label>Quantity to Dispense</label>
          <input type="number" class="form-control" id="disp_qty" value="${defaultQty}" max="${rx.stock_available}" min="0" required>
        </div>
        <div class="form-group">
          <label>Payment Status</label>
          <select class="form-control" id="disp_payment">
            <option value="paid">✅ Paid</option>
            <option value="unpaid">⏳ Unpaid</option>
            <option value="insurance">🏥 Insurance</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notes (optional)</label>
          <input type="text" class="form-control" id="disp_notes" placeholder="e.g. patient advised…">
        </div>
      </div>
      <button id="confirmDispenseBtn" class="btn btn-success" style="width:100%;justify-content:center;padding:1.2rem;font-size:1.3rem;" 
        onclick="submitDispense(${rx.id}, ${rx.stock_available})">
        <span class="btn-text"><i class="fas fa-check-circle"></i> Confirm Dispense</span>
      </button>
    </div>`;
}

// ── Submit Dispense (event-listener based, NOT inline onsubmit) ──
async function submitDispense(rxId, stockAvailable){
  const qty = parseInt(document.getElementById('disp_qty').value);
  const payStatus = document.getElementById('disp_payment').value;
  const notes = document.getElementById('disp_notes').value;
  if(!qty || qty < 1){ toast('Enter a valid quantity','danger'); return; }
  if(qty > stockAvailable){ toast('Quantity exceeds stock available','danger'); return; }
  const btn = document.getElementById('confirmDispenseBtn');
  btn.disabled=true;
  btn.innerHTML='<span class="btn-text"><i class="fas fa-spinner fa-spin"></i> Processing…</span>';
  const r = await pharmAction({action:'dispense_prescription', prescription_id:rxId, qty:qty, payment_status:payStatus, notes:notes});
  if(r.success){
    toast(r.message||'Dispensed successfully');
    closeModal('modalDispense');
    setTimeout(()=>location.reload(),900);
  } else {
    toast(r.message||'Dispense failed','danger');
    btn.disabled=false;
    btn.innerHTML='<span class="btn-text"><i class="fas fa-check-circle"></i> Confirm Dispense</span>';
  }
}

// ── View Rx Detail ───────────────────────────────────────────
async function viewRxDetail(rxId){
  openModal('modalRxDetail');
  document.getElementById('rxDetailContent').innerHTML='<p style="text-align:center;padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;display:block;margin-bottom:1rem;color:var(--role-accent);"></i>Loading...</p>';
  const r = await pharmAction({action:'get_prescription', id:rxId});
  if(!r.success){ document.getElementById('rxDetailContent').innerHTML=`<p style="color:var(--danger);text-align:center;padding:2rem;">${r.message}</p>`; return; }
  const rx = r.prescription;
  const scMap={Pending:'sp-pending',Dispensed:'sp-dispensed','Partially Dispensed':'sp-partial',Cancelled:'sp-cancelled',Expired:'sp-expired'};
  const sc=scMap[rx.status]||'sp-pending';
  document.getElementById('rxDetailContent').innerHTML=`
    <div class="dispense-modal-grid">
      <div class="dm-info-block"><div class="dm-lbl">Rx ID</div><div class="dm-val"><span class="rx-code">${rx.prescription_id||'#'+rx.id}</span></div></div>
      <div class="dm-info-block"><div class="dm-lbl">Status</div><div class="dm-val"><span class="status-pill ${sc}">${rx.status}</span></div></div>
      <div class="dm-info-block"><div class="dm-lbl">Patient</div><div class="dm-val">${rx.patient_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Doctor</div><div class="dm-val">Dr. ${rx.doctor_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Medicine</div><div class="dm-val">${rx.medication_name}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Quantity</div><div class="dm-val" style="font-size:2rem;font-weight:800;">${rx.quantity}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Dosage</div><div class="dm-val">${rx.dosage||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Frequency</div><div class="dm-val">${rx.frequency||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Duration</div><div class="dm-val">${rx.duration||'—'}</div></div>
      <div class="dm-info-block"><div class="dm-lbl">Date Prescribed</div><div class="dm-val">${rx.prescription_date}</div></div>
    </div>
    ${rx.instructions?`<div class="dm-info-block" style="margin-top:1rem;"><div class="dm-lbl">Instructions</div><div class="dm-val" style="font-weight:400;">${rx.instructions}</div></div>`:''}
    ${(rx.status==='Pending'||rx.status==='Partially Dispensed')?`<button class="btn btn-success" style="width:100%;margin-top:1.5rem;justify-content:center;" onclick="closeModal('modalRxDetail');openDispenseModal(${rx.id})"><span class="btn-text"><i class="fas fa-check"></i> Dispense Now</span></button>`:''}`;
}
</script>
