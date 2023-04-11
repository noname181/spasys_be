<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlarmData extends Model
{
    use HasFactory;

    protected $table = "alarm_data";


    protected $primaryKey = 'ad_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ad_no',
        'ad_title', 
        'ad_content',
        'ad_time',
        'ad_must_yn',
        'ad_use_yn',
        'mb_no'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];
}
