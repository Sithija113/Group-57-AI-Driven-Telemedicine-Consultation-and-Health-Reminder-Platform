<?php
// pages/doctor-appointment-details.php - DOCTOR VIEW (with error handling)
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is doctor
if ($_SESSION['user_role'] != 'doctor') {
    header("Location: dashboard-doctors.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get appointment ID from URL
$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($appointment_id == 0) {
    header("Location: dashboard-doctors.php");
    exit();
}

// Get doctor ID
$doctor_sql = "SELECT doctor_id FROM doctors WHERE user_id = $user_id";
$doctor_result = mysqli_query($conn, $doctor_sql);

if (!$doctor_result || mysqli_num_rows($doctor_result) == 0) {
    header("Location: dashboard-doctors.php");
    exit();
}

$doctor = mysqli_fetch_assoc($doctor_result);
$doctor_id = $doctor['doctor_id'];

// Get appointment details with patient information
$appointment_sql = "SELECT a.*, 
                    p.patient_id, p.date_of_birth, p.blood_group, p.gender, p.address, p.emergency_contact,
                    p.medical_history, p.allergies,
                    u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone
                    FROM appointments a
                    LEFT JOIN patients p ON a.patient_id = p.patient_id
                    LEFT JOIN users u ON p.user_id = u.user_id
                    WHERE a.appointment_id = $appointment_id AND a.doctor_id = $doctor_id";

$appointment_result = mysqli_query($conn, $appointment_sql);

if (!$appointment_result || mysqli_num_rows($appointment_result) == 0) {
    header("Location: dashboard-doctors.php");
    exit();
}

$appointment = mysqli_fetch_assoc($appointment_result);

// Check if medical_records table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'medical_records'");
$medical_table_exists = mysqli_num_rows($table_check) > 0;

// Get medical record for this appointment if exists
$medical_record = null;
if ($medical_table_exists) {
    $medical_sql = "SELECT * FROM medical_records WHERE appointment_id = $appointment_id";
    $medical_result = mysqli_query($conn, $medical_sql);
    if ($medical_result && mysqli_num_rows($medical_result) > 0) {
        $medical_record = mysqli_fetch_assoc($medical_result);
    }
} else {
    // Create the table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS medical_records (
        record_id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        appointment_id INT,
        diagnosis TEXT,
        treatment TEXT,
        notes TEXT,
        record_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);
}

// Get prescriptions for this appointment
$prescriptions_sql = "SELECT p.*, m.* 
                      FROM prescriptions p
                      LEFT JOIN medications m ON p.prescription_id = m.prescription_id
                      WHERE p.appointment_id = $appointment_id
                      ORDER BY m.medication_id DESC";
$prescriptions_result = mysqli_query($conn, $prescriptions_sql);

// Handle appointment status update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = mysqli_real_escape_string($conn, $_POST['status']);
        $update_sql = "UPDATE appointments SET status = '$new_status' WHERE appointment_id = $appointment_id";
        
        if (mysqli_query($conn, $update_sql)) {
            $success = "Appointment status updated to " . ucfirst($new_status) . "!";
            $appointment['status'] = $new_status;
        } else {
            $error = "Failed to update status: " . mysqli_error($conn);
        }
    }
    
    // Add diagnosis and treatment
    if (isset($_POST['save_diagnosis'])) {
        $diagnosis = mysqli_real_escape_string($conn, $_POST['diagnosis']);
        $treatment = mysqli_real_escape_string($conn, $_POST['treatment']);
        $notes = mysqli_real_escape_string($conn, $_POST['notes']);
        
        // Check if medical_records table exists and create if not
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'medical_records'");
        if (mysqli_num_rows($table_check) == 0) {
            $create_table = "CREATE TABLE medical_records (
                record_id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                doctor_id INT NOT NULL,
                appointment_id INT,
                diagnosis TEXT,
                treatment TEXT,
                notes TEXT,
                record_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            mysqli_query($conn, $create_table);
        }
        
        // Check if record exists
        $check_record = "SELECT record_id FROM medical_records WHERE appointment_id = $appointment_id";
        $record_result = mysqli_query($conn, $check_record);
        
        if ($record_result && mysqli_num_rows($record_result) > 0) {
            $update_record = "UPDATE medical_records SET 
                              diagnosis = '$diagnosis',
                              treatment = '$treatment',
                              notes = '$notes',
                              record_date = CURDATE()
                              WHERE appointment_id = $appointment_id";
        } else {
            $update_record = "INSERT INTO medical_records 
                              (patient_id, doctor_id, appointment_id, diagnosis, treatment, notes, record_date) 
                              VALUES 
                              ({$appointment['patient_id']}, $doctor_id, $appointment_id, '$diagnosis', '$treatment', '$notes', CURDATE())";
        }
        
        if (mysqli_query($conn, $update_record)) {
            $success = "Diagnosis and treatment saved successfully!";
            // Refresh medical record
            $medical_result = mysqli_query($conn, $medical_sql);
            if ($medical_result && mysqli_num_rows($medical_result) > 0) {
                $medical_record = mysqli_fetch_assoc($medical_result);
            }
        } else {
            $error = "Failed to save diagnosis: " . mysqli_error($conn);
        }
    }
    
    // Add medication
    if (isset($_POST['add_prescription'])) {
        $medicine_name = mysqli_real_escape_string($conn, $_POST['medicine_name']);
        $dosage = mysqli_real_escape_string($conn, $_POST['dosage']);
        $frequency = mysqli_real_escape_string($conn, $_POST['frequency']);
        $duration = mysqli_real_escape_string($conn, $_POST['duration']);
        $instructions = mysqli_real_escape_string($conn, $_POST['instructions']);
        
        // Check if prescription exists
        $check_prescription = "SELECT prescription_id FROM prescriptions WHERE appointment_id = $appointment_id";
        $prescription_result = mysqli_query($conn, $check_prescription);
        
        if ($prescription_result && mysqli_num_rows($prescription_result) > 0) {
            $prescription = mysqli_fetch_assoc($prescription_result);
            $prescription_id = $prescription['prescription_id'];
        } else {
            $insert_prescription = "INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, prescription_date, notes) 
                                    VALUES ($appointment_id, $doctor_id, {$appointment['patient_id']}, CURDATE(), 'Prescription from appointment')";
            mysqli_query($conn, $insert_prescription);
            $prescription_id = mysqli_insert_id($conn);
        }
        
        // Insert medication
        $insert_medication = "INSERT INTO medications (prescription_id, medicine_name, dosage, frequency, duration, instructions) 
                             VALUES ($prescription_id, '$medicine_name', '$dosage', '$frequency', '$duration', '$instructions')";
        
        if (mysqli_query($conn, $insert_medication)) {
            $success = "Medication added successfully!";
            // Refresh prescriptions
            $prescriptions_result = mysqli_query($conn, $prescriptions_sql);
        } else {
            $error = "Failed to add medication: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - Doctor View</title>
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
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

        .info-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
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
            font-size: 12px;
            display: block;
        }

        .info-item .value {
            color: white;
            font-weight: 500;
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

        .section-card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 25px;
            margin-bottom: 30px;
        }

        .section-title {
            color: white;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #f0abfc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d8b4fe;
            font-weight: 500;
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
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(147, 51, 234, 0.4);
        }

        .medication-item {
            background: rgba(147, 51, 234, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            border: 1px solid rgba(147, 51, 234, 0.2);
        }

        .medication-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .medication-name {
            font-size: 18px;
            font-weight: 600;
            color: #f0abfc;
        }

        .medication-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(147, 51, 234, 0.2);
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
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
            <a href="dashboard-doctors.php" class="back-btn"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-notes-medical"></i> Appointment Details</h1>
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

        <!-- Appointment Information -->
        <div class="info-card">
            <h2 style="color: white; margin-bottom: 20px;"><i class="fas fa-calendar-alt" style="color: #f0abfc; margin-right: 10px;"></i> Appointment Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div>
                        <span class="label">Patient Name</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['patient_name']); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                        <span class="label">Date</span>
                        <span class="value"><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></span>
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
                        <span class="label">Status</span>
                        <span class="status-badge status-<?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <span class="label">Phone</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['patient_phone']); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['patient_email']); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-notes-medical"></i>
                    <div>
                        <span class="label">Symptoms</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['symptoms']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Update Status Form -->
            <form method="POST" action="" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(147, 51, 234, 0.3);">
                <div style="display: flex; gap: 15px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label><i class="fas fa-sync-alt"></i> Update Status</label>
                        <select name="status" class="form-control">
                            <option value="pending" <?php echo $appointment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $appointment['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $appointment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $appointment['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" name="update_status" class="btn">Update Status</button>
                </div>
            </form>
        </div>

        <!-- Patient Information -->
        <div class="info-card">
            <h2 style="color: white; margin-bottom: 20px;"><i class="fas fa-id-card" style="color: #f0abfc; margin-right: 10px;"></i> Patient Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <span class="label">Date of Birth</span>
                        <span class="value"><?php echo $appointment['date_of_birth'] ? date('d M Y', strtotime($appointment['date_of_birth'])) : 'Not provided'; ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-venus-mars"></i>
                    <div>
                        <span class="label">Gender</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['gender'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-tint"></i>
                    <div>
                        <span class="label">Blood Group</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['blood_group'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <span class="label">Address</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['address'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div>
                        <span class="label">Emergency Contact</span>
                        <span class="value"><?php echo htmlspecialchars($appointment['emergency_contact'] ?? 'Not provided'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($appointment['medical_history'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(147, 51, 234, 0.05); border-radius: 10px;">
                <h3 style="color: #f0abfc; font-size: 16px; margin-bottom: 10px;"><i class="fas fa-history"></i> Medical History</h3>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($appointment['medical_history'])); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($appointment['allergies'])): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(147, 51, 234, 0.05); border-radius: 10px;">
                <h3 style="color: #f0abfc; font-size: 16px; margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Allergies</h3>
                <p style="color: #d8b4fe;"><?php echo nl2br(htmlspecialchars($appointment['allergies'])); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Diagnosis & Treatment -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-stethoscope"></i>
                <span>Diagnosis & Treatment</span>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-diagnoses"></i> Diagnosis</label>
                    <textarea class="form-control" name="diagnosis" rows="3"><?php echo htmlspecialchars($medical_record['diagnosis'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-pills"></i> Treatment Plan</label>
                    <textarea class="form-control" name="treatment" rows="3"><?php echo htmlspecialchars($medical_record['treatment'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-notes-medical"></i> Additional Notes</label>
                    <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($medical_record['notes'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="save_diagnosis" class="btn">
                    <i class="fas fa-save"></i> Save Diagnosis & Treatment
                </button>
            </form>
        </div>

        <!-- Add Prescription -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-prescription"></i>
                <span>Add Prescription</span>
            </div>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-capsules"></i> Medicine Name</label>
                        <input type="text" class="form-control" name="medicine_name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-weight"></i> Dosage</label>
                        <input type="text" class="form-control" name="dosage" placeholder="e.g., 500mg" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Frequency</label>
                        <input type="text" class="form-control" name="frequency" placeholder="e.g., 3 times daily" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-hourglass-half"></i> Duration</label>
                        <input type="text" class="form-control" name="duration" placeholder="e.g., 7 days" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-info-circle"></i> Instructions</label>
                    <textarea class="form-control" name="instructions" rows="2" placeholder="e.g., Take after meals"></textarea>
                </div>
                
                <button type="submit" name="add_prescription" class="btn">
                    <i class="fas fa-plus-circle"></i> Add Medication
                </button>
            </form>

            <!-- Display Existing Prescriptions -->
            <?php if ($prescriptions_result && mysqli_num_rows($prescriptions_result) > 0): ?>
                <div style="margin-top: 30px;">
                    <h3 style="color: white; margin-bottom: 20px;"><i class="fas fa-prescription-bottle" style="color: #f0abfc; margin-right: 10px;"></i> Prescribed Medications</h3>
                    <?php while ($medication = mysqli_fetch_assoc($prescriptions_result)) {
                        if (isset($medication['medication_id']) && $medication['medication_id']): 
                    ?>
                        <div class="medication-item">
                            <div class="medication-header">
                                <span class="medication-name"><?php echo htmlspecialchars($medication['medicine_name']); ?></span>
                                <span style="color: #f0abfc;"><?php echo htmlspecialchars($medication['dosage']); ?></span>
                            </div>
                            <div class="medication-details">
                                <div><i class="fas fa-clock"></i> <?php echo htmlspecialchars($medication['frequency']); ?></div>
                                <div><i class="fas fa-hourglass-half"></i> <?php echo htmlspecialchars($medication['duration']); ?></div>
                                <?php if (!empty($medication['instructions'])): ?>
                                <div><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($medication['instructions']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    } 
                    ?>
                </div>
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