<?php

namespace App\Http\Requests\Import;

use App\Http\Requests\BaseFormRequest;

class ImportRequest extends BaseFormRequest
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
                'nullable',   
            ],
            'type' => [
                '',
            ],
            'items' => [
                '',
            ],
            'page_type' => [
                '',
            ],
            'w_category_name' => [
                '',
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
