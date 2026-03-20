<?php
$dir = __DIR__;
$files = glob($dir . '/*.php');

foreach($files as $file) {
    if(basename($file) == 'refactor.php') continue;
    $content = file_get_contents($file);
    
    // Replace Bootstrap Card with Admin Card
    $content = preg_replace('/\bclass="([^"]*)\bcard\b([^"]*)"/', 'class="$1adm-card$2"', $content);
    $content = preg_replace('/\bclass="([^"]*)\bcard-header\b([^"]*)"/', 'class="$1adm-card-header$2"', $content);
    $content = preg_replace('/\bclass="([^"]*)\bcard-body\b([^"]*)"/', 'class="$1adm-card-body$2"', $content);
    $content = preg_replace('/\bclass="([^"]*)\bcard-footer\b([^"]*)"/', 'class="$1adm-card-footer$2"', $content);
    
    // Replace tables
    $content = preg_replace('/\bclass="([^"]*)\btable\b([^"]*)"/', 'class="$1adm-table$2"', $content);
    $content = str_replace('adm-table-hover', '', $content); // Admin table handles hover inherently
    $content = str_replace('adm-table-sm', '', $content);
    $content = str_replace('table-responsive', 'adm-table-wrap', $content);
    
    // Buttons (Excluding btn-close)
    $content = preg_replace('/\bclass="([^"]*)\bbtn\b(?!\-group|\-close)([^"]*)"/', 'class="$1adm-btn$2"', $content);
    $content = preg_replace('/\bbtn-(primary|danger|warning|success|info|secondary)\b/', 'adm-btn-$1', $content);
    $content = str_replace('adm-btn-outline-primary', 'adm-btn-primary', $content);
    $content = str_replace('adm-btn-light', 'adm-btn', $content);
    $content = str_replace('adm-btn-secondary', 'adm-btn', $content);
    $content = str_replace('btn-sm', 'adm-btn-sm', $content);
    
    // Badges
    $content = preg_replace('/\bclass="([^"]*)\bbadge\b([^"]*)"/', 'class="$1adm-badge$2"', $content);
    $content = preg_replace('/\bbg-(success|danger|warning|info|primary)\b/', 'adm-badge-$1', $content);
    $content = str_replace('bg-secondary', 'adm-badge-info', $content); // Map secondary to info
    
    // Forms
    $content = preg_replace('/\bclass="([^"]*)\bform-control\b([^"]*)"/', 'class="$1adm-input$2"', $content);
    $content = preg_replace('/\bclass="([^"]*)\bform-select\b([^"]*)"/', 'class="$1adm-input$2"', $content);
    $content = preg_replace('/\bclass="([^"]*)\bform-label\b([^"]*)"/', 'class="$1adm-form-label$2"', $content);
    
    // Remove Bootstrap inline structural overrides that conflict with adm-
    $content = preg_replace('/border-0\s*/', '', $content);
    $content = preg_replace('/shadow-sm\s*/', '', $content);
    $content = preg_replace('/shadow\s*/', '', $content);
    $content = preg_replace('/rounded-[0-9]\s*/', '', $content);
    $content = preg_replace('/\bstyle="[^"]*?(border|box-shadow):[^";]*;?[^"]*"/i', '', $content); // Strip explicit inline shadow/borders
    
    file_put_contents($file, $content);
    echo "Refactored " . basename($file) . "\n";
}
echo "Tabs transformation script completed successfully.\n";
