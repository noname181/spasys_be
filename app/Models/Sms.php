<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Sms extends Model
{
    use HasFactory;

    protected $table = "sms";

    protected $primaryKey = 'sms_no';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'sms_no',
        'mb_no',
        'userKey',
        'callback',
        'receiverTelNo',
        'contents',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

}
