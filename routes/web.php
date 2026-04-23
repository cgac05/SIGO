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
use App\Http\Controllers\Admin\CicloPresupuestarioController;
use App\Http\Controllers\Admin\CertificacionDigitalController;
use App\Http\Controllers\Admin\CertificacionReportController;
use App\Http\Controllers\Admin\VerificacionCertificadoController;
use App\Http\Controllers\Admin\ArchivadoCertificadoController;
use App\Http\Controllers\FacturaCompraController;
use App\Http\Controllers\FirmaController;
use App\Http\Controllers\RecursosFinancierosController; // ← AGREGADO
use App\Http\Controllers\DocumentController;
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
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.upload-photo');
    Route::post('/profile/google-disconnect', [ProfileController::class, 'googleDisconnect'])->name('profile.google-disconnect');
    Route::post('/profile/arco/download', [ProfileController::class, 'arcoDownload'])->name('profile.arco.download');
    Route::post('/profile/arco/cancel', [ProfileController::class, 'arcoCancel'])->name('profile.arco.cancel');
    Route::post('/profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences'])->name('profile.update-notification-preferences');
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

    // Rutas de debug/test (existentes)
    Route::get('/apoyos-test', function() {
        return view('apoyos.index-simple-test', [
            'user' => auth()->user(),
            'apoyos' => (new \App\Http\Controllers\ApoyoController())->getApoyosForDebug()
        ]);
    });
    Route::get('/apoyos-logs', function() {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) return response('No log file found', 404);
        $content = file_get_contents($logFile);
        $lines = array_slice(explode("\n", $content), -100);
        return '<pre style="white-space:pre-wrap;background:#f5f5f5;padding:15px;font-size:12px;">' . htmlspecialchars(implode("\n", $lines)) . '</pre>';
    });
    Route::get('/debug-docs-1014', function() {
        return view('debug-docs-1014');
    });
    Route::get('/debug-ruta-exacta', function() {
        return view('debug-ruta-exacta');
    });
    Route::get('/check-1013', function() {
        $docs = \App\Models\Documento::where('fk_folio', 1013)->orderBy('id_doc', 'desc')->get();
        
        echo "=== DOCUMENTOS DEL FOLIO 1013 ===\n";
        echo str_repeat("=", 150) . "\n\n";
        
        foreach ($docs as $doc) {
            echo "ID: {$doc->id_doc} | ";
            echo "Ruta: {$doc->ruta_archivo} | ";
            echo "Origen: " . ($doc->origen_archivo ?? 'NULL') . " | ";
            echo "GoogleID: " . ($doc->google_file_id ?? 'NULL') . " | ";
            
            // Verificar existencia
            $path1 = storage_path('app/public/' . $doc->ruta_archivo);
            $path2 = public_path('storage/' . $doc->ruta_archivo);
            $exists1 = file_exists($path1);
            $exists2 = file_exists($path2);
            $storageExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->ruta_archivo);
            
            echo ($exists1 ? '✓' : '✗') . " path1 | ";
            echo ($exists2 ? '✓' : '✗') . " path2 | ";
            echo ($storageExists ? '✓' : '✗') . " Storage\n";
        }
        
        if ($docs->isEmpty()) {
            echo "No hay documentos para folio 1013\n";
        }
    });

    // ====================================================================
    // MÓDULO RECURSOS FINANCIEROS (Rol 3) ← AGREGADO
    // ====================================================================
    Route::prefix('finanzas')->group(function () {
        Route::get('/', [RecursosFinancierosController::class, 'index'])
            ->name('finanzas.panel');
        Route::get('/historial', [RecursosFinancierosController::class, 'historial'])
            ->name('finanzas.historial');
        Route::post('/cierre', [RecursosFinancierosController::class, 'cierreFinanciero'])
            ->name('finanzas.cierre');
        Route::get('/{folio}/comprobante', [RecursosFinancierosController::class, 'comprobante'])
            ->name('finanzas.comprobante');
    });

    // Apoyos
    Route::get('/apoyos',                  [ApoyoController::class, 'index'])->name('apoyos.index');
    Route::get('/apoyos/imagen/{path}',    [ApoyoController::class, 'image'])->where('path', '.*')->name('apoyos.image');
    
    // Documentos (servir con validación)
    Route::get('/documentos/descargar/{path}', [DocumentController::class, 'download'])->where('path', '.*')->name('documentos.download');
    Route::get('/documentos/ver/{path}', [DocumentController::class, 'view'])->where('path', '.*')->name('documentos.view');
    
    Route::get('/apoyos/{id}/comentarios', [ApoyoController::class, 'comments'])->name('apoyos.comments');
    Route::post('/apoyos/{id}/comentarios', [ApoyoController::class, 'storeComment'])->name('apoyos.comments.store');
    Route::put('/apoyos/{id}/comentarios/{commentId}', [ApoyoController::class, 'updateComment'])->name('apoyos.comments.update');
    Route::delete('/apoyos/{id}/comentarios/{commentId}', [ApoyoController::class, 'destroyComment'])->name('apoyos.comments.destroy');
    Route::post('/apoyos/{id}/comentarios/{commentId}/like', [ApoyoController::class, 'toggleCommentLike'])->name('apoyos.comments.like');
    Route::get('/apoyos/create',           [ApoyoController::class, 'create'])->name('apoyos.create');
    Route::post('/apoyos',                 [ApoyoController::class, 'store'])->name('apoyos.store');
    Route::get('/apoyos/list',             [ApoyoController::class, 'list'])->name('apoyos.list');
    Route::get('/apoyos/documentos',         [ApoyoController::class, 'getTiposDocumento'])->name('apoyos.documentos.index');
    Route::post('/apoyos/documentos',         [ApoyoController::class, 'storeTipoDocumento'])->name('apoyos.documentos.store');
    Route::put('/apoyos/documentos/{id}',     [ApoyoController::class, 'updateTipoDocumento'])->name('apoyos.documentos.update');
    Route::delete('/apoyos/documentos/{id}',  [ApoyoController::class, 'deleteTipoDocumento'])->name('apoyos.documentos.destroy');
    Route::post('/apoyos/check-inventario',   [ApoyoController::class, 'checkInventario'])->name('apoyos.check-inventario');
    Route::post('/apoyos/aprobar-inventario', [ApoyoController::class, 'aprobarInventario'])->name('apoyos.aprobar-inventario');
    Route::get('/apoyos/{id}/edit',        [ApoyoController::class, 'edit'])->name('apoyos.edit');
    Route::put('/apoyos/{id}',             [ApoyoController::class, 'update'])->name('apoyos.update');
    Route::delete('/apoyos/{id}',          [ApoyoController::class, 'destroy'])->name('apoyos.destroy');
    Route::delete('/apoyos/{id}',          [ApoyoController::class, 'destroy'])->name('apoyos.destroy');

    // Flujo de solicitudes
    Route::get('/solicitudes/proceso', [SolicitudProcesoController::class, 'index'])
        ->name('solicitudes.proceso.index');
    Route::get('/solicitudes/proceso/{folio}', [SolicitudProcesoController::class, 'show'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.show');
    Route::post('/solicitudes/proceso/{folio}/firmar', [SolicitudProcesoController::class, 'firmar'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.firmar');
    Route::post('/solicitudes/proceso/{folio}/rechazar', [SolicitudProcesoController::class, 'rechazar'])
        ->whereNumber('folio')
        ->name('solicitudes.proceso.rechazar');
    Route::get('/solicitudes/{folio}/timeline', [SolicitudProcesoController::class, 'timeline'])
        ->whereNumber('folio')->name('solicitudes.proceso.timeline');
    Route::post('/solicitudes/proceso/revisar-documento', [SolicitudProcesoController::class, 'revisarDocumento'])
        ->name('solicitudes.proceso.revisar-documento');
    Route::post('/solicitudes/proceso/firma-directiva', [SolicitudProcesoController::class, 'firmaDirectiva'])
        ->name('solicitudes.proceso.firma-directiva');
    Route::post('/solicitudes/proceso/cierre-financiero', [SolicitudProcesoController::class, 'cierreFinanciero'])
        ->name('solicitudes.proceso.cierre-financiero');
    Route::get('/solicitudes/padron/export', [SolicitudProcesoController::class, 'exportPadron'])
        ->name('solicitudes.padron.export');

    // Firma Electrónica
    Route::prefix('solicitudes/{folio}/firma')->group(function () {
        Route::get('/', [FirmaController::class, 'show'])->whereNumber('folio')->name('solicitudes.firma.show');
        Route::post('/completar-fase-2', [FirmaController::class, 'completarFase2'])->whereNumber('folio')->name('solicitudes.firma.completar-fase-2');
        Route::post('/firmar', [FirmaController::class, 'firmar'])->whereNumber('folio')->name('solicitudes.firma.firmar');
        Route::post('/rechazar', [FirmaController::class, 'rechazar'])->whereNumber('folio')->name('solicitudes.firma.rechazar');
        Route::get('/historial', [FirmaController::class, 'historialFirmas'])->whereNumber('folio')->name('solicitudes.firma.historial');
    });

    // Verificación de documentos
    Route::prefix('admin/solicitudes')->group(function () {
        Route::get('/', [DocumentVerificationController::class, 'index'])->name('admin.solicitudes.index');
        Route::get('/{folio}', [DocumentVerificationController::class, 'show'])->whereNumber('folio')->name('admin.solicitudes.show');
        Route::post('/{id}/verify', [DocumentVerificationController::class, 'verifyDocument'])->whereNumber('id')->name('admin.documentos.verify');
        Route::get('/{id}/view', [DocumentVerificationController::class, 'viewDocument'])->whereNumber('id')->name('admin.documentos.view');
    });

    // Padrón
    Route::prefix('admin/padron')->group(function () {
        Route::get('', [PadronController::class, 'index'])->middleware('role:2,3')->name('admin.padron.index');
        Route::get('exportar', [PadronController::class, 'exportar'])->middleware('role:2,3')->name('admin.padron.exportar');
        Route::get('{id}', [PadronController::class, 'show'])->middleware('role:2,3')->whereNumber('id')->name('admin.padron.show');
    });

    // Calendario Google
    Route::prefix('admin/calendario')->group(function () {
        Route::get('', [GoogleCalendarController::class, 'mostrarConfiguracion'])->middleware('role:2,3')->name('admin.calendario.config');
        Route::get('auth', [GoogleCalendarController::class, 'redirectToGoogle'])->middleware('role:2,3')->name('admin.calendario.auth');
        Route::get('callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->middleware('role:2,3')->name('admin.calendario.callback');
        Route::post('sync', [GoogleCalendarController::class, 'sincronizar'])->middleware('role:2,3')->name('admin.calendario.sync');
        Route::post('disconnect', [GoogleCalendarController::class, 'desconectar'])->middleware('role:2,3')->name('admin.calendario.disconnect');
        Route::get('logs', [GoogleCalendarController::class, 'mostrarLogs'])->middleware('role:2,3')->name('admin.calendario.logs');
        Route::post('webhook', [GoogleCalendarController::class, 'webhookGoogleCalendar'])->withoutMiddleware('auth')->name('admin.calendario.webhook');
        Route::get('api/status', [GoogleCalendarController::class, 'apiStatus'])->middleware('role:2,3')->name('admin.calendario.api.status');
    });

    // Presupuestación
    Route::prefix('admin/presupuesto')->group(function () {
        Route::get('', [PresupuestoController::class, 'dashboard'])->middleware('role:2,3')->name('admin.presupuesto.index');
        Route::get('dashboard', [PresupuestoController::class, 'dashboard'])->middleware('role:2,3')->name('admin.presupuesto.dashboard');
        Route::get('categoria/{id}', [PresupuestoController::class, 'showCategoria'])->middleware('role:2,3')->whereNumber('id')->name('admin.presupuesto.categoria');
        Route::get('apoyo/{id}', [PresupuestoController::class, 'showApoyo'])->middleware('role:2,3')->whereNumber('id')->name('admin.presupuesto.apoyo');
        Route::get('reportes', [PresupuestoController::class, 'reportes'])->middleware('role:2,3')->name('admin.presupuesto.reportes');
        Route::get('api/historial/{id_categoria}', [PresupuestoController::class, 'apiHistorial'])->middleware('role:2,3')->whereNumber('id_categoria')->name('admin.presupuesto.api.historial');
    });

    // Gestión de Ciclos Presupuestarios
    Route::prefix('admin/ciclos')->middleware('role:2,3')->group(function () {
        Route::get('', [CicloPresupuestarioController::class, 'index'])->name('admin.ciclos.index');
        Route::get('crear', [CicloPresupuestarioController::class, 'create'])->name('admin.ciclos.create');
        Route::post('', [CicloPresupuestarioController::class, 'store'])->name('admin.ciclos.store');
        Route::get('{id}', [CicloPresupuestarioController::class, 'show'])->name('admin.ciclos.show');
        Route::get('{id}/editar', [CicloPresupuestarioController::class, 'edit'])->name('admin.ciclos.edit');
        Route::put('{id}', [CicloPresupuestarioController::class, 'update'])->name('admin.ciclos.update');
        Route::patch('{id}/cerrar', [CicloPresupuestarioController::class, 'cerrar'])->name('admin.ciclos.cerrar');
        Route::patch('{id}/reabrir', [CicloPresupuestarioController::class, 'reabrir'])->name('admin.ciclos.reabrir');
        Route::post('{id}/categorias', [CicloPresupuestarioController::class, 'storeCategoria'])->name('admin.ciclos.storeCategoria');
        Route::put('categorias/{categoriaId}', [CicloPresupuestarioController::class, 'updateCategoria'])->name('admin.ciclos.updateCategoria');
        Route::delete('categorias/{categoriaId}', [CicloPresupuestarioController::class, 'deleteCategoria'])->name('admin.ciclos.deleteCategoria');
    });

    // Dashboard Económico
    Route::prefix('admin/dashboard')->middleware('role:2,3')->group(function () {
        Route::get('economico', [EconomicDashboardController::class, 'index'])->name('admin.dashboard.economico');
        Route::get('api/movimientos', [EconomicDashboardController::class, 'apiMovimientosGrafico'])->name('admin.api.dashboard.movimientos');
        Route::get('api/presupuesto', [EconomicDashboardController::class, 'apiPresupuestoGrafico'])->name('admin.api.dashboard.presupuesto');
        Route::post('test/alertas-presupuesto', function () {
            \Illuminate\Support\Facades\Artisan::call('alertas:presupuesto');
            return redirect()->route('admin.dashboard.economico')->with('status', '✅ Alertas presupuestarias verificadas manualmente.');
        })->middleware('role:2,3')->name('admin.dashboard.test-alertas');
    });

    // Facturas
    Route::prefix('admin/facturas')->middleware('role:2,3')->group(function () {
        Route::get('', [FacturaCompraController::class, 'index'])->name('admin.facturas.index');
        Route::get('create', [FacturaCompraController::class, 'create'])->name('admin.facturas.create');
        Route::post('', [FacturaCompraController::class, 'store'])->name('admin.facturas.store');
        Route::get('{facturaCompra}', [FacturaCompraController::class, 'show'])->name('admin.facturas.show');
        Route::get('{facturaCompra}/edit', [FacturaCompraController::class, 'edit'])->name('admin.facturas.edit');
        Route::put('{facturaCompra}', [FacturaCompraController::class, 'update'])->name('admin.facturas.update');
        Route::delete('{facturaCompra}', [FacturaCompraController::class, 'destroy'])->name('admin.facturas.destroy');
    });

    // Desembolsos
    Route::prefix('admin/desembolsos')->middleware('role:2,3')->group(function () {
        Route::get('', [DesembolsoController::class, 'index'])->name('desembolsos.index');
        Route::get('crear', [DesembolsoController::class, 'create'])->name('desembolsos.create');
        Route::post('', [DesembolsoController::class, 'store'])->name('desembolsos.store');
        Route::get('reporte/periodo', [DesembolsoController::class, 'reportePeriodo'])->name('desembolsos.reporte-periodo');
        Route::get('reporte/apoyo', [DesembolsoController::class, 'reporteApoyo'])->name('desembolsos.reporte-apoyo');
        Route::get('{id}', [DesembolsoController::class, 'show'])->whereNumber('id')->name('desembolsos.show');
    });

    Route::prefix('api/desembolsos')->group(function () {
        Route::post('validar', [DesembolsoController::class, 'apiValidarPresupuesto'])->name('api.desembolsos.validar');
        Route::get('{folio}/historial', [DesembolsoController::class, 'apiHistorialDesembolsos'])->name('api.desembolsos.historial');
        Route::get('apoyo/{id}/ejecucion', [DesembolsoController::class, 'apiEjecucionPresupuestaria'])->name('api.desembolsos.ejecucion');
    });

    // Reconciliación
    Route::prefix('admin/reconciliacion')->middleware('role:2,3')->group(function () {
        Route::get('', [ReconciliacionPresupuestariaController::class, 'index'])->name('reconciliacion.index');
        Route::get('categorias', [ReconciliacionPresupuestariaController::class, 'reporteCategorias'])->name('reconciliacion.categorias');
        Route::get('apoyos', [ReconciliacionPresupuestariaController::class, 'reporteApoyos'])->name('reconciliacion.apoyos');
        Route::get('alertas', [ReconciliacionPresupuestariaController::class, 'reporteAlertas'])->name('reconciliacion.alertas');
        Route::get('descargar', [ReconciliacionPresupuestariaController::class, 'descargar'])->name('reconciliacion.descargar');
    });

    Route::prefix('api/reconciliacion')->group(function () {
        Route::get('ejecucion-global', [ReconciliacionPresupuestariaController::class, 'apiEjecucionGlobal'])->name('api.reconciliacion.ejecucion-global');
        Route::get('categorias', [ReconciliacionPresupuestariaController::class, 'apiCategorias'])->name('api.reconciliacion.categorias');
        Route::get('apoyos', [ReconciliacionPresupuestariaController::class, 'apiApoyos'])->name('api.reconciliacion.apoyos');
        Route::get('discrepancias', [ReconciliacionPresupuestariaController::class, 'apiDiscrepancias'])->name('api.reconciliacion.discrepancias');
        Route::get('alertas', [ReconciliacionPresupuestariaController::class, 'apiAlertas'])->name('api.reconciliacion.alertas');
        Route::get('reporte', [ReconciliacionPresupuestariaController::class, 'apiReporteCompleto'])->name('api.reconciliacion.reporte');
    });

    // Certificación Digital
    Route::prefix('admin/certificacion')->middleware('role:2,3')->group(function () {
        Route::get('', [CertificacionDigitalController::class, 'index'])->name('certificacion.index');
        Route::get('listado', [CertificacionDigitalController::class, 'listado'])->name('certificacion.listado');
        Route::get('search', [CertificacionDigitalController::class, 'buscar'])->name('certificacion.buscar');
        Route::get('reportes', [CertificacionReportController::class, 'dashboardReportes'])->name('certificacion.reportes.dashboard');
        Route::get('reportes/certificados', [CertificacionReportController::class, 'formRepCertificados'])->name('certificacion.reportes.form-certificados');
        Route::get('reportes/exportacion-masiva', [CertificacionReportController::class, 'formExportacionMasiva'])->name('certificacion.reportes.exportacion-masiva');
        Route::get('excel/exportar', [CertificacionReportController::class, 'exportarExcel'])->name('certificacion.exportar.excel');
        Route::post('zip/exportar', [CertificacionReportController::class, 'exportarZIP'])->name('certificacion.exportar.zip');
        Route::get('estadisticas/pdf', [CertificacionReportController::class, 'descargarReporteEstadisticas'])->name('certificacion.descarga.estadisticas-pdf');
        Route::get('{id}/crear', [CertificacionDigitalController::class, 'crearCertificado'])->whereNumber('id')->name('certificacion.crear');
        Route::post('{id}/crear', [CertificacionDigitalController::class, 'generarCertificado'])->whereNumber('id')->name('certificacion.generar');
        Route::get('{id}/validar', [CertificacionDigitalController::class, 'validarForm'])->whereNumber('id')->name('certificacion.validar-form');
        Route::post('{id}/validar', [CertificacionDigitalController::class, 'validar'])->whereNumber('id')->name('certificacion.validar');
        Route::get('{id}/pdf', [CertificacionReportController::class, 'descargarPDF'])->whereNumber('id')->name('certificacion.descarga.pdf');
        Route::get('{id}/cadena-custodia/pdf', [CertificacionReportController::class, 'descargarCadenaCustodiaPDF'])->whereNumber('id')->name('certificacion.descarga.cadena-custodia-pdf');
        Route::get('{id}', [CertificacionDigitalController::class, 'ver'])->whereNumber('id')->name('certificacion.ver');
    });

    Route::prefix('api/certificacion')->group(function () {
        Route::post('generar', [CertificacionDigitalController::class, 'apiGenerarCertificado'])->name('api.certificacion.generar');
        Route::get('validar/{hash}', [CertificacionDigitalController::class, 'apiValidarCertificado'])->name('api.certificacion.validar');
        Route::get('estadisticas', [CertificacionDigitalController::class, 'apiEstadisticas'])->name('api.certificacion.estadisticas');
        Route::post('validacion', [CertificacionDigitalController::class, 'apiRegistrarValidacion'])->name('api.certificacion.validacion');
        Route::get('comprobante/{id}', [CertificacionDigitalController::class, 'apiComprobante'])->whereNumber('id')->name('api.certificacion.comprobante');
        Route::get('cadena-custodia/{id}', [CertificacionDigitalController::class, 'apiCadenaCustodia'])->whereNumber('id')->name('api.certificacion.cadena-custodia');
        Route::post('reportes/excel', [CertificacionReportController::class, 'apiGenerarExcel'])->name('api.certificacion.reportes.excel');
        Route::post('reportes/zip', [CertificacionReportController::class, 'apiGenerarZIP'])->name('api.certificacion.reportes.zip');
        Route::get('reportes/estadisticas', [CertificacionReportController::class, 'apiObtenerEstadisticas'])->name('api.certificacion.reportes.estadisticas');
    });

    // Verificación de Certificados
    Route::prefix('admin/certificacion/verificacion')->middleware('role:2,3')->group(function () {
        Route::get('/', [VerificacionCertificadoController::class, 'dashboardVerificacion'])->name('certificacion.verificacion.dashboard');
        Route::get('{id}/formulario', [VerificacionCertificadoController::class, 'verificarCertificado'])->whereNumber('id')->name('certificacion.verificacion.formulario');
        Route::post('{id}/validar', [VerificacionCertificadoController::class, 'generarReporteValidacion'])->whereNumber('id')->name('certificacion.verificacion.generar-reporte');
        Route::get('{id}/reporte-validacion/pdf', [VerificacionCertificadoController::class, 'descargarReporteValidacion'])->whereNumber('id')->name('certificacion.verificacion.descargar-reporte');
        Route::get('{id}/auditoria', [VerificacionCertificadoController::class, 'auditoriaDetallada'])->whereNumber('id')->name('certificacion.verificacion.auditoria');
        Route::get('{id}/cumplimiento', [VerificacionCertificadoController::class, 'reporteCumplimiento'])->whereNumber('id')->name('certificacion.verificacion.reporte-cumplimiento');
        Route::get('{id}/cumplimiento/pdf', [VerificacionCertificadoController::class, 'descargarReporteCumplimiento'])->whereNumber('id')->name('certificacion.verificacion.descargar-cumplimiento');
        Route::get('lote/formulario', [VerificacionCertificadoController::class, 'formularioValidacionLote'])->name('certificacion.verificacion.formulario-lote');
        Route::post('lote/procesar', [VerificacionCertificadoController::class, 'procesarValidacionLote'])->name('certificacion.verificacion.procesar-lote');
        Route::post('lote/descargar', [VerificacionCertificadoController::class, 'descargarValidacionLote'])->name('certificacion.verificacion.descargar-lote');
    });

    Route::prefix('api/certificacion/verificacion')->group(function () {
        Route::post('validar-multiples', [VerificacionCertificadoController::class, 'apiValidarMultiples'])->name('api.certificacion.verificacion.validar-multiples');
        Route::get('estadisticas', [VerificacionCertificadoController::class, 'apiObtenerEstadisticas'])->name('api.certificacion.verificacion.estadisticas');
    });

    // Archivado de Certificados
    Route::prefix('admin/certificacion/archivado')->middleware('role:2,3')->group(function () {
        Route::get('/', [ArchivadoCertificadoController::class, 'dashboardArchivamiento'])->name('certificacion.archivado.dashboard');
        Route::get('lote/formulario', [ArchivadoCertificadoController::class, 'formularioArchivamientoMasivo'])->name('certificacion.archivado.formulario-masivo');
        Route::post('lote/procesar', [ArchivadoCertificadoController::class, 'procesarArchivamientoMasivo'])->name('certificacion.archivado.procesar-masivo');
        Route::get('gestor/listado', [ArchivadoCertificadoController::class, 'gestorArchivos'])->name('certificacion.archivado.gestor');
        Route::get('{id}/ver', [ArchivadoCertificadoController::class, 'verArchivo'])->whereNumber('id')->name('certificacion.archivado.ver');
        Route::get('{id}/descargar', [ArchivadoCertificadoController::class, 'descargarArchivo'])->whereNumber('id')->name('certificacion.archivado.descargar');
        Route::post('{id}/restaurar', [ArchivadoCertificadoController::class, 'restaurarCertificado'])->whereNumber('id')->name('certificacion.archivado.restaurar');
        Route::get('{id_historico}/versiones', [ArchivadoCertificadoController::class, 'historialVersiones'])->whereNumber('id_historico')->name('certificacion.archivado.versiones');
        Route::post('{id}/archivar', [ArchivadoCertificadoController::class, 'archivarCertificado'])->whereNumber('id')->name('certificacion.archivado.archivar');
    });

    Route::prefix('api/certificacion/archivado')->group(function () {
        Route::post('backup-masivo', [ArchivadoCertificadoController::class, 'generarBackupMasivo'])->name('api.certificacion.archivado.backup-masivo');
        Route::get('descargar-backup', [ArchivadoCertificadoController::class, 'descargarBackupMasivo'])->name('api.certificacion.archivado.descargar-backup');
        Route::get('estadisticas', [ArchivadoCertificadoController::class, 'apiObtenerEstadisticas'])->name('api.certificacion.archivado.estadisticas');
        Route::post('limpiar-antiguos', [ArchivadoCertificadoController::class, 'limpiarArchivosAntiguos'])->name('api.certificacion.archivado.limpiar-antiguos');
    });

    // Notificaciones
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('beneficiario.notificaciones.inbox');
    Route::post('/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarLeida'])->whereNumber('id')->name('beneficiario.notificaciones.marcar-leida');
    Route::post('/notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas'])->name('beneficiario.notificaciones.marcar-todas-leidas');
    Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy'])->whereNumber('id')->name('beneficiario.notificaciones.destroy');
    Route::get('/notificaciones/api/conteo', [NotificacionController::class, 'conteoNoLeidas'])->name('beneficiario.notificaciones.api.conteo');
});

// Validación pública
Route::match(['GET', 'POST'], '/validar', [SolicitudProcesoController::class, 'validarPublico'])
    ->name('solicitudes.publico.validar');

Route::get('/validacion/{token}', [DocumentVerificationController::class, 'validarPublico'])
    ->where('token', '[a-f0-9]{64}')
    ->name('admin.validacion.publico');

// Google Auth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
Route::post('/logout', [GoogleAuthController::class, 'logout'])->middleware('auth')->name('logout');

// Google Drive
Route::middleware('auth')->group(function () {
    Route::get('/api/google-drive/token', [GoogleDriveController::class, 'getToken'])->name('api.google-drive.token');
    Route::post('/api/google-drive/upload', [GoogleDriveController::class, 'upload'])->name('api.google-drive.upload');
    Route::get('/api/google-drive/files', [GoogleDriveController::class, 'list'])->name('api.google-drive.list');
    Route::delete('/api/google-drive/file/{fileId}', [GoogleDriveController::class, 'destroy'])->name('api.google-drive.destroy');
});

// Re-authentication
Route::middleware('auth')->group(function () {
    Route::post('/auth/reauth-verify', [ReauthenticationController::class, 'verify'])->name('auth.reauth-verify');
});

// API Reportes Presupuestarios
Route::middleware(['auth', 'role:2,3'])->prefix('api/reporte')->group(function () {
    Route::get('/resumen-alertas', [\App\Http\Controllers\Api\ReporteApiController::class, 'resumenAlertas'])->name('api.reporte.resumen-alertas');
    Route::get('/tendencia-mensual/{año}', [\App\Http\Controllers\Api\ReporteApiController::class, 'tendenciaMensual'])->whereNumber('año')->name('api.reporte.tendencia-mensual');
    Route::get('/estadisticas-apoyos', [\App\Http\Controllers\Api\ReporteApiController::class, 'estadisticasApoyo'])->name('api.reporte.estadisticas-apoyos');
    Route::get('/mensual', [\App\Http\Controllers\Api\ReporteApiController::class, 'reporteMensual'])->name('api.reporte.mensual');
    Route::prefix('exportar')->group(function () {
        Route::get('/dashboard-excel', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarDashboardExcel'])->name('api.reporte.exportar.dashboard-excel');
        Route::get('/reportes-excel', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarReportesExcel'])->name('api.reporte.exportar.reportes-excel');
        Route::get('/dashboard-pdf', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarDashboardPdf'])->name('api.reporte.exportar.dashboard-pdf');
        Route::get('/reportes-pdf', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarReportesPdf'])->name('api.reporte.exportar.reportes-pdf');
        Route::get('/categoria-pdf/{id}', [\App\Http\Controllers\Api\ReporteApiController::class, 'exportarCategoriaPdf'])->whereNumber('id')->name('api.reporte.exportar.categoria-pdf');
    });
});

// API Notificaciones
Route::middleware('auth')->prefix('api/notificaciones')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'index'])->name('api.notificaciones.index');
    Route::get('/no-leidas', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'noLeidas'])->name('api.notificaciones.noLeidas');
    Route::get('/conteo', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'conteoNoLeidas'])->name('api.notificaciones.conteo');
    Route::post('/{id}/marcar-leida', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'marcarLeida'])->whereNumber('id')->name('api.notificaciones.marcarLeida');
    Route::post('/marcar-todas-leidas', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'marcarTodasLeidas'])->name('api.notificaciones.marcarTodasLeidas');
    Route::delete('/{id}', [\App\Http\Controllers\Api\NotificacionesApiController::class, 'destroy'])->whereNumber('id')->name('api.notificaciones.destroy');
});

// CASO A: Carga Híbrida Asincrónica (Público + Admin)
// Momento 3: Consulta privada (PÚBLICA - sin autenticación)
Route::get('/consulta-privada', [\App\Http\Controllers\CasoAController::class, 'momentoTresForm'])
    ->name('caso-a.momento-tres-form');

// Acceso directo vía QR (folio + clave como parámetros GET)
// Usado por código QR en resumen para acceso directo sin formulario
Route::get('/consulta-privada/acceso-qr', [\App\Http\Controllers\CasoAController::class, 'accesoDirectoQr'])
    ->name('caso-a.acceso-qr');

Route::post('/consulta-privada/validar', [\App\Http\Controllers\CasoAController::class, 'validarMomentoTres'])
    ->name('caso-a.validar-momento-tres');

Route::get('/consulta-privada/resumen', [\App\Http\Controllers\CasoAController::class, 'mostrarResumenMomentoTres'])
    ->name('caso-a.resumen-momento-tres');

// Caso A: Admin Routes (Momento 1 y 2)
Route::middleware(['auth', 'role:1,2'])->group(function () {
    Route::prefix('admin/caso-a')->name('admin.caso-a.')->group(function () {
        // MOMENTO 1: Crear expediente presencial
        Route::get('/momento-uno', [\App\Http\Controllers\CasoAController::class, 'momentoUno'])
            ->name('momento-uno');
        
        Route::post('/momento-uno/guardar', [\App\Http\Controllers\CasoAController::class, 'guardarMomentoUno'])
            ->name('guardar-momento-uno');

        Route::get('/resumen/{folio}', [\App\Http\Controllers\CasoAController::class, 'mostrarResumenMomentoUno'])
            ->name('resumen-momento-uno');

        // MOMENTO 2: Escaneo de documentos
        Route::get('/momento-dos', [\App\Http\Controllers\CasoAController::class, 'momentoDos'])
            ->name('momento-dos');

        Route::post('/momento-dos/cargar', [\App\Http\Controllers\CasoAController::class, 'cargarDocumentoMomentoDos'])
            ->name('cargar-documento-momento-dos');

        Route::post('/momento-dos/confirmar', [\App\Http\Controllers\CasoAController::class, 'confirmarCargaMomentoDos'])
            ->name('confirmar-carga-momento-dos');
    });
});

// API: Búsqueda de beneficiarios (pública para autocomplete)
Route::get('/api/beneficiarios/buscar', function (\Illuminate\Http\Request $request) {
    $query = $request->input('q', '');
    
    if (strlen($query) < 2) {
        return response()->json([]);
    }

    $beneficiarios = \App\Models\Beneficiario::where('nombre', 'LIKE', "%$query%")
        ->orWhere('curp', 'LIKE', "%$query%")
        ->limit(10)
        ->get(['id_beneficiario', 'nombre', 'curp', 'email']);

    return response()->json($beneficiarios);
})->middleware('auth')->name('api.beneficiarios.buscar');

// API: Datos del folio para Momento 2 (dinámico)
Route::middleware(['auth', 'role:1,2'])->group(function () {
    Route::get('/api/caso-a/folio/{folio}', [\App\Http\Controllers\CasoAController::class, 'obtenerDatosDelFolio'])
        ->name('api.caso-a.datos-folio');

    // API: Folios pendientes de escaneo
    Route::get('/api/caso-a/pendientes-escaneo', [\App\Http\Controllers\CasoAController::class, 'obtenerPendientesEscaneo'])
        ->name('api.caso-a.pendientes-escaneo');
});

require __DIR__.'/auth.php';