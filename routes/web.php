<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccesoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccesoEstudianteController;
use App\Http\Controllers\MiComedorController;
use App\Http\Controllers\HistorialTicketsController;
use App\Http\Controllers\Trabajador\AccesoTrabajadorController;
use App\Http\Controllers\Trabajador\PanelTrabajadorController;
use App\Http\Controllers\AccesoPruebasController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [AccesoController::class, 'index'])->name('acceso');
Route::get('/estudiantes/proximamente', [AccesoController::class, 'estudianteProximamente'])
    ->name('estudiante.proximamente');

Route::middleware('guest:web')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1')->name('login.store');
});

Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth:web', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    // aquí irán admin.tickets, admin.estudiantes, admin.becados, admin.horarios, admin.ventanas,
    // admin.sanciones, admin.reportes, admin.notificaciones, admin.usuarios y admin.configuracion:
    // el menú lateral las activa solo, apenas existan esas rutas.
});

Route::prefix('estudiante')->group(function () {
    Route::get('/acceso', [AccesoEstudianteController::class, 'enlaceSga'])->middleware('throttle:30,1')->name('estudiante.acceso');
    Route::get('/ingresar', [AccesoEstudianteController::class, 'formulario'])->name('estudiante.login');
    Route::post('/ingresar', [AccesoEstudianteController::class, 'ingresar'])->middleware('throttle:20,1')->name('estudiante.ingresar');
    Route::post('/salir', [AccesoEstudianteController::class, 'salir'])->name('estudiante.salir');

    Route::middleware('estudiante')->group(function () {
        Route::get('/inicio', [MiComedorController::class, 'index'])->name('estudiante.inicio');
        Route::post('/tickets', [MiComedorController::class, 'solicitar'])->middleware('throttle:20,1')->name('estudiante.tickets.solicitar');
        Route::post('/tickets/{ticket}/cancelar', [MiComedorController::class, 'cancelar'])->name('estudiante.tickets.cancelar');
        Route::get('/tickets', [HistorialTicketsController::class, 'index'])->name('estudiante.tickets.historial');
    });

    if (AccesoPruebasController::habilitado()) {
        Route::get('/acceso-pruebas', [AccesoPruebasController::class, 'formulario'])->name('estudiante.pruebas');
        Route::post('/acceso-pruebas', [AccesoPruebasController::class, 'ingresar'])
            ->middleware('throttle:30,1')->name('estudiante.pruebas.ingresar');
    }

});

Route::prefix('trabajador')->name('trabajador.')->group(function () {
    Route::get('/ingresar', [AccesoTrabajadorController::class, 'formulario'])->name('login');
    Route::post('/ingresar', [AccesoTrabajadorController::class, 'ingresar'])->middleware('throttle:60,1')->name('ingresar');
    Route::post('/salir', [AccesoTrabajadorController::class, 'salir'])->name('salir');

    Route::middleware('trabajador')->group(function () {
        Route::get('/', [PanelTrabajadorController::class, 'inicio'])->name('inicio');
        Route::post('/buscar', [PanelTrabajadorController::class, 'buscar'])->middleware('throttle:120,1')->name('buscar');
        Route::get('/tickets/{ticket}', [PanelTrabajadorController::class, 'ver'])->name('ticket');
        Route::post('/tickets/{ticket}/confirmar', [PanelTrabajadorController::class, 'confirmar'])->middleware('throttle:120,1')->name('confirmar');
        Route::get('/actividad', [PanelTrabajadorController::class, 'actividad'])->name('actividad');
    });
});

