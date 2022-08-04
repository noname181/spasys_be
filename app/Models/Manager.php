<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    use HasFactory;

    protected $table = "manager";

    protected $primaryKey = 'm_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'm_no', 
        'co_no',
        'mb_no',
        'm_position',
        'm_name',
        'm_duty',
        'm_hp',
        'm_email',
        'm_etc',
    ];
}
