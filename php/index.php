<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RMU Medical Sickbay</title>
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
            --primary-color: #16a085;
            --primary-dark: #138871;
            --accent-color: #e74c3c;
            --text-dark: #2c3e50;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #16a085 0%, #1abc9c 50%, #16a085 100%);
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .login-container {
            position: relative;
            z-index: 10;
            background: var(--white);
            padding: 4rem 3rem;
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 90%;
            max-width: 450px;
            animation: slideIn 0.5s ease-out;
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

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-header .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #16a085, #1abc9c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 5px 15px rgba(22, 160, 133, 0.3);
        }

        .login-header .logo-icon i {
            font-size: 4rem;
            color: var(--white);
        }

        .login-header h1 {
            font-size: 2.8rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .login-header p {
            font-size: 1.5rem;
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
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
            font-size: 1.8rem;
            color: #7f8c8d;
            transition: color 0.3s;
        }

        .form-control {
            width: 100%;
            padding: 1.3rem 1.5rem 1.3rem 4.5rem;
            font-size: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 1rem;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(22, 160, 133, 0.1);
        }

        .form-control:focus + i {
            color: var(--primary-color);
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%237f8c8d' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1.5rem center;
        }

        .error-message {
            background: #fee;
            color: var(--accent-color);
            padding: 1.2rem 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 2rem;
            font-size: 1.4rem;
            border-left: 4px solid var(--accent-color);
            display: none;
        }

        .error-message.show {
            display: block;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .btn-login {
            width: 100%;
            padding: 1.5rem;
            font-size: 1.6rem;
            font-weight: 600;
            background: linear-gradient(135deg, #16a085, #1abc9c);
            color: var(--white);
            border: none;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(22, 160, 133, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22, 160, 133, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .login-footer p {
            font-size: 1.4rem;
            color: #7f8c8d;
            margin-bottom: 1rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .login-footer a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .back-home {
            position: absolute;
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
            border-radius: 5rem;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }

        .back-home a:hover {
            background: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                padding: 3rem 2rem;
            }

            .login-header h1 {
                font-size: 2.4rem;
            }

            .back-home {
                top: 1rem;
                left: 1rem;
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

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-header">
            <div class="logo-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1>Welcome Back</h1>
            <p>Login to RMU Medical Sickbay</p>
        </div>

        <!-- Error Message -->
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>

        <!-- Login Form -->
        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label for="uname">Username</label>
                <div class="input-wrapper">
                    <input type="text" 
                           id="uname" 
                           name="uname" 
                           class="form-control" 
                           placeholder="Enter your username"
                           required>
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password"
                           required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="role">Login As</label>
                <div class="input-wrapper">
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="admin">Administrator</option>
                        <option value="doctor">Doctor</option>
                        <option value="patient">Patient</option>
                        <option value="pharmacist">Pharmacist</option>
                    </select>
                    <i class="fas fa-user-tag"></i>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="/RMU-Medical-Management-System/html/index.html">Return to Homepage</a></p>
        </div>
    </div>

    <script>
        // Display error message from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        
        if (error) {
            const errorMessage = document.getElementById('errorMessage');
            const errorText = document.getElementById('errorText');
            errorText.textContent = error;
            errorMessage.classList.add('show');
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('uname').value.trim();
            const password = document.getElementById('password').value.trim();
            const role = document.getElementById('role').value;

            if (!username || !password || !role) {
                e.preventDefault();
                const errorMessage = document.getElementById('errorMessage');
                const errorText = document.getElementById('errorText');
                errorText.textContent = 'Please fill in all fields';
                errorMessage.classList.add('show');
            }
        });

        // Clear error message on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                document.getElementById('errorMessage').classList.remove('show');
            });
        });
    </script>
</body>
</html>
