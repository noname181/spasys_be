<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateData extends Model
{
    use HasFactory;

    protected $table = "rate_data";


    protected $primaryKey = 'rd_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rd_no',
        'mb_no',
        'rd_cate_meta1',
        'rd_cate_meta2',
        'rd_cate1',
        'rd_cate2',
        'rd_cate3',
        'rd_data1',
        'rd_data2',
        'rd_data3'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

}