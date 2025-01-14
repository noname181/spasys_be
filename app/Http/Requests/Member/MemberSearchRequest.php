<?php

namespace App\Http\Requests\Member;

use App\Http\Requests\BaseFormRequest;

class MemberSearchRequest extends BaseFormRequest
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
            'co_name' => [
                'string',
                'nullable',
            ],
            'shop_name' => [
                'string',
                'nullable',
            ],
            'mb_name' => [
                'string',
                'nullable',
            ],
            'mb_id' => [
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
            'co_parent_name' => [
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
