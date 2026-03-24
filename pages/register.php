<?php
// pages/register.php
include __DIR__ . '/../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif (!$terms) {
        $error = "You must agree to the Terms & Conditions";
    } else {
        // Check if email already exists
        $check_email = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $sql = "INSERT INTO users (full_name, email, password, phone, role) 
                    VALUES ('$full_name', '$email', '$hashed_password', '$phone', '$role')";
            
            if (mysqli_query($conn, $sql)) {
                $user_id = mysqli_insert_id($conn);
                
                // If role is patient, create patient record
                if ($role == 'patient') {
                    $sql_patient = "INSERT INTO patients (user_id) VALUES ($user_id)";
                    mysqli_query($conn, $sql_patient);
                }
                
                // If role is doctor, create doctor record
                if ($role == 'doctor') {
                    $sql_doctor = "INSERT INTO doctors (user_id, specialization) VALUES ($user_id, 'General Physician')";
                    mysqli_query($conn, $sql_doctor);
                }
                
                $success = "Registration successful! <a href='login.php' style='color:#f0abfc; font-weight:bold;'>Login here</a>";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MediConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0a0a0f;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        /* Animated purple orbs */
        body::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float1 20s infinite alternate;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            animation: float2 20s infinite alternate;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-50px, -50px) scale(1.2); }
        }

        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(50px, 50px) scale(1.2); }
        }

        .register-wrapper {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: rgba(18, 18, 28, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(147, 51, 234, 0.3);
            position: relative;
            z-index: 1;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Left side - Branding */
        .brand-section {
            background: linear-gradient(145deg, rgba(88, 28, 135, 0.9), rgba(22, 10, 38, 0.95));
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(147, 51, 234, 0.3);
        }

        .brand-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .brand-logo {
            position: relative;
            z-index: 2;
        }

        .brand-logo h1 {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.5);
        }

        .brand-logo h1 i {
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }

        .brand-logo p {
            opacity: 0.9;
            font-size: 16px;
            color: #d8b4fe;
        }

        .brand-content {
            position: relative;
            z-index: 2;
            margin: 40px 0;
        }

        .brand-content h2 {
            font-size: 42px;
            margin-bottom: 40px;
            line-height: 1.2;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .brand-content h2 span {
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 48px;
            display: block;
            margin-top: 10px;
            text-shadow: 0 0 30px rgba(232, 121, 249, 0.5);
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            animation: fadeInLeft 0.5s ease-out forwards;
            opacity: 0;
        }

        .feature-item:nth-child(1) { animation-delay: 0.2s; }
        .feature-item:nth-child(2) { animation-delay: 0.4s; }
        .feature-item:nth-child(3) { animation-delay: 0.6s; }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 28px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(147, 51, 234, 0.4);
            color: #e9d5ff;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .feature-text h4 {
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: 600;
            color: white;
        }

        .feature-text p {
            opacity: 0.9;
            font-size: 14px;
            color: #d8b4fe;
        }

        .brand-footer {
            border-top: 1px solid rgba(147, 51, 234, 0.3);
            padding-top: 20px;
            font-size: 14px;
            color: #c084fc;
            position: relative;
            z-index: 2;
        }

        /* Right side - Form */
        .form-section {
            padding: 60px 50px;
            background: rgba(18, 18, 28, 0.9);
            backdrop-filter: blur(10px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .form-header p {
            color: #a78bfa;
            font-size: 16px;
        }

        .form-header strong {
            color: #f0abfc;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d8b4fe;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group label i {
            color: #c084fc;
            margin-right: 8px;
            font-size: 16px;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(147, 51, 234, 0.3);
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            backdrop-filter: blur(5px);
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            background: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.15), 0 0 20px rgba(168, 85, 247, 0.3);
        }

        .form-control:hover {
            border-color: #a855f7;
            background: rgba(0, 0, 0, 0.4);
        }

        .form-control::placeholder {
            color: #6b7280;
            opacity: 0.7;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23a855f7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 16px;
        }

        .password-hint {
            display: block;
            margin-top: 8px;
            color: #94a3b8;
            font-size: 12px;
        }

        .password-hint i {
            color: #c084fc;
            margin-right: 5px;
        }

        .password-strength {
            height: 6px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 10px;
            margin-top: 10px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 10px;
        }

        .terms-group {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 25px 0;
            padding: 15px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 12px;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .terms-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #a855f7;
        }

        .terms-group label {
            color: #d8b4fe;
            font-size: 14px;
            margin: 0;
            text-transform: none;
            letter-spacing: normal;
            font-weight: normal;
        }

        .terms-group a {
            color: #f0abfc;
            text-decoration: none;
            font-weight: 600;
        }

        .terms-group a:hover {
            text-decoration: underline;
            color: #e879f9;
        }

        .btn-register {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 16px 30px;
            width: 100%;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(147, 51, 234, 0.3);
        }

        .btn-register i {
            margin-right: 10px;
            font-size: 20px;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-register:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(147, 51, 234, 0.5);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
            border: 1px solid transparent;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        .alert i {
            font-size: 20px;
        }

        .alert-danger {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border-color: #7f1d1d;
        }

        .alert-success {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border-color: #065f46;
        }

        .alert-success a {
            color: #f0abfc;
            text-decoration: underline;
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #94a3b8;
        }

        .login-link a {
            color: #c084fc;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin-left: 5px;
        }

        .login-link a:hover {
            text-decoration: underline;
            color: #e879f9;
        }

        @media (max-width: 992px) {
            .register-wrapper {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .brand-section {
                display: none;
            }
            
            .form-section {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-wrapper">
        <!-- Left side - Branding -->
        <div class="brand-section">
            <div class="brand-logo">
                <h1><i class="fas fa-heartbeat"></i> MediConnect</h1>
                <p>Your Health, Our Priority</p>
            </div>
            
            <div class="brand-content">
                <h2>
                    Welcome to the Future
                    <span>of Healthcare</span>
                </h2>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Video Consultations</h4>
                        <p>Connect with top doctors from home</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="feature-text">
                        <h4>AI Health Assistant</h4>
                        <p>24/7 symptom analysis & guidance</p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="feature-text">
                        <h4>Smart Reminders</h4>
                        <p>Never miss your medications</p>
                    </div>
                </div>
            </div>
            
            <div class="brand-footer">
                <p>© 2026 MediConnect. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Right side - Registration Form -->
        <div class="form-section">
            <div class="form-header">
                <h2>Create Account</h2>
                <p>Join our community of <strong>10,000+</strong> happy patients</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           placeholder="Enter your full name"
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           placeholder="077XXXXXXX"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="role">
                        <i class="fas fa-user-tag"></i> Register as
                    </label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="patient" <?php echo (isset($_POST['role']) && $_POST['role'] == 'patient') ? 'selected' : ''; ?>>Patient - Seeking Healthcare</option>
                        <option value="doctor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'doctor') ? 'selected' : ''; ?>>Doctor - Healthcare Provider</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Create a password" required>
                    <small class="password-hint">
                        <i class="fas fa-info-circle"></i> Minimum 6 characters
                    </small>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="passwordStrength"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Re-enter your password" required>
                </div>
                
                <div class="terms-group">
                    <input type="checkbox" id="terms" name="terms" checked>
                    <label for="terms">
                        I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>
                    </label>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Sign In</a>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        const password = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrength');
        
        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            if (value.length >= 6) strength += 25;
            if (value.match(/[a-z]/)) strength += 25;
            if (value.match(/[A-Z]/)) strength += 25;
            if (value.match(/[0-9]/)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength <= 25) {
                strengthBar.style.background = '#f87171';
            } else if (strength <= 50) {
                strengthBar.style.background = '#fbbf24';
            } else if (strength <= 75) {
                strengthBar.style.background = '#34d399';
            } else {
                strengthBar.style.background = '#c084fc';
            }
        });
        
        // Real-time password match validation
        const confirmPassword = document.getElementById('confirm_password');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.style.borderColor = '#f87171';
            } else {
                this.style.borderColor = '#34d399';
            }
        });
    </script>
</body>
</html>