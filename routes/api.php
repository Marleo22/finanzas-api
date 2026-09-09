<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EgresoController;
use App\Http\Controllers\Api\IngresoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de la API
|--------------------------------------------------------------------------
|
| auth:sanctum  -> exige el token Bearer y resuelve $request->user().
|                  Sin el, $request->user() es null y todo el filtrado por
|                  usuario se cae en silencio.
| throttle:api  -> 60 peticiones por minuto. En Laravel 11+ ya no viene
|                  aplicado por defecto; el limitador se define en
|                  AppServiceProvider.
|
*/

// Publica. El throttle es mas estricto que el del resto: un login sin limite
// permite probar contraseñas a fuerza bruta.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('dashboard')->group(function () {
        Route::get('/resumen', [DashboardController::class, 'resumen']);
        Route::get('/egresos-por-categoria', [DashboardController::class, 'egresosPorCategoria']);
        Route::get('/resumen-anual', [DashboardController::class, 'resumenAnual']);
    });

    // Sin show: el catalogo completo viene en el index y no hay pantalla que
    // muestre una categoria sola.
    Route::apiResource('categorias', CategoriaController::class)
        ->except(['show']);

    Route::apiResource('ingresos', IngresoController::class);
    Route::apiResource('egresos', EgresoController::class);
});
