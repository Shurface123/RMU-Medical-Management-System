<!-- ════════════════════════════════════════════════════════════
     MODULE 6: DISPENSING HISTORY & RECORDS
     ════════════════════════════════════════════════════════════ -->
<div id="sec-dispensing" class="dash-section <?=($active_tab==='dispensing')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-hand-holding-medical"></i> Dispensing History</h2>
    <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="dispSearch" placeholder="Search records…" oninput="filterTable('dispSearch','dispTable')"></div>
  </div>

  <div class="filter-tabs">
    <button class="ftab active" onclick="filterDispPay('all',this)">All</button>
    <button class="ftab" onclick="filterDispPay('paid',this)">Paid</button>
    <button class="ftab" onclick="filterDispPay('unpaid',this)">Unpaid</button>
    <button class="ftab" onclick="filterDispPay('insurance',this)">Insurance</button>
  </div>

  <div class="adm-card">
    <div class="adm-table-wrap">
      <table class="adm-table" id="dispTable">
        <thead><tr><th>ID</th><th>Patient</th><th>Doctor</th><th>Rx ID</th><th>Medicine</th><th>Qty</th><th>Date</th><th>Payment</th><th>Dispensed By</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if(empty($dispensing_records)):?>
          <tr><td colspan="10" style="text-align:center;padding:3rem;color:var(--text-muted);">No dispensing records</td></tr>
        <?php else: foreach($dispensing_records as $dr):
          $payMap=['paid'=>'success','unpaid'=>'danger','insurance'=>'info'];
          $paySc=$payMap[$dr['payment_status']]??'primary';
        ?>
        <tr data-payment="<?=$dr['payment_status']?>">
          <td><code>#<?=$dr['id']?></code></td>
          <td><strong><?=htmlspecialchars($dr['patient_name'])?></strong></td>
          <td><?=$dr['doctor_name']?'Dr. '.htmlspecialchars($dr['doctor_name']):'—'?></td>
          <td><code><?=htmlspecialchars($dr['rx_ref']??'—')?></code></td>
          <td><?=htmlspecialchars($dr['medicine_name'])?></td>
          <td style="font-weight:600;"><?=$dr['quantity_dispensed']?></td>
          <td><?=date('d M Y, g:i A',strtotime($dr['dispensing_date']))?></td>
          <td><span class="adm-badge adm-badge-<?=$paySc?>"><?=ucfirst($dr['payment_status'])?></span></td>
          <td><?=htmlspecialchars($dr['pharmacist_name']??'—')?></td>
          <td>
            <div class="action-btns">
              <?php if($dr['payment_status']==='unpaid'):?>
              <button class="adm-btn adm-btn-success adm-btn-sm" onclick="markPaid(<?=$dr['id']?>)" title="Mark Paid"><i class="fas fa-check"></i></button>
              <?php endif;?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif;?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function filterDispPay(status,btn){
  document.querySelectorAll('.filter-tabs .ftab').forEach(b=>b.classList.remove('active'));
  if(btn) btn.classList.add('active');
  document.querySelectorAll('#dispTable tbody tr').forEach(row=>{
    row.style.display=(status==='all'||row.dataset.payment===status)?'':'none';
  });
}
async function markPaid(id){
  if(!confirm('Mark this record as paid?')) return;
  const r=await pharmAction({action:'mark_paid',dispensing_id:id});
  if(r.success){toast('Marked as paid');setTimeout(()=>location.reload(),800);}
  else toast(r.message||'Error','danger');
}
</script>
