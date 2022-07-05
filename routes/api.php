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

Route::post('/login', \App\Http\Controllers\Auth\AuthController::class)->name('login');
Route::post('/register', \App\Http\Controllers\Member\MemberRegisterController::class)->name('member.register');
Route::get('/send_email_otp', [\App\Http\Controllers\API\SendMailController::class, 'sendEmailOtp']);
Route::get('/validate_otp', [\App\Http\Controllers\API\SendMailController::class, 'validateOtp']);

Route::middleware('auth:api')->group(function () {
    Route::put('/change_password', \App\Http\Controllers\Auth\ChangePasswordController::class)->name('change_password');
});
