<?php
/**
 * tab_maintenance.php
 * Module: Maintenance Work Orders (Only visible to Maintenance team)
 */
?>
<div id="sec-maintenance" class="dash-section <?=($active_tab==='maintenance')?'active':''?>">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;">
        <h2 style="font-size:2.2rem;font-weight:700;color:var(--text-primary);"><i class="fas fa-tools" style="color:var(--role-accent);"></i> Work Orders</h2>
    </div>

    <!-- Open Requests Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(400px,1fr));gap:2rem;">
        <?php
        $m_req = dbSelect($conn, "SELECT * FROM maintenance_requests WHERE assigned_to=? AND status NOT IN ('completed','cancelled') ORDER BY priority DESC, reported_at ASC", "i", [$staff_id]);
        if(empty($m_req)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:5rem;background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);">
                <i class="fas fa-thumbs-up" style="font-size:5rem;color:var(--text-muted);margin-bottom:1.5rem;"></i>
                <h3 style="font-size:1.8rem;color:var(--text-secondary);">All Clear</h3>
                <p style="color:var(--text-muted);">No open maintenance work orders assigned to you.</p>
            </div>
        <?php else: foreach($m_req as $m): 
            $pr = $m['priority'];
            $pbg = ['low'=>'var(--info)','medium'=>'var(--warning)','high'=>'var(--danger)','urgent'=>'#8E44AD'][$pr] ?? 'var(--text-muted)';
            $icg = ['electrical'=>'fa-plug','plumbing'=>'fa-faucet','structural'=>'fa-building','equipment'=>'fa-microscope','furniture'=>'fa-chair','other'=>'fa-wrench'][$m['issue_category']]??'fa-tools';
        ?>
            <div class="adm-card" style="border-top:4px solid <?=$pbg?>;">
                <div class="adm-card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1rem;">
                        <h4 style="font-size:1.6rem;font-weight:700;color:var(--text-primary);margin:0;"><i class="fas <?=$icg?>" style="color:var(--text-muted);"></i> <?=ucwords(e($m['issue_category']))?> Issue</h4>
                        <span class="adm-badge" style="background:<?=$pbg?>15;color:<?=$pbg?>;text-transform:uppercase;font-weight:700;letter-spacing:.05em;"><?=e($pr)?> PRIORITY</span>
                    </div>
                    
                    <p style="font-size:1.3rem;font-weight:600;color:var(--text-primary);margin-bottom:.5rem;">Location: <?=e($m['location'])?></p>
                    <p style="font-size:1.2rem;color:var(--text-muted);margin-bottom:1.5rem;">Reported: <?=date('d M Y, h:i A', strtotime($m['reported_at']))?></p>
                    
                    <div style="background:var(--surface-2);padding:1.5rem;border-radius:8px;border:1px solid var(--border);margin-bottom:1.5rem;">
                        <strong style="display:block;margin-bottom:.5rem;font-size:1.2rem;color:var(--text-secondary);text-transform:uppercase;">Issue Details</strong>
                        <p style="font-size:1.3rem;color:var(--text-primary);margin:0;line-height:1.5;"><?=e($m['issue_description'])?></p>
                    </div>

                    <div style="display:flex;gap:1rem;">
                        <button class="adm-btn adm-btn-primary" style="flex:1;" onclick="showToast('Feature rolling out in Phase 5','info')"><i class="fas fa-tools"></i> Log Action</button>
                        <button class="adm-btn adm-btn-outline" style="border-color:var(--success);color:var(--success);" onclick="showToast('Feature rolling out in Phase 5','info')"><i class="fas fa-check"></i> Complete</button>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
