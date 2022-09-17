<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Item;
use App\Models\Warehousing;
use App\Models\WarehousingItem;
use App\Models\Member;


class Alarm extends Model
{
    use HasFactory;

    protected $table = "alarm";

    protected $primaryKey = 'alarm_no';

    public $timestamps = true;

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    // public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'alarm_no',
        'mb_no',
        'w_no',
        'alarm_content',
    ];
    // public function item()
    // {
    //     return $this->hasOne(Item::class, 'item_no', 'item_no')->with(['company']);
    // }
    public function warehousing()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no')->with(['warehousing_item']);;
    }
    public function member()
    {
        return $this->hasOne(Member::class, 'mb_no', 'mb_no');;
    }
}
