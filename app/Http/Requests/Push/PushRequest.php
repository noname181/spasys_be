<?php

namespace App\Http\Requests\Push;

use App\Http\Requests\BaseFormRequest;

class PushRequest extends BaseFormRequest
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
                'int',
            ],
            'push_title' => [
                'nullable',
                'string',
                'max:255'
            ],
            'push_content' => [
                'nullable',
                'string',
                'max:255'
            ],
            'push_must_yn' => [
                'nullable',
                'string',
                'max:1'
            ],
            'push_use_yn' => [
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
