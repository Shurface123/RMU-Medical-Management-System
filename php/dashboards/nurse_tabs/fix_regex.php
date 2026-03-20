<?php
$dir = __DIR__;
$files = glob($dir . '/*.php');

foreach($files as $file) {
    if(basename($file) == 'refactor.php' || basename($file) == 'fix_regex.php') continue;
    $content = file_get_contents($file);
    
    // Clean up recursive adm- prefixes globally
    $content = preg_replace('/(adm-)+/', 'adm-', $content);
    
    // Clean up stat cards mapping collision
    $content = str_replace('stat-adm-card', 'adm-stat-card', $content);
    
    file_put_contents($file, $content);
    echo "Fixed " . basename($file) . "<br>";
}
echo "Tabs regex fix script completed successfully.<br>";
