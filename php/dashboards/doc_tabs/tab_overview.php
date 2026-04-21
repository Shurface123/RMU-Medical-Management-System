<?php // TAB: OVERVIEW ?>
<div id="sec-overview" class="dash-section">

<style>
/* ── Premium Hero Banner ── */
.doc-hero-v2 {
  position:relative;overflow:hidden;border-radius:20px;margin-bottom:2rem;
  background:linear-gradient(135deg,#1C3A6B 0%,#2F80ED 50%,#56CCF2 100%);
  padding:2.5rem 3rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap;
  box-shadow:0 20px 60px rgba(47,128,237,.3);
}
.doc-hero-v2::before {
  content:'';position:absolute;bottom:-30%;right:-10%;width:350px;height:350px;
  background:radial-gradient(circle,rgba(255,255,255,.08) 0%,transparent 70%);border-radius:50%;
}
.doc-hero-v2::after {
  content:'';position:absolute;top:-20%;left:25%;width:250px;height:250px;
  background:radial-gradient(circle,rgba(86,204,242,.15) 0%,transparent 70%);border-radius:50%;
}
.doc-hero-avatar-v2 {
  width:80px;height:80px;border-radius:50%;border:4px solid rgba(255,255,255,.4);
  background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;
  font-size:3.5rem;font-weight:700;color:#fff;flex-shrink:0;overflow:hidden;
  backdrop-filter:blur(10px);position:relative;z-index:1; box-shadow:0 10px 25px rgba(0,0,0,0.2);
}
.doc-hero-info-v2 { flex:1;position:relative;z-index:1; }
.doc-hero-info-v2 h2 { font-size:2.4rem;font-weight:800;color:#fff;margin:0 0 .3rem; letter-spacing:-0.5px; }
.doc-hero-info-v2 p  { font-size:1.25rem;color:rgba(255,255,255,.8);margin:0 0 .8rem; font-weight:500; }
.doc-chips { display:flex;gap:.7rem;flex-wrap:wrap; }
.doc-chip {
  display:inline-flex;align-items:center;gap:.5rem;padding:.35rem 1rem;
  border-radius:20px;font-size:1.1rem;font-weight:600;
  background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);
  backdrop-filter:blur(5px); transition:all 0.3s ease;
}
.doc-chip:hover { background:rgba(255,255,255,0.25); box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.doc-chip.valid   { background:rgba(20,184,166,.3);border-color:rgba(20,184,166,.5); }
.doc-chip.danger  { background:rgba(244,63,94,.3);border-color:rgba(244,63,94,.5); }

/* ── Premium Stat Cards ── */
.doc-stat-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:2rem;
}
.doc-stat-card {
  background:var(--surface);border:1px solid var(--border);border-radius:16px;
  padding:2rem 1.8rem;position:relative;overflow:hidden;cursor:pointer;
  transition:all .3s cubic-bezier(.4,0,.2,1);box-shadow:0 4px 15px rgba(0,0,0,.04);
}
.doc-stat-card::before {
  content:'';position:absolute;top:0;left:0;right:0;height:5px;
  border-radius:16px 16px 0 0;background:var(--card-accent,var(--role-accent));
}
.doc-stat-card:hover { transform:translateY(-5px);box-shadow:0 15px 35px rgba(0,0,0,.1); border-color:var(--card-accent,var(--role-accent)); }
.doc-stat-card .sc-icon {
  width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:1.8rem;margin-bottom:1.2rem;
}
.doc-stat-card .sc-val { font-size:3rem;font-weight:800;line-height:1;color:var(--text-primary);margin-bottom:.5rem; letter-spacing:-1px; }
.doc-stat-card .sc-lbl { font-size:1.2rem;font-weight:600;color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.04em; }

.charts-grid-v2 { display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem; }
.adm-card-v2 { background:var(--surface);border:1px solid var(--border);border-radius:16px;box-shadow:0 4px 15px rgba(0,0,0,.04); overflow:hidden; }
.adm-card-v2 .adm-card-header { padding:1.8rem 2rem; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
.adm-card-v2 .adm-card-header h3 { font-size:1.4rem; font-weight:700; color:var(--text-primary); margin:0; }
</style>

  <!-- Premium Hero Banner -->
  <div class="doc-hero-v2">
    <div class="doc-hero-avatar-v2">
        <i class="fas fa-user-md"></i>
    </div>
    <div class="doc-hero-info-v2">
      <h2>Dr. <?=htmlspecialchars($doc_row['name'])?></h2>
      <p><?=htmlspecialchars($doc_row['specialization']?:'General Practitioner')?> &mdash; RMU Medical Sickbay</p>
      <div class="doc-chips">
        <span class="doc-chip"><i class="fas fa-id-badge"></i><?=htmlspecialchars($doc_row['doctor_id']??'N/A')?></span>
        <?php if($doc_row['experience_years']):?><span class="doc-chip"><i class="fas fa-star"></i><?=$doc_row['experience_years']?> yrs</span><?php endif;?>
        <span class="doc-chip <?=$doc_row['is_available']?'valid':'danger'?>">
          <i class="fas fa-circle" style="font-size:.6rem;"></i> <?=$doc_row['is_available']?'Available':'Unavailable'?>
        </span>
        <span class="doc-chip"><i class="fas fa-clock"></i><?=date('g:i A')?></span>
      </div>
    </div>
    <div style="text-align:right;position:relative;z-index:1;display:flex;gap:1rem;">
      <button onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))" class="btn btn-primary" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:12px;padding:1rem 1.5rem;"><span class="btn-text">
        <i class="fas fa-calendar-check" style="font-size:1.3rem;"></i> Appointments
      </span></button>
      <button onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'))" class="btn btn-primary" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);border-radius:12px;padding:1rem 1.5rem;"><span class="btn-text">
        <i class="fas fa-prescription-bottle-medical" style="font-size:1.3rem;"></i> New Rx
      </span></button>
    </div>
  </div>

  <!-- Premium Stats Strip -->
  <div class="doc-stat-grid">
    <div class="doc-stat-card" style="--card-accent:var(--primary);" onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))">
      <div class="sc-icon" style="background:var(--primary-light);color:var(--primary);"><i class="fas fa-calendar-day"></i></div>
      <div class="sc-val"><?=$stats['today_appts']?></div>
      <div class="sc-lbl">Today's Appts</div>
    </div>
    <div class="doc-stat-card" style="--card-accent:var(--success);" onclick="showTab('patients',document.querySelector('.adm-nav-item[onclick*=patients]'))">
      <div class="sc-icon" style="background:var(--success-light);color:var(--success);"><i class="fas fa-users"></i></div>
      <div class="sc-val"><?=$stats['total_patients']?></div>
      <div class="sc-lbl">Total Patients</div>
    </div>
    <div class="doc-stat-card" style="--card-accent:var(--warning);" onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'))">
      <div class="sc-icon" style="background:var(--warning-light);color:var(--warning);"><i class="fas fa-pills"></i></div>
      <div class="sc-val"><?=$stats['active_rx']?></div>
      <div class="sc-lbl">Active Rx</div>
    </div>
    <div class="doc-stat-card" style="--card-accent:var(--info);" onclick="showTab('beds',document.querySelector('.adm-nav-item[onclick*=beds]'))">
      <div class="sc-icon" style="background:var(--info-light);color:var(--info);"><i class="fas fa-bed"></i></div>
      <div class="sc-val"><?=$stats['avail_beds']?></div>
      <div class="sc-lbl">Available Beds</div>
    </div>
    <div class="doc-stat-card" style="--card-accent:var(--danger);" onclick="showTab('medicine',document.querySelector('.adm-nav-item[onclick*=medicine]'))">
      <div class="sc-icon" style="background:var(--danger-light);color:var(--danger);"><i class="fas fa-triangle-exclamation"></i></div>
      <div class="sc-val"><?=$stats['low_stock']?></div>
      <div class="sc-lbl">Low Medicine Stock</div>
    </div>
  </div>

  <!-- Main Grid -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start;" class="overview-grid">

    <!-- Left: Charts + Today Schedule -->
    <div>
      <div class="charts-grid-v2">
        <div class="adm-card-v2">
          <div class="adm-card-header"><h3><i class="fas fa-chart-line" style="color:var(--primary);margin-right:.5rem;"></i> Weekly Workload</h3></div>
          <div style="padding:1.5rem;"><div class="chart-wrap" style="height:220px;"><canvas id="chartWeekly"></canvas></div></div>
        </div>
        <div class="adm-card-v2">
          <div class="adm-card-header"><h3><i class="fas fa-chart-pie" style="color:var(--primary);margin-right:.5rem;"></i> Appt Status</h3></div>
          <div style="padding:1.5rem;"><div class="chart-wrap" style="height:220px;"><canvas id="chartStatus"></canvas></div></div>
        </div>
      </div>

      <!-- Today's Schedule -->
      <div class="adm-card-v2" style="margin-top:2rem;">
        <div class="adm-card-header">
          <h3><i class="fas fa-calendar-day" style="color:var(--primary);margin-right:.5rem;"></i> Today's Schedule
            <span class="adm-badge adm-badge-primary" style="margin-left:.5rem;"><?=$stats['today_appts']?></span>
          </h3>
          <button onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))" class="btn btn-outline-primary btn-sm" style="border-radius:20px;padding:.5rem 1.5rem;font-weight:600;">View All</button>
        </div>
        <div style="padding:.5rem 0;">
        <?php $today_list=array_filter($appointments,fn($a)=>$a['appointment_date']===date('Y-m-d')); ?>
        <?php if(empty($today_list)):?>
          <div style="text-align:center;padding:4rem 2rem;color:var(--text-muted);">
            <i class="fas fa-calendar-xmark" style="font-size:3rem;margin-bottom:1rem;color:var(--border);"></i>
            <p style="font-size:1.2rem;">No appointments scheduled for today.</p>
          </div>
        <?php else: foreach($today_list as $ap):
          $__sc_map=["Confirmed"=>"success","Completed"=>"info","Cancelled"=>"danger","Rescheduled"=>"warning"]; $sc=$__sc_map[$ap["status"]] ?? "warning";
          [$h,$m]=explode(':',substr($ap['appointment_time'],0,5));
          $h12=$h>12?$h-12:($h==0?12:(int)$h); $ampm=$h>=12?'PM':'AM';
        ?>
          <div style="display:flex;align-items:center;gap:1.5rem;padding:1.5rem 2rem;border-bottom:1px solid var(--border);transition:var(--transition);" onmouseover="this.style.backgroundColor='var(--primary-light)'" onmouseout="this.style.backgroundColor='transparent'">
            <div style="text-align:center;min-width:60px;background:var(--surface-2);border-radius:12px;padding:1rem .5rem;">
               <div style="font-size:1.6rem;font-weight:800;color:var(--primary);line-height:1;"><?=$h12.':'.$m?></div>
               <div style="font-size:1.1rem;color:var(--text-muted);margin-top:.3rem;font-weight:600;"><?=$ampm?></div>
            </div>
            <div style="flex:1;">
              <div style="font-weight:700;font-size:1.4rem;color:var(--text-primary);"><?=htmlspecialchars($ap['patient_name']??'')?></div>
              <div style="font-size:1.2rem;color:var(--text-secondary);margin-top:.2rem;"><?=htmlspecialchars($ap['service_type']??'Consultation')?> &middot; <span style="font-family:monospace;"><?=htmlspecialchars($ap['p_ref']??'')?></span></div>
              <?php if(!empty($ap['symptoms'])):?><div style="font-size:1.1rem;color:var(--text-muted);font-style:italic;margin-top:.4rem;"><i class="fas fa-notes-medical" style="margin-right:.4rem;"></i><?=htmlspecialchars(substr($ap['symptoms'],0,70))?>...</div><?php endif;?>
            </div>
            <span class="adm-badge adm-badge-<?=$sc?>" style="padding:.5rem 1.2rem;font-size:1.1rem;"><?=$ap['status']?></span>
          </div>
        <?php endforeach; endif;?>
        </div>
      </div>
    </div>

    <!-- Right Panel -->
    <div style="display:flex;flex-direction:column;gap:2rem;">

      <!-- Notifications -->
      <div class="adm-card-v2">
        <div class="adm-card-header" style="background:var(--surface-2);">
           <h3><i class="fas fa-bell" style="color:var(--warning);margin-right:.5rem;"></i> System Alerts</h3>
          <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-danger" style="animation:pulse 2s infinite;"><?=$stats['unread_notifs']?> new</span><?php endif;?>
        </div>
        <div style="padding:1rem;">
          <?php if(empty($notifs)):?>
            <p style="text-align:center;padding:2rem;color:var(--text-muted);font-size:1.2rem;margin:0;"><i class="fas fa-check-circle" style="font-size:2rem;color:var(--success);opacity:0.5;display:block;margin-bottom:1rem;"></i>All caught up!</p>
          <?php else: foreach(array_slice($notifs,0,5) as $n):?>
            <div style="display:flex;gap:1.2rem;padding:1.2rem;border-radius:12px;transition:var(--transition);cursor:pointer;" onmouseover="this.style.backgroundColor='var(--surface-2)'" onmouseout="this.style.backgroundColor='transparent'">
              <div style="width:40px;height:40px;border-radius:10px;background:<?=$n['is_read']?'var(--surface-2)':'var(--primary-light)'?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <i class="fas <?=$n['is_read']?'fa-envelope-open':'fa-envelope'?>" style="color:<?=$n['is_read']?'var(--text-muted)':'var(--primary)'?>;font-size:1.4rem;"></i>
              </div>
              <div style="flex:1;">
                <div style="font-size:1.3rem;font-weight:<?=$n['is_read']?'500':'700'?>;color:var(--text-primary);margin-bottom:.2rem;"><?=htmlspecialchars($n['title']??$n['message']??'')?></div>
                <div style="font-size:1.15rem;color:var(--text-secondary);"><?=htmlspecialchars(substr($n['message']??'',0,60))?></div>
                <div style="font-size:1rem;color:var(--text-muted);margin-top:.4rem;font-weight:500;"><i class="fas fa-clock" style="margin-right:.4rem;"></i><?=date('d M, g:i A',strtotime($n['created_at']))?></div>
              </div>
            </div>
          <?php endforeach; endif;?>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="adm-card-v2">
        <div class="adm-card-header"><h3><i class="fas fa-bolt" style="color:var(--warning);margin-right:.5rem;"></i> Quick Actions</h3></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;padding:1.5rem;">
          <button onclick="openModal('modalNewRx')" class="btn btn-outline-primary" style="flex-direction:column;height:100px;border-radius:14px;border:2px solid var(--primary-light);color:var(--primary);background:var(--surface);"><span class="btn-text">
            <i class="fas fa-prescription-bottle-medical" style="font-size:2rem;margin-bottom:.8rem;"></i><span style="font-size:1.2rem;font-weight:600;">Rx</span>
          </span></button>
          <button onclick="openModal('modalNewRecord')" class="btn btn-outline-success" style="flex-direction:column;height:100px;border-radius:14px;border:2px solid var(--success-light);color:var(--success);background:var(--surface);"><span class="btn-text">
            <i class="fas fa-file-medical" style="font-size:2rem;margin-bottom:.8rem;"></i><span style="font-size:1.2rem;font-weight:600;">Record</span>
          </span></button>
          <button onclick="showTab('reports',document.querySelector('.adm-nav-item[onclick*=reports]'))" class="btn btn-outline-info" style="flex-direction:column;height:100px;border-radius:14px;border:2px solid var(--info-light);color:var(--info);background:var(--surface);grid-column:1 / span 2;"><span class="btn-text">
            <i class="fas fa-file-export" style="font-size:2rem;margin-bottom:.8rem;"></i><span style="font-size:1.2rem;font-weight:600;">Generate Report</span>
          </span></button>
        </div>
      </div>

    </div><!-- /right -->
  </div><!-- /grid -->
</div><!-- /overview -->
<style>@media(max-width:1024px){.overview-grid{grid-template-columns:1fr!important;}}</style>
