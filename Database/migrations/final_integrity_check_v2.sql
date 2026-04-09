SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'rmu_medical_sickbay'
AND table_name IN (
    'finance_staff', 'billing_invoices', 'payments', 
    'insurance_claims', 'payment_waivers', 'refunds', 
    'paystack_transactions', 'invoice_line_items', 
    'fee_schedule', 'fee_categories', 'patients', 'users'
);
