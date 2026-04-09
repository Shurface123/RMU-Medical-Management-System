-- =======================================================
-- RMU MEDICAL MANAGEMENT SYSTEM
-- Landing Page & Public Interface Schema
-- Phase 2: Landing Page Tables Migration
-- =======================================================
-- Author: System Enhancement Team
-- Date: 2026-04-09
-- Description: Creates all database tables required for a
-- fully dynamic landing page, public chatbot, and
-- appointment booking interfaces. Uses safe CREATE IF NOT
-- EXISTS and ALTER to avoid dropping any existing data.
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =======================================================
-- 1. LANDING HERO CONTENT
-- Manages the main hero/banner section of the landing page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_hero_content (
    content_id          INT PRIMARY KEY AUTO_INCREMENT,
    headline_text       VARCHAR(300) NOT NULL DEFAULT 'Your Health, Our Priority',
    subheadline_text    TEXT,
    hero_bg_image_url   VARCHAR(500) DEFAULT NULL COMMENT 'Path relative to project root',
    overlay_opacity     DECIMAL(3,2) NOT NULL DEFAULT 0.55 COMMENT '0.0 = transparent to 1.0 = opaque',
    cta1_text           VARCHAR(100) DEFAULT 'Book Appointment',
    cta1_url            VARCHAR(500) DEFAULT '/RMU-Medical-Management-System/php/index.php',
    cta2_text           VARCHAR(100) DEFAULT 'Learn More',
    cta2_url            VARCHAR(500) DEFAULT '/RMU-Medical-Management-System/html/about.html',
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    updated_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 2. LANDING ANNOUNCEMENTS
-- News, events, alerts and notices displayed on landing page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_announcements (
    announcement_id     INT PRIMARY KEY AUTO_INCREMENT,
    title               VARCHAR(300) NOT NULL,
    content             TEXT NOT NULL,
    type                ENUM('news','event','alert','notice') NOT NULL DEFAULT 'news',
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    display_from        DATE DEFAULT NULL,
    display_to          DATE DEFAULT NULL,
    created_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_is_active (is_active),
    INDEX idx_display_range (display_from, display_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 3. LANDING STATISTICS
-- Dynamic counters shown on the landing page
-- (e.g. "5000+ Patients Served", "15 Doctors", etc.)
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_statistics (
    stat_id             INT PRIMARY KEY AUTO_INCREMENT,
    label               VARCHAR(150) NOT NULL COMMENT 'e.g. Patients Served, Doctors, Years of Service',
    stat_value          VARCHAR(50) NOT NULL COMMENT 'e.g. 5000+, 15, 10+',
    icon_class          VARCHAR(100) DEFAULT 'fas fa-chart-bar' COMMENT 'FontAwesome class',
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    updated_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 4. LANDING SERVICES
-- Services offered by the sickbay, shown on Services page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_services (
    service_id          INT PRIMARY KEY AUTO_INCREMENT,
    name                VARCHAR(200) NOT NULL,
    description         TEXT,
    icon_class          VARCHAR(100) DEFAULT 'fas fa-stethoscope' COMMENT 'FontAwesome class or image path',
    is_featured         TINYINT(1) NOT NULL DEFAULT 0,
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_featured (is_featured),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 5. LANDING DOCTORS (Featured Doctor Profiles)
-- Links existing doctors to the public landing page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_doctors (
    entry_id            INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id           INT NOT NULL COMMENT 'FK to doctors.id',
    is_featured         TINYINT(1) NOT NULL DEFAULT 1,
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_doctor (doctor_id),
    INDEX idx_is_featured (is_featured),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 6. LANDING STAFF
-- Staff members shown on the public staff directory page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_staff (
    entry_id            INT PRIMARY KEY AUTO_INCREMENT,
    user_id             INT DEFAULT NULL COMMENT 'FK to users.id — nullable for manually entered staff',
    name                VARCHAR(200) NOT NULL,
    role_title          VARCHAR(200) NOT NULL COMMENT 'e.g. Senior Nurse, Head Pharmacist',
    photo_path          VARCHAR(500) DEFAULT NULL,
    department          VARCHAR(150) DEFAULT NULL,
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 7. LANDING DIRECTOR
-- Director's profile and message for the public page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_director (
    director_id         INT PRIMARY KEY AUTO_INCREMENT,
    name                VARCHAR(200) NOT NULL,
    title               VARCHAR(200) NOT NULL DEFAULT 'Medical Director',
    photo_path          VARCHAR(500) DEFAULT NULL,
    bio                 TEXT,
    message             TEXT COMMENT 'Director message shown on the about/director page',
    qualifications      TEXT COMMENT 'Comma-separated or JSON list of qualifications',
    updated_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 8. LANDING ABOUT
-- Flexible content sections for the About page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_about (
    about_id            INT PRIMARY KEY AUTO_INCREMENT,
    section_name        VARCHAR(200) NOT NULL COMMENT 'e.g. Our Mission, Our Vision, Our History',
    content_text        TEXT NOT NULL,
    image_path          VARCHAR(500) DEFAULT NULL,
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    updated_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 9. LANDING GALLERY
-- Photo gallery shown on the landing page
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_gallery (
    image_id            INT PRIMARY KEY AUTO_INCREMENT,
    title               VARCHAR(300) DEFAULT NULL,
    image_url           VARCHAR(500) NOT NULL,
    category            VARCHAR(100) DEFAULT 'General' COMMENT 'e.g. Facility, Events, Staff, Patients',
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    uploaded_by         INT DEFAULT NULL COMMENT 'FK to users.id',
    uploaded_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 10. LANDING TESTIMONIALS
-- Patient testimonials, reviewed/approved before display
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_testimonials (
    testimonial_id      INT PRIMARY KEY AUTO_INCREMENT,
    patient_name        VARCHAR(200) DEFAULT 'Anonymous',
    content             TEXT NOT NULL,
    rating              TINYINT NOT NULL DEFAULT 5 COMMENT '1 to 5 stars',
    is_approved         TINYINT(1) NOT NULL DEFAULT 0,
    approved_by         INT DEFAULT NULL COMMENT 'FK to users.id',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_approved (is_approved),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 11. LANDING FAQ
-- Frequently asked questions for the public
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_faq (
    faq_id              INT PRIMARY KEY AUTO_INCREMENT,
    question            VARCHAR(500) NOT NULL,
    answer              TEXT NOT NULL,
    category            VARCHAR(100) DEFAULT 'General' COMMENT 'e.g. Appointments, Services, Billing',
    display_order       INT NOT NULL DEFAULT 0,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 12. CHATBOT KNOWLEDGE BASE
-- Stores all chatbot responses, intents, and keywords
-- =======================================================
CREATE TABLE IF NOT EXISTS chatbot_knowledge_base (
    entry_id            INT PRIMARY KEY AUTO_INCREMENT,
    category            VARCHAR(150) NOT NULL COMMENT 'e.g. General, Appointments, Medications, Emergency',
    intent_tag          VARCHAR(150) NOT NULL COMMENT 'Machine-readable intent key, e.g. book_appointment',
    keywords            JSON DEFAULT NULL COMMENT 'JSON array of trigger keywords, e.g. ["book", "schedule", "appointment"]',
    question_variants   JSON DEFAULT NULL COMMENT 'JSON array of question phrasings for matching',
    response_text       TEXT NOT NULL,
    followup_suggestion VARCHAR(500) DEFAULT NULL COMMENT 'Optional follow-up prompt for the user',
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_intent (intent_tag),
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 13. CHATBOT CONVERSATIONS
-- Tracks each chat session for analytics
-- =======================================================
CREATE TABLE IF NOT EXISTS chatbot_conversations (
    conversation_id     INT PRIMARY KEY AUTO_INCREMENT,
    session_id          VARCHAR(255) NOT NULL COMMENT 'PHP session ID or browser UUID',
    user_id             INT DEFAULT NULL COMMENT 'FK to users.id — NULL if guest',
    message_count       INT NOT NULL DEFAULT 0,
    started_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at            TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_started_at (started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 14. CHATBOT MESSAGES
-- Individual messages within a conversation
-- =======================================================
CREATE TABLE IF NOT EXISTS chatbot_messages (
    message_id          INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id     INT NOT NULL,
    sender              ENUM('user','bot') NOT NULL,
    message_text        TEXT NOT NULL,
    intent_matched      VARCHAR(150) DEFAULT NULL COMMENT 'The intent_tag matched from knowledge base',
    sent_at             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chatbot_conversations(conversation_id) ON DELETE CASCADE,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_sender (sender),
    INDEX idx_intent_matched (intent_matched)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 15. PUBLIC APPOINTMENT BOOKINGS
-- Allows logged-in patients to request appointments online
-- =======================================================
CREATE TABLE IF NOT EXISTS public_appointment_bookings (
    booking_id          INT PRIMARY KEY AUTO_INCREMENT,
    patient_user_id     INT NOT NULL COMMENT 'FK to users.id — must be logged in',
    doctor_id           INT DEFAULT NULL COMMENT 'FK to doctors.id',
    service_id          INT DEFAULT NULL COMMENT 'FK to landing_services.service_id',
    preferred_date      DATE NOT NULL,
    preferred_time      TIME DEFAULT NULL,
    reason              TEXT,
    status              ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_patient_user_id (patient_user_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_status (status),
    INDEX idx_preferred_date (preferred_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- 16. LANDING PAGE CONFIG
-- Key-value store for all landing page configuration settings
-- =======================================================
CREATE TABLE IF NOT EXISTS landing_page_config (
    config_id           INT PRIMARY KEY AUTO_INCREMENT,
    setting_key         VARCHAR(150) NOT NULL UNIQUE,
    setting_value       TEXT DEFAULT NULL,
    updated_by          INT DEFAULT NULL COMMENT 'FK to users.id',
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================
-- SEED DEFAULT DATA
-- =======================================================

-- Hero Content (1 active record)
INSERT INTO landing_hero_content
    (headline_text, subheadline_text, overlay_opacity, cta1_text, cta1_url, cta2_text, cta2_url, is_active)
VALUES
    ('Your Health, Our Priority',
     'RMU Medical Sickbay provides comprehensive healthcare services for students and staff of the Regional Maritime University. We are here for you 24/7.',
     0.55,
     'Book Appointment', '/RMU-Medical-Management-System/php/index.php',
     'Explore Services', '/RMU-Medical-Management-System/html/services.html',
     1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


-- Default Landing Statistics
INSERT INTO landing_statistics (label, stat_value, icon_class, display_order, is_active) VALUES
('Patients Served',     '5,000+',   'fas fa-users',          1, 1),
('Qualified Doctors',   '12+',      'fas fa-user-doctor',     2, 1),
('Services Offered',    '20+',      'fas fa-stethoscope',     3, 1),
('Years of Service',    '10+',      'fas fa-award',           4, 1),
('Beds Available',      '50',       'fas fa-bed',             5, 1),
('Ambulances',          '3',        'fas fa-truck-medical',   6, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


-- Default Services
INSERT INTO landing_services (name, description, icon_class, is_featured, display_order, is_active) VALUES
('General Consultation',    'Expert medical consultation for all general health concerns with our experienced doctors.', 'fas fa-stethoscope', 1, 1, 1),
('Emergency Care',          '24/7 emergency medical care for urgent and life-threatening conditions.', 'fas fa-ambulance', 1, 2, 1),
('Pharmacy Services',       'Fully stocked pharmacy with prescription and over-the-counter medications.', 'fas fa-pills', 1, 3, 1),
('Laboratory Services',     'Comprehensive diagnostic tests and laboratory analysis with quick turnaround.', 'fas fa-flask', 1, 4, 1),
('Bed Management',          'Comfortable inpatient facilities for patients requiring extended care.',  'fas fa-bed', 0, 5, 1),
('Health Education',        'Regular health awareness campaigns, seminars and preventive care programs.', 'fas fa-heart-pulse', 0, 6, 1),
('Mental Health Support',   'Confidential counselling and mental health support services for students.', 'fas fa-brain', 0, 7, 1),
('Vaccination Services',    'Immunisation programs and vaccination services for all eligible persons.', 'fas fa-syringe', 0, 8, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


-- Default FAQ Entries
INSERT INTO landing_faq (question, answer, category, display_order, is_active) VALUES
('What are the sickbay opening hours?',    'The RMU Medical Sickbay operates 24 hours a day, 7 days a week for emergency services. Regular consultation hours are Monday – Friday, 8:00 AM to 5:00 PM.', 'General', 1, 1),
('How do I book an appointment?',          'You can book an appointment online by logging in to your student/staff portal, or by visiting the sickbay in person during working hours.', 'Appointments', 2, 1),
('Can I get my prescriptions refilled?',   'Yes. Prescription refills require a consultation with the attending physician. Please bring your original prescription or medical records.', 'Medications', 3, 1),
('Is the sickbay free for students?',      'Healthcare services at the RMU Sickbay are heavily subsidised for all registered students. Some specialist services may attract a small fee.', 'Billing', 4, 1),
('What should I do in a medical emergency?', 'In a life-threatening emergency, call our emergency hotline immediately or proceed directly to the sickbay. Our ambulance service is available 24/7.', 'Emergency', 5, 1),
('Can staff members use the sickbay?',     'Yes. All staff members of the Regional Maritime University are entitled to use sickbay services. Some services may require valid staff ID.', 'General', 6, 1)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


-- Default Landing Page Config
INSERT INTO landing_page_config (setting_key, setting_value) VALUES
('default_theme',               'light'),
('chatbot_greeting',            'Hello! I am the RMU Medical Assistant. How can I help you today?'),
('chatbot_enabled',             '1'),
('announcements_enabled',       '1'),
('gallery_enabled',             '1'),
('testimonials_enabled',        '1'),
('faq_enabled',                 '1'),
('statistics_enabled',          '1'),
('online_booking_enabled',      '1'),
('emergency_hotline',           '153'),
('contact_email',               'sickbay.text@st.rmu.edu.gh'),
('contact_phone',               '0502371207'),
('facility_name',               'RMU Medical Sickbay'),
('facility_address',            'Regional Maritime University, Nungua, Accra, Ghana'),
('google_maps_url',             ''),
('facebook_url',                ''),
('twitter_url',                 ''),
('instagram_url',               '')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


-- Default Chatbot Knowledge Base
INSERT INTO chatbot_knowledge_base (category, intent_tag, keywords, question_variants, response_text, followup_suggestion, is_active) VALUES
('General',      'greeting',
 '["hello","hi","hey","good morning","good afternoon","good evening","howdy"]',
 '["Hello","Hi there","Hey","Good morning"]',
 'Hello! Welcome to the RMU Medical Sickbay virtual assistant. I can help you with appointments, services, medications, and general health inquiries. How can I assist you today?',
 'Would you like to book an appointment or learn about our services?', 1),

('General',      'goodbye',
 '["bye","goodbye","see you","take care","thank you","thanks"]',
 '["Goodbye","Thank you","Thanks for the help"]',
 'Thank you for using the RMU Medical Sickbay assistant. Take care and stay healthy! If you need further assistance, we are always here.',
 NULL, 1),

('Appointments', 'book_appointment',
 '["book","schedule","appointment","see a doctor","consult","visit"]',
 '["How do I book an appointment?","I want to see a doctor","Can I schedule a visit?"]',
 'To book an appointment, please log in to your student or staff portal and navigate to the "Book Appointment" section. You can also visit the sickbay in person during working hours (Mon–Fri, 8AM–5PM).',
 'Would you like me to take you to the booking page?', 1),

('General',      'opening_hours',
 '["hours","open","close","working hours","when","time","available"]',
 '["What are your opening hours?","When is the sickbay open?","Are you open now?"]',
 'The RMU Medical Sickbay is open for consultations Monday to Friday, 8:00 AM to 5:00 PM. Emergency services are available 24 hours a day, 7 days a week.',
 'Do you need emergency assistance right now?', 1),

('Emergency',    'emergency',
 '["emergency","urgent","critical","help","ambulance","accident","dying","unconscious"]',
 '["I need emergency help","This is urgent","Call an ambulance"]',
 'FOR EMERGENCIES, please call our emergency hotline: 153 immediately. If on campus, proceed directly to the RMU Sickbay or flag down any staff member. Do NOT delay seeking help.',
 NULL, 1),

('Services',     'list_services',
 '["services","treatment","what do you offer","what can you treat","facilities"]',
 '["What services do you offer?","What can the sickbay treat?","What facilities are available?"]',
 'Our sickbay offers: General Consultation, Emergency Care, Pharmacy Services, Laboratory Testing, Inpatient Bed Management, Mental Health Support, Vaccination, and Health Education programs.',
 'Would you like more details about any specific service?', 1),

('Medications',  'prescription',
 '["prescription","medicine","medication","refill","drugs","pharmacy"]',
 '["How do I get a prescription?","Can I refill my medication?","Where is the pharmacy?"]',
 'Prescriptions are issued after a consultation with our doctors. You can collect your medications from the sickbay pharmacy. For refills, please schedule a follow-up appointment.',
 'Would you like to book a consultation?', 1),

('Billing',      'cost_fees',
 '["cost","fee","price","pay","free","charge","bill","invoice"]',
 '["Is the sickbay free?","How much does it cost?","Do I have to pay?"]',
 'Healthcare services at the RMU Sickbay are heavily subsidised for all registered students and are generally provided at no direct cost for primary care. Some specialist procedures may attract nominal fees.',
 'Do you have a specific service you would like pricing for?', 1),

('General',      'contact_info',
 '["contact","phone","email","address","location","where","find"]',
 '["How do I contact you?","Where is the sickbay located?","What is your phone number?"]',
 'You can reach us at:\n📞 Phone: 0502371207\n📧 Email: sickbay.text@st.rmu.edu.gh\n📍 Location: Regional Maritime University, Nungua, Accra, Ghana',
 'Is there anything else you would like to know?', 1),

('Mental Health', 'mental_health',
 '["stress","anxiety","depressed","depression","mental","counselling","counseling","therapist","sad","overwhelmed"]',
 '["I am feeling stressed","I need mental health support","I am depressed","Can I see a counsellor?"]',
 'We understand that student life can be challenging. Our sickbay offers confidential mental health and counselling services. Please do not hesitate to visit us during working hours or call us to schedule a private session. You are not alone.',
 'Would you like to book a counselling session?', 1),

('Lab',          'lab_tests',
 '["lab","test","blood test","laboratory","results","sample","urine","analysis"]',
 '["Can I get a blood test?","How do I get my lab results?","What lab tests are available?"]',
 'Our laboratory offers a wide range of diagnostic tests including blood tests, urinalysis, malaria tests, and more. Tests are usually ordered by our doctors. Results are typically available within 24–48 hours.',
 'Would you like to book an appointment with a doctor to request tests?', 1)

ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;


SET FOREIGN_KEY_CHECKS = 1;

-- =======================================================
-- COMPLETION MESSAGES
-- =======================================================
SELECT 'Landing page tables migration completed successfully!' AS Status;
SELECT 'New tables: landing_hero_content, landing_announcements, landing_statistics, landing_services, landing_doctors, landing_staff, landing_director, landing_about, landing_gallery, landing_testimonials, landing_faq, chatbot_knowledge_base, chatbot_conversations, chatbot_messages, public_appointment_bookings, landing_page_config' AS Tables_Created;
SELECT 'Seed data inserted for: statistics, services, faq, config, chatbot_knowledge_base' AS Seeded_Data;
