<?php

namespace App\Http\Requests\Menu;

use App\Http\Requests\BaseFormRequest;

class MenuSearchRequest extends BaseFormRequest
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
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
            ],
            'service_no' => [
                'int',
                'nullable',
            ],
            'menu_depth' => [
                'string',
                'nullable',
            ],
            'menu_name' => [
                'string',
                'nullable',
            ],
            'menu_device' => [
                'string',
                'nullable',
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
