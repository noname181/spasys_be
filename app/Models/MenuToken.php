<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use App\Models\Manual;

class MenuToken extends Model
{
    use HasFactory;

    protected $table = "menu_token";

    protected $primaryKey = 'mt_no';

    public $timestamps = false;

    protected $attributes = ['service_name_array'];

    protected $appends = ['service_name_array'];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'mt_no',
        'role_no',
        'mt_token',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];



}
