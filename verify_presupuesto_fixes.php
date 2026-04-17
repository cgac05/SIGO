<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PresupuestoApoyo;
use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN CORRECCIONES presupuesto_apoyos ===\n\n";

// 1. Verificar modelo
echo "📦 MODELO PresupuestoApoyo:\n";
$p = new PresupuestoApoyo();
echo "  - Primary Key: {$p->getKeyName()}\n";
echo "  - Table: {$p->getTable()}\n\n";

// 2. Verificar que accessores funcionan
echo "🔧 ACCESSORES (compatibilidad):\n";
$attrs = (new PresupuestoApoyo())->getAttributes();
echo "  - Has costo_estimado accessor: " . (method_exists(PresupuestoApoyo::class, 'getCostoEstimadoAttribute') ? "✅" : "❌") . "\n";
echo "  - Has fecha_reserva accessor: " . (method_exists(PresupuestoApoyo::class, 'getFechaReservaAttribute') ? "✅" : "❌") . "\n\n";

// 3. Verificar columnas en BD
echo "📋 COLUMNAS REALES EN BD:\n";
$columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                       WHERE TABLE_NAME = 'presupuesto_apoyos' 
                       ORDER BY ORDINAL_POSITION");
$col_names = array_map(fn($c) => $c->COLUMN_NAME, $columns);
echo "  - monto_solicitado: " . (in_array('monto_solicitado', $col_names) ? "✅" : "❌") . "\n";
echo "  - fecha_solicitud: " . (in_array('fecha_solicitud', $col_names) ? "✅" : "❌") . "\n";
echo "  - id_apoyo: " . (in_array('id_apoyo', $col_names) ? "❌" : "✅ (no existe, correcto)") . "\n";
echo "  - folio: " . (in_array('folio', $col_names) ? "✅" : "❌") . "\n\n";

// 4. Verificar código del servicio
echo "🔍 VERIFICAR PresupuestaryControlService:\n";
$service_file = file_get_contents(__DIR__ . '/app/Services/PresupuetaryControlService.php');
echo "  - Usa 'folio' en create: " . (strpos($service_file, "'folio' => \$id_apoyo") !== false ? "✅" : "❌") . "\n";
echo "  - Usa 'monto_solicitado': " . (strpos($service_file, "'monto_solicitado' => \$costo_estimado") !== false ? "✅" : "❌") . "\n";
echo "  - Usa 'fecha_solicitud': " . (strpos($service_file, "'fecha_solicitud' => now()") !== false ? "✅" : "❌") . "\n";
echo "  - NO usa 'id_apoyo' directo: " . (strpos($service_file, "'id_apoyo' => \$id_apoyo") === false ? "✅" : "❌") . "\n\n";

echo "✅ VERIFICACIÓN COMPLETADA\n";
echo "\n💡 SIGUIENTE PASO: Intentar crear solicitud nuevamente desde /apoyos/47/solicitud\n";
