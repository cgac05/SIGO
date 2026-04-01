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

    return view('dashboard', [
        'user' => $user,
        'tipo' => $user->tipo_usuario,
    ]);
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
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
    Route::get('/apoyos/{id}/edit',        [ApoyoController::class, 'edit'])->name('apoyos.edit');
    Route::post('/apoyos/{id}',            [ApoyoController::class, 'update'])->name('apoyos.update');
    Route::delete('/apoyos/{id}',          [ApoyoController::class, 'destroy'])->name('apoyos.destroy');
    Route::post('/apoyos/check-inventario',   [ApoyoController::class, 'checkInventario'])->name('apoyos.check-inventario');
    Route::post('/apoyos/aprobar-inventario', [ApoyoController::class, 'aprobarInventario'])->name('apoyos.aprobar-inventario');
    Route::post('/apoyos/documentos',         [ApoyoController::class, 'storeTipoDocumento'])->name('apoyos.documentos.store');
    Route::put('/apoyos/documentos/{id}',     [ApoyoController::class, 'updateTipoDocumento'])->name('apoyos.documentos.update');

    // Flujo de cierre y validación de solicitudes
    Route::get('/solicitudes/proceso', [SolicitudProcesoController::class, 'index'])
        ->name('solicitudes.proceso.index');
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

    // Notificaciones 
    Route::get('/notificaciones', [NotificacionController::class, 'index'])
        ->name('notificaciones.index');
    Route::get('/notificaciones/unread-count', [NotificacionController::class, 'unreadCount'])
        ->name('notificaciones.unread-count');
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida'])
        ->whereNumber('id')
        ->name('notificaciones.marcar-leida');
    Route::post('/notificaciones/marcar-todas', [NotificacionController::class, 'marcarTodasLeidas'])
        ->name('notificaciones.marcar-todas');

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

require __DIR__.'/auth.php';