<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CicloPresupuestario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EconomicDashboardController extends Controller
{
    /**
     * Mostrar dashboard económico con resumen de presupuestos e inventario
     */
    public function index()
    {
        $user = Auth::user();
        
        // ===== OBTENER CICLO PRESUPUESTARIO ACTIVO =====
        $cicloId = request()->query('ciclo');
        
        // Si no se especifica ciclo, usar el ciclo ABIERTO más reciente, o el de este año
        if (!$cicloId) {
            $cicloActivo = \App\Models\CicloPresupuestario::abierto()
                ->orderByDesc('ano_fiscal')
                ->first() ?? 
                \App\Models\CicloPresupuestario::orderByDesc('ano_fiscal')->first();
            
            $cicloId = $cicloActivo?->id_ciclo;
        } else {
            $cicloActivo = \App\Models\CicloPresupuestario::findOrFail($cicloId);
        }
        
        // Obtener lista de ciclos para el selector
        $ciclosDisponibles = \App\Models\CicloPresupuestario::orderByDesc('ano_fiscal')->get();
        
        // ===== DATOS DE PRESUPUESTO (FILTRADO POR CICLO) =====
        // Usando presupuesto_total del ciclo (presupuesto_total_inicial)
        $presupuestoTotal = $cicloActivo?->presupuesto_total_inicial ?? 
                           DB::table('presupuesto_categorias as pc')
                               ->where('pc.activo', 1)
                               ->where('pc.id_ciclo', $cicloId)
                               ->sum('pc.presupuesto_anual');

        // Sumar montos entregados (realizado)
        $presupuestoAsignado = DB::table('solicitudes')
            ->where('fk_id_estado', '>=', 5)  // Estados: Aprobado, Desembolsado
            ->sum('monto_entregado');

        $presupuestoDisponible = max(0, $presupuestoTotal - $presupuestoAsignado);
        $porcentajeUtilizacion = $presupuestoTotal > 0 
            ? round(($presupuestoAsignado / $presupuestoTotal) * 100, 2)
            : 0;

        // ===== DATOS DE INVENTARIO =====
        // Total de items en inventario
        $totalInventario = DB::table('BD_Inventario')
            ->sum('stock_actual');

        // Movimientos del mes actual
        $movimientosEsteMes = DB::table('movimientos_inventario')
            ->whereYear('fecha_movimiento', date('Y'))
            ->whereMonth('fecha_movimiento', date('m'))
            ->count();

        // ENTRADA vs SALIDA totales
        $movimientosEntrada = DB::table('movimientos_inventario')
            ->where('tipo_movimiento', 'ENTRADA')
            ->sum('cantidad');

        $movimientosSalida = DB::table('movimientos_inventario')
            ->where('tipo_movimiento', 'SALIDA')
            ->sum('cantidad');

        $movimientosAjuste = DB::table('movimientos_inventario')
            ->where('tipo_movimiento', 'AJUSTE')
            ->sum('cantidad');

        // ===== ÚLTIMAS FACTURAS =====
        $ultimasFacturas = DB::table('facturas_compra as fc')
            ->join('usuarios as u', 'u.id_usuario', '=', 'fc.registrado_por')
            ->leftJoin('Personal as p', 'p.fk_id_usuario', '=', 'u.id_usuario')
            ->select(
                'fc.id_factura',
                'fc.numero_factura',
                'fc.nombre_proveedor',
                'fc.fecha_compra',
                'fc.monto_total',
                'fc.estado',
                DB::raw("CONCAT(ISNULL(p.nombre, ''), ' ', ISNULL(p.apellido_paterno, '')) as nombre_registrado")
            )
            ->orderByDesc('fc.fecha_compra')
            ->limit(10)
            ->get();

        // ===== ESTADO DE INVENTARIO POR APOYO =====
        $inventarioPorApoyo = DB::table('BD_Inventario as bi')
            ->join('Apoyos as a', 'a.id_apoyo', '=', 'bi.fk_id_apoyo')
            ->select(
                'a.id_apoyo',
                'a.nombre_apoyo',
                'a.tipo_apoyo',
                'bi.stock_actual',
                'bi.id_inventario'
            )
            ->orderBy('a.nombre_apoyo')
            ->get();

        // ===== PRESUPUESTO POR CATEGORÍA (FILTRADO POR CICLO) =====
        $presupuestoPorCategoria = DB::table('presupuesto_categorias as pc')
            ->select(
                'pc.id_categoria',
                'pc.nombre',
                'pc.presupuesto_anual',
                'pc.disponible'
            )
            ->where('pc.activo', 1)
            ->where('pc.id_ciclo', $cicloId)
            ->orderBy('pc.nombre')
            ->get()
            ->map(function($item) {
                // Usar datos del modelo, con fallback a cálculos manuales
                $item->monto_presupuestado = floatval($item->presupuesto_anual) ?: 0;
                $item->monto_disponible = floatval($item->disponible) ?: 0;
                // Calcular asignado = presupuesto - disponible
                $item->monto_asignado = max(0, $item->monto_presupuestado - $item->monto_disponible);
                
                // Porcentaje de utilización
                $item->porcentaje = $item->monto_presupuestado > 0 
                    ? round(($item->monto_asignado / $item->monto_presupuestado) * 100, 1)
                    : 0;
                
                // Estado con niveles: normal (<60%), alerta (60-85%), peligro (>85%)
                if ($item->porcentaje >= 90) {
                    $item->estado_alerta = 'danger';  // Rojo - crítico
                    $item->estado_badge = '⚠️ Crítico';
                } elseif ($item->porcentaje >= 75) {
                    $item->estado_alerta = 'warning';  // Amarillo
                    $item->estado_badge = '⚡ Alerta';
                } else {
                    $item->estado_alerta = 'success';  // Verde
                    $item->estado_badge = '✅ Normal';
                }
                
                return $item;
            });

        // ===== MOVIMIENTOS DE INVENTARIO DEL MES ACTUAL =====
        $movimientosDetallados = DB::table('movimientos_inventario as mi')
            ->join('BD_Inventario as bi', 'bi.id_inventario', '=', 'mi.fk_id_inventario')
            ->join('Apoyos as a', 'a.id_apoyo', '=', 'bi.fk_id_apoyo')
            ->join('usuarios as u', 'u.id_usuario', '=', 'mi.registrado_por')
            ->leftJoin('Personal as p', 'p.fk_id_usuario', '=', 'u.id_usuario')
            ->select(
                'mi.id',
                'mi.tipo_movimiento',
                'mi.cantidad',
                'mi.fecha_movimiento',
                'a.nombre_apoyo',
                DB::raw("CONCAT(ISNULL(p.nombre, ''), ' ', ISNULL(p.apellido_paterno, '')) as registrado_por_nombre"),
                'mi.observaciones'
            )
            ->whereYear('mi.fecha_movimiento', date('Y'))
            ->whereMonth('mi.fecha_movimiento', date('m'))
            ->orderByDesc('mi.fecha_movimiento')
            ->limit(15)
            ->get();

        // ===== ALERTAS DE PRESUPUESTO BAJO =====
        $alertasPresupuesto = $presupuestoPorCategoria
            ->filter(function($item) {
                return $item->porcentaje >= 85;
            })
            ->values();

        // ===== ALERTAS DE INVENTARIO BAJO =====
        $alertasInventario = DB::table('BD_Inventario as bi')
            ->join('Apoyos as a', 'a.id_apoyo', '=', 'bi.fk_id_apoyo')
            ->where('bi.stock_actual', '<', 10)  // Alertar si hay menos de 10 items
            ->select(
                'a.nombre_apoyo',
                'bi.stock_actual',
                'a.id_apoyo'
            )
            ->get();

        // ===== TOTALES AGREGADOS DEL CICLO =====
        $totalCategoriasAsignado = $presupuestoPorCategoria->sum('monto_asignado');
        $totalCategoriaDisponible = $presupuestoPorCategoria->sum('monto_disponible');
        $porcentajeCicloUtilizado = $presupuestoTotal > 0 
            ? round(($totalCategoriasAsignado / $presupuestoTotal) * 100, 1)
            : 0;

        return view('admin.dashboard-economico.index', [
            'cicloActivo' => $cicloActivo,
            'ciclosDisponibles' => $ciclosDisponibles,
            'presupuestoTotal' => $presupuestoTotal,
            'presupuestoAsignado' => $presupuestoAsignado,
            'presupuestoDisponible' => $presupuestoDisponible,
            'porcentajeUtilizacion' => $porcentajeUtilizacion,
            'totalInventario' => $totalInventario,
            'movimientosEsteMes' => $movimientosEsteMes,
            'movimientosEntrada' => $movimientosEntrada,
            'movimientosSalida' => $movimientosSalida,
            'movimientosAjuste' => $movimientosAjuste,
            'ultimasFacturas' => $ultimasFacturas,
            'inventarioPorApoyo' => $inventarioPorApoyo,
            'presupuestoPorCategoria' => $presupuestoPorCategoria,
            'movimientosDetallados' => $movimientosDetallados,
            'alertasPresupuesto' => $alertasPresupuesto,
            'alertasInventario' => $alertasInventario,
            // Nuevos datos para integración
            'totalCategoriaAsignado' => $totalCategoriasAsignado,
            'totalCategoriaDisponible' => $totalCategoriaDisponible,
            'porcentajeCicloUtilizado' => $porcentajeCicloUtilizado,
        ]);
    }

    /**
     * API endpoint: Obtener datos de movimientos para gráficos
     */
    public function apiMovimientosGrafico()
    {
        $movimientosPorDia = DB::table('movimientos_inventario')
            ->where('tipo_movimiento', 'SALIDA')
            ->whereYear('fecha_movimiento', date('Y'))
            ->whereMonth('fecha_movimiento', date('m'))
            ->select(
                DB::raw('DAY(fecha_movimiento) as dia'),
                DB::raw('SUM(cantidad) as cantidad')
            )
            ->groupBy(DB::raw('DAY(fecha_movimiento)'))
            ->orderBy(DB::raw('DAY(fecha_movimiento)'))
            ->get();

        return response()->json([
            'dias' => $movimientosPorDia->pluck('dia')->toArray(),
            'cantidades' => $movimientosPorDia->pluck('cantidad')->toArray(),
        ]);
    }

    /**
     * API endpoint: Obtener datos de presupuesto por categoría para gráficos
     */
    public function apiPresupuestoGrafico()
    {
        $datosPresupuesto = DB::table('presupuesto_categorias as pc')
            ->where('pc.activo', 1)
            ->select(
                'pc.nombre',
                'pc.presupuesto_anual',
                DB::raw('0 as monto_asignado')
            )
            ->get();

        return response()->json([
            'categorias' => $datosPresupuesto->pluck('nombre')->toArray(),
            'presupuestado' => $datosPresupuesto->pluck('presupuesto_anual')->toArray(),
            'asignado' => $datosPresupuesto->pluck('monto_asignado')->toArray(),
        ]);
    }
}
