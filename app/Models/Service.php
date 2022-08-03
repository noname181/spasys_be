<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $table = "service";

    protected $primaryKey = 'service_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'service_no',
        'mb_no',
        'service_name',
        'service_eng',
        'service_use_yn',
        'service_regtime',
    ];
}
