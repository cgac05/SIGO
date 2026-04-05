<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoricoCierre;
use App\Services\CertificacionDigitalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CertificacionDigitalController extends Controller
{
    protected $certService;

    public function __construct(CertificacionDigitalService $certService)
    {
        $this->certService = $certService;
    }

    /**
     * GET /admin/certificacion
     * Dashboard de certificación digital con estadísticas
     */
    public function index()
    {
        $estadisticas = $this->certService->obtenerEstadisticas();
        $desembolsos_pendientes = HistoricoCierre::where('estado_certificacion', 'PENDIENTE')
            ->with('solicitud')
            ->paginate(10);

        return view('admin.certificacion.index', [
            'estadisticas' => $estadisticas,
            'desembolsos_pendientes' => $desembolsos_pendientes,
        ]);
    }

    /**
     * GET /admin/certificacion/{id}/generar
     * Formulario para generar certificado
     */
    public function crearCertificado($id)
    {
        $desembolso = HistoricoCierre::with('solicitud', 'usuario')->find($id);
        if (!$desembolso) {
            return redirect()->route('admin.certificacion.index')->with('error', 'Desembolso no encontrado');
        }

        if ($desembolso->estado_certificacion === 'CERTIFICADO') {
            return redirect()->route('admin.certificacion.ver', $id)
                ->with('info', 'Este desembolso ya tiene certificado');
        }

        return view('admin.certificacion.crear', [
            'desembolso' => $desembolso,
        ]);
    }

    /**
     * POST /admin/certificacion/{id}/generar
     * Generar certificado digital
     */
    public function generarCertificado(Request $request, $id)
    {
        $request->validate([
            'confirmacion' => 'required|accepted',
        ]);

        $ip_terminal = $request->ip();
        $resultado = $this->certService->generarCertificado($id, $ip_terminal);

        if ($resultado['exito']) {
            return redirect()->route('admin.certificacion.ver', $id)
                ->with('success', 'Certificado digital generado exitosamente');
        }

        return back()->with('error', $resultado['razon']);
    }

    /**
     * GET /admin/certificacion/{id}
     * Ver detalles del certificado
     */
    public function ver($id)
    {
        $desembolso = HistoricoCierre::with('solicitud', 'usuario')->find($id);
        if (!$desembolso) {
            return redirect()->route('admin.certificacion.index')->with('error', 'Desembolso no encontrado');
        }

        $cadena_custodia = $desembolso->cadena_custodia_json ?? [];

        return view('admin.certificacion.ver', [
            'desembolso' => $desembolso,
            'cadena_custodia' => $cadena_custodia,
        ]);
    }

    /**
     * GET /admin/certificacion/{id}/validar
     * Formulario para validar certificado
     */
    public function validarForm($id)
    {
        $desembolso = HistoricoCierre::find($id);
        if (!$desembolso) {
            return redirect()->route('admin.certificacion.index')->with('error', 'Desembolso no encontrado');
        }

        if ($desembolso->estado_certificacion !== 'CERTIFICADO') {
            return back()->with('error', 'El desembolso debe estar certificado primero');
        }

        return view('admin.certificacion.validar', [
            'desembolso' => $desembolso,
        ]);
    }

    /**
     * POST /admin/certificacion/{id}/validar
     * Registrar validación de certificado
     */
    public function validar(Request $request, $id)
    {
        $request->validate([
            'notas' => 'nullable|string|max:500',
        ]);

        $ip_terminal = $request->ip();
        $id_usuario = auth()->user()->id ?? 0;

        $resultado = $this->certService->registrarValidacion(
            $id,
            $id_usuario,
            $ip_terminal,
            $request->notas
        );

        if ($resultado['exito']) {
            return redirect()->route('admin.certificacion.ver', $id)
                ->with('success', 'Validación registrada en cadena de custodia');
        }

        return back()->with('error', $resultado['razon']);
    }

    /**
     * GET /admin/certificacion/listado/todos
     * Listado de todos los certificados (certificados + validados)
     */
    public function listado()
    {
        $certificados = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
            ->with('solicitud', 'usuario')
            ->orderBy('fecha_certificacion', 'desc')
            ->paginate(15);

        return view('admin.certificacion.listado', [
            'certificados' => $certificados,
        ]);
    }

    /**
     * GET /admin/certificacion/search
     * Buscar certificado por hash o folio
     */
    public function buscar(Request $request)
    {
        $query = $request->query('q');
        
        if (strlen($query) < 3) {
            return response()->json(['error' => 'Ingrese al menos 3 caracteres'], 422);
        }

        $resultados = HistoricoCierre::where(function ($q) use ($query) {
            $q->where('hash_certificado', 'like', '%' . $query . '%')
              ->orWhere('fk_folio', 'like', '%' . $query . '%');
        })->with('solicitud', 'usuario')->limit(10)->get();

        return response()->json($resultados);
    }

    // ============ API ENDPOINTS ============

    /**
     * POST /api/certificacion/generar
     * API AJAX para generar certificado sin recargar página
     */
    public function apiGenerarCertificado(Request $request)
    {
        $request->validate([
            'id_historico' => 'required|integer|exists:Historico_Cierre,id_historico',
        ]);

        $resultado = $this->certService->generarCertificado(
            $request->id_historico,
            $request->ip()
        );

        return response()->json($resultado);
    }

    /**
     * GET /api/certificacion/validar/{hash}
     * API para validar certificado por hash
     */
    public function apiValidarCertificado($hash)
    {
        $resultado = $this->certService->validarCertificado($hash);
        
        return response()->json([
            'valido' => $resultado['valido'],
            'razon' => $resultado['razon'],
            'detalles' => $resultado['detalles'],
        ]);
    }

    /**
     * GET /api/certificacion/estadisticas
     * API para obtener estadísticas en tiempo real
     */
    public function apiEstadisticas()
    {
        $estadisticas = $this->certService->obtenerEstadisticas();
        
        return response()->json($estadisticas);
    }

    /**
     * POST /api/certificacion/validacion
     * API para registrar validación
     */
    public function apiRegistrarValidacion(Request $request)
    {
        $request->validate([
            'id_historico' => 'required|integer|exists:Historico_Cierre,id_historico',
            'notas' => 'nullable|string|max:500',
        ]);

        $resultado = $this->certService->registrarValidacion(
            $request->id_historico,
            auth()->user()->id ?? 0,
            $request->ip(),
            $request->notas ?? ''
        );

        return response()->json($resultado);
    }

    /**
     * GET /api/certificacion/comprobante/{id}
     * API para obtener datos del comprobante certificado
     */
    public function apiComprobante($id)
    {
        $resultado = $this->certService->generarComprobanteCertificado($id);
        
        return response()->json($resultado);
    }

    /**
     * GET /api/certificacion/cadena-custodia/{id}
     * API para obtener cadena de custodia completa
     */
    public function apiCadenaCustodia($id)
    {
        $desembolso = HistoricoCierre::find($id);
        if (!$desembolso) {
            return response()->json(['error' => 'Desembolso no encontrado'], 404);
        }

        return response()->json([
            'id_historico' => $desembolso->id_historico,
            'hash_certificado' => $desembolso->hash_certificado,
            'folio' => $desembolso->fk_folio,
            'monto' => $desembolso->monto_entregado,
            'estado_certificacion' => $desembolso->estado_certificacion,
            'cadena_custodia' => $desembolso->cadena_custodia_json ?? [],
        ]);
    }
}
