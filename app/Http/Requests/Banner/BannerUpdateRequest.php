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
            'banner_no' => [
                'required',
                'integer',
            ],
            'banner_title' => [
                'required',
                'string',
                'max:255',
            ],
            // 'banner_lat' => [
            //     'required',
            //     'numeric',
            //     'regex:/^[-]?[(\d+)]{1,4}(\.[(\d+)]{1,7})?$/'
            // ],
            // 'banner_lng' => [
            //     'required',
            //     'numeric',
            //     'regex:/^[-]?[(\d+)]{1,4}(\.[(\d+)]{1,7})?$/'
            // ],
            'banner_position' => [
                'required',
                'string',
                'max:255',
            ],
            'banner_position_detail' => [
                '',
            ],
            'banner_start' => [
                'string',
                'date_format:Y-m-d',
            ],
            'banner_end' => [
                'string',
                'date_format:Y-m-d',
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
            'banner_link1' => [
                'required',
                'string',
                'max:255',
            ],
            'banner_link2' => [

                'max:255',
            ],
            'banner_link3' => [

                'max:255',
            ],
            'bannerFiles1' => [
                '',
            ],
            'bannerFiles1.*' => [
                'file'
            ],
            'bannerFiles2' => [
                '',
            ],
            'bannerFiles2.*' => [
                'file'
            ],
            'bannerFiles3' => [
                '',
            ],
            'bannerFiles3.*' => [
                'file'
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
