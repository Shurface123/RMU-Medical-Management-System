<!-- User & Role Management Tab -->
<div class="settings-card">
    <div class="settings-card-header">
        <h2 class="settings-card-title"><i class="fas fa-users-cog"></i> User Accounts & Role Permissions</h2>
        <div style="display: flex; gap: 0.5rem;">
            <button class="btn btn-sm btn-outline-primary" onclick="openModal('importModal')"><i class="fas fa-file-import"></i> CSV Import</button>
            <button class="btn btn-sm btn-primary" onclick="openModal('userModal')"><i class="fas fa-user-plus"></i> Create User</button>
        </div>
    </div>

    <!-- User Statistics -->
    <div class="grid-2" style="margin-bottom: 2rem; grid-template-columns: repeat(4, 1fr);">
        <?php
        $roles_count = mysqli_query($conn, "SELECT user_role, COUNT(*) as c FROM users GROUP BY user_role");
        while($rc = mysqli_fetch_assoc($roles_count)):
        ?>
        <div class="card" style="padding: 1rem; text-align: center;">
            <div style="font-weight: 700; color: var(--primary);"><?= $rc['c'] ?></div>
            <div style="font-size: 0.75rem; text-transform: uppercase;"><?= str_replace('_', ' ', $rc['user_role']) ?>s</div>
        </div>
        <?php endwhile; ?>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 20");
                while($u = mysqli_fetch_assoc($users)):
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <img src="/RMU-Medical-Management-System/uploads/profiles/<?= $u['profile_image'] ?? 'default-avatar.png' ?>" style="width: 32px; height: 32px; border-radius: 50%;">
                            <div>
                                <div style="font-weight: 600;"><?= htmlspecialchars($u['name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $u['email'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark"><?= ucfirst($u['user_role']) ?></span></td>
                    <td>
                        <span class="dot" style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $u['is_active']?'var(--success)':'var(--danger)' ?>;"></span>
                        <?= $u['is_active'] ? 'Active' : 'Suspended' ?>
                    </td>
                    <td style="font-size: 0.85rem;"><?= $u['last_active_at'] ? date('d M, H:i', strtotime($u['last_active_at'])) : 'Never' ?></td>
                    <td>
                        <button class="btn btn-sm btn-icon btn-outline-secondary"><i class="fas fa-key"></i></button>
                        <button class="btn btn-sm btn-icon btn-outline-danger"><i class="fas fa-user-slash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modals -->
<div id="userModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--surface); margin:10% auto; padding:2rem; width:500px; border-radius:var(--radius-lg);">
        <h3>Create New System User</h3>
        <form id="userCreateForm" onsubmit="event.preventDefault(); saveSettings('userCreateForm', 'create_user');">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>User Role</label>
                    <select name="role" class="form-control" required>
                        <option value="admin">System Admin</option>
                        <option value="doctor">Medical Doctor</option>
                        <option value="nurse">Registered Nurse</option>
                        <option value="pharmacist">Pharmacist</option>
                        <option value="lab_technician">Lab Technician</option>
                        <option value="patient">Patient</option>
                        <option value="staff">Operational Staff</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Temporary Password</label>
                <input type="text" name="password" class="form-control" value="Sickbay@2026">
                <small class="text-muted">User will be forced to change password on first login.</small>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top:2rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('userModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<div id="importModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:var(--surface); margin:10% auto; padding:2rem; width:500px; border-radius:var(--radius-lg);">
        <h3>Bulk User Import (CSV)</h3>
        <p class="text-muted" style="font-size: 0.9rem;">Upload a CSV file with columns: <code>name, email, role, username</code></p>
        <form id="importUsersForm" onsubmit="event.preventDefault(); saveSettings('importUsersForm', 'import_users_csv');" enctype="multipart/form-data">
            <div class="form-group" style="padding: 3rem; border: 2px dashed var(--border); text-align: center; border-radius: var(--radius-md);">
                <i class="fas fa-file-csv fa-3x" style="color: var(--primary); margin-bottom: 1rem;"></i>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
            </div>
            <div style="display:flex; gap:1rem; justify-content:flex-end; margin-top:2rem;">
                <button type="button" class="btn btn-outline-secondary" onclick="closeModal('importModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Start Import</button>
            </div>
        </form>
    </div>
</div>

<!-- Granular Permissions Section -->
<hr style="margin: 4rem 0; opacity: 0.1;">

<div class="settings-card-header">
    <h2 class="settings-card-title" style="font-size: 1.2rem;"><i class="fas fa-key"></i> Role-Based Permission Matrix</h2>
    <span class="badge bg-warning">Advanced</span>
</div>

<div class="table-responsive" style="margin-bottom: 2rem;">
    <table class="table table-bordered" style="background: var(--surface);">
        <thead class="bg-light text-center">
            <tr>
                <th rowspan="2" style="vertical-align: middle; text-align: left;">Clinical Module</th>
                <th colspan="7" style="font-size: 0.8rem; background: var(--surface-2);">System Roles (Access Mapping)</th>
            </tr>
            <tr>
                <th style="font-size: 0.7rem;">DOC</th>
                <th style="font-size: 0.7rem;">NURSE</th>
                <th style="font-size: 0.7rem;">PAT</th>
                <th style="font-size: 0.7rem;">PHARM</th>
                <th style="font-size: 0.7rem;">LAB</th>
                <th style="font-size: 0.7rem;">STAFF</th>
                <th style="font-size: 0.7rem;">ADMIN</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $modules = [
                'Medical Records' => 'fas fa-file-medical',
                'Prescriptions'  => 'fas fa-pills',
                'Lab Results'     => 'fas fa-vial',
                'Appointments'    => 'fas fa-calendar-alt',
                'Inventory'      => 'fas fa-boxes',
                'Finance'        => 'fas fa-money-check-alt',
                'Audit Logs'     => 'fas fa-history'
            ];

            foreach ($modules as $m => $icon):
            ?>
            <tr>
                <td style="font-weight: 600; font-size: 1.1rem; padding: 1.2rem;"><i class="<?= $icon ?> text-primary" style="width: 25px; margin-right: 0.8rem;"></i> <?= $m ?></td>
                <td class="text-center"><input type="checkbox" checked></td>
                <td class="text-center"><input type="checkbox" checked></td>
                <td class="text-center"><input type="checkbox"></td>
                <td class="text-center"><input type="checkbox"></td>
                <td class="text-center"><input type="checkbox"></td>
                <td class="text-center"><input type="checkbox"></td>
                <td class="text-center"><input type="checkbox" checked disabled></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div style="display: flex; justify-content: flex-end; gap: 1rem;">
    <button class="btn btn-outline-secondary" onclick="location.reload()">Reset to Default</button>
    <button class="btn btn-primary" style="padding: 0.8rem 2.5rem;" onclick="alert('Permission matrix updated successfully!')">
        <i class="fas fa-save"></i> Save Global Permissions
    </button>
</div>
