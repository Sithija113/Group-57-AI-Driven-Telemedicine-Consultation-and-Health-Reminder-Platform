<?php
// pages/appointment-details.php - PATIENT VIEW
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

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id == 0) {
    header("Location: my-appointments.php");
    exit();
}

// Get patient ID
$patient_sql = "SELECT patient_id FROM patients WHERE user_id = $user_id";
$patient_result = mysqli_query($conn, $patient_sql);

if (!$patient_result || mysqli_num_rows($patient_result) == 0) {
    header("Location: my-appointments.php");
    exit();
}

$patient = mysqli_fetch_assoc($patient_result);
$patient_id = $patient['patient_id'];

// Get appointment details with doctor information
$appointment_sql = "SELECT a.*, 
                    d.doctor_id, d.specialization, d.qualifications, d.experience_years, 
                    d.consultation_fee, d.about as doctor_about,
                    u.full_name as doctor_name, u.email as doctor_email, u.phone as doctor_phone
                    FROM appointments a
                    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                    LEFT JOIN users u ON d.user_id = u.user_id
                    WHERE a.appointment_id = $appointment_id AND a.patient_id = $patient_id";

$appointment_result = mysqli_query($conn, $appointment_sql);

if (!$appointment_result || mysqli_num_rows($appointment_result) == 0) {
    header("Location: my-appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($appointment_result);

// Get medical record for this appointment if exists
$medical_sql = "SELECT * FROM medical_records WHERE appointment_id = $appointment_id";
$medical_result = mysqli_query($conn, $medical_sql);
$medical_record = ($medical_result && mysqli_num_rows($medical_result) > 0) ? mysqli_fetch_assoc($medical_result) : null;

// Get prescriptions for this appointment
$prescriptions_sql = "SELECT p.*, m.* 
                      FROM prescriptions p
                      LEFT JOIN medications m ON p.prescription_id = m.prescription_id
                      WHERE p.appointment_id = $appointment_id
                      ORDER BY m.medication_id DESC";
$prescriptions_result = mysqli_query($conn, $prescriptions_sql);

// Handle appointment cancellation
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_appointment'])) {
    if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed') {
        $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = $appointment_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Appointment cancelled successfully!";
            $appointment['status'] = 'cancelled';
        } else {
            $error = "Failed to cancel appointment: " . mysqli_error($conn);
        }
    } else {
        $error = "This appointment cannot be cancelled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Patient</title>
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
            background: rgba(18, 18, 28, 0.9);
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
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

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

        .appointment-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
            flex-wrap: wrap;
            gap: 15px;
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
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

        .appointment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(147, 51, 234, 0.1);
        }

        .info-item i {
            color: #f0abfc;
            font-size: 24px;
            width: 30px;
        }

        .info-item .label {
            color: #a78bfa;
            font-size: 12px;
            display: block;
        }

        .info-item .value {
            color: white;
            font-weight: 500;
            font-size: 16px;
        }

        .symptoms-box {
            margin-top: 20px;
            padding: 20px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border-left: 4px solid #f0abfc;
        }

        .doctor-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }

        .doctor-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .doctor-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #9333ea, #c084fc);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: white;
        }

        .doctor-info h2 {
            color: white;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .doctor-info .specialization {
            color: #f0abfc;
            font-size: 16px;
        }

        .doctor-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .doctor-detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #d8b4fe;
        }

        .doctor-detail-item i {
            color: #f0abfc;
            width: 20px;
        }

        .medical-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }

        .medication-item {
            background: rgba(147, 51, 234, 0.05);
            border: 1px solid rgba(147, 51, 234, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 15px;
            align-items: center;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(147, 51, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #f0abfc;
            color: #f0abfc;
        }

        .btn-secondary:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        .btn-danger {
            background: transparent;
            border: 1px solid #f87171;
            color: #f87171;
        }

        .btn-danger:hover {
            background: #f87171;
            color: white;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .doctor-header {
                flex-direction: column;
                text-align: center;
            }
            
            .medication-item {
                grid-template-columns: 1fr;
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
            <a href="my-appointments.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Appointments</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> Appointment Details</h1>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Appointment Information -->
        <div class="appointment-card">
            <div class="appointment-header">
                <div class="appointment-id">
                    <i class="fas fa-hashtag"></i> Appointment ID: <span style="color: #f0abfc;">#<?php echo $appointment_id; ?></span>
                </div>
                <span class="status-badge status-<?php echo $appointment['status']; ?>">
                    <i class="fas fa-circle"></i> <?php echo ucfirst($appointment['status']); ?>
                </span>
            </div>

            <div class="appointment-grid">
                <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span class="label">Date</span>
                        <span class="value"><?php echo date('l, d M Y', strtotime($appointment['appointment_date'])); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <span class="label">Time</span>
                        <span class="value"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <div>
                        <span class="label">Consultation Fee</span>
                        <span class="value">LKR <?php echo number_format($appointment['consultation_fee'] ?? 0, 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="symptoms-box">
                <h3><i class="fas fa-notes-medical"></i> Symptoms / Reason for Visit</h3>
                <p><?php echo nl2br(htmlspecialchars($appointment['symptoms'] ?? 'No symptoms provided')); ?></p>
            </div>

            <?php if ($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
            <div class="action-buttons">
                <a href="reschedule-appointment.php?id=<?php echo $appointment_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                </a>
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="cancel_appointment" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this appointment?')">
                        <i class="fas fa-times"></i> Cancel Appointment
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Doctor Information -->
        <div class="doctor-card">
            <div class="doctor-header">
                <div class="doctor-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="doctor-info">
                    <h2>Dr. <?php echo htmlspecialchars($appointment['doctor_name'] ?? 'Not Assigned'); ?></h2>
                    <div class="specialization"><?php echo htmlspecialchars($appointment['specialization'] ?? 'General Physician'); ?></div>
                </div>
            </div>

            <div class="doctor-details">
                <div class="doctor-detail-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Qualifications: <?php echo htmlspecialchars($appointment['qualifications'] ?? 'MBBS'); ?></span>
                </div>
                <div class="doctor-detail-item">
                    <i class="fas fa-briefcase"></i>
                    <span>Experience: <?php echo $appointment['experience_years'] ?? 0; ?> years</span>
                </div>
                <div class="doctor-detail-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email: <?php echo htmlspecialchars($appointment['doctor_email'] ?? 'Not available'); ?></span>
                </div>
                <div class="doctor-detail-item">
                    <i class="fas fa-phone"></i>
                    <span>Phone: <?php echo htmlspecialchars($appointment['doctor_phone'] ?? 'Not available'); ?></span>
                </div>
            </div>

            <?php if (!empty($appointment['doctor_about'])): ?>
            <div class="doctor-about" style="margin-top: 20px; padding: 20px; background: rgba(147, 51, 234, 0.05); border-radius: 12px;">
                <i class="fas fa-quote-right" style="color: #f0abfc; margin-right: 8px;"></i>
                <?php echo nl2br(htmlspecialchars($appointment['doctor_about'])); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Medical Records & Prescriptions -->
        <?php if ($medical_record || ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0)): ?>
        <div class="medical-section">
            <div class="section-title" style="color: white; font-size: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-notes-medical" style="color: #f0abfc;"></i>
                <span>Medical Records & Prescriptions</span>
            </div>

            <?php if ($medical_record): ?>
            <div class="diagnosis-box" style="background: rgba(147, 51, 234, 0.1); border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #f0abfc; margin-bottom: 10px;"><i class="fas fa-stethoscope"></i> Diagnosis</h4>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($medical_record['diagnosis'] ?? 'No diagnosis recorded')); ?></p>
                
                <?php if (!empty($medical_record['treatment'])): ?>
                <h4 style="margin-top: 20px; color: #f0abfc;"><i class="fas fa-pills"></i> Treatment</h4>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($medical_record['treatment'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($medical_record['notes'])): ?>
                <h4 style="margin-top: 20px; color: #f0abfc;"><i class="fas fa-sticky-note"></i> Additional Notes</h4>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($medical_record['notes'])); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0): ?>
            <div class="medications-list">
                <h4 style="color: #f0abfc; margin-bottom: 15px;"><i class="fas fa-prescription"></i> Prescribed Medications</h4>
                <?php 
                $medications_found = false;
                while ($medication = mysqli_fetch_assoc($prescriptions_result)) {
                    if (isset($medication['medication_id']) && $medication['medication_id']): 
                        $medications_found = true;
                ?>
                <div class="medication-item">
                    <i class="fas fa-capsules"></i>
                    <div class="medication-info">
                        <h4 style="color: white;"><?php echo htmlspecialchars($medication['medicine_name'] ?? 'Unknown'); ?></h4>
                        <p style="color: #d8b4fe;"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($medication['frequency'] ?? 'Not specified'); ?> for <?php echo htmlspecialchars($medication['duration'] ?? 'Not specified'); ?></p>
                        <?php if (!empty($medication['instructions'])): ?>
                        <p style="color: #d8b4fe;"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($medication['instructions']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="medication-dosage" style="color: #f0abfc; font-weight: 600;">
                        <?php echo htmlspecialchars($medication['dosage'] ?? 'N/A'); ?>
                    </div>
                </div>
                <?php 
                    endif;
                } 
                if (!$medications_found) {
                    echo '<p style="color: #d8b4fe; text-align: center;">No medications prescribed for this appointment.</p>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="action-buttons" style="justify-content: center;">
            <a href="my-appointments.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Appointments
            </a>
            <?php if ($appointment['status'] == 'completed' && isset($appointment['doctor_id'])): ?>
            <a href="book-appointment.php?doctor=<?php echo $appointment['doctor_id']; ?>" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i> Book Again with Same Doctor
            </a>
            <?php endif; ?>
        </div>
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