<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Payment extends Model
{
    use HasFactory;

    protected $table = "payment";

    protected $primaryKey = 'p_no';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'p_no',
        'rgd_no',
        'mb_no',
        'p_price',
        'p_success_yn',
        'p_cancel_time',
        'p_method',
        'p_method_name',
        'p_method_number',
        'p_method_key',
        'p_method_fee',
        'p_card_name',
        'created_at',
        'updated_at',
        'p_resultmgs',
        'p_orderno',
        'p_amount',
        'p_tid',
        'p_acceptdate',
        'p_acceptno',
        'p_cardname',
        'p_accountno',
        'p_receivername',
        'p_depositenddate',
        'p_cardcode',
        'p_cardno',
        'p_cancel_tid',
        'p_cancel_amount',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

}
