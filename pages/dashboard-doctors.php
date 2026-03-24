<?php
// pages/dashboard-doctor.php
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is doctor
if ($_SESSION['user_role'] != 'doctor') {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: dashboard-doctors.php");
    } elseif ($_SESSION['user_role'] == 'patient') {
        header("Location: dashboard-patient.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Initialize variables
$doctor_id = 0;
$specialization = 'General Physician';
$consultation_fee = 2000.00;
$experience_years = 0;
$qualifications = 'MBBS';
$about = '';
$is_available = 1;
$email = '';
$phone = '';
$available_from = '09:00:00';
$available_to = '17:00:00';
$show_edit_form = false;

// Get doctor details
$sql = "SELECT d.*, u.full_name, u.email, u.phone 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.user_id = $user_id";
$result = mysqli_query($conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $doctor = mysqli_fetch_assoc($result);
    $doctor_id = $doctor['doctor_id'];
    $specialization = $doctor['specialization'] ?? 'General Physician';
    $consultation_fee = $doctor['consultation_fee'] ?? 2000.00;
    $experience_years = $doctor['experience_years'] ?? 0;
    $qualifications = $doctor['qualifications'] ?? 'MBBS';
    $about = $doctor['about'] ?? '';
    $is_available = isset($doctor['is_available']) ? (int)$doctor['is_available'] : 1;
    $email = $doctor['email'] ?? '';
    $phone = $doctor['phone'] ?? '';
    $available_from = $doctor['available_from'] ?? '09:00:00';
    $available_to = $doctor['available_to'] ?? '17:00:00';
} else {
    // Create doctor record if doesn't exist
    $insert_sql = "INSERT INTO doctors (user_id, specialization, consultation_fee) 
                   VALUES ($user_id, 'General Physician', 2000.00)";
    if (mysqli_query($conn, $insert_sql)) {
        $doctor_id = mysqli_insert_id($conn);
    }
}

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle availability toggle
    if (isset($_POST['toggle_availability'])) {
        $new_status = $is_available ? 0 : 1;
        
        $check_column = "SHOW COLUMNS FROM doctors LIKE 'is_available'";
        $column_result = mysqli_query($conn, $check_column);
        
        if (mysqli_num_rows($column_result) > 0) {
            $update_sql = "UPDATE doctors SET is_available = $new_status WHERE doctor_id = $doctor_id";
        } else {
            $alter_sql = "ALTER TABLE doctors ADD COLUMN is_available BOOLEAN DEFAULT TRUE";
            mysqli_query($conn, $alter_sql);
            $update_sql = "UPDATE doctors SET is_available = $new_status WHERE doctor_id = $doctor_id";
        }
        
        if (mysqli_query($conn, $update_sql)) {
            $is_available = $new_status;
            $success = $new_status ? "You are now available for appointments!" : "You are now unavailable for appointments.";
        } else {
            $error = "Failed to update availability: " . mysqli_error($conn);
        }
    }
    
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
        $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
        $experience_years = (int)$_POST['experience_years'];
        $consultation_fee = (float)$_POST['consultation_fee'];
        $about = mysqli_real_escape_string($conn, $_POST['about']);
        $available_from = $_POST['available_from'];
        $available_to = $_POST['available_to'];
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        
        $updates = [];
        
        $columns = [
            'specialization' => "'$specialization'",
            'qualifications' => "'$qualifications'",
            'experience_years' => $experience_years,
            'consultation_fee' => $consultation_fee,
            'about' => "'$about'",
            'available_from' => "'$available_from'",
            'available_to' => "'$available_to'"
        ];
        
        foreach ($columns as $column => $value) {
            $check = "SHOW COLUMNS FROM doctors LIKE '$column'";
            if (mysqli_num_rows(mysqli_query($conn, $check)) > 0) {
                $updates[] = "$column = $value";
            }
        }
        
        if (!empty($updates)) {
            $update_sql = "UPDATE doctors SET " . implode(', ', $updates) . " WHERE doctor_id = $doctor_id";
            
            if (mysqli_query($conn, $update_sql)) {
                $update_user_sql = "UPDATE users SET phone = '$phone' WHERE user_id = $user_id";
                mysqli_query($conn, $update_user_sql);
                
                $success = "Profile updated successfully!";
                $show_edit_form = false;
            } else {
                $error = "Failed to update profile: " . mysqli_error($conn);
            }
        }
    }
    
    // Handle show edit form
    if (isset($_POST['show_edit_form'])) {
        $show_edit_form = true;
    }
    
    if (isset($_POST['cancel_edit'])) {
        $show_edit_form = false;
    }
    
    // ========== HANDLE CONFIRM APPOINTMENT ==========
    if (isset($_POST['confirm_appointment'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $update_sql = "UPDATE appointments SET status = 'confirmed' WHERE appointment_id = $appointment_id AND doctor_id = $doctor_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Appointment confirmed successfully!";
        } else {
            $error = "Failed to confirm appointment: " . mysqli_error($conn);
        }
    }
    
    // ========== HANDLE CANCEL APPOINTMENT ==========
    if (isset($_POST['cancel_appointment'])) {
        $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
        $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $appointment_id AND doctor_id = $doctor_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Appointment cancelled successfully!";
        } else {
            $error = "Failed to cancel appointment: " . mysqli_error($conn);
        }
    }
}

// Get today's appointments
$today_sql = "SELECT a.*, p.patient_id, u.full_name as patient_name, u.phone, u.email,
              p.date_of_birth, p.blood_group, p.gender
              FROM appointments a 
              LEFT JOIN patients p ON a.patient_id = p.patient_id
              LEFT JOIN users u ON p.user_id = u.user_id 
              WHERE a.doctor_id = $doctor_id AND a.appointment_date = CURDATE()
              ORDER BY a.appointment_time ASC";
$today_result = mysqli_query($conn, $today_sql);

// Get upcoming appointments
$upcoming_sql = "SELECT a.*, p.patient_id, u.full_name as patient_name, u.phone, u.email,
                 p.date_of_birth, p.blood_group, p.gender
                 FROM appointments a 
                 LEFT JOIN patients p ON a.patient_id = p.patient_id
                 LEFT JOIN users u ON p.user_id = u.user_id 
                 WHERE a.doctor_id = $doctor_id AND a.appointment_date > CURDATE()
                 ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_sql);

// Get past appointments
$past_sql = "SELECT a.*, p.patient_id, u.full_name as patient_name, u.phone, u.email
             FROM appointments a 
             LEFT JOIN patients p ON a.patient_id = p.patient_id
             LEFT JOIN users u ON p.user_id = u.user_id 
             WHERE a.doctor_id = $doctor_id AND a.appointment_date < CURDATE()
             ORDER BY a.appointment_date DESC, a.appointment_time DESC
             LIMIT 10";
$past_result = mysqli_query($conn, $past_sql);

// Get counts
$total_appointments_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id";
$total_result = mysqli_query($conn, $total_appointments_sql);
$total_appointments = ($total_result && $row = mysqli_fetch_assoc($total_result)) ? $row['count'] : 0;

$today_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND appointment_date = CURDATE()";
$today_count_result = mysqli_query($conn, $today_count_sql);
$today_count = ($today_count_result && $row = mysqli_fetch_assoc($today_count_result)) ? $row['count'] : 0;

$pending_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND status = 'pending'";
$pending_count_result = mysqli_query($conn, $pending_count_sql);
$pending_count = ($pending_count_result && $row = mysqli_fetch_assoc($pending_count_result)) ? $row['count'] : 0;

$completed_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = $doctor_id AND status = 'completed'";
$completed_count_result = mysqli_query($conn, $completed_count_sql);
$completed_count = ($completed_count_result && $row = mysqli_fetch_assoc($completed_count_result)) ? $row['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - MediConnect</title>
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

        .logout-btn {
            background: rgba(147, 51, 234, 0.1);
            color: #f0abfc;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .logout-btn:hover {
            background: rgba(147, 51, 234, 0.3);
            color: white;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

        /* Welcome Card */
        .welcome-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-content h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .welcome-content p {
            color: #d8b4fe;
            font-size: 16px;
        }

        .welcome-content p i {
            color: #f0abfc;
            margin-right: 8px;
        }

        .availability-toggle {
            background: rgba(147, 51, 234, 0.1);
            padding: 20px 30px;
            border-radius: 15px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            text-align: center;
            min-width: 280px;
        }

        .availability-toggle h3 {
            color: white;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .status-badge {
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .status-available {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .status-unavailable {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
        }

        .toggle-form {
            display: inline-block;
        }

        .toggle-btn {
            background: transparent;
            border: 1px solid #f0abfc;
            color: #f0abfc;
            padding: 10px 25px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 600;
        }

        .toggle-btn:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            border: 1px solid rgba(147, 51, 234, 0.3);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #a855f7;
            box-shadow: 0 10px 30px rgba(147, 51, 234, 0.3);
        }

        .stat-card i {
            font-size: 35px;
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            color: #d8b4fe;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            color: white;
            font-size: 32px;
            font-weight: 700;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.5);
        }

        /* Profile Card */
        .profile-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            margin-bottom: 30px;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .profile-header h3 {
            color: white;
            font-size: 20px;
        }

        .profile-header h3 i {
            color: #f0abfc;
            margin-right: 10px;
        }

        .edit-profile-btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(147, 51, 234, 0.4);
        }

        .cancel-btn {
            background: transparent;
            border: 1px solid #f87171;
            color: #f87171;
            padding: 10px 25px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }

        .cancel-btn:hover {
            background: #f87171;
            color: white;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .profile-item {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #d8b4fe;
            padding: 12px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(147, 51, 234, 0.1);
        }

        .profile-item i {
            color: #f0abfc;
            width: 20px;
            font-size: 16px;
        }

        .profile-item .label {
            color: #a78bfa;
            font-size: 13px;
            min-width: 90px;
        }

        .profile-item .value {
            color: white;
            font-weight: 500;
        }

        /* Edit Form */
        .edit-form {
            margin-top: 20px;
            padding: 20px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 15px;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #d8b4fe;
            font-size: 13px;
            font-weight: 500;
        }

        .form-group label i {
            color: #f0abfc;
            margin-right: 5px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 8px;
            color: white;
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 15px rgba(147, 51, 234, 0.3);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .save-btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(147, 51, 234, 0.4);
        }

        /* Appointments Section */
        .appointments-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
            padding-bottom: 15px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: transparent;
            border: none;
            color: #d8b4fe;
            padding: 8px 20px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 8px;
        }

        .tab-btn:hover {
            color: #f0abfc;
            background: rgba(147, 51, 234, 0.1);
        }

        .tab-btn.active {
            color: #f0abfc;
            background: rgba(147, 51, 234, 0.15);
            border-bottom: 2px solid #f0abfc;
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

        .appointment-card {
            background: rgba(147, 51, 234, 0.1);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .appointment-time {
            text-align: center;
            padding: 10px;
            background: rgba(147, 51, 234, 0.15);
            border-radius: 10px;
            min-width: 100px;
        }

        .appointment-time .date {
            font-size: 13px;
            color: #d8b4fe;
        }

        .appointment-time .time {
            font-size: 18px;
            font-weight: 600;
            color: #f0abfc;
        }

        .appointment-info h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .appointment-info p {
            color: #d8b4fe;
            font-size: 13px;
            margin-bottom: 5px;
        }

        .appointment-info p i {
            color: #f0abfc;
            width: 20px;
            margin-right: 8px;
        }

        .appointment-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 150px;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            border: none;
            padding: 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .confirm-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .cancel-btn-action {
            background: linear-gradient(135deg, #ef4444, #f87171);
            color: white;
            border: none;
            padding: 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .cancel-btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.4);
        }

        .view-details-btn {
            background: transparent;
            color: #f0abfc;
            text-decoration: none;
            padding: 8px;
            border-radius: 8px;
            font-size: 13px;
            border: 1px solid #f0abfc;
            text-align: center;
            transition: all 0.3s;
            display: block;
        }

        .view-details-btn:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        .no-appointments {
            text-align: center;
            padding: 40px;
            color: #d8b4fe;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(147, 51, 234, 0.3);
        }

        .no-appointments i {
            font-size: 50px;
            color: #f0abfc;
            margin-bottom: 15px;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            margin-left: 8px;
        }

        .badge-pending {
            background: rgba(146, 64, 14, 0.3);
            color: #fcd34d;
            border: 1px solid #fbbf24;
        }

        .badge-confirmed {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .badge-completed {
            background: rgba(29, 78, 216, 0.3);
            color: #93c5fd;
            border: 1px solid #3b82f6;
        }

        .badge-cancelled {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
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

        @media (max-width: 992px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .container {
                padding: 0 20px;
            }
            
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }
            
            .appointment-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .appointment-actions {
                flex-direction: row;
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
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
                <i class="fas fa-user-md"></i>
                <span>Dr. <?php echo htmlspecialchars(str_replace('Dr. ', '', $user_name)); ?></span>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Card with Working Availability Toggle -->
        <div class="welcome-card">
            <div class="welcome-content">
                <h2>Welcome back, Dr. <?php echo htmlspecialchars(str_replace('Dr. ', '', $user_name)); ?>! </h2>
                <p><i class="fas fa-stethoscope"></i> <?php echo htmlspecialchars($specialization); ?> | <?php echo $experience_years; ?> years experience</p>
            </div>
            <div class="availability-toggle">
                <h3>Your Availability</h3>
                <div class="status-badge <?php echo $is_available ? 'status-available' : 'status-unavailable'; ?>">
                    <?php echo $is_available ? '✅ Available for Appointments' : '❌ Currently Unavailable'; ?>
                </div>
                <form method="POST" action="" class="toggle-form">
                    <button type="submit" name="toggle_availability" class="toggle-btn">
                        <i class="fas fa-sync-alt"></i> 
                        <?php echo $is_available ? 'Go Unavailable' : 'Go Available'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Today's Appointments</h3>
                <div class="number"><?php echo $today_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3>Pending</h3>
                <div class="number"><?php echo $pending_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>Completed</h3>
                <div class="number"><?php echo $completed_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>Total Appointments</h3>
                <div class="number"><?php echo $total_appointments; ?></div>
            </div>
        </div>

        <!-- Profile Section with Inline Editing -->
        <div class="profile-section">
            <div class="profile-header">
                <h3><i class="fas fa-id-card"></i> Professional Profile</h3>
                <?php if (!$show_edit_form): ?>
                    <form method="POST" action="">
                        <button type="submit" name="show_edit_form" class="edit-profile-btn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($show_edit_form): ?>
                <!-- Edit Form -->
                <div class="edit-form">
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Specialization</label>
                                <input type="text" class="form-control" name="specialization" 
                                       value="<?php echo htmlspecialchars($specialization); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-graduation-cap"></i> Qualifications</label>
                                <input type="text" class="form-control" name="qualifications" 
                                       value="<?php echo htmlspecialchars($qualifications); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-briefcase"></i> Experience (Years)</label>
                                <input type="number" class="form-control" name="experience_years" 
                                       value="<?php echo $experience_years; ?>" min="0" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Consultation Fee (LKR)</label>
                                <input type="number" class="form-control" name="consultation_fee" 
                                       value="<?php echo $consultation_fee; ?>" min="0" step="100" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Available From</label>
                                <input type="time" class="form-control" name="available_from" 
                                       value="<?php echo $available_from; ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Available To</label>
                                <input type="time" class="form-control" name="available_to" 
                                       value="<?php echo $available_to; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-align-left"></i> About / Bio</label>
                            <textarea class="form-control" name="about" rows="3"><?php echo htmlspecialchars($about); ?></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="save-btn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="submit" name="cancel_edit" class="cancel-btn">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Profile Display -->
                <div class="profile-grid">
                    <div class="profile-item">
                        <i class="fas fa-user-md"></i>
                        <span class="label">Specialization:</span>
                        <span class="value"><?php echo htmlspecialchars($specialization); ?></span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span class="label">Qualifications:</span>
                        <span class="value"><?php echo htmlspecialchars($qualifications); ?></span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-briefcase"></i>
                        <span class="label">Experience:</span>
                        <span class="value"><?php echo $experience_years; ?> years</span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-tag"></i>
                        <span class="label">Consultation Fee:</span>
                        <span class="value">LKR <?php echo number_format($consultation_fee, 2); ?></span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-clock"></i>
                        <span class="label">Available:</span>
                        <span class="value"><?php echo date('h:i A', strtotime($available_from)); ?> - <?php echo date('h:i A', strtotime($available_to)); ?></span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-phone"></i>
                        <span class="label">Phone:</span>
                        <span class="value"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                    <div class="profile-item">
                        <i class="fas fa-envelope"></i>
                        <span class="label">Email:</span>
                        <span class="value"><?php echo htmlspecialchars($email); ?></span>
                    </div>
                </div>
                
                <?php if (!empty($about)): ?>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(147, 51, 234, 0.05); border-radius: 10px; border: 1px solid rgba(147, 51, 234, 0.1);">
                        <p style="color: #d8b4fe; font-size: 14px;"><i class="fas fa-quote-right" style="color: #f0abfc; margin-right: 8px;"></i> <?php echo nl2br(htmlspecialchars($about)); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Appointments Section with Tabs -->
        <div class="appointments-section">
            <div class="section-tabs">
                <button class="tab-btn active" onclick="showTab('today')"><i class="fas fa-calendar-day"></i> Today's Appointments (<?php echo $today_count; ?>)</button>
                <button class="tab-btn" onclick="showTab('upcoming')"><i class="fas fa-calendar-week"></i> Upcoming</button>
                <button class="tab-btn" onclick="showTab('past')"><i class="fas fa-history"></i> Past Appointments</button>
            </div>

            <!-- Today's Appointments Tab -->
            <div id="today" class="tab-content active">
                <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-calendar-day" style="color: #f0abfc;"></i> Today's Schedule</h3>
                <?php if ($today_result && mysqli_num_rows($today_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($today_result)): ?>
                        <div class="appointment-card">
                            <div class="appointment-time">
                                <div class="date"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </div>
                            <div class="appointment-info">
                                <h4><?php echo htmlspecialchars($appointment['patient_name'] ?? 'Unknown Patient'); ?>
                                    <span class="badge badge-<?php echo $appointment['status'] ?? 'pending'; ?>"><?php echo ucfirst($appointment['status'] ?? 'pending'); ?></span>
                                </h4>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></p>
                                <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['symptoms'] ?? 'No symptoms provided'); ?></p>
                            </div>
                            <div class="appointment-actions">
                                <?php if ($appointment['status'] == 'pending'): ?>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="confirm_appointment" class="confirm-btn">
                                            <i class="fas fa-check-circle"></i> Confirm
                                        </button>
                                    </form>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="cancel-btn-action">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>
                                <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="cancel-btn-action">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="doctor-appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-details-btn">
                                <i class="fas fa-edit"></i> View & Update
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-calendar-check"></i>
                        <p>No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Appointments Tab -->
            <div id="upcoming" class="tab-content">
                <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-calendar-week" style="color: #f0abfc;"></i> Upcoming Appointments</h3>
                <?php if ($upcoming_result && mysqli_num_rows($upcoming_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($upcoming_result)): ?>
                        <div class="appointment-card">
                            <div class="appointment-time">
                                <div class="date"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </div>
                            <div class="appointment-info">
                                <h4><?php echo htmlspecialchars($appointment['patient_name'] ?? 'Unknown Patient'); ?>
                                    <span class="badge badge-<?php echo $appointment['status'] ?? 'pending'; ?>"><?php echo ucfirst($appointment['status'] ?? 'pending'); ?></span>
                                </h4>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></p>
                                <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['symptoms'] ?? 'No symptoms provided'); ?></p>
                            </div>
                            <div class="appointment-actions">
                                <?php if ($appointment['status'] == 'pending'): ?>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="confirm_appointment" class="confirm-btn">
                                            <i class="fas fa-check-circle"></i> Confirm
                                        </button>
                                    </form>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="cancel-btn-action">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>
                                <?php elseif ($appointment['status'] == 'confirmed'): ?>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="cancel-btn-action">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="doctor-appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-details-btn">
                                <i class="fas fa-edit"></i> View & Update
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-calendar-plus"></i>
                        <p>No upcoming appointments</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Appointments Tab -->
            <div id="past" class="tab-content">
                <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-history" style="color: #f0abfc;"></i> Past Appointments</h3>
                <?php if ($past_result && mysqli_num_rows($past_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($past_result)): ?>
                        <div class="appointment-card">
                            <div class="appointment-time">
                                <div class="date"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                                <div class="time"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></div>
                            </div>
                            <div class="appointment-info">
                                <h4><?php echo htmlspecialchars($appointment['patient_name'] ?? 'Unknown Patient'); ?>
                                    <span class="badge badge-<?php echo $appointment['status'] ?? 'completed'; ?>"><?php echo ucfirst($appointment['status'] ?? 'completed'); ?></span>
                                </h4>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?? 'N/A'); ?></p>
                                <p><i class="fas fa-notes-medical"></i> <?php echo htmlspecialchars($appointment['symptoms'] ?? 'No symptoms provided'); ?></p>
                            </div>
                            <div class="appointment-actions">
                                <a href="doctor-appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="view-details-btn">
                                <i class="fas fa-edit"></i> View & Update
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-folder-open"></i>
                        <p>No past appointments found</p>
                    </div>
                <?php endif; ?>
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