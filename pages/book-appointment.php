<?php
// pages/book-appointment.php
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

// Handle appointment booking
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $doctor_id = mysqli_real_escape_string($conn, $_POST['doctor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $symptoms = mysqli_real_escape_string($conn, $_POST['symptoms']);
    
    // Generate appointment number
    $appointment_number = 'APT-' . date('Ymd') . '-' . rand(100, 999);
    
    $insert_sql = "INSERT INTO appointments (appointment_number, patient_id, doctor_id, appointment_date, appointment_time, symptoms, status) 
                   VALUES ('$appointment_number', $patient_id, $doctor_id, '$appointment_date', '$appointment_time', '$symptoms', 'pending')";
    
    if (mysqli_query($conn, $insert_sql)) {
        $success = "Appointment booked successfully! Your appointment number is: $appointment_number";
    } else {
        $error = "Failed to book appointment: " . mysqli_error($conn);
    }
}

// Get all doctors for display
$doctors_sql = "SELECT d.doctor_id, u.full_name, d.specialization, d.consultation_fee, d.qualifications, d.experience_years 
                FROM doctors d 
                JOIN users u ON d.user_id = u.user_id 
                WHERE d.is_available = 1 
                ORDER BY d.specialization, u.full_name";
$doctors_result = mysqli_query($conn, $doctors_sql);

// Check if doctors query was successful
if (!$doctors_result) {
    $doctors_result = false;
}

// Get unique specializations for filter
$spec_sql = "SELECT DISTINCT specialization FROM doctors WHERE is_available = 1 ORDER BY specialization";
$spec_result = mysqli_query($conn, $spec_sql);

// Check if specializations query was successful
if (!$spec_result) {
    $spec_result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - MediConnect</title>
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
            margin-bottom: 30px;
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

        /* Main Content Grid */
        .booking-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Doctor List Section */
        .doctor-list-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            height: fit-content;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            color: white;
            font-size: 24px;
        }

        .section-header h2 i {
            color: #f0abfc;
            margin-right: 10px;
        }

        .filter-box {
            background: rgba(147, 51, 234, 0.1);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            margin-bottom: 25px;
        }

        .filter-box select {
            width: 100%;
            padding: 12px 15px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(147, 51, 234, 0.4);
            border-radius: 10px;
            color: white;
            font-size: 15px;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23f0abfc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
        }

        .filter-box select option {
            background: #1a1a24;
            color: white;
            padding: 10px;
        }

        .filter-box select:focus {
            outline: none;
            border-color: #f0abfc;
            box-shadow: 0 0 20px rgba(147, 51, 234, 0.3);
        }

        .filter-box select:hover {
            border-color: #f0abfc;
        }

        .doctor-card {
            background: rgba(147, 51, 234, 0.1);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .doctor-card:hover {
            transform: translateY(-3px);
            border-color: #f0abfc;
            box-shadow: 0 10px 30px rgba(147, 51, 234, 0.3);
            background: rgba(147, 51, 234, 0.15);
        }

        .doctor-card.selected {
            border-color: #f0abfc;
            background: rgba(147, 51, 234, 0.2);
            box-shadow: 0 0 30px rgba(147, 51, 234, 0.4);
        }

        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .doctor-name {
            font-size: 20px;
            font-weight: 600;
            color: white;
        }

        .doctor-specialization {
            background: rgba(147, 51, 234, 0.3);
            color: #f0abfc;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .doctor-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
            font-size: 14px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #d8b4fe;
        }

        .detail-item i {
            color: #f0abfc;
            width: 20px;
        }

        .doctor-fee {
            font-size: 18px;
            font-weight: 700;
            color: #f0abfc;
            margin-top: 10px;
        }

        .doctor-fee small {
            font-size: 14px;
            color: #d8b4fe;
            font-weight: normal;
        }

        .select-doctor-btn {
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid rgba(147, 51, 234, 0.5);
            border-radius: 8px;
            color: #f0abfc;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
        }

        .select-doctor-btn:hover {
            background: linear-gradient(135deg, #9333ea, #c084fc);
            color: white;
            border-color: transparent;
        }

        .no-doctors {
            text-align: center;
            padding: 40px;
            color: #d8b4fe;
            background: rgba(147, 51, 234, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(147, 51, 234, 0.3);
        }

        .no-doctors i {
            font-size: 50px;
            color: #f0abfc;
            margin-bottom: 15px;
        }

        /* Booking Form Section */
        .booking-form-section {
            background: rgba(18, 18, 28, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(147, 51, 234, 0.3);
            padding: 30px;
            height: fit-content;
        }

        .selected-doctor-info {
            background: rgba(147, 51, 234, 0.15);
            border: 1px solid rgba(147, 51, 234, 0.4);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .selected-doctor-info h3 {
            color: white;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .selected-doctor-info h3 i {
            color: #f0abfc;
            margin-right: 8px;
        }

        .selected-doctor-name {
            font-size: 22px;
            font-weight: 600;
            color: #f0abfc;
            margin-bottom: 5px;
        }

        .selected-doctor-spec {
            color: #d8b4fe;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #d8b4fe;
            font-weight: 500;
            font-size: 15px;
        }

        .form-group label i {
            color: #f0abfc;
            margin-right: 8px;
        }

        .form-control {
            width: 100%;
            padding: 15px;
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

        .date-time-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-book {
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
            margin-top: 20px;
        }

        .btn-book i {
            margin-right: 10px;
        }

        .btn-book:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(147, 51, 234, 0.5);
        }

        .btn-book:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .info-note {
            background: rgba(147, 51, 234, 0.1);
            border: 1px solid rgba(147, 51, 234, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #d8b4fe;
        }

        .info-note i {
            color: #f0abfc;
            margin-right: 8px;
        }

        @media (max-width: 992px) {
            .booking-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
            }
            
            .container {
                padding: 0 20px;
            }
            
            .date-time-row {
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
            <a href="dashboard-patient.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-calendar-plus"></i> Book an Appointment</h1>
            <p><i class="fas fa-clock"></i> Select a doctor and choose your preferred date & time</p>
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

        <div class="booking-grid">
            <!-- Doctor List Section -->
            <div class="doctor-list-section">
                <div class="section-header">
                    <h2><i class="fas fa-user-md"></i> Available Doctors</h2>
                </div>

                <div class="filter-box">
                    <select id="specializationFilter" onchange="filterDoctors()">
                        <option value="">All Specializations</option>
                        <?php 
                        if ($spec_result && mysqli_num_rows($spec_result) > 0) {
                            while ($spec = mysqli_fetch_assoc($spec_result)) {
                                echo '<option value="' . htmlspecialchars($spec['specialization']) . '">' . htmlspecialchars($spec['specialization']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div id="doctorList">
                    <?php 
                    if ($doctors_result && mysqli_num_rows($doctors_result) > 0) {
                        while ($doctor = mysqli_fetch_assoc($doctors_result)) {
                    ?>
                        <div class="doctor-card" data-specialization="<?php echo htmlspecialchars($doctor['specialization']); ?>" onclick="selectDoctor(<?php echo $doctor['doctor_id']; ?>, '<?php echo htmlspecialchars(addslashes($doctor['full_name'])); ?>', '<?php echo htmlspecialchars($doctor['specialization']); ?>', <?php echo $doctor['consultation_fee']; ?>)">
                            <div class="doctor-header">
                                <span class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></span>
                                <span class="doctor-specialization"><?php echo htmlspecialchars($doctor['specialization']); ?></span>
                            </div>
                            <div class="doctor-details">
                                <div class="detail-item">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span><?php echo htmlspecialchars($doctor['qualifications']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo $doctor['experience_years']; ?> years exp.</span>
                                </div>
                            </div>
                            <div class="doctor-fee">
                                LKR <?php echo number_format($doctor['consultation_fee'], 2); ?> <small>per consultation</small>
                            </div>
                            <button class="select-doctor-btn" onclick="event.stopPropagation(); selectDoctor(<?php echo $doctor['doctor_id']; ?>, '<?php echo htmlspecialchars(addslashes($doctor['full_name'])); ?>', '<?php echo htmlspecialchars($doctor['specialization']); ?>', <?php echo $doctor['consultation_fee']; ?>)">
                                <i class="fas fa-check-circle"></i> Select Doctor
                            </button>
                        </div>
                    <?php 
                        }
                    } else {
                        echo '<div class="no-doctors">';
                        echo '<i class="fas fa-user-md"></i>';
                        echo '<p>No doctors available at the moment. Please check back later.</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Booking Form Section -->
            <div class="booking-form-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar-alt"></i> Appointment Details</h2>
                </div>

                <div id="selectedDoctorInfo" class="selected-doctor-info" style="display: none;">
                    <h3><i class="fas fa-check-circle"></i> Selected Doctor</h3>
                    <div class="selected-doctor-name" id="selectedDoctorName"></div>
                    <div class="selected-doctor-spec" id="selectedDoctorSpec"></div>
                    <div class="doctor-fee" id="selectedDoctorFee" style="margin-top: 10px;"></div>
                </div>

                <form method="POST" action="" id="bookingForm">
                    <input type="hidden" name="doctor_id" id="doctor_id" required>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Appointment Date</label>
                        <input type="date" class="form-control" name="appointment_date" id="appointment_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Appointment Time</label>
                        <select class="form-control" name="appointment_time" id="appointment_time" required>
                            <option value="">Select Time</option>
                            <option value="09:00:00">09:00 AM</option>
                            <option value="09:30:00">09:30 AM</option>
                            <option value="10:00:00">10:00 AM</option>
                            <option value="10:30:00">10:30 AM</option>
                            <option value="11:00:00">11:00 AM</option>
                            <option value="11:30:00">11:30 AM</option>
                            <option value="12:00:00">12:00 PM</option>
                            <option value="12:30:00">12:30 PM</option>
                            <option value="14:00:00">02:00 PM</option>
                            <option value="14:30:00">02:30 PM</option>
                            <option value="15:00:00">03:00 PM</option>
                            <option value="15:30:00">03:30 PM</option>
                            <option value="16:00:00">04:00 PM</option>
                            <option value="16:30:00">04:30 PM</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-notes-medical"></i> Symptoms / Reason for Visit</label>
                        <textarea class="form-control" name="symptoms" rows="4" placeholder="Please describe your symptoms or reason for consultation" required></textarea>
                    </div>

                    <div class="info-note">
                        <i class="fas fa-info-circle"></i>
                        Your appointment will be pending until confirmed by the doctor. You'll receive a notification once confirmed.
                    </div>

                    <button type="submit" name="book_appointment" class="btn-book" id="bookBtn" disabled>
                        <i class="fas fa-calendar-check"></i> Confirm Appointment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let selectedDoctorId = null;

        function filterDoctors() {
            const filter = document.getElementById('specializationFilter').value;
            const cards = document.getElementsByClassName('doctor-card');
            
            for (let i = 0; i < cards.length; i++) {
                const spec = cards[i].getAttribute('data-specialization');
                if (filter === '' || spec === filter) {
                    cards[i].style.display = 'block';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        }

        function selectDoctor(doctorId, doctorName, specialization, fee) {
            // Remove selected class from all cards
            const cards = document.getElementsByClassName('doctor-card');
            for (let i = 0; i < cards.length; i++) {
                cards[i].classList.remove('selected');
            }
            
            // Add selected class to clicked card
            event.currentTarget.closest('.doctor-card').classList.add('selected');
            
            // Update selected doctor info
            selectedDoctorId = doctorId;
            document.getElementById('doctor_id').value = doctorId;
            document.getElementById('selectedDoctorName').textContent = 'Dr. ' + doctorName;
            document.getElementById('selectedDoctorSpec').textContent = specialization;
            document.getElementById('selectedDoctorFee').innerHTML = 'LKR ' + new Intl.NumberFormat().format(fee) + ' <small>per consultation</small>';
            document.getElementById('selectedDoctorInfo').style.display = 'block';
            
            // Enable book button
            document.getElementById('bookBtn').disabled = false;
            
            // Smooth scroll to booking form
            document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Set minimum date for appointment
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const minDate = tomorrow.toISOString().split('T')[0];
        document.getElementById('appointment_date').setAttribute('min', minDate);

        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            if (!selectedDoctorId) {
                e.preventDefault();
                alert('Please select a doctor first');
            }
        });
    </script>
</body>
</html>