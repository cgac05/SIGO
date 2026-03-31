<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CicloPresupuestario;
use App\Models\PresupuestoApoyo;
use App\Models\PresupuestoCategoria;
use App\Models\MovimientoPresupuestario;
use App\Services\PresupuetaryControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PresupuestoController
 * 
 * Dashboard y reportes para gestión de presupuestación
 */
class PresupuestoController extends Controller
{
    private function authorizeDirectivo(): void
    {
        $user = Auth::user();
        
        // Verificar que sea usuario Personal
        if (!$user || !$user->isPersonal()) {
            abort(403, 'Solo directivos pueden acceder a presupuestación');
        }
        
        // Cargar relación personal si no está cargada
        if (!$user->relationLoaded('personal')) {
            $user->load('personal');
        }
        
        // Verificar que tenga Personal y rol 2
        $personal = $user->personal;
        if (!$personal || (int) $personal->fk_rol !== 2) {
            abort(403, 'Solo directivos (rol 2) pueden acceder a presupuestación');
        }
    }

    /**
     * Dashboard general de presupuestación
     * GET /admin/presupuesto/dashboard
     */
    public function dashboard(Request $request, PresupuetaryControlService $presupuetoService)
    {
        $this->authorizeDirectivo();

        // Ciclo fiscal actual
        $año_actual = now()->year;
        $ciclo = CicloPresupuestario::where('año_fiscal', $año_actual)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$ciclo) {
            return view('admin.presupuesto.no-ciclo');
        }

        // Categorías del ciclo con estadísticas
        $categorias = PresupuestoCategoria::where('id_ciclo', $ciclo->id_ciclo)
            ->with(['apoyos' => function ($query) {
                $query->where('estado', 'APROBADO');
            }])
            ->orderBy('nombre')
            ->get()
            ->map(function ($cat) {
                return [
                    'id_categoria' => $cat->id_categoria,
                    'nombre' => $cat->nombre,
                    'presupuesto_total' => (float) $cat->presupuesto_anual,
                    'disponible' => (float) $cat->disponible,
                    'gastado' => (float) $cat->presupuesto_anual - (float) $cat->disponible,
                    'porcentaje_utilizado' => $cat->getPorcentajeUtilizacion(),
                    'badge_color' => $cat->getBadgeColor(),
                    'estado_visual' => $this->getEstadoVisual($cat->getPorcentajeUtilizacion()),
                    'apoyos_aprobados' => $cat->apoyos->count(),
                ];
            });

        // Resumen general
        $resumen = [
            'presupuesto_total' => (float) $ciclo->presupuesto_total,
            'disponible_total' => (float) $categorias->sum('disponible'),
            'gastado_total' => (float) $categorias->sum('gastado'),
            'porcentaje_general' => $ciclo->presupuesto_total > 0 
                ? round(($categorias->sum('gastado') / $ciclo->presupuesto_total) * 100, 2)
                : 0,
            'num_categorias' => $categorias->count(),
            'num_apoyos_aprobados' => PresupuestoApoyo::where('estado', 'APROBADO')
                ->whereHas('categoria', function ($q) use ($ciclo) {
                    $q->where('id_ciclo', $ciclo->id_ciclo);
                })
                ->count(),
        ];

        // Datos para gráfico de REPARTICIÓN (presupuesto por categoría)
        $datosReparticion = $categorias->map(function ($cat) use ($ciclo) {
            return [
                'nombre' => $cat['nombre'],
                'valor' => $cat['presupuesto_total'],
                'porcentaje' => round(($cat['presupuesto_total'] / $ciclo->presupuesto_total) * 100, 1),
            ];
        })->toArray();

        // Datos para gráfico de GASTOS (ejecución por categoría)
        $gastosCategoria = $categorias->map(function ($cat) use ($ciclo) {
            $total_ciclo = $ciclo->presupuesto_total ?: 1;
            return [
                'nombre' => $cat['nombre'],
                'valor' => $cat['gastado'],
                'porcentaje' => round(($cat['gastado'] / $total_ciclo) * 100, 1),
                'disponible' => $cat['disponible'],
            ];
        })->toArray();

        return view('admin.presupuesto.dashboard', [
            'ciclo' => $ciclo,
            'categorias' => $categorias,
            'resumen' => $resumen,
            'datosReparticion' => $datosReparticion,
            'gastosCategoria' => $gastosCategoria,
            'totalGastosDetallado' => $resumen['gastado_total'],
        ]);
    }

    /**
     * Detalle de categoría
     * GET /admin/presupuesto/categorias/{id}
     */
    public function showCategoria($id_categoria)
    {
        $this->authorizeDirectivo();

        $categoria = PresupuestoCategoria::with([
            'apoyos' => function ($query) {
                $query->with('apoyo', 'directivoAprobador', 'movimientos')
                    ->orderByDesc('fecha_aprobacion');
            },
            'ciclo'
        ])->findOrFail($id_categoria);

        $apoyos = $categoria->apoyos->map(function ($presupuesto) {
            return [
                'id_presupuesto' => $presupuesto->id_presupuesto_apoyo,
                'apoyo_nombre' => $presupuesto->apoyo->nombre_apoyo ?? 'N/A',
                'estado' => $presupuesto->estado,
                'estado_badge' => $presupuesto->getBadgeColor(),
                'costo_estimado' => $presupuesto->costo_estimado,
                'costo_formato' => '$' . number_format($presupuesto->costo_estimado, 2),
                'fecha_reserva' => $presupuesto->fecha_reserva?->format('d/m/Y H:i'),
                'fecha_aprobacion' => $presupuesto->fecha_aprobacion?->format('d/m/Y H:i'),
                'directivo_aprobador' => $presupuesto->directivoAprobador?->nombre ?? 'Pendiente',
                'num_movimientos' => $presupuesto->movimientos->count(),
            ];
        });

        return view('admin.presupuesto.categoria', [
            'categoria' => $categoria,
            'apoyos' => $apoyos,
        ]);
    }

    /**
     * Detalle de apoyo presupuestario
     * GET /admin/presupuesto/apoyos/{id}
     */
    public function showApoyo($id_presupuesto_apoyo)
    {
        $this->authorizeDirectivo();

        $presupuesto = PresupuestoApoyo::with([
            'categoria',
            'apoyo',
            'directivoAprobador',
            'movimientos' => function ($q) {
                $q->with('usuarioResponsable', 'solicitud')
                    ->orderByDesc('fecha_movimiento');
            }
        ])->findOrFail($id_presupuesto_apoyo);

        $movimientos = $presupuesto->movimientos->map(function ($mov) {
            return [
                'tipo' => $mov->tipo_movimiento,
                'tipo_label' => $mov->getTipoLabel(),
                'tipo_color' => $mov->getTipoColor(),
                'tipo_icon' => $mov->getTipoIcon(),
                'monto' => $mov->monto,
                'monto_formato' => $mov->getMontoFormato(),
                'usuario' => $mov->usuarioResponsable?->nombre ?? 'Sistema',
                'solicitante' => $mov->solicitud?->fk_curp ?? 'N/A',
                'notas' => $mov->notas,
                'ip_origen' => $mov->ip_origen,
                'fecha' => $mov->fecha_movimiento->format('d/m/Y H:i:s'),
            ];
        });

        return view('admin.presupuesto.apoyo', [
            'presupuesto' => $presupuesto,
            'apoyo' => $presupuesto->apoyo,
            'categoria' => $presupuesto->categoria,
            'movimientos' => $movimientos,
        ]);
    }

    /**
     * Reporte de presupuesto
     * GET /admin/presupuesto/reportes
     */
    public function reportes(Request $request)
    {
        $this->authorizeDirectivo();

        $año = $request->input('año', now()->year);
        $ciclo = CicloPresupuestario::where('año_fiscal', $año)->first();

        if (!$ciclo) {
            return view('admin.presupuesto.reportes', [
                'año' => $año,
                'ciclo' => null,
                'data' => [],
            ]);
        }

        $categorias = PresupuestoCategoria::where('id_ciclo', $ciclo->id_ciclo)
            ->with('apoyos')
            ->orderBy('nombre')
            ->get()
            ->map(function ($cat) {
                $aprobados = $cat->apoyos->where('estado', 'APROBADO');
                $gastado = (float) $cat->presupuesto_anual - (float) $cat->disponible;

                return [
                    'nombre' => $cat->nombre,
                    'presupuesto' => $cat->presupuesto_anual,
                    'gastado' => $gastado,
                    'disponible' => $cat->disponible,
                    'porcentaje' => $cat->getPorcentajeUtilizacion(),
                    'num_apoyos' => $cat->apoyos->count(),
                    'num_aprobados' => $aprobados->count(),
                    'monto_aprobado' => $aprobados->sum('costo_estimado'),
                ];
            });

        $resumen = [
            'año' => $año,
            'presupuesto_total' => $ciclo->presupuesto_total,
            'gastado_total' => $categorias->sum('gastado'),
            'disponible_total' => $categorias->sum('disponible'),
            'porcentaje_general' => $ciclo->presupuesto_total > 0
                ? round(($categorias->sum('gastado') / $ciclo->presupuesto_total) * 100, 2)
                : 0,
        ];

        return view('admin.presupuesto.reportes', [
            'año' => $año,
            'ciclo' => $ciclo,
            'categorias' => $categorias,
            'resumen' => $resumen,
        ]);
    }

    /**
     * API: Historial de movimientos por categoría
     * GET /admin/presupuesto/api/historial/{id_categoria}
     */
    public function apiHistorial($id_categoria)
    {
        $this->authorizeDirectivo();

        $movimientos = \DB::table('movimientos_presupuestarios as mov')
            ->join('presupuesto_apoyos as pa', 'mov.id_presupuesto_apoyo', '=', 'pa.id_presupuesto_apoyo')
            ->where('pa.id_categoria', $id_categoria)
            ->select([
                'mov.id_movimiento',
                'mov.tipo_movimiento',
                'mov.monto',
                'mov.fecha_movimiento',
                'mov.notas',
            ])
            ->orderByDesc('mov.fecha_movimiento')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $movimientos,
        ]);
    }

    /**
     * Utilidad: Obtener estado visual del presupuesto
     */
    private function getEstadoVisual(float $porcentaje): string
    {
        if ($porcentaje >= 100) {
            return 'AGOTADO';
        }
        if ($porcentaje >= 85) {
            return 'CRÍTICO';
        }
        if ($porcentaje >= 70) {
            return 'ALTO';
        }
        return 'NORMAL';
    }
}
