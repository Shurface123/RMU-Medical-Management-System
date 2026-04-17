<!-- ════════════════════════════════════════════════════════════
     MODULE 6: DISPENSING HISTORY & RECORDS
     ════════════════════════════════════════════════════════════ -->
<div id="sec-dispensing" class="dash-section <?=($active_tab==='dispensing')?'active':''?>">

  <div class="sec-header">
    <h2><i class="fas fa-hand-holding-medical"></i> Dispensing History</h2>
    <div class="adm-search-wrap"><i class="fas fa-search"></i><input type="text" class="adm-search-input" id="dispSearch" placeholder="Search records…" oninput="filterTable('dispSearch','dispTable')"></div>
  </div>

  <div class="filter-tabs">
    <button class="btn btn-primary ftab active" onclick="filterDispPay('all',this)"><span class="btn-text">All</span></button>
    <button class="btn btn-primary ftab" onclick="filterDispPay('paid',this)"><span class="btn-text">Paid</span></button>
    <button class="btn btn-primary ftab" onclick="filterDispPay('unpaid',this)"><span class="btn-text">Unpaid</span></button>
    <button class="btn btn-primary ftab" onclick="filterDispPay('insurance',this)"><span class="btn-text">Insurance</span></button>
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
          <td data-label="ID"><code>#<?=$dr['id']?></code></td>
          <td data-label="Patient">
            <div style="display:flex;align-items:center;gap:.8rem;">
              <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#08a88a,#27ae60);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;box-shadow:0 2px 5px rgba(0,0,0,.15);">
                <?=strtoupper(substr($dr['patient_name']??'U',0,1))?>
              </div>
              <strong style="color:var(--text-primary);"><?=htmlspecialchars($dr['patient_name'])?></strong>
            </div>
          </td>
          <td data-label="Doctor">
            <div style="display:flex;align-items:center;gap:.6rem;">
               <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#2980b9,#56ccf2);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;">
                  <i class="fas fa-user-md" style="font-size:0.8rem;"></i>
               </div>
               <span><?=$dr['doctor_name']?'Dr. '.htmlspecialchars($dr['doctor_name']):'—'?></span>
            </div>
          </td>
          <td data-label="Rx ID"><code><?=htmlspecialchars($dr['rx_ref']??'—')?></code></td>
          <td data-label="Medicine"><span class="adm-badge adm-badge-teal" style="background:rgba(39,174,96,0.1);color:#27ae60;border:1px solid rgba(39,174,96,0.3);"><i class="fas fa-pills" style="margin-right:4px;"></i> <?=htmlspecialchars($dr['medicine_name'])?></span></td>
          <td data-label="Qty" style="font-weight:800;color:var(--text-primary);"><?=$dr['quantity_dispensed']?></td>
          <td data-label="Date"><span style="color:var(--text-muted);"><i class="far fa-clock" style="margin-right:4px;"></i> <?=date('d M y, g:i A',strtotime($dr['dispensing_date']))?></span></td>
          <td data-label="Payment"><span class="adm-badge adm-badge-<?=$paySc?>" style="box-shadow:0 2px 4px rgba(0,0,0,0.1);"><?=ucfirst($dr['payment_status'])?></span></td>
          <td data-label="Dispensed By"><span style="font-size:0.95em;color:var(--text-secondary);"><i class="fas fa-user-circle"></i> <?=htmlspecialchars($dr['pharmacist_name']??'—')?></span></td>
          <td data-label="Actions">
            <div class="action-btns">
              <?php if($dr['payment_status']==='unpaid'):?>
              <button class="btn btn-success btn-sm" onclick="markPaid(<?=$dr['id']?>)" style="border-radius:20px;padding:0.3rem 0.8rem;transition:all 0.2s;box-shadow:0 2px 4px rgba(39,174,96,0.2);"><span class="btn-text"><i class="fas fa-check-circle"></i> Paid</span></button>
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
