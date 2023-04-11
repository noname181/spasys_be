<?php

namespace App\Http\Requests\AlarmData;

use App\Http\Requests\BaseFormRequest;

class AlarmDataRegisterRequest extends BaseFormRequest
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
            'ad_category' => [
                'required',
                'string',
            ],
            'ad_title' => [
                'required',
                'string',
                'max:255'
            ],
            'ad_content' => [
                'required',
                'string',
                'max:255'
            ],
            'ad_time' => [
                'required',
                'string',
                'max:255'
            ],
            'ad_must_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'ad_use_yn' => [
                'required',
                'string',
                'max:1'
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
