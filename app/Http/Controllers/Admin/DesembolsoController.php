<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use App\Models\HistoricoCierre;
use App\Models\BDFinanzas;
use App\Services\DesembolsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DesembolsoController extends Controller
{
    protected $desembolsoService;

    public function __construct(DesembolsoService $desembolsoService)
    {
        $this->desembolsoService = $desembolsoService;
    }

    /**
     * Listar desembolsos registrados pagados
     * GET /admin/desembolsos
     */
    public function index(Request $request)
    {
        try {
            $query = HistoricoCierre::query()
                ->with(['solicitud', 'usuario'])
                ->orderBy('fecha_entrega', 'desc');

            // Filtro por fecha
            if ($request->filled('fecha_inicio')) {
                $query->where('fecha_entrega', '>=', Carbon::parse($request->fecha_inicio)->startOfDay());
            }

            if ($request->filled('fecha_fin')) {
                $query->where('fecha_entrega', '<=', Carbon::parse($request->fecha_fin)->endOfDay());
            }

            // Filtro por folio
            if ($request->filled('folio')) {
                $query->where('fk_folio', 'like', '%' . $request->folio . '%');
            }

            // Filtro por rango de monto
            if ($request->filled('monto_min')) {
                $query->where('monto_entregado', '>=', floatval($request->monto_min));
            }

            if ($request->filled('monto_max')) {
                $query->where('monto_entregado', '<=', floatval($request->monto_max));
            }

            $desembolsos = $query->paginate(20);

            // Calcular totales
            $totales = [
                'total_desembolsos' => $desembolsos->count(),
                'monto_total' => HistoricoCierre::sum('monto_entregado') ?? 0,
                'promedio_desembolso' => $desembolsos->avg('monto_entregado') ?? 0,
            ];

            return view('admin.desembolsos.index', compact('desembolsos', 'totales'));

        } catch (\Exception $e) {
            Log::error("Error listando desembolsos", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar desembolsos: ' . $e->getMessage());
        }
    }

    /**
     * Ver detalles de un desembolso
     * GET /admin/desembolsos/{id}
     */
    public function show($id)
    {
        try {
            $desembolso = HistoricoCierre::findOrFail($id);
            
            // Obtener solicitud relacionada
            $solicitud = Solicitud::where('folio', $desembolso->fk_folio)->first();
            
            // Obtener historial completo del beneficiario
            if ($solicitud) {
                $historial_solicitud = Solicitud::where('folio', $desembolso->fk_folio)
                    ->with(['estado', 'usuario', 'apoyo'])
                    ->first();
            }

            // Obtener snapshot antes del pago
            $snapshot_antes = $desembolso->snapshot_json ? json_decode($desembolso->snapshot_json, true) : null;

            return view('admin.desembolsos.show', compact('desembolso', 'solicitud', 'historial_solicitud', 'snapshot_antes'));

        } catch (\Exception $e) {
            Log::error("Error mostrando desembolso", ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Desembolso no encontrado');
        }
    }

    /**
     * Formulario para registrar nuevo desembolso
     * GET /admin/desembolsos/crear
     */
    public function create()
    {
        try {
            // Obtener solicitudes pendientes de pago (estado "Presupuesto Asignado")
            $solicitudes_pendientes = Solicitud::where('presupuesto_confirmado', 1)
                ->where('fk_id_estado', 4) // Estado "Presupuesto Confirmado"
                ->with(['beneficiario', 'apoyo'])
                ->orderBy('fecha_solicitud', 'desc')
                ->get();

            return view('admin.desembolsos.create', compact('solicitudes_pendientes'));

        } catch (\Exception $e) {
            Log::error("Error en formulario crear desembolso", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar formulario');
        }
    }

    /**
     * Registrar nuevo desembolso
     * POST /admin/desembolsos
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'folio' => 'required|string|exists:solicitudes,folio',
                'monto' => 'required|numeric|min:0.01',
                'ruta_pdf' => 'nullable|string',
                'descripcion' => 'nullable|string|max:500',
            ]);

            $usuario_id = auth()->user()->id_usuario;

            // Validar presupuesto antes de proceder
            $validacion = $this->desembolsoService->validarPresupuestoDisponible(
                $validated['folio'],
                floatval($validated['monto'])
            );

            if (!$validacion['disponible']) {
                return back()
                    ->withInput()
                    ->with('error', 'Validación fallida: ' . $validacion['razon']);
            }

            // Registrar desembolso
            $resultado = $this->desembolsoService->registrarDesembolso(
                $validated['folio'],
                floatval($validated['monto']),
                $usuario_id,
                $validated['ruta_pdf'] ?? null,
                $validated['descripcion'] ?? null
            );

            if (!$resultado['exito']) {
                return back()
                    ->withInput()
                    ->with('error', 'Error al registrar desembolso: ' . $resultado['razon']);
            }

            return redirect()
                ->route('desembolsos.show', $resultado['id_historico'])
                ->with('success', $resultado['razon']);

        } catch (\Exception $e) {
            Log::error("Error registrando desembolso", ['error' => $e->getMessage()]);
            return back()
                ->withInput()
                ->with('error', 'Error del sistema: ' . $e->getMessage());
        }
    }

    /**
     * Reporte de desembolsos por período
     * GET /admin/desembolsos/reporte/período
     */
    public function reportePeriodo(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after:fecha_inicio',
            ]);

            $fechaInicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
            $fechaFin = Carbon::parse($validated['fecha_fin'])->endOfDay();

            $desembolsos = HistoricoCierre::whereBetween('fecha_entrega', [$fechaInicio, $fechaFin])
                ->with(['usuario' => function ($query) {
                    $query->join('Personal', 'Personal.fk_id_usuario', '=', 'usuarios.id_usuario')
                        ->select(['usuarios.id_usuario', DB::raw("CONCAT(Personal.nombre, ' ', Personal.apellido_paterno) as nombre_completo")]);
                }])
                ->orderBy('fecha_entrega', 'desc')
                ->get();

            $totales = [
                'cantidad_desembolsos' => $desembolsos->count(),
                'monto_total' => $desembolsos->sum('monto_entregado'),
                'promedio' => $desembolsos->count() > 0 ? $desembolsos->avg('monto_entregado') : 0,
                'maximo' => $desembolsos->max('monto_entregado'),
                'minimo' => $desembolsos->min('monto_entregado'),
            ];

            return view('admin.desembolsos.reporte', compact('desembolsos', 'totales', 'fechaInicio', 'fechaFin'));

        } catch (\Exception $e) {
            Log::error("Error en reporte de período", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }

    /**
     * Reporte de desembolsos por apoyo
     * GET /admin/desembolsos/reporte/apoyo
     */
    public function reporteApoyo(Request $request)
    {
        try {
            $desembolsos_por_apoyo = Solicitud::select(
                'fk_id_apoyo',
                DB::raw('Count(DISTINCT folio) as cantidad_beneficiarios'),
                DB::raw('SUM(monto_entregado) as monto_total'),
                DB::raw('AVG(monto_entregado) as monto_promedio')
            )
                ->where('monto_entregado', '>', 0)
                ->groupBy('fk_id_apoyo')
                ->with(['apoyo'])
                ->get();

            // Obtener ejecución presupuestaria por apoyo
            $presupuesto_por_apoyo = BDFinanzas::get()
                ->mapWithKeys(function ($bf) {
                    return [
                        $bf->fk_id_apoyo => $this->desembolsoService->obtenerEjecucionPresupuestaria($bf->fk_id_apoyo)
                    ];
                });

            return view('admin.desembolsos.reporte-apoyo', compact('desembolsos_por_apoyo', 'presupuesto_por_apoyo'));

        } catch (\Exception $e) {
            Log::error("Error en reporte por apoyo", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }

    /**
     * API: Validar presupuesto disponible
     * POST /api/desembolsos/validar
     */
    public function apiValidarPresupuesto(Request $request)
    {
        try {
            $validated = $request->validate([
                'folio' => 'required|string',
                'monto' => 'required|numeric|min:0.01',
            ]);

            $resultado = $this->desembolsoService->validarPresupuestoDisponible(
                $validated['folio'],
                floatval($validated['monto'])
            );

            return response()->json($resultado);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener historial de desembolsos
     * GET /api/desembolsos/{folio}/historial
     */
    public function apiHistorialDesembolsos($folio)
    {
        try {
            $historial = $this->desembolsoService->obtenerHistorialDesembolsos($folio);
            return response()->json($historial);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener ejecución presupuestaria
     * GET /api/desembolsos/apoyo/{id}/ejecucion
     */
    public function apiEjecucionPresupuestaria($id_apoyo)
    {
        try {
            $ejecucion = $this->desembolsoService->obtenerEjecucionPresupuestaria($id_apoyo);
            return response()->json($ejecucion);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
