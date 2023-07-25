<?php

namespace App\Http\Requests\Warehousing;

use Illuminate\Foundation\Http\FormRequest;

class WarehousingDataValidate extends FormRequest
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
                'integer',
            ],
            'B' => [
                'required',
                'max:255',
            ],
            'C' => [
                'nullable',
                'max:255',
            ],
            'D' => [
                'nullable',
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
                'nullable',
                'integer',
            ],
            'J' => [
                'required',
                'integer',
            ],
            'K' => [
                'nullable',
                'max:255',
                'date_format:Y.m.d'
            ]
            
        ];
    }
}
