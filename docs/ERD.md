# Database ERD

MySQL 8, InnoDB, utf8mb4. Every foreign key on clinical data is `RESTRICT ON DELETE` — historical
records can never be cascaded away. Users are deactivated (and soft-deleted), never hard-deleted, so
authorship is always preserved. Patients and medical-history entries use soft deletes; finalised
consultations and prescriptions have no delete path at all.

```mermaid
erDiagram
    users ||--o{ model_has_roles : "has"
    roles ||--o{ model_has_roles : ""
    roles ||--o{ role_has_permissions : ""
    permissions ||--o{ role_has_permissions : ""

    patients ||--o{ patient_conditions : "conditions / past diseases / surgeries / family history"
    patients ||--o{ patient_allergies : ""
    patients ||--o{ patient_medications : "other / intercurrent medication"
    patients ||--o{ patient_medical_histories : "clinical notes"
    patients ||--o{ appointments : ""
    patients ||--o{ visits : ""
    patients ||--o{ consultations : ""
    patients ||--o{ prescriptions : ""
    patients ||--o{ medication_courses : ""
    patients ||--o{ medication_repetitions : ""
    patients ||--o{ laboratory_requests : ""
    patients ||--o{ laboratory_reports : ""
    patients ||--o{ follow_ups : ""

    users ||--o{ appointments : "doctor"
    users ||--o{ visits : "doctor"
    users ||--o{ consultations : "doctor"
    users ||--o{ prescriptions : "doctor / authorised_by"

    appointments |o--o| visits : "check-in creates"
    visits ||--o{ consultations : ""
    consultations ||--o| consultation_vitals : ""
    consultations ||--o{ consultation_amendments : "tracked corrections"
    consultations ||--o{ prescriptions : "1 current + superseded versions"
    consultations ||--o{ laboratory_requests : ""
    consultations ||--o| follow_ups : ""

    prescriptions ||--o{ prescription_items : ""
    prescriptions |o--o| prescriptions : "previous_version_id (amendment)"
    prescriptions |o--o{ prescriptions : "repeat_of_id (repeat)"
    medicines |o--o{ prescription_items : "optional catalogue link"
    prescription_items ||--o| medication_courses : "continuation"
    prescription_items ||--o{ medication_repetitions : "source / new item"

    laboratory_tests |o--o{ laboratory_requests : "catalogue"
    laboratory_requests ||--o{ laboratory_reports : "files / results"
    visits |o--o{ laboratory_reports : ""

    users ||--o{ activity_logs : "audit"

    patients {
        bigint id PK
        string patient_code UK "PAT-2026-00001 (configurable)"
        string full_name
        smallint age
        date date_of_birth
        string gender
        string phone
        string phone_normalized "indexed, duplicate detection"
        date registration_date
        string blood_group
        timestamp deleted_at "archive only"
    }
    visits {
        bigint id PK
        string visit_number UK
        bigint patient_id FK
        bigint doctor_id FK
        bigint appointment_id FK "nullable, unique"
        datetime visit_at
        string chief_complaint
        string status "waiting|in_consultation|completed|cancelled"
    }
    consultations {
        bigint id PK
        string consultation_number UK
        bigint visit_id FK
        text symptoms
        text clinical_observations
        text diagnosis
        text treatment_plan
        date follow_up_date
        string status "draft|completed"
        int amendment_count
    }
    prescriptions {
        bigint id PK
        string prescription_number UK
        bigint patient_id FK
        bigint visit_id FK
        bigint consultation_id FK
        bigint doctor_id FK
        date prescription_date
        string status "draft|finalized|superseded|cancelled"
        int version
        bigint previous_version_id FK
        bigint repeat_of_id FK
        bigint authorized_by FK
    }
    prescription_items {
        bigint id PK
        bigint prescription_id FK
        bigint medicine_id FK "nullable = custom medicine"
        string medicine_name
        string strength
        string dosage
        string route
        string frequency
        string timing
        smallint duration_days
        string quantity
    }
    medication_courses {
        bigint id PK
        bigint prescription_item_id FK "unique"
        date start_date
        smallint duration_days
        date expected_end_date "start + days - 1"
        string status "active|completed|discontinued|on_hold"
    }
    laboratory_reports {
        bigint id PK
        string report_number UK
        bigint laboratory_request_id FK "nullable"
        string file_path "private disk, random name"
        string mime_type
        string status "completed|reviewed"
        bigint reviewed_by FK
    }
    activity_logs {
        bigint id PK
        bigint user_id FK
        string action
        string entity_type "morph alias"
        bigint entity_id
        bigint patient_id "indexed"
        json changes "old/new diffs, secrets redacted"
        timestamp created_at
    }
```

Supporting tables: `clinic_settings` (key/value branding & defaults), `document_sequences` (row-locked
counters for patient/visit/Rx numbers), `prescription_templates`, `notifications`, plus Laravel's
`sessions`, `cache`, `jobs` and `failed_jobs`.
