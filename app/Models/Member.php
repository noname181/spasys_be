<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Helper\Tokenable;
use App\Models\Company;
use App\Models\Role;

class Member extends Authenticatable
{
    use HasFactory, Notifiable, Tokenable;

    const ROLE_ADMIN = 1;
    const ROLE_SPASYS_ADMIN = 2;
    const ROLE_SPASYS_MANAGER = 3;
    const ROLE_SPASYS_OPERATOR = 4;
    const ROLE_SPASYS_WORKER = 5;
    const ROLE_SHOP_MANAGER = 6;
    const ROLE_SHOP_OPERATOR = 7;
    const ROLE_SHIPPER_MANAGER = 8;
    const ROLE_SHIPPER_OPERATOR = 9;

    const ADMIN = 'admin';
    const SPASYS = 'spasys';
    const SHOP = 'shop';
    const SHIPPER = 'shipper';

    protected $table = "member";

    protected $primaryKey = 'mb_no';

    protected $attributes = ['mb_services'];

    protected $appends = ['mb_services'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'mb_pw',
        'mb_token',
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'mb_id',
        'role_no',
        'mb_name',
        'mb_email',
        'mb_pw',
        'mb_pw_update_time',
        'mb_language',
        'mb_use_yn',
        'mb_push_yn',
        'mb_hp',
        'mb_tel',
        'mb_note',
        'warehouse_code'
    ];

    protected $casts = [
        'created_at' => "date:Y.m.d H:i",
        'updated_at' => "date:Y.m.d H:i",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'co_no', 'co_no')->with('co_parent');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_no', 'role_no')->with('menu_token');
    }

    public function getMbServicesAttribute()
    {
        $service_no_array = "";
        if(!empty($this->company)){
            $service_no_array = $this->company->co_service;
            if($this->company->co_type == 'spasys'){
                $service_array = Service::where('service_use_yn', 'y')->get();
                return $service_array;
            }
        }
        $service_no_array = explode(" ", $service_no_array);
        $service_array = [];
        $service_array[] = Service::where('service_no','=', '1')->first();
        foreach($service_no_array as $service_name){
            $service = Service::where('service_name', $service_name)->first();
            if($service){
                $service_array[] = $service;
            }
        }
    
        return $service_array;
    }
}
