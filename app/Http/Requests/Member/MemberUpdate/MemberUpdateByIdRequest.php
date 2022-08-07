<?php

namespace App\Http\Requests\Member\MemberUpdate;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemberUpdateByIdRequest extends BaseFormRequest
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
            'mb_no' => [
                'required',
                'integer',
                'max:255',
            ],
            'co_no' => [
                'required',
                'integer',
            ],
            'role_no' => [
                'required',
                'string',
            ],
            'mb_email' => [
                'required',
                'string',
                'max:255',
                'email',
            ],
            'mb_name' => [
                'required',
                'string',
                'max:255',
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
