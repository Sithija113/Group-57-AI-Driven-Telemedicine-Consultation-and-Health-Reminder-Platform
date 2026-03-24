<?php
// pages/medications.php - UPDATED to show all medications
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
    header("Location: dashboard-patient.php");
    exit();
}

// Handle reminder status update (mark as taken)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_taken'])) {
    $reminder_id = mysqli_real_escape_string($conn, $_POST['reminder_id']);
    
    $update_sql = "UPDATE reminders SET status = 'taken', taken_at = NOW() WHERE reminder_id = $reminder_id AND patient_id = $patient_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $success = "Medication marked as taken!";
    } else {
        $error = "Failed to update: " . mysqli_error($conn);
    }
}

// Handle snooze reminder
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['snooze'])) {
    $reminder_id = mysqli_real_escape_string($conn, $_POST['reminder_id']);
    
    $update_sql = "UPDATE reminders SET reminder_time = ADDTIME(reminder_time, '01:00:00') WHERE reminder_id = $reminder_id AND patient_id = $patient_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $success = "Reminder snoozed for 1 hour!";
    } else {
        $error = "Failed to snooze: " . mysqli_error($conn);
    }
}

// ========== FIXED: Get all medications for this patient ==========
// This query joins prescriptions, medications, doctors, and users
$medications_sql = "SELECT m.*, 
                    p.prescription_id, p.prescription_date, p.notes as prescription_notes,
                    d.doctor_id, u.full_name as doctor_name, d.specialization,
                    DATE_FORMAT(p.prescription_date, '%d %M %Y') as formatted_date
                    FROM medications m
                    JOIN prescriptions p ON m.prescription_id = p.prescription_id
                    JOIN doctors d ON p.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    WHERE p.patient_id = $patient_id
                    ORDER BY p.prescription_date DESC, m.medication_id DESC";

$medications_result = mysqli_query($conn, $medications_sql);

// Debug: Check if query worked
if (!$medications_result) {
    $error = "Query failed: " . mysqli_error($conn);
    $medications_result = false;
}

// Get active reminders for today
$today = date('Y-m-d');
$reminders_sql = "SELECT r.*, m.medicine_name, m.dosage, m.instructions,
                 p.doctor_id, u.full_name as doctor_name
                 FROM reminders r
                 JOIN medications m ON r.medication_id = m.medication_id
                 JOIN prescriptions p ON m.prescription_id = p.prescription_id
                 JOIN doctors d ON p.doctor_id = d.doctor_id
                 JOIN users u ON d.user_id = u.user_id
                 WHERE r.patient_id = $patient_id 
                 AND r.reminder_date = '$today'
                 AND r.status = 'pending'
                 ORDER BY r.reminder_time ASC";
$reminders_result = mysqli_query($conn, $reminders_sql);

// Get medication statistics
$total_medications_sql = "SELECT COUNT(DISTINCT m.medication_id) as count 
                          FROM medications m
                          JOIN prescriptions p ON m.prescription_id = p.prescription_id
                          WHERE p.patient_id = $patient_id";
$total_result = mysqli_query($conn, $total_medications_sql);
$total_medications = ($total_result && $row = mysqli_fetch_assoc($total_result)) ? $row['count'] : 0;

$active_reminders_sql = "SELECT COUNT(*) as count FROM reminders 
                         WHERE patient_id = $patient_id 
                         AND reminder_date >= CURDATE() 
                         AND status = 'pending'";
$active_result = mysqli_query($conn, $active_reminders_sql);
$active_reminders = ($active_result && $row = mysqli_fetch_assoc($active_result)) ? $row['count'] : 0;

$taken_today_sql = "SELECT COUNT(*) as count FROM reminders 
                    WHERE patient_id = $patient_id 
                    AND reminder_date = CURDATE() 
                    AND status = 'taken'";
$taken_result = mysqli_query($conn, $taken_today_sql);
$taken_today = ($taken_result && $row = mysqli_fetch_assoc($taken_result)) ? $row['count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Medications - MediConnect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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

        .navbar {
            background: rgba(18, 18, 28, 0.8);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
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

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

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

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        }

        .stat-card .number {
            color: white;
            font-size: 32px;
            font-weight: 700;
        }

        .reminders-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 40px;
        }

        .section-title {
            color: white;
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #f0abfc;
        }

        .reminder-card {
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

        .reminder-time {
            text-align: center;
            padding: 10px 15px;
            background: rgba(147, 51, 234, 0.2);
            border-radius: 12px;
            min-width: 100px;
        }

        .reminder-time .time {
            font-size: 20px;
            font-weight: 700;
            color: #f0abfc;
        }

        .reminder-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .taken-btn {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
        }

        .snooze-btn {
            background: transparent;
            color: #fbbf24;
            border: 1px solid #fbbf24;
        }

        .medications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .medication-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            transition: all 0.3s;
        }

        .medication-card:hover {
            transform: translateY(-5px);
            border-color: #f0abfc;
        }

        .medication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
        }

        .medication-name {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .medication-dosage {
            background: rgba(147, 51, 234, 0.3);
            color: #f0abfc;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d8b4fe;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .doctor-info i {
            color: #f0abfc;
        }

        .medication-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #d8b4fe;
            font-size: 13px;
        }

        .detail-item i {
            color: #f0abfc;
            width: 20px;
        }

        .instructions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(147, 51, 234, 0.2);
            color: #d8b4fe;
            font-size: 13px;
        }

        .no-data {
            text-align: center;
            padding: 60px 40px;
            color: #d8b4fe;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 20px;
            border: 1px dashed rgba(147, 51, 234, 0.3);
        }

        .no-data i {
            font-size: 60px;
            color: #f0abfc;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .container {
                padding: 0 20px;
            }
            
            .reminder-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .reminder-actions {
                justify-content: center;
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
        <div class="page-header">
            <h1><i class="fas fa-pills"></i> My Medications</h1>
            <p><i class="fas fa-clock"></i> Track and manage your medications & reminders</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-capsules"></i>
                <h3>Total Medications</h3>
                <div class="number"><?php echo $total_medications; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-bell"></i>
                <h3>Active Reminders</h3>
                <div class="number"><?php echo $active_reminders; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3>Taken Today</h3>
                <div class="number"><?php echo $taken_today; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Adherence Rate</h3>
                <div class="number">
                    <?php 
                    $total_reminders_today = $active_reminders + $taken_today;
                    $rate = $total_reminders_today > 0 ? round(($taken_today / $total_reminders_today) * 100) : 0;
                    echo $rate . '%';
                    ?>
                </div>
            </div>
        </div>

        <!-- Today's Reminders Section -->
        <div class="reminders-section">
            <div class="section-title">
                <i class="fas fa-bell"></i>
                <span>Today's Reminders</span>
            </div>

            <?php if ($reminders_result && mysqli_num_rows($reminders_result) > 0): ?>
                <?php while ($reminder = mysqli_fetch_assoc($reminders_result)): ?>
                    <div class="reminder-card">
                        <div class="reminder-time">
                            <div class="time"><?php echo date('h:i A', strtotime($reminder['reminder_time'])); ?></div>
                        </div>
                        <div class="reminder-info">
                            <h3><?php echo htmlspecialchars($reminder['medicine_name']); ?> <?php echo htmlspecialchars($reminder['dosage']); ?></h3>
                            <div class="doctor-name">Prescribed by Dr. <?php echo htmlspecialchars($reminder['doctor_name']); ?></div>
                            <?php if (!empty($reminder['instructions'])): ?>
                                <div class="details">
                                    <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($reminder['instructions']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="reminder-actions">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                                <button type="submit" name="mark_taken" class="action-btn taken-btn">
                                    <i class="fas fa-check"></i> Mark Taken
                                </button>
                            </form>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="reminder_id" value="<?php echo $reminder['reminder_id']; ?>">
                                <button type="submit" name="snooze" class="action-btn snooze-btn">
                                    <i class="fas fa-clock"></i> Snooze
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data" style="padding: 40px;">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Reminders for Today</h3>
                    <p>You have no pending medication reminders for today.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Medications Section -->
        <div class="section-title" style="margin-bottom: 25px;">
            <i class="fas fa-capsules"></i>
            <span>My Prescribed Medications</span>
        </div>

        <?php if ($medications_result && mysqli_num_rows($medications_result) > 0): ?>
            <div class="medications-grid">
                <?php while ($medication = mysqli_fetch_assoc($medications_result)): ?>
                    <div class="medication-card">
                        <div class="medication-header">
                            <span class="medication-name"><?php echo htmlspecialchars($medication['medicine_name']); ?></span>
                            <span class="medication-dosage"><?php echo htmlspecialchars($medication['dosage']); ?></span>
                        </div>
                        
                        <div class="doctor-info">
                            <i class="fas fa-user-md"></i>
                            <span>Dr. <?php echo htmlspecialchars($medication['doctor_name']); ?> (<?php echo htmlspecialchars($medication['specialization']); ?>)</span>
                        </div>
                        
                        <div class="medication-details">
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span>Frequency: <?php echo htmlspecialchars($medication['frequency']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-hourglass-half"></i>
                                <span>Duration: <?php echo htmlspecialchars($medication['duration']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Prescribed: <?php echo $medication['formatted_date']; ?></span>
                            </div>
                        </div>

                        <?php if (!empty($medication['instructions'])): ?>
                            <div class="instructions">
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($medication['instructions']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-prescription-bottle"></i>
                <h3>No Medications Found</h3>
                <p>You don't have any prescribed medications yet. They will appear here after your doctor appointments.</p>
                <a href="book-appointment.php" class="back-btn" style="display: inline-block; margin-top: 20px; padding: 12px 30px;">
                    <i class="fas fa-calendar-plus"></i> Book an Appointment
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
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