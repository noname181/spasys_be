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
        'rm_no',
        'co_no',
        'w_no',
        'rd_co_no',
        'rmd_no',
        'rd_cate_meta1',
        'rd_cate_meta2',
        'rd_cate1',
        'rd_cate2',
        'rd_cate3',
        'rd_data1',
        'rd_data2',
        'rd_data3',
        'rd_data4',
        'rd_data5',
        'rd_data6',
        'rd_data7',
        'rd_data8',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];
    
    public function w_import_parent()
    {
        return $this->belongsTo(Warehousing::class, 'w_import_no', 'w_no');
    }
}
