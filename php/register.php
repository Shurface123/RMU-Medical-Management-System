<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://juniv.edu/images/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../css/main.css">
    
    <style>
        :root {
            --primary-color: #2F80ED;
            --primary-dark: #2366CC;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --text-dark: #2c3e50;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2F80ED 0%, #56CCF2 50%, #2F80ED 100%);
            padding: 3rem 0;
        }

        .register-container {
            background: var(--white);
            padding: 4rem 3rem;
            border-radius: 24px;
            box-shadow: 0px 20px 60px rgba(47, 128, 237, 0.2);
            width: 90%;
            max-width: 550px;
            animation: slideIn 0.5s ease-out;
            margin: 2rem 0;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .register-header .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.3);
        }

        .register-header .logo-icon i {
            font-size: 4rem;
            color: var(--white);
        }

        .register-header h1 {
            font-size: 2.8rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .register-header p {
            font-size: 1.5rem;
            color: #7f8c8d;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.6rem;
            color: #7f8c8d;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem 1.5rem 1.2rem 4.2rem;
            font-size: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 24px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(47, 128, 237, 0.1);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%237f8c8d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.5rem center;
        }

        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 0.8rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: var(--accent-color);
        }

        .password-strength-bar.medium {
            width: 66%;
            background: #f39c12;
        }

        .password-strength-bar.strong {
            width: 100%;
            background: var(--success-color);
        }

        .password-hint {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-top: 0.5rem;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            font-size: 1.4rem;
            color: var(--text-dark);
            margin: 0;
            cursor: pointer;
        }

        .error-message,
        .success-message {
            padding: 1.2rem 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 2rem;
            font-size: 1.4rem;
            display: none;
        }

        .error-message {
            background: #fee;
            color: var(--accent-color);
            border-left: 4px solid var(--accent-color);
        }

        .success-message {
            background: #efe;
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .error-message.show,
        .success-message.show {
            display: block;
        }

        .btn-register {
            width: 100%;
            padding: 1.5rem;
            font-size: 1.6rem;
            font-weight: 600;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: var(--white);
            border: none;
            border-radius: 24px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0px 15px 40px rgba(47, 128, 237, 0.4);
        }

        .btn-register:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }

        .register-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .register-footer p {
            font-size: 1.4rem;
            color: #7f8c8d;
        }

        .register-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .register-footer a:hover {
            text-decoration: underline;
        }

        .back-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 100;
        }

        .back-home a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.2);
            transition: all 0.3s;
        }

        .back-home a:hover {
            background: var(--white);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .register-container {
                padding: 3rem 2rem;
            }

            .register-header h1 {
                font-size: 2.4rem;
            }
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <div class="back-home">
        <a href="/RMU-Medical-Management-System/html/index.html">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
    </div>

    <!-- Register Container -->
    <div class="register-container">
        <div class="register-header">
            <div class="logo-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h1>Create Account</h1>
            <p>Join RMU Medical Sickbay</p>
        </div>

        <!-- Error/Success Messages -->
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>

        <div class="success-message" id="successMessage">
            <i class="fas fa-check-circle"></i>
            <span id="successText"></span>
        </div>

        <!-- Registration Form -->
        <form action="register_handler.php" method="POST" id="registerForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="fullname" 
                               name="fullname" 
                               class="form-control" 
                               placeholder="John Doe"
                               required>
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="john@example.com"
                               required>
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-wrapper">
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="0501234567"
                               required>
                        <i class="fas fa-phone"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="johndoe"
                               required>
                        <i class="fas fa-at"></i>
                    </div>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter strong password"
                           required>
                    <i class="fas fa-lock"></i>
                </div>
                <div class="password-strength">
                    <div class="password-strength-bar" id="strengthBar"></div>
                </div>
                <p class="password-hint">Use at least 8 characters with letters and numbers</p>
            </div>

            <div class="form-group full-width">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           class="form-control" 
                           placeholder="Re-enter password"
                           required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="role">Register As</label>
                <div class="input-wrapper">
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="patient">Patient</option>
                        <option value="doctor">Doctor</option>
                        <option value="pharmacist">Pharmacist</option>
                    </select>
                    <i class="fas fa-user-tag"></i>
                </div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the Terms and Conditions</label>
            </div>

            <button type="submit" class="btn-register" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="register-footer">
            <p>Already have an account? <a href="index.php">Login here</a></p>
        </div>
    </div>

    <script>
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 1) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;

            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');

            if (password !== confirmPassword) {
                e.preventDefault();
                errorText.textContent = 'Passwords do not match';
                errorMessage.classList.add('show');
                return;
            }

            if (password.length < 8) {
                e.preventDefault();
                errorText.textContent = 'Password must be at least 8 characters long';
                errorMessage.classList.add('show');
                return;
            }

            if (!terms) {
                e.preventDefault();
                errorText.textContent = 'You must agree to the Terms and Conditions';
                errorMessage.classList.add('show');
                return;
            }
        });

        // Clear error on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                document.getElementById('errorMessage').classList.remove('show');
            });
        });

        // Display messages from URL
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const success = urlParams.get('success');

        if (error) {
            document.getElementById('errorText').textContent = error;
            document.getElementById('errorMessage').classList.add('show');
        }

        if (success) {
            document.getElementById('successText').textContent = success;
            document.getElementById('successMessage').classList.add('show');
        }
    </script>
</body>
</html>
