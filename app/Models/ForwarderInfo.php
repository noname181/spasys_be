<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForwarderInfo extends Model
{
    use HasFactory;

    protected $table = "forwarder_info";

    protected $primaryKey = 'fi_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'fi_no',
        'co_no',
        'mb_no',
        'fi_address',
        'fi_address_detail',
        'fi_post_number',
        'fi_name',
        'fi_tel'
    ];
}
