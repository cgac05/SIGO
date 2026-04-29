<?php
// Diagnostic script para verificar estado del build en servidor

$diagnostics = [];

try {
    $diagnostics['php_version'] = phpversion();
    $diagnostics['base_path'] = base_path();
    $diagnostics['public_path'] = public_path();
    
    $buildDir = public_path('build');
    $diagnostics['build_dir_exists'] = is_dir($buildDir);
    
    $manifestFile = public_path('build/manifest.json');
    $diagnostics['manifest_exists'] = file_exists($manifestFile);
    $diagnostics['manifest_readable'] = is_readable($manifestFile);
    
    if (file_exists($manifestFile) && is_readable($manifestFile)) {
        $content = file_get_contents($manifestFile);
        $manifest = json_decode($content, true);
        $diagnostics['manifest_valid_json'] = $manifest !== null;
        if ($manifest) {
            $diagnostics['manifest_keys'] = array_keys($manifest);
        }
    }
    
    // Listar archivos en build recursivamente
    $buildFiles = [];
    if (is_dir($buildDir)) {
        $files = scandir($buildDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $fullPath = $buildDir . '/' . $file;
                if (is_file($fullPath)) {
                    $buildFiles[] = $file;
                } elseif (is_dir($fullPath) && $file === 'assets') {
                    $assets = scandir($fullPath);
                    foreach ($assets as $asset) {
                        if ($asset !== '.' && $asset !== '..') {
                            $buildFiles[] = 'assets/' . $asset;
                        }
                    }
                }
            }
        }
    }
    $diagnostics['build_files'] = $buildFiles;
    
    // Permisos
    if (is_dir($buildDir)) {
        $diagnostics['build_dir_permissions'] = substr(sprintf('%o', fileperms($buildDir)), -4);
    }
    
    // Buscar logs
    $npmBuildLog = '/tmp/npm_build.log';
    if (file_exists($npmBuildLog) && is_readable($npmBuildLog)) {
        $logContent = file_get_contents($npmBuildLog);
        if (strlen($logContent) > 5000) {
            $diagnostics['npm_build_log_last_1000'] = substr($logContent, -1000);
            $diagnostics['npm_build_log_size'] = strlen($logContent);
        } else {
            $diagnostics['npm_build_log'] = $logContent;
        }
    }
    
} catch (Exception $e) {
    $diagnostics['error'] = $e->getMessage();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

