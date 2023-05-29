<?php

namespace App\Http\Requests\Manual;

use App\Http\Requests\BaseFormRequest;

class ManualUpdateRequest extends BaseFormRequest
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
            'data_menu' => [
                'array'
            ],
            'man_title' => [
                
                'string',
                'max:255',
            ],
            'man_content' => [
                'nullable',
                'string'
            ],
            'man_note' => [
                'nullable',
                'string',
                'max:255',
            ],
            "delete_image" => [
                'array',
                'nullable'
            ],
            'file.*' => [
                'nullable',
                'file',
                'max:5000',
                'mimes:jpg,jpeg,png',
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
