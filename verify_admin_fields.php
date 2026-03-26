use Illuminate\Support\Facades\Schema;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE CAMPOS ADMINISTRATIVOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$campos = [
    'admin_status',
    'admin_observations', 
    'verification_token',
    'id_admin',
    'fecha_verificacion'
];

$todosCorrectos = true;

foreach ($campos as $campo) {
    if (Schema::hasColumn('Documentos_Expediente', $campo)) {
        echo "✓ $campo - EXISTE\n";
    } else {
        echo "✗ $campo - NO EXISTE\n";
        $todosCorrectos = false;
    }
}

echo "\n";
if ($todosCorrectos) {
    echo "✓ TODOS LOS CAMPOS ESTÁN CORRECTAMENTE AGREGADOS\n";
    echo "✓ El sistema administrativo está LISTO para usar\n";
} else {
    echo "✗ FALTAN CAMPOS\n";
    echo "Reproduce: Ejecuta el SQL ALTER TABLE en tu SQL Server\n";
}
echo "\n════════════════════════════════════════════════════════════════\n\n";
