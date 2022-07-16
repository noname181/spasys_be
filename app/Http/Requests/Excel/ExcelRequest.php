<?php

namespace App\Http\Requests\Excel;

use App\Http\Requests\BaseFormRequest;

class ExcelRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public static function rules()
    {
        return [
            'A' => [
                'required',
                'string',
                'max:45',
            ],
            'B' => [
                'required',
                'string',
                'max:45',
            ],
            'C' => [
                'required',
                'string',
                'max:45',
            ],
            'D' => [
                'required',
                'string',
                'max:45',
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
