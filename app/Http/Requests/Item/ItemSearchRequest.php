<?php

namespace App\Http\Requests\Item;

use App\Http\Requests\BaseFormRequest;

class ItemSearchRequest extends BaseFormRequest
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
            'type' => [
                '',
            ],
            'keyword' => [
                '',
            ],
            'items' => [
                '',
            ],
            'w_no' => [
                '',
            ],
            'co_no' => [
                '',
            ],
            'from_date' => [
                'nullable',
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'nullable',
                'string',
                'date_format:Y-m-d'
            ],
            'search_string' => [
                'string',
                'nullable',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'co_name_shop' => [
                '',
            ],
            'co_name_agency' => [
                '',
            ],
            'item_name' => [
                '',
            ],
            'item_channel_code' => [
                '',
            ],
            'item_bar_code' => [
                '',
            ],
            'item_upc_code' => [
                '',
            ],
            'item_channel_name' => [
                '',
            ],
            'item_cargo_bar_code' => [
                '',
            ],
            'item_brand' => [
                '',
            ],
            'product_id' => [
                ''
            ],
            'item_data' => [
                ''
            ],
            'option_id' => [
                ''
            ],
            'status' => [
                ''
            ]
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
