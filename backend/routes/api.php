<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CalificacionController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\CierreCajaController;
use App\Http\Controllers\Api\V1\CitaController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\DisponibilidadController;
use App\Http\Controllers\Api\V1\EmpleadoController;
use App\Http\Controllers\Api\V1\MiCitaController;
use App\Http\Controllers\Api\V1\NotificacionController;
use App\Http\Controllers\Api\V1\PagoController;
use App\Http\Controllers\Api\V1\ProductoController;
use App\Http\Controllers\Api\V1\ProveedorController;
use App\Http\Controllers\Api\V1\PublicCitaController;
use App\Http\Controllers\Api\V1\PublicNotificacionController;
use App\Http\Controllers\Api\V1\QrCobroController;
use App\Http\Controllers\Api\V1\ReporteController;
use App\Http\Controllers\Api\V1\RolController;
use App\Http\Controllers\Api\V1\ServicioController;
use App\Http\Controllers\Api\V1\TipoCorteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Publicas: registro/login de clientes
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login');
    });

    // Catalogo y reserva publica para clientes (sin login)
    Route::prefix('publico')->group(function () {
        Route::get('catalogo', [CatalogoController::class, 'show']);
        Route::get('tipos-corte', [CatalogoController::class, 'tiposCorte']);
        Route::get('servicios', [CatalogoController::class, 'servicios']);
        Route::get('barberos', [CatalogoController::class, 'barberos']);
        Route::get('disponibilidad', [DisponibilidadController::class, 'porDia']);

        Route::post('citas', [PublicCitaController::class, 'store'])
            ->middleware('throttle:10,1');

        Route::post('qr/{referencia}/confirmar', [QrCobroController::class, 'confirmar'])
            ->middleware('throttle:20,1');

        Route::get('citas/confirmar/{token}', [PublicCitaController::class, 'confirmar'])
            ->name('publico.citas.confirmar')
            ->middleware('throttle:20,1');

        Route::get('citas/notificacion/{token}', [PublicNotificacionController::class, 'show'])
            ->name('publico.citas.notificacion.show')
            ->middleware('throttle:20,1');

        Route::post('citas/notificacion/{token}/responder', [PublicNotificacionController::class, 'responder'])
            ->name('publico.citas.notificacion.responder.post')
            ->middleware('throttle:20,1');

        Route::get('citas/notificacion/{token}/{accion}', [PublicNotificacionController::class, 'responderPorEnlace'])
            ->whereIn('accion', ['confirmar', 'cancelar'])
            ->name('publico.citas.notificacion.responder')
            ->middleware('throttle:20,1');
    });

    // Calificacion publica mediante enlace con token
    Route::get('calificaciones/publica/{token}', [CalificacionController::class, 'publicaInfo']);
    Route::post('calificaciones/publica/{token}', [CalificacionController::class, 'publicaStore'])
        ->middleware('throttle:20,1');

    // Protegidas con Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
        });

        Route::get('me', [AuthController::class, 'me']);

        // Cliente final autenticado
        Route::middleware('role:cliente')->group(function () {
            Route::get('mis-citas', [MiCitaController::class, 'index']);
            Route::post('mis-citas/{cita}/cancelar', [MiCitaController::class, 'cancelar']);
            Route::patch('mis-citas/{cita}/reprogramar', [MiCitaController::class, 'reprogramar']);
        });

        // Personal de la barberia (admin y trabajador)
        Route::middleware('role:admin,trabajador')->group(function () {
            Route::get('disponibilidad', [DisponibilidadController::class, 'porDia']);

            Route::apiResource('empleados', EmpleadoController::class)->only(['index', 'show']);
            Route::apiResource('servicios', ServicioController::class)->only(['index', 'show']);

            Route::apiResource('tipos-corte', TipoCorteController::class)
                ->only(['index', 'show'])
                ->parameters(['tipos-corte' => 'tipoCorte']);

            Route::get('productos/stock-bajo', [ProductoController::class, 'stockBajo']);
            Route::apiResource('productos', ProductoController::class)->only(['index', 'show']);

            Route::apiResource('proveedores', ProveedorController::class)
                ->only(['index', 'show'])
                ->parameters(['proveedores' => 'proveedor']);

            Route::get('calificaciones', [CalificacionController::class, 'index']);
            Route::post('calificaciones', [CalificacionController::class, 'store']);

            Route::apiResource('clientes', ClienteController::class);

            Route::get('citas', [CitaController::class, 'index']);
            Route::post('citas', [CitaController::class, 'store']);
            Route::get('citas/{cita}', [CitaController::class, 'show']);
            Route::patch('citas/{cita}/estado', [CitaController::class, 'cambiarEstado']);
            Route::patch('citas/{cita}/reprogramar', [CitaController::class, 'reprogramar']);
            Route::post('citas/{cita}/completar', [CitaController::class, 'completar']);
            Route::delete('citas/{cita}', [CitaController::class, 'destroy']);

            Route::get('pagos', [PagoController::class, 'index']);
            Route::post('pagos', [PagoController::class, 'store']);
            Route::get('pagos/{pago}', [PagoController::class, 'show']);
            Route::patch('pagos/{pago}/estado', [PagoController::class, 'cambiarEstado']);

            Route::post('citas/{cita}/qr', [QrCobroController::class, 'generar']);
            Route::get('qr-cobros/{qrCobro}/imagen', [QrCobroController::class, 'imagen'])
                ->name('qr-cobros.imagen');
        });

        // Gestion reservada al administrador
        Route::middleware('role:admin')->group(function () {
            Route::get('roles', [RolController::class, 'index']);

            Route::apiResource('empleados', EmpleadoController::class)->except(['index', 'show']);
            Route::post('empleados/{empleado}/foto', [EmpleadoController::class, 'subirFoto']);
            Route::delete('empleados/{empleado}/foto', [EmpleadoController::class, 'eliminarFoto']);
            Route::post('empleados/{empleado}/servicios', [EmpleadoController::class, 'syncServicios']);
            Route::put('empleados/{empleado}/horarios', [EmpleadoController::class, 'syncHorarios']);

            Route::apiResource('servicios', ServicioController::class)->except(['index', 'show']);
            Route::post('servicios/{servicio}/empleados', [ServicioController::class, 'syncEmpleados']);
            Route::post('servicios/{servicio}/productos', [ServicioController::class, 'syncProductos']);

            Route::apiResource('tipos-corte', TipoCorteController::class)
                ->except(['index', 'show'])
                ->parameters(['tipos-corte' => 'tipoCorte']);

            Route::apiResource('productos', ProductoController::class)->except(['index', 'show']);

            Route::apiResource('proveedores', ProveedorController::class)
                ->except(['index', 'show'])
                ->parameters(['proveedores' => 'proveedor']);

            Route::prefix('reportes')->group(function () {
                Route::get('ventas', [ReporteController::class, 'ventas']);
                Route::get('comisiones', [ReporteController::class, 'comisiones']);
                Route::get('servicios-mas-solicitados', [ReporteController::class, 'serviciosMasSolicitados']);
                Route::get('clientes-frecuentes', [ReporteController::class, 'clientesFrecuentes']);
                Route::get('citas', [ReporteController::class, 'citas']);
                Route::get('ocupacion', [ReporteController::class, 'ocupacion']);
                Route::get('stock', [ReporteController::class, 'stock']);
                Route::get('dashboard', [ReporteController::class, 'dashboard']);
            });

            Route::get('notificaciones', [NotificacionController::class, 'index']);
            Route::get('notificaciones/{notificacion}', [NotificacionController::class, 'show']);

            Route::get('caja/resumen', [CierreCajaController::class, 'resumen']);
            Route::get('caja/cierres', [CierreCajaController::class, 'index']);
            Route::post('caja/cierres', [CierreCajaController::class, 'store']);
            Route::get('caja/cierres/{cierre}', [CierreCajaController::class, 'show']);
        });
    });
});
