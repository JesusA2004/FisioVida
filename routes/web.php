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
use App\Http\Controllers\FisioVida\ConfiguracionController;
use App\Http\Controllers\FisioVida\ModulosSistemaController;
use App\Http\Controllers\FisioVida\DashboardController;
use App\Http\Controllers\FisioVida\ReportesController;
use App\Http\Controllers\FisioVida\ConsentimientosController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('pacientes', [PacientesController::class, 'index'])
        ->name('pacientes.index')
        ->middleware('permission:patients.view');

    Route::get('pacientes/{paciente}', [PacientesController::class, 'show'])
        ->name('pacientes.show')
        ->middleware('permission:patients.view');

    Route::post('pacientes', [PacientesController::class, 'store'])
        ->name('pacientes.store')
        ->middleware('permission:patients.create');

    Route::put('pacientes/{paciente}', [PacientesController::class, 'update'])
        ->name('pacientes.update')
        ->middleware('permission:patients.update');

    Route::patch('pacientes/{paciente}', [PacientesController::class, 'update'])
        ->name('pacientes.patch')
        ->middleware('permission:patients.update');

    Route::delete('pacientes/{paciente}', [PacientesController::class, 'destroy'])
        ->name('pacientes.destroy')
        ->middleware('permission:patients.delete');
    
    Route::patch('pacientes/{paciente}/activar', [PacientesController::class, 'activate'])
    ->name('pacientes.activate')
    ->middleware('permission:patients.update');

    Route::post('pacientes/{paciente}/consentimientos', [ConsentimientosController::class, 'store'])
        ->name('pacientes.consentimientos.store')
        ->middleware('permission:patients.update');

    Route::post('pacientes/{paciente}/aviso-privacidad', [ConsentimientosController::class, 'storePrivacy'])
        ->name('pacientes.privacy.store')
        ->middleware('permission:patients.update');

    Route::get('usuarios', [UsuariosController::class, 'index'])->name('usuarios.index')->middleware('permission:users.view');
    Route::post('usuarios', [UsuariosController::class, 'store'])->name('usuarios.store')->middleware('permission:users.create');
    Route::put('usuarios/{usuario}', [UsuariosController::class, 'update'])->name('usuarios.update')->middleware('permission:users.update');
    Route::delete('usuarios/{usuario}', [UsuariosController::class, 'destroy'])->name('usuarios.destroy')->middleware('permission:users.update');
    Route::patch('usuarios/{usuario}/toggle-status', [UsuariosController::class, 'toggleStatus'])->name('usuarios.toggle-status')->middleware('permission:users.update');

    Route::get('citas', [CitasController::class, 'index'])->name('citas.index')->middleware('permission:appointments.view');
    Route::post('citas', [CitasController::class, 'store'])->name('citas.store')->middleware('permission:appointments.create');
    Route::put('citas/{cita}', [CitasController::class, 'update'])->name('citas.update')->middleware('permission:appointments.update');
    Route::delete('citas/{cita}', [CitasController::class, 'destroy'])->name('citas.destroy')->middleware('permission:appointments.update');
    Route::patch('citas/{cita}/cancelar', [CitasController::class, 'cancelar'])->name('citas.cancelar')->middleware('permission:appointments.update');
    Route::patch('citas/{cita}/no-show', [CitasController::class, 'noShow'])->name('citas.no-show')->middleware('permission:appointments.update');
    Route::patch('citas/{cita}/avanzar', [CitasController::class, 'avanzar'])->name('citas.avanzar')->middleware('permission:appointments.update');

    Route::get('sesiones', [SesionesController::class, 'index'])->name('sesiones.index')->middleware('permission:sessions.view');
    Route::post('sesiones', [SesionesController::class, 'store'])->name('sesiones.store')->middleware('permission:sessions.create');
    Route::put('sesiones/{sesion}', [SesionesController::class, 'update'])->name('sesiones.update')->middleware('permission:sessions.update');
    Route::delete('sesiones/{sesion}', [SesionesController::class, 'destroy'])->name('sesiones.destroy')->middleware('permission:sessions.update');

    Route::get('ejercicios', [EjerciciosController::class, 'index'])->name('ejercicios.index')->middleware('permission:exercises.view');
    Route::post('ejercicios', [EjerciciosController::class, 'store'])->name('ejercicios.store')->middleware('permission:exercises.create');
    Route::put('ejercicios/{ejercicio}', [EjerciciosController::class, 'update'])->name('ejercicios.update')->middleware('permission:exercises.update');
    Route::patch('ejercicios/{ejercicio}/toggle-active', [EjerciciosController::class, 'toggleActive'])->name('ejercicios.toggle-active')->middleware('permission:exercises.update');
    Route::delete('ejercicios/{ejercicio}', [EjerciciosController::class, 'destroy'])->name('ejercicios.destroy')->middleware('permission:exercises.update');

    Route::get('/archivos/{archivo}/descargar', [ArchivosController::class, 'download'])
    ->name('archivos.download');

    Route::resource('archivos', ArchivosController::class)
        ->except(['create', 'edit']);

    Route::get('pagos', [PagosController::class, 'index'])->name('pagos.index')->middleware('permission:payments.view');
    Route::post('pagos', [PagosController::class, 'store'])->name('pagos.store')->middleware('permission:payments.create');
    Route::put('pagos/{pago}', [PagosController::class, 'update'])->name('pagos.update')->middleware('permission:payments.update');
    Route::delete('pagos/{pago}', [PagosController::class, 'destroy'])->name('pagos.destroy')->middleware('permission:payments.update');
    Route::patch('pagos/{pago}/cancelar', [PagosController::class, 'cancel'])->name('pagos.cancelar')->middleware('permission:payments.update');

    Route::get('reportes', [ReportesController::class, 'index'])->name('reportes.index')->middleware('permission:reports.view');
    Route::get('reportes/export/excel', [ReportesController::class, 'exportExcel'])->name('reportes.export.excel')->middleware('permission:reports.view');
    Route::get('reportes/export/pdf', [ReportesController::class, 'exportPdf'])->name('reportes.export.pdf')->middleware('permission:reports.view');

    Route::get('logs', [LogsController::class, 'index'])->name('logs.index')->middleware('permission:logs.view');
    Route::post('logs', [LogsController::class, 'store'])->name('logs.store')->middleware('permission:logs.view');
    Route::delete('logs/{log}', [LogsController::class, 'destroy'])->name('logs.destroy')->middleware('permission:logs.view');

    Route::get('bitacora', [LogsController::class, 'index'])->name('bitacora.index')->middleware('permission:logs.view');

    Route::get('roles', [RolesController::class, 'index'])->name('roles.index')->middleware('permission:roles.view');
    Route::post('roles', [RolesController::class, 'store'])->name('roles.store')->middleware('permission:roles.create');
    Route::put('roles/{role}', [RolesController::class, 'update'])->name('roles.update')->middleware('permission:roles.update');
    Route::delete('roles/{role}', [RolesController::class, 'destroy'])->name('roles.destroy')->middleware('permission:roles.delete');

    Route::get('actividades', [ActividadesController::class, 'index'])->name('actividades.index')->middleware('permission:activities.view');
    Route::post('actividades', [ActividadesController::class, 'store'])->name('actividades.store')->middleware('permission:activities.create');
    Route::put('actividades/{actividade}', [ActividadesController::class, 'update'])->name('actividades.update')->middleware('permission:activities.update');
    Route::delete('actividades/{actividade}', [ActividadesController::class, 'destroy'])->name('actividades.destroy')->middleware('permission:activities.update');

    Route::patch('actividades/{actividade}/start', [ActividadesController::class, 'start'])->name('actividades.start')->middleware('permission:activities.update');
    Route::patch('actividades/{actividade}/complete', [ActividadesController::class, 'complete'])->name('actividades.complete')->middleware('permission:activities.complete');
    Route::patch('actividades/{actividade}/cancel', [ActividadesController::class, 'cancel'])->name('actividades.cancel')->middleware('permission:activities.update');

    Route::get('configuracion', [ConfiguracionController::class, 'index'])
    ->name('configuracion.index')
    ->middleware('permission:settings.view');

    Route::put('configuracion', [ConfiguracionController::class, 'update'])
        ->name('configuracion.update')
        ->middleware('permission:settings.update');

    Route::post('configuracion', [ConfiguracionController::class, 'update'])
        ->name('configuracion.upload')
        ->middleware('permission:settings.update');

    Route::patch('configuracion/modulos', [ModulosSistemaController::class, 'update'])
    ->name('configuracion.modulos.update')
    ->middleware('permission:settings.update');

    // session_exercises (pivot) como CRUD “anidado” a sesión
    Route::post('sesiones/{sessionId}/ejercicios', [SesionEjerciciosController::class, 'store'])->name('sesiones.ejercicios.store')->middleware('permission:sessions.update');
    Route::put('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'update'])->name('sesiones.ejercicios.update')->middleware('permission:sessions.update');
    Route::delete('sesiones/{sessionId}/ejercicios/{exerciseId}', [SesionEjerciciosController::class, 'destroy'])->name('sesiones.ejercicios.destroy')->middleware('permission:sessions.update');

});

require __DIR__ . '/settings.php';
