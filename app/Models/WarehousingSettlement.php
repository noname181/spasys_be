<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class WarehousingSettlement extends Model
{
    use HasFactory;

    protected $table = "warehousing_settlement";

    protected $primaryKey = 'wse_no';

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
        'wse_no',
        'mb_no',
        'w_no',
        'w_no_settlement',
        'w_amount',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function warehousing()
    {
        return $this->belongsTo(Warehousing::class, 'w_no', 'w_no');
    }

    public function warehousing_settlement()
    {
        return $this->belongsTo(Warehousing::class, 'w_no_settlement', 'w_no');
    }

}
