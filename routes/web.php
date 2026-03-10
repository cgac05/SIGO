<?php
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::get('/Registrar-Solicitud', function () {
    // 1. Obtenemos todos los apoyos activos
    $apoyos = DB::table('Apoyos')->where('activo', 1)->get();
    
    // 2. Obtenemos todos los requisitos cruzando la información con los nombres de los documentos
    $requisitos = DB::table('Requisitos_Apoyo')
        ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
        ->get();

    // 3. Le metemos a cada apoyo su lista de documentos
    $apoyosData = $apoyos->map(function($apoyo) use ($requisitos) {
        $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
        return $apoyo;
    });

    // 4. Retornamos TU vista con la variable JSON inyectada
    return view('solicitudes.registrar', ['apoyosJson' => $apoyosData->toJson()]);

})->middleware(['auth','verified'])->name('solicitudes.registrar');

// Ruta para procesar el formulario
Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])->name('solicitud.guardar');

// Esto siempre va hasta el mero final del archivo web.php
require __DIR__.'/auth.php';