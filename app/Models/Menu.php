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

    protected $attributes = ['service_name_array'];

    protected $appends = ['service_name_array'];

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

    public function getServiceNameArrayAttribute()
    {
        $service_no_array = $this->service_no_array;
        $service_no_array = explode(" ", $service_no_array);
        $service_array = [];

        foreach($service_no_array as $service_no){
            $service = Service::where('service_no', $service_no)->first();
            if($service){
                $service_array[] = $service->service_name;
            }else {
                $service_array[] = "";
            }
            
        }

        return implode("/",$service_array);
    }

}
