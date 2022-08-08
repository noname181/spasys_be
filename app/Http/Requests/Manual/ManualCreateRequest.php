<?php

namespace App\Http\Requests\Manual;

use App\Http\Requests\BaseFormRequest;

class ManualCreateRequest extends BaseFormRequest
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
            'man_title' => [
                'required',
                'string',
                'max:255',
            ],
            'man_content' => [
                'nullable',
                'string'
            ],
            'man_note' => [
                'required',
                'string',
                'max:255',
            ],
            'file' => [
                'required',
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
