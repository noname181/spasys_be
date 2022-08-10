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

    Route::prefix('member')->name('member.')->group(function () {

        Route::middleware('role:spasys_manager,spasys_admin,agency_manager')->group(function () {
            Route::post('/register', \App\Http\Controllers\Member\MemberController::class)->name('register');
            Route::patch('/update_by_id', [\App\Http\Controllers\Member\MemberController::class, 'updateProfileById'])->name('update_profile_by_id');
            Route::delete('/delete_member/{mb_no}',[\App\Http\Controllers\Member\MemberController::class, 'deleteMember'])->name('delete_member');
        });

        Route::patch('/update', [\App\Http\Controllers\Member\MemberController::class, 'updateProfile'])->name('update');
        Route::get('/profile', [\App\Http\Controllers\Member\MemberController::class, 'getProfile'])->name('profile');
        Route::put('/change_password', \App\Http\Controllers\Auth\ChangePasswordController::class)->name('change_password');

        Route::post('/list_members', [\App\Http\Controllers\Member\MemberController::class, 'list_members'])->name('list_members');
        Route::post('/all', [\App\Http\Controllers\Member\MemberController::class, 'getMembers'])->name('members');
        Route::get('/{mb_no}', [\App\Http\Controllers\Member\MemberController::class, 'getMember'])->name('member');

        Route::middleware('role:admin')->group(function () {
            Route::post('/create_account',[\App\Http\Controllers\Member\MemberController::class, 'createAccount'])->name('create_account');
            Route::patch('/update_account/{memeber}',[\App\Http\Controllers\Member\MemberController::class, 'updateAccount'])->name('update_account');
            Route::delete('/delete_account/{mb_no}',[\App\Http\Controllers\Member\MemberController::class, 'deleteAccount'])->name('delete_account');
            Route::post('/spasys', [\App\Http\Controllers\Member\MemberController::class, 'getSpasys'])->name('get_spasys');
        });
    });

    // Manger Role
    Route::post('/get_companies', [App\Http\Controllers\Company\CompanyController::class, 'getcompanies'])->name('get_companies');
    Route::post('/register_company', \App\Http\Controllers\Company\CompanyController::class)->name('register_company');
    Route::get('/get_company/{co_no}', [App\Http\Controllers\Company\CompanyController::class, 'getCompany'])->name('get_company');
    Route::patch('/update_company/{company}', [App\Http\Controllers\Company\CompanyController::class, 'updateCompany'])->name('update_company');
    Route::post('/register_contract', \App\Http\Controllers\Contract\ContractController::class)->name('register_contract');
    Route::post('/get_agency_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getAgencyCompanies'])->name('get_agency_companies');
    Route::post('/get_shop_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShopCompanies'])->name('get_shop_companies');

    Route::prefix('service')->name('service.')->group(function () {
        Route::post('/', \App\Http\Controllers\Service\ServiceController::class)->name('registe_update_services');
        Route::get('/', [App\Http\Controllers\Service\ServiceController::class, 'getServices'])->name('get_services');
        Route::delete('/{service}', [App\Http\Controllers\Service\ServiceController::class, 'deleteService'])->name('delete_services');
    });

    Route::prefix('push')->name('push.')->group(function () {
        Route::get('/', \App\Http\Controllers\Push\PushController::class)->name('get_pushs');
        Route::get('/{push}', [App\Http\Controllers\Push\PushController::class, 'getPushDetail'])->name('get_push_detail');
        Route::post('/create', [App\Http\Controllers\Push\PushController::class, 'createPush'])->name('create');
        Route::patch('/update/{push}', [App\Http\Controllers\Push\PushController::class, 'updatePush'])->name('update');
    });

    Route::prefix('contract')->name('contract.')->group(function () {
        Route::patch('/{contract}', [App\Http\Controllers\Contract\ContractController::class, 'updateContract'])->name('update_contract');
        Route::post('/', \App\Http\Controllers\Contract\ContractController::class)->name('register_contract');
        Route::get('/{co_no}', [App\Http\Controllers\Contract\ContractController::class, 'getContract'])->name('get_contract');
    });

    Route::prefix('qna')->name('qna.')->group(function () {
        Route::get('/', App\Http\Controllers\Qna\QnaController::class)->name('get_qna_index');
        Route::get('/{qna}', [App\Http\Controllers\Qna\QnaController::class, 'getById'])->name('get_qna_by_id');
        Route::post('/', [App\Http\Controllers\Qna\QnaController::class, 'register'])->name('register_qna');
        Route::post('/reply_qna', [App\Http\Controllers\Qna\QnaController::class, 'reply_qna'])->name('reply_qna');
        Route::patch('/', [App\Http\Controllers\Qna\QnaController::class, 'update'])->name('update_qna');
        Route::post('/get_qnas', [App\Http\Controllers\Qna\QnaController::class, 'getQnA'])->name('get_qna');
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
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create'])->name('register_adjustment_group');
        Route::post('/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create_with_co_no'])->name('register_adjustment_group');
        Route::patch('{adjustment}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'update'])->name('update_adjustment_group');
        Route::get('/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'get_all'])->name('get_all_adjustment_group');
    });

    Route::prefix('co_address')->name('co_address.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'create'])->name('register_co_address');
        Route::post('/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'create_with_co_no'])->name('register_co_address');
        Route::get('/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'get_all'])->name('get_all_co_address');
    });

    Route::get('/get_manager/{co_no}', [App\Http\Controllers\Manager\ManagerController::class, 'getManager'])->name('get_manager');
    Route::post('/register_manager', [App\Http\Controllers\Manager\ManagerController::class, 'create'])->name('register_manager');
    Route::delete('/delete_manager/{manager}', [App\Http\Controllers\Manager\ManagerController::class, 'delete'])->name('delete_manager');
    Route::patch('/update_manager/{manager}', [App\Http\Controllers\Manager\ManagerController::class, 'update'])->name('update_manager');

    Route::post('/create_menu', [\App\Http\Controllers\Menu\MenuController::class, 'create']);
    Route::post('/menu', [\App\Http\Controllers\Menu\MenuController::class, 'menu']);
    Route::get('/menu/{menu_no}', [\App\Http\Controllers\Menu\MenuController::class, 'get_menu']);
    Route::post('/update_menu', [\App\Http\Controllers\Menu\MenuController::class, 'update_menu']);
    Route::get('/menu_main', [\App\Http\Controllers\Menu\MenuController::class, 'get_menu_main']);

    Route::prefix('role')->name('role.')->group(function () {
        Route::post('/', \App\Http\Controllers\Role\RoleController::class)->name('registe_update_role');
        Route::get('/', [App\Http\Controllers\Role\RoleController::class, 'getRoles'])->name('get_role');
        Route::delete('/{role}', [App\Http\Controllers\Role\RoleController::class, 'deleteRole'])->name('delete_role');
    });

    Route::prefix('manual')->name('manual.')->group(function () {
        Route::post('/', [App\Http\Controllers\Manual\ManualController::class, 'create'])->name('register_manual');
        Route::get('/{manual}', [App\Http\Controllers\Manual\ManualController::class, 'getManualById'])->name('get_role');
        Route::patch('/{manual}', [App\Http\Controllers\Manual\ManualController::class, 'update'])->name('update_manual');
        Route::post('/suneditor', [App\Http\Controllers\Manual\ManualController::class, 'suneditor'])->name('update_manual');
    });

    Route::prefix('forwarder_info')->name('forwarder_info.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'create'])->name('register_forwarder_info');
        Route::post('/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'create_with_co_no'])->name('register_forwarder_info');
        Route::get('/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'get_all'])->name('get_all_forwarder_info');
    });

    Route::prefix('customs_info')->name('customs_info.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'create'])->name('register_customs_info');
        Route::post('/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'create_with_co_no'])->name('register_customs_info');
        Route::get('/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'get_all'])->name('get_all_customs_info');
    });

    Route::prefix('permission')->name('permission.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Permission\PermissionController::class, 'getMenu'])->name('get_menu');
        Route::post('/save', [\App\Http\Controllers\Permission\PermissionController::class, 'savePermission'])->name('save_permission');
    });

    Route::prefix('report')->name('report.')->group(function () {
        Route::post('/', \App\Http\Controllers\Report\ReportController::class)->name('registe_update_report');
        Route::get('/', [App\Http\Controllers\Report\ReportController::class, 'getReports'])->name('get_report');
        Route::delete('/{report}', [App\Http\Controllers\Report\ReportController::class, 'deleteReport'])->name('delete_report');
    });
});
