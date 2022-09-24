<?php

namespace App\Http\Controllers\Excel;

use App\Utils\Messages;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Http\Requests\Item\ItemSearchRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;

class ExportExcelController extends Controller
{
    public function download_distribution_stocklist(ItemSearchRequest $request){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 10000;
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
            $count_check = 0; 
            $item3 = collect($item2)->map(function ($q){
                $item4 = Item::with(['warehousing_item'])->where('item_no', $q->item_no)->first();
                if(isset($item4['warehousing_item']['wi_number'])){
                return [ 'total_amount' => $item4['warehousing_item']['wi_number'] ,  'total_price' => $item4->item_price2 * $item4['warehousing_item']['wi_number']];
                }
            });
            $item5 = $item3->sum('total_amount');
            $item6 = $item3->sum('total_price');
            $item = $item->paginate($per_page, ['*'], 'page', $page);
            $custom = collect(['sum1' => $item5,'sum2'=>$item6]);
            $item->setCollection(
                $item->getCollection()->map(function ($q){
                    $item = Item::with(['warehousing_item'])->where('item_no', $q->item_no)->first();
                    if(isset($item['warehousing_item']['wi_number'])){
                        $q->total_price_row = $item->item_price2 * $item['warehousing_item']['wi_number'];
                    }
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
            $file_name_download = '../storage/download/DistributionStockList.Xlsx';
            // header('Access-Control-Allow-Origin', '*');
            // header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            // header('Content-Type: application/vnd.ms-excel');
            // header('Access-Control-Allow-Headers: *');
            // header('Content-Disposition: attachment;filename="'.$file_name_download.'"');
            // header('Cache-Control: max-age=0');
            // $Excel_writer->save('php://output');
            $check_status = $Excel_writer->save($file_name_download);
            return response()->json([
                'status' => 1,
                'link_download' => $file_name_download,
                'message' => 'Download File'
            ], 500);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}
