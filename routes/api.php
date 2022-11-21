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
Route::get('/api_item_cron_nologin', [App\Http\Controllers\Item\ItemController::class, 'apiItemCronNoLogin'])->name('api_item_cron_nologin');

Route::middleware('auth')->group(function () {

    Route::prefix('member')->name('member.')->group(function () {

        Route::middleware('role:spasys_manager,spasys_admin,shop_manager')->group(function () {
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


    Route::post('/api_item_cron', [App\Http\Controllers\Item\ItemController::class, 'apiItemCron'])->name('api_item_cron');

    // Manger Role
    Route::middleware('role:spasys_manager,spasys_admin,shop_manager')->group(function () {
        Route::post('/register_company', \App\Http\Controllers\Company\CompanyController::class)->name('register_company');
    });
    Route::post('/get_companies', [App\Http\Controllers\Company\CompanyController::class, 'getCompanies'])->name('get_companies');

    Route::get('/get_company/{co_no}', [App\Http\Controllers\Company\CompanyController::class, 'getCompany'])->name('get_company');
    Route::patch('/update_company/{company}', [App\Http\Controllers\Company\CompanyController::class, 'updateCompany'])->name('update_company');
    Route::patch('/update_company_co_license/{company}', [App\Http\Controllers\Company\CompanyController::class, 'updateCompanyColicense'])->name('update_company_co_license');
    Route::post('/register_contract', \App\Http\Controllers\Contract\ContractController::class)->name('register_contract');
    Route::post('/get_shop_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShopCompanies'])->name('get_shop_companies');
    Route::post('/get_shop_companies_mobile', [\App\Http\Controllers\Company\CompanyController::class, 'getShopCompaniesMobile'])->name('get_shop_companies_mobile');
    Route::post('/get_shipper_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShipperCompanies'])->name('get_shipper_companies');
    Route::post('/get_shipper_companies_mobile', [\App\Http\Controllers\Company\CompanyController::class, 'getShipperCompaniesMobile'])->name('get_shipper_companies_mobile');
    Route::post('/get_shipper_companies2', [\App\Http\Controllers\Company\CompanyController::class, 'getShipperCompanies2'])->name('get_shipper_companies2');
    Route::post('/get_item_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getItemCompanies'])->name('get_item_companies');
    Route::get('/customer_center_information', [\App\Http\Controllers\Company\CompanyController::class, 'getCustomerCenterInformation'])->name('get_CustomerCenterInformation');
    Route::get('/get_company_policy/{co_no}', [App\Http\Controllers\Company\CompanyController::class, 'getCompanyPolicy'])->name('get_company_policy');
    Route::get('/get_company_from_te/{tcon}', [App\Http\Controllers\Company\CompanyController::class, 'getCompanyFromtcon'])->name('get_company_from_tcon');
    Route::post('/get_shop_and_shipper_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShopAndShipperCompanies'])->name('get_shop_and_shipper_companies');

    Route::prefix('service')->name('service.')->group(function () {
        //service route only for spasys admin
        Route::middleware('role:spasys_admin')->group(function () {
            Route::get('/all', [App\Http\Controllers\Service\ServiceController::class, 'getAllServices'])->name('get_all_services');
            Route::post('/', \App\Http\Controllers\Service\ServiceController::class)->name('registe_update_services');
            Route::delete('/{service}', [App\Http\Controllers\Service\ServiceController::class, 'deleteService'])->name('delete_services');
        });

        //get services by co_no
        Route::get('/by_co_no/{co_no}', [App\Http\Controllers\Service\ServiceController::class, 'getServiceByCoNo'])->name('get_services_by_co_no');

        //get services by member company
        Route::get('/by_member', [App\Http\Controllers\Service\ServiceController::class, 'getServiceByMember'])->name('get_services_by_member');

        Route::get('/', [App\Http\Controllers\Service\ServiceController::class, 'getServices'])->name('get_services');
        Route::get('/active', [App\Http\Controllers\Service\ServiceController::class, 'getActiveServices'])->name('get_active_services');
    });

    Route::prefix('push')->name('push.')->group(function () {
        Route::get('/', \App\Http\Controllers\Push\PushController::class)->name('get_pushs');
        Route::post('/getpush', [App\Http\Controllers\Push\PushController::class, 'searchPush'])->name('getPush');
        Route::get('/{push}', [App\Http\Controllers\Push\PushController::class, 'getPushDetail'])->name('get_push_detail');
        Route::post('/create', [App\Http\Controllers\Push\PushController::class, 'createPush'])->name('create');
        Route::patch('/update/{push}', [App\Http\Controllers\Push\PushController::class, 'updatePush'])->name('update');
    });
    Route::prefix('sendemail')->name('sendemail.')->group(function () {
        Route::post('/create', [App\Http\Controllers\SendEmail\SendEmailController::class, 'createSendEmail'])->name('create');
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
        Route::post('/delete_qna', [App\Http\Controllers\Qna\QnaController::class, 'delete_qna'])->name('delete_qna');
    });

    Route::prefix('banner')->name('banner.')->group(function () {
        Route::get('/', App\Http\Controllers\Banner\BannerController::class)->name('get_banner');
        Route::get('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'getById'])->name('get_banner_by_id');
        Route::post('/', [App\Http\Controllers\Banner\BannerController::class, 'register'])->name('register_banner');
        Route::patch('{banner}', [App\Http\Controllers\Banner\BannerController::class, 'update'])->name('update_banner');
        Route::post('/get_banners', [App\Http\Controllers\Banner\BannerController::class, 'getBanner'])->name('get_banners');
    });

    Route::post('/notices', [\App\Http\Controllers\Notice\NoticeController::class, 'create']);
    Route::get('/notices/{id}', [\App\Http\Controllers\Notice\NoticeController::class, 'getNoticeById']);
    Route::patch('/update_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'update']);
    Route::post('/delete_notices', [App\Http\Controllers\Notice\NoticeController::class, 'deleteNotices'])->name('delete_notices');
    Route::get('/notices', [\App\Http\Controllers\Notice\NoticeController::class,'__invoke']);
    Route::post('/search_notices', [\App\Http\Controllers\Notice\NoticeController::class,'searchNotice']);
    Route::post('/get_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'getNotice']);
    Route::post('/delete', [\App\Http\Controllers\Notice\NoticeController::class, 'delete']);

    Route::get('/import_schedule', [\App\Http\Controllers\ImportSchedule\ImportScheduleController::class,'__invoke']);
    Route::post('/get_import_schedule', [\App\Http\Controllers\ImportSchedule\ImportScheduleController::class,'getImportSchedule']);
    Route::post('/get_import_api', [\App\Http\Controllers\ImportSchedule\ImportScheduleController::class,'getImportAPI']);

    Route::get('/warehousing', [\App\Http\Controllers\Warehousing\WarehousingController::class,'__invoke']);
    Route::post('/warehousing_import', [\App\Http\Controllers\Warehousing\WarehousingController::class,'warehousingImport']);

    Route::get('/get_warehousing/{w_no}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingById']);
    Route::get('/get_warehousing_from_rgd/{rgd_no}/{type}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingByRgd']);
    Route::get('/get_warehousing_from_rgd_fulfillment/{rgd_no}/{type}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingByRgdFulfillment']);

    Route::post('/get_warehousing', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousing']);
    Route::post('/get_warehousing_api', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingApi']);
    Route::post('/get_warehousing2', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousing2']);
    Route::post('/get_warehousing_export', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingExport']);
    Route::post('/get_warehousing_import', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImport']); //page 129
    Route::post('/get_warehousing_import_status1', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImportStatus1']); //page 134
    Route::post('/get_warehousing_delivery', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingDelivery']); //page 715
    Route::post('/get_warehousing_delivery_3', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingDelivery3']); //page 715_3
    Route::post('/get_warehousing_delivery_2', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingDelivery2']); //page 715_2
    Route::post('/get_warehousing_delivery_1', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingDelivery1']); //page 715_1

    Route::post('/update_status_delivery', [\App\Http\Controllers\Warehousing\WarehousingController::class,'UpdateStatusDelivery']); //page 715 update status

    Route::post('/get_warehousing_status1', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingStatus1']); //page 140
    Route::post('/get_warehousing_export_status12', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingExportStatus12']); //page 144

    Route::post('/get_warehousing_import_status_complete', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImportStatusComplete']); //Page259
    Route::post('/get_fulfillment_export_status_complete', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getFulfillmentExportStatusComplete']); //Page243
    Route::post('/get_bonded_export_status_complete', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getBondedExportStatusComplete']); //Page243

    Route::post('/get_warehousing_import_status4', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImportStatus4']); //page 263
    Route::post('/get_fulfillment_export_status4', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getFulfillmentExportStatus4']); //page 252
    Route::post('/get_bonded_export_status4', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getBondedExportStatus4']); //page 221

    Route::get('/get_fulfillment_export_status4_by_id/{rgd_no}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getFulfillmentExportStatus4ById']); //page 253 mobile

    Route::post('/get_tax_invoice_list', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getTaxInvoiceList']); //page 277
    Route::post('/get_tax_invoice_completed_list', [\App\Http\Controllers\Warehousing\WarehousingController::class,'get_tax_invoice_completed_list']); //page 282
    Route::post('/get_tid_list', [\App\Http\Controllers\Warehousing\WarehousingController::class,'get_tid_list']); //page 277
    Route::post('/create_tid', [\App\Http\Controllers\Warehousing\WarehousingController::class,'create_tid']); //page 277

    Route::get('/warehousing_request', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'__invoke']);
    Route::post('/warehousing_request_paginate', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'paginateWarehousingRequest']);
    Route::post('/get_warehousing_request', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'getWarehousingRequest']);
    Route::post('/create', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'createWarehousingRequest']);

    Route::get('receiving_goods_delivery/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'__invoke']);
    Route::get('/get_rgd_package/{co_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class, 'get_rgd_package'])->name('get_rgd_package');
    Route::post('receiving_goods_delivery/update_status5', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_status5']);
    Route::post('receiving_goods_delivery/update_status6', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_status6']);
    Route::post('receiving_goods_delivery/update_status5_fulfillment', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_status5_fulfillment']);

    Route::post('receiving_goods_delivery/payment', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'payment']);

    Route::post('receiving_goods_delivery/connection', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'connection']);

    Route::post('receiving_goods_delivery/cancel_settlement', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'cancel_settlement']);
    Route::get('receiving_goods_delivery/cancel_rgd/{rgd_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_ReceivingGoodsDelivery_cancel']);
    Route::get('receiving_goods_delivery/get_rgd/{is_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'getReceivingGoodsDelivery']);
    Route::get('receiving_goods_delivery/get_rgd_warehousing/{w_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'getReceivingGoodsDeliveryWarehousing']);
    //130
    Route::post('receiving_goods_delivery/warehousing/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing'])->name('rgd_warehousing');
    Route::post('receiving_goods_delivery/warehousing/update_rdc_cancel', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_rdc_cancel'])->name('update_rdc_cancel');
    //141
    Route::post('receiving_goods_delivery/warehousing_release/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing_release'])->name('rgd_warehousing_release');
    Route::post('receiving_goods_delivery/warehousing_release_mobile/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing_release_mobile'])->name('rgd_warehousing_release_mobile');
    Route::post('receiving_goods_delivery/warehousing_release_fulfillment/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing_release_fulfillment'])->name('create_warehousing_release_fulfillment');
    Route::post('receiving_goods_delivery/import_schedule/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_import_schedule'])->name('rgd_import_schedule');
    Route::post('receiving_goods_delivery/import_schedule_list/rgd_list', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_import_schedule_list'])->name('rgd_import_schedule_list');

    Route::post('receiving_goods_delivery/warehousing_api', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing_api'])->name('rgd_warehousing_api');

    Route::post('import_schedule/rgd_mobile', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_import_schedule_mobile'])->name('rgd_import_schedule_mobile');

    //upload
    //Route::post('receiving_goods_delivery/register', [App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class, 'register_rgd_file'])->name('register_rgd_file');
    Route::patch('receiving_goods_delivery/update', [App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class, 'update_rgd_file'])->name('update_rgd_file');
    Route::get('receiving_goods_delivery/get_file/{is_no}', [App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class, 'get_rgd_file'])->name('get_rgd_file');

    Route::prefix('adjustment_group')->name('adjustment_group.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create'])->name('register_adjustment_group');
        Route::post('/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create_with_co_no'])->name('register_adjustment_group_co_no');
		//popup mobile
		Route::post('/create_popup/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'create_with_popup'])->name('register_adjustment_group_popup_popup');
        //Route::patch('{adjustment}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'update'])->name('update_adjustment_group');
        Route::patch('/update_adjustment_group', [\App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'updateAG']);
		//
        Route::get('/{co_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'get_all'])->name('get_all_adjustment_group');
        Route::delete('/{ag_no}', [App\Http\Controllers\Adjustment\AdjustmentGroupController::class, 'delete'])->name('delete_adjustment_group');
    });

    Route::prefix('co_address')->name('co_address.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'create'])->name('register_co_address');
        Route::post('/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'create_with_co_no'])->name('register_co_address_no_no');
		//popup mobile
		Route::post('/create_popup/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'create_with_popup'])->name('register_co_address_popup');
		Route::patch('/update_co_address', [\App\Http\Controllers\CoAddress\CoAddressController::class, 'updateCA']);
		//
        Route::get('/{co_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'get_all'])->name('get_all_co_address');
        Route::delete('/{ca_no}', [App\Http\Controllers\CoAddress\CoAddressController::class, 'delete'])->name('delete_co_address');
    });

    Route::get('/get_manager/{co_no}', [App\Http\Controllers\Manager\ManagerController::class, 'getManager'])->name('get_manager');
    Route::post('/register_manager', [App\Http\Controllers\Manager\ManagerController::class, 'create'])->name('register_manager');
    Route::post('/register_manager/mobile', [App\Http\Controllers\Manager\ManagerController::class, 'create_mobile'])->name('register_manager_mobile');
    //popup mobile
    Route::post('/register_manager/create_popup/{co_no}', [App\Http\Controllers\Manager\ManagerController::class, 'create_with_popup'])->name('register_manager_popup');
    Route::patch('/register_manager/update_register_manager', [App\Http\Controllers\Manager\ManagerController::class, 'updateRM'])->name('update_manager_popup');
    //
    Route::post('/delete_manager', [App\Http\Controllers\Manager\ManagerController::class, 'delete'])->name('delete_manager');
    Route::patch('/update_manager/{manager}', [App\Http\Controllers\Manager\ManagerController::class, 'update'])->name('update_manager');

    Route::post('/create_menu', [\App\Http\Controllers\Menu\MenuController::class, 'create']);
    Route::post('/menu', [\App\Http\Controllers\Menu\MenuController::class, 'menu']);
    Route::get('/menu/{menu_no}', [\App\Http\Controllers\Menu\MenuController::class, 'get_menu']);
    Route::post('/update_menu', [\App\Http\Controllers\Menu\MenuController::class, 'update_menu']);
    Route::delete('/delete_menu/{menu_no}', [\App\Http\Controllers\Menu\MenuController::class, 'delete_menu']);
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
        Route::post('/suneditor', [App\Http\Controllers\Manual\ManualController::class, 'suneditor'])->name('update_manual_suneditor');
    });

    Route::prefix('forwarder_info')->name('forwarder_info.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'create'])->name('register_forwarder_info');
        Route::post('/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'create_with_co_no'])->name('create_with_co_no');
        Route::get('/{co_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'get_all'])->name('get_all_forwarder_info');
        Route::delete('/{fi_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'delete'])->name('delete_forwarder_info');

        Route::post('/create_popup/{fi_no}', [App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'create_with_popup'])->name('register_forwarder_info_popup');
        Route::patch('/update_forwarder_info', [\App\Http\Controllers\ForwarderInfo\ForwarderInfoController::class, 'updateFI']);
    });

    Route::prefix('customs_info')->name('customs_info.')->group(function () {
        Route::post('/create_or_update/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'create'])->name('register_customs_info');
        Route::post('/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'create_with_co_no'])->name('create_with_co_no');
        Route::get('/{co_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'get_all'])->name('get_all_customs_info');
        Route::delete('/{ci_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'delete'])->name('delete_customs_info');

        Route::post('/create_popup/{ci_no}', [App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'create_with_popup'])->name('register_customs_info_popup');
        Route::patch('/update_customs_info', [\App\Http\Controllers\CustomsInfo\CustomsInfoController::class, 'updateCI']);
    });
    Route::middleware('role:spasys_admin,spasys_manager')->prefix('permission')->name('permission.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Permission\PermissionController::class, 'getMenu'])->name('get_menu');
        Route::post('/save', [\App\Http\Controllers\Permission\PermissionController::class, 'savePermission'])->name('save_permission');
    });

    Route::prefix('report')->name('report.')->group(function () {
        Route::post('/', \App\Http\Controllers\Report\ReportController::class)->name('registe_update_report');
        Route::post('/all', [App\Http\Controllers\Report\ReportController::class, 'getReports'])->name('get_reports');
        Route::post('/mobi/all', [App\Http\Controllers\Report\ReportController::class, 'getReportsMobi'])->name('get_reports_mobi');
        Route::get('/{report_no}', [App\Http\Controllers\Report\ReportController::class, 'getReport'])->name('get_report');
        Route::delete('/{report}', [App\Http\Controllers\Report\ReportController::class, 'deleteReport'])->name('delete_report');
    });

    Route::prefix('item')->name('item.')->group(function () {
        Route::get('/get_item', [App\Http\Controllers\Item\ItemController::class, 'getItems'])->name('get_item');
        Route::post('/post_item', [App\Http\Controllers\Item\ItemController::class, 'postItems'])->name('post_item');
        Route::post('/post_item_api', [App\Http\Controllers\Item\ItemController::class, 'postItemsApi'])->name('post_item_api');
        Route::post('/post_item_popup', [App\Http\Controllers\Item\ItemController::class, 'postItemsPopup'])->name('post_item_popup');
        Route::post('/post_item_popup_api', [App\Http\Controllers\Item\ItemController::class, 'postItemsPopupApi'])->name('post_item_popup_api');
        Route::post('/import_items', [App\Http\Controllers\Item\ItemController::class, 'importItemsList'])->name('import_items_list');
        Route::post('/post_item_chk', [App\Http\Controllers\Item\ItemController::class, 'postItemschk'])->name('post_item_chk');
        Route::get('/', [App\Http\Controllers\Item\ItemController::class, 'searchItems'])->name('search');
        Route::post('/paginate', [App\Http\Controllers\Item\ItemController::class, 'paginateItems'])->name('paginate');
        Route::post('/paginateapi', [App\Http\Controllers\Item\ItemController::class, 'paginateItemsApi'])->name('paginateapi');
        Route::post('/paginateapiid', [App\Http\Controllers\Item\ItemController::class, 'paginateItemsApiId'])->name('paginateapiid');

        Route::post('/paginateapi_stock', [App\Http\Controllers\Item\ItemController::class, 'paginateItemsApiStock'])->name('paginateapi_stock');

        Route::post('/', \App\Http\Controllers\Item\ItemController::class)->name('create_or_update');
        Route::post('/update_file', [\App\Http\Controllers\Item\ItemController::class, 'updateFile'])->name('update_file');
        Route::get('/{item}', [App\Http\Controllers\Item\ItemController::class, 'getItemById'])->name('get_item_by_id');
        Route::delete('/item_channel/{item_channel}', [App\Http\Controllers\Item\ItemController::class, 'deleteItemChannel'])->name('delete_item_channel');
        Route::post('/import_excel', [App\Http\Controllers\Item\ItemController::class, 'importItems'])->name('import_items');
        Route::post('/api_item', [App\Http\Controllers\Item\ItemController::class, 'apiItems'])->name('api_item');
        Route::post('/api_item_cargo_list', [App\Http\Controllers\Item\ItemController::class, 'apiItemsCargoList'])->name('api_item_cargo_list');
        Route::post('/api_update_stock_items', [App\Http\Controllers\Item\ItemController::class, 'apiupdateStockItems'])->name('api_update_stock_items');
        Route::post('/caculate_total_item', [App\Http\Controllers\Item\ItemController::class, 'caculateItem'])->name('caculate_total_item');


    });
    Route::prefix('scheduleshipment')->name('scheduleshipment.')->group(function () {
        Route::post('/paginate', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'paginateScheduleShipments'])->name('paginate');
        Route::post('/api_schedule_shipments', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'apiScheduleShipments'])->name('api_schedule_shipments');
        Route::get('/{scheduleshipment}', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'getScheduleShipmentById'])->name('get_schedule_shipment_by_id');
        Route::delete('/schedule_shipment_info/{schedule_shipment_info}', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'deleteScheduleShipmentInfo'])->name('delete_schedule_shipment_info');
        Route::delete('/schedule_shipment/{schedule_shipment}', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'deleteScheduleShipment'])->name('delete_schedule_shipment');
        Route::post('/create_or_update', [\App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'CreateOrUpdateByCoPu'])->name('create_or_update');
        Route::get('/get_schedule_shipment_info_by_co_no/{co_no}', [App\Http\Controllers\ScheduleShipment\ScheduleShipmentController::class, 'getScheduleShipmentInfoByCono'])->name('get_schedule_shipment_info_by_co_no');
    });
    Route::prefix('contractwms')->name('contractwms.')->group(function () {
        Route::delete('/contractwms/{contract_wms}', [App\Http\Controllers\ContractWms\ContractwmsController::class, 'deleteContractWms'])->name('delete_contract_wms');
        Route::post('/create_or_update', [\App\Http\Controllers\ContractWms\ContractwmsController::class, 'CreateOrUpdateByCoPu'])->name('create_or_update');
        Route::get('/get_contractwms_by_co_no/{co_no}', [App\Http\Controllers\ContractWms\ContractwmsController::class, 'getContractWmsByCono'])->name('get_contractwms_by_co_no');
    });


    Route::prefix('rate_data_send_meta')->name('rate_data_send_meta.')->group(function () {
        Route::post('/', \App\Http\Controllers\RateMeta\RateMetaController::class)->name('registe_rdsm');
        Route::get('/{rm_no}', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getrm'])->name('get_rm');
        Route::patch('/{rm}', [\App\Http\Controllers\RateMeta\RateMetaController::class, 'updaterm']);
        Route::post('/all', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getRateData'])->name('get_rate_data');
        Route::post('/all_company', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getRateDataCompany'])->name('get_rate_data_company');
    });

    Route::prefix('rate_meta_data')->name('rate_data_send_meta.')->group(function () {
        Route::get('/get_RMD_data/{rmd_no}', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'get_RMD_data'])->name('get_rmd_data');
        Route::post('/rm', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'getAllRM'])->name('get_all_rm');
        Route::post('/co', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'getAllCO'])->name('get_all_co');
        Route::post('/get_precalculate_details', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'getAllCOPrecalculateDetails'])->name('get_all_co_precalculate_details');
    });

    Route::prefix('rate_data')->name('rate_data.')->group(function () {
        Route::middleware('role:spasys_manager,spasys_admin,spasys_operator,shop_manager,shop_operator,shipper_manager,shipper_operator')->group(function () {
            Route::post('/spasys', [\App\Http\Controllers\RateData\RateDataController::class, 'spasysRegisterRateData'])->name('spasys_registe_rate_data');
            Route::post('/spasys2', [\App\Http\Controllers\RateData\RateDataController::class, 'spasysRegisterRateData2'])->name('spasys_registe_rate_data2');
            Route::get('/spasys/{co_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData'])->name('get_spasys_rate_data');
            Route::get('/spasys2', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData2'])->name('get_spasys_rate_data2');
            Route::get('/spasys3', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData3'])->name('get_spasys_rate_data3');

            //GET RATE DATA BY RGD_NO
            Route::get('/spasys/{rgd_no}/{service}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByRgd']);

            Route::post('/spasys4', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData4'])->name('get_spasys_rate_data4');
        });

        Route::get('/monthly_bill_list/{rgd_no}/{bill_type}', [\App\Http\Controllers\RateData\RateDataController::class, 'monthly_bill_list'])->name('monthly_bill_list');
        Route::get('/monthly_bill_list_edit/{rgd_no}/{bill_type}', [\App\Http\Controllers\RateData\RateDataController::class, 'monthly_bill_list_edit'])->name('monthly_bill_list_edit');
        Route::get('/bonded_monthly_bill_list/{rgd_no}/{bill_type}', [\App\Http\Controllers\RateData\RateDataController::class, 'bonded_monthly_bill_list'])->name('bonded_monthly_bill_list');
        Route::get('/bonded_monthly_bill_list_edit/{rgd_no}/{bill_type}', [\App\Http\Controllers\RateData\RateDataController::class, 'bonded_monthly_bill_list_edit'])->name('bonded_monthly_bill_list_edit');
        Route::get('/get_spasys1_from_te/{is_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'getspasys1fromte'])->name('get_spasys1_from_te');
        Route::get('/get_spasys2_from_te/{is_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'getspasys2fromte'])->name('get_spasys2_from_te');
        Route::get('/get_spasys3_from_te/{is_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'getspasys3fromte'])->name('get_spasys3_from_te');

        //FOR SETTLEMENT 보세화물
        Route::get('/get_spasys1_from_logistic_number/{is_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'getspasys1fromlogisticnumber']);
        //REGISTER GENERAL DATA 유통가공
        Route::post('/general', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general'])->name('registe_rate_data_general');
        Route::post('/general_final', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_final'])->name('registe_rate_data_general_final');
        Route::post('/general_monthly_final', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_monthly_final'])->name('registe_rate_data_general_monthly_final');
        Route::post('/general_additional', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_additional'])->name('registe_rate_data_general_additional');
        Route::post('/general_additional2', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_additional2'])->name('registe_rate_data_general_additional2');

        //REGISTER GENERAL DATA FOR 수입풀필먼트 SERVICE
        Route::post('/general_final_service2', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_final_service2'])->name('registe_rate_data_general_final_service2');
        Route::post('/general_final_service2_mobile', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_final_service2_mobile'])->name('registe_rate_data_general_final_service2_mobile');

        //REGISTER GENERAL DATA FOR 보세화물 SERVICE
        Route::post('/general_data_service1', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_service1'])->name('registe_rate_data_general_service1');
        Route::post('/general_data_service1_final', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_service1_final'])->name('registe_rate_data_general_service1_final');
        Route::post('/general_monthly_final_bonded', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_monthly_final_bonded'])->name('registe_rate_data_general_monthly_final_bonded');
        Route::post('/update_storage_days', [\App\Http\Controllers\RateData\RateDataController::class, 'update_storage_days'])->name('update_storage_days');

        Route::post('/tax_invoice_issue', [\App\Http\Controllers\RateData\RateDataController::class, 'tax_invoice_issue']);
        Route::post('/get_tax_invoice_by_rgd_no', [\App\Http\Controllers\RateData\RateDataController::class, 'get_tax_invoice_by_rgd_no']);


        //distribution_est_casebill_edit
        Route::get('/download_data_general/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_data_general']);
        //distribution_add_casebill_edit
        Route::get('/download_data_casebill_edit/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_data_casebill_edit']);
        //distribution_final_casebill_edit
        Route::get('/download_final_case_bill/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_final_case_bill']);
        //distribution_final_monthbill_edit
        Route::get('/download_final_month_bill/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_final_month_bill']);
        //distribution_est_monthbill_edit
        Route::get('/download_est_month_bill/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_est_month_bill']);
        //distribution_add_monthbill_edit
        Route::get('/download_add_month_bill/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_add_month_bill']);
        //distribution_est_monthbill_check
        Route::get('/download_est_month_check/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_est_month_check']);
        //distribution_final_casebill_check
        Route::get('/download_distribution_final/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_distribution_final']);
        //fulfillment_final_monthbill_check
        Route::get('/download_full_fillment_final/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_full_fillment_final']);
        //distribution_final_monthbill_issue
        Route::post('/download_final_monthbill_issue',[\App\Http\Controllers\RateData\RateDataController::class, 'download_final_monthbill_issue']);
        //distribution_add_casebill_issue
        Route::get('/download_add_casebill_issue/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_add_casebill_issue']);
        //distribution_add_monthbill_issue
        Route::post('/download_distribution_monthbill',[\App\Http\Controllers\RateData\RateDataController::class, 'download_distribution_monthbill']);
        //distribution_final_monthbill_check
        Route::post('/download_distribution_final_monthbill',[\App\Http\Controllers\RateData\RateDataController::class, 'download_distribution_final_monthbill']);
        //fulfillment_final_monthbill_edit
        Route::get('/download_fulfillment_final_monthbill/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_fulfillment_final_monthbill']);
        //fulfillment_add_monthbill_issue
        Route::get('/download_fulfillment_additional/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'download_fulfillment_additional']);
        //fulfillment_add_monthbill_check
        Route::get('/fulfillment_add_monthbill_check/{rgd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'fulfillment_add_monthbill_check']);

        //GET GENERAL DATA FOLLOW BILL TYPE
        Route::get('/general/{rgd_no}/{bill_type}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general'])->name('get_rate_data_general');
        //GET FINAL BILL DATA
        Route::get('/general_final/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_final'])->name('get_rate_data_general_final');
        Route::get('/general_final2/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_final2'])->name('get_rate_data_general_final2');
        Route::get('/general_monthly_final/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_monthly_final'])->name('get_rate_data_general_monthly_final');
        Route::get('/general_monthly_final2/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_monthly_final2'])->name('get_rate_data_general_monthly_final2');


        //GET GENERAL BILL FOR CREATE ADDITIONAL MONTHLY BILL PAGE 270
        Route::get('/general_monthly_additional/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_monthly_additional'])->name('get_rate_data_general_monthly_additional');
        //GET GENERAL BILL FOR EDIT ADDITIONAL BILL PAGE
        Route::get('/general_additional2/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional2'])->name('get_rate_data_general_additional2');
        //GET GENERAL BILL FOR CREATE ADDITIONAL CASE BILL PAGE 268
        Route::get('/general_additional/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional'])->name('get_rate_data_general_additional');
        //GET GENERAL BILL FOR CREATE ADDITIONAL CASE BILL PAGE 268 POPUP
        Route::get('/general_additional3/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional3'])->name('get_rate_data_general_additional3');


        Route::post('/', \App\Http\Controllers\RateData\RateDataController::class)->name('registe_rate_data');
        //REGISTER RATE DATA
        Route::post('/set_data',[\App\Http\Controllers\RateData\RateDataController::class, 'register_set_data'])->name('registe_set_data');

        //REGISTER RATE DATA FOR 보세화물 PRECALCULATE
        Route::post('/set_data_precalculate',[\App\Http\Controllers\RateData\RateDataController::class, 'register_set_data_precalculate'])->name('registe_set_data_precalculate');
        Route::post('/register_data_general_precalculate',[\App\Http\Controllers\RateData\RateDataController::class, 'register_data_general_precalculate']);
        Route::get('/get_set_data_precalculate/{rmd_no}/{meta_cate}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_set_data_precalculate']);
        Route::get('/get_data_general_precalculate/{rmd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_data_general_precalculate']);

        //GET RATE DATA
        Route::get('/get_set_data/{rmd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_set_data'])->name('get_set_data');
        Route::get('/get_set_data_mobile/{bill_type}/{rmd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_set_data_mobile'])->name('get_set_data2');
        //GET RATE META DATA
        Route::get('/get_rmd_no/{rgd_no}/{set_type}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_rmd_no'])->name('get_rmd_no');
        Route::get('/get_rmd_no_fulfill/{rgd_no}/{type}/{pretype}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_rmd_no_fulfill'])->name('get_rmd_no_fulfill');
        Route::delete('/delete_row_rate_data/{rd_no}/',[\App\Http\Controllers\RateData\RateDataController::class, 'deleteRowRateData'])->name('delete_row_rate_data');
        //DELETE SET RATE DATA FOR BONDED SERVICE
        Route::delete('/delete_set_rate_data/{rd_no}/',[\App\Http\Controllers\RateData\RateDataController::class, 'deleteSetRateData']);


        Route::get('/by_rm_no/{rm_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateData'])->name('get_rate_data');
        Route::get('/by_co_no/{rd_co_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByCono'])->name('get_rate_data_by_co_no');

        Route::post('/send_mail', [App\Http\Controllers\RateData\RateDataController::class, 'sendMail'])->name('send_mail');
        //quotation_send_details
        Route::get('/download_excel_send_meta/{rm_no}/{rmd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'download_excel_send_meta']);
        Route::delete('/delete_rate_data/{rm_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'deleteRateData'])->name('delete_rate_data');
        //cancel_bill
        Route::post('/cancel_bill', [\App\Http\Controllers\RateData\RateDataController::class,'cancel_bill'])->name('cancel_bill');
        Route::post('/get_list_cancel_bill', [\App\Http\Controllers\RateData\RateDataController::class,'get_list_cancel_bill'])->name('get_list_cancel_bill');

    });

    Route::prefix('alarm')->name('alarm.')->group(function () {
        Route::post('/', \App\Http\Controllers\Alarm\AlarmController::class)->name('registe_or_update_alarm');
        Route::get('/{alarm}', [App\Http\Controllers\Alarm\AlarmController::class, 'getAlarmById'])->name('get_alarm_by_id');
        Route::post('/search', [App\Http\Controllers\Alarm\AlarmController::class, 'searchAlarms'])->name('search');
        Route::post('/search_mobile', [App\Http\Controllers\Alarm\AlarmController::class, 'searchAlarmsMobile'])->name('searchmobile');
    });


    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/settlement_amount_trend_by_month', [App\Http\Controllers\Orders\OrdersController::class, 'settlementAmountTrendByMonth'])->name('settlement_amount_trend_by_month');
        Route::get('/settlement_amount_trend', [App\Http\Controllers\Orders\OrdersController::class, 'settlementAmountTrend'])->name('settlement_amount_trend');
    });

    Route::post('get_import_data', [\App\Http\Controllers\Import\ImportController::class,'get_import_data'])->name('get_import_data');
    Route::post('get_import_data_api', [\App\Http\Controllers\Import\ImportController::class,'get_import_data_api'])->name('get_import_data_api');
    Route::post('get_export_data', [\App\Http\Controllers\Export\ExportController::class,'get_export_data'])->name('get_export_data');
    Route::post('/download_distribution_stocklist', [\App\Http\Controllers\Excel\ExportExcelController::class,'download_distribution_stocklist'])->name('download_distribution_stocklist');
    Route::post('/dowload_fulfillment_stock_list', [\App\Http\Controllers\Excel\ExportExcelController::class,'dowload_fulfillment_stock_list'])->name('dowload_fulfillment_stock_list');
    Route::post('/download_bonded_cargo', [\App\Http\Controllers\Excel\ExportExcelController::class,'download_bonded_cargo'])->name('download_bonded_cargo');
    Route::post('get_package_data', [\App\Http\Controllers\Package\PackageController::class,'get_package_data'])->name('get_package_data');

    Route::post('/import_excel_distribution', [\App\Http\Controllers\Warehousing\WarehousingController::class,'importExcelDistribution']);
    Route::post('/update_rgd_status3', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class, 'updateRgdState3'])->name('update_rgd_status3');

    Route::post('/schedule_list_import', [\App\Http\Controllers\Warehousing\WarehousingController::class, 'scheduleListImport']);
    Route::post('/save_import_storeday', [\App\Http\Controllers\Import\ImportController::class,'save_import_storeday'])->name('save_import_storeday');
    Route::post('/download_bonded_settlement', [\App\Http\Controllers\Warehousing\WarehousingController::class,'downloadBondedSettlement']);
    Route::post('/download_final_month_bill_issue', [\App\Http\Controllers\RateData\RateDataController::class, 'download_final_month_bill_issue']);
    Route::post('/download_est_casebill', function(){
        $data['status'] = 1;
        return json_encode($data);
    });


    Route::get('/fulfillment_billing', [\App\Http\Controllers\Warehousing\WarehousingController::class, 'fulfillment_billing']);
    Route::post('/fulfillment_create_billing', [\App\Http\Controllers\Warehousing\WarehousingController::class, 'fulfillment_create_billing']);

    Route::post('/get_warehousing_status', [\App\Http\Controllers\WarehousingStatus\WarehousingStatusController::class, 'getWarehousingStatus']);
    Route::post('/get_warehousing_status_mobile', [\App\Http\Controllers\WarehousingStatus\WarehousingStatusController::class, 'getWarehousingStatusMobile']);
    Route::get('/load_table_top_right/{rgd_no}', [\App\Http\Controllers\Warehousing\WarehousingController::class, 'load_table_top_right']);
});

