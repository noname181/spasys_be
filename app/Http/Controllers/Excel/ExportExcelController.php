<?php

namespace App\Http\Controllers\Excel;
use File;
use App\Utils\Messages;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Http\Requests\Item\ItemSearchRequest;
use App\Http\Requests\ImportSchedule\ImportScheduleSearchRequest;
use App\Http\Requests\Warehousing\WarehousingSearchRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\WarehousingItem;
use App\Models\ImportSchedule;
use App\Models\StockStatusBad;
use App\Models\ReceivingGoodsDelivery;
use App\Models\Import;
use App\Models\ImportExpected;
use App\Models\Export;
use App\Models\ExportConfirm;

class ExportExcelController extends Controller
{
    public function download_distribution_stocklist(ItemSearchRequest $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');
        $validated = $request->validated();
        try {
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 100000;

            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if($user->mb_type == 'shop'){
                $item = Item::with(['file', 'company','item_channels','warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }else if ($user->mb_type == 'shipper'){
                $item = Item::with(['file', 'company','item_channels','warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }else if($user->mb_type == 'spasys'){
                $item = Item::with(['file', 'company','item_channels','warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent.co_parent',function($q) use ($user){
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }
            if (isset($validated['from_date'])) {
                $item->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $item->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            if (isset($validated['co_name_shop'])) {
                $item->whereHas('company.co_parent',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_name_shop']) .'%');
                });
            }
            if (isset($validated['co_name_agency'])) {
                $item->whereHas('company',function($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_name_agency']) .'%', 'and' , 'co_type' , '=' , 'shipper');
                });
            }
            if (isset($validated['item_name'])) {
                $item->where(function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_name)'), 'like','%'. strtolower($validated['item_name']) .'%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $item->where(function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_cargo_bar_code)'), 'like','%'. strtolower($validated['item_cargo_bar_code']) .'%');
                });
            }
            if (isset($validated['item_channel_code'])) {
                $item->whereHas('item_channels',function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like','%'. strtolower($validated['item_channel_name']) .'%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $item->where(function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_bar_code)'), 'like','%'. strtolower($validated['item_bar_code']) .'%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $item->where(function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_upc_code)'), 'like','%'. strtolower($validated['item_upc_code']) .'%');
                });
            }
            if (isset($validated['item_channel_name'])) {
                $item->whereHas('item_channels',function($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like','%'. strtolower($validated['item_channel_name']) .'%');
                });
            }

            $item2 = $item->get();

            $total_remain = 0;
            $total_get = 0;
            DB::enableQueryLog();
            $item3 = collect($item2)->map(function ($q){
                $item4 = Item::where('item_no', $q->item_no)->first();
                $total_get = WarehousingItem::where('item_no', $q->item_no)->where('wi_type','입고_spasys')->sum('wi_number');
                $total_give = WarehousingItem::where('item_no', $q->item_no)->where('wi_type','출고_spasys')->sum('wi_number');
                $total = $total_get - $total_give;
                return [ 'total_amount' => $total ,  'total_price' => $item4->item_price2 * $total];
            });

            $item = $item->paginate($per_page, ['*'], 'page', $page);

            $item5 = $item3->sum('total_amount');
            $item6 = $item3->sum('total_price');




            $custom = collect(['sum1' => $item5,'sum2'=>$item6]);

            $item->setCollection(
                $item->getCollection()->map(function ($q){
                    $item = Item::with(['warehousing_item'])->where('item_no', $q->item_no)->first();
                    $total_get = WarehousingItem::where('item_no', $q->item_no)->where('wi_type','입고_spasys')->sum('wi_number');
                    $total_give = WarehousingItem::where('item_no', $q->item_no)->where('wi_type','출고_spasys')->sum('wi_number');
                    $total = $total_get - $total_give;
                    $q->total_price_row = $item->item_price2 * $total;
                    $q->item_total_amount = $total;
                    return $q;
                })
            );

            $data = $custom->merge($item);
            $data_export = $data['data'];
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

            $sheet->setCellValue('A1', 'No'); // item_no
            $sheet->setCellValue('B1', '가맹점'); // co_name_shop - company.co_parent.co_name
            $sheet->setCellValue('C1', '화주'); // co_name_agency - company.co_name
            $sheet->setCellValue('D1', '브랜드'); // item_brand
            $sheet->setCellValue('E1', '상품명'); // item_name
            $sheet->setCellValue('F1', '옵션1'); // item_option1
            $sheet->setCellValue('G1', '옵션2'); // item_option2
            $sheet->setCellValue('H1', '등급'); // NULL
            $sheet->setCellValue('I1', '수량');
            $sheet->setCellValue('J1', '상품단가');
            $sn = 2;
            foreach ($data_export as $data) {
                $wi_number = !empty($data['warehousing_item']['wi_number'])?$data['warehousing_item']['wi_number']:'.';
                $sheet->setCellValue('A' . $sn, $data['item_no']);
                $sheet->setCellValue('B' . $sn, $data['company']['co_parent']['co_name']);
                $sheet->setCellValue('C' . $sn, $data['company']['co_name']);
                $sheet->setCellValue('D' . $sn, $data['item_brand']);
                $sheet->setCellValue('E' . $sn, $data['item_name']);
                $sheet->setCellValue('F' . $sn, $data['item_option1']);
                $sheet->setCellValue('G' . $sn, $data['item_option2']);
                $sheet->setCellValue('H' . $sn, '');
                $sheet->setCellValue('I' . $sn, $wi_number);
                $sheet->setCellValue('J' . $sn, $data['item_price2']);
                $sn++;
            }
            $Excel_writer = new Xlsx($spreadsheet);
            if(isset($user->mb_no)){
                $path = 'storage/download/'.$user->mb_no.'/';
            }else{
                $path = 'storage/download/no-name/';
            }
            if (!is_dir($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $mask = $path.'DistributionStockList-*.*';
            array_map('unlink', glob($mask));
            $file_name_download = $path.'DistributionStockList-'.date('YmdHis').'.Xlsx';
            $check_status = $Excel_writer->save($file_name_download);
            return response()->json([
                'status' => 1,
                'link_download' => $file_name_download,
                'message' => 'Download File'
            ], 500);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function dowload_fulfillment_stock_list(ItemSearchRequest $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 10000;
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            // if($user->mb_type == 'shop'){
            //     $item = Item::with(['file', 'company','item_channels','item_info'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('company.co_parent',function($q) use ($user){
            //         $q->where('co_no', $user->co_no);
            //     })->orderBy('item_no', 'DESC');
            // }else if ($user->mb_type == 'shipper'){
            //     $item = Item::with(['file', 'company','item_channels','item_info'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('company',function($q) use ($user){
            //         $q->where('co_no', $user->co_no);
            //     })->orderBy('item_no', 'DESC');
            // }else if($user->mb_type == 'spasys'){
            //     $item = Item::with(['file', 'company','item_channels','item_info'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('company.co_parent.co_parent',function($q) use ($user){
            //         $q->where('co_no', $user->co_no);
            //     })->orderBy('item_no', 'DESC');
            // }
            if ($user->mb_type == 'shop') {
     
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                 
                    $q->whereHas('ContractWms.company.co_parent', function ($k) use ($user) {
                        $k->where('co_no', $user->co_no);
                    });
                })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');
            } else if ($user->mb_type == 'shipper') {
              
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
               
                    $q->whereHas('ContractWms.company', function ($k) use ($user) {
                        $k->where('co_no', $user->co_no);
                    });
                })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');
            } else if ($user->mb_type == 'spasys') {
      
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
             
                    $q->whereHas('ContractWms.company.co_parent.co_parent', function ($k) use ($user) {
                        $k->where('co_no', $user->co_no);
                    });
                })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');
            }

            if (isset($validated['from_date'])) {
                $item->whereHas('item_status_bad', function ($q) use ($validated) {
                    $q->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
                });
            }

            if (isset($validated['to_date'])) {
                $item->whereHas('item_status_bad', function ($q) use ($validated) {
                    $q->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
                });
            }
            if (isset($validated['status'])) {
                if ($validated['status'] == '하') {
                    $status = 1;
                } else {
                    $status = 0;
                }
                $item->where(DB::raw('status'), '=', $status);
            }
            if (isset($validated['co_name_shop'])) {
                $item->whereHas('item_status_bad.ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }
            if (isset($validated['product_id'])) {
                $item->whereHas('item_status_bad', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(product_id)'), 'like', '%' . strtolower($validated['product_id']) . '%');
                });
            }
            if (isset($validated['co_name_agency'])) {
                $item->whereHas('item_status_bad.ContractWms.company', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_agency']) . '%', 'and', 'co_type', '=', 'shipper');
                });
            }
            if (isset($validated['item_name'])) {
                $item->whereHas('item_status_bad', function ($q) use ($validated) {
                    $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $item->whereHas('item_status_bad', function ($q) use ($validated) {
                    $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_channel_code'])) {
                $item->whereHas('item_status_bad.item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $item->whereHas('item_status_bad', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $item->whereHas('item_status_bad', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['item_channel_name'])) {
                $item->whereHas('item_status_bad.item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_brand'])) {
                $item->whereHas('item_status_bad', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            $item = $item->get();
            // if (isset($validated['from_date'])) {
            //     $item->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            // }

            // if (isset($validated['to_date'])) {
            //     $item->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            // }
            // if (isset($validated['co_name_shop'])) {
            //     $item->whereHas('company.co_parent',function($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_name_shop']) .'%');
            //     });
            // }
            // if (isset($validated['co_name_agency'])) {
            //     $item->whereHas('company',function($query) use ($validated) {
            //         $query->where(DB::raw('lower(co_name)'), 'like','%'. strtolower($validated['co_name_agency']) .'%', 'and' , 'co_type' , '=' , 'shipper');
            //     });
            // }
            // if (isset($validated['item_name'])) {
            //     $item->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_name)'), 'like','%'. strtolower($validated['item_name']) .'%');
            //     });
            // }
            // if (isset($validated['item_cargo_bar_code'])) {
            //     $item->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_cargo_bar_code)'), 'like','%'. strtolower($validated['item_cargo_bar_code']) .'%');
            //     });
            // }
            // if (isset($validated['item_channel_code'])) {
            //     $item->whereHas('item_channels',function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_channel_name)'), 'like','%'. strtolower($validated['item_channel_name']) .'%');
            //     });
            // }
            // if (isset($validated['item_bar_code'])) {
            //     $item->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_bar_code)'), 'like','%'. strtolower($validated['item_bar_code']) .'%');
            //     });
            // }
            // if (isset($validated['item_upc_code'])) {
            //     $item->where(function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_upc_code)'), 'like','%'. strtolower($validated['item_upc_code']) .'%');
            //     });
            // }
            // if (isset($validated['item_channel_name'])) {
            //     $item->whereHas('item_channels',function($query) use ($validated) {
            //         $query->where(DB::raw('lower(item_channel_name)'), 'like','%'. strtolower($validated['item_channel_name']) .'%');
            //     });
            // }
            // $item2 = $item->get();
            // $count_check = 0;
            // $item3 = collect($item2)->map(function ($q){
            //     $item4 = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
            //     if(isset($item4['item_info']['stock'])){
            //     return [ 'total_amount' => $item4['item_info']['stock']];
            //     }
            // })->sum('total_amount');
            // $item5 = collect($item2)->map(function ($q){
            //     $item6 = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
            //     if(isset($item6['item_info']['stock'])){
            //     return [ 'total_price' => $item6->item_price2 * $item6['item_info']['stock']];
            //     }
            // })->sum('total_price');


            //$item = $item->paginate($per_page, ['*'], 'page', $page);

            //$custom = collect(['sum1' => $item3,'sum2'=>$item5]);

            //return DB::getQueryLog();
            // $item->setCollection(
            //     $item->getCollection()->map(function ($q){
            //         $item = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
            //         if(isset($item['item_info']['stock'])){
            //             $q->total_price_row = $item->item_price2 * $item['item_info']['stock'];
            //         }
            //         return $q;
            //     })
            // );

            // $data = $custom->merge($item);
            // $data_export = $data['data'];
            $item =  json_decode($item);
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

            $sheet->setCellValue('A1', 'No'); // item_no
            $sheet->setCellValue('B1', '가맹점'); // co_name_shop - company.co_parent.co_name
            $sheet->setCellValue('C1', '화주'); // co_name_agency - company.co_name
            $sheet->setCellValue('D1', '상품코드'); // item_brand
            $sheet->setCellValue('E1', '옵션코드'); // item_name
            $sheet->setCellValue('F1', '브랜드'); // item_option1
            $sheet->setCellValue('G1', '상품명'); // item_option2
            $sheet->setCellValue('H1', '상'); // NULL
            $sheet->setCellValue('I1', '하');
            $sheet->setCellValue('J1', '정상가(KRW)');
            $sheet->setCellValue('K1', '판매가(KRW)');
            $sheet->setCellValue('L1', '할인가(KRW)');
            $sheet->setCellValue('M1', '정상가 합계');
            $sheet->setCellValue('N1', '판매가 합계');
            $sheet->setCellValue('O1', '할인가 합계');
            $sn = 2;
            foreach ($item as $data) {
                $stock_0 = '';
                $stock_1 = '';
                $item2 = Item::with(['item_info'])->where('item.item_no', $data->item_no)->first();
                    if (isset($item2['item_info']['stock'])) {
                        $total_price_row = $item2->item_price2 * $item2['item_info']['stock'];
                    }
                    if (isset($data->option_id)) {
                        $status_0 = StockStatusBad::where('product_id', $data->product_id)->where('option_id', $data->option_id)->where('status', 0)->first();
                    } else {
                        $status_0 = StockStatusBad::where('product_id', $data->product_id)->where('status', 0)->first();
                    }
                    if (isset($status_0->stock)) {
                        $stock_0 = $status_0->stock;
                    }

                    if (isset($data->option_id)) {
                        $status_1 = StockStatusBad::where('product_id', $data->product_id)->where('option_id', $data->option_id)->where('status', 1)->first();
                    } else {
                        $status_1 = StockStatusBad::where('product_id', $data->product_id)->where('status', 1)->first();
                    }
                    if (isset($status_1->stock)) {
                        $stock_1 = $status_1->stock;
                    }

                    if (isset($status_0->stock) || isset($status_1->stock)) {
                        $stock0 = isset($status_0->stock) ? $status_0->stock : 0;
                        $stock1 = isset($status_1->stock) ? $status_1->stock : 0;
                        $stock_total = $stock1 + $stock0;
                    }

                //$wi_number = !empty($data['warehousing_item']['wi_number'])?$data['warehousing_item']['wi_number']:'.';
                $sheet->setCellValue('A' . $sn, $data->item_no);
                $sheet->setCellValue('B' . $sn, $data->item_status_bad->contract_wms->company->co_parent->co_name);
                $sheet->setCellValue('C' . $sn, $data->item_status_bad->contract_wms->company->co_name);
                $sheet->setCellValue('D' . $sn, $data->product_id);
                $sheet->setCellValue('E' . $sn, $data->option_id);
                $sheet->setCellValue('F' . $sn, '');
                $sheet->setCellValue('G' . $sn, $data->item_status_bad->item_name);
                $sheet->setCellValue('H' . $sn, $stock_0);
                $sheet->setCellValue('I' . $sn, $stock_1);
                $sheet->setCellValue('J' . $sn, '');
                $sheet->setCellValue('K' . $sn, $data->item_status_bad->item_price2);
                $sheet->setCellValue('L' . $sn, '');
                $sheet->setCellValue('M' . $sn, '');
                $sheet->setCellValue('N' . $sn, '');
                $sheet->setCellValue('O' . $sn, '');
                $sn++;
            }
            $Excel_writer = new Xlsx($spreadsheet);
            if(isset($user->mb_no)){
                $path = 'storage/download/'.$user->mb_no.'/';
            }else{
                $path = 'storage/download/no-name/';
            }
            if (!is_dir($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            if (!is_dir($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $mask = $path.'FulfillmentStockList-*.*';
            array_map('unlink', glob($mask));
            $file_name_download = $path.'FulfillmentStockList-'.date('YmdHis').'.Xlsx';
            $Excel_writer->save($file_name_download);
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json([
                'status' => 1,
                'link_download' => $file_name_download,
                'message' => 'Download File'
            ], 200);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function download_distribution_release_list(WarehousingSearchRequest $request){
        try {
            DB::enableQueryLog();
            $validated = $request->validated();

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')

                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            } else if ($user->mb_type == 'shipper') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')

                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            } else if ($user->mb_type == 'spasys') {
                $warehousing = ReceivingGoodsDelivery::with(['w_no', 'warehousing'])->with(['mb_no'])->join('warehousing', 'warehousing.w_no', '=', 'receiving_goods_delivery.w_no')->whereNull('rgd_parent_no')->whereHas('w_no', function ($query) use ($user) {
                    $query->where('w_type', '=', 'EW')
                        ->where('rgd_status1', '=', '출고')
                        ->where('rgd_status2', '=', '작업완료')

                        ->where(function ($q) {
                            $q->where(function ($query) {
                                $query->where('rgd_status4', '!=', '예상경비청구서')->where('rgd_status4', '!=', '확정청구서');
                            })
                                ->orWhereNull('rgd_status4');
                        })->whereHas('co_no.co_parent.co_parent', function ($q2) use ($user) {
                            $q2->where('co_no', $user->co_no);
                        });
                });
            }
            $warehousing->whereNull('rgd_parent_no');

            if (isset($validated['from_date'])) {
                $warehousing->where('warehousing.w_completed_day', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $warehousing->where('warehousing.w_completed_day', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $warehousing->whereHas('w_no.co_no.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_parent_name']) . '%');
                });
            }
            if (isset($validated['co_name'])) {
                $warehousing->whereHas('w_no.co_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
                });
            }
            if (isset($validated['w_type'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_type', 'like', '%' . $validated['w_type'] . '%');
                });
            }
            if (isset($validated['w_schedule_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    return $q->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number'] . '%');
                });
            }
            if (isset($validated['rgd_status1'])) {
                $warehousing->where('rgd_status1', '=', $validated['rgd_status1']);
            }
            if (isset($validated['rgd_status2'])) {
                $warehousing->where('rgd_status2', '=', $validated['rgd_status2']);
            }
            if (isset($validated['rgd_status3'])) {
                $warehousing->where('rgd_status3', '=', $validated['rgd_status3']);
            }
            if (isset($validated['item_brand'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $warehousing->whereHas('w_no.warehousing_item.item_no', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['rgd_status3_1']) || isset($validated['rgd_status3_2']) || isset($validated['rgd_status3_3'])) {
                $warehousing->where(function ($q) use ($validated) {
                    $q->Where('rgd_status3', '=', $validated['rgd_status3_1'] ? $validated['rgd_status3_1'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_2'] ? $validated['rgd_status3_2'] : "")
                        ->orWhere('rgd_status3', '=', $validated['rgd_status3_3'] ? $validated['rgd_status3_3'] : "");
                });
            }
            if (isset($validated['w_schedule_number2'])) {

                $warehousing->where(function ($q) use ($validated) {
                    $q->whereHas('w_no', function ($q1) use ($validated) {
                        $q1->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%', 'and', 'w_type', '=', 'IW');
                    })->orWhereHas('w_no.w_import_parent', function ($q2) use ($validated) {
                        $q2->where('w_schedule_number2', 'like', '%' . $validated['w_schedule_number2'] . '%');
                    });
                });
            }
            if (isset($validated['connection_number'])) {
                $warehousing->whereHas('w_no', function ($q) use ($validated) {
                    $q->where('connection_number', 'like', '%' . $validated['connection_number'] . '%');
                });
            }
            if (isset($validated['rgd_receiver'])) {
                $warehousing->where('rgd_receiver', 'like', '%' . $validated['rgd_receiver'] . '%');
            }
            if (isset($validated['rgd_contents'])) {
                $warehousing->where('rgd_contents', '=', $validated['rgd_contents']);
            }
            $warehousing->orderBy('w_completed_day', 'DESC');
            //$warehousing = $warehousing->paginate($per_page, ['*'], 'page', $page);
            $warehousing = $warehousing->get();
            // $warehousing->setCollection(
            //     $warehousing->getCollection()->map(function ($item) {

            //         $item->total_item = WarehousingItem::where('w_no', $item->w_no)->where('wi_type', '입고_shipper')->sum('wi_number');
            //         if (!empty($item['warehousing']['warehousing_item'][0]) && isset($item['warehousing']['warehousing_item'][0]['item'])) {
            //             $first_name_item = $item['warehousing']['warehousing_item'][0]['item']['item_name'];
            //             $total_item = $item['warehousing']['warehousing_item']->count();
            //             $final_total = ($total_item / 2   - 1);
            //             if ($final_total <= 0) {
            //                 $item->first_item_name_total = $first_name_item . '외';
            //             } else {
            //                 $item->first_item_name_total = $first_name_item . '외' . ' ' . $final_total . '건';
            //             }
            //         } else {
            //             $item->first_item_name_total = '';
            //         }

            //         return $item;
            //     })
            // );

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);

            $sheet->setCellValue('A1', 'No'); 
            $sheet->setCellValue('B1', '가맹점'); 
            $sheet->setCellValue('C1', '화주'); 
            $sheet->setCellValue('D1', '입고화물번호'); 
            $sheet->setCellValue('E1', '화물연계번호'); 
            $sheet->setCellValue('F1', '출고화물번호'); 
            $sheet->setCellValue('G1', '옵션2'); 
            $sheet->setCellValue('H1', '등급');
            $sheet->setCellValue('I1', '수량');
            $sheet->setCellValue('J1', '상품단가');

            $warehousing =  json_decode($warehousing);
            foreach($warehousing as $data){
                
            }
           // return response()->json(['message' => $warehousing], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
        }
    }
    public function download_bonded_cargo(ImportScheduleSearchRequest $request){
        try {
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            DB::enableQueryLog();
            $validated = $request->validated();
            $user = Auth::user();
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 100000;
            $page = isset($validated['page']) ? $validated['page'] : 1;

            if ($user->mb_type == 'shop') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1','ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number','ti_logistic_type')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number','te_e_confirm_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'shipper') {
               
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number','ti_logistic_type')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date','te_e_confirm_number' ,'te_carry_in_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                
                //FIX NOT WORK 'with'
                $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
                    ->leftjoin('company as parent_spasys', function ($join) {
                        $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    })
                   ->leftjoin('company', function ($join) {
                        $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                    })->leftjoin('company as parent_shop', function ($join) {
                        $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    })
                    // ->leftjoin('company', function ($join) {
                    //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');

                    // })->leftjoin('company as parent_shop', function ($join) {
                    //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                    // })->leftjoin('company as parent_spasys', function ($join) {
                    //     $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
                    //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                    // })
                    //->where('parent_spasys.co_no', $user->co_no)

                    ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                    ->where('tie_is_date', '>=', '2022-01-04')
                    ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

                $sub_2 = Import::select('receiving_goods_delivery.rgd_no','receiving_goods_delivery.rgd_status3','receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number','ti_logistic_type')
                    ->leftjoin('receiving_goods_delivery', function ($join) {
                        $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                    })
                    ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

                // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
                //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

                $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number','te_e_confirm_number', 'te_e_order', 'te_e_number')
                    // ->leftjoin('receiving_goods_delivery', function ($join) {
                    //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                    // })
                    ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


                $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                    $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('tie_is_date', 'DESC');


                
                //return DB::getQueryLog();
                //END FIX NOT WORK 'with'
            }
            // $import_schedule = ImportSchedule::with('co_no')->with('files')->orderBy('is_no', 'DESC');

            // if (isset($validated['from_date'])) {
            //     $import_schedule->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            // }

            // if (isset($validated['to_date'])) {
            //     $import_schedule->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            // }

            // if (isset($validated['co_name'])) {
            //     $import_schedule->whereHas('co_no', function($q) use($validated) {
            //         return $q->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name']) . '%');
            //     });
            // }

            // if (isset($validated['m_bl'])) {
            //     $import_schedule->where('m_bl', 'like', '%' . $validated['m_bl'] . '%');
            // }

            // if (isset($validated['h_bl'])) {
            //     $import_schedule->where('h_bl', 'like', '%' . $validated['h_bl'] . '%');
            // }

            // if (isset($validated['logistic_manage_number'])) {
            //     $import_schedule->where('logistic_manage_number', 'like', '%' . $validated['logistic_manage_number'] . '%');
            // }

            $import_schedule = $import_schedule->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', '가맹점');
            $sheet->setCellValue('C1', '화주');
            $sheet->setCellValue('D1', 'H-BL');
            $sheet->setCellValue('E1', '화물유형');
            $sheet->setCellValue('F1', '화물관리번호');
            $sheet->setCellValue('G1', '선명');
            $sheet->setCellValue('H1', '품명');
            $sheet->setCellValue('I1', '입항일자');
            $sheet->setCellValue('J1', '반입일자');
            $sheet->setCellValue('K1', '반입수량');
            $sheet->setCellValue('L1', '반입수량');

            $num_row = 2;
            $data_schedules =  json_decode($import_schedule);
            foreach($data_schedules as $data){
                if($data->co_type == 'shop'){
                    $shop = $data->co_name;
                    $shop2 = "";
                } else {
                    $shop = $data->co_name_shop;
                    $shop2= $data->co_name;
                }

                
                $sheet->setCellValue('A'.$num_row, isset($data->is_no)?$data->is_no:'');
                $sheet->setCellValue('B'.$num_row, $shop);
                $sheet->setCellValue('C'.$num_row, $shop2);
                $sheet->setCellValue('D'.$num_row, $data->tie_h_bl);
                $sheet->setCellValue('E'.$num_row, $data->ti_logistic_type);
                $sheet->setCellValue('F'.$num_row, $data->te_logistic_manage_number);
                $sheet->setCellValue('G'.$num_row, $data->tie_is_ship);
                $sheet->setCellValue('H'.$num_row, $data->tie_is_name_eng);
                $sheet->setCellValue('I'.$num_row, $data->tie_is_date);
                $sheet->setCellValue('J'.$num_row, $data->ti_i_date);
                $sheet->setCellValue('K'.$num_row, $data->ti_i_number);
                $sheet->setCellValue('L'.$num_row, $data->te_e_confirm_number);
                $num_row++;
            }

            $Excel_writer = new Xlsx($spreadsheet);
            if(isset($user->mb_no)){
                $path = 'storage/download/'.$user->mb_no.'/';
            }else{
                $path = 'storage/download/no-name/';
            }
            if (!is_dir($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $mask = $path.'DownloadBondedCargo-*.*';
            array_map('unlink', glob($mask));
            $file_name_download = $path.'DownloadBondedCargo-'.date('YmdHis').'.Xlsx';
            $Excel_writer->save($file_name_download);
            return response()->json([
                'status' => 1,
                'link_download' => $file_name_download,
                'message' => 'Download File'
            ], 200);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            //return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}
