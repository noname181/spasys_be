<?php

namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseFormRequest;

class BannerSearchRequest extends BaseFormRequest
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
            'from_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'to_date' => [
                'string',
                'date_format:Y-m-d'
            ],
            'search_string' => [
                '',
            ],
            'banner_position' => [
                '',
            ],
            'banner_use_yn' => [
                '',
            ],
            'per_page' => [
                'nullable',
                'int',
            ],
            'page' => [
                'nullable',
                'int',
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
