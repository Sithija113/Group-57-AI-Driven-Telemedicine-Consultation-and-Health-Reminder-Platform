<?php
// pages/my-appointments.php
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

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get patient ID
$sql = "SELECT patient_id FROM patients WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
} else {
    // Create patient record if doesn't exist
    $insert_sql = "INSERT INTO patients (user_id) VALUES ($user_id)";
    mysqli_query($conn, $insert_sql);
    $patient_id = mysqli_insert_id($conn);
}

// Handle appointment cancellation
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
    
    // Check if appointment belongs to this patient
    $check_sql = "SELECT * FROM appointments WHERE appointment_id = $appointment_id AND patient_id = $patient_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $appointment_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Appointment cancelled successfully!";
        } else {
            $error = "Failed to cancel appointment: " . mysqli_error($conn);
        }
    } else {
        $error = "Appointment not found or you don't have permission to cancel it.";
    }
}

// Handle reschedule request (simplified - just changes status to pending)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reschedule_appointment'])) {
    $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
    
    // Check if appointment belongs to this patient
    $check_sql = "SELECT * FROM appointments WHERE appointment_id = $appointment_id AND patient_id = $patient_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE appointments SET status = 'pending' WHERE appointment_id = $appointment_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Reschedule request sent to doctor. Please book a new time.";
        } else {
            $error = "Failed to request reschedule: " . mysqli_error($conn);
        }
    } else {
        $error = "Appointment not found or you don't have permission to reschedule it.";
    }
}

// Get upcoming appointments (today and future)
$upcoming_sql = "SELECT a.*, d.doctor_id, u.full_name as doctor_name, d.specialization, 
                 d.qualifications, d.experience_years, d.consultation_fee
                 FROM appointments a 
                 JOIN doctors d ON a.doctor_id = d.doctor_id 
                 JOIN users u ON d.user_id = u.user_id 
                 WHERE a.patient_id = $patient_id AND a.appointment_date >= CURDATE()
                 ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$upcoming_result = mysqli_query($conn, $upcoming_sql);

// Get past appointments
$past_sql = "SELECT a.*, d.doctor_id, u.full_name as doctor_name, d.specialization, 
              d.qualifications, d.experience_years, d.consultation_fee
              FROM appointments a 
              JOIN doctors d ON a.doctor_id = d.doctor_id 
              JOIN users u ON d.user_id = u.user_id 
              WHERE a.patient_id = $patient_id AND a.appointment_date < CURDATE()
              ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$past_result = mysqli_query($conn, $past_sql);

// Get counts
$total_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id";
$total_result = mysqli_query($conn, $total_sql);
$total_appointments = ($total_result && $row = mysqli_fetch_assoc($total_result)) ? $row['count'] : 0;

$upcoming_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id AND appointment_date >= CURDATE()";
$upcoming_count_result = mysqli_query($conn, $upcoming_count_sql);
$upcoming_count = ($upcoming_count_result && $row = mysqli_fetch_assoc($upcoming_count_result)) ? $row['count'] : 0;

$completed_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id AND status = 'completed'";
$completed_count_result = mysqli_query($conn, $completed_count_sql);
$completed_count = ($completed_count_result && $row = mysqli_fetch_assoc($completed_count_result)) ? $row['count'] : 0;

$cancelled_count_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id AND status = 'cancelled'";
$cancelled_count_result = mysqli_query($conn, $cancelled_count_sql);
$cancelled_count = ($cancelled_count_result && $row = mysqli_fetch_assoc($cancelled_count_result)) ? $row['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - MediConnect</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
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

        .book-btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .book-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.5);
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

        /* Tabs */
        .appointments-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }

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

        /* Appointment Cards */
        .appointment-card {
            background: rgba(147, 51, 234, 0.1);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 25px;
            align-items: center;
            transition: all 0.3s;
        }

        .appointment-card:hover {
            transform: translateY(-3px);
            border-color: #f0abfc;
            box-shadow: 0 10px 30px rgba(147, 51, 234, 0.3);
        }

        .appointment-time {
            text-align: center;
            padding: 15px;
            background: rgba(147, 51, 234, 0.15);
            border-radius: 12px;
            min-width: 120px;
        }

        .appointment-time .date {
            font-size: 14px;
            color: #d8b4fe;
            margin-bottom: 5px;
        }

        .appointment-time .day {
            font-size: 28px;
            font-weight: 700;
            color: #f0abfc;
            line-height: 1;
        }

        .appointment-time .month {
            font-size: 14px;
            color: #d8b4fe;
            text-transform: uppercase;
        }

        .appointment-time .time {
            font-size: 16px;
            font-weight: 600;
            color: #f0abfc;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid rgba(147, 51, 234, 0.3);
        }

        .appointment-info h3 {
            color: white;
            font-size: 20px;
            margin-bottom: 10px;
        }

        .appointment-info .doctor-specialty {
            color: #f0abfc;
            font-size: 14px;
            margin-bottom: 15px;
            display: inline-block;
            padding: 4px 12px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 20px;
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #d8b4fe;
            font-size: 14px;
        }

        .detail-item i {
            color: #f0abfc;
            width: 20px;
        }

        .appointment-footer {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(147, 51, 234, 0.2);
        }

        .status-badge {
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(146, 64, 14, 0.3);
            color: #fcd34d;
            border: 1px solid #fbbf24;
        }

        .status-confirmed {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .status-completed {
            background: rgba(29, 78, 216, 0.3);
            color: #93c5fd;
            border: 1px solid #3b82f6;
        }

        .status-cancelled {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
        }

        .appointment-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 140px;
        }

        .action-btn {
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .view-btn {
            background: transparent;
            color: #f0abfc;
            border: 1px solid #f0abfc;
        }

        .view-btn:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        .cancel-btn {
            background: transparent;
            color: #f87171;
            border: 1px solid #f87171;
        }

        .cancel-btn:hover {
            background: #f87171;
            color: white;
        }

        .reschedule-btn {
            background: transparent;
            color: #fbbf24;
            border: 1px solid #fbbf24;
        }

        .reschedule-btn:hover {
            background: #fbbf24;
            color: #0a0a0f;
        }

        .book-new-btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .no-appointments {
            text-align: center;
            padding: 60px 40px;
            color: #d8b4fe;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 15px;
            border: 1px dashed rgba(147, 51, 234, 0.3);
        }

        .no-appointments i {
            font-size: 60px;
            color: #f0abfc;
            margin-bottom: 20px;
        }

        .no-appointments h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .no-appointments p {
            margin-bottom: 25px;
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
            
            .appointment-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .appointment-time {
                margin: 0 auto;
                max-width: 200px;
            }
            
            .appointment-actions {
                flex-direction: row;
                justify-content: center;
            }

            .appointment-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
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
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <a href="dashboard-patient.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
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

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-calendar-alt"></i> My Appointments</h1>
                <p><i class="fas fa-clock"></i> Manage your appointments and schedule</p>
            </div>
            <a href="book-appointment.php" class="book-btn">
                <i class="fas fa-plus-circle"></i> Book New Appointment
            </a>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Total Appointments</h3>
                <div class="number"><?php echo $total_appointments; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3>Upcoming</h3>
                <div class="number"><?php echo $upcoming_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>Completed</h3>
                <div class="number"><?php echo $completed_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-times-circle"></i>
                <h3>Cancelled</h3>
                <div class="number"><?php echo $cancelled_count; ?></div>
            </div>
        </div>

        <!-- Appointments Section with Tabs -->
        <div class="appointments-section">
            <div class="section-tabs">
                <button class="tab-btn active" onclick="showTab('upcoming')"><i class="fas fa-calendar-week"></i> Upcoming (<?php echo $upcoming_count; ?>)</button>
                <button class="tab-btn" onclick="showTab('past')"><i class="fas fa-history"></i> Past Appointments</button>
            </div>

            <!-- Upcoming Appointments Tab -->
            <div id="upcoming" class="tab-content active">
                <?php if ($upcoming_result && mysqli_num_rows($upcoming_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($upcoming_result)): 
                        $appointment_date = strtotime($appointment['appointment_date']);
                        $day = date('d', $appointment_date);
                        $month = date('M', $appointment_date);
                        $year = date('Y', $appointment_date);
                        $time = date('h:i A', strtotime($appointment['appointment_time']));
                    ?>
                        <div class="appointment-card">
                            <div class="appointment-time">
                                <div class="date"><?php echo $day . ' ' . $month . ' ' . $year; ?></div>
                                <div class="time"><?php echo $time; ?></div>
                            </div>
                            
                            <div class="appointment-info">
                                <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                                <span class="doctor-specialty"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                                
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo htmlspecialchars($appointment['qualifications']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-briefcase"></i>
                                        <span><?php echo $appointment['experience_years']; ?> years exp.</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span>LKR <?php echo number_format($appointment['consultation_fee'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="appointment-footer">
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <i class="fas fa-circle"></i> <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                    <?php if (!empty($appointment['symptoms'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-notes-medical"></i>
                                            <span><?php echo htmlspecialchars($appointment['symptoms']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="appointment-actions">
                                <a href="appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> Details
                                </a>
                                
                                <?php if ($appointment['status'] != 'cancelled' && $appointment['status'] != 'completed'): ?>
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="reschedule_appointment" class="action-btn reschedule-btn" style="width: 100%;" onclick="return confirm('Request to reschedule this appointment?')">
                                            <i class="fas fa-calendar-alt"></i> Reschedule
                                        </button>
                                    </form>
                                    
                                    <form method="POST" action="" style="display: block;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                        <button type="submit" name="cancel_appointment" class="action-btn cancel-btn" style="width: 100%;" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-calendar-plus"></i>
                        <h3>No Upcoming Appointments</h3>
                        <p>You don't have any upcoming appointments scheduled.</p>
                        <a href="book-appointment.php" class="book-new-btn">
                            <i class="fas fa-calendar-check"></i> Book Your First Appointment
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Appointments Tab -->
            <div id="past" class="tab-content">
                <?php if ($past_result && mysqli_num_rows($past_result) > 0): ?>
                    <?php while ($appointment = mysqli_fetch_assoc($past_result)): 
                        $appointment_date = strtotime($appointment['appointment_date']);
                        $day = date('d', $appointment_date);
                        $month = date('M', $appointment_date);
                        $year = date('Y', $appointment_date);
                        $time = date('h:i A', strtotime($appointment['appointment_time']));
                    ?>
                        <div class="appointment-card">
                            <div class="appointment-time">
                                <div class="date"><?php echo $day . ' ' . $month . ' ' . $year; ?></div>
                                <div class="time"><?php echo $time; ?></div>
                            </div>
                            
                            <div class="appointment-info">
                                <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                                <span class="doctor-specialty"><?php echo htmlspecialchars($appointment['specialization']); ?></span>
                                
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-graduation-cap"></i>
                                        <span><?php echo htmlspecialchars($appointment['qualifications']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-briefcase"></i>
                                        <span><?php echo $appointment['experience_years']; ?> years exp.</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-tag"></i>
                                        <span>LKR <?php echo number_format($appointment['consultation_fee'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <div class="appointment-footer">
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <i class="fas fa-circle"></i> <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                    <?php if (!empty($appointment['symptoms'])): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-notes-medical"></i>
                                            <span><?php echo htmlspecialchars($appointment['symptoms']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="appointment-actions">
                                <a href="appointment-details.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-sm btn-primary">
    <i class="fas fa-eye"></i> View Details
</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-appointments">
                        <i class="fas fa-folder-open"></i>
                        <h3>No Past Appointments</h3>
                        <p>You don't have any past appointment history.</p>
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