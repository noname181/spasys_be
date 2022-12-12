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
use App\Models\ItemChannel;
use App\Models\ImportExpected;
use App\Models\Import;
use App\Models\Export;
use App\Models\ExportConfirm;

use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use App\Models\StockStatusBad;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Http;

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
            $items = Item::with(['item_channels', 'company', 'file'])->where('item_service_name', '유통가공')->orderBy('item_no', 'DESC');

            if (isset($validated['co_no']) && Auth::user()->mb_type == "shop") {
                $items->where('co_no', $validated['co_no']);
            }

            if (isset($validated['w_no'])) {
                $warehousing = Warehousing::where('w_no', $validated['w_no'])->first();
                $items->where('co_no', $warehousing->co_no);
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

            if (Auth::user()->mb_type == "shop") {
                $items->whereHas('company.co_parent', function ($query) use ($co_no) {
                    $query->where(DB::raw('co_no'), '=', $co_no);
                });
            } elseif (Auth::user()->mb_type == "shipper") {
                $items->where('co_no', $co_no);
            } else {
                $co_child = Company::where('co_parent_no', $co_no)->get();
                $co_no = array();
                foreach ($co_child as $o) {
                    $co_no[] = $o->co_no;
                }

                $items->whereHas('company.co_parent', function ($query) use ($co_no) {
                    $query->whereIn(DB::raw('co_no'), $co_no);
                });
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
            $items = Item::with(['item_channels', 'ContractWms'])->where('item_service_name', '수입풀필먼트')->orderBy('item_no', 'DESC');

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



            $items = $items->paginate($per_page, ['*'], 'page', $page);
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
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->orderBy('item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'warehousing_item'])->where('item_service_name', '=', '유통가공')->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
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
                    $query->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
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

    public function paginateItemsApiIdRaw()
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
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
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
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])
                    ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                        $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                        $leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                    })
                    ->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                        $e->whereNotNull('stock');
                    })->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('item.item_no', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])
                    ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                        $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                        $leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                    })->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                        $e->whereNotNull('stock');
                    })->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('item.item_no', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $item = Item::with(['file', 'company', 'item_channels', 'item_info', 'ContractWms'])->select('item.*','stock_status_bad.stock')
                    ->leftjoin(DB::raw('stock_status_bad'), function ($leftJoin) {
                        $leftJoin->on('item.product_id', '=', 'stock_status_bad.product_id');
                        $leftJoin->on('item.option_id', '=', 'stock_status_bad.option_id');
                    })->where('item_service_name', '=', '수입풀필먼트')->whereHas('item_info', function ($e) {
                        $e->whereNotNull('stock');
                    })->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('item.item_no', 'DESC');
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
                $item->where(function ($query) use ($validated) {
                    $query->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
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
                $item4 = Item::with(['item_info'])->where('item.item_no', $q->item_no)->first();
                if (isset($item4['item_info']['stock'])) {
                    return ['total_amount' => $item4['item_info']['stock']];
                }
            })->sum('total_amount');
            $item5 = collect($item2)->map(function ($q) {
                $item6 = Item::with(['item_info'])->where('item.item_no', $q->item_no)->first();
                if (isset($item6['item_info']['stock'])) {
                    return ['total_price' => $item6->item_price2 * $item6['item_info']['stock']];
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
            $item['item_channels'] = $item_channels;
            $item['file'] = $file;
            $item['company'] = $company;
            $item['item_info'] = $item_info;
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
        // try {
        DB::beginTransaction();
        $f = Storage::disk('public')->put('files/tmp', $request['file']);

        $path = storage_path('app/public') . '/' . $f;
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheet(0);
        $datas = $sheet->toArray(null, true, true, true);

        $sheet2 = $spreadsheet->getSheet(1);
        $data_channels = $sheet2->toArray(null, true, true, true);

        $results[$sheet->getTitle()] = [];
        $errors[$sheet->getTitle()] = [];

        $data_item_count = 0;
        $data_channel_count = 0;

        $check_error = false;
        foreach ($datas as $key => $d) {
            if ($key <= 2) {
                continue;
            }

            $validator = Validator::make($d, ExcelRequest::rules());
            if ($validator->fails()) {
                $data_item_count =  $data_item_count - 1;
                $errors[$sheet->getTitle()][] = $validator->errors();
                $check_error = true;
            } else {
                $data_item_count =  $data_item_count + 1;
                $item_no = Item::insertGetId([
                    'item_service_name' => '유통가공',
                    'mb_no' => Auth::user()->mb_no,
                    'co_no' => Auth::user()->co_no,
                    'item_brand' => $d['B'],
                    'item_name' => $d['C'],
                    'item_option1' => $d['D'],
                    'item_option2' => $d['E'],
                    'item_cargo_bar_code' => $d['F'],
                    'item_upc_code' => $d['G'],
                    'item_bar_code' => $d['H'],
                    'item_weight' => $d['I'],
                    'item_url' => $d['J'],
                    'item_price1' => $d['K'],
                    'item_price2' => $d['L'],
                    'item_price3' => $d['M'],
                    'item_price4' => $d['N'],
                    'item_cate1' => $d['O'],
                    'item_cate2' => $d['P'],
                    'item_cate3' => $d['Q'],
                    'item_origin' => $d['R'],
                    'item_manufacturer' => $d['S']
                ]);

                // Check validator item_channel
                if ($data_channels) {
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

    public function apiItemsRaw($request = null)
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
                        'mb_no' => Auth::user()->mb_no,
                        'co_no' => isset($item->co_no) ? $item->co_no : Auth::user()->co_no,
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
                                                'product_id' => isset($item->product_id) ? $item->product_id : null,
                                                'option_id' => isset($option['product_id']) ? $option['product_id'] : null,
                                                'mb_no' => 0,
                                                'co_no' => 0,
                                                'item_name' => isset($item->name) ? $item_no->name : null,
                                                'supply_code' => isset($item->supply_code) ? $item_no->supply_code : null,
                                                'item_brand' => isset($item->brand) ? $item_no->brand : null,
                                                'item_origin' => isset($item->origin) ? $item_no->origin : null,
                                                'item_weight' => isset($item->weight) ? $item_no->weight : null,
                                                'item_price1' => isset($item->org_price) ? $item_no->org_price : null,
                                                'item_price2' => isset($item->shop_price) ? $item_no->shop_price : null,
                                                'item_price3' => isset($item->supply_price) ? $item_no->supply_price : null,
                                                'item_url' => isset($item->img_500) ? $item_no->img_500 : null,
                                                'item_option1' => isset($option['options']) ? $option['options'] : null,
                                                'item_service_name' => '수입풀필먼트'
                                            ]
                                        );
                                        $item_info_no = ItemInfo::updateOrCreate(
                                            [
                                                'item_no' => $item_no->item_no,
                                            ],
                                            [
                                                'item_no' => isset($item_no->item_no) ? $item_no->item_no : null,
                                                'product_id' => isset($item_no->product_id) ? $item_no->product_id : null,
                                                'supply_code' => isset($item_no->supply_code) ? $item_no->supply_code : null,
                                                'trans_fee' => isset($item_no->trans_fee) ? $item_no->trans_fee : null,
                                                'img_desc1' => isset($item_no->img_desc1) ? $item_no->img_desc1 : null,
                                                'img_desc2' => isset($item_no->img_desc2) ? $item_no->img_desc2 : null,
                                                'img_desc3' => isset($item_no->img_desc3) ? $item_no->img_desc3 : null,
                                                'img_desc4' => isset($item_no->img_desc4) ? $item_no->img_desc4 : null,
                                                'img_desc5' => isset($item_no->img_desc5) ? $item_no->img_desc5 : null,
                                                'product_desc' => isset($item_no->product_desc) ? $item_no->product_desc : null,
                                                'product_desc2' => isset($item_no->product_desc2) ? $item_no->product_desc2 : null,
                                                'location' => isset($item_no->location) ? $item_no->location : null,
                                                'memo' => isset($item_no->memo) ? $item_no->memo : null,
                                                'category' => isset($item_no->category) ? $item_no->category : null,
                                                'maker' => isset($item_no->maker) ? $item_no->maker : null,
                                                'md' => isset($item_no->md) ? $item_no->md : null,
                                                'manager1' => isset($item_no->manager1) ? $item_no->manager1 : null,
                                                'manager2' => isset($item_no->manager2) ? $item_no->manager2 : null,
                                                'supply_options' => isset($option['supply_options']) ? $option['supply_options'] : '',
                                                'enable_sale' => isset($option['enable_sale']) ? $option['enable_sale'] : 1,
                                                'use_temp_soldout' => isset($option['use_temp_soldout']) ? $option['use_temp_soldout'] : 0,
                                                'stock_alarm1' => isset($option['stock_alarm1']) ? $option['stock_alarm1'] : 0,
                                                'stock_alarm2' => isset($option['stock_alarm2']) ? $option['stock_alarm2'] : 0,
                                                'extra_price' => isset($option['extra_price']) ? $option['extra_price'] : 0,
                                                'extra_shop_price' => isset($option['extra_shop_price']) ? $option['extra_shop_price'] : 0,
                                                'extra_column6' => isset($option['extra_column6']) ? $option['extra_column6'] : '',
                                                'extra_column7' => isset($option['extra_column7']) ? $option['extra_column7'] : '',
                                                'extra_column8' => isset($option['extra_column8']) ? $option['extra_column8'] : '',
                                                'extra_column9' => isset($option['extra_column9']) ? $option['extra_column9'] : '',
                                                'extra_column10' => isset($option['extra_column10']) ? $option['extra_column10'] : '',
                                                'reg_date' => isset($option['reg_date']) ? $option['reg_date'] : null,
                                                'last_update_date' => isset($option['last_update_date']) ? $option['last_update_date'] : null,
                                                'new_link_id' => isset($option['new_link_id']) ? $option['new_link_id'] : '',
                                                'link_id' => isset($option['link_id']) ? $option['link_id'] : '',
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
                                    'product_id' => isset($item->product_id) ? $item->product_id : null,
                                    'mb_no' => 0,
                                    'co_no' => 0,
                                    'item_name' => isset($item->name) ? $item->name : null,
                                    'supply_code' => isset($item->supply_code) ? $item->supply_code : null,
                                    'item_brand' => isset($item->brand) ? $item->brand : null,
                                    'item_origin' => isset($item->origin) ? $item->origin : null,
                                    'item_weight' => isset($item->weight) ? $item->weight : null,
                                    'item_price1' => isset($item->org_price) ? $item->org_price : null,
                                    'item_price2' => isset($item->shop_price) ? $item->shop_price : null,
                                    'item_price3' => isset($item->supply_price) ? $item->supply_price : null,
                                    'item_url' => isset($item->img_500) ? $item->img_500 : null,
                                    'item_option1' => isset($item->options) ? $item->options : '',
                                    'item_service_name' => '수입풀필먼트'
                                ]
                            );
                            
                            $item_info_no = ItemInfo::updateOrCreate(
                                [
                                    'item_no' => $item_no->item_no,
                                ],
                                [
                                    'item_no' => $item_no->item_no,
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
                                ]
                            );
                        }
                    } else {
                        if($i_item == 6){
                            return $item;
                        }
                        $item_info_no = ItemInfo::updateOrCreate(
                            [
                                'item_no' => $item_no->item_no,
                            ],
                            [
                                'item_no' => $item_no->item_no,
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
            }


            DB::commit();
            return response()->json([
                'message' => '완료되었습니다.',
                'status' => 1,
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

    public function apiItemsCargoList(Request $request)
    {

        try {

            DB::beginTransaction();

            DB::statement("set session sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            $user = Auth::user();
            if ($user->mb_type == 'shop') {
                $import_schedule = ImportExpected::with(['company', 'receiving_goods_delivery'])->whereHas('company.co_parent', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->select('tie_status_2', 'tie_status', 'tie_m_bl', 'tie_h_bl', 'tie_no', 'tie_logistic_manage_number', 'tie_co_license', 'tie_is_number', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number', 'tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    ->leftjoin(DB::raw('(SELECT ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number
                    FROM t_import group by ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number)
                    bbb'), function ($leftJoin) {

                        $leftJoin->on('t_import_expected.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number
                    FROM t_export_confirm group by tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number)
                    ccc'), function ($leftjoin) {

                        $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number
                    FROM t_export group by te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number)
                    ddd'), function ($leftjoin) {

                        $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                        $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                    })->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number'])->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'shipper') {
                $import_schedule = ImportExpected::with(['company', 'receiving_goods_delivery'])->whereHas('company', function ($q) use ($user) {
                    $q->where('co_no', $user->co_no);
                })->select('tie_status_2', 'tie_status', 'tie_m_bl', 'tie_h_bl', 'tie_no', 'tie_logistic_manage_number', 'tie_co_license', 'tie_is_number', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number', 'tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    ->leftjoin(DB::raw('(SELECT ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number
                FROM t_import group by ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number)
                bbb'), function ($leftJoin) {

                        $leftJoin->on('t_import_expected.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number
                FROM t_export_confirm group by tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number)
                ccc'), function ($leftjoin) {

                        $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number
                FROM t_export group by te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number)
                ddd'), function ($leftjoin) {

                        $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                        $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                    })->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number'])->orderBy('te_carry_out_number', 'DESC');
            } else if ($user->mb_type == 'spasys') {
                $import_schedule = ImportExpected::with(['company', 'receiving_goods_delivery'])->whereHas('company.co_parent.co_parent', function ($q) use ($user) {
                    $q->where('warehouse_code', $user->warehouse_code);
                })->select('tie_status_2', 'tie_status', 'tie_m_bl', 'tie_h_bl', 'tie_no', 'tie_logistic_manage_number', 'tie_co_license', 'tie_is_number', 'ti_logistic_manage_number', 'ti_i_confirm_number', 'ti_i_date', 'ti_i_order', 'ti_i_number', 'ti_carry_in_number', 'tec_logistic_manage_number', 'tec_ec_confirm_number', 'tec_ec_date', 'tec_ec_number', 'te_logistic_manage_number', 'te_carry_out_number', 'te_e_date', 'te_carry_in_number', 'te_e_order', 'te_e_number')
                    ->leftjoin(DB::raw('(SELECT ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number
           FROM t_import group by ti_logistic_manage_number, ti_i_confirm_number, ti_i_date, ti_i_order, ti_i_number, ti_carry_in_number)
           bbb'), function ($leftJoin) {

                        $leftJoin->on('t_import_expected.tie_logistic_manage_number', '=', 'bbb.ti_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number
           FROM t_export_confirm group by tec_logistic_manage_number, tec_ec_confirm_number, tec_ec_date, tec_ec_number)
           ccc'), function ($leftjoin) {

                        $leftjoin->on('bbb.ti_logistic_manage_number', '=', 'ccc.tec_logistic_manage_number');
                    })->leftjoin(DB::raw('(SELECT te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number
           FROM t_export group by te_logistic_manage_number, te_carry_out_number, te_e_date, te_carry_in_number, te_e_order, te_e_number)
           ddd'), function ($leftjoin) {

                        $leftjoin->on('ccc.tec_logistic_manage_number', '=', 'ddd.te_logistic_manage_number');
                        $leftjoin->on('bbb.ti_carry_in_number', '=', 'ddd.te_carry_in_number');
                    })->where('tie_is_date', '>=', '2022-01-04')->where('tie_is_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->groupBy(['tie_logistic_manage_number', 't_import_expected.tie_is_number'])->orderBy('te_carry_out_number', 'DESC');
            }

            $import_schedule = $import_schedule->get();


            DB::statement("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

            foreach ($import_schedule as $value) {
                if (isset($value['tie_logistic_manage_number'])) {
                    $logistic_manage_number = $value['tie_logistic_manage_number'];
                    $logistic_manage_number = str_replace('-', '', $logistic_manage_number);


                    $xmlString = simplexml_load_file("https://unipass.customs.go.kr:38010/ext/rest/cargCsclPrgsInfoQry/retrieveCargCsclPrgsInfo?crkyCn=s230z262h044b104n070k070a3&cargMtNo=" . $logistic_manage_number . "") or die("Error: Cannot create object");
                    $json = json_encode($xmlString);
                    $array = json_decode($json, TRUE);
                    //return $array;


                    if (isset($array['cargCsclPrgsInfoDtlQryVo']) && $array['cargCsclPrgsInfoDtlQryVo']) {
                        $data_apis = $array['cargCsclPrgsInfoDtlQryVo'];
                        foreach ($data_apis as $data) {
                            $status = isset($data['cargTrcnRelaBsopTpcd']) ? $data['cargTrcnRelaBsopTpcd'] : '';
                            // if($status == '입항적하목록 심사완료' || $status == '입항보고 제출' || 
                            // $status == '입항보고 수리' || $status == '입항적하목록 운항정보 정정' || $status == '하기신고 수리'
                            // || $status == '반입신고' || $status == '보세운송 신고 접수' || $status == '보세운송 신고 수리' || $status == '반출신고'
                            // || $status == '반입신고' || $status == '수입신고'
                            // ){
                            //     $status = '수입신고접수';
                            // }else if($status == '수입(사용소비) 심사진행' || $status == '수입신고수리'){
                            //     $status = '수입신고수리';
                            // }else if($status == '수입신고 수리후 정정 접수'){
                            //     $status = '수입신고정정접수';
                            // }else if($status == '수입신고 수리후 정정 완료'){
                            //     $status = '수입신고정정완료';
                            // }
                            // if($status == '수입신고 수리후 정정 완료'){
                            //     $status1 = '수입신고정정완료';
                            // }else if($status == '수입신고 수리후 정정 접수'){
                            //     $status1 = '수입신고정정접수';
                            // }else if($status == '수입신고수리'){
                            //     $status1 = '수입신고수리';
                            // }else if($status == '수입신고'){
                            //     $status1 = '수입신고접수';
                            // }else{
                            //     $status1 = null;
                            // }
                            $status1 = '';
                            switch ($status) {
                                case '수입신고 수리후 정정 완료':
                                    $status1 = '수입신고정정완료';
                                    break;
                                case '수입신고 수리후 정정 접수':
                                    $status1 = '수입신고정정접수';
                                    break;
                                case '수입신고수리':
                                    $status1 = '수입신고수리';
                                    break;
                                case '수입신고':
                                    $status1 = '수입신고접수';
                                    break;
                                default:
                                    $status1 = null;
                                    break;
                            }


                            $import_expected = ImportExpected::where('tie_logistic_manage_number', $value['tie_logistic_manage_number'])
                                ->update([
                                    'tie_status_2' => $status1
                                ]);

                            $import = Import::where('ti_logistic_manage_number', $value['ti_logistic_manage_number'])->where('ti_i_confirm_number', $value['ti_i_confirm_number'])
                                ->update([
                                    'ti_status_2' => $status1
                                ]);

                            $export = Export::where('te_logistic_manage_number', $value['te_logistic_manage_number'])->where('te_carry_out_number', $value['te_carry_out_number'])
                                ->update([
                                    'te_status_2' => $status1
                                ]);

                            // $export_confirm = ExportConfirm::where('te_logistic_manage_number', $value['te_logistic_manage_number'])
                            // ->update([
                            //     'te_status_2' => $status
                            // ]);
                            if ($status1 != null) {
                                break;
                            }
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
        if ($filter['limit'] != '') {
            $url_api .= '&limit=' . $filter['limit'];
        }
        if ($filter['page'] != '') {
            $url_api .= '&page=' . $filter['page'];
        }
        $response = file_get_contents($url_api);
        $api_data = json_decode($response);
        $total_data = 0;
        $pages = 0;
        if (isset($api_data->api_data) && $api_data->api_data > 100) {
            $total_data = $api_data->api_data;
            $pages = ceil($total_data / 100);
            for ($i = 1; $i <= $pages; $i++) {
                $this->apiItemsRaw($api_data);
            }
        } else {
            if (!empty($api_data->data)) {
                return $this->apiItemsRaw($api_data);
            } else {
                return response()->json([
                    'message' => '새로운 데이터가 없습니다.',
                    'status' => 1
                ], 200);
            }
        }
        
        return response()->json([
            'message' => '새로운 데이터가 없습니다.',
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
                'message' => '새로운 데이터가 없습니다.',
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
                'message' => '완료되었습니다.',
                'status' => 1
            ], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018], 500);
        }
    }

    public function updateStockStatus($url_api)
    {
        $response = file_get_contents($url_api);
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
            }
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
        for ($bad = 0; $bad <= 1; $bad++) {
            if (!empty($list_items)) {
                $url_api .= '&product_id=';
                foreach ($list_items as $key_item => $item) {
                    if ($key_item > 0) {
                        $url_api .= ',';
                    }
                    $url_api .= $item['product_id'];
                    if ($key_item >= 50) {
                        break;
                    }
                }
            }
            $url_api .= '&bad=' . $bad;

            $response = file_get_contents($url_api);
            $api_data = json_decode($response);
            if (!empty($api_data->data)) {
                foreach ($api_data->data as $item) {
                    $item = (array)$item;
                    $item_info = Item::where('product_id', $item['product_id'])->first();
                    if ($item['stock'] == 0) { // Khong thuoc kho nao
                        $stock = rand(10, 100);
                        $item_info_no = ItemInfo::updateOrCreate([
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
        return response()->json([
            'message' => '완료되었습니다.',
            'status' => 1
        ], 200);
    }
}
