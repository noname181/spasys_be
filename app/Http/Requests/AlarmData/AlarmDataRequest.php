<?php

namespace App\Http\Requests\AlarmData;

use App\Http\Requests\BaseFormRequest;

class AlarmDataRequest extends BaseFormRequest
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
            'menu_no' => [
                'nullable',
                'string',
            ],
            'ad_title' => [
                'nullable',
                'string',
                'max:255'
            ],
            'ad_content' => [
                'nullable',
                'string',
                'max:255'
            ],
            'ad_must_yn' => [
                'nullable',
                'string',
                'max:1'
            ],
            'ad_use_yn' => [
                'nullable',
                'string',
                'max:1'
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'ad_category' => [
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
