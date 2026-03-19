<?php

use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApoyoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
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

Route::get('/debug-user', function () {
    $user = Auth::user();
    if (!$user) {
        return 'No hay usuario autenticado';
    }

    $data = [
        'id_usuario' => $user->id,
        'email' => $user->email,
        'tipo_usuario' => $user->tipo_usuario,
        'isPersonal()' => $user->isPersonal(),
        'isBeneficiario()' => $user->isBeneficiario(),
        'has_personal_relation' => $user->personal ? 'SI' : 'NO',
    ];

    if ($user->personal) {
        $data['personal'] = [
            'numero_empleado' => $user->personal->numero_empleado,
            'nombre' => $user->personal->nombre,
            'fk_rol' => $user->personal->fk_rol,
        ];
    }

    return response()->json($data, 200, [], JSON_PRETTY_PRINT);
});

Route::get('/test-apoyos', function () {
    \Log::info('Ruta /test-apoyos alcanzada', ['user_id' => Auth::id()]);
    return 'Test alcanzado - Usuario: ' . Auth::user()->email;
});

Route::get('/api/apoyos-debug', function () {
    $apoyos = DB::table('Apoyos')
        ->select([
            'id_apoyo',
            'nombre_apoyo',
            'tipo_apoyo',
            'monto_maximo',
            'activo',
            'fecha_inicio as fechaInicio',
            'fecha_fin as fechafin',
            'foto_ruta',
            'descripcion',
        ])
        ->orderBy('id_apoyo', 'desc')
        ->get();

    return response()->json([
        'total' => $apoyos->count(),
        'apoyos' => $apoyos,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/apoyos',                  [ApoyoController::class, 'index'])->name('apoyos.index');
    Route::get('/apoyos/imagen/{path}',    [ApoyoController::class, 'image'])->where('path', '.*')->name('apoyos.image');
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
});

require __DIR__.'/auth.php';