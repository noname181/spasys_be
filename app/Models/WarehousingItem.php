<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehousing;
use App\Models\Item;

class WarehousingItem extends Model
{
    use HasFactory;

    protected $table = "warehousing_item";

    protected $primaryKey = 'wi_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'wi_no',
        'item_no',
        'w_no',
        'wi_number',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function w_no()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no');
    }

    public function item_no()
    {
        return $this->belongsTo(Item::class, 'item_no', 'item_no');
    }
}