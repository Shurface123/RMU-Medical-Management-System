<?php
$dir = __DIR__;
$files = glob($dir . '/*.php');

foreach($files as $file) {
    if(in_array(basename($file), ['refactor.php', 'fix_regex.php', 'refactor_dom.php', 'process_reports.php'])) continue;
    $content = file_get_contents($file);
    $original = $content;

    // 1. STATS GRID: Replace specific rows containing stat-cards
    // Look for rows that have 'stat-card' or 'adm-stat-card' immediately inside cols.
    // Instead of regex hell, let's just do a generic replacement for Bootstrap classes.
    
    // Convert col-* to grid-cell (neutralizes width restrictions so CSS Grid controls them)
    $content = preg_replace('/\bcol-(12|md-[0-9]+|lg-[0-9]+|sm-[0-9]+)\b/', 'grid-cell', $content);
    $content = preg_replace('/\bcol\b/', 'grid-cell', $content);
    
    // Convert rows to appropriate grids based on contents
    // A heuristic: if it has form inputs, it's a form-row. If it has cards, it's cards-grid.
    $lines = explode("\n", $content);
    $in_form = false;
    
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], '<form') !== false) $in_form = true;
        if (strpos($lines[$i], '</form>') !== false) $in_form = false;
        
        if (preg_match('/<div[^>]*class="[^"]*\brow\b/', $lines[$i])) {
            // It's a row. Let's look ahead to see what's inside.
            $lookahead = implode(" ", array_slice($lines, $i, 15));
            if ($in_form || strpos($lookahead, '<input') !== false || strpos($lookahead, '<select') !== false) {
                $lines[$i] = preg_replace('/\brow(\s+[a-z0-9\-]+)*\b/', 'form-row', $lines[$i]);
            } elseif (strpos($lookahead, 'stat-card') !== false || strpos($lookahead, 'adm-mini-card') !== false) {
                $lines[$i] = preg_replace('/\brow(\s+[a-z0-9\-]+)*\b/', 'stat-grid', $lines[$i]);
            } elseif (strpos($lookahead, '<canvas') !== false || strpos($lookahead, 'chart') !== false) {
                // For analytics charts
                $lines[$i] = preg_replace('/\brow(\s+[a-z0-9\-]+)*\b/', 'charts-grid', $lines[$i]);
            } else {
                $lines[$i] = preg_replace('/\brow(\s+[a-z0-9\-]+)*\b/', 'cards-grid', $lines[$i]);
            }
            
            // Remove lingering gap and margin classes from rows because CSS grid handles gap natively
            $lines[$i] = preg_replace('/\b(g-[0-5]|gx-[0-5]|gy-[0-5]|mb-[0-5]|mt-[0-5]|pb-[0-5]|pt-[0-5]|align-items-center|justify-content-\w+)\b/', '', $lines[$i]);
        }
    }
    
    $content = implode("\n", $lines);
    
    // Quick Fixes for specific components
    $content = str_replace('card-body', 'adm-card-body', $content); // in case missed earlier
    $content = str_replace('card-header', 'adm-card-header', $content);
    
    // Remove inline styles that mess up standard grids
    $content = preg_replace('/style="border-radius:[^"]*;/i', 'style="', $content);
    $content = preg_replace('/style="border:[^"]*;/i', 'style="', $content);
    $content = preg_replace('/style="box-shadow:[^"]*;/i', 'style="', $content);
    $content = preg_replace('/style="\s*"/i', '', $content); // clear empty styles

    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "Refactored DOM: " . basename($file) . "<br>\n";
    }
}
echo "DOM Refactor Script Completed.<br>\n";
