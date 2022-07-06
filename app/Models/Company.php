<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = "company";


    protected $primaryKey = 'co_no';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'co_no',
        'mb_no', 
        'co_name',
        'co_country',
        'co_service',
        'co_license',
        'co_owner',
        'co_close_yn',
        'co_homepage',
        'co_email',
        'co_etc'
    ];
}
