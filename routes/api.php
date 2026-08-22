<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EgresoController;
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

    Route::apiResource('egresos', EgresoController::class);
});
