<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - RMU Medical Sickbay</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <link rel="shortcut icon" type="image/png" href="/RMU-Medical-Management-System/image/logo-ju-small.png">
    <style>
        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; margin: 0; padding: 0; }
        body { background: #f3f4f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .signup-container { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; width: 100%; max-width: 500px; padding: 2.5rem; }
        .text-center { text-align: center; }
        .logo { width: 80px; margin-bottom: 1rem; }
        h2 { color: #1f2937; font-size: 1.8rem; margin-bottom: 0.5rem; }
        p.subtitle { color: #6b7280; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.2rem; }
        label { display: block; margin-bottom: 0.4rem; color: #374151; font-weight: 500; font-size: 0.95rem; }
        input[type="text"], input[type="email"], input[type="password"], select { width: 100%; padding: 0.8rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; transition: border-color 0.2s; }
        input:focus, select:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-primary { width: 100%; padding: 0.9rem; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 1rem; }
        .btn-primary:hover { background: #2563eb; }
        .login-link { text-align: center; margin-top: 1.5rem; color: #6b7280; font-size: 0.95rem; }
        .login-link a { color: #3b82f6; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="text-center">
            <img src="/RMU-Medical-Management-System/image/logo-ju-small.png" alt="RMU Logo" class="logo">
            <h2>Create an Account</h2>
            <p class="subtitle">Join the RMU Medical Sickbay portal</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="signup-check.php" method="post">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="John Doe" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="uname">Email Address (Username)</label>
                <input type="email" id="uname" name="uname" required placeholder="john.doe@rmu.edu" value="<?php echo isset($_GET['uname']) ? htmlspecialchars($_GET['uname']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" required placeholder="0501234567" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="patient">Patient / Student</option>
                    <option value="staff">RMU Staff</option>
                </select>
                <p style="font-size: 0.8rem; color: #6b7280; margin-top: 0.3rem;">Notice: Doctor and Admin accounts must be created by an Administrator.</p>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label for="re_password">Confirm Password</label>
                <input type="password" id="re_password" name="re_password" required placeholder="Re-enter password">
            </div>

            <button type="submit" class="btn btn-primary"><span class="btn-text">Register Account</span></button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>
</body>
</html>
