<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateDataSendMeta extends Model
{
    use HasFactory;

    protected $table = "rate_data_send_meta";


    protected $primaryKey = 'rdsm_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rdsm_no',
        'mb_no',
        'rdsm_biz_name',
        'rdsm_owner_name',
        'rdsm_biz_number',
        'rdsm_biz_email',
        'rdsm_biz_address',
        'rdsm_biz_address_detail',
        'rdsm_name',
        'rdsm_hp'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

}
