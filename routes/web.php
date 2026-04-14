<?php

use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\SolicitudProcesoController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApoyoController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ReauthenticationController;
use App\Http\Controllers\PadronController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\Admin\PresupuestoController;
use App\Http\Controllers\Admin\EconomicDashboardController;
use App\Http\Controllers\Admin\DesembolsoController;
use App\Http\Controllers\Admin\ReconciliacionPresupuestariaController;
use App\Http\Controllers\Admin\CertificacionDigitalController;
use App\Http\Controllers\Admin\CertificacionReportController;
use App\Http\Controllers\Admin\VerificacionCertificadoController;
use App\Http\Controllers\Admin\ArchivadoCertificadoController;
use App\Http\Controllers\FacturaCompraController;
use App\Http\Controllers\FirmaController;
use App\Models\Beneficiario;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    $beneficiariosCount = Beneficiario::count();
    return view('welcome', ['beneficiariosCount' => $beneficiariosCount]);
});

Route::get('/dashboard', function () {
    $user = Auth::user()->loadMissing(['personal', 'beneficiario']);

    return view('dashboard-new', [
        'user' => $user,
        'tipo' => $user->tipo_usuario,
    ]);
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Foto de Perfil
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.upload-photo');
    
    // Google Vinculación
    Route::post('/profile/google-disconnect', [ProfileController::class, 'googleDisconnect'])->name('profile.google-disconnect');
    
    // Derechos ARCO
    Route::post('/profile/arco/download', [ProfileController::class, 'arcoDownload'])->name('profile.arco.download');
    Route::post('/profile/arco/cancel', [ProfileController::class, 'arcoCancel'])->name('profile.arco.cancel');
    Route::post('/profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.update-notification-preferences');
    
    // 2FA y Seguridad
    Route::post('/profile/2fa/enable', [ProfileController::class, 'enable2fa'])->name('profile.enable-2fa');
    Route::post('/profile/2fa/disable', [ProfileController::class, 'disable2fa'])->name('profile.disable-2fa');
    Route::post('/profile/logout-all-sessions', [ProfileController::class, 'logoutAllSessions'])->name('profile.logout-all-sessions');
});

Route::get('/Registrar-Solicitud', function () {
    return redirect()->route('apoyos.index');
})->middleware(['auth', 'beneficiario.profile'])->name('solicitudes.registrar');

Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])
    ->middleware(['auth', 'beneficiario.profile'])
    ->name('solicitud.guardar');

Route::get('/apoyos/{id}/solicitud', [SolicitudController::class, 'create'])
    ->middleware(['auth', 'beneficiario.profile'])
    ->name('solicitud.create');

Route::middleware('auth')->group(function () {
    Route::get('/apoyos-test', function() {
        return view('apoyos.index-simple-test', [
            'user' => auth()->user(),
            'apoyos' => (new \App\Http\Controllers\ApoyoController())->getApoyosForDebug()
        ]);
    });
    Route::get('/apoyos-direct', function() {
        $controller = app()->make(\App\Http\Controllers\ApoyoController::class);
        $user = auth()->user()->loadMissing(['personal', 'beneficiario']);
        $isBeneficiario = $user->isBeneficiario();
        
        $apoyosQuery = \Illuminate\Support\Facades\DB::table('Apoyos')
            ->select([
                'id_apoyo',
                'nombre_apoyo',
                'tipo_apoyo',
                'monto_maximo',
                'activo',
                'anio_fiscal',
                'cupo_limite',
                'fecha_inicio',
                'fecha_fin',
                'foto_ruta',
                'descripcion',
            ]);

        if ($isBeneficiario) {
            $hoy = \Illuminate\Support\Carbon::now()->toDateString();
            $apoyosQuery
                ->where('activo', 1)
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_inicio')
                        ->orWhereDate('fecha_inicio', '<=', $hoy);
                })
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_fin')
                        ->orWhereDate('fecha_fin', '>=', $hoy);
                });
        }

        $apoyos = $apoyosQuery->orderBy('id_apoyo', 'desc')->get();
        
        return view('apoyos.index-direct', compact('apoyos', 'user'));
    });
    Route::get('/apoyos-test-bare', function() {
        $user = auth()->user()->loadMissing(['personal', 'beneficiario']);
        $apoyosQuery = \Illuminate\Support\Facades\DB::table('Apoyos')
            ->select([
                'id_apoyo',
                'nombre_apoyo',
                'tipo_apoyo',
                'monto_maximo',
                'activo',
                'anio_fiscal',
                'cupo_limite',
                'fecha_inicio',
                'fecha_fin',
                'foto_ruta',
                'descripcion',
            ])
            ->where('activo', 1)
            ->orderBy('id_apoyo', 'desc')
            ->get();
        
        return view('apoyos.index-test-direct', compact('apoyos', 'user'));
    });
    Route::get('/apoyos-no-component', function() {
        // Llama al controller index pero con vista alternativa
        $controller = app()->make(\App\Http\Controllers\ApoyoController::class);
        $user = auth()->user()->loadMissing(['personal', 'beneficiario']);
        $isBeneficiario = $user->isBeneficiario();

        $apoyosQuery = \Illuminate\Support\Facades\DB::table('Apoyos')
            ->select([
                'id_apoyo',
                'nombre_apoyo',
                'tipo_apoyo',
                'monto_maximo',
                'activo',
                'anio_fiscal',
                'cupo_limite',
                'fecha_inicio',
                'fecha_fin',
                'foto_ruta',
                'descripcion',
            ]);

        if ($isBeneficiario) {
            $hoy = \Carbon\Carbon::now()->toDateString();
            $apoyosQuery
                ->where('activo', 1)
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_inicio')
                        ->orWhereDate('fecha_inicio', '<=', $hoy);
                })
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_fin')
                        ->orWhereDate('fecha_fin', '>=', $hoy);
                });
        }

        $apoyos = $apoyosQuery->orderBy('id_apoyo', 'desc')->get();
        
        return view('apoyos.index-no-component', compact('apoyos', 'user'));
    });
    Route::get('/apoyos-logs', function() {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            return response('No log file found', 404);
        }
        $content = file_get_contents($logFile);
        $lines = array_slice(explode("\n", $content), -100);
        $logs = implode("\n", $lines);
        return '<pre style="white-space: pre-wrap; word-wrap: break-word; background: #f5f5f5; padding: 15px; overflow: auto; font-size: 12px;">' . htmlspecialchars($logs) . '</pre>';
    });
    Route::get('/apoyos-component-removed', function() {
        // Same logic as ApoyoController index() but returns view without component
        $user = auth()->user()->loadMissing(['personal', 'beneficiario']);
        $isBeneficiario = $user->isBeneficiario();

        $apoyosQuery = \Illuminate\Support\Facades\DB::table('Apoyos')
            ->select([
                'id_apoyo', 'nombre_apoyo', 'tipo_apoyo', 'monto_maximo', 'activo',
                'anio_fiscal', 'cupo_limite', 'fecha_inicio', 'fecha_fin', 'foto_ruta', 'descripcion',
            ]);

        if ($isBeneficiario) {
            $hoy = \Carbon\Carbon::now()->toDateString();
            $apoyosQuery
                ->where('activo', 1)
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $hoy);
                })
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $hoy);
                });
        }

        $apoyos = $apoyosQuery->orderBy('id_apoyo', 'desc')->get();

        $tiposDocumentos = \Illuminate\Support\Facades\DB::table('Cat_TiposDocumento')
            ->select('id_tipo_doc', 'nombre_documento')
            ->orderBy('nombre_documento')
            ->get();

        $misSolicitudes = collect();
        $solicitudesRecientes = collect();

        return view('apoyos.index-component-removed', compact('apoyos', 'tiposDocumentos', 'user', 'misSolicitudes', 'solicitudesRecientes'));
    });
    Route::get('/apoyos',                  [ApoyoController::class, 'index'])->name('apoyos.index');
    Route::get('/apoyos/imagen/{path}',    [ApoyoController::class, 'image'])->where('path', '.*')->name('apoyos.image');
    Route::get('/apoyos/{id}/comentarios', [ApoyoController::class, 'comments'])->name('apoyos.comments');
    Route::post('/apoyos/{id}/comentarios', [ApoyoController::class, 'storeComment'])->name('apoyos.comments.store');
    Route::put('/apoyos/{id}/comentarios/{commentId}', [ApoyoController::class, 'updateComment'])->name('apoyos.comments.update');
    Route::delete('/apoyos/{id}/comentarios/{commentId}', [ApoyoController::class, 'destroyComment'])->name('apoyos.comments.destroy');
    Route::post('/apoyos/{id}/comentarios/{commentId}/like', [ApoyoController::class, 'toggleCommentLike'])->name('apoyos.comments.like');
    
    Route::get('/apoyos/create',           [ApoyoController::class, 'create'])->name('apoyos.create');
    Route::post('/apoyos',                 [ApoyoController::class, 'store'])->name('apoyos.store');
    Route::get('/apoyos/list',             [ApoyoController::class, 'list'])->name('apoyos.list');
    
    // Specific routes BEFORE generic {id} routes to prevent mismatching
    Route::get('/apoyos/documentos',         [ApoyoController::class, 'getTiposDocumento'])->name('apoyos.documentos.index');
    Route::post('/apoyos/documentos',         [ApoyoController::class, 'storeTipoDocumento'])->name('apoyos.documentos.store');
    Route::put('/apoyos/documentos/{id}',     [ApoyoController::class, 'updateTipoDocumento'])->name('apoyos.documentos.update');
    Route::delete('/apoyos/documentos/{id}',  [ApoyoController::class, 'deleteTipoDocumento'])->name('apoyos.documentos.destroy');
    Route::post('/apoyos/check-inventario',   [ApoyoController::class, 'checkInventario'])->name('apoyos.check-inventario');
    Route::post('/apoyos/aprobar-inventario', [ApoyoController::class, 'aprobarInventario'])->name('apoyos.aprobar-inventario');
    
    // Generic {id} routes AFTER specific routes
    Route::get('/apoyos/{id}/edit',        [ApoyoController::class, 'edit'])->name('apoyos.edit');
    Route::post('/apoyos/{id}',            [ApoyoController::class, 'update'])->name('apoyos.update');
    Route::delete('/apoyos/{id}',          [ApoyoController::class, 'destroy'])->name('apoyos.destroy');

    // Flujo de cierre y validación de solicitudes
    Route::get('/solicitudes/proceso', [SolicitudProcesoController::class, 'index'])
        ->name('solicitudes.proceso.index');
    Route::get('/solicitudes/proceso/{folio}', [SolicitudProcesoController::class, 'show'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.show');
    Route::post('/solicitudes/proceso/{folio}/firmar', [SolicitudProcesoController::class, 'firmar'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.firmar');
    Route::get('/solicitudes/{folio}/timeline', [SolicitudProcesoController::class, 'timeline'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.timeline');
    Route::post('/solicitudes/proceso/revisar-documento', [SolicitudProcesoController::class, 'revisarDocumento'])
        ->name('solicitudes.proceso.revisar-documento');
    Route::post('/solicitudes/proceso/firma-directiva', [SolicitudProcesoController::class, 'firmaDirectiva'])
        ->name('solicitudes.proceso.firma-directiva');
    Route::post('/solicitudes/proceso/cierre-financiero', [SolicitudProcesoController::class, 'cierreFinanciero'])
        ->name('solicitudes.proceso.cierre-financiero');
    Route::get('/solicitudes/padron/export', [SolicitudProcesoController::class, 'exportPadron'])
        ->name('solicitudes.padron.export');

    // ============================================================
    // FASE 8: Firma Electrónica - Rutas de firma y aprobación
    // ============================================================
    Route::prefix('solicitudes/{folio}/firma')->group(function () {
        Route::get('/', [FirmaController::class, 'show'])
            ->whereNumber('folio')
            ->name('solicitudes.firma.show');
        
        Route::post('/completar-fase-2', [FirmaController::class, 'completarFase2'])
            ->whereNumber('folio')
            ->name('solicitudes.firma.completar-fase-2');
        
        Route::post('/firmar', [FirmaController::class, 'firmar'])
            ->whereNumber('folio')
            ->name('solicitudes.firma.firmar');
        
        Route::post('/rechazar', [FirmaController::class, 'rechazar'])
            ->whereNumber('folio')
            ->name('solicitudes.firma.rechazar');
        
        Route::get('/historial', [FirmaController::class, 'historialFirmas'])
            ->whereNumber('folio')
            ->name('solicitudes.firma.historial');
    });

    // Módulo administrativo - Verificación de documentos
    Route::prefix('admin/solicitudes')->group(function () {
        Route::get('/', [DocumentVerificationController::class, 'index'])
            ->name('admin.solicitudes.index');
        Route::get('/{folio}', [DocumentVerificationController::class, 'show'])
            ->whereNumber('folio')
            ->name('admin.solicitudes.show');
        Route::post('/{id}/verify', [DocumentVerificationController::class, 'verifyDocument'])
            ->whereNumber('id')
            ->name('admin.documentos.verify');
        Route::get('/{id}/view', [DocumentVerificationController::class, 'viewDocument'])
            ->whereNumber('id')
            ->name('admin.documentos.view');
    });

    // ====================================================================
    // MÓDULO ADMINISTRATIVO - PADRÓN
    // ====================================================================
    Route::prefix('admin/padron')->group(function () {
        Route::get('', [PadronController::class, 'index'])
            ->middleware('role:2,3')
            ->name('admin.padron.index');
        
        Route::get('exportar', [PadronController::class, 'exportar'])
            ->middleware('role:2,3')
            ->name('admin.padron.exportar');
        
        Route::get('{id}', [PadronController::class, 'show'])
            ->middleware('role:2,3')
            ->whereNumber('id')
            ->name('admin.padron.show');
    });

    // ====================================================================
    // MÓDULO ADMINISTRATIVO - CALENDARIO DE GOOGLE
    // ====================================================================
    Route::prefix('admin/calendario')->group(function () {
        Route::get('', [GoogleCalendarController::class, 'mostrarConfiguracion'])
            ->middleware('role:2,3')
            ->name('admin.calendario.config');
        
        Route::get('auth', [GoogleCalendarController::class, 'redirectToGoogle'])
            ->middleware('role:2,3')
            ->name('admin.calendario.auth');
        
        Route::get('callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
            ->middleware('role:2,3')
            ->name('admin.calendario.callback');
        
        Route::post('sync', [GoogleCalendarController::class, 'sincronizar'])
            ->middleware('role:2,3')
            ->name('admin.calendario.sync');
        
        Route::post('disconnect', [GoogleCalendarController::class, 'desconectar'])
            ->middleware('role:2,3')
            ->name('admin.calendario.disconnect');
        
        Route::get('logs', [GoogleCalendarController::class, 'mostrarLogs'])
            ->middleware('role:2,3')
            ->name('admin.calendario.logs');
        
        Route::post('webhook', [GoogleCalendarController::class, 'webhookGoogleCalendar'])
            ->withoutMiddleware('auth')
            ->name('admin.calendario.webhook');
        
        Route::get('api/status', [GoogleCalendarController::class, 'apiStatus'])
            ->middleware('role:2,3')
            ->name('admin.calendario.api.status');
    });

    // ====================================================================
    // MÓDULO ADMINISTRATIVO - PRESUPUESTACIÓN
    // ====================================================================
    Route::prefix('admin/presupuesto')->group(function () {
        Route::get('', [PresupuestoController::class, 'dashboard'])
            ->middleware('role:2,3')
            ->name('admin.presupuesto.index');
        
        Route::get('dashboard', [PresupuestoController::class, 'dashboard'])
            ->middleware('role:2,3')
            ->name('admin.presupuesto.dashboard');
        
        Route::get('categoria/{id}', [PresupuestoController::class, 'showCategoria'])
            ->middleware('role:2,3')
            ->whereNumber('id')
            ->name('admin.presupuesto.categoria');
        
        Route::get('apoyo/{id}', [PresupuestoController::class, 'showApoyo'])
            ->middleware('role:2,3')
            ->whereNumber('id')
            ->name('admin.presupuesto.apoyo');
        
        Route::get('reportes', [PresupuestoController::class, 'reportes'])
            ->middleware('role:2,3')
            ->name('admin.presupuesto.reportes');
        
        Route::get('api/historial/{id_categoria}', [PresupuestoController::class, 'apiHistorial'])
            ->middleware('role:2,3')
            ->whereNumber('id_categoria')
            ->name('admin.presupuesto.api.historial');
    });

    // ====================================================================
    // MÓDULO ADMIN - DASHBOARD ECONÓMICO
    // ====================================================================
    Route::prefix('admin/dashboard')->middleware('role:2,3')->group(function () {
        Route::get('economico', [EconomicDashboardController::class, 'index'])
            ->name('admin.dashboard.economico');
        
        Route::get('api/movimientos', [EconomicDashboardController::class, 'apiMovimientosGrafico'])
            ->name('admin.api.dashboard.movimientos');
        
        Route::get('api/presupuesto', [EconomicDashboardController::class, 'apiPresupuestoGrafico'])
            ->name('admin.api.dashboard.presupuesto');
        
        // Trigger manual para verificar alertas presupuestarias (testing/admin)
        Route::post('test/alertas-presupuesto', function () {
            \Illuminate\Support\Facades\Artisan::call('alertas:presupuesto');
            return redirect()->route('admin.dashboard.economico')
                ->with('status', '✅ Alertas presupuestarias verificadas manualmente.');
        })->middleware('role:3')->name('admin.dashboard.test-alertas');
    });

    // ====================================================================
    // MÓDULO ADMIN - GESTIÓN DE FACTURAS
    // ====================================================================
    Route::prefix('admin/facturas')->middleware('role:2,3')->group(function () {
        Route::get('', [FacturaCompraController::class, 'index'])
            ->name('admin.facturas.index');
        
        Route::get('create', [FacturaCompraController::class, 'create'])
            ->name('admin.facturas.create');
        
        Route::post('', [FacturaCompraController::class, 'store'])
            ->name('admin.facturas.store');
        
        Route::get('{facturaCompra}', [FacturaCompraController::class, 'show'])
            ->name('admin.facturas.show');
        
        Route::get('{facturaCompra}/edit', [FacturaCompraController::class, 'edit'])
            ->name('admin.facturas.edit');
        
        Route::put('{facturaCompra}', [FacturaCompraController::class, 'update'])
            ->name('admin.facturas.update');
        
        Route::delete('{facturaCompra}', [FacturaCompraController::class, 'destroy'])
            ->name('admin.facturas.destroy');
    });

    // ====================================================================
    // MÓDULO ADMIN - GESTIÓN DE DESEMBOLSOS Y PAGOS (FASE 8)
    // ====================================================================
    Route::prefix('admin/desembolsos')->middleware('role:2,3')->group(function () {
        Route::get('', [DesembolsoController::class, 'index'])
            ->name('desembolsos.index');
        
        Route::get('crear', [DesembolsoController::class, 'create'])
            ->name('desembolsos.create');
        
        Route::post('', [DesembolsoController::class, 'store'])
            ->name('desembolsos.store');
        
        Route::get('reporte/periodo', [DesembolsoController::class, 'reportePeriodo'])
            ->name('desembolsos.reporte-periodo');
        
        Route::get('reporte/apoyo', [DesembolsoController::class, 'reporteApoyo'])
            ->name('desembolsos.reporte-apoyo');
        
        Route::get('{id}', [DesembolsoController::class, 'show'])
            ->whereNumber('id')
            ->name('desembolsos.show');
    });

    // APIs para desembolsos
    Route::prefix('api/desembolsos')->middleware('auth')->group(function () {
        Route::post('validar', [DesembolsoController::class, 'apiValidarPresupuesto'])
            ->name('api.desembolsos.validar');
        
        Route::get('{folio}/historial', [DesembolsoController::class, 'apiHistorialDesembolsos'])
            ->name('api.desembolsos.historial');
        
        Route::get('apoyo/{id}/ejecucion', [DesembolsoController::class, 'apiEjecucionPresupuestaria'])
            ->name('api.desembolsos.ejecucion');
    });

    // ====================================================================
    // MÓDULO ADMIN - RECONCILIACIÓN PRESUPUESTARIA (FASE 8 PARTE 3)
    // ====================================================================
    Route::prefix('admin/reconciliacion')->middleware('role:2,3')->group(function () {
        Route::get('', [ReconciliacionPresupuestariaController::class, 'index'])
            ->name('reconciliacion.index');
        
        Route::get('categorias', [ReconciliacionPresupuestariaController::class, 'reporteCategorias'])
            ->name('reconciliacion.categorias');
        
        Route::get('apoyos', [ReconciliacionPresupuestariaController::class, 'reporteApoyos'])
            ->name('reconciliacion.apoyos');
        
        Route::get('alertas', [ReconciliacionPresupuestariaController::class, 'reporteAlertas'])
            ->name('reconciliacion.alertas');
        
        Route::get('descargar', [ReconciliacionPresupuestariaController::class, 'descargar'])
            ->name('reconciliacion.descargar');
    });

    // APIs para reconciliación
    Route::prefix('api/reconciliacion')->middleware('auth')->group(function () {
        Route::get('ejecucion-global', [ReconciliacionPresupuestariaController::class, 'apiEjecucionGlobal'])
            ->name('api.reconciliacion.ejecucion-global');
        
        Route::get('categorias', [ReconciliacionPresupuestariaController::class, 'apiCategorias'])
            ->name('api.reconciliacion.categorias');
        
        Route::get('apoyos', [ReconciliacionPresupuestariaController::class, 'apiApoyos'])
            ->name('api.reconciliacion.apoyos');
        
        Route::get('discrepancias', [ReconciliacionPresupuestariaController::class, 'apiDiscrepancias'])
            ->name('api.reconciliacion.discrepancias');
        
        Route::get('alertas', [ReconciliacionPresupuestariaController::class, 'apiAlertas'])
            ->name('api.reconciliacion.alertas');
        
        Route::get('reporte', [ReconciliacionPresupuestariaController::class, 'apiReporteCompleto'])
            ->name('api.reconciliacion.reporte');
    });

    // ====================================================================
    // MÓDULO ADMIN - CERTIFICACIÓN DIGITAL DE ENTREGAS (FASE 9)
    // ====================================================================
    Route::prefix('admin/certificacion')->middleware('role:2,3')->group(function () {
        Route::get('', [CertificacionDigitalController::class, 'index'])
            ->name('certificacion.index');
        
        Route::get('listado', [CertificacionDigitalController::class, 'listado'])
            ->name('certificacion.listado');
        
        Route::get('{id}/crear', [CertificacionDigitalController::class, 'crearCertificado'])
            ->whereNumber('id')
            ->name('certificacion.crear');
        
        Route::post('{id}/crear', [CertificacionDigitalController::class, 'generarCertificado'])
            ->whereNumber('id')
            ->name('certificacion.generar');
        
        Route::get('{id}/validar', [CertificacionDigitalController::class, 'validarForm'])
            ->whereNumber('id')
            ->name('certificacion.validar-form');
        
        Route::post('{id}/validar', [CertificacionDigitalController::class, 'validar'])
            ->whereNumber('id')
            ->name('certificacion.validar');
        
        Route::get('{id}', [CertificacionDigitalController::class, 'ver'])
            ->whereNumber('id')
            ->name('certificacion.ver');
        
        Route::get('search', [CertificacionDigitalController::class, 'buscar'])
            ->name('certificacion.buscar');
    });

    // APIs para certificación digital
    Route::prefix('api/certificacion')->middleware('auth')->group(function () {
        Route::post('generar', [CertificacionDigitalController::class, 'apiGenerarCertificado'])
            ->name('api.certificacion.generar');
        
        Route::get('validar/{hash}', [CertificacionDigitalController::class, 'apiValidarCertificado'])
            ->name('api.certificacion.validar');
        
        Route::get('estadisticas', [CertificacionDigitalController::class, 'apiEstadisticas'])
            ->name('api.certificacion.estadisticas');
        
        Route::post('validacion', [CertificacionDigitalController::class, 'apiRegistrarValidacion'])
            ->name('api.certificacion.validacion');
        
        Route::get('comprobante/{id}', [CertificacionDigitalController::class, 'apiComprobante'])
            ->whereNumber('id')
            ->name('api.certificacion.comprobante');
        
        Route::get('cadena-custodia/{id}', [CertificacionDigitalController::class, 'apiCadenaCustodia'])
            ->whereNumber('id')
            ->name('api.certificacion.cadena-custodia');
    });

    // ====================================================================
    // MÓDULO ADMIN - REPORTES Y EXPORTACIÓN DE CERTIFICACIÓN (FASE 9 PARTE 2)
    // ====================================================================
    Route::prefix('admin/certificacion')->middleware('role:2,3')->group(function () {
        // Dashboard de reportes
        Route::get('reportes', [CertificacionReportController::class, 'dashboardReportes'])
            ->name('certificacion.reportes.dashboard');
        
        // Formularios de generación de reportes
        Route::get('reportes/certificados', [CertificacionReportController::class, 'formRepCertificados'])
            ->name('certificacion.reportes.form-certificados');
        
        Route::get('reportes/exportacion-masiva', [CertificacionReportController::class, 'formExportacionMasiva'])
            ->name('certificacion.reportes.exportacion-masiva');
        
        // Descargas individuales
        Route::get('{id}/pdf', [CertificacionReportController::class, 'descargarPDF'])
            ->whereNumber('id')
            ->name('certificacion.descarga.pdf');
        
        Route::get('{id}/cadena-custodia/pdf', [CertificacionReportController::class, 'descargarCadenaCustodiaPDF'])
            ->whereNumber('id')
            ->name('certificacion.descarga.cadena-custodia-pdf');
        
        // Exportaciones
        Route::get('excel/exportar', [CertificacionReportController::class, 'exportarExcel'])
            ->name('certificacion.exportar.excel');
        
        Route::post('zip/exportar', [CertificacionReportController::class, 'exportarZIP'])
            ->name('certificacion.exportar.zip');
        
        Route::get('estadisticas/pdf', [CertificacionReportController::class, 'descargarReporteEstadisticas'])
            ->name('certificacion.descarga.estadisticas-pdf');
    });

    // APIs para reportes de certificación
    Route::prefix('api/certificacion/reportes')->middleware('auth')->group(function () {
        Route::post('excel', [CertificacionReportController::class, 'apiGenerarExcel'])
            ->name('api.certificacion.reportes.excel');
        
        Route::post('zip', [CertificacionReportController::class, 'apiGenerarZIP'])
            ->name('api.certificacion.reportes.zip');
        
        Route::get('estadisticas', [CertificacionReportController::class, 'apiObtenerEstadisticas'])
            ->name('api.certificacion.reportes.estadisticas');
    });

    // ====================================================================
    // MÓDULO ADMIN - VERIFICACIÓN DIGITAL DE CERTIFICADOS (FASE 9 PARTE 3)
    // ====================================================================
    Route::prefix('admin/certificacion/verificacion')->middleware('role:2,3')->group(function () {
        // Dashboard de verificación
        Route::get('/', [VerificacionCertificadoController::class, 'dashboardVerificacion'])
            ->name('certificacion.verificacion.dashboard');
        
        // Verificación individual
        Route::get('{id}/formulario', [VerificacionCertificadoController::class, 'verificarCertificado'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.formulario');
        
        // Reporte de validación
        Route::post('{id}/validar', [VerificacionCertificadoController::class, 'generarReporteValidacion'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.generar-reporte');
        
        Route::get('{id}/reporte-validacion/pdf', [VerificacionCertificadoController::class, 'descargarReporteValidacion'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.descargar-reporte');
        
        // Auditoría detallada
        Route::get('{id}/auditoria', [VerificacionCertificadoController::class, 'auditoriaDetallada'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.auditoria');
        
        // Cumplimiento LGPDP
        Route::get('{id}/cumplimiento', [VerificacionCertificadoController::class, 'reporteCumplimiento'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.reporte-cumplimiento');
        
        Route::get('{id}/cumplimiento/pdf', [VerificacionCertificadoController::class, 'descargarReporteCumplimiento'])
            ->whereNumber('id')
            ->name('certificacion.verificacion.descargar-cumplimiento');
        
        // Validación en lote
        Route::get('lote/formulario', [VerificacionCertificadoController::class, 'formularioValidacionLote'])
            ->name('certificacion.verificacion.formulario-lote');
        
        Route::post('lote/procesar', [VerificacionCertificadoController::class, 'procesarValidacionLote'])
            ->name('certificacion.verificacion.procesar-lote');
        
        Route::post('lote/descargar', [VerificacionCertificadoController::class, 'descargarValidacionLote'])
            ->name('certificacion.verificacion.descargar-lote');
    });

    // APIs para verificación de certificados
    Route::prefix('api/certificacion/verificacion')->middleware('auth')->group(function () {
        Route::post('validar-multiples', [VerificacionCertificadoController::class, 'apiValidarMultiples'])
            ->name('api.certificacion.verificacion.validar-multiples');
        
        Route::get('estadisticas', [VerificacionCertificadoController::class, 'apiObtenerEstadisticas'])
            ->name('api.certificacion.verificacion.estadisticas');
    });

    // ====================================================================
    // MÓDULO ADMIN - ARCHIVADO Y BACKUP DE CERTIFICADOS (FASE 9 PARTE 4)
    // ====================================================================
    Route::prefix('admin/certificacion/archivado')->middleware('role:2,3')->group(function () {
        // Dashboard de archivamiento
        Route::get('/', [ArchivadoCertificadoController::class, 'dashboardArchivamiento'])
            ->name('certificacion.archivado.dashboard');
        
        // Visualización de archivos
        Route::get('{id}/ver', [ArchivadoCertificadoController::class, 'verArchivo'])
            ->whereNumber('id')
            ->name('certificacion.archivado.ver');
        
        Route::get('{id}/descargar', [ArchivadoCertificadoController::class, 'descargarArchivo'])
            ->whereNumber('id')
            ->name('certificacion.archivado.descargar');
        
        // Restauración de certificados
        Route::post('{id}/restaurar', [ArchivadoCertificadoController::class, 'restaurarCertificado'])
            ->whereNumber('id')
            ->name('certificacion.archivado.restaurar');
        
        // Historial de versiones
        Route::get('{id_historico}/versiones', [ArchivadoCertificadoController::class, 'historialVersiones'])
            ->whereNumber('id_historico')
            ->name('certificacion.archivado.versiones');
        
        // Archivamiento individual
        Route::post('{id}/archivar', [ArchivadoCertificadoController::class, 'archivarCertificado'])
            ->whereNumber('id')
            ->name('certificacion.archivado.archivar');
        
        // Archivamiento masivo
        Route::get('lote/formulario', [ArchivadoCertificadoController::class, 'formularioArchivamientoMasivo'])
            ->name('certificacion.archivado.formulario-masivo');
        
        Route::post('lote/procesar', [ArchivadoCertificadoController::class, 'procesarArchivamientoMasivo'])
            ->name('certificacion.archivado.procesar-masivo');
        
        // Gestor de archivos
        Route::get('gestor/listado', [ArchivadoCertificadoController::class, 'gestorArchivos'])
            ->name('certificacion.archivado.gestor');
    });

    // APIs para archivado de certificados
    Route::prefix('api/certificacion/archivado')->middleware('auth')->group(function () {
        Route::post('backup-masivo', [ArchivadoCertificadoController::class, 'generarBackupMasivo'])
            ->name('api.certificacion.archivado.backup-masivo');
        
        Route::get('descargar-backup', [ArchivadoCertificadoController::class, 'descargarBackupMasivo'])
            ->name('api.certificacion.archivado.descargar-backup');
        
        Route::get('estadisticas', [ArchivadoCertificadoController::class, 'apiObtenerEstadisticas'])
            ->name('api.certificacion.archivado.estadisticas');
        
        Route::post('limpiar-antiguos', [ArchivadoCertificadoController::class, 'limpiarArchivosAntiguos'])
            ->name('api.certificacion.archivado.limpiar-antiguos');
    });

    // ====================================================================
    // MÓDULO BENEFICIARIO - NOTIFICACIONES
    // ====================================================================
    Route::get('/notificaciones', [NotificacionController::class, 'index'])
        ->name('beneficiario.notificaciones.inbox');
    
    Route::post('/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarLeida'])
        ->whereNumber('id')
        ->name('beneficiario.notificaciones.marcar-leida');
    
    Route::post('/notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas'])
        ->name('beneficiario.notificaciones.marcar-todas-leidas');
    
    Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy'])
        ->whereNumber('id')
        ->name('beneficiario.notificaciones.destroy');
    
    Route::get('/notificaciones/api/conteo', [NotificacionController::class, 'conteoNoLeidas'])
        ->name('beneficiario.notificaciones.api.conteo');
});

// Validación pública de solicitudes (sin autenticación)
Route::match(['GET', 'POST'], '/validar', [SolicitudProcesoController::class, 'validarPublico'])
    ->name('solicitudes.publico.validar');

// Validación de documentos verificados vía QR (sin autenticación)
Route::get('/validacion/{token}', [DocumentVerificationController::class, 'validarPublico'])
    ->where('token', '[a-f0-9]{64}')
    ->name('admin.validacion.publico');

// Google Authentication Routes
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');

// Google Drive API Routes (Protegidas por autenticación)
Route::middleware('auth')->group(function () {
    Route::get('/api/google-drive/token', [GoogleDriveController::class, 'getToken'])->name('api.google-drive.token');
    Route::post('/api/google-drive/upload', [GoogleDriveController::class, 'upload'])->name('api.google-drive.upload');
    Route::get('/api/google-drive/files', [GoogleDriveController::class, 'list'])->name('api.google-drive.list');
    Route::delete('/api/google-drive/file/{fileId}', [GoogleDriveController::class, 'destroy'])->name('api.google-drive.destroy');
});

// Re-authentication Routes (para operaciones sensibles)
Route::middleware('auth')->group(function () {
    Route::post('/auth/reauth-verify', [ReauthenticationController::class, 'verify'])->name('auth.reauth-verify');
});

// API Reportes Presupuestarios (Protegidas por rol)
Route::middleware(['auth', 'role:2,3'])->prefix('api/reporte')->group(function () {
    Route::get('/resumen-alertas', [\App\Http\Controllers\Api\ReporteApiController::class, 'resumenAlertas'])
        ->name('api.reporte.resumen-alertas');
    
    Route::get('/tendencia-mensual/{año}', [\App\Http\Controllers\Api\ReporteApiController::class, 'tendenciaMensual'])
        ->whereNumber('año')
        ->name('api.reporte.tendencia-mensual');
    
    Route::get('/estadisticas-apoyos', [\App\Http\Controllers\Api\ReporteApiController::class, 'estadisticasApoyo'])
        ->name('api.reporte.estadisticas-apoyos');
    
    Route::get('/mensual', [\App\Http\Controllers\Api\ReporteApiController::class, 'reporteMensual'])
        ->name('api.reporte.mensual');

    // Rutas de Exportación
    Route::prefix('exportar')->group(function () {
        Route::get('/dashboard-excel', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarDashboardExcel'])
            ->name('api.reporte.exportar.dashboard-excel');
        
        Route::get('/reportes-excel', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarReportesExcel'])
            ->name('api.reporte.exportar.reportes-excel');
        
        Route::get('/dashboard-pdf', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarDashboardPdf'])
            ->name('api.reporte.exportar.dashboard-pdf');
        
        Route::get('/reportes-pdf', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarReportesPdf'])
            ->name('api.reporte.exportar.reportes-pdf');
        
        Route::get('/categoria-pdf/{id}', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarCategoriaPdf'])
            ->whereNumber('id')
            ->name('api.reporte.exportar.categoria-pdf');
    });
});

// API Notificaciones (Protegidas por autenticación, solo para beneficiarios)
Route::middleware('auth')->prefix('api/notificaciones')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'index'])
        ->name('api.notificaciones.index');
    
    Route::get('/no-leidas', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'noLeidas'])
        ->name('api.notificaciones.noLeidas');
    
    Route::get('/conteo', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'conteoNoLeidas'])
        ->name('api.notificaciones.conteo');
    
    Route::post('/{id}/marcar-leida', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'marcarLeida'])
        ->whereNumber('id')
        ->name('api.notificaciones.marcarLeida');
    
    Route::post('/marcar-todas-leidas', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'marcarTodasLeidas'])
        ->name('api.notificaciones.marcarTodasLeidas');
    
    Route::delete('/{id}', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'destroy'])
        ->whereNumber('id')
        ->name('api.notificaciones.destroy');
});

require __DIR__.'/auth.php';