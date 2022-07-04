<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SendMailController;
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

Route::get('/hello', function () {
    return "Wellcome to spasys1 api!";
})->name('hello');

Route::post('/register', \App\Http\Controllers\Member\MemberRegisterController::class)->name('member.register');
Route::get('/request_otp', [\App\Http\Controllers\API\SendMailController::class, 'requestOtp']);