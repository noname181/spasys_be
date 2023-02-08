<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoConnect extends Model
{
    use HasFactory;

    protected $table = "cargo_connect";

    protected $primaryKey = 'connect_no';

    public $timestamps = true;

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    // public $timestamps = true;
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'connect_no',
        'w_no',
        'is_no',
        'ss_no',
        'type'
    ];
}
