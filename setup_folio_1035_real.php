<?php
/**
 * Verificar/Crear folio de prueba 1035 con apoyo REAL
 */
require 'bootstrap/app.php';
require 'bootstrap/app.php';

use App\Models\Solicitud;
use App\Models\Apoyo;
use App\Models\ClaveSegumientoPrivada;
use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════\n";
echo "VERIFICAR/CREAR FOLIO 1035 CON APOYO REAL\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    // 1. Conectar directamente a BD
    echo "✓ Conectando a BD...\n";
    $connection = DB::connection('sqlsrv');
    $connection->getPdo();  // Test connection
    echo "   ✓ Conectado exitosamente\n\n";
    
    // 2. Obtener primer apoyo activo
    echo "✓ Buscando apoyos activos...\n";
    $apoyo = DB::table('apoyos')
        ->where('activo', 1)
        ->first();
    
    if (!$apoyo) {
        echo "✗ NO hay apoyos activos en la BD\n";
        exit;
    }
    
    echo "   Apoyo encontrado:\n";
    echo "   ID: {$apoyo->id_apoyo}\n";
    echo "   Nombre: {$apoyo->nombre_apoyo}\n\n";
    
    // 3. Borrar folio 1035 si existe
    echo "✓ Preparando folio...\n";
    DB::table('Solicitudes')->where('folio', 1035)->delete();
    DB::table('claves_seguimiento_privadas')->where('folio', 1035)->delete();
    echo "   ✓ Registros anteriores eliminados\n\n";
    
    // 4. Crear nueva solicitud
    echo "✓ Creando solicitud con folio 1035...\n";
    $solicitud_id = DB::table('Solicitudes')->insertGetId([
        'folio' => 1035,
        'fk_curp' => 'TEST1234567890TEST',
        'fk_id_apoyo' => $apoyo->id_apoyo,  // ✓ ID REAL
        'beneficiario_id' => null,
        'origen_solicitud' => 'admin_caso_a',
        'estado_solicitud' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
        'fecha_creacion' => DB::raw('GETDATE()'),
    ], 'folio');
    
    echo "   ✓ Solicitud creada\n\n";
    
    // 5. Crear clave privada
    echo "✓ Creando clave de acceso...\n";
    DB::table('claves_seguimiento_privadas')->insert([
        'folio' => 1035,
        'clave_alfanumerica' => 'TEST-TEST-TEST-TEST',
        'beneficiario_id' => null,
        'fecha_creacion' => DB::raw('GETDATE()'),
        'activa' => 1,
        'bloqueada' => 0,
    ]);
    
    echo "   ✓ Clave creada\n\n";
    
    // 6. Verificar
    echo "✓ Verificando datos creados...\n";
    $verif_solicitud = DB::table('Solicitudes')->where('folio', 1035)->first();
    $verif_apoyo = DB::table('apoyos')->where('id_apoyo', $verif_solicitud->fk_id_apoyo)->first();
    $verif_clave = DB::table('claves_seguimiento_privadas')->where('folio', 1035)->first();
    
    echo "   Solicitud:\n";
    echo "     Folio: {$verif_solicitud->folio}\n";
    echo "     CURP: {$verif_solicitud->fk_curp}\n";
    echo "     Estado: {$verif_solicitud->estado_solicitud}\n\n";
    
    echo "   Apoyo:\n";
    echo "     ID: {$verif_apoyo->id_apoyo}\n";
    echo "     Nombre: {$verif_apoyo->nombre_apoyo}\n\n";
    
    echo "   Clave:\n";
    echo "     Folio: {$verif_clave->folio}\n";
    echo "     Clave: {$verif_clave->clave_alfanumerica}\n";
    echo "     Activa: " . ($verif_clave->activa ? 'SÍ' : 'NO') . "\n\n";
    
    // 7. Mostrar URLs
    echo str_repeat("═", 60) . "\n";
    echo "LISTO PARA TESTING\n";
    echo str_repeat("═", 60) . "\n\n";
    
    echo "URLs para probar:\n\n";
    
    echo "1️⃣  Ver en resumen admin:\n";
    echo "   http://localhost:8000/admin/caso-a/resumen/1035\n\n";
    
    echo "2️⃣  Escanear QR (copiar en navegador):\n";
    echo "   http://localhost:8000/consulta-privada/acceso-qr?folio=1035&clave=TEST-TEST-TEST-TEST\n\n";
    
    echo "3️⃣  Ver consulta privada:\n";
    echo "   http://localhost:8000/consulta-privada/resumen\n\n";
    
    echo str_repeat("═", 60) . "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    echo "Detalles:\n";
    echo $e->getTraceAsString();
}
