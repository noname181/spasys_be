<?php

namespace App\Http\Requests\Push;

use App\Http\Requests\BaseFormRequest;

class PushtUpdateRequest extends BaseFormRequest
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
                'required',
                'int',
            ],
            'push_title' => [
                'required',
                'string'
            ],
            'push_content' => [
                'required',
                'string'
            ],
            'push_time' => [
                'required',
                'string'
            ],
            'push_must_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'push_use_yn' => [
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
