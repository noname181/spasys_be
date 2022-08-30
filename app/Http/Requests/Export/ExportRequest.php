<?php

namespace App\Http\Requests\Export;

use App\Http\Requests\BaseFormRequest;

class ExportRequest extends BaseFormRequest
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
    public static function rules()
    {
        return [
            'w_no' => [
                '',
                
            ],
            'type' => [
                '',
            ],
            'page' => [
                '',
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
