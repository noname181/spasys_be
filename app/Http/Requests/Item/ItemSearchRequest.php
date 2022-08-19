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
            'from_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
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
