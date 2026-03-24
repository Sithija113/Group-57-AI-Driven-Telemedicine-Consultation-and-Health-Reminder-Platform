<?php
// pages/login.php 
require_once __DIR__ . '/../includes/config.php';

$error = '';
$success = '';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: dashboard-admin.php");
    } elseif ($_SESSION['user_role'] == 'doctor') {
        header("Location: dashboard-doctor.php");
    } else {
        header("Location: dashboard-patient.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Both email and password are required";
    } else {
        // Check if user exists
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $sql);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: dashboard-admin.php");
                } elseif ($user['role'] == 'doctor') {
                    header("Location: dashboard-doctors.php");
                } else {
                    header("Location: dashboard-patient.php");
                }
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MediConnect</title>
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

        .login-wrapper {
            width: 100%;
            max-width: 1000px;
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

        .testimonial {
            background: rgba(147, 51, 234, 0.1);
            padding: 25px;
            border-radius: 20px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(147, 51, 234, 0.3);
            margin-top: 30px;
        }

        .testimonial i {
            color: #f0abfc;
            font-size: 30px;
            margin-bottom: 15px;
        }

        .testimonial p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
            font-style: italic;
            color: #d8b4fe;
        }

        .testimonial .author {
            font-weight: 600;
            color: #f0abfc;
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

        .forgot-password {
            text-align: right;
            margin-top: 8px;
        }

        .forgot-password a {
            color: #c084fc;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
            color: #f0abfc;
        }

        .btn-login {
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
            margin-top: 20px;
        }

        .btn-login i {
            margin-right: 10px;
            font-size: 20px;
        }

        .btn-login::before {
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

        .btn-login:hover::before {
            width: 400px;
            height: 400px;
        }

        .btn-login:hover {
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

        .register-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(147, 51, 234, 0.3);
        }

        .register-link p {
            color: #94a3b8;
            font-size: 16px;
        }

        .register-link a {
            color: #c084fc;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            margin-left: 5px;
        }

        .register-link a:hover {
            text-decoration: underline;
            color: #f0abfc;
        }

        @media (max-width: 992px) {
            .login-wrapper {
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
    <div class="login-wrapper">
        <!-- Left side - Branding -->
        <div class="brand-section">
            <div class="brand-logo">
                <h1><i class="fas fa-heartbeat"></i> MediConnect</h1>
                <p>Your Health, Our Priority</p>
            </div>
            
            <div class="brand-content">
                <h2>
                    Welcome Back to
                    <span>Better Health</span>
                </h2>
                
                <div class="testimonial">
                    <i class="fas fa-quote-left"></i>
                    <p>MediConnect has transformed how I manage my healthcare. The video consultations save me hours of travel time.</p>
                    <div class="author">- Dr. Kamal Perera</div>
                </div>
            </div>
            
            <div class="brand-footer">
                <p>© 2026 MediConnect. All rights reserved.</p>
            </div>
        </div>
        
        <!-- Right side - Login Form -->
        <div class="form-section">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Login to access your <strong>Health Dashboard</strong></p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Registration successful! Please login.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" required>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Create Account</a></p>
            </div>
        </div>
    </div>
</body>
</html>