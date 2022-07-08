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

Route::get('/hello', function () {
    return "Wellcome to spasys1 api!";
})->name('hello');

Route::post('/login', \App\Http\Controllers\Auth\AuthController::class)->name('login');
Route::post('/register', \App\Http\Controllers\Member\MemberRegisterController::class)->name('member.register');
Route::post('/forgot_password', [\App\Http\Controllers\Api\SendMailController::class, 'forgotPassword']);
Route::post('/send_email_otp', [\App\Http\Controllers\Api\SendMailController::class, 'sendEmailOtp']);
Route::post('/validate_otp', [\App\Http\Controllers\Api\SendMailController::class, 'validateOtp']);
Route::patch('/forgot_password', [\App\Http\Controllers\Api\SendMailController::class, 'sendPassword']);

Route::middleware('auth')->group(function () {
    Route::put('/change_password', \App\Http\Controllers\Auth\ChangePasswordController::class)->name('change_password');

    // Manger Role
    Route::middleware('role.manager')->group(function () {
        Route::post('/create_company', \App\Http\Controllers\Company\CompanyRegisterController::class)->name('create_company');
    });

    Route::prefix('qna')->name('qna.')->group(function () {
        Route::get('/', App\Http\Controllers\Qna\QnaController::class)->name('get_qna');
        Route::get('/{qna}', [App\Http\Controllers\Qna\QnaController::class, 'getById'])->name('get_qna_by_id');
        Route::post('/', [App\Http\Controllers\Qna\QnaController::class, 'register'])->name('register_qna');
        Route::patch('/{qna}', [App\Http\Controllers\Qna\QnaController::class, 'update'])->name('update_qna');
    });

    Route::prefix('banner')->name('banner.')->group(function () {
        Route::get('/', App\Http\Controllers\Banner\BannerController::class)->name('get_banner');
        Route::get('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'getById'])->name('get_banner_by_id');
        Route::post('/', [App\Http\Controllers\Banner\BannerController::class, 'register'])->name('register_banner');
        Route::patch('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'update'])->name('update_banner');
    });

});

Route::post('/notices', [\App\Http\Controllers\Api\NoticeController::class, 'create']);
Route::get('/notices/{id}', [\App\Http\Controllers\Api\NoticeController::class, 'getNoticeById']);
Route::patch('/notices', [\App\Http\Controllers\Api\NoticeController::class, 'update']);
Route::get('/notices', [\App\Http\Controllers\Api\NoticeController::class,'__invoke']);