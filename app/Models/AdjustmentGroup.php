<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentGroup extends Model
{
    use HasFactory;

    protected $table = "adjustment_group";

    protected $primaryKey = 'ag_no';

    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'ag_no', 
        'co_no',
        'mb_no',
        'ag_name',
        'ag_manager',
        'ag_email',
        'ag_regtime',
        'ag_hp',
    ];
}
