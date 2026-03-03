<!-- ════════════════════════════════════════════════════════════
     MODULE 3: PRESCRIPTION MANAGEMENT
     ════════════════════════════════════════════════════════════ -->
<div id="sec-prescriptions" class="dash-section <?=($active_tab==='prescriptions')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-prescription-bottle-medical"></i> Prescriptions</h2>
    <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="rxSearch" placeholder="Search by patient, doctor, medicine…" oninput="filterTable('rxSearch','rxTable')"></div>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterByAttr('all','rxTable',this)">All</button>
    <button class="ftab" onclick="filterByAttr('Pending','rxTable',this)">Pending (<?=$stats['pending_rx']?>)</button>
    <button class="ftab" onclick="filterByAttr('Dispensed','rxTable',this)">Dispensed</button>
    <button class="ftab" onclick="filterByAttr('Partially Dispensed','rxTable',this)">Partially Dispensed</button>
    <button class="ftab" onclick="filterByAttr('Cancelled','rxTable',this)">Cancelled</button>
    <button class="ftab" onclick="filterByAttr('Expired','rxTable',this)">Expired</button>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="rxTable">
        <thead><tr><th>Rx ID</th><th>Patient</th><th>Doctor</th><th>Medicine</th><th>Dosage</th><th>Qty</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($all_rx)):?>
          <tr><td colspan="9" style="text-align:center;padding:3rem;color:var(--text-muted);">No prescriptions</td></tr>
        <?php else: foreach($all_rx as $rx):
          $scMap=['Pending'=>'warning','Dispensed'=>'success','Partially Dispensed'=>'info','Cancelled'=>'danger','Expired'=>'danger'];
          $sc=$scMap[$rx['status']]??'primary';
          // Stock check
          $medEsc=mysqli_real_escape_string($conn,$rx['medication_name']);
          $medStk=mysqli_fetch_assoc(mysqli_query($conn,"SELECT stock_quantity FROM medicines WHERE medicine_name='$medEsc' LIMIT 1"));
          $inStock=($medStk['stock_quantity']??0)>=($rx['quantity']??1);
        ?>
        <tr data-status="<?=htmlspecialchars($rx['status'])?>" data-id="<?=$rx['id']?>">
          <td><code style="font-weight:600;"><?=htmlspecialchars($rx['prescription_id']??'#'.$rx['id'])?></code></td>
          <td><strong><?=htmlspecialchars($rx['patient_name'])?></strong><br><span style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($rx['p_ref']??'')?></span></td>
          <td>Dr. <?=htmlspecialchars($rx['doctor_name']??'')?></td>
          <td>
            <strong><?=htmlspecialchars($rx['medication_name'])?></strong>
            <?php if(!$inStock && $rx['status']==='Pending'):?>
            <br><span class="adm-badge adm-badge-danger" style="font-size:.9rem;">Low/No Stock</span>
            <?php endif;?>
          </td>
          <td><?=htmlspecialchars($rx['dosage']??'')?> &middot; <?=htmlspecialchars($rx['frequency']??'')?></td>
          <td style="font-weight:600;"><?=$rx['quantity']??1?></td>
          <td><?=date('d M Y',strtotime($rx['prescription_date']))?></td>
          <td><span class="adm-badge adm-badge-<?=$sc?>"><?=htmlspecialchars($rx['status'])?></span></td>
          <td>
            <div class="action-btns">
              <?php if($rx['status']==='Pending'||$rx['status']==='Partially Dispensed'):?>
              <button class="adm-btn adm-btn-success adm-btn-sm" onclick="openDispenseModal(<?=$rx['id']?>)" title="Dispense"><i class="fas fa-check"></i> Dispense</button>
              <?php endif;?>
              <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="viewRxDetail(<?=$rx['id']?>)" title="View"><i class="fas fa-eye"></i></button>
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
<div class="modal-bg" id="modalDispense">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-check-circle" style="color:var(--success);"></i> Dispense Prescription</h3><button class="modal-close" onclick="closeModal('modalDispense')">&times;</button></div>
    <div id="dispenseContent"><p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading prescription details…</p></div>
  </div>
</div>

<!-- ══ Rx Detail Modal ══ -->
<div class="modal-bg" id="modalRxDetail">
  <div class="modal-box wide">
    <div class="modal-header"><h3><i class="fas fa-prescription" style="color:var(--primary);"></i> Prescription Details</h3><button class="modal-close" onclick="closeModal('modalRxDetail')">&times;</button></div>
    <div id="rxDetailContent"><p style="text-align:center;color:var(--text-muted);padding:2rem;">Loading…</p></div>
  </div>
</div>

<script>
async function openDispenseModal(rxId){
  openModal('modalDispense');
  const r=await pharmAction({action:'get_prescription',id:rxId});
  if(!r.success){document.getElementById('dispenseContent').innerHTML=`<p style="color:var(--danger);">${r.message}</p>`;return;}
  const rx=r.prescription;
  let stockWarn=rx.stock_available<rx.quantity?`<div style="background:var(--danger-light);border-left:3px solid var(--danger);padding:.8rem 1.2rem;border-radius:0 8px 8px 0;margin-bottom:1rem;color:var(--danger);font-size:1.2rem;"><i class="fas fa-exclamation-triangle"></i> Only <strong>${rx.stock_available}</strong> units in stock (need ${rx.quantity})</div>`:'';
  document.getElementById('dispenseContent').innerHTML=`
    ${stockWarn}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">PATIENT</strong><p style="font-weight:600;">${rx.patient_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DOCTOR</strong><p>Dr. ${rx.doctor_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">MEDICINE</strong><p style="font-weight:600;">${rx.medication_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DOSAGE</strong><p>${rx.dosage||''} · ${rx.frequency||''}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">PRESCRIBED QTY</strong><p>${rx.quantity}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">IN STOCK</strong><p style="color:${rx.stock_available>=rx.quantity?'var(--success)':'var(--danger)'};font-weight:700;">${rx.stock_available}</p></div>
    </div>
    ${rx.instructions?`<div style="margin-bottom:1.5rem;"><strong style="color:var(--text-muted);font-size:1.1rem;">INSTRUCTIONS</strong><p>${rx.instructions}</p></div>`:''}
    <form onsubmit="submitDispense(event,${rx.id})">
      <div class="form-row">
        <div class="form-group"><label>Quantity to Dispense</label><input type="number" class="form-control" name="qty" value="${Math.min(rx.quantity,rx.stock_available)}" max="${rx.stock_available}" min="0" required></div>
        <div class="form-group"><label>Payment Status</label>
          <select class="form-control" name="payment_status"><option value="paid">Paid</option><option value="unpaid">Unpaid</option><option value="insurance">Insurance</option></select>
        </div>
      </div>
      <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="2" placeholder="Optional notes…"></textarea></div>
      <button type="submit" class="adm-btn adm-btn-success" style="width:100%;justify-content:center;"><i class="fas fa-check"></i> Confirm Dispense</button>
    </form>`;
}

async function submitDispense(e,rxId){
  e.preventDefault();
  const fd=Object.fromEntries(new FormData(e.target));
  fd.action='dispense_prescription'; fd.prescription_id=rxId;
  const r=await pharmAction(fd);
  if(r.success){toast(r.message||'Dispensed');closeModal('modalDispense');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}

async function viewRxDetail(rxId){
  openModal('modalRxDetail');
  const r=await pharmAction({action:'get_prescription',id:rxId});
  if(!r.success){document.getElementById('rxDetailContent').innerHTML=`<p style="color:var(--danger);">${r.message}</p>`;return;}
  const rx=r.prescription;
  document.getElementById('rxDetailContent').innerHTML=`
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">RX ID</strong><p style="font-weight:600;">${rx.prescription_id||'#'+rx.id}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">STATUS</strong><p><span class="adm-badge adm-badge-${rx.status==='Dispensed'?'success':(rx.status==='Pending'?'warning':'danger')}">${rx.status}</span></p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">PATIENT</strong><p>${rx.patient_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DOCTOR</strong><p>Dr. ${rx.doctor_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">MEDICINE</strong><p style="font-weight:600;">${rx.medication_name}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">QUANTITY</strong><p>${rx.quantity}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DOSAGE</strong><p>${rx.dosage||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">FREQUENCY</strong><p>${rx.frequency||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DURATION</strong><p>${rx.duration||'—'}</p></div>
      <div><strong style="color:var(--text-muted);font-size:1.1rem;">DATE PRESCRIBED</strong><p>${rx.prescription_date}</p></div>
    </div>
    ${rx.instructions?`<div style="margin-bottom:1rem;"><strong style="color:var(--text-muted);font-size:1.1rem;">INSTRUCTIONS</strong><p>${rx.instructions}</p></div>`:''}`;
}
</script>
