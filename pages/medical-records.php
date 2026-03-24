<?php
// pages/medical-records.php
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
$sql = "SELECT patient_id, date_of_birth, blood_group, gender, medical_history, allergies 
        FROM patients WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
} else {
    header("Location: dashboard-patient.php");
    exit();
}

// Handle file upload (if you want to add this feature later)
$upload_success = '';
$upload_error = '';

// Get all medical records
$records_sql = "SELECT mr.*, a.appointment_date, u.full_name as doctor_name, d.specialization
                FROM medical_records mr
                LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
                LEFT JOIN doctors d ON mr.doctor_id = d.doctor_id
                LEFT JOIN users u ON d.user_id = u.user_id
                WHERE mr.patient_id = $patient_id
                ORDER BY mr.record_date DESC, mr.created_at DESC";
$records_result = mysqli_query($conn, $records_sql);

// Get all prescriptions
$prescriptions_sql = "SELECT p.*, u.full_name as doctor_name, d.specialization,
                      GROUP_CONCAT(CONCAT(m.medicine_name, ' ', m.dosage) SEPARATOR '| ') as medicines
                      FROM prescriptions p
                      JOIN doctors d ON p.doctor_id = d.doctor_id
                      JOIN users u ON d.user_id = u.user_id
                      LEFT JOIN medications m ON p.prescription_id = m.prescription_id
                      WHERE p.patient_id = $patient_id
                      GROUP BY p.prescription_id
                      ORDER BY p.prescription_date DESC";
$prescriptions_result = mysqli_query($conn, $prescriptions_sql);

// Get all appointments summary
$appointments_sql = "SELECT a.*, u.full_name as doctor_name, d.specialization
                     FROM appointments a
                     JOIN doctors d ON a.doctor_id = d.doctor_id
                     JOIN users u ON d.user_id = u.user_id
                     WHERE a.patient_id = $patient_id
                     ORDER BY a.appointment_date DESC";
$appointments_result = mysqli_query($conn, $appointments_sql);

// Get counts
$total_records = ($records_result) ? mysqli_num_rows($records_result) : 0;
$total_prescriptions = ($prescriptions_result) ? mysqli_num_rows($prescriptions_result) : 0;
$total_appointments = ($appointments_result) ? mysqli_num_rows($appointments_result) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - MediConnect</title>
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

        /* Patient Info Card */
        .info-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 40px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #d8b4fe;
            padding: 15px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(147, 51, 234, 0.1);
        }

        .info-item i {
            color: #f0abfc;
            font-size: 20px;
            width: 30px;
        }

        .info-item .label {
            color: #a78bfa;
            font-size: 13px;
            display: block;
        }

        .info-item .value {
            color: white;
            font-weight: 500;
            font-size: 16px;
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

        /* Records Grid */
        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .record-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            transition: all 0.3s;
        }

        .record-card:hover {
            transform: translateY(-5px);
            border-color: #f0abfc;
            box-shadow: 0 15px 35px rgba(147, 51, 234, 0.4);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(147, 51, 234, 0.3);
        }

        .record-date {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f0abfc;
            font-size: 14px;
        }

        .record-date i {
            font-size: 16px;
        }

        .record-type {
            background: rgba(147, 51, 234, 0.3);
            color: #f0abfc;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .diagnosis-section {
            margin: 15px 0;
            padding: 15px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border-left: 3px solid #f0abfc;
        }

        .diagnosis-title {
            color: #f0abfc;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .diagnosis-text {
            color: white;
            font-size: 15px;
            line-height: 1.5;
        }

        .treatment-text {
            color: #d8b4fe;
            font-size: 14px;
            line-height: 1.5;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(147, 51, 234, 0.2);
        }

        .notes-text {
            margin-top: 10px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            color: #a78bfa;
            font-size: 13px;
            font-style: italic;
        }

        /* Prescription Card */
        .prescription-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            margin-bottom: 20px;
        }

        .prescription-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .prescription-date {
            color: #f0abfc;
            font-size: 14px;
        }

        .prescription-date i {
            margin-right: 5px;
        }

        .medicines-list {
            margin-top: 15px;
        }

        .medicine-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .medicine-item i {
            color: #f0abfc;
        }

        .medicine-name {
            color: white;
            font-weight: 500;
        }

        /* Appointment Card */
        .appointment-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 20px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
        }

        .appointment-date {
            text-align: center;
            padding: 10px;
            background: rgba(147, 51, 234, 0.15);
            border-radius: 10px;
            min-width: 100px;
        }

        .appointment-date .day {
            font-size: 24px;
            font-weight: 700;
            color: #f0abfc;
        }

        .appointment-date .month {
            font-size: 14px;
            color: #d8b4fe;
        }

        .appointment-info h4 {
            color: white;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .appointment-info p {
            color: #d8b4fe;
            font-size: 13px;
        }

        .appointment-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-completed {
            background: rgba(6, 78, 59, 0.3);
            color: #a7f3d0;
            border: 1px solid #34d399;
        }

        .status-cancelled {
            background: rgba(153, 27, 27, 0.3);
            color: #fecaca;
            border: 1px solid #f87171;
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

        .no-data h3 {
            color: white;
            font-size: 24px;
            margin-bottom: 10px;
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
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .records-grid {
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
            <a href="dashboard-patient.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-notes-medical"></i> Medical Records</h1>
                <p><i class="fas fa-history"></i> Your complete health history</p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-file-medical"></i>
                <h3>Medical Records</h3>
                <div class="number"><?php echo $total_records; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-prescription"></i>
                <h3>Prescriptions</h3>
                <div class="number"><?php echo $total_prescriptions; ?></div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Appointments</h3>
                <div class="number"><?php echo $total_appointments; ?></div>
            </div>
            
        </div>

        <!-- Patient Information Card -->
        <div class="info-card">
            <h2 style="color: white; margin-bottom: 20px;"><i class="fas fa-id-card" style="color: #f0abfc; margin-right: 10px;"></i> Personal Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <span class="label">Date of Birth</span>
                        <span class="value"><?php echo $patient['date_of_birth'] ? date('d M Y', strtotime($patient['date_of_birth'])) : 'Not provided'; ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-venus-mars"></i>
                    <div>
                        <span class="label">Gender</span>
                        <span class="value"><?php echo htmlspecialchars($patient['gender'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-tint"></i>
                    <div>
                        <span class="label">Blood Group</span>
                        <span class="value"><?php echo htmlspecialchars($patient['blood_group'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($patient['medical_history'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(147, 51, 234, 0.05); border-radius: 10px;">
                <h3 style="color: #f0abfc; font-size: 16px; margin-bottom: 10px;"><i class="fas fa-history"></i> Medical History</h3>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($patient['medical_history'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($patient['allergies'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(147, 51, 234, 0.05); border-radius: 10px;">
                <h3 style="color: #f0abfc; font-size: 16px; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Allergies</h3>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($patient['allergies'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tabs Navigation -->
        <div class="section-tabs">
            <button class="tab-btn active" onclick="showTab('records')"><i class="fas fa-file-medical"></i> Medical Records</button>
            <button class="tab-btn" onclick="showTab('prescriptions')"><i class="fas fa-prescription"></i> Prescriptions</button>
            <button class="tab-btn" onclick="showTab('appointments')"><i class="fas fa-calendar-alt"></i> Appointment History</button>
        </div>

        <!-- Medical Records Tab -->
        <div id="records" class="tab-content active">
            <?php if ($records_result && mysqli_num_rows($records_result) > 0): ?>
                <div class="records-grid">
                    <?php while ($record = mysqli_fetch_assoc($records_result)): ?>
                        <div class="record-card">
                            <div class="record-header">
                                <div class="record-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('d M Y', strtotime($record['record_date'])); ?>
                                </div>
                                <span class="record-type">Medical Record</span>
                            </div>
                            
                            <div class="doctor-info">
                                <i class="fas fa-user-md"></i>
                                <span>Dr. <?php echo htmlspecialchars($record['doctor_name'] ?? 'Unknown'); ?> (<?php echo htmlspecialchars($record['specialization'] ?? 'General'); ?>)</span>
                            </div>
                            
                            <?php if (!empty($record['diagnosis'])): ?>
                            <div class="diagnosis-section">
                                <div class="diagnosis-title">Diagnosis</div>
                                <div class="diagnosis-text"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($record['treatment'])): ?>
                            <div class="treatment-text">
                                <strong>Treatment:</strong> <?php echo nl2br(htmlspecialchars($record['treatment'])); ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($record['notes'])): ?>
                            <div class="notes-text">
                                <i class="fas fa-sticky-note"></i> <?php echo nl2br(htmlspecialchars($record['notes'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-notes-medical"></i>
                    <h3>No Medical Records Found</h3>
                    <p>Your medical records will appear here after your doctor visits.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prescriptions Tab -->
        <div id="prescriptions" class="tab-content">
            <?php if ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0): ?>
                <?php while ($prescription = mysqli_fetch_assoc($prescriptions_result)): ?>
                    <div class="prescription-card">
                        <div class="prescription-header">
                            <div>
                                <h3 style="color: white; font-size: 18px;">Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></h3>
                                <p style="color: #d8b4fe; font-size: 14px;"><?php echo htmlspecialchars($prescription['specialization']); ?></p>
                            </div>
                            <div class="prescription-date">
                                <i class="fas fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($prescription['prescription_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="medicines-list">
                            <?php 
                            $medicines = explode('|', $prescription['medicines']);
                            foreach ($medicines as $medicine):
                                if (!empty(trim($medicine))):
                            ?>
                                <div class="medicine-item">
                                    <i class="fas fa-capsules"></i>
                                    <span class="medicine-name"><?php echo htmlspecialchars($medicine); ?></span>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <?php if (!empty($prescription['notes'])): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(147, 51, 234, 0.2); color: #a78bfa; font-size: 13px;">
                                <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($prescription['notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-prescription"></i>
                    <h3>No Prescriptions Found</h3>
                    <p>Your prescriptions will appear here after your doctor visits.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Appointment History Tab -->
        <div id="appointments" class="tab-content">
            <?php if ($appointments_result && mysqli_num_rows($appointments_result) > 0): ?>
                <?php while ($appointment = mysqli_fetch_assoc($appointments_result)): 
                    $appointment_date = strtotime($appointment['appointment_date']);
                ?>
                    <div class="appointment-card">
                        <div class="appointment-date">
                            <div class="day"><?php echo date('d', $appointment_date); ?></div>
                            <div class="month"><?php echo date('M Y', $appointment_date); ?></div>
                        </div>
                        <div class="appointment-info">
                            <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                            <p><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                            <p><i class="fas fa-clock" style="color: #f0abfc;"></i> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                            <?php if (!empty($appointment['symptoms'])): ?>
                                <p><i class="fas fa-notes-medical" style="color: #f0abfc;"></i> <?php echo htmlspecialchars($appointment['symptoms']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Appointment History</h3>
                    <p>You haven't had any appointments yet.</p>
                    <a href="book-appointment.php" class="back-btn" style="display: inline-block; margin-top: 20px; padding: 12px 30px;">
                        <i class="fas fa-calendar-plus"></i> Book an Appointment
                    </a>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>