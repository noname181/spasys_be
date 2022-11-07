<?php

namespace App\Http\Requests\ScheduleShipment;

use App\Http\Requests\BaseFormRequest;

class ScheduleShipmentSearchRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'item_no' => [
                'nullable',
                'int',
            ],
            'co_no' => [
                'nullable',
                'int',
            ],
            'shop_no' => [
                'nullable',
                'int',
            ],
            'seq' => [
                'string',
                'nullable',
                'max:255',
            ],
            'pack' => [
                'string',
                'nullable',
                'max:255',
            ],
            'shop_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id_seq' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id_seq2' => [
                'string',
                'nullable',
                'max:255',
            ],
            'shop_product_id' => [
                'string',
                'nullable',
                'max:255',
            ],
            'product_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'options' => [
                'string',
                'nullable',
                'max:255',
            ],
            'qty' => [
                'integer',
                'nullable',
            ],
            'order_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_mobile' => [
                'string',
                'nullable',
                'max:1000',
            ],
            'order_tel' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_mobile' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_tel' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_address' => [
                'string',
                'nullable',
                'max:1000',
            ],
            'recv_zip' => [
                'string',
                'nullable',
                'max:255',
            ],
            'memo' => [
                'string',
                'nullable',
            ],
          
            'order_cs' => [
                'int',
                'nullable',
            ],
            'collect_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'order_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'trans_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'trans_date_pos' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'shopstat_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'supply_price' => [
                'int',
                'nullable',
            ],
            'amount' => [
                'int',
                'nullable',
            ],
            'extra_money' => [
                'int',
                'nullable',
            ],

            'trans_corp' => [
                'string',
                'nullable',
                'max:255',
            ],
            'trans_no' => [
                'string',
                'nullable',
                'max:255',
            ],
            'trans_who' => [
                'string',
                'nullable',
                'max:255',
            ],
            'prepay_price' => [
                'int',
                'nullable',
            ],
            'gift' => [
                'string',
                'nullable',
                'max:255',
            ],
            'hold' => [
                'int',
                'nullable',
            ],
            'org_seq' => [
                'int',
                'nullable',
            ],
            'deal_no' => [
                'string',
                'nullable',
                'max:255',
            ],
            'sub_domain' => [
                'integer',
                'nullable',
            ],
            'sub_domain_seq' => [
                'integer',
                'nullable',
            ],
            'order_products' => [
                'integer',
                'nullable',
            ],
            'ssi_no' => [
                'int',
                'nullable',
            ],
            'ss_no' => [
                'int',
                'nullable',
            ],
            'schedule_shipment_info' => [
                'array',
                'nullable'
            ],
            'schedule_shipment_info.*.supply_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment_info.*.supply_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment' => [
                'array',
                'nullable'
            ],
            'schedule_shipment.*.shop_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment.*.shop_name' => [
                'string',
                'nullable',
                'max:255',
            ],
          
            'item_no' => [
                'nullable',
                'int',
            ],
            'co_no' => [
                'nullable',
                'int',
            ],
            'shop_no' => [
                'nullable',
                'int',
            ],
            'seq' => [
                'string',
                'nullable',
                'max:255',
            ],
            'pack' => [
                'string',
                'nullable',
                'max:255',
            ],
            'shop_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id_seq' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_id_seq2' => [
                'string',
                'nullable',
                'max:255',
            ],
            'shop_product_id' => [
                'string',
                'nullable',
                'max:255',
            ],
            'product_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'options' => [
                'string',
                'nullable',
                'max:255',
            ],
            'qty' => [
                'integer',
                'nullable',
            ],
            'order_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_mobile' => [
                'string',
                'nullable',
                'max:1000',
            ],
            'order_tel' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_mobile' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_tel' => [
                'string',
                'nullable',
                'max:255',
            ],
            'recv_address' => [
                'string',
                'nullable',
                'max:1000',
            ],
            'recv_zip' => [
                'string',
                'nullable',
                'max:255',
            ],
            'memo' => [
                'string',
                'nullable',
            ],
            'status' => [
                'string',
                'nullable',
                'max:255',
            ],
            'order_cs' => [
                'int',
                'nullable',
            ],
            'collect_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'order_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'trans_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'trans_date_pos' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'shopstat_date' => [
                'string',
                'date_format:Y-m-d H:i:s',
            ],
            'supply_price' => [
                'int',
                'nullable',
            ],
            'amount' => [
                'int',
                'nullable',
            ],
            'extra_money' => [
                'int',
                'nullable',
            ],

            'trans_corp' => [
                'string',
                'nullable',
                'max:255',
            ],
            'trans_no' => [
                'string',
                'nullable',
                'max:255',
            ],
            'trans_who' => [
                'string',
                'nullable',
                'max:255',
            ],
            'prepay_price' => [
                'int',
                'nullable',
            ],
            'gift' => [
                'string',
                'nullable',
                'max:255',
            ],
            'hold' => [
                'int',
                'nullable',
            ],
            'org_seq' => [
                'int',
                'nullable',
            ],
            'deal_no' => [
                'string',
                'nullable',
                'max:255',
            ],
            'sub_domain' => [
                'integer',
                'nullable',
            ],
            'sub_domain_seq' => [
                'integer',
                'nullable',
            ],
            'order_products' => [
                'integer',
                'nullable',
            ],
            'ssi_no' => [
                'int',
                'nullable',
            ],
            'ss_no' => [
                'int',
                'nullable',
            ],
            'schedule_shipment_info' => [
                'array',
                'nullable'
            ],
            'schedule_shipment_info.*.supply_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment_info.*.supply_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment' => [
                'array',
                'nullable'
            ],
            'schedule_shipment.*.shop_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'schedule_shipment.*.shop_name' => [
                'string',
                'nullable',
                'max:255',
            ],
            'per_page' => [
                'nullable',
                'int',
            ], 
            'page' => [
                'nullable',
                'int',
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'co_name' => [
                'nullable',
                'string',
            ],
            'co_parent_name' => [
                'nullable',
                'string',
            ],
            'item_brand' => [
                'nullable',
                'string',
            ],
            'item_channel_name' => [
                'nullable',
                'string',
            ],
            'item_name' => [
                'nullable',
                'string',
            ],
            'status' => [
                'string',
                'nullable',
                'max:255',
            ],
            'from_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'per_page' => [
                'nullable',
                'int',
            ], 
            'page' => [
                'nullable',
                'int',
            ],
        ];
    }

        /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }
}
