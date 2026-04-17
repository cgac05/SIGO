<?php
/**
 * FINAL VERIFICATION: All column name fixes validation
 * Checks for any remaining references to outdated column names
 */

$patterns = [
    'CRITICAL - id_presupuesto_apoyo (should be id_apoyo_presupuesto)' => 'id_presupuesto_apoyo',
    'CRITICAL - costo_estimado (should be monto_solicitado)' => "costo_estimado'",
    'CRITICAL - fecha_cambio (should use created_at)' => "fecha_cambio'",
    'CRITICAL - fecha_movimiento (should use created_at)' => "fecha_movimiento'",
    'CRITICAL - id_usuario as FK (should be creado_por)' => "id_usuario'",
    'WARN - tipo column (should be tipo_movimiento)' => "'tipo'",
];

$excludeFiles = [
    'migration', 'test', '.md', 'check_', 'debug_', 'verify_', 'create_', 
    'precheck_', 'prepare_', '.txt', '.sql', '.sh', 'CHANGELOG'
];

$appFiles = glob('c:\\xampp\\htdocs\\SIGO\\app\\**\\*.php', GLOB_RECURSIVE);
$foundIssues = [];

foreach ($appFiles as $file) {
    $skip = false;
    foreach ($excludeFiles as $exclude) {
        if (stripos($file, $exclude) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    $content = file_get_contents($file);
    foreach ($patterns as $description => $pattern) {
        if (preg_match("/$pattern/ii", $content)) {
            if (!isset($foundIssues[$description])) {
                $foundIssues[$description] = [];
            }
            $foundIssues[$description][] = str_replace('c:\\xampp\\htdocs\\SIGO\\', '', $file);
        }
    }
}

if (empty($foundIssues)) {
    echo "✅ SUCCESS: No remaining column name issues found!\n";
} else {
    echo "⚠️  ISSUES FOUND:\n\n";
    foreach ($foundIssues as $issue => $files) {
        echo "❌ $issue\n";
        foreach (array_unique($files) as $file) {
            echo "   - $file\n";
        }
        echo "\n";
    }
}
?>
