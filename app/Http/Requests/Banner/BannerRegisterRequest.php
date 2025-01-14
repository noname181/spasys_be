<?php

namespace App\Http\Requests\Banner;

use App\Http\Requests\BaseFormRequest;

class BannerRegisterRequest extends BaseFormRequest
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
            'banner_start' => [
                '',
            ],
            'banner_end' => [
                '',
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
            'banner_position' => [
                'required',
                'string',
                'max:255',
            ],
            'banner_position_detail' => [
                '',
            ],
            'banner_link1' => [
                '',
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
