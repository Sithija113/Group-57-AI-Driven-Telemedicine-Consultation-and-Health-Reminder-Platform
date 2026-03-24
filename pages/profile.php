<?php
// pages/profile.php
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is patient
if ($_SESSION['user_role'] != 'patient') {
    header("Location: dashboard-patient.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Initialize variables with default values
$patient_id = 0;
$date_of_birth = '';
$gender = '';
$blood_group = '';
$address = '';
$emergency_contact = '';
$phone = '';
$full_name = $user_name; // Use session value as default
$medical_history = '';
$allergies = '';
$medical_history_exists = false;
$allergies_exists = false;

// First, get user data from users table (this is where full_name and phone come from)
$user_sql = "SELECT phone, full_name FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_sql);
if ($user_result && mysqli_num_rows($user_result) > 0) {
    $user_data = mysqli_fetch_assoc($user_result);
    $phone = $user_data['phone'] ?? '';
    // full_name from database might be different from session
    $full_name = $user_data['full_name'] ?? $user_name;
}

// Now get patient details (this table doesn't have full_name or phone)
$sql = "SELECT * FROM patients WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
    
    // Safely assign values from patient record
    $date_of_birth = $patient['date_of_birth'] ?? '';
    $gender = $patient['gender'] ?? '';
    $blood_group = $patient['blood_group'] ?? '';
    $address = $patient['address'] ?? '';
    $emergency_contact = $patient['emergency_contact'] ?? '';
    
    // Check if medical_history column exists
    $check_medical = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'medical_history'");
    $medical_history_exists = mysqli_num_rows($check_medical) > 0;
    if ($medical_history_exists) {
        $medical_history = $patient['medical_history'] ?? '';
    }
    
    // Check if allergies column exists
    $check_allergies = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'allergies'");
    $allergies_exists = mysqli_num_rows($check_allergies) > 0;
    if ($allergies_exists) {
        $allergies = $patient['allergies'] ?? '';
    }
} else {
    // Create patient record if doesn't exist
    $insert_sql = "INSERT INTO patients (user_id) VALUES ($user_id)";
    if (mysqli_query($conn, $insert_sql)) {
        $patient_id = mysqli_insert_id($conn);
    }
    
    // Check if medical_history column exists
    $check_medical = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'medical_history'");
    $medical_history_exists = mysqli_num_rows($check_medical) > 0;
    
    // Check if allergies column exists
    $check_allergies = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'allergies'");
    $allergies_exists = mysqli_num_rows($check_allergies) > 0;
}

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $date_of_birth = !empty($_POST['date_of_birth']) ? "'" . mysqli_real_escape_string($conn, $_POST['date_of_birth']) . "'" : "NULL";
        $gender = !empty($_POST['gender']) ? "'" . mysqli_real_escape_string($conn, $_POST['gender']) . "'" : "NULL";
        $blood_group = !empty($_POST['blood_group']) ? "'" . mysqli_real_escape_string($conn, $_POST['blood_group']) . "'" : "NULL";
        $address = !empty($_POST['address']) ? "'" . mysqli_real_escape_string($conn, $_POST['address']) . "'" : "NULL";
        $emergency_contact = !empty($_POST['emergency_contact']) ? "'" . mysqli_real_escape_string($conn, $_POST['emergency_contact']) . "'" : "NULL";
        
        // Update users table
        $update_user = "UPDATE users SET full_name = '$full_name', phone = '$phone' WHERE user_id = $user_id";
        if (mysqli_query($conn, $update_user)) {
            // Update session name
            $_SESSION['user_name'] = $full_name;
        }
        
        // Check if patient record exists
        $check_patient = "SELECT patient_id FROM patients WHERE user_id = $user_id";
        $check_result = mysqli_query($conn, $check_patient);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Insert new patient record
            $insert_patient = "INSERT INTO patients (user_id, date_of_birth, gender, blood_group, address, emergency_contact) 
                               VALUES ($user_id, $date_of_birth, $gender, $blood_group, $address, $emergency_contact)";
            mysqli_query($conn, $insert_patient);
            $success = "Profile updated successfully!";
        } else {
            // Build update query dynamically
            $updates = [];
            $updates[] = "date_of_birth = $date_of_birth";
            $updates[] = "gender = $gender";
            $updates[] = "blood_group = $blood_group";
            $updates[] = "address = $address";
            $updates[] = "emergency_contact = $emergency_contact";
            
            // Check if medical_history column exists before including it
            $check_medical = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'medical_history'");
            if (mysqli_num_rows($check_medical) > 0 && isset($_POST['medical_history'])) {
                $medical_history_val = "'" . mysqli_real_escape_string($conn, $_POST['medical_history']) . "'";
                $updates[] = "medical_history = $medical_history_val";
            }
            
            // Check if allergies column exists before including it
            $check_allergies = mysqli_query($conn, "SHOW COLUMNS FROM patients LIKE 'allergies'");
            if (mysqli_num_rows($check_allergies) > 0 && isset($_POST['allergies'])) {
                $allergies_val = "'" . mysqli_real_escape_string($conn, $_POST['allergies']) . "'";
                $updates[] = "allergies = $allergies_val";
            }
            
            $update_patient = "UPDATE patients SET " . implode(', ', $updates) . " WHERE user_id = $user_id";
            
            if (mysqli_query($conn, $update_patient)) {
                $success = "Profile updated successfully!";
            } else {
                $error = "Failed to update profile: " . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Get current password hash
        $pass_sql = "SELECT password FROM users WHERE user_id = $user_id";
        $pass_result = mysqli_query($conn, $pass_sql);
        $user_data = mysqli_fetch_assoc($pass_result);
        
        if (password_verify($current_password, $user_data['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
                    
                    if (mysqli_query($conn, $update_pass)) {
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Failed to change password: " . mysqli_error($conn);
                    }
                } else {
                    $error = "New password must be at least 6 characters long";
                }
            } else {
                $error = "New passwords do not match";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MediConnect</title>
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
            color: white;
            position: relative;
        }

        /* Animated purple orbs */
        body::before {
            content: '';
            position: fixed;
            top: -20%;
            left: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: float1 20s infinite alternate;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -20%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
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

        /* Navbar */
        .navbar {
            background: rgba(18, 18, 28, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo i {
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(147, 51, 234, 0.1);
            padding: 8px 15px;
            border-radius: 30px;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .user-info i {
            color: #f0abfc;
            font-size: 18px;
        }

        .user-info span {
            color: #e9d5ff;
            font-weight: 500;
        }

        .back-btn {
            background: rgba(147, 51, 234, 0.1);
            color: #f0abfc;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .back-btn:hover {
            background: rgba(147, 51, 234, 0.3);
            color: white;
        }

        .back-btn i {
            margin-right: 8px;
        }

        /* Container */
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header p {
            color: #d8b4fe;
            font-size: 18px;
        }

        .page-header p i {
            color: #f0abfc;
            margin-right: 8px;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            font-size: 20px;
        }

        .alert-success {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .alert-danger {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
        }

        /* Profile Card */
        .profile-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 40px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #9333ea, #c084fc);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            border: 3px solid #f0abfc;
            box-shadow: 0 0 30px rgba(147, 51, 234, 0.5);
        }

        .profile-title h2 {
            color: white;
            font-size: 32px;
            margin-bottom: 5px;
        }

        .profile-title p {
            color: #d8b4fe;
            font-size: 16px;
        }

        .profile-title p i {
            color: #f0abfc;
            margin-right: 8px;
        }

        /* Section Tabs */
        .section-tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
            padding-bottom: 15px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: #d8b4fe;
            padding: 10px 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 30px;
        }

        .tab-btn:hover {
            color: #f0abfc;
            background: rgba(147, 51, 234, 0.1);
        }

        .tab-btn.active {
            color: #f0abfc;
            background: rgba(147, 51, 234, 0.15);
            border: 1px solid #f0abfc;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d8b4fe;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label i {
            color: #f0abfc;
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .form-control:hover {
            border-color: #a855f7;
        }

        .form-control option {
            background: #1a1a24;
            color: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.5);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #f0abfc;
            color: #f0abfc;
            margin-left: 15px;
        }

        .btn-secondary:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        /* Password Requirements */
        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 8px;
            font-size: 12px;
            color: #d8b4fe;
        }

        .password-requirements i {
            color: #f0abfc;
            margin-right: 5px;
        }

        .requirement {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement.valid {
            color: #a7f3d0;
        }

        .requirement.invalid {
            color: #fecaca;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <i class="fas fa-heartbeat"></i> MediConnect
        </div>
        <div class="nav-right">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($full_name); ?></span>
            </div>
            <a href="dashboard-patient.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p><i class="fas fa-edit"></i> Manage your personal information</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-title">
                    <h2><?php echo htmlspecialchars($full_name); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_email); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($phone ?: 'Not provided'); ?></p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="section-tabs">
                <button class="tab-btn active" onclick="showTab('personal')"><i class="fas fa-user-edit"></i> Personal Information</button>
                <?php if ($medical_history_exists || $allergies_exists): ?>
                <button class="tab-btn" onclick="showTab('medical')"><i class="fas fa-notes-medical"></i> Medical Info</button>
                <?php endif; ?>
                <button class="tab-btn" onclick="showTab('password')"><i class="fas fa-lock"></i> Change Password</button>
            </div>

            <!-- Personal Information Tab -->
            <div id="personal" class="tab-content active">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Date of Birth</label>
                            <input type="date" class="form-control" name="date_of_birth" value="<?php echo $date_of_birth; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender</label>
                            <select class="form-control" name="gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $gender == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $gender == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $gender == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tint"></i> Blood Group</label>
                            <select class="form-control" name="blood_group">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo $blood_group == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo $blood_group == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo $blood_group == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo $blood_group == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="O+" <?php echo $blood_group == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo $blood_group == 'O-' ? 'selected' : ''; ?>>O-</option>
                                <option value="AB+" <?php echo $blood_group == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo $blood_group == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-phone-alt"></i> Emergency Contact</label>
                            <input type="tel" class="form-control" name="emergency_contact" value="<?php echo htmlspecialchars($emergency_contact); ?>" placeholder="Emergency contact number">
                        </div>
                        
                        <div class="form-group full-width">
                            <label><i class="fas fa-map-marker-alt"></i> Address</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Medical Information Tab (only shown if columns exist) -->
            <?php if ($medical_history_exists || $allergies_exists): ?>
            <div id="medical" class="tab-content">
                <form method="POST" action="">
                    <?php if ($medical_history_exists): ?>
                    <div class="form-group">
                        <label><i class="fas fa-history"></i> Medical History</label>
                        <textarea class="form-control" name="medical_history" rows="5" placeholder="List any past medical conditions, surgeries, or ongoing health issues..."><?php echo htmlspecialchars($medical_history); ?></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($allergies_exists): ?>
                    <div class="form-group">
                        <label><i class="fas fa-exclamation-triangle"></i> Allergies</label>
                        <textarea class="form-control" name="allergies" rows="3" placeholder="List any allergies to medications, foods, or other substances..."><?php echo htmlspecialchars($allergies); ?></textarea>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" name="update_profile" class="btn">
                        <i class="fas fa-save"></i> Save Medical Information
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Change Password Tab -->
            <div id="password" class="tab-content">
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" required>
                        <div class="password-requirements">
                            <div class="requirement" id="length">
                                <i class="fas fa-circle"></i> At least 6 characters
                            </div>
                            <div class="requirement" id="lowercase">
                                <i class="fas fa-circle"></i> Contains lowercase letter
                            </div>
                            <div class="requirement" id="uppercase">
                                <i class="fas fa-circle"></i> Contains uppercase letter
                            </div>
                            <div class="requirement" id="number">
                                <i class="fas fa-circle"></i> Contains number
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-check-circle"></i> Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        <small style="color: #d8b4fe; display: block; margin-top: 5px;" id="password_match"></small>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn">
                        <i class="fas fa-sync-alt"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName('tab-btn');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

        // Password strength checker
        const password = document.getElementById('new_password');
        const confirm = document.getElementById('confirm_password');
        
        if (password) {
            password.addEventListener('input', function() {
                const value = this.value;
                
                // Check length
                const lengthReq = document.getElementById('length');
                if (value.length >= 6) {
                    lengthReq.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> At least 6 characters';
                    lengthReq.classList.add('valid');
                } else {
                    lengthReq.innerHTML = '<i class="fas fa-circle" style="color: #fecaca;"></i> At least 6 characters';
                    lengthReq.classList.remove('valid');
                }
                
                // Check lowercase
                const lowerReq = document.getElementById('lowercase');
                if (/[a-z]/.test(value)) {
                    lowerReq.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Contains lowercase letter';
                    lowerReq.classList.add('valid');
                } else {
                    lowerReq.innerHTML = '<i class="fas fa-circle" style="color: #fecaca;"></i> Contains lowercase letter';
                    lowerReq.classList.remove('valid');
                }
                
                // Check uppercase
                const upperReq = document.getElementById('uppercase');
                if (/[A-Z]/.test(value)) {
                    upperReq.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Contains uppercase letter';
                    upperReq.classList.add('valid');
                } else {
                    upperReq.innerHTML = '<i class="fas fa-circle" style="color: #fecaca;"></i> Contains uppercase letter';
                    upperReq.classList.remove('valid');
                }
                
                // Check number
                const numberReq = document.getElementById('number');
                if (/[0-9]/.test(value)) {
                    numberReq.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Contains number';
                    numberReq.classList.add('valid');
                } else {
                    numberReq.innerHTML = '<i class="fas fa-circle" style="color: #fecaca;"></i> Contains number';
                    numberReq.classList.remove('valid');
                }
            });
        }
        
        if (confirm) {
            confirm.addEventListener('input', function() {
                const matchSpan = document.getElementById('password_match');
                if (this.value === password.value) {
                    matchSpan.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Passwords match';
                    matchSpan.style.color = '#10b981';
                } else {
                    matchSpan.innerHTML = '<i class="fas fa-exclamation-circle" style="color: #f87171;"></i> Passwords do not match';
                    matchSpan.style.color = '#f87171';
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.getElementsByClassName('alert');
            for (let i = 0; i < alerts.length; i++) {
                if (alerts[i]) {
                    alerts[i].style.opacity = '0';
                    alerts[i].style.transition = 'opacity 0.5s';
                    setTimeout(() => {
                        alerts[i].style.display = 'none';
                    }, 500);
                }
            }
        }, 5000);
    </script>
</body>
</html>