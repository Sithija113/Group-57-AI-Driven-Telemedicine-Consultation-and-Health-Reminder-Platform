<?php
// pages/reschedule-appointment.php
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
$sql = "SELECT patient_id FROM patients WHERE user_id = $user_id";
$result = mysqli_query($conn, $sql);
if ($result && mysqli_num_rows($result) > 0) {
    $patient = mysqli_fetch_assoc($result);
    $patient_id = $patient['patient_id'];
} else {
    header("Location: my-appointments.php");
    exit();
}

// Get appointment details
$appointment_sql = "SELECT a.*, d.doctor_id, u.full_name as doctor_name, d.specialization, 
                    d.consultation_fee, d.available_from, d.available_to
                    FROM appointments a
                    JOIN doctors d ON a.doctor_id = d.doctor_id
                    JOIN users u ON d.user_id = u.user_id
                    WHERE a.appointment_id = $appointment_id AND a.patient_id = $patient_id";
$appointment_result = mysqli_query($conn, $appointment_sql);

if (!$appointment_result || mysqli_num_rows($appointment_result) == 0) {
    header("Location: my-appointments.php");
    exit();
}

$appointment = mysqli_fetch_assoc($appointment_result);
$doctor_id = $appointment['doctor_id'];

// Handle reschedule
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reschedule'])) {
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Validate date (must be future)
    if (strtotime($new_date) <= strtotime(date('Y-m-d'))) {
        $error = "Please select a future date.";
    } else {
        // Check if the slot is available
        $check_sql = "SELECT * FROM appointments 
                      WHERE doctor_id = $doctor_id 
                      AND appointment_date = '$new_date' 
                      AND appointment_time = '$new_time' 
                      AND status != 'cancelled'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = "This time slot is already booked. Please choose another time.";
        } else {
            // Update the appointment
            $update_sql = "UPDATE appointments 
                          SET appointment_date = '$new_date', 
                              appointment_time = '$new_time',
                              notes = CONCAT(IFNULL(notes, ''), '\n\nRescheduled on: " . date('Y-m-d H:i:s') . "\nReason: $reason'),
                              status = 'pending'
                          WHERE appointment_id = $appointment_id";
            
            if (mysqli_query($conn, $update_sql)) {
                $success = "Appointment rescheduled successfully! Waiting for doctor confirmation.";
                
                // Redirect to my appointments after 2 seconds
                header("refresh:2;url=my-appointments.php");
            } else {
                $error = "Failed to reschedule: " . mysqli_error($conn);
            }
        }
    }
}

// Get doctor's available time slots
$available_times = [
    '09:00:00' => '09:00 AM',
    '09:30:00' => '09:30 AM',
    '10:00:00' => '10:00 AM',
    '10:30:00' => '10:30 AM',
    '11:00:00' => '11:00 AM',
    '11:30:00' => '11:30 AM',
    '12:00:00' => '12:00 PM',
    '12:30:00' => '12:30 PM',
    '14:00:00' => '02:00 PM',
    '14:30:00' => '02:30 PM',
    '15:00:00' => '03:00 PM',
    '15:30:00' => '03:30 PM',
    '16:00:00' => '04:00 PM',
    '16:30:00' => '04:30 PM'
];

// Get booked times for this doctor
$booked_times_sql = "SELECT appointment_date, appointment_time FROM appointments 
                     WHERE doctor_id = $doctor_id AND status != 'cancelled'";
$booked_times_result = mysqli_query($conn, $booked_times_sql);
$booked_times = [];
while ($row = mysqli_fetch_assoc($booked_times_result)) {
    $booked_times[$row['appointment_date']][] = $row['appointment_time'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment - MediConnect</title>
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

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #fff, #e9d5ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-header p {
            color: #d8b4fe;
            font-size: 16px;
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

        .card {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            margin-bottom: 30px;
        }

        .current-appointment {
            background: rgba(147, 51, 234, 0.1);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #f0abfc;
        }

        .current-appointment h3 {
            color: white;
            margin-bottom: 15px;
        }

        .current-appointment p {
            color: #d8b4fe;
            margin: 8px 0;
        }

        .form-group {
            margin-bottom: 20px;
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

        .form-control:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(147, 51, 234, 0.5);
        }

        .btn-secondary {
            background: transparent;
            border: 1px solid #f0abfc;
            color: #f0abfc;
            margin-top: 15px;
        }

        .btn-secondary:hover {
            background: #f0abfc;
            color: #0a0a0f;
        }

        .info-text {
            background: rgba(147, 51, 234, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 13px;
            color: #a78bfa;
            text-align: center;
        }

        .doctor-info {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .doctor-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #9333ea, #c084fc);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .doctor-details h4 {
            color: white;
            margin-bottom: 5px;
        }

        .doctor-details p {
            color: #d8b4fe;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
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
            <h1><i class="fas fa-calendar-alt"></i> Reschedule Appointment</h1>
            <p>Choose a new date and time for your appointment</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="my-appointments.php" class="btn btn-secondary" style="display: inline-block; width: auto; padding: 10px 25px;">
                    <i class="fas fa-arrow-left"></i> Return to My Appointments
                </a>
            </div>
        <?php else: ?>
            <div class="card">
                <!-- Current Appointment Info -->
                <div class="current-appointment">
                    <h3><i class="fas fa-calendar-check"></i> Current Appointment</h3>
                    <p><i class="fas fa-user-md"></i> <strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?> (<?php echo htmlspecialchars($appointment['specialization']); ?>)</p>
                    <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo date('l, d M Y', strtotime($appointment['appointment_date'])); ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                    <p><i class="fas fa-tag"></i> <strong>Fee:</strong> LKR <?php echo number_format($appointment['consultation_fee'], 2); ?></p>
                </div>

                <!-- Doctor Info -->
                <div class="doctor-info">
                    <div class="doctor-avatar">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-details">
                        <h4>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h4>
                        <p><?php echo htmlspecialchars($appointment['specialization']); ?></p>
                        <p>Available: <?php echo date('h:i A', strtotime($appointment['available_from'])); ?> - <?php echo date('h:i A', strtotime($appointment['available_to'])); ?></p>
                    </div>
                </div>

                <!-- Reschedule Form -->
                <form method="POST" action="" id="rescheduleForm">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Select New Date</label>
                        <input type="date" class="form-control" name="new_date" id="new_date" 
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Select New Time</label>
                        <select class="form-control" name="new_time" id="new_time" required>
                            <option value="">Select Time</option>
                            <?php foreach ($available_times as $time => $display): ?>
                                <option value="<?php echo $time; ?>"><?php echo $display; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-edit"></i> Reason for Rescheduling (Optional)</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="Please tell us why you need to reschedule..."></textarea>
                    </div>

                    <div class="info-text">
                        <i class="fas fa-info-circle"></i>
                        Once rescheduled, your appointment will be marked as pending and will need doctor confirmation.
                    </div>

                    <button type="submit" name="reschedule" class="btn">
                        <i class="fas fa-calendar-check"></i> Confirm Reschedule
                    </button>

                    <a href="my-appointments.php" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </form>
            </div>

            <div class="info-text">
                <i class="fas fa-clock"></i>
                Note: Rescheduled appointments require doctor confirmation. You'll receive a notification once confirmed.
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Get the date input element
        const dateInput = document.getElementById('new_date');
        const timeSelect = document.getElementById('new_time');
        
        // Disable time select initially
        timeSelect.disabled = true;
        
        // When date changes, check available times
        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            
            if (selectedDate) {
                // Enable time select
                timeSelect.disabled = false;
                
                // Get all time options
                const options = timeSelect.options;
                
                // Enable all options first
                for (let i = 0; i < options.length; i++) {
                    options[i].disabled = false;
                }
                
                // Check booked times for this date via AJAX
                if (selectedDate) {
                    fetch(`check-availability.php?date=${selectedDate}&doctor_id=<?php echo $doctor_id; ?>`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.booked_times) {
                                // Disable booked time slots
                                for (let i = 0; i < options.length; i++) {
                                    const timeValue = options[i].value;
                                    if (data.booked_times.includes(timeValue)) {
                                        options[i].disabled = true;
                                        options[i].style.opacity = '0.5';
                                    } else {
                                        options[i].disabled = false;
                                        options[i].style.opacity = '1';
                                    }
                                }
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            } else {
                timeSelect.disabled = true;
            }
        });
        
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