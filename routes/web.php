<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccesoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccesoEstudianteController;
use App\Http\Controllers\MiComedorController;

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
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth:web')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
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
    });
});
