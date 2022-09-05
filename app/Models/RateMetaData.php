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
        'set_type',
        'w_no',
        'co_no'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

    public function rate_meta() {
        return $this->hasOne(RateMeta::class, 'rm_no', 'rm_no');
    }

    public function company() {
        return $this->hasOne(Company::class, 'co_no', 'co_no')->with(['contract', 'co_parent']);
    }

    public function member() {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no')->with('company:co_no,co_name,co_service');
    }

}
