<?php

namespace App\Http\Requests\ReceivingGoodsDelivery;

use App\Http\Requests\BaseFormRequest;

class ReceivingGoodsDeliveryCreateMobileRequest extends BaseFormRequest
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
            'wr_contents' => [
                '',
            ],
            'remove' => [
                '',
            ],
            'location.*.rgd_no' => [
                '',
            ],
            'location.*.is_no' => [
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
