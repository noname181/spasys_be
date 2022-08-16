<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;
use App\Models\Company;

class Warehousing extends Model
{
    use HasFactory;

    protected $table = "warehousing";

    protected $primaryKey = 'w_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'w_no',
        'w_schedule_number',
        'mb_no',
        'co_no',
        'w_schedule_day',
        'w_schedule_amount',
        'w_amount',
        'm_bl',
        'h_bl',
        'logistic_manage_number',
        'connection_number',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function mb_no()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');
    }

    public function co_no()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no');
    }
}
