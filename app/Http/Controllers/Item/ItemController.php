<?php

namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemRequest;
use App\Http\Requests\Item\ItemSearchRequest;
use App\Models\Warehousing;
use App\Models\Item;
use App\Models\File;
use App\Models\ItemChannel;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Register and Update Item
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(ItemRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::beginTransaction();
            $item_no = $request->get('item_no');
            if (!isset($item_no)) {
                $item_no = Item::insertGetId([
                    'mb_no' => Auth::user()->mb_no,
                    'co_no' => $validated['co_no'],
                    'item_brand' => $validated['item_brand'],
                    'item_service_name' => $validated['item_service_name'],
                    'item_name' => $validated['item_name'],
                    'item_option1' => $validated['item_option1'],
                    'item_option2' => $validated['item_option2'],
                    'item_cargo_bar_code' => $validated['item_cargo_bar_code'],
                    'item_upc_code' => $validated['item_upc_code'],
                    'item_bar_code' => $validated['item_bar_code'],
                    'item_weight' => $validated['item_weight'],
                    'item_price1' => $validated['item_price1'],
                    'item_price2' => $validated['item_price2'],
                    'item_price3' => $validated['item_price3'],
                    'item_price4' => $validated['item_price4'],
                    'item_cate1' => $validated['item_cate1'],
                    'item_cate2' => $validated['item_cate2'],
                    'item_cate3' => $validated['item_cate3'],
                    'item_url' => $validated['item_url'],
                    'item_origin' => $validated['item_origin'],
                    'item_manufacturer' => $validated['item_manufacturer'],
                ]);

                $item_channels = [];
                if(isset($validated['item_channels'])) {
                    foreach ($validated['item_channels'] as $item_channel) {
                        if (isset($item_channel['item_channel_code']) && isset($item_channel['item_channel_name'])) {
                            $item_channels[] = [
                                'item_no' => $item_no,
                                'item_channel_code' => $item_channel['item_channel_code'],
                                'item_channel_name' => $item_channel['item_channel_name']
                            ];
                        }
                    }
                    ItemChannel::insert($item_channels);
                }
               
            } else {
                // Update data
                $item = Item::with('file')->where('item_no', $item_no)->first();
                if (is_null($item)) {
                    return response()->json(['message' => Messages::MSG_0020], 404);
                }

                $update = [
                    'mb_no' => Auth::user()->mb_no,
                    'co_no' => $validated['co_no'],
                    'item_brand' => $validated['item_brand'],
                    'item_service_name' => $validated['item_service_name'],
                    'item_name' => $validated['item_name'],
                    'item_option1' => $validated['item_option1'],
                    'item_option2' => $validated['item_option2'],
                    'item_cargo_bar_code' => $validated['item_cargo_bar_code'],
                    'item_upc_code' => $validated['item_upc_code'],
                    'item_bar_code' => $validated['item_bar_code'],
                    'item_weight' => $validated['item_weight'],
                    'item_price1' => $validated['item_price1'],
                    'item_price2' => $validated['item_price2'],
                    'item_price3' => $validated['item_price3'],
                    'item_price4' => $validated['item_price4'],
                    'item_cate1' => $validated['item_cate1'],
                    'item_cate2' => $validated['item_cate2'],
                    'item_cate3' => $validated['item_cate3'],
                    'item_url' => $validated['item_url'],
                    'item_origin' => $validated['item_origin'],
                    'item_manufacturer' => $validated['item_manufacturer'],
                ];
                $item->update($update);

                if(isset($validated['item_channels'])) 
                    foreach ($validated['item_channels'] as $item_channel) {
                        ItemChannel::updateOrCreate(
                            [
                                'item_channel_no' => $item_channel['item_channel_no'] ?: null,
                            ],
                            [
                                'item_no' => $item_no,
                                'item_channel_code' => $item_channel['item_channel_code'],
                                'item_channel_name' => $item_channel['item_channel_name']
                            ]
                        );
                    }
            }

            // Insert file
            if (isset($validated['file'])) {
                $path = join('/', ['files', 'item', $item_no]);
                $url = Storage::disk('public')->put($path, $validated['file']);

                $file = File::where('file_table', 'item')->where('file_table_key', $item_no)->first();

                if(!empty($file)){
                    Storage::disk('public')->delete($file->file_url);
                    $file->delete();  
                }

                File::insert([
                    'file_table' => 'item',
                    'file_table_key' => $item_no,
                    'file_name_old' => $validated['file']->getClientOriginalName(),
                    'file_name' => basename($url),
                    'file_size' => $validated['file']->getSize(),
                    'file_extension' => $validated['file']->extension(),
                    'file_position' => 0,
                    'file_url' => $url
                ]);
               
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                '$validated' => $validated['co_no']
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function getItems(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            $items = Item::with('item_channels')->where('item_service_name', '유통가공')->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function postItems(ItemSearchRequest $request)
    {
       
        $validated = $request->validated();
       // return  $validated;
        try {
            DB::enableQueryLog();

            
            
            //return $warehousing;
            if(isset($validated['items'])){
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with('item_channels');
            
            if (isset($item_no)) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with('warehousing_item');

                $items->whereHas('warehousing_item.w_no',function($query) use ($validated) {
                    if($validated['type'] == 'IW'){        
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고');
                    }else{
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '출고');
                    }
                });     
                  
            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where('1','=','2');
            }

            $items->where('item_service_name', '유통가공');
            
            $items = $items->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function searchItems(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $item = Item::with(['file', 'company'])->paginate($per_page, ['*'], 'page', $page);
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateItems(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $item = Item::with(['file', 'company','item_channels']);
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
                    $query->where('item_cargo_bar_code', '=', $validated['item_cargo_bar_code']);
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
            $item = $item->paginate($per_page, ['*'], 'page', $page);
            //return DB::getQueryLog();
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }


    public function getItemById(Item $item)
    {
        try {
            $file = $item->file()->first();
            $item_channels = $item->item_channels()->get();
            $item['item_channels'] = $item_channels;
            $item['file'] = $file;
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function deleteItemChannel(ItemChannel $itemChannel)
    {
        try {
            $itemChannel->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }

    public function importItems(Request $request)
    {
        try {
            $f = Storage::disk('public')->put('files/tmp', $request['file']);

            $path = storage_path('app/public') . '/' . $f;
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);

            $sheet = $spreadsheet->getSheet(0);
            $datas = $sheet->toArray(null, true, true, true);
            $results[$sheet->getTitle()] = [];
            $errors[$sheet->getTitle()] = [];
            foreach ($datas as $key => $d) {
                if($key == 1) {
                    continue;
                }
                $validator = Validator::make($d, ExcelRequest::rules());
                if ($validator->fails()) {
                    $errors[$sheet->getTitle()][] = $validator->errors();
                } else {
                    $item_no = Item::insertGetId([
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => $d['A'],
                        'item_brand' => $d['B'],
                        'item_service_name' => $d['C'],
                        'item_name' => $d['D'],
                        'item_option1' => $d['E'],
                        'item_option2' => $d['F'],
                        'item_cargo_bar_code' => $d['G'],
                        'item_upc_code' => $d['H'],
                        'item_bar_code' => $d['I'],
                        'item_weight' => $d['J'],
                        'item_url' => $d['K'],
                        'item_price1' => $d['L'],
                        'item_price2' => $d['M'],
                        'item_price3' => $d['N'],
                        'item_price4' => $d['O'],
                        'item_cate1' => $d['P'],
                        'item_cate2' => $d['Q'],
                        'item_cate3' => $d['R']
                    ]);

                    // Check validator item_channel
                    $flgCheck = false;
                    $i = 0;
                    $item_channel = [];
                    $item_channels = [];
                    foreach ($d as $k => $val) {
                        if ($k === 'S') {
                            $flgCheck = true;
                        }
                        if ($flgCheck) {
                            if ($i === 0) {
                                $item_channel[$k] = $val;
                                $i = $i + 1;
                            }else if ($i === 1) {
                                $item_channel[$k] = $val;
                                $i = 0; // reset $i
                                $j = 0;
                                $validate = [];

                                $v = [];
                                foreach ($item_channel as $i_key_channel => $v_key_channel) {
                                    if ($j === 0) {
                                        $validate[$i_key_channel] = [
                                            'required',
                                            'max:255',
                                        ];
                                        $j = $j + 1;
                                        if(isset($v_key_channel)) {
                                            $v['item_channel_name'] = $v_key_channel;
                                        }
                                    } else if ($j === 1) {
                                        $validate[$i_key_channel] = [
                                            'required',
                                            'integer',
                                        ];
                                        if(isset($v_key_channel)) {
                                            $v['item_channel_code'] = $v_key_channel;
                                        }
                                    }
                                }
                                if (count($v) >= 1) {
                                    $validator = Validator::make($d, $validate);
                                    if ($validator->fails()) {
                                        $errors[$sheet->getTitle()][] = $validator->errors();
                                    } else {
                                        $item_channels[] = array_merge($v, ['item_no' => $item_no]);
                                    }
                                }
                                $item_channel = [];
                            }
                        }
                    }
                    ItemChannel::insert($item_channels);
                }
            }

            Storage::disk('public')->delete($f);
            return response()->json([
                'message' => Messages::MSG_0007,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0004], 500);
        }
    }

    public function updateFile(Request $request){
        $path = join('/', ['files', 'item', $request->item_no]);
        $url = Storage::disk('public')->put($path, $request->file);

        File::insert([
            'file_table' => 'item',
            'file_table_key' => $request->item_no,
            'file_name_old' => $request->file->getClientOriginalName(),
            'file_name' => basename($url),
            'file_size' => $request->file->getSize(),
            'file_extension' => $request->file->extension(),
            'file_position' => 0,
            'file_url' => $url
        ]);

        return response()->json([
            'message' => Messages::MSG_0007,
        ]);
    }

}
