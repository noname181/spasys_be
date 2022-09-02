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
            'w_no' => [
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
            'items.*.warehousing_item.wi_number' => [
                '',
            ],
            'items.*.warehousing_item.wi_number_received' => [
                '',
            ],
            'items.*.warehousing_item.wi_no' => [
                '',
            ],
            'items.*.warehousing_item2.wi_number' => [
                '',
            ],
            'items.*.warehousing_item2.wi_number_received' => [
                '',
            ],
            'items.*.warehousing_item2.wi_no' => [
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
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_address' => [
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_receiver' => [
                'required',
                'string',
                'max:255',
            ],            
            'location.*.rgd_hp' => [
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_memo' => [
                'required',
                'string',
            ],
            'location.*.rgd_status1' => [
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_status2' => [
               ''
            ],
            'location.*.rgd_status3' => [
                'required',
                'string',
                'max:255',
            ],
            'location.*.rgd_delivery_company' => [
                'required',
                'string',
                'max:255',
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
                'required',
                'date_format:Y-m-d'
            ],
            'location.*.rgd_arrive_day' => [
                'required',
                'date_format:Y-m-d'
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
