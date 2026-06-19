<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;

// Ruta de autenticación (opcional)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('login', [UsuarioController::class, 'login']);
Route::middleware('auth:sanctum')->post('logout', [UsuarioController::class, 'logout']);


// GET /api/users/verify/{email} - Verifica disponibilidad de email
Route::get('users/verify/{email}', [UsuarioController::class, 'verificarEmail']);

Route::apiResource('users', UsuarioController::class);

