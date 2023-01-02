<?php

namespace App\Http\Requests\Item;

use App\Http\Requests\BaseFormRequest;

class ItemRequest extends BaseFormRequest
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
            'item_brand' => [
                'required',
                'string',
                'max:255',
            ],
            'item_service_name' => [
                'required',
                'string',
                'max:255',
            ],
            'co_no' => [
                'nullable',
                'int',
            ],
            'item_name' => [
                'required',
                'string',
                'max:255',
            ],
            'item_option1' => [
                'required',
                'string',
                'max:255',
            ],
            'item_option2' => [
                'required',
                'string',
                'max:255',
            ],
            'item_cargo_bar_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_upc_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_bar_code' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_weight' => [
                'integer',
                'nullable',
            ],
            'item_price1' => [
                'nullable',
            ],
            'item_price2' => [
                'nullable',
            ],
            'item_price3' => [
                'nullable',
            ],
            'item_price4' => [
                'nullable',
            ],
            'item_cate1' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_cate2' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_cate3' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_url' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_channels' => [
                'array',
                'nullable'
            ],
            'item_channels.*.item_channel_name' => [
                'required',
                'string',
                'max:255',
            ],
            'item_channels.*.item_channel_code' => [
                'required',
                'string',
            ],
            'file' => [
                'file',
                'nullable',
                'max:5000',
                'mimes:jpg,jpeg,png'
            ],
            'item_origin' => [
                'string',
                'nullable',
                'max:255',
            ],
            'item_manufacturer' => [
                'string',
                'nullable',
                'max:255',
            ],
            'product_id' => [
                '',
            ],
            'item_no' => [
                '',
            ],
            'supply_code' => [
                '',
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
    public function messages()
    {
        return [
            'file.mimes' => 'jpg, jpeg, png 파일만 업로드 할 수 있습니다'
        ];
    }
}
