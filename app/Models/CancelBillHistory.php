<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Member;

class CancelBillHistory extends Model
{
    use HasFactory;

    protected $table = "cancel_bill_history";

    protected $primaryKey = 'cbh_no';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'cbh_no',
        'rgd_no',
        'mb_no',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i:s",
        'updated_at' => "date:Y.m.d H:i",
    ];
    public function member()
    {
        return $this->belongsTo(Member::class, 'mb_no', 'mb_no')->with('company');
    }

    public function rgd()
    {
        return $this->hasOne(ReceivingGoodsDelivery::class, 'rgd_no', 'rgd_no')->select(['rgd_no', 'rgd_settlement_number', 'rgd_status4']);
    }
}
