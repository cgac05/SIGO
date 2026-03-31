<?php

use App\Http\Controllers\CambioPasswordController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\SolicitudProcesoController;
use App\Http\Controllers\DocumentVerificationController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApoyoController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\Auth\GoogleAuthController;
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

Route::get('/test-personal', function () {
    return view('test-personal');
})->middleware('auth');

Route::middleware(['auth', 'forzar.cambio.password'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/personal/crear',  [PersonalController::class, 'create'])->name('personal.crear');
    Route::post('/personal/crear', [PersonalController::class, 'store'])->name('personal.store');

    Route::post('/password/forzar', [CambioPasswordController::class, 'update'])->name('password.forzar.update');
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

Route::middleware(['auth', 'forzar.cambio.password'])->group(function () {
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

// ============= CASO A - Carga Híbrida (3 Momentos) =============

// Importar controllers
use App\Http\Controllers\CasoAController;
use App\Http\Controllers\GoogleCalendarController;

// MOMENTO 1 & 2: Beneficiario Presencial + Admin Escaneo (Protegidas - Solo Admin Rol 1-2)
Route::middleware(['auth', 'role:1,2'])->prefix('admin/caso-a')->group(function () {
    // Momento 1: Crear expediente presencial
    Route::get('/momento-uno', [CasoAController::class, 'momentoUno'])
        ->name('caso-a.momento-uno');
    Route::post('/momento-uno/guardar', [CasoAController::class, 'guardarMomentoUno'])
        ->name('caso-a.momento-uno.guardar');
    Route::get('/resumen/{folio}', [CasoAController::class, 'mostrarResumenMomentoUno'])
        ->name('caso-a.resumen-uno');

    // Momento 2: Escanear documentos
    Route::get('/momento-dos', [CasoAController::class, 'momentoDos'])
        ->name('caso-a.momento-dos');
    Route::post('/momento-dos/cargar', [CasoAController::class, 'cargarDocumentoMomentoDos'])
        ->name('caso-a.momento-dos.cargar');
    Route::post('/momento-dos/confirmar', [CasoAController::class, 'confirmarCargaMomentoDos'])
        ->name('caso-a.momento-dos.confirmar');
});

// MOMENTO 3: Consulta Privada (Sin autenticación - público)
Route::get('/consulta-privada', [CasoAController::class, 'momentoTresForm'])
    ->name('caso-a.momento-tres');
Route::post('/consulta-privada/validar', [CasoAController::class, 'validarMomentoTres'])
    ->name('caso-a.momento-tres.validar');
Route::get('/consulta-privada/resumen', [CasoAController::class, 'mostrarResumenMomentoTres'])
    ->middleware('caso-a.sesion-privada')
    ->name('caso-a.momento-tres.resumen');
Route::post('/consulta-privada/logout', [CasoAController::class, 'cerrarSesionMomentoTres'])
    ->name('caso-a.momento-tres.logout');

// ============= GOOGLE CALENDAR INTEGRATION =============

// Google Calendar Authorization + Sync (Protegidas - Solo Directivos Rol 2)
Route::middleware(['auth', 'role:2'])->prefix('admin/calendario')->group(function () {
    // Configuración
    Route::get('/', [GoogleCalendarController::class, 'mostrarConfiguracion'])
        ->name('calendario.config');

    // OAuth Flow - Inicio (Protegido)
    Route::get('/auth', [GoogleCalendarController::class, 'redirectToGoogle'])
        ->name('calendario.auth');

    // Sincronización
    Route::post('/sync', [GoogleCalendarController::class, 'sincronizar'])
        ->name('calendario.sync');

    // Desconectar
    Route::post('/disconnect', [GoogleCalendarController::class, 'desconectar'])
        ->name('calendario.disconnect');

    // Logs de sincronización
    Route::get('/logs', [GoogleCalendarController::class, 'mostrarLogs'])
        ->name('calendario.logs');
});

// OAuth Callback (Sin protección - Google no mantiene sesión de SIGO)
Route::get('/admin/calendario/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])
    ->name('calendario.callback');

// API Endpoints (JSON responses)
Route::middleware(['auth', 'role:2'])->prefix('api/calendario')->group(function () {
    Route::get('/status', [GoogleCalendarController::class, 'apiStatus'])
        ->name('api.calendario.status');
});

require __DIR__.'/auth.php';