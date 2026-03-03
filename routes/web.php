<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;


// //👇 Endpoints de API (prefijo manual)
// Route::prefix('api')->group(function () {
//     Route::post('/login', [AuthController::class, 'login']);
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
// });

Route::get('{any?}', function () {
    return view('application');
})->where('any', '.*');


