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
        't_startdate',
        't_type',
        'co_no_parent',
        'co_no_shipper',
        't_regtime',
        't_modify',
        'tid_sum',
        'tid_vat',
        'co_license',
        'co_owner',
        'co_name',
        'co_major',
        'co_email',
        'co_email2',
        'co_address',
        'co_address2',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}
