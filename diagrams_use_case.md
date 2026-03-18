# RMU Medical Sickbay System - Use Case Diagrams

This document contains the structural Use Case Diagrams mapping the interactions between actors and the RMU Medical Sickbay System. The diagrams follow standard UML-like notation mapped within Mermaid `flowchart LR` structures.

## Conventions & Legends
- **System Boundary:** `RMU Medical Sickbay System` bounds the internal use cases.
- **Primary Actors:** Represented on the **Left** with FontAwesome user icons.
- **Secondary Actors:** Represented on the **Right**.
- **Use Cases:** Oval shapes with `Verb + Noun` actions.
- **Relationships:** Solid lines for interactions, dashed lines with `include` or `extend`.
- **Role Color Coding:**
  - Admin: `Red`
  - Doctor: `Blue`
  - Nurse: `Green`
  - Pharmacist: `Purple`
  - Lab Technician: `Yellow`
  - Patient: `Orange`
  - System/DB: `Gray`

---

## 1. Appointment & Patient Management
**Roles:** Patient, Doctor, Admin, System DB
**Type:** Use Case Diagram

```mermaid
---
title: "1. Appointment & Patient Management | Roles: Patient, Doctor, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;
    classDef boundary fill:transparent,stroke:#333,stroke-width:2px,stroke-dasharray: 5 5;

    %% Primary Actors
    Pat(("fa:fa-user\nPatient")):::patient
    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Adm(("fa:fa-user-tie\nAdmin")):::admin

    %% System Boundary
    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Group_Booking ["Booking Module"]
            UC1([Book Appointment]):::usecase
            UC2([Review Availability]):::usecase
            UC3([Trigger Email Confirmation]):::usecase
        end
        
        subgraph Group_Mgmt ["Management Module"]
            UC4([Approve/Reschedule Appointment]):::usecase
            UC5([Cancel Appointment]):::usecase
            UC6([Manage Patient Profiles]):::usecase
            UC7([View System Analytics]):::usecase
        end
        
        %% Relationships within boundary
        UC1 -. " include " .-> UC2
        UC1 -. " include " .-> UC3
    end

    %% Secondary Actors
    DB(("fa:fa-database\nSystem DB")):::sys
    Mail(("fa:fa-envelope\nEmail Service")):::sys

    %% Actor Connections
    Pat --- UC1
    Pat --- UC5
    Pat --- UC6
    
    Doc --- UC4
    Doc --- UC5
    
    Adm --- UC6
    Adm --- UC7

    %% Secondary Connections
    UC1 --- DB
    UC3 --- Mail
    UC4 --- DB
    UC6 --- DB
```

---

## 2. Clinical Consultation & Prescriptions
**Roles:** Doctor, Nurse, Pharmacist, Patient
**Type:** Use Case Diagram

```mermaid
---
title: "2. Clinical Consultation & Prescriptions | Roles: Doctor, Nurse, Pharmacist, Patient | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef nurse fill:#d4edda,stroke:#c3e6cb,stroke-width:2px,color:#000;
    classDef pharmacist fill:#e2d9f3,stroke:#cbbce5,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    %% Primary Actors
    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Nur(("fa:fa-user-nurse\nNurse")):::nurse

    %% System Boundary
    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        UC1([Record Consultation Notes]):::usecase
        UC2([Issue Prescription]):::usecase
        UC3([Check Stock Alerts]):::usecase
        UC4([Schedule Medication Administration]):::usecase
        UC5([Dispense Medication]):::usecase
        UC6([View Active Prescriptions]):::usecase
        UC7([Administer Medication]):::usecase
        
        UC2 -. " extend " .-> UC3
        UC2 -. " include " .-> UC4
    end

    %% Secondary Actors 
    Pharm(("fa:fa-pills\nPharmacist")):::pharmacist
    Pat(("fa:fa-user\nPatient")):::patient
    Notifier(("fa:fa-bell\nCross-Notify System")):::sys

    %% Connections
    Doc --- UC1
    Doc --- UC2
    
    Nur --- UC7
    
    UC5 --- Pharm
    UC6 --- Pat
    
    %% Internal triggers to secondary
    UC2 --- Notifier
    UC5 --- Notifier
```

---

## 3. Laboratory Management Flow
**Roles:** Doctor, Lab Technician, Patient
**Type:** Use Case Diagram

```mermaid
---
title: "3. Laboratory Management Flow | Roles: Doctor, Lab Technician, Patient | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef lab fill:#fff3cd,stroke:#ffeeba,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    %% Primary Actors
    Doc(("fa:fa-user-md\nDoctor")):::doctor

    %% System Boundary
    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        UC1([Order Lab Test]):::usecase
        UC2([Process Test Sample]):::usecase
        UC3([Upload Lab Results]):::usecase
        UC4([Review Lab Results]):::usecase
        UC5([Release Results to Patient]):::usecase
        UC6([View Lab Results]):::usecase
        
        UC1 -. " include " .-> UC2
        UC3 -. " include " .-> UC4
        UC4 -. " extend " .-> UC5
    end

    %% Secondary Actors
    LabT(("fa:fa-microscope\nLab Technician")):::lab
    Pat(("fa:fa-user\nPatient")):::patient
    DB(("fa:fa-database\nSystem DB")):::sys

    %% Connections
    Doc --- UC1
    Doc --- UC4
    Doc --- UC5
    
    UC2 --- LabT
    UC3 --- LabT
    
    UC6 --- Pat
    
    UC1 --- DB
    UC3 --- DB
```

---

## 4. Ward & Emergency Management
**Roles:** Nurse, Doctor, Admin
**Type:** Use Case Diagram

```mermaid
---
title: "4. Ward & Emergency Management | Roles: Nurse, Doctor, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef nurse fill:#d4edda,stroke:#c3e6cb,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    %% Primary Actors
    Nur(("fa:fa-user-nurse\nNurse")):::nurse

    %% System Boundary
    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        UC1([Record Patient Vitals]):::usecase
        UC2([Flag Abnormal Vitals]):::usecase
        UC3([Request Bed Transfer]):::usecase
        UC4([Approve Bed Transfer]):::usecase
        UC5([Trigger Rapid Emergency]):::usecase
        UC6([Broadcast Emergency Alert]):::usecase
        
        UC1 -. " extend " .-> UC2
        UC5 -. " include " .-> UC6
    end

    %% Secondary Actors
    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Adm(("fa:fa-user-tie\nAdmin")):::admin
    Notifier(("fa:fa-bell\nCross-Notify System")):::sys

    %% Connections
    Nur --- UC1
    Nur --- UC3
    Nur --- UC5
    
    UC4 --- Doc
    
    UC2 --- Notifier
    UC6 --- Notifier
    
    Notifier --- Doc
    Notifier --- Adm
```

---

## 5. Patient Complete System Interactions
**Roles:** Patient, Doctor, Lab Technician, System Notification
**Type:** Use Case Diagram

```mermaid
---
title: "5. Patient Complete System Interactions | Roles: Patient, Doctor, Lab Tech, System | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef lab fill:#fff3cd,stroke:#ffeeba,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;
    
    Pat(("fa:fa-user\nPatient")):::patient

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Auth ["Authentication"]
            UC1([Register Account]):::usecase
            UC2([Login to System]):::usecase
            UC17([Change Password]):::usecase
            UC20([Logout]):::usecase
        end
        
        subgraph Clinical ["Clinical View"]
            UC7([View My Prescriptions]):::usecase
            UC8([Request Prescription Refill]):::usecase
            UC9([View Lab Results]):::usecase
            UC10([View Medical Records]):::usecase
        end
        
        subgraph Booking ["Booking"]
            UC3([View Dashboard Overview]):::usecase
            UC4([Book Appointment]):::usecase
            UC5([View My Appointments]):::usecase
            UC6([Cancel Appointment]):::usecase
        end
        
        subgraph Profile ["Profile & Files"]
            UC11([Manage Emergency Contacts]):::usecase
            UC12([Update Personal Profile]):::usecase
            UC13([Update Medical Profile]):::usecase
            UC14([Upload Medical Documents]):::usecase
            UC15([View Insurance Information]):::usecase
            UC18([Toggle Notification Preferences]):::usecase
            UC19([View Notifications]):::usecase
        end
    end
    
    Doc(("fa:fa-user-md\nDoctor")):::doctor
    LabT(("fa:fa-microscope\nLab Technician")):::lab
    SysNotif(("fa:fa-bell\nSystem")):::sys

    Pat --- Auth
    Pat --- Clinical
    Pat --- Booking
    Pat --- Profile
    
    UC4 --- Doc
    UC8 --- Doc
    UC9 --- Doc
    UC9 --- LabT
    UC4 --- SysNotif
    UC19 --- SysNotif
    UC1 --- SysNotif
```

---

## 6. Doctor Complete System Interactions
**Roles:** Doctor, Patient, Nurse, Lab Technician, Pharmacist, System
**Type:** Use Case Diagram

```mermaid
---
title: "6. Doctor Complete System Interactions | Roles: Doctor, Patient, Nurse, Lab Tech, Pharmacist, System | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef nurse fill:#d4edda,stroke:#c3e6cb,stroke-width:2px,color:#000;
    classDef pharmacist fill:#e2d9f3,stroke:#cbbce5,stroke-width:2px,color:#000;
    classDef lab fill:#fff3cd,stroke:#ffeeba,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    Doc(("fa:fa-user-md\nDoctor")):::doctor

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Dashboard ["Dashboard & Appointments"]
            UC1([Login to System]):::usecase
            UC2([View Dashboard Overview & Analytics]):::usecase
            UC3([View Appointment Requests]):::usecase
            UC4([Approve Appointment]):::usecase
            UC5([Reschedule Appointment]):::usecase
            UC6([Cancel Appointment]):::usecase
        end

        subgraph Consultations ["Consultations & Meds"]
            UC7([View Patient Medical Records]):::usecase
            UC8([Add Medical Record Entry]):::usecase
            UC9([Issue Prescription]):::usecase
            UC10([Approve Prescription Refill Request]):::usecase
            UC14([View Medicine Inventory Status]):::usecase
        end

        subgraph Labs ["Lab Module"]
            UC11([Request Lab Test for Patient]):::usecase
            UC12([View Submitted Lab Results]):::usecase
            UC13([Release Lab Results to Patient]):::usecase
            UC13X([Add Notes to Lab Results]):::usecase
        end

        subgraph Wards ["Beds & Tasks"]
            UC15([View Bed Management]):::usecase
            UC16([Request Bed Assignment for Patient]):::usecase
            UC17([Send Task/Instruction to Nurse]):::usecase
            UC18([View Nurse Acknowledgements]):::usecase
        end

        subgraph AdminProfile ["Staff & Reports"]
            UC19([Generate Reports]):::usecase
            UC20([Export Reports PDF/CSV/Excel]):::usecase
            UC21([Manage Availability Schedule]):::usecase
            UC22([Update Professional Profile]):::usecase
            UC23([View Staff Directory]):::usecase
            UC24([View Patient Emergency Contacts]):::usecase
            UC25([Change Password & Security Settings]):::usecase
            UC26([View Notifications]):::usecase
            UC27([Logout]):::usecase
        end
    end

    Pat(("fa:fa-user\nPatient")):::patient
    Nur(("fa:fa-user-nurse\nNurse")):::nurse
    LabT(("fa:fa-microscope\nLab Technician")):::lab
    Pharm(("fa:fa-pills\nPharmacist")):::pharmacist
    SysNotif(("fa:fa-bell\nSystem")):::sys

    Doc --- Dashboard
    Doc --- Consultations
    Doc --- Labs
    Doc --- Wards
    Doc --- AdminProfile

    %% Cross Interactions
    UC3 --- Pat
    UC4 --- Pat
    UC5 --- Pat
    UC6 --- Pat
    UC9 --- Pharm
    UC10 --- Pharm
    UC11 --- LabT
    UC13 --- Pat
    UC17 --- Nur
    
    UC4 --- SysNotif
    UC5 --- SysNotif
    UC6 --- SysNotif
    UC9 --- SysNotif
    UC11 --- SysNotif
    UC13 --- SysNotif
    UC17 --- SysNotif
```

---

## 7. Administrator Complete System Management
**Roles:** Administrator, System
**Type:** Use Case Diagram

```mermaid
---
title: "7. Administrator Complete System Management | Roles: Admin, System | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    Adm(("fa:fa-user-tie\nAdministrator")):::admin

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Core ["Dashboard & Accounts"]
            UC1([Login to System]):::usecase
            UC2([View Comprehensive Dashboard]):::usecase
            UC3([Manage User Accounts]):::usecase
            UC4([Approve Staff Registration Requests]):::usecase
            UC5([Create Admin Accounts]):::usecase
            UC28([Force Logout Any User]):::usecase
        end

        subgraph HR ["Staff & HR Management"]
            UC6([Manage Doctors]):::usecase
            UC7([Manage Nurses]):::usecase
            UC8([Manage Pharmacists]):::usecase
            UC9([Manage Lab Technicians]):::usecase
            UC10([Manage Support Staff]):::usecase
            UC11([Assign Tasks to Staff]):::usecase
            UC12([Manage Shift Schedules]):::usecase
            UC13([Approve/Reject Leave Requests]):::usecase
            UC29([View Profile Completeness of Staff]):::usecase
        end

        subgraph Clinical ["Clinical Oversight"]
            UC6B([Manage Patients]):::usecase
            UC21([View & Manage Medicine Inventory]):::usecase
            UC22([View Bed Management]):::usecase
            UC23([View All Lab Tests Catalog]):::usecase
        end

        subgraph Ops ["Logistics & Facilities"]
            UC14([Manage Ambulance Fleet]):::usecase
            UC15([Manage Facility Maintenance Requests]):::usecase
            UC16([Manage Kitchen Dietary Orders]):::usecase
            UC17([Manage Cleaning Dispatch]):::usecase
        end

        subgraph Comms ["Comms & Settings"]
            UC18([Send System Broadcast Messages]):::usecase
            UC19([Manage Health Messages]):::usecase
            UC24([Generate System-wide Reports]):::usecase
            UC25([View Full Audit Trail]):::usecase
            UC26([Manage System Settings]):::usecase
            UC27([View Analytics Dashboard]):::usecase
            UC30([Logout]):::usecase
        end
    end

    SysNotif(("fa:fa-bell\nSystem Service/DB")):::sys

    Adm --- Core
    Adm --- HR
    Adm --- Clinical
    Adm --- Ops
    Adm --- Comms

    UC4 --- SysNotif
    UC11 --- SysNotif
    UC18 --- SysNotif
    UC28 --- SysNotif
```

---

## 8. Pharmacist Complete System Operations
**Roles:** Pharmacist, Doctor, Patient, Administrator, System
**Type:** Use Case Diagram

```mermaid
---
title: "8. Pharmacist Complete System Operations | Roles: Pharmacist, System, Doctor, Patient, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef pharmacist fill:#e2d9f3,stroke:#cbbce5,stroke-width:2px,color:#000;
    classDef patient fill:#ffe5cc,stroke:#ffcc99,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    Pharm(("fa:fa-pills\nPharmacist")):::pharmacist

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Core ["Dashboard & Profile"]
            UC1([Login to System]):::usecase
            UC2([View Dashboard Overview]):::usecase
            UC19([Update Professional Profile]):::usecase
            UC20([Manage Notification Preferences]):::usecase
            UC21([View Notifications]):::usecase
            UC22([Logout]):::usecase
        end

        subgraph RxMgmt ["Prescription Management"]
            UC3([View Pending Prescriptions]):::usecase
            UC4([Dispense Prescription]):::usecase
            UC5([Partially Dispense Prescription]):::usecase
            UC6([Substitute Medicine if allowed]):::usecase
            UC7([Mark Prescription as Dispensed]):::usecase
            UC18([View Dispensing History]):::usecase
        end

        subgraph Inventory ["Inventory & Stocks"]
            UC8([View Medicine Inventory]):::usecase
            UC9([Add New Medicine Stock]):::usecase
            UC10([Update Medicine Details]):::usecase
            UC11([Record Stock Adjustment]):::usecase
            UC12([Record Expired Stock Disposal]):::usecase
            UC13([Manage Batch Tracking]):::usecase
        end

        subgraph Supplier ["Suppliers & Alerts"]
            UC14([Create Purchase Order]):::usecase
            UC15([Receive Stock from Supplier]):::usecase
            UC16([Manage Suppliers]):::usecase
            UC17([Resolve Low Stock & Expiry Alerts]):::usecase
        end
        
        subgraph Reports ["Reporting"]
            UC23([Generate Pharmacy Reports]):::usecase
            UC24([Export Reports PDF/CSV/Excel]):::usecase
        end
    end

    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Pat(("fa:fa-user\nPatient")):::patient
    Adm(("fa:fa-user-tie\nAdministrator")):::admin
    SysNotif(("fa:fa-bell\nSystem Alert Engine")):::sys

    Pharm --- Core
    Pharm --- RxMgmt
    Pharm --- Inventory
    Pharm --- Supplier
    Pharm --- Reports

    %% Cross Interactions
    UC3 --- Doc
    UC4 --- Pat
    UC17 --- Adm
    
    UC4 --- SysNotif
    UC7 --- SysNotif
    UC8 --- SysNotif
```

---

## 9. Lab Technician Complete System Operations
**Roles:** Lab Technician, Doctor, Administrator, System
**Type:** Use Case Diagram

```mermaid
---
title: "9. Lab Technician Complete System Operations | Roles: Lab Tech, System, Doctor, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef lab fill:#fff3cd,stroke:#ffeeba,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    LabT(("fa:fa-microscope\nLab Technician")):::lab

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Core ["Dashboard & Profile"]
            UC1([Login to System]):::usecase
            UC2([View Dashboard Overview]):::usecase
            UC26([Update Professional Profile]):::usecase
            UC27([View Notifications]):::usecase
            UC25([View Personal Audit Trail]):::usecase
            UC28([Logout]):::usecase
        end

        subgraph Orders ["Test Order Management"]
            UC3([View Incoming Test Orders]):::usecase
            UC4([Accept Test Order]):::usecase
            UC5([Reject Test Order with reason]):::usecase
            UC6([Register Sample & Assign ID]):::usecase
            UC7([Update Sample Status]):::usecase
            UC8([Flag Rejected Sample/Poor Condition]):::usecase
        end

        subgraph Results ["Result Processing & Validation"]
            UC9([Enter Test Results per parameter]):::usecase
            UC10([Attach Result File/PDF]):::usecase
            UC11([Validate Results]):::usecase
            UC12([Release Results to Doctor]):::usecase
            UC13([Amend Validated Results]):::usecase
            UC14([Send Critical Value Alert]):::usecase
            UC15([Manage Reference Ranges]):::usecase
        end

        subgraph QA ["Equipment & QC"]
            UC16([Log Equipment Usage]):::usecase
            UC17([Schedule Equipment Maintenance]):::usecase
            UC18([Record Equipment Calibration]):::usecase
            UC19([Log Quality Control Run]):::usecase
        end

        subgraph Reagents ["Inventory & Reports"]
            UC20([Manage Reagent Inventory]):::usecase
            UC21([Record Reagent Usage]):::usecase
            UC22([View Reagent Alerts]):::usecase
            UC23([Generate Lab Reports]):::usecase
            UC24([Export Reports PDF/CSV/Excel]):::usecase
        end
    end

    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Adm(("fa:fa-user-tie\nAdministrator")):::admin
    SysNotif(("fa:fa-bell\nSystem Alert Engine")):::sys

    LabT --- Core
    LabT --- Orders
    LabT --- Results
    LabT --- QA
    LabT --- Reagents

    %% Cross Interactions
    UC3 --- Doc
    UC5 --- Doc
    UC8 --- Doc
    UC12 --- Doc
    UC14 --- Doc
    UC17 --- Adm
    
    UC8 --- SysNotif
    UC12 --- SysNotif
    UC14 --- SysNotif
    UC22 --- SysNotif
```

---

## 10. Nurse Complete System Operations
**Roles:** Nurse, Doctor, Administrator, System
**Type:** Use Case Diagram

```mermaid
---
title: "10. Nurse Complete System Operations | Roles: Nurse, System, Doctor, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef doctor fill:#cce5ff,stroke:#b8daff,stroke-width:2px,color:#000;
    classDef nurse fill:#d4edda,stroke:#c3e6cb,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    Nur(("fa:fa-user-nurse\nNurse")):::nurse

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Core ["Dashboard & Profile"]
            UC1([Login to System]):::usecase
            UC2([View Dashboard Overview]):::usecase
            UC27([View My Shift Schedule]):::usecase
            UC28([Submit Leave Request]):::usecase
            UC30([Update Professional Profile]):::usecase
            UC31([View Notifications]):::usecase
            UC32([Logout]):::usecase
        end

        subgraph Vitals ["Vitals & Clinical"]
            UC3([Record Patient Vitals]):::usecase
            UC4([View Vital History & Trends]):::usecase
            UC5([Flag Abnormal Vitals]):::usecase
            UC6([Notify Doctor of Critical Vitals]):::usecase
            UC13([Add Nursing Notes]):::usecase
            UC14([Upload Wound Images]):::usecase
            UC15([Lock Nursing Notes]):::usecase
        end

        subgraph Meds ["Medication & Fluids"]
            UC7([View Medication Admin Schedule]):::usecase
            UC8([Mark Medication Administered]):::usecase
            UC9([Mark Med Missed/Refused/Held]):::usecase
            UC16([Track IV Fluid Administration]):::usecase
            UC17([Record Fluid Balance]):::usecase
        end

        subgraph Wards ["Wards & Handovers"]
            UC10([View Ward Bed Occupancy Map]):::usecase
            UC11([Request Bed Transfer]):::usecase
            UC12([Manage Isolation Records]):::usecase
            UC18([Create Shift Handover Report]):::usecase
            UC19([Acknowledge Incoming Handover]):::usecase
        end

        subgraph Comms ["Tasks & Emergencies"]
            UC20([View & Complete Assigned Tasks]):::usecase
            UC21([Send Message to Doctor]):::usecase
            UC22([Receive Doctor Instructions]):::usecase
            UC23([Acknowledge Doctor Task]):::usecase
            UC24([Trigger Emergency Alert]):::usecase
            UC25([Record Patient Education]):::usecase
            UC26([Create Discharge Instructions]):::usecase
            UC29([Generate Nursing Reports]):::usecase
        end
    end

    Doc(("fa:fa-user-md\nDoctor")):::doctor
    Adm(("fa:fa-user-tie\nAdministrator")):::admin
    SysNotif(("fa:fa-bell\nSystem Alert Engine")):::sys

    Nur --- Core
    Nur --- Vitals
    Nur --- Meds
    Nur --- Wards
    Nur --- Comms

    %% Cross Interactions
    UC6 --- Doc
    UC11 --- Adm
    UC21 --- Doc
    UC22 --- Doc
    UC23 --- Doc
    UC24 --- Doc
    UC24 --- Adm
    UC28 --- Adm
    
    UC6 --- SysNotif
    UC18 --- SysNotif
    UC24 --- SysNotif
```

---

## 11. Ancillary & Support Staff System Operations
**Roles:** Ambulance Driver, Cleaner, Laundry Staff, Maintenance, Security, Kitchen Staff
**Type:** Use Case Diagram

```mermaid
---
title: "11. Support Staff System Operations | Roles: Ancillary Teams, Admin | Type: Use Case Diagram"
---
flowchart LR
    %% Style Definitions
    classDef admin fill:#f8d7da,stroke:#f5c6cb,stroke-width:2px,color:#000;
    classDef sys fill:#e2e3e5,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef support fill:#e0e0e0,stroke:#d6d8db,stroke-width:2px,color:#000;
    classDef usecase fill:#ffffff,stroke:#333333,stroke-width:1px,rx:20,ry:20,color:#000;

    subgraph Roles ["Ancillary Staff Roles"]
        direction TB
        Amb(("fa:fa-ambulance\nAmbulance Driver")):::support
        Clean(("fa:fa-broom\nCleaner")):::support
        Laund(("fa:fa-tshirt\nLaundry Staff")):::support
        Maint(("fa:fa-tools\nMaintenance Staff")):::support
        Sec(("fa:fa-shield-alt\nSecurity Officer")):::support
        Kitch(("fa:fa-utensils\nKitchen Staff")):::support
    end

    subgraph System ["RMU Medical Sickbay System"]
        direction TB
        
        subgraph Universal ["Universal Staff Actions"]
            U1([Login to System]):::usecase
            U2([View Dashboard Overview]):::usecase
            U3([View My Tasks]):::usecase
            U4([View Shift Schedule]):::usecase
            U5([Submit Leave Request]):::usecase
            U6([Send/Receive Messages]):::usecase
            U7([View Notifications]):::usecase
            U8([Update Profile]):::usecase
            U9([Logout]):::usecase
        end

        subgraph Transport ["Ambulance & Transport"]
            T1([View Emergency Pickups]):::usecase
            T2([Accept/Reject Trip]):::usecase
            T3([Update Trip Status]):::usecase
            T4([Log Fuel Usage]):::usecase
            T5([Report Vehicle Issue]):::usecase
        end

        subgraph Facilities ["Cleaning & Laundry"]
            C1([View Assigned Cleaning]):::usecase
            C2([Complete Cleaning Checklist]):::usecase
            C3([Upload Proof Photo]):::usecase
            C4([Report Biohazard]):::usecase
            L1([Register Laundry Batch]):::usecase
            L2([Update Laundry Status]):::usecase
            L3([Report Damaged Items]):::usecase
        end

        subgraph HardMaint ["Maintenance & Security"]
            M1([View Maintenace Queue]):::usecase
            M2([Update Repair Status]):::usecase
            M3([Log Repair Cost/Parts]):::usecase
            M4([Record Equipment Inspection]):::usecase
            S1([Log Patrol Check-in]):::usecase
            S2([Report Security Incident]):::usecase
            S3([Log Visitor Entry/Exit]):::usecase
            S4([Escalate Incident to Admin]):::usecase
        end

        subgraph Catering ["Kitchen Operations"]
            K1([View Meal Prep Tasks]):::usecase
            K2([View Dietary/Allergy Alerts]):::usecase
            K3([Update Meal Status]):::usecase
            K4([Report Ingredient Shortage]):::usecase
        end
    end

    Adm(("fa:fa-user-tie\nAdministrator")):::admin

    Roles --- Universal
    Amb --- Transport
    Clean --- Facilities
    Laund --- Facilities
    Maint --- HardMaint
    Sec --- HardMaint
    Kitch --- Catering

    %% Admin Escalations
    T5 --- Adm
    C4 --- Adm
    S4 --- Adm
    K4 --- Adm
```
