# RMU Medical Sickbay System - Sequence Diagrams

This document contains standardized sequence diagrams showing the chronological interactions, lifelines, message passing, and module activations across different roles within the RMU Medical Sickbay System. 

## Conventions & Legends
- **Actors/Participants:** Declared at the top.
- **Activation Bars:** Indicated by vertical rectangles on lifelines (`+` / `-`).
- **Messages:** Solid arrows for requests/actions (`->>`). Dashed arrows for returns/callbacks (`-->>`).
- **Fragments:** Uses `alt` (alternative/if-else) and `opt` (optional paths).
- **Role Color Coding:** Enforced using categorized bounding boxes.
  - Admin: `Red` (`rgb(248, 215, 218)`)
  - Doctor: `Blue` (`rgb(204, 229, 255)`)
  - Nurse: `Green` (`rgb(212, 237, 218)`)
  - Pharmacist: `Purple` (`rgb(226, 217, 243)`)
  - Lab Technician: `Yellow` (`rgb(255, 243, 205)`)
  - Patient: `Orange` (`rgb(255, 229, 204)`)
  - System/DB: `Gray` (`rgb(226, 227, 229)`)

---

## 1. Patient Appointment Booking & Confirmation
**Roles Involved:** Patient, System (Web+DB), Email Service, Doctor
**Type:** Sequence Diagram

```mermaid
---
title: "1. Patient Appointment Booking & Confirmation | Roles: Patient, System, Doctor | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end
    box rgb(226, 227, 229) "System Background"
        participant Sys as Backend System API
        participant Mail as Email Service Module
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end

    Pat->>+Sys: POST /booking_handler.php (JSON Data)
    Sys->>Sys: Parse JSON & Check Date Bounds
    
    alt Slot Conflict Exists
        Sys-->>Pat: Error: "Slot already booked"
    else Slot Available
        Sys->>Sys: Generate RMU-APT-ID Custom Key
        Sys->>Sys: Insert record (Status: Pending)
        
        opt Send Notifications
            Sys->>+Mail: sendEmail(Patient)
            Mail-->>-Sys: true
            Sys->>+Mail: sendEmail(Doctor)
            Mail-->>-Sys: true
        end
        
        Sys->>Doc: crossNotify() - New Appointment Pending
        Sys-->>-Pat: 200 JSON: Booking Success & ID
    end
    
    %% Later Action
    Doc->>+Sys: Click "Approve" on Dashboard
    Sys->>Sys: Update appointment status to 'Confirmed'
    Sys->>Pat: crossNotify() - "Appointment Confirmed by Dr."
    Sys-->>-Doc: 200 JSON: Action Succeeded
```

---

## 2. Lab Test Lifecycle & Result Release
**Roles Involved:** Doctor, System (DB Dual Write), Lab Technician, Patient
**Type:** Sequence Diagram

```mermaid
---
title: "2. Lab Test Lifecycle & Result Release | Roles: Doctor, System, Lab Tech, Patient | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(226, 227, 229) "System Data Bus"
        participant Sys as Backend API
    end
    box rgb(255, 243, 205) "Lab Technician Role"
        actor Lab as Lab Technician
    end
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end

    Doc->>+Sys: POST /doctor_actions.php (create_lab_request)
    Sys->>Sys: Insert 'lab_tests' (Legacy Log)
    Sys->>Sys: Insert 'lab_test_orders' (Queue Sync)
    
    Sys->>Lab: mirrorToLabNotifications() (ALL Techs)
    Sys->>Pat: notifyPatient() - "Test Ordered"
    Sys-->>-Doc: Success: Generated Test & Order IDs
    
    Note over Lab: Sometime Later
    Lab->>+Sys: POST /lab_actions.php (upload_results)
    Sys->>Sys: Insert 'lab_results' (Status: Unreviewed)
    Sys->>Sys: Update 'lab_test_orders' (Status: Processed)
    Sys->>Doc: notifyDoctor() - "Results Ready for Review"
    Sys-->>-Lab: Success: Result Logged
    
    Doc->>+Sys: POST review_lab (Action: Make Accessible = 1)
    Sys->>Sys: Update 'lab_results' (doctor_reviewed = 1)
    
    alt is Patient Accessible
        Sys->>Pat: notifyPatient() - "Results Available!"
    end
    
    Sys->>Lab: notifyLabTech() - "Doctor Reviewed Result"
    Sys-->>-Doc: Success: Review Accepted
```

---

## 3. Pharmacy Dispensing & Alert Escalation
**Roles Involved:** Pharmacist, System, Patient, Doctor, Admin
**Type:** Sequence Diagram

```mermaid
---
title: "3. Pharmacy Dispensing & Alert Escalation | Roles: Pharmacist, System, Patient, Doctor, Admin | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(226, 217, 243) "Pharmacist Role"
        actor Phm as Pharmacist
    end
    box rgb(226, 227, 229) "System Data Bus"
        participant Sys as Backend API
    end
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Admin
    end

    Phm->>+Sys: POST /pharmacy_actions.php (dispense_prescription)
    Sys->>Sys: Fetch Inventory & Validate Stock
    
    alt Current Stock < Req. Qty
        Sys-->>Phm: Error: "Insufficient Stock"
    else Stock Available
        Sys->>Sys: Deduct stock from 'medicines'
        Sys->>Sys: Insert 'dispensing_records'
        Sys->>Sys: Log 'stock_transactions'
        
        Sys->>Pat: Notify: "Medication Dispensed"
        Sys->>Doc: Notify: "Rx Fulfilled"
        
        %% Threshold check block
        alt New Stock == 0
            Sys->>Adm: secureNotifyAdmins() - "⚠️ Stock Empty"
            Sys->>Doc: crossNotify() - "⚠️ Med Out of Stock"
        else New Stock <= Reorder Level
            Sys->>Adm: secureNotifyAdmins() - "⚠️ Low Stock Alert"
        end
        
        Sys-->>-Phm: Success: Dispensed & Inventory Synced
    end
```

---

## 4. Patient - Appointment Booking Sequence
**Roles Involved:** Patient, UI, System/Server, DB, Notification Service
**Type:** Sequence Diagram

```mermaid
---
title: "4. Patient Appointment Booking Sequence | Roles: Patient, System, Doctor | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end

    Pat->>+UI: Selects Doctor, Time, Submits Form
    UI->>+Srv: POST /booking_handler.php
    Srv->>Srv: Validate Session
    Srv->>+DB: SELECT count(*) WHERE doctor_available
    DB-->>-Srv: Availability Status
    
    alt is Not Available
        Srv-->>UI: 400 Error: Slot Unavailable
        UI-->>Pat: Shows Error Toast
    else is Available
        Srv->>+DB: INSERT INTO appointments
        DB-->>-Srv: Insert ID
        Srv->>+Notif: Create Notification / Email
        Notif->>Doc: Email & Alert "New Appointment"
        Notif-->>-Srv: Success
        Srv-->>-UI: 200 OK
        UI-->>-Pat: Shows Success Confirmation
    end
```

---

## 5. Patient - Lab Result Access Sequence
**Roles Involved:** Patient, UI, System/Server, DB
**Type:** Sequence Diagram

```mermaid
---
title: "5. Patient Lab Result Access Sequence | Roles: Patient, System | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
    end

    Pat->>+UI: Navigates to Lab Results Page
    UI->>+Srv: GET /api/patient/lab_results.php
    Srv->>+DB: SELECT * FROM lab_results WHERE patient_id = X
    DB-->>-Srv: Result Set
    
    alt doctor_reviewed == 1 (Released)
        Srv-->>UI: Return Results Data
        UI-->>Pat: Displays Results & Download Button
    else doctor_reviewed == 0 (Not Released)
        Srv-->>-UI: Return Empty/Status "Unreviewed"
        UI-->>-Pat: Shows "Awaiting Doctor Review" Message
    end
```

---

## 6. Doctor - Prescription Issuance Sequence
**Roles Involved:** Doctor, UI, Server, DB, Notification Service, Pharmacist, Patient
**Type:** Sequence Diagram

```mermaid
---
title: "6. Doctor Prescription Issuance Sequence | Roles: Doctor, System, Pharmacist, Patient | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(226, 217, 243) "Pharmacist Role"
        actor Pharm as Pharmacist
    end
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end

    Doc->>+UI: Selects Patient, Selects Medicine, Submits
    UI->>+Srv: POST /doctor_actions.php (issue_prescription)
    Srv->>Srv: Validate Session + Role (Doctor Only)
    
    Srv->>+DB: SELECT * FROM patients WHERE id = X
    DB-->>-Srv: Patient Data
    
    Srv->>+DB: SELECT stock_quantity FROM medicines WHERE id = Y
    DB-->>-Srv: Stock Data
    
    alt stock_quantity < requested
        Srv-->>UI: 400 Error: Insufficient Stock
        UI-->>Doc: Shows Error Alert
    else stock_quantity >= requested
        Srv->>+DB: INSERT INTO prescriptions
        DB-->>-Srv: Prescription ID
        
        Srv->>+Notif: Create Notification Queue
        Notif->>Pat: notifyPatient() "New Rx Issued"
        Notif->>Pharm: crossNotify() "New Rx Pending"
        Notif-->>-Srv: Notified All Parties
        
        Srv-->>-UI: 200 OK Response
        UI-->>-Doc: Shows Success Confirmation
    end
```

---

## 7. Doctor - Lab Result Release Sequence
**Roles Involved:** Doctor, UI, Server, DB, Notification Service, Patient
**Type:** Sequence Diagram

```mermaid
---
title: "7. Doctor Lab Result Release Sequence | Roles: Doctor, System, Patient | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end

    Doc->>+UI: Clicks "Release to Patient" on Lab Result
    UI->>+Srv: POST /doctor_actions.php (release_lab_result)
    
    Srv->>+DB: SELECT * FROM lab_results WHERE id = X
    DB-->>-Srv: Lab Result Data
    
    Note over Srv: Doctor validates the data & bounds
    
    Srv->>+DB: UPDATE lab_results SET doctor_reviewed = 1
    DB-->>-Srv: Update Success
    
    Srv->>+Notif: Queue Email/System Alert
    Notif->>Pat: notifyPatient() "Lab Results Available!"
    Notif-->>-Srv: Notification Sent
    
    Srv-->>-UI: 200 OK Response
    UI-->>-Doc: Shows Success Confirmation & UI Updates
```

---

## 8. Administrator - Staff Account Approval Sequence
**Roles Involved:** Administrator, UI, Server, DB, Notification Service, Pending Staff
**Type:** Sequence Diagram

```mermaid
---
title: "8. Administrator Staff Account Approval Sequence | Roles: Admin, System, Staff | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(224, 224, 224) "Pending Staff Role"
        actor Staff as Pending Staff
    end

    Adm->>+UI: Clicks "Approve" on Registration Request
    UI->>+Srv: POST /admin_actions.php (approve_staff)
    Srv->>Srv: Validate Session + Role (Admin Only)
    
    Srv->>+DB: SELECT * FROM pending_registrations WHERE id = X
    DB-->>-Srv: Registration Data
    
    Note over Srv: Admin Review validates the data
    
    Srv->>+DB: INSERT INTO users (Status: Active)
    DB-->>-Srv: New User ID
    Srv->>+DB: DELETE FROM pending_registrations
    DB-->>-Srv: Success
    
    Srv->>+Notif: Queue Email/Dashboard Alert
    Notif->>Staff: notifyStaff() "Account Activated!"
    Notif-->>-Srv: Notification Sent
    
    Srv-->>-UI: 200 OK Response
    UI-->>-Adm: Shows Success Confirmation & Removes row
```

---

## 9. Administrator - System Broadcast Sequence
**Roles Involved:** Administrator, UI, Server, DB, Notification Service
**Type:** Sequence Diagram

```mermaid
---
title: "9. Administrator System Broadcast Sequence | Roles: Admin, System | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end

    Adm->>+UI: Submits Broadcast Form (Message, Role Target)
    UI->>+Srv: POST /admin_actions.php (send_broadcast)
    
    Srv->>+DB: SELECT id FROM users WHERE role IN (targets)
    DB-->>-Srv: List of Recipient IDs
    
    Note over Srv: Loop through IDs for batch insert
    
    Srv->>+DB: INSERT INTO notifications VALUES (batch data)
    DB-->>-Srv: Batch Insert Success
    
    Srv->>+Notif: Queue Email Service
    Notif->>Notif: sendBulkEmails(recipients)
    Notif-->>-Srv: Queue Processed
    
    Srv-->>-UI: 200 OK Return count sent
    UI-->>-Adm: Shows "Sent to X users" Confirmation
```

---

## 10. Pharmacist - Prescription Dispensing Sequence
**Roles Involved:** Pharmacist, UI, Server, DB, Notification Service, Doctor, Patient
**Type:** Sequence Diagram

```mermaid
---
title: "10. Pharmacist Prescription Dispensing Sequence | Roles: Pharmacist, System, Doctor, Patient | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(226, 217, 243) "Pharmacist Role"
        actor Pharm as Pharmacist
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(255, 229, 204) "Patient Role"
        actor Pat as Patient
    end

    Pharm->>+UI: Clicks "Dispense" and Confirms Quantities
    UI->>+Srv: POST /pharmacy_actions.php (dispense_rx)
    Srv->>Srv: Validate Session + Role (Pharmacist Only)
    
    Srv->>+DB: SELECT * FROM prescriptions WHERE id = X
    DB-->>-Srv: Prescription Data & Items
    
    Note over Srv: Loop through each item in prescription
    
    Srv->>+DB: SELECT stock_quantity FROM medicines WHERE item_id = Y
    DB-->>-Srv: Current Stock Data
    
    alt stock < requested
        Srv-->>UI: Error: Attempted to dispense unavailable stock
        UI-->>Pharm: Shows Error (Forces partial dispense UI)
    else stock >= requested
        Srv->>+DB: UPDATE prescriptions SET status = 'Dispensed'
        DB-->>-Srv: Prescriptions Updated
        
        Srv->>+DB: INSERT INTO dispensing_records
        DB-->>-Srv: Dispensing DB Logged
        
        Srv->>+DB: UPDATE medicines SET stock_quantity = stock - disp
        DB-->>-Srv: Inventory Deducted
        
        Srv->>+Notif: Queue System Alert
        Notif->>Pat: notifyPatient() "Prescription Dispensed"
        Notif->>Doc: crossNotify() "Patient Received Meds"
        Notif-->>-Srv: Notification Sent
        
        Srv-->>-UI: 200 OK Confirmation
        UI-->>-Pharm: Shows Success & Updates Stock UI counter
    end
```

---

## 11. Lab Technician - Lab Result Entry & Release Sequence
**Roles Involved:** Lab Technician, UI, Server, DB, Notification Service, Doctor
**Type:** Sequence Diagram

```mermaid
---
title: "11. Lab Technician Result Entry & Release Sequence | Roles: Lab Tech, System, Doctor | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(255, 243, 205) "Lab Technician Role"
        actor LabT as Lab Technician
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end

    LabT->>+UI: Enters Test Results & Submits
    UI->>+Srv: POST /lab_actions.php (submit_results)
    Srv->>Srv: Validate Session + Role (Lab Tech Only)
    
    Srv->>+DB: SELECT * FROM lab_test_orders WHERE id = X
    DB-->>-Srv: Test Order Data
    
    Note over Srv: System compares entered ranges against parameters
    
    Srv->>Sys: checkReferenceRanges(results)
    
    alt Contains Critical Values
        Srv->>+Notif: Flag Urgent Alert
        Notif->>Doc: crossNotify() "🚨 CRITICAL LAB RESULT"
        Notif-->>-Srv: Sent
    end
    
    LabT->>Srv: confirms validation
    
    Srv->>+DB: UPDATE lab_results SET status = 'validated'
    DB-->>-Srv: Update Validated
    
    LabT->>+Srv: POST release_results
    Srv->>+DB: UPDATE lab_results SET technician_released = 1
    DB-->>-Srv: Update Released
    
    Srv->>+Notif: Queue System Alert
    Notif->>Doc: notifyDoctor() "Lab Results Ready for Review"
    Notif-->>-Srv: Notification Sent
    
    Srv-->>-UI: 200 OK Confirmation
    UI-->>-LabT: Shows Success "Released to Doctor"
```

---

## 12. Nurse - Critical Vital Alert Sequence
**Roles Involved:** Nurse, UI, Server, DB, Notification Service, Doctor
**Type:** Sequence Diagram

```mermaid
---
title: "12. Nurse Critical Vital Alert Sequence | Roles: Nurse, System, Doctor | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(212, 237, 218) "Nurse Role"
        actor Nur as Nurse
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end

    Nur->>+UI: Enters Vitals & Submits
    UI->>+Srv: POST /nurse_actions.php (save_vitals)
    Srv->>Srv: Validate Session + Role (Nurse Only)
    
    Srv->>Sys: compareToThresholds(vitals)
    
    alt isCritical == true
        Srv->>+Notif: Queue Urgent Push
        Notif->>Doc: crossNotifyDashboard() "🔥 CRITICAL VITALS: Patient X"
        Notif-->>-Srv: Sent
        
        Srv->>+DB: INSERT INTO vitals (flagged='critical', doctor_notified=1)
        DB-->>-Srv: Saved
        
        Srv-->>UI: 200 OK (Critical Flag = True)
        UI-->>Nur: Shows standard success + CRITICAL ALERT Banner
        
        Doc->>UI: Clicks Notification
        UI->>Srv: POST acknowledge_alert
        Srv->>DB: UPDATE vitals SET doctor_acknowledged=1
    else isCritical == false
        Srv->>+DB: INSERT INTO vitals (flagged='normal')
        DB-->>-Srv: Saved
        Srv-->>-UI: 200 OK (Critical Flag = False)
        UI-->>-Nur: Shows standard success
    end
```

---

## 13. Nurse - Emergency Alert Sequence (Code Blue)
**Roles Involved:** Nurse, UI, Server, DB, Notification Service, Doctor, Admin
**Type:** Sequence Diagram

```mermaid
---
title: "13. Nurse Emergency Alert Sequence (Code Blue) | Roles: Nurse, System, Doctor, Admin | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(212, 237, 218) "Nurse Role"
        actor Nur as Nurse
    end
    box rgb(226, 227, 229) "Frontend UI"
        participant UI as Browser UI
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(204, 229, 255) "Doctor Role"
        actor Doc as Doctor
    end
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end

    Nur->>+UI: Clicks one-click "Code Blue"
    UI->>+Srv: POST /emergency_actions.php (trigger_alert)
    
    Srv->>+DB: INSERT INTO emergency_alerts (type, location, status='Active')
    DB-->>-Srv: Alert ID Created
    
    par Broadcast
        Srv->>+Notif: Broadcast Channel
        Notif->>Doc: crossNotify() "🚨 CODE BLUE: Ward A"
        Notif->>Adm: crossNotify() "🚨 CODE BLUE: Ward A"
        Notif-->>-Srv: Broadcast successful
    end
    
    Srv-->>-UI: 200 OK Alert Active
    UI-->>-Nur: Shows Flashing Active Alert Status
    
    Doc->>+UI: Acknowledges Alert in Dashboard
    UI->>+Srv: POST /emergency_actions.php (respond_alert)
    
    Srv->>+DB: UPDATE emergency_alerts SET responder_id = X, status = 'Responding'
    DB-->>-Srv: DB Updated
    
    Srv->>+Notif: Targeted Response
    Notif->>Nur: notifyNurse() "Dr. X is responding"
    Notif-->>-Srv: Sent
    
    Srv-->>-UI: 200 OK
    UI-->>-Doc: Responding Status Activated
```

---

## 14. Support Staff - Ambulance Dispatch Sequence
**Roles Involved:** Administrator, Support Staff (Driver), UI, Server, DB, Notification Service
**Type:** Sequence Diagram

```mermaid
---
title: "14. Ambulance Dispatch Sequence | Roles: Admin, Driver, System | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(224, 224, 224) "Support Staff Role"
        actor Drv as Ambulance Driver
    end

    Adm->>+Srv: POST /ambulance_actions.php (dispatch_trip)
    
    Srv->>+DB: SELECT status FROM vehicles WHERE id=X AND status='Available'
    DB-->>-Srv: Checked
    
    Srv->>+DB: INSERT INTO trips (vehicle_id, driver_id, destination)
    DB-->>-Srv: Trip ID
    
    par Update Statuses
        Srv->>+DB: UPDATE vehicles SET status='In Use'
        DB-->>-Srv: Updated
        Srv->>+DB: UPDATE users SET status='On Trip' WHERE id=driver_id
        DB-->>-Srv: Updated
    end
    
    Srv->>+Notif: Dispatch Alert
    Notif->>Drv: notifyDriver() "New Trip Assigned"
    Notif-->>-Srv: Sent
    
    Srv-->>-Adm: 200 OK
    
    Drv->>+Srv: POST update_trip_status (En Route/Arrived)
    Srv->>+DB: UPDATE trips SET status = 'Updated'
    DB-->>-Srv: Success
    Srv->>Notif: Notify Admin & Requesting Ward
```

---

## 15. Support Staff - Cleaning Dispatch Sequence
**Roles Involved:** Administrator, Support Staff (Cleaner), UI, Server, DB, Notification Service
**Type:** Sequence Diagram

```mermaid
---
title: "15. Cleaning Dispatch Sequence | Roles: Admin, Cleaner, System | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(224, 224, 224) "Support Staff Role"
        actor Cln as Cleaner
    end

    Adm->>+Srv: POST /facilities_actions.php (assign_cleaning)
    
    Srv->>+DB: INSERT INTO cleaning_schedule
    DB-->>-Srv: Assigned Log ID
    
    Srv->>+Notif: Route Alert
    Notif->>Cln: notifyStaff() "New Cleaning Task: Ward B"
    Notif-->>-Srv: Sent
    
    Cln->>+Srv: POST complete_cleaning (photo proof attached)
    
    Srv->>+DB: UPDATE cleaning_log SET status='Completed', photo_url='X'
    DB-->>-Srv: Log Updated
    
    Srv->>+DB: UPDATE locations SET sanitation_status='Clean'
    DB-->>-Srv: Ward Status Updated
    
    Srv->>Notif: Notification to Admin (Task Finished)
    Srv-->>-Cln: 200 OK "Task Closed"
```

---

## 16. Support Staff - Maintenance Request Sequence
**Roles Involved:** Any User, Support Staff (Maintenance), Server, DB, Notification Service, Admin
**Type:** Sequence Diagram

```mermaid
---
title: "16. Maintenance Request Sequence | Roles: User, Maintenance, System, Admin | Type: Sequence Diagram"
---
sequenceDiagram
    box rgb(255, 255, 255) "Any Roles"
        actor Usr as Any User
    end
    box rgb(226, 227, 229) "Backend System"
        participant Srv as Server (PHP)
        participant DB as Database (MySQL)
        participant Notif as Notification Service
    end
    box rgb(224, 224, 224) "Support Staff Role"
        actor Mnt as Maintenance
    end
    box rgb(248, 215, 218) "Admin Role"
        actor Adm as Administrator
    end

    Usr->>+Srv: POST /maintenance_actions.php (report_issue)
    
    Srv->>+DB: INSERT INTO maintenance_requests
    DB-->>-Srv: Request ID
    
    Srv->>+Notif: Route Request
    Notif->>Mnt: notifyStaffDashboard() "New Repair Ticket"
    Notif-->>-Srv: Sent
    
    Mnt->>+Srv: POST accept_ticket
    Srv->>+DB: UPDATE maintenance_requests SET status='Assigned'
    DB-->>-Srv: Updated
    
    Mnt->>+Srv: POST closing_repair (Parts used, cost)
    Srv->>+DB: UPDATE maintenance_requests SET status='Completed'
    DB-->>-Srv: Updated
    
    Srv->>Notif: notifyAdmin() "Repair Verified & Closed"
    Srv-->>-Mnt: 200 OK
```
