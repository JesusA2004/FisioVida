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
use App\Http\Controllers\FisioVida\RolesController;
use App\Http\Controllers\FisioVida\ActividadesController;
use App\Http\Controllers\FisioVida\PermisosController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pacientes', PacientesController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:patients.view');
    Route::resource('usuarios', UsuariosController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:users.view');
    Route::resource('citas', CitasController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:appointments.view');
    Route::resource('sesiones', SesionesController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:sessions.view');
    Route::resource('ejercicios', EjerciciosController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:exercises.view');
    Route::resource('archivos', ArchivosController::class)->only(['index', 'store', 'destroy'])->middleware('permission:files.view');
    Route::resource('pagos', PagosController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:payments.view');
    Route::resource('logs', LogsController::class)->only(['index', 'store', 'destroy'])->middleware('permission:logs.view');
    Route::resource('roles', RolesController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:roles.view');
    Route::resource('actividades', ActividadesController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:activities.view');
    Route::resource('permisos', PermisosController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('permission:permissions.view');

    // session_exercises (pivot) como CRUD “anidado” a sesión
    Route::post('sesiones/{sessionId}/ejercicios', [SesionEjerciciosController::class, 'store'])->name('sesiones.ejercicios.store')->middleware('permission:sessions.update');
    Route::put('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'update'])->name('sesiones.ejercicios.update')->middleware('permission:sessions.update');
    Route::delete('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'destroy'])->name('sesiones.ejercicios.destroy')->middleware('permission:sessions.update');
});

require __DIR__ . '/settings.php';
