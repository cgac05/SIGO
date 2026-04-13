<?php
// Script de diagnóstico para verificar renderizado del componente

echo "=== DIAGNÓSTICO DE COMPONENTE RESUMEN-CRÍTICO ===\n\n";

// 1. Verificar archivo existe
$component_path = 'resources/views/components/firma/resumen-critico.blade.php';
if (file_exists($component_path)) {
    echo "✓ Archivo componente EXISTS: $component_path\n";
    echo "  Tamaño: " . filesize($component_path) . " bytes\n";
    echo "  Primeras 5 líneas:\n";
    $lines = file($component_path);
    for ($i = 0; $i < 5 && $i < count($lines); $i++) {
        echo "    " . trim($lines[$i]) . "\n";
    }
} else {
    echo "✗ Archivo componente NO EXISTE: $component_path\n";
}

echo "\n";

// 2. Verificar vista principal
$view_path = 'resources/views/solicitudes/firma.blade.php';
if (file_exists($view_path)) {
    echo "✓ Vista principal EXISTS: $view_path\n";
    $content = file_get_contents($view_path);
    if (strpos($content, '@component') !== false) {
        echo "  ✓ Contiene @component directive\n";
    } else {
        echo "  ✗ NO CONTIENE @component directive\n";
    }
    if (strpos($content, 'beneficiario') !== false) {
        echo "  ✓ Contiene variable \$beneficiario\n";
    } else {
        echo "  ✗ NO CONTIENE variable \$beneficiario\n";
    }
} else {
    echo "✗ Vista NO EXISTE: $view_path\n";
}

echo "\n";

// 3. Verificar caché compilada
$cache_dir = 'bootstrap/cache/views';
if (is_dir($cache_dir)) {
    echo "✓ Directorio caché EXISTS: $cache_dir\n";
    $files = count(glob($cache_dir . '/*.php'));
    echo "  Archivos compilados: " . $files . "\n";
} else {
    echo "✗ Directorio caché NO EXISTE: $cache_dir\n";
}

echo "\n=== FIN DIAGNÓSTICO ===\n";
?>
