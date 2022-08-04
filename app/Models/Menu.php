<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;

class Menu extends Model
{
    use HasFactory;

    protected $table = "menu";

    protected $primaryKey = 'menu_no';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'menu_no',
        'mb_no', 
        'menu_name',
        'menu_depth',
        'menu_parent_no',
        'menu_url',
        'menu_device',
        'menu_use_yn',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function service()
    {
        return $this->hasOne(Service::class, 'service_no', 'service_no');
    }

}
