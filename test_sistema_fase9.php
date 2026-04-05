<?php
// Test script to verify models and functionality
$start = microtime(true);

require 'vendor/autoload.php';

try {
    // Check if files exist
    $files = [
        'app/Models/ArchivoCertificado.php',
        'app/Models/VersionCertificado.php',
        'app/Models/AuditoriaVerificacion.php',
        'app/Services/ArchivadoCertificadoService.php',
        'app/Services/VerificacionCertificadoService.php',
        'app/Http/Controllers/Admin/ArchivadoCertificadoController.php',
        'app/Http/Controllers/Admin/VerificacionCertificadoController.php',
    ];
    
    echo "🔍 Validating Fase 9 Implementation\n";
    echo "====================================\n\n";
    
    $missing = [];
    foreach ($files as $file) {
        if (!file_exists($file)) {
            $missing[] = $file;
            echo "❌ Missing: $file\n";
        } else {
            echo "✅ Found: " . basename($file) . "\n";
        }
    }
    
    // Check directories
    echo "\n📁 Storage Directories:\n";
    $dirs = ['storage/certificados_archivados', 'storage/backups'];
    foreach ($dirs as $dir) {
        if (is_dir($dir)) {
            echo "✅ $dir\n";
        } else {
            echo "❌ $dir (NOT FOUND)\n";
            $missing[] = $dir;
        }
    }
    
    // Check views
    echo "\n📄 Blade Views:\n";
    $views = [
        'resources/views/admin/certificacion/archivado/dashboard.blade.php',
        'resources/views/admin/certificacion/archivado/gestor-archivos.blade.php',
        'resources/views/admin/certificacion/archivado/visualizar-archivo.blade.php',
        'resources/views/admin/certificacion/archivado/historial-versiones.blade.php',
        'resources/views/admin/certificacion/archivado/formulario-masivo.blade.php',
        'resources/views/admin/certificacion/archivado/resultados-archivamiento.blade.php',
    ];
    
    foreach ($views as $view) {
        if (file_exists($view)) {
            echo "✅ " . basename($view) . "\n";
        } else {
            echo "❌ " . basename($view) . "\n";
            $missing[] = $view;
        }
    }
    
    $elapsed = microtime(true) - $start;
    
    echo "\n" . str_repeat("=", 36) . "\n";
    if (empty($missing)) {
        echo "✅ SISTEMA FASE 9 LISTO\n";
        echo "=======================\n";
        printf("Check time: %.2fms\n", $elapsed * 1000);
        echo "\n✓ All models present\n";
        echo "✓ All services present\n";
        echo "✓ All controllers present\n";
        echo "✓ All views present\n";
        echo "✓ Storage directories created\n";
        exit(0);
    } else {
        echo "❌ ISSUES FOUND (" . count($missing) . ")\n";
        echo str_repeat("=", 36) . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
