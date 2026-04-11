<?php // TAB: OVERVIEW ?>
<div id="sec-overview" class="dash-section">

  <!-- Hero Banner -->
  <div class="doc-hero">
    <div class="doc-avatar-hero"><i class="fas fa-user-md"></i></div>
    <div class="doc-hero-info" style="flex:1;">
      <h2>Dr. <?=htmlspecialchars($doc_row['name'])?></h2>
      <p><?=htmlspecialchars($doc_row['specialization']?:'General Practitioner')?> &mdash; RMU Medical Sickbay</p>
      <div style="margin-top:.6rem;">
        <span class="hero-badge"><i class="fas fa-id-badge"></i><?=htmlspecialchars($doc_row['doctor_id']??'N/A')?></span>
        <?php if($doc_row['experience_years']):?><span class="hero-badge"><i class="fas fa-star"></i><?=$doc_row['experience_years']?> yrs</span><?php endif;?>
        <span class="hero-badge" style="background:<?=$doc_row['is_available']?'rgba(26,188,156,.3)':'rgba(231,76,60,.3)'?>;">
          <i class="fas fa-circle" style="font-size:.45rem;"></i><?=$doc_row['is_available']?'Available':'Unavailable'?>
        </span>
        <span class="hero-badge"><i class="fas fa-clock"></i><?=date('g:i A')?></span>
      </div>
    </div>
    <div style="text-align:right;position:relative;z-index:1;">
      <button onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))" class="btn btn-primary btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);margin:.3rem;"><span class="btn-text">
        <i class="fas fa-calendar-check"></i> Appointments
      </span></button>
      <button onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'))" class="btn btn-primary btn" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);margin:.3rem;"><span class="btn-text">
        <i class="fas fa-prescription-bottle-medical"></i> New Rx
      </span></button>
    </div>
  </div>

  <!-- Stats Strip -->
  <div class="adm-summary-strip">
    <div class="adm-mini-card" onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))">
      <div class="adm-mini-card-num blue"><?=$stats['today_appts']?></div>
      <div class="adm-mini-card-label"><i class="fas fa-calendar-day"></i> Today's Appts</div>
    </div>
    <div class="adm-mini-card" onclick="showTab('patients',document.querySelector('.adm-nav-item[onclick*=patients]'))">
      <div class="adm-mini-card-num teal"><?=$stats['total_patients']?></div>
      <div class="adm-mini-card-label"><i class="fas fa-users"></i> My Patients</div>
    </div>
    <div class="adm-mini-card" onclick="showTab('prescriptions',document.querySelector('.adm-nav-item[onclick*=prescriptions]'))">
      <div class="adm-mini-card-num green"><?=$stats['active_rx']?></div>
      <div class="adm-mini-card-label"><i class="fas fa-pills"></i> Active Rx</div>
    </div>
    <div class="adm-mini-card" onclick="showTab('beds',document.querySelector('.adm-nav-item[onclick*=beds]'))">
      <div class="adm-mini-card-num"><?=$stats['avail_beds']?></div>
      <div class="adm-mini-card-label"><i class="fas fa-bed"></i> Available Beds</div>
    </div>
    <div class="adm-mini-card" onclick="showTab('medicine',document.querySelector('.adm-nav-item[onclick*=medicine]'))">
      <div class="adm-mini-card-num red"><?=$stats['low_stock']?></div>
      <div class="adm-mini-card-label"><i class="fas fa-triangle-exclamation"></i> Low Stock</div>
    </div>
  </div>

  <!-- Main Grid -->
  <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;" class="overview-grid">

    <!-- Left: Charts + Today Schedule -->
    <div>
      <!-- Mini Charts -->
      <div class="charts-grid">
        <div class="adm-card">
          <div class="adm-card-header"><h3><i class="fas fa-chart-line"></i> This Week's Appointments</h3></div>
          <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartWeekly"></canvas></div></div>
        </div>
        <div class="adm-card">
          <div class="adm-card-header"><h3><i class="fas fa-chart-pie"></i> Appointment Status</h3></div>
          <div style="padding:1.5rem;"><div class="chart-wrap"><canvas id="chartStatus"></canvas></div></div>
        </div>
      </div>

      <!-- Today's Schedule -->
      <div class="adm-card">
        <div class="adm-card-header">
          <h3><i class="fas fa-calendar-day"></i> Today's Schedule
            <span class="adm-badge adm-badge-primary" style="margin-left:.5rem;"><?=$stats['today_appts']?></span>
          </h3>
          <a href="#" onclick="showTab('appointments',document.querySelector('.adm-nav-item[onclick*=appointments]'))" class="btn-icon btn btn-primary btn-sm"><span class="btn-text">View All</span></a>
        </div>
        <div style="padding:.5rem 1.5rem;">
        <?php $today_list=array_filter($appointments,fn($a)=>$a['appointment_date']===$today); ?>
        <?php if(empty($today_list)):?>
          <div style="text-align:center;padding:3rem;color:var(--text-muted);">
            <i class="fas fa-calendar-xmark" style="font-size:2.5rem;margin-bottom:1rem;opacity:.4;display:block;"></i>
            <p>No appointments scheduled for today.</p>
          </div>
        <?php else: foreach($today_list as $ap):
          $__sc_map=["Confirmed"=>"success","Completed"=>"info","Cancelled"=>"danger","Rescheduled"=>"warning"]; $sc=$__sc_map[$ap["status"]] ?? "warning";
          [$h,$m]=explode(':',substr($ap['appointment_time'],0,5));
          $h12=$h>12?$h-12:($h==0?12:(int)$h); $ampm=$h>=12?'PM':'AM';
        ?>
          <div style="display:flex;align-items:flex-start;gap:1rem;padding:1rem 0;border-bottom:1px solid var(--border);">
            <div style="text-align:center;min-width:52px;"><div style="font-size:1.5rem;font-weight:800;color:var(--role-accent);"><?=$h12.':'.$m?></div><div style="font-size:1rem;color:var(--text-muted);"><?=$ampm?></div></div>
            <div style="flex:1;">
              <div style="font-weight:600;font-size:1.3rem;"><?=htmlspecialchars($ap['patient_name']??'')?></div>
              <div style="font-size:1.1rem;color:var(--text-secondary);"><?=htmlspecialchars($ap['service_type']??'Consultation')?> &middot; <?=htmlspecialchars($ap['p_ref']??'')?></div>
              <?php if(!empty($ap['symptoms'])):?><div style="font-size:1.1rem;color:var(--text-muted);font-style:italic;"><?=htmlspecialchars(substr($ap['symptoms'],0,70))?>...</div><?php endif;?>
            </div>
            <span class="adm-badge adm-badge-<?=$sc?>"><?=$ap['status']?></span>
          </div>
        <?php endforeach; endif;?>
        </div>
      </div>
    </div>

    <!-- Right Panel -->
    <div style="display:flex;flex-direction:column;gap:1.5rem;">

      <!-- Notifications -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-bell"></i> Notifications</h3>
          <?php if($stats['unread_notifs']>0):?><span class="adm-badge adm-badge-danger"><?=$stats['unread_notifs']?> new</span><?php endif;?>
        </div>
        <div style="padding:.5rem 1.5rem;">
          <?php if(empty($notifs)):?>
            <p style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:1.2rem;">All caught up!</p>
          <?php else: foreach(array_slice($notifs,0,6) as $n):?>
            <div style="display:flex;gap:.8rem;padding:.8rem 0;border-bottom:1px solid var(--border);align-items:flex-start;">
              <div style="width:8px;height:8px;border-radius:50%;background:<?=$n['is_read']?'var(--border)':'var(--role-accent)'?>;flex-shrink:0;margin-top:.6rem;"></div>
              <div style="flex:1;">
                <div style="font-size:1.2rem;font-weight:<?=$n['is_read']?'400':'600'?>;color:var(--text-primary);"><?=htmlspecialchars($n['title']??$n['message']??'')?></div>
                <div style="font-size:1.1rem;color:var(--text-muted);"><?=htmlspecialchars(substr($n['message']??'',0,60))?></div>
                <div style="font-size:1rem;color:var(--text-muted);margin-top:.2rem;"><?=date('d M, g:i A',strtotime($n['created_at']))?></div>
              </div>
            </div>
          <?php endforeach; endif;?>
        </div>
      </div>

      <!-- Activity Feed -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-clock-rotate-left"></i> Recent Activity</h3></div>
        <div style="padding:.5rem 1.5rem;">
          <?php if(empty($activity)):?>
            <p style="text-align:center;padding:1.5rem;color:var(--text-muted);font-size:1.2rem;">No activity yet.</p>
          <?php else: foreach($activity as $act):
            $dot=($act['type']==='Prescription')?'orange':'';
          ?>
            <div class="activity-item">
              <span class="activity-dot <?=$dot?>"></span>
              <div style="flex:1;">
                <div style="font-size:1.2rem;font-weight:500;"><?=htmlspecialchars($act['type'])?> &mdash; <?=htmlspecialchars($act['person']??'')?></div>
                <div style="font-size:1.1rem;color:var(--text-muted);"><?=date('d M, g:i A',strtotime($act['ts']))?></div>
              </div>
              <?php 
                $bc_map=['Completed'=>'success','Pending'=>'warning','Cancelled'=>'danger'];
                $bc=$bc_map[$act['status']] ?? 'info';
              ?>
              <span class="adm-badge adm-badge-<?=$bc?>"><?=$act['status']?></span>
            </div>
          <?php endforeach; endif;?>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="adm-card">
        <div class="adm-card-header"><h3><i class="fas fa-bolt"></i> Quick Actions</h3></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:1.5rem;">
          <button onclick="openModal('modalNewRx')" class="btn btn-primary" style="flex-direction:column;height:auto;padding:1.2rem .8rem;text-align:center;gap:.5rem;"><span class="btn-text">
            <i class="fas fa-prescription-bottle-medical" style="font-size:1.5rem;"></i><span style="font-size:1.1rem;">New Prescription</span>
          </span></button>
          <button onclick="openModal('modalNewRecord')" class="btn btn-success" style="flex-direction:column;height:auto;padding:1.2rem .8rem;text-align:center;gap:.5rem;"><span class="btn-text">
            <i class="fas fa-file-circle-plus" style="font-size:1.5rem;"></i><span style="font-size:1.1rem;">Add Record</span>
          </span></button>
          <button onclick="showTab('reports',document.querySelector('.adm-nav-item[onclick*=reports]'))" class="btn-icon btn btn-ghost" style="flex-direction:column;height:auto;padding:1.2rem .8rem;text-align:center;gap:.5rem;"><span class="btn-text">
            <i class="fas fa-file-export" style="font-size:1.5rem;"></i><span style="font-size:1.1rem;">Generate Report</span>
          </span></button>
        </div>
      </div>

    </div><!-- /right -->
  </div><!-- /grid -->
</div><!-- /overview -->
<style>@media(max-width:900px){.overview-grid{grid-template-columns:1fr!important;}}</style>
