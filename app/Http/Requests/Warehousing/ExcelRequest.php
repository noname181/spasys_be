<?php

namespace App\Http\Requests\Warehousing;

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
                'nullable',
                'max:255',
            ],
            'B' => [
                'nullable',
                'max:255',
            ],
            'C' => [
                'nullable',
                'max:255',
            ],
            'D' => [
                'required',
                'max:255',
            ],
            'E' => [
                'nullable',
                'max:255',
            ],
            'F' => [
                'nullable',
                'max:255',
            ],
            'G' => [
                'nullable',
                'max:255',
            ],
            'H' => [
                'nullable',
                'max:255',
            ],
            'I' => [
                'required',
                'max:255',
                'date_format:Y.m.d'
            ],
            'J' => [
                'required',
                'integer',
            ],
            'K' => [
                'nullable',
                'max:255',
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
