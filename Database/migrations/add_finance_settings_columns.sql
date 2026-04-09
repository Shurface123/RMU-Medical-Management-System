-- Migration: Sync Finance Settings Table Structure
-- Phase 10 Hotfix: Align DB with UI requirements

USE rmu_medical_sickbay;

-- Adding missing columns to finance_settings (Standard syntax)
ALTER TABLE finance_settings
    ADD COLUMN invoice_prefix VARCHAR(50) DEFAULT 'RMU-INV' AFTER auto_receipt_enabled,
    ADD COLUMN default_due_days INT UNSIGNED DEFAULT 30 AFTER invoice_prefix,
    ADD COLUMN default_tax_rate DECIMAL(5,2) DEFAULT 0.00 AFTER default_due_days,
    ADD COLUMN currency VARCHAR(10) DEFAULT 'GHS' AFTER default_tax_rate,
    ADD COLUMN waiver_approval_threshold DECIMAL(15,2) DEFAULT 500.00 AFTER currency,
    ADD COLUMN refund_approval_threshold DECIMAL(15,2) DEFAULT 200.00 AFTER waiver_approval_threshold,
    ADD COLUMN max_refund_pct INT UNSIGNED DEFAULT 100 AFTER refund_approval_threshold,
    ADD COLUMN overdue_alert_days INT UNSIGNED DEFAULT 7 AFTER max_refund_pct,
    ADD COLUMN budget_alert_pct INT UNSIGNED DEFAULT 80 AFTER overdue_alert_days;

-- Ensure existence of a settings row for all existing finance staff
INSERT IGNORE INTO finance_settings (finance_staff_id)
SELECT finance_staff_id FROM finance_staff;
