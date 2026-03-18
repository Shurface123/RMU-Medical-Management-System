# RMU Medical Sickbay System — Architecture & System Design Documentation

## 1. System Overview
The **RMU Medical Sickbay System** is a comprehensive, multi-role web application designed to manage clinical, operational, and administrative workflows within the RMU medical facility. It utilizes a centralized MySQL database and a monolithic PHP architecture, featuring real-time cross-dashboard notifications, strict RBAC (Role-Based Access Control), and specialized portals for various medical and administrative staff.

**Tech Stack:**
*   **Backend:** PHP 8.3.14 (Vanilla PHP, no major framework)
*   **Database:** MySQL Server
*   **Web Server:** Apache 2.4.62 (WAMP Environment)
*   **Frontend:** HTML5, CSS3 (Custom styling with CSS Variables), Vanilla JavaScript, Chart.js for analytics.

---

## 2. Role Definitions & Capabilities

The system enforces strict access control through a centralized authentication middleware.

1.  **Admin (`admin`):**
    *   Full system oversight and analytics dashboard.
    *   Manage all users (Doctors, Nurses, Staff, Patients).
    *   Monitor fleet (Ambulances) and total hospital Bed capacity.
    *   Receive system-wide alerts (Emergencies, Abnormal Vitals, Low Stock).
2.  **Doctor (`doctor`):**
    *   Manage patient appointments (approve, reschedule, cancel).
    *   Create medical records, prescribe medications, order lab tests.
    *   Review lab results and release them to patients.
    *   Assign inpatient beds and approve nurse bed-transfer requests.
    *   Assign specific clinical tasks to pending nurses.
3.  **Nurse (`nurse`):**
    *   Record patient vitals (auto-flags abnormal readings).
    *   Administer prescribed medications and update administration schedules.
    *   Request bed transfers for admitted patients.
    *   Manage nursing shift handovers and clinical notes.
    *   Trigger global "Rapid Emergency" alerts.
4.  **Pharmacist (`pharmacist`):**
    *   Manage medicine inventory, stock adjustments, and supply chain (Purchase Orders).
    *   Dispense medications based on doctor prescriptions.
    *   Receive automated low-stock and expiring medicine alerts.
5.  **Lab Technician (`lab_technician`):**
    *   Receive and process lab test orders from doctors.
    *   Upload test results and notify doctors for review.
6.  **Patient (`patient`):**
    *   Book appointments with specific doctors (triggers email confirmations).
    *   View active prescriptions, clinical lab results, and medical records.
7.  **Support Staff (`cleaner`, `ambulance_driver`, `security`, `maintenance`, `kitchen_staff`, `laundry_staff`):**
    *   Access specialized staff portals.
    *   Subject to an explicit "Admin Approval" gate before logging in.

---

## 3. Major Workflows (End-to-End)

### Workflow 1: Patient Appointment Booking
1.  **Patient** logs in and submits an appointment request for a specific doctor, date, and time.
2.  The backend checks for time-slot conflicts. If clear, the appointment is created with a `Pending` status.
3.  An automated email confirmation is sent to the patient and the assigned doctor via `EmailService`.
4.  **Doctor** sees the request on their dashboard and can `Approve`, `Reschedule`, or `Cancel`.
5.  State changes trigger an automated internal notification back to the patient.

### Workflow 2: Clinical Consultation & Prescription
1.  **Doctor** conducts the consultation and creates a `medical_record`.
2.  **Doctor** issues a prescription.
3.  The system notifies the **Patient** ("New Prescription Issued") and alerts ALL **Pharmacists** ("New Prescription Pending").
4.  If the doctor schedules the medication for an inpatient, it automatically populates the **Nurse's** medication administration queue.

### Workflow 3: Pharmacy Dispensing & Inventory Routing
1.  **Pharmacist** views the pending prescription and verifies stock.
2.  Pharmacist dispenses the medicine. The system automatically:
    *   Deducts stock from the `medicines` table.
    *   Logs the action in `stock_transactions` and `dispensing_records`.
    *   Notifies both the **Doctor** and the **Patient** that the medication was dispensed.
3.  If stock drops below the `reorder_level`, an automated alert is triggered to **Admins** and **Pharmacists**. If stock hits 0, **Doctors** are also warned.

### Workflow 4: Lab Test Lifecycle
1.  **Doctor** orders a Lab Test. The system dual-writes to `lab_tests` (legacy) and `lab_test_orders` (queue).
2.  All **Lab Technicians** receive a notification in their specialized `lab_notifications` table.
3.  **Lab Tech** processes the sample and submits the result. The system notifies the prescribing Doctor.
4.  **Doctor** reviews the result and chooses to release it.
5.  Upon release, the **Patient** is notified and can view the result on their dashboard.

### Workflow 5: Nursing Vitals & Emergency Triggers
1.  **Nurse** records patient vitals.
2.  The backend cross-checks the values against standard clinical thresholds (e.g., SpO2 < 92, HR > 120).
3.  If abnormal, the system flags the vital record and automatically sends an urgent notification to the attending **Doctor** and all **Admins**.
4.  If a critical event occurs, the Nurse uses the `"Rapid Emergency"` tool. This bypasses standard routing and fires a `critical` priority alert to **ALL Doctors** and **ALL Admins** simultaneously.

### Workflow 6: Inpatient Bed Management
1.  **Doctor** assigns a patient to a bed. Status becomes `Occupied`.
2.  **Nurse** identifies a need to move the patient (e.g., condition worsened) and submits a Bed Transfer Request.
3.  The request notifies the attending **Doctor**.
4.  **Doctor** approves the transfer. The system automatically releases the old bed, occupies the new bed, and notifies the **Nurse**.

---

## 4. Cross-Role Communication Framework

The system relies on a robust internal messaging bus, primarily driven by `crossNotify()` and the `notifications` table.

*   **Primary Tables:**
    *   `notifications`: Global notifications for Admin, Doctor, Patient, Pharmacist, Nurse.
    *   `lab_notifications`: Specialized queue specifically for lab technicians.
    *   `nurse_notifications`: Specialized alerts for nurses (e.g., handover alerts).
*   **Notification Triggers:**
    *   **Dual-Write Architecture:** When notifying a lab tech, the system writes to *both* `notifications` and `lab_notifications` to ensure the isolated lab SPA receives the update.
    *   **Broadcasts:** Helpers like `notifyAllDoctors()` and `notifyAllAdmins()` efficiently map alerts to active personnel without raw SQL repetition.

---

## 5. Complete Database Schema (Core Overview)

The database utilizes strict relational integrity. Below are the core entities mapping the architecture:

### Users & Identity
*   `users`: Central authentication table (`email`, `password` bcrypt hash, `user_role`, `locked_until`).
*   `doctors`, `nurses`, `patients`, `staff`, `pharmacist_profile`, `lab_technicians`: Profile extensions linked via `user_id` FK.

### Clinical Data
*   `appointments`: Tracks scheduling (`status`, `doctor_id`, `patient_id`).
*   `medical_records`: Clinical consultation notes and diagnoses.
*   `patient_vitals`: Nurse-recorded telemetry (`bp_systolic`, `oxygen_saturation`, `is_flagged`).
*   `prescriptions` & `dispensing_records`: Tracks medication lifecycle.
*   `lab_test_orders` & `lab_results`: Manages the lab queue and outcome data.

### Facility & Operations
*   `beds`, `wards`, `bed_assignments`, `bed_transfers`: Inpatient location tracking.
*   `medicines`, `stock_transactions`, `purchase_orders`: Pharmacy inventory and supply chain.
*   `ambulances`: Fleet tracking.
*   `nurse_tasks`, `nurse_shifts`, `shift_handover`: Nursing operational logistics.

### Security & Auditing
*   `global_login_attempts`: Brute-force protection tracker.
*   `staff_audit_trail`: Security logs for staff actions.
*   `notifications`: The central nervous system for state changes.

---

## 6. Tech Stack & Security Patterns

1.  **Authentication & Brute-Force Protection:**
    *   Passwords are encrypted using `bcrypt` (auto-upgrades legacy MD5 hashes on login).
    *   The system actively tracks `global_login_attempts`. 5 failed requests within 15 minutes trigger a hard 15-minute account lockout (`locked_until`).
2.  **Session & Route Protection:**
    *   Centralized `SessionManager` handles role verification.
    *   Role boundaries are strictly enforced. Example: An Ambulance Driver cannot access the Nurse dashboard.
3.  **Prepared Statements & Input Validation:**
    *   All advanced modules (Pharmacy, Nurse, Lab, Booking) strictly utilize `mysqli_prepare` to prevent SQL injection.
    *   Wrapper functions (`dbExecute()`, `dbInsert()`) abstract parameter binding safely.
4.  **Cross-Site Request Forgery (CSRF):**
    *   AJAX POST endpoints (e.g., `nurse_actions.php`, `pharmacy_actions.php`) enforce CSRF token validation via header (`HTTP_X_CSRF_TOKEN`) or form payload before processing state changes.
