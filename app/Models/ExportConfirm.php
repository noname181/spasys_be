<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class ExportConfirm extends Model
{
    use HasFactory;

    protected $table = "t_export_confirm";

    protected $primaryKey = 'tec_no';

        public $timestamps = true;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->timezone('Asia/seoul')->format('Y-m-d H:i:s');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
         'tec_no',
         "tec_status",
         "tec_status_2",
         "tec_logistic_manage_number",
         "tec_ec_confirm_number",
         "tec_ec_type",
         "tec_ec_date",
         "tec_ec_number",
         "tec_register_id",
         "tec_number",
         'created_at',
         'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
        'tec_ec_date' => "date:Y.m.d",
    ];

    public function export()
    {
        return $this->hasOne(Export::class, 'te_logistic_manage_number', 'tie_logistic_manage_number');
    }
}
