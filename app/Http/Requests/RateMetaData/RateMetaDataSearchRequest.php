<?php

namespace App\Http\Requests\RateMetaData;

use App\Http\Requests\BaseFormRequest;

class RateMetaDataSearchRequest extends BaseFormRequest
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
                'nullable',
                'date'
            ],
            'to_date' => [
                'nullable',
                'date'
            ],
            'rm_biz_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'rm_biz_number' => [
                'nullable',
                'string',
                'max:255'
            ],
            'rm_owner_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'co_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'co_parent_name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'service' => [
                'nullable',
                'string',
            ],
            'c_transaction_yn' => [
                'nullable',
                'string',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'rd_cate_meta1' => [
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
