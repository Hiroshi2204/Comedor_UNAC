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
use App\Http\Controllers\Admin\{
    TicketsAdminController,
    EstudiantesAdminController,
    SancionesAdminController,
    BecadosAdminController,
    HorariosAdminController,
    VentanasAdminController,
    ReportesAdminController,
    UsuariosAdminController,
    ConfiguracionAdminController,
    DemoAdminController
};

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
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/tickets', [TicketsAdminController::class, 'index'])->name('tickets');
        Route::get('/tickets/exportar', [TicketsAdminController::class, 'exportar'])->name('tickets.exportar');
        Route::get('/tickets/{ticket}', [TicketsAdminController::class, 'show'])->whereNumber('ticket')->name('tickets.show');

        Route::get('/estudiantes', [EstudiantesAdminController::class, 'index'])->name('estudiantes');
        Route::get('/estudiantes/{estudiante}', [EstudiantesAdminController::class, 'show'])->whereNumber('estudiante')->name('estudiantes.show');

        Route::get('/sanciones', [SancionesAdminController::class, 'index'])->name('sanciones');
        Route::post('/sanciones/{sancion}/levantar', [SancionesAdminController::class, 'levantar'])->whereNumber('sancion')->name('sanciones.levantar');

        Route::get('/becados', [BecadosAdminController::class, 'index'])->name('becados');
        Route::post('/becados/importar', [BecadosAdminController::class, 'importar'])->middleware('throttle:10,1')->name('becados.importar');
        Route::get('/becados/{importacion}', [BecadosAdminController::class, 'show'])->whereNumber('importacion')->name('becados.show');
        Route::post('/becados/{importacion}/procesar', [BecadosAdminController::class, 'procesar'])->whereNumber('importacion')->name('becados.procesar');

        Route::get('/horarios', [HorariosAdminController::class, 'index'])->name('horarios');
        Route::post('/horarios/sincronizar', [HorariosAdminController::class, 'sincronizar'])->name('horarios.sincronizar');
        Route::get('/horarios/progreso', [HorariosAdminController::class, 'progreso'])->name('horarios.progreso');

        Route::get('/ventanas', [VentanasAdminController::class, 'index'])->name('ventanas');
        Route::post('/ventanas/{servicio}', [VentanasAdminController::class, 'actualizar'])->name('ventanas.actualizar');

        Route::get('/reportes', [ReportesAdminController::class, 'index'])->name('reportes');
        Route::get('/reportes/exportar', [ReportesAdminController::class, 'exportar'])->name('reportes.exportar');

        Route::get('/usuarios', [UsuariosAdminController::class, 'index'])->name('usuarios');
        Route::post('/usuarios', [UsuariosAdminController::class, 'guardar'])->name('usuarios.guardar');
        Route::put('/usuarios/{usuario}', [UsuariosAdminController::class, 'actualizar'])->whereNumber('usuario')->name('usuarios.actualizar');

        Route::get('/configuracion', [ConfiguracionAdminController::class, 'index'])->name('configuracion');
        Route::post('/configuracion', [ConfiguracionAdminController::class, 'guardar'])->name('configuracion.guardar');
        Route::post('/configuracion/restablecer', [ConfiguracionAdminController::class, 'restablecer'])->name('configuracion.restablecer');

        // Modo demostración (solo si DEMO_RELOJ=true y fuera de producción)
        Route::get('/demo', [DemoAdminController::class, 'index'])->name('demo');
        Route::post('/demo/fijar', [DemoAdminController::class, 'fijar'])->name('demo.fijar');
        Route::post('/demo/avanzar', [DemoAdminController::class, 'avanzar'])->name('demo.avanzar');
        Route::post('/demo/desactivar', [DemoAdminController::class, 'desactivar'])->name('demo.desactivar');
        Route::post('/demo/cerrar', [DemoAdminController::class, 'cerrar'])->name('demo.cerrar');
    });
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
