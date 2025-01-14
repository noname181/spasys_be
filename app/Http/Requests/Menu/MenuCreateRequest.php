<?php

namespace App\Http\Requests\Menu;

use App\Http\Requests\BaseFormRequest;

class MenuCreateRequest extends BaseFormRequest
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
            'menu_name' => [
                'required',
                'string',
                'max:255',
            ],
            'menu_depth' => [
                'required',
                'string',
                'max:255',
            ],
            'menu_parent_no' => [
                'nullable',
                'integer',
            ],
            'menu_url' => [
                '',
            ],
            'menu_device' => [
                'required',
                'string',
                'max:255',
            ],
            'menu_use_yn' => [
                'required',
                'string',
                'max:1',
            ],
            'menu_service_no_array' => [
                'nullable',
                'string',
                'max:255',
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
