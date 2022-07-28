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
Route::post('/import_excel', \App\Http\Controllers\Excel\ImportExcelController::class)->name('import_excel');
Route::get('/export_excel', [\App\Http\Controllers\Excel\ExportExcelController::class, 'exportExcel']);

Route::post('/login', \App\Http\Controllers\Auth\AuthController::class)->name('login');
Route::post('/forgot_password', [\App\Http\Controllers\Api\SendMailController::class, 'forgotPassword']);
Route::post('/send_email_otp', [\App\Http\Controllers\Api\SendMailController::class, 'sendEmailOtp']);
Route::post('/validate_otp', [\App\Http\Controllers\Api\SendMailController::class, 'validateOtp']);
Route::patch('/forgot_password', [\App\Http\Controllers\Api\SendMailController::class, 'sendPassword']);
Route::get('/find_id', [\App\Http\Controllers\Member\MemberController::class, 'findUserId'])->name('member.findUserId');

Route::middleware('auth')->group(function () {
    Route::post('/register', \App\Http\Controllers\Member\MemberController::class)->name('member.register');
    Route::patch('/member/update', [\App\Http\Controllers\Member\MemberController::class, 'updateProfile'])->name('member.update');
    Route::get('/profile', [\App\Http\Controllers\Member\MemberController::class, 'getProfile'])->name('member.profile');
    Route::put('/change_password', \App\Http\Controllers\Auth\ChangePasswordController::class)->name('change_password');

    // Manger Role
    Route::post('/register_company', \App\Http\Controllers\Company\CompanyController::class)->name('register_company');
    Route::get('/get_company/{co_no}', [App\Http\Controllers\Company\CompanyController::class, 'getCompany'])->name('get_company');
    Route::patch('/update_company/{company}', [App\Http\Controllers\Company\CompanyController::class, 'updateCompany'])->name('update_company');
    Route::post('/register_contract', \App\Http\Controllers\Contract\ContractController::class)->name('register_contract');

    Route::prefix('qna')->name('qna.')->group(function () {
        Route::get('/', App\Http\Controllers\Qna\QnaController::class)->name('get_qna_index');
        Route::get('/{qna}', [App\Http\Controllers\Qna\QnaController::class, 'getById'])->name('get_qna_by_id');
        Route::post('/', [App\Http\Controllers\Qna\QnaController::class, 'register'])->name('register_qna');
        Route::patch('/{qna}', [App\Http\Controllers\Qna\QnaController::class, 'update'])->name('update_qna');
        Route::post('/get_qna', [App\Http\Controllers\Qna\QnaController::class, 'getQnA'])->name('get_qna');
    });

    Route::prefix('banner')->name('banner.')->group(function () {
        Route::get('/', App\Http\Controllers\Banner\BannerController::class)->name('get_banner');
        Route::get('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'getById'])->name('get_banner_by_id');
        Route::post('/', [App\Http\Controllers\Banner\BannerController::class, 'register'])->name('register_banner');
        Route::patch('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'update'])->name('update_banner');
    });

    Route::post('/notices', [\App\Http\Controllers\Notice\NoticeController::class, 'create']);
    Route::get('/notices/{id}', [\App\Http\Controllers\Notice\NoticeController::class, 'getNoticeById']);
    Route::patch('/update_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'update']);
    Route::get('/notices', [\App\Http\Controllers\Notice\NoticeController::class,'__invoke']);
    Route::post('/search_notices', [\App\Http\Controllers\Notice\NoticeController::class,'searchNotice']);
    Route::post('/get_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'getNotice']);
    Route::post('/delete', [\App\Http\Controllers\Notice\NoticeController::class, 'delete']);

    Route::prefix('adjustment_group')->name('adjustment_group.')->group(function () {
        Route::post('/', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create'])->name('register_adjustment_group');
        Route::patch('{adjustment}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'update'])->name('update_adjustment_group');
    });

    Route::post('/register_manager', [App\Http\Controllers\Manager\ManagerController::class, 'create'])->name('register_manager');
    Route::delete('/delete_manager/{manager}', [App\Http\Controllers\Manager\ManagerController::class, 'delete'])->name('delete_manager');
    Route::patch('/update_manager/{manager}', [App\Http\Controllers\Manager\ManagerController::class, 'update'])->name('update_manager');

    Route::post('/create_menu', [\App\Http\Controllers\Menu\MenuController::class, 'create']);
    Route::post('/menu', [\App\Http\Controllers\Menu\MenuController::class, 'menu']);
    Route::get('/menu/{menu_no}', [\App\Http\Controllers\Menu\MenuController::class, 'get_menu']);
});
