<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Tax extends Model
{
    use HasFactory;

    protected $table = "tax";

    protected $primaryKey = 't_no';

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
        't_no',
        'b_no',
        't_mgtnum',
        'rgd_no',
        't_startdate',
        't_type',
        'co_no_parent',
        'co_no_shipper',
        't_regtime',
        't_modify',
        't_taxtxt',
        't_taxcode',
        't_status',
        't_result_sendtime',
        't_result_regtime',
        't_result_no',
        't_amount',
        't_tax',
        't_total',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}
