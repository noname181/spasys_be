<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class COAddress extends Model
{
    use HasFactory;

    protected $table = "co_address";

    protected $primaryKey = 'ca_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ca_no',
        'co_no',
        'mb_no', 
        'ca_address',
        'ca_address_detail',
        'ca_post_number',
        'ca_name',
        'ca_tel'
    ];
}
