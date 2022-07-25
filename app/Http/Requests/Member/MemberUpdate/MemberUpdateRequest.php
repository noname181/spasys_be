<?php

namespace App\Http\Requests\Member\MemberUpdate;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemberUpdateRequest extends BaseFormRequest
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
        Log::error($this->user);
        return [
            'mb_email' => [
                'required',
                'string',
                'max:255',
                'email',
                'unique:member,mb_email,'.Auth::user()->mb_no.',mb_no'
            ],
            'mb_tel' => [
                'string',
                'max:255',
                'regex:/^\(\+[0-9]{2}\) [0-9]{2}-[0-9]{4}-[0-9]{4}$/'
            ],
            'mb_hp' => [
                'required',
                'string',
                'max:255',
                'regex:/[0-9]{3}-[0-9]{4}-[0-9]{4}$/',
                'unique:member,mb_hp,'.Auth::user()->mb_no.',mb_no'
            ],
            'mb_push_yn' => [
                'required',
                'string',
                'max:1'
            ],
            'mb_service_no_array' => [
                'required',
                'string'
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
        return [
        ];
    }
}
