<?php
// Diagnostic script para verificar estado del build en servidor

$diagnostics = [
    'base_path' => base_path(),
    'public_path' => public_path(),
    'build_dir_exists' => is_dir(public_path('build')),
    'manifest_exists' => file_exists(public_path('build/manifest.json')),
    'manifest_readable' => is_readable(public_path('build/manifest.json')),
];

if (file_exists(public_path('build/manifest.json'))) {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    $diagnostics['manifest_content'] = $manifest;
    $diagnostics['manifest_valid_json'] = $manifest !== null;
}

// Listar archivos en build
$buildFiles = [];
if (is_dir(public_path('build'))) {
    $iterator = new RecursiveDirectoryIterator(public_path('build'));
    foreach (new RecursiveIteratorIterator($iterator) as $file) {
        if ($file->isFile()) {
            $buildFiles[] = $file->getRelativePathname();
        }
    }
}
$diagnostics['build_files'] = $buildFiles;

// Verificar permisos
$diagnostics['build_permissions'] = substr(sprintf('%o', fileperms(public_path('build'))), -4);

// Revisar si hay logs de build
$logFile = '/tmp/npm_build.log';
if (file_exists($logFile)) {
    $diagnostics['npm_build_log'] = file_get_contents($logFile);
}

header('Content-Type: application/json');
echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
