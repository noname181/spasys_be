<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportConfirm extends Model
{
    use HasFactory;

    protected $table = "t_export_confirm";

    protected $primaryKey = 'tec_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
         'ti_no',
         "ti_status",
         "ti_logistic_manage_number",
         "ti_ec_confirm_number",
         "ti_ec_type",
         "ti_ec_date",
         "ti_register_id",
         "ti_ec_number",
         'created_at',
         'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

}
