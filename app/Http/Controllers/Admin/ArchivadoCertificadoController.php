<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ArchivadoCertificadoService;
use App\Models\HistoricoCierre;
use App\Models\ArchivoCertificado;
use App\Models\VersionCertificado;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ArchivadoCertificadoController extends Controller
{
    protected $archivadoService;

    public function __construct(ArchivadoCertificadoService $archivadoService)
    {
        $this->middleware(['auth', 'role:2,3']);
        $this->archivadoService = $archivadoService;
    }

    /**
     * Dashboard de archivamiento
     */
    public function dashboardArchivamiento()
    {
        $estadisticas = $this->archivadoService->generarReporteArchivamiento();

        // Últimos archivos creados
        $ultimos_archivos = ArchivoCertificado::where('activo', 1)
            ->with(['historico', 'usuarioArchivador'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Cambios recientes
        $cambios_recientes = VersionCertificado::with(['historico', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('admin.certificacion.archivado.dashboard', compact(
            'estadisticas',
            'ultimos_archivos',
            'cambios_recientes'
        ));
    }

    /**
     * Archivar certificado individual
     */
    public function archivarCertificado($id)
    {
        $desembolso = HistoricoCierre::findOrFail($id);

        $resultado = $this->archivadoService->archivarCertificado($id);

        if ($resultado['exito']) {
            return back()->with('success', 'Certificado archivado exitosamente');
        }

        return back()->with('error', $resultado['razon']);
    }

    /**
     * Página de visualización de archivo
     */
    public function verArchivo($id)
    {
        $archivo = ArchivoCertificado::with(['historico', 'usuarioArchivador'])->findOrFail($id);

        // Obtener versiones
        $versiones = $this->archivadoService->obtenerVersiones($archivo->id_historico);

        return view('admin.certificacion.archivado.visualizar-archivo', compact(
            'archivo',
            'versiones'
        ));
    }

    /**
     * Descargar archivo ZIP
     */
    public function descargarArchivo($id)
    {
        $resultado = $this->archivadoService->descargarArchivoZip($id);
        return $resultado;
    }

    /**
     * Restaurar certificado desde archivo
     */
    public function restaurarCertificado($id)
    {
        $archivo = ArchivoCertificado::findOrFail($id);

        $resultado = $this->archivadoService->restaurarCertificado($id);

        if ($resultado['exito']) {
            return response()->json([
                'exito' => true,
                'mensaje' => 'Certificado restaurado exitosamente',
                'datos' => $resultado['datos_restaurados'],
            ]);
        }

        return response()->json([
            'exito' => false,
            'mensaje' => $resultado['razon'],
        ], 500);
    }

    /**
     * Ver historial de versiones
     */
    public function historialVersiones($id_historico)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id_historico);

        $resultado = $this->archivadoService->obtenerVersiones($id_historico);

        return view('admin.certificacion.archivado.historial-versiones', compact(
            'desembolso',
            'resultado'
        ));
    }

    /**
     * Formulario de archivamiento masivo
     */
    public function formularioArchivamientoMasivo()
    {
        return view('admin.certificacion.archivado.formulario-masivo');
    }

    /**
     * Procesar archivamiento masivo
     */
    public function procesarArchivamientoMasivo(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|string|in:CERTIFICADO,VALIDADO',
        ]);

        $query = HistoricoCierre::with(['solicitud', 'usuario']);

        if ($request->fecha_inicio) {
            $query->where('fecha_entrega', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->where('fecha_entrega', '<=', $fecha_fin);
        }

        if ($request->estado) {
            $query->where('estado_certificacion', $request->estado);
        }

        $certificados = $query->paginate(50);

        $resultados = [];
        foreach ($certificados as $cert) {
            $resultado = $this->archivadoService->archivarCertificado($cert->id_historico);
            $resultados[$cert->id_historico] = $resultado['exito'];
        }

        return view('admin.certificacion.archivado.resultados-archivamiento', compact(
            'certificados',
            'resultados'
        ));
    }

    /**
     * Generar backup masivo
     */
    public function generarBackupMasivo(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:historicos_cierres,id_historico',
        ]);

        $ids = $request->ids ?? [];
        $resultado = $this->archivadoService->generarBackupMasivo($ids);

        if ($resultado['exito']) {
            return response()->json([
                'exito' => true,
                'mensaje' => "Backup generado con {$resultado['cantidad']} certificados",
                'esdescargar' => true,
                'archivo' => $resultado['nombre_archivo'],
            ]);
        }

        return response()->json([
            'exito' => false,
            'mensaje' => $resultado['razon'],
        ], 500);
    }

    /**
     * Descargar backup masivo
     */
    public function descargarBackupMasivo()
    {
        $ruta_backup = storage_path('backups');
        if (!is_dir($ruta_backup)) {
            return back()->with('error', 'No hay backups disponibles');
        }

        $archivos = glob("{$ruta_backup}/*.zip");
        if (empty($archivos)) {
            return back()->with('error', 'No hay archivos de backup');
        }

        $archivo_mas_reciente = max($archivos);
        return response()->download($archivo_mas_reciente);
    }

    /**
     * API: Obtener estadísticas de archivamiento
     */
    public function apiObtenerEstadisticas()
    {
        $resultado = $this->archivadoService->generarReporteArchivamiento();

        return response()->json([
            'exito' => $resultado['exito'],
            'datos' => $resultado['estadisticas'],
        ]);
    }

    /**
     * Limpieza automática de archivos antiguos
     */
    public function limpiarArchivosAntiguos()
    {
        $resultado = $this->archivadoService->limpiarArchivosAntiguos(365);

        return response()->json([
            'exito' => $resultado['exito'],
            'mensaje' => $resultado['razon'],
            'archivos_eliminados' => $resultado['archivos_eliminados'],
            'tamanio_liberado_mb' => $resultado['tamanio_liberado_mb'],
        ]);
    }

    /**
     * Página de gestión de archivos archivados
     */
    public function gestorArchivos()
    {
        $archivos_activos = ArchivoCertificado::where('activo', 1)
            ->with(['historico', 'usuarioArchivador'])
            ->paginate(20);

        $estadisticas = $this->archivadoService->generarReporteArchivamiento();

        return view('admin.certificacion.archivado.gestor-archivos', compact(
            'archivos_activos',
            'estadisticas'
        ));
    }
}
