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
    // Detectar qué guard está autenticado
    if (Auth::guard('beneficiario')->check()) {
        $user = Auth::guard('beneficiario')->user();
        return view('dashboard', ['user' => $user, 'tipo' => 'beneficiario']);
    }
    
    if (Auth::guard('web')->check()) {
        $user = Auth::guard('web')->user();
        return view('dashboard', ['user' => $user, 'tipo' => 'personal']);
    }
    
    // Si no está autenticado en ningún guard, redirigir a login
    return redirect()->route('login');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/Registrar-Solicitud', function () {
    $ahora = now();

    // 1. Solo apoyos activos cuya fecha de vigencia cubre el momento actual
    $apoyos = DB::table('Apoyos')
        ->where('activo', 1)
        ->where('fechaInicio', '<=', $ahora)
        ->where('fechafin',    '>=', $ahora)
        ->orderBy('id_apoyo', 'desc')
        ->get();

    // 2. Requisitos con nombres de documento
    $requisitos = DB::table('Requisitos_Apoyo')
        ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
        ->get();

    // 3. Inyectamos los requisitos en cada apoyo
    $apoyosData = $apoyos->map(function($apoyo) use ($requisitos) {
        $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
        return $apoyo;
    });

    return view('solicitudes.registrar', ['apoyosJson' => $apoyosData->toJson()]);

})->middleware(['auth', 'verified'])->name('solicitudes.registrar');

// Ruta para procesar el formulario
Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])->name('solicitud.guardar');

// Rutas para administrar apoyos
Route::get('/apoyos',      [ApoyoController::class, 'index'])->name('apoyos.index');
Route::post('/apoyos',     [ApoyoController::class, 'store'])->name('apoyos.store');
Route::get('/apoyos/list', [ApoyoController::class, 'list'])->name('apoyos.list');

require __DIR__.'/auth.php';