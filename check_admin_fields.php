<?php
// Usar Artisan en lugar de bootstrap directo
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║         VERIFICACIÓN DE CAMPOS ADMINISTRATIVOS                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

try {
    // Verificar que la tabla existe
    if (!Schema::hasTable('Documentos_Expediente')) {
        echo "❌ ERROR: Tabla 'Documentos_Expediente' no existe\n";
        exit(1);
    }

    echo "✓ Tabla 'Documentos_Expediente' existe\n\n";

    // Campos que debería tener
    $camposEsperados = [
        'admin_status' => 'nvarchar',
        'admin_observations' => 'nvarchar',
        'verification_token' => 'nvarchar',
        'id_admin' => 'int',
        'fecha_verificacion' => 'datetime2'
    ];

    echo "ESTADO DE CAMPOS ADMINISTRATIVOS:\n";
    echo "─────────────────────────────────────────────────────────────\n";

    $todosCorrectos = true;
    foreach ($camposEsperados as $campo => $tipo) {
        $existe = Schema::hasColumn('Documentos_Expediente', $campo);
        
        if ($existe) {
            echo "✓ $campo - EXISTE\n";
        } else {
            echo "✗ $campo - NO EXISTE\n";
            $todosCorrectos = false;
        }
    }

    echo "\n";
    if ($todosCorrectos) {
        echo "╔════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  ✓ TODOS LOS CAMPOS ADMINISTRATIVOS ESTÁN CORRECTAMENTE AGREGADOS     ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════╝\n";
        
        // Contar documentos
        $documentCount = DB::table('Documentos_Expediente')->count();
        echo "\nDocumentos en la tabla: $documentCount\n";
        echo "\n✓ El sistema administrativo está LISTO para usar\n";
        
        exit(0);
    } else {
        echo "╔════════════════════════════════════════════════════════════════════════╗\n";
        echo "║  ✗ FALTAN CAMPOS POR AGREGAR                                          ║\n";
        echo "║  Ejecuta el siguiente SQL en tu SQL Server:                            ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";
        
        echo file_get_contents(__DIR__ . '/create_admin_fields.sql');
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
