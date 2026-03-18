# RMU Medical Sickbay System - Entity Relationship (ER) Diagram

This document contains the comprehensive Entity Relationship diagram modeling the core structure of the RMU Medical Sickbay System's MySQL database.

## Conventions & Legends
- **Entities:** Represented as boxes with the entity name in the header.
- **Attributes:** Listed inside the entity box.
- **Keys:** Primary Keys are denoted with `PK`. Foreign Keys are denoted with `FK`.
- **Relationships:** Labeled lines connecting entities showing the action.
- **Cardinality:** Crow's foot notation representing `1`, `M`, or `N` relationships.
  - `||--o{` : Exactly 1 to Zero-or-Many (1:M)
  - `||--||` : Exactly 1 to Exactly 1 (1:1)
  - `}o--||` : Zero-or-Many to Exactly 1 (M:1)

---

## Complete System ER Diagram
**Type:** Entity Relationship Diagram

```mermaid
---
title: "Complete System ER Diagram | RMU Medical Management System | Type: Entity Relationship Diagram"
---
erDiagram
    %% =======================
    %% USER MANAGEMENT
    %% =======================
    USERS ||--|| PATIENTS : "is a"
    USERS ||--|| DOCTORS : "is a"
    USERS ||--|| NURSES : "is a"
    USERS ||--|| STAFF : "is a"

    USERS {
        int id PK
        string user_name
        string email
        string password
        string user_role
        string name
        string phone
        string gender
        date date_of_birth
        string profile_image
        boolean is_active
        boolean is_verified
        datetime created_at
    }

    PATIENTS {
        int id PK
        int user_id FK
        string patient_id
        string full_name
        string blood_group
        text allergies
        string marital_status
        string nationality
        string insurance_provider
        int profile_completion
        string account_status
    }

    DOCTORS {
        int id PK
        int user_id FK
        string specialization
        string license_number
        date license_expiry
        int department_id
        string designation
        int years_of_experience
    }

    NURSES {
        int id PK
        int user_id FK
        string license_number
        string specialization
        int department_id
        string ward_assignment
        string shift_type
    }

    STAFF {
        int id PK
        int user_id FK
        string staff_id
        string employee_id
        string role
        int department_id
        string approval_status
        string shift_type
    }

    %% =======================
    %% APPOINTMENTS
    %% =======================
    PATIENTS ||--o{ APPOINTMENTS : "books"
    DOCTORS ||--o{ APPOINTMENTS : "assigned to"

    APPOINTMENTS {
        int appointment_id PK
        int patient_id FK
        int doctor_id FK
        date requested_date
        time requested_time
        text reason
        string status
        date reschedule_date
        text cancellation_reason
    }

    %% =======================
    %% MEDICAL RECORDS & PRESCRIPTIONS
    %% =======================
    PATIENTS ||--o{ MEDICAL_RECORDS : "has"
    DOCTORS ||--o{ MEDICAL_RECORDS : "creates"
    
    PATIENTS ||--o{ PRESCRIPTIONS : "receives"
    DOCTORS ||--o{ PRESCRIPTIONS : "issues"
    PRESCRIPTIONS ||--o{ PRESCRIPTION_ITEMS : "contains"
    MEDICINES ||--o{ PRESCRIPTION_ITEMS : "included in"

    MEDICAL_RECORDS {
        int record_id PK
        int patient_id FK
        int doctor_id FK
        text diagnosis
        text treatment_plan
        text notes
        date visit_date
    }

    PRESCRIPTIONS {
        int prescription_id PK
        int doctor_id FK
        int patient_id FK
        int pharmacist_id FK
        string status
        datetime date_issued
        datetime date_dispensed
    }

    PRESCRIPTION_ITEMS {
        int item_id PK
        int prescription_id FK
        int medicine_id FK
        int prescribed_quantity
        string dosage_instructions
        string frequency
        string duration
    }

    %% =======================
    %% PHARMACY & INVENTORY
    %% =======================
    MEDICINES ||--o{ MEDICINE_INVENTORY : "stocked as"
    PRESCRIPTIONS ||--o{ DISPENSING_RECORDS : "fulfilled by"

    MEDICINES {
        int medicine_id PK
        string name
        string category
        string unit
        boolean requires_prescription
    }

    MEDICINE_INVENTORY {
        int inventory_id PK
        int medicine_id FK
        int supplier_id FK
        string batch_number
        int quantity_in_stock
        date expiry_date
        string status
    }

    DISPENSING_RECORDS {
        int id PK
        int prescription_id FK
        int pharmacist_id FK
        int medicine_id FK
        int quantity_dispensed
    }

    MEDICINES {
        int id PK
        string medicine_id
        string medicine_name
        int stock_quantity
        int reorder_level
    }

    %% Bed Management
    PATIENTS ||--o{ BED_ASSIGNMENTS : "occupies"
    BEDS ||--o{ BED_ASSIGNMENTS : "assigned to"
    WARDS ||--o{ BEDS : "contains"
    
    WARDS {
        int id PK
        string ward_name
        string ward_type
    }
    
    BEDS {
        int id PK
        int ward_id FK
        string bed_number
        string status "Available, Occupied"
    }
    
    BED_ASSIGNMENTS {
        int id PK
        int patient_id FK
        int bed_id FK
        datetime admission_date
        string status "Active, Discharged"
    }

    %% Infrastructure & Notifications
    USERS ||--o{ NOTIFICATIONS : "receives"
    
    NOTIFICATIONS {
        int id PK
        int user_id FK
        string user_role
        string type
        string message
        boolean is_read
    }
    
    NURSES ||--o{ EMERGENCY_ALERTS : "triggers"
    
    EMERGENCY_ALERTS {
        int id PK
        string alert_type
        int triggered_by FK
        string status
        datetime triggered_at
    }
```
