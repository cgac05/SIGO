<?php

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
