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
    return view('solicitudes.registrar');
})->middleware(['auth','verified'])->name('solicitudes.registrar');
require __DIR__.'/auth.php';
Route::post('/guardar-solicitud', [SolicitudController::class, 'guardar'])->name('solicitud.guardar');