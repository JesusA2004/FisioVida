<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\FisioVida\PacientesController;
use App\Http\Controllers\FisioVida\UsuariosController;
use App\Http\Controllers\FisioVida\CitasController;
use App\Http\Controllers\FisioVida\SesionesController;
use App\Http\Controllers\FisioVida\EjerciciosController;
use App\Http\Controllers\FisioVida\ArchivosController;
use App\Http\Controllers\FisioVida\PagosController;
use App\Http\Controllers\FisioVida\LogsController;
use App\Http\Controllers\FisioVida\SesionEjerciciosController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::resource('pacientes', PacientesController::class)->only(['index','store','update','destroy']);
    Route::resource('usuarios', UsuariosController::class)->only(['index','store','update','destroy']);
    Route::resource('citas', CitasController::class)->only(['index','store','update','destroy']);
    Route::resource('sesiones', SesionesController::class)->only(['index','store','update','destroy']);
    Route::resource('ejercicios', EjerciciosController::class)->only(['index','store','update','destroy']);
    Route::resource('archivos', ArchivosController::class)->only(['index','store','destroy']);
    Route::resource('pagos', PagosController::class)->only(['index','store','update','destroy']);
    Route::resource('logs', LogsController::class)->only(['index','store','destroy']);

    // session_exercises (pivot) como CRUD “anidado” a sesión
    Route::post('sesiones/{sessionId}/ejercicios', [SesionEjerciciosController::class, 'store'])->name('sesiones.ejercicios.store');
    Route::put('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'update'])->name('sesiones.ejercicios.update');
    Route::delete('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'destroy'])->name('sesiones.ejercicios.destroy');
});

require __DIR__ . '/settings.php';
