<?php
/**
 * tab_overview.php
 * The landing page for the Staff Dashboard.
 * Gives a summary of pending tasks, active shifts, and notifications.
 */
?>
<div id="sec-overview" class="dash-section <?=($active_tab==='overview')?'active':''?>">
    
    <!-- Welcome Banner -->
    <div class="adm-welcome" style="margin-bottom:2rem;background:var(--surface);padding:2rem;border-radius:var(--radius-md);box-shadow:var(--shadow-sm);border:1px solid var(--border);">
        <h2>Welcome, <?=e(explode(' ',$displayName)[0])?> 👋</h2>
        <p style="color:var(--text-secondary);font-size:1.4rem;margin-top:.5rem;">
            <?=date('l, d F Y')?> &bull; <strong style="color:var(--text-primary);"><?=e($displayRole)?></strong>
        </p>
    </div>

    <!-- Stats Grid -->
    <div class="adm-stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2rem;margin-bottom:2.5rem;">
        
        <a class="adm-stat-card" style="display:block;background:var(--surface);padding:2rem;border-radius:var(--radius-md);border:1px solid var(--border);position:relative;overflow:hidden;text-decoration:none;transition:var(--transition);" onclick="showTab('tasks',document.querySelector('[onclick*=tasks]'))">
            <div class="adm-stat-icon staff" style="width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin-bottom:1.5rem;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <span class="adm-stat-label" style="display:block;font-size:1.3rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Pending Tasks</span>
            <div class="adm-stat-value" style="font-size:3.2rem;font-weight:700;color:var(--text-primary);"><?=$stats['pending_tasks']?></div>
            <div class="adm-stat-footer" style="margin-top:1.5rem;font-size:1.2rem;color:var(--text-muted);display:flex;align-items:center;gap:.6rem;border-top:1px solid var(--border);padding-top:1rem;">
                <i class="fas fa-arrow-right"></i> View my checklist
            </div>
            <!-- Decorative circle -->
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(79,70,229,.05);z-index:0;"></div>
        </a>

        <a class="adm-stat-card" style="display:block;background:var(--surface);padding:2rem;border-radius:var(--radius-md);border:1px solid var(--border);position:relative;overflow:hidden;text-decoration:none;transition:var(--transition);" onclick="showTab('schedule',document.querySelector('[onclick*=schedule]'))">
            <div class="adm-stat-icon" style="background:linear-gradient(135deg, #F39C12, #F7CF68);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin-bottom:1.5rem;">
                <i class="fas fa-clock"></i>
            </div>
            <span class="adm-stat-label" style="display:block;font-size:1.3rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Current Shift</span>
            <div class="adm-stat-value" style="font-size:2.4rem;font-weight:700;color:var(--text-primary);margin-top:1rem;">
                <?php
                // Get active shift for today
                $today = date('Y-m-d');
                $sql = "SELECT shift_type, start_time, end_time FROM staff_shifts WHERE staff_id=? AND shift_date=? ORDER BY id DESC LIMIT 1";
                $shiftRow = dbRow($conn, $sql, "is", [$staff_id, $today]);
                if($shiftRow) {
                    echo e(ucfirst($shiftRow['shift_type'])) . ' <span style="font-size:1.4rem;color:var(--text-muted);font-weight:500;">('.date('H:i', strtotime($shiftRow['start_time'])).' - '.date('H:i', strtotime($shiftRow['end_time'])).')</span>';
                } else {
                    echo 'Off Duty';
                }
                ?>
            </div>
        </a>

        <?php if($staffRole === 'cleaner'):
            $logs = dbVal($conn, "SELECT COUNT(*) FROM cleaning_logs WHERE staff_id=? AND DATE(created_at)=?", "is", [$staff_id, $today]);
        ?>
        <a class="adm-stat-card" style="display:block;background:var(--surface);padding:2rem;border-radius:var(--radius-md);border:1px solid var(--border);position:relative;overflow:hidden;text-decoration:none;transition:var(--transition);" onclick="showTab('cleaning',document.querySelector('[onclick*=cleaning]'))">
            <div class="adm-stat-icon" style="background:linear-gradient(135deg, #1ABC9C, #48C9B0);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin-bottom:1.5rem;">
                <i class="fas fa-broom"></i>
            </div>
            <span class="adm-stat-label" style="display:block;font-size:1.3rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Wards Cleaned</span>
            <div class="adm-stat-value" style="font-size:3.2rem;font-weight:700;color:var(--text-primary);"><?=$logs?></div>
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(26,188,156,.05);z-index:0;"></div>
        </a>
        <?php endif; ?>

        <?php if($staffRole === 'maintenance'):
            $m_tasks = dbVal($conn, "SELECT COUNT(*) FROM maintenance_requests WHERE assigned_to=? AND status='assigned'", "i", [$staff_id]);
        ?>
        <a class="adm-stat-card" style="display:block;background:var(--surface);padding:2rem;border-radius:var(--radius-md);border:1px solid var(--border);position:relative;overflow:hidden;text-decoration:none;transition:var(--transition);" onclick="showTab('maintenance',document.querySelector('[onclick*=maintenance]'))">
            <div class="adm-stat-icon" style="background:linear-gradient(135deg, #E74C3C, #EC7063);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin-bottom:1.5rem;">
                <i class="fas fa-wrench"></i>
            </div>
            <span class="adm-stat-label" style="display:block;font-size:1.3rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Open Work Orders</span>
            <div class="adm-stat-value" style="font-size:3.2rem;font-weight:700;color:var(--text-primary);"><?=$m_tasks?></div>
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(231,76,60,.05);z-index:0;"></div>
        </a>
        <?php endif; ?>

        <?php if($staffRole === 'ambulance_driver'):
            $trips = dbVal($conn, "SELECT COUNT(*) FROM ambulance_trips WHERE driver_id=? AND DATE(created_at)=?", "is", [$staff_id, $today]);
        ?>
        <a class="adm-stat-card" style="display:block;background:var(--surface);padding:2rem;border-radius:var(--radius-md);border:1px solid var(--border);position:relative;overflow:hidden;text-decoration:none;transition:var(--transition);" onclick="showTab('ambulance',document.querySelector('[onclick*=ambulance]'))">
            <div class="adm-stat-icon" style="background:linear-gradient(135deg, #2980B9, #5DADE2);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;margin-bottom:1.5rem;">
                <i class="fas fa-truck-medical"></i>
            </div>
            <span class="adm-stat-label" style="display:block;font-size:1.3rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.5rem;">Trips Today</span>
            <div class="adm-stat-value" style="font-size:3.2rem;font-weight:700;color:var(--text-primary);"><?=$trips?></div>
            <div style="position:absolute;top:-20px;right:-20px;width:100px;height:100px;border-radius:50%;background:rgba(41,128,185,.05);z-index:0;"></div>
        </a>
        <?php endif; ?>

    </div>

    <!-- Quick Actions & Tasks Grid -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;">
        
        <!-- My Schedule Snapshot -->
        <div style="background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);padding:2rem;">
            <h3 style="font-size:1.6rem;font-weight:700;display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;color:var(--text-primary);">
                <i class="fas fa-calendar-check" style="color:var(--role-accent);"></i> Upcoming Schedule
            </h3>
            <?php
            // Fetch next 3 shifts
            $sql = "SELECT * FROM staff_shifts WHERE staff_id=? AND shift_date >= ? ORDER BY shift_date ASC LIMIT 3";
            $shifts = dbSelect($conn, $sql, "is", [$staff_id, $today]);
            if(empty($shifts)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem;">No upcoming shifts scheduled.</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach($shifts as $sf): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:1.2rem;background:var(--surface-2);border-radius:8px;border-left:4px solid var(--role-accent);">
                        <div>
                            <strong style="display:block;font-size:1.3rem;"><?=date('D, d M', strtotime($sf['shift_date']))?></strong>
                            <span style="color:var(--text-secondary);font-size:1.2rem;"><?=e(ucfirst($sf['shift_type']))?> Shift</span>
                        </div>
                        <div style="text-align:right;">
                            <span style="font-weight:600;color:var(--text-primary);"><?=date('H:i', strtotime($sf['start_time']))?> - <?=date('H:i', strtotime($sf['end_time']))?></span><br>
                            <span style="font-size:1.1rem;color:var(--text-muted);"><i class="fas fa-map-marker-alt"></i> <?=e($sf['location_ward_assigned'])?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications Snapshot -->
        <div style="background:var(--surface);border-radius:var(--radius-lg);border:1px solid var(--border);padding:2rem;">
            <h3 style="font-size:1.6rem;font-weight:700;display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem;color:var(--text-primary);">
                <i class="fas fa-bell" style="color:var(--warning);"></i> Recent Alerts
            </h3>
            <?php
            $notifs = dbSelect($conn, "SELECT message, created_at, is_read, type FROM staff_notifications WHERE staff_id=? ORDER BY id DESC LIMIT 4", "i", [$staff_id]);
            if(empty($notifs)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem;">All caught up! No recent alerts.</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:1.2rem;">
                <?php foreach($notifs as $n): 
                    $bg = ((int)$n['is_read'] === 0) ? 'rgba(79,70,229,.1)' : 'transparent';
                    $border = ((int)$n['is_read'] === 0) ? '1px solid rgba(79,70,229,.2)' : '1px solid transparent';
                    $iconMap = ['task'=>'fa-check-circle','alert'=>'fa-exclamation-circle','shift'=>'fa-calendar','message'=>'fa-envelope'];
                    $icon = $iconMap[$n['type']] ?? 'fa-info-circle';
                ?>
                    <div style="display:flex;align-items:flex-start;gap:1.5rem;padding:1.2rem;background:<?=$bg?>;border:<?=$border?>;border-radius:8px;">
                        <i class="fas <?=$icon?>" style="color:var(--role-accent);font-size:1.6rem;margin-top:.4rem;"></i>
                        <div style="flex:1;">
                            <p style="margin:0;font-size:1.3rem;font-weight:500;color:var(--text-primary);line-height:1.4;"><?=e($n['message'])?></p>
                            <span style="font-size:1.1rem;color:var(--text-muted);margin-top:.4rem;display:block;">
                                <?=date('d M h:i A', strtotime($n['created_at']))?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>
