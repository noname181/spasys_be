<?php

namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemRequest;
use App\Http\Requests\Item\ItemSearchRequest;
use App\Http\Requests\Item\ChannelRequest;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\Item;
use App\Models\ItemInfo;
use App\Models\Company;
use App\Models\File;
use File as Files;
use App\Models\ItemChannel;
use App\Models\ImportExpected;
use App\Models\Import;
use App\Models\Export;
use App\Models\ExportConfirm;
use App\Models\ReceivingGoodsDelivery;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use App\Models\StockStatusBad;
use App\Models\StockStatusCompany;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use \Carbon\Carbon;
use App\Utils\CommonFunc;
use Illuminate\Support\Facades\Http;
use App\Models\Alarm;

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
                    'co_no' => isset($validated['co_no']) ? $validated['co_no'] : Auth::user()->co_no,
                    'item_brand' => $validated['item_brand'],
                    'item_service_name' => $validated['item_service_name'],
                    'item_name' => $validated['item_name'],
                    'item_option1' => $validated['item_option1'],
                    'item_option2' => $validated['item_option2'],
                    'item_cargo_bar_code' => $validated['item_cargo_bar_code'],
                    'item_upc_code' => $validated['item_upc_code'],
                    'item_bar_code' => $validated['item_bar_code'],
                    'item_weight' => $validated['item_weight'],
                    'item_price1' => isset($validated['item_price1']) ? $validated['item_price1'] : 0,
                    'item_price2' => isset($validated['item_price2']) ? $validated['item_price2'] : 0,
                    'item_price3' => isset($validated['item_price3']) ? $validated['item_price3'] : 0,
                    'item_price4' => isset($validated['item_price4']) ? $validated['item_price4'] : 0,
                    'item_cate1' => $validated['item_cate1'],
                    'item_cate2' => $validated['item_cate2'],
                    'item_cate3' => $validated['item_cate3'],
                    'item_url' => $validated['item_url'],
                    'item_origin' => $validated['item_origin'],
                    'item_manufacturer' => $validated['item_manufacturer'],
                ]);

                $item_channels = [];
                if (isset($validated['item_channels'])) {
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

                if (isset($validated['item_channels']))
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

                if (!empty($file)) {
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
                'item_no' => $item_no ? $item_no : ($item ? $item->item_no : null),
                '$validated' => isset($validated['co_no']) ? $validated['co_no'] : Auth::user()->co_no
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }


    public function getItems(ItemSearchRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';
            $items = Item::with(['item_channels', 'company'])->where('item_service_name', '유통가공')->orderBy('item_no', 'DESC');

            if (Auth::user()->mb_type == "shop") {
                $items->whereHas('company.co_parent', function ($query) use ($co_no) {
                    $query->where(DB::raw('co_no'), '=', $co_no);
                });
            } else {
                $items->where('co_no', $co_no);
            }

            $items = $items->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'user' => Auth::user(),
                'sql' => DB::getQueryLog()
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
            if (isset($validated['items'])) {
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with(['item_channels', 'file'])->orderBy('item_no', 'DESC');

            if (isset($validated['items'])) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with(['warehousing_item' => function ($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                }]);

                $items->whereHas('warehousing_item', function ($query) use ($validated) {
                    if ($validated['type'] == 'IW') {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                    } else {
                        $query->where('w_no', '=', $validated['w_no']);
                    }
                });

                $sql_count = WarehousingItem::where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')->get()->count();

                if ($sql_count != 0) {
                    $items->with(['warehousing_item2' => function ($query) use ($validated) {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    }]);

                    $items->whereHas('warehousing_item2', function ($query) use ($validated) {
                        if ($validated['type'] == 'IW') {
                            $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                        } else {
                            $query->where('w_no', '=', $validated['w_no']);
                        }
                    });
                }
            }

            if (isset($validated['w_no']) && isset($validated['items']) && $validated['type'] == "EW") {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with(['warehousing_item' => function ($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '출고_shipper');
                }]);
            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where(DB::raw('1'), '=', '2');
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

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function postItemsApi(ItemSearchRequest $request)
    {

        $validated = $request->validated();
        // return  $validated;
        try {
            DB::enableQueryLog();



            //return $warehousing;
            if (isset($validated['items'])) {
                $item_no =  array_column($validated['items'], 'item_no');
            }

            $items = Item::with('item_channels')->orderBy('item_no', 'DESC');

            if (isset($validated['items'])) {
                $items->whereIn('item_no', $item_no);
            }

            if (isset($validated['w_no']) && !isset($validated['items'])) {
                $warehousing = Warehousing::find($validated['w_no']);

                $items->with(['warehousing_item' => function ($query) use ($validated) {
                    $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                }]);

                $items->whereHas('warehousing_item', function ($query) use ($validated) {
                    if ($validated['type'] == 'IW') {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_shipper');
                    } else {
                        $query->where('w_no', '=', $validated['w_no']);
                    }
                });

                $sql_count = WarehousingItem::where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys')->get();
                $count = $sql_count->count();

                if ($count != 0) {
                    $items->with(['warehousing_item2' => function ($query) use ($validated) {
                        $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                    }]);

                    $items->whereHas('warehousing_item2', function ($query) use ($validated) {
                        if ($validated['type'] == 'IW') {
                            $query->where('w_no', '=', $validated['w_no'])->where('wi_type', '=', '입고_spasys');
                        } else {
                            $query->where('w_no', '=', $validated['w_no']);
                        }
                    });
                }
            }

            if (!isset($validated['w_no']) && !isset($validated['items'])) {
                $items->where(DB::raw('1'), '=', '2');
            }

            $items->where('item_service_name', '수입풀필먼트');

            $items = $items->get();
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function postItemsPopup(ItemSearchRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::enableQueryLog();

            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';
            $item = [];
            $count = 0;
            if (isset($validated['item_data'])) {
                foreach ($validated['item_data'] as $value) {
                    if ($value['item_no']) {
                        $item[] = $value['item_no'];
                        $count++;
                    }
                }
            }

            $items = Item::with(['item_channels', 'company', 'file'])->where('item_service_name', '유통가공');



            if (isset($validated['w_no'])) {
                $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
                $items->where('co_no', $warehousing->co_no);
            } else {
                if (isset($validated['co_no']) && Auth::user()->mb_type == "shop") {
                    $items->where('co_no', $validated['co_no']);
                } else if (isset($validated['co_no']) && Auth::user()->mb_type == "spasys") {
                    $items->where('co_no', $validated['co_no']);
                } else if (isset($validated['co_no']) && Auth::user()->mb_type == "shipper") {
                    $items->where('co_no', $validated['co_no']);
                }
            }

            if (isset($validated['keyword'])) {
                if ($validated['type'] == 'item_brand' || $validated['type'] == 'item_name') {
                    $items->where(function ($query) use ($validated) {
                        $query->where(DB::raw('lower(' . $validated['type'] . ')'), 'like', '%' . strtolower($validated['keyword']) . '%');
                    });
                } else {
                    $items->whereHas('item_channels', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(' . $validated['type'] . ')'), 'like', '%' . strtolower($validated['keyword']) . '%');
                    });
                }
            }

            // if (Auth::user()->mb_type == "shop") {
            //     $items->whereHas('company.co_parent', function ($query) use ($co_no) {
            //         $query->where(DB::raw('co_no'), '=', $co_no);
            //     });
            // } elseif (Auth::user()->mb_type == "shipper") {
            //     $items->where('co_no', $co_no);
            // } else {
            //     $co_child = Company::where('co_parent_no', $co_no)->get();
            //     $co_no = array();
            //     foreach ($co_child as $o) {
            //         $co_no[] = $o->co_no;
            //     }

            //     $items->whereHas('company.co_parent', function ($query) use ($co_no) {
            //         $query->whereIn(DB::raw('co_no'), $co_no);
            //     });
            // }
            if ($item) {
                $items = $items->orwhere(function ($query) use ($item, $validated) {
                    $query->whereIn('item_no', $item);
                    if (isset($validated['w_no'])) {
                        $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
                        $query->where('co_no', $warehousing->co_no);
                    } else {
                        if (isset($validated['co_no']) && Auth::user()->mb_type == "shop") {
                            $query->where('co_no', $validated['co_no']);
                        } else if (isset($validated['co_no']) && Auth::user()->mb_type == "spasys") {
                            $query->where('co_no', $validated['co_no']);
                        } else if (isset($validated['co_no']) && Auth::user()->mb_type == "shipper") {
                            $query->where('co_no', $validated['co_no']);
                        }
                    }
                });
                sort($item);
                $orderedIds = implode(',', $item);

                $items = $items->orderByRaw(\DB::raw("FIELD(item_no, " . $orderedIds . " ) desc"))->orderBy('item_no', 'DESC');
            } else {
                $items = $items->orderBy('item_no', 'DESC');
            }


            $items = $items->paginate($per_page, ['*'], 'page', $page);

            // $sortedResult = $items->getCollection()->sortBy('item_no')->values();
            // $items->setCollection($sortedResult);

            // $items->setCollection(
            //     $items->getCollection()->map(function ($val,$validated) {

            //         $val->push($validated['item_data']);

            //         return $val;
            //     })
            // );

            // if($page == 1){
            //     foreach($validated['item_data'] as $value){
            //         if($value['item_no']){
            //             $items->prepend($value);
            //         }
            //     }
            //     $items->total(22);
            // }

            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                // 'item' => $items->total(),
                'user' => Auth::user(),
                //'orderedIds' => $orderedIds,
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function postItemsPopupApi(ItemSearchRequest $request)
    {

        $validated = $request->validated();
        try {
            DB::enableQueryLog();

            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;

            $co_no = Auth::user()->co_no ? Auth::user()->co_no : '';

            $item = [];
            $count = 0;
            if (isset($validated['item_data'])) {
                foreach ($validated['item_data'] as $value) {
                    if ($value['item_no']) {
                        $item[] = $value['item_no'];
                        $count++;
                    }
                }
            }

            $items = Item::with(['item_channels', 'ContractWms'])->where('item_service_name', '수입풀필먼트');

            if (isset($validated['co_no'])) {
                $items->whereHas('ContractWms.company', function ($q) use ($validated) {
                    $q->where('co_no', $validated['co_no']);
                });
            }

            // if (isset($validated['w_no'])) {
            //     $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
            //     $items->where('co_no', $warehousing->co_no);
            // }



            if (isset($validated['keyword'])) {
                if ($validated['type'] == 'item_brand' || $validated['type'] == 'item_name') {
                    $items->where(function ($query) use ($validated) {
                        $query->where(DB::raw('lower(' . $validated['type'] . ')'), 'like', '%' . strtolower($validated['keyword']) . '%');
                    });
                } else if ($validated['type'] == 'item_channel_code') {
                    $items->where(function ($query) use ($validated) {
                        $query->where(DB::raw('lower(product_id)'), 'like', '%' . strtolower($validated['keyword']) . '%');
                    });
                } else {
                    $items->whereHas('item_channels', function ($query) use ($validated) {
                        $query->where(DB::raw('lower(' . $validated['type'] . ')'), 'like', '%' . strtolower($validated['keyword']) . '%');
                    });
                }
            }

            // if (Auth::user()->mb_type == "shop") {
            //     $items->whereHas('company.co_parent', function ($query) use ($co_no) {
            //         $query->where(DB::raw('co_no'), '=', $co_no);
            //     });
            // } elseif (Auth::user()->mb_type == "shipper") {
            //     $items->where('co_no', $co_no);
            // } else {
            //     $co_child = Company::where('co_parent_no', $co_no)->get();
            //     $co_no = array();
            //     foreach ($co_child as $o) {
            //         $co_no[] = $o->co_no;
            //     }

            //     $items->whereHas('company.co_parent', function ($query) use ($co_no) {
            //         $query->whereIn(DB::raw('co_no'), $co_no);
            //     });
            // }

            if ($item) {
                $items = $items->orwhere(function ($query) use ($item, $validated) {
                    $query->whereIn('item_no', $item);
                    if (isset($validated['co_no'])) {
                        $query->whereHas('ContractWms.company', function ($q) use ($validated) {
                            $q->where('co_no', $validated['co_no']);
                        });
                    }
                });
                sort($item);
                $orderedIds = implode(',', $item);

                $items = $items->orderByRaw(\DB::raw("FIELD(item_no, " . $orderedIds . " ) desc"))->orderBy('item_no', 'DESC');
            } else {
                $items = $items->orderBy('item_no', 'DESC');
            }


            $items = $items->paginate($per_page, ['*'], 'page', $page);
            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'user' => Auth::user(),
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function importItemsList(ItemSearchRequest $request)
    {

        $validated = $request->validated();
        // return  $validated;
        try {
            //return $warehousing;
            if (isset($validated['items'])) {
                $item_no =  array_column($validated['items'], 'item_no');
            }



            if (isset($validated['w_no'])) {
                if (isset($item_no)) {
                    $warehousing_items = WarehousingItem::where('w_no', $validated['w_no'])->whereIn('item_no', $item_no)->get();
                } else {
                    $warehousing_items = WarehousingItem::where('w_no', $validated['w_no'])->get();
                }

                $items = [];
                foreach ($warehousing_items as $warehousing_item) {
                    $item = Item::with(['item_channels', 'company'])->where('item_no', $warehousing_item->item_no)->first();
                    $item->warehousing_item = $warehousing_item;
                    $items[] = $item;
                }
            }


            return response()->json([
                'message' => Messages::MSG_0007,
                'items' => $items,
                'sql' => DB::getQueryLog()
            ]);
        } catch (\Exception $e) {
            Log::error($e);

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
            $item = Item::with(['file', 'company'])->orderBy('item_no', 'DESC')->paginate($per_page, ['*'], 'page', $page);
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

            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('created_at', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('created_at', 'DESC');
            }
            if (isset($validated['from_date'])) {
                $item->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $item->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }
            if (isset($validated['co_name_shop'])) {
                $item->whereHas('company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }
            if (isset($validated['co_name_agency'])) {
                $item->whereHas('company', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_agency']) . '%', 'and', 'co_type', '=', 'shipper');
                });
            }
            if (isset($validated['item_name'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_name)'), 'like', '%' . $validated['item_name'] . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . $validated['item_cargo_bar_code'] . '%');
                });
            }
            if (isset($validated['item_channel_code'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_code)'), 'like', '%' . strtolower($validated['item_channel_code']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['item_channel_name'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . $validated['item_channel_name'] . '%');
                });
            }
            if (isset($validated['item_brand'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_brand)'), 'like', '%' . $validated['item_brand'] . '%');
                });
            }
            $item2 = $item->get();

            // $item3 = collect($item2)->map(function ($q){
            //     $item4 = Item::with(['warehousing_item'])->where('item_no', $q->item_no)->first();
            //     if(isset($item4['warehousing_item']['wi_number'])){
            //     return [ 'total_amount' => $item4['warehousing_item']['wi_number'] ,  'total_price' => $item4->item_price2 * $item4['warehousing_item']['wi_number']];
            //     }
            // });
            //  $item5 = $item3->sum('total_amount');
            //  $item6 = $item3->sum('total_price');


            $total_remain = 0;
            $total_get = 0;
            DB::enableQueryLog();
            $item3 = collect($item2)->map(function ($q) {
                $item4 = Item::where('item_no', $q->item_no)->first();
                $total_get = WarehousingItem::where('item_no', $q->item_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                $total_give = WarehousingItem::where('item_no', $q->item_no)->where('wi_type', '출고_spasys')->sum('wi_number');
                $total = $total_get - $total_give;
                return ['total_amount' => $total,  'total_price' => $item4->item_price2 * $total];
            });

            $item = $item->paginate($per_page, ['*'], 'page', $page);

            $item5 = $item3->sum('total_amount');
            $item6 = $item3->sum('total_price');




            $custom = collect(['sum1' => $item5, 'sum2' => $item6]);

            //


            //return DB::getQueryLog();
            $item->setCollection(
                $item->getCollection()->map(function ($q) {
                    $item = Item::with(['warehousing_item'])->where('item_no', $q->item_no)->first();
                    $total_get = WarehousingItem::where('item_no', $q->item_no)->where('wi_type', '입고_spasys')->sum('wi_number');
                    $total_give = WarehousingItem::where('item_no', $q->item_no)->where('wi_type', '출고_spasys')->sum('wi_number');
                    $total = $total_get - $total_give;
                    //if(isset($item['warehousing_item']['wi_number'])){
                    $q->total_price_row = $item->item_price2 * $total;
                    $q->item_total_amount = $total;
                    //}
                    return $q;
                })
            );

            $data = $custom->merge($item);

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateItemsApiId(Request $request)
    {
        try {
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }

            $item = $item->get();

            return response()->json(['item' => $item]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateItemsApiIdRawNoLogin()
    {
        try {
            $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->orderBy('item_no', 'DESC');
            $item = $item->get();
            return $item;
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateItemsApiIdCompanyRawNoLogin($co_no)
    {
        try {
            $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->whereIn('supply_code', $co_no)->orderBy('item_no', 'DESC');
            $item = $item->get();
            return $item;
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public static function paginateItemsApiIdRaw()
    {
        try {
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }
            // $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트');
            $item = $item->get();

            return $item;
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function paginateItemsApi(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
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
                $item->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }
            if (isset($validated['co_name_agency'])) {
                $item->whereHas('ContractWms.company', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_agency']) . '%', 'and', 'co_type', '=', 'shipper');
                });
            }
            if (isset($validated['item_name'])) {
                //return $validated['item_name'];
                $item->where(function ($query) use ($validated) {
                    $query->where('item_name', 'like', '%' . $validated['item_name'] . '%');
                });
            }
            if (isset($validated['product_id'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(product_id)'), 'like', '%' . strtolower($validated['product_id']) . '%');
                });
            }
            if (isset($validated['option_id'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(option_id)'), 'like', '%' . strtolower($validated['option_id']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_channel_code'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['item_channel_name'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_brand'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            $item2 = $item->get();
            $count_check = 0;
            $item3 = collect($item2)->map(function ($q) {
                $item4 = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
                if (isset($item4['item_info']['stock'])) {
                    return ['total_amount' => $item4['item_info']['stock']];
                }
            })->sum('total_amount');
            $item5 = collect($item2)->map(function ($q) {
                $item6 = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
                if (isset($item6['item_info']['stock'])) {
                    return ['total_price' => $item6->item_price2 * $item6['item_info']['stock']];
                }
            })->sum('total_price');


            $item = $item->paginate($per_page, ['*'], 'page', $page);

            $custom = collect(['sum1' => $item3, 'sum2' => $item5]);

            //return DB::getQueryLog();
            $item->setCollection(
                $item->getCollection()->map(function ($q) {
                    $item = Item::with(['item_info'])->where('item_no', $q->item_no)->first();
                    if (isset($item['item_info']['stock'])) {
                        $q->total_price_row = $item->item_price2 * $item['item_info']['stock'];
                    }
                    return $q;
                })
            );
            $data = $custom->merge($item);
            return response()->json($data);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
    public function paginateItemsApiStock(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            $user = Auth::user();
            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            if ($user->mb_type == 'shop') {
                // $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->select('item.*', 'stock_status_bad.stock')
                //     ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                //         $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                //         $leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                //     })
                //     ->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                //         $e->whereNotNull('stock');
                //     })->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->orderBy('item.item_no', 'DESC');
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                    //    $q->where('item_service_name', '=', '수입풀필먼트');
                    //    $q->whereHas('item_info', function ($e) {
                    //             $e->whereNotNull('stock');
                    //     });
                    $q->whereHas('ContractWms.company.co_parent', function ($k) use ($user) {
                        $k->where('co_no', $user->co_no);
                    });
                })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                // $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->select('item.*', 'stock_status_bad.stock')
                //     ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                //         $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                //         $leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                //     })->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                //         $e->whereNotNull('stock');
                //     })->whereHas('ContractWms.company', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->orderBy('item.item_no', 'DESC');
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                    //    $q->where('item_service_name', '=', '수입풀필먼트');
                    //    $q->whereHas('item_info', function ($e) {
                    //             $e->whereNotNull('stock');
                    //     });
                    $q->whereHas('ContractWms.company', function ($k) use ($user) {
                        $k->where('co_no', $user->co_no);
                    });
                })->whereNotNull('stock')->groupby('product_id')->groupby('option_id')->orderBy('product_id', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                // $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->select('item.*', 'stock_status_bad.stock')
                //     ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                //         $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                //         //$leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                //     })->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                //         $e->whereNotNull('stock');
                //     })->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                //         $q->where('co_no', $user->co_no);
                //     })->orderBy('item.item_no', 'DESC');
                $item = StockStatusBad::with(['item_status_bad'])->whereHas('item_status_bad', function ($q) use ($user) {
                    //    $q->where('item_service_name', '=', '수입풀필먼트');
                    //    $q->whereHas('item_info', function ($e) {
                    //             $e->whereNotNull('stock');
                    //     });
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
                    $query->where(DB::raw('lower(item_channel_code)'), 'like', '%' . strtolower($validated['item_channel_code']) . '%');
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
            $item2 = $item->get();

            $count_check = 0;

            $item3 = collect($item2)->map(function ($q) {
                // $item4 = Item::with(['item_info'])->where('item.item_no', $q->item_no)->first();
                // if (isset($item4['item_info']['stock'])) {
                //     return ['total_amount' => $item4['item_info']['stock']];
                // }
                if (isset($q->option_id)) {
                    $status = StockStatusBad::where('product_id', $q->product_id)->where('option_id', $q->option_id)->get();
                } else {
                    $status = StockStatusBad::where('product_id', $q->product_id)->get();
                }

                $count_total = 0;
                if (isset($status)) {
                    foreach ($status as $total) {
                        $count_total += $total->stock;
                    }
                }
                return ['total_amount' => $count_total];
            })->sum('total_amount');

            $item5 = collect($item2)->map(function ($q) {
                $item6 = Item::with(['item_info'])->where('item.item_no', $q->item_no)->first();

                if (isset($q->option_id)) {
                    $status = StockStatusBad::where('product_id', $q->product_id)->where('option_id', $q->option_id)->get();
                } else {
                    $status = StockStatusBad::where('product_id', $q->product_id)->get();
                }

                $count_total = 0;
                if (isset($status)) {
                    foreach ($status as $total) {
                        $count_total += $total->stock;
                    }
                }

                if (isset($count_total)) {
                    return ['total_price' => $item6->item_price2 * $count_total];
                }
            })->sum('total_price');


            $item = $item->paginate($per_page, ['*'], 'page', $page);

            $custom = collect(['sum1' => $item3, 'sum2' => $item5]);

            //return DB::getQueryLog();
            $item->setCollection(
                $item->getCollection()->map(function ($q) {
                    $item = Item::with(['item_info'])->where('item.item_no', $q->item_no)->first();
                    if (isset($item['item_info']['stock'])) {
                        $q->total_price_row = $item->item_price2 * $item['item_info']['stock'];
                    }
                    if (isset($q->option_id)) {
                        $status_0 = StockStatusBad::where('product_id', $q->product_id)->where('option_id', $q->option_id)->where('status', 0)->first();
                    } else {
                        $status_0 = StockStatusBad::where('product_id', $q->product_id)->where('status', 0)->first();
                    }
                    if (isset($status_0->stock)) {
                        $q->stock_0 = $status_0->stock;
                    }

                    if (isset($q->option_id)) {
                        $status_1 = StockStatusBad::where('product_id', $q->product_id)->where('option_id', $q->option_id)->where('status', 1)->first();
                    } else {
                        $status_1 = StockStatusBad::where('product_id', $q->product_id)->where('status', 1)->first();
                    }
                    if (isset($status_1->stock)) {
                        $q->stock_1 = $status_1->stock;
                    }

                    if (isset($status_0->stock) || isset($status_1->stock)) {
                        $stock0 = isset($status_0->stock) ? $status_0->stock : 0;
                        $stock1 = isset($status_1->stock) ? $status_1->stock : 0;
                        $q->stock_total = $stock1 + $stock0;
                    }


                    return $q;
                })
            );

            $data = $custom->merge($item);
            return $data;

            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            return response()->json($data);
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
            $company = $item->company()->get();
            $item_info = $item->item_info()->get();
            $contract_wms = $item->ContractWms()->first();
            $item['item_channels'] = $item_channels;
            $item['file'] = $file;
            $item['company'] = $company;
            $item['item_info'] = $item_info;
            $item['contract_wms'] = $contract_wms;
            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function getItemByIdApi(Item $item)
    {
        try {
            $file = $item->file()->first();
            $item_channels = $item->item_channels()->get();
            $company = $item->company()->get();
            $item_info = $item->item_info()->get();
            $contract_wms = $item->ContractWms()->first();
            $item['item_channels'] = $item_channels;
            $item['file'] = $file;
            $item['company'] = $company;
            $item['item_info'] = $item_info;
            $item['contract_wms'] = $contract_wms;
            $item_api = Item::with(['item_info'])->where('item.item_no', $item->item_no)->first();
            if (isset($item->option_id)) {
                $status_0 = StockStatusBad::where('product_id', $item->product_id)->where('option_id', $item->option_id)->where('status', 0)->first();
            } else {
                $status_0 = StockStatusBad::where('product_id', $item->product_id)->where('status', 0)->first();
            }
            if (isset($status_0->stock)) {
                $stock_0 = $status_0->stock;
            }
            if (isset($item->option_id)) {
                $status_1 = StockStatusBad::where('product_id', $item->product_id)->where('option_id', $item->option_id)->where('status', 1)->first();
            } else {
                $status_1 = StockStatusBad::where('product_id', $item->product_id)->where('status', 1)->first();
            }
            if (isset($status_1->stock)) {
                $stock_1 = $status_1->stock;
            }

            if (isset($status_0->stock) || isset($status_1->stock)) {
                $stock0 = isset($status_0->stock) ? $status_0->stock : 0;
                $stock1 = isset($status_1->stock) ? $status_1->stock : 0;
                $stock_total = $stock1 + $stock0;
            }

            return response()->json([
                'item' => $item,
                'stock_0' => isset($stock_0) ? $stock_0 : '',
                'stock_1' => isset($stock_1) ? $stock_1 : '',
                'stock_total' => isset($stock_total) ? $stock_total : ''
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
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
        // try {
        DB::beginTransaction();
        $f = Storage::disk('public')->put('files/tmp', $request['file']);

        $path = storage_path('app/public') . '/' . $f;
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheet(0);
        $datas = $sheet->toArray2(null, true, true, true);

        $sheet2 = null;//$spreadsheet->getSheet(1);
        $data_channels = null;//$sheet2->toArray(null, true, true, true);

        $results[$sheet->getTitle()] = [];
        $errors[$sheet->getTitle()] = [];

        $data_item_count = 0;
        $data_channel_count = 0;

        $check_error = false;
        //return $datas;
        foreach ($datas as $key => $d) {
            if ($key < 2) { 
                continue;
            }
           
            $validator = Validator::make($d, ExcelRequest::rules());
            
            if ($validator->fails()) {
                $data_item_count =  $data_item_count - 1;
                $errors[$sheet->getTitle()][] = $validator->errors();
                $check_error = true;
            } else {
                $data_item_count =  $data_item_count + 1;
                $company = Company::where('co_name', $d['B'])->first();
               
                $item_no = Item::insertGetId([
                    'item_service_name' => '유통가공',
                    'mb_no' => Auth::user()->mb_no,
                    //'co_no' => Auth::user()->co_no,
                    'co_no' => $company->co_no,
                    'item_brand' => $d['C'],
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
                    'item_cate3' => $d['R'],
                    // 'item_origin' => $d['R'],
                    // 'item_manufacturer' => $d['S']
                ]);

                // Check validator item_channel
                if (isset($data_channels)) {
                    $validator = [];
                    foreach ($data_channels as $key2 => $channel) {
                        if ($key2 == 1) {
                            continue;
                        }
                        $validator = Validator::make($channel, ChannelRequest::rules());
                        if ($d['A'] === $channel['A']) {
                            $data_channel_count = $data_channel_count + 1;
                            ItemChannel::insert([
                                'item_no' => $item_no,
                                'item_channel_code' => $channel['C'],
                                'item_channel_name' => $channel['D']
                            ]);
                        }
                    }
                    if ($validator->fails()) {
                        DB::rollback();
                        $data_channel_count =   $data_channel_count - 1;
                        $errors[$sheet->getTitle()][] = $validator->errors();
                        return $errors;
                        $check_error = true;
                    }
                }
            }
        }

        Storage::disk('public')->delete($f);
        if ($check_error == true) {
            DB::rollback();
            return response()->json([
                'message' => Messages::MSG_0007,
                'status' => 2,
                'errors' => $errors,
                'data_item_count' => $data_item_count,
                'data_channel_count' => $data_channel_count
            ], 201);
        } else {
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'errors' => $errors,
                'status' => 1,
                'data_item_count' => $data_item_count,
                'data_channel_count' => $data_channel_count
            ], 201);
        }

        // } catch (\Exception $e) {

        //     Log::error($e);
        //     return response()->json(['message' => Messages::MSG_0004], 500);
        // }
    }

    public function downloadFulfillmentItemList(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::enableQueryLog();
            $user = Auth::user();

            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->where('item_service_name', '=', '수입풀필먼트')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            }

            // if (isset($validated['from_date'])) {
            //     $item->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            // }

            // if (isset($validated['to_date'])) {
            //     $item->where('created_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            // }
            if (isset($validated['co_name_shop'])) {
                $item->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_shop']) . '%');
                });
            }
            if (isset($validated['co_name_agency'])) {
                $item->whereHas('ContractWms.company', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . strtolower($validated['co_name_agency']) . '%', 'and', 'co_type', '=', 'shipper');
                });
            }
            if (isset($validated['item_name'])) {
                //return $validated['item_name'];
                $item->where(function ($query) use ($validated) {
                    $query->where('item_name', 'like', '%' . $validated['item_name'] . '%');
                });
            }
            if (isset($validated['product_id'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(product_id)'), 'like', '%' . strtolower($validated['product_id']) . '%');
                });
            }
            if (isset($validated['option_id'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(option_id)'), 'like', '%' . strtolower($validated['option_id']) . '%');
                });
            }
            if (isset($validated['item_cargo_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_cargo_bar_code)'), 'like', '%' . strtolower($validated['item_cargo_bar_code']) . '%');
                });
            }
            if (isset($validated['item_channel_code'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_bar_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_bar_code)'), 'like', '%' . strtolower($validated['item_bar_code']) . '%');
                });
            }
            if (isset($validated['item_upc_code'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_upc_code)'), 'like', '%' . strtolower($validated['item_upc_code']) . '%');
                });
            }
            if (isset($validated['item_channel_name'])) {
                $item->whereHas('item_channels', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }
            if (isset($validated['item_brand'])) {
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }
            $item = $item->get();
            //$item->get();
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'No');
            $sheet->setCellValue('B1', '가맹점');
            $sheet->setCellValue('C1', '화주');
            $sheet->setCellValue('D1', '상품코드');
            $sheet->setCellValue('E1', '옵션코드');
            $sheet->setCellValue('F1', '상품명');
            $sheet->setCellValue('G1', '옵션1');
            $sheet->setCellValue('H1', '옵션2');
            $sheet->setCellValue('I1', '등록일시');

            $num_row = 2;
            //return $item;
            // $data_schedules =  json_decode($import_schedule);
            if (!empty($item)) {
                foreach ($item as $key => $data) {
                    
                    $sheet->setCellValue('A' . $num_row, ($key+1));
                    $sheet->setCellValue('B' . $num_row, isset($data->ContractWms->company->co_parent->co_name) ? $data->ContractWms->company->co_parent->co_name : '');
                    $sheet->setCellValue('C' . $num_row, isset($data->ContractWms->company->co_name) ? $data->ContractWms->company->co_name : '');
                    $sheet->setCellValue('D' . $num_row, $data->product_id);
                    $sheet->setCellValue('E' . $num_row, $data->option_id);
                    $sheet->setCellValue('F' . $num_row, $data->item_name);
                    $sheet->setCellValue('G' . $num_row, $data->item_option1);
                    $sheet->setCellValue('H' . $num_row, $data->item_option2);
                    $sheet->setCellValue('I' . $num_row, $data->created_at);
                    $num_row++;
                }
            }
            
            $Excel_writer = new Xlsx($spreadsheet);
            if (isset($user->mb_no)) {
                $path = 'storage/download/' . $user->mb_no . '/';
            } else {
                $path = 'storage/download/no-name/';
            }
            if (!is_dir($path)) {
                Files::makeDirectory($path, $mode = 0777, true, true);
            }

            $name = '수입_상품리스팅_';

            $mask = $path . $name . '*.*';
            array_map('unlink', glob($mask) ?: []);
            $file_name_download = $path . $name . date('YmdHis') . '.Xlsx';
            $check_status = $Excel_writer->save($file_name_download);
            return response()->json([
                'status' => 1,
                'link_download' => '../'. $file_name_download,
                'message' => 'Download File',
            ], 200);
            ob_end_clean();
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
        }
    }

    public function updateFile(Request $request)
    {
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

    public function apiItems(Request $request)
    {
        //return $request;
        //$validated = $request->validated();
        try {

            DB::beginTransaction();
            $user = Auth::user();
            if ($user->mb_type == 'shipper') {
                foreach ($request->data as $i_item => $item) {
                    $item_no = Item::updateOrCreate(
                        [
                            'product_id' => $item['product_id']
                        ],
                        [
                            'mb_no' => Auth::user()->mb_no,
                            'co_no' =>  isset($item['co_no']) ? $item['co_no'] : Auth::user()->co_no,
                            'item_name' => $item['name'],
                            'supply_code' => $item['supply_code'],
                            'item_brand' => $item['brand'],
                            'item_origin' => $item['origin'],
                            'item_weight' => $item['weight'],
                            'item_price1' => $item['org_price'],
                            'item_price2' => $item['shop_price'],
                            'item_price3' => $item['supply_price'],
                            'item_url' => $item['img_500'],
                            'item_option1' => $item['options'],
                            'item_bar_code' => $item['barcode'],
                            'item_service_name' => '수입풀필먼트',
                        ]
                    );
                    if ($item_no->item_no) {
                        $item_info_no = ItemInfo::updateOrCreate(

                            [

                                'item_no' => $item_no->item_no,
                            ],
                            [
                                'product_id' => $item['product_id'],
                                'supply_code' => $item['supply_code'],
                                'trans_fee' => $item['trans_fee'],
                                'img_desc1' => $item['img_desc1'],
                                'img_desc2' => $item['img_desc2'],
                                'img_desc3' => $item['img_desc3'],
                                'img_desc4' => $item['img_desc4'],
                                'img_desc5' => $item['img_desc5'],
                                'product_desc' => $item['product_desc'],
                                'product_desc2' => $item['product_desc2'],
                                'location' => $item['location'],
                                'memo' => $item['memo'],
                                'category' => $item['category'],
                                'maker' => $item['maker'],
                                'md' => $item['md'],
                                'manager1' => $item['manager1'],
                                'manager2' => $item['manager2'],
                                'supply_options' => $item['supply_options'],
                                'enable_sale' => $item['enable_sale'],
                                'use_temp_soldout' => $item['use_temp_soldout'],
                                'stock_alarm1' => $item['stock_alarm1'],
                                'stock_alarm2' => $item['stock_alarm2'],
                                'extra_price' => $item['extra_price'],
                                'extra_shop_price' => $item['extra_shop_price'],

                                'extra_column1' => $item['extra_column1'],
                                'extra_column2' => $item['extra_column2'],
                                'extra_column3' => $item['extra_column3'],
                                'extra_column4' => $item['extra_column4'],
                                'extra_column5' => $item['extra_column5'],
                                'extra_column6' => $item['extra_column6'],
                                'extra_column7' => $item['extra_column7'],
                                'extra_column8' => $item['extra_column8'],
                                'extra_column9' => $item['extra_column9'],
                                'extra_column10' => $item['extra_column10'],
                                'reg_date' => $item['reg_date'],
                                'last_update_date' => $item['last_update_date'],
                                'new_link_id' => $item['new_link_id'],
                                'link_id' => $item['link_id'],
                            ]
                        );
                    }
                }
            } else if ($user->mb_type == 'shop') {
                $get_shipper_company = Company::with(['co_parent'])->whereHas('co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $co_no_shipper = array();
                foreach ($get_shipper_company as $shipper_company) {

                    foreach ($request->data as $i_item => $item) {
                        $item_no = Item::updateOrCreate(
                            [
                                'product_id' => $item['product_id']
                            ],
                            [
                                'mb_no' => Auth::user()->mb_no,
                                'co_no' => $shipper_company->co_no,
                                'item_name' => $item['name'],
                                'supply_code' => $item['supply_code'],
                                'item_brand' => $item['brand'],
                                'item_origin' => $item['origin'],
                                'item_weight' => $item['weight'],
                                'item_price1' => $item['org_price'],
                                'item_price2' => $item['shop_price'],
                                'item_price3' => $item['supply_price'],
                                'item_url' => $item['img_500'],
                                'item_option1' => $item['options'],
                                'item_bar_code' => $item['barcode'],
                                'item_service_name' => '수입풀필먼트',
                            ]
                        );
                        if ($item_no->item_no) {
                            $item_info_no = ItemInfo::updateOrCreate(

                                [

                                    'item_no' => $item_no->item_no,
                                ],
                                [
                                    'product_id' => $item['product_id'],
                                    'supply_code' => $item['supply_code'],
                                    'trans_fee' => $item['trans_fee'],
                                    'img_desc1' => $item['img_desc1'],
                                    'img_desc2' => $item['img_desc2'],
                                    'img_desc3' => $item['img_desc3'],
                                    'img_desc4' => $item['img_desc4'],
                                    'img_desc5' => $item['img_desc5'],
                                    'product_desc' => $item['product_desc'],
                                    'product_desc2' => $item['product_desc2'],
                                    'location' => $item['location'],
                                    'memo' => $item['memo'],
                                    'category' => $item['category'],
                                    'maker' => $item['maker'],
                                    'md' => $item['md'],
                                    'manager1' => $item['manager1'],
                                    'manager2' => $item['manager2'],
                                    'supply_options' => $item['supply_options'],
                                    'enable_sale' => $item['enable_sale'],
                                    'use_temp_soldout' => $item['use_temp_soldout'],
                                    'stock_alarm1' => $item['stock_alarm1'],
                                    'stock_alarm2' => $item['stock_alarm2'],
                                    'extra_price' => $item['extra_price'],
                                    'extra_shop_price' => $item['extra_shop_price'],

                                    'extra_column1' => $item['extra_column1'],
                                    'extra_column2' => $item['extra_column2'],
                                    'extra_column3' => $item['extra_column3'],
                                    'extra_column4' => $item['extra_column4'],
                                    'extra_column5' => $item['extra_column5'],
                                    'extra_column6' => $item['extra_column6'],
                                    'extra_column7' => $item['extra_column7'],
                                    'extra_column8' => $item['extra_column8'],
                                    'extra_column9' => $item['extra_column9'],
                                    'extra_column10' => $item['extra_column10'],
                                    'reg_date' => $item['reg_date'],
                                    'last_update_date' => $item['last_update_date'],
                                    'new_link_id' => $item['new_link_id'],
                                    'link_id' => $item['link_id'],
                                ]
                            );
                        }
                    }
                }
            } else if ($user->mb_type == 'spasys') {

                $get_shop_company = Company::with(['co_parent'])->whereHas('co_parent.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->get();
                $co_no_shop = array();
                foreach ($get_shop_company as $shop_company) {

                    foreach ($request->data as $i_item => $item) {
                        $item_no = Item::updateOrCreate(
                            [
                                'product_id' => $item['product_id']
                            ],
                            [
                                'mb_no' => Auth::user()->mb_no,
                                'co_no' => $shop_company->co_no,
                                'item_name' => $item['name'],
                                'supply_code' => $item['supply_code'],
                                'item_brand' => $item['brand'],
                                'item_origin' => $item['origin'],
                                'item_weight' => $item['weight'],
                                'item_price1' => $item['org_price'],
                                'item_price2' => $item['shop_price'],
                                'item_price3' => $item['supply_price'],
                                'item_url' => $item['img_500'],
                                'item_option1' => $item['options'],
                                'item_bar_code' => isset($item['barcode']) ? $item['barcode'] : null,
                                'item_service_name' => '수입풀필먼트',
                            ]
                        );
                        if ($item_no->item_no) {
                            $item_info_no = ItemInfo::updateOrCreate(

                                [

                                    'item_no' => $item_no->item_no,
                                ],
                                [
                                    'product_id' => $item['product_id'],
                                    'supply_code' => $item['supply_code'],
                                    'trans_fee' => $item['trans_fee'],
                                    'img_desc1' => $item['img_desc1'],
                                    'img_desc2' => $item['img_desc2'],
                                    'img_desc3' => $item['img_desc3'],
                                    'img_desc4' => $item['img_desc4'],
                                    'img_desc5' => $item['img_desc5'],
                                    'product_desc' => $item['product_desc'],
                                    'product_desc2' => $item['product_desc2'],
                                    'location' => $item['location'],
                                    'memo' => $item['memo'],
                                    'category' => $item['category'],
                                    'maker' => $item['maker'],
                                    'md' => $item['md'],
                                    'manager1' => $item['manager1'],
                                    'manager2' => $item['manager2'],
                                    'supply_options' => $item['supply_options'],
                                    'enable_sale' => $item['enable_sale'],
                                    'use_temp_soldout' => $item['use_temp_soldout'],
                                    'stock_alarm1' => $item['stock_alarm1'],
                                    'stock_alarm2' => $item['stock_alarm2'],
                                    'extra_price' => $item['extra_price'],
                                    'extra_shop_price' => $item['extra_shop_price'],

                                    'extra_column1' => $item['extra_column1'],
                                    'extra_column2' => $item['extra_column2'],
                                    'extra_column3' => $item['extra_column3'],
                                    'extra_column4' => $item['extra_column4'],
                                    'extra_column5' => $item['extra_column5'],
                                    'extra_column6' => $item['extra_column6'],
                                    'extra_column7' => $item['extra_column7'],
                                    'extra_column8' => $item['extra_column8'],
                                    'extra_column9' => $item['extra_column9'],
                                    'extra_column10' => $item['extra_column10'],
                                    'reg_date' => $item['reg_date'],
                                    'last_update_date' => $item['last_update_date'],
                                    'new_link_id' => $item['new_link_id'],
                                    'link_id' => $item['link_id'],
                                ]
                            );
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    // public function apiItemsRaw($request = null)
    // {
    //     try {
    //         DB::beginTransaction();
    //         $user = Auth::user();
    //         $data_select = !empty($request->data)?$request->data:array();
    //         if ($user->mb_type == 'shipper') {
    //             foreach ($data_select as $i_item => $item) {
    //                 $check_item = DB::table('item')->where('product_id','=',$item->product_id)
    //                 ->where('co_no','=',$item->co_no)
    //                 ->get();
    //                 if(count($check_item) == 0){
    //                     $item_no = Item::updateOrCreate(
    //                         [
    //                             'product_id' => $item->product_id
    //                         ],
    //                         [
    //                             'mb_no' => Auth::user()->mb_no,
    //                             'co_no' => isset($item->co_no) ? $item->co_no : Auth::user()->co_no,
    //                             'item_name' => $item->name,
    //                             'supply_code' => $item->supply_code,
    //                             'item_brand' => $item->brand,
    //                             'item_origin' => $item->origin,
    //                             'item_weight' => $item->weight,
    //                             'item_price1' => $item->org_price,
    //                             'item_price2' => $item->shop_price,
    //                             'item_price3' => $item->supply_price,
    //                             'item_url' => $item->img_500,
    //                             'item_service_name' => '수입풀필먼트',
    //                         ]
    //                     );
    //                     $item_info_no = ItemInfo::updateOrCreate(
    //                         [
    //                             'item_no' => $item_no->item_no,
    //                         ],
    //                         [
    //                             'product_id' => $item->product_id,
    //                             'supply_code' => $item->supply_code,
    //                             'trans_fee' => $item->trans_fee,
    //                             'img_desc1' => $item->img_desc1,
    //                             'img_desc2' => $item->img_desc2,
    //                             'img_desc3' => $item->img_desc3,
    //                             'img_desc4' => $item->img_desc4,
    //                             'img_desc5' => $item->img_desc5,
    //                             'product_desc' => $item->product_desc,
    //                             'product_desc2' => $item->product_desc2,
    //                             'location' => $item->location,
    //                             'memo' => $item->memo,
    //                             'category' => $item->category,
    //                             'maker' => $item->maker,
    //                             'md' => $item->md,
    //                             'manager1' => $item->manager1,
    //                             'manager2' => $item->manager2
    //                         ]
    //                     );
    //                 }
    //                 if ($item_no->item_no) {
    //                     if(!empty($item->options)){
    //                         $item_arr = (array)$item->options;
    //                         if (is_array($item_arr) || is_object($item_arr))
    //                         {
    //                             foreach($item_arr as $option){
    //                                 $option_pro_id = $item->product_id . $option->product_id;
    //                                 $check_item = DB::table('item')->where('product_id','=',$option_pro_id)
    //                                 ->where('co_no','=',$item->co_no)
    //                                 ->get();
    //                                 if(count($check_item) == 0){
    //                                     $item_no = Item::updateOrCreate(
    //                                         [
    //                                             'product_id' => $option_pro_id
    //                                         ],
    //                                         [
    //                                             'mb_no' => Auth::user()->mb_no,
    //                                             'co_no' => isset($item->co_no) ? $item->co_no : Auth::user()->co_no,
    //                                             'item_name' => $item->name,
    //                                             'supply_code' => $item->supply_code,
    //                                             'item_brand' => $item->brand,
    //                                             'item_origin' => $item->origin,
    //                                             'item_weight' => $item->weight,
    //                                             'item_price1' => $item->org_price,
    //                                             'item_price2' => $item->shop_price,
    //                                             'item_price3' => $item->supply_price,
    //                                             'item_url' => $item->img_500,
    //                                             'item_option1' => $option->options,
    //                                             'item_service_name' => '수입풀필먼트'
    //                                         ]
    //                                     );
    //                                     $item_info_no = ItemInfo::updateOrCreate(
    //                                         [
    //                                             'item_no' => $item_no->item_no,
    //                                         ],
    //                                         [
    //                                             'product_id' => $item->product_id,
    //                                             'supply_code' => $item->supply_code,
    //                                             'trans_fee' => $item->trans_fee,
    //                                             'img_desc1' => $item->img_desc1,
    //                                             'img_desc2' => $item->img_desc2,
    //                                             'img_desc3' => $item->img_desc3,
    //                                             'img_desc4' => $item->img_desc4,
    //                                             'img_desc5' => $item->img_desc5,
    //                                             'product_desc' => $item->product_desc,
    //                                             'product_desc2' => $item->product_desc2,
    //                                             'location' => $item->location,
    //                                             'memo' => $item->memo,
    //                                             'category' => $item->category,
    //                                             'maker' => $item->maker,
    //                                             'md' => $item->md,
    //                                             'manager1' => $item->manager1,
    //                                             'manager2' => $item->manager2,
    //                                             'supply_options' => !empty($option->supply_options)?$option->supply_options:'',
    //                                             'enable_sale' => !empty($option->enable_sale)?$option->enable_sale:1,
    //                                             'use_temp_soldout' => !empty($option->use_temp_soldout)?$option->use_temp_soldout:0,
    //                                             'stock_alarm1' => !empty($option->stock_alarm1)?$option->stock_alarm1:0,
    //                                             'stock_alarm2' => !empty($option->stock_alarm2)?$option->stock_alarm2:0,
    //                                             'extra_price' => !empty($option->extra_price)?$option->extra_price:0,
    //                                             'extra_shop_price' => !empty($option->extra_shop_price)?$option->extra_shop_price:0,
    //                                             'extra_column6' => !empty($option->extra_column6)?$option->extra_column6:'',
    //                                             'extra_column7' => !empty($option->extra_column7)?$option->extra_column7:'',
    //                                             'extra_column8' => !empty($option->extra_column8)?$option->extra_column8:'',
    //                                             'extra_column9' => !empty($option->extra_column9)?$option->extra_column9:'',
    //                                             'extra_column10' => !empty($option->extra_column10)?$option->extra_column10:'',
    //                                             'reg_date' => !empty($option->reg_date)?$option->reg_date:null,
    //                                             'last_update_date' => !empty($option->last_update_date)?$option->last_update_date:null,
    //                                             'new_link_id' => !empty($option->new_link_id)?$option->new_link_id:'',
    //                                             'link_id' => !empty($option->link_id)?$option->link_id:'',
    //                                         ]
    //                                     );
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         } else if ($user->mb_type == 'shop') {
    //             $get_shipper_company = Company::with(['co_parent'])->whereHas('co_parent', function ($q) use ($user) {
    //                 $q->where('co_no', $user->co_no);
    //             })->get();
    //             $co_no_shipper = array();
    //             foreach ($get_shipper_company as $shipper_company) {
    //                 foreach ($data_select as $i_item => $item) {
    //                     $check_item = DB::table('item')->where('product_id','=',$item->product_id)
    //                                 ->where('co_no','=',$shipper_company->co_no)
    //                                 ->get();

    //                     if(count($check_item) == 0){
    //                         $item_no = Item::updateOrCreate(
    //                             [
    //                                 'product_id' => $item->product_id
    //                             ],
    //                             [
    //                                 'mb_no' => Auth::user()->mb_no,
    //                                 'co_no' => $shipper_company->co_no,
    //                                 'item_name' => $item->name,
    //                                 'supply_code' => $item->supply_code,
    //                                 'item_brand' => $item->brand,
    //                                 'item_origin' => $item->origin,
    //                                 'item_weight' => $item->weight,
    //                                 'item_price1' => $item->org_price,
    //                                 'item_price2' => $item->shop_price,
    //                                 'item_price3' => $item->supply_price,
    //                                 'item_url' => $item->img_500,
    //                                 'item_service_name' => '수입풀필먼트'
    //                             ]
    //                         );

    //                         if ($item_no->item_no) {
    //                             $item_info_no = ItemInfo::updateOrCreate(
    //                                 [
    //                                     'item_no' => $item_no->item_no,
    //                                 ],
    //                                 [
    //                                     'product_id' => $item->product_id,
    //                                     'supply_code' => $item->supply_code,
    //                                     'trans_fee' => $item->trans_fee,
    //                                     'img_desc1' => $item->img_desc1,
    //                                     'img_desc2' => $item->img_desc2,
    //                                     'img_desc3' => $item->img_desc3,
    //                                     'img_desc4' => $item->img_desc4,
    //                                     'img_desc5' => $item->img_desc5,
    //                                     'product_desc' => $item->product_desc,
    //                                     'product_desc2' => $item->product_desc2,
    //                                     'location' => $item->location,
    //                                     'memo' => $item->memo,
    //                                     'category' => $item->category,
    //                                     'maker' => $item->maker,
    //                                     'md' => $item->md,
    //                                     'manager1' => $item->manager1,
    //                                     'manager2' => $item->manager2,
    //                                     'extra_column1' => $item->extra_column1,
    //                                     'extra_column2' => $item->extra_column2,
    //                                     'extra_column3' => $item->extra_column3,
    //                                     'extra_column4' => $item->extra_column4,
    //                                     'extra_column5' => $item->extra_column5,
    //                                     'reg_date' => $item->reg_date,
    //                                     'last_update_date' => $item->last_update_date,
    //                                 ]
    //                             );
    //                         }
    //                     }

    //                     if(!empty($item->options)){
    //                         if (is_array($item->options) || is_object($item->options))
    //                         {
    //                             foreach($item->options as $option){

    //                                 $check_item = DB::table('item')->where('product_id','=',$item->product_id)->where('option_id',$option->product_id)
    //                                 ->where('co_no','=',$shipper_company->co_no)
    //                                 ->get();

    //                                 if(count($check_item) == 0){
    //                                     $item_no = Item::updateOrCreate(
    //                                         [
    //                                             'product_id' => $item->product_id,
    //                                             'option_id' => $option->product_id
    //                                         ],
    //                                         [
    //                                             'mb_no' => Auth::user()->mb_no,
    //                                             'co_no' => isset($item->co_no) ? $item->co_no : Auth::user()->co_no,
    //                                             'item_name' => $item->name,
    //                                             'supply_code' => $item->supply_code,
    //                                             'item_brand' => $item->brand,
    //                                             'item_origin' => $item->origin,
    //                                             'item_weight' => $item->weight,
    //                                             'item_price1' => $item->org_price,
    //                                             'item_price2' => $item->shop_price,
    //                                             'item_price3' => $item->supply_price,
    //                                             'item_url' => $item->img_500,
    //                                             'item_option1' => $option->options,
    //                                             'item_service_name' => '수입풀필먼트'
    //                                         ]
    //                                     );
    //                                     $item_info_no = ItemInfo::updateOrCreate(
    //                                         [
    //                                             'item_no' => $item_no->item_no,
    //                                         ],
    //                                         [
    //                                             'product_id' => $item->product_id,
    //                                             'supply_code' => $item->supply_code,
    //                                             'trans_fee' => $item->trans_fee,
    //                                             'img_desc1' => $item->img_desc1,
    //                                             'img_desc2' => $item->img_desc2,
    //                                             'img_desc3' => $item->img_desc3,
    //                                             'img_desc4' => $item->img_desc4,
    //                                             'img_desc5' => $item->img_desc5,
    //                                             'product_desc' => $item->product_desc,
    //                                             'product_desc2' => $item->product_desc2,
    //                                             'location' => $item->location,
    //                                             'memo' => $item->memo,
    //                                             'category' => $item->category,
    //                                             'maker' => $item->maker,
    //                                             'md' => $item->md,
    //                                             'manager1' => $item->manager1,
    //                                             'manager2' => $item->manager2,
    //                                             'supply_options' => !empty($option->supply_options)?$option->supply_options:'',
    //                                             'enable_sale' => !empty($option->enable_sale)?$option->enable_sale:1,
    //                                             'use_temp_soldout' => !empty($option->use_temp_soldout)?$option->use_temp_soldout:0,
    //                                             'stock_alarm1' => !empty($option->stock_alarm1)?$option->stock_alarm1:0,
    //                                             'stock_alarm2' => !empty($option->stock_alarm2)?$option->stock_alarm2:0,
    //                                             'extra_price' => !empty($option->extra_price)?$option->extra_price:0,
    //                                             'extra_shop_price' => !empty($option->extra_shop_price)?$option->extra_shop_price:0,
    //                                             'extra_column6' => !empty($option->extra_column6)?$option->extra_column6:'',
    //                                             'extra_column7' => !empty($option->extra_column7)?$option->extra_column7:'',
    //                                             'extra_column8' => !empty($option->extra_column8)?$option->extra_column8:'',
    //                                             'extra_column9' => !empty($option->extra_column9)?$option->extra_column9:'',
    //                                             'extra_column10' => !empty($option->extra_column10)?$option->extra_column10:'',
    //                                             'reg_date' => !empty($option->reg_date)?$option->reg_date:null,
    //                                             'last_update_date' => !empty($option->last_update_date)?$option->last_update_date:null,
    //                                             'new_link_id' => !empty($option->new_link_id)?$option->new_link_id:'',
    //                                             'link_id' => !empty($option->link_id)?$option->link_id:'',
    //                                         ]
    //                                     );
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         } else if ($user->mb_type == 'spasys') {
    //             $get_shop_company = Company::with(['co_parent'])->whereHas('co_parent.co_parent', function ($q) use ($user) {
    //                 $q->where('co_no', $user->co_no);
    //             })->get();
    //             $co_no_shop = array();
    //             foreach ($get_shop_company as $shop_company) {
    //                 foreach ($data_select as $i_item => $item) {
    //                     $data_update_create = [
    //                         'mb_no' => Auth::user()->mb_no,
    //                         'co_no' => $shop_company->co_no,
    //                         'item_name' => $item->name,
    //                         'supply_code' => $item->supply_code,
    //                         'item_brand' => $item->brand,
    //                         'item_origin' => $item->origin,
    //                         'item_weight' => $item->weight,
    //                         'item_price1' => $item->org_price,
    //                         'item_price2' => $item->shop_price,
    //                         'item_price3' => $item->supply_price,
    //                         'item_url' => $item->img_500,
    //                         'item_option1' => '',
    //                         'item_bar_code' => isset($item->barcode) ? $item->barcode: null,
    //                         'item_service_name' => '수입풀필먼트'
    //                     ];
    //                     $data_update_create['item_name'] = $data_update_create['item_name'];
    //                     $data_update_create['product_id'] = $item->product_id;
    //                     $check_item = DB::table('item')->where('product_id','=',$item->product_id)
    //                                 ->where('co_no','=',$shop_company->co_no)
    //                                 ->get();
    //                     if(count($check_item) == 0){
    //                         $item_no = Item::updateOrCreate(
    //                             [
    //                                 'product_id' => $item->product_id,
    //                                 'co_no' => $shop_company->co_no
    //                             ],
    //                             $data_update_create
    //                         );
    //                         $item_info_no = ItemInfo::updateOrCreate(
    //                             [
    //                                 'item_no' => $item_no->item_no,
    //                             ],
    //                             [
    //                                 'product_id' => $item->product_id,
    //                                 'supply_code' => $item->supply_code,
    //                                 'trans_fee' => $item->trans_fee,
    //                                 'img_desc1' => $item->img_desc1,
    //                                 'img_desc2' => $item->img_desc2,
    //                                 'img_desc3' => $item->img_desc3,
    //                                 'img_desc4' => $item->img_desc4,
    //                                 'img_desc5' => $item->img_desc5,
    //                                 'product_desc' => $item->product_desc,
    //                                 'product_desc2' => $item->product_desc2,
    //                                 'location' => $item->location,
    //                                 'memo' => $item->memo,
    //                                 'category' => $item->category,
    //                                 'maker' => $item->maker,
    //                                 'md' => $item->md,
    //                                 'manager1' => $item->manager1,
    //                                 'manager2' => $item->manager2
    //                             ]
    //                         );
    //                     }else{
    //                         $item_no = Item::where('product_id', $item->product_id)
    //                                     ->where('co_no', $shop_company->co_no)
    //                                     ->update($data_update_create);
    //                         $item_no_update = Item::where('product_id', $item->product_id)->where('co_no', $shop_company->co_no)->first();
    //                         if(!empty($item_no_update->item_no)){
    //                             ItemInfo::where('item_no', $item_no_update->item_no)->update(
    //                                 [
    //                                     'product_id' => $item->product_id,
    //                                     'supply_code' => $item->supply_code,
    //                                     'trans_fee' => $item->trans_fee,
    //                                     'img_desc1' => $item->img_desc1,
    //                                     'img_desc2' => $item->img_desc2,
    //                                     'img_desc3' => $item->img_desc3,
    //                                     'img_desc4' => $item->img_desc4,
    //                                     'img_desc5' => $item->img_desc5,
    //                                     'product_desc' => $item->product_desc,
    //                                     'product_desc2' => $item->product_desc2,
    //                                     'location' => $item->location,
    //                                     'memo' => $item->memo,
    //                                     'category' => $item->category,
    //                                     'maker' => $item->maker,
    //                                     'md' => $item->md,
    //                                     'manager1' => $item->manager1,
    //                                     'manager2' => $item->manager2
    //                                 ]
    //                             );
    //                         }
    //                     }
    //                     if(!empty($item->options) && is_array($item->options)){
    //                         foreach($item->options as $option){
    //                             $data_update_create['item_option1'] = $option->options;
    //                             $data_update_create['item_name'] = $data_update_create['item_name'];
    //                             $check_item = DB::table('item')->where('product_id','=',$item->product_id)->where('option_id',$option->product_id)
    //                                 ->where('co_no','=',$shop_company->co_no)
    //                                 ->get();
    //                             $data_item_info = [
    //                                 'product_id' => $item->product_id,
    //                                 'supply_code' => $item->supply_code,
    //                                 'trans_fee' => $item->trans_fee,
    //                                 'img_desc1' => $item->img_desc1,
    //                                 'img_desc2' => $item->img_desc2,
    //                                 'img_desc3' => $item->img_desc3,
    //                                 'img_desc4' => $item->img_desc4,
    //                                 'img_desc5' => $item->img_desc5,
    //                                 'product_desc' => $item->product_desc,
    //                                 'product_desc2' => $item->product_desc2,
    //                                 'location' => $item->location,
    //                                 'memo' => $item->memo,
    //                                 'category' => $item->category,
    //                                 'maker' => $item->maker,
    //                                 'md' => $item->md,
    //                                 'manager1' => $item->manager1,
    //                                 'manager2' => $item->manager2,
    //                                 'supply_options' => $option->supply_options,
    //                                 'enable_sale' => $option->enable_sale,
    //                                 'use_temp_soldout' => $option->use_temp_soldout,
    //                                 'stock_alarm1' => $option->stock_alarm1,
    //                                 'stock_alarm2' => $option->stock_alarm2,
    //                                 'extra_price' => $option->extra_price,
    //                                 'extra_shop_price' => $option->extra_shop_price,
    //                                 'extra_column1' => !empty($option->extra_column1)?$option->extra_column1:'',
    //                                 'extra_column2' => !empty($option->extra_column2)?$option->extra_column2:'',
    //                                 'extra_column3' => !empty($option->extra_column3)?$option->extra_column3:'',
    //                                 'extra_column4' => !empty($option->extra_column4)?$option->extra_column4:'',
    //                                 'extra_column5' => !empty($option->extra_column5)?$option->extra_column5:'',
    //                                 'extra_column6' => $option->extra_column6,
    //                                 'extra_column7' => $option->extra_column7,
    //                                 'extra_column8' => $option->extra_column8,
    //                                 'extra_column9' => $option->extra_column9,
    //                                 'extra_column10' => $option->extra_column10,
    //                                 'reg_date' => $option->reg_date,
    //                                 'last_update_date' => $option->last_update_date,
    //                                 'new_link_id' => $option->new_link_id,
    //                                 'link_id' => $option->link_id,
    //                             ];
    //                             if(count($check_item) == 0){
    //                                 $data_update_create['product_id'] = $item->product_id;
    //                                 $item_no = Item::updateOrCreate(
    //                                     [
    //                                         'product_id' => $item->product_id,
    //                                         'option_id' => $option->product_id,
    //                                         'co_no' => $shop_company->co_no
    //                                     ],
    //                                     $data_update_create
    //                                 );
    //                                 if($item_no->item_no) {
    //                                     ItemInfo::updateOrCreate(
    //                                         [
    //                                             'item_no' => $item_no->item_no,
    //                                         ],
    //                                         $data_item_info
    //                                     );
    //                                 }
    //                             }else{
    //                                 $item_no = Item::where('product_id', $item->product_id)
    //                                             ->where('co_no', $shop_company->co_no)
    //                                             ->update(
    //                                                 $data_update_create
    //                                             );
    //                                 $item_no_update = Item::where('product_id', $item->product_id)
    //                                 ->where('co_no', $shop_company->co_no)->first();
    //                                 if(!empty($item_no_update->item_no)) {
    //                                     ItemInfo::where('item_no', $item_no_update->item_no)->update(
    //                                         $data_item_info
    //                                     );
    //                                 }
    //                             }
    //                         }
    //                     }
    //                 }
    //             }
    //         }

    //         DB::commit();
    //         return response()->json([
    //             'message' => '완료되었습니다.',
    //             'status' => 1,
    //             'data' => $data_select
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         Log::error($e);
    //         return $e;
    //         return response()->json([
    //             'message' => '오류가 발생하였습니다.',
    //         ], 500);
    //     }
    // }

    public function apiItemsRaw($request = null, $url_api = null)
    {
        try {
            DB::beginTransaction();
            $data_select = !empty($request->data) ? $request->data : array();

            foreach ($data_select as $i_item => $item) {
                $item_no_all = Item::updateOrCreate(
                    [
                        'product_id' => $item->product_id
                    ],
                    [
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => isset($item->co_no) ? $item->co_no : Auth::user()->co_no,
                        'item_name' => isset($item->name) ? $item->name : null,
                        'supply_code' => isset($item->supply_code) ? $item->supply_code : null,
                        'item_brand' => isset($item->brand) ? $item->brand : null,
                        'item_origin' => isset($item->origin) ? $item->origin : null,
                        'item_weight' => isset($item->weight) ? $item->weight : null,
                        'item_price1' => isset($item->org_price) ? $item->org_price : null,
                        'item_price2' => isset($item->shop_price) ? $item->shop_price : null,
                        'item_price3' => isset($item->supply_price) ? $item->supply_price : null,
                        'item_url' => isset($item->img_500) ? $item->img_500 : null,
                        'item_service_name' => '수입풀필먼트',
                    ]
                );

                if ($item_no_all->item_no) {
                    if (!empty($item->options)) {
                        $item_arr = $item->options;
                        if (is_array($item_arr) || is_object($item_arr)) {
                            foreach ($item_arr as $option) {
                                if (is_array($option) || is_object($option)) {
                                    $option = (array)$option;
                                    if (!empty($option['product_id'])) {
                                        $item_no = Item::updateOrCreate(
                                            [
                                                'product_id' => $item->product_id,
                                                'option_id' => $option['product_id'],
                                            ],
                                            [
                                                'product_id' => $item->product_id,
                                                'option_id' => $option['product_id'],
                                                'mb_no' => 0,
                                                'co_no' => 0,
                                                'item_name' => $item->name,
                                                'supply_code' => $item->supply_code,
                                                'item_brand' => $item->brand,
                                                'item_origin' => $item->origin,
                                                'item_weight' => $item->weight,
                                                'item_price1' => $item->org_price,
                                                'item_price2' => $item->shop_price,
                                                'item_price3' => $item->supply_price,
                                                'item_url' => $item->img_500,
                                                'item_option1' => $option['options'],
                                                'item_service_name' => '수입풀필먼트'
                                            ]
                                        );
                                        $item_info_no = ItemInfo::updateOrCreate(
                                            [
                                                'item_no' => $item_no->item_no,
                                            ],
                                            [
                                                'item_no' => $item_no->item_no,
                                                'product_id' => $item->product_id,
                                                'supply_code' => $item->supply_code,
                                                'trans_fee' => $item->trans_fee,
                                                'img_desc1' => $item->img_desc1,
                                                'img_desc2' => $item->img_desc2,
                                                'img_desc3' => $item->img_desc3,
                                                'img_desc4' => $item->img_desc4,
                                                'img_desc5' => $item->img_desc5,
                                                'product_desc' => $item->product_desc,
                                                'product_desc2' => $item->product_desc2,
                                                'location' => $item->location,
                                                'memo' => $item->memo,
                                                'category' => $item->category,
                                                'maker' => $item->maker,
                                                'md' => $item->md,
                                                'manager1' => $item->manager1,
                                                'manager2' => $item->manager2,
                                                'supply_options' => !empty($option['supply_options']) ? $option['supply_options'] : '',
                                                'enable_sale' => !empty($option['enable_sale']) ? $option['enable_sale'] : 1,
                                                'use_temp_soldout' => !empty($option['use_temp_soldout']) ? $option['use_temp_soldout'] : 0,
                                                'stock_alarm1' => !empty($option['stock_alarm1']) ? $option['stock_alarm1'] : 0,
                                                'stock_alarm2' => !empty($option['stock_alarm2']) ? $option['stock_alarm2'] : 0,
                                                'extra_price' => !empty($option['extra_price']) ? $option['extra_price'] : 0,
                                                'extra_shop_price' => !empty($option['extra_shop_price']) ? $option['extra_shop_price'] : 0,
                                                'extra_column6' => !empty($option['extra_column6']) ? $option['extra_column6'] : '',
                                                'extra_column7' => !empty($option['extra_column7']) ? $option['extra_column7'] : '',
                                                'extra_column8' => !empty($option['extra_column8']) ? $option['extra_column8'] : '',
                                                'extra_column9' => !empty($option['extra_column9']) ? $option['extra_column9'] : '',
                                                'extra_column10' => !empty($option['extra_column10']) ? $option['extra_column10'] : '',
                                                'reg_date' => !empty($option['reg_date']) ? $option['reg_date'] : null,
                                                'last_update_date' => !empty($option['last_update_date']) ? $option['last_update_date'] : null,
                                                'new_link_id' => !empty($option['new_link_id']) ? $option['new_link_id'] : '',
                                                'link_id' => !empty($option['link_id']) ? $option['link_id'] : '',
                                            ]
                                        );
                                    }
                                }
                            }
                        } else {

                            $item_no = Item::updateOrCreate(
                                [
                                    'product_id' => $item->product_id
                                ],
                                [
                                    'product_id' => $item->product_id,
                                    'mb_no' => 0,
                                    'co_no' => 0,
                                    'item_name' => $item->name,
                                    'supply_code' => $item->supply_code,
                                    'item_brand' => $item->brand,
                                    'item_origin' => $item->origin,
                                    'item_weight' => $item->weight,
                                    'item_price1' => $item->org_price,
                                    'item_price2' => $item->shop_price,
                                    'item_price3' => $item->supply_price,
                                    'item_url' => $item->img_500,
                                    'item_option1' => $item->options ? $item->options : '',
                                    'item_service_name' => '수입풀필먼트'
                                ]
                            );



                            $item_info_no = ItemInfo::updateOrCreate(
                                [
                                    'item_no' => $item_no->item_no,
                                ],
                                [
                                    'item_no' => $item_no->item_no,
                                    'product_id' => $item->product_id,
                                    'supply_code' => $item->supply_code,
                                    'trans_fee' => $item->trans_fee,
                                    'img_desc1' => $item->img_desc1,
                                    'img_desc2' => $item->img_desc2,
                                    'img_desc3' => $item->img_desc3,
                                    'img_desc4' => $item->img_desc4,
                                    'img_desc5' => $item->img_desc5,
                                    'product_desc' => $item->product_desc,
                                    'product_desc2' => $item->product_desc2,
                                    'location' => $item->location,
                                    'memo' => $item->memo,
                                    'category' => $item->category,
                                    'maker' => $item->maker,
                                    'md' => $item->md,
                                    'manager1' => $item->manager1,
                                    'manager2' => $item->manager2
                                ]
                            );
                        }
                    }


                    $item_info_no = ItemInfo::updateOrCreate(
                        [
                            'item_no' => $item_no_all->item_no,
                        ],
                        [
                            'item_no' => $item_no_all->item_no,
                            'product_id' => isset($item->product_id) ? $item->product_id : null,
                            'supply_code' => isset($item->supply_code) ? $item->supply_code : null,
                            'trans_fee' => isset($item->trans_fee) ? $item->trans_fee : null,
                            'img_desc1' => isset($item->img_desc1) ? $item->img_desc1 : null,
                            'img_desc2' => isset($item->img_desc2) ? $item->img_desc2 : null,
                            'img_desc3' => isset($item->img_desc3) ? $item->img_desc3 : null,
                            'img_desc4' => isset($item->img_desc4) ? $item->img_desc4 : null,
                            'img_desc5' => isset($item->img_desc5) ? $item->img_desc5 : null,
                            'product_desc' => isset($item->product_desc) ? $item->product_desc : null,
                            'product_desc2' => isset($item->product_desc2) ? $item->product_desc2 : null,
                            'location' => isset($item->location) ? $item->location : null,
                            'memo' => isset($item->memo) ? $item->memo : null,
                            'category' => isset($item->category) ? $item->category : null,
                            'maker' => isset($item->maker) ? $item->maker : null,
                            'md' => isset($item->md) ? $item->md : null,
                            'manager1' => isset($item->manager1) ? $item->manager1 : null,
                            'manager2' => isset($item->manager2) ? $item->manager2 : null,
                            'extra_column1' => isset($item->extra_column1) ? $item->extra_column1 : null,
                            'extra_column2' => isset($item->extra_column2) ? $item->extra_column2 : null,
                            'extra_column3' => isset($item->extra_column3) ? $item->extra_column3 : null,
                            'extra_column4' => isset($item->extra_column4) ? $item->extra_column4 : null,
                            'extra_column5' => isset($item->extra_column5) ? $item->extra_column5 : null
                        ]
                    );
                }
            }


            DB::commit();
            return response()->json([
                'message' => '완료되었습니다.',
                'status' => 1,
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json([
                'message' => '오류가 발생하였습니다.',
            ], 500);
        }
    }

    public function apiItemsRawNoLogin($request = null)
    {
        try {
            DB::beginTransaction();
            $data_select = !empty($request->data) ? $request->data : array();
            foreach ($data_select as $i_item => $item) {
                $item_no = Item::updateOrCreate(
                    [
                        'product_id' => $item->product_id
                    ],
                    [
                        'mb_no' => 0,
                        'co_no' => 0,
                        'item_name' => $item->name,
                        'supply_code' => $item->supply_code,
                        'item_brand' => $item->brand,
                        'item_origin' => $item->origin,
                        'item_weight' => $item->weight,
                        'item_price1' => $item->org_price,
                        'item_price2' => $item->shop_price,
                        'item_price3' => $item->supply_price,
                        'item_url' => $item->img_500,
                        'item_service_name' => '수입풀필먼트',
                    ]
                );
                if ($item_no->item_no) {
                    if (!empty($item->options)) {
                        $item_arr = (array)$item->options;
                        if (is_array($item_arr) || is_object($item_arr)) {
                            foreach ($item_arr as $option) {
                                $item_no = Item::updateOrCreate(
                                    [
                                        'product_id' => $item->product_id,
                                        'option_id' => isset($option->product_id) ? $option->product_id : ''
                                    ],
                                    [
                                        'product_id' => $item->product_id,
                                        'option_id' => isset($option->product_id) ? $option->product_id : '',
                                        'mb_no' => 0,
                                        'co_no' => 0,
                                        'item_name' => $item->name,
                                        'supply_code' => $item->supply_code,
                                        'item_brand' => $item->brand,
                                        'item_origin' => $item->origin,
                                        'item_weight' => $item->weight,
                                        'item_price1' => $item->org_price,
                                        'item_price2' => $item->shop_price,
                                        'item_price3' => $item->supply_price,
                                        'item_url' => $item->img_500,
                                        'item_option1' => isset($option->options) ? $option->options : '',
                                        'item_service_name' => '수입풀필먼트'
                                    ]
                                );
                                $item_info_no = ItemInfo::updateOrCreate(
                                    [
                                        'item_no' => $item_no->item_no,
                                    ],
                                    [
                                        'item_no' => $item_no->item_no,
                                        'product_id' => $item->product_id,
                                        'supply_code' => $item->supply_code,
                                        'trans_fee' => $item->trans_fee,
                                        'img_desc1' => $item->img_desc1,
                                        'img_desc2' => $item->img_desc2,
                                        'img_desc3' => $item->img_desc3,
                                        'img_desc4' => $item->img_desc4,
                                        'img_desc5' => $item->img_desc5,
                                        'product_desc' => $item->product_desc,
                                        'product_desc2' => $item->product_desc2,
                                        'location' => $item->location,
                                        'memo' => $item->memo,
                                        'category' => $item->category,
                                        'maker' => $item->maker,
                                        'md' => $item->md,
                                        'manager1' => $item->manager1,
                                        'manager2' => $item->manager2,
                                        'supply_options' => !empty($option->supply_options) ? $option->supply_options : '',
                                        'enable_sale' => !empty($option->enable_sale) ? $option->enable_sale : 1,
                                        'use_temp_soldout' => !empty($option->use_temp_soldout) ? $option->use_temp_soldout : 0,
                                        'stock_alarm1' => !empty($option->stock_alarm1) ? $option->stock_alarm1 : 0,
                                        'stock_alarm2' => !empty($option->stock_alarm2) ? $option->stock_alarm2 : 0,
                                        'extra_price' => !empty($option->extra_price) ? $option->extra_price : 0,
                                        'extra_shop_price' => !empty($option->extra_shop_price) ? $option->extra_shop_price : 0,
                                        'extra_column6' => !empty($option->extra_column6) ? $option->extra_column6 : '',
                                        'extra_column7' => !empty($option->extra_column7) ? $option->extra_column7 : '',
                                        'extra_column8' => !empty($option->extra_column8) ? $option->extra_column8 : '',
                                        'extra_column9' => !empty($option->extra_column9) ? $option->extra_column9 : '',
                                        'extra_column10' => !empty($option->extra_column10) ? $option->extra_column10 : '',
                                        'reg_date' => !empty($option->reg_date) ? $option->reg_date : null,
                                        'last_update_date' => !empty($option->last_update_date) ? $option->last_update_date : null,
                                        'new_link_id' => !empty($option->new_link_id) ? $option->new_link_id : '',
                                        'link_id' => !empty($option->link_id) ? $option->link_id : '',
                                    ]
                                );
                            }
                        } else {
                            $item_no = Item::updateOrCreate(
                                [
                                    'product_id' => $item->product_id
                                ],
                                [
                                    'product_id' => $item->product_id,
                                    'mb_no' => 0,
                                    'co_no' => 0,
                                    'item_name' => $item->name,
                                    'supply_code' => $item->supply_code,
                                    'item_brand' => $item->brand,
                                    'item_origin' => $item->origin,
                                    'item_weight' => $item->weight,
                                    'item_price1' => $item->org_price,
                                    'item_price2' => $item->shop_price,
                                    'item_price3' => $item->supply_price,
                                    'item_url' => $item->img_500,
                                    'item_option1' => $item->options ? $item->options : '',
                                    'item_service_name' => '수입풀필먼트'
                                ]
                            );

                            $item_info_no = ItemInfo::updateOrCreate(
                                [
                                    'item_no' => $item_no->item_no,
                                ],
                                [
                                    'item_no' => $item_no->item_no,
                                    'product_id' => $item->product_id,
                                    'supply_code' => $item->supply_code,
                                    'trans_fee' => $item->trans_fee,
                                    'img_desc1' => $item->img_desc1,
                                    'img_desc2' => $item->img_desc2,
                                    'img_desc3' => $item->img_desc3,
                                    'img_desc4' => $item->img_desc4,
                                    'img_desc5' => $item->img_desc5,
                                    'product_desc' => $item->product_desc,
                                    'product_desc2' => $item->product_desc2,
                                    'location' => $item->location,
                                    'memo' => $item->memo,
                                    'category' => $item->category,
                                    'maker' => $item->maker,
                                    'md' => $item->md,
                                    'manager1' => $item->manager1,
                                    'manager2' => $item->manager2
                                ]
                            );
                        }
                    } else {
                        $item_info_no = ItemInfo::updateOrCreate(
                            [
                                'item_no' => $item_no->item_no,
                            ],
                            [
                                'item_no' => $item_no->item_no,
                                'product_id' => $item->product_id,
                                'supply_code' => $item->supply_code,
                                'trans_fee' => $item->trans_fee,
                                'img_desc1' => $item->img_desc1,
                                'img_desc2' => $item->img_desc2,
                                'img_desc3' => $item->img_desc3,
                                'img_desc4' => $item->img_desc4,
                                'img_desc5' => $item->img_desc5,
                                'product_desc' => $item->product_desc,
                                'product_desc2' => $item->product_desc2,
                                'location' => $item->location,
                                'memo' => $item->memo,
                                'category' => $item->category,
                                'maker' => $item->maker,
                                'md' => $item->md,
                                'manager1' => $item->manager1,
                                'manager2' => $item->manager2,

                                'extra_column1' => $item->extra_column1,
                                'extra_column2' => $item->extra_column2,
                                'extra_column3' => $item->extra_column3,
                                'extra_column4' => $item->extra_column4,
                                'extra_column5' => $item->extra_column5
                            ]
                        );
                    }
                }
            }


            DB::commit();
            return response()->json([
                'message' => '완료되었습니다.',
                'status' => 1,
                'data' => $data_select
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json([
                'message' => '오류가 발생하였습니다.',
            ], 500);
        }
    }

    public function apiItemsCargoList()
    {
        set_time_limit(180);
        try {

            DB::beginTransaction();

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 't_import_expected.update_api_time', 't_import_expected.tie_h_bl', 't_import_expected.tie_co_license')

                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number', 'ti_i_confirm_number')

                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);


            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')

                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })

                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {


                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })->orderBy('update_api_time', 'ASC');

            //$import_schedule->whereNull('ddd.te_logistic_manage_number');
            $import_schedule = $import_schedule->offset(0)->limit(30)->get();
            //$this->createBondedSettlement();

            //return $import_schedule;
            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");



            foreach ($import_schedule as $value) {
                if (isset($value->tie_logistic_manage_number)) {
                    ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                        ->update([
                            'update_api_time' => Carbon::now(),
                        ]);
                    $logistic_manage_number = $value->tie_logistic_manage_number; //'23KE0EA1FII00100007';//
                    $logistic_manage_number = str_replace('-', '', $logistic_manage_number);
                    $url = "https://unipass.customs.go.kr:38010/ext/rest/cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo?crkyCn=s230z262h044b104n070k070a3&cargMtNo=" . $logistic_manage_number . "";
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return data inplace of echoing on screen
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

                    //$xmlString = simplexml_load_string(file_get_contents($url));
                    $data = curl_exec($ch);
                    if ($data === false) {
                        $result = curl_error($ch);
                    } else {
                        $result = $data;
                    }
                    curl_close($ch);
                    $result = simplexml_load_string($result);
                    $json = json_encode($result);
                    $array = json_decode($json, TRUE);
                    //return $array;
                    if (isset($array['cargCsclPrgsInfoDtlQryVo']) && $array['cargCsclPrgsInfoDtlQryVo']) {
                        $data_apis = $array['cargCsclPrgsInfoDtlQryVo'];
                        $status = '';
                        foreach ($data_apis as $key => $data) {
                            $status = isset($data['cargTrcnRelaBsopTpcd']) ? $data['cargTrcnRelaBsopTpcd'] : '';
                            $status1 = '';
                            // if($status == '수입신고'){
                            //     return $key;
                            // }
                            if ($status == '수입신고 수리후 정정 완료') {
                                $status1 = '수입신고정정완료';
                                if (isset($value->tie_logistic_manage_number)) {
                                    ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                        ->update([
                                            'tie_status_2' => $status1,
                                            'update_api_time' => Carbon::now(),
                                        ]);
                                }

                                if (isset($value->ti_logistic_manage_number) && isset($value->ti_i_confirm_number)) {
                                    Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                        ->update([
                                            'ti_status_2' => $status1
                                        ]);
                                }

                                if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                    Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                        ->update([
                                            'te_status_2' => $status1
                                        ]);
                                }

                                $check_alarm = Alarm::with(['alarm_data'])->where('alarm_h_bl', $value->tie_h_bl)->whereHas('alarm_data', function ($query) {
                                    $query->where(DB::raw('lower(ad_title)'), 'like', '' . strtolower('[보세화물] 수입신고정정신고완료') . '');
                                })->first();

                                if ($check_alarm == null) {
                                    CommonFunc::insert_alarm_cargo_api_status2_service1('[보세화물] 수입신고정정신고완료', null, null, $value, 'cargo_api_status2');
                                }

                                break;
                            } else if ($status == '수입신고 수리후 정정 접수') {

                                $status1 = '수입신고정정접수';
                                if (isset($value->tie_logistic_manage_number)) {
                                    ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                        ->update([
                                            'tie_status_2' => $status1,
                                            'update_api_time' => Carbon::now(),
                                        ]);
                                }

                                if (isset($value->ti_logistic_manage_number) && isset($value->ti_i_confirm_number)) {
                                    Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                        ->update([
                                            'ti_status_2' => $status1
                                        ]);
                                }

                                if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                    Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                        ->update([
                                            'te_status_2' => $status1
                                        ]);
                                }

                                $check_alarm = Alarm::with(['alarm_data'])->where('alarm_h_bl', $value->tie_h_bl)->whereHas('alarm_data', function ($query) {
                                    $query->where(DB::raw('lower(ad_title)'), 'like', '' . strtolower('[보세화물] 수입신고정정접수') . '');
                                })->first();

                                if ($check_alarm == null) {
                                    CommonFunc::insert_alarm_cargo_api_status2_service1('[보세화물] 수입신고정정접수', null, null, $value, 'cargo_api_status2');
                                }

                                break;
                            } else if ($status == '수입신고수리') {

                                $status1 = '수입신고수리';

                                if (isset($value->tie_logistic_manage_number)) {
                                    ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                        ->update([
                                            'tie_status_2' => $status1,
                                            'update_api_time' => Carbon::now(),
                                        ]);
                                }

                                if (isset($value->ti_logistic_manage_number) && isset($value->ti_i_confirm_number)) {
                                    Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                        ->update([
                                            'ti_status_2' => $status1
                                        ]);
                                }

                                if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                    Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                        ->update([
                                            'te_status_2' => $status1
                                        ]);
                                }

                                $check_alarm = Alarm::with(['alarm_data'])->where('alarm_h_bl', $value->tie_h_bl)->whereHas('alarm_data', function ($query) {
                                    $query->where(DB::raw('lower(ad_title)'), 'like', '' . strtolower('[보세화물] 수입신고수리완료') . '');
                                })->first();

                                if ($check_alarm == null) {
                                    CommonFunc::insert_alarm_cargo_api_status2_service1('[보세화물] 수입신고수리완료', null, null, $value, 'cargo_api_status2');
                                }

                                break;
                            } else if ($status == '수입신고') {

                                $status1 = '수입신고접수';
                                if (isset($value->tie_logistic_manage_number)) {
                                    ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                        ->update([
                                            'tie_status_2' => $status1,
                                            'update_api_time' => Carbon::now(),
                                        ]);
                                }

                                if (isset($value->ti_logistic_manage_number) && isset($value->ti_i_confirm_number)) {
                                    Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                        ->update([
                                            'ti_status_2' => $status1
                                        ]);
                                }

                                if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                    Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                        ->update([
                                            'te_status_2' => $status1
                                        ]);
                                }

                                $check_alarm = Alarm::with(['alarm_data'])->where('alarm_h_bl', $value->tie_h_bl)->whereHas('alarm_data', function ($query) {
                                    $query->where(DB::raw('lower(ad_title)'), 'like', '' . strtolower('[보세화물] 수입신고접수') . '');
                                })->first();

                                if ($check_alarm == null) {
                                    CommonFunc::insert_alarm_cargo_api_status2_service1('[보세화물] 수입신고접수', null, null, $value, 'cargo_api_status2');
                                }

                                break;
                            }
                        }



                        //return $array;
                        // if ($status1 != null) {
                        //     break;
                        // }

                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function apiItemsCargoList_bk()
    {

        try {

            DB::beginTransaction();

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            // $user = Auth::user();
            // if ($user->mb_type == 'shop') {

            //     $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 't_import_expected.update_api_time', 't_import_expected.tie_h_bl')
            //         ->leftjoin('company', function ($join) {
            //             $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
            //         })->leftjoin('company as parent_shop', function ($join) {
            //             $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
            //         })->leftjoin('company as parent_spasys', function ($join) {
            //             $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
            //         })->where('parent_shop.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
            //         ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
            //         ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            //     $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
            //         ->leftjoin('receiving_goods_delivery', function ($join) {
            //             $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
            //         })
            //         ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            //     // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            //     $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
            //         // ->leftjoin('receiving_goods_delivery', function ($join) {
            //         //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
            //         // })
            //         ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            //     $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
            //         $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            //     })
            //     // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
            //     //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
            //     // })
            //     ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

            //         //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
            //         $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            //     })->orderBy('update_api_time', 'ASC');
            // } else if ($user->mb_type == 'shipper') {


            //     $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 't_import_expected.update_api_time', 't_import_expected.tie_h_bl')
            //         ->leftjoin('company', function ($join) {
            //             $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
            //         })->leftjoin('company as parent_shop', function ($join) {
            //             $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
            //         })->leftjoin('company as parent_spasys', function ($join) {
            //             $join->on('parent_shop.co_parent_no', '=', 'parent_spasys.co_no');
            //         })->where('company.co_no', $user->co_no)->where('tie_is_date', '>=', '2022-01-04')
            //         ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
            //         ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            //     $sub_2 = Import::select('receiving_goods_delivery.rgd_address', 'receiving_goods_delivery.rgd_status1', 'ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
            //         ->leftjoin('receiving_goods_delivery', function ($join) {
            //             $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
            //         })
            //         ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            //     // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            //     $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
            //         // ->leftjoin('receiving_goods_delivery', function ($join) {
            //         //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
            //         // })
            //         ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);


            //     $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
            //         $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            //     })
            //     // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
            //     //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
            //     // })
            //     ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

            //         //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
            //         $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
            //     })->orderBy('update_api_time', 'ASC');
            // } else if ($user->mb_type == 'spasys') {


            $sub = ImportExpected::select('t_import_expected.tie_logistic_manage_number', 't_import_expected.update_api_time', 't_import_expected.tie_h_bl')
                // ->leftjoin('company as parent_spasys', function ($join) {
                //     $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
                // })
                // ->leftjoin('company', function ($join) {
                //     $join->on('company.co_license', '=', 't_import_expected.tie_co_license');
                // })->leftjoin('company as parent_shop', function ($join) {
                //     $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
                // })
                // ->where('parent_spasys.warehouse_code', $user->company['warehouse_code'])
                ->where('tie_is_date', '>=', '2022-01-04')
                ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_logistic_manage_number', 'ti_carry_in_number', 'ti_i_confirm_number')
                ->leftjoin('receiving_goods_delivery', function ($join) {
                    $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('te_logistic_manage_number', 'te_carry_in_number', 'te_carry_out_number')
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
                })->orderBy('update_api_time', 'ASC');
            //}
            //$import_schedule->whereNull('ddd.te_logistic_manage_number');
            $import_schedule = $import_schedule->offset(0)->limit(20)->get();
            //$this->createBondedSettlement();

            //return $import_schedule;
            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");



            foreach ($import_schedule as $value) {
                if (isset($value->tie_logistic_manage_number)) {
                    $logistic_manage_number = $value->tie_logistic_manage_number; //'23KE0EA1FII00100007';//
                    $logistic_manage_number = str_replace('-', '', $logistic_manage_number);

                    $xmlString = simplexml_load_string(file_get_contents("https://unipass.customs.go.kr:38010/ext/rest/cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo?crkyCn=s230z262h044b104n070k070a3&cargMtNo=" . $logistic_manage_number . ""));

                    $json = json_encode($xmlString);
                    $array = json_decode($json, TRUE);

                    if (isset($array['cargCsclPrgsInfoDtlQryVo']) && $array['cargCsclPrgsInfoDtlQryVo']) {
                        $data_apis = $array['cargCsclPrgsInfoDtlQryVo'];
                        foreach ($data_apis as $data) {
                            $status = isset($data['cargTrcnRelaBsopTpcd']) ? $data['cargTrcnRelaBsopTpcd'] : '';
                            $status1 = '';
                            switch ($status) {
                                case '수입신고 수리후 정정 완료':
                                    $status1 = '수입신고정정완료';
                                    if (isset($value->tie_logistic_manage_number)) {
                                        ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                            ->update([
                                                'tie_status_2' => $status1,
                                                'update_api_time' => Carbon::now(),
                                            ]);
                                    }

                                    if (isset($value->ti_i_confirm_number) && isset($value->ti_i_confirm_number)) {
                                        Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                            ->update([
                                                'ti_status_2' => $status1
                                            ]);
                                    }

                                    if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                        Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                            ->update([
                                                'te_status_2' => $status1
                                            ]);
                                    }
                                    break;
                                case '수입신고 수리후 정정 접수':
                                    $status1 = '수입신고정정접수';
                                    if (isset($value->tie_logistic_manage_number)) {
                                        ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                            ->update([
                                                'tie_status_2' => $status1,
                                                'update_api_time' => Carbon::now(),
                                            ]);
                                    }

                                    if (isset($value->ti_i_confirm_number) && isset($value->ti_i_confirm_number)) {
                                        Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                            ->update([
                                                'ti_status_2' => $status1
                                            ]);
                                    }

                                    if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                        Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                            ->update([
                                                'te_status_2' => $status1
                                            ]);
                                    }
                                    break;
                                case '수입신고수리':
                                    $status1 = '수입신고수리';
                                    if (isset($value->tie_logistic_manage_number)) {
                                        ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                            ->update([
                                                'tie_status_2' => $status1,
                                                'update_api_time' => Carbon::now(),
                                            ]);
                                    }

                                    if (isset($value->ti_i_confirm_number) && isset($value->ti_i_confirm_number)) {
                                        Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                            ->update([
                                                'ti_status_2' => $status1
                                            ]);
                                    }

                                    if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                        Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                            ->update([
                                                'te_status_2' => $status1
                                            ]);
                                    }
                                    break;
                                case '수입신고':
                                    $status1 = '수입신고접수';
                                    if (isset($value->tie_logistic_manage_number)) {
                                        ImportExpected::where('tie_logistic_manage_number', $value->tie_logistic_manage_number)
                                            ->update([
                                                'tie_status_2' => $status1,
                                                'update_api_time' => Carbon::now(),
                                            ]);
                                    }

                                    if (isset($value->ti_i_confirm_number) && isset($value->ti_i_confirm_number)) {
                                        Import::where('ti_logistic_manage_number', $value->ti_logistic_manage_number)->where('ti_i_confirm_number', $value->ti_i_confirm_number)
                                            ->update([
                                                'ti_status_2' => $status1
                                            ]);
                                    }

                                    if (isset($value->te_logistic_manage_number) && isset($value->te_carry_out_number)) {
                                        Export::where('te_logistic_manage_number', $value->te_logistic_manage_number)->where('te_carry_out_number', $value->te_carry_out_number)
                                            ->update([
                                                'te_status_2' => $status1
                                            ]);
                                    }
                                    break;
                                default:
                                    $status1 = null;
                                    break;
                            }



                            //return $array;
                            // if ($status1 != null) {
                            //     break;
                            // }
                        }
                    }
                }
            }



            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }

    public function apiupdateStockItems(Request $request)
    {
        //return $request;
        //$validated = $request->validated();
        try {
            DB::beginTransaction();
            foreach ($request->data as $i_item => $item) {
                $item_info_no = ItemInfo::where('product_id', $item['product_id'])
                    ->update([
                        'stock' => $item['stock']
                    ]);
            }
            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,

            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function caculateItem(ItemSearchRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::enableQueryLog();

            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::select(DB::raw('SUM(warehousing_item.wi_number) as total'))
                    ->with(['company'])
                    ->leftJoin('warehousing_item', 'item.item_no', '=', 'warehousing_item.item_no')
                    ->where('item.item_service_name', '=', '유통가공')
                    ->get();
            }
            //return DB::getQueryLog();


            return response()->json($item);
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function apiItemCron(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_product_info',
            'date_type' => 'last_update_date',
            'start_date' => '2022-05-05',
            'end_date' => date('Y-m-d'),
            'limit' => 100,
            'page' => 1
        );
        $filter = array();
        $url_api = 'https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?';
        foreach ($param_arrays as $key => $param) {
            $filter[$key] = !empty($request[$key]) ? $request[$key] : $param;
        }
        $url_api .= '&partner_key=' . $filter['partner_key'];
        $url_api .= '&domain_key=' . $filter['domain_key'];
        $url_api .= '&action=' . $filter['action'];
        $url_api .= '&date_type=' . $filter['date_type'];
        $url_api .= '&start_date=' . $filter['start_date'];
        $url_api .= '&end_date=' . $filter['end_date'];
        // if ($filter['limit'] != '') {
        //     $url_api .= '&limit=' . $filter['limit'];
        // }
        // if ($filter['page'] != '') {
        //     $url_api .= '&page=' . $filter['page'];
        // }
        if ($filter['limit'] != '') {
            $url_api .= '&limit=' . $filter['limit'];
        }

        $response = file_get_contents($url_api);
        $api_data = json_decode($response);

        $total_data = 0;
        $pages = 0;
        if (isset($api_data->total) && $api_data->total) {
            $total_data = $api_data->total;
            $pages = ceil($total_data / 100);

            for ($i = 1; $i <= $pages; $i++) {


                $url_api1 = '&page=' . $i;

                $response = file_get_contents($url_api . $url_api1);
                $api_data = json_decode($response);

                $this->apiItemsRaw($api_data, $url_api . $url_api1);
            }
        }
        // } else {
        //     if (!empty($api_data->data)) {
        //         if ($filter['limit'] != '') {
        //             $url_api .= '&limit=' . $filter['limit'];
        //         }
        //         if ($filter['page'] != '') {
        //             $url_api .= '&page=' . $filter['page'];
        //         }
        //         $response = file_get_contents($url_api);
        //         $api_data = json_decode($response);
        //         return $this->apiItemsRaw($api_data, $url_api);
        //     } else {
        //         return response()->json([
        //             'param' => $url_api,
        //             'message' => '완료되었습니다.',
        //             'status' => 1
        //         ], 200);
        //     }
        // }
        return response()->json([
            'message' => '완료되었습니다.',
            'status' => 1
        ], 200);
    }



    public function apiItemCronNoLogin(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_product_info',
            'date_type' => 'last_update_date',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'limit' => 50,
            'page' => ''
        );
        $filter = array();
        $url_api = 'https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?';
        foreach ($param_arrays as $key => $param) {
            $filter[$key] = !empty($request[$key]) ? $request[$key] : $param;
        }
        $url_api .= '&partner_key=' . $filter['partner_key'];
        $url_api .= '&domain_key=' . $filter['domain_key'];
        $url_api .= '&action=' . $filter['action'];
        $url_api .= '&date_type=' . $filter['date_type'];
        $url_api .= '&start_date=' . $filter['start_date'];
        $url_api .= '&end_date=' . $filter['end_date'];
        if ($filter['limit'] != '') {
            $url_api .= '&limit=' . $filter['limit'];
        }
        if ($filter['page'] != '') {
            $url_api .= '&page=' . $filter['page'];
        }
        $response = file_get_contents($url_api);
        $api_data = json_decode($response);
        if (!empty($api_data->data)) {
            return $this->apiItemsRawNoLogin($api_data);
        } else {
            return response()->json([
                'message' => '완료되었습니다.',
                'status' => 1
            ], 200);
        }
        return $api_data;
    }

    public function updateStockItemsApi(Request $request)
    {
        try {

            $param_arrays = array(
                'partner_key' => '50e2331771d085ddccbcd2188a03800c',
                'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
                'action' => 'get_stock_info'
            );
            $filter = array();
            $url_api = 'https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?';
            foreach ($param_arrays as $key => $param) {
                $filter[$key] = !empty($request[$key]) ? $request[$key] : $param;
            }
            $url_api .= '&partner_key=' . $filter['partner_key'];
            $url_api .= '&domain_key=' . $filter['domain_key'];
            $url_api .= '&action=' . $filter['action'];
            $params = array();
            $list_items = $this->paginateItemsApiIdRaw()->toArray();
            foreach ($list_items as $item) {
                if (!empty($item)) {
                    if (!empty($item['option_id'])) {
                        $params[] = $item['option_id'];
                    } else {
                        $params[] = $item['product_id'];
                    }
                }
            }

            for ($bad_status = 0; $bad_status <= 1; $bad_status++) {
                $url_api .= '&bad=' . $bad_status;
                $url_api .= '&product_id=';
                if (!empty($params)) {
                    $unique_params = array_unique($params);
                    if (count($unique_params) > 100) {
                        $chunk_params = array_chunk($unique_params, 100);
                        foreach ($chunk_params as $param) {
                            $link_params = implode(',', $param);
                            $url_api .= $link_params;

                            $this->updateStockStatus($url_api);
                        }
                    } else {
                        $link_params = implode(',', $unique_params);
                        $url_api .= $link_params;

                        $this->updateStockStatus($url_api);
                    }
                } else {

                    $this->updateStockStatus($url_api);
                }
            }
            return response()->json([
                //'param' => $url_api,
                'message' => '완료되었습니다.',
                'status' => 1
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function updateStockItemsApiNoLogin(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_stock_info'
        );
        $filter = array();
        $url_api = 'https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?';
        foreach ($param_arrays as $key => $param) {
            $filter[$key] = !empty($request[$key]) ? $request[$key] : $param;
        }
        $url_api .= '&partner_key=' . $filter['partner_key'];
        $url_api .= '&domain_key=' . $filter['domain_key'];
        $url_api .= '&action=' . $filter['action'];
        $list_items = $this->paginateItemsApiIdRawNoLogin();
        $params = array();
        foreach ($list_items as $item) {
            if (!empty($item)) {
                if (!empty($item['option_id'])) {
                    $params[] = $item['option_id'];
                } else {
                    $params[] = $item['product_id'];
                }
            }
        }

        for ($bad = 0; $bad <= 1; $bad++) {
            // if (!empty($list_items)) {
            //     $url_api .= '&product_id=';
            //     foreach ($list_items as $key_item => $item) {
            //         if ($key_item > 0) {
            //             $url_api .= ',';
            //         }
            //         $url_api .= $item['product_id'];

            //         if ($key_item >= 50) {
            //             break;
            //         }
            //     }
            // }
            $url_api .= '&bad=' . $bad;
            $url_api .= '&product_id=';

            if (!empty($params)) {
                $unique_params = array_unique($params);

                if (count($unique_params) > 100) {
                    $chunk_params = array_chunk($unique_params, 100);
                    //return $chunk_params;
                    foreach ($chunk_params as $param) {
                        $link_params = implode(',', $param);
                        $url_api .= $link_params;
                        return $url_api;
                        $this->updateStockStatus($url_api);
                    }
                } else {
                    $link_params = implode(',', $unique_params);
                    $url_api .= $link_params;

                    $this->updateStockStatus($url_api);
                }
            } else {
                $this->updateStockStatus($url_api);
            }



            // $response = file_get_contents($url_api);
            // $api_data = json_decode($response);
            // if (!empty($api_data->data)) {
            //     foreach ($api_data->data as $item) {
            //         $item = (array)$item;
            //         $item_info = Item::where('product_id', $item['product_id'])->orWhere('option_id', $item['product_id'])->first();
            //         if ($item['stock'] == 0) { // Khong thuoc kho nao
            //             $stock = rand(10, 100);
            //             ItemInfo::updateOrCreate([
            //                 'product_id' => $item['product_id'],
            //                 'stock' => $stock, //$item['stock'],
            //                 'item_no' => $item_info->item_no,
            //             ], [
            //                 'product_id' => $item['product_id'],
            //                 'stock' => $stock, //$item['stock'],
            //                 'status' => $item['bad'],
            //                 'item_no' => $item_info->item_no,
            //             ]);
            //         }
            //         if ($item['stock'] > 0 && $item_info) {
            //             ItemInfo::where('product_id', $item_info->product_id)
            //             ->where('item_no', $item_info->item_no)
            //             ->update([
            //                 'product_id' => $item_info->product_id,
            //                 'stock' => $item['stock'],
            //                 'status' => $item['bad'],
            //                 'item_no' => $item_info->item_no
            //             ]);
            //         StockStatusBad::updateOrCreate([
            //             'product_id' => $item_info->product_id,
            //             'option_id' => !empty($item_info['option_id']) ? $item_info['option_id'] : '',
            //             'status' => $item['bad'],
            //         ], [
            //             'product_id' => $item_info['product_id'],
            //             'option_id' => !empty($item_info['option_id']) ? $item_info['option_id'] : '',
            //             'stock' => $item['stock'],
            //             'status' => $item['bad'],
            //             'item_no' => $item_info->item_no
            //         ]);
            //         }
            //     }
            // }
        }
        return response()->json([
            //'params' => $url_api,
            'message' => '완료되었습니다.',
            'status' => 1,
        ], 200);
    }

    public function updateStockStatus($url_api)
    {
        // $response = file_get_contents($url_api);
        // $api_data = json_decode($response);

        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $url_api);
        curl_setopt($con, CURLOPT_HEADER, 0);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, 1); // Return data inplace of echoing on screen
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($con, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $response = curl_exec($con);
        curl_close($con);
        $api_data = json_decode($response);

        if (!empty($api_data->data)) {
            foreach ($api_data->data as $item) {

                $item = (array)$item;
                $item_info = Item::where('product_id', $item['product_id'])->orWhere('option_id', $item['product_id'])->first();
                if ($item['stock'] > 0 && $item_info) {

                    ItemInfo::where('product_id', $item_info->product_id)
                        ->where('item_no', $item_info->item_no)
                        ->update([
                            'product_id' => $item_info->product_id,
                            'stock' => $item['stock'],
                            'status' => $item['bad'],
                            'item_no' => $item_info->item_no
                        ]);
                    StockStatusBad::updateOrCreate([
                        'product_id' => $item_info->product_id,
                        'option_id' => !empty($item_info['option_id']) ? $item_info['option_id'] : '',
                        'status' => $item['bad'],
                    ], [
                        'product_id' => $item_info['product_id'],
                        'option_id' => !empty($item_info['option_id']) ? $item_info['option_id'] : '',
                        'stock' => $item['stock'],
                        'status' => $item['bad'],
                        'item_no' => $item_info->item_no
                    ]);
                }
                if ($item['stock'] == 0) { // Khong thuoc kho nao
                    $stock = rand(10, 100);
                    ItemInfo::updateOrCreate([
                        'product_id' => $item['product_id'],
                        'stock' => $stock, //$item['stock'],
                        'item_no' => $item_info->item_no,
                    ], [
                        'product_id' => $item['product_id'],
                        'stock' => $stock, //$item['stock'],
                        'status' => $item['bad'],
                        'item_no' => $item_info->item_no,
                    ]);
                }
            }
        }
    }

    public function updateStockStatusCompany($url_api,$shipper)
    {
        // $response = file_get_contents($url_api);
        // $api_data = json_decode($response);

        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $url_api);
        curl_setopt($con, CURLOPT_HEADER, 0);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, 1); // Return data inplace of echoing on screen
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($con, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $response = curl_exec($con);
        curl_close($con);
        $api_data = json_decode($response, true);
        $total_stock = 0;
        if (!empty($api_data['data'])) {
            foreach ($api_data['data'] as $item) {
                if ($item['stock'] > 0) {
                    $total_stock += $item['stock'];
                }
            }
        }

        $today = StockStatusCompany::where('created_at', 'LIKE', Carbon::now()->format('Y-m-d'). '%')->where('co_no', $shipper['co_no'])->first();


        if(isset($today->ssc_id)){
            StockStatusCompany::where('created_at', 'LIKE', Carbon::now()
            ->format('Y-m-d H:i'). '%')
            ->where('co_no', $shipper['co_no'])
            ->update([
                'stock' => $total_stock + $today->stock
            ]);
        } else {
            StockStatusCompany::insert(
                [
                    'co_no' => $shipper['co_no'],
                    'stock' => $total_stock,
                ]
            );
        }


    }

    public function updateStockCompanyApiNoLogin(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_stock_info'
        );
        $filter = array();
        $url_api = 'https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?';
        foreach ($param_arrays as $key => $param) {
            $filter[$key] = !empty($request[$key]) ? $request[$key] : $param;
        }
        $url_api .= '&partner_key=' . $filter['partner_key'];
        $url_api .= '&domain_key=' . $filter['domain_key'];
        $url_api .= '&action=' . $filter['action'];

        $company_shipper = Company::with(['ContractWms'])->where("co_type", "shipper")->get();
        //return $company_shipper;
        foreach ($company_shipper as $shipper) {

            $cw_code = [];

            if(isset($shipper->ContractWms)){
                foreach($shipper->ContractWms as $contract_wms){
                    $cw_code[] = $contract_wms['cw_code'];
                }
            }

            $list_items = $this->paginateItemsApiIdCompanyRawNoLogin($cw_code)->toArray();

            $params = array();
            foreach ($list_items as $item) {
                if (!empty($item)) {
                    if (!empty($item['option_id'])) {
                        $params[] = $item['option_id'];
                    } else {
                        $params[] = $item['product_id'];
                    }
                }
            }

            for ($bad = 0; $bad <= 1; $bad++) {
                $url_api .= '&bad=' . $bad;
                $url_api .= '&product_id=';

                if (!empty($params)) {
                    $unique_params = array_unique($params);

                    if (count($unique_params) > 100) {
                        $chunk_params = array_chunk($unique_params, 100);
                        return $chunk_params;
                        foreach ($chunk_params as $param) {
                            $link_params = implode(',', $param);
                            $url_api .= $link_params;

                            $this->updateStockStatusCompany($url_api,$shipper);
                        }
                    } else {
                        $link_params = implode(',', $unique_params);
                        $url_api .= $link_params;
                        //return $url_api;
                        $this->updateStockStatusCompany($url_api,$shipper);
                    }
                } else {

                    $this->updateStockStatusCompany($url_api,$shipper);
                }
            }
        }


        return response()->json([
            'params' => $url_api,
            'message' => '완료되었습니다.',
            'status' => 1,
        ], 200);
    }

    //THUONG EDIT TO MAKE SETTLEMENT
    public function createBondedSettlement()
    {
        try {

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");


            //FIX NOT WORK 'with'
            $sub = ImportExpected::select('company.co_type', 't_import_expected.tie_status_2 as import_expected', 'parent_spasys.co_name as co_name_spasys', 'parent_spasys.co_no as co_no_spasys', 'parent_shop.co_name as co_name_shop', 'parent_shop.co_no as co_no_shop', 'company.co_no', 'company.co_name', 't_import_expected.*')
            ->leftjoin('company as parent_spasys', function ($join) {
                $join->on('parent_spasys.warehouse_code', '=', 't_import_expected.tie_warehouse_code');
            })
           ->leftjoin('company', function ($join) {
                $join->on('company.co_license', '=', 't_import_expected.tie_co_license')
                ->where('company.co_type', '=', "shipper");
            })->leftjoin('company as parent_shop', function ($join) {
                $join->on('company.co_parent_no', '=', 'parent_shop.co_no');
            })
            ->where('tie_is_date', '>=', '2022-01-04')
            ->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
            ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number']);

            $sub_2 = Import::select('ti_status_2', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number')
                // ->leftjoin('receiving_goods_delivery', function ($join) {
                //     $join->on('t_import.ti_carry_in_number', '=', 'receiving_goods_delivery.is_no');
                // })
                ->groupBy(['ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number']);

            // $sub_3 = ExportConfirm::select('tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number')
            //     ->groupBy(['tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number']);

            $sub_4 = Export::select('connection_number', 't_export.te_status_2', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                // ->leftjoin('receiving_goods_delivery', function ($join) {
                //     $join->on('t_export.te_carry_out_number', '=', 'receiving_goods_delivery.is_no');
                // })
                ->groupBy(['te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number']);
            $sub_5 = ReceivingGoodsDelivery::select('is_no', 'rgd_status3', 'rgd_status1')->groupBy('is_no');

            $import_schedule = DB::query()->fromSub($sub, 'aaa')->leftJoinSub($sub_2, 'bbb', function ($leftJoin) {
                $leftJoin->on('aaa.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
            })
                // ->leftJoinSub($sub_3, 'ccc', function ($leftjoin) {
                //     $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                // })
                ->leftJoinSub($sub_4, 'ddd', function ($leftjoin) {

                    //$leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                    $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                })
                ->leftJoinSub($sub_5, 'nnn', function ($leftjoin) {
                    $leftjoin->on('ddd.te_carry_out_number', '=', 'nnn.is_no')->where('ddd.te_carry_out_number', '!=', null);
                    $leftjoin->orOn('bbb.ti_carry_in_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number');
                    $leftjoin->orOn('aaa.tie_logistic_manage_number', '=', 'nnn.is_no')->whereNull('ddd.te_carry_out_number')->whereNull('bbb.ti_carry_in_number');
                })
                ->orderBy('tie_is_date', 'DESC')->orderBy('tie_h_bl', 'DESC')
                ->where('updated_at', '>', Carbon::now()->subDays(2));


            foreach ($import_schedule->get() as $item) {


                if ($item->te_carry_out_number) {

                    $is_exist = Warehousing::where('ti_carry_in_number', $item->ti_carry_in_number)->first();

                    if (isset($is_exist->w_no)) {
                        if ($is_exist->te_carry_out_number == null) {
                            $warehousing = Warehousing::updateOrCreate(
                                [
                                    'ti_carry_in_number' => $item->ti_carry_in_number,
                                ],
                                [
                                    'logistic_manage_number' => $item->ti_logistic_manage_number ? $item->ti_logistic_manage_number : ($item->te_logistic_manage_number ? $item->te_logistic_manage_number : $item->tec_logistic_manage_number),
                                    'w_category_name' => '보세화물',
                                    // 'w_completed_day' => $item['import']['ti_i_date'] ? $item['import']['ti_i_date'] : NULL,
                                    // 'w_schedule_day' => $item['tie_is_date'] ? $item['tie_is_date'] : NULL,
                                    'tie_no' => $item->tie_no,
                                    'w_schedule_amount' => $item->tie_is_number,
                                    'w_amount' => $item->ti_i_number,
                                    'w_type' => 'SET',
                                    'co_no' => isset($item->co_no) ? $item->co_no : null,
                                    'te_carry_out_number' => $item->te_carry_out_number,
                                ]
                            );
                        } else {
                            $warehousing = Warehousing::updateOrCreate(
                                [
                                    'te_carry_out_number' => $item->te_carry_out_number,
                                ],
                                [
                                    'logistic_manage_number' => $item->ti_logistic_manage_number ? $item->ti_logistic_manage_number : ($item->te_logistic_manage_number ? $item->te_logistic_manage_number : $item->tec_logistic_manage_number),
                                    'w_category_name' => '보세화물',
                                    // 'w_completed_day' => $item['import']['ti_i_date'] ? $item['import']['ti_i_date'] : NULL,
                                    // 'w_schedule_day' => $item['tie_is_date'] ? $item['tie_is_date'] : NULL,
                                    'tie_no' => $item->tie_no,
                                    'w_schedule_amount' => $item->tie_is_number,
                                    'w_amount' => $item->ti_i_number,
                                    'w_type' => 'SET',
                                    'co_no' => isset($item->co_no) ? $item->co_no : $item->co_no,
                                    'ti_carry_in_number' => $item->ti_carry_in_number,
                                ]
                            );
                        }
                    } else {
                        $warehousing = Warehousing::updateOrCreate(
                            [
                                'te_carry_out_number' => $item->te_carry_out_number,
                            ],
                            [
                                'logistic_manage_number' => $item->ti_logistic_manage_number ? $item->ti_logistic_manage_number : ($item->te_logistic_manage_number ? $item->te_logistic_manage_number : $item->tec_logistic_manage_number),
                                'w_category_name' => '보세화물',
                                // 'w_completed_day' => $item['import']['ti_i_date'] ? $item['import']['ti_i_date'] : NULL,
                                // 'w_schedule_day' => $item['tie_is_date'] ? $item['tie_is_date'] : NULL,
                                'tie_no' => $item->tie_no,
                                'w_schedule_amount' => $item->tie_is_number,
                                'w_amount' => $item->ti_i_number,
                                'w_type' => 'SET',
                                'co_no' => isset($item->co_no) ? $item->co_no : $item->co_no,
                                'ti_carry_in_number' => $item->ti_carry_in_number,
                            ]
                        );
                    }
                } else if ($item->ti_carry_in_number) {
                    $warehousing = Warehousing::updateOrCreate(
                        [
                            'ti_carry_in_number' => $item->ti_carry_in_number,
                        ],
                        [
                            'logistic_manage_number' => $item->ti_logistic_manage_number ? $item->ti_logistic_manage_number : ($item->te_logistic_manage_number ? $item->te_logistic_manage_number : $item->tec_logistic_manage_number),
                            'w_category_name' => '보세화물',
                            // 'w_completed_day' => $item['import']['ti_i_date'] ? $item['import']['ti_i_date'] : NULL,
                            // 'w_schedule_day' => $item['tie_is_date'] ? $item['tie_is_date'] : NULL,
                            'tie_no' => $item->tie_no,
                            'w_schedule_amount' => $item->tie_is_number,
                            'w_amount' => $item->ti_i_number,
                            'w_type' => 'SET',
                            'co_no' => isset($item->co_no) ? $item->co_no : null,
                            'te_carry_out_number' => $item->te_carry_out_number,
                        ]
                    );
                }


                //THUONG EDIT TO MAKE SETTLEMENT
                if (isset($warehousing->w_no)) {
                    ReceivingGoodsDelivery::updateOrCreate(
                        [
                            'w_no' => $warehousing->w_no,
                        ],
                        [
                            'service_korean_name' => '보세화물',
                            'rgd_status1' => '입고',
                            'rgd_tracking_code' => $warehousing->logistic_manage_number,
                            'rgd_ti_carry_in_number' => $warehousing->ti_carry_in_number,
                            'rgd_te_carry_out_number' => $warehousing->te_carry_out_number,
                        ]
                    );
                }
            }
            DB::commit();
            return response()->json(['message' => 'Success']);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }
}
