<?php
session_start();
require_once '../db_conn.php';

// Check if admin or doctor is logged in
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'doctor'])) {
    header("Location: /RMU-Medical-Management-System/php/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "<script>alert('Patient ID missing.'); window.location.href='/RMU-Medical-Management-System/php/patient/patient.php';</script>";
    exit;
}

$patient_uid = (int)$_GET['id'];
$success_msg = '';
$error_msg   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $name   = mysqli_real_escape_string($conn, $_POST['name']);
    $email  = mysqli_real_escape_string($conn, $_POST['email']);
    $phone  = mysqli_real_escape_string($conn, $_POST['phone']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $dob    = mysqli_real_escape_string($conn, $_POST['dob']);
    
    $blood_group = mysqli_real_escape_string($conn, $_POST['blood_group']);
    $allergies   = mysqli_real_escape_string($conn, $_POST['allergies']);
    $chronics    = mysqli_real_escape_string($conn, $_POST['chronic_conditions']);
    $emg_name    = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
    $emg_phone   = mysqli_real_escape_string($conn, $_POST['emergency_contact_phone']);

    mysqli_begin_transaction($conn);
    try {
        // Update users table
        $u_stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, gender=?, date_of_birth=? WHERE id=?");
        $u_stmt->bind_param("sssssi", $name, $email, $phone, $gender, $dob, $patient_uid);
        $u_stmt->execute();
        
        // Update patients table
        $p_stmt = $conn->prepare("UPDATE patients SET blood_group=?, allergies=?, chronic_conditions=?, emergency_contact_name=?, emergency_contact_phone=? WHERE user_id=?");
        $p_stmt->bind_param("sssssi", $blood_group, $allergies, $chronics, $emg_name, $emg_phone, $patient_uid);
        $p_stmt->execute();
        
        mysqli_commit($conn);
        $success_msg = "Patient record updated successfully.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error_msg = "Error updating patient: " . $e->getMessage();
    }
}

// Fetch current details
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.is_active, 
           p.patient_id, p.blood_group, p.allergies, p.chronic_conditions, p.emergency_contact_name, p.emergency_contact_phone 
    FROM users u 
    LEFT JOIN patients p ON u.id = p.user_id 
    WHERE u.id = ?");
$stmt->bind_param("i", $patient_uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
    echo "<script>alert('Patient not found.'); window.location.href='/RMU-Medical-Management-System/php/patient/patient.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - RMU Medical Sickbay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/RMU-Medical-Management-System/css/admin-dashboard.css">
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
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
        .back-link:hover { color: var(--primary); }
    </style>
</head>
<body style="background: var(--bg-primary);">
    <div style="padding: 2rem;">
        <a href="/RMU-Medical-Management-System/php/patient/patient.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Patients</a>
        <div class="edit-container">
            <h2 style="font-size: 2.2rem; margin-bottom: 0.5rem; color: var(--text-primary);">Edit Patient Record</h2>
            <p style="color: var(--text-secondary); margin-bottom: 2rem; font-size: 1.1rem;">Update biographical and clinical information for patient ID: <strong><?php echo htmlspecialchars($row['patient_id'] ?? $row['id']); ?></strong></p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <h4 style="margin-bottom: 1rem; color: var(--primary);"><i class="fas fa-user"></i> Personal Details</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($row['date_of_birth']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?php echo $row['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $row['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <?php 
                            $bg_opts = ['A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown'];
                            foreach($bg_opts as $bg) {
                                $sel = rtrim($row['blood_group']) === trim($bg) ? 'selected' : '';
                                echo "<option value=\"$bg\" $sel>$bg</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <hr style="border:0; border-top: 1px solid var(--border); margin: 2rem 0;">
                <h4 style="margin-bottom: 1rem; color: var(--danger);"><i class="fas fa-heartbeat"></i> Medical Notes & Emergency Contacts</h4>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Known Allergies</label>
                        <input type="text" name="allergies" class="form-control" value="<?php echo htmlspecialchars($row['allergies']); ?>" placeholder="E.g., Penicillin, Peanuts">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Chronic Conditions</label>
                        <input type="text" name="chronic_conditions" class="form-control" value="<?php echo htmlspecialchars($row['chronic_conditions']); ?>" placeholder="E.g., Asthma, Diabetes">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo htmlspecialchars($row['emergency_contact_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo htmlspecialchars($row['emergency_contact_phone']); ?>">
                    </div>
                </div>
                
                <div style="margin-top: 2.5rem; display: flex; gap: 1rem; align-items: center;">
                    <button type="submit" name="update_patient" class="btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    <!-- Deactivate option logic omitted per requirements; uses separate delete file -->
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <a href="Delete.php?id=<?php echo $row['id']; ?>" class="back-link" style="color: #e74c3c; margin: 0;" onclick="return confirm('WARNING: Are you sure you want to deactivate/delete this patient?');"><i class="fas fa-trash-alt"></i> Deactivate Patient</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
