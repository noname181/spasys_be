<?php

namespace App\Http\Requests\ReceivingGoodsDelivery;

use App\Http\Requests\BaseFormRequest;

class ReceivingGoodsDeliveryCreateRequest extends BaseFormRequest
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
            'connection_number' => [
                ''
            ], 
            'connect_w' => [
                ''
            ], 
            'type_w_choose' => [
                ''
            ], 
            'is_no' => [
                ''
            ], 
            'w_no' => [
                '',
            ],
            'co_no' => [
                '',
            ],
            'page_type' => [
                '',
            ],
            'wr_contents' => [
                '',
            ],
            'w_schedule_amount' =>[
                '',   
            ],
            'w_amount' =>[
                '',   
            ],
            'w_schedule_number' =>[
                '',   
            ],
            'w_schedule_day' => [
                ''
            ],
            'remove' => [
                'array',
            ],
            'items.*.item_no' => [
                '',
            ],
            'items.*.wi_number' => [
                '',
            ],
            'items.*.item_brand' => [
                '',
            ],
            'items.*.item_name' => [
                '',
            ],
            'items.*.item_option1' => [
                '',
            ],
            'items.*.item_option2' => [
                '',
            ],
            'items.*.item_price3' => [
                '',
            ],
            'items.*.item_price4' => [
                '',
            ],
            'items.*.warehousing_item.*.wi_number' => [
                '',
            ],
            'items.*.warehousing_item.*.wi_number_received' => [
                '',
            ],
            'items.*.warehousing_item.*.wi_no' => [
                '',
            ],
            'items.*.warehousing_item2.*.wi_number' => [
                '',
            ],
            'items.*.warehousing_item2.*.wi_number_received' => [
                '',
            ],
            'items.*.warehousing_item2.*.wi_no' => [
                '',
            ],
            'location.*.rgd_no' => [
                '',
            ],
            'location.*.is_no' => [
                '',
            ],
            'location.*.w_no' => [
                '',
            ],
           
            'location.*.rgd_contents' => [
                'nullable',
                'string',
                'max:255',
            ],
            'location.*.rgd_address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'location.*.rgd_address_detail' => [
                'nullable',
                'string',
                'max:255',
            ],
            'location.*.rgd_receiver' => [
                'nullable',
                'string',
                'max:255',
            ],            
            'location.*.rgd_hp' => [
                'nullable',
                'string',
                'max:255',
            ],
            'location.*.rgd_memo' => [
                'nullable',
                'string',
            ],
            'location.*.rgd_status1' => [
                '',
            ],
            'location.*.rgd_status2' => [
               ''
            ],
            'location.*.rgd_status3' => [
                ''
            ],
            'location.*.rgd_status4' => [
                ''
            ],
            'location.*.rgd_delivery_company' => [
                '',
            ],
            'location.*.rgd_tracking_code' => [
                '',
                '',
                'max:255',
            ],
            'location.*.rgd_delivery_man' => [
                '',
                '',
                'max:255',
            ],
            'location.*.rgd_delivery_man_hp' => [
                '',
                '',
                'max:255',
            ],
            'location.*.rgd_delivery_schedule_day' => [
                'nullable'
            ],
            'location.*.rgd_arrive_day' => [
                'nullable'
            ],

            'package' => [
                'nullable'
            ],
            'package.*.note' => [
                'nullable'
            ],
            'package.*.order_number' => [
                'nullable'
            ],
            'package.*.pack_type' => [
                'nullable'
            ],
            'package.*.quantity' => [
                'nullable'
            ],
            'package.*.reciever' => [
                'nullable'
            ],
            'package.*.reciever_address' => [
                'nullable'
            ],
            'package.*.reciever_contract' => [
                'nullable'
            ],
            'package.*.reciever_detail_address' => [
                'nullable'
            ],
            'package.*.sender' => [
                'nullable'
            ],
            'package.*.sender_address' => [
                'nullable'
            ],
            'package.*.sender_contract' => [
                'nullable'
            ],
            'package.*.sender_detail_address' => [
                'nullable'
            ],

            'item_new' => [
                'nullable',
                'array'
            ],
            
            'item_new.*.item_brand' => [
                'nullable'
            ],
            
            'item_new.*.item_name' => [
                'nullable'
            ],
            
            'item_new.*.item_option1' => [
                'nullable'
            ],
            
            'item_new.*.item_option2' => [
                'nullable'
            ],
            
            'item_new.*.item_channel_name' => [
                'nullable'
            ],
            
            'item_new.*.item_channel_code' => [
                'nullable'
            ],

            'item_new.*.item_price3' => [
                'nullable'
            ],

            'item_new.*.item_price4' => [
                'nullable'
            ],

            'item_new.*.wi_number' => [
                'nullable'
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
