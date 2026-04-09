<?php
/**
 * Phase 2 Migration Runner — Finance & Revenue Tables
 * Run via: php run_finance_migration.php
 * 
 * Safe to re-run: uses IF NOT EXISTS and ON DUPLICATE KEY
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "╔═══════════════════════════════════════════════════════╗\n";
echo "║  PHASE 2: Finance & Revenue Database Migration       ║\n";
echo "║  RMU Medical Sickbay Management System               ║\n";
echo "╚═══════════════════════════════════════════════════════╝\n\n";

// ── Connect ──
$conn = new mysqli('localhost', 'root', 'Confrontation@433', 'rmu_medical_sickbay');
if ($conn->connect_error) {
    die("❌ DB Connection failed: " . $conn->connect_error . "\n");
}
$conn->set_charset('utf8mb4');
echo "✅ Connected to rmu_medical_sickbay\n\n";

// ── Read and execute SQL migration ──
$sqlFile = __DIR__ . '/Database/migrations/finance_phase2_migration.sql';
if (!file_exists($sqlFile)) {
    die("❌ Migration file not found: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);
echo "📄 Loaded migration file (" . strlen($sql) . " bytes)\n\n";

// Execute multi-query
$conn->multi_query($sql);

$stmtNum = 0;
$errors  = [];
$success = 0;

do {
    $stmtNum++;
    if ($result = $conn->store_result()) {
        $result->free();
    }
    
    if ($conn->errno) {
        $errors[] = "Statement #$stmtNum: [{$conn->errno}] {$conn->error}";
        echo "❌ Error at statement #$stmtNum: {$conn->error}\n";
    } else {
        $success++;
    }
} while ($conn->more_results() && $conn->next_result());

echo "\n══════════════════════════════════════════════════════\n";
echo "Statements executed: $stmtNum\n";
echo "Successful: $success\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n─── ERRORS ─────────────────────────────────────────\n";
    foreach ($errors as $e) echo "  ⚠ $e\n";
}

// ── Verify: List all finance tables ──
echo "\n─── VERIFICATION ───────────────────────────────────\n\n";

$expectedTables = [
    'paystack_config', 'revenue_categories', 'fee_schedule', 'finance_staff',
    'billing_invoices', 'invoice_line_items', 'payments', 'paystack_transactions',
    'insurance_claims', 'payment_waivers', 'refunds', 'daily_cash_reports',
    'budget_allocations', 'financial_reports', 'finance_notifications',
    'finance_audit_trail', 'finance_settings', 'legacy_payments'
];

echo "TABLE NAME                    ENGINE     COLLATION                  COLS\n";
echo str_repeat('─', 78) . "\n";

$allGood = true;
foreach ($expectedTables as $tbl) {
    // Check existence
    $exists = $conn->query("SHOW TABLES LIKE '$tbl'");
    if ($exists->num_rows === 0) {
        echo sprintf("%-30s ❌ NOT FOUND\n", $tbl);
        $allGood = false;
        continue;
    }

    // Engine + Collation
    $info = $conn->query("SELECT ENGINE, TABLE_COLLATION FROM information_schema.TABLES 
                          WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='$tbl'")->fetch_assoc();
    
    // Column count
    $cols = $conn->query("SELECT COUNT(*) AS c FROM information_schema.COLUMNS 
                          WHERE TABLE_SCHEMA='rmu_medical_sickbay' AND TABLE_NAME='$tbl'")->fetch_assoc();
    
    $eng = $info['ENGINE'] ?? '?';
    $col = $info['TABLE_COLLATION'] ?? '?';
    $cc  = $cols['c'] ?? '?';
    
    $engOk = ($eng === 'InnoDB') ? '✅' : '⚠';
    $colOk = ($col === 'utf8mb4_unicode_ci') ? '✅' : '⚠';
    
    echo sprintf("%-30s %s %-9s %s %-25s %s cols\n", $tbl, $engOk, $eng, $colOk, $col, $cc);
}

// ── Verify user_role ENUM ──
echo "\n─── users.user_role ENUM CHECK ──────────────────────\n";
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'user_role'");
if ($row = $result->fetch_assoc()) {
    echo "  Type: " . $row['Type'] . "\n";
    $hasFinanceOfficer = strpos($row['Type'], 'finance_officer') !== false;
    $hasFinanceManager = strpos($row['Type'], 'finance_manager') !== false;
    echo "  finance_officer: " . ($hasFinanceOfficer ? '✅ Present' : '❌ Missing') . "\n";
    echo "  finance_manager: " . ($hasFinanceManager ? '✅ Present' : '❌ Missing') . "\n";
}

// ── Verify self-referencing FK on revenue_categories ──
echo "\n─── revenue_categories self-referencing FK ──────────\n";
$fk = $conn->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                     FROM information_schema.KEY_COLUMN_USAGE
                     WHERE TABLE_SCHEMA='rmu_medical_sickbay' 
                       AND TABLE_NAME='revenue_categories' 
                       AND REFERENCED_TABLE_NAME='revenue_categories'");
if ($fk && $fk->num_rows > 0) {
    $r = $fk->fetch_assoc();
    echo "  ✅ FK: {$r['CONSTRAINT_NAME']} ({$r['COLUMN_NAME']} → {$r['REFERENCED_TABLE_NAME']}.{$r['REFERENCED_COLUMN_NAME']})\n";
} else {
    echo "  ❌ Self-referencing FK not found\n";
}

// ── Count seed data ──
echo "\n─── SEED DATA ──────────────────────────────────────\n";
$catCount = $conn->query("SELECT COUNT(*) AS c FROM revenue_categories")->fetch_assoc()['c'];
echo "  revenue_categories: $catCount rows\n";
$psCount = $conn->query("SELECT COUNT(*) AS c FROM paystack_config")->fetch_assoc()['c'];
echo "  paystack_config: $psCount rows\n";

echo "\n══════════════════════════════════════════════════════\n";
echo $allGood ? "✅ PHASE 2 MIGRATION COMPLETE!\n" : "⚠ Some issues detected — review above.\n";
echo "══════════════════════════════════════════════════════\n";

$conn->close();
