<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\BaseFormRequest;

class RoleRequest extends BaseFormRequest
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
            'roles' => [
                'array',
            ],
            'roles.*.role_no' => [
                'int',
                'nullable'
            ],
            'roles.*.role_name' => [
                'required',
                'max:255'
            ],
            'roles.*.role_eng' => [
                'required',
                'max:255'
            ],
            'roles.*.role_use_yn' => [
                'required',
                'string',
                'max:1'
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
