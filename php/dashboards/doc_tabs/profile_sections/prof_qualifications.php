<?php // SECTION D: Qualifications & Certifications ?>
<div id="prof-qualifications" class="prof-section" style="display:none;">
  <!-- Qualifications -->
  <div class="adm-card" style="margin-bottom:1.5rem;">
    <div class="adm-card-header"><h3><i class="fas fa-graduation-cap"></i> Qualifications</h3>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addQualForm').style.display='block'"><span class="btn-text"><i class="fas fa-plus"></i> Add</span></button>
    </div>
    <div style="padding:1.5rem;">
      <!-- Add form (hidden) -->
      <div id="addQualForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
        <h4 style="margin-bottom:1rem;">Add Qualification</h4>
        <form onsubmit="addQual(event)">
          <div class="form-row">
            <div class="form-group"><label>Degree / Certificate *</label><input type="text" name="degree_name" class="form-control" required placeholder="e.g. MBChB, FGCS, MD"></div>
            <div class="form-group"><label>Institution *</label><input type="text" name="institution" class="form-control" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Year Awarded</label><input type="number" name="year_awarded" class="form-control" min="1950" max="<?=date('Y')?>"></div>
            <div class="form-group"><label>Certificate File</label><input type="file" name="cert_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
          </div>
          <div style="display:flex;gap:.8rem;"><button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button><button type="button" class="btn btn-ghost btn" onclick="this.closest('div[id]').style.display='none'"><span class="btn-text">Cancel</span></button></div>
        </form>
      </div>
      <!-- List -->
      <div id="qualList">
      <?php if(empty($qualifications)):?><p style="color:var(--text-muted);text-align:center;padding:2rem;">No qualifications added yet.</p>
      <?php else: foreach($qualifications as $q):?>
        <div class="file-row" data-id="<?=$q['id']?>">
          <div class="file-icon-box" style="background:linear-gradient(135deg,var(--role-accent),#2F80ED);"><i class="fas fa-award"></i></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($q['degree_name'])?></div>
            <div style="font-size:1.15rem;color:var(--text-muted);"><?=htmlspecialchars($q['institution'])?> <?=$q['year_awarded']?' &middot; '.$q['year_awarded']:''?></div>
          </div>
          <?php if(!empty($q['cert_file_path'])):?><a href="/RMU-Medical-Management-System/<?=htmlspecialchars($q['cert_file_path'])?>" target="_blank" class="btn btn-outline btn-icon btn btn-sm" title="Download"><span class="btn-text"><i class="fas fa-download"></i></span></a><?php endif;?>
          <button class="btn btn-danger btn-sm" onclick="delQual(<?=$q['id']?>,this)" title="Delete"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
        </div>
      <?php endforeach; endif;?>
      </div>
    </div>
  </div>

  <!-- Certifications -->
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-certificate"></i> Professional Certifications</h3>
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('addCertForm').style.display='block'"><span class="btn-text"><i class="fas fa-plus"></i> Add</span></button>
    </div>
    <div style="padding:1.5rem;">
      <div id="addCertForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
        <h4 style="margin-bottom:1rem;">Add Certification</h4>
        <form onsubmit="addCert(event)">
          <div class="form-row">
            <div class="form-group"><label>Certification Name *</label><input type="text" name="cert_name" class="form-control" required></div>
            <div class="form-group"><label>Issuing Organization *</label><input type="text" name="issuing_org" class="form-control" required></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>Issue Date</label><input type="date" name="issue_date" class="form-control"></div>
            <div class="form-group"><label>Expiry Date</label><input type="date" name="expiry_date" class="form-control"></div>
          </div>
          <div class="form-group"><label>Certificate File</label><input type="file" name="cert_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
          <div style="display:flex;gap:.8rem;"><button type="submit" class="btn btn-primary"><span class="btn-text"><i class="fas fa-save"></i> Save</span></button><button type="button" class="btn btn-ghost btn" onclick="this.closest('div[id]').style.display='none'"><span class="btn-text">Cancel</span></button></div>
        </form>
      </div>
      <div id="certList">
      <?php if(empty($certifications)):?><p style="color:var(--text-muted);text-align:center;padding:2rem;">No certifications added yet.</p>
      <?php else: foreach($certifications as $c):
        $cExpWarn=(!empty($c['expiry_date'])&&strtotime($c['expiry_date'])<=strtotime('+60 days')&&strtotime($c['expiry_date'])>time());
        $cExpired=(!empty($c['expiry_date'])&&strtotime($c['expiry_date'])<time());
      ?>
        <div class="file-row" data-id="<?=$c['id']?>" style="<?=$cExpired?'border-left:3px solid var(--danger);':($cExpWarn?'border-left:3px solid var(--warning);':'')?>">
          <div class="file-icon-box" style="background:<?=$cExpired?'var(--danger)':($cExpWarn?'var(--warning)':'linear-gradient(135deg,#9B59B6,#2F80ED)')?>"><i class="fas fa-certificate"></i></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($c['cert_name'])?> <?=$cExpired?'<span style="color:var(--danger);font-size:1.1rem;">(EXPIRED)</span>':($cExpWarn?'<span style="color:var(--warning);font-size:1.1rem;">(Expiring Soon)</span>':'')?></div>
            <div style="font-size:1.15rem;color:var(--text-muted);"><?=htmlspecialchars($c['issuing_org'])?> <?=$c['expiry_date']?' &middot; Exp: '.date('d M Y',strtotime($c['expiry_date'])):''?></div>
          </div>
          <?php if(!empty($c['cert_file_path'])):?><a href="/RMU-Medical-Management-System/<?=htmlspecialchars($c['cert_file_path'])?>" target="_blank" class="btn btn-outline btn-icon btn btn-sm"><span class="btn-text"><i class="fas fa-download"></i></span></a><?php endif;?>
          <button class="btn btn-danger btn-sm" onclick="delCert(<?=$c['id']?>,this)"><span class="btn-text"><i class="fas fa-trash"></i></span></button>
        </div>
      <?php endforeach; endif;?>
      </div>
    </div>
  </div>
</div>
<script>
async function addQual(e){
  e.preventDefault();const fd=new FormData(e.target);fd.append('action','add_qualification');
  const res=await profAction(fd,true);
  if(res.success){toast('Qualification added!');location.reload();}else toast(res.message||'Error','danger');
}
async function delQual(id,btn){
  if(!confirm('Delete this qualification?'))return;
  const res=await profAction({action:'delete_qualification',id});
  if(res.success){toast('Deleted');btn.closest('.file-row').remove();}else toast(res.message||'Error','danger');
}
async function addCert(e){
  e.preventDefault();const fd=new FormData(e.target);fd.append('action','add_certification');
  const res=await profAction(fd,true);
  if(res.success){toast('Certification added!');location.reload();}else toast(res.message||'Error','danger');
}
async function delCert(id,btn){
  if(!confirm('Delete this certification?'))return;
  const res=await profAction({action:'delete_certification',id});
  if(res.success){toast('Deleted');btn.closest('.file-row').remove();}else toast(res.message||'Error','danger');
}
</script>
