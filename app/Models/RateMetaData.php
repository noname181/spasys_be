<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateMetaData extends Model
{
    use HasFactory;

    protected $table = "rate_meta_data";


    protected $primaryKey = 'rmd_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rmd_no',
        'mb_no',
        'rm_no',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function rate_meta() {
        return $this->hasOne(RateMeta::class, 'rm_no', 'rm_no');
    }

}
