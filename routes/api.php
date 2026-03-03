<?php

use App\Http\Controllers\ApidianController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ControlController;
use App\Http\Controllers\ScrapingController;
use App\Http\Controllers\ScrapingDianController;
use App\Http\Controllers\UserController;
use App\Models\User;

use RoachPHP\Roach;
use App\Spiders\ScrapingDianSpider;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
//Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::get('/test-timezone', function () {
    return [
        'timezone_config' => config('app.timezone'),
        'now' => now()->toDateTimeString(),
    ];
});

Route::post('/test-spider', function () {
    Roach::startSpider(ScrapingDianSpider::class);
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/getcompanies', [CompanyController::class, 'getCompanies']);
    Route::post('/companies', [CompanyController::class, 'store']);
    // Otras rutas de la API...
    Route::put('/companies/{id}', [CompanyController::class, 'update']);
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy']);

    // Rutas para Usuarios

    Route::get('/users', [UserController::class, 'getUsers']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/password/{id}', [UserController::class, 'updatePassword']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Rutas Tabla de Control
    Route::get('/getcontrol/{id}', [ControlController::class, 'getControl']);
    Route::get('/control', [ControlController::class, 'index']);
    Route::put('/control/{id}', [ControlController::class, 'update']);

    // Rutas Documentos DIAN
    Route::get('/documentosdian', [ApidianController::class, 'index'])->name('apidian.index');
    Route::post('/list/payroll', [ApidianController::class, 'load_payroll'])->name('apidian.index_nomina');
    Route::post('/list/documents', [ApidianController::class, 'loaddata'])->name('apidian.loaddata');
    Route::post('/list/notes', [ApidianController::class, 'load_notes'])->name('apidian.index_notes');
    Route::post('/list/support', [ApidianController::class, 'load_support'])->name('apidian.index_support');
    Route::post('/downdocument/xml', [ApidianController::class, 'downxml'])->name('apidian.downxml');
    Route::post('/downdocument/pdf', [ApidianController::class, 'downpdf'])->name('apidian.downpdf');
    Route::post('/sendpackage', [ApidianController::class, 'sendpackage'])->name('apidian.sendpackage');

    Route::post('/scraping/dian', [ScrapingController::class, 'scraping_dian'])->name('scraping.dian');
    Route::post('/scraping/dianf', [ScrapingDianController::class, 'scraping_dian'])->name('scraping.dianf');
});
