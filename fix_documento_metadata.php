<?php
/**
 * Script para verificar y reparar metadata de documentos duplicados/conflictivos
 * Búsqueda por folio 1013, documento ID 11
 */

require 'vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')->bootstrap($app);

use App\Models\Documento;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

echo "=== SCRIPT: Verificar metadata de documentos ===\n\n";

// Buscar folio 1013
$solicitud = Solicitud::where('folio', 1013)->first();
if (!$solicitud) {
    echo "❌ Solicitud folio 1013 no encontrada\n";
    exit(1);
}

echo "✅ Solicitud encontrada: Folio 1013 (ID: {$solicitud->id_folio})\n";

// Buscar documento ID 11
$documento = Documento::where('id_documento', 11)
    ->where('fk_id_solicitud', $solicitud->id_folio)
    ->first();

if (!$documento) {
    echo "❌ Documento ID 11 no encontrado en solicitud 1013\n";
    exit(1);
}

echo "✅ Documento encontrado: ID 11\n\n";
echo "METADATA ACTUAL:\n";
echo "  - Origen: " . ($documento->origen_archivo ?? 'NULL') . "\n";
echo "  - Ruta: " . ($documento->ruta_archivo ?? 'NULL') . "\n";
echo "  - Google File ID: " . ($documento->google_file_id ?? 'NULL') . "\n";
echo "  - Google File Name: " . ($documento->google_file_name ?? 'NULL') . "\n\n";

// Verificar si el archivo existe
$ruta = $documento->ruta_archivo;
$hash = basename($ruta, '.pdf');

echo "BÚSQUEDA DE ARCHIVO:\n";
echo "  - Buscando por hash: $hash\n";

// Rutas a verificar
$paths = [
    storage_path('app/public/' . $ruta),
    storage_path('app/public/' . $ruta . '.pdf'),
    public_path('storage/' . $ruta),
    public_path('storage/' . $ruta . '.pdf'),
];

// Agregar búsqueda sin extensión
if (pathinfo($ruta, PATHINFO_EXTENSION) === 'pdf') {
    $rutaSinExt = substr($ruta, 0, -4); // Quitar .pdf
    $paths[] = storage_path('app/public/' . $rutaSinExt);
    $paths[] = public_path('storage/' . $rutaSinExt);
}

$fileFound = false;
$foundPath = null;

foreach ($paths as $path) {
    echo "  ✓ Verificando: $path\n";
    if (file_exists($path)) {
        echo "    -> ENCONTRADO ✅\n";
        $fileFound = true;
        $foundPath = $path;
        break;
    }
}

if (!$fileFound) {
    echo "\n  ❌ Archivo NO encontrado en ninguna ubicación\n";
}

// Verificar Storage facade
echo "\nVERIFICACIÓN Storage FACADE:\n";
if (Storage::disk('public')->exists($ruta)) {
    echo "  ✅ Existe en Storage::disk('public')\n";
    $fileFound = true;
} else {
    echo "  ❌ NO existe en Storage::disk('public')\n";
}

// Búsqueda por glob en directorio solicitudes
echo "\nBÚSQUEDA POR GLOB en storage/app/public/solicitudes/:\n";
$glob_pattern = storage_path('app/public/solicitudes/' . $hash . '*');
echo "  Pattern: $glob_pattern\n";

$glob_results = glob($glob_pattern);
if ($glob_results) {
    foreach ($glob_results as $file) {
        echo "  ✅ ENCONTRADO: $file\n";
        $fileFound = true;
        $foundPath = $file;
    }
} else {
    echo "  ❌ No se encontraron archivos similares\n";
}

// RESULTADO Y ACCIÓN
echo "\n" . str_repeat("=", 50) . "\n";
if ($fileFound) {
    echo "✅ RESULTADO: Archivo SÍ existe\n";
    echo "   Ubicación: $foundPath\n";
    
    // Determinar origen correcto
    $correctOrigin = 'local';
    $correctGoogleId = null;
    
    // Actualizar
    $documento->update([
        'origen_archivo' => $correctOrigin,
        'google_file_id' => $correctGoogleId,
    ]);
    
    echo "\n✏️ ACTUALIZACIÓN BD:\n";
    echo "   - origen_archivo actualizado a: 'local'\n";
    echo "   - google_file_id limpiado (NULL)\n";
} else {
    echo "❌ RESULTADO: Archivo NO existe\n";
    echo "\nACCIONES RECOMENDADAS:\n";
    echo "  1. Verificar si el archivo fue movido/eliminado durante aprobación\n";
    echo "  2. Buscar en directorio completo: dir " . storage_path('app/public/solicitudes\\') . "\n";
    echo "  3. Restaurar desde backup si es disponible\n";
    echo "  4. Eliminar documento de BD si es test: \n";
    echo "     DELETE FROM documentos_expediente WHERE id_documento = 11;\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Script finalizado\n";
