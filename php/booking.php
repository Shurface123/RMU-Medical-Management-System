<?php
// AUTHENTICATION CHECK - Require login to book appointments
require_once 'includes/auth_middleware.php';
requireAuth('index.php'); // Redirect to login if not authenticated

require_once 'db_conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - RMU Medical Sickbay</title>
    <link rel="shortcut icon" href="https://juniv.edu/images/favicon.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="../css/main.css">
    
    <style>
        :root {
            --primary-color: #2F80ED;
            --primary-dark: #2366CC;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --text-dark: #2c3e50;
            --white: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #2F80ED 0%, #56CCF2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        .booking-container {
            max-width: 900px;
            margin: 2rem auto;
            background: var(--white);
            border-radius: 24px;
            box-shadow: 0px 20px 60px rgba(47, 128, 237, 0.2);
            overflow: hidden;
        }

        .booking-header {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: var(--white);
            padding: 3rem;
            text-align: center;
        }

        .booking-header h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .booking-header p {
            font-size: 1.6rem;
            opacity: 0.95;
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            padding: 3rem;
            background: #f8f9fa;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 3px;
            background: #dee2e6;
            z-index: 1;
        }

        .step:first-child::before {
            display: none;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            font-weight: 700;
            position: relative;
            z-index: 2;
            transition: all 0.3s;
        }

        .step.active .step-number {
            background: var(--primary-color);
            color: var(--white);
        }

        .step.completed .step-number {
            background: var(--success-color);
            color: var(--white);
        }

        .step.completed::before {
            background: var(--success-color);
        }

        .step-label {
            font-size: 1.4rem;
            color: #6c757d;
            font-weight: 600;
        }

        .step.active .step-label {
            color: var(--primary-color);
        }

        /* Form Content */
        .booking-content {
            padding: 3rem;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .form-control {
            width: 100%;
            padding: 1.2rem 1.5rem;
            font-size: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 1rem;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(47, 128, 237, 0.1);
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Doctor Cards */
        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .doctor-card {
            border: 2px solid #e0e0e0;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .doctor-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(22, 160, 133, 0.2);
        }

        .doctor-card.selected {
            border-color: var(--primary-color);
            background: rgba(22, 160, 133, 0.05);
        }

        .doctor-card input[type="radio"] {
            display: none;
        }

        .doctor-card .doctor-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .doctor-card .doctor-icon i {
            font-size: 3.5rem;
            color: var(--white);
        }

        .doctor-card h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .doctor-card p {
            font-size: 1.3rem;
            color: #6c757d;
        }

        /* Confirmation Summary */
        .summary-box {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #dee2e6;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .summary-value {
            color: #6c757d;
        }

        /* Buttons */
        .form-buttons {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            margin-top: 3rem;
        }

        .btn {
            padding: 1.3rem 3rem;
            font-size: 1.6rem;
            font-weight: 600;
            border: none;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2F80ED, #56CCF2);
            color: var(--white);
            flex: 1;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: var(--white);
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .success-message {
            text-align: center;
            padding: 3rem;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--success-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }

        .success-icon i {
            font-size: 5rem;
            color: var(--white);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .progress-steps {
                flex-direction: column;
                gap: 2rem;
            }

            .step::before {
                display: none;
            }

            .doctor-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Back to Home Button */
        .back-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 100;
        }

        .back-home a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.5rem;
            font-weight: 600;
            box-shadow: 0px 10px 30px rgba(47, 128, 237, 0.2);
            transition: all 0.3s;
        }

        .back-home a:hover {
            background: var(--white);
            transform: translateY(-2px);
            box-shadow: 0px 15px 40px rgba(47, 128, 237, 0.3);
        }
    </style>
</head>
<body>
    <!-- Back to Home Button -->
    <div class="back-home">
        <a href="/RMU-Medical-Management-System/html/index.html">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
    </div>

    <div class="booking-container">
            <h1><i class="fas fa-calendar-check"></i> Book Appointment</h1>
            <p>Schedule your visit to RMU Medical Sickbay</p>
        </div>

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step active" id="step1Indicator">
                <div class="step-number">1</div>
                <div class="step-label">Patient Info</div>
            </div>
            <div class="step" id="step2Indicator">
                <div class="step-number">2</div>
                <div class="step-label">Select Doctor</div>
            </div>
            <div class="step" id="step3Indicator">
                <div class="step-number">3</div>
                <div class="step-label">Date & Time</div>
            </div>
            <div class="step" id="step4Indicator">
                <div class="step-number">4</div>
                <div class="step-label">Confirm</div>
            </div>
        </div>

        <!-- Booking Form -->
        <div class="booking-content">
            <form action="booking_handler.php" method="POST" id="bookingForm">
                
                <!-- Step 1: Patient Information -->
                <div class="form-step active" id="step1">
                    <h2 style="margin-bottom: 2rem;">Patient Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_name">Full Name *</label>
                            <input type="text" id="patient_name" name="patient_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="patient_email">Email Address *</label>
                            <input type="email" id="patient_email" name="patient_email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_phone">Phone Number *</label>
                            <input type="tel" id="patient_phone" name="patient_phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="patient_age">Age *</label>
                            <input type="number" id="patient_age" name="patient_age" class="form-control" min="1" max="120" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patient_gender">Gender *</label>
                            <select id="patient_gender" name="patient_gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="patient_type">Patient Type *</label>
                            <select id="patient_type" name="patient_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="Student">Student</option>
                                <option value="Staff">Staff</option>
                                <option value="Visitor">Visitor</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Doctor -->
                <div class="form-step" id="step2">
                    <h2 style="margin-bottom: 2rem;">Select Doctor</h2>
                    <div class="doctor-grid">
                        <?php
                        $doctors_query = "SELECT * FROM doctor LIMIT 6";
                        $doctors_result = mysqli_query($conn, $doctors_query);
                        
                        if (mysqli_num_rows($doctors_result) > 0) {
                            while ($doctor = mysqli_fetch_assoc($doctors_result)) {
                                echo '<label class="doctor-card">';
                                echo '<input type="radio" name="doctor_id" value="' . $doctor['D_ID'] . '" required>';
                                echo '<div class="doctor-icon"><i class="fas fa-user-md"></i></div>';
                                echo '<h3>' . htmlspecialchars($doctor['D_Name']) . '</h3>';
                                echo '<p>' . htmlspecialchars($doctor['D_Specialist']) . '</p>';
                                echo '</label>';
                            }
                        } else {
                            echo '<p>No doctors available. Please contact administration.</p>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Step 3: Date & Time -->
                <div class="form-step" id="step3">
                    <h2 style="margin-bottom: 2rem;">Select Date & Time</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appointment_date">Appointment Date *</label>
                            <input type="date" id="appointment_date" name="appointment_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="appointment_time">Preferred Time *</label>
                            <select id="appointment_time" name="appointment_time" class="form-control" required>
                                <option value="">Select Time</option>
                                <option value="08:00">08:00 AM</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:00">04:00 PM</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group full-width">
                        <label for="appointment_type">Appointment Type *</label>
                        <select id="appointment_type" name="appointment_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="Consultation">General Consultation</option>
                            <option value="Follow-up">Follow-up Visit</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Check-up">Routine Check-up</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="symptoms">Symptoms / Reason for Visit</label>
                        <textarea id="symptoms" name="symptoms" class="form-control" placeholder="Describe your symptoms or reason for visit..."></textarea>
                    </div>
                </div>

                <!-- Step 4: Confirmation -->
                <div class="form-step" id="step4">
                    <h2 style="margin-bottom: 2rem;">Confirm Appointment</h2>
                    <div class="summary-box" id="summaryBox">
                        <div class="summary-item">
                            <span class="summary-label">Patient Name:</span>
                            <span class="summary-value" id="summary_name">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Email:</span>
                            <span class="summary-value" id="summary_email">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Phone:</span>
                            <span class="summary-value" id="summary_phone">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Doctor:</span>
                            <span class="summary-value" id="summary_doctor">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Date:</span>
                            <span class="summary-value" id="summary_date">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Time:</span>
                            <span class="summary-value" id="summary_time">-</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Type:</span>
                            <span class="summary-value" id="summary_type">-</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">
                        <i class="fas fa-check"></i> Confirm Booking
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 4;

        // Set minimum date to today
        document.getElementById('appointment_date').min = new Date().toISOString().split('T')[0];

        function changeStep(direction) {
            // Validate current step before moving forward
            if (direction === 1 && !validateStep(currentStep)) {
                return;
            }

            // Hide current step
            document.getElementById('step' + currentStep).classList.remove('active');
            document.getElementById('step' + currentStep + 'Indicator').classList.remove('active');
            
            if (direction === 1) {
                document.getElementById('step' + currentStep + 'Indicator').classList.add('completed');
            }

            // Update current step
            currentStep += direction;

            // Show new step
            document.getElementById('step' + currentStep).classList.add('active');
            document.getElementById('step' + currentStep + 'Indicator').classList.add('active');

            // Update buttons
            updateButtons();

            // Update summary if on step 4
            if (currentStep === 4) {
                updateSummary();
            }

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            const stepElement = document.getElementById('step' + step);
            const inputs = stepElement.querySelectorAll('input[required], select[required], textarea[required]');
            
            for (let input of inputs) {
                if (!input.value) {
                    input.focus();
                    alert('Please fill in all required fields');
                    return false;
                }
            }
            return true;
        }

        function updateButtons() {
            document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'flex';
            document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'flex';
            document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'flex' : 'none';
        }

        function updateSummary() {
            document.getElementById('summary_name').textContent = document.getElementById('patient_name').value;
            document.getElementById('summary_email').textContent = document.getElementById('patient_email').value;
            document.getElementById('summary_phone').textContent = document.getElementById('patient_phone').value;
            
            const selectedDoctor = document.querySelector('input[name="doctor_id"]:checked');
            if (selectedDoctor) {
                const doctorCard = selectedDoctor.closest('.doctor-card');
                document.getElementById('summary_doctor').textContent = doctorCard.querySelector('h3').textContent;
            }
            
            document.getElementById('summary_date').textContent = document.getElementById('appointment_date').value;
            document.getElementById('summary_time').textContent = document.getElementById('appointment_time').value;
            document.getElementById('summary_type').textContent = document.getElementById('appointment_type').value;
        }

        // Doctor card selection
        document.querySelectorAll('.doctor-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.doctor-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
