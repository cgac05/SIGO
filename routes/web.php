<?php
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ApoyoController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (Auth::guard('beneficiario')->check()) {
        $user = Auth::guard('beneficiario')->user();
        return view('dashboard', ['user' => $user, 'tipo' => 'beneficiario']);
    }
    if (Auth::guard('web')->check()) {
        $user = Auth::guard('web')->user();
        return view('dashboard', ['user' => $user, 'tipo' => 'personal']);
    }
    return redirect()->route('login');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/Registrar-Solicitud', function () {
    $hoy = now()->toDateString();
    $apoyos = DB::table('Apoyos')
        ->where('activo', 1)
        ->where(function ($query) use ($hoy) {
            $query->whereNull('fechaInicio')
                ->orWhereDate('fechaInicio', '<=', $hoy);
        })
        ->where(function ($query) use ($hoy) {
            $query->whereNull('fechafin')
                ->orWhereDate('fechafin', '>=', $hoy);
        })
        ->orderBy('id_apoyo', 'desc')
        ->get();

    $requisitos = DB::table('Requisitos_Apoyo')
        ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
        ->get();

    $apoyosData = $apoyos->map(function($apoyo) use ($requisitos) {
        if (!empty($apoyo->fechaInicio)) {
            $apoyo->fechaInicio = Carbon::parse($apoyo->fechaInicio)->toDateString();
        }

        if (!empty($apoyo->fechafin)) {
            $apoyo->fechafin = Carbon::parse($apoyo->fechafin)->toDateString();
        }

        $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
        return $apoyo;
    });

    $misSolicitudes = collect();
    $curpBeneficiario = Auth::guard('beneficiario')->id();

    if ($curpBeneficiario) {
        $misSolicitudes = DB::table('Solicitudes')
            ->leftJoin('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->orderByDesc('Solicitudes.folio')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.estado',
                'Solicitudes.fecha_creacion',
                'Apoyos.nombre_apoyo',
            ])
            ->limit(10)
            ->get();
    }

    return view('solicitudes.registrar', [
        'apoyosJson' => $apoyosData->toJson(),
        'misSolicitudes' => $misSolicitudes,
    ]);

})->middleware('auth:web,beneficiario')->name('solicitudes.registrar');

Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])
    ->middleware('auth:web,beneficiario')
    ->name('solicitud.guardar');

Route::get('/apoyos',      [ApoyoController::class, 'index'])->name('apoyos.index');
Route::post('/apoyos',     [ApoyoController::class, 'store'])->name('apoyos.store');
Route::get('/apoyos/list', [ApoyoController::class, 'list'])->name('apoyos.list');

require __DIR__.'/auth.php';