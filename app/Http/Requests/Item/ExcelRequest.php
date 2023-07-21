<?php

namespace App\Http\Requests\Item;

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
                'required',
                'max:255',
            ],
            'E' => [
                'nullable',
                'max:255',
            ],
            'F' => [
                'nullable',
            ],
            'G' => [
                'max:255',
                'nullable',
            ],
            'H' => [
                'max:255',
                'nullable',
            ],
            'I' => [
                'max:255',
                'nullable',
            ],
            'J' => [
                'integer',
                'nullable',
            ],
            'K' => [
                'integer',
                'nullable',
            ],
            'L' => [
                'integer',
                'nullable',
            ],
            'M' => [
                'integer',
                'nullable',
            ],
            'N' => [
                'integer',
                'nullable',
            ],
            'O' => [
                'nullable',
                'integer',
            ],
            'P' => [
                'nullable',
                'max:255',
            ],
            'Q' => [
                'nullable',
                'max:255',
            ],
            'R' => [
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
