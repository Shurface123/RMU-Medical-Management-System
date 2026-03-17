<?php
$files = [
    'php/patient/patient.php',
    'php/medicine/medicine.php',
    'php/staff/staff.php',
    'php/test/test.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) {
        echo "File not found: $f\n";
        continue;
    }
    $c = file_get_contents($f);
    $c = str_replace('<div class="adm-table-wrap">', '<div class="adm-table-wrap table-container">', $c);
    $c = str_replace('<table class="adm-table">', '<table class="adm-table" style="width: 100%; min-width: 900px;">', $c);
    file_put_contents($f, $c);
    echo "Updated $f\n";
}
?>
