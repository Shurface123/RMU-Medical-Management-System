<?php
session_start();
require_once '../db_conn.php';
require_once '../classes/AuditLogger.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$auditLogger = new AuditLogger($conn);

$message = '';
$error = '';

// Handle holiday actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_holiday':
            $holidayName = $_POST['holiday_name'];
            $holidayDate = $_POST['holiday_date'];
            $holidayType = $_POST['holiday_type'];
            $description = $_POST['description'] ?? '';
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            $query = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, description, recurring, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $userId = $_SESSION['user_id'];
            $stmt->bind_param("sssiii", $holidayName, $holidayDate, $holidayType, $recurring, $userId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_create', 'holidays', $stmt->insert_id, "Added holiday: $holidayName");
                $message = "Holiday added successfully!";
            } else {
                $error = "Failed to add holiday.";
            }
            break;
            
        case 'update_holiday':
            $holidayId = $_POST['holiday_id'];
            $holidayName = $_POST['holiday_name'];
            $holidayDate = $_POST['holiday_date'];
            $holidayType = $_POST['holiday_type'];
            $description = $_POST['description'] ?? '';
            $recurring = isset($_POST['recurring']) ? 1 : 0;
            
            $query = "UPDATE holidays SET holiday_name = ?, holiday_date = ?, holiday_type = ?, description = ?, recurring = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssiii", $holidayName, $holidayDate, $holidayType, $recurring, $holidayId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_update', 'holidays', $holidayId, "Updated holiday: $holidayName");
                $message = "Holiday updated successfully!";
            } else {
                $error = "Failed to update holiday.";
            }
            break;
            
        case 'delete_holiday':
            $holidayId = $_POST['holiday_id'];
            
            $query = "DELETE FROM holidays WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $holidayId);
            
            if ($stmt->execute()) {
                $auditLogger->logAction($_SESSION['user_id'], 'holiday_delete', 'holidays', $holidayId, "Deleted holiday ID: $holidayId");
                $message = "Holiday deleted successfully!";
            } else {
                $error = "Failed to delete holiday.";
            }
            break;
    }
}

// Get current year
$currentYear = date('Y');
$selectedYear = $_GET['year'] ?? $currentYear;

// Get holidays for selected year
$holidaysQuery = "SELECT h.*, u.user_name as created_by_name 
                  FROM holidays h
                  LEFT JOIN users u ON h.created_by = u.id
                  WHERE YEAR(holiday_date) = ? OR recurring = 1
                  ORDER BY holiday_date ASC";
$stmt = $conn->prepare($holidaysQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$holidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get holiday statistics
$statsQuery = "SELECT 
                COUNT(*) as total_holidays,
                SUM(CASE WHEN holiday_type = 'public' THEN 1 ELSE 0 END) as public_holidays,
                SUM(CASE WHEN holiday_type = 'medical' THEN 1 ELSE 0 END) as medical_holidays,
                SUM(CASE WHEN recurring = 1 THEN 1 ELSE 0 END) as recurring_holidays
               FROM holidays
               WHERE YEAR(holiday_date) = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $selectedYear);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Calendar | RMU Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/assets/css/logout.css">
    <style>
        :root { --primary: #8b5cf6; --primary-light: rgba(139, 92, 246, 0.15); }
        .staff-hero { display:flex; align-items:center; gap:2rem; padding:2.5rem; margin-bottom:2.5rem; background:linear-gradient(135deg, #8b5cf6, #6d28d9); border-radius:var(--radius-lg); color:#fff; position:relative; overflow:hidden; box-shadow:var(--shadow-md); }
        .hero-bg-icon { position:absolute; right:-20px; bottom:-40px; font-size:15rem; opacity:0.1; transform:rotate(-15deg); z-index:1; }
        .staff-hero-avatar { width:72px; height:72px; border-radius:50%; background:rgba(255,255,255,0.2); border:3px solid rgba(255,255,255,0.3); display:flex; align-items:center; justify-content:center; font-size:2.5rem; z-index:2; }
        .staff-hero-info { z-index:2; }
        .staff-hero-info h2 { font-size:2.2rem; font-weight:700; margin:0; font-family:'Outfit',sans-serif; }
        .staff-hero-info p { font-size:1.2rem; margin:0.4rem 0 0; opacity:0.9; }

        .holiday-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:1.5rem; margin-top:2rem; }
        .holiday-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:1.8rem; transition:all 0.3s ease; position:relative; overflow:hidden; display:flex; flex-direction:column; box-shadow:var(--shadow-sm); }
        .holiday-card:hover { transform:translateY(-5px); box-shadow:var(--shadow-md); border-color:var(--primary); }
        .holiday-card::before { content:''; position:absolute; left:0; top:0; width:6px; height:100%; background:var(--primary); opacity:0.8; }
        .holiday-card.medical::before { background:#ef4444; }
        .holiday-card.public::before { background:#3b82f6; }
        
        .holiday-date-circle { width:55px; height:55px; border-radius:14px; background:var(--surface-2); display:flex; flex-direction:column; align-items:center; justify-content:center; font-weight:700; margin-bottom:1.2rem; border:1px solid var(--border); }
        .holiday-date-circle span:first-child { font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); line-height:1; }
        .holiday-date-circle span:last-child { font-size:1.3rem; color:var(--text-primary); }

        .holiday-name { font-size:1.4rem; font-weight:700; color:var(--text-primary); margin-bottom:0.5rem; display:flex; align-items:center; gap:0.6rem; }
        .holiday-desc { font-size:1rem; color:var(--text-secondary); line-height:1.6; margin-bottom:1.5rem; flex-grow:1; }
        
        .holiday-meta { display:flex; flex-wrap:wrap; gap:0.6rem; margin-bottom:1.5rem; }
        .h-badge { padding:0.4rem 0.9rem; border-radius:20px; font-size:0.85rem; font-weight:600; text-transform:uppercase; display:flex; align-items:center; gap:0.4rem; }
        .h-badge-public { background:#dbeafe; color:#1e40af; }
        .h-badge-medical { background:#fee2e2; color:#991b1b; }
        .h-badge-recurring { background:#f3e8ff; color:#6b21a8; }

        .card-actions { display:flex; gap:0.8rem; border-top:1px solid var(--border); padding-top:1.2rem; }
        
        .btn-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:all 0.2s; border:none; }
        .btn-edit { background:var(--primary-light); color:var(--primary); }
        .btn-edit:hover { background:var(--primary); color:#fff; }
        .btn-delete { background:rgba(239, 68, 68, 0.1); color:#ef4444; }
        .btn-delete:hover { background:#ef4444; color:#fff; }

        .adm-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(8px); z-index:1000; align-items:center; justify-content:center; animation:fadeIn 0.3s ease; }
        .adm-modal.active { display:flex; }
        .adm-modal-content { background:var(--surface); border-radius:20px; width:95%; max-width:550px; padding:2.5rem; border:1px solid var(--border); box-shadow:var(--shadow-lg); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:1.2rem; margin-bottom:1.5rem; }
        .adm-form-group { margin-bottom:1.5rem; }
        .adm-form-group label { display:block; margin-bottom:0.6rem; font-weight:600; color:var(--text-secondary); font-size:0.95rem; }
        .adm-input { width:100%; padding:0.9rem 1.2rem; border-radius:12px; border:1.5px solid var(--border); background:var(--surface); color:var(--text-primary); font-size:1rem; outline:none; transition:all 0.2s; }
        .adm-input:focus { border-color:var(--primary); box-shadow:0 0 0 4px var(--primary-light); }
    </style>
</head>
<body data-theme="<?php echo $_SESSION['rmu_theme'] ?? 'light'; ?>">
    <?php $active_page = 'holidays'; include '../includes/_sidebar.php'; ?>

    <main class="adm-main">
        <div class="adm-topbar">
            <div class="adm-topbar-left">
                <button class="adm-menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <span class="adm-page-title"><i class="fas fa-calendar-alt"></i> Institutional Calendar</span>
            </div>
            <div class="adm-topbar-right">
                <div class="adm-topbar-datetime"><i class="far fa-calendar-alt"></i> <span><?php echo date('D, M d, Y'); ?></span></div>
                <button class="adm-theme-toggle" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>
                <div class="adm-avatar"><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?></div>
            </div>
        </div>

        <div class="adm-content" style="animation:fadeIn 0.4s ease;">
            <div class="staff-hero">
                <i class="fas fa-calendar-check hero-bg-icon"></i>
                <div class="staff-hero-avatar"><i class="fas fa-calendar-day"></i></div>
                <div class="staff-hero-info">
                    <h2>Holiday Management</h2>
                    <p>Configure public holidays and medical facility closures for <?php echo $selectedYear; ?>.</p>
                </div>
                <div style="margin-left:auto; display:flex; gap:1rem; z-index:2;">
                    <select class="adm-input" style="width:auto; padding:0.6rem 1rem;" onchange="window.location.href='?year=' + this.value">
                        <?php for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $selectedYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="btn btn-primary" onclick="openModal('addModal')" style="background:#fff; color:var(--primary); border:none; box-shadow:0 4px 15px rgba(0,0,0,0.15);">
                        <i class="fas fa-plus"></i> Add Holiday
                    </button>
                </div>
            </div>

            <div class="adm-stats-grid">
                <div class="adm-stat-card">
                    <div class="adm-stat-icon" style="background:linear-gradient(135deg, #8b5cf6, #6d28d9);"><i class="fas fa-calendar-alt"></i></div>
                    <div class="adm-stat-label">Total Holidays</div>
                    <div class="adm-stat-value"><?php echo $stats['total_holidays']; ?></div>
                    <div class="adm-stat-footer"><i class="fas fa-history"></i> Logged for year</div>
                </div>
                <div class="adm-stat-card">
                    <div class="adm-stat-icon" style="background:linear-gradient(135deg, #3b82f6, #1d4ed8);"><i class="fas fa-flag"></i></div>
                    <div class="adm-stat-label">Public Holidays</div>
                    <div class="adm-stat-value"><?php echo $stats['public_holidays']; ?></div>
                    <div class="adm-stat-footer"><i class="fas fa-globe"></i> National observance</div>
                </div>
                <div class="adm-stat-card">
                    <div class="adm-stat-icon" style="background:linear-gradient(135deg, #ef4444, #b91c1c);"><i class="fas fa-hospital-user"></i></div>
                    <div class="adm-stat-label">Facility Closures</div>
                    <div class="adm-stat-value"><?php echo $stats['medical_holidays']; ?></div>
                    <div class="adm-stat-footer"><i class="fas fa-lock"></i> Service restriction</div>
                </div>
                <div class="adm-stat-card">
                    <div class="adm-stat-icon" style="background:linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-sync-alt"></i></div>
                    <div class="adm-stat-label">Recurring</div>
                    <div class="adm-stat-value"><?php echo $stats['recurring_holidays']; ?></div>
                    <div class="adm-stat-footer"><i class="fas fa-calendar-check"></i> Annual repeats</div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="adm-alert adm-alert-success" style="margin-bottom:2rem;"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="holiday-grid">
                <?php if (empty($holidays)): ?>
                    <div style="grid-column:1/-1; text-align:center; padding:5rem; background:var(--surface); border-radius:20px; border:2px dashed var(--border);">
                        <i class="fas fa-calendar-times" style="font-size:4rem; color:var(--border); margin-bottom:1.5rem;"></i>
                        <h3>No Holidays Scheduled</h3>
                        <p style="color:var(--text-muted);">Add public holidays or facility closures for the selected year.</p>
                    </div>
                <?php else: 
                    foreach ($holidays as $h): 
                        $dt = strtotime($h['holiday_date']);
                ?>
                    <div class="holiday-card <?php echo $h['holiday_type']; ?>">
                        <div class="holiday-date-circle">
                            <span><?php echo date('M', $dt); ?></span>
                            <span><?php echo date('d', $dt); ?></span>
                        </div>
                        <h3 class="holiday-name">
                            <?php echo htmlspecialchars($h['holiday_name']); ?>
                            <?php if ($h['recurring']): ?><i class="fas fa-sync-alt" style="font-size:0.9rem; color:#8b5cf6;" title="Recurring Annual"></i><?php endif; ?>
                        </h3>
                        <p class="holiday-desc"><?php echo htmlspecialchars($h['description'] ?: 'No additional details provided for this event.'); ?></p>
                        
                        <div class="holiday-meta">
                            <span class="h-badge h-badge-<?php echo $h['holiday_type']; ?>">
                                <i class="fas <?php echo $h['holiday_type'] === 'public' ? 'fa-flag' : ($h['holiday_type'] === 'medical' ? 'fa-medkit' : 'fa-calendar'); ?>"></i>
                                <?php echo ucfirst($h['holiday_type']); ?>
                            </span>
                            <?php if ($h['recurring']): ?>
                                <span class="h-badge h-badge-recurring"><i class="fas fa-redo"></i> Recurring</span>
                            <?php endif; ?>
                        </div>

                        <div class="card-actions">
                            <button class="btn-icon btn-edit" onclick='editHoliday(<?php echo json_encode($h); ?>)' title="Edit Entry"><i class="fas fa-pen-to-square"></i></button>
                            <button class="btn-icon btn-delete" onclick="deleteHoliday(<?php echo $h['id']; ?>, '<?php echo htmlspecialchars($h['holiday_name']); ?>')" title="Delete Entry"><i class="fas fa-trash-can"></i></button>
                            <div style="margin-left:auto; font-size:0.8rem; color:var(--text-muted); display:flex; flex-direction:column; align-items:flex-end;">
                                <span>Logged by:</span>
                                <span style="font-weight:600;"><?php echo htmlspecialchars($h['created_by_name'] ?: 'Admin'); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="addModal" class="adm-modal">
        <div class="adm-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h3 style="margin:0; font-size:1.6rem;"><i class="fas fa-plus-circle" style="color:var(--primary);"></i> Add Holiday</h3>
                <button class="btn-icon btn-ghost" onclick="closeModal('addModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_holiday">
                <div class="adm-form-group">
                    <label>Holiday Event Name</label>
                    <input type="text" name="holiday_name" class="adm-input" required placeholder="e.g. Independence Day">
                </div>
                <div class="form-row">
                    <div class="adm-form-group">
                        <label>Date</label>
                        <input type="date" name="holiday_date" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label>Type</label>
                        <select name="holiday_type" class="adm-input" required>
                            <option value="public">Public Holiday</option>
                            <option value="medical">Facility Closure</option>
                            <option value="other">Other Event</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Description / Notes</label>
                    <textarea name="description" class="adm-input" rows="3" placeholder="Briefly describe the holiday or closure reason..."></textarea>
                </div>
                <div class="adm-form-group" style="display:flex; align-items:center; gap:0.8rem; background:var(--surface-2); padding:1rem; border-radius:12px;">
                    <input type="checkbox" name="recurring" id="add_recurring" style="width:20px; height:20px; cursor:pointer;">
                    <label for="add_recurring" style="margin:0; cursor:pointer;">Mark as Recurring (Annual observance on same date)</label>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Save Holiday</button>
                    <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="adm-modal">
        <div class="adm-modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem;">
                <h3 style="margin:0; font-size:1.6rem;"><i class="fas fa-edit" style="color:var(--primary);"></i> Edit Holiday</h3>
                <button class="btn-icon btn-ghost" onclick="closeModal('editModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_holiday">
                <input type="hidden" name="holiday_id" id="edit_holiday_id">
                <div class="adm-form-group">
                    <label>Holiday Event Name</label>
                    <input type="text" name="holiday_name" id="edit_holiday_name" class="adm-input" required>
                </div>
                <div class="form-row">
                    <div class="adm-form-group">
                        <label>Date</label>
                        <input type="date" name="holiday_date" id="edit_holiday_date" class="adm-input" required>
                    </div>
                    <div class="adm-form-group">
                        <label>Type</label>
                        <select name="holiday_type" id="edit_holiday_type" class="adm-input" required>
                            <option value="public">Public Holiday</option>
                            <option value="medical">Facility Closure</option>
                            <option value="other">Other Event</option>
                        </select>
                    </div>
                </div>
                <div class="adm-form-group">
                    <label>Description / Notes</label>
                    <textarea name="description" id="edit_description" class="adm-input" rows="3"></textarea>
                </div>
                <div class="adm-form-group" style="display:flex; align-items:center; gap:0.8rem; background:var(--surface-2); padding:1rem; border-radius:12px;">
                    <input type="checkbox" name="recurring" id="edit_recurring" style="width:20px; height:20px; cursor:pointer;">
                    <label for="edit_recurring" style="margin:0; cursor:pointer;">Mark as Recurring (Annual observance)</label>
                </div>
                <div style="display:flex; gap:1rem; margin-top:1rem;">
                    <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Update Entry</button>
                    <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete_holiday">
        <input type="hidden" name="holiday_id" id="delete_holiday_id">
    </form>

    <script>
        const sidebar = document.getElementById('admSidebar');
        const overlay = document.getElementById('admOverlay');
        document.getElementById('menuToggle')?.addEventListener('click', () => { sidebar.classList.toggle('active'); overlay.classList.toggle('active'); });
        overlay?.addEventListener('click', () => { sidebar.classList.remove('active'); overlay.classList.remove('active'); });

        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

        function editHoliday(h) {
            document.getElementById('edit_holiday_id').value = h.id;
            document.getElementById('edit_holiday_name').value = h.holiday_name;
            document.getElementById('edit_holiday_date').value = h.holiday_date;
            document.getElementById('edit_holiday_type').value = h.holiday_type;
            document.getElementById('edit_description').value = h.description || '';
            document.getElementById('edit_recurring').checked = h.recurring == 1;
            openModal('editModal');
        }

        function deleteHoliday(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This will remove it from all payroll and scheduling calculations.`)) {
                document.getElementById('delete_holiday_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        const themeIcon = document.getElementById('themeIcon');
        document.getElementById('themeToggle')?.addEventListener('click', () => {
            const html = document.documentElement;
            const t = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', t);
            localStorage.setItem('rmu_theme', t);
            themeIcon.className = t === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        });

        window.onclick = e => { if (e.target.classList.contains('adm-modal')) e.target.classList.remove('active'); };
    </script>
</body>
</html>
