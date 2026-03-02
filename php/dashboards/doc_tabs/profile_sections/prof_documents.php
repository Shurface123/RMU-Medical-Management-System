<?php // SECTION I: Documents & Uploads ?>
<div id="prof-documents" class="prof-section" style="display:none;">
  <div class="adm-card">
    <div class="adm-card-header"><h3><i class="fas fa-file-upload"></i> Documents & Uploads</h3>
      <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="document.getElementById('addDocForm').style.display='block'"><i class="fas fa-plus"></i> Upload</button>
    </div>
    <div style="padding:1.5rem;">
      <!-- Upload form -->
      <div id="addDocForm" style="display:none;background:var(--surface-2);border-radius:12px;padding:1.5rem;margin-bottom:1.5rem;">
        <h4 style="margin-bottom:1rem;">Upload Document</h4>
        <form onsubmit="uploadDoc(event)">
          <div class="form-row">
            <div class="form-group"><label>File *</label><input type="file" name="document" class="form-control" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></div>
            <div class="form-group"><label>Description</label><input type="text" name="description" class="form-control" placeholder="e.g. Medical License, Insurance"></div>
          </div>
          <p style="font-size:1.05rem;color:var(--text-muted);margin-bottom:1rem;">Accepted: PDF, JPG, PNG, DOC, DOCX (max 10MB)</p>
          <div style="display:flex;gap:.8rem;"><button type="submit" class="adm-btn adm-btn-primary"><i class="fas fa-upload"></i> Upload</button><button type="button" class="adm-btn" onclick="this.closest('div[id]').style.display='none'">Cancel</button></div>
        </form>
      </div>
      <!-- Document list -->
      <div id="docList">
      <?php if(empty($documents)):?><p style="color:var(--text-muted);text-align:center;padding:2rem;">No documents uploaded yet.</p>
      <?php else: foreach($documents as $dc):
        $ftLower=strtolower($dc['file_type']??'');
        $iconMap=['pdf'=>'fa-file-pdf','jpg'=>'fa-file-image','jpeg'=>'fa-file-image','png'=>'fa-file-image','webp'=>'fa-file-image','doc'=>'fa-file-word','docx'=>'fa-file-word'];
        $icon=$iconMap[$ftLower]??'fa-file';
        $colorMap=['pdf'=>'#E74C3C','jpg'=>'#2F80ED','jpeg'=>'#2F80ED','png'=>'#2F80ED','webp'=>'#2F80ED','doc'=>'#2980B9','docx'=>'#2980B9'];
        $color=$colorMap[$ftLower]??'var(--text-muted)';
        $sizeStr=$dc['file_size']>1048576?round($dc['file_size']/1048576,1).' MB':round($dc['file_size']/1024,1).' KB';
      ?>
        <div class="file-row" data-id="<?=$dc['id']?>">
          <div class="file-icon-box" style="background:<?=$color?>;"><i class="fas <?=$icon?>"></i></div>
          <div style="flex:1;">
            <div style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($dc['file_name'])?></div>
            <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars($dc['description']??'No description')?> &middot; <?=$sizeStr?> &middot; <?=date('d M Y',strtotime($dc['uploaded_at']))?></div>
          </div>
          <a href="/RMU-Medical-Management-System/<?=htmlspecialchars($dc['file_path'])?>" target="_blank" class="adm-btn adm-btn-sm" title="Download"><i class="fas fa-download"></i></a>
          <button class="adm-btn adm-btn-danger adm-btn-sm" onclick="delDoc(<?=$dc['id']?>,this)" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
      <?php endforeach; endif;?>
      </div>
    </div>
  </div>
</div>
<script>
async function uploadDoc(e){
  e.preventDefault();const fd=new FormData(e.target);fd.append('action','upload_document');
  const res=await profAction(fd,true);
  if(res.success){toast('Document uploaded!');location.reload();}else toast(res.message||'Error','danger');
}
async function delDoc(id,btn){
  if(!confirm('Delete this document?'))return;
  const res=await profAction({action:'delete_document',id});
  if(res.success){toast('Deleted');btn.closest('.file-row').remove();}else toast(res.message||'Error','danger');
}
</script>
