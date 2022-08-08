<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomsInfo extends Model
{
    use HasFactory;

    protected $table = "customs_info";

    protected $primaryKey = 'ci_no';

    public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ci_no',
        'co_no',
        'mb_no',
        'ci_address',
        'ci_address_detail',
        'ci_post_number',
        'ci_name',
        'ci_tel'
    ];
}
