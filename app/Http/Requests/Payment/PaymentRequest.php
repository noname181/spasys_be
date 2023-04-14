<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\BaseFormRequest;

class PaymentRequest extends BaseFormRequest
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
            'rgd_no' => [
                '',
            ],
            'mb_no' => [
                '',
            ],
            'p_price' => [
                '',
            ],
            'p_success_yn' => [
                '',
            ],
            'p_cancel_yn' => [
                '',
            ],
            'p_cancel_time' => [
                '',
            ],
            'p_method' => [
                '',
            ],
            'p_method_name' => [
                '',
            ],

            'p_method_number' => [
                '',
            ],
            'p_method_key' => [
                '',
            ],
            'p_method_fee' => [
                '',
            ],
            'p_card_name' => [
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
