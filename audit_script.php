<?php
// Ensure we only scan the specific project folder
$baseDir = __DIR__;
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));

$valid_tables = ['users', 'user_sessions', 'patients', 'doctors', 'staff', 'appointments', 'medical_records', 'prescriptions', 'medicines', 'services', 'ambulances', 'ambulance_requests', 'beds', 'bed_assignments', 'lab_tests', 'payments', 'audit_log', 'active_appointments', 'patient_medical_summary', 'medicine_inventory_status'];

$missing_links = [];
$broken_includes = [];
$broken_queries = [];

$php_html_files = [];
foreach ($files as $file) {
    if ($file->isDir()) continue;
    $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    if ($ext !== 'php' && $ext !== 'html') continue;
    
    // Ignore vendor or other third party dirs if any
    $path = $file->getPathname();
    if (strpos($path, 'vendor') !== false || strpos($path, 'node_modules') !== false) continue;
    
    // Only parse if actually in our target dir
    $filePath = str_replace('\\', '/', $path);
    $baseDirPath = str_replace('\\', '/', $baseDir);
    if (strpos($filePath, $baseDirPath) === false) continue;
    
    $php_html_files[] = $filePath;
}

foreach ($php_html_files as $filePath) {
    $relFilePath = str_replace($baseDirPath, '', $filePath);
    $content = file_get_contents($filePath);
    $dir = dirname($filePath);
    
    // Links (href, action, window.location, header(Location))
    preg_match_all('/(?:href|action)\s*=\s*["\']([^"\']+?\.(?:php|html))(?:\?[^"\']*)?["\']/i', $content, $matches1);
    preg_match_all('/header\s*\(\s*["\']Location:\s*([^"\'\?]+?\.(?:php|html))(?:\?[^"\']*)?["\']/i', $content, $matches2);
    preg_match_all('/window\.location(?:\\.href)?\s*=\s*["\']([^"\'\?]+?\.(?:php|html))(?:\?[^"\']*)?["\']/i', $content, $matches3);
    
    $all_links = array_unique(array_merge($matches1[1], $matches2[1], $matches3[1]));
    
    foreach ($all_links as $link) {
        if (strpos($link, 'http') === 0 || strpos($link, '//') === 0 || strpos($link, 'javascript:') === 0 || strpos($link, '#') === 0) continue;
        
        $link = trim(explode('?', explode('#', $link)[0])[0]);
        if (empty($link)) continue;
        
        $targetPath = '';
        if (strpos($link, '/') === 0) {
            $cleanLink = preg_replace('/^\/RMU-Medical-Management-System/', '', $link);
            if ($cleanLink === $link) continue; 
            $targetPath = $baseDirPath . $cleanLink;
        } else {
            $targetPath = $dir . '/' . $link;
        }
        
        $targetPath = preg_replace('/\/+/', '/', $targetPath);
        
        if (!file_exists($targetPath)) {
            $parts = explode('/', $targetPath);
            $abs = [];
            foreach ($parts as $p) {
                if ($p == '.' || $p == '') continue;
                if ($p == '..') array_pop($abs);
                else $abs[] = $p;
            }
            $drv = preg_match('/^[a-z]:/i', $parts[0]??'') ? '' : '/';
            $resolvedPath = str_replace('\\', '/', $drv . implode('/', $abs));
            
            if (!file_exists($resolvedPath)) {
                $lines = explode("\n", $content);
                $lineNum = 0;
                foreach($lines as $i => $line) {
                    if (strpos($line, $link) !== false) {
                        $lineNum = $i + 1;
                        break;
                    }
                }
                $missing_links[$relFilePath][] = ['link' => $link, 'line' => $lineNum];
            }
        }
    }
    
    // Includes
    preg_match_all('/(?:include|require)(?:_once)?\s*\(?\s*["\']([^"\']+\.php)["\']/i', $content, $matches4);
    foreach ($matches4[1] as $inc) {
        if (strpos($inc, 'http') === 0) continue;
        
        $targetPath = preg_replace('/\/+/', '/', $dir . '/' . $inc);
        if (!file_exists($targetPath)) {
            $parts = explode('/', $targetPath);
            $abs = [];
            foreach ($parts as $p) {
                if ($p == '.' || $p == '') continue;
                if ($p == '..') array_pop($abs);
                else $abs[] = $p;
            }
            $drv = preg_match('/^[a-z]:/i', $parts[0]??'') ? '' : '/';
            $resolvedPath = str_replace('\\', '/', $drv . implode('/', $abs));
            
            if (!file_exists($resolvedPath)) {
                $lines = explode("\n", $content);
                $lineNum = 0;
                foreach($lines as $i => $line) {
                    if (strpos($line, $inc) !== false) {
                        $lineNum = $i + 1;
                        break;
                    }
                }
                $broken_includes[$relFilePath][] = ['include' => $inc, 'line' => $lineNum];
            }
        }
    }
    
    // Queries
    preg_match_all('/(?:FROM|INTO|UPDATE|JOIN)\s+([a-zA-Z0-9_]+)\b/i', $content, $matches5);
    $found_tables = array_unique($matches5[1]);
    $exclude = ['select', 'delete', 'insert', 'update', 'where', 'set', 'from', 'inner', 'left', 'right', 'join', 'as', 'on', 'and', 'or', 'by', 'order', 'group', 'limit', 'is', 'null', 'not', 'values', 'into', 'count', 'desc', 'asc', 'now', 'date', 'month', 'year'];
    foreach ($found_tables as $table) {
        $ltable = strtolower($table);
        if (!in_array($ltable, $valid_tables) && !in_array($ltable, $exclude)) {
            if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|JOIN).+?' . preg_quote($table, '/') . '/is', $content)) {
                $broken_queries[$relFilePath][] = $table;
            }
        }
    }
}

// OUTPUT REPORT
$output = "MISSING FILES AUDIT REPORT\n";
$output .= "===========================\n";

$grouped_missing = [];
foreach ($missing_links as $file => $links) {
    $parts = explode('/', trim($file, '/'));
    $module = ($parts[0] == 'php' && isset($parts[1]) && !preg_match('/\.php$/', $parts[1])) ? ucfirst($parts[1]) : 'Core/HTML';
    
    foreach ($links as $l) {
        $msg = "  - " . $l['link'] . " \t→ MISSING (referenced in " . basename($file) . " line " . $l['line'] . ")";
        if (!isset($grouped_missing[$module]) || !in_array($msg, $grouped_missing[$module])) {
            $grouped_missing[$module][] = $msg;
        }
    }
}

ksort($grouped_missing);
foreach ($grouped_missing as $module => $msgs) {
    if (empty($msgs)) continue;
    $output .= "\nDashboard: $module\n";
    foreach ($msgs as $msg) {
        $output .= "$msg\n";
    }
}

if (!empty($broken_includes)) {
    $output .= "\nBROKEN INCLUDES / REQUIRES\n";
    $output .= "===========================\n";
    foreach ($broken_includes as $file => $incs) {
        $seen = [];
        foreach ($incs as $i) {
            $msg = "  - " . $i['include'] . " \t→ MISSING (included in " . str_replace('\\', '/', $file) . " line " . $i['line'] . ")";
            if (!in_array($msg, $seen)) {
                $output .= $msg . "\n";
                $seen[] = $msg;
            }
        }
    }
}

if (!empty($broken_queries)) {
    $output .= "\nPOSSIBLE BROKEN DATABASE QUERIES (Unknown Tables)\n";
    $output .= "===========================\n";
    foreach ($broken_queries as $file => $tables) {
        foreach (array_unique($tables) as $t) {
            if (strlen($t) > 2) {
                $output .= "  - Table '$t' referenced in " . str_replace('\\', '/', $file) . "\n";
            }
        }
    }
}

file_put_contents('C:/Users/Test/.gemini/antigravity/brain/69aed29e-12e4-4b37-bbc2-ec9f7d51f5a1/audit_report.md', $output);
echo "SUCCESS: Saved to audit_report.md";
?>
