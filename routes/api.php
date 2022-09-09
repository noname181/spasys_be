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

    // Manger Role
    Route::middleware('role:spasys_manager,spasys_admin,shop_manager')->group(function () {
        Route::post('/register_company', \App\Http\Controllers\Company\CompanyController::class)->name('register_company');
    });
    Route::post('/get_companies', [App\Http\Controllers\Company\CompanyController::class, 'getCompanies'])->name('get_companies');

    Route::get('/get_company/{co_no}', [App\Http\Controllers\Company\CompanyController::class, 'getCompany'])->name('get_company');
    Route::patch('/update_company/{company}', [App\Http\Controllers\Company\CompanyController::class, 'updateCompany'])->name('update_company');
    Route::post('/register_contract', \App\Http\Controllers\Contract\ContractController::class)->name('register_contract');
    Route::post('/get_shop_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShopCompanies'])->name('get_shop_companies');
    Route::post('/get_shipper_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getShipperCompanies'])->name('get_shipper_companies');
    Route::post('/get_item_companies', [\App\Http\Controllers\Company\CompanyController::class, 'getItemCompanies'])->name('get_item_companies');

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
        Route::post('/get_banners', [App\Http\Controllers\Banner\BannerController::class, 'getBanner'])->name('get_banners');
    });

    Route::post('/notices', [\App\Http\Controllers\Notice\NoticeController::class, 'create']);
    Route::get('/notices/{id}', [\App\Http\Controllers\Notice\NoticeController::class, 'getNoticeById']);
    Route::patch('/update_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'update']);
    Route::get('/notices', [\App\Http\Controllers\Notice\NoticeController::class,'__invoke']);
    Route::post('/search_notices', [\App\Http\Controllers\Notice\NoticeController::class,'searchNotice']);
    Route::post('/get_notices', [\App\Http\Controllers\Notice\NoticeController::class, 'getNotice']);
    Route::post('/delete', [\App\Http\Controllers\Notice\NoticeController::class, 'delete']);

    Route::get('/import_schedule', [\App\Http\Controllers\ImportSchedule\ImportScheduleController::class,'__invoke']);
    Route::post('/get_import_schedule', [\App\Http\Controllers\ImportSchedule\ImportScheduleController::class,'getImportSchedule']);

    Route::get('/warehousing', [\App\Http\Controllers\Warehousing\WarehousingController::class,'__invoke']);
    Route::get('/get_warehousing/{w_no}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingById']);
    Route::get('/get_warehousing_from_rgd/{rgd_no}', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingByRgd']);

    Route::post('/get_warehousing', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousing']);
    Route::post('/get_warehousing_export', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingExport']);
    Route::post('/get_warehousing_import', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImport']); //page 129
    Route::post('/get_warehousing_import_status1', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingImportStatus1']); //page 134

    Route::post('/get_warehousing_status1', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingStatus1']); //page 140
    Route::post('/get_warehousing_export_status12', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingExportStatus12']); //page 144, Page259
    Route::post('/get_warehousing_export_status4', [\App\Http\Controllers\Warehousing\WarehousingController::class,'getWarehousingExportStatus4']); //page 263


    Route::get('/warehousing_request', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'__invoke']);
    Route::post('/get_warehousing_request', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'getWarehousingRequest']);
    Route::post('/create', [\App\Http\Controllers\WarehousingRequest\WarehousingRequestController::class,'createWarehousingRequest']);

    Route::get('receiving_goods_delivery/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'__invoke']);
    Route::post('receiving_goods_delivery/update_status4', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'update_status4']);
    Route::get('receiving_goods_delivery/get_rgd/{is_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'getReceivingGoodsDelivery']);
    Route::get('receiving_goods_delivery/get_rgd_warehousing/{w_no}', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'getReceivingGoodsDeliveryWarehousing']);
    //130
    Route::post('receiving_goods_delivery/warehousing/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing'])->name('rgd_warehousing');
    //141
    Route::post('receiving_goods_delivery/warehousing_release/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_warehousing_release'])->name('rgd_warehousing_release');
    Route::post('receiving_goods_delivery/import_schedule/rgd', [\App\Http\Controllers\ReceivingGoodsDelivery\ReceivingGoodsDeliveryController::class,'create_import_schedule'])->name('rgd_import_schedule');

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
        Route::post('/post_item_popup', [App\Http\Controllers\Item\ItemController::class, 'postItemsPopup'])->name('post_item_popup');
        Route::post('/import_items', [App\Http\Controllers\Item\ItemController::class, 'importItemsList'])->name('import_items_list');
        Route::post('/post_item_chk', [App\Http\Controllers\Item\ItemController::class, 'postItemschk'])->name('post_item_chk');
        Route::get('/', [App\Http\Controllers\Item\ItemController::class, 'searchItems'])->name('search');
        Route::post('/paginate', [App\Http\Controllers\Item\ItemController::class, 'paginateItems'])->name('paginate');
        Route::post('/paginateapi', [App\Http\Controllers\Item\ItemController::class, 'paginateItemsApi'])->name('paginateapi');
        Route::post('/', \App\Http\Controllers\Item\ItemController::class)->name('create_or_update');
        Route::post('/update_file', [\App\Http\Controllers\Item\ItemController::class, 'updateFile'])->name('update_file');
        Route::get('/{item}', [App\Http\Controllers\Item\ItemController::class, 'getItemById'])->name('get_item_by_id');
        Route::delete('/item_channel/{item_channel}', [App\Http\Controllers\Item\ItemController::class, 'deleteItemChannel'])->name('delete_item_channel');
        Route::post('/import_excel', [App\Http\Controllers\Item\ItemController::class, 'importItems'])->name('import_items');
        Route::post('/api_item', [App\Http\Controllers\Item\ItemController::class, 'apiItems'])->name('api_item');
        Route::post('/api_update_stock_items', [App\Http\Controllers\Item\ItemController::class, 'apiupdateStockItems'])->name('api_update_stock_items');

    });

    Route::prefix('rate_data_send_meta')->name('rate_data_send_meta.')->group(function () {
        Route::post('/', \App\Http\Controllers\RateMeta\RateMetaController::class)->name('registe_rdsm');
        Route::get('/{rm_no}', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getrm'])->name('get_rm');
        Route::patch('/{rm}', [\App\Http\Controllers\RateMeta\RateMetaController::class, 'updaterm']);
        Route::post('/all', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getRateData'])->name('get_rate_data');
        Route::post('/all_company', [App\Http\Controllers\RateMeta\RateMetaController::class, 'getRateDataCompany'])->name('get_rate_data_company');
    });

    Route::prefix('rate_meta_data')->name('rate_data_send_meta.')->group(function () {
        Route::post('/rm', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'getAllRM'])->name('get_all_rm');
        Route::post('/co', [App\Http\Controllers\RateMetaData\RateMetaDataController::class, 'getAllCO'])->name('get_all_co');
    });

    Route::prefix('rate_data')->name('rate_data.')->group(function () {
        Route::middleware('role:spasys_manager,spasys_admin,spasys_operator,shop_manager,shop_operator')->group(function () {
            Route::post('/spasys', [\App\Http\Controllers\RateData\RateDataController::class, 'spasysRegisterRateData'])->name('spasys_registe_rate_data');
            Route::get('/spasys', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData'])->name('get_spasys_rate_data');
            Route::get('/spasys2', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData2'])->name('get_spasys_rate_data2');
            Route::get('/spasys3', [App\Http\Controllers\RateData\RateDataController::class, 'getSpasysRateData3'])->name('get_spasys_rate_data3');
        });
        Route::post('/general', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general'])->name('registe_rate_data_general');
        Route::post('/general_final', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_final'])->name('registe_rate_data_general_final');
        Route::post('/general_additional', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_additional'])->name('registe_rate_data_general_additional');
        Route::post('/general_additional2', [\App\Http\Controllers\RateData\RateDataController::class, 'registe_rate_data_general_additional2'])->name('registe_rate_data_general_additional2');
        Route::get('/general/{rgd_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general'])->name('get_rate_data_general');
        Route::get('/general_final/{w_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_final'])->name('get_rate_data_general_final');
        Route::get('/general_final2/{w_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_final2'])->name('get_rate_data_general_final2');
        Route::get('/general_additional/{w_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional'])->name('get_rate_data_general_additional');
        Route::get('/general_additional2/{w_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional2'])->name('get_rate_data_general_additional2');
        Route::get('/general_additional3/{w_no}', [\App\Http\Controllers\RateData\RateDataController::class, 'get_rate_data_general_additional3'])->name('get_rate_data_general_additional3');

        Route::post('/', \App\Http\Controllers\RateData\RateDataController::class)->name('registe_rate_data');
        Route::post('/set_data',[\App\Http\Controllers\RateData\RateDataController::class, 'register_set_data'])->name('registe_set_data');
        Route::post('/set_data_final',[\App\Http\Controllers\RateData\RateDataController::class, 'register_set_data_final'])->name('registe_set_data_final');
        Route::post('/set_data_additional',[\App\Http\Controllers\RateData\RateDataController::class, 'register_set_data_final'])->name('registe_set_data_additional');
        Route::get('/get_set_data/{rmd_no}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_set_data'])->name('get_set_data');
        Route::get('/get_rmd_no/{w_no}/{set_type}',[\App\Http\Controllers\RateData\RateDataController::class, 'get_rmd_no'])->name('get_rmd_no');

        Route::get('/{rm_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateData'])->name('get_rate_data');
        Route::get('/by_co_no/{rd_co_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByCono'])->name('get_rate_data_by_co_no');
        Route::get('/by_rm_no/{rm_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByRmno'])->name('get_rate_data_by_rm_no');
        Route::get('/importfulfillment/by_rm_no/{rm_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByImportFulfillmentByRmno'])->name('get_rate_data_by_importfulfillment_rmno');
        Route::get('/importfulfillment/by_co_no/{co_no}/{rmd_no}', [App\Http\Controllers\RateData\RateDataController::class, 'getRateDataByImportFulfillmentByCono'])->name('get_rate_data_by_importfulfillment_cono');
        Route::post('/importfulfillment', [App\Http\Controllers\RateData\RateDataController::class, 'createOrUpdateImportFulfillment'])->name('update_or_create_importfulfillment');
        Route::post('/send_mail', [App\Http\Controllers\RateData\RateDataController::class, 'sendMail'])->name('send_mail');
    });

    Route::prefix('alarm')->name('alarm.')->group(function () {
        Route::post('/', \App\Http\Controllers\Alarm\AlarmController::class)->name('registe_or_update_alarm');
        Route::get('/{alarm}', [App\Http\Controllers\Alarm\AlarmController::class, 'getAlarmById'])->name('get_alarm_by_id');
        Route::get('/', [App\Http\Controllers\Alarm\AlarmController::class, 'searchAlarms'])->name('search');
    });


    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/settlement_amount_trend_by_month', [App\Http\Controllers\Orders\OrdersController::class, 'settlementAmountTrendByMonth'])->name('settlement_amount_trend_by_month');
        Route::get('/settlement_amount_trend', [App\Http\Controllers\Orders\OrdersController::class, 'settlementAmountTrend'])->name('settlement_amount_trend');
    });

    Route::post('get_import_data', [\App\Http\Controllers\Import\ImportController::class,'get_import_data'])->name('get_import_data');
    Route::post('get_export_data', [\App\Http\Controllers\Export\ExportController::class,'get_export_data'])->name('get_export_data');
});
