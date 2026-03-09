<?php

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
