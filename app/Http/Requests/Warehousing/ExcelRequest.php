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
                '',
                'max:255',
            ],
            'B' => [
                '',
                'max:255',
            ],
            'C' => [
                '',
                'max:255',
            ],
            'D' => [
                'required',
                'max:255',
            ],
            'E' => [
                'required',
                'max:255',
            ],
            'F' => [
                '',
                'max:255',
            ],
            'G' => [
                '',
                'max:255',
            ],
            'H' => [
                '',
                'max:255',
            ],
            'I' => [
                'required',
                'max:255',
                'date_format:Y.m.d'
            ],
            'J' => [
                'required',
                'max:255',
            ],
            'K' => [
                '',
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
