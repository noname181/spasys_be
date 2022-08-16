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
            'rgd_contents' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_address' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_address_detail' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_receiver' => [
                'required',
                'string',
                'max:255',
            ],            
            'rgd_hp' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_memo' => [
                'required',
                'string',
            ],
            'rgd_status1' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_status2' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_status3' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_delivery_company' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_tracking_code' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_delivery_man' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_delivery_man_hp' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_delivery_schedule_day' => [
                'required',
                'string',
                'max:255',
            ],
            'rgd_arrive_day' => [
                'required',
                'string',
                'max:255',
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
