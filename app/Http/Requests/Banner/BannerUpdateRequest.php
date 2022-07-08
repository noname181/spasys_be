<?php

namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseFormRequest;

class BannerUpdateRequest extends BaseFormRequest
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
            'banner_title' => [
                'required',
                'string',
                'max:255',
            ],
            'banner_lat' => [
                'required',
                'numeric',
            ],
            'banner_lng' => [
                'required',
                'numeric',
            ],
            'banner_start' => [
                'required',
                'date',
            ],
            'banner_end' => [
                'required',
                'date',
            ],
            'banner_use_yn' => [
                'required',
                'string',
                'max:1',
            ],
            'banner_sliding_yn' => [
                'required',
                'string',
                'max:1',
            ],
            'mb_no' => [
                'required',
                'integer',
                'max:255',
                'exists:member,mb_no'
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
