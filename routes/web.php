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
    $user = Auth::user()->loadMissing('beneficiario');

    if (! $user->isBeneficiario()) {
        return redirect()->route('dashboard')->with('error', 'Solo los beneficiarios pueden registrar solicitudes de apoyo.');
    }

    $curpBeneficiario = $user->beneficiario?->curp;
    $hoy = now()->toDateString();

    $apoyos = DB::table('Apoyos')
        ->where('activo', 1)
        ->where(function ($query) use ($hoy) {
            $query->whereNull('fecha_inicio')
                ->orWhereDate('fecha_inicio', '<=', $hoy);
        })
        ->where(function ($query) use ($hoy) {
            $query->whereNull('fecha_fin')
                ->orWhereDate('fecha_fin', '>=', $hoy);
        })
        ->select([
            'id_apoyo',
            'nombre_apoyo',
            'tipo_apoyo',
            'monto_maximo',
            'anio_fiscal',
            'cupo_limite',
            'fecha_inicio as fechaInicio',
            'fecha_fin as fechafin',
        ])
        ->orderBy('id_apoyo', 'desc')
        ->get();

    $requisitos = DB::table('Requisitos_Apoyo')
        ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
<<<<<<< HEAD
        ->select('Requisitos_Apoyo.*', 'Cat_TiposDocumento.nombre_documento')
=======
        ->select([
            'Requisitos_Apoyo.fk_id_apoyo',
            'Requisitos_Apoyo.fk_id_tipo_doc',
            'Requisitos_Apoyo.es_obligatorio',
            'Cat_TiposDocumento.nombre_documento',
            'Cat_TiposDocumento.tipo_archivo_permitido',
            'Cat_TiposDocumento.validar_tipo_archivo',
        ])
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
        ->get();

    $apoyosData = $apoyos->map(function ($apoyo) use ($requisitos) {
        if (! empty($apoyo->fechaInicio)) {
            $apoyo->fechaInicio = Carbon::parse($apoyo->fechaInicio)->toDateString();
        }

        if (! empty($apoyo->fechafin)) {
            $apoyo->fechafin = Carbon::parse($apoyo->fechafin)->toDateString();
        }

        $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();

        return $apoyo;
    });

    $misSolicitudes = collect();

    if ($curpBeneficiario) {
        $misSolicitudes = DB::table('Solicitudes')
            ->leftJoin('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->orderByDesc('Solicitudes.folio')
            ->select([
                'Solicitudes.folio',
                'Cat_EstadosSolicitud.nombre_estado as estado',
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
})->middleware(['auth', 'beneficiario.profile'])->name('solicitudes.registrar');

Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])
    ->middleware(['auth', 'beneficiario.profile'])
    ->name('solicitud.guardar');

Route::get('/apoyos',                  [ApoyoController::class, 'index'])->name('apoyos.index');
Route::get('/apoyos/create',           [ApoyoController::class, 'create'])->name('apoyos.create');
Route::post('/apoyos',                 [ApoyoController::class, 'store'])->name('apoyos.store');
Route::get('/apoyos/list',             [ApoyoController::class, 'list'])->name('apoyos.list');
Route::post('/apoyos/check-inventario',   [ApoyoController::class, 'checkInventario'])->name('apoyos.check-inventario');
Route::post('/apoyos/aprobar-inventario', [ApoyoController::class, 'aprobarInventario'])->name('apoyos.aprobar-inventario');
Route::post('/apoyos/documentos',         [ApoyoController::class, 'storeTipoDocumento'])->name('apoyos.documentos.store');
Route::put('/apoyos/documentos/{id}',     [ApoyoController::class, 'updateTipoDocumento'])->name('apoyos.documentos.update');

require __DIR__.'/auth.php';