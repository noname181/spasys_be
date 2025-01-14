<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class StockHistory extends Model
{
    use HasFactory;

    protected $table = "stock_history";

    protected $primaryKey = 'sh_no';

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
        'sh_no',
        'mb_no',
        'sh_date',
        'sh_left_stock',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }
}
