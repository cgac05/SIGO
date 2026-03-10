<?php
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApoyoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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
    // 1. Obtenemos todos los apoyos activos
    $apoyos = DB::table('Apoyos')->where('activo', 1)->get();

    // Requisitos por apoyo: hacemos un join para traer también el nombre del documento.
    // Esta consulta se ejecuta en memoria (collection) tras el get(); si la base de datos
    // es grande, considerar hacer una consulta SQL que ya devuelva la estructura deseada.
    $requisitos = DB::table('Requisitos_Apoyo')
        ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
        ->select('Requisitos_Apoyo.*', 'Cat_TiposDocumento.nombre_documento')
        ->get();

    // Anidamos requisitos dentro de cada apoyo para inyectar JSON utilizable por Alpine.
    $apoyosData = $apoyos->map(function($apoyo) use ($requisitos) {
        $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
        return $apoyo;
    });

    // Retornamos la vista con `apoyosJson` ya serializado; la vista lo parsea en JS (Alpine).
    return view('solicitudes.registrar', ['apoyosJson' => $apoyosData->toJson()]);

})->middleware(['auth','verified'])->name('solicitudes.registrar');

// Ruta para procesar el formulario
Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])->name('solicitud.guardar');

// Esto siempre va hasta el mero final del archivo web.php
require __DIR__.'/auth.php';
// Ruta para el Personal (Administradores)
Route::middleware(['auth', 'verified'])->group(function () {
    //Route::get('/dashboard/admin', function () {
        return view('dashboard'); // O la vista específica de admin
    //})->name('dashboard.admin');
});

// Ruta para los Beneficiarios (Jóvenes)
Route::middleware(['auth:beneficiario'])->group(function () {
    //Route::get('/dashboard/beneficiario', function () {
        return view('dashboard'); // Crea esta vista después
    //})->name('dashboard.beneficiario');
});

require __DIR__.'/auth.php';

// Rutas para administrar apoyos (listar + crear)
// - GET  /apoyos       -> `index()` devuelve la vista HTML con la tabla
// - POST /apoyos       -> `store()` crea un nuevo apoyo (acepta formularios normales y AJAX)
// - GET  /apoyos/list  -> `list()` devuelve JSON con los apoyos (usado por AJAX para recarga)
Route::get('/apoyos', [ApoyoController::class, 'index'])->name('apoyos.index');
Route::post('/apoyos', [ApoyoController::class, 'store'])->name('apoyos.store');
Route::get('/apoyos/list', [ApoyoController::class, 'list'])->name('apoyos.list');
