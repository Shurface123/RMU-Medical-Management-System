<?php
$sname    = "localhost";
$unmae    = "root";
$password = "Confrontation@433";
$db_name  = "rmu_medical_sickbay";
$conn = mysqli_connect($sname, $unmae, $password, $db_name);
if (!$conn) { die("DB Error: " . mysqli_connect_error()); }

$output = "# RMU Medical Management System\n\n## 1. Directory & File Map\n\n```text\n";

$dir = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('c:/wamp64/www/RMU-Medical-Management-System', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD
);

foreach ($dir as $file) {
    if (strpos($file->getPathname(), '.git') !== false || strpos($file->getPathname(), 'node_modules') !== false) {
        continue;
    }
    $indent = str_repeat('  ', $dir->getDepth());
    $output .= $indent . ($file->isDir() ? "📁 " : "📄 ") . $file->getFilename() . "\n";
}

$output .= "```\n\n## 2. Database Schema (`$db_name`)\n\n";

$result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    $output .= "### Table: `$table`\n";
    $output .= "```sql\n";
    $create = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
    if ($createRow = mysqli_fetch_assoc($create)) {
        $output .= $createRow['Create Table'] . ";\n";
    }
    $output .= "```\n\n";
}

file_put_contents('C:\Users\Test\.gemini\antigravity\brain\69aed29e-12e4-4b37-bbc2-ec9f7d51f5a1\system_architecture_raw.md', $output);
echo "SUCCESS";
?>
