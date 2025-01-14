<?php

namespace App\Http\Controllers\ScheduleShipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleShipment\ScheduleShipmentRequest;
use App\Http\Requests\ScheduleShipment\ScheduleShipmentSearchRequest;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\ScheduleShipment;
use App\Models\ScheduleShipmentInfo;
use App\Models\StockStatusBad;
use App\Models\Company;
use App\Models\File;
use App\Models\ItemChannel;
use App\Models\StockHistory;
use App\Utils\Messages;
use App\Http\Requests\Item\ExcelRequest;
use App\Models\ContractWms;
use App\Models\Item;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Utils\CommonFunc;
use \Carbon\Carbon;

class ScheduleShipmentController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginateScheduleShipments(ScheduleShipmentSearchRequest $request)
    {
        try {

            $validated = $request->validated();
            $user = Auth::user();
            // If per_page is null set default data = 15
            $per_page = isset($validated['per_page']) ? $validated['per_page'] : 15;
            // If page is null set default data = 1
            $page = isset($validated['page']) ? $validated['page'] : 1;
            DB::enableQueryLog();
            if ($request->type == 'page136') {
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고예정')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })->orderBy('ss_no', 'DESC');
                }
            } else {
                if ($user->mb_type == 'shop') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'shipper') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                } else if ($user->mb_type == 'spasys') {
                    $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'ContractWms', 'receving_goods_delivery'])->where('status', '출고')->whereHas('ContractWms.company.co_parent.co_parent', function ($q) use ($user) {
                        $q->where('co_no', $user->co_no);
                    })
                        // ->where(function ($q) {
                        //     $q->whereHas('receving_goods_delivery', function ($q1) {
                        //         $q1->where('rgd_status3', '=',"배송완료");
                        //     });
                        // })
                        ->orderBy('ss_no', 'DESC');
                }
            }
            //return DB::getQueryLog();

            if (isset($validated['from_date'])) {
                $schedule_shipment->where('updated_at', '>=', date('Y-m-d 00:00:00', strtotime($validated['from_date'])));
            }

            if (isset($validated['to_date'])) {
                $schedule_shipment->where('updated_at', '<=', date('Y-m-d 23:59:00', strtotime($validated['to_date'])));
            }

            if (isset($validated['co_parent_name'])) {
                $schedule_shipment->whereHas('ContractWms.company.co_parent', function ($query) use ($validated) {
                    $query->where(DB::raw('lower(co_name)'), 'like', '%' . $validated['co_parent_name'] . '%');
                });
            }

            if (isset($validated['co_name'])) {
                $schedule_shipment->whereHas('ContractWms.company', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(co_name)'), 'like', '%' . $validated['co_name'] . '%');
                });
            }

            if (isset($validated['item_brand'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_brand)'), 'like', '%' . strtolower($validated['item_brand']) . '%');
                });
            }

            if (isset($validated['item_channel_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item.item_channels', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_channel_name)'), 'like', '%' . strtolower($validated['item_channel_name']) . '%');
                });
            }

            if (isset($validated['item_name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(item_name)'), 'like', '%' . strtolower($validated['item_name']) . '%');
                });
            }

            if (isset($validated['product_id'])) {
                $schedule_shipment->whereHas('schedule_shipment_info.item', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(product_id)'), 'like', '%' . strtolower($validated['product_id']) . '%');
                });
            }

            if (isset($validated['status'])) {
                if ($validated['status'] == '배송준비') {
                    $schedule_shipment->whereDoesntHave('receving_goods_delivery')->orwhereHas('receving_goods_delivery', function ($q) use ($validated) {
                        $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                    });
                } elseif ($validated['status'] == '배송중') {
                    $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                        $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                    });
                } elseif ($validated['status'] == '배송완료') {
                    $schedule_shipment->whereHas('receving_goods_delivery', function ($q) use ($validated) {
                        $q->where(DB::raw('lower(rgd_status3)'), 'like', '%' . strtolower($validated['status']) . '%');
                    });
                }
            }

            if (isset($validated['order_id'])) {

                $schedule_shipment->where(DB::raw('lower(order_id)'), 'like', '%' . strtolower($validated['order_id']) . '%');
            }
            if (isset($validated['shop_product_id'])) {

                $schedule_shipment->where(DB::raw('lower(shop_product_id)'), 'like', '%' . strtolower($validated['shop_product_id']) . '%');
            }
            if (isset($validated['status_api'])) {

                $schedule_shipment->where('status', '=', $validated['status_api']);
            }
            if (isset($validated['recv_name'])) {
                $schedule_shipment->where(DB::raw('lower(recv_name)'), 'like', '%' . strtolower($validated['recv_name']) . '%');
            }
            if (isset($validated['name'])) {
                $schedule_shipment->whereHas('schedule_shipment_info', function ($q) use ($validated) {
                    $q->where('name', 'like', '%' . $validated['name'] . '%');
                });
            }
            if (isset($validated['qty'])) {
                $schedule_shipment->whereHas('schedule_shipment_info', function ($q) use ($validated) {
                    return $q->where(DB::raw('lower(qty)'), 'like', '%' . strtolower($validated['qty']) . '%');
                });
            }
            if (isset($validated['trans_corp'])) {
                $schedule_shipment->where(DB::raw('lower(trans_corp)'), 'like', '%' . strtolower($validated['trans_corp']) . '%');
            }

            $schedule_shipment = $schedule_shipment->paginate($per_page, ['*'], 'page', $page);
            $schedule_shipment->setCollection(
                $schedule_shipment->getCollection()->map(function ($q) {
                    $schedule_shipment_item = DB::table('schedule_shipment_info')->whereNotNull('order_cs')->where('schedule_shipment_info.ss_no', $q->ss_no)->get();
                    $count_item = 0;
                    foreach ($schedule_shipment_item as $item) {
                        $q->total_amount += $item->qty;
                        $count_item++;
                    }
                    $q->count_item = $count_item;

                    $scheduleshipment_info_ = ScheduleShipmentInfo::with(['item'])->where('ss_no', $q->ss_no)->first();
                    $item_schedule_shipment = Item::where('product_id', $scheduleshipment_info_->product_id)->first();
                    if (isset($schedule_shipment_item[0]->name)) {
                        // $item_first_name = $item_schedule_shipment['item_name'];
                        $item_first_name = $schedule_shipment_item[0]->name;

                        // $total_item = $scheduleshipment_info_['item']->count() - 1;

                        $total_item = $count_item - 1;

                        if ($total_item <= 0) {
                            $q->first_item_name_total = $item_first_name;
                        } else {
                            $q->first_item_name_total = $item_first_name . '외' . ' ' . $total_item . '건';
                        }
                    } else {
                        $q->first_item_name_total = '';
                    }
                    // $text = $q->status == '출고' ? "EWC" : "EW";
                    // $q->w_schedule_number = isset($q->w_schedule_number) ? $q->w_schedule_number : (new CommonFunc)->generate_w_schedule_number_service2($q->ss_no, $text, $q->created_at);
                    //$q->test = $total_item;

                    return  $q;
                })
            );

            return response()->json($schedule_shipment);
        } catch (\Exception $e) {
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0018,], 500);
        }
    }
    public function apiScheduleShipments(Request $request)
    {
        //return $request;
        //$validated = $request->validated();
        try {
            DB::beginTransaction();
            $user = Auth::user();
            foreach ($request->data as $i_schedule => $schedule) {
                $data_schedule = [
                    'co_no' => $user->co_no,
                    'seq' => isset($schedule['seq']) ? $schedule['seq'] : null,
                    'pack' => isset($schedule['pack']) ? $schedule['pack'] : null,
                    'shop_code' => isset($schedule['shop_id']) ? $schedule['shop_id'] : null,
                    'shop_name' => isset($schedule['shop_name']) ? $schedule['shop_name'] : null,
                    'order_id' => isset($schedule['order_id']) ? $schedule['order_id'] : null,
                    'order_id_seq' => isset($schedule['order_id_seq']) ? $schedule['order_id_seq'] : null,
                    'order_id_seq2' => isset($schedule['order_id_seq2']) ? $schedule['order_id_seq2'] : null,
                    'shop_product_id' => isset($schedule['shop_product_id']) ? $schedule['shop_product_id'] : null,
                    'product_name' => isset($schedule['product_name']) ? $schedule['product_name'] : null,
                    'options' => isset($schedule['options']) ? $schedule['options'] : null,
                    'qty' => isset($schedule['qty']) ? $schedule['qty'] : null,
                    'order_name' => isset($schedule['order_name']) ? $schedule['order_name'] : null,
                    'order_mobile' => isset($schedule['order_mobile']) ? $schedule['order_mobile'] : null,
                    'order_tel' => isset($schedule['order_tel']) ? $schedule['order_tel'] : null,
                    'recv_name' => isset($schedule['recv_name']) ? $schedule['recv_name'] : null,
                    'recv_mobile' => isset($schedule['recv_mobile']) ? $schedule['recv_mobile'] : null,
                    'recv_tel' => isset($schedule['recv_tel']) ? $schedule['recv_tel'] : null,
                    'recv_address' => isset($schedule['recv_address']) ? $schedule['recv_address'] : null,
                    'recv_zip' => isset($schedule['recv_zip']) ? $schedule['recv_zip'] : null,
                    'memo' => isset($schedule['memo']) ? $schedule['memo'] : null,
                    'status' => isset($schedule['status']) ? $schedule['status'] : null,
                    'delivery_status' => '택배',
                    'w_category_name' => '수입풀필먼트',
                    'order_cs' => isset($schedule['order_cs']) ? $schedule['order_cs'] : null,
                    'collect_date' => isset($schedule['collect_date']) ? $schedule['collect_date'] : null,
                    'order_date' => isset($schedule['order_date']) ? ($schedule['order_date'] != '0000-00-00 00:00:00' ? $schedule['order_date'] : null) : null,
                    'trans_date' => isset($schedule['trans_date']) ? $schedule['trans_date'] : null,
                    'trans_date_pos' => isset($schedule['trans_date_pos']) ? $schedule['trans_date_pos'] : null,
                    'shopstat_date' => isset($schedule['shopstat_date']) ? $schedule['shopstat_date'] : null,
                    'supply_price' => isset($schedule['supply_price']) ? $schedule['supply_price'] : null,
                    'amount' => isset($schedule['amount']) ? $schedule['amount'] : null,
                    'extra_money' => isset($schedule['extra_money']) ? $schedule['extra_money'] : null,
                    'trans_corp' => isset($schedule['trans_corp']) ? $schedule['trans_corp'] : null,
                    'trans_no' => isset($schedule['trans_no']) ? '출고' : '출고예정',
                    'trans_who' => isset($schedule['trans_who']) ? $schedule['trans_who'] : null,
                    'prepay_price' => isset($schedule['prepay_price']) ? $schedule['prepay_price'] : null,
                    'gift' => isset($schedule['gift']) ? $schedule['gift'] : null,
                    'hold' => isset($schedule['hold']) ? $schedule['hold'] : null,
                    'org_seq' => isset($schedule['org_seq']) ? $schedule['org_seq'] : null,
                    'deal_no' => isset($schedule['deal_no']) ? $schedule['deal_no'] : null,
                    'sub_domain' => isset($schedule['sub_domain']) ? $schedule['sub_domain'] : null,
                    'sub_domain_seq' => isset($schedule['sub_domain_seq']) ? $schedule['sub_domain_seq'] : null,
                ];
                //return $data_schedule;
                $ss_no = ScheduleShipment::insertGetId($data_schedule);
                //return $ss_no;

                if (isset($schedule['order_products'])) {
                    foreach ($schedule['order_products'] as $ss_info => $schedule_info) {
                        $ss_info_no = ScheduleShipmentInfo::insertGetId([
                            'ss_no' => $ss_no,
                            'co_no' => $user->co_no,
                            'barcode' => isset($schedule_info['barcode']) ? $schedule_info['barcode'] : null,
                            'brand' => isset($schedule_info['brand']) ? $schedule_info['brand'] : null,
                            'cancel_date' => isset($schedule_info['cancel_date']) ? $schedule_info['cancel_date'] : null,
                            'change_date' => isset($schedule_info['change_date']) ? $schedule_info['change_date'] : null,
                            'enable_sale' => isset($schedule_info['enable_sale']) ? $schedule_info['enable_sale'] : null,
                            'extra_money' => isset($schedule_info['extra_money']) ? $schedule_info['extra_money'] : null,
                            'is_gift' => isset($schedule_info['is_gift']) ? $schedule_info['is_gift'] : null,
                            'link_id' => isset($schedule_info['link_id']) ? $schedule_info['link_id'] : null,
                            'name' => isset($schedule_info['name']) ? $schedule_info['name'] : null,
                            'new_link_id' => isset($schedule_info['new_link_id']) ? $schedule_info['new_link_id'] : null,
                            'options' => isset($schedule_info['options']) ? $schedule_info['options'] : null,
                            'order_cs' => isset($schedule_info['order_cs']) ? $schedule_info['order_cs'] : null,
                            'prd_amount' => isset($schedule_info['prd_amount']) ? $schedule_info['prd_amount'] : null,
                            'prd_seq' => isset($schedule_info['prd_seq']) ? $schedule_info['prd_seq'] : null,
                            'prd_supply_price' => isset($schedule_info['prd_supply_price']) ? $schedule_info['prd_supply_price'] : null,
                            'product_id' => isset($schedule_info['product_id']) ? $schedule_info['product_id'] : null,
                            'qty' => isset($schedule_info['qty']) ? $schedule_info['qty'] : null,
                            'shop_price' => isset($schedule_info['shop_price']) ? $schedule_info['shop_price'] : null,
                            'supply_code' => isset($schedule_info['supply_code']) ? $schedule_info['supply_code'] : null,
                            'supply_name' => isset($schedule_info['supply_name']) ? $schedule_info['supply_name'] : null,
                            'supply_options' => isset($schedule_info['supply_options']) ? $schedule_info['supply_options'] : null,

                        ]);
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
            //return $e;
            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function apiScheduleShipmentsRaw($data_schedule = null)
    {

        try {
            DB::beginTransaction();
            $user = Auth::user();
            //return $data_schedule;
            foreach ($data_schedule as $i_schedule => $schedule) {
                foreach ($schedule['data_item'] as $schedule_item) {
                    if (str_contains($schedule_item['shop_product_id'], 'S')) {
                        $shop_option_id = $schedule_item['shop_product_id'];
                        $shop_product_id = Item::where('option_id', $shop_option_id)->select('product_id')->first();
                        $shop_product_id = !empty($shop_product_id->product_id) ? $shop_product_id->product_id : '';
                    } else {
                        $shop_option_id = '';
                        $shop_product_id = !empty($schedule_item['shop_product_id']) ? $schedule_item['shop_product_id'] : '';
                    }
                    $data_schedule = [
                        'co_no' => $user->co_no,
                        'seq' => isset($schedule_item['seq']) ? $schedule_item['seq'] : null,
                        'pack' => isset($schedule_item['pack']) ? $schedule_item['pack'] : null,
                        'shop_code' => isset($schedule_item['shop_id']) ? $schedule_item['shop_id'] : null,
                        'shop_name' => isset($schedule_item['shop_name']) ? $schedule_item['shop_name'] : null,
                        'order_id' => !empty($schedule_item['order_id']) ? $schedule_item['order_id'] : $i_schedule,
                        'order_id_seq' => isset($schedule_item['order_id_seq']) ? $schedule_item['order_id_seq'] : null,
                        'order_id_seq2' => isset($schedule_item['order_id_seq2']) ? $schedule_item['order_id_seq2'] : null,
                        'shop_product_id' => $shop_product_id,
                        'shop_option_id' => $shop_option_id,
                        'product_name' => isset($schedule_item['product_name']) ? $schedule_item['product_name'] . '외 ' . (count($schedule_item['order_products']) - 1) . '건' : null,
                        'options' => isset($schedule_item['options']) ? $schedule_item['options'] : null,
                        'qty' => isset($schedule_item['qty']) ? $schedule_item['qty'] : null,
                        'w_category_name' => '수입풀필먼트',
                        'order_name' => isset($schedule_item['order_name']) ? $schedule_item['order_name'] : null,
                        'order_mobile' => isset($schedule_item['order_mobile']) ? $schedule_item['order_mobile'] : null,
                        'order_tel' => isset($schedule_item['order_tel']) ? $schedule_item['order_tel'] : null,
                        'recv_name' => isset($schedule_item['recv_name']) ? $schedule_item['recv_name'] : null,
                        'recv_mobile' => isset($schedule_item['recv_mobile']) ? $schedule_item['recv_mobile'] : null,
                        'recv_tel' => isset($schedule_item['recv_tel']) ? $schedule_item['recv_tel'] : null,
                        'recv_address' => isset($schedule_item['recv_address']) ? $schedule_item['recv_address'] : null,
                        'recv_zip' => isset($schedule_item['recv_zip']) ? $schedule_item['recv_zip'] : null,
                        'memo' => isset($schedule_item['memo']) ? $schedule_item['memo'] : null,
                        //'status' => isset($schedule_item['trans_no']) && $schedule_item['trans_no'] != '' ? '출고' : '출고예정',
                        'status' => isset($schedule_item['status']) && $schedule_item['status'] == 8 ? '출고' : '출고예정',
                        'delivery_status' => '택배',
                        'order_cs' => isset($schedule_item['order_cs']) ? $schedule_item['order_cs'] : null,
                        'collect_date' => isset($schedule_item['collect_date']) ? $schedule_item['collect_date'] : null,
                        'order_date' => isset($schedule_item['order_date']) ? ((int)$schedule_item['order_date'] > 2022 ? $schedule_item['order_date'] : null) : null,
                        'trans_date' => isset($schedule_item['trans_date']) && $schedule_item['trans_date'] != '0000-00-00 00:00:00' && $schedule_item['trans_date'] != '' ? $schedule_item['trans_date'] : null,
                        'trans_date_pos' => isset($schedule_item['trans_date_pos']) && $schedule_item['trans_date_pos'] != '0000-00-00 00:00:00' && $schedule_item['trans_date_pos'] != '' ? $schedule_item['trans_date_pos'] : null,
                        'shopstat_date' => isset($schedule_item['shopstat_date']) && $schedule_item['shopstat_date'] != '' ? $schedule_item['shopstat_date'] : null,
                        'supply_price' => isset($schedule_item['supply_price']) ? $schedule_item['supply_price'] : null,
                        'amount' => isset($schedule_item['amount']) ? $schedule_item['amount'] : null,
                        'extra_money' => isset($schedule_item['extra_money']) ? $schedule_item['extra_money'] : null,
                        'trans_corp' => isset($schedule_item['trans_corp']) ? $schedule_item['trans_corp'] : null,
                        'trans_no' => isset($schedule_item['trans_no']) && $schedule_item['trans_no'] != '' ? $schedule_item['trans_no'] : null,
                        'trans_who' => isset($schedule_item['trans_who']) ? $schedule_item['trans_who'] : null,
                        'prepay_price' => isset($schedule_item['prepay_price']) ? $schedule_item['prepay_price'] : null,
                        'gift' => isset($schedule_item['gift']) ? $schedule_item['gift'] : null,
                        'hold' => isset($schedule_item['hold']) ? $schedule_item['hold'] : null,
                        'org_seq' => isset($schedule_item['org_seq']) ? $schedule_item['org_seq'] : null,
                        'deal_no' => isset($schedule_item['deal_no']) ? $schedule_item['deal_no'] : null,
                        'sub_domain' => isset($schedule_item['sub_domain']) ? $schedule_item['sub_domain'] : null,
                        'sub_domain_seq' => isset($schedule_item['sub_domain_seq']) ? $schedule_item['sub_domain_seq'] : null,
                    ];
                    // if($i_schedule == "610001"){
                    //     return $data_schedule;
                    // }
                    if (isset($schedule_item['order_id'])) $ss_no = ScheduleShipment::updateOrCreate(['order_id' => $i_schedule], $data_schedule);

                    if($ss_no->ss_no){
                        $text = $ss_no->status == '출고' ? "EWC" : "EW";
                        $w_schedule_number = (new CommonFunc)->generate_w_schedule_number_service2($ss_no->ss_no, $text, $ss_no->created_at);
                        ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                            'w_schedule_number' => $w_schedule_number
                        ]);
                    }

                    if ($ss_no->ss_no && isset($schedule_item['order_products'])) {
                        $check_fisrt = 0;
                        foreach ($schedule_item['order_products'] as $ss_info => $schedule_info) {
                            if (!empty($ss_no->ss_no)) {
                                $ss_info_no = ScheduleShipmentInfo::updateOrCreate([
                                    'ss_no' => $ss_no->ss_no,
                                    'prd_seq' => $schedule_info['prd_seq']
                                ], [
                                    'ss_no' => $ss_no->ss_no,
                                    'co_no' => $user->co_no,
                                    'barcode' => isset($schedule_info['barcode']) ? $schedule_info['barcode'] : null,
                                    'brand' => isset($schedule_info['brand']) ? $schedule_info['brand'] : null,
                                    'cancel_date' => !empty($schedule_info['cancel_date']) ? $schedule_info['cancel_date'] : null,
                                    'change_date' => !empty($schedule_info['change_date']) ? $schedule_info['change_date'] : null,
                                    'enable_sale' => isset($schedule_info['enable_sale']) ? $schedule_info['enable_sale'] : null,
                                    'extra_money' => isset($schedule_info['extra_money']) ? $schedule_info['extra_money'] : null,
                                    'is_gift' => isset($schedule_info['is_gift']) ? $schedule_info['is_gift'] : null,
                                    'link_id' => isset($schedule_info['link_id']) ? $schedule_info['link_id'] : null,
                                    'name' => isset($schedule_info['name']) ? $schedule_info['name'] : null,
                                    'new_link_id' => isset($schedule_info['new_link_id']) ? $schedule_info['new_link_id'] : null,
                                    'options' => isset($schedule_info['options']) ? $schedule_info['options'] : null,
                                    'order_cs' => isset($schedule_info['order_cs']) ? $schedule_info['order_cs'] : null,
                                    'prd_amount' => isset($schedule_info['prd_amount']) ? $schedule_info['prd_amount'] : null,
                                    'prd_seq' => isset($schedule_info['prd_seq']) ? $schedule_info['prd_seq'] : null,
                                    'prd_supply_price' => isset($schedule_info['prd_supply_price']) ? $schedule_info['prd_supply_price'] : null,
                                    'product_id' => isset($schedule_info['product_id']) ? $schedule_info['product_id'] : null,
                                    'qty' => isset($schedule_info['qty']) ? $schedule_info['qty'] : null,
                                    'shop_price' => isset($schedule_info['shop_price']) ? $schedule_info['shop_price'] : null,
                                    'supply_code' => isset($schedule_info['supply_code']) ? $schedule_info['supply_code'] : null,
                                    'supply_name' => isset($schedule_info['supply_name']) ? $schedule_info['supply_name'] : null,
                                    'supply_options' => isset($schedule_info['supply_options']) ? $schedule_info['supply_options'] : null,
                                ]);





                                $dt_update = [];
                                if ($schedule_info['product_id']) {
                                    if (str_contains($schedule_info['product_id'], 'S')) {
                                        $shop_option_id = $schedule_info['product_id'];
                                        $shop_product_id = Item::where('option_id', $shop_option_id)->select('product_id')->first();
                                        $shop_product_id = isset($shop_product_id->product_id) ? $shop_product_id->product_id : '';
                                    } else {
                                        $shop_product_id = $schedule_info['product_id'];
                                        $shop_option_id = '';
                                    }

                                    if ($check_fisrt == 0) {
                                        if (!empty($shop_product_id)) {
                                            $dt_update['shop_product_id'] = $shop_product_id;
                                        }
                                        if (!empty($shop_option_id)) {
                                            $dt_update['shop_option_id'] = $shop_option_id;
                                        }
                                    }

                                    ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update($dt_update);
                                    $check_fisrt = 1;
                                }
                            }
                        }

                        $check = ScheduleShipmentInfo::where('ss_no', $ss_no->ss_no)->whereNotNull("order_cs")->get();
                        // if($ss_no->ss_no == "3429"){
                        //     return count($check);
                        // }
                        
                        if(isset($schedule_item['status']) && $schedule_item['status'] == 8){
                            
            
                            $order_cs_status = "출고예정 취소";
                            $number = array("1","2");
                            
                            foreach ($check as $key => $c) {
                                if($order_cs_status == "출고예정 취소"){
                                    if(!in_array($c->order_cs, $number)){
                                        $order_cs_status = "출고";
                                    }
                                }
                            }
                        }else{
                            $order_cs_status = "출고예정 취소";
                            $number = array("1","2");
                            
                            foreach ($check as $key => $c) {
                                if($order_cs_status == "출고예정 취소"){
                                    if(!in_array($c->order_cs, $number)){
                                        $order_cs_status = "출고예정";
                                    }
                                }
                            }
                        }
                        
                            //foreach ($check as $key => $c) {       
                                    // if ($order_cs_status == "출고") {
                                    //     if ($c == end($check)) {
                                    //         if ($c->order_cs == "1") {
                                    //             $order_cs_status = "출고예정";
                                    //         } else if ($c->order_cs == "2") { 
                                    //             $order_cs_status = "출고예정 취소";
                                    //         }
                                    //     }
                                    // }
                            
                                    // if ($order_cs_status == "출고예정") {
                                    //     if (count($check) > 1 && ($c->order_cs == "1" || $c->order_cs == "2" || $c->order_cs == "7")) {
                                    //         $order_cs_status = "출고예정 취소";
                                    //     } else {
                                    //         if ($c->order_cs == "1") {
                                    //             $order_cs_status = "출고";
                                    //         } else if ($c->order_cs == "2") { 
                                    //             $order_cs_status = "출고예정 취소";
                                    //         }
                                    //     }
                                    // }
                            //}
                        // }else{
                        //     $order_cs_status = "정상";

                        //     foreach ($check as $key => $c) {

                        //         // if ($order_cs_status == "정상") {
                        //         //     if ($c == end($check)) {
                        //         //         if ($c->order_cs == "1") {
                        //         //             $order_cs_status = "전체취소";
                        //         //         } else if ($c->order_cs == "2") { 
                        //         //             $order_cs_status = "부분취소";
                        //         //         }
                        //         //     }
                        //         // }
                        
                        //         if ($order_cs_status == "정상") {
                        //             if (count($check) > 1 && ($c->order_cs == "1" || $c->order_cs == "2" || $c->order_cs == "7")) {
                        //                 $order_cs_status = "부분취소";
                        //             }else{
                        //                 if ($c->order_cs == "1") {
                        //                     $order_cs_status = "전체취소";
                        //                 } else if ($c->order_cs == "2") { 
                        //                     $order_cs_status = "부분취소";
                        //                 }
                        //             }
                        //         }
                        //     }
                        // }
                        

                        ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                            'order_cs_status' => $order_cs_status
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019, 'status' => 0], 500);
        }
    }
    public function apiScheduleShipmentsRawNoLogin($data_schedule = null)
    {
        try {
            DB::beginTransaction();

            foreach ($data_schedule as $i_schedule => $schedule) {
                $data_schedule = [
                    'co_no' => 136,
                    'seq' => isset($schedule['seq']) ? $schedule['seq'] : null,
                    'pack' => isset($schedule['pack']) ? $schedule['pack'] : null,
                    'shop_code' => isset($schedule['shop_id']) ? $schedule['shop_id'] : null,
                    'shop_name' => isset($schedule['shop_name']) ? $schedule['shop_name'] : null,
                    'order_id' => isset($schedule['order_id']) ? $schedule['order_id'] : null,
                    'order_id_seq' => isset($schedule['order_id_seq']) ? $schedule['order_id_seq'] : null,
                    'order_id_seq2' => isset($schedule['order_id_seq2']) ? $schedule['order_id_seq2'] : null,
                    'shop_product_id' => isset($schedule['shop_product_id']) ? $schedule['shop_product_id'] : null,
                    'product_name' => isset($schedule['product_name']) ? $schedule['product_name'] . '외 ' . (count($schedule['order_products']) - 1) . '건' : null,
                    'options' => isset($schedule['options']) ? $schedule['options'] : null,
                    'qty' => isset($schedule['qty']) ? $schedule['qty'] : null,
                    'order_name' => isset($schedule['order_name']) ? $schedule['order_name'] : null,
                    'order_mobile' => isset($schedule['order_mobile']) ? $schedule['order_mobile'] : null,
                    'order_tel' => isset($schedule['order_tel']) ? $schedule['order_tel'] : null,
                    'recv_name' => isset($schedule['recv_name']) ? $schedule['recv_name'] : null,
                    'recv_mobile' => isset($schedule['recv_mobile']) ? $schedule['recv_mobile'] : null,
                    'recv_tel' => isset($schedule['recv_tel']) ? $schedule['recv_tel'] : null,
                    'recv_address' => isset($schedule['recv_address']) ? $schedule['recv_address'] : null,
                    'recv_zip' => isset($schedule['recv_zip']) ? $schedule['recv_zip'] : null,
                    'memo' => isset($schedule['memo']) ? $schedule['memo'] : null,
                    'status' => isset($schedule['status']) && $schedule['status'] == 8 ? '출고' : '출고예정',
                    'delivery_status' => '택배',
                    'w_category_name' => '수입풀필먼트',
                    'order_cs' => isset($schedule['order_cs']) ? $schedule['order_cs'] : null,
                    'collect_date' => isset($schedule['collect_date']) ? $schedule['collect_date'] : null,
                    'order_date' => isset($schedule['order_date']) ? ($schedule['order_date'] != '0000-00-00 00:00:00' ? $schedule['order_date'] : null) : null,
                    'trans_date' => !empty($schedule['trans_date']) ? $schedule['trans_date'] : null,
                    'trans_date_pos' => !empty($schedule['trans_date_pos']) ? $schedule['trans_date_pos'] : null,
                    'shopstat_date' => isset($schedule['shopstat_date']) ? $schedule['shopstat_date'] : null,
                    'supply_price' => isset($schedule['supply_price']) ? $schedule['supply_price'] : null,
                    'amount' => isset($schedule['amount']) ? $schedule['amount'] : null,
                    'extra_money' => isset($schedule['extra_money']) ? $schedule['extra_money'] : null,
                    'trans_corp' => isset($schedule['trans_corp']) ? $schedule['trans_corp'] : null,
                    'trans_no' => isset($schedule['trans_no']) ? '출고' : '출고예정',
                    'trans_who' => isset($schedule['trans_who']) ? $schedule['trans_who'] : null,
                    'prepay_price' => isset($schedule['prepay_price']) ? $schedule['prepay_price'] : null,
                    'gift' => isset($schedule['gift']) ? $schedule['gift'] : null,
                    'hold' => isset($schedule['hold']) ? $schedule['hold'] : null,
                    'org_seq' => isset($schedule['org_seq']) ? $schedule['org_seq'] : null,
                    'deal_no' => isset($schedule['deal_no']) ? $schedule['deal_no'] : null,
                    'sub_domain' => isset($schedule['sub_domain']) ? $schedule['sub_domain'] : null,
                    'sub_domain_seq' => isset($schedule['sub_domain_seq']) ? $schedule['sub_domain_seq'] : null,
                ];
                $ss_no = ScheduleShipment::updateOrCreate(['order_id' => $schedule['order_id']], $data_schedule);

                if($ss_no->ss_no){
                    $text = $ss_no->status == '출고' ? "EWC" : "EW";
                    $w_schedule_number = (new CommonFunc)->generate_w_schedule_number_service2($ss_no->ss_no, $text, $ss_no->created_at);
                    ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                        'w_schedule_number' => $w_schedule_number
                    ]);
                }


                if ($ss_no->ss_no && isset($schedule['order_products'])) {
                    $i_temp = 0;
                    if (isset($schedule['order_products'])) {
                        $check_fisrt = 0;
                        foreach ($schedule['order_products'] as $ss_info => $schedule_info) {
                            if ($i_temp == 0 && isset($schedule_info['product_id'])) {
                                if (str_contains($schedule_info['product_id'], 'S')) {
                                    $shop_option_id = $schedule_info['product_id'];
                                    ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                                        'shop_option_id' => $shop_option_id
                                    ]);
                                }
                                $i_temp = 1;
                            }
                            if (!empty($ss_no->ss_no) && !empty($schedule_info['barcode'])) {
                                $ss_info_no = ScheduleShipmentInfo::updateOrCreate([
                                    'ss_no' => $ss_no->ss_no,
                                    'prd_seq' => $schedule_info['prd_seq']
                                ], [
                                    'ss_no' => $ss_no->ss_no,
                                    'co_no' => null,
                                    'barcode' => isset($schedule_info['barcode']) ? $schedule_info['barcode'] : null,
                                    'brand' => isset($schedule_info['brand']) ? $schedule_info['brand'] : null,
                                    'cancel_date' => !empty($schedule_info['cancel_date']) ? $schedule_info['cancel_date'] : null,
                                    'change_date' => !empty($schedule_info['change_date']) ? $schedule_info['change_date'] : null,
                                    'enable_sale' => isset($schedule_info['enable_sale']) ? $schedule_info['enable_sale'] : null,
                                    'extra_money' => isset($schedule_info['extra_money']) ? $schedule_info['extra_money'] : null,
                                    'is_gift' => isset($schedule_info['is_gift']) ? $schedule_info['is_gift'] : null,
                                    'link_id' => isset($schedule_info['link_id']) ? $schedule_info['link_id'] : null,
                                    'name' => isset($schedule_info['name']) ? $schedule_info['name'] : null,
                                    'new_link_id' => isset($schedule_info['new_link_id']) ? $schedule_info['new_link_id'] : null,
                                    'options' => isset($schedule_info['options']) ? $schedule_info['options'] : null,
                                    'order_cs' => isset($schedule_info['order_cs']) ? $schedule_info['order_cs'] : null,
                                    'prd_amount' => isset($schedule_info['prd_amount']) ? $schedule_info['prd_amount'] : null,
                                    'prd_seq' => isset($schedule_info['prd_seq']) ? $schedule_info['prd_seq'] : null,
                                    'prd_supply_price' => isset($schedule_info['prd_supply_price']) ? $schedule_info['prd_supply_price'] : null,
                                    'product_id' => isset($schedule_info['product_id']) ? $schedule_info['product_id'] : null,
                                    'qty' => isset($schedule_info['qty']) ? $schedule_info['qty'] : null,
                                    'shop_price' => isset($schedule_info['shop_price']) ? $schedule_info['shop_price'] : null,
                                    'supply_code' => isset($schedule_info['supply_code']) ? $schedule_info['supply_code'] : null,
                                    'supply_name' => isset($schedule_info['supply_name']) ? $schedule_info['supply_name'] : null,
                                    'supply_options' => isset($schedule_info['supply_options']) ? $schedule_info['supply_options'] : null,
                                ]);

                                $check = ScheduleShipmentInfo::where('ss_no', $ss_no->ss_no)->where('barcode', $schedule_info['barcode'])->get();

                                if(isset($schedule['status']) && $schedule['status'] == 8){
                            
            
                                    $order_cs_status = "출고예정 취소";
                                    $number = array("1","2");
                                    
                                    foreach ($check as $key => $c) {
                                        if($order_cs_status == "출고예정 취소"){
                                            if(!in_array($c->order_cs, $number)){
                                                $order_cs_status = "출고";
                                            }
                                        }
                                    }
                                }else{
                                    $order_cs_status = "출고예정 취소";
                                    $number = array("1","2");
                                    
                                    foreach ($check as $key => $c) {
                                        if($order_cs_status == "출고예정 취소"){
                                            if(!in_array($c->order_cs, $number)){
                                                $order_cs_status = "출고예정";
                                            }
                                        }
                                    }
                                }

                                ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                                    'order_cs_status' => $order_cs_status
                                ]);

                                if ($check_fisrt == 0 && $schedule_info['product_id']) {
                                    if (str_contains($schedule_info['product_id'], 'S')) {
                                        $shop_option_id = $schedule_info['product_id'];
                                        $shop_product_id = Item::where('option_id', $shop_option_id)->select('product_id')->first();
                                        $shop_product_id = isset($shop_product_id->product_id) ? $shop_product_id->product_id : '';
                                    } else {
                                        $shop_product_id = $schedule_info['product_id'];
                                        $shop_option_id = '';
                                    }
                                    $check = ScheduleShipment::where(['ss_no' => $ss_no->ss_no])->update([
                                        'shop_product_id' => isset($shop_product_id) ? $shop_product_id : '',
                                        'shop_option_id' => isset($shop_option_id) ? $shop_option_id : ''
                                    ]);
                                    $check_fisrt = 1;
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'message' => '완료되었습니다.',
                'status' => 1
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return $e;
            return response()->json(['message' => Messages::MSG_0019, 'status' => 0], 500);
        }
    }
    public function getScheduleShipmentById($ss_no)
    {
        DB::statement("SET SQL_MODE=''");
        $schedule_shipment_item = ScheduleShipmentInfo::with(['file'])->select('schedule_shipment_info.*', 'item.product_id as product_id', 'schedule_shipment_info.product_id as option_id')->leftjoin('item', 'schedule_shipment_info.product_id', '=', 'item.option_id')->where('schedule_shipment_info.ss_no', $ss_no)->whereNotNull('schedule_shipment_info.order_cs')->get();
        $schedule_shipment = ScheduleShipment::with(['schedule_shipment_info', 'receving_goods_delivery'])
        ->where(function ($query) {
            $query->whereHas('receving_goods_delivery', function ($q) {
                $q->whereNull('rgd_monthbill_start');
            })->ordoesntHave('receving_goods_delivery');
        })->where('ss_no', $ss_no)->get();
        $collect_test = array();
        if (!empty($schedule_shipment) && !empty($schedule_shipment_item)) {
            $collect_test = collect($schedule_shipment)->map(function ($item) use ($schedule_shipment_item) {
                $item->item2 = isset($schedule_shipment_item) ? $schedule_shipment_item : array();
                // $text = $item->status == '출고' ? "EWC" : "EW";
                // $item->w_schedule_number = (new CommonFunc)->generate_w_schedule_number_service2($item->ss_no, $text, $item->created_at);
                return $item;
            });
            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $collect_test,
                    'schedule_shipment_item' => $schedule_shipment_item
                ],
                200
            );
        } else {
            return response()->json(['message' => CommonFunc::renderMessage(Messages::MSG_0016, ['ScheduleShipment'])], 400);
        }
    }
    public function deleteScheduleShipmentInfo(ScheduleShipmentInfo $scheduleShipmentInfo)
    {
        try {
            $scheduleShipmentInfo->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    public function deleteScheduleShipment(ScheduleShipment $scheduleShipment)
    {
        try {
            $scheduleShipment->delete();
            return response()->json([
                'message' => Messages::MSG_0007,
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }
    public function CreateOrUpdateByCoPu(ScheduleShipmentRequest $request)
    {
        $validated = $request->validated();
        try {
            DB::beginTransaction();
            //$ssi_no = $request->get('ssi_no');

            if (isset($validated['co_no'])) {
                if (isset($validated['schedule_shipment_info'])) {
                    foreach ($validated['schedule_shipment_info'] as $ssi) {
                        $co_no = $request->get('co_no');
                        ScheduleShipmentInfo::updateOrCreate(
                            [
                                'ssi_no' => $ssi['ssi_no'] ?: null,
                            ],
                            [
                                'co_no' => $co_no,
                                'supply_code' => ($ssi['supply_code'] && $ssi['supply_code'] != 'null') ? $ssi['supply_code']  : null,
                                'supply_name' => ($ssi['supply_name'] && $ssi['supply_name'] != 'null') ? $ssi['supply_name']  : null,
                            ]
                        );
                    }
                }
                if (isset($validated['schedule_shipment'])) {
                    foreach ($validated['schedule_shipment'] as $ss) {
                        $co_no = $request->get('co_no');
                        ScheduleShipment::updateOrCreate(
                            [
                                'ss_no' => ($ss['ss_no'] &&  $ss['ss_no'] != 'undefined') ?  $ss['ss_no'] : null,
                            ],
                            [
                                'co_no' => $co_no,
                                'shop_code' => ($ss['shop_code'] && $ss['shop_code'] != 'null') ? $ss['shop_code'] : null,
                                'shop_name' => ($ss['shop_name'] && $ss['shop_name'] != 'null') ? $ss['shop_name'] : null,
                            ]
                        );
                    }
                }
            }



            DB::commit();
            return response()->json([
                'message' => Messages::MSG_0007,
                'co_no' => $co_no ? $co_no : ($ssi ? $ssi->co_no : null),
                '$validated' => isset($validated['co_no']) ? $validated['co_no'] : ''
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0019], 500);
        }
    }
    public function getScheduleShipmentInfoByCono($request)
    {
        try {
            $schedule_shipment_info = ScheduleShipmentInfo::where('co_no', '=', $request)->get();
            $schedule_shipment = ScheduleShipment::where('co_no', '=', $request)->get();

            return response()->json(
                [
                    'message' => Messages::MSG_0007,
                    'data' => $schedule_shipment_info,
                    'data2' => $schedule_shipment
                ],
                200
            );
        } catch (\Exception $e) {
            Log::error($e);

            return response()->json(['message' => Messages::MSG_0006], 500);
        }
    }

    public function requestDataAPI($filter = array())
    {
        $url_api = "https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?";
        foreach ($filter as $key => $param) {
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
        //return $url_api;
        $api_data = json_decode($response, 1);
        return $api_data;
    }

    public function mapDataAPI($data_maps = array())
    {
        $data_temp = array();
        $product_infos = Item::all()->groupBy('product_id');
        foreach ($data_maps as $data_item) {
            $order_products_data = array();
            $order_products = array();
            if (!empty($data_item['order_products'])) {
                $total_qty = 0;
                $option_first_id = '';
                foreach ($data_item['order_products'] as $key => $order) {
                    if (!empty($order)) {
                        $order_products_data[] = array(
                            'product_id' => $order['product_id'],
                            'option_id' => isset($product_infos[$order['product_id']][0]['option_id']) ? $product_infos[$order['product_id']][0]['option_id'] : '',
                            'name' => $order['name'],
                            'options' => $order['options'],
                            'qty' => $order['qty'],
                            'brand' => $order['brand'],
                            'barcode' => $order['barcode']
                        );
                        $total_qty += $order['qty'];
                    }
                    if ($option_first_id == '' && isset($product_infos[$order['product_id']][0]['option_id'])) {
                        $option_first_id = isset($product_infos[$order['product_id']][0]['option_id']) ? $product_infos[$order['product_id']][0]['option_id'] : '';
                    }
                }
                $data_item['qty'] = $total_qty;
                $data_item['shop_option_id'] = $option_first_id;
            }
            $order_products[] = $data_item['order_products'];
            $data_temp[$data_item['order_id']]['data_item'][] = $data_item;
            $data_temp[$data_item['order_id']]['order_products'] = $order_products_data;
        }
        return array(
            'order_products' => $order_products,
            'data_temp' => $data_temp
        );
    }

    public function getScheduleFromApi(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_order_info',
            'date_type' => 'collect_date',
            'start_date' => '2022-05-01', //date('Y-m-d')
            'end_date' => date('Y-m-d'),
            'limit' => 100,
            'page' => '1'
        );
        $base_schedule_datas = $this->requestDataAPI($param_arrays); //Get Data

        $total_data = (isset($base_schedule_datas['total']) && $base_schedule_datas['total'] > 0) ? $base_schedule_datas['total'] : 0;
        $limit_data = (isset($base_schedule_datas['limit']) && $base_schedule_datas['limit'] > 0) ? $base_schedule_datas['limit'] : 0;
        $check_pages = ($total_data > $limit_data) && $limit_data > 0 ? (int)ceil($total_data / $limit_data) : 1; // Check total page to foreach;

        $test = [];
        if (isset($check_pages) && $check_pages > 1) {
            for ($page = 1; $page <= $check_pages; $page++) {
                $param_arrays['page'] = $page;
                $base_schedule_datas = $this->requestDataAPI($param_arrays); //Get Data
                $test[] = $base_schedule_datas;
                $data_schedule = $this->mapDataAPI($base_schedule_datas['data']);
                // if($page == 3)
                //     return $data_schedule['data_temp'];
                if (!empty($data_schedule['data_temp'])) {
                    //if($page == 9)
                    $this->apiScheduleShipmentsRaw($data_schedule['data_temp']);
                }
            }
            return response()->json([
                'param' => $test,
                'message' => '완료되었습니다.',
                'status' => 1
            ], 200);
        } else {
            if (!empty($base_schedule_datas['data'])) {
                $data_schedule = $this->mapDataAPI($base_schedule_datas['data']);
                if (!empty($data_schedule['data_temp'])) {
                    $this->apiScheduleShipmentsRaw($data_schedule['data_temp']);
                }
                return response()->json([
                    'param' => $test,
                    'message' => '완료되었습니다.',
                    'status' => 1
                ], 200);
            } else {
                return response()->json([
                    'param' => $test,
                    'message' => '완료되었습니다.',
                    'status' => 0
                ], 200);
            }
        }
    }

    public function getScheduleFromApiNoLogin(Request $request)
    {
        $param_arrays = array(
            'partner_key' => '50e2331771d085ddccbcd2188a03800c',
            'domain_key' => '50e2331771d085ddeab1bc2f91a39ae14e1b924b8df05d11ff40eea3aff3d9fb',
            'action' => 'get_order_info',
            'date_type' => 'collect_date',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d'),
            'limit' => 100,
            'page' => ''
        );
        $filter = array();
        $url_api = "https://api2.cloud.ezadmin.co.kr/ezadmin/function.php?";
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
        $total_schedule = isset($response['total']) ? $response['total'] : 0;
        $pages = ($total_schedule > $filter['limit']) ? ceil($total_schedule / $filter['limit']) : 1;
        $data_temp = array();
        if ($pages > 1) {
            for ($page = 1; $page <= $pages; $page++) {
                $url_api .= '&page=' . $page;
                $response = file_get_contents($url_api);
                $api_data = json_decode($response, 1);
                if (!empty($api_data['data'])) {
                    $order_products = array();
                    foreach ($api_data['data'] as $data_item) {
                        $order_products_data = array();
                        if (!empty($data_item['order_products'])) {
                            $total_qty = 0;
                            foreach ($data_item['order_products'] as $key => $order) {
                                if (!empty($order)) {
                                    $order_products_data[] = array(
                                        'product_id' => $order['product_id'],
                                        'name' => $order['name'],
                                        'options' => $order['options'],
                                        'qty' => $order['qty'],
                                        'brand' => $order['brand'],
                                        'barcode' => $order['barcode']
                                    );
                                    $total_qty += $order['qty'];
                                }
                            }
                            $data_item['qty'] = $total_qty;
                        }
                        $order_products[] = $data_item['order_products'];
                        $data_temp[$data_item['order_id']] = $data_item;
                        $data_temp[$data_item['order_id']]['order_products'] = $order_products_data;
                    }

                    return $this->apiScheduleShipmentsRawNoLogin($data_temp);
                }
            }
        } else {
            $url_api .= '&page=1';
            $response = file_get_contents($url_api);
            $api_data = json_decode($response, 1);
            if (!empty($api_data['data'])) {
                $order_products = array();
                foreach ($api_data['data'] as $data_item) {
                    $order_products_data = array();
                    if (!empty($data_item['order_products'])) {
                        $total_qty = 0;
                        foreach ($data_item['order_products'] as $key => $order) {
                            if (!empty($order)) {
                                $order_products_data[] = array(
                                    'product_id' => $order['product_id'],
                                    'name' => $order['name'],
                                    'options' => $order['options'],
                                    'qty' => $order['qty'],
                                    'brand' => $order['brand'],
                                    'barcode' => $order['barcode']
                                );
                                $total_qty += $order['qty'];
                            }
                        }
                        $data_item['qty'] = $total_qty;
                    }
                    $order_products[] = $data_item['order_products'];
                    $data_temp[$data_item['order_id']] = $data_item;
                    $data_temp[$data_item['order_id']]['order_products'] = $order_products_data;
                }
                return $this->apiScheduleShipmentsRawNoLogin($data_temp);
            }
        }
        if (empty($api_data['data'])) {
            return response()->json([
                //'param' => $url_api,
                'message' => '완료되었습니다.',
                'status' => 0
            ], 200);
        }
        return $api_data;
    }

    public function stock_history()
    {
        $contract_wms = ContractWms::with(['company', 'item'])->where('cw_tab', '공급처')->get();
        $data_stock = array();
        $data_company = array();

        foreach ($contract_wms as $contractWms) {
            $data_company[] = $contractWms->company;
            $total = 0;
            foreach ($contractWms->item as $item) {
                $stock  = StockStatusBad::where('product_id', $item->product_id)->where('item_no', $item->item_no)->where('status', '0')->where(DB::raw("(DATE_FORMAT(created_at,'%Y-%m-%d'))"), "=", Carbon::now()->format('Y-m-d'))->first();
                $total = $total + ($stock != null ? $stock->stock : 0);
            }
            $data_stock[] =  $total;
            //$stock_check = StockHistory::where('mb_no',$contractWms->company->member->mb_no)->first();

            //if($stock_check->sh_date){
            StockHistory::insertGetId([
                'mb_no' => $contractWms->company->member->mb_no,
                'sh_date' => Carbon::now()->format('Y-m-d'),
                'sh_left_stock' => $total,
            ]);
            // }
        }
        return response()->json([
            'message' => '완료되었습니다.',
            'status' => 1
        ], 200);
    }
}
