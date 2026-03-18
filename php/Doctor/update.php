<?php
session_start();
require_once '../db_conn.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Doctor ID missing.'); window.location.href='/RMU-Medical-Management-System/php/Doctor/doctor.php';</script>";
    exit;
}

$doc_uid = (int)$_GET['id'];
$success_msg = '';
$error_msg   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['name']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $phone  = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $spec       = mysqli_real_escape_string($conn, $_POST['specialization']);
    $experience = (int)$_POST['experience_years'];
    $license    = mysqli_real_escape_string($conn, $_POST['license_number']);
    $avail_days = mysqli_real_escape_string($conn, $_POST['available_days']);
    $avail_hrs  = mysqli_real_escape_string($conn, $_POST['available_hours']);

    mysqli_begin_transaction($conn);
    try {
        // Update users table
        $u_stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
        $u_stmt->bind_param("sssi", $name, $email, $phone, $doc_uid);
        $u_stmt->execute();
        
        // Update doctors table
        $d_stmt = $conn->prepare("UPDATE doctors SET specialization=?, experience_years=?, license_number=?, available_days=?, available_hours=? WHERE user_id=?");
        $d_stmt->bind_param("sisssi", $spec, $experience, $license, $avail_days, $avail_hrs, $doc_uid);
        $d_stmt->execute();
        
        mysqli_commit($conn);
        $success_msg = "Doctor record updated successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error updating doctor: " . $e->getMessage();
    }
}

// Fetch current details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.is_active, 
           d.doctor_id, d.specialization, d.experience_years, d.license_number, d.available_days, d.available_hours 
    FROM users u 
    LEFT JOIN doctors d ON u.id = d.user_id 
    WHERE u.id = ?");
$stmt->bind_param("i", $doc_uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo "<script>alert('Doctor not found.'); window.location.href='/RMU-Medical-Management-System/php/Doctor/doctor.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - RMU Medical Sickbay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <style>
        .edit-container { max-width: 800px; margin: 3rem auto; background: var(--surface); padding: 2.5rem; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { width: 100%; }
        .form-label { display: block; font-weight: 600; font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 0.6rem; text-transform: uppercase; letter-spacing: 1px; }
        .form-control { width: 100%; padding: 1rem 1.2rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-family: inherit; font-size: 1.1rem; color: var(--text-primary); background: var(--surface); }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(47,128,237,0.1); }
        .btn-primary { background: var(--primary); color: white; border: none; padding: 1rem 2rem; border-radius: var(--radius-md); font-weight: 600; font-size: 1.1rem; cursor: pointer; transition: background 0.3s; }
        .btn-primary:hover { background: #246ac2; }
        .alert { padding: 1.2rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 500; font-size: 1.1rem; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); text-decoration: none; margin-bottom: 1rem; font-weight: 600; font-size: 1.1rem; }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div style="padding: 2rem;">
        <a href="/RMU-Medical-Management-System/php/Doctor/doctor.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Doctors</a>
        <div class="edit-container">
            <h2 style="font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--text-primary);">Edit Doctor Status</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1.1rem;">Update profile for Doctor ID: <strong><?php echo htmlspecialchars($row['doctor_id'] ?? $row['id']); ?></strong></p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <h4 style="margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-user-md"></i> Core Identity</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone']); ?>" required>
                    </div>
                </div>

                <hr style="border:0; border-top: 1px solid var(--border); margin: 2rem 0;">
                <h4 style="margin-bottom: 1rem; color: #1abc9c;"><i class="fas fa-stethoscope"></i> Clinical Assignment</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($row['specialization']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Medical License Number</label>
                        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($row['license_number']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Experience (Years)</label>
                        <input type="number" name="experience_years" class="form-control" value="<?php echo (int)$row['experience_years']; ?>" min="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Available Days</label>
                        <input type="text" name="available_days" class="form-control" value="<?php echo htmlspecialchars($row['available_days']); ?>" placeholder="E.g. Mon-Fri">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Available Hours</label>
                        <input type="text" name="available_hours" class="form-control" value="<?php echo htmlspecialchars($row['available_hours']); ?>" placeholder="E.g. 08:00 AM - 04:00 PM">
                    </div>
                </div>
                
                <div style="margin-top: 2.5rem; display: flex; gap: 1rem;">
                    <button type="submit" name="update_doctor" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <!-- Deactivate option in typical system is a separate button targeting the auth module -->
                </div>
            </form>
        </div>
    </div>
</body>
</html>
