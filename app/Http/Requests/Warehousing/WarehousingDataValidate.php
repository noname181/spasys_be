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
    public function rules()
    {
        return [
            'A' => [
                'required',
                'integer',
            ],
            'B' => [
                'required',
                'max:255',
            ],
            'C' => [
                'required',
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
                'required',
                'max:255',
            ],
            'G' => [
                'required',
                'max:255',
            ],
            'H' => [
                'required',
                'max:255',
            ],
            'I' => [
                'required',
                'integer',
            ]
        ];
    }
}
