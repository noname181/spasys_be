<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $table = "package";


    protected $primaryKey = 'p_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'p_no',
        'w_no',
        'wr_contents',
        'wr_type',
        'note',
        'order_number',
        'pack_type',
        'quantity',
        'reciever',
        'reciever_address',
        'reciever_contract',
        'reciever_detail_address',
        'sender',
        'sender_address',
        'sender_contract',
        'sender_detail_address',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

}
