-- ============================================================
-- RMU MEDICAL SICKBAY — LAB TECHNICIAN DASHBOARD SEED DATA
-- Technician : Jefferson Forson  (lab_technicians.id = 2)
-- Doctor     : Dr. Joyce Eli     (doctors.id = 4)
-- Patients   : IDs 5-10 (existing patients)
-- Generated  : 2026-04-19
-- All INSERTs use INSERT IGNORE for idempotent re-runs
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ══════════════════════════════════════════════════════════════
-- 1)  LAB_EQUIPMENT  — 10 instruments in the lab
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_equipment (name, model, serial_number, manufacturer, category, department, location, purchase_date, warranty_expiry, status, last_calibration_date, next_calibration_date, last_maintenance_date, next_maintenance_date, assigned_technician_id, notes) VALUES
('Sysmex XN-550 Auto-Analyzer',    'XN-550',      'SYS-2024-001', 'Sysmex Corporation',    'Hematology',    'Laboratory', 'Sector A – Bench 1', '2023-01-15', '2026-01-15', 'Operational',     '2026-03-01', '2026-09-01', '2025-12-15', '2026-06-15', 2, 'Primary CBC analyzer. Daily QC passing.'),
('Roche Cobas C111 Chemistry Analyzer', 'Cobas C111', 'RCH-2023-045', 'Roche Diagnostics', 'Biochemistry',  'Laboratory', 'Sector B – Bench 2', '2022-06-10', '2025-06-10', 'Operational',     '2026-02-20', '2026-08-20', '2026-01-10', '2026-07-10', 2, 'Used for LFT, RFT, glucose, lipid profile.'),
('bio-Merieux VIDAS Immunoanalyzer',  'VIDAS 3',   'BIO-2024-112', 'bioMérieux',            'Immunology',    'Laboratory', 'Sector C – Bench 1', '2024-02-01', '2027-02-01', 'Operational',     '2026-01-15', '2026-07-15', '2025-11-20', '2026-05-20', 2, 'ELISA-based serology tests.'),
('Mindray BC-5380 Hematology Sys',   'BC-5380',   'MIN-2022-033', 'Mindray',               'Hematology',    'Laboratory', 'Sector A – Bench 3', '2022-03-15', '2025-03-15', 'Calibration Due', '2025-10-10', '2026-04-10', '2025-09-01', '2026-03-01', 2, 'Backup CBC machine. Calibration scheduled.'),
('Thermo Scientific Centrifuge',     'Sorvall ST8','THR-2021-078', 'Thermo Fisher',         'Centrifuge',    'Laboratory', 'Sector B – Bench 4', '2021-07-20', '2024-07-20', 'Operational',     '2026-03-20', '2026-09-20', '2026-02-01', '2026-08-01', 2, 'Used for serum separation.'),
('Beckman Coulter AU480 Chemistry',  'AU480',     'BCK-2023-091', 'Beckman Coulter',       'Biochemistry',  'Laboratory', 'Sector B – Bench 1', '2023-09-10', '2026-09-10', 'Operational',     '2026-04-01', '2026-10-01', '2026-03-05', '2026-09-05', 2, 'High-throughput biochemistry workstation.'),
('Bio-Rad iQ5 PCR Cycler',           'iQ5',       'BRD-2024-055', 'Bio-Rad Laboratories',  'Molecular',     'Laboratory', 'Sector D – PCR Room','2024-05-01', '2027-05-01', 'Operational',     '2026-04-10', '2026-10-10', '2026-03-10', '2026-09-10', 2, 'Used for DNA amplification.'),
('Olympus CX43 Microscope',          'CX43',      'OLY-2020-018', 'Olympus Corporation',   'Microscopy',    'Laboratory', 'Sector A – Bench 2', '2020-11-12', '2023-11-12', 'Maintenance',     '2025-08-01', '2026-02-01', '2025-12-01', '2026-06-01', 2, 'Needs binocular head replacement.'),
('Siemens Clinitek Status+ UA Analyzer', 'Clinitek Status+', 'SIE-2023-202', 'Siemens Healthineers', 'Urinalysis', 'Laboratory', 'Sector A – Bench 4', '2023-04-22', '2026-04-22', 'Operational', '2026-03-15', '2026-09-15', '2026-01-20', '2026-07-20', 2, 'Automated urinalysis strips reader.'),
('BioSafety Cabinet Class II',       'MSC-Advantage', 'THR-2022-067', 'Thermo Fisher',    'Safety Cabinet','Laboratory', 'Sector D – Micro',  '2022-08-30', '2025-08-30', 'Operational',     '2026-02-28', '2026-08-28', '2025-10-15', '2026-04-15', 2, 'Microbiological work cabinet. Filters replaced Jan 2026.');

-- ══════════════════════════════════════════════════════════════
-- 2)  REAGENT_INVENTORY  — 10 reagents/consumables
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO reagent_inventory (name, catalog_number, manufacturer, category, unit, quantity_in_stock, reorder_level, unit_cost, expiry_date, storage_conditions, status, batch_number, date_received, supplier_name) VALUES
('Sysmex XN-550 Cell Pack DCL',      'CAT-SYS-001', 'Sysmex',            'Hematology Reagent', 'pcs', 48, 10, 120.00, '2027-01-15', '2–8°C',        'In Stock',      'BT2024-0011', '2024-11-01', 'LabMed Ghana'),
('Total Bilirubin Reagent (Jendrassik)', 'CAT-RCH-002', 'Roche',         'Biochemistry',        'mL',  320, 50, 45.50,  '2026-10-31', '2–8°C',        'In Stock',      'BT2025-0342', '2025-01-10', 'Romedic Supplies'),
('Creatinine Jaffe Reagent',         'CAT-RCH-003', 'Roche',            'Biochemistry',        'mL',  180, 50, 38.00,  '2026-12-20', '2–8°C',        'In Stock',      'BT2025-0211', '2025-01-10', 'Romedic Supplies'),
('Glucose Oxidase Reagent',          'CAT-BIO-004', 'Randox',           'Biochemistry',        'mL',  90,  30, 22.00,  '2026-08-01', '15–25°C',      'Low Stock',     'BT2024-0778', '2024-09-20', 'DiagLab Africa'),
('VIDAS HIV Duo Ultra Reagent',      'CAT-BIO-012', 'bioMérieux',       'Serology',            'test',60,  15, 250.00, '2026-06-30', '2–8°C',        'In Stock',      'BT2025-0481', '2025-02-15', 'Romedic Supplies'),
('Malaria RDT Kit (CareStart)',       'CAT-MAL-005', 'Access Bio',       'Rapid Diagnostics',   'pcs', 200, 40, 8.00,   '2027-03-01', '2–30°C',       'In Stock',      'BT2025-0551', '2025-03-01', 'LabMed Ghana'),
('HbsAg Rapid Test Strip',           'CAT-SER-006', 'SD Bioline',       'Serology',            'pcs', 150, 30, 12.50,  '2026-11-15', '2–30°C',       'In Stock',      'BT2025-0233', '2025-01-20', 'DiagLab Africa'),
('Urine Dipstick (10-param)',        'CAT-URA-007', 'Siemens',          'Urinalysis',          'pcs', 500, 100, 1.80,  '2026-09-30', '15–30°C',      'In Stock',      'BT2024-0912', '2024-12-05', 'LabMed Ghana'),
('Blood Culture Bottles (Aerobic)',  'CAT-MIC-008', 'BD Diagnostics',   'Microbiology',        'pcs', 40,  20, 35.00,  '2026-07-31', '2–25°C',       'Low Stock',     'BT2025-0105', '2025-01-08', 'Romedic Supplies'),
('EDTA Vacutainer Tubes (3 mL)',     'CAT-CON-009', 'BD Vacutainer',    'Consumables',         'pcs', 800, 200, 0.75,  '2028-01-01', '15–25°C',      'In Stock',      'BT2024-0655', '2024-10-15', 'LabMed Ghana');

-- ══════════════════════════════════════════════════════════════
-- 3)  LAB_TEST_ORDERS  — 10 orders (Jefferson processes; Dr. Joyce ordered)
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_test_orders (order_id, patient_id, doctor_id, technician_id, test_catalog_id, test_name, urgency, order_date, required_by_date, clinical_notes, diagnosis, order_status) VALUES
('LAB-ORD-2001', 5,  4, 2, 1,  'Complete Blood Count (CBC)',          'Routine',  '2026-04-01', '2026-04-02', 'Routine annual CBC. Patient reports fatigue.',           'Anaemia query', 'Completed'),
('LAB-ORD-2002', 6,  4, 2, 2,  'Blood Glucose (Fasting)',             'Urgent',   '2026-04-02', '2026-04-02', 'Fasting glucose for DM screening. 8h fast confirmed.',  'Diabetes Mellitus T2', 'Completed'),
('LAB-ORD-2003', 7,  4, 2, 4,  'Liver Function Test (LFT)',           'Routine',  '2026-04-03', '2026-04-04', 'Monitor LFT in patient on hepatotoxic medication.',      'Drug-induced liver injury query', 'Completed'),
('LAB-ORD-2004', 8,  4, 2, 5,  'Renal Function Test (RFT)',           'STAT',     '2026-04-05', '2026-04-05', 'Acute kidney injury suspected. Creatinine critical.',    'Acute Kidney Injury', 'Completed'),
('LAB-ORD-2005', 9,  4, 2, 6,  'Lipid Profile',                      'Routine',  '2026-04-07', '2026-04-08', 'Cardiovascular risk stratification. Fasting sample.',   'Dyslipidaemia query', 'Completed'),
('LAB-ORD-2006', 10, 4, 2, 8,  'Malaria Parasite Test (MP)',          'Urgent',   '2026-04-08', '2026-04-08', 'High fever, chills. Suspected uncomplicated malaria.',  'Malaria Falciparum', 'Completed'),
('LAB-ORD-2007', 5,  4, 2, 7,  'Urinalysis',                         'Routine',  '2026-04-09', '2026-04-10', 'Dysuria + frequency. Rule out UTI.',                    'UTI query', 'Processing'),
('LAB-ORD-2008', 6,  4, 2, 12, 'HIV Rapid Test',                     'Urgent',   '2026-04-10', '2026-04-10', 'Pre-operative HIV screen. Patient consented.',           'Pre-op workup', 'Sample Collected'),
('LAB-ORD-2009', 7,  4, 2, 15, 'Widal Test',                         'Routine',  '2026-04-12', '2026-04-13', 'Prolonged fever with GI symptoms. Typhoid query.',      'Enteric Fever query', 'Pending'),
('LAB-ORD-2010', 8,  4, 2, 10, 'Blood Culture & Sensitivity',        'STAT',     '2026-04-14', '2026-04-14', 'Sepsis protocol activated. Blood cultures before ABx.',  'Sepsis', 'Accepted');

-- ══════════════════════════════════════════════════════════════
-- 4)  LAB_SAMPLES  — 10 specimen collections by Jefferson
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_samples (sample_id, order_id, patient_id, technician_id, sample_type, sample_code, collection_date, collection_time, collected_by, container_type, volume_collected, storage_location, condition_on_receipt, status, notes) VALUES
('SAMP-2001', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2001'), 5,  2, 'Blood',  'BLD-20260401-001', '2026-04-01', '07:45:00', 2, 'EDTA Vacutainer 3mL',   '3 mL',  'Fridge Rack A-1', 'Good',        'Collected',   'Good sample. No haemolysis.'),
('SAMP-2002', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2002'), 6,  2, 'Blood',  'BLD-20260402-001', '2026-04-02', '08:10:00', 2, 'Plain Vacutainer 5mL',  '5 mL',  'Bench B centrifuge', 'Good',    'Processing',  'Patient confirmed 8h fast.'),
('SAMP-2003', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2003'), 7,  2, 'Blood',  'BLD-20260403-001', '2026-04-03', '09:00:00', 2, 'SST Vacutainer 5mL',    '5 mL',  'Fridge Rack B-2', 'Good',        'Received',    'Centrifuged at 3000 rpm x 10 min.'),
('SAMP-2004', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2004'), 8,  2, 'Blood',  'BLD-20260405-001', '2026-04-05', '11:30:00', 2, 'SST Vacutainer 5mL',    '5 mL',  'Bench B – urgent rack', 'Good', 'Processing',  'STAT sample. Prioritised.'),
('SAMP-2005', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2005'), 9,  2, 'Blood',  'BLD-20260407-001', '2026-04-07', '07:55:00', 2, 'EDTA + SST Vacutainer', '8 mL',  'Fridge Rack A-3', 'Good',        'Collected',   '12h fast confirmed. Lipid profile.'),
('SAMP-2006', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2006'), 10, 2, 'Blood',  'BLD-20260408-001', '2026-04-08', '14:20:00', 2, 'Thin/Thick Film Slide',  '2 mL',  'Microscopy rack',  'Good',        'Processing',  'Thick and thin film prepared for MP.'),
('SAMP-2007', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2007'), 5,  2, 'Urine',  'URI-20260409-001', '2026-04-09', '10:05:00', 2, 'Universal Urine Cup',    '20 mL', 'Bench A – UA analyzer', 'Good',  'Received',    'Mid-stream clean catch sample.'),
('SAMP-2008', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2008'), 6,  2, 'Blood',  'BLD-20260410-001', '2026-04-10', '09:40:00', 2, 'Plain Vacutainer 3mL',   '3 mL',  'Fridge Rack C-1', 'Good',        'Collected',   'HIV RDT strip test prepared.'),
('SAMP-2009', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2009'), 7,  2, 'Blood',  'BLD-20260412-001', '2026-04-12', '08:30:00', 2, 'Plain Vacutainer 5mL',   '5 mL',  'Fridge Rack B-4', 'Good',        'Collected',   'Widal test – serum Obtained.'),
('SAMP-2010', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2010'), 8,  2, 'Blood',  'BLD-20260414-001', '2026-04-14', '16:00:00', 2, 'Blood Culture Bottle (Aerobic)', '10 mL', 'Incubator 37°C', 'Good', 'Processing',  'Two blood culture sets drawn. Aerobic + anaerobic.');

-- ══════════════════════════════════════════════════════════════
-- 5)  LAB_RESULTS  — 10 validated/released results
--     Uses lab_results composite (patient_id, test_id, order_id, technician_id)
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_results (patient_id, test_id, doctor_id, test_date, result_date, status, result_interpretation, technician_notes, submitted_by, doctor_reviewed, patient_accessible, order_id, technician_id, validated_by, validated_at, result_status, released_to_doctor, released_at, unit_of_measurement, reference_range_min, reference_range_max) VALUES
(5,  1,  4, '2026-04-01', '2026-04-01', 'Completed', 'Abnormal',   'Hb: 8.2 g/dL – Microcytic hypochromic anaemia pattern. Suggest iron studies.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2001'), 2, 2, '2026-04-01 14:00:00', 'Released', 1, '2026-04-01 14:30:00', 'g/dL', 12.0000, 16.0000),
(6,  2,  4, '2026-04-02', '2026-04-02', 'Completed', 'Critical',   'FBG: 18.4 mmol/L – Severe hyperglycaemia. Notified Dr. Joyce immediately.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2002'), 2, 2, '2026-04-02 10:30:00', 'Released', 1, '2026-04-02 11:00:00', 'mmol/L', 3.9000, 6.1000),
(7,  4,  4, '2026-04-03', '2026-04-03', 'Completed', 'Abnormal',   'ALT 92 U/L (N: 7-56), ALP 210 U/L – Elevated. Consistent with drug-induced hepatitis.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2003'), 2, 2, '2026-04-03 15:00:00', 'Released', 1, '2026-04-03 15:45:00', 'U/L', 7.0000, 56.0000),
(8,  5,  4, '2026-04-05', '2026-04-05', 'Completed', 'Critical',   'Creatinine: 642 µmol/L, eGFR: 8 mL/min – Severe AKI. Urgent nephrology referral indicated.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2004'), 2, 2, '2026-04-05 12:30:00', 'Released', 1, '2026-04-05 13:00:00', 'µmol/L', 44.0000, 106.0000),
(9,  6,  4, '2026-04-07', '2026-04-07', 'Completed', 'Abnormal',   'Total Chol: 6.8 mmol/L, LDL: 4.2 mmol/L – Hypercholesterolaemia. Statin therapy recommended.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2005'), 2, 2, '2026-04-07 11:00:00', 'Released', 1, '2026-04-07 11:30:00', 'mmol/L', 0.0000, 5.2000),
(10, 8,  4, '2026-04-08', '2026-04-08', 'Completed', 'Abnormal',   'P. falciparum +++ on thick film. Parasite density high. Anti-malarials initiated.', 2, 1, 1, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2006'), 2, 2, '2026-04-08 15:15:00', 'Released', 1, '2026-04-08 15:45:00', 'parasites/µL', 0.0000, 0.0000),
(5,  7,  4, '2026-04-09', '2026-04-09', 'Completed', 'Abnormal',   'Nitrites: Positive, Leucocytes: +++, Bacteria: >100,000 CFU/mL – UTI confirmed.', 2, 1, 0, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2007'), 2, 2, '2026-04-09 13:00:00', 'Validated', 0, NULL, 'CFU/mL', 0.0000, 10000.0000),
(6,  12, 4, '2026-04-10', '2026-04-10', 'Completed', 'Normal',     'HIV RDT: Non-reactive. Pre-operative clearance granted.', 2, 0, 0, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2008'), 2, 2, '2026-04-10 10:00:00', 'Released', 1, '2026-04-10 10:20:00', 'N/A', 0.0000, 0.0000),
(7,  15, 4, '2026-04-12', '2026-04-13', 'In Progress', 'Inconclusive', 'H antigen 1:160 borderline. Repeat serology after 5 days recommended.', 2, 0, 0, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2009'), 2, NULL, NULL, 'Pending Validation', 0, NULL, 'titre', 0.0000, 80.0000),
(8,  10, 4, '2026-04-14', NULL,         'In Progress', 'Normal',    'Incubating 72h. No growth at 24h. Continue monitoring.', 2, 0, 0, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2010'), 2, NULL, NULL, 'Draft', 0, NULL, 'growth', 0.0000, 0.0000);

-- ══════════════════════════════════════════════════════════════
-- 6)  LAB_INTERNAL_MESSAGES  — 10 messages between Jefferson & Dr. Joyce
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_internal_messages (sender_id, sender_role, receiver_id, receiver_role, patient_id, order_id, subject, message_content, is_read, priority) VALUES
(2, 'lab_technician', 4, 'doctor', 6,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2002'), 'CRITICAL: Fasting Glucose 18.4 mmol/L – Immediate Action',   'Dr. Joyce, the fasting blood glucose for Adjoa Yeboah (ORD-2002) has returned critically elevated at 18.4 mmol/L. Patient is still in the facility. Please advise urgently. — Jefferson Forson, Lab Tech.', 1, 'Urgent'),
(4, 'doctor', 2, 'lab_technician', 6,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2002'), 'Re: CRITICAL FBG – Action Taken',                             'Thank you Jefferson. I have reviewed the result and have initiated IV insulin protocol. Please release result to patient record and document in your ledger. Follow up glucose in 4 hours. — Dr. Joyce Eli.', 1, 'Urgent'),
(2, 'lab_technician', 4, 'doctor', 8,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2004'), 'CRITICAL: Creatinine 642 µmol/L – Acute Kidney Injury',       'Dr. Joyce, Daniel Antwi (ORD-2004) creatinine is critically elevated at 642 µmol/L with eGFR of 8. This is consistent with severe AKI. Result released. Urgent nephrology consult recommended.', 1, 'Urgent'),
(4, 'doctor', 2, 'lab_technician', 8,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2004'), 'Re: AKI Alert – Nephrology Referral Arranged',                 'Acknowledged Jefferson. I have placed an urgent nephrology referral. Please ensure creatinine + BUN are re-checked in 6 hours and flag to me immediately. Good catch. — Dr. Joyce.', 1, 'Urgent'),
(2, 'lab_technician', 4, 'doctor', 10, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2006'), 'Malaria Result – P. falciparum +++ Confirmed',                 'Dr. Joyce, Kofi Adu (ORD-2006) malaria film confirms P. falciparum heavy parasitaemia (+++ on thick film). Result released. Patient was febrile at time of collection. Artemether-lumefantrine should be initiated.', 1, 'Normal'),
(4, 'doctor', 2, 'lab_technician', 10, (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2006'), 'Re: Malaria Result – Treatment Started',                       'Thank you Jefferson. AL course started immediately. Please also arrange a follow-up thin film on Day 3. Note the parasitaemia for your weekly QC report. — Dr. Joyce.', 0, 'Normal'),
(4, 'doctor', 2, 'lab_technician', 5,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2001'), 'New Order – Lovelace Baidoo CBC + Iron Studies',               'Jefferson, following up on Lovelace Baidoo anemia results (Hb 8.2). Please add Serum Ferritin and TIBC to the pending orders. I will send a formal order shortly. Appreciate the quick turnaround.', 0, 'Normal'),
(2, 'lab_technician', 4, 'doctor', 7,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2007'), 'UTI Result – Adjoa Appiah Culture Pending',                    'Dr. Joyce, urinalysis for Adjoa Appiah (ORD-2007) shows significant bacteriuria and leucocyturia. Empirical UTI. Culture and sensitivity has been set up — results expected in 48h. Result validated awaiting your review.', 0, 'Normal'),
(4, 'doctor', 2, 'lab_technician', 9,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2005'), 'Query: Lipid Profile – Missing HDL Value',                     'Jefferson, I noticed the lipid profile for Adjoa Appiah (ORD-2005) does not include the HDL cholesterol value. Was it left out of the panel? Please verify and update accordingly. — Dr. Joyce.', 1, 'Normal'),
(2, 'lab_technician', 4, 'doctor', 9,  (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2005'), 'Re: Lipid Profile – HDL Included in Updated Report',           'Dr. Joyce, apologies for the omission. HDL was 0.9 mmol/L (Low). I have updated the result record. TC:HDL ratio = 7.6 (High cardiovascular risk). Full corrected report now available for your review.', 0, 'Normal');

-- ══════════════════════════════════════════════════════════════
-- 7)  LAB_NOTIFICATIONS  — 10 notifications for Jefferson
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_notifications (recipient_id, recipient_role, sender_id, sender_role, message, type, is_read, related_module, related_record_id) VALUES
(2, 'lab_technician', 4, 'doctor',  'New STAT order placed by Dr. Joyce Eli: Creatinine / Renal Function (ORD-2004). Patient: Daniel Antwi. Urgency: STAT.', 'New Order', 1, 'orders', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2004')),
(2, 'lab_technician', 4, 'doctor',  'New URGENT order placed by Dr. Joyce Eli: Blood Glucose Fasting (ORD-2002). Patient: Adjoa Yeboah. Urgency: Urgent.', 'New Order', 1, 'orders', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2002')),
(2, 'lab_technician', 4, 'doctor',  'Critical value alert: Fasting Glucose 18.4 mmol/L for Adjoa Yeboah has been released to Dr. Joyce Eli. Documenting critical notification.', 'Critical Value', 1, 'results', NULL),
(2, 'lab_technician', NULL, NULL,   'Reagent LOW STOCK alert: Glucose Oxidase Reagent (CAT-BIO-004) is below reorder threshold (90 units remaining, min 30). Re-order recommended.', 'Reagent Alert', 0, 'inventory', NULL),
(2, 'lab_technician', NULL, NULL,   'Equipment alert: Mindray BC-5380 (Bench 3) is due for calibration. Last calibration: Oct 2025. Please schedule immediately.', 'Equipment Alert', 0, 'equipment', NULL),
(2, 'lab_technician', 4, 'doctor',  'Dr. Joyce Eli has reviewed and added notes to Result ORD-2001 (CBC – Lovelace Baidoo). Please review doctor notes.', 'Result Ready', 1, 'results', NULL),
(2, 'lab_technician', NULL, NULL,   'New URGENT order placed by Dr. Joyce Eli: HIV Rapid Test (ORD-2008). Patient: Adjoa Yeboah. Pre-operative screen.', 'New Order', 0, 'orders', (SELECT id FROM lab_test_orders WHERE order_id='LAB-ORD-2008')),
(2, 'lab_technician', NULL, NULL,   'Blood Culture bottles (CAT-MIC-008) stock is LOW (40 units remaining, min 20). Procurement has been notified.', 'Reagent Alert', 0, 'inventory', NULL),
(2, 'lab_technician', NULL, NULL,   'Monthly Quality Control Report is due. Please complete QC documentation for April 2026 before end of the month.', 'Quality Control', 0, 'qc', NULL),
(2, 'lab_technician', NULL, NULL,   'System: Lab Dashboard has been updated with new Export Hub functionality. CSV and Excel exports are now active for Reports module.', 'System', 0, 'system', NULL);

-- ══════════════════════════════════════════════════════════════
-- 8)  LAB_QUALITY_CONTROL  — 10 QC runs by Jefferson
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_quality_control (technician_id, equipment_id, test_catalog_id, qc_date, qc_type, lot_number, expected_range_min, expected_range_max, result_obtained, passed, corrective_action, notes) VALUES
(2, 1, 1,  '2026-04-01', 'Internal', 'LOT-SYS-2024-01', 4.50, 5.50, 4.92, 1, NULL,               'Daily CBC QC Level-1 passed. CV < 2%.'),
(2, 1, 1,  '2026-04-01', 'Internal', 'LOT-SYS-2024-02', 4.50, 5.50, 4.88, 1, NULL,               'Daily CBC QC Level-2 passed.'),
(2, 2, 2,  '2026-04-02', 'Internal', 'LOT-RCH-2025-10', 5.00, 5.80, 5.35, 1, NULL,               'Glucose QC: Normal control passed.'),
(2, 2, 4,  '2026-04-03', 'Internal', 'LOT-RCH-2025-11', 28.0, 38.0, 29.5, 1, NULL,               'LFT QC: ALT within acceptable range.'),
(2, 2, 5,  '2026-04-05', 'Internal', 'LOT-RCH-2025-12', 80.0, 120.0, 89.0, 1, NULL,              'RFT QC: Creatinine normal control passed.'),
(2, 2, 6,  '2026-04-07', 'Internal', 'LOT-RCH-2025-13', 4.80, 5.20, 4.75, 0, 'Reagent recalibrated and QC re-run. Second result 5.05 – accepted.', 'Lipid QC: Initial fail due to reagent temperature. Corrected.'),
(2, 3, 12, '2026-04-10', 'Internal', 'LOT-BIO-2025-04', 0.00, 0.50, 0.10, 1, NULL,               'HIV VIDAS QC: Non-reactive control passed.'),
(2, 9, 7,  '2026-04-09', 'Internal', 'LOT-SIE-2024-07', 1.010, 1.030, 1.020, 1, NULL,            'UA analyzer: Specific gravity control within range.'),
(2, 1, 1,  '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 4.60, 5.40, 4.98, 1, NULL,               'External QC (proficiency testing) – CBC panel. Acceptable performance.'),
(2, 2, 2,  '2026-04-14', 'External', 'LOT-EXT-2026-Q1', 5.10, 5.90, 5.68, 1, NULL,               'External QC – Glucose panel. Result within target range.');

-- ══════════════════════════════════════════════════════════════
-- 9)  LAB_AUDIT_TRAIL  — 10 audit entries by Jefferson
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_audit_trail (technician_id, user_id, action_type, module_affected, record_id, old_value, new_value, ip_address, device_info) VALUES
(2, 2, 'Result Entry',    'Lab Results',         NULL, NULL, 'CBC result entered for patient 5 (ORD-2001). Hb: 8.2 g/dL.',          '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Result Released', 'Lab Results',         NULL, 'Draft', 'Released',                                                          '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Critical Alert',  'Lab Results',         NULL, NULL, 'CRITICAL FBG 18.4 mmol/L — Dr. Joyce Eli notified via message.',       '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Sample Received', 'Specimen Management', NULL, NULL, 'Sample SAMP-2004 received for AKI STAT order ORD-2004.',              '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'QC Run',          'Quality Control',     NULL, NULL, 'Daily internal QC for CBC analyzer (Sysmex XN-550). Level 1 PASS.',   '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Reagent Update',  'Reagent Inventory',   NULL, '120', '96', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Order Accepted',  'Lab Orders',          NULL, 'Pending', 'Accepted',                                                        '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Equipment Update','Equipment Fleet',     NULL, 'Operational', 'Maintenance',                                                 '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Result Amended',  'Lab Results',         NULL, 'Hb: 8.0 g/dL', 'Hb: 8.2 g/dL — Transcription error corrected after double-check.', '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124'),
(2, 2, 'Session Login',   'Authentication',      NULL, NULL, 'Jefferson Forson logged in to Lab Dashboard. IP: 192.168.1.10.',       '192.168.1.10', 'Mozilla/5.0 Windows Chrome/124');

-- ══════════════════════════════════════════════════════════════
-- 10) LAB_WORKLOAD_LOG  — 10 daily shift logs for Jefferson
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_workload_log (technician_id, shift_date, shift_type, total_orders_received, total_completed, total_pending, total_rejected, total_critical_results, notes) VALUES
(2, '2026-04-01', 'Morning', 12, 11, 1, 0, 0, 'Routine day. CBC batch run completed. All QC within range.'),
(2, '2026-04-02', 'Morning', 8,  7,  0, 0, 1, 'Critical glucose result (18.4) reported to Dr. Joyce. Immediate action taken.'),
(2, '2026-04-03', 'Morning', 10, 9,  1, 0, 0, 'LFT batch completed. One sample pending repeat due to haemolysis.'),
(2, '2026-04-04', 'Afternoon',6, 6,  0, 0, 0, 'Low workload afternoon shift. Equipment maintenance checks performed.'),
(2, '2026-04-05', 'Morning', 15, 13, 1, 1, 1, 'STAT AKI creatinine critical – nephrology notified via Dr. Joyce. One rejected sample (haemolysed).'),
(2, '2026-04-07', 'Morning', 11, 10, 1, 0, 0, 'Lipid profile QC initially failed – recalibrated. All results released post-correction.'),
(2, '2026-04-08', 'Afternoon',9, 9,  0, 0, 1, 'Malaria thick film P. falciparum +++. Urgent result relayed.'),
(2, '2026-04-09', 'Morning', 13, 12, 1, 0, 0, 'UTI confirmed urinalysis. Blood culture set up. Culture pending 48h.'),
(2, '2026-04-12', 'Morning', 10, 8,  2, 0, 0, 'Widal test borderline. Two orders held for clinician clarification.'),
(2, '2026-04-14', 'Morning', 14, 11, 3, 0, 1, 'Sepsis protocol cultures ongoing. Monthly external QC submitted.');

-- ══════════════════════════════════════════════════════════════
-- 11) LAB_TECHNICIAN_ACTIVITY_LOG — 10 activity entries
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_technician_activity_log (technician_id, action_description, ip_address, device_info) VALUES
(2, 'Logged in to Lab Technician Dashboard.',                                  '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Accepted and processed STAT order LAB-ORD-2004 (RFT – AKI).',           '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Entered and validated CBC result for patient Lovelace Baidoo.',          '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Released critical glucose result to Dr. Joyce Eli for patient Adjoa Yeboah.',  '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Performed daily internal QC for Sysmex XN-550 – Level 1 and 2 PASS.',   '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Updated reagent stock: EDTA Vacutainers received (800 units from LabMed Ghana).', '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Logged calibration for Sysmex XN-550. Next due: 2026-09-01.',           '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Submitted malaria parasite thick film result for Kofi Adu (ORD-2006).', '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Set up aerobic blood culture bottles for sepsis patient Daniel Antwi.', '192.168.1.10', 'Chrome 124 / Windows 10'),
(2, 'Exported April 2026 lab results report as CSV from Reports module.',     '192.168.1.10', 'Chrome 124 / Windows 10');

-- ══════════════════════════════════════════════════════════════
-- 12) LAB_TECHNICIAN_SETTINGS — Jefferson's preferences
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_technician_settings
  (technician_id, theme_preference, language, alert_sound_enabled,
   notif_new_order, notif_critical_result, notif_equipment_alert, notif_reagent_alert,
   notif_qc_reminder, notif_doctor_msg, notif_system, notif_stat_order,
   notif_reagent_expiry, notif_result_amend, notif_license_expiry,
   notif_shift_reminder, preferred_channel)
VALUES
  (2, 'dark', 'en', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 'In-Dashboard');

-- ══════════════════════════════════════════════════════════════
-- 13) LAB_REFERENCE_RANGES — 10 parametric baselines
-- ══════════════════════════════════════════════════════════════
INSERT IGNORE INTO lab_reference_ranges (test_catalog_id, parameter_name, gender, age_min_years, age_max_years, normal_min, normal_max, critical_low, critical_high, unit, updated_by) VALUES
(1,  'Haemoglobin',            'Male',   18, 999, 13.5000, 17.5000,  7.0000, 20.0000, 'g/dL',     2),
(1,  'Haemoglobin',            'Female', 18, 999, 12.0000, 16.0000,  7.0000, 20.0000, 'g/dL',     2),
(1,  'White Blood Cells',      'Both',   18, 999,  4.5000, 11.0000,  2.0000, 30.0000, 'x10⁹/L',  2),
(1,  'Platelets',              'Both',   18, 999,150.0000,400.0000, 50.0000,1000.0000,'x10⁹/L',   2),
(2,  'Fasting Blood Glucose',  'Both',   18, 999,  3.9000,  6.1000,  2.5000, 25.0000, 'mmol/L',   2),
(4,  'ALT (SGPT)',             'Both',   18, 999,  7.0000, 56.0000,  0.0000,200.0000, 'U/L',      2),
(4,  'Total Bilirubin',        'Both',   18, 999,  3.4000, 17.1000,  0.0000, 60.0000, 'µmol/L',   2),
(5,  'Serum Creatinine',       'Male',   18, 999, 62.0000,115.0000, 20.0000,800.0000, 'µmol/L',   2),
(5,  'Serum Creatinine',       'Female', 18, 999, 44.0000, 97.0000, 20.0000,700.0000, 'µmol/L',   2),
(6,  'Total Cholesterol',      'Both',   18, 999,  0.0000,  5.2000,  0.0000, 15.0000, 'mmol/L',   2);

SET FOREIGN_KEY_CHECKS = 1;
