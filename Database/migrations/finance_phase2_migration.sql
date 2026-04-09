-- ============================================================
-- PHASE 2 MIGRATION: Finance & Revenue Department Tables
-- RMU Medical Sickbay Management System
-- Generated: 2026-04-08
-- ============================================================
-- CONVENTIONS:
--   Engine:    InnoDB (all tables)
--   Collation: utf8mb4_unicode_ci (all tables)
--   Money:     DECIMAL(15,2) stored in GHS — convert to pesewas only at API call time
--   PKs:       {singular_table_name}_id
--   Soft delete: is_active / status ENUM — never hard DELETE financial records
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 0. RENAME LEGACY PAYMENTS TABLE ─────────────────────────────────────
-- The existing `payments` table is a basic prototype. Rename to preserve data.
RENAME TABLE payments TO legacy_payments;

-- ─── 1. paystack_config (no FKs) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS paystack_config (
    config_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    config_key       VARCHAR(100)  NOT NULL COMMENT 'e.g. public_key, secret_key, webhook_secret',
    config_value     VARBINARY(512) NOT NULL COMMENT 'AES-256 encrypted value',
    environment      ENUM('test','live') NOT NULL DEFAULT 'test',
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    description      VARCHAR(500)  DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_paystack_config_key (config_key, environment)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Paystack API credentials — values AES-256 encrypted';


-- ─── 2. revenue_categories (self-referencing parent — FK added after) ──────
CREATE TABLE IF NOT EXISTS revenue_categories (
    category_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_name    VARCHAR(200)  NOT NULL,
    category_code    VARCHAR(50)   NOT NULL,
    parent_category_id INT UNSIGNED DEFAULT NULL COMMENT 'Self-ref FK added via ALTER',
    description      TEXT          DEFAULT NULL,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    sort_order       INT UNSIGNED  NOT NULL DEFAULT 0,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_revenue_cat_code (category_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hierarchical revenue classification for all billable services';


-- ─── 3. fee_schedule (FK: created_by, updated_by → users) ─────────────────
CREATE TABLE IF NOT EXISTS fee_schedule (
    fee_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name     VARCHAR(300)  NOT NULL,
    service_code     VARCHAR(50)   NOT NULL,
    category_id      INT UNSIGNED  DEFAULT NULL,
    base_amount      DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'GHS — never pesewas',
    student_amount   DECIMAL(15,2) DEFAULT NULL COMMENT 'Discounted rate for students',
    insurance_amount DECIMAL(15,2) DEFAULT NULL COMMENT 'Rate for insured patients',
    tax_rate_pct     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    is_taxable       TINYINT(1)    NOT NULL DEFAULT 0,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    effective_from   DATE          NOT NULL,
    effective_to     DATE          DEFAULT NULL,
    created_by       INT           DEFAULT NULL,
    updated_by       INT           DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fee_service_code (service_code),
    KEY idx_fee_category (category_id),
    KEY idx_fee_active (is_active, effective_from),
    CONSTRAINT fk_fee_category      FOREIGN KEY (category_id) REFERENCES revenue_categories(category_id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_fee_created_by    FOREIGN KEY (created_by)  REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_fee_updated_by    FOREIGN KEY (updated_by)  REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Master fee schedule — all billable service prices';


-- ─── 4. finance_staff (FK: user_id → users) ──────────────────────────────
CREATE TABLE IF NOT EXISTS finance_staff (
    finance_staff_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL,
    staff_code       VARCHAR(50)   NOT NULL,
    role_level       ENUM('finance_officer','finance_manager','cashier','accountant') NOT NULL DEFAULT 'finance_officer',
    department       VARCHAR(200)  DEFAULT 'Finance & Revenue',
    can_process_refunds   TINYINT(1) NOT NULL DEFAULT 0,
    can_approve_waivers   TINYINT(1) NOT NULL DEFAULT 0,
    can_generate_reports  TINYINT(1) NOT NULL DEFAULT 1,
    can_manage_budgets    TINYINT(1) NOT NULL DEFAULT 0,
    max_refund_amount     DECIMAL(15,2) DEFAULT NULL COMMENT 'NULL = no limit (manager only)',
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    hired_at         DATE          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finance_staff_user   (user_id),
    UNIQUE KEY uq_finance_staff_code   (staff_code),
    CONSTRAINT fk_finance_staff_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Finance department staff profiles and permissions';


-- ─── 5. billing_invoices (FK: patient_id → patients, generated_by → users) ─
CREATE TABLE IF NOT EXISTS billing_invoices (
    invoice_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number   VARCHAR(50)   NOT NULL COMMENT 'Format: RMU-INV-YYYYMMDD-NNNN',
    patient_id       INT           NOT NULL,
    generated_by     INT           DEFAULT NULL,
    invoice_date     DATE          NOT NULL,
    due_date         DATE          DEFAULT NULL,
    subtotal         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_amount  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_amount     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    paid_amount      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    balance_due      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency         VARCHAR(10)   NOT NULL DEFAULT 'GHS',
    status           ENUM('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Void','Written Off') NOT NULL DEFAULT 'Draft',
    payment_terms    VARCHAR(200)  DEFAULT NULL,
    notes            TEXT          DEFAULT NULL,
    is_student_invoice TINYINT(1)  NOT NULL DEFAULT 0,
    insurance_claim_id INT UNSIGNED DEFAULT NULL COMMENT 'Linked insurance claim, if any',
    voided_reason    TEXT          DEFAULT NULL,
    voided_by        INT           DEFAULT NULL,
    voided_at        DATETIME      DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_invoice_number (invoice_number),
    KEY idx_invoice_patient (patient_id),
    KEY idx_invoice_status  (status),
    KEY idx_invoice_date    (invoice_date),
    CONSTRAINT fk_invoice_patient     FOREIGN KEY (patient_id)   REFERENCES patients(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_invoice_generated   FOREIGN KEY (generated_by) REFERENCES users(id)    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Master billing invoices for all patient services';


-- ─── 6. invoice_line_items (FK: invoice_id → billing_invoices) ────────────
CREATE TABLE IF NOT EXISTS invoice_line_items (
    line_item_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id       INT UNSIGNED  NOT NULL,
    fee_id           INT UNSIGNED  DEFAULT NULL COMMENT 'FK to fee_schedule if from catalog',
    service_description VARCHAR(500) NOT NULL,
    service_code     VARCHAR(50)   DEFAULT NULL,
    category_id      INT UNSIGNED  DEFAULT NULL,
    quantity         INT           NOT NULL DEFAULT 1,
    unit_price       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discount_pct     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    discount_amount  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tax_amount       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    line_total       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    reference_type   VARCHAR(50)   DEFAULT NULL COMMENT 'e.g. appointment, prescription, lab_test, bed_charge',
    reference_id     INT           DEFAULT NULL COMMENT 'PK of the source record',
    notes            TEXT          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lineitem_invoice (invoice_id),
    KEY idx_lineitem_fee     (fee_id),
    CONSTRAINT fk_lineitem_invoice FOREIGN KEY (invoice_id) REFERENCES billing_invoices(invoice_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lineitem_fee     FOREIGN KEY (fee_id)     REFERENCES fee_schedule(fee_id)         ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_lineitem_cat     FOREIGN KEY (category_id) REFERENCES revenue_categories(category_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Individual line items on an invoice';


-- ─── 7. payments (new finance-grade table) ────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    payment_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(60)  NOT NULL COMMENT 'RMU-[YmdHis]-[6-char random]',
    invoice_id       INT UNSIGNED  NOT NULL,
    patient_id       INT           NOT NULL,
    amount           DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'GHS',
    currency         VARCHAR(10)   NOT NULL DEFAULT 'GHS',
    payment_method   ENUM('Cash','Mobile Money','Card','Bank Transfer','Insurance','Paystack','Cheque','Other') NOT NULL,
    payment_date     DATETIME      NOT NULL,
    status           ENUM('Pending','Completed','Failed','Refunded','Partially Refunded','Cancelled') NOT NULL DEFAULT 'Pending',
    paystack_reference VARCHAR(100) DEFAULT NULL COMMENT 'Paystack txn reference if online',
    paystack_response  JSON        DEFAULT NULL COMMENT 'Raw Paystack verification JSON',
    receipt_number   VARCHAR(60)   DEFAULT NULL COMMENT 'RMU-RCT-YYYYMMDD-NNNN',
    receipt_path     VARCHAR(500)  DEFAULT NULL COMMENT 'PDF path under /uploads/receipts/',
    processed_by     INT           DEFAULT NULL COMMENT 'Finance staff user_id (NULL for online)',
    channel          ENUM('Online','Counter','Mobile','Auto') NOT NULL DEFAULT 'Counter',
    notes            TEXT          DEFAULT NULL,
    reconciled       TINYINT(1)    NOT NULL DEFAULT 0,
    reconciled_at    DATETIME      DEFAULT NULL,
    reconciled_by    INT           DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_reference   (payment_reference),
    UNIQUE KEY uq_receipt_number      (receipt_number),
    KEY idx_payment_invoice  (invoice_id),
    KEY idx_payment_patient  (patient_id),
    KEY idx_payment_status   (status),
    KEY idx_payment_date     (payment_date),
    KEY idx_payment_paystack (paystack_reference),
    CONSTRAINT fk_payment_invoice   FOREIGN KEY (invoice_id)   REFERENCES billing_invoices(invoice_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payment_patient   FOREIGN KEY (patient_id)   REFERENCES patients(id)                ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payment_processor FOREIGN KEY (processed_by) REFERENCES users(id)                   ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='All payment transactions — online (Paystack) and manual';


-- ─── 8. paystack_transactions (FK: payment_id → payments) ────────────────
CREATE TABLE IF NOT EXISTS paystack_transactions (
    transaction_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id           INT UNSIGNED  DEFAULT NULL,
    paystack_reference   VARCHAR(100)  NOT NULL COMMENT 'Reference sent to Paystack',
    paystack_access_code VARCHAR(100)  DEFAULT NULL,
    paystack_txn_id      BIGINT        DEFAULT NULL COMMENT 'Paystack internal transaction ID',
    amount_pesewas       BIGINT        NOT NULL DEFAULT 0 COMMENT 'Amount in pesewas sent to API',
    amount_ghs           DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Equivalent GHS for DB records',
    currency             VARCHAR(10)   NOT NULL DEFAULT 'GHS',
    email                VARCHAR(200)  DEFAULT NULL,
    channel              VARCHAR(50)   DEFAULT NULL COMMENT 'card, bank, mobile_money, ussd, etc.',
    gateway_response     VARCHAR(500)  DEFAULT NULL,
    ip_address           VARCHAR(45)   DEFAULT NULL,
    status               ENUM('Initialized','Pending','Success','Failed','Abandoned','Reversed') NOT NULL DEFAULT 'Initialized',
    event_type           VARCHAR(100)  DEFAULT NULL COMMENT 'Webhook event: charge.success, etc.',
    paystack_raw_response JSON         DEFAULT NULL COMMENT 'Full raw Paystack JSON payload',
    webhook_received_at  DATETIME      DEFAULT NULL,
    webhook_ip           VARCHAR(45)   DEFAULT NULL,
    webhook_signature_valid TINYINT(1) DEFAULT NULL,
    metadata             JSON          DEFAULT NULL COMMENT 'Custom metadata sent to Paystack',
    paid_at              DATETIME      DEFAULT NULL,
    verified_at          DATETIME      DEFAULT NULL,
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_paystack_ref (paystack_reference),
    KEY idx_paystack_payment (payment_id),
    KEY idx_paystack_status  (status),
    KEY idx_paystack_txnid   (paystack_txn_id),
    CONSTRAINT fk_paystack_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Paystack API transaction log — every API call and webhook event';


-- ─── 9. insurance_claims (FK: invoice_id → billing_invoices, patient_id → patients, claims_officer → users) ─
CREATE TABLE IF NOT EXISTS insurance_claims (
    claim_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    claim_number     VARCHAR(50)   NOT NULL COMMENT 'RMU-CLM-YYYYMMDD-NNNN',
    invoice_id       INT UNSIGNED  NOT NULL,
    patient_id       INT           NOT NULL,
    insurance_provider VARCHAR(200) NOT NULL,
    policy_number    VARCHAR(100)  NOT NULL,
    claim_amount     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    approved_amount  DECIMAL(15,2) DEFAULT NULL,
    patient_copay    DECIMAL(15,2) DEFAULT NULL,
    status           ENUM('Draft','Submitted','Under Review','Approved','Partially Approved','Rejected','Paid','Appealed') NOT NULL DEFAULT 'Draft',
    submission_date  DATE          DEFAULT NULL,
    response_date    DATE          DEFAULT NULL,
    rejection_reason TEXT          DEFAULT NULL,
    supporting_docs  JSON          DEFAULT NULL COMMENT 'Array of document file paths',
    claims_officer   INT           DEFAULT NULL,
    insurer_reference VARCHAR(100) DEFAULT NULL,
    notes            TEXT          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_claim_number (claim_number),
    KEY idx_claim_invoice  (invoice_id),
    KEY idx_claim_patient  (patient_id),
    KEY idx_claim_status   (status),
    CONSTRAINT fk_claim_invoice  FOREIGN KEY (invoice_id) REFERENCES billing_invoices(invoice_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_claim_patient  FOREIGN KEY (patient_id) REFERENCES patients(id)                ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_claim_officer  FOREIGN KEY (claims_officer) REFERENCES users(id)               ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Insurance claim submissions and tracking';


-- ─── 10. payment_waivers (FK: invoice_id, patient_id, approved_by, created_by) ─
CREATE TABLE IF NOT EXISTS payment_waivers (
    waiver_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    waiver_number    VARCHAR(50)   NOT NULL COMMENT 'RMU-WVR-YYYYMMDD-NNNN',
    invoice_id       INT UNSIGNED  NOT NULL,
    patient_id       INT           NOT NULL,
    waiver_type      ENUM('Full','Partial','Student Discount','Staff Discount','Indigent','Hardship','Other') NOT NULL,
    original_amount  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    waived_amount    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    reason           TEXT          NOT NULL,
    supporting_docs  JSON          DEFAULT NULL,
    status           ENUM('Pending','Approved','Rejected','Revoked') NOT NULL DEFAULT 'Pending',
    approved_by      INT           DEFAULT NULL,
    approved_at      DATETIME      DEFAULT NULL,
    rejection_reason TEXT          DEFAULT NULL,
    created_by       INT           NOT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_waiver_number (waiver_number),
    KEY idx_waiver_invoice (invoice_id),
    KEY idx_waiver_patient (patient_id),
    KEY idx_waiver_status  (status),
    CONSTRAINT fk_waiver_invoice  FOREIGN KEY (invoice_id)  REFERENCES billing_invoices(invoice_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_waiver_patient  FOREIGN KEY (patient_id)  REFERENCES patients(id)                ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_waiver_approved FOREIGN KEY (approved_by) REFERENCES users(id)                   ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_waiver_created  FOREIGN KEY (created_by)  REFERENCES users(id)                   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Payment waiver requests and approvals';


-- ─── 11. refunds (FK: payment_id, invoice_id, patient_id, processed_by, approved_by) ─
CREATE TABLE IF NOT EXISTS refunds (
    refund_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    refund_reference VARCHAR(60)   NOT NULL COMMENT 'RMU-RFD-YYYYMMDD-NNNN',
    payment_id       INT UNSIGNED  NOT NULL,
    invoice_id       INT UNSIGNED  NOT NULL,
    patient_id       INT           NOT NULL,
    refund_amount    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency         VARCHAR(10)   NOT NULL DEFAULT 'GHS',
    reason           TEXT          NOT NULL,
    refund_method    ENUM('Cash','Mobile Money','Card Reversal','Bank Transfer','Paystack Refund','Other') NOT NULL,
    status           ENUM('Pending Approval','Approved','Processing','Completed','Rejected','Failed') NOT NULL DEFAULT 'Pending Approval',
    paystack_refund_reference VARCHAR(100) DEFAULT NULL COMMENT 'Paystack refund reference if online',
    paystack_refund_response  JSON         DEFAULT NULL,
    requires_approval TINYINT(1)   NOT NULL DEFAULT 1,
    approval_threshold DECIMAL(15,2) DEFAULT NULL COMMENT 'Amount above which manager approval needed',
    processed_by     INT           DEFAULT NULL,
    approved_by      INT           DEFAULT NULL,
    approved_at      DATETIME      DEFAULT NULL,
    rejection_reason TEXT          DEFAULT NULL,
    completed_at     DATETIME      DEFAULT NULL,
    notes            TEXT          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_refund_reference (refund_reference),
    KEY idx_refund_payment (payment_id),
    KEY idx_refund_invoice (invoice_id),
    KEY idx_refund_patient (patient_id),
    KEY idx_refund_status  (status),
    CONSTRAINT fk_refund_payment   FOREIGN KEY (payment_id)   REFERENCES payments(payment_id)          ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_refund_invoice   FOREIGN KEY (invoice_id)   REFERENCES billing_invoices(invoice_id)  ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_refund_patient   FOREIGN KEY (patient_id)   REFERENCES patients(id)                  ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_refund_processor FOREIGN KEY (processed_by) REFERENCES users(id)                     ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_refund_approver  FOREIGN KEY (approved_by)  REFERENCES users(id)                     ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Refund requests, approvals, and Paystack refund tracking';


-- ─── 12. daily_cash_reports (FK: generated_by, reconciled_by → users) ─────
CREATE TABLE IF NOT EXISTS daily_cash_reports (
    report_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date      DATE          NOT NULL,
    opening_balance  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_cash_received     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_mobile_money      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_card_payments     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_bank_transfers    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_paystack_payments DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_insurance_claims  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_refunds_issued    DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_waivers           DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    closing_balance  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discrepancy      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    discrepancy_notes TEXT         DEFAULT NULL,
    status           ENUM('Open','Submitted','Reconciled','Flagged') NOT NULL DEFAULT 'Open',
    generated_by     INT           DEFAULT NULL,
    reconciled_by    INT           DEFAULT NULL,
    reconciled_at    DATETIME      DEFAULT NULL,
    notes            TEXT          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_daily_report_date (report_date),
    KEY idx_daily_status (status),
    CONSTRAINT fk_daily_generated  FOREIGN KEY (generated_by)  REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_daily_reconciled FOREIGN KEY (reconciled_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='End-of-day cash reconciliation reports';


-- ─── 13. budget_allocations (FK: category_id → revenue_categories, created_by, approved_by → users) ─
CREATE TABLE IF NOT EXISTS budget_allocations (
    allocation_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fiscal_year      VARCHAR(20)   NOT NULL COMMENT 'e.g. 2026, 2026-2027',
    fiscal_period    ENUM('Annual','Q1','Q2','Q3','Q4','Monthly') NOT NULL DEFAULT 'Annual',
    category_id      INT UNSIGNED  NOT NULL,
    department       VARCHAR(200)  DEFAULT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    spent_amount     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency         VARCHAR(10)   NOT NULL DEFAULT 'GHS',
    status           ENUM('Draft','Active','Exhausted','Closed','Revised') NOT NULL DEFAULT 'Draft',
    notes            TEXT          DEFAULT NULL,
    created_by       INT           DEFAULT NULL,
    approved_by      INT           DEFAULT NULL,
    approved_at      DATETIME      DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_budget_year     (fiscal_year),
    KEY idx_budget_category (category_id),
    KEY idx_budget_status   (status),
    CONSTRAINT fk_budget_category FOREIGN KEY (category_id) REFERENCES revenue_categories(category_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_budget_creator  FOREIGN KEY (created_by)  REFERENCES users(id)                      ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_budget_approver FOREIGN KEY (approved_by) REFERENCES users(id)                      ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Departmental and categorical budget allocations';


-- ─── 14. financial_reports (FK: generated_by → users) ────────────────────
CREATE TABLE IF NOT EXISTS financial_reports (
    report_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type      ENUM('Daily Summary','Weekly Summary','Monthly Summary','Quarterly Report','Annual Report','Revenue Breakdown','Outstanding Invoices','Payment Reconciliation','Insurance Claims','Custom') NOT NULL,
    title            VARCHAR(300)  NOT NULL,
    period_start     DATE          NOT NULL,
    period_end       DATE          NOT NULL,
    parameters       JSON          DEFAULT NULL COMMENT 'Filter parameters used to generate',
    summary_data     JSON          DEFAULT NULL COMMENT 'Aggregated summary metrics',
    file_path        VARCHAR(500)  DEFAULT NULL COMMENT 'Path to generated PDF/XLSX',
    file_format      ENUM('PDF','CSV','XLSX') DEFAULT NULL,
    generated_by     INT           DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_finreport_type (report_type),
    KEY idx_finreport_period (period_start, period_end),
    CONSTRAINT fk_finreport_generator FOREIGN KEY (generated_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Generated financial report records and file references';


-- ─── 15. finance_notifications (FK: recipient_id → users) ────────────────
CREATE TABLE IF NOT EXISTS finance_notifications (
    notification_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id     INT           NOT NULL,
    sender_id        INT           DEFAULT NULL,
    type             ENUM('Payment Received','Invoice Generated','Invoice Overdue','Refund Processed','Refund Request','Waiver Request','Budget Alert','Insurance Update','Reconciliation','System','Approval Required') NOT NULL,
    title            VARCHAR(300)  NOT NULL,
    message          TEXT          NOT NULL,
    priority         ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    is_read          TINYINT(1)    NOT NULL DEFAULT 0,
    read_at          DATETIME      DEFAULT NULL,
    action_url       VARCHAR(500)  DEFAULT NULL,
    related_module   VARCHAR(100)  DEFAULT NULL COMMENT 'e.g. invoices, payments, refunds',
    related_record_id INT UNSIGNED DEFAULT NULL,
    metadata         JSON          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_finnotif_recipient (recipient_id),
    KEY idx_finnotif_read     (is_read),
    KEY idx_finnotif_type     (type),
    CONSTRAINT fk_finnotif_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_finnotif_sender    FOREIGN KEY (sender_id)    REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Finance-specific notification system';


-- ─── 16. finance_audit_trail (FK: user_id → users) — INSERT ONLY ─────────
CREATE TABLE IF NOT EXISTS finance_audit_trail (
    audit_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT           NOT NULL,
    action           VARCHAR(200)  NOT NULL COMMENT 'e.g. payment.created, refund.approved, invoice.voided',
    module           VARCHAR(100)  NOT NULL COMMENT 'e.g. payments, invoices, refunds, waivers, budgets',
    record_id        INT UNSIGNED  DEFAULT NULL,
    old_values       JSON          DEFAULT NULL COMMENT 'Previous state snapshot',
    new_values       JSON          DEFAULT NULL COMMENT 'New state snapshot',
    description      TEXT          DEFAULT NULL,
    ip_address       VARCHAR(45)   DEFAULT NULL,
    user_agent       TEXT          DEFAULT NULL,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_finaudit_user   (user_id),
    KEY idx_finaudit_action (action),
    KEY idx_finaudit_module (module),
    KEY idx_finaudit_date   (created_at),
    CONSTRAINT fk_finaudit_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Immutable financial audit trail — INSERT ONLY, no UPDATE/DELETE allowed';


-- ─── 17. finance_settings (FK: finance_staff_id → finance_staff) ─────────
CREATE TABLE IF NOT EXISTS finance_settings (
    setting_id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    finance_staff_id     INT UNSIGNED  NOT NULL,
    notif_new_payment    TINYINT(1) NOT NULL DEFAULT 1,
    notif_invoice_overdue TINYINT(1) NOT NULL DEFAULT 1,
    notif_refund_request TINYINT(1) NOT NULL DEFAULT 1,
    notif_waiver_request TINYINT(1) NOT NULL DEFAULT 1,
    notif_budget_alert   TINYINT(1) NOT NULL DEFAULT 1,
    notif_insurance_update TINYINT(1) NOT NULL DEFAULT 1,
    notif_reconciliation TINYINT(1) NOT NULL DEFAULT 1,
    notif_system_alerts  TINYINT(1) NOT NULL DEFAULT 1,
    preferred_channel    ENUM('dashboard','email','sms','all') NOT NULL DEFAULT 'dashboard',
    theme_preference     VARCHAR(20)  DEFAULT 'light',
    language             VARCHAR(30)  DEFAULT 'en',
    dashboard_preferences JSON       DEFAULT NULL COMMENT 'Widget visibility, layout prefs',
    default_report_format ENUM('PDF','CSV','XLSX') DEFAULT 'PDF',
    auto_receipt_enabled TINYINT(1)  NOT NULL DEFAULT 1,
    created_at           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_finsettings_staff (finance_staff_id),
    CONSTRAINT fk_finsettings_staff FOREIGN KEY (finance_staff_id) REFERENCES finance_staff(finance_staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-staff finance dashboard settings and notification preferences';


-- ═══════════════════════════════════════════════════════════════════════════
-- POST-CREATION ALTERs
-- ═══════════════════════════════════════════════════════════════════════════

-- ─── A. Self-referencing FK on revenue_categories ─────────────────────────
ALTER TABLE revenue_categories
  ADD CONSTRAINT fk_revenue_parent
    FOREIGN KEY (parent_category_id) REFERENCES revenue_categories(category_id)
    ON UPDATE CASCADE ON DELETE SET NULL;

-- ─── B. Update users.user_role ENUM to add finance roles ─────────────────
ALTER TABLE users
  MODIFY COLUMN user_role ENUM(
    'admin','doctor','patient','staff','pharmacist',
    'nurse','lab_technician','finance_officer','finance_manager'
  ) NOT NULL;

-- ─── C. Update notifications.user_role to include finance roles ──────────
ALTER TABLE notifications
  MODIFY COLUMN user_role ENUM(
    'admin','doctor','patient','staff','pharmacist',
    'nurse','finance_officer','finance_manager'
  ) DEFAULT NULL;

-- ─── D. Update user_sessions.user_role to include finance roles ──────────
ALTER TABLE user_sessions
  MODIFY COLUMN user_role ENUM(
    'admin','doctor','patient','staff','pharmacist',
    'nurse','finance_officer','finance_manager'
  ) DEFAULT NULL;

-- ─── E. Update permission_matrix.role to include finance roles ───────────
ALTER TABLE permission_matrix
  MODIFY COLUMN role ENUM(
    'admin','doctor','patient','staff','pharmacist',
    'nurse','lab_technician','finance_officer','finance_manager'
  ) DEFAULT NULL;


SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED: Default Revenue Categories
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO revenue_categories (category_name, category_code, description, sort_order) VALUES
('Consultation Fees',     'CONSULT',    'Doctor consultation and examination fees', 1),
('Laboratory Services',   'LAB',        'Lab test and diagnostic fees',             2),
('Pharmacy Sales',         'PHARMACY',   'Medication and pharmaceutical sales',      3),
('Bed & Ward Charges',     'BED',        'Inpatient bed and ward accommodation',     4),
('Procedure Fees',         'PROCEDURE',  'Medical procedures and surgeries',         5),
('Emergency Services',     'EMERGENCY',  'Emergency room and urgent care fees',      6),
('Ambulance Services',     'AMBULANCE',  'Ambulance transport charges',              7),
('Administrative Fees',    'ADMIN_FEE',  'Registration and administrative charges',  8),
('Insurance Reimbursements','INSURANCE', 'Payments received from insurance claims',  9),
('Miscellaneous',          'MISC',       'Other uncategorized revenue',             10)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- ═══════════════════════════════════════════════════════════════════════════
-- SEED: Default Paystack Config (placeholder — encrypt before production)
-- ═══════════════════════════════════════════════════════════════════════════
INSERT INTO paystack_config (config_key, config_value, environment, description) VALUES
('public_key',     AES_ENCRYPT('pk_test_xxxxxxxxxxxxxxxx', 'RMU_SICKBAY_AES_KEY_2026'), 'test', 'Paystack public/publishable key'),
('secret_key',     AES_ENCRYPT('sk_test_xxxxxxxxxxxxxxxx', 'RMU_SICKBAY_AES_KEY_2026'), 'test', 'Paystack secret key'),
('webhook_secret', AES_ENCRYPT('whsec_xxxxxxxxxxxxxxxx',   'RMU_SICKBAY_AES_KEY_2026'), 'test', 'Paystack webhook signing secret'),
('callback_url',   AES_ENCRYPT('/RMU-Medical-Management-System/php/payment/paystack_callback.php', 'RMU_SICKBAY_AES_KEY_2026'), 'test', 'Payment callback URL')
ON DUPLICATE KEY UPDATE config_value = VALUES(config_value);


-- ═══════════════════════════════════════════════════════════════════════════
-- END OF PHASE 2 MIGRATION
-- ═══════════════════════════════════════════════════════════════════════════
