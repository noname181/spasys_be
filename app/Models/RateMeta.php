<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RateMeta extends Model
{
    use HasFactory;

    protected $table = "rate_meta";


    protected $primaryKey = 'rm_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'rm_no',
        'mb_no',
        'rm_biz_name',
        'rm_owner_name',
        'rm_biz_number',
        'rm_biz_email',
        'rm_biz_address',
        'rm_biz_address_detail',
        'rm_name',
        'rm_hp'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d",
        'updated_at' => "date:Y.m.d",
    ];

}
