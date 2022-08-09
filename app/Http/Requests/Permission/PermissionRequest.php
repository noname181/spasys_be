<?php

namespace App\Http\Requests\Permission;

use App\Http\Requests\BaseFormRequest;

class PermissionRequest extends BaseFormRequest
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
            'menu_device' => [
                'nullable',
                'string',
            ],
            'role_no' => [
                'nullable',
                'integer',
            ],
            'service_no' => [
                'nullable',
                'integer',
            ],
            'menu' => [
                'nullable',
                'array',
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
