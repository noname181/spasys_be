<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    protected $table = "t_export";

    protected $primaryKey = 'te_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
         'te_no',
         "te_status",
         "te_logistic_manage_number",
         "te_e_confirm_number",
         "te_e_confirm_type",
         "te_e_confirm_date",
         "te_carry_in_number",
         "te_carry_out_number",
         "te_register_id",
         "te_e_date",
         "te_e_time",
         "te_e_order",
         "te_m_bl",
         "te_h_bl",
         "te_e_division_type",
         "te_e_packing_type",
         "te_e_number",
         "te_e_weight",
         "te_e_weight_unit",
         "te_e_type",
         "te_e_do_number",
         "te_e_price",
         "te_co_license",
         "te_logistic_type",
         'created_at',
         'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'te_e_date' => "date:Y.m.d",
        'te_e_confirm_date' => "date:Y.m.d",
    ];

}