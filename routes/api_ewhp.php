<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/api_ewhp', function () {
    return "Wellcome to spasys1 api_ewhp!";
})->name('api_ewhp');


Route::middleware('auth.ewhp')->group(function () {
   
    Route::post('/import_schedule',[\App\Http\Controllers\EWHP\EWHPController::class, 'import_schedule']);
    Route::post('/import',[\App\Http\Controllers\EWHP\EWHPController::class, 'import']);
    Route::post('/export',[\App\Http\Controllers\EWHP\EWHPController::class, 'export']);
      
});

