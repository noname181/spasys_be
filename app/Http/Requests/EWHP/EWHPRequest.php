<?php

namespace App\Http\Requests\EWHP;

use App\Http\Requests\BaseFormRequest;

class EWHPRequest extends BaseFormRequest
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
            'import' => [
                'array',
                'nullable'
            ],
            "import.*.status" => [
                'nullable',
                'max:255',
            ],
            "import.*.logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.carry_in_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.register_id" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_date" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_time" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_report_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_division_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_order" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.m_bl" => [
                'nullable',
                'max:255',
            ],
            "import.*.h_bl" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_report_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_packing_type" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_number" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_weight" => [
                'nullable',
                'max:255',
            ],
            "import.*.i_weight_unit" => [
                'nullable',
                'max:255',
            ],
            "import.*.co_license" => [
                'nullable',
                'max:255',
            ],
            "import.*.logistic_type" => [
                'nullable',
                'max:255',
            ],

            "export" => [
                'array',
                'nullable'
            ],
            "export.*.status" => [
                'nullable',
                'max:255',
            ],
            "export.*.logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_confirm_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_confirm_date" => [
                'nullable',
                'max:255',
            ],
            "export.*.carry_in_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.carry_out_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.register_id" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_date" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_time" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_order" => [
                'nullable',
                'max:255',
            ],
            "export.*.m_bl" => [
                'nullable',
                'max:255',
            ],
            "export.*.h_bl" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_division_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_packing_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_weight" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_weight_unit" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_type" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_do_number" => [
                'nullable',
                'max:255',
            ],
            "export.*.e_price" => [
                'nullable',
                'max:255',
            ],
            "export.*.co_license" => [
                'nullable',
                'max:255',
            ],
            "export.*.logistic_type" => [
                'nullable',
                'max:255',
            ],

            "import_expected" => [
                'array',
                'nullable'
            ],

            'import_expected.*.status' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.logistic_manage_number' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.register_id' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_date' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_ship' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.co_license' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_cargo_eng' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_number' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_weight' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_weight_unit' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.m_bl' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.h_bl' => [
                'nullable',
                'max:255',
            ],
            'import_expected.*.is_name_eng' => [
                'nullable',
                'max:255',
            ],
            
            "export_confirm" => [
                'array',
                'nullable'
            ],

            "export_confirm.*.status" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.logistic_manage_number" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.ec_confirm_number" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.ec_type" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.ec_date" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.register_id" => [
                'nullable',
                'max:255',
            ],
            "export_confirm.*.ec_number" => [
                'nullable',
                'max:255',
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
