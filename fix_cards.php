<?php
$files = glob('php/*/*.php');
if ($files) {
    foreach ($files as $f) {
        if (strpos($f, 'add-') !== false || strpos($f, 'update') !== false) {
            $c = file_get_contents($f);
            $c = str_replace('<div class="card">', '<div class="card" style="width: 100%; max-width: 100%; box-sizing: border-box;">', $c);
            $c = str_replace('<div class="adm-card"', '<div class="adm-card" style="width: 100%; max-width: 100%; box-sizing: border-box;"', $c);
            file_put_contents($f, $c);
            echo "Patched card widths in: $f\n";
        }
    }
}
?>
