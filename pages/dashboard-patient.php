<?php
// pages/dashboard-patient.php
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is patient
if ($_SESSION['user_role'] != 'patient') {
    // If not patient, redirect to appropriate dashboard
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: dashboard-admin.php");
    } elseif ($_SESSION['user_role'] == 'doctor') {
        header("Location: dashboard-doctor.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// Get patient ID
$sql = "SELECT patient_id FROM patients WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);

// Check if query was successful
if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
} else {
    // If no patient record exists, create one
    $insert_sql = "INSERT INTO patients (user_id) VALUES ($user_id)";
    mysqli_query($conn, $insert_sql);
    $patient_id = mysqli_insert_id($conn);
}

// Get counts
$appointments_count = 0;
$prescriptions_count = 0;
$reminders_count = 0;

// Get appointments count
$sql_appointments = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = $patient_id";
$result = mysqli_query($conn, $sql_appointments);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $appointments_count = $row['count'];
}

// Get prescriptions count
$sql_prescriptions = "SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = $patient_id";
$result = mysqli_query($conn, $sql_prescriptions);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $prescriptions_count = $row['count'];
}

// Get reminders count
$sql_reminders = "SELECT COUNT(*) as count FROM reminders WHERE patient_id = $patient_id AND status = 'pending'";
$result = mysqli_query($conn, $sql_reminders);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $reminders_count = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MediConnect</title>
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
        }

        .welcome-card h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .welcome-card p {
            color: #d8b4fe;
            font-size: 16px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
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
            font-size: 40px;
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            color: #d8b4fe;
            font-size: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .number {
            color: white;
            font-size: 42px;
            font-weight: 700;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.5);
        }

        /* Section Title */
        .section-title {
            color: white;
            font-size: 24px;
            margin-bottom: 25px;
            font-weight: 600;
            text-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .section-title i {
            background: linear-gradient(135deg, #f0abfc, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-right: 10px;
        }

        /* Action Grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.2), rgba(124, 58, 237, 0.1));
            backdrop-filter: blur(10px);
            color: white;
            padding: 35px 25px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            border: 1px solid rgba(147, 51, 234, 0.3);
        }

        .action-card:hover {
            transform: translateY(-8px);
            border-color: #f0abfc;
            box-shadow: 0 20px 40px rgba(147, 51, 234, 0.4);
            background: linear-gradient(135deg, rgba(147, 51, 234, 0.3), rgba(124, 58, 237, 0.2));
        }

        .action-card i {
            font-size: 45px;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #f0abfc, #e879f9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .action-card h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        /* Appointments Table */
        .appointments-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            margin-top: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .table-header h3 {
            color: white;
            font-size: 20px;
        }

        .view-all {
            color: #f0abfc;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 15px;
            background: rgba(147, 51, 234, 0.1);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            transition: all 0.3s;
        }

        .view-all:hover {
            background: rgba(147, 51, 234, 0.3);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px 10px;
            color: #d8b4fe;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid rgba(147, 51, 234, 0.3);
        }

        td {
            padding: 15px 10px;
            color: #e9d5ff;
            border-bottom: 1px solid rgba(147, 51, 234, 0.2);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-confirmed {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .status-pending {
            background: rgba(146, 64, 14, 0.3);
            color: #fcd34d;
            border: 1px solid #fbbf24;
        }

        .status-cancelled {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
        }

        .btn-small {
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            margin-right: 5px;
            display: inline-block;
            background: rgba(147, 51, 234, 0.2);
            color: #f0abfc;
            border: 1px solid rgba(147, 51, 234, 0.3);
            transition: all 0.3s;
        }

        .btn-small:hover {
            background: rgba(147, 51, 234, 0.4);
            color: white;
        }

        /* Quick Tips */
        .tips-section {
            background: rgba(147, 51, 234, 0.1);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .tips-section i {
            font-size: 40px;
            color: #f0abfc;
        }

        .tips-content h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .tips-content p {
            color: #d8b4fe;
            font-size: 14px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }

            .tips-section {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .action-grid {
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
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>! </h2>
            <p>Your health is our priority. Manage your appointments and health records here.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Total Appointments</h3>
                <div class="number"><?php echo $appointments_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-file-prescription"></i>
                <h3>Prescriptions</h3>
                <div class="number"><?php echo $prescriptions_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3>Pending Reminders</h3>
                <div class="number"><?php echo $reminders_count; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-user-md"></i>
                <h3>Available Doctors</h3>
                <div class="number">2</div>
            </div>
        </div>

        <!-- Quick Actions -->
<h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>

<div class="action-grid">
    <a href="book-appointment.php" class="action-card">
        <i class="fas fa-calendar-plus"></i>
        <h3>Book Appointment</h3>
    </a>
    
    <a href="my-appointments.php" class="action-card">
        <i class="fas fa-list"></i>
        <h3>My Appointments</h3>
    </a>
    
    <a href="chatbot.php" class="action-card">
        <i class="fas fa-robot"></i>
        <h3>AI Assistant</h3>
    </a>
    
    <a href="medications.php" class="action-card">
        <i class="fas fa-pills"></i>
        <h3>Medications</h3>
    </a>
    
    <a href="medical-records.php" class="action-card">
        <i class="fas fa-file-medical"></i>
        <h3>Medical Records</h3>
    </a>
    
    <a href="profile.php" class="action-card">
        <i class="fas fa-user-circle"></i>
        <h3>My Profile</h3>
    </a>
</div>

        <div class="appointments-section">
            <div class="table-header">
                <h3><i class="fas fa-calendar-alt" style="color: #f0abfc; margin-right: 10px;"></i> Recent Appointments</h3>
                <a href="my-appointments.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Specialization</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get recent appointments
                    $sql = "SELECT a.*, d.doctor_id, u.full_name as doctor_name, d.specialization 
                            FROM appointments a 
                            LEFT JOIN doctors d ON a.doctor_id = d.doctor_id 
                            LEFT JOIN users u ON d.user_id = u.user_id 
                            WHERE a.patient_id = $patient_id 
                            ORDER BY a.appointment_date DESC LIMIT 5";
                    $result = mysqli_query($conn, $sql);
                    
                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $status_class = '';
                            if ($row['status'] == 'confirmed') {
                                $status_class = 'status-confirmed';
                            } elseif ($row['status'] == 'pending') {
                                $status_class = 'status-pending';
                            } elseif ($row['status'] == 'cancelled') {
                                $status_class = 'status-cancelled';
                            } else {
                                $status_class = 'status-pending';
                            }
                            
                            $doctor_name = isset($row['doctor_name']) ? $row['doctor_name'] : 'Not Assigned';
                            $specialization = isset($row['specialization']) ? $row['specialization'] : 'General';
                            
                            echo "<tr>";
                            echo "<td>Dr. " . htmlspecialchars($doctor_name) . "</td>";
                            echo "<td>" . htmlspecialchars($specialization) . "</td>";
                            echo "<td>" . date('d M Y', strtotime($row['appointment_date'])) . "</td>";
                            echo "<td>" . date('h:i A', strtotime($row['appointment_time'])) . "</td>";
                            echo "<td><span class='status-badge $status_class'>" . ucfirst($row['status']) . "</span></td>";
                            echo "<td><a href='appointment-details.php?id=" . $row['appointment_id'] . "' class='btn-small'><i class='fas fa-eye'></i> View</a></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; padding: 30px; color: #a78bfa;'>No appointments found. <a href='book-appointment.php' style='color: #f0abfc;'>Book your first appointment</a></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="tips-section">
            <i class="fas fa-lightbulb"></i>
            <div class="tips-content">
                <h4>Quick Tip</h4>
                <p>You can book appointments with specialists 24/7. Our AI assistant can help you find the right doctor based on your symptoms.</p>
            </div>
        </div>
    </div>
</body>
</html>